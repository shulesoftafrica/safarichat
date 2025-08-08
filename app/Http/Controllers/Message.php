<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventGuestCategory;
use \App\Models\Message as SMS;
use \App\Models\EventsGuest;
use \App\Models\OutgoingMessage;
use \App\Jobs\SendWhatsAppMessage;
use \App\Jobs\ProcessBulkMessages;
use Illuminate\Support\Arr;
use Auth;
use DB;
use Illuminate\Support\Env;
use App\Models\AdminBooking;
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
        $this->baseUrl = 'https://waapi.app/api/v1/instances/';
        $this->token = 'j7BiHiAiUsJfuKN3b99rhEUzVEfEuhSICQo5LdNPbc240e88';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->data[''] = [];
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        if (Auth::user()->messageInstances()->where('type', 'whatsapp')->count() == 0) {
            // Create a new WhatsApp instance for the user
            \App\Models\MessageInstance::create([
                'user_id' => Auth::id(),
                'type' => 'whatsapp',
                'status' => 0,
                'phone_number' => ltrim(Auth::user()->phone, '+') ,
                'name' => Auth::user()->name,
            ]);
        }
        $this->data['guest_categories'] = EventGuestCategory::where('event_id', $event_id)->get();
          //check if the invoice has been paid for bulksms
        $this->checkBookedInvoicePayment();
        $this->data['whatsapp'] = $this->checkChannelStatus('whatsapp');
        $this->data['remained_sms'] = $this->checkChannelStatus('quick-sms');
        $this->data['phone_sms'] = $this->checkChannelStatus('phone-sms');
      
        return view('message.index', $this->data);
    }

    public function checkBookedInvoicePayment(){
        // Get all unpaid bookings for the current user
        $bookings = AdminBooking::where('user_id', Auth::id())
            ->where('status', 0)
            ->whereNotNull('reference')
            ->get();

        foreach ($bookings as $booking) {
            // Check if the invoice with the same reference is paid in shulesoft.admin.addon_invoice
            $invoice = DB::connection('shulesoft')
                ->table('admin.addon_invoices')
                ->where('reference', $booking->reference)
                ->where('status', 1)
                ->first();

            if ($invoice) {
                // Mark booking as paid
                $booking->status = 1;
                $booking->save();

                // Load payment details from shulesoft.admin.payments where invoice_id = $invoice->id
                $shulesoft_payment = DB::connection('shulesoft')
                    ->table('admin.payments')
                    ->where('invoice_id', $invoice->id)
                    ->first();

                if ($shulesoft_payment) {
                   $payment= \App\Models\AdminPayment::create([
                        'user_id' => $booking->user_id,
                        'amount' => $shulesoft_payment->amount,
                        'transaction_id' => $shulesoft_payment->transaction_id ?? '',
                        'method' => $shulesoft_payment->method ?? '',
                        'date' => $shulesoft_payment->date ?? now(),
                        'admin_booking_id' => $booking->id,
                    ]);
               

                //now check if this package is for bulksms or not
                if ($booking->admin_package_id == 5) {
                    $admin_packages_payment_id=DB::table('dikodiko.admin_packages_payments')->insertGetId([
                        'admin_payment_id' => $payment->id,
                        'admin_package_id' => $booking->admin_package_id,
                        'start_date' => now(),
                        'end_date' => now()->addYear()
                    ]);
                    DB::table('dikodiko.admin_sms_brought')->insert([
                        'admin_packages_payment_id' => $admin_packages_payment_id,
                        'sms_provided' => $invoice->quantity ?? 0,
                        'user_id' => $booking->user_id
                    ]);
                    $user = \App\Models\User::find($booking->user_id);
                    if ($user) {
                        // Send a message to the user informing about SMS allocation
                        $message = "Hello {$user->name}, SMS credits have been allocated to your account. You can now start sending messages using your DikoDiko account.";
                        // Use the send_message method to notify the user via SMS
                          $this->sendMessage($user->phone, $message, 'whatsapp');
                }
                }
            }
         }
        }
    }

    public function channel()
    {

        $this->data['instances'] = Auth::user()->messageInstances()->get();
       
        $instance = \App\Models\MessageInstance::where('user_id', Auth::user()->id)->where('type','bulksms')->first();
        if (empty($instance)) {
              $user = Auth::user();

              if($user->bulksms_enabled == 1){
               
            $instance = \App\Models\MessageInstance::create([
                'name' => substr(preg_replace('/[^A-Za-z0-9 ]/', '', Auth::user()->name), 0, 11),
                'user_id' => Auth::id(),
                'phone_number' => Auth::user()->phone,
                'status' => 0,
                'type' => 'bulksms',

            ]);
            // Send WhatsApp message to user about sender name creation and required details
          
            if ($user && !empty($user->phone)) {
                $waMessage = "Hello {$user->name}, your sender name has been created. To complete registration, please submit your NIDA number and an introduction letter for approval.";
                $this->sendMessage($user->phone, $waMessage, 'whatsapp');
            }
        }
        }  
         $this->checkBookedInvoicePayment();
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

                \App\Models\MessageInstance::create([
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

        $instance = \App\Models\MessageInstance::where('uuid', $uuid)->first();
        if (!$instance) {
            return redirect()->back()->with('error', 'Channel not found.');
        }
        //check if its whatsapp
        if ($instance->type === 'whatsapp' && !empty($instance->instance_id)) {
            $this->LogoutInstance($instance->instance_id);
        }
        $instance->delete();

        return redirect()->back()->with('success', 'Channel deleted successfully.');
    }
    public function schedule()
    {

        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;

        $this->data['usertypes'] = \App\Models\EventGuestCategory::where('event_id', $event_id)->get();

        if ($_POST) {
            $category_id = request('category_id');
            $criteria = strip_tags(request('criteria'));
            $users = $this->getUserByCriteria($criteria, $event_id, null, $category_id);
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
            //                        \App\Models\EventsGuest::where('event_id', $event_id)->get();
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
                'event_guest_category_id' => request('event_guest_category_id'),
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
        $category_id = request('category_id');
        $criteria = strip_tags(request('criteria'));
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        $users = $this->getUserByCriteria($criteria, $event_id, null, $category_id);
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
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        $user_id = Auth::id();

        // Real WhatsApp Business Data
        $this->data['sms_sent'] = DB::table('dikodiko.messages')->where('user_id', $user_id)->where('type', 2)->count();
        $this->data['whatsapp_sent'] = \App\Models\OutgoingMessage::where('user_id', $user_id)->count();
        
        // If no outgoing messages, fallback to old data
        if ($this->data['whatsapp_sent'] == 0) {
            $this->data['whatsapp_sent'] = DB::table('dikodiko.messages')->where('user_id', $user_id)->where('type', 4)->count();
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
        
        // Customer engagement metrics
        $this->data['total_contacts'] = \App\Models\EventsGuest::where('event_id', $event_id)->count();
        $this->data['contacts_messaged'] = \App\Models\OutgoingMessage::where('user_id', $user_id)
            ->distinct('phone_number')
            ->count();
            
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
            $this->data['reports'] = DB::table('dikodiko.messages')
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
        
        $this->data['schedules'] = DB::table('dikodiko.reminders')->where('user_id', $user_id)->count();
        $this->data['guest_categories'] = EventGuestCategory::where('event_id', $event_id)->get();

        return view('message.report', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function getUserByCriteria($criteria, $event_id, $request = null, $sub_category = null)
    {
        
        switch ($criteria) {
            case 1:
                //All
                $users = \App\Models\EventsGuest::where('event_id', $event_id);
                break;

            case 2:
                //Select Guest Category
                $users = $request <> null ? \App\Models\EventsGuest::where('event_id', $event_id)->where('event_guest_category_id', $request->event_guest_category_id) : [];
                break;

            case 3:
                //Full Paid Guest

                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 4:
                //Non Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 5:
                //Partially Paid Guest
                $users = \App\Models\EventsGuest::where('event_id', $event_id)->whereNotIn('id', \App\Models\Payment::get(['events_guests_id']));
                break;

            case 6:
                //Input Numbers
                $phones = explode(',', strip_tags(request('custom_numbers')));
                $obj = [];
                foreach ($phones as $phone) {
                    $build = ['guest_phone' => $phone, 'guest_email' => '', 'guest_name' => '', 'guest_pledge' => '', 'custom' => 1];
                    array_push($obj, $build);
                }
                $users = (object) $obj;
                break;
            default:
                break;
        }

        if ($criteria == 6) {
            $users = $users;
        } else {
            $users = $sub_category <> null && (int) $sub_category > 0 && (int) $criteria <> 6 ? $users->where('event_guest_category_id', $sub_category)->get() : $users->get();
        }
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

        //save message to DB here first
        if (in_array('whatsapp', $request->source)) {
            $whatsappInstance = \App\Models\MessageInstance::where('user_id', Auth::id())
                ->where('type', 'whatsapp')
                ->where('is_paid', 1)
                ->first();
            if (!$whatsappInstance) {
                return redirect()->back()->withErrors(['error' => 'WhatsApp channel is not activated or paid. Please activate and pay for WhatsApp integration.']);
            }
        }


        $event_id = Auth::user()->event->id;
        // Determine recipients based on criteria from the form
        if ($criteria == 1) {
            // All Contacts
            $users = $this->getUserByCriteria(1, $event_id, $request);
        } elseif ($criteria == 2) {
            // Select Category
            $categoryId = $request->input('event_guest_category_id');
            $users = $this->getUserByCriteria(2, $event_id, $request, $categoryId);
        } elseif ($criteria == 6) {
            // Custom Numbers
            $users = $this->getUserByCriteria(6, $event_id, $request);
        } elseif ($criteria == 7) {
            // Excel Upload
            // Parse uploaded Excel file for phone numbers
            $users = [];
            if ($request->hasFile('excel_contacts')) {
                $file = $request->file('excel_contacts');
                if (!$file->isValid()) {
                    return redirect()->back()->withErrors(['excel_contacts' => 'Uploaded file is not valid.']);
                }
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['csv', 'xls', 'xlsx'])) {
                    $data = [];
                    if ($extension === 'csv') {
                        $handle = fopen($file->getRealPath(), 'r');
                        $header = fgetcsv($handle);
                        if (!$header || !in_array('phone', array_map('strtolower', $header)) || !in_array('name', array_map('strtolower', $header))) {
                            fclose($handle);
                            return redirect()->back()->withErrors(['excel_contacts' => 'CSV file must contain "phone" and "name" columns.']);
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
                            return redirect()->back()->withErrors(['excel_contacts' => 'No valid contacts found in the file.']);
                        }
                    } else {
                        // Use Laravel Excel if available
                        if (class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                            $rows = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);
                            $sheet = $rows[0] ?? [];
                            $header = array_map('strtolower', $sheet[0] ?? []);
                            if (!in_array('phone', $header) || !in_array('name', $header)) {
                                return redirect()->back()->withErrors(['excel_contacts' => 'Excel file must contain "phone" and "name" columns.']);
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
                                return redirect()->back()->withErrors(['excel_contacts' => 'No valid contacts found in the file.']);
                            }
                        } else {
                            return redirect()->back()->withErrors(['excel_contacts' => 'Excel import package is not installed.']);
                        }
                    }
                } else {
                    return redirect()->back()->withErrors(['excel_contacts' => 'Invalid file type. Only CSV, XLS, and XLSX are allowed.']);
                }
            }
            $users = collect($users);
        } else {
            // Default fallback
            $users = $this->getUserByCriteria($criteria, $event_id, $request);
        }
        
        // Use queue system for message processing
        $this->queueMessages($users, $request->message, $request->source);
        
        return redirect()->back()->with('success', 'Messages queued for delivery successfully! You will receive notifications as they are processed.');
    }

    /**
     * Queue messages for processing
     */
    private function queueMessages($users, $message, $sources)
    {
        if (is_array($users)) {
            $userCount = count($users);
        } elseif ($users instanceof \Illuminate\Support\Collection) {
            $userCount = $users->count();
        } else {
            $userCount = 0;
        }
        
        Log::info('Queueing messages for delivery', [
            'user_id' => Auth::id(),
            'recipient_count' => $userCount,
            'sources' => $sources
        ]);

        // If it's a large batch (more than 100), use bulk processing
        if ($userCount > 100) {
            foreach ($sources as $source) {
                ProcessBulkMessages::dispatch(
                    $users instanceof \Illuminate\Support\Collection ? $users->toArray() : $users,
                    $message,
                    Auth::id(),
                    $source,
                    50 // Batch size
                )->delay(now()->addSeconds(5));
            }
        } else {
            // For smaller batches, queue individual messages
            $delay = 0;
            foreach ($users as $user) {
                $user = (object) $user;
                $phoneNumber = validate_phone_number($user->guest_phone);
                
                if (is_array($phoneNumber)) {
                    $chatId = $phoneNumber[1];
                    $personalizedMessage = $this->personalizeMessage($message, $user);
                    
                    foreach ($sources as $source) {
                        SendWhatsAppMessage::dispatch(
                            $personalizedMessage,
                            $chatId,
                            $source,
                            Auth::id()
                        )->delay(now()->addSeconds($delay));
                        
                        $delay += 2; // 2 second delay between messages to avoid rate limiting
                    }
                }
            }
        }
    }

    /**
     * Personalize message with user data
     */
    private function personalizeMessage($message, $user)
    {
        $datediff = time() - strtotime(Auth::user()->event->date);
        $paid_amount = isset($user->custom) ? 0 : ($user->payments ? $user->payments()->sum('amount') : 0);
        
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
                ? Auth::user()->messageInstances()->where('type', 'whatsapp')->value('instance_id')
                : Auth::user()->messageInstances()->where('type', $source)->value('instance_id'))
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


    public function send($message, $id, $source, $message_sentby_id, $user_id = null)
    {
        $status = $this->checkChannelStatus($source, $user_id);
        if ($status == false) {
            if ($message_sentby_id <> null) {
                $check = \App\Models\MessageSentby::where('id', $message_sentby_id)->where('channel', $source)->first();
                if (!empty($check)) {
                    return \App\Models\MessageSentby::where('id', $message_sentby_id)->where('channel', $source)->update(['return_code' => 'Failed: Low balance']);
                } else {
                    return false;
                }
            }
        }
        switch ($source) {
            case 'whatsapp':

                if (request()->file('file')) {
                    $this->validate(\request(), ['file' => 'max:2000'], ['file' => 'The photo size must be less than 2MB']);
                    if (strtolower(request()->file('file')->guessExtension()) == 'ogg') {
                        $filename = 'http://dikodiko.shulesoft.com/public/images/voice.ogg';
                        $return = $this->file($id, 'ogg', $filename, $message);
                    } else {
                        $filename = $this->saveFile(request()->file('file'));
                        $return = $this->file($id, strtolower(request()->file('file')->guessExtension()), $filename, $message);
                    }
                } else {
                    $return = $this->sendMessage($id, $message);
                }
                break;
            case 'quick-sms':
                $return = $this->send_sms(str_replace('@c.us', NULL, $id), $message, 1, $message_sentby_id);
                break;
            case 'phone-sms':
                $return = $this->send_sms(str_replace('@c.us', NULL, $id), $message, 0, $message_sentby_id);
                break;
            case 'telegram':
                $return = $this->telegram($id, $message);
                break;
            case 'email':
                $return = $this->sendCustomEmail($id, $message);
                break;
            default:
                break;
        }
        \App\Models\MessageSentby::where('id', $message_sentby_id)->update(['return_code' => json_encode($return)]);
    }

    public function telegram($id, $sms)
    {
        $chat_id = str_replace('@c.us', NULL, $id);
        $telegram_subscriber = DB::table('telegram_users')->where('phone_number', 'ilike', "%$chat_id%")->first();
        if (!empty($telegram_subscriber)) {
            $bot_token = '1414183991:AAFORE9WoKxEtUk6se9Xjq5VIRraVO8fkp0';
            $telegram = new Telegram($bot_token);
            $message = array('chat_id' => $telegram_subscriber->telegram_id, 'text' => $sms, 'parse_mode' => 'HTML');
            return $telegram->sendMessage($message);
        }
    }

    public function sendCustomEmail($id, $sms, $message_sentby_id = null)
    {
        $return = 0;

        if (filter_var($id, FILTER_VALIDATE_EMAIL)) {
            try {

                $link = url('/');
                $data = ['content' => isset($sms->body) ? $sms->body : '', 'link' => $link, 'photo' => isset($sms->photo) ?? '', 'sitename' => 'dikodiko', 'name' => isset($sms->name) ? $sms->name : ''];
                $message = (object) ['sitename' => 'dikodiko', 'email' => $id, 'subject' => isset($sms->subject) ? $sms->subject : 'DikoDiko Email'];
                $return = \Mail::send('auth.email.default', $data, function ($m) use ($message) {
                    $m->from('info@dikodiko.co.tz', $message->sitename);
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

    public function send_sms($phone_number, $body, $type, $message_sentby_id)
    {
        $karibusms = new \karibusms();
        $user = \App\Models\MessageSentby::find($message_sentby_id)->message->user->userKey()->where('type', 'sms');
        if ($user->count() > 0) {
            $api_key = $user->first()->api_key;
            $api_secret = $user->first()->api_secret;
        } else {
            return \App\Models\MessageSentby::where('id', $message_sentby_id)->update(['status' => 1, 'return_code' => 'Error: No SMS App has been installed or activated', 'updated_at' => 'now()']);
        }
        $karibusms->API_KEY = $api_key;
        $karibusms->API_SECRET = $api_secret;
        $karibusms->set_name('DIKODIKO');
        $karibusms->karibuSMSpro = $type;
        $result = (object) json_decode($karibusms->send_sms($phone_number, $body, 'dikodiko_' . time()));
        if (is_object($result) && isset($result->success) && $result->success == 1) {
            \App\Models\MessageSentby::where('id', $message_sentby_id)->update(['return_code' => json_encode($result), 'updated_at' => 'now()']);
        } else {
            \App\Models\MessageSentby::where('id', $message_sentby_id)->update(['status' => 1, 'return_code' => json_encode($result), 'updated_at' => 'now()']);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function process()
    {
        $pending = DB::select('SELECT b.channel,a.email,a.phone, b.id as message_sentby_id, a.body,a.subject, a.user_id FROM dikodiko.messages a join dikodiko.messages_sentby b on a.id=b.message_id  where return_code is null limit 100');
        if (!empty($pending)) {
            foreach ($pending as $message) {

                if ($message->channel == 'email' && filter_var($message->email, FILTER_VALIDATE_EMAIL)) {
                    $chat_id = $message->email;
                    $this->send($message->body, $chat_id, $message->channel, $message->message_sentby_id);
                }
                if ($message->channel <> 'email') {
                    $chat_id = validate_phone_number($message->phone)[1] ;
                    $this->send($message->body, $chat_id, $message->channel, $message->message_sentby_id, $message->user_id);
                }
            }
        }
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
                    $booking = Auth::user()->messageInstances()->where('type', 'whatsapp')->where('is_paid', 1)->first();
                    $link = !empty($booking) ? 'whatsapp' : 'paywhatsappModal';
                    $whatsapp = $this->checkChannelStatus('whatsapp');
                    $whatsapp == FALSE ?
                        array_push($results, [
                            'channel' => $channel,
                            'message' => '<p class="alert alert-danger" style="cursor:pointer" role="alert" data-toggle="modal" data-target="#' . $link . '">Please '
                                . '<span class="badge bg-green" data-toggle="modal" '
                                . 'data-target=".bs-whatsapp-modal-lg">make payments</span> '
                                . 'and connect your whatsapp number with DikoDiko to proceed</p>'
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
            $addons = Auth::user()->messageInstances()->where('type', 'whatsapp')->where('is_paid', 1)->first();

            return empty($addons) ? FALSE : $addons->is_paid;
        }
        if ($key == 'quick-sms') {
            $status = DB::table('users_sms_status')->where('user_id', $user_id)->first();
            return !empty($status) ? $status->message_left : 0;
        }
        if ($key == 'phone-sms') {
            //check if mobile app have been downloaded and last active time
            $verify = DB::table('users_keys')->where('user_id', $user_id)->where('type', 'phone-sms')->first();
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

            $addons = DB::table('admin_packages_payments')->whereIn('admin_payment_id', \App\Models\AdminPayment::whereUserId($user_id)->get(['id']))
                ->whereIn('admin_package_id', \App\Models\AdminPackage::whereName('phone-sms')->get(['id']))
                ->where('end_date', '>=', date('Y-m-d H:i', time()))->first();
            return empty($addons) ? FALSE : $addons->end_date;
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
            $this->data['guests'] = \App\Models\EventsGuest::where('event_id', Auth::user()->event->id)->get();
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
            $pendingInstance = \App\Models\MessageInstance::where('user_id', Auth::id())
                ->where('status', 0)
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

        $instance = \App\Models\MessageInstance::where('instance_id', $instance_id)->first();

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

                \App\Models\MessageInstance::where('instance_id', $instance_id)->update([
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
                        $data = $data + ['status' => 1];
                        \App\Models\MessageInstance::where('instance_id', $instance_id)
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
                        \App\Models\MessageInstance::where('instance_id', $instance_id)
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
                        'status' => 0
                    ];
                    \App\Models\MessageInstance::where('instance_id', $instance_id)
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
                'status' => 'required|integer',
                'is_paid' => 'required|integer'
            ]);

         
            // Create the instance using the MessageInstance model
            $instance = new \App\Models\MessageInstance();
            $instance->instance_id = $request->instance_id;
            $instance->type = $request->type;
            $instance->name = $request->name;
            $instance->owner = $request->owner;
            $instance->user_id = $request->user_id;
            $instance->connect_status = $request->connect_status;
            $phone_number = preg_replace('/\s+/', '', ltrim($request->phone_number, '+'));
            $instance->phone_number = $request->country_code . $phone_number;
            $instance->webhook_url = $request->webhook_url;
            $instance->webhook_events = $request->webhook_events;
            $instance->status = $request->status;
            $instance->is_paid = $request->is_paid;
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
}
