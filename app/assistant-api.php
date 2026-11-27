<?php
if(!defined('ROOT')){http_response_code(404);exit;}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
require_once __DIR__.'/recommendations.php';require_once __DIR__.'/assistant-service.php';
$status=200;$error='';
try {
    if(!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)){header('Allow: GET, POST');$status=405;throw new RuntimeException('Method not allowed.');}
    if($_SERVER['REQUEST_METHOD']==='POST'){
        if(!$currentUser){$status=401;throw new RuntimeException('Please sign in to continue chatting.');}
        try{check_csrf();}catch(Throwable $e){$status=419;throw new RuntimeException('Your session changed. Please send your request again.');}
        $action=scalar_value($_POST,'action');
        if($action==='reset')assistant_reset_conversation();
        elseif($action==='message'){
            try{assistant_send_message(scalar_value($_POST,'message'),scalar_value($_POST,'request_id'));}
            catch(InvalidArgumentException $e){$status=422;throw $e;}
            catch(AssistantRateLimitException $e){$status=429;throw $e;}
        }else{$status=422;throw new RuntimeException('Unknown chat action.');}
    }
}catch(Throwable $exception){
    if($status===200){$status=500;error_log('Assistant request failed: '.$exception->getMessage());$error='The assistant could not respond. Please try again.';}
    else $error=$exception->getMessage();
}
try{ob_start();require __DIR__.'/assistant-content.php';$html=ob_get_clean();}
catch(Throwable $exception){if(ob_get_level())ob_end_clean();$html='';$status=500;$error='The assistant is temporarily unavailable. Please try again.';error_log('Assistant rendering failed: '.$exception->getMessage());}
http_response_code($status);
echo json_encode(['ok'=>$status===200,'html'=>$html,'error'=>$error,'authenticated'=>(bool)$currentUser,'csrf'=>csrf(),'retry_after_ms'=>max(0,(int)ceil(((int)($_SESSION['assistant_last']??0)+2-microtime(true))*1000))],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
exit;
