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
}
