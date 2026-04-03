<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\BusinessContact;
use \App\Models\Payment;
use \App\Models\User;
use \App\Models\BillingWebhookEvent;
use Auth;
use DB;
use Illuminate\Support\Str;

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

          //Check if user has no active whatsapp instance
            $hasWhatsappInstance = \App\Models\WhatsappInstance::where('user_id', Auth::id())
            ->where('status', 'connected')
            ->exists();

            if (!$hasWhatsappInstance) {
                  $this->data['ward'] = Auth::user()->business;
                $this->data['event'] = [];
           
                return view('auth.business.wasender', $this->data);
            }

         
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

        // WhatsApp-based metrics using the new tables with instance filtering
        $user_id = Auth::id();
        $activeInstanceId = session('active_whatsapp_instance');
        
        // Base query with optional instance filtering
        $messageQuery = function($model) use ($user_id, $activeInstanceId) {
            $query = $model::where('user_id', $user_id);
            if ($activeInstanceId) {
                $query->where('whatsapp_instance_id', $activeInstanceId);
            }
            return $query;
        };
        
        // Total WhatsApp contacts (guests)
        $this->data['guests'] = BusinessContact::where('business_id', $business_id)->count();
        
        // Instance-aware active conversations
        $this->data['active_conversations'] = $messageQuery(\App\Models\IncomingMessage::class)
            ->where('created_at', '>=', now()->subDays(30))
            ->distinct('phone_number')
            ->count();
            
        // Instance-aware messages sent today
        $this->data['messages_sent_today'] = $messageQuery(\App\Models\OutgoingMessage::class)
            ->whereDate('created_at', today())
            ->count();
            
        // Instance-aware response rate (incoming messages vs outgoing messages ratio)
        $outgoing_count = $messageQuery(\App\Models\OutgoingMessage::class)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $incoming_count = $messageQuery(\App\Models\IncomingMessage::class)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();
        $this->data['response_rate'] = $outgoing_count > 0 ? round(($incoming_count / $outgoing_count) * 100, 1) : 0;
        
        // Instance-aware chart data - messages over time (last 12 months)
        $instanceFilter = $activeInstanceId ? "AND whatsapp_instance_id = $activeInstanceId" : "";
        $this->data['reports'] = DB::select("
            SELECT 
                COUNT(*) as sum, 
                TO_CHAR(created_at, 'MM-YYYY') as month_date 
            FROM outgoing_messages 
            WHERE user_id = ? 
                AND created_at >= ? 
                $instanceFilter
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
                FROM business_contacts 
                WHERE user_id = ? 
                GROUP BY month_date 
                ORDER BY month_date ASC
            ", [$user_id]);
        }

        // Instance-aware recent activity data for WhatsApp
        $recentQuery = \App\Models\IncomingMessage::where('user_id', $user_id);
        if ($activeInstanceId) {
            $recentQuery->where('whatsapp_instance_id', $activeInstanceId);
        }
        $this->data['recent_messages'] = $recentQuery
            ->with('guest')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        // Instance selector data
        $this->data['whatsapp_instances'] = \App\Models\WhatsappInstance::where('user_id', $user_id)
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->get();
            
        // Current active instance
        $this->data['active_instance_id'] = $activeInstanceId;
        
        // Load billing data from billing_accounts table
        $billingAccount = null;
        if (Auth::user()->business) {
            $billingAccount = Auth::user()->business->billingAccount;
        }
        
   
        // Set billing-related data
        if ($billingAccount) {
            $this->data['subscription_status'] = $billingAccount->status ?? 'inactive';
            $this->data['subscription_plan'] = $billingAccount->subscription_plan ?? 'trial';
          
            $this->data['available_credits'] = $billingAccount->ai_credits ?? 0;
            $this->data['subscription_expires_at'] = $billingAccount->subscription_expires_at;
        } else {
            $this->data['subscription_status'] = 'inactive';
            $this->data['subscription_plan'] = 'trial';
            $this->data['available_credits'] = 0;
            $this->data['subscription_expires_at'] = null;
        }
        
        return view('home', $this->data);
    }

    public function profile()
    {
        $this->data['guests'] = BusinessContact::count();
        $this->data['total_pledge'] = BusinessContact::sum('guest_pledge');
        // Event payment system removed - focusing on guest/contact management
        exit;
        return view('auth.profile', $this->data);
    }

    public function upgrade()
    {
        // Upgrade functionality moved to new billing system
        return redirect()->route('dashboard')->with('info', 'Please use the new billing system for upgrades.');
    }

    // Payment method removed - using new billing system

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
            $user_info = \App\Models\User::whereEmail(request('partner_name') . '@safarichat.ai')->wherePhone($phone)->first();
            $user = empty($user_info) ? \App\Models\User::create([
                'name' => request('partner_name'),
                'email' => request('partner_name') . '@safarichat.ai',
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

    /**
     * Create business campaign (Phase 2: Business-centric approach)
     */
    public function createCampaign()
    {
        $this->validate(request(), [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\']+$/',
                function ($attribute, $value, $fail) {
                    $sanitized = strip_tags(trim($value));
                    if ($value !== $sanitized) {
                        $fail('The '.$attribute.' field contains invalid characters.');
                    }
                    if (preg_match('/<[^>]*>/', $value)) {
                        $fail('The '.$attribute.' field contains HTML tags.');
                    }
                },
            ],
            "district_id" => "required|min:1",
            "date" => "date|required",
            'business_type_id' => 'required|integer'
        ]);

        // Get or create business for the user
        $userBusiness = Auth::user()->business;
        if (!$userBusiness) {
            $userBusiness = \App\Models\Business::create([
                'user_id' => Auth::user()->id,
                'name' => Auth::user()->name . ' Business',
                'address' => 'Default Address',
                'descriptions' => 'Auto-created business profile',
                'ward_id' => 1,
                'business_type_id' => request('business_type_id')
            ]);
        }

        // Update business with campaign information
        $userBusiness->update([
            'campaign_name' => request('name'),
            'campaign_start_date' => now()->toDateString(),
            'campaign_end_date' => request('date'),
            'district_id' => request('district_id'),
            'campaign_uid' => \Str::uuid(),
            'business_type_id' => request('business_type_id')
        ]);

        // Create WhatsApp instance
        $whatsapp_instance = \App\Models\WhatsappInstance::firstOrCreate([
            'user_id' => Auth::id(),
            'phone_number' => Auth::user()->phone,
        ], [
            'instance_name' => Auth::user()->name,
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Campaign created successfully! Your business profile has been updated.');
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
        
        // Load billing data FIRST to get correct plan
        $billingAccount = $userBusiness->billingAccount;
        
        if ($billingAccount) {
            $this->data['subscription_status'] = $billingAccount->status ?? 'inactive';
            $this->data['subscription_plan'] = $billingAccount->subscription_plan ?? 'trial';
            $this->data['available_credits'] = $billingAccount->ai_credits ?? 0;
            $this->data['subscription_expires_at'] = $billingAccount->subscription_expires_at;
            $this->data['subscription_started_at'] = $billingAccount->subscription_started_at;
            $this->data['billing_account'] = $billingAccount;
        } else {
            $this->data['subscription_status'] = 'inactive';
            $this->data['subscription_plan'] = 'trial';
            $this->data['available_credits'] = 0;
            $this->data['subscription_expires_at'] = null;
            $this->data['subscription_started_at'] = null;
            $this->data['billing_account'] = null;
        }
        
        // Get current plan limits
        $currentPlan = $this->data['subscription_plan'];
        $planConfig = config("safarichat_billing.plans.{$currentPlan}");
        $this->data['max_users'] = $planConfig['limits']['whatsapp_channels'] ?? 1;
        
        // Load all users for this business (owner + team members)
        $businessUsers = \App\Models\User::where(function($query) use ($userBusiness) {
            $query->where('id', $userBusiness->user_id) // Owner
                  ->orWhere('parent_business_id', $userBusiness->id); // Team members
        })->get();
        
        $this->data['user_accounts'] = $businessUsers->map(function($user) {
            return (object)['user' => $user];
        })->all();
        
        $this->data['current_user_count'] = $businessUsers->count();
        if ($_POST) {
            $table = request('table');
            switch ($table) {
                case 'user':
                    \App\Models\User::findOrFail(Auth::user()->id)->update(request()->all());
                    break;
                case 'add_user':
                    return $this->storeTeamMember();
                case 'delete_user':
                    return $this->deleteTeamMember();
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
                    // Exclude 'email' — it is the stable billing identifier auto-generated
                    // by getBillingEmail() and must never be overwritten by the user.
                    \App\Models\Business::findOrFail($userBusiness->id)->update(
                        request()->except(['_token', '_method', 'table', 'email'])
                    );
                    break;
                default:
                    break;
            }
            return redirect()->back()->with('success', 'success');
        }

        // Get categories for this business, including legacy null business_id records
        $this->data['categories'] = \App\Models\BusinessContactCategory::with('business')
            ->where(function($query) use ($userBusiness) {
                $query->where('business_id', $userBusiness->id)
                      ->orWhereNull('business_id');
            })->get();
            
        // Billing data already loaded above (before user count logic)
        
        // Load available plans from config
        $billingConfig = config('safarichat_billing');
        $this->data['available_plans'] = $billingConfig['plans'] ?? [];
        
        // Load payment history (you'll need to create this model/migration)
        $this->data['payment_history'] = [];  // TODO: Implement payment history model
        
        return view('auth.settings', $this->data);
    }

    // Support system removed - use external support tools instead

    /**
     * Return billing history as JSON for the settings page modal.
     * Reads from billing_webhook_events (successful subscription/payment events).
     */
    public function billingHistory()
    {
        $userBusiness = Auth::user()->business;
        if (!$userBusiness) {
            return response()->json(['success' => false, 'data' => []]);
        }

        $billingAccount = $userBusiness->billingAccount;
        if (!$billingAccount) {
            return response()->json(['success' => true, 'data' => []]);
        }

        // Fetch successful webhook events linked to this billing account
        $events = BillingWebhookEvent::where('billing_account_id', $billingAccount->id)
            ->where('processing_status', 'success')
            ->whereIn('event_type', [
                'payment.success',
                'subscription.created',
                'subscription.renewed',
                'subscription.upgraded',
                'credits.purchased',
            ])
            ->orderByDesc('processed_at')
            ->limit(50)
            ->get()
            ->map(function ($event) {
                $payload = $event->payload ?? [];

                // Description
                $description = match($event->event_type) {
                    'payment.success'        => 'Payment received',
                    'subscription.created'   => 'Subscription activated',
                    'subscription.renewed'   => 'Subscription renewed',
                    'subscription.upgraded'  => 'Plan upgraded',
                    'credits.purchased'      => 'AI Credits purchased',
                    default                  => ucfirst(str_replace('.', ' ', $event->event_type)),
                };

                // Amount — from payment block
                $amount = $payload['payment']['amount']
                    ?? $payload['wallet_transaction']['amount']
                    ?? null;

                // Plan info
                $planRaw = $payload['subscription']['plan']['name']
                    ?? $payload['subscription']['plan']
                    ?? $payload['wallet_transaction']['plan_name']
                    ?? null;

                // Invoice number
                $invoiceNumber = $payload['wallet_transaction']['invoice_number']
                    ?? $payload['invoice']['invoice_number']
                    ?? null;

                // Transaction ID
                $txId = $payload['payment']['transaction_id']
                    ?? $event->transaction_id
                    ?? null;

                return [
                    'date'           => $event->processed_at
                                            ? \Carbon\Carbon::parse($event->processed_at)->format('M d, Y H:i')
                                            : ($event->created_at ? \Carbon\Carbon::parse($event->created_at)->format('M d, Y H:i') : '-'),
                    'event_type'     => $event->event_type,
                    'description'    => $description,
                    'plan'           => $planRaw,
                    'amount'         => $amount ? number_format((float)$amount) . ' TZS' : '-',
                    'invoice_number' => $invoiceNumber,
                    'transaction_id' => $txId,
                    'status'         => 'Paid',
                ];
            });

        return response()->json(['success' => true, 'data' => $events]);
    }

    public function storeTeamMember()
    {
        $userBusiness = Auth::user()->business;
        
        // Get current plan limits
        $billingAccount = $userBusiness->billingAccount;
        $currentPlan = $billingAccount->subscription_plan ?? 'trial';
        $planConfig = config("safarichat_billing.plans.{$currentPlan}");
        $maxUsers = $planConfig['limits']['whatsapp_channels'] ?? 1;
        
        // Count existing users (owner + team members)
        $currentUserCount = \App\Models\User::where(function($query) use ($userBusiness) {
            $query->where('id', $userBusiness->user_id)
                  ->orWhere('parent_business_id', $userBusiness->id);
        })->count();
        
        // Check if limit reached
        if ($currentUserCount >= $maxUsers) {
            return redirect()->back()->with('error', 'User limit reached for your current plan. Please upgrade to add more users.');
        }
        
        // Validate request
        $validated = request()->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:6'
        ]);
        
        // Create new team member
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => \Hash::make($validated['password']),
            'parent_business_id' => $userBusiness->id,
            'role' => 'member',
            'uuid' => (string) \Str::uuid(),
        ]);
        
        return redirect()->back()->with('success', 'Team member added successfully!');
    }
    
    public function deleteTeamMember()
    {
        $userId = request('user_id');
        $userBusiness = Auth::user()->business;
        
        // Prevent deleting the logged-in user
        if ($userId == Auth::user()->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }
        
        // Find the user and ensure they belong to this business
        $user = \App\Models\User::where('id', $userId)
            ->where('parent_business_id', $userBusiness->id)
            ->first();
            
        if (!$user) {
            return redirect()->back()->with('error', 'User not found or you do not have permission to delete this user.');
        }
        
        $user->delete();
        
        return redirect()->back()->with('success', 'Team member deleted successfully.');
    }

    public function addUser()
    {
        // Legacy method - redirect to storeTeamMember
        return $this->storeTeamMember();
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
        // Payments functionality moved to new billing system
        return redirect()->route('dashboard')->with('info', 'Payment management moved to new billing system.');
    }

    public function paymentdestroy()
    {
        // Payment deletion moved to new billing system
        return redirect()->route('dashboard')->with('info', 'Payment management moved to new billing system.');
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
        // Addon invoice creation moved to new billing system
        return redirect()->route('dashboard')->with('info', 'Invoice creation moved to new billing system.');
    }
}
