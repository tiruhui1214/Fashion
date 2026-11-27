<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/feature-rules.php';
require dirname(__DIR__).'/app/recommendation-rules.php';
$checks=0;
function expect($actual,$expected): void {global $checks;$checks++;if($actual!==$expected)throw new RuntimeException('Expected '.var_export($expected,true).', got '.var_export($actual,true));}
function invalid(callable $f): void {global $checks;try{$f();}catch(InvalidArgumentException $e){$checks++;return;}throw new RuntimeException('Expected invalid input.');}
expect(to_cents('19.99'),1999);expect(to_cents('0.01'),1);expect(discount_cents(999,'10.00'),100);expect(discount_cents(10001,'100.00'),10001);expect(discount_cents(10000,'10.25'),1025);
foreach(['-1','1.001','1e3','NaN'] as $s)invalid(fn()=>to_cents($s));
$rows=[['id'=>1,'variant_id'=>2,'quantity'=>1,'price'=>'19.99']];$q=['subtotal'=>1999,'discount'=>200,'shipping'=>800,'total'=>2599,'code'=>'TEN'];
$hash=checkout_fingerprint($rows,$q);expect($hash===checkout_fingerprint($rows,$q),true);
expect($hash===checkout_fingerprint([array_replace($rows[0],['price'=>'20.00'])],$q),false);
expect($hash===checkout_fingerprint([array_replace($rows[0],['quantity'=>2])],$q),false);
expect($hash===checkout_fingerprint($rows,array_replace($q,['discount'=>0])),false);
$addr=['label'=>'Home','recipient'=>'Test User','phone'=>'0123456789','line1'=>'Street 1','line2'=>'','city'=>'KL','state'=>'Kuala Lumpur','postcode'=>'50000','country'=>'Malaysia','is_default'=>'1'];
expect(address_values($addr)['is_default'],1);invalid(fn()=>address_values(array_replace($addr,['phone'=>'x'])));invalid(fn()=>address_values(array_replace($addr,['postcode'=>'123'])));invalid(fn()=>address_values(array_replace($addr,['state'=>'unknown'])));
expect(in_array('XXL',size_options(),true),true);expect(in_array('9-10Y',size_options(),true),true);
$prefs=parse_preferences('Smart casual dinner in black budget RM 150 size M',[]);
expect($prefs['budget'],15000);expect($prefs['color'],'black');expect($prefs['size'],'M');expect($prefs['occasion'],'dinner');expect($prefs['style'],'smart casual');
$next=parse_preferences('change budget to RM120', $prefs);expect($next['budget'],12000);expect($next['color'],'black');expect($next['occasion'],'dinner');
$next=parse_preferences('不要裙子',$next);expect(in_array('dress',$next['exclude'],true),true);
$p=['id'=>1,'category'=>'Shirts','style'=>'smart casual','occasion'=>'work, dinner','price'=>'89.00','color'=>'Black','size'=>'M','department'=>'Women'];
expect(candidate_score($p,$prefs)>0,true);expect(candidate_score(array_replace($p,['price'=>'160.00']),$prefs),null);expect(candidate_score(array_replace($p,['size'=>'S']),$prefs),null);
expect(complement_role('Shirts'), 'top');expect(complement_role('Trousers'),'bottom');expect(complement_role('Dresses'),'dress');
expect(complementary('top','bottom'),true);expect(complementary('top','top'),false);expect(complementary('dress','bottom'),false);
$filtered=valid_model_recommendations([['id'=>1,'reason'=>'Fits your dinner preference.'],['id'=>999,'reason'=>'Fake'],['id'=>1,'reason'=>'duplicate']],[$p]);expect(count($filtered),1);expect($filtered[0]['id'],1);
echo "$checks feature rules checks passed.\n";
