<?php

namespace App\Http\Controllers;

use App\Models\LineShopConnection;
use App\Models\Product;
use App\Services\LineShopConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LineShopController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Show the Connect LineShop page (value prop + form / connected state). */
    public function index()
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business
            ? LineShopConnection::where('business_id', $business->id)->first()
            : null;

        $manualProducts = $business
            ? Product::where('business_id', $business->id)->whereNull('external_source')->count()
            : 0;

        return view('lineshop.connect', compact('connection', 'manualProducts'));
    }

    /** Save + validate a connection (manual: store URL + client id + API key). */
    public function connect(Request $request, LineShopConnector $connector)
    {
        $data = $request->validate([
            'base_url'  => 'required|url|max:255',
            'client_id' => 'required|integer|min:1',
            'api_key'   => 'required|string|max:255',
        ]);

        $business = Auth::user()->effectiveBusiness();
        if (! $business) {
            return back()->withErrors(['lineshop' => 'No business found for your account.'])->withInput();
        }

        // Validate against LineShop before saving as "connected".
        $test = $connector->test($data['base_url'], (int) $data['client_id'], $data['api_key']);

        $connection = LineShopConnection::updateOrCreate(
            ['business_id' => $business->id],
            [
                'base_url'      => rtrim($data['base_url'], '/'),
                'client_id'     => (int) $data['client_id'],
                'api_key'       => $data['api_key'],
                'shop_name'     => $test['shop_name'] ?? null,
                'status'        => $test['ok'] ? LineShopConnection::STATUS_CONNECTED : LineShopConnection::STATUS_ERROR,
                'webhook_token' => Str::random(48),
                'last_error'    => $test['ok'] ? null : $test['message'],
            ]
        );

        if (! $test['ok']) {
            return back()->withErrors(['lineshop' => $test['message']])->withInput();
        }

        return redirect()->route('lineshop.index')
            ->with('lineshop_success', 'LineShop connected! Now click "Sync products" to pull your catalog.');
    }

    /** Pull/refresh the catalog from LineShop. */
    public function sync(LineShopConnector $connector)
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business ? LineShopConnection::where('business_id', $business->id)->first() : null;

        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'No LineShop connection found.'], 404);
        }

        $result = $connector->syncCatalog($connection);

        return response()->json([
            'success'        => $result['ok'],
            'message'        => $result['message'],
            'synced'         => $result['synced'],
            'product_count'  => $connection->fresh()->product_count,
            'last_synced_at' => optional($connection->fresh()->last_synced_at)->diffForHumans(),
        ], $result['ok'] ? 200 : 422);
    }

    /** Disconnect (keeps already-synced products; they just stop refreshing). */
    public function disconnect(Request $request)
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business ? LineShopConnection::where('business_id', $business->id)->first() : null;

        if ($connection) {
            $connection->delete();
        }

        return redirect()->route('lineshop.index')
            ->with('lineshop_success', 'LineShop disconnected. Your already-synced products were kept.');
    }
}
