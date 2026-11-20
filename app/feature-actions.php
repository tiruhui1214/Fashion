<?php
// Included only after login_required() and check_csrf() in actions.php.
if($action==='profile'){
    $name=text_input('name',100);$email=strtolower(text_input('email',190));$phone=validated_phone(scalar_value($_POST,'phone'),true);
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
    if(one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$uid]))throw new RuntimeException('Email is already in use.');
    query('UPDATE users SET name=?,email=?,phone=? WHERE id=?',[$name,$email,$phone,$uid]);flash('Profile updated.');go('account');
}
if($action==='address_save'){
    $id=integer_input('id',0);$a=address_values($_POST);
    db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
    if($id&&!one('SELECT id FROM addresses WHERE id=? AND user_id=?',[$id,$uid]))throw new RuntimeException('Address not found.');
    if(!one('SELECT id FROM addresses WHERE user_id=?',[$uid]))$a['is_default']=1;
    if($a['is_default'])query('UPDATE addresses SET is_default=0 WHERE user_id=?',[$uid]);
    $params=array_values($a);
    if($id)query('UPDATE addresses SET label=?,recipient=?,phone=?,line1=?,line2=?,city=?,state=?,postcode=?,country=?,is_default=? WHERE id=? AND user_id=?',array_merge($params,[$id,$uid]));
    else query('INSERT INTO addresses(label,recipient,phone,line1,line2,city,state,postcode,country,is_default,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?)',array_merge($params,[$uid]));
    db()->commit();flash('Address saved.');go('account');
}
if($action==='address_delete'){
    $id=integer_input('id',1);db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
    query('DELETE FROM addresses WHERE id=? AND user_id=?',[$id,$uid]);
    if(!one('SELECT id FROM addresses WHERE user_id=? AND is_default=1',[$uid])){
        $first=one('SELECT id FROM addresses WHERE user_id=? ORDER BY id LIMIT 1',[$uid]);if($first)query('UPDATE addresses SET is_default=1 WHERE id=?',[$first['id']]);
    }
    db()->commit();flash('Address removed.');go('account');
}
if($action==='cart_select'){
    $ids=$_POST['selected']??[];if(!is_array($ids))throw new RuntimeException('Invalid cart selection.');
    $ids=array_values(array_unique(array_map(function($id){if(!is_scalar($id)||!ctype_digit((string)$id))throw new RuntimeException('Invalid cart selection.');return (int)$id;},$ids)));
    db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
    $own=array_map('intval',array_column(all('SELECT id FROM cart_items WHERE user_id=?',[$uid]),'id'));
    if(array_diff($ids,$own))throw new RuntimeException('Cart item not found.');
    query('UPDATE cart_items SET is_selected=0 WHERE user_id=?',[$uid]);
    if($ids)query('UPDATE cart_items SET is_selected=1 WHERE user_id=? AND id IN ('.implode(',',array_fill(0,count($ids),'?')).')',array_merge([$uid],$ids));
    db()->commit();flash('Selection updated.');go('cart');
}
if($action==='cart_update'){
    $id=integer_input('id',1);$quantity=integer_input('quantity',1,99);
    db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
    $r=one('SELECT c.id,v.stock,v.active variant_active,p.active FROM cart_items c JOIN variants v ON v.id=c.variant_id JOIN products p ON p.id=v.product_id WHERE c.id=? AND c.user_id=? FOR UPDATE',[$id,$uid]);
    if(!$r||!$r['active']||!$r['variant_active']||$quantity>$r['stock'])throw new RuntimeException('Requested quantity or product is unavailable.');
    query('UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?',[$quantity,$id,$uid]);db()->commit();flash('Bag updated.');go('cart');
}
if($action==='cart_delete'){
    db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);query('DELETE FROM cart_items WHERE id=? AND user_id=?',[integer_input('id',1),$uid]);db()->commit();flash('Item removed.');go('cart');
}
if($action==='promotion_apply'){
    $code=strtoupper(text_input('promo_code',40));$rows=selected_cart($uid);if(!$rows)throw new RuntimeException('Select at least one item before applying a code.');
    checkout_quote($rows,$code);$_SESSION['promo_code']=$code;flash('Promotion applied.');go('checkout');
}
if($action==='promotion_remove'){unset($_SESSION['promo_code']);flash('Promotion removed.');go('checkout');}
if($action==='checkout'){
    $key=scalar_value($_POST,'checkout_key');
    if(empty($_SESSION['checkout_key'])||!hash_equals($_SESSION['checkout_key'],$key))throw new RuntimeException('Checkout session expired. Refresh checkout.');
    if($oldOrder=one('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$key,$uid]))go('order',['id'=>$oldOrder['id']]);
    $address=checkout_address($uid,$_POST);
    db()->beginTransaction();$account=one('SELECT active FROM users WHERE id=? FOR UPDATE',[$uid]);
    if(!$account||!$account['active'])throw new RuntimeException('Account is inactive.');
    if($oldOrder=one('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$key,$uid])){db()->commit();go('order',['id'=>$oldOrder['id']]);}
    $rows=selected_cart($uid,true);if(!$rows)throw new RuntimeException('Select at least one item for checkout.');
    foreach($rows as $r)if(!$r['active']||$r['quantity']>$r['stock'])throw new RuntimeException('Stock changed for '.$r['name'].'. Update your bag.');
    $quote=checkout_quote($rows,(string)($_SESSION['promo_code']??''),true);
    if(!hash_equals(quote_signature($rows,$quote),scalar_value($_POST,'quote_signature')))throw new RuntimeException('Prices, selection or discount changed. Review the updated order total and confirm again.');
    if(scalar_value($_POST,'payment_result')!=='success')throw new RuntimeException('Simulated payment declined. No order was placed and no stock was deducted.');
    $reference='FM-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
    query('INSERT INTO orders(user_id,reference,recipient,phone,address,subtotal,shipping,total,checkout_key,promotion_id,promo_code_used,discount_amount,shipping_address_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)',[$uid,$reference,$address['recipient'],$address['phone'],address_snapshot($address),from_cents($quote['subtotal']),from_cents($quote['shipping']),from_cents($quote['total']),$key,$quote['promotion_id'],$quote['code']?:null,from_cents($quote['discount']),$address['id']]);
    $oid=(int)db()->lastInsertId();
    foreach($rows as $r){
        query('INSERT INTO order_items(order_id,variant_id,product_id,name,color,size,image,unit_price,quantity) VALUES(?,?,?,?,?,?,?,?,?)',[$oid,$r['variant_id'],$r['product_id'],$r['name'],$r['color'],$r['size'],$r['image'],$r['price'],$r['quantity']]);
        query('UPDATE variants SET stock=stock-? WHERE id=?',[$r['quantity'],$r['variant_id']]);
        query('DELETE FROM cart_items WHERE id=? AND user_id=?',[$r['id'],$uid]);
    }
    query('INSERT INTO payments(order_id,amount,payment_reference,paid_at) VALUES(?,?,?,?)',[$oid,from_cents($quote['total']),'SIM-'.strtoupper(bin2hex(random_bytes(8))),date('Y-m-d H:i:s')]);
    record_status($oid,$uid,null,'Confirmed');db()->commit();unset($_SESSION['promo_code']);flash('Your order is confirmed. This was a simulated payment.');go('order',['id'=>$oid]);
}
if($action==='review'){
    $pid=integer_input('product_id',1);$rating=integer_input('rating',1,5);$comment=text_input('comment',1500);
    if(!one("SELECT o.id FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.user_id=? AND i.product_id=? AND o.status='Completed'",[$uid,$pid]))throw new RuntimeException('Reviews are available after your order is completed.');
    // Editing does not undo moderation. A hidden review remains hidden until admin restores it.
    query('INSERT INTO reviews(user_id,product_id,rating,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment),customer_deleted=0',[$uid,$pid,$rating,$comment]);flash('Review saved.');go('product',['id'=>$pid]);
}
if($action==='assistant_message'||$action==='assistant_reset'){
    require_once __DIR__.'/recommendations.php';require_once __DIR__.'/assistant-service.php';
    if($action==='assistant_reset')assistant_reset_conversation();
    else assistant_send_message(text_input('message',600));
    go('home',['chat'=>'open']);
}
