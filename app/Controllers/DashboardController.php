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
            $platformDistibution = $this->platformDistibutionCount($startDate, $endingDate);
            $output = $platformDistibution;

            $sum = 0;
            $mobileCount = 0;

            foreach ($platformDistibution as $user) {
                $sum += $user->browser_count;
                if ($user->browser == 'mobile') {
                    $mobileCount += $user->browser_count;
                }
            }

            $output = [
                "status" => 200,
                "chartType" => "count",
                "title" => 'Device distribution',
                "value" => "No Data"
            ];

            if ($sum > 0) {
                $output = [
                    "status" => 200,
                    "chartType" => "half-donut-chart",
                    "labels" => ["mobile", "web"],
                    "chartData" => [$mobileCount, $sum - $mobileCount]
                ];
            }
        }
        return json_encode($output);
    }

    public function totalUniqueUsers($req, $res, $args)
    {
        $output = ['status' => 400];
        $key = Activation::htmlEncode($this->getData($args, "key"));

        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endDaate = $this->EndingDate($req);

            $totalAppVisits = $this->getTotalAppVisits($startDate, $endDaate);
            $uniqueAppVisits = $this->getUniqueAppVisits($startDate, $endDaate);

            $labels = [];
            $totalUserCount = [];
            $uniqueUserCount = [];

            foreach ($totalAppVisits as $date => $count) {
                $labels[] = $date;
                $totalUserCount[] = $count;
                $uniqueUserCount[] = $uniqueAppVisits[$date];
            }
            $output = [
                "status" => 200,
                "chartType" => "line-chart",
                "labels" => $labels,
                "chartData" => [
                    ["label" => 'Total users', "value" => $totalUserCount],
                    ["label" => 'Unique users', "value" => $uniqueUserCount]
                ],
                "parameters" => [
                    "title" => 'Total and unique users',
                    "xLabelString" => 'Date',
                    "yLabelString" => 'Number of users'
                ]
            ];
        }
        return json_encode($output);
    }

    public function trafficHeatMap($req, $res, $args)
    {
        $output = ['status' => 400];

        $key = Activation::htmlEncode($this->getData($args, 'key'));



        if ($key == $this->chartKey) {

            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);

            $usersTraffic = $this->getTrafficHeatMapCount($startDate, $endDate);

            $labels = ["12am-4am", "4am-8am", "8am-12pm", "12pm-4pm", "4pm-8pm", "8pm-12am"];
            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $formatData = $this->formatHeatmapData($usersTraffic, $labels, $days);

            $output = [
                "status" => 200,
                "chartType" => "heat-map",
                "chartData" => $formatData['finalData'],
                "parameters" => [
                    "title" => 'Traffic heat map',
                    "legend" => $formatData['bucketLegend'],
                    "xLabels" => $labels,
                    "yLabels" => $days,
                ]
            ];
        }
        return json_encode($output);
    }






    public function totalRegistered($req, $res, $args)
    {
        $output = ["status" => 400];
        $key = Activation::htmlEncode($this->getData($args, 'key'));
        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);
            $count = $this->getOnlyCount($startDate, $endDate, new User(), "USER_COUNT");
            $output = [
                "status" => 200,
                "chartType" => "insight-count",
                "title" => 'Total registered',
                "value" => number_format($count)
            ];
        }

        return json_encode($output);
    }


    public function deviceDistribution($req, $res, $args)
    {
        $output = ["status" => 400];
        $key = Activation::htmlEncode($args['key']);

        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);
            $labels = [];
            $data = [];
            $platformDistribution = $this->getPlatformDistributionCount($startDate, $endDate);
            foreach ($platformDistribution as $user) {
                $labels[] = $user->device;
                $data[] = $user->device_count;
            }
            $output = [
                "status" => 200,
                "chartType" => "half-donut-chart",
                "labels" => $labels,
                "chartData" => $data,
                "parameters" => [
                    "title" => 'Device distribution'
                ]
            ];
        }
        return json_encode($output);
    }

    public function browserDistribution($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = Activation::htmlEncode($args['key']);
        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);
            $labels = [];
            $data = [];
            $platformDistribution = $this->getBrowserDistributionCount($startDate, $endDate);
            $i = 0;
            $otherCount = 0;
            foreach ($platformDistribution as $user) {
                if ($i <= 4) {
                    $labels[] = $user->browser;
                    $data[] = $user->browser_count;
                } else {
                    $otherCount += $user->browser_count;
                }
                $i++;
            }
            if ($otherCount) {
                $labels[] = "Others";
                $data[] = $otherCount;
            }
            $output = [
                "status" => 200,
                "chartType" => "pie-chart",
                "labels" => $labels,
                "chartData" => $data,
                "parameters" => [
                    "title" => 'Browser distribution'
                ]
            ];
        }
        return json_encode($output);
    }

    public function osDistribution($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = Activation::htmlEncode($args['key']);
        if ($key == $this->chartKey) {
            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);
            $labels = [];
            $data = [];
            $platformDistribution = $this->getOSDistributionCount($startDate, $endDate);
            $i = 0;
            $otherCount = 0;
            foreach ($platformDistribution as $user) {
                if ($i <= 4) {
                    $labels[] = $user->os;
                    $data[] = $user->os_count;
                } else {
                    $otherCount += $user->os_count;
                }
                $i++;
            }
            if ($otherCount) {
                $labels[] = "Others";
                $data[] = $otherCount;
            }
            $output = [
                "status" => 200,
                "chartType" => "pie-chart",
                "labels" => $labels,
                "chartData" => $data,
                "parameters" => [
                    "title" => 'Os distribution'
                ]
            ];
        }
        return json_encode($output);
    }

    public function dayWiseTraffic($req, $res, $args)
    {
        $output = ["status" => 400];

        $key = Activation::htmlEncode($args['key']);
        if ($key == $this->chartKey) {

            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);

            $users = $this->getDayWiseTrafficCount($startDate, $endDate);
            $labels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            $data = array_fill(0, 7, 0);
            if ($users) {
                foreach ($users as $user) {
                    $pos = array_search($user->created_day, $labels);
                    if ($pos !== false) {
                        $data[$pos] = $user->created_day_count;
                    }
                }
            }

            $output = [
                "status" => 200,
                "chartType" => "horizontal-bar-chart",
                "labels" => $labels,
                "chartData" => $data,
                "parameters" => [
                    "title" => 'Day wise traffic',
                    "labelString" => 'Number of users'
                ]
            ];
        }
        return json_encode($output);
    }

    public function timeWiseTraffic($req, $res, $args){
        
        $output = ["status" => 400];
        $key = Activation::htmlEncode($args['key']);
        if ($key == $this->chartKey) {

            $startDate = $this->StartDate($req);
            $endDate = $this->EndingDate($req);
            $users = $this->getTimeWiseTrafficCount($startDate, $endDate);
            $labels = ['12am-1am', '1am-2am', '2am-3am', '3am-4am', '4am-5am', '5am-6am', '6am-7am', '7am-8am', '8am-9am', '9am-10am', '10am-11am', '11am-12pm', '12pm-1pm', '1pm-2pm', '2pm-3pm', '3pm-4pm', '4pm-5pm', '5pm-6pm', '6pm-7pm', '7pm-8pm', '8pm-9pm', '9pm-10pm', '10pm-11pm', '11pm-12am'];
            $data = array_fill(0, 24, 0);

            if ($users) {
                foreach ($users as $user) {
                    $data[$user->created_hour] = $user->created_hour_count;
                }
            }

            $output = [
                "status" => 200,
                "chartType" => "line-chart",
                "labels" => $labels,
                "chartData" => [
                    ["label" => 'Total users', "value" => $data]
                ],
                "parameters" => [
                    "title" => 'Time wise traffic',
                    "xLabelString" => 'Time',
                    "yLabelString" => 'Number of users'
                ]
            ];
        }
        return json_encode($output);
    }
}
