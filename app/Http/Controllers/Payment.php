<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventsGuest;
use  \App\Models\SmsPayments;
use Auth;
use DB;
use PDF;

class Payment extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //$this->data['payments'] = \App\Models\Payment::whereIn('events_guests_id',EventsGuest::where('event_id', Auth::user()->usersEvents()->where('status', 1)->first()->event_id)->get(['id']))->get();
        $event_id = Auth::user()->usersEvents()->orderBy('id', 'desc')->first()->event_id;
        // $this->data['event_guest_payments'] =  \App\Models\EventsGuest::whereIn('id',\App\Models\Payment::get(['events_guests_id']))->where('event_id',$event_id)->get();
        $this->data['payments'] = \App\Models\Payment::whereIn('events_guests_id', \App\Models\EventsGuest::where('event_id', $event_id)->get(['id']))->get();
        $this->data['guests'] = \App\Models\EventsGuest::whereEventId($event_id)->get();
        return view('payment.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
        $data = $request->all();
        $payment = \App\Models\Payment::create($data);
        
        // $this->sendPaymentSMS($payment->eventsGuest);
        
        return redirect()->back()->with('success', 'success');
    }

    public function sendPaymentSMS($guest) {
        $message = new \App\Http\Controllers\Message();
        $content = request('message');
        $paid_amount = request('amount');
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $datediff = time() - strtotime($user_events->event->date);
        $replacements = array(
            $guest->guest_name, $guest->guest_pledge, $paid_amount, ((float) $guest->payments()->sum('amount') - (float) $guest->guest_pledge), round($datediff / (60 * 60 * 24))
        );
        $sms = $message->getCleanSms($replacements, $content, array(
            '/#name/i', '/#pledge/i', '/#paid_amount/i', '/#balance/i', '/#days_remain/i'
        ));
        $phone = validate_phone_number($guest->guest_phone);
        if (is_array($phone)) {
            $chat_id = validate_phone_number($guest->guest_phone)[1] . '@c.us';
        } else {
            return false;
        }
        $sources = request('channels');
        foreach ($sources as $source) {
            $message->storeMessage($sms, $chat_id, $source);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy() {
        //
        $id = request()->segment(3);
        \App\Models\Payment::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'success');
    }

    
    // Handles reference creation for addons only
    public function createAddonReference($addon = 'whatsapp', $custom_amount = null, $param = null) {
        $addon = request('type', $addon);

        $addons = DB::table('admin_packages')->where('name', $addon)->first();

        if ($custom_amount == null && !empty($addons)) {
            $amount = $addons->price - Auth::user()->discountRequests()->where('status', 1)->count() * 5000;
        } else {
            $amount = $custom_amount == null ? $addons->price : $custom_amount;
        }

        $user_phone = Auth::user()->phone;
        if (!empty(request('phone'))) {
            $existingUser = \App\Models\MessageInstance::where('phone_number', request('phone'))->first();
            if ($existingUser) {
                echo '<div class="alert alert-danger">This phone number is already registered.</div>';
                exit;
            }
        }

        $instance = \App\Models\MessageInstance::create([
            'name' => Auth::user()->name,
            'nida' => Auth::user()->id,
            'user_id' => Auth::id(),
            'phone_number' => request('phone'),
            'status' => 0,
        ]);
        $phone = strlen(request('phone')) < 4 ? str_replace('+', null, validate_phone_number($user_phone)[1]) : str_replace('+', null, validate_phone_number(request('phone'))[1]);
        $order_id = $instance->uuid;
        $order = array(
            "order_id" => $order_id,
            "amount" => (int) $amount > 0 ? $amount : 10000,
            'buyer_name' => Auth::user()->name,
            'buyer_phone' => str_replace('+', null, validate_phone_number($user_phone)[1])
        );

        $this->createOrder($order, $addons->name, (int) $addons->id);
        $this->data['minimal'] = 1;
        $this->data['booking'] = DB::table('admin_bookings')->where('order_id', $order_id)->first();
        if (!empty($this->data['booking'])) {
            $message = 'Hello *' . Auth::user()->name . '*' . chr(10) . chr(10)
                . 'Your *Control Number* for  Payment has been created' . chr(10)
                . 'Use' . chr(10)
                . 'Control Number: *' . $this->data['booking']->token . '*' . chr(10)
                . 'Pay via *Selcom Mastarpass QR* to proceed with ShuleSoft whatsapp Business' . chr(10) . 'Thanks';
            $chat_id = $phone . '@c.us';
            $this->sendMessage($chat_id, $message, 1);
        }
        //check registration if not register
        if (in_array($addons->name, ['whatsapp', 'quick-sms'])) {
            if (DB::table('admin_integration_requests')->where('user_id', Auth::user()->id)->count() != 1) {
                DB::table('admin_integration_requests')->insert([
                    'user_id' => Auth::user()->id,
                    'phone' => $phone
                ]);
            }
        }
        // if (isset($param) && !empty($param)) {
        //     $this->createPromotion(array_merge(['booking_id' => $this->data['booking']->id], $param));
        // }
        return view('payment.pay', $this->data);
    }

    // Handles reference creation for packages by id
    public function createPackageReference($package_id = null, $custom_amount = null, $param = null) {
        $addons = DB::table('admin_packages')->where('id', $package_id)->first();

        if ($custom_amount == null && !empty($addons)) {
            $amount = $addons->price - Auth::user()->discountRequests()->where('status', 1)->count() * 5000;
        } else {
            $amount = $custom_amount == null ? $addons->price : $custom_amount;
        }

        $user_phone = Auth::user()->phone;
      
        $phone = strlen(request('phone')) < 4 ? str_replace('+', null, validate_phone_number($user_phone)[1]) : str_replace('+', null, validate_phone_number(request('phone'))[1]);
        $order_id = time();
        $order = array(
            "order_id" => $order_id,
            "amount" => (int) $amount > 0 ? $amount : 10000,
            'buyer_name' => Auth::user()->name,
            'buyer_phone' => str_replace('+', null, validate_phone_number($user_phone)[1])
        );

        $this->createOrder($order, $addons->name, (int) $addons->id);
        $this->data['minimal'] = 1;
        $this->data['booking'] = DB::table('admin_bookings')->where('order_id', $order_id)->first();
        if (!empty($this->data['booking'])) {
            $message = 'Hello *' . Auth::user()->name . '*' . chr(10) . chr(10)
                . 'Your *Control Number* for  Payment has been created' . chr(10)
                . 'Use' . chr(10)
                . 'Control Number: *' . $this->data['booking']->token . '*' . chr(10)
                . 'Pay via *Selcom Mastarpass QR* to proceed with ShuleSoft whatsapp Business' . chr(10) . 'Thanks';
            $chat_id = $phone . '@c.us';
            $this->sendMessage($chat_id, $message, 1);
        }
        // if (isset($param) && !empty($param)) {
        //     $this->createPromotion(array_merge(['booking_id' => $this->data['booking']->id], $param));
        // }
        return view('payment.pay', $this->data);
    }

    public function createOrder($orders = null, $service = null, $package_id = null) {
        //save data in booking table
        $order = (object) $orders;
        $db = DB::connection('shulesoft');

    // Insert into invoices table
    $invoiceData = [
        'student_id' => isset($order->student_id) ? $order->student_id : null,
        'amount' => isset($order->amount) ? $order->amount : null,
        'order_id' => $order->order_id,
        'schema_name' => 'public', // adjust as needed
        'created_by_sid' => Auth::user()->id,
    ];
    $invoiceId = $db->table('invoices')->insertGetId($invoiceData);

    // Insert into invoice_prefix table
    $prefixData = [
        'invoice_id' => $invoiceId,
        'schema_name' => 'public', // adjust as needed
    ];
    $prefixId = $db->table('invoice_prefix')->insertGetId($prefixData);

    // Get the reference from invoice_prefix table
    $reference = $db->table('invoice_prefix')->where('uid', $prefixId)->value('reference');
        
    $booking = \App\Models\AdminBooking::create([
                    'order_id' => $order->order_id,
                     'amount' => $order->amount, 
                     'reference'=>$reference,
                     'methods' => $service, 
                     'user_id' => Auth::user()->id, 
                     'admin_package_id' => $package_id
        ]);
        //push request to shulesoft api url
        // $request = json_decode($this->curlPaymentApi($orders));

        // //get the booking you need
        // if (is_object($request)) {
        //     $booking->update(['token' => $request->payment_token, 'payment_gateway_url' => $request->payment_gateway_url, 'qr' => $request->qr]);
        // }
        //update booking table with param from api
        return TRUE;
        //save
    }

    public function createPromotion($param) {
        //save in promotion table
        $promotion = \App\Models\Promotion::create($param);
    }

    public function createSMSReference() {
        $sms = request('number_of_sms');
        $order_id = 'diko' . time();
        $amount = 20 * $sms;
        $phone = str_replace('+', null, validate_phone_number(Auth::user()->phone)[1]);

         $prefix = 'SASA96243';
        $index = collect(DB::connection('shulesoft')->select("select shulesoft.get_unique_invoice_index('theresia')"))->first();
        $reference = $prefix . $index->get_unique_invoice_index;


        $client = app(\App\Http\Controllers\Home::class)->getClient();
        $schema_name = strtolower(str_replace(' ', '', trim($client->username)));

     $data = [
            'schema_name' => $schema_name,
            'reference' => $reference,
            'amount' => $amount,
            'client_id' => $client->id,
            'date' => date('Y-m-d'),
            'created_at' => now(),
            'addon_id' => 2, //default addon id for dikodiko bulk sms
            'prefix' => $prefix,
        ];

    

        $this->data['minimal'] = 1;
        // Check if a booking already exists for this user, package, and amount
        $existingBooking = \App\Models\AdminBooking::where([
            ['user_id', '=', Auth::user()->id],
            ['admin_package_id', '=',5],
            ['amount', '=', $amount],
            ['status', '=', 0]
        ])->first();

        if (!$existingBooking) {
            $this->data['booking'] = \App\Models\AdminBooking::create([
            'order_id' => time(),
            'amount' => $amount,
            'reference' => $reference,
            'methods' => 'online',
            'user_id' => Auth::user()->id,
            'admin_package_id' => 5,
            'status' => 0,
            ]);

            $user = Auth::user();
   
            // Send message to the user
            $userMessage = "Hello {$user->name},\n\nYour invoice for Bulk SMS has been created successfully. Reference Number: *{$reference}*.\nThank you for using DikoDiko.";
            $this->sendMessage($user->phone, $userMessage, 'whatsapp');

            // Send message to admin
            $adminMessage = "New BULK SMS invoice created for user: {$user->name} (ID: {$user->id}, Phone: {$user->phone}). Reference Number: *{$reference}*, Amount: " . request('amount') . ".";
            $this->notifyAdmin($adminMessage);

        } else {
            $this->data['booking'] = $existingBooking;
        }

        return view('payment.pay', $this->data);
    }



    public function verifyPayment() {
        $transaction_id = request('transaction_id');
        $booking_id = request('booking_id');
        $book = \App\Models\AdminPayment::where('transaction_id', $transaction_id)
            ->where('user_id', Auth::user()->id)
            ->whereIn('admin_booking_id', \App\Models\AdminBooking::where('order_id', request('booking_id'))->get(['id']));
        $valid = $book->first();
        if (!empty($valid)) {
            \App\Models\AdminPayment::create([
                'user_id' => Auth::user()->id,
                'admin_booking_id' =>request('booking_id'),
                'transaction_id' => $transaction_id,
                'amount' => $valid->amount ?? 0
            ]);
            return response()->json(['status' => 'success','message' => 'Payment has been reflected successfully']);
        }
        $this->notifyAdmin("Client " . Auth::user()->name . " with phone " . Auth::user()->phone . " has requested for payment verification for transaction id: " . $transaction_id. "kindly check the payment gateway or Bank statement for more details");
        return response()->json(['status' => 'fail', 'message' => 'Error: Payment not yet reflected, kindly wait within 10 minutes']);}

    public function cancelPayment() {

        $order_id = request()->segment(3);
        $book = \App\Models\AdminBooking::where('order_id', $order_id);
        $valid = $book->first();
        if (!empty($valid)) {
            $order = (object) array("order_id" => $order_id, 'action' => 'cancel', 'source' => 'dikodiko');
            $this->curlPaymentApi($order);
            $book->delete();
        }
        return redirect()->back()->with('success', 'success');
    }

    public function card() {
        $payment_id = request()->segment(3);
        $id = request()->segment(4);
        if ($id == 'printall') {
            $this->data['payment'] = \App\Models\Payment::find($payment_id);
            return view('certificate.printall', $this->data);
        } else {
            return $this->createCard($payment_id);
        }

        // return view('payment.card', $this->data);
    }

    function createCard($id) {

        $pdf = new \setasign\Fpdi\Fpdi('L');

        // add a page
        $pdf->AddPage();

        // set the source file to doc1.pdf and import a page
        //$file_name = request('type') == 100 ? '2019_certificate.pdf' : '2018_certificate.pdf';
        $file_name = 'card.pdf';
        $pdf->setSourceFile("storage/uploads/" . $file_name);
        $tplIdx = $pdf->importPage(1);
        // use the imported page and place it at point 5,1 with a width of 283 mm, 200mm height
        $pdf->useTemplate($tplIdx, 5, 1, 283, 210);
        // set the source file to doc2.pdf and import a page
        $this->getName($id);
        $user_name = 'storage/uploads/mycard' . $id . '.pdf';
        if (!file_exists($user_name)) {
            $handle = fopen($user_name, '+r');
            fclose($handle);
        }

        $pdf->setSourceFile($user_name);
        $tplIdx = $pdf->importPage(1);
        // use the imported page and place it at point 95,98 with a width of 210 mm
        $pdf->useTemplate($tplIdx, 90, 94, 210, 90);

        $pdf->Output();
    }

    function getName($id) {

        $payment = \App\Models\Payment::find($id);
        return PDF::loadHTML('<h1 style="font-size:120px">' . $payment->eventsGuest->guest_name . '</h1>')
                        ->setPaper('a4', 'landscape')
                        ->setOptions(['dpi' => 250, 'defaultFont' => 'sans-serif'])
                        ->setWarnings(false)
                        ->save('storage/app/new_myfile' . $id . '.pdf');
    }

    /**
     * 
     * @param type $payment_id
     * @access : Via kernel background operation
     */
    function sendCertificates() {
        $users = User::whereNull('role_id')->whereIn('user_type_id', [7, 9])->get();
        foreach ($users as $user) {
            if ($user->userType->id == 9 && $user->payment()->count() == 0) {
                continue;
            }
            // $att = $user->attendance()->first(); commented for 2019 only
            //if (count($att) == 1) {

            $id = $user->id;
            $content = 'Please Click the link below to download/print your certificate'
                    . '<br/>'
                    . '<br/>'
                    . '<a href="https://engineersday.co.tz/certificate/' . $id . '?type=100&auth=' . encrypt($id) . '" style="display: inline-block; margin-bottom: 0; font-weight: 40px; text-align: center;
    vertical-align: middle; cursor: pointer; background-image: none; border: 1px solid transparent; white-space: nowrap; padding: 12px 24px; font-size: 14px; line-height: 1.428571429; border-radius: 4px;color: #fff; background-color: #5cb85c; border-color: #4cae4c;">Event Certificate</a>';
            $this->send_email($user->email, 'AED 2019- Certificate of Attendance', $content);
            /// }
        }
        return redirect()->back()->with('success', 'Success');
    }

    public function createDiscount() {
        $phone = request('phone');
        $valid_phone_number = validate_phone_number($phone);
        if (empty($valid_phone_number)) {
            die('<span class="alert alert-danger">Invalid Phone Number, kindly provide a valid number</span>');
        }
        $phone_number = $valid_phone_number[1];
        $find_user = \App\Models\User::where('phone', $phone_number)->first();
        if (!empty($find_user)) {
            die('<span class="alert alert-danger">This user already has an account in DikoDiko, kindly try another user</span>');
        }
        $invited = \App\Models\DiscountRequest::where('phone', $phone_number)->where('type', 1)->first();
        if (!empty($invited)) {
            die('<span class="alert alert-danger">This user is already invited by other event owner, kindly try another user</span>');
        }
        \App\Models\DiscountRequest::create(['phone' => $phone_number, 'user_id' => Auth::user()->id]);
        //send message
        $message = preg_match('/tanzania/i', $valid_phone_number[0]) ?
                'Habari,'
                . 'Umealikwa na  *' . Auth::user()->name . '*  ujiunge  kwenye program ya DikoDiko (www.dikodiko.co.tz) ili uweze kusimamia sherehe yako kwa urahisi'
                . ''
                . 'Ikiwa humfahamu *' . Auth::user()->name . '* au hauna sherehe yoyote (harusi, birthday nk), tafadhali andika neno HAPANA na utume kwenye namba'
                . ''
                . 'Asante' :
                'Hello '
                . 'You have been invited by  *' . Auth::user()->name . '* to join DikoDiko (www.dikodiko.co.tz) to manage your event easily'
                . ''
                . 'If you do not know  *' . Auth::user()->name . '* or you don not have any event (wedding, sendoff, birthday etc), kindly reply with a word NO and we will delete this request'
                . ''
                . 'Thank you';

        $chat_id = $phone_number . '@c.us';
        $this->sendMessage($chat_id, $message, 1);
        die('<span class="alert alert-success">Success: user invite have been sent successfully. You will get notified on the status</span>');
    }

    public function paymentTransactions(){
        $package_id=request()->segment(3);
        $this->data['payments'] = \App\Models\AdminBooking::where('user_id', Auth::user()->id)
        ->where('admin_package_id',$package_id)->orderBy('created_at', 'desc')
        ->paginate(10);
        $this->data['title'] = 'Payment Transactions';
        return view('payment.transactions', $this->data);
    }

    public function processPayment()
{      
    DB::table('messagelogs')->insert([
        'message' => json_encode(request()->all()),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $payment = new SmsPayments();

    if(strtoupper(request('from')) != 'CRDB BANK') {
        return response()->json(['success' => true, 'message' => 'Not a payment message.'], 400);
    }

    $message = request('content');

    // Extract amount (TZS 1000 or TZS 10.0)
    preg_match('/TZS\s*([\d,\.]+)/i', $message, $amountMatch);
    $amount = isset($amountMatch[1]) ? str_replace(',', '', $amountMatch[1]) : null;

    // Extract token from REF or Ref:
    preg_match('/REF[:\s]*([0-9-a-z]+)/i', $message, $tokenMatch);
    if (empty($tokenMatch[1])) {
        // Try alternative format (space after REF:)
        preg_match('/REF[:\s]*([0-9a-z]+)/i', $message, $tokenMatch);
    }
    $token = isset($tokenMatch[1]) ? $tokenMatch[1] : null;

    // Extract date (YYYY-MM-DD HH:MM:SS)
    preg_match('/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $message, $dateMatch);
    $paymentDate = isset($dateMatch[1]) ? $dateMatch[1] : null;

    // Extract payer name (after "From:" or "Kutoka:")
    if (preg_match('/From:\s*([A-Z\s\.]+)/i', $message, $payerMatch)) {
        $payer = trim($payerMatch[1]);
    } elseif (preg_match('/Kutoka:\s*([A-Z\s\.]+)/iu', $message, $payerMatch)) {
        $payer = trim($payerMatch[1]);
    } else {
        $payer = null;
    }

    //if amount is null or 0, skip the request
    if (is_null($amount) || $amount <= 0) {
        return response()->json(['success' => false, 'message' => 'Invalid amount.'], 400);
    }
    $payment->amount = $amount;
    $payment->method = null; // Not available in message
    $payment->token = $token;
    $payment->status = 0; // Assuming 0 means pending
    $payment->phone_number = null; // Not available in message
    $payment->message = $message;
    $payment->payer = $payer;
    $payment->payment_date = $paymentDate;

    //check first if the payment already exists
    $existingPayment = SmsPayment::where('token', $token)
        ->where('status', 0) // Assuming 0 means pending
        ->first();

    if ($existingPayment) {
        return response()->json(['success' => false, 'message' => 'Payment already exists.'], 409);
    }

    if ($payment->save()) {
        // Generate a unique confirmation code
        $confirmationCode = strtoupper(uniqid('CODE_'));
        //$payment->code = $confirmationCode;
        $payment->save();

        return response()->json(['success' => true, 'code' => $confirmationCode]);
    }

    return response()->json(['success' => false, 'message' => 'Payment processing failed.'], 500);
}
    /**
     * Verify monthly subscription payment
     */
    public function verify(Request $request)
    {
        $request->validate([
            'reference_number' => 'required|string|max:255|min:5',
            'amount_paid' => 'required|numeric|min:1|max:1000000'
        ]);

        $userId = Auth::user()->id;
        $referenceNumber = trim($request->reference_number);
        $amountPaid = (float) $request->amount_paid;
        $monthlyFee = 50000; // TSH 50,000 per month

        try {
            $checkexistingPayment = \App\Models\AdminPayment::where('transaction_id', $referenceNumber)->first();
            if (!$checkexistingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This transaction ID is not valid or has not been used received by us.'
                ]);
            }

            // Check if reference number already exists
            $existingPayment = \App\Models\AdminPayment::where('transaction_id', $referenceNumber)->where('status',1)->first();
            if ($existingPayment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This reference number has already been used for another payment.'
                ]);
            }

            // Check if amount is sufficient
            if ($amountPaid < $monthlyFee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount (TSH ' . number_format($amountPaid) . ') is less than required monthly fee (TSH ' . number_format($monthlyFee) . '). Please pay the full amount to activate your subscription.'
                ]);
            }

            // Calculate months covered by payment
            $monthsCovered = floor($amountPaid / $monthlyFee);
            $excessAmount = $amountPaid % $monthlyFee;

            // Get current active subscription end date
            $currentSubscription = \App\Models\AdminPayment::where('user_id', $userId)
                ->where('subscription_end', '>=', now())
                ->orderBy('subscription_end', 'desc')
                ->first();

            // Calculate subscription start and end dates
            $startDate = $currentSubscription ? 
                \Carbon\Carbon::parse($currentSubscription->subscription_end) : 
                now();

            $endDate = \Carbon\Carbon::parse($startDate)->addMonths($monthsCovered);

            // Create payment record
            $payment = \App\Models\AdminPayment::create([
                'user_id' => $userId,
                'amount' => $amountPaid,
                'transaction_id' => $referenceNumber,
                'method' => 'LIPA_NAMBA',
                'date' => now(),
                'subscription_start' => $startDate,
                'subscription_end' => $endDate,
                'months_covered' => $monthsCovered,
                'excess_amount' => $excessAmount
            ]);

            $message = 'Payment verified successfully! ';
            $message .= 'Your subscription is now active for ' . $monthsCovered . ' month(s) until ' . $endDate->format('d M Y') . '.';
            
            if ($excessAmount > 0) {
                $message .= ' Excess amount of TSH ' . number_format($excessAmount) . ' will be credited for future renewals.';
            }

            // Log the successful payment
            \Log::info('Subscription payment verified', [
                'user_id' => $userId,
                'amount' => $amountPaid,
                'reference' => $referenceNumber,
                'months_covered' => $monthsCovered,
                'subscription_end' => $endDate
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'subscription_end' => $endDate->format('Y-m-d H:i:s'),
                'months_covered' => $monthsCovered
            ]);

        } catch (\Exception $e) {
            \Log::error('Payment verification error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'reference_number' => $referenceNumber,
                'amount_paid' => $amountPaid
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please try again or contact support.'
            ]);
        }
    }

    /**
     * Show subscription status
     */
    public function subscriptionStatus()
    {
        $userId = Auth::user()->id;
        $activeSubscription = getPackage();
        $isTrialActive = is_trial();
        
        $this->data['user'] = Auth::user();
        $this->data['active_subscription'] = $activeSubscription;
        $this->data['is_trial'] = $isTrialActive;
        $this->data['trial_days_left'] = 0;
        
        if ($isTrialActive) {
            $trialStart = Auth::user()->created_at;
            $trialDays = config('app.TRIAL_DAYS', 3);
            $daysUsed = now()->diffInDays($trialStart);
            $this->data['trial_days_left'] = max(0, $trialDays - $daysUsed);
        }
        
        $this->data['recent_payments'] = \App\Models\AdminPayment::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        return view('payment.subscription_status', $this->data);
    }
}
