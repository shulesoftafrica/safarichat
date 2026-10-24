<?php

namespace App\Http\Controllers;

use App\Models\CatalogApiConnection;
use App\Models\Product;
use App\Services\GenericApiCatalogConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CatalogApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business
            ? CatalogApiConnection::where('business_id', $business->id)->first()
            : null;

        return view('catalog-api.connect', compact('connection'));
    }

    public function connect(Request $request, GenericApiCatalogConnector $connector)
    {
        $data = $request->validate([
            'name'         => 'nullable|string|max:100',
            'base_url'     => 'required|string|max:255',
            'catalog_path' => 'nullable|string|max:191',
            'auth_type'    => 'required|in:bearer,header,none',
            'auth_header'  => 'nullable|string|max:100',
            'auth_value'   => 'nullable|string|max:500',
            'page_param'   => 'nullable|string|max:50',
        ]);

        $business = Auth::user()->effectiveBusiness();
        if (! $business) {
            return back()->withErrors(['api' => 'No business found for your account.'])->withInput();
        }

        $cfg = [
            'base_url'     => $data['base_url'],
            'catalog_path' => $data['catalog_path'] ?: '/products',
            'auth_type'    => $data['auth_type'],
            'auth_header'  => $data['auth_header'] ?: 'Authorization',
            'auth_value'   => $data['auth_value'] ?? '',
        ];

        $test = $connector->test($cfg);

        $connection = CatalogApiConnection::updateOrCreate(
            ['business_id' => $business->id],
            [
                'name'         => $data['name'] ?? null,
                'base_url'     => rtrim($data['base_url'], '/'),
                'catalog_path' => $cfg['catalog_path'],
                'auth_type'    => $cfg['auth_type'],
                'auth_header'  => $cfg['auth_header'],
                'auth_value'   => $cfg['auth_value'],
                'page_param'   => $data['page_param'] ?: 'page',
                'status'       => $test['ok'] ? CatalogApiConnection::STATUS_CONNECTED : CatalogApiConnection::STATUS_ERROR,
                'last_error'   => $test['ok'] ? null : $test['message'],
            ]
        );

        if (! $test['ok']) {
            return back()->withErrors(['api' => $test['message']])->withInput();
        }

        return redirect()->route('catalog-api.index')
            ->with('api_success', 'Connected! Now click "Sync products" to pull your catalog.');
    }

    public function sync(GenericApiCatalogConnector $connector)
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business ? CatalogApiConnection::where('business_id', $business->id)->first() : null;
        if (! $connection) {
            return response()->json(['success' => false, 'message' => 'No API connection found.'], 404);
        }

        $result = $connector->sync($connection);

        return response()->json([
            'success'        => $result['ok'],
            'message'        => $result['message'],
            'product_count'  => $connection->fresh()->product_count,
            'last_synced_at' => optional($connection->fresh()->last_synced_at)->diffForHumans(),
        ], $result['ok'] ? 200 : 422);
    }

    public function disconnect()
    {
        $business = Auth::user()->effectiveBusiness();
        $connection = $business ? CatalogApiConnection::where('business_id', $business->id)->first() : null;
        if ($connection) {
            $connection->delete();
        }
        return redirect()->route('catalog-api.index')
            ->with('api_success', 'Disconnected. Your already-synced products were kept.');
    }
}
