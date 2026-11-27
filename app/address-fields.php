<?php
// Caller supplies $addressFields and $addressPrefix (default '') for unique IDs.
$addressFields=$addressFields??[];$addressPrefix=$addressPrefix??'';
foreach(['recipient'=>'Recipient name','phone'=>'Phone','line1'=>'Street address','line2'=>'Address line 2 (optional)','city'=>'City','postcode'=>'Postcode'] as $key=>$label):
$length=['recipient'=>100,'phone'=>30,'line1'=>255,'line2'=>255,'city'=>100,'postcode'=>5][$key]; ?>
<label><?=e($label)?><input id="<?=e($addressPrefix.$key)?>" name="<?=e($key)?>" value="<?=old($key,$addressFields[$key]??'')?>" maxlength="<?=$length?>" <?=$key==='line2'?'':'required'?> <?=$key==='postcode'?'pattern="[0-9]{5}"':''?>></label>
<?php endforeach; ?>
<label>State<select name="state" id="<?=e($addressPrefix)?>state" required><option value="">Choose state</option><?php foreach(malaysia_states() as $state):?><option value="<?=e($state)?>" <?=($old['state']??$addressFields['state']??'')===$state?'selected':''?>><?=e($state)?></option><?php endforeach;?></select></label>
<input type="hidden" name="country" value="Malaysia"><p>Delivery within Malaysia.</p>
