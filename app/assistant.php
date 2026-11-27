<?php
// Compatibility only: the assistant now lives in the storefront floating panel.
if(!defined('ROOT')){http_response_code(404);exit;}
go('home',['chat'=>'open']);
