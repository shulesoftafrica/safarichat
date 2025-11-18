<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaapiController extends Controller
{
    private $baseUrl = 'https://waapi.app/api/v1';
    public $token;

    public function __construct()
    {
        $this->token = config('app.waapi_token');
    }

    /**
     * Test WAAPI connection
     */
    public function testConnection()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ])->get($this->baseUrl . '/instances');

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'WAAPI connection successful',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'WAAPI connection failed',
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'WAAPI connection error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all instances for current user
     */
    public function getUserInstances()
    {
        try {
            $userId = auth()->id();
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $instances = DB::table('whatsapp_instances')
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $instances
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get instances: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a test message
     */
    public function sendTestMessage(Request $request)
    {
        try {
            $request->validate([
                'instance_id' => 'required|string',
                'chat_id' => 'required|string',
                'message' => 'required|string'
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json'
            ])->post($this->baseUrl . '/instances/' . $request->instance_id . '/client/action/send-message', [
                'chatId' => $request->chat_id,
                'message' => $request->message
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'data' => $response->json()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send message',
                    'error' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test queue processing with a message send
     */
    public function testQueueMessage(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|string',
                'message' => 'required|string',
                'instance_id' => 'nullable|string'
            ]);

            $phoneNumber = $request->phone_number;
            $message = $request->message;
            $instanceId = $request->instance_id;
            $userId = auth()->id() ?? 1;

            // Dispatch message to queue
            \App\Jobs\SendWhatsAppMessage::dispatch(
                $message,
                $phoneNumber,
                'whatsapp',
                $userId,
                null,
                $instanceId
            );

            return response()->json([
                'success' => true,
                'message' => 'Message queued successfully',
                'data' => [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'queue' => 'high_priority',
                    'user_id' => $userId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error queuing message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test incoming message processing
     */
    public function testIncomingMessage(Request $request)
    {
        try {
            $request->validate([
                'instance_id' => 'required|string',
                'phone_number' => 'required|string',
                'message' => 'required|string'
            ]);

            // Simulate incoming message payload
            $messageData = [
                'id' => 'test_' . uniqid(),
                'chatId' => $request->phone_number . '@c.us',
                'fromMe' => false,
                'body' => $request->message,
                'timestamp' => time(),
                'senderName' => 'Test User',
                'isGroup' => false
            ];

            // Find WhatsApp instance
            $whatsappInstance = DB::table('whatsapp_instances')
                ->where('instance_id', $request->instance_id)
                ->first();

            if (!$whatsappInstance) {
                return response()->json([
                    'success' => false,
                    'message' => 'WhatsApp instance not found'
                ], 404);
            }

            // Dispatch to incoming message processing queue
            \App\Jobs\ProcessIncomingMessage::dispatch(
                $messageData,
                $request->instance_id,
                $whatsappInstance
            );

            return response()->json([
                'success' => true,
                'message' => 'Incoming message queued for processing',
                'data' => [
                    'message_id' => $messageData['id'],
                    'phone_number' => $request->phone_number,
                    'instance_id' => $request->instance_id,
                    'queue' => 'high_priority'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error testing incoming message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        try {
            // Get queue counts from database
            $stats = [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
                'queue_breakdown' => DB::table('jobs')
                    ->select('queue', DB::raw('COUNT(*) as count'))
                    ->groupBy('queue')
                    ->get(),
                'recent_jobs' => DB::table('jobs')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(['id', 'queue', 'attempts', 'created_at']),
                'recent_failed_jobs' => DB::table('failed_jobs')
                    ->orderBy('failed_at', 'desc')
                    ->limit(5)
                    ->get(['id', 'queue', 'exception', 'failed_at'])
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting queue stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs()
    {
        try {
            $deletedCount = DB::table('failed_jobs')->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} failed jobs"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing failed jobs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retry failed jobs
     */
    public function retryFailedJobs()
    {
        try {
            // Laravel's built-in command to retry failed jobs
            \Artisan::call('queue:retry', ['id' => 'all']);
            $output = \Artisan::output();
            
            return response()->json([
                'success' => true,
                'message' => 'Failed jobs retried',
                'output' => $output
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrying failed jobs: ' . $e->getMessage()
            ], 500);
        }
    }
}
