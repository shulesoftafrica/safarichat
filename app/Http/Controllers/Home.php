<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventsGuest;
use \App\Models\Payment;
use \App\Models\Budget;
use \App\Models\BudgetPayment;
use \App\Models\User;
use Auth;
use DB;

class Home extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->data['epayment_enabled'] = $this->epayment_enabled;
    }

    private $HEADER = array(
        'application/x-www-form-urlencoded'
    );
    private $URL = 'http://51.91.251.252:8081/api';

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
       
          // Check if user has no active whatsapp instance
            $hasWhatsappInstance = \App\Models\MessageInstance::where('user_id', Auth::id())
            ->where('type', 'whatsapp')
            ->where('status', 1)
            ->exists();

            if (!$hasWhatsappInstance) {
                  $this->data['ward'] = Auth::user()->business;
                $this->data['event'] = [];
               
                return view('auth.business.setup', $this->data);
            }
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        if (!$user_events) {
            // Create a dummy event
            $event = \App\Models\Event::create([
                'name' => 'Default Event',
                'event_type_id' => 1,
                'date' => now(),
                'district_id' => 1
            ]);
            $user_events = \App\Models\UsersEvent::create([
                'user_id' => Auth::user()->id,
                'event_id' => $event->id
            ]);
        }
        $event_id = $user_events->event_id;

        // WhatsApp-based metrics using the new tables
        $user_id = Auth::id();
        
        // Total WhatsApp contacts (guests)
        $this->data['guests'] = EventsGuest::whereEventId($event_id)->count();
        
        // Active conversations (users who have received/sent messages in last 30 days)
        $this->data['active_conversations'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->where('received_at', '>=', now()->subDays(30))
            ->distinct('phone_number')
            ->count();
            
        // Messages sent today
        $this->data['messages_sent_today'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->whereDate('created_at', today())
            ->count();
            
        // Response rate (incoming messages vs outgoing messages ratio)
        $outgoing_count = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $incoming_count = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->where('received_at', '>=', now()->subDays(7))
            ->count();
        $this->data['response_rate'] = $outgoing_count > 0 ? round(($incoming_count / $outgoing_count) * 100, 1) : 0;
        
        // Chart data - messages over time (last 12 months)
        $this->data['reports'] = DB::select("
            SELECT 
                COUNT(*) as sum, 
                TO_CHAR(created_at, 'MM-YYYY') as month_date 
            FROM outgoing_messages 
            WHERE user_id = ? 
                AND created_at >= ? 
            GROUP BY TO_CHAR(created_at, 'MM-YYYY'), DATE_TRUNC('month', created_at)
            ORDER BY DATE_TRUNC('month', created_at) ASC 
            LIMIT 12
        ", [$user_id, now()->subMonths(12)]);
        
        // If no message data, fall back to guest data
        if (empty($this->data['reports'])) {
            $this->data['reports'] = DB::select("
                SELECT 
                    count(*) as sum, 
                    extract(month from created_at)||'-'||extract(year from created_at) as month_date 
                FROM events_guests 
                WHERE event_id = ? 
                GROUP BY month_date 
                ORDER BY month_date ASC
            ", [$event_id]);
        }

        // Budget and expenses remain the same
        $this->data['total_budget'] = Budget::where('event_id', $event_id)->sum('initial_price');
        $this->data['total_expenses'] = BudgetPayment::whereIn('budget_id', Budget::where('event_id', $event_id)->get(['id']))->sum('amount');

        // Recent activity data for WhatsApp
        $this->data['recent_messages'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->with('guest')
            ->orderBy('received_at', 'desc')
            ->limit(5)
            ->get();
            
        $this->data['whatsapp_instances'] = \App\Models\WhatsappInstance::where('user_id', $user_id)->get();
        
        return view('home', $this->data);
    }

    public function profile()
    {
        $this->data['guests'] = EventsGuest::count();
        $this->data['total_pledge'] = EventsGuest::sum('guest_pledge');
        $this->data['total_payments'] = Payment::sum('amount');
        $this->data['total_Budget'] = Budget::sum('initial_price');
        exit;
        return view('auth.profile', $this->data);
    }

    public function upgrade()
    {
        $this->data['packages'] = \App\Models\AdminPackage::whereIsAddon(0)->get();
        $this->data['addon_id'] = request()->segment(3);
        if (!in_array($this->data['addon_id'], [2, 4])) {
            redirect()->back()->with('error', 'Invalid Package');
        }
        if ($_POST) {
            $package_id = request()->segment(4);
            $this->data['booking'] = \App\Models\AdminBooking::where('admin_package_id', $package_id)->where('user_id', Auth::user()->id)->whereNotIn('id', \App\Models\AdminPayment::where('user_id', Auth::user()->id)->get(['admin_booking_id']))->first();
            $this->data['package'] = \App\Models\AdminPackage::find($package_id);
            if (empty($this->data['booking'])) {

                $this->data['booking'] =  \App\Models\AdminBooking::create([
                    'order_id' => time(),
                    'amount' =>  $this->data['package']->price,
                    'reference' => substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 8),
                    'methods' => 'online',
                    'user_id' => Auth::user()->id,
                    'admin_package_id' => $package_id
                ]);
            }

            return view('payment.fullpaymentpage', $this->data);
        }
        return view('auth.upgrade', $this->data);
    }

    public function payment() {}

    public function verify()
    {
        $user = \App\Models\User::whereId(Auth::user()->id)->where('verify_code', trim(request('code')))->first();
        if (!empty($user)) {
            \App\Models\User::find(Auth::user()->id)->update(['verified' => 1, 'email_verified_at' => now()->endOfDay()]);
            echo 'success';
        } else {
            echo 'error';
        }
    }

    public function resend()
    {
        $code = rand(192, 9999) . substr(str_shuffle('abcdefghkmnpqrst'), 0, 3);
        DB::table('users')->where('id', Auth::user()->id)->update(['verify_code' => $code]);

        $body = 'Your Verification Code is ' . $code;
        $message = new \App\Http\Controllers\Message();
        if (request('tag') == 'email') {
            $sms = [
                'body' => $body,
                'name' => Auth::user()->name,
                'subject' => 'DikoDiko Verification Code'
            ];
            $message->sendCustomEmail(Auth::user()->email, (object) $sms);
            echo 'success';
        }
        if (request('tag') == 'whatsapp') {
            $chat_id = validate_phone_number(Auth::user()->phone)[1] . '@c.us';
            $this->sendMessage($chat_id, $body, 1);
            echo 'success';
        }
    }

    public function createEvent()
    {

        $this->validate(request(), [
            // 'name' => 'required|max:255',
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\']+$/', // Only allow letters, spaces, hyphens and apostrophes
                function ($attribute, $value, $fail) {
                    // Additional sanitization
                    $sanitized = strip_tags(trim($value));
                    if ($value !== $sanitized) {
                        $fail('The '.$attribute.' field contains invalid characters.');
                    }
                    // Check for potential XSS
                    if (preg_match('/<[^>]*>/', $value)) {
                        $fail('The '.$attribute.' field contains HTML tags.');
                    }
                },
            ],
            "district_id" => "required|min:1",
            "date" => "date|required",
            'event_type_id' => 'required|integer'
        ]);
        $request = request([
            'name',
            'event_type_id',
            'date',
            'district_id',
            'location'
        ]);
        $event = \App\Models\Event::firstOrCreate($request);
        
        $user_events = \App\Models\UsersEvent::firstOrCreate([
            'user_id' => Auth::user()->id,
            'event_id' => $event->id
        ]);
        $message_instances = \App\Models\MessageInstance::firstOrCreate([
            'name' => Auth::user()->name,
            'nida' => Auth::user()->id,
            'user_id' => Auth::id(),
            'phone_number' => Auth::user()->phone,
            'status' => 0,
            'type' => 'whatsapp'
        ]);
        if (in_array((int) $event->event_type_id, [1, 3])) {
            //register partner account and send notifications to the partners
            $phone = validate_phone_number(request('partner_phone'))[1];
            $user_info = \App\Models\User::whereEmail(request('partner_name') . '@dikodiko.com')->wherePhone($phone)->first();
            $user = empty($user_info) ? \App\Models\User::create([
                'name' => request('partner_name'),
                'email' => request('partner_name') . '@dikodiko.com',
                'password' => bcrypt(rand(45555, 999989)),
                'phone' => $phone,
                'event_type_id' > 1
            ]) : $user_info;
            $user_events = \App\Models\UsersEvent::firstOrCreate([
                'user_id' => $user->id,
                'event_id' => $event->id
            ]);

            $instance = \App\Models\MessageInstance::create([
                'name' => Auth::user()->name,
                'nida' => Auth::user()->id,
                'user_id' => Auth::id(),
                'phone_number' => Auth::user()->phone,
                'status' => 0,
                'type' => 'whatsapp'
            ]);
        }
        return redirect()->back()->with('success', 'success');
    }

    public function settings()
    {

        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $this->data['event'] = $user_events->event;
        $this->data['user_accounts'] = \App\Models\UsersEvent::whereEventId($user_events->event_id)->get();
        if ($_POST) {
            $table = request('table');
            switch ($table) {
                case 'user':
                    \App\Models\User::findOrFail(Auth::user()->id)->update(request()->all());

                    break;
                case 'event_guest_category':
                    if ((int) request('edit') > 0) {
                        \App\Models\EventGuestCategory::whereId(request('edit'))->whereEventId($user_events->event_id)->update(['name' => request('name')]);
                    } else {
                        \App\Models\EventGuestCategory::firstOrCreate(['name' => request('name'), 'event_id' => $user_events->event_id]);
                    }
                    break;
                case 'event':
                    \App\Models\Event::findOrFail($user_events->event_id)->update(request()->all());
                    break;
                default:
                    break;
            }
            return redirect()->back()->with('success', 'success');
        }

        $this->data['categories'] = \App\Models\EventGuestCategory::whereEventId($user_events->event_id)->get();
        return view('auth.settings', $this->data);
    }

    public function support()
    {
        // Handle support ticket creation
        if (request()->isMethod('post') && request()->has(['topic', 'details'])) {
            \App\Models\AdminSupport::firstOrCreate([
                'user_id' => Auth::user()->id,
                'topic' => strip_tags(request('topic')),
                'details' => strip_tags(request('details'))
            ]);
            
            return redirect()->back()->with('success', 'Support ticket created successfully! Our team will get back to you soon.');
        }
        
        // Show support documentation page
        $this->data['user'] = Auth::user();
        
        // Get user's WhatsApp setup status for contextual help
        $whatsappInstance = Auth::user()->whatsappInstance();
       
        $this->data['has_whatsapp'] = !empty($whatsappInstance);
        $this->data['whatsapp_connected'] = $whatsappInstance && $whatsappInstance->connect_status == 'ready';
        
        // Get basic stats for help context
        $this->data['total_contacts'] = \App\Models\EventsGuest::whereIn('event_id', 
            Auth::user()->usersEvents->pluck('event_id'))->count();
        $this->data['messages_sent'] = \App\Models\OutgoingMessage::where('user_id', Auth::id())->count();
        $this->data['has_sent_messages'] = $this->data['messages_sent'] > 0;
        
        return view('support.index', $this->data);
    }

    public function addUser()
    {
        \App\Models\User::findOrCreate(request()->all());
        return redirect()->back()->with('success', 'success');
    }

    public function sendEventCode()
    {
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        $guests = \App\Models\EventsGuest::where('event_id', $event_id)->get();
        foreach ($guests as $value) {
            if (strlen($value->code) < 3) {
                $verify_code = rand(192, 9999) . substr(str_shuffle('abcdefghkmnpqrst'), 0, 4);
                $value->update(['code' => $verify_code]);
            } else {
                $verify_code = $value->code;
            }
            $message = 'Hello ' . $value->guest_name . ', unaweza shiriki ya ' . $user_events->event->name . ' LIVE (mubashara). Ingia kupitia hii link (www.dikodiko.co.tz/live),   Event Code yako ni ' . $verify_code;

            $messages = \App\Models\Message::firstOrCreate([
                'body' => $message,
                'user_id' => Auth::user()->id,
                'phone' => str_replace('@c.us', NULL, $value->guest_phone)
            ]);
            \App\Models\MessageSentby::create(['message_id' => $messages->id, 'channel' => 'phone-sms']);
        }
        echo "success";
    }

    public function createBusiness()
    {


        $this->validate(request(), [
            'address' => 'required|max:255',
            "descriptions" => "required|min:5",
            'legal_document' => 'required'
        ]);
        \App\Models\Business::where('id', Auth::user()->business->id)->update(request()->except('_token'));
        return redirect()->back()->with('success', 'success');
    }

    public function registerBusinessService()
    {

        $service_id = request('service_id');
        $name = request('name');
        $valid_phone = validate_phone_number(request('phone'));
        $phone = $valid_phone[1];
        $is_new = 0;
        //check if business exists
        $business = \App\Models\Business::where('phone', 'ilike', $phone)->first();
        if (empty($business)) {
            $business = \App\Models\Business::create([
                'name' => $name,
                'user_id' => Auth::user()->id,
                'address' => request('address'),
                'phone' => $phone,
                'status' => 0
            ]);
            $is_new = 1;
        }
        $business_service = \App\Models\BusinessService::where('business_id', $business->id)->first();
        if (empty($business_service)) {
            $business_service = \App\Models\BusinessService::create([
                'business_id' => $business->id,
                'service_id' => $service_id,
                'service_name' => $name
            ]);
        }
        //now send messages to this busienss
        if ($is_new == 1) {
            $message = preg_match('/tanzania/i', $valid_phone[0]) ?
                'Habari ' . $name . ''
                . 'Biashara yako imesajiliwa kwenye program ya DikoDiko (www.dikodiko.co.tz) kama mtoa huduma ya  ' . $business_service->service->name . '. '
                . ''
                . 'Ili watu wengine wenye sherehe waweze kukufahamu, kamilisha usajili wako kwa kuingia www.dikodiko.co.tz kisha jisajili kama biashara (ni bure)'
                . ''
                . 'Ikiwa hutoi huduma hii na umesajiliwa kimakosa, tafadhali wasiliana na *' . Auth::user()->name . '*'
                . ''
                . 'Kama unataka kujua zaidi DikoDiko ni nini, basi wasiliana nasi kupitia *255734952586*'
                . ''
                . 'Asante' :
                'Hello ' . $name . ''
                . 'Your business has been registered in  DikoDiko (www.dikodiko.co.tz) as a business that provide  ' . $business_service->service->name . ' service. '
                . ''
                . 'For other event owners to know and see your business, kindly proceed and finish your registration via www.dikodiko.co.tz and register as a business ( Its Free)'
                . ''
                . 'If you do not provide such service and your number has been registered wrongly, kindly contact event owner via *' . Auth::user()->name . '*'
                . ''
                . 'If you want to learn more about DikoDiko kindly call us via whatsapp no:  *255734952586*'
                . ''
                . 'Thank you';

            $chat_id = $phone . '@c.us';
            $this->sendMessage($chat_id, $message, 1);
            //create a discount
            // $this->createBusinessDiscount($phone);
        }
        return redirect()->back()->with('success', 'success');
    }

    public function createBusinessDiscount($phone)
    {
        $valid_phone_number = validate_phone_number($phone);
        if (empty($valid_phone_number)) {
            die('<span class="alert alert-danger">Invalid Phone Number, kindly provide a valid number</span>');
        }
        $phone_number = $valid_phone_number[1];
        $find_user = \App\Models\Business::where('phone', $phone_number)->where('status', 1)->first();
        if (!empty($find_user)) {
            die('<span class="alert alert-danger">This business already has an account in DikoDiko, discount will not be applied</span>');
        }
        $invited = \App\Models\DiscountRequest::where('phone', $phone_number)->where('type', 2)->first();
        if (!empty($invited)) {
            die('<span class="alert alert-danger">This user is already invited by other event owner,  discount will not be applied</span>');
        }
        \App\Models\DiscountRequest::create(['phone' => $phone_number, 'user_id' => Auth::user()->id, 'type' => 2]);
        //send message
        $message = preg_match('/tanzania/i', $valid_phone_number[0]) ?
            'Habari,'
            . 'Umealikwa na  *' . Auth::user()->name . '*  ujiunge  kwenye program ya DikoDiko (www.dikodiko.co.tz) ili uweze kupata wateja wengi zaidi wenye harusi na shughuli mbalimbali'
            . ' '
            . ''
            . 'Ikiwa hutoi huduma hii na umesajiliwa kimakosa, tafadhali usifanye chochote au wasiliana na ' . Auth::user()->name . ' kwa ' . Auth::user()->phone
            . ''
            . 'Asante' :
            'Hello '
            . 'You have been invited by  *' . Auth::user()->name . '* to join DikoDiko (www.dikodiko.co.tz) to get  customers who have weddings and other similar events'
            . ''
            . 'If you do not provide any service for weddings or local events, kindly ignore this message or contact   *' . Auth::user()->name . '* with ' . Auth::user()->phone . ' '
            . ''
            . 'Thank you';

        $chat_id = $phone_number . '@c.us';
        $this->sendMessage($chat_id, $message, 'whatsapp');
        die('<span class="alert alert-success">Success: Business invite have been sent successfully. You will get notified on the status</span>');
    }

    public function payments()
    {

        $this->data['payments'] = \App\Models\AdminPayment::all();
        $this->data['users'] = User::all();
        if ($_POST) {
            $data = request()->all();
            \App\Models\AdminPayment::create($data);

            $user = User::find(request('user_id'));
            $message = 'Hello ' . $user->name . chr(10) . chr(10) .
                'Your payment of Tsh :' . request('amount') . chr(10) .
                'with reference number  : *' . request('transaction_id') . '* ' . chr(10) . chr(10) .
                'has been received successfully. You can now enjoy using DikoDiko to give your event a meaning you dream';
            $chatId = $user->phone . '@c.us';

            $this->sendMessage($user->phone, $message, 'whatsapp');
            return redirect()->back()->with('success', 'success');
        }
        return view('payment.admin', $this->data);
    }

    public function paymentdestroy()
    {
        $id = request()->segment(3);
        \App\Models\AdminPayment::find($id)->delete();
        return redirect()->back()->with('success', 'success');
    }

    public function getClient(){
                // Check if the user is already registered as a client
        $client = DB::connection('shulesoft')->table('admin.clients')
            ->select('id', 'username')
            ->where('username', Auth::user()->uuid)
            ->first();

        // If not found, insert the user as a new client
        if (!$client) {
            $user = Auth::user();
            $clientId = DB::connection('shulesoft')->table('admin.clients')->insertGetId([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->uuid,
            'project_id' =>6,
            'status' => 1,
            'user_id' => $user->id,
            ]);
            $client = DB::connection('shulesoft')->table('admin.clients')
            ->select('id', 'username')
            ->where('username', Auth::user()->uuid)
            ->first();
        }
        return $client;
    }

    public function createAddonInvoice()
    {
        // $packages = \App\Models\AdminPackage::whereIsAddon(0)->get();
      
        // $this->data['addon_id'] = $addon_id = request()->segment(3);
        // if (!in_array($this->data['addon_id'], [2, 4])) {
        //     redirect()->back()->with('error', 'Invalid Package');
        // }
       
        $prefix = 'SASA96243';
        $index = collect(DB::connection('shulesoft')->select("select shulesoft.get_unique_invoice_index('theresia')"))->first();
        $reference = $prefix . $index->get_unique_invoice_index;


        $client = $this->getClient();

        $schema_name = strtolower(str_replace(' ', '', trim($client->username)));

        $data = [
            'schema_name' => $schema_name,
            'reference' => $reference,
            'amount' => request('amount'),
            'client_id' => $client->id,
            'date' => date('Y-m-d'),
            'created_at' => now(),
            'addon_id' => 7, //default addon id for dikodiko
            'prefix' => $prefix,
        ];

        // Check if the invoice already exists
        $existingInvoice = DB::connection('shulesoft')->table('admin.addon_invoices')
            ->where('schema_name', $data['schema_name'])
            ->where('addon_id', $data['addon_id'])
            ->where('amount', $data['amount'])
              ->where('status', 0) // Assuming status 0 means unpaid
            ->where('client_id', $data['client_id'])
            ->first();

        if (!$existingInvoice) {
            DB::connection('shulesoft')->table('admin.addon_invoices')->insert($data);
        }


        $request = request()->segment(3);


        // $payment = DB::connection('shulesoft')->table('admin.addon_invoices')->where('client_id', $client->id)->first();
      
        // $data1 = [
        //    'invoice_id' => $payment->id,
        //     'amount' => $payment->amount,
        //     'transaction_id' => $payment->reference,
        //     'note' =>'dikodiko addon payment',
        //     'phone' => Auth::user()->phone,
        //     'created_at' => now(),
        //     'status' => 0,
        //     'method' => 'E-payment',
        //     'client_id' => $client->id,

        // ];
    
        // DB::connection('shulesoft')->table('admin.payments')->insert($data1);

        // Check if a booking already exists for this user, package, and amount
        $existingBooking = \App\Models\AdminBooking::where([
            ['user_id', '=', Auth::user()->id],
            ['admin_package_id', '=', request('package_id')],
            ['amount', '=', request('amount')],
        ])->first();

        if (!$existingBooking) {
            $this->data['booking'] = \App\Models\AdminBooking::create([
            'order_id' => time(),
            'amount' => request('amount'),
            'reference' => $reference,
            'methods' => 'online',
            'user_id' => Auth::user()->id,
            'admin_package_id' => request('package_id'),
            'status' => 0,
            ]);

            $user = Auth::user();
   
            // Send message to the user
            $userMessage = "Hello {$user->name},\n\nYour invoice has been created successfully. Reference Number: *{$reference}*.\nThank you for using DikoDiko.";
            $this->sendMessage($user->phone, $userMessage, 'whatsapp');

            // Send message to admin
            $adminMessage = "New invoice created for user: {$user->name} (ID: {$user->id}, Phone: {$user->phone}). Reference Number: *{$reference}*, Amount: " . request('amount') . ".";
            $this->notifyAdmin($adminMessage);

        } else {
            $this->data['booking'] = $existingBooking;
        }
        $this->data['transaction'] = $data;

        return view('payment.fullpaymentpage', $this->data);
    }
}
