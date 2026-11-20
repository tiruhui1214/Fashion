<?php
// All operations arrive through the existing admin_required() and CSRF gates.
if($action==='admin_customer_save'){
    $name=text_input('name',100);$email=strtolower(text_input('email',190));$phone=validated_phone(scalar_value($_POST,'phone'),true);$enabled=isset($_POST['active'])?1:0;
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email.');
    if($id&&!one("SELECT id FROM users WHERE id=? AND role='customer'",[$id]))throw new RuntimeException('Customer not found.');
    if(one('SELECT id FROM users WHERE email=? AND id<>?',[$email,$id]))throw new RuntimeException('Email is already registered.');
    if($id)query("UPDATE users SET name=?,email=?,phone=?,active=? WHERE id=? AND role='customer'",[$name,$email,$phone,$enabled,$id]);
    else{$password=scalar_value($_POST,'password');if(strlen($password)<8||strlen($password)>72)throw new RuntimeException('Use a password between 8 and 72 characters.');query('INSERT INTO users(name,email,phone,active,password_hash) VALUES(?,?,?,?,?)',[$name,$email,$phone,$enabled,password_hash($password,PASSWORD_DEFAULT)]);}
    flash('Customer saved.');go('admin',['section'=>'customers']);
}
if($action==='admin_product_save'){
    $category=integer_input('category_id',1);if(!one('SELECT id FROM categories WHERE id=?',[$category]))throw new RuntimeException('Choose a category.');
    $price=scalar_value($_POST,'price');$cents=to_cents($price);if($cents<1||$cents>99999999)throw new RuntimeException('Price must be RM 0.01–999999.99.');
    $values=[$category,text_input('name',150),from_cents($cents),text_input('description',10000),text_input('material',255),isset($_POST['featured'])?1:0,isset($_POST['active'])?1:0];
    $style=scalar_value($_POST,'style');$occasion=scalar_value($_POST,'occasion');
    if(mb_strlen($style)>255||mb_strlen($occasion)>255)throw new RuntimeException('Style and occasion tags must be at most 255 characters.');
    if($id){if(!one('SELECT id FROM products WHERE id=?',[$id]))throw new RuntimeException('Product not found.');query('UPDATE products SET category_id=?,name=?,price=?,description=?,material=?,featured=?,active=?,style=?,occasion=? WHERE id=?',array_merge($values,[$style,$occasion,$id]));}
    else query('INSERT INTO products(category_id,name,price,description,material,featured,active,style,occasion,created_by_admin_id) VALUES(?,?,?,?,?,?,?,?,?,?)',array_merge($values,[$style,$occasion,(int)$u['id']]));
    flash('Product saved.');go('admin',['section'=>'products']);
}
if($action==='admin_product_delete'){
    db()->beginTransaction();one('SELECT id FROM products WHERE id=? FOR UPDATE',[$id]);
    if(one('SELECT id FROM order_items WHERE product_id=? LIMIT 1',[$id]))throw new RuntimeException('This product has order history. Unpublish it instead of deleting it.');
    query('DELETE FROM products WHERE id=?',[$id]);db()->commit();flash('Product deleted.');go('admin',['section'=>'products']);
}
if($action==='admin_variant_save'){
    $old=$id?one('SELECT * FROM variants WHERE id=?',[$id]):null;if($id&&!$old)throw new RuntimeException('Variant not found.');
    $pid=integer_input('product_id',1);if(!one('SELECT id FROM products WHERE id=?',[$pid]))throw new RuntimeException('Product not found.');
    if($old&&(int)$old['product_id']!==$pid&&one('SELECT id FROM order_items WHERE variant_id=? LIMIT 1',[$id]))throw new RuntimeException('A purchased variant cannot be moved to another product.');
    $size=text_input('size',10);if(!in_array($size,size_options(),true))throw new RuntimeException('Choose a valid size.');
    $color=text_input('color',60);$sku=text_input('sku',80);
    if(one('SELECT id FROM variants WHERE sku=? AND id<>?',[$sku,$id])||one('SELECT id FROM variants WHERE product_id=? AND color=? AND size=? AND id<>?',[$pid,$color,$size,$id]))throw new RuntimeException('SKU or product colour/size already exists.');
    $values=[$pid,$color,color_input('hex'),$size,$sku,integer_input('stock',0,100000),upload('image',$old['image']??'',true),integer_input('minimum_stock_level',0,100000),isset($_POST['active'])?1:0];
    if($id)query('UPDATE variants SET product_id=?,color=?,hex=?,size=?,sku=?,stock=?,image=?,minimum_stock_level=?,active=? WHERE id=?',array_merge($values,[$id]));
    else query('INSERT INTO variants(product_id,color,hex,size,sku,stock,image,minimum_stock_level,active) VALUES(?,?,?,?,?,?,?,?,?)',$values);
    flash('Variant saved.');go('admin',['section'=>'inventory','product_id'=>$pid]);
}
if($action==='admin_variant_delete'){
    db()->beginTransaction();one('SELECT id FROM variants WHERE id=? FOR UPDATE',[$id]);ensure_deletable_variant($id);query('DELETE FROM variants WHERE id=?',[$id]);db()->commit();flash('Variant deleted.');go('admin',['section'=>'inventory']);
}
if($action==='admin_review_moderate'){
    $hidden=integer_input('hidden',0,1);if(!one('SELECT id FROM reviews WHERE id=?',[$id]))throw new RuntimeException('Review not found.');
    query('UPDATE reviews SET is_hidden=?,moderated_by_admin_id=?,moderated_at=? WHERE id=?',[$hidden,(int)$u['id'],date('Y-m-d H:i:s'),$id]);flash('Review moderation saved.');go('admin',['section'=>'reviews']);
}
if($action==='admin_order_status'){
    $target=text_input('status',30);db()->beginTransaction();$o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
    $next=['Confirmed'=>'Processing','Processing'=>'Shipped','Shipped'=>'Completed'];
    if(!$o||($next[$o['status']]??null)!==$target)throw new RuntimeException('Invalid order transition. Follow Confirmed → Processing → Shipped → Completed.');
    query('UPDATE orders SET status=?,last_status_updated_by_admin_id=? WHERE id=?',[$target,(int)$u['id'],$id]);record_status($id,(int)$u['id'],$o['status'],$target);db()->commit();flash('Order status updated.');go('admin',['section'=>'orders','view'=>$id]);
}
