<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventConfig;
use App\Services\ConversionEventService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public webhook endpoint third-party systems (LineShop, ShuleSoft, a payment
 * gateway, a website) POST to when a conversion happens. Authenticated only by
 * the unguessable {token} in the URL — one token per configured event.
 */
class EventHookController extends Controller
{
    public function handle(Request $request, string $token, ConversionEventService $service)
    {
        $config = EventConfig::where('token', $token)->first();

        if (!$config) {
            return response()->json(['status' => 'error', 'message' => 'Unknown event endpoint.'], 404);
        }

        if (!$config->is_enabled) {
            return response()->json(['status' => 'error', 'message' => 'This event is disabled.'], 403);
        }

        $payload = $request->all();
        if (empty($payload)) {
            // Accept a raw JSON body even when the content-type is not set.
            $raw = json_decode($request->getContent(), true);
            if (is_array($raw)) {
                $payload = $raw;
            }
        }

        try {
            $event = $service->handleWebhook($payload, $config);

            return response()->json([
                'status'   => 'ok',
                'event_id' => $event->id,
                'matched'  => $event->status !== \App\Models\ConversionEvent::STATUS_ORPHAN,
                'result'   => $event->status,
            ]);
        } catch (\Throwable $e) {
            Log::error('Event webhook failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error', 'message' => 'Could not process the event.'], 500);
        }
    }
}
