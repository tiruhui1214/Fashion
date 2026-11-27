<?php
declare(strict_types=1);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kuala_Lumpur');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
const ROOT = __DIR__ . '/..';
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(string $page = 'home', array $params = []): string { return 'index.php?' . http_build_query(['page'=>$page]+$params); }
function go(string $page = 'home', array $params = []): void { header('Location: '.url($page,$params)); exit; }
function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = [$message,$type]; }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function token(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function check_csrf(): void { if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Your session expired. Refresh the page and try again.'); }
function config(): array { $file=ROOT.'/config.local.php'; return is_file($file)?require $file:require ROOT.'/config.example.php'; }
function db(): PDO { static $pdo; if(!$pdo) { $c=config(); $pdo=new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]); } return $pdo; }
function query(string $sql,array $params=[]): PDOStatement { $s=db()->prepare($sql); $s->execute($params); return $s; }
function all(string $sql,array $params=[]): array { return query($sql,$params)->fetchAll(); }
function one(string $sql,array $params=[]): ?array { return query($sql,$params)->fetch()?:null; }
function user(): ?array { return empty($_SESSION['uid'])?null:one('SELECT id,name,email,phone,role FROM users WHERE id=? AND active=1',[$_SESSION['uid']]); }
function login_required(): array { $u=user(); if(!$u) {flash('Please sign in to continue.','info');go('login');} return $u; }
function admin_required(): array { $u=user(); if(!$u) {flash('Please sign in as an administrator.','info');go('adminlogin');} if($u['role']!=='admin') {http_response_code(403);exit('Access denied. Administrator access is required.');} return $u; }
function money($value): string { return 'RM '.number_format((float)$value,2); }
function text_input(string $name,int $max=255): string { $v=trim((string)($_POST[$name]??'')); if($v===''||mb_strlen($v)>$max) throw new RuntimeException(ucwords(str_replace('_',' ',$name))." is required (maximum $max characters)."); return $v; }
function integer_input(string $name,int $min=0,int $max=100000): int { $v=filter_var($_POST[$name]??null,FILTER_VALIDATE_INT); if($v===false||$v===null||$v<$min||$v>$max)throw new RuntimeException("Enter a valid $name between $min and $max."); return $v; }
function color_input(string $name): string { $v=(string)($_POST[$name]??'');if(!preg_match('/^#[0-9a-fA-F]{6}$/',$v))throw new RuntimeException('Choose a valid colour.');return $v; }
function safe_image(?string $path): string { if(in_array($path,['assets/branding/HIFIVE_PrimaryLogo.svg','assets/branding/HIFIVE_BrandLogo.svg','assets/branding/HIFIVE_Favicon.svg'],true))return $path; if($path&&preg_match('~^(assets/[a-zA-Z0-9_.-]+\.(svg|jpg|jpeg|png|webp)|uploads/[a-f0-9]+\.(jpg|png|webp))$~D',$path)) return $path; return 'assets/product-sage.svg'; }
function upload(string $field,string $existing='',bool $required=false): string {
 $f=$_FILES[$field]??null;
 if(!$f||$f['error']===UPLOAD_ERR_NO_FILE){if($required&&!$existing)throw new RuntimeException('Please upload an image.');return $existing;}
 if($f['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Image upload failed. Check PHP upload limits.');
 if($f['size']>5*1024*1024)throw new RuntimeException('Images must be 5 MB or smaller.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 $dimensions=@getimagesize($f['tmp_name']);
 if(!isset($types[$mime])||!$dimensions||$dimensions[0]>10000||$dimensions[1]>10000)throw new RuntimeException('Upload a valid JPG, PNG or WebP image up to 10000 pixels per side.');
 $path='uploads/'.bin2hex(random_bytes(16)).'.'.$types[$mime];
 if(!move_uploaded_file($f['tmp_name'],ROOT.'/'.$path))throw new RuntimeException('Cannot save image. Check uploads folder permissions.');return $path;
}
function cart_rows(int $uid,bool $lock=false): array { return all('SELECT c.id,c.quantity,c.is_selected,v.id variant_id,v.stock,v.color,v.size,v.image,p.id product_id,p.name,p.price,(p.active AND v.active) active,cat.name category,cat.department FROM cart_items c JOIN variants v ON v.id=c.variant_id JOIN products p ON p.id=v.product_id JOIN categories cat ON cat.id=p.category_id WHERE c.user_id=? ORDER BY v.id'.($lock?' FOR UPDATE':''),[$uid]); }
function cart_subtotal(array $rows): int { $sum=0;foreach($rows as $r)$sum+=to_cents((string)$r['price'])*(int)$r['quantity'];return $sum; }
function cancel_order(int $id,int $uid,bool $admin=false): void {
 db()->beginTransaction();try{
 $o=one('SELECT * FROM orders WHERE id=? FOR UPDATE',[$id]);
 if(!$o||(!$admin&&(int)$o['user_id']!==$uid))throw new RuntimeException('Order not found.');
 if(!in_array($o['status'],['Confirmed','Processing'],true))throw new RuntimeException('Only confirmed or processing orders can be cancelled.');
 foreach(all('SELECT * FROM order_items WHERE order_id=? ORDER BY variant_id',[$id]) as $item) if($item['variant_id'])query('UPDATE variants SET stock=stock+? WHERE id=?',[$item['quantity'],$item['variant_id']]);
 query("UPDATE orders SET status='Cancelled',last_status_updated_by_admin_id=CASE WHEN ?=1 THEN ? ELSE last_status_updated_by_admin_id END WHERE id=?",[$admin?1:0,$uid,$id]);record_status($id,$uid,$o['status'],'Cancelled');db()->commit();
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();throw $ex;}
}
function product_card(array $p): void {
 $variants=all('SELECT color,hex,MIN(image) image,SUM(stock) stock FROM variants WHERE product_id=? AND active=1 GROUP BY color,hex ORDER BY MIN(id)',[$p['id']]);
 $first=$variants[0]??['image'=>'assets/product-sage.svg']; ?>
 <article class="product-card"><div class="product-photo"><a href="<?=e(url('product',['id'=>$p['id']]))?>"><img loading="lazy" src="<?=e(safe_image($first['image']))?>" alt="<?=e($p['name'])?>"></a><form method="post"><?=token()?><input type="hidden" name="action" value="wishlist"><input type="hidden" name="id" value="<?=e($p['id'])?>"><button class="heart" aria-label="Toggle wishlist for <?=e($p['name'])?>"><?=icon('heart')?></button></form><?php if($p['featured']):?><span class="photo-label">THE HIFIVE EDIT</span><?php endif;?></div>
 <div class="card-meta"><small><?=e($p['department']??'HIFIVE')?></small><span><?=money($p['price'])?></span></div><a class="product-name" href="<?=e(url('product',['id'=>$p['id']]))?>"><?=e($p['name'])?></a><div class="swatches"><?php foreach($variants as $i=>$v):?><button type="button" class="swatch <?=$i===0?'selected':''?>" style="--swatch:<?=e($v['hex'])?>" data-card-image="<?=e(safe_image($v['image']))?>" aria-label="Show <?=e($v['color'])?>" title="<?=e($v['color'])?>"></button><?php endforeach;?></div></article>
<?php }

function setting(string $key,string $default=''): string {
 $r=one('SELECT setting_value FROM settings WHERE setting_key=?',[$key]);
 $value=$r?(string)$r['setting_value']:$default;
 // Present an existing legacy brand setting under the new name without rewriting store data.
 return $key==='brand_name'&&strcasecmp(trim($value),'FORME')===0?'HIFIVE':$value;
}

function icon(string $name): string {
 $paths=['account'=>'<circle cx="12" cy="8" r="3.5"/><path d="M4.5 21v-1.5a7.5 7.5 0 0 1 15 0V21"/>','search'=>'<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>','heart'=>'<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>','bag'=>'<path d="M5 7h14l1 14H4L5 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>'];
 return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??'').'</svg>';
}

require_once __DIR__ . '/catalog-banners.php';
require_once __DIR__ . '/feature-services.php';


// Fixed, reviewed brand assets; arbitrary uploaded SVG remains disallowed.
function brand_asset(string $kind='primary'): string {
 $files=['primary'=>'HIFIVE_PrimaryLogo.svg','brand'=>'HIFIVE_BrandLogo.svg','favicon'=>'HIFIVE_Favicon.svg'];
 $path='assets/branding/'.($files[$kind]??$files['primary']);
 return $path.'?v='.filemtime(ROOT.'/'.$path);
}
