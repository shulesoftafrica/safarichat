<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use Auth;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController {

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests;

    public $data;
    var $APIurl = '';
    var $token = '';
    public $bot;
    public $main_menu = '';
    public $users_events_id;
    public $default_instance_id='63159';
    public $epayment_enabled = false;


    public function getCleanSms($replacements, $message, $pattern = null) {
     
        $pattern = !empty($pattern) ? $pattern : $this->patterns;

        $array = array ( 'abc' => 'Test', 'def' => 'Variable', 'ghi' => 'Change' );

        $regexes = array_map(function ($k) { return "/" . preg_quote("%!$k!%") . "/"; }, array_keys($pattern));


        $sms = preg_replace($regexes, $replacements, $message);
        if (preg_match('/#/', $sms)) {
            //try to replace that character
            return preg_replace('/\#[a-zA-Z]+/i', '', $sms);
        } else {
            return $sms;
        }
    }



    public function sendTextMessage($chatId, $text, $source = null,$instance_id = null) {      
    
        $action=  (new Message())->send($text, $chatId);
         
         if($action){
            return response()->json(['status' => 'success', 'message' => 'Message saved successfully']);
          
         }else{
            return response()->json(['status' => 'error', 'message' => 'Failed to save message'], 500);
         }
    
    }

    public function curlServer($fields, $url = 'http://51.91.251.252:8081/api/payment') {
// Open connection

        $ch = curl_init();
// Set the url, number of POST vars, POST data

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ($fields));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    //PAYMENT API
    public function curlPaymentApi($orders = null) {
        return $this->curlServer($orders);
    }

    public function uploadExcel($sheet_name = null) {
        try {
            $file = request()->file('file');
            
            if (!$file) {
                $this->resp->success = FALSE;
                $this->resp->msg = 'No file uploaded';
                return json_encode($this->resp);
            }

            // Use Laravel Excel to read the file directly without saving
            $data = Excel::toArray([], $file);
            
            if (empty($data)) {
                $this->resp->success = FALSE;
                $this->resp->msg = 'Empty Excel file or invalid format';
                return json_encode($this->resp);
            }

            if ($sheet_name == null) {
                // Return first sheet data
                return $this->formatExcelData($data[0]);
            } else {
                // Return all sheets data with sheet names as keys
                $formatted_data = [];
                foreach ($data as $index => $sheet_data) {
                    $sheet_key = $sheet_name . '_' . $index; // Since we don't have actual sheet names
                    $formatted_data[$sheet_key] = [$this->formatExcelData($sheet_data)];
                }
                return $formatted_data;
            }
        } catch (\Exception $e) {
            $this->resp->success = FALSE;
            $this->resp->msg = 'Error processing Excel file: ' . $e->getMessage();
            return json_encode($this->resp);
        }
    }

    private function formatExcelData($sheet_data) {
        if (empty($sheet_data) || count($sheet_data) < 2) {
            return [];
        }
        
        // First row contains headers
        $headers = $sheet_data[0];
        $data = [];
        
        // Process remaining rows
        for ($i = 1; $i < count($sheet_data); $i++) {
            $row = $sheet_data[$i];
            // Combine headers with row data
            $formatted_row = array_combine($headers, $row);
            $data[] = $formatted_row;
        }
        
        return $data;
    }

    public function notifyAdmin($message, $type = 2) {
        $admins = ['255689353642@c.us', '255714825469@c.us'];
        foreach ($admins as $admin) {
            $this->sendTextMessage($admin, $message, 1);
        }
    }

    
  
}
