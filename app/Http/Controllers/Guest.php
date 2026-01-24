<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessContact;
use App\Models\BusinessContact as EventsGuest;
use App\Models\BusinessContactCategory;
use App\Models\BusinessContactCategory as EventGuestCategory;
use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
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
        $business_id = Auth::user()->business->id;
        if ($_POST) {
            (int) request('id') > 0 ? EventsGuest::find(request('id'))->update(request()->except('name', '_token', 'id')) : $this->store(request());
        }
        
        // Get paginated guests data with handoff information
        $this->data['guests'] = EventsGuest::with(['contactCategory', 'assignedAgent', 'lead'])
            ->where('business_id', $business_id)
            ->limit(1000)
            ->get();
        $this->data['guest_categories'] = EventGuestCategory::where('business_id', $business_id)->get();
        $this->data['total_guests'] = EventsGuest::where('business_id', $business_id)->count();
        
        // Get subscription plan and limits
        $billingAccount = Auth::user()->business->billingAccount;
        $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
        $planLimits = config('safarichat_billing.plans.' . $currentPlan . '.limits', []);
        $this->data['subscription_plan'] = $currentPlan;
        $this->data['max_contacts'] = $planLimits['max_contacts'] ?? 10;
        
        // Add handoff statistics
        $this->data['handoff_stats'] = [
            'ai_handled' => EventsGuest::where('business_id', $business_id)->where('handoff_status', 'ai')->count(),
            'pending_handoff' => EventsGuest::where('business_id', $business_id)->where('handoff_status', 'pending_handoff')->count(),
            'handed_off' => EventsGuest::where('business_id', $business_id)->where('handoff_status', 'handed_off')->count(),
            'completed' => EventsGuest::where('business_id', $business_id)->where('handoff_status', 'completed')->count(),
            'urgent_cases' => EventsGuest::where('business_id', $business_id)->where('priority_level', '<=', 2)->count()
        ];
        
        // Get available agents for assignment
        $this->data['available_agents'] = \App\Models\User::select('id', 'name', 'email')
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();
        
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
        $business_id = Auth::user()->business->id;
        $data=array_merge($request->all(), ['business_id' => $business_id]);
      
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

            $business_id = Auth::user()->business->id;

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
                $category = \App\Models\EventGuestCategory::where('name', 'ilike', strtolower($user->category))->where('business_id', $business_id)->first();
                $category_id = !empty($category) ? $category->id : \App\Models\EventGuestCategory::firstOrCreate(['name' => ucfirst($user->category), 'business_id' => $business_id])->id;

                //check available event guests
                $check_guests = EventsGuest::where('guest_phone', $phone)->first();

                $event = empty($check_guests) ? EventsGuest::create([
                        'business_id' => $business_id,
                        'guest_name' => $user->name,
                        'guest_email' => isset($user->email) ? $user->email : '',
                        'guest_phone' => $phone,
                        'event_guest_category_id' => $category_id,
                        'guest_pledge' => $user->pledge
                    ]) : $check_guests;
                $with = '';
                if (isset($user->contribution) && (int) $user->contribution > 0) {
                    // Event payment system removed - guest payments no longer tracked
                    // Focus on guest/contact management instead of event payments
                    $with = '';
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
            $existingPhones = EventsGuest::where('business_id', $business_id)
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
                    'business_id' => $business_id,
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
        try {
            $guestId = request('id');
            $business_id = Auth::user()->business->id;
            
            // Find the guest and ensure it belongs to the current business
            $guest = EventsGuest::where('id', $guestId)
                ->where('business_id', $business_id)
                ->first();
                
            if (!$guest) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found or access denied'
                    ], 404);
                }
                return redirect()->back()->with('error', 'Contact not found or access denied');
            }
            
            // Handle lead status separately
            $leadStatus = request('lead_status');
            if ($leadStatus) {
                // Get or create lead for this contact
                $lead = $guest->lead;
                if (!$lead) {
                    // Get default AI sales agent for this business
                    $defaultAgent = \App\Models\AiSalesAgent::where('business_id', $guest->business_id)
                        ->where('is_active', true)
                        ->first();
                        
                    if (!$defaultAgent) {
                        // Create a default AI sales agent if none exists
                        $defaultAgent = \App\Models\AiSalesAgent::create([
                            'business_id' => $guest->business_id,
                            'user_id' => Auth::id(),
                            'name' => 'Default Sales Agent',
                            'is_active' => true,
                            'allow_outreach' => true,
                            'personality_type' => 'professional'
                        ]);
                    }
                    
                    $lead = Lead::create([
                        'business_contact_id' => $guest->id,
                        'business_id' => $guest->business_id,
                        'user_id' => Auth::id(),
                        'ai_sales_agent_id' => $defaultAgent->id,
                        'name' => $guest->guest_name,
                        'phone_number' => $guest->guest_phone,
                        'email' => $guest->guest_email,
                        'status' => $leadStatus,
                        'source' => 'manual_edit'
                    ]);
                } else {
                    $lead->status = $leadStatus;
                    $lead->save();
                }
            }
            
            // Update guest data (excluding lead_status as it's handled above)
            $guest->update(request()->except('id', '_token', 'lead_status'));
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contact updated successfully',
                    'guest' => $guest
                ]);
            }
            
            return redirect()->back()->with('success', 'Contact updated successfully');
            
        } catch (\Exception $e) {
            \Log::error('Error updating guest in edit method: ' . $e->getMessage(), [
                'guest_id' => request('id'),
                'request_data' => request()->all()
            ]);
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contact: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update contact');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        try {
            $business_id = Auth::user()->business->id;
            
            // Find the guest and ensure it belongs to the current business
            $guest = EventsGuest::where('id', $id)
                ->where('business_id', $business_id)
                ->first();
                
            if (!$guest) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Contact not found or access denied'
                    ], 404);
                }
                return redirect()->back()->with('error', 'Contact not found or access denied');
            }

            // Update guest data (validation should be done on frontend)
            $updateData = $request->except('_token', 'id', 'lead_status');
            
            // Handle phone number formatting if provided
            if (isset($updateData['guest_phone'])) {
                // Basic phone formatting - remove non-numeric chars except +
                $phone = preg_replace('/[^0-9+]/', '', $updateData['guest_phone']);
                $updateData['guest_phone'] = $phone;
            }

            // Update the guest record
            $guest->update($updateData);
            
            // Handle lead status update if provided
            if ($request->has('lead_status')) {
                $this->updateLeadStatus($guest, $request->lead_status);
            }
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Contact updated successfully',
                    'guest' => $guest->fresh()
                ]);
            }
            
            return redirect()->back()->with('success', 'Contact updated successfully');
            
        } catch (\Exception $e) {
            \Log::error('Error updating guest: ' . $e->getMessage(), [
                'guest_id' => $id,
                'request_data' => $request->all()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update contact: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update contact');
        }
    }

    /**
     * Update lead status for a guest
     *
     * @param  EventsGuest  $guest
     * @param  string  $leadStatus
     * @return void
     */
    private function updateLeadStatus($guest, $leadStatus) {
        try {
            // Find the lead associated with this guest (contact)
            $lead = Lead::where('business_contact_id', $guest->id)->first();
            
            if (!$lead) {
                // Ensure we have an AI sales agent for this business
                $aiSalesAgent = AiSalesAgent::where('business_id', $guest->business_id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$aiSalesAgent) {
                    // Create a default AI sales agent if none exists
                    $aiSalesAgent = AiSalesAgent::create([
                        'business_id' => $guest->business_id,
                        'name' => 'Default Sales Agent',
                        'personality_type' => 'professional',
                        'is_active' => true,
                        'allow_outreach' => true,
                        'business_hours_start' => '09:00',
                        'business_hours_end' => '17:00',
                        'timezone' => 'UTC',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                // Create a new lead if one doesn't exist
                $lead = Lead::create([
                    'business_contact_id' => $guest->id,
                    'business_id' => $guest->business_id,
                    'user_id' => Auth::id(),
                    'ai_sales_agent_id' => $aiSalesAgent->id,
                    'status' => $leadStatus,
                    'source' => 'manual_edit',
                    'last_interaction_at' => now()
                ]);
            } else {
                // Update existing lead status
                $lead->update([
                    'status' => $leadStatus,
                    'last_interaction_at' => now()
                ]);
            }
            
            \Log::info('Lead status updated successfully', [
                'guest_id' => $guest->id,
                'lead_id' => $lead->id,
                'old_status' => $lead->getOriginal('status'),
                'new_status' => $leadStatus
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating lead status: ' . $e->getMessage(), [
                'guest_id' => $guest->id,
                'lead_status' => $leadStatus
            ]);
        }
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
            $business_id = Auth::user()->business->id;
            
            $guest = EventsGuest::where('id', $id)
                ->where('business_id', $business_id)
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
        $find = \App\Models\BusinessContactCategory::find($id);
        if (!empty($find)) {
            !empty($find->businessContacts()->get()) ? $find->delete() : 'You cannot delete this Category, there are guest available ';
        }
        return redirect()->back()->with('success', 'success');
    }

    public function addguestcategory() {
        // FEATURE GATE: Check if customer categorization is allowed in current subscription plan
        $customerId = Auth::user()->customer_id ?? Auth::id();
        $billingStatus = BillingService::getCachedStatus($customerId);
        
        if (!$billingStatus['permissions']['customer_categorization']) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer categorization is not available in your current plan',
                    'upgrade_required' => true,
                    'feature' => 'customer_categorization',
                    'current_plan' => $billingStatus['subscription']['plan'],
                    'required_plan' => 'pro'
                ], 403);
            }
            
            // For non-AJAX requests, return HTML error
            echo '<div class="alert alert-warning">Customer categorization is not available in your current plan. <a href="#" onclick="showUpgradeModal(\'customer_categorization\')">Upgrade to Pro</a></div>';
            return;
        }
        
        $business_id = Auth::user()->business->id;
        if (strlen(request('name')) > 2) {
            \App\Models\EventGuestCategory::firstOrCreate(['name' => request('name'), 'business_id' => $business_id]);
            $result = '<select class="form-control" name="event_guest_category_id" id="append_option">';
            $guest_categories = \App\Models\EventGuestCategory::where('business_id', $business_id)->get();
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
            $business_id = Auth::user()->business->id;

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
                    'business_id' => $business_id,
                    'event_guest_category_id' => 1, // Default category
                    'created_at' => now(),
                    'updated_at' => now()
                ];                    // Check if contact already exists for this business
                    $existing = \App\Models\EventsGuest::where('business_id', $business_id)
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
            $business_id = Auth::user()->business->id;

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
                        'business_id' => $business_id,
                        'event_guest_category_id' => 1, // Default category
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Check if contact already exists for this event
                    $existing = \App\Models\EventsGuest::where('business_id', $business_id)
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
            $business_id = Auth::user()->business->id;

            $contact = EventsGuest::with('eventGuestCategory')
                ->where('id', $id)
                ->where('business_id', $business_id)
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
     * Get conversations for a specific contact with pagination
     */
    public function getConversations($id)
    {
        try {
            $business_id = Auth::user()->business->id;

            // Get contact
            $contact = EventsGuest::where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            if (!$contact) {
                return response()->json(['success' => false, 'message' => 'Contact not found']);
            }

            $limit = request('limit', 3);
            $offset = request('offset', 0);

            // Get conversations from the conversations table
            // First, get the lead associated with this contact
            $lead = $contact->lead;

            if (!$lead) {
                return response()->json([
                    'success' => true,
                    'conversations' => [],
                    'has_more' => false,
                    'total' => 0
                ]);
            }

            // Get conversations for this lead with pagination
            $conversations = \App\Models\Conversation::where('lead_id', $lead->id)
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit + 1) // Get one extra to check if there are more
                ->get();

            $hasMore = $conversations->count() > $limit;
            if ($hasMore) {
                $conversations = $conversations->take($limit); // Remove the extra record
            }

            // Format conversations for frontend
            $formattedConversations = $conversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'message_content' => $conversation->message_content,
                    'sender_type' => $conversation->sender_type,
                    'created_at' => $conversation->created_at,
                    'timestamp' => $conversation->timestamp ?? $conversation->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'conversations' => $formattedConversations,
                'has_more' => $hasMore,
                'total' => \App\Models\Conversation::where('lead_id', $lead->id)->count()
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
            $business_id = Auth::user()->business->id;

            // Get contact
            $contact = EventsGuest::where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            if (!$contact) {
                return response()->json(['success' => false, 'message' => 'Contact not found']);
            }

            // Get both outgoing and incoming messages for this contact
            $outgoingMessages = $contact->outgoingMessages()
                ->with('whatsappInstance')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'type' => 'outgoing',
                        'message_content' => $message->message_body ?? $message->message,
                        'status' => $message->status,
                        'delivery_status' => $message->delivery_status ?? null,
                        'media_path' => $message->media_path,
                        'media_url' => $message->media_url,
                        'caption' => $message->caption,
                        'sent_at' => $message->sent_at ?? $message->created_at,
                        'waapi_message_id' => $message->waapi_message_id,
                        'retry_count' => $message->retry_count ?? 0,
                        'error_message' => $message->error_message,
                        'whatsapp_instance' => $message->whatsappInstance ? [
                            'instance_name' => $message->whatsappInstance->instance_name,
                            'phone_number' => $message->whatsappInstance->phone_number
                        ] : null
                    ];
                });

            $incomingMessages = $contact->incomingMessages()
                ->with('whatsappInstance')
                ->orderBy('message_timestamp', 'desc')
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'type' => 'incoming',
                        'message_content' => $message->message_body,
                        'sender_name' => $message->sender_name,
                        'message_type' => $message->message_type,
                        'media_data' => $message->media_data,
                        'from_me' => $message->from_me,
                        'is_group' => $message->is_group,
                        'status' => $message->status,
                        'auto_reply' => $message->auto_reply,
                        'received_at' => $message->message_timestamp,
                        'waapi_message_id' => $message->message_id,
                        'whatsapp_instance' => $message->whatsappInstance ? [
                            'instance_name' => $message->whatsappInstance->instance_name,
                            'phone_number' => $message->whatsappInstance->phone_number
                        ] : null
                    ];
                });

            // Merge and sort all messages by timestamp
            $allMessages = $outgoingMessages->concat($incomingMessages)
                ->sortByDesc(function ($message) {
                    return $message['type'] === 'outgoing' ? $message['sent_at'] : $message['received_at'];
                })
                ->values()
                ->take(50); // Limit to recent 50 messages for performance

            return response()->json([
                'success' => true,
                'messages' => $allMessages,
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->guest_name,
                    'phone' => $contact->guest_phone,
                    'email' => $contact->guest_email
                ],
                'message_stats' => [
                    'total_outgoing' => $outgoingMessages->count(),
                    'total_incoming' => $incomingMessages->count(),
                    'recent_messages' => $allMessages->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get conversation summary for a specific contact
     */
    public function getConversationSummary($id)
    {
        try {
            $business_id = Auth::user()->business->id;

            // Get contact with lead relationship
            $contact = EventsGuest::with(['lead', 'lead.conversations'])
                ->where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            if (!$contact) {
                return response()->json(['success' => false, 'message' => 'Contact not found']);
            }

            // Get conversation data
            $lead = $contact->lead;
            $conversations = $lead ? $lead->conversations()->orderBy('created_at', 'desc')->get() : collect();
            
            // Get message statistics
            $outgoingCount = $contact->outgoingMessages()->count();
            $incomingCount = $contact->incomingMessages()->count();
            $totalMessages = $outgoingCount + $incomingCount;
            
            // Get last interaction
            $lastOutgoing = $contact->outgoingMessages()->latest()->first();
            $lastIncoming = $contact->incomingMessages()->latest('message_timestamp')->first();
            
            $lastInteraction = null;
            if ($lastOutgoing && $lastIncoming) {
                $lastInteraction = $lastOutgoing->created_at > $lastIncoming->message_timestamp ? 
                    $lastOutgoing->created_at : $lastIncoming->message_timestamp;
            } elseif ($lastOutgoing) {
                $lastInteraction = $lastOutgoing->created_at;
            } elseif ($lastIncoming) {
                $lastInteraction = $lastIncoming->message_timestamp;
            }

            // Build conversation summary
            $summary = [
                'overview' => [
                    'total_messages' => $totalMessages,
                    'outgoing_messages' => $outgoingCount,
                    'incoming_messages' => $incomingCount,
                    'ai_responses' => $conversations->where('message_type', 'AI_AGENT')->count(),
                    'last_interaction' => $lastInteraction,
                    'stage' => $lead ? $lead->status : 'Unknown',
                    'lead_score' => $lead ? $lead->lead_score : 0
                ]
            ];

            // Extract key topics from conversations if available
            $keyTopics = [];
            if ($conversations->isNotEmpty()) {
                foreach ($conversations as $conversation) {
                    // Look for AI context summaries
                    if ($conversation->message_type === 'ai_context_summary' && !empty($conversation->message_content)) {
                        // Extract key topics from AI context (simple keyword extraction)
                        $content = strtolower($conversation->message_content);
                        $topics = [];
                        
                        // Common business/education keywords to look for
                        $keywords = ['school', 'student', 'education', 'management', 'system', 'software', 'learning', 'teaching', 'administration', 'fee', 'payment', 'registration', 'academic', 'curriculum'];
                        
                        foreach ($keywords as $keyword) {
                            if (strpos($content, $keyword) !== false) {
                                $topics[] = ucfirst($keyword);
                            }
                        }
                        
                        $keyTopics = array_merge($keyTopics, $topics);
                    }
                }
                
                // Remove duplicates and limit to 10 topics
                $keyTopics = array_unique($keyTopics);
                $keyTopics = array_slice($keyTopics, 0, 10);
                
                $summary['key_topics'] = $keyTopics;
                
                // Get AI context if available
                $aiContext = $conversations->where('message_type', 'ai_context_summary')->first();
                if ($aiContext) {
                    $summary['ai_context'] = $aiContext->message_content;
                }
            }

            // Recent activity timeline
            $recentActivity = [];
            
            // Add lead status changes
            if ($lead) {
                $recentActivity[] = [
                    'action' => 'Lead Status',
                    'description' => "Status: {$lead->status}",
                    'date' => $lead->updated_at
                ];
                
                if ($lead->last_interaction_at) {
                    $recentActivity[] = [
                        'action' => 'Last Lead Interaction',
                        'description' => 'AI system interaction',
                        'date' => $lead->last_interaction_at
                    ];
                }
            }
            
            // Add recent conversations
            foreach ($conversations->take(3) as $conversation) {
                $actionType = $conversation->message_type === 'AI_AGENT' ? 'AI Response' : 'Conversation';
                $recentActivity[] = [
                    'action' => $actionType,
                    'description' => $conversation->conversation_stage ? "Stage: {$conversation->conversation_stage}" : null,
                    'date' => $conversation->created_at
                ];
            }
            
            // Add handoff information if available
            if ($contact->handoff_status && $contact->handoff_status !== 'ai') {
                $recentActivity[] = [
                    'action' => 'Handoff Status',
                    'description' => "Status: " . ucfirst(str_replace('_', ' ', $contact->handoff_status)),
                    'date' => $contact->handoff_requested_at ?: $contact->updated_at
                ];
            }
            
            // Sort by date and limit to 5 most recent
            usort($recentActivity, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });
            
            $summary['recent_activity'] = array_slice($recentActivity, 0, 5);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->guest_name,
                    'phone' => $contact->guest_phone,
                    'email' => $contact->guest_email,
                    'lead_status' => $lead ? $lead->status : null
                ]
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
            $business_id = $user->business->id;
            
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
                ->where('business_id', $business_id)
                ->get();

            if ($contacts->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No valid contacts found']);
            }

            $queuedCount = 0;
            $failedCount = 0;
            $batchId = 'unique_msg_' . uniqid();

            foreach ($contacts as $contact) {
                try {
                    // Format phone number
                    $phone = $this->formatPhoneNumber($contact->guest_phone);
                    
                    // Create outgoing message record
                    $outgoingMessage = \App\Models\OutgoingMessage::create([
                        'user_id' => $user->id,
                        'events_guest_id' => $contact->id,
                        'whatsapp_instance_id' => $whatsappInstance->id,
                        'phone_number' => $phone,
                        'message' => $message,
                        'message_body' => $message,
                        'message_type' => 'text',
                        'status' => 'pending',
                        'batch_id' => $batchId,
                        'provider' => 'waapi',
                        'priority' => 'normal',
                        'scheduled_at' => $scheduleDate ? \Carbon\Carbon::parse($scheduleDate) : null,
                        'queued_at' => now(),
                        'retry_count' => 0
                    ]);

                    // Dispatch to job queue
                    \App\Jobs\SendWhatsAppMessage::dispatch(
                        $message,
                        $phone,
                        'whatsapp',
                        $user->id,
                        null, // no files
                        $whatsappInstance->instance_id,
                        [
                            'whatsapp_instance_id' => $whatsappInstance->id,
                            'provider' => 'waapi',
                            'priority' => 'normal',
                            'batch_id' => $batchId,
                            'outgoing_message_id' => $outgoingMessage->id,
                            'scheduled_at' => $scheduleDate
                        ]
                    );

                    $queuedCount++;
                } catch (\Exception $e) {
                    \Log::error('Failed to queue message for contact', [
                        'contact_id' => $contact->id,
                        'phone' => $contact->guest_phone,
                        'error' => $e->getMessage()
                    ]);
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Messages queued: {$queuedCount}, Failed: {$failedCount}",
                'queued_count' => $queuedCount,
                'failed_count' => $failedCount,
                'batch_id' => $batchId
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
            $business_id = $user->business->id;
            
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

            // Get user's WhatsApp instance (connected and ready)
            $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', $user->id)
                ->where('status', 'connected')
                ->where('connect_status', 'ready')
                ->first();

            // Fallback to system default instance if user instance not found
            if (!$whatsappInstance) {
                $whatsappInstance = \App\Models\WhatsappInstance::where('is_system_default', 1)
                    ->where('status', 'connected')
                    ->where('connect_status', 'ready')
                    ->first();
            }

            if (!$whatsappInstance) {
                return response()->json(['success' => false, 'message' => 'No WhatsApp instance found (user or system default)']);
            }

            // Get contacts
            $contacts = EventsGuest::whereIn('id', $contactIds)
                ->where('business_id', $business_id)
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
                    
                    // Create outgoing message record
                    $outgoingMessage = \App\Models\OutgoingMessage::create([
                        'user_id' => $user->id,
                        'events_guest_id' => $contact->id,
                        'whatsapp_instance_id' => $whatsappInstance->id,
                        'phone_number' => $phone,
                        'message' => $message,
                        'message_body' => $message,
                        'message_type' => !empty($attachments) ? 'media' : 'text',
                        'status' => 'pending',
                        'batch_id' => 'media_msg_' . uniqid(),
                        'provider' => 'waapi',
                        'priority' => 'normal',
                        'scheduled_at' => $scheduleDate ? \Carbon\Carbon::parse($scheduleDate) : null,
                        'queued_at' => now(),
                        'retry_count' => 0,
                        'media_path' => !empty($uploadedFiles) ? implode(',', array_column($uploadedFiles, 'path')) : null,
                        'caption' => $message
                    ]);

                    // Dispatch to job queue
                    \App\Jobs\SendWhatsAppMessage::dispatch(
                        $message,
                        $phone,
                        'whatsapp',
                        $user->id,
                        $uploadedFiles, // Pass uploaded files
                        $whatsappInstance->instance_id,
                        [
                            'whatsapp_instance_id' => $whatsappInstance->id,
                            'provider' => 'waapi',
                            'priority' => 'normal',
                            'outgoing_message_id' => $outgoingMessage->id,
                            'scheduled_at' => $scheduleDate
                        ]
                    );

                    $successCount++;
                } catch (\Exception $e) {
                    \Log::error('Failed to queue message with attachments for contact', [
                        'contact_id' => $contact->id,
                        'phone' => $contact->guest_phone,
                        'error' => $e->getMessage()
                    ]);
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
                'message' => "Messages queued: {$successCount}, Failed: {$failedCount}",
                'queued_count' => $successCount,
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
            $business_id = Auth::user()->business->id;
            
            $contactIds = $request->input('contact_ids', []);
            
            if (empty($contactIds)) {
                return response()->json(['success' => false, 'message' => 'No contacts selected']);
            }

            $deletedCount = EventsGuest::whereIn('id', $contactIds)
                ->where('business_id', $business_id)
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

    // ===== HANDOFF MANAGEMENT METHODS =====

    /**
     * Request handoff for a guest
     */
    public function requestHandoff(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'reason' => 'required|string|max:1000',
                'priority_level' => 'required|integer|in:1,2,3,4,5',
                'assigned_agent_id' => 'nullable|exists:users,id'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            $business_id = Auth::user()->business->id;
            
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->requestHandoff(
                $request->reason,
                $request->priority_level,
                $request->assigned_agent_id
            );

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Handoff requested successfully' : 'Failed to request handoff'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Assign agent to handle guest handoff
     */
    public function assignAgent(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'agent_id' => 'required|exists:users,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            $business_id = Auth::user()->business->id;
            
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->assignToAgent($request->agent_id, $request->notes);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Agent assigned successfully' : 'Failed to assign agent'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Complete handoff process
     */
    public function completeHandoff(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            $business_id = Auth::user()->business->id;
            
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->completeHandoff($request->notes);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Handoff completed successfully' : 'Failed to complete handoff'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Return guest to AI handling
     */
    public function returnToAI(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'notes' => 'nullable|string|max:1000'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            $business_id = Auth::user()->business->id;
            
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->returnToAI($request->notes);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Returned to AI successfully' : 'Failed to return to AI'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Update guest priority level
     */
    public function updatePriority(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'priority_level' => 'required|integer|in:1,2,3,4,5'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            // event_id removed due to schema change
            
            $business_id = Auth::user()->business->id;
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->updatePriority($request->priority_level);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Priority updated successfully' : 'Failed to update priority'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Add notes to guest handoff
     */
    public function addHandoffNotes(Request $request)
    {
        try {
            $request->validate([
                'guest_id' => 'required|exists:events_guests,id',
                'notes' => 'required|string|max:1000'
            ]);

            $guest = EventsGuest::findOrFail($request->guest_id);
            
            // Ensure guest belongs to user's business
            // event_id removed due to schema change
            
            $business_id = Auth::user()->business->id;
            if ($guest->business_id !== $business_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access']);
            }

            $result = $guest->addHandoffNotes($request->notes);

            return response()->json([
                'success' => $result,
                'message' => $result ? 'Notes added successfully' : 'Failed to add notes'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get handoff dashboard data
     */
    public function getHandoffDashboard()
    {
        try {
            $business_id = Auth::user()->business->id;

            // Get handoff statistics
            $stats = [
                'total_guests' => EventsGuest::where('business_id', $business_id)->count(),
                'ai_handled' => EventsGuest::where('business_id', $business_id)->byHandoffStatus('ai')->count(),
                'pending_handoff' => EventsGuest::where('business_id', $business_id)->byHandoffStatus('pending_handoff')->count(),
                'handed_off' => EventsGuest::where('business_id', $business_id)->byHandoffStatus('handed_off')->count(),
                'completed' => EventsGuest::where('business_id', $business_id)->byHandoffStatus('completed')->count(),
                'urgent_cases' => EventsGuest::where('business_id', $business_id)->urgent()->count()
            ];

            // Get recent handoff activities
            $recentHandoffs = EventsGuest::where('business_id', $business_id)
                ->whereIn('handoff_status', ['pending_handoff', 'handed_off'])
                ->with(['assignedAgent', 'eventGuestCategory'])
                ->orderBy('handoff_requested_at', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'recent_handoffs' => $recentHandoffs
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Get available agents for assignment
     */
    public function getAvailableAgents()
    {
        try {
            // Get users who can be assigned as agents (you may want to add role-based filtering)
            $agents = \App\Models\User::select('id', 'name', 'email')
                ->where('id', '!=', Auth::id()) // Exclude current user
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'agents' => $agents
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

}
