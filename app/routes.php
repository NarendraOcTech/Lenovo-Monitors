<?php

use App\Middleware\ValidateUserKeyMiddleware;
use App\Middleware\DecryptDataMiddleware;
use App\Middleware\AuthMiddleware;

$app->post('/users', 'UsersController:createUser');


$app->group('/users', function () {
    $this->post('/register[/{userKey}]', 'UsersController:register');
})
    // ->add(new DecryptDataMiddleware())
    ->add(new ValidateUserKeyMiddleware());


$app->group('/analytics', function () {
    $this->get('/totalAppVisitsCount[/{key}]', 'DashboardController:totalAppVisitsCount');
    
});
