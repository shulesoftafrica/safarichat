<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventsGuest;
use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CrmImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Import contacts from external CRM
     * 
     * POST /api/crm/import/contacts
     */
    public function importContacts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contacts' => 'required|array|min:1|max:1000',
                'contacts.*.crm_id' => 'required|string',
                'contacts.*.name' => 'required|string|max:255',
                'contacts.*.phone' => 'required|string|max:20',
                'contacts.*.email' => 'nullable|email',
                'contacts.*.company' => 'nullable|string|max:255',
                'contacts.*.industry' => 'nullable|string|max:100',
                'contacts.*.crm_status' => 'nullable|string|max:50',
                'contacts.*.tags' => 'nullable|array',
                'contacts.*.custom_fields' => 'nullable|array',
                'contacts.*.created_in_crm' => 'nullable|date',
                'contacts.*.updated_in_crm' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $contacts = $request->contacts;
            $imported = [];
            $skipped = [];
            $errors = [];
            $userId = Auth::id();

            DB::beginTransaction();

            foreach ($contacts as $index => $contactData) {
                try {
                    // Check if contact already exists by phone or CRM ID
                    $existingContact = EventsGuest::where('user_id', $userId)
                                                 ->where(function($query) use ($contactData) {
                                                     $query->where('guest_phone', $contactData['phone'])
                                                           ->orWhere('crm_id', $contactData['crm_id']);
                                                 })->first();

                    if ($existingContact) {
                        $skipped[] = [
                            'crm_id' => $contactData['crm_id'],
                            'name' => $contactData['name'],
                            'reason' => 'Contact already exists'
                        ];
                        continue;
                    }

                    // Create new contact
                    $newContact = EventsGuest::create([
                        'user_id' => $userId,
                        'guest_name' => $contactData['name'],
                        'guest_phone' => $contactData['phone'],
                        'guest_email' => $contactData['email'],
                        'crm_id' => $contactData['crm_id'],
                        'contacted_for_sales' => false,
                        'crm_data' => [
                            'company' => $contactData['company'] ?? null,
                            'industry' => $contactData['industry'] ?? null,
                            'crm_status' => $contactData['crm_status'] ?? null,
                            'tags' => $contactData['tags'] ?? [],
                            'custom_fields' => $contactData['custom_fields'] ?? [],
                            'created_in_crm' => $contactData['created_in_crm'] ?? null,
                            'updated_in_crm' => $contactData['updated_in_crm'] ?? null,
                            'imported_at' => now()->toISOString()
                        ]
                    ]);

                    $imported[] = [
                        'id' => $newContact->id,
                        'crm_id' => $contactData['crm_id'],
                        'name' => $contactData['name'],
                        'phone' => $contactData['phone'],
                        'email' => $contactData['email']
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Contact at index {$index} (CRM ID: {$contactData['crm_id']}): " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'imported_count' => count($imported),
                    'skipped_count' => count($skipped),
                    'error_count' => count($errors),
                    'errors' => $errors,
                    'total_processed' => count($contacts)
                ],
                'message' => 'Contact import completed'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing contacts from CRM', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error importing contacts'
            ], 500);
        }
    }

    /**
     * Import conversation context from external CRM
     * 
     * POST /api/crm/import/context
     */
    public function importContext(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'contact_crm_id' => 'required|string',
                'conversations' => 'required|array|min:1|max:500',
                'conversations.*.message_content' => 'required|string|max:4000',
                'conversations.*.sender_type' => 'required|in:customer,agent,system',
                'conversations.*.timestamp' => 'required|date',
                'conversations.*.crm_conversation_id' => 'nullable|string',
                'conversations.*.metadata' => 'nullable|array',
                'conversations.*.tags' => 'nullable|array',
                'contact_background' => 'nullable|array',
                'previous_interactions' => 'nullable|array',
                'customer_preferences' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Find the contact by CRM ID
            $userId = Auth::id();
            $crmId = $request->contact_crm_id;
            
            \Log::info("Looking for contact", [
                'user_id' => $userId,
                'crm_id' => $crmId
            ]);
            
            $contact = EventsGuest::where('user_id', $userId)
                                 ->where('crm_id', $crmId)
                                 ->first();

            if (!$contact) {
                \Log::warning("Contact not found", [
                    'user_id' => $userId,
                    'crm_id' => $crmId,
                    'total_contacts' => EventsGuest::where('user_id', $userId)->count()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found. Please import the contact first.'
                ], 404);
            }

            // Find or create a lead for this contact
            $lead = Lead::firstOrCreate(
                [
                    'events_guest_id' => $contact->id,
                    'user_id' => Auth::id()
                ],
                [
                    'name' => $contact->guest_name,
                    'phone_number' => $contact->guest_phone,
                    'email' => $contact->guest_email,
                    'source' => 'crm_import',
                    'status' => Lead::STATUS_NEW,
                    'lead_score' => 50,
                    'metadata' => [
                        'crm_imported' => true,
                        'contact_background' => $request->contact_background ?? [],
                        'previous_interactions' => $request->previous_interactions ?? [],
                        'customer_preferences' => $request->customer_preferences ?? []
                    ]
                ]
            );

            $importedConversations = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->conversations as $index => $conversationData) {
                try {
                    // Map sender type
                    $messageType = $this->mapSenderToMessageType($conversationData['sender_type']);
                    
                    $conversation = Conversation::create([
                        'lead_id' => $lead->id,
                        'message_type' => $messageType,
                        'sender_type' => $conversationData['sender_type'],
                        'message_content' => $conversationData['message_content'],
                        'conversation_state' => 'INTRO', // Default state for imported conversations
                        'is_active' => false, // Mark as historical
                        'crm_conversation_id' => $conversationData['crm_conversation_id'] ?? null,
                        'conversation_context' => [
                            'imported_from_crm' => true,
                            'original_timestamp' => $conversationData['timestamp'],
                            'metadata' => $conversationData['metadata'] ?? [],
                            'tags' => $conversationData['tags'] ?? [],
                            'imported_at' => now()->toISOString()
                        ],
                        'created_at' => $conversationData['timestamp'],
                        'updated_at' => now()
                    ]);

                    $importedConversations[] = [
                        'id' => $conversation->id,
                        'crm_conversation_id' => $conversationData['crm_conversation_id'] ?? null,
                        'message_type' => $messageType,
                        'sender_type' => $conversationData['sender_type'],
                        'timestamp' => $conversationData['timestamp'],
                        'message_preview' => substr($conversationData['message_content'], 0, 100) . '...'
                    ];

                } catch (\Exception $e) {
                    $errors[] = "Conversation at index {$index}: " . $e->getMessage();
                }
            }

            DB::commit();

            // Update contact with context information
            $contact->update([
                'crm_data' => array_merge($contact->crm_data ?? [], [
                    'context_imported' => true,
                    'context_imported_at' => now()->toISOString(),
                    'conversation_count' => count($importedConversations)
                ])
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'contact' => [
                        'id' => $contact->id,
                        'crm_id' => $contact->crm_id,
                        'name' => $contact->guest_name
                    ],
                    'lead' => [
                        'id' => $lead->id,
                        'status' => $lead->status
                    ],
                    'imported_conversations' => $importedConversations,
                    'imported_count' => count($importedConversations),
                    'error_count' => count($errors),
                    'errors' => $errors,
                    'context_data' => [
                        'contact_background' => !empty($request->contact_background),
                        'previous_interactions' => !empty($request->previous_interactions),
                        'customer_preferences' => !empty($request->customer_preferences)
                    ]
                ],
                'message' => 'Context import completed successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing context from CRM', [
                'error' => $e->getMessage(),
                'contact_crm_id' => $request->contact_crm_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error importing context'
            ], 500);
        }
    }

    /**
     * Get imported contact with context
     * 
     * GET /api/crm/contacts/{crm_id}/context
     */
    public function getContactContext(string $crmId): JsonResponse
    {
        try {
            $contact = EventsGuest::where('user_id', Auth::id())
                                 ->where('crm_id', $crmId)
                                 ->first();

            if (!$contact) {
                return response()->json([
                    'success' => false,
                    'message' => 'Contact not found'
                ], 404);
            }

            $lead = Lead::where('events_guest_id', $contact->id)->first();
            
            $context = [
                'contact' => [
                    'id' => $contact->id,
                    'crm_id' => $contact->crm_id,
                    'name' => $contact->guest_name,
                    'phone' => $contact->guest_phone,
                    'email' => $contact->guest_email,
                    'crm_data' => $contact->crm_data
                ],
                'lead' => $lead ? [
                    'id' => $lead->id,
                    'status' => $lead->status,
                    'lead_score' => $lead->lead_score,
                    'metadata' => $lead->metadata
                ] : null,
                'conversations' => []
            ];

            if ($lead) {
                $conversations = Conversation::where('lead_id', $lead->id)
                                           ->orderBy('created_at', 'asc')
                                           ->get();

                $context['conversations'] = $conversations->map(function($conv) {
                    return [
                        'id' => $conv->id,
                        'message_type' => $conv->message_type,
                        'sender_type' => $conv->sender_type,
                        'message_content' => $conv->message_content,
                        'conversation_state' => $conv->conversation_state,
                        'is_imported' => isset($conv->conversation_context['imported_from_crm']),
                        'original_timestamp' => $conv->conversation_context['original_timestamp'] ?? null,
                        'created_at' => $conv->created_at
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $context,
                'message' => 'Contact context retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving contact context', [
                'error' => $e->getMessage(),
                'crm_id' => $crmId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving context'
            ], 500);
        }
    }

    /**
     * Map sender type to message type
     */
    private function mapSenderToMessageType(string $senderType): string
    {
        switch ($senderType) {
            case 'customer':
                return Conversation::TYPE_CUSTOMER;
            case 'agent':
                return Conversation::TYPE_HUMAN_AGENT;
            case 'system':
                return Conversation::TYPE_AI_AGENT;
            default:
                return Conversation::TYPE_CUSTOMER;
        }
    }
}