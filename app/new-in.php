<?php
require_once __DIR__.'/new-in-data.php';
$newProducts=all('SELECT p.*,c.department,c.name category FROM products p JOIN categories c ON c.id=p.category_id WHERE p.active=1 ORDER BY p.created_at DESC,p.id DESC');
$queueProducts=array_slice($newProducts,0,8);$queueVariants=[];
if($queueProducts){
    $ids=array_column($queueProducts,'id');
    $queueVariants=all('SELECT id,product_id,color,hex,size,stock,image,active FROM variants WHERE active=1 AND product_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY product_id,id',$ids);
}
$queueItems=new_in_presenters($queueProducts,$queueVariants);
?>
<section class="new-in-intro container"><div><p class="eyebrow">HIFIVE / THE LATEST EDIT</p><h1>New in<span>.</span></h1></div><p>Fresh perspectives.<br>Find your next everyday favourite.</p><a class="text-link" href="#new-in-grid">Browse all <?=count($newProducts)?> pieces ↓</a></section>
<?php if($queueItems):?>
<section class="new-in-queue" data-new-in-queue aria-label="New arrivals showcase">
 <div class="queue-stage" data-queue-stage tabindex="0" aria-label="Product queue. Use left and right arrow keys to browse.">
  <div class="queue-stage-caption"><span>THE NEW ARRIVALS</span><span data-queue-counter>01 / <?=str_pad((string)count($queueItems),2,'0',STR_PAD_LEFT)?></span></div>
  <div class="queue-ground" aria-hidden="true"></div>
  <?php foreach($queueItems as $i=>$item):$image=$item['selected']['image']??'assets/product-sage.svg';?>
  <button class="queue-figure <?=$i===0?'is-current':''?>" type="button" data-queue-item="<?=$i?>" data-position="<?=min($i,3)?>" aria-label="View <?=e($item['name'])?>" aria-pressed="<?=$i===0?'true':'false'?>" <?=$i>2?'tabindex="-1"':''?>>
   <img src="<?=e(safe_image($image))?>" alt="<?=e($item['name'])?>" width="600" height="800" draggable="false" <?=$i>2?'loading="lazy"':'decoding="async"'?>>
  </button>
  <?php endforeach;?>
  <div class="queue-controls"><button type="button" data-queue-prev aria-label="Previous product" <?=count($queueItems)<2?'disabled':''?>>← <span>PREV</span></button><button type="button" data-queue-next aria-label="Next product" <?=count($queueItems)<2?'disabled':''?>><span>NEXT</span> →</button></div>
 </div>
 <div class="queue-information">
 <?php foreach($queueItems as $i=>$item):$selected=$item['selected'];$color=$selected['color']??'';?>
  <article class="queue-panel" data-queue-panel="<?=$i?>" <?=$i!==0?'hidden':''?>>
   <div class="queue-panel-top"><span><?=e($item['department'].' / '.$item['category'])?></span><a href="<?=e(url('product',['id'=>$item['id']]))?>">Full details ↗</a></div>
   <p class="queue-edition">THE LATEST / <?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></p>
   <h2><?=e($item['name'])?></h2><p class="queue-price"><?=money($item['price'])?></p>
   <p class="queue-description"><?=e($item['description'])?></p>
   <?php if($selected):?>
   <form method="post" class="queue-purchase" data-queue-purchase>
    <?=token()?><input type="hidden" name="action" value="cart_add">
    <fieldset class="queue-colors"><legend>Colour · <strong data-queue-color-name><?=e($color)?></strong></legend><div>
     <?php foreach($item['colors'] as $name=>$variant):?><button type="button" class="queue-color <?=$name===$color?'is-selected':''?>" data-new-color="<?=e($name)?>" data-image="<?=e(safe_image($variant['image']))?>" style="--color:<?=e($variant['hex'])?>" aria-label="<?=e($name)?>" aria-pressed="<?=$name===$color?'true':'false'?>"></button><?php endforeach;?>
    </div></fieldset>
    <label class="queue-size">Size<select name="variant_id" required data-queue-variant>
     <?php foreach($item['variants'] as $variant):$same=$variant['color']===$color;?><option value="<?=(int)$variant['id']?>" data-color="<?=e($variant['color'])?>" data-stock="<?=(int)$variant['stock']?>" <?=$same?'':'hidden'?> <?=$same&&$variant['stock']>0?'':'disabled'?> <?=(int)$variant['id']===(int)$selected['id']?'selected':''?>><?=e($variant['size'].($variant['stock']>0?'':' · Sold out'))?></option><?php endforeach;?>
    </select></label>
    <div class="queue-purchase-meta"><a href="<?=e(url('help',['topic'=>'size']))?>">Size guide ↗</a><label>Qty<input type="number" name="quantity" min="1" max="<?=max(1,min(99,(int)$selected['stock']))?>" value="1" required data-queue-quantity></label></div>
    <p class="queue-stock" data-queue-stock aria-live="polite"><?=$item['available']?(int)$selected['stock'].' available in this size':'This colour / size is currently sold out.'?></p>
    <button class="queue-add" data-queue-add <?=$item['available']?'':'disabled'?>><?=$item['available']?'Add to bag':'Sold out'?> <span aria-hidden="true">↗</span></button>
   </form>
   <?php else:?><p class="queue-stock">No available variants yet.</p><a class="queue-details-link" href="<?=e(url('product',['id'=>$item['id']]))?>">View product details ↗</a><?php endif;?>
   <p class="queue-delivery">Malaysia delivery · RM 8<br>Complimentary from RM 200</p>
  </article>
 <?php endforeach;?>
 <p class="sr-only" data-queue-status aria-live="polite" aria-atomic="true"></p>
 </div>
</section>
<noscript><p class="container notice info">Browse all new arrivals below. Open a product for all colours and sizes.</p></noscript>
<?php endif;?>
<section class="container section new-in-grid-section" id="new-in-grid"><div class="section-heading"><div><p class="eyebrow">THE COMPLETE EDIT</p><h2>Fresh arrivals.</h2></div><a class="text-link" href="<?=e(url('catalog'))?>">Search & filter the collection ↗</a></div>
<?php if($newProducts):?><div class="product-grid catalog-grid"><?php foreach($newProducts as $item)product_card($item);?></div><?php else:?><div class="empty-state"><h2>A new chapter is coming.</h2><p>New arrivals will appear here when products are published.</p><a class="btn" href="<?=e(url('catalog'))?>">Browse the collection</a></div><?php endif;?></section>
