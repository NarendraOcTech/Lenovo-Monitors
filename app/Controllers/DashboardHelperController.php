<?php

namespace App\Controllers;

use App\Models\Activation;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Database\QueryException;

class DashboardHelperController extends Controller
{
    protected $chartKey = "QPLnvDm20SH1EP8Wy3TmeMUGEDIDjc";
    protected $reportKey = "Qu92xqr93q90oCVXFtDOLsJuDPFKsD";
    protected $dashboardStartDate = '2025-03-25';
    protected $dashboardEndtDate = '2025-10-27';

    protected function StartDate($req)
    {
        $startDate = $req->getQueryParam("startDate");
        if (!empty($startDate) && strtotime($startDate) > strtotime($this->dashboardStartDate)) {
            return $startDate;
        }
        return $this->dashboardStartDate;
    }

    protected function EndingDate($req)
    {
        $endDate = $req->getQueryParam("endDate");
        $todayDate = date("Y-m-d");
        if (!empty($endDate) && strtotime($endDate) <= strtotime($todayDate)) {
            return $endDate;
        }
        if (!empty($endDate) && strtotime($this->dashboardEndtDate) < strtotime($todayDate)) {
            return $this->dashboardEndtDate;
        }
        return $todayDate;
    }

    protected function getPreLoadedData($chartKey)
    {
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartKey)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();

        $preloadedData = [];

        foreach ($preloadedList as $val) {
            $preloadedData[$val->e_date] = $val->e_count;
        }


