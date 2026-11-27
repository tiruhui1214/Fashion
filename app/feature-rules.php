<?php
declare(strict_types=1);
function scalar_value(array $values,string $key,string $default=''): string {
    $v=$values[$key]??$default;
    if(!is_scalar($v))throw new InvalidArgumentException('Invalid '.str_replace('_',' ',$key).'.');
    return trim((string)$v);
}
function to_cents(string $value): int {
    if(!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D',$value))throw new InvalidArgumentException('Invalid monetary amount.');
    [$whole,$fraction]=array_pad(explode('.',$value,2),2,'');
    return (int)$whole*100+(int)str_pad($fraction,2,'0');
}
function from_cents(int $value): string {return intdiv($value,100).'.'.str_pad((string)($value%100),2,'0',STR_PAD_LEFT);}
function discount_cents(int $subtotal,string $rate): int {
    $basis=to_cents($rate);
    if($basis<=0||$basis>10000)throw new InvalidArgumentException('Invalid discount percentage.');
    return min($subtotal,intdiv($subtotal*$basis+5000,10000));
}
function checkout_fingerprint(array $rows,array $quote): string {
    $items=array_map(fn($r)=>[(int)$r['id'],(int)$r['variant_id'],(int)$r['quantity'],to_cents((string)$r['price'])],$rows);
    return hash('sha256',json_encode([$items,$quote['subtotal'],$quote['discount'],$quote['shipping'],$quote['total'],$quote['code']],JSON_THROW_ON_ERROR));
}
function size_options(): array {return ['XS','S','M','L','XL','XXL','3-4Y','5-6Y','7-8Y','9-10Y'];}
function malaysia_states(): array {return ['Johor','Kedah','Kelantan','Kuala Lumpur','Labuan','Melaka','Negeri Sembilan','Pahang','Penang','Perak','Perlis','Putrajaya','Sabah','Sarawak','Selangor','Terengganu'];}
function validated_phone(string $phone,bool $optional=false): string {
    if($optional&&$phone==='')return '';
    if(!preg_match('/^[+0-9 ()-]{8,30}$/D',$phone)||strlen(preg_replace('/\D/','',$phone))<8)throw new InvalidArgumentException('Enter a valid phone number.');
    return $phone;
}
function address_values(array $input): array {
    $result=[];
    foreach(['label'=>60,'recipient'=>100,'phone'=>30,'line1'=>255,'line2'=>255,'city'=>100,'state'=>100,'postcode'=>10,'country'=>60] as $key=>$limit){
        $v=scalar_value($input,$key,$key==='country'?'Malaysia':'');
        if(($v===''&&$key!=='line2')||mb_strlen($v)>$limit)throw new InvalidArgumentException('Enter a valid '.str_replace('_',' ',$key).'.');
        $result[$key]=$v;
    }
    validated_phone($result['phone']);
    if(!preg_match('/^\d{5}$/D',$result['postcode']))throw new InvalidArgumentException('Enter a five-digit Malaysian postcode.');
    if(!in_array($result['state'],malaysia_states(),true)||$result['country']!=='Malaysia')throw new InvalidArgumentException('Choose a Malaysian state. Delivery is within Malaysia.');
    $result['is_default']=scalar_value($input,'is_default')==='1'?1:0;
    return $result;
}
