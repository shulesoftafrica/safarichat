<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\BusinessContactCategory;
use \App\Models\BusinessContactCategory as EventGuestCategory;
use \App\Models\Message as SMS;
use \App\Models\BusinessContact;
use \App\Models\BusinessContact as EventsGuest;
use \App\Models\OutgoingMessage;
use \App\Models\Lead;
use \App\Jobs\SendWhatsAppMessage;
use \App\Jobs\SendWhatsAppMediaMessage;
use \App\Jobs\ProcessBulkMessages;
use \App\Services\WaSenderService;
use \App\Services\SystemWhatsAppService;
use \App\Services\BillingService;
use \App\Services\LocalBillingValidator;
use Illuminate\Support\Arr;
use Auth;
use DB;
use Illuminate\Support\Env;
// AdminBooking removed - using new billing system
use Illuminate\Support\Facades\Log;

class Message extends Controller
{

    public $patterns = array(
        '/#name/i',
        '/#username/i',
        '/#default_password/i',
        '/#email/i',
        '/#phone/i',
        '/#role/i',
        '/#student_name/i',
        '/#invoice/i',
        '/#balance/i',
        '/#student_username/i'
    );

    public $token;
    public $baseUrl;

    public function __construct()
    {
        $this->middleware('auth');
        // WaSender configuration is now handled by the service
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data[''] = [];
     
        if (Auth::user()->whatsappInstances()->count() == 0) {
            // Create a new WhatsApp instance for the user
            \App\Models\WhatsappInstance::create([
                'user_id' => Auth::id(),
                'status' => 'pending',
                'phone_number' => ltrim(Auth::user()->phone, '+'),
                'instance_name' => Auth::user()->name,
            ]);
        }
        
        // Pass Lead Statuses instead of Customer Categories
        $this->data['lead_statuses'] = [
            \App\Models\Lead::STATUS_NEW => 'New',
            \App\Models\Lead::STATUS_OUTREACHED => 'Outreached',
            \App\Models\Lead::STATUS_REPLIED => 'Replied',
            \App\Models\Lead::STATUS_ENGAGED => 'Engaged',
            \App\Models\Lead::STATUS_QUALIFIED => 'Qualified',
            \App\Models\Lead::STATUS_PITCHED => 'Pitched',
            \App\Models\Lead::STATUS_DEMO_SCHEDULED => 'Demo Scheduled',
            \App\Models\Lead::STATUS_PROPOSAL_SENT => 'Proposal Sent',
            \App\Models\Lead::STATUS_NEGOTIATING => 'Negotiating',
            \App\Models\Lead::STATUS_CLOSED => 'Closed',
            \App\Models\Lead::STATUS_LOST => 'Lost',
            \App\Models\Lead::STATUS_HANDED_OFF => 'Handed Off',
            \App\Models\Lead::STATUS_DO_NOT_CONTACT => 'Do Not Contact',
            \App\Models\Lead::STATUS_NEEDS_ATTENTION => 'Needs Attention',
            \App\Models\Lead::STATUS_CONVERTED => 'Converted',
            \App\Models\Lead::STATUS_CHURNED => 'Churned'
        ];
        
          //check if the invoice has been paid for bulksms
      
        $this->data['whatsapp'] = $this->checkChannelStatus('whatsapp');
        $this->data['remained_sms'] = $this->checkChannelStatus('quick-sms');
        $this->data['phone_sms'] = $this->checkChannelStatus('phone-sms');
      
        return view('message.index', $this->data);
    }

    public function checkBookedInvoicePayment(){
        // Payment status checking moved to new billing system
        return response()->json(['status' => 'success', 'message' => 'Payment checking moved to new billing system']);
    }

