<?php
require dirname(__DIR__).'/app/feature-services.php';
$actual=[];
foreach(feature_required_columns() as $table=>$columns)foreach($columns as $column)$actual[]=['TABLE_NAME'=>$table,'COLUMN_NAME'=>$column];
if(feature_missing_columns($actual)!==[])throw new RuntimeException('Complete schema rejected.');
$withoutPhone=array_values(array_filter($actual,fn($r)=>!($r['TABLE_NAME']==='users'&&$r['COLUMN_NAME']==='phone')));
if(feature_missing_columns($withoutPhone)!==['users.phone'])throw new RuntimeException('Missing users.phone was accepted.');
$withoutPayment=array_values(array_filter($actual,fn($r)=>$r['TABLE_NAME']!=='payments'));
if(!in_array('payments.order_id',feature_missing_columns($withoutPayment),true))throw new RuntimeException('Missing payments table was accepted.');
echo "3 schema readiness checks passed.\n";
