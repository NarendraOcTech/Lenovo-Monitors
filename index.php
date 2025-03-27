<?php
 header('Access-Control-Allow-Origin: *');
 header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
 header('Access-Control-Allow-Headers: Content-Type, Authorization, cds-pixel-id');
 header("Content-Type: application/json");
require_once './bootstrap/app.php';

$app->run();