<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class Api extends Controller {

    public function whatsapp() {
        //get the JSON body from the instance
        $json = file_get_contents('php://input');
        $decoded = json_decode($json, true);
        DB::table('whatsapp_logs')->insert(['content' => json_encode($decoded)]);
        //write parsed JSON-body to the file for debugging
//        ob_start();
//        var_dump($decoded);
//        $input = ob_get_contents();
//        ob_end_clean();
//        file_put_contents('input_requests.log', $input . PHP_EOL, FILE_APPEND);

        if (isset($decoded['messages'])) {
            //check every new message
            foreach ($decoded['messages'] as $message) {
                //delete excess spaces and split the message on spaces. The first word in the message is a command, other words are parameters
                $text = explode(' ', trim($message['body']));
                //current message shouldn't be send from your bot, because it calls recursion
                if (!$message['fromMe']) {
                    //check what command contains the first word and call the function
                    // switch (mb_strtolower($text[0], 'UTF-8')) {
                    if (preg_match('/login/i', mb_strtolower($text[0], 'UTF-8'))) {
                        // $this->welcome($message['chatId'], false);
                        $this->authUser($message['chatId'], mb_strtolower($text[0], 'UTF-8'));
                    } else {
                        $bot_message = $this->getWhatsAppBotMessage(mb_strtolower($text[0], 'UTF-8'));
                        $this->sendTextMessage($message['chatId'], $bot_message, 1);
                    }
//                        case 'chatId': {
//                                $this->showchatId($message['chatId']);
//                                break;
//                            }
//                        case 'time': {
//                                $this->time($message['chatId']);
//                                break;
//                            }
//                        case 'me': {
//                                $this->me($message['chatId'], $message['senderName']);
//                                break;
//                            }
//                        case 'file': {
//                                $this->file($message['chatId'], $text[1]);
//                                break;
//                            }
//                        case 'ptt': {
//                                $this->ptt($message['chatId']);
//                                break;
//                            }
//                        case 'geo': {
//                                $this->geo($message['chatId']);
//                                break;
//                            }
//                        case 'group': {
//                                $this->group($message['author']);
//                                break;
//                            }
//                    default: {
//                        //  $this->welcome($message['chatId'], true);
//                        $bot_message = $this->bot->createMessage(mb_strtolower($text[0], 'UTF-8'), $message['chatId']);
//                        $this->sendTextMessage($message['chatId'], $bot_message['text']);
//                        break;
//                    }
                }
                //  }
            }
        }
    }

    public function otp(){
        // Sanitize phone number to only allow digits
        $rawPhone = request('email');
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        // Ensure only 9 digits
        $cleanPhone = substr($cleanPhone, -9);
        if (empty($cleanPhone)) {
            return back()->with('error', 'Invalid phone number provided');
        }
        $this->data['phone'] = $phone = $cleanPhone;
        $verify_code = rand(192, 999) . substr(str_shuffle('123456789'), 0, 3);
        $message = 'Hello, Your Verification Code is ' . $verify_code;
        $existing = DB::table('otpcodes')->where('phone', $phone)->where('status', 0)->first();
        if ($existing) {
            DB::table('otpcodes')->where('phone', $phone)->where('status', 0)->update([
            'code' => bcrypt($verify_code)
            ]);
        } else {
            DB::table('otpcodes')->insert([
            'phone' => $phone,
            'code' => bcrypt($verify_code)
            ]);
        }
        $this->sendTextMessage($phone, $message, 'whatsapp','reset_pass');
        $this->data['message']='';

         return view('auth.verify', $this->data);
    }

    public function otpverify(){
        $phone = request('email');
        $input_code = request('otp');

        // Retrieve the OTP sent to the user from session or database
        // For this example, let's assume OTP is stored in session
        $stored_code = optional(DB::table('otpcodes')->where('phone', $phone)->first())->code;

     
        if ($input_code && $stored_code && password_verify($input_code, $stored_code)) {
            // OTP is valid, proceed to registration page
            // Optionally, you can clear the OTP from session or database

            //lets check if user already registered, so send him to dashboard
            $user = DB::table('users')->where('phone', $phone)->first();
            if ($user) {
                // Update password with new OTP code
                DB::table('users')->where('id', $user->id)->update([
                    'password' => bcrypt($input_code),
                   
                    'updated_at' => now(),
                ]);
                // Attempt login
                if ($this->loginUser($phone, $input_code)) {
                    //register whatsapp instance as default

                    return redirect('/home');
                } else {
                    $this->data['phone'] = $phone;
                    $this->data['message'] = 'Failed to login. Please try again.';
                    return view('auth.verify', $this->data);
                }
            }

            // session()->forget('otp_code_' . $phone);
            return view('auth.register', ['phone' => $phone]);
        } else {
            // OTP is invalid, reload verify page with error
            $this->data['phone'] = $phone;
            $this->data['message'] = 'Invalid OTP code. Please try again.';
           
            return view('auth.verify', $this->data);
        }
     }

    public function getWhatsAppBotMessage($id) {
        $content = DB::table('whatsapp_bot_contents')->where('number', (int) $id)->first();
        if (!empty($content)) {
            $check_group = DB::table('whatsapp_bot_contents')->where('number', '<>', (int) $id)->where('group_id', $id)->first();
            if (!empty($check_group)) {
                $reply = '';
                $reply .= $content->content;
                $contents = DB::table('whatsapp_bot_contents')->where('number', '<>', (int) $id)->where('group_id', $id)->orderBy('number', 'ASC')->get();

                $reply .= chr(10);
                if (count($contents) > 0) {
                    $reply .= chr(10);
                    $reply .= 'Type the number below to proceed.' . chr(10);
                    $reply .= chr(10);
                    foreach ($contents as $menu) {
                        $reply .= $menu->number . ' .  ' . $menu->menu . chr(10);
                    }
                    $reply .= chr(10);
                    return $reply;
                } else {
                    return $content->content;
                }
            } else {
                return $content->content;
            }
        } else {
            $reply = 'Invalid Number is Supplied.';
            $reply .= chr(10);
            $reply .= chr(10);
            $reply = 'Do you need any help, just click the number below to proceed.' . chr(10);
            $reply .= chr(10);
            $menus = DB::table('whatsapp_bot_contents')->where('parent', 1)->orderBy('number', 'asc')->limit(5)->get();
            foreach ($menus as $menu) {
                $reply .= $menu->number . ' .  ' . $menu->menu . chr(10);
            }
            $reply .= chr(10);
            return $reply;
        }
    }

    /**
     * Description:
     * This method will be updated later to allow two things
     * 
     *  1. The same login credentials for a number exists in all schemas
     *  2. Authenticate if message real come from ShuleSoft web to prevent illigal testing
     * @param type $chat_id
     * @param type $schema_message
     */
    public function authUser($chat_id, $schema_message = null) {
        $phone = str_replace('@c.us', NULL, $chat_id);

        if (preg_match('/[0-9]/', $phone) && is_array(validate_phone_number($phone))) {
            $user = DB::table('users')->where('phone', 'ilike', "%{$phone}%")->first();
            if (!empty($user)) {

                $reply = 'Thank you ' . $user->name . '.' . chr(10);
                $reply .= chr(10);
                $reply .= 'Your Email - ' . $user->email . chr(10);
                $reply .= 'Your Password - ' . $this->createPassword($user) . chr(10);
                $reply .= chr(10);

                $reply .= chr(10);
                $reply .= 'Use this infomation to login at  https://dikodiko.co.tz/ and kindly change this password after login' . chr(10);
                $reply .= chr(10) . 'For more help call us ';

                $message = array('chat_id' => $chat_id, 'text' => $reply . $this->main_menu, 'parse_mode' => 'HTML');
            } else {
                $reply = 'Your WhatSapp phone number does not exist in DikoDiko Account. Open www.dikodiko.co.tz and create your account.' . chr(10);
                $message = array('chat_id' => $chat_id, 'text' => $reply . $this->main_menu, 'parse_mode' => 'HTML');
            }
        } else {
            $reply = 'Your account does not have a valid phone number, kindly contact  us ' . chr(10);
            $message = array('chat_id' => $chat_id, 'text' => $reply . $this->main_menu, 'parse_mode' => 'HTML');
        }
        $this->sendTextMessage($chat_id, $message['text'], 1);
    }

    public function createPassword($users) {
        $pass = rand(1, 999) . substr(str_shuffle('abcdefghkmnp'), 0, 3);
        $password = bcrypt($pass);
        $user_info = DB::table('users')->where('id', $users->id);
        $user_info->update(['password' => $password]);
        return $pass;
    }

    public function pushEmailsToSend() {
        $pending = DB::select("SELECT b.channel,a.email,a.phone as phone_number, b.id||'_dikodiko' as sms_id, b.id as message_sentby_id,"
                        . " a.body as message,a.subject, 'DIKODIKO' as name, case when b.channel='quick-sms' then 1 else 0 end as karibusmspro, "
                        . "(select api_key from dikodiko.users_keys where user_id=a.user_id and type=b.channel), (select api_secret from dikodiko.users_keys where user_id=a.user_id "
                        . " and type=b.channel) FROM dikodiko.messages a join dikodiko.messages_sentby b on a.id=b.message_id  where return_code is null and channel in ('email','quick-sms') limit 50");
        $object = [];
        if (!empty($pending)) {
            foreach ($pending as $message) {
                if ($message->channel == 'email' && filter_var($message->email, FILTER_VALIDATE_EMAIL)) {
                    $chat_id = $message->email;

                    (new \App\Http\Controllers\Message())->sendCustomEmail($chat_id, $message->body, $message->message_sentby_id);
                } else {
                    array_push($object, $message);
                    \App\Models\MessageSentby::where('id', $message->message_sentby_id)->update(['status' => 1, 'return_code' => 'pushed to be sent', 'updated_at' => 'now()']);
                }
            }
        }
        return $object;
    }

    private function resendNonDelivered($user_id) {
        $pending = DB::select("SELECT a.phone, b.id, a.body FROM "
                        . " dikodiko.messages a join dikodiko.messages_sentby b on a.id=b.message_id  where user_id=" . $user_id . " and"
                        . " b.status=1 and channel in ('phone-sms') limit 5");
        $object = [];
        if (!empty($pending)) {
            foreach ($pending as $message) {
                array_push($object, (array) $message);
                \App\Models\MessageSentby::where('id', $message->id)->update(['status' => 2, 'return_code' => 'pushed to be sent', 'updated_at' => 'now()']);
            }
        }
        return json_encode(['messages' => $object]);
    }

    public function pushPhoneSMS() {
        $code = request()->segment(3);
        $verify = DB::table('users_keys')->where('api_key', trim($code))->where('type', 'phone-sms')->first();
        $object = [];
        if (!empty($verify)) {
            $pending = DB::select("SELECT a.phone, b.id, a.body FROM dikodiko.messages a join dikodiko.messages_sentby b on a.id=b.message_id  where user_id=" . $verify->user_id . " and"
                            . " return_code is null and channel in ('phone-sms') limit 5");
            if (!empty($pending)) {
                foreach ($pending as $message) {
                    array_push($object, (array) $message);
                    \App\Models\MessageSentby::where('id', $message->id)->update(['status' => 1, 'return_code' => 'pushed to be sent', 'updated_at' => 'now()']);
                }
                DB::table('users_keys')->where('api_key', trim($code))->update(['last_active' => 'now()']);
            } else {
                //wait for 3sec then check empty non delivered
                sleep(3);
                return $this->resendNonDelivered($verify->user_id);
            }
        } else {
            array_push($object, ['phone' => '0714825469', 'body' => 'Invalid Code supplied', 'id' => 1, 'code' => $code]);
        }
        return json_encode(['messages' => $object]);
    }

    public function aunthenticateMobile() {
        $code = request()->segment(3);
        $ime = request()->segment(4);
        $model = request()->segment(5);
        $verify = DB::table('users_keys')->where('api_key', $code)->where('type', 'phone-sms')->first();
        if (!empty($verify)) {
            DB::table('users_keys')->where('api_key', $code)->update(['device' => $ime, 'others' => $model, 'last_active' => 'now()']);
            $status = 1;
        } else {
            $status = 0;
            $code = '';
        }
        echo json_encode(['data' => [['code' => $code, 'status' => $status]]]);
    }

    public function updatestatus() {
        $code = request()->segment(3);
        $sms_id = request()->segment(4);
        $ime = request()->segment(5);
        \App\Models\MessageSentby::where('id', $sms_id)->update(['status' => 2, 'return_code' => 'message sent', 'updated_at' => 'now()', 'device' => $ime]);
        DB::table('users_keys')->where('api_key', trim($code))->update(['last_active' => 'now()']);
        echo json_encode(['reports' => [['code' => $code, 'status' => $ime]]]);
    }

    public function smsReport() {
        $code = request()->segment(3);
        $verify = DB::table('users_keys')->where('api_key', $code)->where('type', 'phone-sms')->first();
        if (!empty($verify)) {
            $sent_sms = \App\Models\MessageSentby::where('channel', 'phone-sms')->whereNotNull('return_code')->whereIn('message_id', \App\Models\Message::whereUserId($verify->user_id)->get(['id']))->count();
            $pending_sms = \App\Models\MessageSentby::where('channel', 'phone-sms')->whereNull('return_code')->whereIn('message_id', \App\Models\Message::whereUserId($verify->user_id)->get(['id']))->count();
        } else {
            $sent_sms = 0;
            $pending_sms = 0;
        }
        echo json_encode(['reports' => [['sent' => $sent_sms, 'pending' => $pending_sms]]]);
    }

    public function apiAcceptPayment() {
        $transid = request('transid');
        $order_id = request('order_id');
        $reference = request('reference');
        $result = request('result');
        $payment_status = request('payment_status');

        $book = \App\Models\AdminBooking::where('order_id', $order_id);
        $valid = $book->first();
        if (!empty($valid)) {
            $book->update(['status' => 1, 'reference' => $reference]);
        }
        $payment = \App\Models\AdminPayment::create([
                    'user_id' => $valid->user_id,
                    'amount' => $valid->amount,
                    'transaction_id' => $transid,
                    'method' => request('channel'),
                    'date' => 'now()',
                    'admin_booking_id' => $valid->id
        ]);
        $event = $book->user->usersEvents()->first();
        \App\Models\AdminPackagePayment::create([
            'admin_payment_id' => $payment->id,
            'admin_package_id' => $valid->admin_package_id,
            'start_date' => 'now()',
            'end_date' => !empty($event) ? $event->event->date : 'now()+30 days'
        ]);

        $subject = 'Dikodiko Payment Accepted';
        $message = 'Hello ' . $book->user->name . ' ,<br/>'
                . ' Your payment with reference number ' . $valid->token . ' has been accepted successfully.';

        $chat_id = $book->user->phone . '@c.us';
        $this->send_email($book->user->email, $subject, $message);
        $this->sendTextMessage($chat_id, $message, 1);
        return true;
    }

    public function liveEvent() {
        $event_uid = request()->segment(2);
        $this->data['noevent'] = 0;
        if (strlen($event_uid) > 4) {
            $this->data['guest'] = $guest = \App\Models\EventsGuest::where('code', $event_uid)->firstOrFail();
            $this->data['event'] = $guest->event;
            return view('event.live', $this->data);
        } else {
            $this->data['noevent'] = 1;
            return view('event.live', $this->data);
        }
    }

    public function verifyCode() {
        $code = request('code');
        $guests = \App\Models\EventsGuest::where('code', $code)->first();
        if (!empty($guests)) {
            DB::table('live_attendees')->insert(['events_guest_id' => $guests->id, 'device' => getDevice()]);
            return redirect(url('live/' . $code))->with('success', 'welcome');
        } else {
            return redirect()->back()->with('error', 'Invalid code supplied');
        }
    }

    public function resendCode() {
        $code = request('val');
        $phone = validate_phone_number($code);
        if (!is_array($phone)) {
            die('Invalid Phone Number, please enter a valid number');
        }
        $guests = \App\Models\EventsGuest::where('guest_phone', $phone[1])->first();
        if (!empty($guests)) {
            $verify_code = rand(192, 9999) . substr(str_shuffle('abcdefghkmnpqrst'), 0, 4);
            $message = 'Hello, Your Verification Code is ' . $verify_code;
            $messages = \App\Models\Message::firstOrCreate([
                        'body' => $message, 'user_id' => $guests->event->usersEvents()->first()->user->id, 'phone' => str_replace('@c.us', NULL, $phone[1])
            ]);
            \App\Models\MessageSentby::create(['message_id' => $messages->id, 'channel' => 'phone-sms']);
            echo "Success: Code has been resent, kindly check your phone within 5 min";
            ;
        } else {
            echo 'This number is not registered, kindly use another number or contact event owner';
        }
    }

    public function getPayment() {
        $amount = request('amount');
        $code = request()->segment(2);
        $guest = \App\Models\EventsGuest::where('code', $code)->firstOrFail();

        $user_phone = $guest->guest_phone;

        $phone = str_replace('+', null, validate_phone_number($user_phone)[1]);
        $order_id = 'dikou' . time();
        $order = array("order_id" => $order_id, "amount" => $amount,
            'buyer_name' => Auth::user()->name, 'buyer_phone' => str_replace('+', null, validate_phone_number($user_phone)[1]));

        $order = (object) $orders;
        $booking = \App\Models\AdminBooking::create([
                    'order_id' => $order->order_id, 'amount' => $order->amount, 'methods' => $service, 'user_id' => Auth::user()->id, 'admin_package_id' => $package_id
        ]);
        //push request to shulesoft api url
        $request = json_decode($this->curlPaymentApi($orders));

        //get the booking you need
        if (is_object($request)) {
            $booking->update(['token' => $request->payment_token, 'payment_gateway_url' => $request->payment_gateway_url, 'qr' => $request->qr]);
        }

        $this->data['minimal'] = 1;
        $this->data['booking'] = DB::table('admin_bookings')->where('order_id', $order_id)->first();
        if (!empty($this->data['booking'])) {
            $message = 'Hello *' . Auth::user()->name . '*' . chr(10) . chr(10)
                    . 'Your *Control Number* for whatsapp Payment has been created' . chr(10)
                    . 'Use' . chr(10)
                    . 'Control Number: *' . $this->data['booking']->token . '*' . chr(10)
                    . 'Pay via *Selcom Mastarpass QR* to proceed with ShuleSoft whatsapp Business' . chr(10) . 'Thanks';
            $chat_id = $phone . '@c.us';
            $this->sendTextMessage($chat_id, $message, 1);
        }
        //check registration if not register
        in_array($addons->name, ['whatsapp', 'quick-sms']) ? DB::table('admin_integration_requests')->where('user_id', Auth::user()->id)->count() == 1 ? '' :
                                DB::table('admin_integration_requests')->insert(['user_id' => Auth::user()->id,
                                    'phone' => $phone]) : '';
        return view('auth.payment', $this->data);
    }

    public function getDistrict() {
        $select = '<select class="form-control" id="services"><option>Select</option>';
        $districts = \App\Models\District::where('region_id', request('region_id'))->get();
        foreach ($districts as $district) {
            $select .= '<option value="' . $district->id . '">' . ucwords(strtolower($district->name)) . '</option>';
        }
        $select .= ' </select>';
        echo $select;
    }

    public function getWard() {
        $select = '<select class="form-control" id="services"><option>Select</option>';
        $districts = \App\Models\Ward::where('district_id', request('district_id'))->get();
        foreach ($districts as $district) {
            $select .= '<option value="' . $district->id . '">' . ucwords(strtolower($district->name)) . '</option>';
        }
        $select .= ' </select>';
        echo $select;
    }

    public function createUser() {
        $request = request();
        $data = $request->all();

        // Determine password: from session or from otpcodes table
        if (session()->has('otpcode') && !empty(session('otpcode'))) {
            $code=session('otpcode');
        } else {
            $otpRecord = DB::table('otpcodes')
            ->where('phone', $data['sp_whatsapp'] ?? null)
            ->orderByDesc('id')
            ->first();
             $code=$otpRecord->code;   
        }
        $password = bcrypt($code);
        
        // Determine user type and prepare data
        if (isset($data['name']) && isset($data['politician_type'])) {
            // Politician registration
            $user = [
                'name' => $data['name'],
                'phone' => $data['sp_whatsapp'] ?? $data['sp_whatsapp'] ?? null,
                'user_type_id' => 1,
                'verified'=>1,
                'password'=>$password,
                'created_at' => now(),
                'updated_at' => now(),
                'username' => strtolower(str_replace(' ', '', trim($data['name'] . $data['sp_whatsapp']))),
            ];
        } elseif (isset($data['sp_type']) && isset($data['sp_name'])) {
            // Service provider registration
            $user = [
                'name' => $data['sp_name'],
                'phone' => $data['sp_whatsapp'] ?? $data['sp_whatsapp'] ?? null,
                'user_type_id' => 2,
                'verified'=>1,
                'password'=>$password,
              // 'sp_type' => $data['sp_type'],
               // 'business_type' => $data['sp_business_type'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
                'username' => strtolower(str_replace(' ', '', trim($data['name'] . $data['sp_whatsapp']))),
            ];
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid registration data.'], 422);
        }


        // Insert user and return response
        $userId = DB::table('users')->insertGetId($user);

        $client = DB::connection('shulesoft')->table('admin.clients')->insert([
            'name' => $data['name'],
            'phone' => $data['sp_whatsapp'] ?? $data['sp_whatsapp'] ?? null,
           'username' => strtolower(str_replace(' ', '', trim($data['name'])) . $data['sp_whatsapp']),
            'region_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'username' => strtolower(str_replace(' ', '', trim($data['name'] . $data['sp_whatsapp']))),

        ]);


        if (isset($data['politician_type']) && isset($data['party'])) {
            DB::table('users_political_parties')->insert([
                'user_id' => $userId,
                'party_id' => $data['party'],
                'created_at' => now(),
                'updated_at' => now(),
                'username' => strtolower(str_replace(' ', '', trim($data['name'] . $data['sp_whatsapp']))),
            ]);
        }else{
            if (!empty($data['sp_business_type']) && !empty($data['sp_sub_category'])) {
                DB::table('user_sub_category')->insert([
                    'user_id' => $userId,
                    'sub_category_id' => $data['sp_sub_category'],
                    'created_at' => now(),
                    'updated_at' => now(),
                'username' => strtolower(str_replace(' ', '', trim($data['name'] . $data['sp_whatsapp']))),
                ]);
            }
        }
    $adminPhone = '255714825469'; // Replace with actual admin phone number
    $userDetails = "New user registered:\n";
    $userDetails .= "Name: " . ($data['name'] ?? $data['sp_name'] ?? '') . "\n";
    $userDetails .= "Phone: " . ($data['sp_whatsapp'] ?? '') . "\n";
    if (isset($data['politician_type'])) {
        $userDetails .= "Type: Politician\n";
        $userDetails .= "Party: " . ($data['party'] ?? '') . "\n";
    } elseif (isset($data['sp_type'])) {
        $userDetails .= "Type: Service Provider\n";
        $userDetails .= "Business Type: " . ($data['sp_business_type'] ?? '') . "\n";
        $userDetails .= "Sub Category: " . ($data['sp_sub_category'] ?? '') . "\n";
    }
    $this->sendTextMessage($adminPhone, $userDetails, 'whatsapp', 'reset_pass');

        $login=$this->loginUser($user['phone'], $code);
        
        return $login? response()->json([
            'success' => true,
            'message' => 'User registered successfully.',
            'redirect' => url('/home'),
            'user_id' => $userId
        ]): response()->json([
            'success' => false,
            'message' => 'Failed to login after registration.'
        ], 500);

        
    }


    // public function storeUserInfo() {
    //     $request = request();
    //     $data = $request->all();

    //     // Validate input
    //     // if (!isset($data['name']) || !isset($data['phone'])) {
    //     //     return response()->json(['success' => false, 'message' => 'Name and phone are required.'], 422);
    //     // }

    //     // Generate username from name
    //     $username = strtolower(str_replace(' ', '', $data['name'])) . rand(100, 999);

    //     // Store in database
    //     $userId = DB::table('admin.clients')->insertGetId([
    //         'name' => $data['name'],
    //         'phone' => $data['phone'],
    //         'username' => $username,
    //         'created_at' => now(),
    //         'updated_at' => now()
    //     ]);
    

    //     return true;

    //     // return response()->json([
    //     //     'success' => true,
    //     //     'message' => 'User info stored successfully.',
    //     //     'data' => [
    //     //         'user_id' => $userId,
    //     //         'username' => $username
    //     //     ]
    //     // ]);
    // }

    public function loginUser($phone, $password) {
        $credentials = ['phone' => $phone, 'password' => $password];
        if (auth()->attempt($credentials)) {
            DB::table('users')->where('phone', $phone)->update(['verified' => 1]);
            session(['user_id' => auth()->id()]);
            return true;
        }
        return false;
    }

    public function sub_categories(){
        $categoryId = request()->segment(3) ?? request('id');
        if (!$categoryId) {
            return response()->json([]);
        }
        $subCategories = \DB::table('services')
            ->where('category_id', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name']);
        return response()->json($subCategories);
    }

    public function parties(){
        $parties = \App\Models\PoliticalParty::all(['id', 'name']);
        return response()->json($parties);
    }

    public function registerBusiness(){
        $request = request();
        $data = $request->all();

        // Validate required fields
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'business_type' => 'required|string|max:50',
            'business_data' => 'required|string'
        ]);

        $businessData = json_decode($data['business_data'], true);

        // Save to users table
        $userId = \DB::table('users')->insertGetId([
            'phone' => $data['phone'],
            'name' => $businessData['owner_name'] ?? '',
            'business_name' => $businessData['business_name'] ?? '',
            'business_type' => $data['business_type'],
            'business_size' => $businessData['business_size'] ?? '',
            'region' => $businessData['region'] ?? '',
            'customer_volume' => $businessData['customer_volume'] ?? '',
            'years_in_business' => $businessData['years_in_business'] ?? '',
            'budget_range' => $businessData['budget_range'] ?? '',
            'password'=> bcrypt($data['phone']), // Use phone as password for simplicity
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Save communication channels
        if (!empty($businessData['current_channels'])) {
            foreach ($businessData['current_channels'] as $channel) {
                \DB::table('user_communication_channels')->insert([
                    'user_id' => $userId,
                    'channel' => $channel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Save business goals
        if (!empty($businessData['business_goals'])) {
            foreach ($businessData['business_goals'] as $goal) {
                \DB::table('user_business_goals')->insert([
                    'user_id' => $userId,
                    'goal' => $goal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Save desired features
        if (!empty($businessData['desired_features'])) {
            foreach ($businessData['desired_features'] as $feature) {
                \DB::table('user_desired_features')->insert([
                    'user_id' => $userId,
                    'feature' => $feature,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Optionally, log registration or send welcome message
       // $this->sendTextMessage($data['phone'], 'Welcome to SafariChat! Your business profile has been created.', 'whatsapp');

        return redirect('/home')->with('success', 'Business profile registered successfully!');
    }

    /**
     * Save WhatsApp instance data to database
     */
    public function saveWhatsappInstance(\Illuminate\Http\Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'instance_id' => 'required',
                'instance_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'webhook_url' => 'nullable|url',
                'status' => 'required|string|in:connecting,connected,disconnected,error'
            ]);

            // Check if table exists, if not create it
            if (!\Schema::hasTable('whatsapp_instances')) {
                \Schema::create('whatsapp_instances', function ($table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->string('instance_id')->unique();
                    $table->string('instance_name');
                    $table->string('phone_number');
                    $table->string('webhook_url')->nullable();
                    $table->enum('status', ['connecting', 'connected', 'disconnected', 'error'])->default('connecting');
                    $table->json('metadata')->nullable(); // For storing additional WAAPI response data
                    $table->timestamp('last_seen')->nullable();
                    $table->timestamps();
                    
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->index(['user_id', 'status']);
                });
            }

            // Check if instance already exists for this user
            $existingInstance = \DB::table('whatsapp_instances')
                ->where('user_id', $request->user_id)
                ->where('instance_id', $request->instance_id)
                ->first();

            if ($existingInstance) {
                // Update existing instance
                \DB::table('whatsapp_instances')
                    ->where('id', $existingInstance->id)
                    ->update([
                        'instance_name' => $request->instance_name,
                        'phone_number' => $request->phone_number,
                        'webhook_url' => $request->webhook_url,
                        'status' => $request->status,
                        'updated_at' => now(),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp instance updated successfully',
                    'data' => [
                        'id' => $existingInstance->id,
                        'instance_id' => $request->instance_id,
                        'status' => $request->status
                    ]
                ]);
            } else {
                // Create new instance record
                $instanceId = \DB::table('whatsapp_instances')->insertGetId([
                    'user_id' => $request->user_id,
                    'instance_id' => $request->instance_id,
                    'instance_name' => $request->instance_name,
                    'phone_number' => $request->phone_number,
                    'webhook_url' => $request->webhook_url,
                    'status' => $request->status,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'WhatsApp instance saved successfully',
                    'data' => [
                        'id' => $instanceId,
                        'instance_id' => $request->instance_id,
                        'status' => $request->status
                    ]
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Failed to save WhatsApp instance: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save WhatsApp instance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update WhatsApp instance status
     */
    public function updateInstanceStatus(\Illuminate\Http\Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'instance_id' => 'required|string',
                'status' => 'required|string|in:connecting,connected,disconnected,error',
                'metadata' => 'nullable|array' // For additional status data from WAAPI
            ]);

            // Find the instance
            $instance = \DB::table('whatsapp_instances')
                ->where('instance_id', $request->instance_id)
                ->first();

            if (!$instance) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp instance not found'
                ], 404);
            }

            // Prepare update data
            $updateData = [
                'status' => $request->status,
                'updated_at' => now(),
            ];

            // Update last_seen if connected
            if ($request->status === 'connected') {
                $updateData['last_seen'] = now();
            }

            // Update metadata if provided
            if ($request->has('metadata') && !empty($request->metadata)) {
                $updateData['metadata'] = json_encode($request->metadata);
            }

            // Update the instance
            \DB::table('whatsapp_instances')
                ->where('instance_id', $request->instance_id)
                ->update($updateData);

            // If status changed to connected, you might want to trigger additional actions
            if ($request->status === 'connected') {
                // Log successful connection
                \Log::info('WhatsApp instance connected successfully', [
                    'instance_id' => $request->instance_id,
                    'user_id' => $instance->user_id
                ]);

                // You can add additional logic here like:
                // - Sending welcome message to user
                // - Updating user's account status
                // - Triggering onboarding notifications
            }

            return response()->json([
                'success' => true,
                'message' => 'Instance status updated successfully',
                'data' => [
                    'instance_id' => $request->instance_id,
                    'old_status' => $instance->status,
                    'new_status' => $request->status,
                    'updated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Log the error
            \Log::error('Failed to update WhatsApp instance status: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update instance status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's WhatsApp instances
     */
    public function getUserWhatsappInstances(\Illuminate\Http\Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID is required'
                ], 400);
            }

            $instances = \DB::table('whatsapp_instances')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp instances retrieved successfully',
                'data' => $instances
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get WhatsApp instances: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve WhatsApp instances'
            ], 500);
        }
    }

    /**
     * Delete WhatsApp instance
     */
    public function deleteWhatsappInstance(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'instance_id' => 'required|string'
            ]);

            $deleted = \DB::table('whatsapp_instances')
                ->where('instance_id', $request->instance_id)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp instance not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp instance deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to delete WhatsApp instance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete WhatsApp instance'
            ], 500);
        }
    }

    /**
     * Process incoming WhatsApp messages from WAAPI webhook
     */
    public function processIncomingMessages(\Illuminate\Http\Request $request)
    {
        try {
            // Log the incoming webhook data
            \Log::info('WAAPI Incoming Message Webhook:', $request->all());
            
            // Get the JSON payload
            $payload = $request->all();
            
            // Extract instance information
            $instanceId = $request->segment(4) ?? null;
           
            $accessToken = $request->header('Authorization') ? str_replace('Bearer ', '', $request->header('Authorization')) : null;
            
            if (!$instanceId) {
                \Log::warning('No instance_id in webhook payload');
                return response()->json(['status' => 'error', 'message' => 'No instance_id provided'], 400);
            }
            
            // Find the WhatsApp instance
            $whatsappInstance = \App\Models\WhatsappInstance::where('instance_id', $instanceId)->first();
            
            if (!$whatsappInstance) {
                \Log::warning('WhatsApp instance not found: ' . $instanceId);
                return response()->json(['status' => 'error', 'message' => 'Instance not found'], 404);
            }
            
            // Process messages if they exist in the payload - USE QUEUE for better performance
            if (isset($payload['messages']) && is_array($payload['messages'])) {
                foreach ($payload['messages'] as $messageData) {
                    // Dispatch to queue for processing
                    \App\Jobs\ProcessIncomingMessage::dispatch($messageData, $instanceId, $whatsappInstance)
                        ->onQueue('high_priority');
                }
                
                \Log::info('Dispatched ' . count($payload['messages']) . ' messages to queue for processing', [
                    'instance_id' => $instanceId
                ]);
            }
            
            // Handle different webhook event types immediately (these are lightweight)
            if (isset($payload['event_type'])) {
                $this->handleWebhookEvent($whatsappInstance, $payload);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Messages queued for processing',
                'queued_messages' => isset($payload['messages']) ? count($payload['messages']) : 0
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error processing incoming messages: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 500);
        }
    }

    /**
     * Process a single incoming message
     */
    private function processSingleMessage($whatsappInstance, $messageData)
    {
        try {
            // Extract message information
            $messageId = $messageData['id'] ?? uniqid();
            $chatId = $messageData['chatId'] ?? $messageData['chat_id'] ?? null;
            $fromMe = $messageData['fromMe'] ?? false;
            $messageBody = $messageData['body'] ?? $messageData['message'] ?? '';
            $messageType = $this->determineMessageType($messageData);
            $timestamp = $messageData['timestamp'] ?? time();
            $senderName = $messageData['senderName'] ?? $messageData['sender_name'] ?? null;
            $isGroup = $messageData['isGroup'] ?? false;
            
            // Skip messages from self
            if ($fromMe) {
                return;
            }
            
            // Extract phone number from chatId
            $phoneNumber = $this->extractPhoneFromChatId($chatId);
            
            if (!$phoneNumber) {
                \Log::warning('Could not extract phone number from chatId: ' . $chatId);
                return;
            }
            
            // Check if message already exists
            $existingMessage = \App\Models\IncomingMessage::where('message_id', $messageId)
                ->where('instance_id', $whatsappInstance->instance_id)
                ->first();
                
            if ($existingMessage) {
                return; // Message already processed
            }
            
            // Find or create guest record
            $guest = $this->findOrCreateGuest($whatsappInstance->user_id, $phoneNumber, $senderName);
            
            // Create incoming message record
            $incomingMessage = \App\Models\IncomingMessage::create([
                'user_id' => $whatsappInstance->user_id,
                'instance_id' => $whatsappInstance->instance_id,
                'message_id' => $messageId,
                'events_guest_id' => $guest ? $guest->id : null,
                'chat_id' => $chatId,
                'phone_number' => $phoneNumber,
                'sender_name' => $senderName,
                'message_body' => $messageBody,
                'message_type' => $messageType,
                'media_data' => $this->extractMediaData($messageData),
                'from_me' => $fromMe,
                'is_group' => $isGroup,
                'message_timestamp' => \Carbon\Carbon::createFromTimestamp($timestamp),
                'status' => 'received',
                'metadata' => json_encode($messageData)
            ]);
            
            // Update instance statistics
            $whatsappInstance->incrementMessageCount();
            
            // Process auto-reply if configured
            $this->processAutoReply($whatsappInstance, $incomingMessage);
            
            \Log::info('Processed incoming message', [
                'message_id' => $messageId,
                'phone' => $phoneNumber,
                'instance_id' => $whatsappInstance->instance_id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error processing single message: ' . $e->getMessage(), [
                'message_data' => $messageData,
                'instance_id' => $whatsappInstance->instance_id
            ]);
        }
    }

    /**
     * Find or create a guest record for the phone number
     */
    private function findOrCreateGuest($userId, $phoneNumber, $senderName = null)
    {
        try {
            // Get user's current event
            $user = \App\Models\User::find($userId);
            $userEvent = $user->usersEvents()->orderBy('id', 'desc')->first();
            
            if (!$userEvent) {
                \Log::warning('No event found for user: ' . $userId);
                return null;
            }
            
            $eventId = $userEvent->event_id;
            
            // Clean phone number for consistent matching
            $cleanPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Try to find existing guest
            $guest = \App\Models\EventsGuest::where('event_id', $eventId)
                ->where(function($query) use ($cleanPhone, $phoneNumber) {
                    $query->where('guest_phone', $cleanPhone)
                          ->orWhere('guest_phone', $phoneNumber)
                          ->orWhere('guest_phone', '+' . $cleanPhone);
                })
                ->first();
            
            if ($guest) {
                // Update name if we have a sender name and current name is generic
                if ($senderName && (empty($guest->guest_name) || strpos($guest->guest_name, 'Contact') === 0)) {
                    $guest->update(['guest_name' => $senderName]);
                }
                return $guest;
            }
            
            // Create new guest
            $guestName = $senderName ?: 'WhatsApp Contact';
            
            $newGuest = \App\Models\EventsGuest::create([
                'event_id' => $eventId,
                'guest_name' => $guestName,
                'guest_phone' => $cleanPhone,
                'guest_email' => '',
                'guest_pledge' => 0,
                'event_guest_category_id' => 1, // Default category
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            \Log::info('Created new guest from WhatsApp message', [
                'guest_id' => $newGuest->id,
                'phone' => $cleanPhone,
                'name' => $guestName
            ]);
            
            return $newGuest;
            
        } catch (\Exception $e) {
            \Log::error('Error finding/creating guest: ' . $e->getMessage(), [
                'phone' => $phoneNumber,
                'user_id' => $userId
            ]);
            return null;
        }
    }

    /**
     * Format phone number for consistent storage
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Remove + if present
        $phone = str_replace('+', '', $phone);
        
        // Add country code if not present (assuming +255 for Tanzania)
        if (!str_starts_with($phone, '255') && str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '255') && strlen($phone) >= 9) {
            $phone = '255' . $phone;
        }
        
        return '+' . $phone;
    }

    /**
     * Extract phone number from WhatsApp chat ID
     */
    private function extractPhoneFromChatId($chatId)
    {
        if (!$chatId) return null;
        
        // Remove @c.us, @g.us suffixes
        $phone = str_replace(['@c.us', '@g.us', '@s.whatsapp.net'], '', $chatId);
        
        // Return clean phone number
        return $phone;
    }

    /**
     * Determine message type from message data
     */
    private function determineMessageType($messageData)
    {
        if (isset($messageData['type'])) {
            return $messageData['type'];
        }
        
        // Check for media presence
        if (isset($messageData['media']) || isset($messageData['attachment'])) {
            return 'media';
        }
        
        // Check for location
        if (isset($messageData['location']) || isset($messageData['latitude'])) {
            return 'location';
        }
        
        // Default to text
        return 'text';
    }

    /**
     * Extract media data from message
     */
    private function extractMediaData($messageData)
    {
        $mediaData = [];
        
        if (isset($messageData['media'])) {
            $mediaData = $messageData['media'];
        }
        
        if (isset($messageData['attachment'])) {
            $mediaData['attachment'] = $messageData['attachment'];
        }
        
        if (isset($messageData['caption'])) {
            $mediaData['caption'] = $messageData['caption'];
        }
        
        return !empty($mediaData) ? $mediaData : null;
    }

    /**
     * Process auto-reply for incoming message
     */
    private function processAutoReply($whatsappInstance, $incomingMessage)
    {
        try {
            // Check if this is a command or keyword
            $messageText = strtolower(trim($incomingMessage->message_body));
            
            // Get auto-reply for this keyword
            $autoReply = $this->getAutoReply($whatsappInstance->user_id, $messageText);
            
            if ($autoReply) {
                $this->sendAutoReply($whatsappInstance, $incomingMessage->chat_id, $autoReply);
                $incomingMessage->markAsReplied($autoReply);
            } else {
                $incomingMessage->markAsProcessed();
            }
            
        } catch (\Exception $e) {
            \Log::error('Error processing auto-reply: ' . $e->getMessage());
        }
    }

    /**
     * Get auto-reply message for keyword
     */
    private function getAutoReply($userId, $keyword)
    {
        // Check for specific keywords
        $autoReplies = [
            'hello' => 'Hello! Welcome to our service. How can I help you today?',
            'hi' => 'Hi there! Thank you for contacting us.',
            'help' => 'I can help you with our services. Please let me know what you need.',
            'info' => 'For more information, please visit our website or call our support team.',
            'menu' => 'Here are our available services:\n1. Service A\n2. Service B\n3. Service C\n\nReply with a number to learn more.'
        ];
        
        // Check if keyword matches any auto-reply
        foreach ($autoReplies as $key => $reply) {
            if (strpos($keyword, $key) !== false) {
                return $reply;
            }
        }
        
        // Check database for custom auto-replies
        $customReply = \DB::table('whatsapp_auto_replies')
            ->where('user_id', $userId)
            ->where('keyword', $keyword)
            ->value('reply_message');
            
        return $customReply;
    }

    /**
     * Send auto-reply message
     */
    private function sendAutoReply($whatsappInstance, $chatId, $message)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . ($whatsappInstance->access_token ?? config('app.waapi_token')),
                'Content-Type' => 'application/json'
            ])->post(
                "https://waapi.app/api/v1/instances/{$whatsappInstance->instance_id}/client/action/send-message",
                [
                    'chatId' => $chatId,
                    'message' => $message
                ]
            );
            
            if ($response->successful()) {
                \Log::info('Auto-reply sent successfully', [
                    'chat_id' => $chatId,
                    'instance_id' => $whatsappInstance->instance_id
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to send auto-reply: ' . $e->getMessage());
        }
    }

    /**
     * Handle different webhook event types
     */
    private function handleWebhookEvent($whatsappInstance, $payload)
    {
        $eventType = $payload['event_type'] ?? null;
        
        switch ($eventType) {
            case 'instance_status':
                $this->handleInstanceStatusUpdate($whatsappInstance, $payload);
                break;
                
            case 'message_status':
                $this->handleMessageStatusUpdate($whatsappInstance, $payload);
                break;
                
            default:
                \Log::info('Unhandled webhook event type: ' . $eventType);
        }
    }

    /**
     * Handle instance status updates
     */
    private function handleInstanceStatusUpdate($whatsappInstance, $payload)
    {
        $status = $payload['status'] ?? null;
        
        if ($status) {
            $whatsappInstance->update([
                'connect_status' => $status,
                'last_seen' => now()
            ]);
            
            \Log::info('Updated instance status', [
                'instance_id' => $whatsappInstance->instance_id,
                'status' => $status
            ]);
        }
    }

    /**
     * Handle message status updates (sent, delivered, read)
     */
    private function handleMessageStatusUpdate($whatsappInstance, $payload)
    {
        $messageId = $payload['message_id'] ?? null;
        $status = $payload['status'] ?? null;
        
        if ($messageId && $status) {
            \App\Models\OutgoingMessage::where('waapi_message_id', $messageId)
                ->where('instance_id', $whatsappInstance->instance_id)
                ->update(['status' => $status]);
        }
    }

    /**
     * Fetch messages from WAAPI (for manual sync)
     */
    public function syncMessagesFromWAAPI(\Illuminate\Http\Request $request)
    {
        try {
            $instanceId = $request->input('instance_id');
            $limit = $request->input('limit', 50);
            
            if (!$instanceId) {
                return response()->json(['success' => false, 'message' => 'Instance ID required'], 400);
            }
            
            $whatsappInstance = \App\Models\WhatsappInstance::where('instance_id', $instanceId)->first();
            
            if (!$whatsappInstance) {
                return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
            }
            
            // Call WAAPI to get messages
            $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                'Authorization' => 'Bearer ' . ($whatsappInstance->access_token ?? config('app.waapi_token')),
                'Content-Type' => 'application/json'
            ])->get(
                "https://waapi.app/api/v1/instances/{$instanceId}/client/action/get-messages",
                ['limit' => $limit]
            );
            
            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Failed to fetch messages from WAAPI'], 500);
            }
            
            $data = $response->json();
            $messages = $data['data'] ?? [];
            $processedCount = 0;
            
            foreach ($messages as $messageData) {
                $this->processSingleMessage($whatsappInstance, $messageData);
                $processedCount++;
            }
            
            return response()->json([
                'success' => true,
                'message' => "Synced {$processedCount} messages",
                'processed_count' => $processedCount
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error syncing messages from WAAPI: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync messages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get incoming messages for a specific instance
     */
    public function getIncomingMessages(\Illuminate\Http\Request $request, $instanceId)
    {
        try {
            $whatsappInstance = \App\Models\WhatsappInstance::where('instance_id', $instanceId)->first();
            
            if (!$whatsappInstance) {
                return response()->json(['success' => false, 'message' => 'Instance not found'], 404);
            }
            
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);
            $status = $request->input('status');
            
            $query = \App\Models\IncomingMessage::where('instance_id', $whatsappInstance->id)
                ->with(['guest'])
                ->orderBy('received_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $messages = $query->limit($limit)->offset($offset)->get();
            
            return response()->json([
                'success' => true,
                'messages' => $messages,
                'total' => $query->count()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting incoming messages: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to get messages'], 500);
        }
    }

    /**
     * Mark a message as processed
     */
    public function markMessageAsProcessed(\Illuminate\Http\Request $request, $messageId)
    {
        try {
            $message = \App\Models\IncomingMessage::findOrFail($messageId);
            $message->markAsProcessed();
            
            return response()->json([
                'success' => true,
                'message' => 'Message marked as processed'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error marking message as processed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to update message'], 500);
        }
    }

}
