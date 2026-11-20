<?php
declare(strict_types=1);
// CLI only: this file must not expose database upgrade operations over HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
try {
    // Migration needs database configuration, not storefront sessions or rendering.
    $root = dirname(__DIR__, 2);
    $c = require is_file($root.'/config.local.php') ? $root.'/config.local.php' : $root.'/config.example.php';
    $pdo = new PDO("mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4", $c['username'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
    $one = static function(string $sql, array $params=[]) use ($pdo): ?array {
        $statement=$pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    };
    // MariaDB DDL commits implicitly. Each operation is additive and rerunnable.
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS promotions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 created_by_admin_id INT UNSIGNED NOT NULL,
 promo_code VARCHAR(40) NOT NULL UNIQUE,
 discount_rate DECIMAL(5,2) NOT NULL,
 minimum_purchase_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 start_date DATETIME NOT NULL,
 end_date DATETIME NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT promotions_creator_fk FOREIGN KEY(created_by_admin_id) REFERENCES users(id),
 CONSTRAINT promotions_rate_check CHECK(discount_rate > 0 AND discount_rate <= 100),
 CONSTRAINT promotions_minimum_check CHECK(minimum_purchase_amount >= 0),
 CONSTRAINT promotions_dates_check CHECK(end_date >= start_date)
) ENGINE=InnoDB;
SQL);
    $columns = [
        'promotion_id' => 'INT UNSIGNED DEFAULT NULL',
        'promo_code_used' => 'VARCHAR(40) DEFAULT NULL',
        'discount_amount' => 'DECIMAL(10,2) NOT NULL DEFAULT 0.00',
    ];
    foreach ($columns as $name => $definition) {
        $exists = $one('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?', ['orders', $name]);
        if (!$exists) {
            $pdo->exec('ALTER TABLE orders ADD COLUMN `'.$name.'` '.$definition);
        }
    }
    $foreignKey = $one("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='promotion_id' AND REFERENCED_TABLE_NAME='promotions' AND REFERENCED_COLUMN_NAME='id'");
    if (!$foreignKey) {
        $pdo->exec('ALTER TABLE orders ADD CONSTRAINT orders_promotion_fk FOREIGN KEY(promotion_id) REFERENCES promotions(id) ON DELETE SET NULL');
    }
    echo "Promotion schema upgrade complete. Existing orders and amounts were retained.\n";
} catch (Throwable $error) {
    // Do not print credentials or connection configuration.
    fwrite(STDERR, "Promotion schema upgrade failed. Verify MySQL is running and the configured database user can alter tables.\n");
    exit(1);
}
