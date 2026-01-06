<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessContact;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CrmImportContactsController extends Controller
{
    /**
     * Import contacts from external CRM
     */
    public function importContacts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contacts' => 'required|array|max:1000',
            'contacts.*.crm_id' => 'required|string|max:255',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'required|string|max:20',
            'contacts.*.email' => 'nullable|email|max:255',
            'contacts.*.company' => 'nullable|string|max:255',
            'contacts.*.industry' => 'nullable|string|max:100',
            'contacts.*.crm_status' => 'nullable|string|max:100',
            'contacts.*.tags' => 'nullable|array',
            'contacts.*.tags.*' => 'string|max:50',
            'contacts.*.custom_fields' => 'nullable|array',
            'contacts.*.created_in_crm' => 'nullable|date',
            'contacts.*.updated_in_crm' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $imported = [];
        $skipped = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($request->contacts as $contactData) {
                try {
                    // Check if contact already exists by phone or CRM ID
                    $existingContact = BusinessContact::where('business_id', $user->business->id ?? null)
                        ->where(function ($query) use ($contactData) {
                            $query->where('guest_phone', $contactData['phone'])
                                  ->orWhere('crm_id', $contactData['crm_id']);
                        })
                        ->first();

                    if ($existingContact) {
                        $skipped[] = [
                            'crm_id' => $contactData['crm_id'],
                            'phone' => $contactData['phone'],
                            'reason' => 'Contact already exists',
                        ];
                        continue;
                    }

                    // Create new contact
                    $contact = BusinessContact::create([
                        'business_id' => $user->business->id ?? null,
                        'user_id' => $user->id,
                        'crm_id' => $contactData['crm_id'],
                        'guest_name' => $contactData['name'],
                        'guest_phone' => $contactData['phone'],
                        'guest_email' => $contactData['email'] ?? null,
                        'company' => $contactData['company'] ?? null,
                        'industry' => $contactData['industry'] ?? null,
                        'crm_data' => !empty($contactData['custom_fields']) ? json_encode($contactData['custom_fields']) : null,
                        'custom_data' => !empty($contactData['custom_fields']) ? json_encode($contactData['custom_fields']) : null,
                        'source' => 'crm_import',
                        'tags' => !empty($contactData['tags']) ? implode(',', $contactData['tags']) : null,
                        'imported_from_crm' => true,
                        'crm_created_at' => isset($contactData['created_in_crm']) ? Carbon::parse($contactData['created_in_crm']) : null,
                        'crm_updated_at' => isset($contactData['updated_in_crm']) ? Carbon::parse($contactData['updated_in_crm']) : null,
                    ]);

                    $imported[] = [
                        'id' => $contact->id,
                        'crm_id' => $contact->crm_id,
                        'name' => $contact->guest_name,
                        'phone' => $contact->guest_phone,
                        'email' => $contact->guest_email,
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'crm_id' => $contactData['crm_id'],
                        'error' => $e->getMessage(),
                    ];
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
                    'total_processed' => count($request->contacts),
                ],
                'message' => 'Contact import completed',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error importing contacts: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import conversation context for a contact
     */
    public function importContext(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contact_crm_id' => 'required|string|max:255',
            'import_strategy' => 'nullable|in:full,summarized,chunked',
            'conversations' => 'required_if:import_strategy,full,chunked|array|max:500',
            'conversations.*.message_content' => 'required_with:conversations|string',
            'conversations.*.sender_type' => 'required_with:conversations|in:customer,agent,system',
            'conversations.*.timestamp' => 'required_with:conversations|date',
            'conversations.*.crm_conversation_id' => 'nullable|string|max:255',
            'conversations.*.metadata' => 'nullable|array',
            'conversations.*.tags' => 'nullable|array',
            'conversations.*.importance' => 'nullable|in:low,normal,high',
            
            // For summarized import
            'conversation_summary' => 'required_if:import_strategy,summarized|array',
            'conversation_summary.total_messages' => 'required_with:conversation_summary|integer',
            'conversation_summary.date_range' => 'required_with:conversation_summary|array',
            'conversation_summary.key_interactions' => 'nullable|array',
            'conversation_summary.conversation_themes' => 'nullable|array',
            'conversation_summary.customer_sentiment' => 'nullable|string',
            'recent_messages' => 'nullable|array|max:20',
            
            // For chunked import
            'chunk_info' => 'required_if:import_strategy,chunked|array',
            'chunk_info.chunk_number' => 'required_with:chunk_info|integer|min:1',
            'chunk_info.total_chunks' => 'required_with:chunk_info|integer|min:1',
            
            // Context data
            'contact_background' => 'nullable|array',
            'previous_interactions' => 'nullable|array',
            'customer_preferences' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Find the contact
        $contact = BusinessContact::where('business_id', $user->business->id ?? null)
            ->where('crm_id', $request->contact_crm_id)
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found. Please import the contact first.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Find or create lead for this contact
            $lead = Lead::firstOrCreate([
                'business_id' => $user->business->id ?? null,
                'contact_id' => $contact->id,
            ], [
                'user_id' => $user->id,
                'status' => 'new',
                'source' => 'crm_import',
                'lead_score' => 50,
                'metadata' => [
                    'crm_imported' => true,
                    'import_date' => now()->toISOString(),
                    'contact_background' => $request->contact_background ?? null,
                    'previous_interactions' => $request->previous_interactions ?? null,
                    'customer_preferences' => $request->customer_preferences ?? null,
                ],
            ]);

            $importedConversations = [];
            $importStrategy = $request->import_strategy ?? 'full';

            if ($importStrategy === 'summarized') {
                // Handle summarized import
                $summaryData = $request->conversation_summary;
                
                // Create a summary conversation entry
                $conversation = Conversation::create([
                    'lead_id' => $lead->id,
                    'message_type' => 'SYSTEM',
                    'message_content' => "CRM Import Summary:\n" .
                        "Total Messages: {$summaryData['total_messages']}\n" .
                        "Date Range: {$summaryData['date_range']['start']} to {$summaryData['date_range']['end']}\n" .
                        "Themes: " . implode(', ', $summaryData['conversation_themes'] ?? []) . "\n" .
                        "Sentiment: {$summaryData['customer_sentiment']}\n" .
                        "Last Interaction: {$summaryData['last_interaction_summary']}",
                    'conversation_state' => 'IMPORTED_SUMMARY',
                    'is_imported' => true,
                    'import_type' => 'summarized',
                    'original_timestamp' => Carbon::parse($summaryData['date_range']['end']),
                    'metadata' => [
                        'key_interactions' => $summaryData['key_interactions'] ?? [],
                        'total_original_messages' => $summaryData['total_messages'],
                    ],
                ]);

                $importedConversations[] = $conversation;

                // Import recent messages if provided
                if (!empty($request->recent_messages)) {
                    foreach ($request->recent_messages as $messageData) {
                        $conv = $this->createConversationFromMessage($lead, $messageData, true);
                        $importedConversations[] = $conv;
                    }
                }

            } else {
                // Handle full or chunked import
                foreach ($request->conversations as $messageData) {
                    $conv = $this->createConversationFromMessage($lead, $messageData, true);
                    $importedConversations[] = $conv;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'contact' => [
                        'id' => $contact->id,
                        'crm_id' => $contact->crm_id,
                        'name' => $contact->name,
                    ],
                    'lead' => [
                        'id' => $lead->id,
                        'status' => $lead->status,
                    ],
                    'imported_conversations' => array_map(function ($conv) {
                        return [
                            'id' => $conv->id,
                            'crm_conversation_id' => $conv->crm_conversation_id ?? null,
                            'message_type' => $conv->message_type,
                            'sender_type' => $conv->sender_type ?? 'unknown',
                            'timestamp' => $conv->original_timestamp,
                            'message_preview' => substr($conv->message_content, 0, 100) . (strlen($conv->message_content) > 100 ? '...' : ''),
                        ];
                    }, $importedConversations),
                    'imported_count' => count($importedConversations),
                    'error_count' => 0,
                    'errors' => [],
                    'context_data' => [
                        'contact_background' => !empty($request->contact_background),
                        'previous_interactions' => !empty($request->previous_interactions),
                        'customer_preferences' => !empty($request->customer_preferences),
                    ],
                ],
                'message' => 'Context import completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error importing context: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get contact with full context
     */
    public function getContactContext(Request $request, string $crmId): JsonResponse
    {
        $user = $request->user();

        $contact = BusinessContact::where('business_id', $user->business->id ?? null)
            ->where('crm_id', $crmId)
            ->with(['leads.conversations' => function ($query) {
                $query->orderBy('original_timestamp', 'asc');
            }])
            ->first();

        if (!$contact) {
            return response()->json([
                'success' => false,
                'message' => 'Contact not found',
            ], 404);
        }

        $lead = $contact->leads->first();

        return response()->json([
            'success' => true,
            'data' => [
                'contact' => [
                    'id' => $contact->id,
                    'crm_id' => $contact->crm_id,
                    'name' => $contact->guest_name,
                    'phone' => $contact->guest_phone,
                    'email' => $contact->guest_email,
                    'crm_data' => [
                        'company' => $contact->company,
                        'industry' => $contact->industry,
                        'context_imported' => (bool) $contact->imported_from_crm,
                        'conversation_count' => $lead ? $lead->conversations->count() : 0,
                    ],
                ],
                'lead' => $lead ? [
                    'id' => $lead->id,
                    'status' => $lead->status,
                    'lead_score' => $lead->lead_score,
                    'metadata' => $lead->metadata,
                ] : null,
                'conversations' => $lead ? $lead->conversations->map(function ($conv) {
                    return [
                        'id' => $conv->id,
                        'message_type' => $conv->message_type,
                        'sender_type' => $conv->sender_type ?? 'unknown',
                        'message_content' => $conv->message_content,
                        'conversation_state' => $conv->conversation_state,
                        'is_imported' => (bool) $conv->is_imported,
                        'original_timestamp' => $conv->original_timestamp,
                        'created_at' => $conv->created_at,
                    ];
                }) : [],
            ],
            'message' => 'Contact context retrieved successfully',
        ]);
    }

    /**
     * Helper method to create conversation from message data
     */
    private function createConversationFromMessage(Lead $lead, array $messageData, bool $isImported = false): Conversation
    {
        $messageType = match (strtolower($messageData['sender_type'])) {
            'customer' => 'CUSTOMER',
            'agent' => 'AGENT',
            'system' => 'SYSTEM',
            default => 'CUSTOMER',
        };

        return Conversation::create([
            'lead_id' => $lead->id,
            'message_type' => $messageType,
            'message_content' => $messageData['message_content'],
            'conversation_state' => $this->determineConversationState($messageData['message_content']),
            'is_imported' => $isImported,
            'import_type' => 'full',
            'sender_type' => $messageData['sender_type'],
            'crm_conversation_id' => $messageData['crm_conversation_id'] ?? null,
            'original_timestamp' => Carbon::parse($messageData['timestamp']),
            'metadata' => array_merge(
                $messageData['metadata'] ?? [],
                [
                    'tags' => $messageData['tags'] ?? [],
                    'importance' => $messageData['importance'] ?? 'normal',
                ]
            ),
        ]);
    }

    /**
     * Determine conversation state from message content
     */
    private function determineConversationState(string $content): string
    {
        $content = strtolower($content);

        if (str_contains($content, 'hello') || str_contains($content, 'hi ') || str_contains($content, 'good morning')) {
            return 'INTRO';
        }

        if (str_contains($content, '?') && (str_contains($content, 'price') || str_contains($content, 'cost'))) {
            return 'PRICING_INQUIRY';
        }

        if (str_contains($content, 'thank') && str_contains($content, 'buy')) {
            return 'PURCHASE_INTENT';
        }

        return 'GENERAL';
    }
}
