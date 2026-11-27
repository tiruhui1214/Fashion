<?php
/** Build bounded, per-product variant groups without additional database queries. */
function new_in_presenters(array $products,array $variants): array {
    $grouped=[];
    foreach($variants as $variant){
        if(empty($variant['active']))continue;
        $variant['stock']=max(0,(int)$variant['stock']);
        $variant['hex']=preg_match('/^#[0-9a-f]{6}$/i',(string)$variant['hex'])?$variant['hex']:'#808080';
        $grouped[(int)$variant['product_id']][]=$variant;
    }
    $items=[];
    foreach(array_slice($products,0,8) as $product){
        $rows=$grouped[(int)$product['id']]??[];$selected=null;$colors=[];
        foreach($rows as $variant){
            $colors[$variant['color']]??=$variant;
            if($selected===null&&$variant['stock']>0)$selected=$variant;
        }
        $available=$selected!==null;$selected??=$rows[0]??null;
        $items[]=$product+['variants'=>$rows,'colors'=>$colors,'selected'=>$selected,'available'=>$available];
    }
    return $items;
}
