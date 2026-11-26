<?php
require __DIR__.'/app/bootstrap.php';
$installed=is_file(__DIR__.'/.installed');$error='';$success=false;
if($_SERVER['REQUEST_METHOD']==='POST'&&!$installed){
 try{
  check_csrf();$name=text_input('name',100);$email=text_input('email',190);$password=(string)($_POST['password']??'');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<10||strlen($password)>72)throw new RuntimeException('Use a valid email and an administrator password of 10–72 characters.');
  $c=config();if(!preg_match('/^[A-Za-z0-9_]+$/D',$c['database']))throw new RuntimeException('Invalid database name.');
  $pdo=new PDO("mysql:host={$c['host']};port={$c['port']};charset=utf8mb4",$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  // A fresh-install schema is not a migration for existing tables.
  $existingUsers=$pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME='users'");
  $existingUsers->execute([$c['database']]);
  if($existingUsers->fetchColumn() && $pdo->query("SELECT id FROM `".$c['database']."`.users WHERE role='admin' LIMIT 1")->fetchColumn()) {
   throw new RuntimeException('An existing store was found. Do not reinstall. Preserve .installed and run database/migrations/20261120_features.php with PHP CLI, then open index.php?page=login.');
  }
  // Run the checked-in schema using the database name from local configuration.
  $sql=file_get_contents(__DIR__.'/database/schema.sql');$sql=str_replace(['CREATE DATABASE IF NOT EXISTS forme','USE forme'],['CREATE DATABASE IF NOT EXISTS `'.$c['database'].'`','USE `'.$c['database'].'`'],$sql);
  foreach(explode(';',$sql) as $statement)if(trim($statement)!=='')$pdo->exec($statement);
  if(one("SELECT id FROM users WHERE role='admin'"))throw new RuntimeException('An administrator already exists. Setup cannot create another. See the installation guide.');
  db()->beginTransaction();query("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'admin')",[$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
  require __DIR__.'/app/seed.php';seed_demo();
  if(file_put_contents(__DIR__.'/.installed',date(DATE_ATOM))===false)throw new RuntimeException('Cannot lock setup. Check folder write permissions.');
  db()->commit();$success=true;$installed=true;
 }catch(Throwable $ex){try{if(db()->inTransaction())db()->rollBack();}catch(Throwable $ignored){}error_log($ex->getMessage());$error=$ex instanceof PDOException?'Cannot initialise the database. Start MySQL in XAMPP and check config.local.php.':$ex->getMessage();}
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/svg+xml" sizes="any" href="<?=e(brand_asset('favicon'))?>"><title>HIFIVE · Local setup</title><link rel="stylesheet" href="assets/style.css?v=<?=filemtime(__DIR__.'/assets/style.css')?>"></head><body class="setup-body"><main class="setup-panel"><a class="wordmark setup-brand" href="index.php"><img src="<?=e(brand_asset())?>" width="240" height="80" alt="HIFIVE home"></a><p class="eyebrow">LOCAL DEVELOPMENT INSTALLER</p><h1><?= $installed?'Your store starts here.':'Welcome to HIFIVE.' ?></h1><?php if($error):?><p class="notice error"><?=e($error)?></p><?php endif;?><?php if($installed):?><p><?=$success?'Database and sample collection created.':'Setup is locked because this installation has already been configured.'?></p><p>Sign in with your administrator email, then use <strong>Manage store</strong>. Sample images are original illustration placeholders: replace them with your own photography in the dashboard.</p><a class="btn" href="index.php">Open the store →</a><?php else:?><p>Start Apache and MySQL in XAMPP first. This installer creates the database, sample products and your administrator account. Run only on your own computer.</p><form method="post" class="form-grid"><?=token()?><label>Administrator name<input name="name" required maxlength="100" autocomplete="name"></label><label>Email<input name="email" type="email" required maxlength="190" autocomplete="email"></label><label>Password · at least 10 characters<input name="password" type="password" required minlength="10" maxlength="72" autocomplete="new-password"></label><button class="btn">Create local store</button></form><small>Default database connection: 127.0.0.1:3306, database hifive (new local installs), user root, empty password. If different, copy config.example.php to config.local.php and edit it. Never use these defaults on a public server.</small><?php endif;?></main></body></html>
