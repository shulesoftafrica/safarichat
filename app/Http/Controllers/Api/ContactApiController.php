<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventsGuest;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContactApiController extends Controller
{
    /**
     * Store a new contact from external system
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check billing limits first
            $billingStatus = BillingService::getBillingStatus(Auth::id());
            if (!$billingStatus || !isset($billingStatus['limits']['contacts'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify subscription limits',
                    'upgrade_required' => true,
                    'feature' => 'contacts'
                ], 402);
            }

            $contactLimits = $billingStatus['limits']['contacts'];
            if ($contactLimits['current'] >= $contactLimits['max']) {
                return response()->json([
                    'success' => false,
                    'message' => "Contact limit reached. Your {$billingStatus['subscription']['plan']} plan allows {$contactLimits['max']} contacts.",
                    'upgrade_required' => true,
                    'feature' => 'contacts',
                    'current_limit' => $contactLimits['max'],
                    'current_usage' => $contactLimits['current']
                ], 402);
            }

            // FEATURE GATE: Check if customer categorization is allowed when assigning category
            if (!empty($request->event_guest_category_id)) {
                if (!$billingStatus['permissions']['customer_categorization']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer categorization is not available in your current plan',
                        'upgrade_required' => true,
                        'feature' => 'customer_categorization',
                        'current_plan' => $billingStatus['subscription']['plan'],
                        'required_plan' => 'pro'
                    ], 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'guest_name' => 'required|string|max:255',
                'guest_phone' => 'required|string|max:20',
                'guest_email' => 'nullable|email',
                'event_id' => 'nullable|integer',
                'guest_pledge' => 'nullable|numeric|min:0',
                'event_guest_category_id' => 'nullable|integer',
                'contacted_for_sales' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate phone number
            $exists = EventsGuest::where('user_id', Auth::id())
                                ->where('guest_phone', $request->guest_phone)
                                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact with this phone number already exists'
                ], 409);
            }

            $contact = EventsGuest::create([
                'user_id' => Auth::id(),
                'guest_name' => $request->guest_name,
                'guest_phone' => $request->guest_phone,
                'guest_email' => $request->guest_email,
                'event_id' => $request->event_id,
                'guest_pledge' => $request->guest_pledge ?? 0,
                'event_guest_category_id' => $request->event_guest_category_id,
                'contacted_for_sales' => $request->contacted_for_sales ?? false,
                'contacted_at' => $request->contacted_for_sales ? now() : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'data' => $contact,
                'message' => 'Contact created successfully'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating contact', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error creating contact'
            ], 500);
        }
    }

    /**
     * Bulk store contacts from external system
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            // Check billing limits first
            $billingStatus = BillingService::getBillingStatus(Auth::id());
            if (!$billingStatus || !isset($billingStatus['limits']['contacts'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify subscription limits',
                    'upgrade_required' => true,
                    'feature' => 'contacts'
                ], 402);
            }

            $contactLimits = $billingStatus['limits']['contacts'];
            $requestedContacts = count($request->input('contacts', []));
            $newTotal = $contactLimits['current'] + $requestedContacts;
            
            if ($newTotal > $contactLimits['max']) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot add {$requestedContacts} contacts. Your {$billingStatus['subscription']['plan']} plan allows {$contactLimits['max']} contacts. You have {$contactLimits['current']} contacts and can add " . ($contactLimits['max'] - $contactLimits['current']) . " more.",
                    'upgrade_required' => true,
                    'feature' => 'contacts',
                    'current_limit' => $contactLimits['max'],
                    'current_usage' => $contactLimits['current'],
                    'requested_count' => $requestedContacts
                ], 402);
            }

            // FEATURE GATE: Check if customer categorization is allowed when any contact has category
            $hasCategories = collect($request->input('contacts', []))->some(function($contact) {
                return !empty($contact['event_guest_category_id']);
            });
            
            if ($hasCategories && !$billingStatus['permissions']['customer_categorization']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer categorization is not available in your current plan',
                    'upgrade_required' => true,
                    'feature' => 'customer_categorization',
                    'current_plan' => $billingStatus['subscription']['plan'],
                    'required_plan' => 'pro'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'contacts' => 'required|array|min:1|max:100',
                'contacts.*.guest_name' => 'required|string|max:255',
                'contacts.*.guest_phone' => 'required|string|max:20',
                'contacts.*.guest_email' => 'nullable|email',
                'contacts.*.event_id' => 'nullable|integer',
                'contacts.*.guest_pledge' => 'nullable|numeric|min:0',
                'contacts.*.event_guest_category_id' => 'nullable|integer',
                'contacts.*.contacted_for_sales' => 'nullable|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $created = [];
            $errors = [];
            $userId = Auth::id();

            DB::beginTransaction();

            foreach ($request->contacts as $index => $contactData) {
                try {
                    // Check for duplicate phone within user's contacts
                    $exists = EventsGuest::where('user_id', $userId)
                                        ->where('guest_phone', $contactData['guest_phone'])
                                        ->exists();

                    if ($exists) {
                        $errors[] = "Contact at index {$index}: Phone number already exists";
                        continue;
                    }

                    $contact = EventsGuest::create([
                        'user_id' => $userId,
                        'guest_name' => $contactData['guest_name'],
                        'guest_phone' => $contactData['guest_phone'],
                        'guest_email' => $contactData['guest_email'] ?? null,
                        'event_id' => $contactData['event_id'] ?? null,
                        'guest_pledge' => $contactData['guest_pledge'] ?? 0,
                        'event_guest_category_id' => $contactData['event_guest_category_id'] ?? null,
                        'contacted_for_sales' => $contactData['contacted_for_sales'] ?? false,
                        'contacted_at' => ($contactData['contacted_for_sales'] ?? false) ? now() : null,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    $created[] = $contact;

                } catch (\Exception $e) {
                    $errors[] = "Contact at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'created' => $created,
                    'created_count' => count($created),
                    'error_count' => count($errors),
                    'errors' => $errors
                ],
                'message' => 'Bulk contact creation completed'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in bulk contact creation', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error in bulk contact creation'
            ], 500);
        }
    }

    /**
     * Get all contacts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = EventsGuest::where('user_id', Auth::id());

            // Filter by contacted status
            if ($request->has('contacted_for_sales')) {
                $query->where('contacted_for_sales', $request->contacted_for_sales);
            }

            // Filter by event
            if ($request->has('event_id')) {
                $query->where('event_id', $request->event_id);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('guest_name', 'like', "%{$search}%")
                      ->orWhere('guest_phone', 'like', "%{$search}%")
                      ->orWhere('guest_email', 'like', "%{$search}%");
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $contacts = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $contacts->items(),
                'pagination' => [
                    'current_page' => $contacts->currentPage(),
                    'last_page' => $contacts->lastPage(),
                    'per_page' => $contacts->perPage(),
                    'total' => $contacts->total(),
                    'from' => $contacts->firstItem(),
                    'to' => $contacts->lastItem()
                ],
                'message' => 'Contacts retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving contacts', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving contacts'
            ], 500);
        }
    }

    /**
     * Update contact sales status
     */
    public function updateContactStatus(Request $request, int $contact): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contacted_for_sales' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $contact = EventsGuest::where('user_id', Auth::id())->findOrFail($contact);

            $contact->update([
                'contacted_for_sales' => $request->contacted_for_sales,
                'contacted_at' => $request->contacted_for_sales ? now() : null
            ]);

            return response()->json([
                'success' => true,
                'data' => $contact->fresh(),
                'message' => 'Contact status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found'
            ], 404);
        }
    }
}