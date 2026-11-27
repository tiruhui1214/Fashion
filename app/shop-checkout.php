<?php
$uid=(int)$currentUser['id'];$allRows=cart_rows($uid);$rows=array_values(array_filter($allRows,fn($r)=>(bool)$r['is_selected']));
$quoteError='';
try{$quote=checkout_quote($rows,$page==='checkout'?(string)($_SESSION['promo_code']??''):'');}
catch(RuntimeException $error){$quoteError=$error->getMessage();$quote=checkout_quote($rows);}
?>
<section class="container section shop-page"><p class="eyebrow">YOUR EVERYDAY, SELECTED</p><h1><?=$page==='cart'?'Your shopping bag.':'The final details.'?></h1>
<?php if(!$allRows):?><div class="empty-state"><h2>Your bag is empty.</h2><a class="btn" href="<?=e(url('catalog'))?>">Continue shopping</a></div>
<?php else:?><div class="checkout-layout"><div>
<?php if($page==='cart'):?>
<form method="post" id="cart-selection" class="inline-form"><?=token()?><input type="hidden" name="action" value="cart_select"><label class="checkbox"><input type="checkbox" data-select-all <?=count($rows)===count($allRows)?'checked':''?>>Select all</label><button class="btn btn-outline">Update selection</button></form>
<?php foreach($allRows as $r):?><article class="cart-item"><a href="<?=e(url('product',['id'=>$r['product_id']]))?>"><img src="<?=e(safe_image($r['image']))?>" alt="<?=e($r['name'])?>"></a><div>
<label class="checkbox"><input type="checkbox" name="selected[]" form="cart-selection" value="<?=$r['id']?>" <?=$r['is_selected']?'checked':''?> data-cart-selected>Select <?=e($r['name'])?></label>
<p><?=e($r['color'].' / '.$r['size'])?> · <?=money($r['price'])?></p>
<?php if(!$r['active']||$r['quantity']>$r['stock']):?><p class="error-text">Unavailable in the requested quantity. Update, remove or unselect it.</p><?php endif;?>
<form method="post" class="inline-form"><?=token()?><input type="hidden" name="action" value="cart_update"><input type="hidden" name="id" value="<?=$r['id']?>"><label>Quantity<input type="number" name="quantity" min="1" max="99" value="<?=$r['quantity']?>" required></label><button class="text-button">Update</button></form>
<form method="post" data-confirm="Remove this item from your bag?"><?=token()?><input type="hidden" name="action" value="cart_delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="text-button muted">Remove</button></form></div><strong><?=money((float)$r['price']*$r['quantity'])?></strong></article><?php endforeach;?>
<?php if($rows):require_once __DIR__.'/recommendations.php';?><section class="section"><h2>Complete your selected outfit.</h2><?php render_recommendations(cart_recommendation_result($rows));?></section><?php endif;?>
<?php else: ?>
<?php if(!$rows):?><p class="notice info">Select at least one item in your shopping bag to check out.</p><a href="<?=e(url('cart'))?>">Return to bag</a>
<?php else:
if(empty($_SESSION['checkout_key'])||one('SELECT id FROM orders WHERE checkout_key=? AND user_id=?',[$_SESSION['checkout_key'],$uid]))$_SESSION['checkout_key']=bin2hex(random_bytes(32));
$addresses=all('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC,id',[$uid]);
$addressId=isset($_GET['address'])?max(0,(int)$_GET['address']):(isset($old['address_id'])?max(0,(int)$old['address_id']):(int)($addresses[0]['id']??0));
$address=null;foreach($addresses as $a)if((int)$a['id']===$addressId)$address=$a;
if($addressId&&!$address){$addressId=0;}
?>
<p class="notice info">Simulated payment only. No bank or card information is required.</p>
<form method="get" class="inline-form"><input type="hidden" name="page" value="checkout"><label>Delivery address<select name="address"><option value="0">Enter a new delivery address</option><?php foreach($addresses as $a):?><option value="<?=$a['id']?>" <?=$addressId===(int)$a['id']?'selected':''?>><?=e($a['label'].($a['is_default']?' · Default':''))?></option><?php endforeach;?></select></label><button class="btn btn-outline">Use address</button></form>
<?php if($quoteError):?><p class="notice error"><?=e($quoteError)?></p><?php endif;?>
<form method="post" class="inline-form"><?=token()?><input type="hidden" name="action" value="promotion_apply"><label>Promotion code<input name="promo_code" maxlength="40" value="<?=e($_SESSION['promo_code']??'')?>" required></label><button class="btn btn-outline">Apply code</button></form>
<?php if(isset($_SESSION['promo_code'])):?><form method="post"><?=token()?><input type="hidden" name="action" value="promotion_remove"><button class="text-button">Remove promotion</button></form><?php endif;?>
<form method="post" class="form-grid" id="checkout-form"><?=token()?><input type="hidden" name="action" value="checkout"><input type="hidden" name="checkout_key" value="<?=e($_SESSION['checkout_key'])?>"><input type="hidden" name="quote_signature" value="<?=e(quote_signature($rows,$quote))?>"><input type="hidden" name="address_id" value="<?=$addressId?>"><h2>Delivery details</h2>
<?php if($address):?><p><?=e($address['recipient'])?><br><?=e($address['phone'])?><br><?=e(address_snapshot($address))?></p><a href="<?=e(url('account',['address'=>$address['id']]))?>">Edit saved address</a><?php if(!$address['state']):?><p class="error-text">Complete the state in this saved address before ordering, or enter a new address.</p><?php endif;?>
<?php else:$addressFields=['recipient'=>$currentUser['name'],'phone'=>$currentUser['phone']??''];$addressPrefix='checkout-';require __DIR__.'/address-fields.php';endif;?>
<label>Simulated payment result<select name="payment_result"><option value="success">Approve demo payment</option><option value="failure">Decline demo payment</option></select></label><button class="btn" <?=$quoteError?'disabled':''?>>Place demo order · <?=money($quote['total']/100)?></button></form>
<?php endif;endif;?>
</div><aside class="order-summary"><p class="eyebrow">SELECTED ITEMS SUMMARY</p><p><?=count($rows)?> selected of <?=count($allRows)?> bag items</p>
<?php foreach($rows as $r):?><p class="summary-line"><span><?=e($r['name'])?> × <?=$r['quantity']?></span><span><?=money((float)$r['price']*$r['quantity'])?></span></p><?php endforeach;?>
<p class="summary-line"><span>Subtotal</span><span><?=money($quote['subtotal']/100)?></span></p>
<p class="summary-line"><span>Discount<?= $quote['code']?' · '.e($quote['code']):''?></span><span>−<?=money($quote['discount']/100)?></span></p>
<p class="summary-line"><span>Delivery</span><span><?=money($quote['shipping']/100)?></span></p><p class="summary-line summary-total"><span>Total</span><strong><?=money($quote['total']/100)?></strong></p>
<?php if($page==='cart'&&$rows):?><a class="btn full" href="<?=e(url('checkout'))?>">Checkout selected items</a><?php endif;?>
<a class="text-link" href="<?=e(url($page==='cart'?'catalog':'cart'))?>"><?=$page==='cart'?'Continue shopping':'Return to bag'?></a><small>Free delivery from RM200 merchandise subtotal before discounts. Only selected items are purchased.</small></aside></div><?php endif;?></section>
