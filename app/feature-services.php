<?php
require_once __DIR__.'/feature-rules.php';
require_once __DIR__.'/promotions.php';
function feature_required_columns(): array {
    $required=[];
    foreach(require dirname(__DIR__).'/database/feature-schema.php' as $table=>$columns)$required[$table]=array_keys($columns);
    $required['orders']=array_merge($required['orders'],['promotion_id','promo_code_used','discount_amount']);
    $required['promotions']=['id','created_by_admin_id','promo_code','discount_rate','minimum_purchase_amount','start_date','end_date','active'];
    $required['payments']=['id','order_id','amount','payment_method','payment_status','payment_reference','paid_at'];
    $required['order_status_history']=['id','order_id','actor_user_id','from_status','to_status','changed_at'];
    return $required;
}
function feature_missing_columns(array $actual): array {
    $present=[];foreach($actual as $r)$present[$r['TABLE_NAME'].'.'.$r['COLUMN_NAME']]=true;
    $missing=[];foreach(feature_required_columns() as $table=>$columns)foreach($columns as $column)if(!isset($present[$table.'.'.$column]))$missing[]=$table.'.'.$column;
    return $missing;
}
function feature_schema_ready(): bool {
    return one("SELECT setting_value FROM settings WHERE setting_key='features_version' AND setting_value='20261120'")!==null
        && feature_missing_columns(all('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()'))===[];
}
function selected_cart(int $uid,bool $lock=false): array {return array_values(array_filter(cart_rows($uid,$lock),fn($r)=>(bool)$r['is_selected']));}
function checkout_quote(array $rows,string $code='',bool $lock=false): array {
    $subtotal=cart_subtotal($rows);$discount=0;$promotion=null;
    if($code!==''){
        $promotion=one('SELECT * FROM promotions WHERE promo_code=?'.($lock?' FOR UPDATE':''),[strtoupper($code)]);
        if(!$promotion||promotion_status($promotion)!=='Active')throw new RuntimeException('Promotion is invalid, disabled, not started or expired. Remove it or use another code.');
        if($subtotal<to_cents((string)$promotion['minimum_purchase_amount']))throw new RuntimeException('This code requires a merchandise subtotal of '.money($promotion['minimum_purchase_amount']).'.');
        $discount=discount_cents($subtotal,(string)$promotion['discount_rate']);
    }
    $shipping=$rows&&$subtotal<20000?800:0;
    $total=$subtotal-$discount+$shipping;
    if($total>9999999999)throw new RuntimeException('Order total exceeds the supported limit. Reduce the quantity.');
    return ['subtotal'=>$subtotal,'discount'=>$discount,'shipping'=>$shipping,'total'=>$total,'code'=>$promotion['promo_code']??'','promotion_id'=>$promotion['id']??null];
}
function quote_signature(array $rows,array $quote): string {
    $_SESSION['quote_secret']??=bin2hex(random_bytes(32));
    return hash_hmac('sha256',checkout_fingerprint($rows,$quote),$_SESSION['quote_secret']);
}
function checkout_address(int $uid,array $input): array {
    $id=filter_var($input['address_id']??0,FILTER_VALIDATE_INT);
    if($id===false||$id<0)throw new RuntimeException('Choose a valid address.');
    if($id){
        $address=one('SELECT * FROM addresses WHERE id=? AND user_id=?',[$id,$uid]);
        if(!$address)throw new RuntimeException('Saved address not found.');
        // Validate legacy incomplete records too: ask the customer to finish them.
        $value=address_values($address);$value['id']=$id;return $value;
    }
    $value=address_values(['label'=>'Checkout']+$input);$value['id']=null;return $value;
}
function address_snapshot(array $a): string {return implode(', ',array_filter([$a['line1'],$a['line2'],$a['postcode'].' '.$a['city'],$a['state'],$a['country']],fn($v)=>$v!==''));}
function review_summary(int $pid): array {return one('SELECT COUNT(*) review_count,COALESCE(AVG(rating),0) average_rating FROM reviews WHERE product_id=? AND is_hidden=0 AND customer_deleted=0',[$pid]);}
function record_status(int $oid,?int $actor,?string $from,string $to): void {
    query('INSERT INTO order_status_history(order_id,actor_user_id,from_status,to_status) VALUES(?,?,?,?)',[$oid,$actor,$from,$to]);
}
function ensure_deletable_variant(int $variantId): void {
    if(one('SELECT id FROM order_items WHERE variant_id=? LIMIT 1',[$variantId]))throw new RuntimeException('This variant has order history. Disable it instead of deleting it.');
}
function feature_search_form(string $section,string $label='Search'): void { ?>
<form method="get" class="inline-form"><input type="hidden" name="page" value="admin"><input type="hidden" name="section" value="<?=e($section)?>"><label><?=e($label)?><input name="search" maxlength="100" value="<?=e(is_string($_GET['search']??null)?$_GET['search']:'')?>"></label><button class="btn btn-outline">Search</button><a href="<?=e(url('admin',['section'=>$section]))?>">Clear</a></form>
<?php }
