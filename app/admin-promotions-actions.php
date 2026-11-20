<?php
require_once __DIR__.'/promotions.php';
if(!promotion_schema_ready())throw new RuntimeException('Run the promotion database upgrade before managing promotions.');
$id=integer_input('id',0);
if($action==='admin_promotion_save'){
    $values=promotion_values($_POST);
    if($id&&!one('SELECT id FROM promotions WHERE id=?',[$id]))throw new RuntimeException('Promotion not found.');
    if(one('SELECT id FROM promotions WHERE promo_code=? AND id<>?',[$values['promo_code'],$id]))throw new RuntimeException('This promotion code already exists.');
    $params=array_values($values);
    if($id){
        query('UPDATE promotions SET promo_code=?,discount_rate=?,minimum_purchase_amount=?,start_date=?,end_date=?,active=? WHERE id=?',array_merge($params,[$id]));
    }else{
        query('INSERT INTO promotions(promo_code,discount_rate,minimum_purchase_amount,start_date,end_date,active,created_by_admin_id) VALUES(?,?,?,?,?,?,?)',array_merge($params,[(int)$u['id']]));
    }
    flash('Promotion saved.');
}elseif($action==='admin_promotion_delete'){
    if(!$id||!one('SELECT id FROM promotions WHERE id=?',[$id]))throw new RuntimeException('Promotion not found.');
    query('DELETE FROM promotions WHERE id=?',[$id]);
    flash('Promotion deleted. Existing order totals are unchanged.');
}else{
    throw new RuntimeException('Unknown promotion action.');
}
go('admin',['section'=>'promotions']);
