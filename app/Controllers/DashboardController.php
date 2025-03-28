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
        // $route = $request->getAttribute('route');
        // $userKey = $route->getArgument('userKey');

        $output = ["status" => 400];

        $key = Activation::htmlEncode($this->getData($args, 'key'));
        if ($key == $this->chartKey) {

            $startDate = $this->StartDate($req);
            $endingDate = $this->EndingDate($req);

            $total = $this->getTotalAppVisits($startDate, $endingDate);
            $count = 0;

            foreach ($total as $t) {
                $count += $t;
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

    public function uniqueVisitsCount($req, $res, $args)
    {
        $output = ["status" => 400];
        $key = Activation::htmlEncode($this->getData($args, "key"));
        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endingDate = $this->EndingDate($req);

            $unique = $this->getUniqueAppVisits($startDate, $endingDate);

            $count = 0;
            foreach ($unique as $t) {
                $count += $t;
            }

            $output = [
                "status" => 200,
                "chartType" => "i-count",
                "datasets" => [
                    [
                        "icon" => "user",
                        "title" => 'Unique app visits',
                        "value" => number_format($count),
                    ]
                ]
            ];

        }
        return json_encode($output);
    }

    public function appRevisitsCount($req, $res, $args)
    {
        $output = ["status" => 400];
        $key = Activation::htmlEncode($this->getData($args, "key"));
        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endingDate = $this->EndingDate($req);

            $total = $this->getTotalAppVisits($startDate, $endingDate);
            $unique = $this->getUniqueAppVisits($startDate, $endingDate);

            $totalCount = 0;
            $uniqueCount = 0;
            foreach ($total as $t) {
                $totalCount += $t;
            }
            foreach ($unique as $t) {
                $uniqueCount += $t;
            }

            $output = [
                "status" => 200,
                "chartType" => "i-count",
                "datasets" => [
                    [
                        "icon" => "user",
                        "title" => 'App Revisits',
                        "value" => number_format($totalCount - $uniqueCount),
                    ]
                ]
            ];

        }
        return json_encode($output);
    }

    public function deviceDistributionCount($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = Activation::htmlEncode($this->getData($args, "key"));

        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endingDate = $this->EndingDate($req);
            $platformDistibution = $this->platformDistibution($startDate, $endingDate);
            $output = $platformDistibution;
        }
        return json_encode($output);
    }






}
