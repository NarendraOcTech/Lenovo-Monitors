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
    $this->get('/uniqueVisitsCount[/{key}]', 'DashboardController:uniqueVisitsCount');
    $this->get('/appRevisitsCount[/{key}]', 'DashboardController:appRevisitsCount');

    $this->get('/deviceDistributionCount[/{key}]', 'DashboardController:deviceDistributionCount');

    $this->get('/totalUniqueUsers[/{key}]', 'DashboardController:totalUniqueUsers');

    $this->get('/trafficHeatMap[/{key}]', 'DashboardController:trafficHeatMap');
    
    $this->get('/totalRegistered[/{key}]', 'DashboardController:totalRegistered');

    $this->get('/deviceDistribution[/{key}]', 'DashboardController:deviceDistribution');
    $this->get('/browserDistribution[/{key}]', 'DashboardController:browserDistribution');
    $this->get('/osDistribution[/{key}]', 'DashboardController:osDistribution');
    $this->get('/dayWiseTraffic[/{key}]', 'DashboardController:dayWiseTraffic');
    $this->get('/timeWiseTraffic[/{key}]', 'DashboardController:timeWiseTraffic');
});
