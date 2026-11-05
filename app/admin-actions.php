<?php
$section=(string)($_POST['section']??'dashboard');$id=(int)($_POST['id']??0);
if($action==='admin_banner_save'){
 $old=$id?one('SELECT * FROM banners WHERE id=?',[$id]):null;
 $placement=(string)($_POST['placement']??'hero');$alignment=(string)($_POST['alignment']??'left');
 if(!in_array($placement,['hero','campaign'],true)||!in_array($alignment,['left','center','right'],true))throw new RuntimeException('Invalid banner settings.');
 $title=text_input('title',150);$subtitle=text_input('subtitle',255);$button=text_input('button_text',60);$link=text_input('link',255);
 if(!preg_match('/^index\.php\?page=(catalog|product|about)(&[a-z_]+=[a-zA-Z0-9%+_-]+)*$/D',$link))throw new RuntimeException('Use an internal link, for example index.php?page=catalog&department=Women');
 $values=[$placement,$title,$subtitle,upload('image',$old['image']??'',true),upload('mobile_image',$old['mobile_image']??''),color_input('text_color'),$alignment,$button,$link,integer_input('sort_order',0,999),isset($_POST['active'])?1:0];
 if($id)query('UPDATE banners SET placement=?,title=?,subtitle=?,image=?,mobile_image=?,text_color=?,alignment=?,button_text=?,link=?,sort_order=?,active=? WHERE id=?',array_merge($values,[$id]));
 else query('INSERT INTO banners(placement,title,subtitle,image,mobile_image,text_color,alignment,button_text,link,sort_order,active) VALUES(?,?,?,?,?,?,?,?,?,?,?)',$values);
 $section='content';
}elseif($action==='admin_banner_delete'){query('DELETE FROM banners WHERE id=?',[$id]);$section='content';}
elseif($action==='admin_category_save'){
 $old=$id?one('SELECT * FROM categories WHERE id=?',[$id]):null;$dep=text_input('department',10);if(!in_array($dep,['Women','Men','Kids'],true))throw new RuntimeException('Invalid department.');
 $values=[$dep,text_input('name',80),upload('image',$old['image']??'',true)];
 if($id)query('UPDATE categories SET department=?,name=?,image=? WHERE id=?',array_merge($values,[$id]));else query('INSERT INTO categories(department,name,image) VALUES(?,?,?)',$values);$section='categories';
}elseif($action==='admin_category_delete'){query('DELETE FROM categories WHERE id=?',[$id]);$section='categories';}
elseif($action==='admin_product_save'){
 $category=integer_input('category_id',1);if(!one('SELECT id FROM categories WHERE id=?',[$category]))throw new RuntimeException('Choose a category.');
 $price=trim((string)($_POST['price']??''));if(!preg_match('/^\d{1,6}(\.\d{1,2})?$/D',$price)||(float)$price<=0)throw new RuntimeException('Enter a price from RM 0.01 to RM 999999.99, with at most two decimal places.');
 $values=[$category,text_input('name',150),$price,text_input('description',10000),text_input('material',255),isset($_POST['featured'])?1:0,isset($_POST['active'])?1:0];
 if($id)query('UPDATE products SET category_id=?,name=?,price=?,description=?,material=?,featured=?,active=? WHERE id=?',array_merge($values,[$id]));else query('INSERT INTO products(category_id,name,price,description,material,featured,active) VALUES(?,?,?,?,?,?,?)',$values);$section='products';
}elseif($action==='admin_product_delete'){query('DELETE FROM products WHERE id=?',[$id]);$section='products';}
elseif($action==='admin_variant_save'){
 $old=$id?one('SELECT * FROM variants WHERE id=?',[$id]):null;$product=integer_input('product_id',1);
 if(!one('SELECT id FROM products WHERE id=?',[$product]))throw new RuntimeException('Product not found.');
 $size=text_input('size',10);if(!in_array($size,['XS','S','M','L','XL','XXL','3-4Y','5-6Y','7-8Y','9-10Y'],true))throw new RuntimeException('Choose a valid size.');
 $values=[$product,text_input('color',60),color_input('hex'),$size,text_input('sku',80),integer_input('stock',0,100000),upload('image',$old['image']??'',true)];
 if($id)query('UPDATE variants SET product_id=?,color=?,hex=?,size=?,sku=?,stock=?,image=? WHERE id=?',array_merge($values,[$id]));else query('INSERT INTO variants(product_id,color,hex,size,sku,stock,image) VALUES(?,?,?,?,?,?,?)',$values);$section='inventory';
}elseif($action==='admin_variant_delete'){query('DELETE FROM variants WHERE id=?',[$id]);$section='inventory';}
elseif($action==='admin_gallery_add'){
 $product=integer_input('product_id',1);if(!one('SELECT id FROM products WHERE id=?',[$product]))throw new RuntimeException('Product not found.');
 query('INSERT INTO product_images(product_id,color,image) VALUES(?,?,?)',[$product,text_input('color',60),upload('image','',true)]);$section='inventory';
}elseif($action==='admin_gallery_delete'){query('DELETE FROM product_images WHERE id=?',[$id]);$section='inventory';}
elseif($action==='admin_order_status'){
 $target=text_input('status',30);db()->beginTransaction();$o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
 $next=['Confirmed'=>'Processing','Processing'=>'Shipped','Shipped'=>'Completed'];
 if(!$o||($next[$o['status']]??null)!==$target)throw new RuntimeException('Invalid order transition. Follow Confirmed → Processing → Shipped → Completed.');
 query('UPDATE orders SET status=? WHERE id=?',[$target,$id]);db()->commit();$section='orders';
}elseif($action==='admin_order_delete'){
 db()->beginTransaction();$order=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
 if(!$order||$order['status']!=='Cancelled')throw new RuntimeException('Only cancelled demonstration orders can be deleted.');
 query('DELETE FROM orders WHERE id=?',[$id]);db()->commit();$section='orders';
}elseif($action==='admin_settings_save'){
 $email=text_input('contact_email',190);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid contact email.');
 $values=[text_input('brand_name',50),text_input('tagline',100),$email,text_input('contact_phone',40),upload('logo',setting('logo'))];
 foreach(array_combine(['brand_name','tagline','contact_email','contact_phone','logo'],$values) as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);$section='settings';
}elseif($action==='admin_customer_save'){
 $name=text_input('name',100);$email=text_input('email',190);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email.');
 $old=$id?one("SELECT * FROM users WHERE id=? AND role='customer'",[$id]):null;if($id&&!$old)throw new RuntimeException('Customer not found.');
 if($id)query("UPDATE users SET name=?,email=? WHERE id=? AND role='customer'",[$name,$email,$id]);
 else{$password=(string)($_POST['password']??'');if(strlen($password)<8||strlen($password)>72)throw new RuntimeException('Use a password between 8 and 72 characters.');query('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)',[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);}$section='customers';
}elseif($action==='admin_customer_delete'){
 if(one('SELECT id FROM orders WHERE user_id=?',[$id]))throw new RuntimeException('Customers with orders cannot be deleted.');query("DELETE FROM users WHERE id=? AND role='customer'",[$id]);$section='customers';
}else throw new RuntimeException('Unknown administrator action.');
flash('Changes saved.');go('admin',['section'=>$section]);
