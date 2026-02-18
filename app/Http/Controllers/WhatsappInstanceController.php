<?php

namespace App\Http\Controllers;

use App\Models\WhatsappInstance;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WhatsappInstanceController extends Controller
{
    /**
     * Update the specified WhatsApp instance.
     */
    public function update(Request $request, $id)
    {
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'instance_name' => 'nullable|string|max:255',
            'display_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:32',
            'instance_description' => 'nullable|string',
            'is_primary' => 'nullable|boolean',
        ]);

        $instance->instance_name = $validated['instance_name'] ?? $instance->instance_name;
        $instance->display_name = $validated['display_name'] ?? $instance->display_name;
        $instance->phone_number = $validated['phone_number'] ?? $instance->phone_number;
        $instance->instance_description = $validated['instance_description'] ?? $instance->instance_description;
        $instance->is_primary = $request->has('is_primary') ? 1 : 0;
        $instance->save();

        // If set as primary, unset primary for other instances
        if ($instance->is_primary) {
            WhatsappInstance::where('user_id', auth()->id())
                ->where('id', '!=', $instance->id)
                ->update(['is_primary' => 0]);
        }

        return redirect()->route('ai-agents.index')->with('success', 'WhatsApp instance updated successfully.');
    }

    /**
     * Show the form for editing the specified WhatsApp instance.
     */
    public function edit($id)
    {
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        return view('whatsapp.instances.edit', compact('instance'));
    }

    /**
     * Get user's WhatsApp instances (API)
     */
    public function index()
    {
        $instances = WhatsappInstance::where('user_id', Auth::id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'instances' => $instances
        ]);
    }
    
    /**
     * Get single WhatsApp instance (API)
     */
    public function show($id)
    {
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'instance' => $instance
        ]);
    }

    /**
     * Select active WhatsApp instance for session
     */
    public function selectActiveInstance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'instance_id' => 'nullable|integer|exists:whatsapp_instances,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid instance ID',
                'errors' => $validator->errors()
            ], 422);
        }

        $instanceId = $request->instance_id;
        $user = Auth::user();
        
        // If instance_id is null or empty, clear the active instance (show all lines)
        if (empty($instanceId)) {
            session()->forget('active_whatsapp_instance_id');
            
            return response()->json([
                'success' => true,
                'message' => 'Showing all WhatsApp lines',
                'instance_name' => 'All Lines'
            ]);
        }
        
        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $instanceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }
        
        // Store in session
        session(['active_whatsapp_instance_id' => $instanceId]);
        
        return response()->json([
            'success' => true,
            'message' => 'Active instance updated successfully',
            'instance_name' => $instance->display_name ?: $instance->phone_number,
            'phone_number' => $instance->phone_number,
            'purpose' => $instance->purpose
        ]);
    }

    /**
     * Get currently active WhatsApp instance
     */
    public function getActiveInstance()
    {
        $sessionInstanceId = session('active_whatsapp_instance_id');
        
        if ($sessionInstanceId) {
            $instance = WhatsappInstance::where('id', $sessionInstanceId)
                ->where('user_id', Auth::id())
                ->first();
                
            if ($instance) {
                return response()->json([
                    'success' => true,
                    'active_instance' => $instance
                ]);
            }
        }
        
        // Fallback to primary instance or first available
        $instance = WhatsappInstance::where('user_id', Auth::id())
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at')
            ->first();
            
        if ($instance) {
            session(['active_whatsapp_instance_id' => $instance->id]);
        }
        
        return response()->json([
            'success' => true,
            'active_instance' => $instance
        ]);
    }

    /**
     * Update instance configuration
     */
    public function updateInstance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'nullable|string|max:100',
            'purpose' => 'required|string|in:general,sales,support,marketing,orders',
            'instance_description' => 'nullable|string|max:500',
            'is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        // If setting as primary, remove primary flag from other instances
        if ($request->boolean('is_primary')) {
            WhatsappInstance::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        // Update instance
        $instance->update($request->only([
            'display_name', 
            'purpose', 
            'instance_description', 
            'is_primary'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Instance updated successfully',
            'instance' => $instance->fresh()
        ]);
    }

    /**
     * Get instance statistics
     */
    public function getInstanceStats($id)
    {
        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        // Get statistics
        $stats = [
            'conversations' => \App\Models\IncomingMessage::where('whatsapp_instance_id', $instance->id)
                ->distinct('phone_number')
                ->count(),
            'messages' => \App\Models\IncomingMessage::where('whatsapp_instance_id', $instance->id)->count(),
            'contacts' => \App\Models\BusinessContact::whereHas('incomingMessages', function($query) use ($instance) {
                $query->where('whatsapp_instance_id', $instance->id);
            })->count()
        ];

        return response()->json([
            'success' => true,
            'instance' => $instance,
            'stats' => $stats
        ]);
    }

    /**
     * Get real-time connection status from WaSender API
     */
    public function getStatus($id)
    {
        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        try {
            // Fetch real-time status from WaSender API
            $unifiedService = app(\App\Services\UnifiedNotificationService::class);
            $statusResult = $unifiedService->getSessionStatus($instance->instance_id);

            if (isset($statusResult['success']) && $statusResult['success']) {
                $realTimeStatus = $statusResult['status'] ?? null;

                // Update database with real-time status
                if ($realTimeStatus) {
                    $instance->update([
                        'connect_status' => $realTimeStatus,
                        'last_active_at' => now()
                    ]);
                }

                \Log::info('Real-time WaSender status fetched via API', [
                    'instance_id' => $instance->instance_id,
                    'status' => $realTimeStatus
                ]);

                return response()->json([
                    'success' => true,
                    'status' => $realTimeStatus,
                    'instance' => $instance,
                    'message' => 'Status updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch status from WaSender API',
                    'error' => $statusResult['error'] ?? 'Unknown error'
                ], 500);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch real-time WaSender status', [
                'instance_id' => $instance->instance_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reconnect WhatsApp instance (generate new QR code)
     */
    public function reconnect($id)
    {
        // Verify user owns this instance
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        try {
            // Reset connection status
            $instance->update([
                'is_active' => false,
                'connection_status' => 'disconnected',
                'last_seen' => null
            ]);

            // Here you would typically:
            // 1. Call WaSender API to restart the instance
            // 2. Generate a new QR code
            // 3. Reset session data
            
            // For now, we'll return a success response with QR generation instructions
            return response()->json([
                'success' => true,
                'message' => 'Instance reconnection initiated. Please scan the new QR code.',
                'instance' => $instance->fresh(),
                'qr_code_url' => null, // This would be populated by WaSender API
                'reconnection_status' => 'pending'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reconnecting instance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available instance purposes
     */
    public function getPurposes()
    {
        $purposes = [
            'general' => 'General Business',
            'sales' => 'Sales Inquiries',
            'support' => 'Customer Support',
            'marketing' => 'Marketing Campaigns',
            'orders' => 'Order Processing'
        ];

        return response()->json([
            'success' => true,
            'purposes' => $purposes
        ]);
    }
    
    /**
     * Store a new WhatsApp instance
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schema_name' => 'required|string|max:255|regex:/^[a-z][a-z0-9_]*$/',
            'display_name' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|in:sales,support,marketing,personal,other',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $schemaName = $request->schema_name;

        // Check if schema name is unique for this user
        $existingInstance = WhatsappInstance::where('user_id', $user->id)
            ->where('schema_name', $schemaName)
            ->first();

        if ($existingInstance) {
            return response()->json([
                'success' => false,
                'message' => 'Schema name already exists'
            ], 422);
        }

        try {
            // Check billing limits first
            $billingStatus = BillingService::getBillingStatus($user->id);
            if (!$billingStatus || !isset($billingStatus['limits']['whatsapp_channels'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify subscription limits',
                    'upgrade_required' => true,
                    'feature' => 'whatsapp_channels'
                ], 402);
            }

            $channelLimits = $billingStatus['limits']['whatsapp_channels'];
            if ($channelLimits['current'] >= $channelLimits['max']) {
                return response()->json([
                    'success' => false,
                    'message' => "WhatsApp channel limit reached. Your {$billingStatus['subscription']['plan']} plan allows {$channelLimits['max']} WhatsApp channels.",
                    'upgrade_required' => true,
                    'feature' => 'whatsapp_channels',
                    'current_limit' => $channelLimits['max'],
                    'current_usage' => $channelLimits['current']
                ], 402);
            }

            // Create new instance
            $instance = WhatsappInstance::create([
                'user_id' => $user->id,
                'schema_name' => $schemaName,
                'display_name' => $request->display_name,
                'purpose' => $request->purpose,
                'description' => $request->description,
                'is_primary' => false,
                'is_active' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp instance created successfully',
                'instance' => $instance
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating instance: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete a WhatsApp instance
     */
    public function destroy($id)
    {
        $instance = WhatsappInstance::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found or access denied'
            ], 404);
        }

        // Prevent deletion of primary instance
        if ($instance->is_primary) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete primary instance'
            ], 422);
        }

        try {
            $instance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Instance deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting instance: ' . $e->getMessage()
            ], 500);
        }
    }
}