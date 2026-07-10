<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('leads') || !Schema::hasTable('lead_products') || !Schema::hasTable('products')) {
            return;
        }

        $hasLeadUserId = Schema::hasColumn('leads', 'user_id');
        $hasLeadBusinessId = Schema::hasColumn('leads', 'business_id');
        $hasProductUserId = Schema::hasColumn('products', 'user_id');
        $hasProductBusinessId = Schema::hasColumn('products', 'business_id');
        $hasActiveCampaign = Schema::hasColumn('products', 'is_active_campaign');

        $orphanLeadQuery = DB::table('leads')
            ->leftJoin('lead_products', 'leads.id', '=', 'lead_products.lead_id')
            ->whereNull('lead_products.id')
            ->select('leads.id');

        if ($hasLeadUserId) {
            $orphanLeadQuery->addSelect('leads.user_id');
        }

        if ($hasLeadBusinessId) {
            $orphanLeadQuery->addSelect('leads.business_id');
        }

        $orphanLeadIds = $orphanLeadQuery
            ->orderBy('leads.id')
            ->get();

        foreach ($orphanLeadIds as $lead) {
            $leadUserId = $hasLeadUserId ? ($lead->user_id ?? null) : null;
            $leadBusinessId = $hasLeadBusinessId ? ($lead->business_id ?? null) : null;

            $productId = null;

            // 1) User-scoped active campaign product
            if ($leadUserId && $hasProductUserId && $hasActiveCampaign) {
                $productId = DB::table('products')
                    ->where('user_id', $leadUserId)
                    ->where('is_active_campaign', true)
                    ->value('id');
            }

            if (!$productId) {
                // 2) Single active product for user
                if ($leadUserId && $hasProductUserId) {
                    $activeProducts = DB::table('products')
                        ->where('user_id', $leadUserId)
                        ->where('status', 'active')
                        ->pluck('id');

                    if ($activeProducts->count() === 1) {
                        $productId = $activeProducts->first();
                    }
                }
            }

            if (!$productId) {
                // 3) Any active product for user
                if ($leadUserId && $hasProductUserId) {
                    $productId = DB::table('products')
                        ->where('user_id', $leadUserId)
                        ->where('status', 'active')
                        ->orderBy('id')
                        ->value('id');
                }
            }

            if (!$productId) {
                // 4) Any product for user
                if ($leadUserId && $hasProductUserId) {
                    $productId = DB::table('products')
                        ->where('user_id', $leadUserId)
                        ->orderBy('id')
                        ->value('id');
                }
            }

            if (!$productId) {
                // 5) Business-scoped fallbacks
                if ($leadBusinessId && $hasProductBusinessId) {
                    $productId = DB::table('products')
                        ->where('business_id', $leadBusinessId)
                        ->where('status', 'active')
                        ->orderBy('id')
                        ->value('id');

                    if (!$productId) {
                        $productId = DB::table('products')
                            ->where('business_id', $leadBusinessId)
                            ->orderBy('id')
                            ->value('id');
                    }
                }
            }

            if (!$productId && $leadUserId && $hasProductUserId) {
                // 6) Auto-create a default product for the lead owner
                $generatedSku = 'AUTO-' . strtoupper(substr(md5($leadUserId . '-' . $lead->id . '-' . microtime(true)), 0, 10));

                $productId = DB::table('products')->insertGetId([
                    'user_id' => $leadUserId,
                    'business_id' => $leadBusinessId,
                    'name' => 'Auto Assigned Product',
                    'sku' => $generatedSku,
                    'category' => 'General',
                    'description' => 'System generated product for orphan lead backfill',
                    'retail_price' => 0,
                    'wholesale_price' => 0,
                    'max_discount' => 0,
                    'quantity' => null,
                    'status' => 'active',
                    'ai_generated_description' => false,
                    'minimal_description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if (!$productId) {
                // 7) Final global fallback without failing entire migration
                $productId = DB::table('products')
                    ->where('status', 'active')
                    ->orderBy('id')
                    ->value('id');

                if (!$productId) {
                    $productId = DB::table('products')->orderBy('id')->value('id');
                }
            }

            if (!$productId) {
                Log::warning('Skipping orphan lead backfill: unable to resolve product', [
                    'lead_id' => $lead->id,
                    'user_id' => $leadUserId,
                    'business_id' => $leadBusinessId,
                ]);
                continue;
            }

            DB::table('lead_products')->insert([
                'lead_id' => $lead->id,
                'product_id' => $productId,
                'status' => 'INTERESTED',
                'is_primary_product' => true,
                'quoted_price' => null,
                'discount_applied' => 0,
                'sales_notes' => null,
                'demo_scheduled_date' => null,
                'proposal_sent_date' => null,
                'last_interaction_at' => null,
                'negotiation_history' => null,
                'follow_up_count' => 0,
                'next_followup_at' => null,
                'followup_scheduled_by_customer' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty. This migration repairs orphaned lead-product data.
    }
};