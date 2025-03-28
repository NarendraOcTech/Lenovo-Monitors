<?php
require __DIR__ . '/../vendor/autoload.php';

$app = new \Slim\App([
    'setting' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
        'db' => [
            'driver' => "mysql",
            'host' => "localhost",
            'database' => "octechdigital",
            'username' => "root",
            'password' => '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => 'sample_lenovo_monitors_',
        ]
    ]
]);




$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['setting']['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['notFoundHandler'] = function ($c) {
    return function ($request, $response) use ($c) {
        return $response->withJson(['resp' => 'eyJzdGF0dXNDb2RlIjo0MDQsICJtZXNzYWdlIjoiSW52YWxpZCByZXF1ZXN0In0='], 404);
    };
};

date_default_timezone_set("Asia/Kolkata");

$container['WOOHOO_BASE_URL'] = 'https://sandbox.woohoo.in';
$container['WOOHOO_USERNAME'] = 'vouchagramapisandbox@woohoo.in';
$container['WOOHOO_PASSWORD'] = 'vouchagramapisandbox@1234';
$container['WOOHOO_CLIENT_ID'] = '8af50260ae5444bdc34665c2b6e6daa9';
$container['WOOHOO_CLIENT_SECRET'] = '93c1d8f362749dd1fe0a819ae8b5de95';

$container['db'] = function ($container) use ($capsule) {
    return $capsule;
};

$container["UsersController"] = function ($container) {

    return new \App\Controllers\UsersController($container);
};

$container["DashboardController"] = function ($container) {

    return new \App\Controllers\DashboardController($container);
};

require __DIR__ . './../app/routes.php';



// 'driver' => "mysql",
// 'host' => "localhost",
// 'database' => "octechdigital",
// 'username' => "root",
// 'password' => '',
// 'charset' => 'utf8',
// 'collation' => 'utf8_unicode_ci',
// 'prefix' => 'sample_lenovo_monitors_',
?>