<?php

namespace App\Controllers;

use App\Helper\Hash;
use App\Models\Activation;
use App\Models\Reward;
use App\Models\Score;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Winner;

class DashboardController extends DashboardHelperController
{
    private function totalAppVisitsCount($req, $res){
        $key = $req->getAttribute("key");
        return $res->withJson($key);
    }
}
