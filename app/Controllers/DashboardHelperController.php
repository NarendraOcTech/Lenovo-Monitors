<?php

namespace App\Controllers;

use App\Models\Activation;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Database\QueryException;

class DashboardHelperController extends Controller
{
    protected $chartKey = "QPLnvDm20SH1EP8Wy3TmeMUGEDIDjc";
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
            // if (!array_key_exists($info->event_date, $preLoadedData)) {
            //     $preLoadedData[$info->event_date] = [];
            // }
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


    protected function platformDistibution($startDate, $endDate)
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
        return $outputObj;

        

        // return $createdDayHour;



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