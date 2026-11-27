<?php
$section=(string)($_POST['section']??'dashboard');$id=(int)($_POST['id']??0);
require __DIR__.'/admin-feature-actions.php';
if(in_array($action,['admin_promotion_save','admin_promotion_delete'],true)){
 require __DIR__.'/admin-promotions-actions.php';
 return;
}
if(in_array($action,['admin_catalog_banner_save','admin_catalog_banner_reset'],true)){
 $scope=text_input('target',60);$targets=catalog_banner_targets();
 if(!isset($targets[$scope]))throw new RuntimeException('Choose a valid category page.');
 if($action==='admin_catalog_banner_reset'){
  db()->beginTransaction();
  foreach(catalog_banner_keys($scope) as $key)query('DELETE FROM settings WHERE setting_key=?',[$key]);
  db()->commit();
 }else{
  $old=catalog_banner($scope);
  $heading=text_input('title',100);$subtitle=text_input('subtitle',180);$color=color_input('text_color');
  $image=upload('image',$old['image'],true);
  $mobile=isset($_POST['clear_mobile'])?'':upload('mobile_image',$old['mobile_image']);
  $values=['image'=>$image,'mobile_image'=>$mobile,'title'=>$heading,'subtitle'=>$subtitle,'text_color'=>$color];
  db()->beginTransaction();
  foreach($values as $field=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',['catalog_'.$scope.'_'.$field,$value]);
  db()->commit();
 }
 flash('Category page banner saved.');go('admin',['section'=>'catalog','target'=>$scope]);
}elseif($action==='admin_banner_save'){
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
elseif($action==='admin_gallery_add'){
 $product=integer_input('product_id',1);if(!one('SELECT id FROM products WHERE id=?',[$product]))throw new RuntimeException('Product not found.');
 query('INSERT INTO product_images(product_id,color,image) VALUES(?,?,?)',[$product,text_input('color',60),upload('image','',true)]);$section='inventory';
}elseif($action==='admin_gallery_delete'){query('DELETE FROM product_images WHERE id=?',[$id]);$section='inventory';}
elseif($action==='admin_order_delete'){
 db()->beginTransaction();$order=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
 if(!$order||$order['status']!=='Cancelled')throw new RuntimeException('Only cancelled demonstration orders can be deleted.');
 query('DELETE FROM orders WHERE id=?',[$id]);db()->commit();$section='orders';
}elseif($action==='admin_settings_save'){
 $email=text_input('contact_email',190);if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid contact email.');
 $values=[text_input('brand_name',50),text_input('tagline',100),$email,text_input('contact_phone',40),upload('logo',setting('logo'))];
 foreach(array_combine(['brand_name','tagline','contact_email','contact_phone','logo'],$values) as $key=>$value)query('INSERT INTO settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$value]);$section='settings';
}elseif($action==='admin_customer_delete'){
 if(one('SELECT id FROM orders WHERE user_id=?',[$id]))throw new RuntimeException('Customers with orders cannot be deleted.');query("DELETE FROM users WHERE id=? AND role='customer'",[$id]);$section='customers';
}else throw new RuntimeException('Unknown administrator action.');
flash('Changes saved.');go('admin',['section'=>$section]);
