<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/assistant-service.php';
$_SESSION=[];$calls=0;
function fake_recommender(array $preferences,string $message,array $history): array {
 global $calls;$calls++;
 return ['items'=>[['id'=>1,'name'=>'Test Shirt']],'preferences'=>$preferences,'mode'=>'Catalogue rules','notice'=>''];
}
function parse_preferences(string $message,array $previous): array {return $previous+['color'=>'black'];}
$first=assistant_send_message('black shirt','request-00001','fake_recommender');
if(count($first['messages'])!==2||$calls!==1)throw new RuntimeException('Message did not produce a response.');
assistant_send_message('black shirt','request-00001','fake_recommender');
if($calls!==1)throw new RuntimeException('Duplicate request was sent twice.');
$caught=false;try{assistant_send_message('new message','request-00002','fake_recommender');}catch(RuntimeException $e){$caught=true;}
if(!$caught)throw new RuntimeException('Rate limit missing.');
assistant_reset_conversation();
if(isset($_SESSION['assistant'])||!isset($_SESSION['assistant_last']))throw new RuntimeException('Reset must clear conversation but preserve the rate timestamp.');
$caught=false;try{assistant_send_message('after reset','request-00003','fake_recommender');}catch(AssistantRateLimitException $e){$caught=true;}
if(!$caught)throw new RuntimeException('Reset bypassed the rate limit.');
foreach(['',str_repeat('a',601)] as $bad){$caught=false;try{assistant_send_message($bad,'request-00003','fake_recommender');}catch(InvalidArgumentException $e){$caught=true;}if(!$caught)throw new RuntimeException('Invalid message accepted.');}
echo "7 floating assistant service checks passed.\n";
