<?php
$root=$argv[1]??dirname(__DIR__);
require $root.'/app/feature-rules.php';
require $root.'/app/recommendations.php';
define('ROOT',__DIR__);$checks=0;
function verify($yes,$label){global $checks;if(!$yes)throw new RuntimeException($label);$checks++;}
$old=['budget'=>15000,'department'=>'Women','color'=>'black','size'=>'M','style'=>'minimalist','occasion'=>'work'];
$prefs=parse_preferences('kids clay colour shirt',$old);
verify($prefs['department']==='Kids','department');verify($prefs['color']==='clay','Clay colour recognised');
verify($prefs['style']===''&&$prefs['occasion']===''&&$prefs['size']==='','old outfit context cleared');
verify(parse_preferences('kids clay shirt',array_replace($old,['department'=>'Kids']))['occasion']==='','same department new outfit clears stale occasion');
verify($prefs['budget']===15000,'budget retained');verify($prefs['product_type']==='top','shirt intent recognised');
$refined=parse_preferences('change budget to RM120',$old);verify($refined['style']==='minimalist'&&$refined['color']==='black','refinement preserves preferences');
$explicit=parse_preferences('Kids minimalist school clay tops',$old);verify($explicit['occasion']==='school'&&$explicit['style']==='minimalist','explicit new context retained');
foreach(['sage','sand','ink'] as $color)verify(parse_preferences($color,[])['color']===$color,'boutique colour '.$color);
$catalog=[['id'=>1,'variant_id'=>1,'name'=>'Little Days Cotton Set','price'=>'89.90','style'=>'','occasion'=>'','category'=>'Sets','department'=>'Kids','color'=>'Clay','size'=>'3-4Y','image'=>''],['id'=>2,'variant_id'=>2,'name'=>'Everyday Pocket Tee','price'=>'49.90','style'=>'','occasion'=>'','category'=>'Tops','department'=>'Kids','color'=>'Sage','size'=>'3-4Y','image'=>''],['id'=>2,'variant_id'=>3,'name'=>'Everyday Pocket Tee','price'=>'49.90','style'=>'','occasion'=>'','category'=>'Tops','department'=>'Kids','color'=>'Sand','size'=>'3-4Y','image'=>'']];
function all($sql,$params=[]){global $catalog;return $catalog;}
$result=recommendations($prefs);verify(!$result['items'],'no invented Clay shirt');verify(str_contains($result['notice'],'Clay')&&str_contains($result['notice'],'Sage')&&str_contains($result['notice'],'Sand'),'explains actual available colours');
$prefs=parse_preferences('sage',$prefs);$result=recommendations($prefs);verify(count($result['items'])===1&&$result['items'][0]['id']===2,'follow-up Sage returns real top');
$prefs=parse_preferences('Kids clay set',$old);$result=recommendations($prefs);verify(count($result['items'])===1&&$result['items'][0]['id']===1,'Clay set matches');
$clean=context_model_preferences(['department'=>'Kids','style'=>'minimalist','occasion'=>'work','product_type'=>'set','color'=>'sand'],$prefs,'Kids clay set');verify($clean['style']===''&&$clean['occasion']===''&&$clean['color']==='clay'&&$clean['product_type']==='set','model cannot restore stale context or contradict explicit request');
echo "$checks recommendation context checks passed.\n";
