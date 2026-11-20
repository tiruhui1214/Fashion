<?php
declare(strict_types=1);

/** Pure input rules shared by administration and tests. Rates are percentages. */
function promotion_values(array $input): array {
    $read = static function(string $key) use ($input): string {
        $value=$input[$key]??'';
        if(!is_scalar($value))throw new InvalidArgumentException('Invalid promotion field.');
        return trim((string)$value);
    };
    $code=strtoupper($read('promo_code'));
    if(!preg_match('/^[A-Z0-9][A-Z0-9_-]{0,39}$/D',$code))throw new InvalidArgumentException('Use 1–40 letters, numbers, hyphens or underscores for the code, starting with a letter or number.');
    $decimal=static function(string $value,string $label,int $max): string {
        if(!preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D',$value))throw new InvalidArgumentException($label.' must be a non-negative number with at most two decimal places.');
        [$whole,$fraction]=array_pad(explode('.',$value,2),2,'');
        $cents=(int)$whole*100+(int)str_pad($fraction,2,'0');
        if($cents>$max)throw new InvalidArgumentException($label.' is too large.');
        return intdiv($cents,100).'.'.str_pad((string)($cents%100),2,'0',STR_PAD_LEFT);
    };
    $rate=$decimal($read('discount_rate'),'Discount percentage',10000);
    if($rate==='0.00')throw new InvalidArgumentException('Discount percentage must be greater than zero and no more than 100.');
    $minimum=$decimal($read('minimum_purchase_amount'),'Minimum purchase amount',99999999);
    $date=static function(string $value): string {
        $tz=new DateTimeZone('Asia/Kuala_Lumpur');
        $d=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$value,$tz);
        if(!$d||$d->format('Y-m-d\TH:i')!==$value||(int)$d->format('Y')<1000)throw new InvalidArgumentException('Enter valid promotion start and end dates and times.');
        return $d->format('Y-m-d H:i:s');
    };
    $start=$date($read('start_date'));$end=$date($read('end_date'));
    if($end<$start)throw new InvalidArgumentException('End date must be on or after the start date.');
    $active=$read('active');
    if(!in_array($active,['','0','1'],true))throw new InvalidArgumentException('Invalid enabled setting.');
    return ['promo_code'=>$code,'discount_rate'=>$rate,'minimum_purchase_amount'=>$minimum,'start_date'=>$start,'end_date'=>$end,'active'=>$active==='1'?1:0];
}

function promotion_status(array $promotion,?DateTimeImmutable $now=null): string {
    if(!(int)$promotion['active'])return 'Disabled';
    $now=($now??new DateTimeImmutable('now',new DateTimeZone('Asia/Kuala_Lumpur')))->setTimezone(new DateTimeZone('Asia/Kuala_Lumpur'))->format('Y-m-d H:i:s');
    if($now<$promotion['start_date'])return 'Scheduled';
    if($now>$promotion['end_date'])return 'Expired';
    return 'Active';
}

function promotion_schema_ready(): bool {
    return one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='promotions'")!==null;
}
