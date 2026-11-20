<?php
declare(strict_types=1);
// Service contract tests use an in-memory catalogue, never an external API or DB.
require dirname(__DIR__).'/app/feature-rules.php';
require dirname(__DIR__).'/app/recommendations.php';
$catalog=[
 ['id'=>1,'variant_id'=>1,'name'=>'Shirt','price'=>'100.00','style'=>'minimalist','occasion'=>'work','category'=>'Shirts','department'=>'Women','color'=>'Black','size'=>'M','image'=>'assets/product-sage.svg'],
 ['id'=>2,'variant_id'=>2,'name'=>'Trousers','price'=>'80.00','style'=>'minimalist','occasion'=>'work','category'=>'Trousers','department'=>'Women','color'=>'Black','size'=>'M','image'=>'assets/product-sage.svg'],
 ['id'=>3,'variant_id'=>3,'name'=>'Dress','price'=>'120.00','style'=>'elegant','occasion'=>'dinner','category'=>'Dresses','department'=>'Women','color'=>'Black','size'=>'M','image'=>'assets/product-sage.svg'],
];
function all(string $sql,array $params=[]): array {global $catalog;return $catalog;}
$checks=0;
function check_service(bool $yes): void {global $checks;if(!$yes)throw new RuntimeException('Recommendation service contract failed.');$checks++;}
$result=recommendations([],'',[],[['product_id'=>1,'category'=>'Shirts','department'=>'Women']]);
check_service(array_column($result['items'],'id')===[2]);
check_service($result['mode']==='Catalogue rules');
$prefs=['color'=>'black','size'=>'M','budget'=>9000];$result=recommendations($prefs);
check_service(array_column($result['items'],'id')===[2]);
$catalog[1]['price']='95.00';check_service(refresh_recommendations($result)['items']===[]);
$catalog[1]['price']='85.00';check_service(refresh_recommendations($result)['items'][0]['price']==='85.00');
$catalog=array_values(array_filter($catalog,fn($p)=>$p['variant_id']!==2));check_service(refresh_recommendations($result)['items']===[]);
$clean=clean_model_preferences(['budget'=>-1,'department'=>'Unknown','size'=>'XXXL'],['budget'=>5000,'department'=>'Women','size'=>'M']);
check_service($clean['budget']===5000);check_service($clean['department']==='');check_service($clean['size']==='');
$response=json_encode(['choices'=>[['message'=>['content'=>json_encode(['preferences'=>['color'=>'black'],'recommendations'=>[['id'=>2,'reason'=>'A complementary bottom.']]])]]]]);
check_service(decode_model_response($response)['recommendations'][0]['id']===2);
foreach(['not json','{}','{"choices":[{"message":{"content":"{}"}}]}'] as $bad){
    $caught=false;try{decode_model_response($bad);}catch(Throwable $e){$caught=true;}check_service($caught);
}
echo "$checks recommendation service checks passed.\n";