        return $preloadedData;
    }

    protected function getReportEventData($chartKey, $startDate, $endDate)
    {
        $preloadedList = Report::select('event_date', 'sub_key', 'event_count')
            ->where('chart_key', $chartKey)
            ->where('event_date', '>=', $startDate)
            ->where('event_date', '<=', $endDate)
            ->get();

        $preLoadedData = [];

        foreach ($preloadedList as $info) {
            if (!array_key_exists($info->event_date, $preLoadedData)) {
                $preLoadedData[$info->event_date] = [];
            }
            $preLoadedData[$info->event_date][$info->sub_key] = $info->event_count;
        }
        return $preLoadedData;

    }


    protected function getTotalAppVisits($startDate, $endDate)
    {

        $preloadedData = $this->getPreLoadedData('TOTAL_APP_VISITS');

        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);

        $outputObj = [];

        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = Activation::where('created_date', $thisDate)->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData(
                        [
                            'event_date' => $thisDate,
                            'chart_key' => 'TOTAL_APP_VISITS',
                            'sub_key' => 'TOTAL_APP_VISITS',
                            'event_count' => $count
                        ]
                    );
                }

            }
        }
        return $outputObj;
    }

    protected function getUniqueAppVisits($startDate, $endDate)
    {

        $preloadedData = $this->getPreLoadedData('UNIQUE_APP_VISITS');
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = Activation::whereColumn("userKey", "=", "masterKey")
                    ->where('created_date', $thisDate)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData(
                        [
                            'event_date' => $thisDate,
                            'chart_key' => "UNIQUE_APP_VISITS",
                            'sub_key' => "UNIQUE_APP_VISITS",
                            'event_count' => $count
                        ]
                    );
                }
            }

        }

        return $outputObj;
    }


    protected function platformDistibutionCount($startDate, $endDate)
    {

        $preLoadedData = $this->getReportEventData('PLATFORM_DISTRIBUTION', $startDate, $endDate);

        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            $thisDate = date('y-m-d', $i);
            if (array_key_exists($thisDate, $preLoadedData)) {
                $outputObj[$thisDate] = $preLoadedData[$thisDate];
            } else {
                $selectRows = 'device, count(device) as platform_count';
                $createdDayHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('device')
                    ->get();
                $createdDayHour = [];
                foreach ($createdDayHourList as $row) {
                    $createdDayHour[$row->device] = $row->platform_count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'PLATFORM_DISTRIBUTION',
                            'sub_key' => $row->device,
                            'event_count' => $row->platform_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdDayHour;
            }
        }

        $deviceObj = [];

        foreach ($outputObj as $output) {
            foreach ($output as $device => $platformCount) {
                if (array_key_exists($device, $deviceObj)) {
                    $deviceObj[$device] += $platformCount;

                } else {
                    $deviceObj[$device] = $platformCount;
                }
            }
        }
        arsort($deviceObj);


        $response = [];

        foreach ($deviceObj as $createdDay => $count) {
            $response[] = (object) [
                'browser' => $createdDay,
                'browser_count' => $count
            ];
        }
        return $response;
    }

    protected function formatHeatmapData($createdData, $labels, $days)
    {
        $short_day = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        $data = [];
        foreach ($days as $day) {
            $data[] = array_fill(0, count($labels), 0);
        }



        foreach ($createdData as $user) {
            $created_day_hour = explode("-", $user->created_day_hour);
            $day = $created_day_hour[0];
            $hour = $created_day_hour[1];
            $d = array_search($day, $short_day);
            $t = floor($hour / 4);
            $data[$d][$t] += $user->created_day_hour_count;

        }

        $maxval = 0;
        $minval = 0;
        // $data = [[0,6,1,0,0,0],[0,0,0,0,8,0],[0,0,0,4,0,0],[1,0,0,0,6,0],[0,0,0,0,0,0],[0,2,0,3,0,0],[0,0,0,4,8,6]];
        // $result = [];
        for ($i = 0; $i < count($data); $i++) {
            for ($j = 0; $j < count($data[$i]); $j++) {
                if (($i == 0 && $j == 0) || $data[$i][$j] < $minval) {
                    $minval = $data;
                }
                if ($maxval < $data[$i][$j]) {
                    $maxval = $data[$i][$j];
                }

            }
        }

        $new_maxval = 0;
        $buckets = [];

        if ($maxval == 0) {
            $buckets = [15];
            $new_maxval = 15;
        } elseif ($maxval < 50) {
            $buckets = [18, 35, 50];
            $new_maxval = 50;
        } else {
            $new_maxval = $maxval + (25 - ($maxval % 25));
            $buckets = [
                round($new_maxval * 0.3),
                round($new_maxval * 0.55),
                round($new_maxval * 0.75),
                round($new_maxval * 0.9),
                round($new_maxval)
            ];
        }

        $finalData = [];

        foreach ($data as $row) {
            $bucket_no = 0;
            $t_data = [];
            foreach ($row as $val) {
                if ($val <= $buckets[0]) {
                    $bucket_no = 1;
                } elseif ($val <= $buckets[1]) {
                    $bucket_no = 2;
                } elseif ($val <= $buckets[2]) {
                    $bucket_no = 3;
                } elseif ($val <= $buckets[3]) {
                    $bucket_no = 4;
                }
                $t_data[] = ["value" => $val, "bucketNo" => $bucket_no];
            }
            $finalData[] = $t_data;
        }
        $bucket_legend = [];
        $lastVal = -1;
        foreach ($buckets as $b) {
            $bucket_legend[] = ($lastVal + 1) . " to " . $b;
            $lastVal = $b;
        }

        return ['finalData' => $finalData, 'bucketLegend' => $bucket_legend];

    }


    protected function getTrafficHeatMapCount($startDate, $endDate)
    {

        $preloadedData = $this->getReportEventData('TRAFFIC_HEAT_MAP', $startDate, $endDate);
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
        $outputdata = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startDate; $i <= $endDate; $i += 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputdata[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRow = 'created_day_hour, count(created_day_hour) as created_day_hour_count';
                $createdDayList = Activation::selectRaw($selectRow)
                    ->where('created_date', $thisDate)
                    ->groupBy('created_day_hour')
                    ->get();
                $createdDay = [];

                foreach ($createdDayList as $info) {
                    $createdDay[$info->created_day_hour] = $info->created_day_hour_count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'DAY_WISE_TRAFFIC',
                            'sub_key' => $info->created_day_hour,
                            'event_count' => $info->created_day_hour_count
                        ]);
                    }
                }

                $outputdata[$thisDate] = $createdDay;
            }
        }

        $createdDayObj = [];

        foreach ($outputdata as $eachDay) {
            foreach ($eachDay as $day => $count) {
                if (array_key_exists($day, $createdDayObj)) {
                    $createdDayObj[$day] += $count;
                } else {
                    $createdDayObj[$day] = $count;
                }
            }
        }

        $response = [];

        foreach ($createdDayObj as $created_day => $created_day_count) {
            $response[] = (object) [
                'created_day_hour' => $created_day,
                'created_day_hour_count' => $created_day_count
            ];
        }

        return $response;
    }

    protected function getChartName($name, $value = null)
    {
        $str = !empty($name) ? $name . "_" . $value : $name;
        return "TOTAL_" . strtoupper($str) . "_COUNT";
    }

    private function getPreloadedList($chartKey)
    {
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartKey)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();

        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        return $preloadedData;
    }

    protected function getOnlyCount($startDate, $endDate, $class, $chartName, $createdDate = "created_date")
    {
        $chart = $this->getChartName($chartName);
        $preloadedData = $this->getPreloadedList($chart);


        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = 0;
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj += $preloadedData[$thisDate];
            } else {
                $count = $class::where($createdDate, $thisDate)
                    ->count();
                $outputObj += $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chart,
                        'sub_key' => $chart,
                        'event_count' => $count
                    ]);
                }
            }
        }
        return $outputObj;
    }



    protected function getPlatformDistributionCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('PLATFORM_DISTRIBUTION', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "device ,count(device) as platform_count";

                $createdDayHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('device')
                    ->get();
                $createdDayHours = [];

                foreach ($createdDayHourList as $info) {
                    $createdDayHours[$info->device] = $info->platform_count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'PLATFORM_DISTRIBUTION',
                            'sub_key' => $info->device,
                            'event_count' => $info->platform_count
                        ]);
                    }
                }

                $outputObj[$thisDate] = $createdDayHours;
            }
        }

        $deviceObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $device => $platformCount) {
                if (array_key_exists($device, $deviceObj)) {
                    $deviceObj[$device] += $platformCount;
                } else {
                    $deviceObj[$device] = $platformCount;
                }
            }
        }

        $response = [];
        foreach ($deviceObj as $createdDay => $count) {
            $response[] = (object) [
                "device" => $createdDay,
                "device_count" => $count
            ];
        }

        return $response;
    }

    protected function getBrowserDistributionCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('BROWSER_DISTRIBUTION', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "browser ,count(browser) as platform_count";
                $createdDayHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('browser')
                    ->get();
                $createdDayHours = [];
                foreach ($createdDayHourList as $info) {
                    $createdDayHours[$info->browser] = $info->platform_count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'BROWSER_DISTRIBUTION',
                            'sub_key' => $info->browser,
                            'event_count' => $info->platform_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdDayHours;
            }
        }
        $deviceObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $device => $platformCount) {
                if (array_key_exists($device, $deviceObj)) {
                    $deviceObj[$device] += $platformCount;
                } else {
                    $deviceObj[$device] = $platformCount;
                }
            }
        }
        arsort($deviceObj);
        $response = [];
        foreach ($deviceObj as $createdDay => $count) {
            $response[] = (object) [
                "browser" => $createdDay,
                "browser_count" => $count
            ];
        }
        return $response;
    }

    protected function getOSDistributionCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('OS_DISTRIBUTION', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "os ,count(os) as platform_count";
                $createdDayHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('os')
                    ->get();
                $createdDayHours = [];
                foreach ($createdDayHourList as $info) {
                    //$createdDayHours[$info->platform] = $info->platform;
                    $createdDayHours[$info->os] = $info->platform_count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'OS_DISTRIBUTION',
                            'sub_key' => $info->os,
                            'event_count' => $info->platform_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdDayHours;
            }
        }
        $deviceObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $device => $platformCount) {
                if (array_key_exists($device, $deviceObj)) {
                    $deviceObj[$device] += $platformCount;
                } else {
                    $deviceObj[$device] = $platformCount;
                }
            }
        }
        arsort($deviceObj);
        $response = [];
        foreach ($deviceObj as $createdDay => $count) {
            $response[] = (object) [
                "os" => $createdDay,
                "os_count" => $count
            ];
        }
        return $response;
    }

    protected function getDayWiseTrafficCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('DAY_WISE_TRAFFIC', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "created_day ,count(created_day) as created_day_count";
                $createdDayList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('created_day')
                    ->get();
                $createdDays = [];
                foreach ($createdDayList as $info) {
                    $createdDays[$info->created_day] = $info->created_day_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'DAY_WISE_TRAFFIC',
                            'sub_key' => $info->created_day,
                            'event_count' => $info->created_day_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdDays;
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        $response = [];
        foreach ($createdDayObj as $createdDay => $count) {
            $response[] = (object) [
                "created_day" => $createdDay,
                "created_day_count" => $count
            ];
        }
        return $response;
    }

    protected function getTimeWiseTrafficCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('TIME_WISE_TRAFFIC', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "created_hour ,count(created_hour) as created_hour_count";
                $createdHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('created_hour')
                    ->get();
                $createdHours = [];
                foreach ($createdHourList as $info) {
                    $createdHours[$info->created_hour] = $info->created_hour_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'TIME_WISE_TRAFFIC',
                            'sub_key' => $info->created_hour,
                            'event_count' => $info->created_hour_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }
        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        $response = [];
        foreach ($createdHourObj as $createdHour => $count) {
            $response[] = (object) [
                "created_hour" => $createdHour,
                "created_hour_count" => $count
            ];
        }
        return $response;
    }



    private function addReportData(array $reportData)
    {
        try {
            Report::saveData($reportData);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
}
?>