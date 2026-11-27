<?php
$pid=(int)$p['id'];$summary=review_summary($pid);
$own=$currentUser?one('SELECT * FROM reviews WHERE product_id=? AND user_id=? AND customer_deleted=0',[$pid,$currentUser['id']]):null;
$eligible=$currentUser&&one("SELECT o.id FROM orders o JOIN order_items i ON i.order_id=o.id WHERE o.user_id=? AND i.product_id=? AND o.status='Completed' LIMIT 1",[$currentUser['id'],$pid]);
?>
<section class="container section reviews" id="reviews"><p class="eyebrow">YOUR EVERYDAY STORIES</p><h2>Reviews</h2>
<p><strong><?=number_format((float)$summary['average_rating'],1)?> / 5</strong> · <?=(int)$summary['review_count']?> reviews</p>
<?php $reviews=all('SELECT r.*,u.name FROM reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? AND r.is_hidden=0 AND r.customer_deleted=0 ORDER BY r.id DESC',[$pid]);if(!$reviews):?><p>No published reviews yet.</p><?php endif;?>
<?php foreach($reviews as $r):?><article class="review"><strong><?=e($r['name'])?></strong><span><?=str_repeat('★',(int)$r['rating'])?></span><p><?=e($r['comment'])?></p></article><?php endforeach;?>
<?php if($eligible):?>
<?php if($own&&$own['is_hidden']):?><p class="notice info">Your review is hidden by moderation. Editing it does not automatically publish it.</p><?php endif;?>
<form method="post" class="form-grid narrow"><?=token()?><input type="hidden" name="action" value="review"><input type="hidden" name="product_id" value="<?=$pid?>"><fieldset class="rating-field"><legend>Your rating</legend><div class="rating-stars"><?php for($i=5;$i>=1;$i--):?><label class="rating-choice"><input type="radio" name="rating" value="<?=$i?>" required <?=(int)($old['rating']??$own['rating']??5)===$i?'checked':''?>><span aria-hidden="true">★</span><span class="sr-only"><?=$i?> <?=$i===1?'star':'stars'?></span></label><?php endfor;?></div></fieldset><label>Your review<textarea name="comment" maxlength="1500" required><?=old('comment',$own['comment']??'')?></textarea></label><button class="btn"><?=$own?'Update review':'Save review'?></button></form>
<?php elseif($currentUser):?><p>Reviews are available after a completed purchase of this product.</p><?php else:?><a href="<?=e(url('login'))?>">Sign in to review a completed purchase.</a><?php endif;?>
<?php if($own):?><form method="post" data-confirm="Delete your review?"><?=token()?><input type="hidden" name="action" value="review_delete"><input type="hidden" name="product_id" value="<?=$pid?>"><button class="text-button">Delete my review</button></form><?php endif;?></section>
