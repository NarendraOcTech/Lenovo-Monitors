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
    public function totalAppVisitsCount($req, $res, $args)
    {


        // $key = $req->getAttribute("key");
        // $data = $req->getParsedBody("data");
        // $key = $req->getQueryParam("key");
        $output = ["status" => 400];

        $key = Activation::htmlEncode($this->getData($args, 'key'));
        if ($key == $this->chartKey) {

            $startDate = $this->StartDate($req);
            $endingDate = $this->EndingDate($req);

            $total = $this->getTotalAppVisits($startDate, $endingDate);
            $count = 0;

            foreach ($total as $t) {
                $count += 1;
            }
            $output = [
                "status" => 200,
                "chartType" => "i-count",
                "datasets" => [
                    [
                        "icon" => "users",
                        "title" => 'Total app visits',
                        "value" => number_format($count),
                    ]
                ]
            ];



        }
        return json_encode($output);
    }
}
