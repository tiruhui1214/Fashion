<?php
require_once __DIR__.'/recommendations.php';
$conversation=$_SESSION['assistant']??['preferences'=>[],'messages'=>[]];
?>
<section class="container section"><p class="eyebrow">YOUR PERSONAL EDIT</p><h1>Fashion assistant.</h1><p>Describe an occasion, style, colour, size or budget per item. Refine your request in English or Chinese; your previous preferences stay until changed or reset.</p>
<p>Try: “Smart casual dinner, black, budget RM150, size M” or “简约上课穿搭，预算 RM120，不要裙子”.</p>
<?php foreach($conversation['messages'] as $m):?><div class="address-card"><strong><?=$m['role']==='user'?'You':'Assistant'?></strong><p><?=e($m['text'])?></p></div><?php endforeach;?>
<?php if($conversation['preferences']):?><p><strong>Current preferences:</strong> <?php foreach($conversation['preferences'] as $key=>$value):if($value===null||$value===''||$value===[])continue;?><?=e($key)?>: <?=e(is_array($value)?implode(', ',$value):($key==='budget'?money($value/100):$value))?> · <?php endforeach;?></p><?php endif;?>
<form method="post" class="form-grid narrow"><?=token()?><input type="hidden" name="action" value="assistant_message"><label>Your request<textarea name="message" maxlength="600" required></textarea></label><button class="btn">Find my style</button></form>
<form method="post"><?=token()?><input type="hidden" name="action" value="assistant_reset"><button class="text-button">Reset preferences</button></form>
<?php if(isset($conversation['result'])):
// Stored results may have become stale since the last request. Refresh without a model call.
$result=refresh_recommendations($conversation['result']);render_recommendations($result);
else:?><p>Recommendations use catalogue rules until a model service is configured.</p><?php endif;?></section>
