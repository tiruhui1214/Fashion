<?php
if(!defined('ROOT')){http_response_code(404);exit;}
if(!$currentUser): ?>
<div class="chat-welcome"><span class="chat-kicker">YOUR PERSONAL EDIT</span><h3>A little styling help?</h3><p>Sign in to find pieces for your occasion, style and budget. Your conversation stays with you as you browse.</p><a class="btn" href="<?=e(url('login'))?>">Sign in to chat</a></div>
<?php return; endif;
require_once __DIR__.'/recommendations.php';
$conversation=$_SESSION['assistant']??['preferences'=>[],'messages'=>[]];
if(!$conversation['messages']): ?>
<div class="chat-welcome"><span class="chat-kicker">YOUR PERSONAL EDIT</span><h3>What are you dressing for?</h3><p>Tell me your occasion, style, colour, size or budget per item. You can refine your request in English or Chinese.</p><button type="button" class="chat-example" data-chat-example="Minimalist work outfit, budget RM150">Minimalist work outfit · RM150</button><button type="button" class="chat-example" data-chat-example="简约上课穿搭，预算 RM120，不要裙子">简约上课穿搭 · RM120</button></div>
<?php endif;foreach($conversation['messages'] as $message):?>
<div class="chat-message chat-message--<?=$message['role']==='user'?'user':'assistant'?>"><span class="chat-message-author"><?=$message['role']==='user'?'You':'Style assistant'?></span><p><?=nl2br(e($message['text']))?></p></div>
<?php endforeach;
if($conversation['preferences']):?><details class="chat-preferences"><summary>Current preferences</summary><p><?php foreach($conversation['preferences'] as $key=>$value):if($value===null||$value===''||$value===[])continue;?><span><?=e(ucfirst(str_replace('_',' ',$key)))?>: <?=e(is_array($value)?implode(', ',$value):($key==='budget'?money($value/100):$value))?></span><?php endforeach;?></p></details><?php endif;
if(isset($conversation['result'])):
$result=refresh_recommendations($conversation['result']); ?>
<div class="chat-results"><p class="chat-mode"><?=e($result['mode'])?></p><?php if($result['notice']):?><p class="chat-notice"><?=e($result['notice'])?></p><?php endif;?>
<?php if(!$result['items']&&!$result['notice']):?><p class="chat-notice">No active, in-stock matches right now. Try a different size, colour or budget.</p><?php endif;foreach($result['items'] as $product):?>
<article class="chat-product"><a href="<?=e(url('product',['id'=>$product['id']]))?>" tabindex="-1" aria-hidden="true"><img src="<?=e(safe_image($product['image']))?>" alt="" loading="lazy"></a><div><a class="chat-product-name" href="<?=e(url('product',['id'=>$product['id']]))?>"><?=e($product['name'])?></a><p><?=money($product['price'])?> · <?=e($product['color'].' / '.$product['size'])?></p><p class="chat-product-reason"><?=e($product['reason'])?></p><a class="chat-product-link" href="<?=e(url('product',['id'=>$product['id']]))?>">View piece →</a></div></article>
<?php endforeach;?></div><?php endif;?>
