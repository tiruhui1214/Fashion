<?php
if($_SERVER['REQUEST_METHOD']!=='POST')return;
$wishlistJson=($_POST['action']??'')==='wishlist'&&str_contains($_SERVER['HTTP_ACCEPT']??'','application/json');
$wishlistReply=static function(array $body,int $status=200): never {
 http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($body,JSON_INVALID_UTF8_SUBSTITUTE);exit;
};
try {
 if($wishlistJson){
  if(!user())$wishlistReply(['ok'=>false,'message'=>'Please sign in to save items.'],401);
  try{check_csrf();}catch(Throwable $e){$wishlistReply(['ok'=>false,'message'=>'Your session expired. Refresh the page and try again.'],419);}
 }else check_csrf();$action=(string)($_POST['action']??'');
 if(in_array($action,['register','login'],true)) {
  $email=strtolower(text_input('email',190));if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter a valid email address.');
  $password=(string)($_POST['password']??'');
  if($action==='register'){
   $name=text_input('name',100);if(strlen($password)<8||strlen($password)>72)throw new RuntimeException('Use a password between 8 and 72 characters.');
   if($password!==($_POST['confirm_password']??''))throw new RuntimeException('Passwords do not match.');
   if(one('SELECT id FROM users WHERE email=?',[$email]))throw new RuntimeException('This email is already registered. Please sign in.');
   query("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'customer')",[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$u=['id'=>db()->lastInsertId()];
  }else{
   $loginRole=($_GET['page']??'login')==='adminlogin'?'admin':'customer';
   $u=one('SELECT * FROM users WHERE email=?',[$email]);if(!$u||!$u['active']||!password_verify($password,$u['password_hash'])||$u['role']!==$loginRole)throw new RuntimeException('Email or password is incorrect.');
  }
  session_regenerate_id(true);$_SESSION['uid']=(int)$u['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));unset($_SESSION['checkout_key'],$_SESSION['quote_secret'],$_SESSION['promo_code'],$_SESSION['assistant'],$_SESSION['cart_recommendations']);flash('Welcome to HIFIVE.');go($action==='login'?($u['role']==='admin'?'admin':'home'):'account');
 }
 if($action==='logout'){$_SESSION=[];session_regenerate_id(true);flash('You have signed out.');go();}
 $u=login_required();$uid=(int)$u['id'];
 require __DIR__.'/feature-actions.php';
 if($action==='wishlist'){
  $id=integer_input('id',1);if(!one('SELECT id FROM products WHERE id=? AND active=1',[$id]))throw new RuntimeException('Product unavailable.');
  if(one('SELECT product_id FROM wishlist WHERE user_id=? AND product_id=?',[$uid,$id])){query('DELETE FROM wishlist WHERE user_id=? AND product_id=?',[$uid,$id]);$saved=false;$wishlistMessage='Removed from your wishlist.';}
  else {query('INSERT INTO wishlist(user_id,product_id) VALUES(?,?)',[$uid,$id]);$saved=true;$wishlistMessage='Saved to your wishlist.';}
  if($wishlistJson)$wishlistReply(['ok'=>true,'saved'=>$saved,'product_id'=>$id,'message'=>$wishlistMessage]);
  flash($wishlistMessage);
 }
 elseif($action==='cart_add'){
  $id=integer_input('variant_id',1);$quantity=integer_input('quantity',1,99);
  db()->beginTransaction();
  // Serialise cart additions for this user and lock the selected stock row.
  one('SELECT id FROM users WHERE id=? FOR UPDATE',[$uid]);
  $v=one('SELECT v.*,(v.active AND p.active) active FROM variants v JOIN products p ON p.id=v.product_id WHERE v.id=? FOR UPDATE',[$id]);
  $existing=one('SELECT * FROM cart_items WHERE user_id=? AND variant_id=? FOR UPDATE',[$uid,$id]);$total=$quantity+(int)($existing['quantity']??0);
  if(!$v||!$v['active']||$total>(int)$v['stock']||$total>99)throw new RuntimeException('The requested quantity is unavailable.');
  query('INSERT INTO cart_items(user_id,variant_id,quantity) VALUES(?,?,?) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity)',[$uid,$id,$total]);db()->commit();flash('Added to your bag.');go('cart');
 }
 elseif($action==='cancel_order'){cancel_order(integer_input('id',1),$uid,$u['role']==='admin');flash('Order cancelled. Stock has been restored.');go($u['role']==='admin'?'admin':'orders',$u['role']==='admin'?['section'=>'orders']:[]);}
 elseif($action==='review_delete'){query('UPDATE reviews SET customer_deleted=1 WHERE user_id=? AND product_id=?',[$uid,integer_input('product_id',1)]);flash('Review removed.');if(($_GET['page']??'')==='account')go('account',['tab'=>'reviews']);go('product',['id'=>(int)$_POST['product_id']]);}
 elseif(str_starts_with($action,'admin_')){
  admin_required();require ROOT.'/app/admin-actions.php';
 }
 elseif($action!=='wishlist')throw new RuntimeException('Unknown action.');
}catch(Throwable $ex){
 if(isset($GLOBALS['pdo'])&&$GLOBALS['pdo']->inTransaction())$GLOBALS['pdo']->rollBack();
 try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}
 if($ex instanceof PDOException){error_log($ex->getMessage());$message=$ex->getCode()==='23000'?'This record is in use, or a value already exists. Check the data before trying again.':'Database operation failed. Please try again.';}else $message=$ex->getMessage();
 if($wishlistJson)$wishlistReply(['ok'=>false,'message'=>$message],$ex instanceof PDOException?500:400);
 flash($message,'error');
 // Keep normal field values across the redirect; never retain passwords, tokens or uploaded files.
 $_SESSION['old']=array_filter($_POST,fn($key)=>!in_array($key,['password','confirm_password','csrf','checkout_key'],true),ARRAY_FILTER_USE_KEY);
}
header('Location: '.url((string)($_GET['page']??'home'),array_diff_key($_GET,['page'=>true])));exit;
