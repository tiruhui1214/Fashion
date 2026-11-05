<?php
if($_SERVER['REQUEST_METHOD']!=='POST')return;
try {
 check_csrf();$action=(string)($_POST['action']??'');
 if(in_array($action,['register','login'],true)) {
  $email=strtolower(text_input('email',190));if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
  $password=(string)($_POST['password']??'');
  if($action==='register'){
   $name=text_input('name',100);if(strlen($password)<8||strlen($password)>72)throw new RuntimeException('Use a password between 8 and 72 characters.');
   if($password!==($_POST['confirm_password']??''))throw new RuntimeException('Passwords do not match.');
   if(one('SELECT id FROM users WHERE email=?',[$email]))throw new RuntimeException('This email is already registered. Please sign in.');
   query('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$u=['id'=>db()->lastInsertId()];
  }else{
   $u=one('SELECT * FROM users WHERE email=?',[$email]);if(!$u||!password_verify($password,$u['password_hash']))throw new RuntimeException('Email or password is incorrect.');
  }
  session_regenerate_id(true);$_SESSION['uid']=(int)$u['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));unset($_SESSION['checkout_key']);flash('Welcome to FORME.');go('account');
 }
 if($action==='logout'){$_SESSION=[];session_regenerate_id(true);flash('You have signed out.');go();}
 $u=login_required();$uid=(int)$u['id'];
 if($action==='profile'){
  $name=text_input('name',100);$email=text_input('email',190);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
  if(one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$uid]))throw new RuntimeException('Email is already in use.');
  query('UPDATE users SET name=?,email=? WHERE id=?',[$name,$email,$uid]);flash('Profile updated.');go('account');
 }
 if($action==='address_save'){
  $id=(int)($_POST['id']??0);$values=[text_input('label',60),text_input('recipient',100),text_input('phone',30),text_input('line1',255),text_input('city',100),text_input('postcode',10)];
  if(!preg_match('/^\d{5}$/',$values[5]))throw new RuntimeException('Enter a five-digit Malaysian postcode.');
  if($id){if(!one('SELECT id FROM addresses WHERE id=? AND user_id=?',[$id,$uid]))throw new RuntimeException('Address not found.');query('UPDATE addresses SET label=?,recipient=?,phone=?,line1=?,city=?,postcode=? WHERE id=? AND user_id=?',array_merge($values,[$id,$uid]));}
  else query('INSERT INTO addresses(label,recipient,phone,line1,city,postcode,user_id) VALUES(?,?,?,?,?,?,?)',array_merge($values,[$uid]));
  flash('Address saved.');go('account');
 }
 if($action==='address_delete'){query('DELETE FROM addresses WHERE id=? AND user_id=?',[(int)($_POST['id']??0),$uid]);flash('Address removed.');go('account');}
 if($action==='wishlist'){
  $id=integer_input('id',1);if(!one('SELECT id FROM products WHERE id=? AND active=1',[$id]))throw new RuntimeException('Product unavailable.');
  if(one('SELECT product_id FROM wishlist WHERE user_id=? AND product_id=?',[$uid,$id])){query('DELETE FROM wishlist WHERE user_id=? AND product_id=?',[$uid,$id]);flash('Removed from your wishlist.');}
  else {query('INSERT INTO wishlist(user_id,product_id) VALUES(?,?)',[$uid,$id]);flash('Saved to your wishlist.');}
 }
 elseif($action==='cart_add'){
  $id=integer_input('variant_id',1);$quantity=integer_input('quantity',1,99);
  db()->beginTransaction();
  // Serialise cart additions for this user and lock the selected stock row.
  one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
  $v=one('SELECT v.*,p.active FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE',[$id]);
  $existing=one('SELECT * FROM cart_items WHERE user_id=? AND variant_id=? FOR UPDATE',[$uid,$id]);$total=$quantity+(int)($existing['quantity']??0);
  if(!$v||!$v['active']||$total>(int)$v['stock']||$total>99)throw new RuntimeException('The requested quantity is unavailable.');
  query('INSERT INTO cart_items(user_id,variant_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)',[$uid,$id,$total]);db()->commit();flash('Added to your bag.');go('cart');
 }
 elseif($action==='cart_update'){
  $quantity=integer_input('quantity',1,99);$id=integer_input('id',1);
  $row=one('SELECT c.*,v.stock FROM cart_items c JOIN variants v ON v.id=c.variant_id WHERE c.id=? AND c.user_id=?',[$id,$uid]);
  if(!$row||$quantity>(int)$row['stock'])throw new RuntimeException('Requested quantity exceeds available stock.');
  query('UPDATE cart_items SET quantity=? WHERE id=? AND user_id=?',[$quantity,$id,$uid]);flash('Bag updated.');go('cart');
 }
 elseif($action==='cart_delete'){query('DELETE FROM cart_items WHERE id=? AND user_id=?',[(int)($_POST['id']??0),$uid]);flash('Item removed.');go('cart');}
 elseif($action==='checkout'){
  $recipient=text_input('recipient',100);$phone=text_input('phone',30);$line=text_input('line1',255);$city=text_input('city',100);$postcode=text_input('postcode',10);
  if(!preg_match('/^\d{5}$/',$postcode))throw new RuntimeException('Enter a five-digit Malaysian postcode.');
  if(!preg_match('/^[+0-9 ()-]{8,30}$/',$phone))throw new RuntimeException('Enter a valid phone number.');
  $key=(string)($_POST['checkout_key']??'');if(empty($_SESSION['checkout_key'])||!hash_equals($_SESSION['checkout_key'],$key))throw new RuntimeException('Checkout session expired. Please refresh checkout.');
  if($old=one('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$key,$uid]))go('order',['id'=>$old['id']]);
  if(($_POST['payment_result']??'')!=='success')throw new RuntimeException('Simulated payment declined. No order was placed and no stock was deducted.');
  db()->beginTransaction();one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
  if($old=one('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$key,$uid])){db()->commit();go('order',['id'=>$old['id']]);}
  $rows=cart_rows($uid,true);if(!$rows)throw new RuntimeException('Your bag is empty.');
  foreach($rows as $r)if(!$r['active']||$r['quantity']>$r['stock'])throw new RuntimeException('Stock changed for '.$r['name'].'. Please update your bag.');
  $subtotal=cart_subtotal($rows);$shipping=$subtotal>=20000?0:800;$reference='FM-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4)));
  query('INSERT INTO orders(user_id,reference,recipient,phone,address,subtotal,shipping,total,checkout_key) VALUES(?,?,?,?,?,?,?,?,?)',[$uid,$reference,$recipient,$phone,"$line, $postcode $city, Malaysia",$subtotal/100,$shipping/100,($subtotal+$shipping)/100,$key]);$oid=(int)db()->lastInsertId();
  foreach($rows as $r){query('INSERT INTO order_items(order_id,variant_id,product_id,name,color,size,image,unit_price,quantity) VALUES(?,?,?,?,?,?,?,?,?)',[$oid,$r['variant_id'],$r['product_id'],$r['name'],$r['color'],$r['size'],$r['image'],$r['price'],$r['quantity']]);query('UPDATE variants SET stock=stock-? WHERE id=?',[$r['quantity'],$r['variant_id']]);}
  query('DELETE FROM cart_items WHERE user_id=?',[$uid]);db()->commit();flash('Your order is confirmed. This was a simulated payment.');go('order',['id'=>$oid]);
 }
 elseif($action==='cancel_order'){cancel_order(integer_input('id',1),$uid,$u['role']==='admin');flash('Order cancelled. Stock has been restored.');go($u['role']==='admin'?'admin':'orders',$u['role']==='admin'?['section'=>'orders']:[]);}
 elseif($action==='review'){
  $pid=integer_input('product_id',1);$rating=integer_input('rating',1,5);$comment=text_input('comment',1500);
  if(!one("SELECT o.id FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.user_id=? AND i.product_id=? AND o.status='Completed'",[$uid,$pid]))throw new RuntimeException('Reviews are available after your order is completed.');
  query('INSERT INTO reviews(user_id,product_id,rating,comment) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)',[$uid,$pid,$rating,$comment]);flash('Your review has been saved.');go('product',['id'=>$pid]);
 }
 elseif($action==='review_delete'){query('DELETE FROM reviews WHERE user_id=? AND product_id=?',[$uid,integer_input('product_id',1)]);flash('Review removed.');go('product',['id'=>(int)$_POST['product_id']]);}
 elseif(str_starts_with($action,'admin_')){
  admin_required();require ROOT.'/app/admin-actions.php';
 }
 elseif($action!=='wishlist')throw new RuntimeException('Unknown action.');
}catch(Throwable $ex){
 if(isset($GLOBALS['pdo'])&&$GLOBALS['pdo']->inTransaction())$GLOBALS['pdo']->rollBack();
 try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}
 if($ex instanceof PDOException){error_log($ex->getMessage());$message=$ex->getCode()==='23000'?'This record is in use, or a value already exists. Check the data before trying again.':'Database operation failed. Please try again.';}else $message=$ex->getMessage();
 flash($message,'error');
 // Keep normal field values across the redirect; never retain passwords, tokens or uploaded files.
 $_SESSION['old']=array_filter($_POST,fn($key)=>!in_array($key,['password','confirm_password','csrf','checkout_key'],true),ARRAY_FILTER_USE_KEY);
}
header('Location: '.url((string)($_GET['page']??'home'),array_diff_key($_GET,['page'=>true])));exit;
