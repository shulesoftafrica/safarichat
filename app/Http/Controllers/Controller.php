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


    public function createPassword($users) {
        $pass = rand(1, 999) . substr(str_shuffle('abcdefghkmnp'), 0, 3);
        $password = bcrypt($pass);
        $user_info = DB::table($users->schema_name . '.' . $users->table)->where($users->table . 'ID', $users->id);
        $user_info->update(['password' => $password, 'default_password' => $pass]);
        return $pass;
    }

    //this function calls function sendRequest to send a simple message
    //@param $chatId [string] [required] - the ID of chat where we send a message
    //@param $text [string] [required] - text of the message
    public function welcome($chatId, $noWelcome = false) {
        $welcomeString = ($noWelcome) ? "Incorrect command\n" : "WhatsApp Demo Bot PHP\n";
        $this->sendTextMessage($chatId, $welcomeString .
                "Commands:\n" .
                "1. chatId - show ID of the current chat\n" .
                "2. time - show server time\n" .
                "3. me - show your nickname\n" .
                "4. file [format] - get a file. Available formats: doc/gif/jpg/png/pdf/mp3/mp4\n" .
                "5. ptt - get a voice message\n" .
                "6. geo - get a location\n" .
                "7. group - create a group with the bot"
        );
    }
    


    //sends Id of the current chat. it is called when the bot gets the command "chatId"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    public function showchatId($chatId) {
        $this->sendTextMessage($chatId, 'chatId: ' . $chatId);
    }

    //sends current server time. it is called when the bot gets the command "time"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    public function time($chatId) {
        $this->sendTextMessage($chatId, date('d.m.Y H:i:s'));
    }

    //sends your nickname. it is called when the bot gets the command "me"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    //@param $name [string] [required] - the "senderName" property of the message
    public function me($chatId, $name) {
        $this->sendTextMessage($chatId, $name);
    }

    //sends a file. it is called when the bot gets the command "file"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    //@param $format [string] [required] - file format, from the params in the message body (text[1], etc)
    public function file($chatId, $format, $filename, $caption = null) {
        $availableFiles = array(
            'doc' => 'document.doc',
            'gif' => 'gifka.gif',
            'jpg' => 'jpgfile.jpg',
            'png' => 'pngfile.png',
            'pdf' => 'presentation.pdf',
            'mp4' => 'video.mp4',
            'mp3' => 'mp3file.mp3'
        );

        if (isset($availableFiles[$format])) {
            $data = array(
                'chatId' => $chatId,
                'body' => $filename,
                'filename' => $availableFiles[$format],
                'caption' => $caption
            );
            $this->sendRequest('sendFile', $data);
        }
        if (strtolower($format) == 'ogg') {
            $data = array(
                'audio' => $filename,
                'chatId' => $chatId
            );
            $this->sendRequest('sendAudio', $data);
        }
    }

    //sends a voice message. it is called when the bot gets the command "ptt"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    public function ptt($chatId) {
        $data = array(
            'audio' => 'https://domain.com/PHP/ptt.ogg',
            'chatId' => $chatId
        );
        $this->sendRequest('sendAudio', $data);
    }

    //sends a location. it is called when the bot gets the command "geo"
    //@param $chatId [string] [required] - the ID of chat where we send a message
    public function geo($chatId) {
        $data = array(
            'lat' => 51.51916,
            'lng' => -0.139214,
            'address' => 'Ваш адрес',
            'chatId' => $chatId
        );
        $this->sendRequest('sendLocation', $data);
    }

    //creates a group. it is called when the bot gets the command "group"
    //@param chatId [string] [required] - the ID of chat where we send a message
    //@param author [string] [required] - "author" property of the message
    public function group($author) {
        $phone = str_replace('@c.us', '', $author);
        $data = array(
            'groupName' => 'Group with the bot PHP',
            'phones' => array($phone),
            'messageText' => 'It is your group. Enjoy'
        );
        $this->sendRequest('group', $data);
    }

    public function sendTextMessage($chatId, $text, $source = null,$instance_id = null) {      
    
        $action=  (new Message())->send($text, $chatId);
         
         if($action){
            return response()->json(['status' => 'success', 'message' => 'Message saved successfully']);
          
         }else{
            return response()->json(['status' => 'error', 'message' => 'Failed to save message'], 500);
         }
    
    }

    public function sendRequest($method, $data, $source = null) {
        if (empty(Auth::user()) || $source == 1) {
            $whatsapp_url = 'https://api.chat-api.com/instance269111/';
            $token = 'fztc8hvuc6lrwbyr';
            $url = $whatsapp_url . $method . '?token=' . $token;
        } else {
            $event = DB::table('events')->whereIn('id', Auth::user()->usersEvents()->get(['event_id']))->where('status', 1)->first();
            $url = $event->whatsapp_api_url . $method . '?token=' . $event->whatsapp_token;
        }

        if (is_array($data)) {
            $data = json_encode($data);
        }
        $options = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $data]]);

        //$response = file_get_contents($url, false, $options);
        // $response = $this->curlServer($body, $url);

        //$requests = array('chat_id' => '43434', 'text' => $response, 'parse_mode' => '', 'source' => 'user');
        // file_put_contents('requests.log', $response . PHP_EOL, FILE_APPEND);
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

    public function saveFile($file) {
//Move Uploaded File

        $destinationPath = 'storage/uploads';
        //!is_dir($destinationPath) ? mkdir($destinationPath) : '';
        $filename = $file->getClientOriginalName();
        $path = $destinationPath . '/' . $filename;
        if (!file_exists($path)) {
            // $file->move($destinationPath, $filename);
            $file->move(public_path() . "/images/", $file->getClientOriginalName());
        }
        //request()->file->move($destinationPath, $filename);
        // Storage::putFile($destinationPath,$filename);
        return url($path);
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
