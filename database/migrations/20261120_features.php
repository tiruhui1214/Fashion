<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/20261120_promotions.php';
try {
    foreach(require dirname(__DIR__).'/feature-schema.php' as $table=>$columns)foreach($columns as $column=>$definition){
        if(!$one('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column]))$pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
    foreach(explode(';',file_get_contents(dirname(__DIR__).'/feature-tables.sql')) as $sql)if(trim($sql)!=='')$pdo->exec($sql);
    foreach([
      ['products','created_by_admin_id','users','products_creator_fk'],
      ['reviews','moderated_by_admin_id','users','reviews_moderator_fk'],
      ['orders','shipping_address_id','addresses','orders_address_fk'],
      ['orders','last_status_updated_by_admin_id','users','orders_admin_fk'],
    ] as [$table,$column,$target,$name]){
        if(!$one('SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND REFERENCED_TABLE_NAME=?',[$table,$column,$target]))$pdo->exec("ALTER TABLE `$table` ADD CONSTRAINT `$name` FOREIGN KEY(`$column`) REFERENCES `$target`(id) ON DELETE SET NULL");
    }
    // Preserve legacy orders. Record their stored paid amount, never recalculate it.
    $pdo->exec("INSERT INTO payments(order_id,amount,payment_reference,paid_at) SELECT o.id,o.total,CONCAT('LEGACY-',o.reference),o.created_at FROM orders o LEFT JOIN payments p ON p.order_id=o.id WHERE p.id IS NULL");
    $pdo->exec("INSERT INTO settings(setting_key,setting_value) VALUES('features_version','20261120') ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    echo "Features 1–7 schema upgrade complete.\n";
}catch(Throwable $error){
    fwrite(STDERR,"Feature upgrade failed; your data was not reset. Fix the schema/configuration and rerun. ".$error->getMessage()."\n");exit(1);
}
