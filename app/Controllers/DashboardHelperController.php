<?php

namespace App\Controllers;

use App\Models\Activation;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Database\QueryException;

class DashboardHelperController extends Controller
{
    protected $dashboardStartDate = '2025-02-20';
    protected $dashboardEndDate = '2025-10-20';
    protected $chartKey = "QPLnvDm20SH1EP8Wy3TmeMUGEDIDjc";
    protected $reportKey = "Qu92xqr93q90oCVXFtDOLsJuDPFKsD";

    protected function getStartDate($req)
    {
        $startDate = $req->getQueryParam('startDate');
        if (!empty($startDate) && strtotime($this->dashboardStartDate) < strtotime($startDate)) {
            return $startDate;
        }
        return $this->dashboardStartDate;
    }

    protected function getEndDate($req)
    {
        /* $todayDate = date('Y-m-d');
        $endDate = $req->getQueryParam('endDate');
        if (!empty($endDate) && strtotime($endDate) <= strtotime($todayDate)) {
            return $endDate;
        }
        if (
            !empty($endDate) &&
            strtotime($this->dashboardEndDate) < $todayDate &&
            strtotime($this->dashboardEndDate) < strtotime($endDate)
        ) {
            return $this->dashboardEndDate;
        }
        return $todayDate; */
        $todayDate = date('Y-m-d');
        $endDate = $req->getQueryParam('endDate');
        if (!empty($endDate) && strtotime($endDate) <= strtotime($todayDate)) {
            return $endDate;
        }
        if (!empty($endDate) && strtotime($this->dashboardEndDate) < strtotime($todayDate)) {
            return $this->dashboardEndDate;
        }
        return $todayDate;
    }
    protected function getUniqueAppVisitCount($startDate, $endDate)
    {
        $preloadedData = $this->getPreloadedList('UNIQUE_APP_VISITS');

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = Activation::whereColumn('userKey', '=', 'masterKey')
                    ->where('created_date', $thisDate)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => 'UNIQUE_APP_VISITS',
                        'sub_key' => 'UNIQUE_APP_VISITS',
                        'event_count' => $count
                    ]);
                }
            }
        }
        return $outputObj;
    }

    protected function getTotalAppVisitsCount($startDate, $endDate)
    {
        $preloadedData = $this->getPreloadedList('TOTAL_APP_VISITS');

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = Activation::where('created_date', $thisDate)->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => 'TOTAL_APP_VISITS',
                        'sub_key' => 'TOTAL_APP_VISITS',
                        'event_count' => $count
                    ]);
                }
            }
        }
        return $outputObj;
    }

    protected function getWhereReward($startDate, $endDate, $class, $where, $num = 1, $createdDate = "created_date")
    {
        $chart = $num == 1 ? $this->getChartName($where) : $this->getChartName($where, $num);
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
                    ->where($where, $num)
                    ->where("used", 1)
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

    protected function getNotNullCount($startDate, $endDate, $class, $name)
    {
        $chart = $this->getChartName($name);
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
                $count = $class::where('created_date', $thisDate)->whereNotNull($name)
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

    protected function getChartName($name, $value = null)
    {
        $str = !empty($name) ? $name . "_" . $value : $name;
        return "TOTAL_" . strtoupper($str) . "_COUNT";
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

    protected function getTrafficHeatMapCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('TRAFFIC_HEAT_MAP', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "created_day_hour ,count(created_day_hour) as created_day_hour_count";
                $createdDayHourList = Activation::selectRaw($selectRows)
                    ->where('created_date', $thisDate)
                    ->groupBy('created_day_hour')
                    ->get();
                $createdDayHours = [];
                foreach ($createdDayHourList as $info) {
                    $createdDayHours[$info->created_day_hour] = $info->created_day_hour_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => 'TRAFFIC_HEAT_MAP',
                            'sub_key' => $info->created_day_hour,
                            'event_count' => $info->created_day_hour_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdDayHours;
            }
        }
        $dayHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $dayHour => $count) {
                if (array_key_exists($dayHour, $dayHourObj)) {
                    $dayHourObj[$dayHour] += $count;
                } else {
                    $dayHourObj[$dayHour] = $count;
                }
            }
        }
        $response = [];
        foreach ($dayHourObj as $dayHour => $count) {
            $response[] = (object)[
                "created_day_hour" => $dayHour,
                "created_day_hour_count" => $count
            ];
        }
        return $response;
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
                    //$createdDayHours[$info->platform] = $info->platform;
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
            $response[] = (object)[
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
                    //$createdDayHours[$info->platform] = $info->platform;
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
            $response[] = (object)[
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
            $response[] = (object)[
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
            $response[] = (object)[
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
            $response[] = (object)[
                "created_hour" => $createdHour,
                "created_hour_count" => $count
            ];
        }
        return $response;
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

    private function getReportEventData($chartKey, $startDate, $endDate)
    {
        $preloadedList = Report::select('event_date', 'sub_key', 'event_count')
            ->where('chart_key', $chartKey)
            ->where('event_date', '>=', $startDate)
            ->where('event_date', '<=', $endDate)
            ->get();

        $preloadedData = [];
        foreach ($preloadedList as $info) {
            if (!array_key_exists($info->event_date, $preloadedData)) {
                $preloadedData[$info->event_date] = [];
            }
            $preloadedData[$info->event_date][$info->sub_key] = $info->event_count;
        }
        return $preloadedData;
    }


    private function addReportData($saveData)
    {
        try {
            Report::saveData($saveData);
        } catch (QueryException $e) {
            return false;
        }
        return true;
    }
}
