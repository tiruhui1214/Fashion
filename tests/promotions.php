<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/promotions.php';
$checks=0;
function same($expected,$actual): void {global $checks;$checks++;if($expected!==$actual)throw new RuntimeException('Expected '.var_export($expected,true).', got '.var_export($actual,true));}
function rejects(array $overrides): void {
 global $input,$checks;
 try {promotion_values(array_replace($input,$overrides));} catch (InvalidArgumentException $e) {$checks++;return;}
 throw new RuntimeException('Expected invalid promotion to be rejected.');
}
$input=['promo_code'=>' summer10 ','discount_rate'=>'10.25','minimum_purchase_amount'=>'100.00','start_date'=>'2026-11-20T09:00','end_date'=>'2026-12-20T23:59','active'=>'1'];
$value=promotion_values($input);
same('SUMMER10',$value['promo_code']);same('10.25',$value['discount_rate']);same('100.00',$value['minimum_purchase_amount']);same('2026-11-20 09:00:00',$value['start_date']);same(1,$value['active']);
foreach(['0','-1','100.01','1e2','abc','1.001'] as $rate)rejects(['discount_rate'=>$rate]);
same('100.00',promotion_values(array_replace($input,['discount_rate'=>'100']))['discount_rate']);
foreach(['-1','1.001','1e3','100000000'] as $minimum)rejects(['minimum_purchase_amount'=>$minimum]);
foreach(['','BAD CODE','<script>','éTOILE',str_repeat('A',41)] as $code)rejects(['promo_code'=>$code]);
rejects(['start_date'=>'2026-02-30T10:00']);rejects(['end_date'=>'2026-11-19T09:00']);rejects(['active'=>[]]);rejects(['promo_code'=>[]]);
same(0,promotion_values(array_diff_key($input,['active'=>true]))['active']);
same('0.00',promotion_values(array_replace($input,['minimum_purchase_amount'=>'0']))['minimum_purchase_amount']);
$tz=new DateTimeZone('Asia/Kuala_Lumpur');
same('Scheduled',promotion_status($value,new DateTimeImmutable('2026-11-20 08:59:59',$tz)));
same('Active',promotion_status($value,new DateTimeImmutable('2026-11-20 09:00:00',$tz)));
same('Active',promotion_status($value,new DateTimeImmutable('2026-12-20 23:59:00',$tz)));
same('Expired',promotion_status($value,new DateTimeImmutable('2026-12-20 23:59:01',$tz)));
same('Disabled',promotion_status(array_replace($value,['active'=>0]),new DateTimeImmutable('2026-11-21',$tz)));
echo "$checks promotion checks passed.\n";
