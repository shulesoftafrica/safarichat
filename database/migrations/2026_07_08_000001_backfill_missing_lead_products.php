<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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

        $orphanLeadIds = DB::table('leads')
            ->leftJoin('lead_products', 'leads.id', '=', 'lead_products.lead_id')
            ->whereNull('lead_products.id')
            ->select('leads.id', 'leads.user_id')
            ->orderBy('leads.id')
            ->get();

        foreach ($orphanLeadIds as $lead) {
            $productId = DB::table('products')
                ->where('user_id', $lead->user_id)
                ->where('is_active_campaign', true)
                ->value('id');

            if (!$productId) {
                $activeProducts = DB::table('products')
                    ->where('user_id', $lead->user_id)
                    ->where('status', 'active')
                    ->pluck('id');

                if ($activeProducts->count() === 1) {
                    $productId = $activeProducts->first();
                }
            }

            if (!$productId) {
                throw new RuntimeException(
                    "Lead {$lead->id} has no product association and no active campaign or single active product fallback could be resolved."
                );
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