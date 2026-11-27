<?php
declare(strict_types=1);
function parse_preferences(string $message,array $previous): array {
    if(preg_match('/\b(?:start over|new search|reset preferences)\b/i',$message))$previous=[];
    $p=$previous+['budget'=>null,'color'=>'','size'=>'','occasion'=>'','style'=>'','department'=>'','product_type'=>'','exclude'=>[]];
    $m=mb_strtolower($message);
    // A department switch or explicit department + garment starts a new outfit brief; keep budget.
    foreach(['Women','Men','Kids'] as $dep)if(preg_match('/\b'.strtolower($dep).'\b/',$m)){
        if((($p['department']??'')!==''&&$p['department']!==$dep)||preg_match('/\b(?:shirts?|tops?|blouses?|tees?|t-shirts?|trousers?|pants?|jeans?|skirts?|shorts?|dresses|dress|jackets?|coats?|blazers?|sets?|shoes?)\b/i',$message)){
            foreach(['color','size','occasion','style','product_type'] as $field)$p[$field]='';
            $p['exclude']=[];
        }
        $p['department']=$dep;
    }
    $typeMessage=preg_replace('/(?:no|without|avoid)\s+(?:a\s+)?(?:dress(?:es)?|skirts?)\b/i','',$m);
    foreach(['top'=>'shirts?|tops?|blouses?|tees?|t-shirts?','bottom'=>'trousers?|pants?|jeans?|skirts?|shorts?','dress'=>'dresses|dress','outer'=>'jackets?|coats?|blazers?|outerwear','set'=>'sets?','shoe'=>'shoes?|heels?|sneakers?|boots?','accessory'=>'bags?|accessories|belts?|scarves'] as $type=>$terms)
        if(preg_match('/\b(?:'.$terms.')\b/i',$typeMessage))$p['product_type']=$type;

    if(preg_match('/(?:rm\s*|budget(?:\s+(?:to|of|is))?\s*|预算\s*)(\d+(?:\.\d{1,2})?)/iu',$message,$v))$p['budget']=to_cents($v[1]);
    foreach(['black'=>'黑','white'=>'白','ivory'=>'象牙','beige'=>'米色','blue'=>'蓝','red'=>'红','green'=>'绿','brown'=>'棕','grey'=>'灰','pink'=>'粉','clay'=>'陶土','sage'=>'鼠尾草','sand'=>'沙色','ink'=>'墨色'] as $en=>$cn)if(preg_match('/\b'.$en.'\b/i',$m)||mb_strpos($m,$cn)!==false)$p['color']=$en;
    if(preg_match('/(?:size|尺码)\s*(XXL|XL|XS|S|M|L|3-4Y|5-6Y|7-8Y|9-10Y)\b/i',$message,$v))$p['size']=strtoupper($v[1]);
    foreach(['work'=>'上班','dinner'=>'晚餐','casual'=>'日常','party'=>'派对','school'=>'上课','travel'=>'旅行'] as $en=>$cn)if(($en!=='casual'||!str_contains($m,'smart casual'))&&(preg_match('/\b'.$en.'\b/i',$m)||mb_strpos($m,$cn)!==false))$p['occasion']=$en;
    foreach(['smart casual'=>'商务休闲','minimalist'=>'简约','elegant'=>'优雅','streetwear'=>'街头','classic'=>'经典','sporty'=>'运动'] as $en=>$cn)if(str_contains($m,$en)||mb_strpos($m,$cn)!==false)$p['style']=$en;
    foreach(['Women','Men','Kids'] as $dep)if(preg_match('/\b'.strtolower($dep).'\b/',$m))$p['department']=$dep;
    if(preg_match('/(?:no|without|avoid)\s+(?:a\s+)?(?:dress|dresses|skirts?)\b/i',$message)||str_contains($m,'不要裙'))$p['exclude']=array_values(array_unique(array_merge($p['exclude'],['dress','skirt'])));
    if(str_contains($m,'any style'))$p['style']='';
    if(str_contains($m,'any occasion'))$p['occasion']='';
    if(str_contains($m,'any size'))$p['size']='';
    if(str_contains($m,'any color')||str_contains($m,'任何颜色'))$p['color']='';
    if(str_contains($m,'no budget limit')||str_contains($m,'不限预算'))$p['budget']=null;
    return $p;
}
function complement_role(string $category): string {
    $c=strtolower($category);
    foreach(['dress'=>['dress','连衣裙'],'bottom'=>['trouser','pant','jean','skirt','short','下装'],'top'=>['shirt','top','blouse','tee','knit','sweater','上衣'],'outer'=>['jacket','coat','blazer','outer'],'shoe'=>['shoe','heel','sneaker','boot'],'accessory'=>['bag','accessor','belt','scarf']] as $role=>$terms)foreach($terms as $term)if(str_contains($c,$term))return $role;
    return 'other';
}
function complementary(string $a,string $b): bool {
    return in_array($b,match($a){'top'=>['bottom','outer','shoe','accessory'],'bottom'=>['top','outer','shoe','accessory'],'dress'=>['outer','shoe','accessory'],'outer'=>['top','bottom','dress','shoe','accessory'],'shoe','accessory'=>['top','bottom','dress','outer'],default=>[]},true);
}
function candidate_score(array $p,array $prefs): ?int {
    if(($prefs['budget']??null)!==null&&to_cents((string)$p['price'])>$prefs['budget'])return null;
    if(($prefs['size']??'')!==''&&$prefs['size']!==$p['size'])return null;
    if(($prefs['department']??'')!==''&&$prefs['department']!==$p['department'])return null;
    foreach($prefs['exclude']??[] as $term)if(str_contains(strtolower($p['category']),$term))return null;
    if(($prefs['product_type']??'')!==''&&recommendation_product_type($p)!==$prefs['product_type'])return null;
    $score=1;
    foreach(['color'=>4,'style'=>3,'occasion'=>3] as $field=>$weight){
        $wanted=strtolower($prefs[$field]??'');
        if($wanted==='')continue;
        $actual=strtolower($p[$field]??'');
        if(!str_contains($actual,$wanted))return null;
        $score+=$weight;
    }
    return $score;
}
function valid_model_recommendations(array $items,array $catalog): array {
    $allowed=array_fill_keys(array_column($catalog,'id'),true);$seen=[];$result=[];
    foreach($items as $item){
        if(!is_array($item))continue;
        $id=filter_var($item['id']??null,FILTER_VALIDATE_INT);
        if(!$id||!isset($allowed[$id])||isset($seen[$id])||!is_string($item['reason']??null))continue;
        $reason=trim(strip_tags($item['reason']));if($reason==='')continue;
        $seen[$id]=true;$result[]=['id'=>$id,'reason'=>mb_substr($reason,0,400)];
        if(count($result)>=6)break;
    }
    return $result;
}

function recommendation_product_type(array $product): string {
    if(preg_match('/\bsets?\b/i',$product['category']??''))return 'set';
    return complement_role($product['category']??'');
}
function recommendation_no_match_notice(array $catalog,array $prefs): string {
    $basePrefs=$prefs;
    foreach(['color','style','occasion'] as $field)$basePrefs[$field]='';
    $base=array_values(array_filter($catalog,fn($p)=>candidate_score($p,$basePrefs)!==null));
    $scope=trim(($prefs['department']??'').' '.($prefs['product_type']??'product'));
    $color=$prefs['color']??'';
    if($color!==''&&$base&&!array_filter($base,fn($p)=>strcasecmp($p['color'],$color)===0)){
        $colors=array_values(array_unique(array_column($base,'color')));sort($colors);
        return 'No in-stock '.$scope.' matches the colour '.ucfirst($color).'. Available colours for the other current filters: '.implode(', ',$colors).'. Tell me a different colour to continue.';
    }
    if($base&&(($prefs['style']??'')!==''||($prefs['occasion']??'')!==''))return 'No products match all current filters, including the style / occasion tags. Try any style or any occasion, or start a new search.';
    return 'No active, in-stock products match your current department, product type, size and budget. Try a different filter or start a new search.';
}
