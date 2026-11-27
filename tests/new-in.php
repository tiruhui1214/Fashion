<?php
require dirname(__DIR__).'/app/new-in-data.php';
$checks=0;
function verify($condition,$message){global $checks;$checks++;if(!$condition)throw new RuntimeException($message);}
$products=[['id'=>1,'name'=>'Shirt','price'=>'129.90','department'=>'Women','description'=>'Cotton','material'=>'Cotton'],['id'=>2,'name'=>'Coat','price'=>'200.00','department'=>'Men','description'=>'Warm','material'=>'Wool']];
$variants=[
 ['id'=>10,'product_id'=>1,'color'=>'Black','hex'=>'#000000','size'=>'S','stock'=>0,'active'=>1,'image'=>'assets/product-ink.svg'],
 ['id'=>11,'product_id'=>1,'color'=>'Black','hex'=>'#000000','size'=>'M','stock'=>4,'active'=>1,'image'=>'assets/product-ink.svg'],
 ['id'=>12,'product_id'=>1,'color'=>'Sand','hex'=>'invalid','size'=>'S','stock'=>3,'active'=>1,'image'=>'assets/product-sand.svg'],
 ['id'=>13,'product_id'=>1,'color'=>'Red','hex'=>'#ff0000','size'=>'L','stock'=>9,'active'=>0,'image'=>'assets/product-clay.svg'],
 ['id'=>20,'product_id'=>2,'color'=>'Grey','hex'=>'#999999','size'=>'L','stock'=>0,'active'=>1,'image'=>'assets/product-ink.svg'],
 ['id'=>30,'product_id'=>999,'color'=>'Alien','hex'=>'#fff000','size'=>'S','stock'=>10,'active'=>1,'image'=>'assets/product-clay.svg'],
];
$items=new_in_presenters($products,$variants);
verify(count($items)===2,'Keep published products including sold-out previews');
verify($items[0]['selected']['id']===11,'Initial selection must choose stocked variant');
verify(count($items[0]['variants'])===3,'Inactive variants must be excluded');
verify(count($items[0]['colors'])===2,'Group unique colours without foreign product leakage');
verify($items[0]['colors']['Sand']['hex']==='#808080','Invalid colour hex must use safe fallback');
verify($items[1]['available']===false,'All sold-out variants must disable purchase');
verify($items[1]['selected']['id']===20,'Sold-out product can retain representative image');
verify(new_in_presenters([],[])===[],'Empty catalog must be safe');
$empty=new_in_presenters([$products[0]],[])[0];
verify($empty['selected']===null&&!$empty['available'],'Product without variants must not create fake variant');
verify(count(new_in_presenters(array_fill(0,12,$products[0]),$variants))===8,'Queue must limit eager presentation to eight');
verify($items[0]['price']==='129.90','Price must remain sourced from product');
verify($items[0]['variants'][0]['stock']===0,'Retain actual sold-out state');
echo "$checks New In data checks passed.\n";
