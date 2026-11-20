<?php
declare(strict_types=1);
class AssistantRateLimitException extends RuntimeException {}
function assistant_reset_conversation(): void {
    unset($_SESSION['assistant'],$_SESSION['assistant_request_ids']);
}
function assistant_send_message(string $message,string $requestId='',?callable $recommender=null): array {
    $message=trim($message);
    if($message===''||mb_strlen($message)>600)throw new InvalidArgumentException('Enter a request between 1 and 600 characters.');
    if($requestId!==''&&!preg_match('/^[a-zA-Z0-9_-]{8,80}$/D',$requestId))throw new InvalidArgumentException('Invalid request. Please try again.');
    if($requestId!==''&&in_array($requestId,$_SESSION['assistant_request_ids']??[],true))return $_SESSION['assistant'];
    if(time()-(int)($_SESSION['assistant_last']??0)<2)throw new AssistantRateLimitException('Please wait a moment before sending another request.');
    $history=$_SESSION['assistant']??['preferences'=>[],'messages'=>[]];
    $history['preferences']=parse_preferences($message,$history['preferences']);
    $history['messages'][]=['role'=>'user','text'=>$message];$history['messages']=array_slice($history['messages'],-11);
    $_SESSION['assistant_last']=time();
    $result=($recommender??'recommendations')($history['preferences'],$message,$history['messages']);
    $history['result']=$result;$history['preferences']=$result['preferences'];
    $names=array_column($result['items'],'name');
    $history['messages'][]=['role'=>'assistant','text'=>$names?'Suggested: '.implode(', ',$names).'. Explore the pieces below.':'No matching products found. Try changing your colour, size, style or budget.'];
    $history['messages']=array_slice($history['messages'],-12);
    $_SESSION['assistant']=$history;
    if($requestId!=='')$_SESSION['assistant_request_ids']=array_slice(array_merge($_SESSION['assistant_request_ids']??[],[$requestId]),-20);
    return $history;
}
