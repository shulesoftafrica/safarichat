<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventsGuest;
use \App\Models\EventGuestCategory;
use Auth;

class Guest extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        if ($_POST) {

            (int) request('id') > 0 ? EventsGuest::find(request('id'))->update(request()->except('name', '_token', 'id')) : $this->store(request());
        }
        
        // Get paginated guests data
        $this->data['guests'] = EventsGuest::whereEventId($event_id)->get();
        $this->data['guest_categories'] = EventGuestCategory::where('event_id', $event_id)->get();
        $this->data['total_guests'] = EventsGuest::whereEventId($event_id)->count();
        return view('guest.index', $this->data);
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
        $this->validate(request(), [
            'guest_name' => ['required', 'string', 'max:100', 'regex:/^([a-zA-Z\s\-\'\(\)]*)$/'], // name validation, only letters, spaces, hyphens, apostrophes, parentheses allowed
            'guest_phone' => ['required', 'string', 'max:30', 'unique:events_guests', 'regex:/^[0-9]*$/'], // phone number validation, only numbers allowed
        ]);
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        $event_id = $user_events->event_id;
        $data=array_merge($request->all(), ['event_id' => $event_id]);
      
        EventsGuest::create($data);
        return redirect()->back()->with('success', 'success');
    }

    private function checkKeysExists($value, $keys_array = null) {

        $required = $keys_array == null ? array('name', 'category', 'phone', 'pledge') : $keys_array;

        $data = array_change_key_case(array_shift($value), CASE_LOWER);
        $keys = str_replace(' ', '_', array_keys($data));
        $results = array_combine($keys, array_values($data));

        if (count(array_intersect_key(array_flip($required), $results)) === count($required)) {
            //All required keys exist!
            $status = 1;
        } else {
            $missing = array_intersect_key(array_flip($required), $results);
            $data_miss = array_diff(array_flip($required), $missing);
            $status = '<div class="alert icon-custom-alert alert-outline-pink b-round fade show"> Column with title  <b> ' . implode(', ', array_keys($data_miss)) . '</b>  miss from Excel file. '
                    . 'Please make sure file is in the same format as a sample file</div>';
        }

        return $status;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function uploadGuest() {
        //

        ini_set('max_execution_time', 300); //overwrite execution time, 5min
        // Detect file type (Excel or VCF)
        $file = request()->file('file');
        if (!$file) {
            $status = '<div class="alert alert-danger">No file uploaded.</div>';
        } else {
            $extension = strtolower($file->getClientOriginalExtension());

            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events->event_id;

            if (in_array($extension, ['xls', 'xlsx', 'csv'])) {
            // Handle Excel file
            $data = $this->uploadExcel();

            $status = $this->checkKeysExists($data);
            if ((int) $status == 1) {
                $status = '';
                foreach ($data as $user_info) {
                $user = (object) $user_info;
                if (strlen($user->name) < 2) {
                    continue;
                }
                if (strlen($user->phone) < 4) {
                    $status .= '<div class="alert alert-info col-lg-12">This Person ' . $user->name . ' have an Invalid No :' . $user->phone . '. Kindly update and upload again</div><br/>';
                    continue;
                }
                $phone = validate_phone_number($user->phone)[1];
                $category = \App\Models\EventGuestCategory::where('name', 'ilike', strtolower($user->category))->whereEventId($event_id)->first();
                $category_id = !empty($category) ? $category->id : \App\Models\EventGuestCategory::firstOrCreate(['name' => ucfirst($user->category), 'event_id' => $event_id])->id;

                //check available event guests
                $check_guests = EventsGuest::where('guest_phone', $phone)->first();

                $event = empty($check_guests) ? EventsGuest::create([
                        'event_id' => $event_id,
                        'guest_name' => $user->name,
                        'guest_email' => isset($user->email) ? $user->email : '',
                        'guest_phone' => $phone,
                        'event_guest_category_id' => $category_id,
                        'guest_pledge' => $user->pledge
                    ]) : $check_guests;
                $with = '';
                if (isset($user->contribution) && (int) $user->contribution > 0) {
                    //check if payment has been uploaded already
                    $payment = \App\Models\Payment::firstOrCreate([
                        'events_guests_id' => $event->id,
                        'amount' => $user->contribution,
                        'method' => 'Mobile'
                    ]);
                    $with = ' With Paid Amount of Tsh ' . $payment->amount;
                }
                $status .= '<div class="alert alert-success col-lg-12">User ' . $user->name . ' has been uploaded successfully' . $with . '</div><br/>';
                }
            }
            } elseif ($extension === 'vcf') {
            // Handle VCF file (phone contacts)
            // Use vcard-parser package: https://github.com/jeroendesloovere/vcardparser
            // Install via composer: composer require jeroendesloovere/vcard
            try {
                $vcfContent = file_get_contents($file->getRealPath());
                $parser = new \JeroenDesloovere\VCard\VCardParser($vcfContent);
                $contacts = $parser->getCards();

               

                $imported_count = 0;
                $status = '';
            $guestsToInsert = [];
            // Collect all phone numbers from contacts to check for existing ones in DB
            $contactPhones = array_filter(array_map(function($contact) {
                $phones = $contact->phone ?? [];
                if (is_array($phones)) {
                    $phone = reset($phones);
                } else {
                    $phone = $phones;
                }
                $phoneStr = is_array($phone) ? (isset($phone[0]) ? $phone[0] : '') : $phone;
                return validate_phone_number($phoneStr)[1] ?? null;
            }, $contacts));
            $existingPhones = EventsGuest::where('event_id', $event_id)
                ->whereIn('guest_phone', $contactPhones)
                ->pluck('guest_phone')
                ->toArray();

            foreach ($contacts as $contact) {
                $name = $contact->fullname ?? '';
                $phones = $contact->phone ?? [];
                $email = $contact->email ?? '';
                if (is_array($email)) {
                    $first = reset($email);
                    if (is_array($first)) {
                        $email = isset($first[0]) ? $first[0] : '';
                    } else {
                        $email = $first;
                    }
                }
                if (is_array($phones)) {
                    $phone = reset($phones);
                } else {
                    $phone = $phones;
                }
                $phoneStr = is_array($phone) ? (isset($phone[0]) ? $phone[0] : '') : $phone;

                if (empty($name) || empty($phoneStr)) {
                    continue;
                }
                $phone = validate_phone_number($phoneStr)[1];

                if (strlen($phone) < 4) {
                    $status .= '<div class="alert alert-info col-lg-12">This Person ' . $name . ' have an Invalid No :' . $phone . '. Kindly update and upload again</div><br/>';
                    continue;
                }

                $category_id = 1; // Default category

                if (in_array($phone, $existingPhones)) {
                    continue;
                }

                // Clean fields to ensure valid UTF-8 and remove emojis/special chars
                $clean_name = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $name); // Remove emojis
                $clean_name = mb_convert_encoding($clean_name, 'UTF-8', 'UTF-8'); // Ensure valid UTF-8
                $clean_email = mb_convert_encoding($email, 'UTF-8', 'UTF-8');
                $clean_phone = mb_convert_encoding($phone, 'UTF-8', 'UTF-8');

                $guestsToInsert[] = [
                    'event_id' => $event_id,
                    'guest_name' => $clean_name,
                    'guest_email' => $clean_email,
                    'guest_phone' => $clean_phone,
                    'event_guest_category_id' => $category_id,
                    'guest_pledge' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $status .= '<div class="alert alert-success col-lg-12">Contact ' . $name . ' has been uploaded successfully</div><br/>';
                $imported_count++;
            }

            // Remove any guests that would cause duplicates before insert
            if (!empty($guestsToInsert)) {
                // Remove guests with phones already in DB
                $guestsToInsert = array_filter($guestsToInsert, function($guest) use ($existingPhones) {
                    return !in_array($guest['guest_phone'], $existingPhones);
                });

                // Remove duplicates by guest_phone within $guestsToInsert
                $uniqueGuests = [];
                foreach ($guestsToInsert as $guest) {
                    if (!isset($uniqueGuests[$guest['guest_phone']])) {
                        $uniqueGuests[$guest['guest_phone']] = $guest;
                    }
                }
                $guestsToInsert = array_values($uniqueGuests);

                if (!empty($guestsToInsert)) {
                    $collection = collect($guestsToInsert);
                    $chunks = $collection->chunk(500); // You can adjust the chunk size

                    foreach ($chunks as $chunk) {
                        EventsGuest::insert($chunk->toArray());
                    }
                }
            }
                if ($imported_count == 0) {
                $status = '<div class="alert alert-warning">No valid contacts found in VCF file.</div>';
                }
            } catch (\Exception $e) {
                $status = '<div class="alert alert-danger">Failed to parse VCF file: ' . $e->getMessage() . '</div>';
            }
            } else {
            $status = '<div class="alert alert-danger">Unsupported file type. Please upload Excel (.xls, .xlsx, .csv) or VCF (.vcf) file.</div>';
            }
        }
        $this->data['status'] = $status;
        return view('auth.status', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit() {
        EventsGuest::find(request('id'))->update(request()->except('id'));
        return redirect()->back()->with('success', 'success');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id = null) {
        try {
            $id = $id ?: request()->segment(3);
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;
            
            $guest = EventsGuest::where('id', $id)
                ->where('event_id', $event_id)
                ->first();
                
            if (!$guest) {
                if (request()->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Contact not found']);
                }
                return redirect()->back()->with('error', 'Contact not found');
            }
            
            $guest->delete();
            
            if (request()->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Contact deleted successfully']);
            }
            
            return redirect()->back()->with('success', 'Contact deleted successfully');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()]);
            }
            return redirect()->back()->with('error', 'Failed to delete contact');
        }
    }

    public function guestcategory() {
        $id = request()->segment(3);
        $find = \App\Models\EventGuestCategory::find($id);
        if (!empty($find)) {
            !empty($find->event->eventsGuests()->get()) ? $find->delete() : 'You cannot delete this Category, there are guest available ';
        }
        return redirect()->back()->with('success', 'success');
    }

    public function addguestcategory() {
        $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
        if (strlen(request('name')) > 2) {
            \App\Models\EventGuestCategory::firstOrCreate(['name' => request('name'), 'event_id' => $user_events->event_id]);
            $result = '<select class="form-control" name="event_guest_category_id" id="append_option">';
            $guest_categories = \App\Models\EventGuestCategory::where('event_id', $user_events->event_id)->get();
            foreach ($guest_categories as $category) {
                $result .= ' <option value="' . $category->id . '">' . $category->name . '</option>';
            }
            $result .= ' </select>';
            echo $result;
        }
    }

    public function importWhatsappContacts(\Illuminate\Http\Request $request) {
        try {
            // Get contacts from the request (sent from frontend after WAAPI call)
            $contacts = $request->input('contacts', []);
            $instance_id = $request->input('instance_id');
            
            if (empty($contacts)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No contacts provided',
                    'imported_count' => 0
                ]);
            }

            $imported_count = 0;
            $user_id = Auth::id();
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;

            if (!$event_id) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No active event found for user',
                    'imported_count' => 0
                ]);
            }

            foreach ($contacts as $contact) {
                try {
                    // WAAPI contact format: {id, name, notify, verifiedName, isGroup, etc.}
                    $phone = $contact['id'] ?? '';
                    $name = $contact['name'] ?? $contact['verifiedName'] ?? $contact['notify'] ?? '';
                    
                    // Skip if no phone or if it's a group
                    if (empty($phone) || ($contact['isGroup'] ?? false)) {
                        continue;
                    }

                    // Clean phone number (remove @c.us suffix if present)
                    $clean_phone = str_replace('@c.us', '', $phone);
                    
                    // Skip if phone is not valid (should be numeric with country code)
                    if (!preg_match('/^\d{10,15}$/', $clean_phone)) {
                        continue;
                    }

                // Prepare guest data
                $guest_data = [
                    'guest_name' => !empty($name) ? $name : 'Contact ' . substr($clean_phone, -4),
                    'guest_phone' => '+' . $clean_phone,
                    'guest_email' => '',
                    'guest_pledge' => 0,
                    'event_id' => $event_id,
                    'event_guest_category_id' => 1, // Default category
                    'created_at' => now(),
                    'updated_at' => now()
                ];                    // Check if contact already exists for this event
                    $existing = \App\Models\EventsGuest::where('event_id', $event_id)
                        ->where('guest_phone', $guest_data['guest_phone'])
                        ->first();

                    if (!$existing) {
                        \App\Models\EventsGuest::create($guest_data);
                        $imported_count++;
                    }

                } catch (\Exception $e) {
                    \Log::warning('Error importing contact: ' . $e->getMessage(), [
                        'contact' => $contact,
                        'user_id' => $user_id
                    ]);
                    continue;
                }
            }

            return response()->json([
                'success' => true, 
                'message' => 'Contacts imported successfully',
                'imported_count' => $imported_count,
                'total_contacts' => count($contacts)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in importWhatsappContacts: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to import contacts: ' . $e->getMessage(),
                'imported_count' => 0
            ], 500);
        }
    }

    public function importGoogleContacts(\Illuminate\Http\Request $request) {
        try {
            // Get contacts from the request (sent from frontend after Google API call)
            $contacts = $request->input('contacts', []);
            
            if (empty($contacts)) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No Google contacts provided',
                    'imported_count' => 0
                ]);
            }

            $imported_count = 0;
            $user_id = Auth::id();
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;

            if (!$event_id) {
                return response()->json([
                    'success' => false, 
                    'message' => 'No active event found for user',
                    'imported_count' => 0
                ]);
            }

            foreach ($contacts as $contact) {
                try {
                    // Google contact format: {name, phones, emails, primaryPhone, primaryEmail}
                    $name = $contact['name'] ?? '';
                    $primary_phone = $contact['primaryPhone'] ?? '';
                    $primary_email = $contact['primaryEmail'] ?? '';
                    
                    // Skip if no name or phone
                    if (empty($name) || empty($primary_phone)) {
                        continue;
                    }

                    // Clean and validate phone number
                    $clean_phone = preg_replace('/[^0-9+]/', '', $primary_phone);
                    
                    // Ensure phone starts with + and has country code
                    if (!str_starts_with($clean_phone, '+')) {
                        // Try to add default country code for Tanzania
                        if (preg_match('/^[67]\d{8}$/', $clean_phone)) {
                            $clean_phone = '+255' . $clean_phone;
                        } elseif (preg_match('/^0[67]\d{8}$/', $clean_phone)) {
                            $clean_phone = '+255' . substr($clean_phone, 1);
                        } else {
                            // Skip invalid phone numbers
                            continue;
                        }
                    }
                    
                    // Skip if phone is not valid (should be 10-15 digits)
                    $phone_digits = preg_replace('/[^0-9]/', '', $clean_phone);
                    if (strlen($phone_digits) < 10 || strlen($phone_digits) > 15) {
                        continue;
                    }

                    // Prepare guest data
                    $guest_data = [
                        'guest_name' => trim($name),
                        'guest_phone' => $clean_phone,
                        'guest_email' => $primary_email ?: '',
                        'guest_pledge' => 0,
                        'event_id' => $event_id,
                        'event_guest_category_id' => 1, // Default category
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Check if contact already exists for this event
                    $existing = \App\Models\EventsGuest::where('event_id', $event_id)
                        ->where('guest_phone', $guest_data['guest_phone'])
                        ->first();

                    if (!$existing) {
                        \App\Models\EventsGuest::create($guest_data);
                        $imported_count++;
                    }

                } catch (\Exception $e) {
                    \Log::warning('Error importing Google contact: ' . $e->getMessage(), [
                        'contact' => $contact,
                        'user_id' => $user_id
                    ]);
                    continue;
                }
            }

            return response()->json([
                'success' => true, 
                'message' => 'Google contacts imported successfully',
                'imported_count' => $imported_count,
                'total_contacts' => count($contacts)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in importGoogleContacts: ' . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => 'Failed to import Google contacts: ' . $e->getMessage(),
                'imported_count' => 0
            ], 500);
        }
    }

    /**
     * Get contact details with group information
     */
    public function getContactDetails($id)
    {
        try {
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;

            $contact = EventsGuest::with('eventGuestCategory')
                ->where('id', $id)
                ->where('event_id', $event_id)
                ->first();

            if (!$contact) {
                return response()->json(['success' => false, 'message' => 'Contact not found']);
            }

            return response()->json([
                'success' => true,
                'contact' => [
                    'id' => $contact->id,
                    'guest_name' => $contact->guest_name,
                    'guest_phone' => $contact->guest_phone,
                    'guest_email' => $contact->guest_email,
                    'category_name' => $contact->eventGuestCategory ? $contact->eventGuestCategory->name : null,
                    'created_at' => $contact->created_at
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get messages sent to a specific contact
     */
    public function getContactMessages($id)
    {
        try {
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;

            // Get contact
            $contact = EventsGuest::where('id', $id)
                ->where('event_id', $event_id)
                ->first();

            if (!$contact) {
                return response()->json(['success' => false, 'message' => 'Contact not found']);
            }

            // For now, return empty array - will be populated when message tracking is implemented
            // In the future, this should query a messages table
            $messages = [];

            return response()->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Send message to selected contacts
     */
    public function sendUniqueMessage(Request $request)
    {
        try {
            $user = Auth::user();
            $user_events = $user->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;
            
            $contactIds = $request->input('contact_ids', []);
            $message = $request->input('message');
            $scheduleDate = $request->input('schedule_date');

            if (empty($contactIds) || !$message) {
                return response()->json(['success' => false, 'message' => 'Missing required data']);
            }

            // Get user's WhatsApp instance
            $whatsappInstance = $user->whatsappInstance();
            
            if (!$whatsappInstance) {
                return response()->json(['success' => false, 'message' => 'No WhatsApp instance found']);
            }

            // Get contacts
            $contacts = EventsGuest::whereIn('id', $contactIds)
                ->where('event_id', $event_id)
                ->get();

            if ($contacts->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No valid contacts found']);
            }

            $successCount = 0;
            $failedCount = 0;

            foreach ($contacts as $contact) {
                try {
                    // Format phone number
                    $phone = $this->formatPhoneNumber($contact->guest_phone);
                    
                    // Prepare message data for WAAPI
                    $messageData = [
                        'chatId' => $phone . '@c.us',
                        'message' => $message
                    ];

                    if ($scheduleDate) {
                        $messageData['schedule'] = $scheduleDate;
                    }

                    // Send via WAAPI
                    $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                        'Authorization' => 'Bearer ' . $whatsappInstance->access_token,
                        'Content-Type' => 'application/json'
                    ])->post(
                        "https://waapi.app/api/v1/instances/{$whatsappInstance->instance_id}/client/action/send-message",
                        $messageData
                    );

                    if ($response->successful()) {
                        $successCount++;
                        // TODO: Store message in database for tracking
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Messages sent: {$successCount}, Failed: {$failedCount}",
                'sent_count' => $successCount,
                'failed_count' => $failedCount
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Send message with file attachments to selected contacts
     */
    public function sendMessage(Request $request)
    {
        try {
            $user = Auth::user();
            $user_events = $user->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;
            
            // Handle both JSON and FormData requests
            $contactIds = $request->input('contact_ids');
            if (is_string($contactIds)) {
                $contactIds = json_decode($contactIds, true);
            }
            $contactIds = $contactIds ?: [];
            
            $message = $request->input('message', '');
            $scheduleDate = $request->input('schedule_date');
            $attachments = $request->file('attachments', []);

            if (empty($contactIds) || (empty($message) && empty($attachments))) {
                return response()->json(['success' => false, 'message' => 'Missing required data']);
            }

            // Get user's WhatsApp instance
            $whatsappInstance = $user->whatsappInstance();
            
            if (!$whatsappInstance) {
                return response()->json(['success' => false, 'message' => 'No WhatsApp instance found']);
            }

            // Get contacts
            $contacts = EventsGuest::whereIn('id', $contactIds)
                ->where('event_id', $event_id)
                ->get();

            if ($contacts->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No valid contacts found']);
            }

            $successCount = 0;
            $failedCount = 0;
            $uploadedFiles = [];

            // Handle file uploads first
            if (!empty($attachments)) {
                foreach ($attachments as $attachment) {
                    try {
                        // Validate file size (16MB limit)
                        if ($attachment->getSize() > 16 * 1024 * 1024) {
                            return response()->json(['success' => false, 'message' => 'File too large: ' . $attachment->getClientOriginalName()]);
                        }

                        // Store file temporarily
                        $filename = time() . '_' . $attachment->getClientOriginalName();
                        $path = $attachment->storeAs('temp_attachments', $filename, 'public');
                        $fullPath = storage_path('app/public/' . $path);
                        
                        $uploadedFiles[] = [
                            'path' => $fullPath,
                            'filename' => $attachment->getClientOriginalName(),
                            'mime_type' => $attachment->getMimeType(),
                            'temp_path' => $path
                        ];
                    } catch (\Exception $e) {
                        return response()->json(['success' => false, 'message' => 'Failed to upload file: ' . $e->getMessage()]);
                    }
                }
            }

            foreach ($contacts as $contact) {
                try {
                    // Format phone number
                    $phone = $this->formatPhoneNumber($contact->guest_phone);
                    $chatId = $phone . '@c.us';
                    
                    // Send text message if provided
                    if (!empty($message)) {
                        $messageData = [
                            'chatId' => $chatId,
                            'message' => $message
                        ];

                        if ($scheduleDate) {
                            $messageData['schedule'] = $scheduleDate;
                        }

                        $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                            'Authorization' => 'Bearer ' . $whatsappInstance->access_token,
                            'Content-Type' => 'application/json'
                        ])->post(
                            "https://waapi.app/api/v1/instances/{$whatsappInstance->instance_id}/client/action/send-message",
                            $messageData
                        );

                        if (!$response->successful()) {
                            $failedCount++;
                            continue;
                        }
                    }

                    // Send file attachments
                    foreach ($uploadedFiles as $file) {
                        try {
                            $fileData = [
                                'chatId' => $chatId,
                                'caption' => !empty($message) ? '' : $file['filename'] // Use filename as caption if no message
                            ];

                            $response = \Illuminate\Support\Facades\Http::timeout(60)
                                ->withHeaders([
                                    'Authorization' => 'Bearer ' . $whatsappInstance->access_token,
                                ])
                                ->attach('file', file_get_contents($file['path']), $file['filename'])
                                ->post(
                                    "https://waapi.app/api/v1/instances/{$whatsappInstance->instance_id}/client/action/send-media",
                                    $fileData
                                );

                            if (!$response->successful()) {
                                $failedCount++;
                                break;
                            }
                        } catch (\Exception $e) {
                            $failedCount++;
                            break;
                        }
                    }

                    $successCount++;

                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            // Clean up temporary files
            foreach ($uploadedFiles as $file) {
                try {
                    if (file_exists($file['path'])) {
                        unlink($file['path']);
                    }
                } catch (\Exception $e) {
                    // Ignore cleanup errors
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Messages sent: {$successCount}, Failed: {$failedCount}",
                'sent_count' => $successCount,
                'failed_count' => $failedCount
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Bulk delete contacts
     */
    public function bulkDelete(Request $request)
    {
        try {
            $user_events = Auth::user()->usersEvents()->orderBy('id', 'desc')->first();
            $event_id = $user_events ? $user_events->event_id : null;
            
            $contactIds = $request->input('contact_ids', []);
            
            if (empty($contactIds)) {
                return response()->json(['success' => false, 'message' => 'No contacts selected']);
            }

            $deletedCount = EventsGuest::whereIn('id', $contactIds)
                ->where('event_id', $event_id)
                ->delete();

            return response()->json([
                'success' => true,
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Format phone number for WhatsApp
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
        } elseif (!str_starts_with($phone, '255')) {
            $phone = '255' . $phone;
        }
        
        return $phone;
    }

    /**
     * Display incoming WhatsApp messages management
     */
    public function incomingMessages()
    {
        // Get user's WhatsApp instances
        $whatsappInstances = \App\Models\WhatsappInstance::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('whatsapp.incoming_messages', [
            'whatsappInstances' => $whatsappInstances
        ]);
    }

}