    public function channel()
    {

        $this->data['instances'] = Auth::user()->whatsappInstances()->get();
       
        $instance = \App\Models\WhatsappInstance::where('user_id', Auth::user()->id)->first();
        if (empty($instance)) {
              $user = Auth::user();

              if($user->bulksms_enabled == 1){
               
            $instance = \App\Models\WhatsappInstance::create([
                'name' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', Auth::user()->name), 0, 11),
                'user_id' => Auth::id(),
                'phone_number' => Auth::user()->phone,
                'status' => 0,
                'type' => 'bulksms',

            ]);
            // Send WhatsApp message to user about sender name creation and required details
          
            if ($user && !empty($user->phone)) {
                $waMessage = "Hello {$user->name}, your sender name has been created. To complete registration, please submit your NIDA number and an introduction letter for approval.";
                $this->send($waMessage, $user->phone, $user->id);
            }
        }
        }  
 
        // if (Auth::user()->messageInstances()->where('type', 'whatsapp')->count() == 0) {
        //     // Create a new WhatsApp instance for the user
        //     \App\Models\MessageInstance::create([
        //         'user_id' => Auth::id(),
        //         'type' => 'whatsapp',
        //         'status' => 0,
        //         'phone_number' => Auth::user()->phone,
        //         'name' => Auth::user()->name,
        //     ]);
        // }
        if ($_POST) {
            //write
            $request = request();

            $validatedData = $request->validate([
                'sender_name' => [
                    'required',
                    'string',
                    'max:11',
                    'regex:/^[A-Za-z0-9 ]{1,11}$/'
                ],
                'nida_number' => 'required|string|max:30',
                'intro_letter' => 'required|file|mimes:pdf,doc,docx|max:2048',
            ]);

            try {
                // Handle file upload
                if ($request->hasFile('intro_letter')) {
                    $file = $request->file('intro_letter');
                    $filePath = $file->store('intro_letters', 'public');
                } else {
                    return redirect()->back()->withErrors(['intro_letter' => 'Introduction letter is required.']);
                }

                \App\Models\WhatsappInstance::create([
                    'name' => $validatedData['sender_name'],
                    'nida' => $validatedData['nida_number'],
                    'file_path' => $filePath,
                    'type' => 'bulksms',
                    'user_id' => Auth::id(),
                    'phone_number' => Auth::user()->phone,
                    'status' => 0,
                ]);
                $this->notifySystemAdmin('A new sender name has been submitted for approval: ' . $validatedData['sender_name'] . '. NIDA: ' . $validatedData['nida_number']);
                return redirect()->back()->with('success', 'Sender name submitted for approval.');
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['error' => 'Failed to submit: ' . $e->getMessage()]);
            }
        }
        return view('message.channel', $this->data);
    }


    public function channelDelete()
    {
        $uuid = request()->segment(3);
        if (!$uuid) {
            return redirect()->back()->with('error', 'Channel UUID is required.');
        }

        $instance = \App\Models\WhatsappInstance::where('id', $uuid)->first();
        if (!$instance) {
            return redirect()->back()->with('error', 'Channel not found.');
        }
        //check if its whatsapp instance and has instance_id
        if (!empty($instance->instance_id)) {
            $this->LogoutInstance($instance->instance_id);
        }
        $instance->delete();

        return redirect()->back()->with('success', 'Channel deleted successfully.');
    }
    public function schedule()
    {

     $this->data['usertypes'] = \App\Models\BusinessContactCategory::where('business_id', Auth::user()->business->id)->get();

        if ($_POST) {
            $category_id = request('category_id');
            $criteria = strip_tags(request('criteria'));
            $users = $this->getUserByCriteria($criteria, Auth::user()->business->id, null, $category_id);
            $exclude_users = request('users');
            $user_inputs = [];

            if ($criteria == 6) {
                //custom selection, so take users 
                $user_inputs = $exclude_users;
            } else {
                //exclude users
                //loop throught the array and exclude users
                foreach ($users as $user) {
                    !in_array($user->id, !empty($exclude_users) ? $exclude_users : []) ?
                        array_push($user_inputs, $user->id) : "";
                }
            }

            //            $first = Arr::first($users, function ($value, $key) {
            //                        return $value >= 0;
            //                    });
            //            $user_inputs = [];
            //            if ((int) $first == 0) {
            //                $lists = (int) request('event_guest_category_id') > 0 ?
            //                        \App\Models\EventsGuest::whereEventGuestCategoryId(request('event_guest_category_id'))->get(['id']) :
            //                        \App\Models\EventsGuest::where('business_id', Auth::user()->business->id)->get();
            //                foreach ($lists as $list) {
            //                    array_push($user_inputs, $list->id);
            //                }
            //            } else {
            //                $user_inputs = $users;
            //            }
            //add criteria sort

            $users_lists = implode(',', array_values($user_inputs));
            $arr = [
                'user_id' => Auth::user()->id,
                'lead_status' => request('lead_status'),
                'date' => date('Y-m-d h:i', strtotime(request('date'))),
                'time' => date('h:i', strtotime(request('time'))),
                'message' => strip_tags(request('message')),
                'last_date' => date('Y-m-d h:i', strtotime(request('last_date'))),
                'title' => strip_tags(request('title')),
                'users' => $users_lists,
                'is_repeated' => request('is_repeated'),
                'channels' => implode(',', request('channels')),
                'criteria' => strip_tags(request('criteria')),
                'days' => request('date') == Null ? implode(',', request('days')) : ''
            ];
            \App\Models\Reminder::create($arr);
            return redirect()->back()->with('success', 'success');
        }
        $this->data['schedules'] = \App\Models\Reminder::where('user_id', Auth::user()->id)->get();
        return view('message.schedule', $this->data);
    }

    /**
     * Ajax function to get users
     */
    public function callUsers()
    {
        $lead_status = request('lead_status'); // Updated from category_id to lead_status
        $criteria = strip_tags(request('criteria'));
      
        $users = $this->getUserByCriteria($criteria, Auth::user()->business->id, null, $lead_status);
        if (empty($users)) {
            echo '0';
        } else {
            // echo "<option value='" . 0 . "'>All</option>";
            foreach ($users as $user) {
                echo "<option value='" . $user->id . "'>" . $user->guest_name . "</option>";
            }
        }
    }

    public function report()
    {
        // FEATURE GATE: Check if sales reports are allowed in current subscription plan
        $customerId = Auth::user()->customer_id ?? Auth::id();
        $billingStatus = BillingService::getCachedStatus($customerId);
        
        if (!$billingStatus['permissions']['sales_reports']) {
            return view('message.report-upgrade-required', [
                'feature' => 'Sales Reports & Analytics',
                'current_plan' => $billingStatus['subscription']['plan'],
                'required_plan' => 'premium'
            ]);
        }
        
        $business_id = Auth::user()->business->id;
        $user_id = Auth::id();

        // Real WhatsApp Business Data
        $this->data['sms_sent'] = DB::table('messages')->where('user_id', $user_id)->where('type', 2)->count();
        $this->data['whatsapp_sent'] = \App\Models\OutgoingMessage::where('user_id', $user_id)->count();
        
        // If no outgoing messages, fallback to old data
        if ($this->data['whatsapp_sent'] == 0) {
            $this->data['whatsapp_sent'] = DB::table('messages')->where('user_id', $user_id)->where('type', 4)->count();
        }
        
        // Real message analytics
        $this->data['whatsapp_received'] = \App\Models\IncomingMessage::where('user_id', $user_id)->count();
        $this->data['active_conversations'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->where('received_at', '>=', now()->subDays(30))
            ->distinct('phone_number')
            ->count();
        
        // Response rate calculation
        $this->data['response_rate'] = $this->data['whatsapp_sent'] > 0 
            ? round(($this->data['whatsapp_received'] / $this->data['whatsapp_sent']) * 100, 1) 
            : 0;
            
        // Today's activity
        $this->data['messages_sent_today'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->whereDate('created_at', today())
            ->count();
            
        $this->data['messages_received_today'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->whereDate('received_at', today())
            ->count();
        
        // This week's data
        $this->data['messages_sent_week'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
            
        $this->data['messages_received_week'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->where('received_at', '>=', now()->startOfWeek())
            ->count();
        
        // Customer engagement metrics — user_id scope matches billing limit checks
        $this->data['total_contacts'] = \App\Models\BusinessContact::where('user_id', $user_id)->count();
        $this->data['contacts_messaged'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->distinct()
            ->count('phone_number');
            
        // WhatsApp instances
        $this->data['whatsapp_instances'] = \App\Models\WhatsappInstance::where('user_id', $user_id)->count();
        $this->data['connected_instances'] = \App\Models\WhatsappInstance::where('user_id', $user_id)
            ->where('connect_status', 'Connected')
            ->count();
        
        // Message status analytics
        $this->data['successful_messages'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->where('status', 'sent')
            ->count();
            
        $this->data['failed_messages'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->where('status', 'failed')
            ->count();
        
        // Auto-reply statistics
        $this->data['auto_replies_sent'] = \App\Models\IncomingMessage::where('user_id', $user_id)
            ->where('auto_reply', true)
            ->count();
        
        // Message type breakdown
        $this->data['text_messages'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->where('message_type', 'text')
            ->count();
            
        $this->data['media_messages'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->whereIn('message_type', ['image', 'document', 'audio', 'video'])
            ->count();
        
        // Time-based analytics (last 12 months)
        $this->data['reports'] = DB::select("
            SELECT 
                COUNT(*) as count, 
                TO_CHAR(created_at, 'MM') as month,
                TO_CHAR(created_at, 'YYYY') as year,
                TO_CHAR(created_at, 'Mon-YY') as month_name
            FROM outgoing_messages 
            WHERE user_id = ? 
                AND created_at >= ? 
            GROUP BY TO_CHAR(created_at, 'MM'), TO_CHAR(created_at, 'YYYY'), TO_CHAR(created_at, 'Mon-YY'), DATE_TRUNC('month', created_at)
            ORDER BY DATE_TRUNC('month', created_at) ASC 
            LIMIT 12
        ", [$user_id, now()->subMonths(12)]);
        
        // If no outgoing message data, use old format
        if (empty($this->data['reports'])) {
            $this->data['reports'] = DB::table('messages')
                ->where('user_id', $user_id)
                ->select(DB::raw('count(*) as count, extract(month from created_at) as month, extract(year from created_at) as year'))
                ->groupBy('month', 'year')
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }
        
        // Customer satisfaction proxy (response rate trends)
        $this->data['customer_satisfaction'] = $this->data['response_rate'] > 50 ? 4.8 : ($this->data['response_rate'] > 25 ? 4.2 : 3.5);
        
        // Business impact calculations
        $this->data['cost_per_message'] = 50; // TSh 50 per message via WhatsApp
        $this->data['total_messaging_cost'] = $this->data['whatsapp_sent'] * $this->data['cost_per_message'];
        $this->data['estimated_reach'] = $this->data['whatsapp_sent'] * 0.98; // 98% delivery rate
        $this->data['estimated_leads'] = ceil($this->data['whatsapp_sent'] * 0.25); // 25% conversion to leads
        
        // ROI calculations
        $this->data['estimated_revenue_per_lead'] = 50000; // TSh 50,000 average per lead
        $this->data['estimated_total_revenue'] = $this->data['estimated_leads'] * $this->data['estimated_revenue_per_lead'];
        $this->data['roi_percentage'] = $this->data['total_messaging_cost'] > 0 
            ? round((($this->data['estimated_total_revenue'] - $this->data['total_messaging_cost']) / $this->data['total_messaging_cost']) * 100, 1)
            : 0;
        
        $this->data['schedules'] = DB::table('reminders')->where('user_id', $user_id)->count();
        $this->data['guest_categories'] = EventGuestCategory::where('business_id', Auth::user()->business->id)->get();

        return view('message.report', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getUserByCriteria($criteria, $business_id, $request = null, $sub_category = null)
    {
        Log::info('getUserByCriteria called', [
            'criteria' => $criteria,
            'business_id' => $business_id,
            'sub_category' => $sub_category,
            'request_custom_numbers' => $request ? $request->input('custom_numbers') : null
        ]);
        
        switch ($criteria) {
            case 1:
                //All
                $users = \App\Models\BusinessContact::where('business_id', $business_id);
                break;

            case 2:
                //Select Lead Status
                if ($request != null && $request->input('lead_status')) {
                    $users = \App\Models\BusinessContact::where('business_id', $business_id)
                        ->whereHas('lead', function($query) use ($request) {
                            $query->where('status', $request->input('lead_status'));
                        });
                } else {
                    $users = [];
                }
                break;

            case 3:
                //Full Paid Guest

                $users = \App\Models\BusinessContact::where('business_id', $business_id)->whereIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 4:
                //Non Paid Guest
                $users = \App\Models\BusinessContact::where('business_id', $business_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 5:
                //Partially Paid Guest
                $users = \App\Models\BusinessContact::where('business_id', $business_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 6:
                //Input Numbers
                $customNumbers = $request ? $request->input('custom_numbers') : request('custom_numbers');
                $phones = explode(',', strip_tags($customNumbers));
                $obj = [];
                foreach ($phones as $phone) {
                    $phone = trim($phone); // Remove whitespace
                    if (!empty($phone)) { // Only add non-empty phones
                        $build = ['guest_phone' => $phone, 'guest_email' => '', 'guest_name' => '', 'guest_pledge' => '', 'custom' => 1];
                        array_push($obj, $build);
                    }
                }
                $users = $obj; // Return array directly instead of casting to object
                break;
            default:
                break;
        }

        if ($criteria == 6) {
            $users = $users; // Already an array for custom numbers
        } else {
            // For lead status filtering (case 2), the query is already built with the status filter
            $users = $users->get();
        }
        
        // Debug logging
        $userCount = is_array($users) ? count($users) : (isset($users) && method_exists($users, 'count') ? $users->count() : 0);
        Log::info('getUserByCriteria result', [
            'criteria' => $criteria,
            'user_count' => $userCount,
            'users_type' => gettype($users),
            'users' => $userCount > 0 && $userCount <= 3 ? $users : 'truncated'
        ]);
        
        return $users;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $criteria = $request->criteria;
        
        // Add debug logging
        \Log::info('Campaign store request received', [
            'criteria' => $criteria,
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'has_files' => $request->hasFile('files'),
            'headers' => $request->headers->all(),
            'user_id' => Auth::id()
        ]);
        
        // Helper function to return either JSON or redirect based on request type
        $responseHelper = function($success, $message, $errors = null, $redirectRoute = null) use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                if ($success) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'redirect' => $redirectRoute ?: route('campaigns.index')
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                        'errors' => $errors
                    ], 422);
                }
            }
            
            if ($success) {
                $route = $redirectRoute ?: route('campaigns.index');
                return redirect($route)->with('success', $message);
            } else {
                return redirect()->back()->withErrors($errors ?: ['error' => $message])->withInput();
            }
        };
        
        // Validate criteria is provided
        if (empty($criteria)) {
            \Log::error('Campaign store: No criteria provided');
            return $responseHelper(false, 'Please select who you want to message', ['criteria' => 'Please select a recipient type']);
        }

        //save message to DB here first
        if (in_array('whatsapp', $request->source)) {
            $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', Auth::id())
                ->where('status', 'connected')
                ->where('connect_status', 'ready')
                ->first();
            if (!$whatsappInstance) {
                return $responseHelper(false, 'WhatsApp channel is not activated or paid. Please activate and pay for WhatsApp integration.', ['error' => 'WhatsApp channel is not activated or paid. Please activate and pay for WhatsApp integration.']);
            }
        }


        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('files')) {
            $uploadedFiles = $request->file('files');
            
            // Ensure we handle both single file and array of files
            if (!is_array($uploadedFiles)) {
                $uploadedFiles = [$uploadedFiles];
            }

            foreach ($uploadedFiles as $file) {
                if ($file && $file->isValid()) {
                    // Validate file size (16MB limit for WhatsApp)
                    if ($file->getSize() > 16 * 1024 * 1024) {
                        return $responseHelper(false, "File {$file->getClientOriginalName()} is too large. Maximum 16MB allowed.", ['files' => "File {$file->getClientOriginalName()} is too large. Maximum 16MB allowed."]);
                    }

                    // Validate file type
                    $allowedMimes = [
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                        'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
                        'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/mpeg',
                        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain', 'text/csv'
                    ];

                    if (!in_array($file->getMimeType(), $allowedMimes)) {
                        return $responseHelper(false, "File type {$file->getMimeType()} is not supported.", ['files' => "File type {$file->getMimeType()} is not supported."]);
                    }

                    // Store the file
                    $filePath = $file->store('attachments/' . date('Y/m'), 'public');
                    
                    // Ensure the directory exists
                    $fullPath = storage_path('app/public/' . $filePath);
                    if (!file_exists($fullPath)) {
                        Log::error('File was not stored properly', ['path' => $fullPath]);
                        return $responseHelper(false, "Failed to store file {$file->getClientOriginalName()}.", ['files' => "Failed to store file {$file->getClientOriginalName()}."]);
                    }
                    
                    $attachments[] = [
                        'path' => $filePath,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'filename' => $file->getClientOriginalName()
                    ];
                }
            }
        }

        $business_id = Auth::user()->business->id;
        
        // Debug logging
        Log::info('Message store processing', [
            'criteria' => $criteria,
            'business_id' => $business_id,
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
            'sources' => $request->source ?? []
        ]);
        
        // Determine recipients based on criteria from the form
        if ($criteria == 1) {
            // All Contacts
            $users = $this->getUserByCriteria(1, $business_id, $request);
        } elseif ($criteria == 2) {
            // Select Lead Status
            $users = $this->getUserByCriteria(2, $business_id, $request);
        } elseif ($criteria == 6) {
            // Custom Numbers
            $users = $this->getUserByCriteria(6, $business_id, $request);
        } elseif ($criteria == 7) {
            // Excel Upload
            // Parse uploaded Excel file for phone numbers
            $users = [];
            if ($request->hasFile('excel_contacts')) {
                $file = $request->file('excel_contacts');
                if (!$file->isValid()) {
                    return $responseHelper(false, 'Uploaded file is not valid.', ['excel_contacts' => 'Uploaded file is not valid.']);
                }
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['csv', 'xls', 'xlsx'])) {
                    $data = [];
                    if ($extension === 'csv') {
                        $handle = fopen($file->getRealPath(), 'r');
                        $header = fgetcsv($handle);
                        if (!$header || !in_array('phone', array_map('strtolower', $header)) || !in_array('name', array_map('strtolower', $header))) {
                            fclose($handle);
                            return $responseHelper(false, 'CSV file must contain "phone" and "name" columns.', ['excel_contacts' => 'CSV file must contain "phone" and "name" columns.']);
                        }
                        while (($row = fgetcsv($handle)) !== false) {
                            $rowData = array_combine($header, $row);
                            if (
                                !empty($rowData['phone']) &&
                                !empty($rowData['name'])
                            ) {
                                $users[] = [
                                    'guest_phone' => $rowData['phone'],
                                    'guest_name' => $rowData['name'],
                                    'custom' => 1
                                ];
                            }
                        }
                        fclose($handle);
                        if (empty($users)) {
                            return $responseHelper(false, 'No valid contacts found in the file.', ['excel_contacts' => 'No valid contacts found in the file.']);
                        }
                    } else {
                        // Use Laravel Excel if available
                        if (class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                            $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
                            $sheet = $rows[0] ?? [];
                            $header = array_map('strtolower', $sheet[0] ?? []);
                            if (!in_array('phone', $header) || !in_array('name', $header)) {
                                return $responseHelper(false, 'Excel file must contain "phone" and "name" columns.', ['excel_contacts' => 'Excel file must contain "phone" and "name" columns.']);
                            }
                            foreach (array_slice($sheet, 1) as $row) {
                                $rowData = array_combine($header, $row);
                                if (
                                    !empty($rowData['phone']) &&
                                    !empty($rowData['name'])
                                ) {
                                    $users[] = [
                                        'guest_phone' => $rowData['phone'],
                                        'guest_name' => $rowData['name'],
                                        'custom' => 1
                                    ];
                                }
                            }
                            if (empty($users)) {
                                return $responseHelper(false, 'No valid contacts found in the file.', ['excel_contacts' => 'No valid contacts found in the file.']);
                            }
                        } else {
                            return $responseHelper(false, 'Excel import package is not installed.', ['excel_contacts' => 'Excel import package is not installed.']);
                        }
                    }
                } else {
                    return $responseHelper(false, 'Invalid file type. Only CSV, XLS, and XLSX are allowed.', ['excel_contacts' => 'Invalid file type. Only CSV, XLS, and XLSX are allowed.']);
                }
            }
            $users = collect($users);
        } else {
            // Default fallback
            $users = $this->getUserByCriteria($criteria, $business_id, $request);
        }
        
     
        // Use queue system for message processing with attachments
        $this->queueMessages($users, $request->message, $request->source, $attachments);
        
        $messageCount = is_array($users) ? count($users) : (isset($users) && method_exists($users, 'count') ? $users->count() : 0);
        $attachmentCount = count($attachments);
        
        $successMessage = "Messages queued for delivery successfully to {$messageCount} recipients!";
        if ($attachmentCount > 0) {
            $successMessage .= " Including {$attachmentCount} attachment(s).";
        }
        $successMessage .= " You will receive notifications as they are processed.";
        
        // Check if this is an AJAX request
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect' => route('campaigns.index'),
                'messageCount' => $messageCount,
                'attachmentCount' => $attachmentCount
            ]);
        }
        
        return redirect()->route('campaigns.index')->with('success', $successMessage);
    }

    /**
     * Queue messages for AI personalization and delivery
     * 
     * NEW WORKFLOW (Following advanced_messaging.md):
     * 1. Create Campaign record
     * 2. Create MessageQueue entries for each recipient (status: staged)
     * 3. Dispatch PersonalizeCampaignMessagesJob for AI analysis
     * 4. AI personalizes based on conversation history, language, tone
     * 5. Messages scheduled for optimal send time
     * 6. ScheduleMessageSendJob delivers refined messages
     */
    private function queueMessages($users, $message, $sources, $attachments = [])
    {
        // Only handle WhatsApp now, ignore other sources
        if (!in_array('whatsapp', $sources)) {
            Log::info('No WhatsApp source specified, skipping message queuing');
            return;
        }

        // Debug logging
        Log::info('queueMessages called with data', [
            'users_type' => gettype($users),
            'users_is_array' => is_array($users),
            'users_is_collection' => $users instanceof \Illuminate\Support\Collection,
            'sources' => $sources,
            'message' => substr($message, 0, 100) // First 100 chars
        ]);

        if (is_array($users)) {
            $userCount = count($users);
        } elseif ($users instanceof \Illuminate\Support\Collection) {
            $userCount = $users->count();
        } else {
            $userCount = 0;
        }
        
        Log::info('Creating personalized campaign with AI processing', [
            'user_id' => Auth::id(),
            'recipient_count' => $userCount,
            'has_attachments' => !empty($attachments)
        ]);

        // Get user's WhatsApp instance for validation
        $waSenderService = new WaSenderService();
        $instance = $waSenderService->getUserInstance(Auth::id());
       
        if (!$instance) {
            Log::error('No active WhatsApp instance found for user', ['user_id' => Auth::id()]);
            return;
        }

        // STEP 1: Create Campaign record
        $campaign = \App\Models\Campaign::create([
            'user_id' => Auth::id(),
            'business_id' => Auth::user()->business->id ?? null,
            'campaign_name' => 'Campaign ' . now()->format('M d, Y H:i'),
            'campaign_type' => \App\Models\Campaign::TYPE_BROADCAST,
            'original_message' => $message,
            'recipient_criteria' => [], // Can be enhanced with actual criteria
            'total_recipients' => $userCount,
            'queued_count' => 0, // Will be incremented as we create queue entries
            'status' => \App\Models\Campaign::STATUS_STAGING,
            'has_attachments' => !empty($attachments),
            'started_at' => now()
        ]);

        Log::info('Campaign created for AI personalization', [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->campaign_name,
            'total_recipients' => $userCount
        ]);

        // Prepare attachment context for AI
        $attachmentContext = null;
        if (!empty($attachments)) {
            $attachmentContext = "Attachments included:\n";
            foreach ($attachments as $attachment) {
                $attachmentContext .= "- {$attachment['original_name']} ({$attachment['mime_type']})\n";
            }
        }

        // STEP 2: Create MessageQueue entries for each recipient
        $queuedCount = 0;
        $nurtureCount = 0;

        foreach ($users as $user) {
            $user = (object) $user;

            $phoneNumber = validate_phone_number($user->guest_phone);
           
            if (is_array($phoneNumber)) {
                $cleanPhone = $phoneNumber[1];
               
                // Check if nurture mode should be applied (for ghosting contacts)
                // Nurture mode has its own AI processing, so we skip adding to campaign queue
                $nurtureApplied = $this->applyNurtureModeIfNeeded($user, $message);
                
                if ($nurtureApplied) {
                    Log::info("Nurture mode applied, using separate nurture pipeline", [
                        'phone' => $cleanPhone,
                        'user_id' => Auth::id()
                    ]);
                    $nurtureCount++;
                    continue; // Skip adding to campaign queue
                }
                
                // Find or create contact record for relationship tracking
                $contact = \App\Models\BusinessContact::firstOrCreate(
                    [
                        'guest_phone' => $cleanPhone,
                        'business_id' => Auth::user()->business->id ?? null
                    ],
                    [
                        'guest_name' => $user->guest_name ?? 'Contact',
                        'user_id' => Auth::id(),
                        'engagement_score' => 50 // Default score
                    ]
                );

                // Create MessageQueue entry (status: staged for AI personalization)
                $messageQueue = \App\Models\MessageQueue::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => Auth::id(),
                    'contact_id' => $contact->id,
                    'phone_number' => $cleanPhone,
                    'contact_name' => $user->guest_name ?? $contact->guest_name ?? 'Contact',
                    'original_message' => $message, // Store original message
                    'refined_message' => null, // Will be filled by AI personalization
                    'attachment_context' => $attachmentContext,
                    'status' => \App\Models\MessageQueue::STATUS_STAGED, // Pending AI analysis
                    'priority' => 5, // Default priority (1-10 scale)
                    'provider' => \App\Models\MessageQueue::PROVIDER_WASENDER,
                    'created_at' => now()
                ]);

                $queuedCount++;
                $campaign->increment('queued_count');

                Log::info('MessageQueue entry created for AI personalization', [
                    'message_queue_id' => $messageQueue->id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'phone' => $cleanPhone
                ]);
            }
        }

        // Update campaign with final counts
        $campaign->update([
            'status' => \App\Models\Campaign::STATUS_PROCESSING
        ]);

        Log::info('Campaign staging complete', [
            'campaign_id' => $campaign->id,
            'queued_for_ai' => $queuedCount,
            'nurture_mode' => $nurtureCount,
            'total_recipients' => $userCount
        ]);

        // STEP 3: Dispatch AI personalization job
        if ($queuedCount > 0) {
            \App\Jobs\PersonalizeCampaignMessagesJob::dispatch($campaign->id)
                ->onQueue('ai_personalization');
            
            Log::info('PersonalizeCampaignMessagesJob dispatched', [
                'campaign_id' => $campaign->id,
                'messages_to_personalize' => $queuedCount
            ]);
        }

        // Note: Attachments will be sent during the final delivery phase
        // by ScheduleMessageSendJob after AI personalization is complete
    }

    /**
     * Queue media message for processing
     */
    private function queueMediaMessage($phoneNumber, $attachment, $caption, $delay, $instance)
    {
        // Determine media type based on file extension/mime type
        $mimeType = $attachment['mime_type'];
        $mediaType = $this->getMediaType($mimeType);
        $filePath = storage_path('app/public/' . $attachment['path']); // Convert to full path
        
        // Queue media message with appropriate delay
        SendWhatsAppMediaMessage::dispatch(
            $phoneNumber,
            $filePath,
            $mediaType,
            $caption,
            Auth::id(),
            $instance->instance_id,
            ['filename' => $attachment['filename']]
        )->delay(now()->addSeconds($delay));
        
        Log::info('Queued WhatsApp media message', [
            'phone' => $phoneNumber,
            'media_type' => $mediaType,
            'file_path' => $filePath,
            'delay' => $delay
        ]);
    }

    /**
     * Determine media type from MIME type
     */
    private function getMediaType($mimeType)
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif (strpos($mimeType, 'video/') === 0) {
            return 'video';
        } elseif (strpos($mimeType, 'audio/') === 0) {
            return 'audio';
        } else {
            return 'document';
        }
    }

    /**
     * Personalize message with user data
     */
    private function personalizeMessage($message, $user)
    {
        $datediff = time() - strtotime(Auth::user()->event->date);
        // Event payment tracking removed - focusing on contact management
        $paid_amount = 0;
        
        $replacements = [
            '#name' => $user->guest_name ?? 'Valued Customer',
            '#pledge' => $user->guest_pledge ?? '0',
            '#paid_amount' => $paid_amount,
            '#balance' => ((float) $paid_amount - (float) ($user->guest_pledge ?? 0)),
            '#days_remain' => round($datediff / (60 * 60 * 24))
        ];

        return $this->getCleanSms(
            array_values($replacements), 
            $message, 
            array_keys($replacements)
        );
    }

    public function sendContentToUsers($users, $message)
    {
        // This method is kept for backward compatibility but now uses queues
        $this->queueMessages($users, $message, ['whatsapp']);
    }

    public function storeMessage($message, $id, $source, $default_instance_id = null)
    {
        // Store the message in the database
        $messages = \App\Models\Message::firstOrCreate([
            'body' => $message,
            'user_id' => Auth::check() ? Auth::user()->id : 1,
            'type' => ($source === 'whatsapp' || $source === 2) ? 2 : 1, //'1=whatsapp, 2=bulksms';
            'phone' => str_replace('@c.us', NULL, $id)
        ]);
        $phone = str_replace('@c.us', NULL, $id);

        $file_source = null;
        if (request()->hasFile('files')) {
            $uploadedFiles = request()->file('files');
            if (is_array($uploadedFiles)) {
                // Only handle the first file for attachment (or adjust as needed)
                $file = $uploadedFiles[0];
            } else {
                $file = $uploadedFiles;
            }
            if ($file && $file->isValid()) {
                $file_source = $file->store('attachments', 'public');
            }
        }
        // Get instance_id from messageInstances where type equals $source
        $instance_id = $default_instance_id == null
            ? (is_int($source)
                ? Auth::user()->whatsappInstances()->where('connect_status', 'ready')->value('instance_id')
                : Auth::user()->whatsappInstances()->where('status', 'active')->value('instance_id'))
            : $default_instance_id;

      
        return \App\Models\MessageSentby::create(['message_id' => $messages->id, 'channel' => $source]);
    }



    //     $messages = \App\Models\Message::firstOrCreate([
    //         'body' => $message,
    //         'user_id' => Auth::check() ? Auth::user()->id : 1,
    //         'type' => ($source === 'whatsapp' || $source === 2) ? 2 : 1,
    //         'phone' => str_replace('@c.us', NULL, $id)
    //     ]);
    //     $phone = str_replace('@c.us', NULL, $id);

    //     $file_source = null;
    //     if (request()->hasFile('files')) {
    //         $uploadedFiles = request()->file('files');
    //         if (is_array($uploadedFiles)) {
    //             // Only handle the first file for attachment (or adjust as needed)
    //             $file = $uploadedFiles[0];
    //         } else {
    //             $file = $uploadedFiles;
    //         }
    //         if ($file && $file->isValid()) {
    //             $file_source = $file->store('attachments', 'public');
    //         }
    //     }
    //     // Get instance_id from messageInstances where type equals $source
    //     $instance_id = $default_instance_id == null ?
    //         Auth::user()->messageInstances()->where('type', $source)->value('instance_id') : $default_instance_id;


    //     return \App\Models\MessageSentby::create(['message_id' => $messages->id, 'channel' => $source]);
    // }


    public function send($message, $phoneNumber, $userId = null, $messageTypeHint = null)
    {
        // Determine effective user ID
        $effectiveUserId = $userId ?? (Auth::check() ? Auth::id() : null);
        
        try {
            // First check if this is a system message that should always use system default
            $messageType = $this->determineSystemMessageType($message, $effectiveUserId, $messageTypeHint);
            $systemMessageTypes = ['otp_verification', 'welcome_message', 'payment_reminder', 'password_reset', 'system_notification'];
            $isSystemMessage = in_array($messageType, $systemMessageTypes);
            
            // If this is a system message (like OTP), go directly to system default
            if ($isSystemMessage) {
                Log::info('Using system default for system message', [
                    'message_type' => $messageType,
                    'phone' => $phoneNumber,
                    'user_id' => $effectiveUserId
                ]);
                
                // Use system default instance via SystemWhatsAppService
                $systemService = app(SystemWhatsAppService::class);
                
                if ($systemService->isAvailable()) {
                    // Get system instance for validation
                    $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
             
                    if ($systemInstance && $systemInstance->canSendMessageType($messageType)) {
                        // Use appropriate SystemWhatsAppService method based on message type
                        $result = match($messageType) {
                            'otp_verification' => $systemService->sendGenericMessage($phoneNumber, $message, 'otp_verification'),
                            'welcome_message' => $systemService->sendGenericMessage($phoneNumber, $message, 'welcome_message'),
                            'payment_reminder' => $systemService->sendGenericMessage($phoneNumber, $message, 'payment_reminder'),
                            'password_reset' => $systemService->sendGenericMessage($phoneNumber, $message, 'password_reset'),
                            default => $systemService->sendGenericMessage($phoneNumber, $message, 'system_notification')
                        };

                        Log::info('Message sent via system default WhatsApp instance', [
                            'phone' => $phoneNumber,
                            'user_id' => $effectiveUserId,
                            'message_type' => $messageType,
                            'result' => $result
                        ]);
                        
                        return [
                            'success' => $result,
                            'message' => $result ? 'Message sent via system instance' : 'Failed to send via system instance',
                            'method' => 'system_default',
                            'message_type' => $messageType
                        ];
                    }
                }
                
                Log::error('System WhatsApp instance not available for system message', [
                    'user_id' => $effectiveUserId,
                    'phone' => $phoneNumber,
                    'message_type' => $messageType,
                    'system_available' => $systemService->isAvailable()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'System WhatsApp instance not available',
                    'error_code' => 'SYSTEM_INSTANCE_UNAVAILABLE'
                ];
            }
            
            // For regular user messages, try to get user's WhatsApp instance
            $waSenderService = new WaSenderService();
            $instance = null;
            if ($effectiveUserId) {
                $instance = $waSenderService->getUserInstance($effectiveUserId);
            }
              
            // If no user instance found, fall back to system default instance for regular messages
            if (!$instance) {
                Log::info('No user WhatsApp instance found for regular message, attempting system default', [
                    'user_id' => $effectiveUserId,
                    'phone' => $phoneNumber
                ]);
                
                // Try to use system default instance via SystemWhatsAppService for regular messages
                $systemService = app(SystemWhatsAppService::class);
                
                if ($systemService->isAvailable()) {
                    // For regular messages without user instance, treat as system notification
                    $fallbackMessageType = 'system_notification';
                    
                    // Get system instance for validation
                    $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
             
                    if ($systemInstance && $systemInstance->canSendMessageType($fallbackMessageType)) {
                        $result = $systemService->sendGenericMessage($phoneNumber, $message, $fallbackMessageType);

                        Log::info('Regular message sent via system default WhatsApp instance', [
                            'phone' => $phoneNumber,
                            'user_id' => $effectiveUserId,
                            'message_type' => $fallbackMessageType,
                            'result' => $result
                        ]);
                        
                        return [
                            'success' => $result,
                            'message' => $result ? 'Message sent via system instance' : 'Failed to send via system instance',
                            'method' => 'system_default',
                            'message_type' => $fallbackMessageType
                        ];
                    }
                }
                
                Log::error('No WhatsApp instance available (user or system)', [
                    'user_id' => $effectiveUserId,
                    'phone' => $phoneNumber,
                    'system_available' => $systemService->isAvailable()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No WhatsApp instance available for messaging',
                    'error_code' => 'NO_INSTANCE_AVAILABLE'
                ];
            }
          
            // Send using user's instance via WaSender service
            $result = $waSenderService->sendTextMessage(
                $phoneNumber,
                $message,
                $instance->instance_id,
                $effectiveUserId
            );
            
            Log::info('WhatsApp message sent via user instance', [
                'phone' => $phoneNumber,
                'user_id' => $effectiveUserId,
                'instance_id' => $instance->instance_id,
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'user_id' => $effectiveUserId
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SEND_FAILED'
            ];
        }
    }

    /**
     * Send system message using SystemWhatsAppService with proper message type detection
     */
    private function sendSystemMessage($phoneNumber, $message, $userId = null, $messageTypeHint = null)
    {
        try {
            $systemService = app(SystemWhatsAppService::class);
            
            if (!$systemService->isAvailable()) {
                return [
                    'success' => false,
                    'message' => 'System WhatsApp instance not available',
                    'error_code' => 'SYSTEM_INSTANCE_UNAVAILABLE'
                ];
            }
            
            // Determine message type
            $messageType = $this->determineSystemMessageType($message, $userId);
            
            // Send appropriate message type
            $result = match($messageType) {
                'otp_verification' => $systemService->sendSystemNotification($phoneNumber, 'OTP Verification', $message),
                'welcome_message' => $systemService->sendSystemNotification($phoneNumber, 'Welcome', $message),
                'payment_reminder' => $systemService->sendSystemNotification($phoneNumber, 'Payment Notice', $message),
                'password_reset' => $systemService->sendSystemNotification($phoneNumber, 'Password Reset', $message),
                default => $systemService->sendSystemNotification($phoneNumber, 'Notification', $message)
            };
            
            return [
                'success' => $result,
                'message' => $result ? 'Message sent via system instance' : 'Failed to send via system instance',
                'method' => 'system_default',
                'message_type' => $messageType
            ];
            
        } catch (\Exception $e) {
            Log::error('System message sending failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SYSTEM_SEND_FAILED'
            ];
        }
    }

    /**
     * Determine the appropriate system message type based on context
     */
    private function determineSystemMessageType($message, $userId = null, $messageTypeHint = null)
    {
        // If a specific message type hint is provided (from sendTextMessage calls), use it
        if ($messageTypeHint) {
            // Handle legacy parameter mappings
            switch ($messageTypeHint) {
                case 'reset_pass':
                    return 'password_reset';
                case 'otp':
                case 'otp_verification':
                    return 'otp_verification';
                case 'welcome':
                    return 'welcome_message';
                case 'payment':
                    return 'payment_reminder';
                default:
                    // If it's already a valid system message type, use it
                    if (in_array($messageTypeHint, ['password_reset', 'otp_verification', 'welcome_message', 'payment_reminder', 'system_notification'])) {
                        return $messageTypeHint;
                    }
            }
        }
        
        // Convert message to lowercase for pattern matching
        $messageText = strtolower($message);
        
        // OTP patterns
        if (preg_match('/(verification code|otp|verify|code|\d{4,6})/', $messageText)) {
            return 'otp_verification';
        }
        
        // Welcome patterns
        if (preg_match('/(welcome|greeting|hello|hi|thank you for joining)/', $messageText)) {
            return 'welcome_message';
        }
        
        // Payment patterns
        if (preg_match('/(payment|invoice|bill|amount|due|balance|remind)/', $messageText)) {
            return 'payment_reminder';
        }
        
        // Password reset patterns
        if (preg_match('/(password|reset|forgot|change password)/', $messageText)) {
            return 'password_reset';
        }
        
        // System notification patterns (default)
        return 'system_notification';
    }
    
    /**
     * Send media message via WhatsApp using WaSender
     * 
     * @param string $phoneNumber Phone number
     * @param string $mediaUrl Media file URL or path
     * @param string $mediaType Media type (image, document, audio, video)
     * @param string|null $caption Optional caption
     * @param array $additionalData Additional data for specific media types
     * @param int|null $userId User ID
     * @return array Response result
     */
    public function sendMediaMessage(string $phoneNumber, string $mediaUrl, string $mediaType, ?string $caption = null, array $additionalData = [], ?int $userId = null): array
    {
        try {
            $waSenderService = new WaSenderService();
            $effectiveUserId = $userId ?? (Auth::check() ? Auth::id() : null);
            
            // Get user's WhatsApp instance
            $instance = null;
            if ($effectiveUserId) {
                $instance = $waSenderService->getUserInstance($effectiveUserId);
            }
            
            if (!$instance) {
                Log::warning('No user WhatsApp instance for media message, system default not supported for media', [
                    'user_id' => $effectiveUserId,
                    'phone' => $phoneNumber,
                    'media_type' => $mediaType
                ]);
                
                return [
                    'success' => false,
                    'message' => 'No WhatsApp instance available for media messaging',
                    'error_code' => 'NO_MEDIA_INSTANCE_AVAILABLE'
                ];
            }
            
            $result = null;
            
            // Send based on media type
            switch ($mediaType) {
                case 'image':
                    $result = $waSenderService->sendImage(
                        $phoneNumber,
                        $mediaUrl,
                        $caption,
                        $instance->instance_id,
                        $userId ?? Auth::id()
                    );
                    break;
                    
                case 'document':
                    $result = $waSenderService->sendDocument(
                        $phoneNumber,
                        $mediaUrl,
                        $additionalData['filename'] ?? null,
                        $caption,
                        $instance->instance_id,
                        $userId ?? Auth::id()
                    );
                    break;
                    
                case 'audio':
                    $result = $waSenderService->sendAudio(
                        $phoneNumber,
                        $mediaUrl,
                        $instance->instance_id,
                        $userId ?? Auth::id()
                    );
                    break;
                    
                case 'video':
                    $result = $waSenderService->sendVideo(
                        $phoneNumber,
                        $mediaUrl,
                        $caption,
                        $instance->instance_id,
                        $userId ?? Auth::id()
                    );
                    break;
                    
                default:
                    throw new Exception("Unsupported media type: {$mediaType}");
            }
            
            Log::info('WhatsApp media message sent via WaSender', [
                'phone' => $phoneNumber,
                'media_type' => $mediaType,
                'user_id' => $userId ?? Auth::id(),
                'result' => $result
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp media message via WaSender', [
                'phone' => $phoneNumber,
                'media_type' => $mediaType,
                'error' => $e->getMessage(),
                'user_id' => $userId ?? Auth::id()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'MEDIA_SEND_FAILED'
            ];
        }
    }


    public function sendCustomEmail($id, $sms, $message_sentby_id = null)
    {
        $return = 0;

        if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
            try {

                $link = url('/');
                $data = ['content' => isset($sms->body) ? $sms->body : '', 'link' => $link, 'photo' => isset($sms->photo) ?? '', 'sitename' => 'SafariChat', 'name' => isset($sms->name) ? $sms->name : ''];
                $message = (object) ['sitename' => 'SafariChat', 'email' => $id, 'subject' => isset($sms->subject) ? $sms->subject : 'SafariChat Email'];
                $return = \Mail::send('auth.email.default', $data, function ($m) use ($message) {
                    $m->from('info@co.tz', $message->sitename);
                    $m->to($message->email)->subject($message->subject);
                });
            } catch (\Exception $e) {
                $return = $e->getMessage();
            }
        }
        $message_sentby_id != NULL ?
            \App\Models\MessageSentby::where('id', $message_sentby_id)->update(['status' => 1, 'return_code' => json_encode($return), 'updated_at' => 'now()']) : '';
        return $return;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function process()
    {
        // Process pending regular messages
        $pending = DB::select('SELECT b.channel,a.email,a.phone, b.id as message_sentby_id, a.body,a.subject, a.user_id FROM messages a join messages_sentby b on a.id=b.message_id  where return_code is null limit 100');
      
        if (!empty($pending)) {
            foreach ($pending as $message) {
                if ($message->channel <> 'email') {
                    $chat_id = validate_phone_number($message->phone)[1] ;
                    
                    try {
                        $result = $this->send($message->body, $chat_id, $message->user_id);
                        
                        // Update return_code based on send result
                        $returnCode = $result['success'] ? 'sent' : 'failed';
                        
                        DB::update('UPDATE messages_sentby SET return_code = ?, updated_at = ? WHERE id = ?', [
                            $returnCode,
                            now(),
                            $message->message_sentby_id
                        ]);
                        
                        \Log::info('Message processed', [
                            'message_sentby_id' => $message->message_sentby_id,
                            'phone' => $message->phone,
                            'status' => $returnCode,
                            'success' => $result['success']
                        ]);
                        
                    } catch (\Exception $e) {
                        // Mark as failed if exception occurs
                        DB::update('UPDATE messages_sentby SET return_code = ?, updated_at = ? WHERE id = ?', [
                            'error',
                            now(),
                            $message->message_sentby_id
                        ]);
                        
                        \Log::error('Failed to process message', [
                            'message_sentby_id' => $message->message_sentby_id,
                            'phone' => $message->phone,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        // Note: Sales outreach is now handled by DailyOutreachCommand (scheduled twice daily)
        // Note: Follow-ups are now handled by dedicated scheduled-followups task
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function channelStatus()
    {
        $channels = request('key');
        $results = [];
        if (!empty($channels)) {
            foreach ($channels as $channel) {
                if ($channel == 'phone-sms') {
                    //check if phone is connected and can send SMS
                    $phone_status = $this->checkChannelStatus('phone-sms');
                    array_push($results, [
                        'channel' => $channel,
                        'message' => strlen($phone_status) > 10 ?
                            '<p class="alert alert-warning">' . $phone_status . '</p>' :
                            ''
                    ]);
                }
                if ($channel == 'whatsapp') {
                    //check if whatsapp is active and can send SMS
                    // $booking =\App\Models\AdminBooking::where([
                    //                                                 'admin_package_id' => 4,
                    //                                                 'user_id' => Auth::user()->id,
                    //                                             ])->first();
                    $booking = Auth::user()->whatsappInstances()->where('status', 'active')->first();
                    $link = !empty($booking) ? 'whatsapp' : 'paywhatsappModal';
                    $whatsapp = $this->checkChannelStatus('whatsapp');
                    $whatsapp == FALSE ?
                        array_push($results, [
                            'channel' => $channel,
                            'message' => '<p class="alert alert-danger" style="cursor:pointer" role="alert" data-toggle="modal" data-target="#' . $link . '">Please '
                                . '<span class="badge bg-green" data-toggle="modal" '
                                . 'data-target=".bs-whatsapp-modal-lg">make payments</span> '
                                . 'and connect your whatsapp number with SafariChat to proceed</p>'
                        ]) : array_push($results, [
                            'channel' => 'whatsapp',
                            'message' => ''
                        ]);
                }

                if ($channel == 'bulksms') {
                    //check if user has got a balance to be a send SMS
                    $message_left = $this->checkChannelStatus('quick-sms');
                    $if_less = (int) $message_left == 0 ? '' : ' Or Consider to send less than ' . (int) $message_left . ' SMS';
                    array_push($results, [
                        'channel' => $channel,
                        'message' => (int) $message_left < 100 ?
                            '<p class="alert alert-danger" style="cursor:pointer" role="alert" data-toggle="modal" data-target="#messaging_request">You have left with ' . (int) $message_left . ' SMS balance, kindly 
                                        Add More SMS ' . $if_less . ' </p>' :
                            ''
                    ]);
                }
            }
        } else {
            $channels = [];
        }
        if (!in_array('whatsapp', $channels)) {
            array_push($results, [
                'channel' => 'whatsapp',
                'message' => ''
            ]);
        }
        if (!in_array('phone-sms', $channels)) {
            array_push($results, [
                'channel' => 'phone-sms',
                'message' => ''
            ]);
        }
        if (!in_array('quick-sms', $channels)) {
            array_push($results, [
                'channel' => 'quick-sms',
                'message' => ''
            ]);
        }
        echo json_encode($results);
    }

    public function checkChannelStatus($key, $user_id = null)
    {
        $user_id = $user_id == null ? Auth::user()->id : $user_id;
        if ($key == 'whatsapp') {
            //check addons payments
            // $addons = DB::table('admin_packages_payments')->whereIn('admin_payment_id', \App\Models\AdminPayment::whereUserId($user_id)->get(['id']))
            //                 ->whereIn('admin_package_id', \App\Models\AdminPackage::whereName('whatsapp')->get(['id']))
            //                 ->where('end_date', '>=', date('Y-m-d H:i', time()))->first();
            $addons = Auth::user()->whatsappInstances()->where('status', 'active')->first();

            return empty($addons) ? FALSE : ($addons->status == 'active');
        }
        if ($key == 'quick-sms') {
            // Use the new credits system
            $user = \App\Models\User::find($user_id);
            return $user ? $user->available_credits : 0;
        }
        if ($key == 'phone-sms') {
            // Mobile SMS functionality removed - users_keys table no longer exists
            return 'Mobile SMS functionality has been removed';
        }
            if (empty($verify)) {
                return $message = 'Seems your mobile phone is not active, kindly download a mobile app and login first before you send message';
            } else {
                //check when was active
                $try_period = $verify->last_active;
                $now = time();
                $your_date = strtotime($try_period);
                $datediff = $now - $your_date;
                $days = round($datediff / (60 * 60 * 24));
                $when = $try_period == null ? '' : 'Since ' . date('d M Y H:i', strtotime($try_period));
                return (int) $days > 1 ? 'Seems your mobile phone is not active ' . $when . ', kindly download or OPEN a mobile app and login first before you send message' : TRUE;
            }
    }

    public function addChannel()
    {
        $channel = request('channel');
        $user_id = Auth::user()->id;
        $status = $this->checkChannelStatus($channel, $user_id);
        if ($status == FALSE) {
            return redirect()->back()->with('error', 'You have not activated ' . $channel . ' channel, please activate it first');
        }
        if ($channel == 'whatsapp') {
            return redirect()->route('message.channel');
        } else {
            return redirect()->back();
        }
    }


    public function sent()
    {
        $channel = request()->segment(3);
        if ($channel == null) {
            $this->data['messages'] = \App\Models\Message::whereUserId(Auth::user()->id)->get();
        } else {
            $this->data['guests'] = \App\Models\BusinessContact::where('business_id', Auth::user()->business->id)->get();
            $this->data['messages'] = \App\Models\Message::whereUserId(Auth::user()->id)->where('type', $channel)->get();
        }

        return view('message.sent', $this->data);
    }

    public function deleteReminder()
    {
        $id = request()->segment(3);
        \App\Models\Reminder::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'success');
    }

    public function background() {}

    public function createWhatsAppInvoices()
    {
        $this->validate(request(), [
            'phone_number' => 'required',
        ], $this->custom_validation_message);

        $phone = $this->validatePhone(request('phone_number'));

        if (!$phone) {
            return redirect()->back()->with('error', 'Invalid phone number format. Please enter a valid phone number.');
        }

        try {
            $requested_free_trail = request('free_trial');

            $free_trial = [
                'free_trial' => false,
                'free_trial_end_date' => date('Y-m-d', strtotime('+3 days')),
            ];

            DB::beginTransaction();
            $data = [
                'status' => 0,
                'created_at' => now(),
                'schema_name' => SCHEMA_NAME,
                'phone_number' => $phone[1],
            ];
            if ($requested_free_trail == 'on') {
                $data = array_merge($data, $free_trial);
            }
            DB::table('shulesoft.whatsapp_instances')->insert($data);

            $existing = MessageChannel::whereRaw('LOWER(name) = ?', [strtolower('whatsapp')])->first();
            if ($existing) {
                $existing->update(['status' => 1, 'username' => $phone[1]]);
            } else {
                MessageChannel::create([
                    'name' => 'whatsapp',
                    'status' => 1,
                    'username' => $phone[1]
                ]);
            }
            if ($requested_free_trail != 'on') {

                $addon = DB::table('admin.addons')->whereRaw('LOWER(name) = ?', [strtolower('whatsapp')])->first();
                $amount = $addon?->price;
                $description = 'Whatsapp Integration payment';

                $reference = $this->createAddonInvoice(4, $amount, $description);
                if (!$reference) {
                    throw new \Exception('Failed to create invoice');
                }
                DB::commit();
                return redirect(base_url('message/set'))
                    ->with('success', 'Invoice created successfully. Pay Via CRDB (Bank/Agent/Mobile App) with control Number and continue with the integration after payment: ' . $reference);
            } else {
                DB::commit();
                return redirect(base_url('message/set'))
                    ->with('success', ' Three days free trial request created successfully. Please pair the instance with your phone.');
            }
        } catch (\Exception $e) {
            DB::rollback();
            return redirect(base_url('message/set'))
                ->with('error', 'Failed to create invoice. Please try again.');
        }
    }


    public function createInstance()
    {
        try {
            $pendingInstance = \App\Models\WhatsappInstance::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->first();

            if (empty($pendingInstance)) {
                return redirect()->back()->with('error', 'You have no pending application. Please enable whatsapp integration.');
            }
            $ch = curl_init('https://waapi.app/api/v1/instances');

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'accept: application/json',
                'authorization: Bearer ' . $this->token,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            if ($httpCode == 201) { //created
                $responseData = json_decode($response, true);

                // Update the pending message instance with the new instance details
                $pendingInstance->update([
                    'instance_id' => $responseData['instance']['id'],
                    'owner' => $responseData['instance']['owner'],
                    'name' => $responseData['instance']['name'],
                    'updated_at' => now()
                ]);
                return redirect('message/getInstances')->with('success', 'WhatsApp instance created successfully. Please pair the instance with your phone.');
            } else {
                return redirect()->back()->with('error', 'Failed to create WhatsApp instance');
            }
        } catch (\Exception $e) {
            Log::error('Error creating instance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to request whatsapp instance');
        }
    }

    public function requestPairCode()
    {
        $instance_id = request()->segment(3);

        $instance = \App\Models\WhatsappInstance::where('instance_id', $instance_id)->first();

        if (!$instance) {
            return response()->json([
                'status' => 'error',
                'message' => 'Instance not found in database. Please create instance first.'
            ], 404);
        }

        $phone_number = str_replace('+', '', $instance->phone_number);

        $curl = curl_init();

        $url = $this->baseUrl . $instance_id . '/client/action/request-pairing-code';

        // JSON-encoded request data
        $data = json_encode([
            'phoneNumber' => $phone_number
        ]);
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request failed',
                'error' => $error
            ], $httpCode);
        } elseif ($httpCode !== 200) {
            return response()->json([
                'status' => 'error',
                'error' => $httpCode,
                'message' => 'Request failed'
            ], $httpCode);
        } else {
            $responseData = json_decode($response, true);

            $status = $responseData['data']['status'] ?? null;
            \Log::info('Pairing status response', ['status' => json_encode($status)]);
            // $instanceId = $responseData['data']['instanceId'] ?? null;

            if ($responseData['data']['status']  == 'success') {
                $pairingCode = $responseData['data']['data']['pairingCode'] ?? null;

                \App\Models\WhatsappInstance::where('instance_id', $instance_id)->update([
                    'connect_status' => 'qr', 
                    'pairing_code' => $pairingCode
                ]);

                return response()->json([
                    'status' => $status,
                    'code' => $pairingCode,
                    'message' => '',
                    'qr' => ''
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => $responseData['data']['explanation']
                ], 200);
            }
        }
    }
    public function getinstancestatus()
    {
        $instance_id = request('instance_id');
        return $this->checkStatus($instance_id);
    }

    public function finalizePairing()
    {
        $instance_id = request('instance_id');
        return $this->checkStatus($instance_id, true);
    }
    public function checkStatus($instance_id, $final = false)
    {
        $url = $this->baseUrl . $instance_id . '/client/status';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'authorization: Bearer ' . $this->token,
            ],
        ]);
        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);

                if (isset($responseData['clientStatus'])) {
                    $instanceStatus = $responseData['clientStatus']['instanceStatus'];
                    $data = [
                        'connect_status' =>  $instanceStatus,
                        'updated_at' => now(),
                        'webhook_url' => $responseData['clientStatus']['instanceWebhook'] ?? null,
                    ];
                    if ($final && $instanceStatus == 'ready') {
                        $data = $data + ['status' => 'active'];
                        \App\Models\WhatsappInstance::where('instance_id', $instance_id)
                            ->update($data);

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Pairing successful. You can now use the WhatsApp instance.',
                            'clientStatus' => $responseData['clientStatus']
                        ]);
                    } elseif ($final && $instanceStatus != 'ready') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Finalizing pairing failed. Please try again or try to requesrt a new pairing code.',
                        ]);
                    } else {
                        \App\Models\WhatsappInstance::where('instance_id', $instance_id)
                            ->update($data);

                        return response()->json([
                            'status' => 'success',
                            'clientStatus' => $responseData['clientStatus']
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid response format'
                ], 400);
            }
            curl_close($ch);

            return response()->json([
                'status' => 'error',
                'message' => 'Request failed'
            ], $httpCode);
        } catch (\Exception $e) {
            Log::error('Error getting instance status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error in Pairing phone code'
            ], 500);
        }
    }

    function LogoutInstance($instance_id)
    {

        $url = $this->baseUrl . $instance_id . '/client/action/logout';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->token,
            ],
        ]);
        try {
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);
            if ($httpCode == 200) {
                $responseData = json_decode($response, true);

                $status = $responseData['data']['status'];
                if ($status == 'success') {
                    $data = [
                        'connect_status' =>  'qr',
                        'updated_at' => now(),
                        'status' => 'pending'
                    ];
                    \App\Models\WhatsappInstance::where('instance_id', $instance_id)
                        ->update($data);

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Successfully Logged out. You can request new pairing code and reconnect again.',
                    ]);
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Logout failed. Please try again.',
                    ]);
                }
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Request failed'
            ], $httpCode);
        } catch (\Exception $e) {
            Log::error('Error in while logging out: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error in while logging out'
            ], 500);
        }
    }

    public function transactions()
    {
        $transactions = DB::connection('shulesoft')->table('admin.addon_invoices')
            ->where('schema_name', Auth::user()->username)
            ->get();

    if ($transactions->isEmpty()) {
        return redirect()->back()->with('error', 'No transactions found for your account.');
    }
        $this->data['transactions'] = $transactions;

        return view('auth.transactions', $this->data);
    }

    public function whatsappGroup(){
        $this->data['groups'] = [];
       // \App\Models\MessageGroup::where('user_id', Auth::user()->id)->get();
        return view('message.group', $this->data);
    }

    public function createNewInstance(\Illuminate\Http\Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'instance_id' => 'required',
                'type' => 'required|string',
                'name' => 'required|string|max:255',
                'owner' => 'required|string|max:255',
                'user_id' => 'required|integer|exists:users,id',
                'connect_status' => 'required|string',
                'phone_number' => 'required',
                'webhook_url' => 'nullable|string',
                'webhook_events' => 'nullable|string',
                'status' => 'required|string'
            ]);

         
            // Create the instance using the WhatsappInstance model
            $instance = new \App\Models\WhatsappInstance();
            $instance->instance_id = $request->instance_id;
            $instance->instance_name = $request->name ?? 'WhatsApp Instance';
            $instance->user_id = $request->user_id;
            $instance->connect_status = $request->connect_status ?? 'pending';
            $phone_number = preg_replace('/\s+/', '', ltrim($request->phone_number, '+'));
            $instance->phone_number = ($request->country_code ?? '') . $phone_number;
            $instance->webhook_url = $request->webhook_url;
            $instance->status = $this->mapRequestStatusToWhatsappInstanceStatus($request->status ?? 'pending');
            $instance->created_at = now();
            $instance->updated_at = now();
            
            $instance->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Instance saved successfully',
                'instance' => $instance
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error saving instance: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save instance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Map request status values to WhatsappInstance status values
     */
    private function mapRequestStatusToWhatsappInstanceStatus($requestStatus)
    {
        if (is_numeric($requestStatus)) {
            return $requestStatus == 1 ? 'active' : 'pending';
        }
        
        return in_array($requestStatus, ['pending', 'connecting', 'connected', 'active', 'disconnected', 'error', 'suspended']) 
            ? $requestStatus 
            : 'pending';
    }
    
    /**
     * Process event guests for automated sales outreach
     */
    private function processEventGuestsForSales()
    {
        try {
            // Get event guests that haven't been contacted for sales yet
            $newGuests = \App\Models\BusinessContact::where(function($query) {
                    $query->where('contacted_for_sales', false)
                          ->orWhereNull('contacted_for_sales');
                })
                ->whereNotNull('guest_phone')
                ->where('guest_phone', '!=', '')
                ->limit(10) // Process in batches to prevent overload
                ->get();

            \Log::info('Processing event guests for sales', ['count' => $newGuests->count()]);

            foreach ($newGuests as $guest) {

                   // Get active AI sales agent for this user
                $aiAgent = \App\Models\AiSalesAgent::where('user_id', $guest->user_id)
                                                 ->where('status', 'active')
                                                 ->first();


                if ($aiAgent) {
                              // Find or create lead for this guest
                $lead = \App\Models\Lead::firstOrCreate(
                    ['business_contact_id' => $guest->id],
                    [
                        'business_id' => $guest->business_id,
                        'source' => 'event_guest',
                        'status' => 'NEW',
                        'ai_sales_agent_id'=>$aiAgent->id,
                        'last_interaction_at' => now(),
                        'conversion_probability' => 0,
                        'lead_score' => 0,
                        'is_churned' => false,
                        'win_back_attempts' => 0
                    ]
                );

                
                    // Send initial sales message
                    $this->sendInitialSalesMessage($lead, $aiAgent, $guest);
                    
                    // Mark guest as contacted
                    $guest->update(['contacted_for_sales' => true, 'contacted_at' => now()]);
                    
                    \Log::info('Sales message sent to event guest', [
                        'guest_id' => $guest->id,
                        'lead_id' => $lead->id,
                        'phone' => $guest->guest_phone
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error processing event guests for sales', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Send initial AI-generated sales message to a lead
     */
    private function sendInitialSalesMessage($lead, $aiAgent, $guest = null)
    {
        try {
            // Get user's products for personalization
            $products = \App\Models\Product::where('user_id', $aiAgent->user_id)
                                         ->where('status', 'active')
                                         ->take(3)
                                         ->get();

            // Generate personalized sales message using AI agent configuration
            $message = $this->generatePersonalizedSalesMessage($lead, $aiAgent, $products, $guest);
            
            // Get user's WhatsApp instance
            $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', $aiAgent->user_id)
                                                           ->where('status', 'active')
                                                           ->first();
            
            if ($whatsappInstance && $message) {
                // Create conversation record for tracking
                $conversation = \App\Models\Conversation::create([
                    'lead_id' => $lead->id,
                    'ai_sales_agent_id' => $aiAgent->id,
                    'message' => $message,
                    'message_type' => 'outbound',
                    'sender_type' => 'ai_agent',
                    'created_at' => now()
                ]);
                
                // Queue the message for sending
                \App\Jobs\SendWhatsAppMessage::dispatch(
                    $message,
                    $lead->phone_number,
                    'whatsapp',
                    $aiAgent->user_id,
                    null, // no files
                    $whatsappInstance->instance_id
                );
                
                // Update lead status
                $lead->update([
                    'status' => 'contacted',
                    'last_contact_at' => now(),
                    'ai_sales_agent_id' => $aiAgent->id
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Error sending initial sales message', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return false;
    }
    
    /**
     * Generate personalized sales message using AI agent configuration
     * PHASE 3: Campaign-focused message generation
     */
    private function generatePersonalizedSalesMessage($lead, $aiAgent, $products, $guest = null)
    {
        // Get the SINGLE active campaign product
        $activeCampaignProduct = \App\Models\Product::where('user_id', $aiAgent->user_id)
                                                   ->where('is_active_campaign', true)
                                                   ->first();
        
        // If no active campaign, fallback to general products message
        if (!$activeCampaignProduct) {
            return $this->generateGeneralSalesMessage($lead, $aiAgent, $products, $guest);
        }
        
        // Get business credibility data
        $business = $aiAgent->user->business;
        $businessMission = $business ? ($business->mission ?? '') : '';
        $credibilityStats = $business ? ($business->credibility_statistics ?? '') : '';
        
        $name = $lead->name ?? 'there';
        $businessContext = '';
        
        if ($guest && $guest->business) {
            $businessContext = " {$guest->business->name}";
        }
        
        // PART 1: Context - Acknowledge user
        $greeting = $this->getGreetingByTone($aiAgent->communication_tone, $name);
        
        // PART 2: Hook - Problem + Solution using campaign data
        $painPoint = $activeCampaignProduct->campaign_pain_point;
        $hookText = $activeCampaignProduct->campaign_hook_text;
        $productName = $activeCampaignProduct->name;
        
        // Build hook based on tone
        $hook = $this->buildCampaignHook($aiAgent->communication_tone, $painPoint, $productName, $hookText);
        
        // PART 3: CTA & Attachment Offer
        $hasAttachment = !empty($activeCampaignProduct->campaign_attachment_path);
        $cta = $this->buildCTA($aiAgent->communication_tone, $hasAttachment, $productName);
        
        // Combine the three parts
        $baseMessage = "{$greeting} {$hook} {$cta}";
        
        // Add credibility if available (optional enhancement)
        if ($credibilityStats) {
            $credibilityLine = " ({$credibilityStats})";
            $baseMessage = str_replace($cta, $credibilityLine . ' ' . $cta, $baseMessage);
        }
        
        // Translate message based on AI agent's language preferences
        $targetLanguage = $this->determineTargetLanguage($aiAgent, $lead);
        
        if ($targetLanguage && $targetLanguage !== 'en') {
            $translatedMessage = $this->translateMessage($baseMessage, $targetLanguage, $aiAgent);
            return $translatedMessage ?: $baseMessage;
        }
        
        return $baseMessage;
    }
    
    /**
     * Generate greeting based on communication tone
     */
    private function getGreetingByTone($tone, $name)
    {
        return match($tone) {
            'professional' => "Hello {$name},",
            'friendly' => "Hi {$name}! 👋",
            'consultative' => "Hello {$name},",
            'direct' => "Hi {$name},",
            default => "Hello {$name},"
        };
    }
    
    /**
     * Build campaign hook using pain point and hook text
     */
    private function buildCampaignHook($tone, $painPoint, $productName, $hookText)
    {
        return match($tone) {
            'professional' => "{$painPoint} Our {$productName} offers a solution: {$hookText}",
            'friendly' => "{$painPoint} I've got great news! Our {$productName} can help: {$hookText}",
            'consultative' => "{$painPoint} I'd like to introduce you to our {$productName} - {$hookText}",
            'direct' => "{$painPoint} Solution: {$productName} - {$hookText}",
            default => "{$painPoint} We have a solution with our {$productName}: {$hookText}"
        };
    }
    
    /**
     * Build call-to-action with optional attachment offer
     */
    private function buildCTA($tone, $hasAttachment, $productName)
    {
        $baseQuestion = match($tone) {
            'professional' => "Would you be interested in learning more?",
            'friendly' => "What do you think? Want to know more?",
            'consultative' => "Would you like to explore how this could work for you?",
            'direct' => "Interested?",
            default => "Would you like more information?"
        };
        
        if ($hasAttachment) {
            $attachmentOffer = match($tone) {
                'professional' => " I can send you our detailed brochure with more information.",
                'friendly' => " I can share our brochure with all the details! 📄",
                'consultative' => " I have a comprehensive brochure I can share with you.",
                'direct' => " I can send the brochure.",
                default => " I have a brochure I can send you."
            };
            return $baseQuestion . $attachmentOffer;
        }
        
        return $baseQuestion;
    }
    
    /**
     * Fallback method for when no active campaign exists
     */
    private function generateGeneralSalesMessage($lead, $aiAgent, $products, $guest = null)
    {
        $name = $lead->name ?? 'there';
        $businessContext = '';
        
        if ($guest && $guest->business) {
            $businessContext = " I noticed you're interested in {$guest->business->name}.";
        }
        
        // Build product showcase
        $productList = '';
        if ($products->count() > 0) {
            $productNames = $products->pluck('name')->take(2)->implode(', ');
            $productList = " We offer {$productNames} and more.";
        }
        
        // Use AI agent's communication tone and personality
        $tone = $aiAgent->communication_tone;
        
        $baseMessage = match($tone) {
            'professional' => "Hello {$name},{$businessContext} I'm {$aiAgent->assistant_name} from our sales team.{$productList} Would you like to learn more about how our solutions can benefit you?",
            'friendly' => "Hi {$name}! 😊{$businessContext} I'm {$aiAgent->assistant_name}.{$productList} I'd love to chat about how we can help you. What do you think?",
            'consultative' => "Hello {$name},{$businessContext} I'm {$aiAgent->assistant_name}. I specialize in helping clients find the right solutions.{$productList} What challenges are you currently facing that I might be able to help with?",
            'direct' => "Hi {$name},{$businessContext} {$aiAgent->assistant_name} here.{$productList} Interested in learning more? Let me know!",
            default => "Hello {$name},{$businessContext} I'm {$aiAgent->assistant_name}.{$productList} I'd be happy to discuss how we can help you. Are you available for a quick chat?"
        };
        
        return $baseMessage;
    }
    
    /**
     * Determine the target language for the message
     */
    private function determineTargetLanguage($aiAgent, $lead)
    {
        // If auto-detect is enabled, try to detect from lead's previous messages or location
        if ($aiAgent->auto_detect_language) {
            // First check if lead has a preferred language from previous conversations
            $lastIncomingMessage = \App\Models\IncomingMessage::where('sender', $lead->phone)
                ->where('user_id', $aiAgent->user_id)
                ->latest()
                ->first();
                
            if ($lastIncomingMessage) {
                $detectedLanguage = $this->detectMessageLanguage($lastIncomingMessage->body);
                
                // Check if detected language is in agent's supported languages
                $supportedLanguages = array_merge(
                    [$aiAgent->primary_language], 
                    $aiAgent->additional_languages ?? []
                );
                
                if (in_array($detectedLanguage, $supportedLanguages)) {
                    return $detectedLanguage;
                }
            }
        }
        
        // Default to agent's primary language
        return $aiAgent->primary_language ?? 'en';
    }
    
    /**
     * Detect the language of a message using AI
     */
    private function detectMessageLanguage($messageText)
    {
        try {
            // Use OpenAI to detect language
            $openAiService = app(\App\Services\OpenAiService::class);
            
            $prompt = "Detect the language of this text and return only the 2-letter language code (e.g., 'en' for English, 'sw' for Swahili, 'fr' for French, etc.). If uncertain, return 'en'.\n\nText: {$messageText}";
            
            $response = $openAiService->generateResponse($prompt, 'You are a language detection expert.');
            
            // Extract language code from response
            $languageCode = strtolower(trim($response));
            
            // Validate it's a proper 2-letter code
            if (preg_match('/^[a-z]{2}$/', $languageCode)) {
                return $languageCode;
            }
            
        } catch (\Exception $e) {
            \Log::warning('Language detection failed', [
                'message' => $messageText,
                'error' => $e->getMessage()
            ]);
        }
        
        return 'en'; // Default fallback
    }
    
    /**
     * Translate message to target language using AI
     */
    private function translateMessage($message, $targetLanguage, $aiAgent)
    {
        try {
            // Language mapping for better context
            $languageNames = [
                'en' => 'English',
                'sw' => 'Swahili',
                'fr' => 'French',
                'es' => 'Spanish',
                'pt' => 'Portuguese',
                'ar' => 'Arabic',
                'de' => 'German',
                'it' => 'Italian',
                'zh' => 'Chinese',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'hi' => 'Hindi',
                'ur' => 'Urdu',
                'bn' => 'Bengali',
                'tr' => 'Turkish',
                'ru' => 'Russian',
                'nl' => 'Dutch',
                'sv' => 'Swedish',
                'da' => 'Danish',
                'no' => 'Norwegian',
                'fi' => 'Finnish',
                'pl' => 'Polish',
                'cs' => 'Czech',
                'sk' => 'Slovak',
                'hu' => 'Hungarian',
                'ro' => 'Romanian',
                'bg' => 'Bulgarian',
                'hr' => 'Croatian',
                'sr' => 'Serbian',
                'sl' => 'Slovenian',
                'et' => 'Estonian',
                'lv' => 'Latvian',
                'lt' => 'Lithuanian',
                'mt' => 'Maltese',
                'ga' => 'Irish',
                'cy' => 'Welsh',
                'eu' => 'Basque',
                'ca' => 'Catalan',
                'gl' => 'Galician',
                'is' => 'Icelandic',
                'mk' => 'Macedonian',
                'sq' => 'Albanian',
                'bs' => 'Bosnian',
                'me' => 'Montenegrin'
            ];
            
            $targetLanguageName = $languageNames[$targetLanguage] ?? ucfirst($targetLanguage);
            
            $openAiService = app(\App\Services\OpenAiService::class);
            
            $systemPrompt = "You are a professional translator specializing in business communications. Translate messages while maintaining the original tone, formality level, and cultural appropriateness. Keep the same communication style and preserve any emojis or formatting.";
            
            $prompt = "Translate this sales message to {$targetLanguageName}. Maintain the {$aiAgent->communication_tone} tone and keep it natural for business communication:\n\n{$message}";
            
            $translatedMessage = $openAiService->generateResponse($prompt, $systemPrompt);
            
            // Log successful translation for debugging
            \Log::info('Message translated successfully', [
                'from_language' => 'en',
                'to_language' => $targetLanguage,
                'original_length' => strlen($message),
                'translated_length' => strlen($translatedMessage),
                'agent_id' => $aiAgent->id
            ]);
            
            return trim($translatedMessage);
            
        } catch (\Exception $e) {
            \Log::error('Translation failed', [
                'message' => $message,
                'target_language' => $targetLanguage,
                'agent_id' => $aiAgent->id,
                'error' => $e->getMessage()
            ]);
            
            // Return fallback message if available
            if ($aiAgent->language_fallback_message) {
                return $aiAgent->language_fallback_message;
            }
            
            return null; // Will fall back to original English message
        }
    }
    
    /**
     * Check if nurture mode should be applied for a contact
     * 
     * @param mixed $user Contact object containing guest_phone
     * @param string $message Original message
     * @return bool Whether nurture mode was applied
     */
    private function applyNurtureModeIfNeeded($user, $message)
    {
        try {
            // Find contact by phone number
            $phoneNumber = validate_phone_number($user->guest_phone);
            if (!is_array($phoneNumber)) {
                return false;
            }
            
            $cleanPhone = $phoneNumber[1];
            
            // Find business contact — scope by business_id when available on the
            // incoming $user object so we don't cross business boundaries
            $contactQuery = \App\Models\BusinessContact::where(function($q) use ($cleanPhone) {
                $q->where('guest_phone', $cleanPhone)
                  ->orWhere('guest_phone', 'LIKE', '%' . substr($cleanPhone, -9));
            });
            if (!empty($user->business_id)) {
                $contactQuery->where('business_id', $user->business_id);
            }
            $contact = $contactQuery->first();
                
            if (!$contact) {
                Log::info("No contact found for nurture mode check", ['phone' => $cleanPhone]);
                return false;
            }
            
            // Check if ghosting
            $ghostingAnalysis = \App\Services\GhostingDetector::analyze($contact->id);
            
            if (!$ghostingAnalysis['is_ghosting']) {
                return false;
            }
            
            // Check if opted out
            if (\App\Services\GhostingDetector::hasOptedOut($contact->id)) {
                Log::info("Contact has opted out, skipping nurture mode", ['contact_id' => $contact->id]);
                return false;
            }
            
            // Create a message queue entry for nurture processing
            $queueEntry = \App\Models\MessageQueue::create([
                'user_id' => Auth::id(),
                'contact_id' => $contact->id,
                'phone_number' => $cleanPhone,
                'contact_name' => $contact->name ?? $user->guest_name,
                'original_message' => $message,
                'status' => 'staged',
                'detected_language' => $ghostingAnalysis['detected_language'] ?? 'en',
                'detected_tone' => $ghostingAnalysis['detected_tone'] ?? 'casual',
                'relationship_stage' => 'ghosting',
                'last_interaction_at' => $ghostingAnalysis['last_incoming_at'],
            ]);
            
            // Dispatch nurture job
            \App\Jobs\ProcessNurtureMessageJob::dispatch($queueEntry->id)
                ->onQueue('ai_nurture');
            
            Log::info("Nurture mode applied for contact", [
                'contact_id' => $contact->id,
                'queue_entry_id' => $queueEntry->id,
                'unanswered_count' => $ghostingAnalysis['unanswered_count']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error applying nurture mode", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Process scheduled follow-ups and reminders using smart AI
     */
    private function processScheduledFollowUps()
    {
        try {
            $smartFollowupService = app(\App\Services\SmartFollowupService::class);
            $smartFollowupService->processSmartFollowups();
        } catch (\Exception $e) {
            \Log::error('Error processing smart follow-ups', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
}
