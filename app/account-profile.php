<?php
$accountTab=(string)($_GET['tab']??'overview');
if(isset($_GET['address'])||isset($_GET['new_address']))$accountTab='addresses';
if(!in_array($accountTab,['overview','details','addresses','reviews'],true))$accountTab='overview';
$editingAddress=isset($_GET['address'])?one('SELECT * FROM addresses WHERE id=? AND user_id=?',[(int)$_GET['address'],$currentUser['id']]):null;
$addresses=all('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id',[$currentUser['id']]);
$showAddressForm=isset($_GET['new_address'])||$editingAddress||($old['action']??'')==='address_save';
?>
<section class="container section account-space"><header class="account-heading"><p class="eyebrow">YOUR PERSONAL SPACE</p><h1>Hello, <?=e($currentUser['name'])?>.</h1><p>A place for your details, orders and everyday favourites.</p></header>
<div class="account-layout"><aside class="account-sidebar"><nav aria-label="Your account">
<?php foreach(['overview'=>'Overview','details'=>'My details','orders'=>'My orders','addresses'=>'Saved addresses','reviews'=>'My reviews','wishlist'=>'Wishlist'] as $key=>$label):$href=in_array($key,['orders','wishlist'],true)?url($key):url('account',['tab'=>$key]);?><a href="<?=e($href)?>" <?=$accountTab===$key?'aria-current="page"':''?>><?=e($label)?></a><?php endforeach;?>
<?php if($currentUser['role']==='admin'):?><a href="<?=e(url('admin'))?>">Manage store →</a><?php endif;?>
</nav><form method="post"><?=token()?><input type="hidden" name="action" value="logout"><button class="text-button">Log out</button></form></aside>
<div class="account-content">
<?php if($accountTab==='overview'):$recentOrders=all('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 3',[$currentUser['id']]);?>
<div class="account-section-heading"><h2>Your account</h2><a class="text-link" href="<?=e(url('account',['tab'=>'details']))?>">Edit details →</a></div>
<div class="account-summary-grid"><section class="account-summary"><p class="eyebrow">MY DETAILS</p><h3><?=e($currentUser['name'])?></h3><p><?=e($currentUser['email'])?><br><?=e($currentUser['phone']??'')?></p></section><section class="account-summary"><p class="eyebrow">DEFAULT ADDRESS</p><?php $defaultAddress=null;foreach($addresses as $a)if($a['is_default']){$defaultAddress=$a;break;}?>
<?php if($defaultAddress):?><h3><?=e($defaultAddress['label'])?></h3><p><?=e($defaultAddress['recipient'])?><br><?=e(address_snapshot($defaultAddress))?></p><?php else:?><p>No default address yet.</p><?php endif;?><a class="text-link" href="<?=e(url('account',['tab'=>'addresses']))?>">Manage addresses →</a></section></div>
<div class="account-section-heading"><h2>Recent orders</h2><a class="text-link" href="<?=e(url('orders'))?>">View all →</a></div>
<?php if(!$recentOrders):?><p>No orders yet. <a class="text-link" href="<?=e(url('catalog'))?>">Explore the collection →</a></p><?php endif;?>
<?php foreach($recentOrders as $o):?><article class="account-list-row"><div><strong><?=e($o['reference'])?></strong><p><?=e($o['created_at'])?> · <?=money($o['total'])?></p></div><span class="badge"><?=e($o['status'])?></span><a class="text-link" href="<?=e(url('order',['id'=>$o['id']]))?>">View →</a></article><?php endforeach;?>
<?php elseif($accountTab==='details'):?>
<h2>My details</h2><form method="post" class="form-grid account-details-form"><?=token()?><input type="hidden" name="action" value="profile">
<?php foreach(['name'=>'Full name','email'=>'Email address','phone'=>'Phone (optional)'] as $key=>$label):?><label><?=e($label)?><input name="<?=e($key)?>" type="<?=$key==='email'?'email':($key==='phone'?'tel':'text')?>" value="<?=old($key,$currentUser[$key]??'')?>" maxlength="<?=$key==='email'?190:($key==='phone'?30:100)?>" <?=$key==='phone'?'':'required'?>></label><?php endforeach;?><button class="btn">Save details</button></form>
<?php elseif($accountTab==='addresses'):?>
<div class="account-section-heading"><h2>Saved addresses</h2><?php if(!$showAddressForm):?><a class="btn secondary" href="<?=e(url('account',['tab'=>'addresses','new_address'=>1]))?>">Add address +</a><?php endif;?></div>
<?php if($showAddressForm):?>
<section><h2><?=$editingAddress?'Edit address':'Add address'?></h2><form method="post" class="form-grid"><?=token()?><input type="hidden" name="action" value="address_save"><input type="hidden" name="id" value="<?=e($editingAddress['id']??0)?>"><label>Address label<input name="label" value="<?=old('label',$editingAddress['label']??'Home')?>" maxlength="60" required></label>
<?php $addressFields=$editingAddress??[];$addressPrefix='account-';require __DIR__.'/address-fields.php';?>
<label class="checkbox"><input type="checkbox" name="is_default" value="1" <?=($old['is_default']??$editingAddress['is_default']??!$addresses)?'checked':''?>>Use as default address</label><button class="btn">Save address</button><?php if($editingAddress):?><a href="<?=e(url('account',['tab'=>'addresses']))?>">Add another address</a><?php endif;?></form></section>
<a class="text-link" href="<?=e(url('account',['tab'=>'addresses']))?>">Cancel and return to addresses</a>
<?php else:?>
<?php if(!$addresses):?><p>No saved addresses yet. Add one to make checkout easier.</p><?php endif;?><div class="account-address-grid">
<?php foreach($addresses as $a):?><article class="address-card"><strong><?=e($a['label'])?></strong> <?=$a['is_default']?'<span class="badge">Default</span>':''?><p><?=e($a['recipient'])?><br><?=e(address_snapshot($a))?><br><?=e($a['phone'])?></p><a class="text-link" href="<?=e(url('account',['tab'=>'addresses','address'=>$a['id']]))?>">Edit</a><form method="post" data-confirm="Remove this address? Existing order delivery details will be retained."><?=token()?><input type="hidden" name="action" value="address_delete"><input type="hidden" name="id" value="<?=$a['id']?>"><button class="text-button">Remove</button></form></article><?php endforeach;?></div>
<?php endif;?>
<?php elseif($accountTab==='reviews'):
$myReviews=all('SELECT r.*,p.name product_name,p.active product_active FROM reviews r JOIN products p ON p.id=r.product_id WHERE r.user_id=? AND r.customer_deleted=0 ORDER BY r.id DESC',[$currentUser['id']]);
$reviewable=all("SELECT DISTINCT p.id,p.name FROM products p JOIN order_items i ON i.product_id=p.id JOIN orders o ON o.id=i.order_id WHERE o.user_id=? AND o.status='Completed' AND p.active=1 AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.user_id=o.user_id AND r.product_id=p.id AND r.customer_deleted=0) ORDER BY p.id DESC",[$currentUser['id']]);?>
<h2>My reviews</h2><p>Share your experience with a written review and 1–5 stars. Reviews are available after a completed purchase.</p>
<h3>Ready to review</h3><?php if(!$reviewable):?><p>No products awaiting a review.</p><?php endif;?>
<?php foreach($reviewable as $product):?><article class="account-list-row"><strong><?=e($product['name'])?></strong><a class="text-link" href="<?=e(url('product',['id'=>$product['id']]))?>#reviews">Write a review →</a></article><?php endforeach;?>
<h3>Your reviews</h3><?php if(!$myReviews):?><p>You have not submitted a review yet.</p><?php endif;?>
<?php foreach($myReviews as $review):?><article class="account-review"><div class="account-section-heading"><h3><?=e($review['product_name'])?></h3><span class="badge"><?=$review['is_hidden']?'Hidden by moderation':'Published'?></span></div><p class="review-stars" aria-label="<?=(int)$review['rating']?> out of 5 stars"><?=str_repeat('★',(int)$review['rating']).str_repeat('☆',5-(int)$review['rating'])?></p><p><?=e($review['comment'])?></p><div class="account-review-actions"><?php if($review['product_active']):?><a class="text-link" href="<?=e(url('product',['id'=>$review['product_id']]))?>#reviews">Edit review →</a><?php endif;?><form method="post" data-confirm="Remove your review?"><?=token()?><input type="hidden" name="action" value="review_delete"><input type="hidden" name="product_id" value="<?=(int)$review['product_id']?>"><button class="text-button" type="submit">Remove</button></form></div></article><?php endforeach;?>
<?php endif;?></div></div></section>
