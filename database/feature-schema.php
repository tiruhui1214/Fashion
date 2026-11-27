<?php
// Declarative additive changes shared by CLI migration and fresh schema builder.
return [
 'users'=>['phone'=>"VARCHAR(30) NOT NULL DEFAULT ''",'active'=>'TINYINT(1) NOT NULL DEFAULT 1','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
 'addresses'=>['line2'=>"VARCHAR(255) NOT NULL DEFAULT ''",'state'=>"VARCHAR(100) NOT NULL DEFAULT ''",'country'=>"VARCHAR(60) NOT NULL DEFAULT 'Malaysia'",'is_default'=>'TINYINT(1) NOT NULL DEFAULT 0','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
 'products'=>['style'=>"VARCHAR(255) NOT NULL DEFAULT ''",'occasion'=>"VARCHAR(255) NOT NULL DEFAULT ''",'created_by_admin_id'=>'INT UNSIGNED DEFAULT NULL','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
 'variants'=>['minimum_stock_level'=>'INT UNSIGNED NOT NULL DEFAULT 5','active'=>'TINYINT(1) NOT NULL DEFAULT 1','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
 'cart_items'=>['is_selected'=>'TINYINT(1) NOT NULL DEFAULT 1'],
 'reviews'=>['is_hidden'=>'TINYINT(1) NOT NULL DEFAULT 0','customer_deleted'=>'TINYINT(1) NOT NULL DEFAULT 0','moderated_by_admin_id'=>'INT UNSIGNED DEFAULT NULL','moderated_at'=>'DATETIME DEFAULT NULL','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
 'orders'=>['shipping_address_id'=>'INT UNSIGNED DEFAULT NULL','last_status_updated_by_admin_id'=>'INT UNSIGNED DEFAULT NULL','updated_at'=>'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
];
