<?php

namespace App\Observers;

use App\Events\CsFirstProductCreated;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Fire CsFirstProductCreated when the very first product for a user is saved.
     * The count check runs AFTER the record is persisted, so count() === 1 means
     * this is definitively the user's first and only product.
     */
    public function created(Product $product): void
    {
        $userId = $product->user_id;

        if (!$userId) {
            return;
        }

        $total = Product::where('user_id', $userId)->count();

        if ($total === 1) {
            $user = User::find($userId);

            if (!$user) {
                return;
            }

            Log::info('ProductObserver: first product created, firing CsFirstProductCreated', [
                'user_id'    => $userId,
                'product_id' => $product->id,
            ]);

            CsFirstProductCreated::dispatch($product, $user);
        }
    }
}
