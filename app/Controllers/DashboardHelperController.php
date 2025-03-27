<?php

namespace App\Controllers;

use App\Models\Activation;
use App\Models\Report;
use App\Models\Reward;
use Illuminate\Database\QueryException;

class DashboardHelperController extends Controller
{
    protected $chartKey = "QPLnvDm20SH1EP8Wy3TmeMUGEDIDjc";
    protected $dashboardStartDate = '2025-02-20';
    protected $dashboardEndtDate = '2025-10-20';

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


    protected function getTotalAppVisits($startDate, $endDate)
    {

        $preloadedData = $this->getPreLoadedData('TOTAL_APP_VISITS');

        $count = 0;

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

    private function addReportData(array $reportData){
        try{
            Report::saveData($reportData);
        }catch (\Exception $e){
            return false;
        }
        return true;
    }

}
?>