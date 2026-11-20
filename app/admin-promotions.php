<?php
require_once __DIR__.'/promotions.php';
if(!promotion_schema_ready()): ?>
<section class="admin-card"><h2>Promotion database upgrade required</h2><p>Run <code>database/migrations/20261120_promotions.php</code> using PHP CLI from this installation. Follow <code>docs/PROMOTION-UPGRADE.md</code>. Your existing products and orders are retained.</p></section>
<?php return; endif;
$editId=max(0,(int)($_GET['edit']??0));
$editing=$editId?one('SELECT * FROM promotions WHERE id=?',[$editId]):null;
if($editId&&!$editing): ?><p class="notice error">Promotion not found.</p><?php return; endif;
$search=is_string($_GET['search']??null)?trim($_GET['search']):'';
$status=is_string($_GET['status']??null)?$_GET['status']:'';
$where=[];$params=[];
if($search!==''){$where[]='promo_code LIKE ?';$params[]='%'.$search.'%';}
$now=(new DateTimeImmutable('now',new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s');
switch($status){
 case 'active':$where[]='active=1 AND start_date<=? AND end_date>=?';$params[]=$now;$params[]=$now;break;
 case 'scheduled':$where[]='active=1 AND start_date>?';$params[]=$now;break;
 case 'expired':$where[]='active=1 AND end_date<?';$params[]=$now;break;
 case 'disabled':$where[]='active=0';break;
 default:$status='';
}
$promotions=all('SELECT * FROM promotions'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY id DESC',$params);
$failed=($old['action']??'')==='admin_promotion_save'&&(int)($old['id']??0)===$editId;
$value=static function(string $key,string $default='') use ($editing,$failed,$old): string {
 $v=$failed?($old[$key]??$default):($editing[$key]??$default);
 return is_scalar($v)?(string)$v:$default;
};
$dateValue=static function(string $key) use ($value): string {return substr(str_replace(' ','T',$value($key)),0,16);};
$enabled=$failed?isset($old['active']):(bool)($editing['active']??true);
?>
<p class="admin-description">Percentage discounts apply to the merchandise subtotal, excluding shipping. One code per order. All times are Malaysia time (UTC+8). Checkout integration is the next stage.</p>
<form method="get" class="inline-form">
 <input type="hidden" name="page" value="admin"><input type="hidden" name="section" value="promotions">
 <label>Promotion code<input name="search" value="<?=e($search)?>" maxlength="40" placeholder="Search codes"></label>
 <label>Status<select name="status"><?php foreach([''=>'All','active'=>'Active','scheduled'=>'Scheduled','expired'=>'Expired','disabled'=>'Disabled'] as $key=>$label):?><option value="<?=e($key)?>" <?=$status===$key?'selected':''?>><?=e($label)?></option><?php endforeach;?></select></label>
 <button class="btn btn-outline">Search</button><a href="<?=e(url('admin',['section'=>'promotions']))?>">Clear</a>
</form>
<div class="admin-split"><section class="admin-card"><h2>Promotions</h2><div class="table-wrap"><table>
<thead><tr><th>Code</th><th>Discount</th><th>Minimum spend</th><th>Validity (MYT)</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($promotions as $p):?><tr>
<td><?=e($p['promo_code'])?></td><td><?=e($p['discount_rate'])?>%</td><td><?=money($p['minimum_purchase_amount'])?></td>
<td><?=e($p['start_date'])?><br><?=e($p['end_date'])?></td><td><span class="badge"><?=e(promotion_status($p))?></span></td>
<td><a href="<?=e(url('admin',['section'=>'promotions','edit'=>$p['id']]))?>">View / edit</a>
<form method="post" data-confirm="Delete this promotion? Existing order totals and saved discount details will be retained."><?php admin_hidden('admin_promotion_delete','promotions',(int)$p['id']);?><button class="text-button danger">Delete</button></form></td>
</tr><?php endforeach;?>
<?php if(!$promotions):?><tr><td colspan="6">No promotions found.</td></tr><?php endif;?>
</tbody></table></div></section>
<section class="admin-card"><h2><?=$editing?'Edit promotion':'Add promotion'?></h2>
<form method="post" class="form-grid"><?php admin_hidden('admin_promotion_save','promotions',$editId);?>
<label>Promotion code<input name="promo_code" value="<?=e($value('promo_code'))?>" maxlength="40" pattern="[A-Za-z0-9][A-Za-z0-9_-]{0,39}" required></label>
<small>Codes are saved in uppercase. Use letters, numbers, hyphens or underscores.</small>
<label>Discount percentage<input type="number" name="discount_rate" value="<?=e($value('discount_rate'))?>" min="0.01" max="100" step="0.01" required></label>
<label>Minimum purchase amount · RM<input type="number" name="minimum_purchase_amount" value="<?=e($value('minimum_purchase_amount','0.00'))?>" min="0" max="999999.99" step="0.01" required></label>
<label>Start date and time · MYT<input type="datetime-local" name="start_date" value="<?=e($dateValue('start_date'))?>" required></label>
<label>End date and time · MYT<input type="datetime-local" name="end_date" value="<?=e($dateValue('end_date'))?>" required></label>
<label class="checkbox"><input type="checkbox" name="active" value="1" <?=$enabled?'checked':''?>>Enabled</label>
<small>The promotion is valid through the exact end time entered above.</small>
<button class="btn">Save promotion</button>
<?php if($editing):?><a href="<?=e(url('admin',['section'=>'promotions']))?>">Add another promotion</a><?php endif;?>
</form></section></div>
