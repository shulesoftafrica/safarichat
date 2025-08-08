<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use DB;
use Auth;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use \App\Http\Controllers\Traits\Notify;

class Controller extends BaseController {

    use AuthorizesRequests,
        DispatchesJobs,
        ValidatesRequests,
        Notify;

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

    public function sendSms() {
        
    }

    public function send_email($email, $subject, $message) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {

                $link = 'https://dikodiko.africa';
                $data = ['content' => $message, 'link' => $link, 'photo' => '', 'sitename' => 'dikodiko', 'name' => ''];
                $message = (object) ['sitename' => 'dikodiko', 'email' => $email, 'subject' => $subject];
                $return = \Mail::send('email.default', $data, function ($m) use ($message) {
                            $m->from('noreply@shulesoft.com', $message->sitename);
                            $m->to($message->email)->subject($message->subject);
                        });
            } catch (\Exception $e) {
                $return = $e->getMessage();
            }
        }

        return $this;
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
    
public function notifySystemAdmin($message) {
    $adminContacts = ['admin@example.com', 'admin2@example.com']; // Replace with actual admin contacts
    foreach ($adminContacts as $contact) {
        // You can use a mail service or any other notification service to send the message
        mail($contact, 'System Notification', $message);
    }
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
       
         $action= app(\App\Http\Controllers\Message::class)->storeMessage($text, $chatId, $source,$instance_id);
        $action = DB::table('messages')->insert([
            'body' => $text,
            'user_id' => Auth::check() ? Auth::user()->id : 1,
            'created_at' => now(),
            'status' => 0,
            'phone' => $chatId,
           // 'type' => $source == 'whatsapp' ? 4 : 2,
        ]);
         
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
            $folder = "storage/uploads/";
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }
            $file = request()->file('file');
            $name = time() . rand(4343, 3243434) . '.' . $file->guessClientExtension();
            $move = $file->move($folder, $name);
            $path = $folder . $name;
            if (!$move) {
                die('upload Error');
            } else {
                $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            }
        } catch (Exception $e) {
            $this->resp->success = FALSE;
            $this->resp->msg = 'Error Uploading file';
            echo json_encode($this->resp);
        }
        $sheets = $objPHPExcel->getSheetNames();

        if ($sheet_name == null) {
            unlink($path);
            return $this->getDataBySheet($objPHPExcel, 0);
        } else {
            $data = [];
            foreach ($sheets as $key => $value) {
                $data[$value] = [];
            }
            foreach ($sheets as $key => $value) {
                $excel_data = $this->getDataBySheet($objPHPExcel, $key);
                count($excel_data) > 0 ? array_push($data[$value], $excel_data) : '';
            }
            return $data;
        }
    }

    public function getDataBySheet($objPHPExcel, $sheet_id) {
        $sheet = $objPHPExcel->getSheet($sheet_id);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headings = $sheet->rangeToArray('A1:' . $highestColumn . 1, NULL, TRUE, FALSE);
        $data = array();
        for ($row = 2; $row <= $highestRow; $row++) {
            //  Read a row of data into an array
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
            $rowData[0] = array_combine($headings[0], $rowData[0]);
            $data = array_merge_recursive($data, $rowData);
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
