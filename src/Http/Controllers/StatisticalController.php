<?php

namespace Toh\Statistical\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Toh\Statistical\Models\Statistical;
use Cache;

class StatisticalController
{
    public function getIndex(){
        // dd($_SERVER["DOCUMENT_ROOT"]);
        dd(2);
    }

    public function countVisited(){
        $laravel = app();
        $version = $laravel::VERSION;
        $version = explode('.', $version);

        if (count($version) >= 2) {
            $version = $version[0].$version[1];
        } else {
            $version = $version[0];
        }
        
        $timeout_cache = 24*60*60;

        if ((int)$version <= 57) {
            $timeout_cache = 24*60;
        }

        $day_current = date('Y-m-d');
        $date_current = date('Y-m-d H:i:s');
        $cache_visited_by_ip_day_current = 'cache_visited_by_ip_' . $day_current;

        Cache::remember($cache_visited_by_ip_day_current, $timeout_cache, function () {
            return [];
        });

        $cache_visited_by_ip = Cache::get($cache_visited_by_ip_day_current);
        

        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                  $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                  $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if(filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        if (!in_array($ip, $cache_visited_by_ip)) {
            Statistical::insert(['created_at' => $date_current, 'ip' => $ip]);
            $cache_visited_by_ip[]= $ip;
            Cache::put($cache_visited_by_ip_day_current, $cache_visited_by_ip, $timeout_cache); 
            // $datediff= strtotime(date('Y-m-d') . ' 23:59:59') - strtotime(date('Y-m-d H:i:s'));
        }

   //      foreach ($cache_visited_by_ip as $value) {
            // $datediff = strtotime(date('Y-m-d H:i:s')) - strtotime($value['created_at']);

   //       if ($value['ip'] == $ip && ($datediff / (60 * 60 * 24)) >=1 ) {
      //           $cache_visited_by_ip[]= ['created_at' => date('Y-m-d H:i:s'), 'ip' => $ip];
      //           Cache::put('cache_visited_by_ip', $cache_visited_by_ip, 14400); // 10*24*60 = 10 ngày
      //           break;
   //       }
   //      }
    }

    public function getStatistical7DayNearest(){
        $date_from = date('Y-m-d', strtotime("-7 day"));
        $date_to = date('Y-m-d', strtotime("-1 day"));
        $date_from = $date_from . " 00:00:00";
        $date_to = $date_to . " 23:59:59";
        $data = Statistical::selectRaw('count(id) as total')->whereBetween('created_at', [$date_from, $date_to])->value('total');
        return round($data/7);
    }
    
    public function getInfoGitPullNearest(){
        $root_folder = str_replace("public", "", $_SERVER["DOCUMENT_ROOT"]);
        $data = exec('cd '. $root_folder .' && stat -c %y .git/FETCH_HEAD', $output);
        
        if (strlen($data) > 2) {
            return substr($data, 0, 19);
        }

        return '';
    }

    public function saveVisitedWebsite(){
        if (Cache::has('cache_visited_by_ip')) {
            $cache_visited_by_ip = Cache::get('cache_visited_by_ip');
            
            if (count($cache_visited_by_ip) > 0) {
                Statistical::insert($cache_visited_by_ip);
                Cache::forget('cache_visited_by_ip');
            } else {
                echo 'empty';
            }
        } else {
            echo 'empty';
        }
    }

    public function getDataAjaxHighchart(Request $request){
        $type = $request->type;
        $type = $type > 0 ? $type : 1;
        $thisDay = $thisMonth = $thisYear = '';
        $data = [];

        if ($type == 1 || $type== 2) {
            if ($type== 1) { // Hôm nay
                $thisDay = date('Y-m-d');
            } else { 
                $thisDay = date('Y-m-d', strtotime("-1 day"));
            } 

            $data = Statistical::selectRaw('COUNT(id) as total, HOUR(created_at) as hour')->whereDate('created_at', '=', $thisDay)->groupBy('hour')->pluck('total', 'hour')->toArray();
            // dd($data);
        } elseif ($type == 3 || $type== 4) {
            if ($type== 3) { // Tuần nay
                $date_from = date("Y-m-d", strtotime("monday this week"));
                $date_to = date("Y-m-d", strtotime("sunday this week"));
            } else {
                $date_from = date("Y-m-d", strtotime("last week monday"));
                $date_to = date("Y-m-d", strtotime("last sunday"));
            } 
            $date_from = $date_from . " 00:00:00";
            $date_to = $date_to . " 23:59:59";
            $arr = Statistical::selectRaw('COUNT(id) as total, DAY(created_at) as day')->whereBetween('created_at', [$date_from, $date_to])->groupBy('day')->pluck('total', 'day')->toArray();
            
            $period = new \DatePeriod(new \DateTime($date_from), new \DateInterval('P1D'), new \DateTime($date_to));

            foreach ($period as $date) {
                $tmp_day = $date->format("d");
                $tmp_day = (int)$tmp_day;

                if (!isset($arr[$tmp_day])) {
                    $data[] = ['day' => $tmp_day, 'value' => 0];
                } else {
                    $data[] = ['day' => $tmp_day, 'value' => $arr[$tmp_day]];
                }
            }
        } elseif ($type== 5 || $type== 6) {
            if ($type == 5) {  // Tháng này
                $thisMonth = date('m');
                $thisYear = date('Y');
            } else {
                $thisMonth = date('m') - 1 > 0 ? date('m') - 1 : 12;
                $thisYear = date('m') - 1 > 0 ? date('Y'): date('Y') - 1;
            }
            
            $results = \DB::select("SELECT COUNT(id) as total, DAY(created_at) as day FROM statisticals WHERE YEAR(created_at)=$thisYear AND MONTH(created_at)=$thisMonth GROUP BY DAY(created_at)");
            
            foreach ($results as $value) {
                $data[$value->day] = $value->total;
            } 
        } elseif ($type== 7 || $type== 8) {
            if ($type== 7) { // Năm nay
                $thisYear = date('Y');
            } else {
                $thisYear = date('Y') - 1;
            } 

            $results = \DB::select("SELECT COUNT(id) as total, MONTH(created_at) as month FROM statisticals WHERE YEAR(created_at)=$thisYear GROUP BY MONTH(created_at)");

            foreach ($results as $value) {
                $data[$value->month] = $value->total;
            }
        } else { // Tùy chỉnh
            // $date_from = $date_from . " 00:00:00";
            // $date_from = $date_to . " 23:59:59";
            // $data = Statistical::selectRaw('COUNT(id) as total, DATE_FORMAT(created_at, "%d/%m/%Y") as day')->where('type', 1)->whereBetween('created_at', [$request->date_from, $request->date_to])->groupBy('created_at')->pluck('total', 'day')->toArray();
        }

        return response()->json(['status' => 200, 'data' => $data, 'month' => $thisMonth, 'year' => $thisYear]);
    }
}