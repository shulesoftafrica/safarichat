<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventsGuest;
use \App\Models\Payment;
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

    public function testMessage(){
        $wasender = new \App\Services\WaSenderService();
                
                try {
                    $result = $wasender->sendTextMessage(
                        '255714825469', // Phone number in international format
                        'Hello! This is a test message from  SafariChat.', // Message content
                        null, // Instance ID (will use default from config)
                        Auth::id() // User ID for tracking
                    );
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Test message sent successfully',
                        'data' => $result
                    ]);
                    
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send test message',
                        'error' => $e->getMessage()
                    ], 500);
                }
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

          // Check if user has no active whatsapp instance
            // $hasWhatsappInstance = \App\Models\MessageInstance::where('user_id', Auth::id())
            // ->where('type', 'whatsapp')
            // ->where('status', 1)
            // ->exists();

            // if (!$hasWhatsappInstance) {
            //       $this->data['ward'] = Auth::user()->business;
            //     $this->data['event'] = [];
           
            //     return view('auth.business.wasender', $this->data);
            // }

         
        $userBusiness = Auth::user()->business;
   
     
        if (!$userBusiness) {
            // Create a default business if none exists
            $userBusiness = \App\Models\Business::create([
                'user_id' => Auth::user()->id,
                'name' => Auth::user()->name . ' Business',
                'address' => 'Default Address',
                'descriptions' => 'Default Business Description',
                'ward_id' => 1
            ]);
        }
        $business_id = $userBusiness->id;

        // WhatsApp-based metrics using the new tables
        $user_id = Auth::id();
        
        // Total WhatsApp contacts (guests)
        $this->data['guests'] = EventsGuest::where('business_id', $business_id)->count();
        
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
                WHERE business_id = ? 
                GROUP BY month_date 
                ORDER BY month_date ASC
            ", [$business_id]);
        }

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
                'subject' => 'SafariChat Verification Code'
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
        $whatsapp_instance = \App\Models\WhatsappInstance::firstOrCreate([
            'user_id' => Auth::id(),
            'phone_number' => Auth::user()->phone,
        ], [
            'instance_name' => Auth::user()->name,
            'status' => 'pending'
        ]);
        if (in_array((int) $event->event_type_id, [1, 3])) {
            //register partner account and send notifications to the partners
            $phone = validate_phone_number(request('partner_phone'))[1];
            $user_info = \App\Models\User::whereEmail(request('partner_name') . '@safarichat.africa')->wherePhone($phone)->first();
            $user = empty($user_info) ? \App\Models\User::create([
                'name' => request('partner_name'),
                'email' => request('partner_name') . '@safarichat.africa',
                'password' => bcrypt(rand(45555, 999989)),
                'phone' => $phone,
                'event_type_id' > 1
            ]) : $user_info;
            $user_events = \App\Models\UsersEvent::firstOrCreate([
                'user_id' => $user->id,
                'event_id' => $event->id
            ]);

            $instance = \App\Models\WhatsappInstance::create([
                'instance_name' => Auth::user()->name,
                'user_id' => Auth::id(),
                'phone_number' => Auth::user()->phone,
                'status' => 'pending'
            ]);
        }
        return redirect()->back()->with('success', 'success');
    }

    public function settings()
    {

        $userBusiness = Auth::user()->business;
        if (!$userBusiness) {
            // Create a default business if none exists
            $userBusiness = \App\Models\Business::create([
                'user_id' => Auth::user()->id,
                'name' => Auth::user()->name . ' Business',
                'address' => 'Default Address',
                'descriptions' => 'Default Business Description',
                'ward_id' => 1
            ]);
        }
        $this->data['business'] = $userBusiness;
        // Create proper structure for user_accounts with user relationship
        $this->data['user_accounts'] = collect([Auth::user()])->map(function($user) {
            return (object)['user' => $user];
        })->all();
        if ($_POST) {
            $table = request('table');
            switch ($table) {
                case 'user':
                    \App\Models\User::findOrFail(Auth::user()->id)->update(request()->all());

                    break;
                case 'event_guest_category':
                    if ((int) request('edit') > 0) {
                        \App\Models\EventGuestCategory::whereId(request('edit'))
                            ->where(function($query) use ($userBusiness) {
                                $query->where('business_id', $userBusiness->id)
                                      ->orWhereNull('business_id');
                            })
                            ->update(['name' => request('name'), 'business_id' => $userBusiness->id]);
                    } else {
                        \App\Models\EventGuestCategory::firstOrCreate(['name' => request('name'), 'business_id' => $userBusiness->id]);
                    }
                    break;
                case 'business':
                    \App\Models\Business::findOrFail($userBusiness->id)->update(request()->all());
                    break;
                default:
                    break;
            }
            return redirect()->back()->with('success', 'success');
        }

        // Get categories for this business, including legacy null business_id records
        $this->data['categories'] = \App\Models\EventGuestCategory::with('business')
            ->where(function($query) use ($userBusiness) {
                $query->where('business_id', $userBusiness->id)
                      ->orWhereNull('business_id');
            })->get();
            
        // Add subscription data for the settings page
        $this->data['current_subscription'] = Auth::user()->activeSubscription;
        $this->data['subscription_status'] = Auth::user()->subscription_status;
        $this->data['available_credits'] = Auth::user()->available_credits;
        $this->data['trial_ends_at'] = Auth::user()->trial_ends_at;
        
        // Add available packages for upgrade options
        $this->data['packages'] = \App\Models\AdminPackage::where('is_addon', 0)->get();
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
                'has been received successfully. You can now enjoy using safarichat to give your event a meaning you dream';
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
            'addon_id' => 7, //default addon id for 
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
        //     'note' =>'safarichat addon payment',
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
            $userMessage = "Hello {$user->name},\n\nYour invoice has been created successfully. Reference Number: *{$reference}*.\nThank you for using Safarichat.";
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
