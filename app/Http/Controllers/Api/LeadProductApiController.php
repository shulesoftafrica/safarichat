<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LeadProductApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Associate products with a lead
     * 
     * POST /api/leads/{leadId}/products
     */
    public function addProducts(Request $request, int $leadId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_ids' => 'required|array|min:1',
                'product_ids.*' => 'exists:products,id',
                'primary_product_id' => 'nullable|exists:products,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify lead belongs to user
            $lead = Lead::where('user_id', Auth::id())->findOrFail($leadId);

            $primaryProductId = $request->primary_product_id ?? $request->product_ids[0];
            $addedProducts = [];

            DB::beginTransaction();

            foreach ($request->product_ids as $productId) {
                // Check if product is already associated
                $existingAssociation = $lead->leadProducts()
                                          ->where('product_id', $productId)
                                          ->first();

                if ($existingAssociation) {
                    continue; // Skip if already associated
                }

                $leadProduct = $lead->leadProducts()->create([
                    'product_id' => $productId,
                    'status' => LeadProduct::STATUS_INTERESTED,
                    'is_primary_product' => $productId == $primaryProductId,
                    'is_active' => true
                ]);

                $product = Product::forUser(auth()->id())->find($productId);
                if (!$product) {
                    continue; // Skip products not owned by current user
                }
                $addedProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'status' => $leadProduct->status,
                    'is_primary' => $leadProduct->is_primary_product
                ];
            }

            // If we added a new primary product, update existing ones
            if ($primaryProductId && in_array($primaryProductId, $request->product_ids)) {
                $lead->leadProducts()
                    ->where('product_id', '!=', $primaryProductId)
                    ->update(['is_primary_product' => false]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $leadId,
                    'added_products' => $addedProducts,
                    'total_products' => $lead->leadProducts()->count()
                ],
                'message' => 'Products associated with lead successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding products to lead', [
                'error' => $e->getMessage(),
                'lead_id' => $leadId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Lead not found or error adding products'
            ], 404);
        }
    }

    /**
     * Get products associated with a lead
     * 
     * GET /api/leads/{leadId}/products
     */
    public function getLeadProducts(int $leadId): JsonResponse
    {
        try {
            $lead = Lead::where('user_id', Auth::id())
                       ->with(['leadProducts.product'])
                       ->findOrFail($leadId);

            $products = $lead->leadProducts->map(function($leadProduct) {
                return [
                    'id' => $leadProduct->product->id,
                    'name' => $leadProduct->product->name,
                    'description' => $leadProduct->product->description,
                    'price' => $leadProduct->product->price,
                    'status' => $leadProduct->status,
                    'is_primary' => $leadProduct->is_primary_product,
                    'quoted_price' => $leadProduct->quoted_price,
                    'discount_applied' => $leadProduct->discount_applied,
                    'last_interaction_at' => $leadProduct->last_interaction_at,
                    'demo_scheduled_date' => $leadProduct->demo_scheduled_date,
                    'proposal_sent_date' => $leadProduct->proposal_sent_date,
                    'follow_up_count' => $leadProduct->follow_up_count,
                    'sales_notes' => $leadProduct->sales_notes,
                    'created_at' => $leadProduct->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $leadId,
                    'lead_name' => $lead->name,
                    'products' => $products
                ],
                'message' => 'Lead products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }
    }

    /**
     * Update product-specific lead status
     * 
     * PUT /api/leads/{leadId}/products/{productId}/status
     */
    public function updateProductStatus(Request $request, int $leadId, int $productId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:' . implode(',', [
                    LeadProduct::STATUS_INTERESTED, LeadProduct::STATUS_PITCHED,
                    LeadProduct::STATUS_DEMO_REQUESTED, LeadProduct::STATUS_DEMO_COMPLETED,
                    LeadProduct::STATUS_PROPOSAL_SENT, LeadProduct::STATUS_NEGOTIATING,
                    LeadProduct::STATUS_CLOSED, LeadProduct::STATUS_LOST
                ]),
                'quoted_price' => 'nullable|numeric|min:0',
                'discount_applied' => 'nullable|numeric|min:0',
                'sales_notes' => 'nullable|string|max:1000',
                'demo_scheduled_date' => 'nullable|date|after:today',
                'proposal_sent_date' => 'nullable|date',
                'next_followup_at' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify lead belongs to user
            $lead = Lead::where('user_id', Auth::id())->findOrFail($leadId);

            $leadProduct = $lead->leadProducts()
                              ->where('product_id', $productId)
                              ->firstOrFail();

            $updateData = [
                'status' => $request->status,
                'last_interaction_at' => now()
            ];

            if ($request->has('quoted_price')) {
                $updateData['quoted_price'] = $request->quoted_price;
            }

            if ($request->has('discount_applied')) {
                $updateData['discount_applied'] = $request->discount_applied;
            }

            if ($request->has('sales_notes')) {
                $updateData['sales_notes'] = $request->sales_notes;
            }

            if ($request->has('demo_scheduled_date')) {
                $updateData['demo_scheduled_date'] = $request->demo_scheduled_date;
            }

            if ($request->has('proposal_sent_date')) {
                $updateData['proposal_sent_date'] = $request->proposal_sent_date;
            }

            if ($request->has('next_followup_at')) {
                $updateData['next_followup_at'] = $request->next_followup_at;
            }

            $leadProduct->update($updateData);

            // Update main lead's last interaction time
            $lead->update(['last_interaction_at' => now()]);

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $leadId,
                    'product_id' => $productId,
                    'product_name' => $leadProduct->product->name,
                    'status' => $leadProduct->status,
                    'quoted_price' => $leadProduct->quoted_price,
                    'discount_applied' => $leadProduct->discount_applied,
                    'last_interaction_at' => $leadProduct->last_interaction_at
                ],
                'message' => 'Product status updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead or product association not found'
            ], 404);
        }
    }

    /**
     * Remove product association from lead
     * 
     * DELETE /api/leads/{leadId}/products/{productId}
     */
    public function removeProduct(int $leadId, int $productId): JsonResponse
    {
        try {
            $lead = Lead::where('user_id', Auth::id())->findOrFail($leadId);

            $leadProduct = $lead->leadProducts()
                              ->where('product_id', $productId)
                              ->firstOrFail();

            // Don't allow removal if it's the only product
            if ($lead->leadProducts()->count() <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove the only product associated with this lead'
                ], 400);
            }

            $wasPrimary = $leadProduct->is_primary_product;
            $leadProduct->delete();

            // If removed product was primary, make another one primary
            if ($wasPrimary) {
                $newPrimary = $lead->leadProducts()->first();
                if ($newPrimary) {
                    $newPrimary->update(['is_primary_product' => true]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $leadId,
                    'removed_product_id' => $productId,
                    'remaining_products' => $lead->leadProducts()->count()
                ],
                'message' => 'Product removed from lead successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lead or product association not found'
            ], 404);
        }
    }

    /**
     * Set primary product for a lead
     * 
     * PUT /api/leads/{leadId}/products/{productId}/primary
     */
    public function setPrimaryProduct(int $leadId, int $productId): JsonResponse
    {
        try {
            $lead = Lead::where('user_id', Auth::id())->findOrFail($leadId);

            $leadProduct = $lead->leadProducts()
                              ->where('product_id', $productId)
                              ->firstOrFail();

            DB::beginTransaction();

            // Remove primary flag from all products
            $lead->leadProducts()->update(['is_primary_product' => false]);

            // Set the specified product as primary
            $leadProduct->update(['is_primary_product' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'lead_id' => $leadId,
                    'primary_product_id' => $productId,
                    'primary_product_name' => $leadProduct->product->name
                ],
                'message' => 'Primary product updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lead or product association not found'
            ], 404);
        }
    }

    /**
     * Get all leads for a specific product
     * 
     * GET /api/products/{productId}/leads
     */
    public function getLeadsByProduct(Request $request, int $productId): JsonResponse
    {
        try {
            // Verify product exists and user has access
            $product = Product::where('user_id', Auth::id())->findOrFail($productId);

            $query = Lead::where('user_id', Auth::id())
                        ->whereHas('leadProducts', function($q) use ($productId) {
                            $q->where('product_id', $productId);
                        })
                        ->with(['contact', 'leadProducts' => function($q) use ($productId) {
                            $q->where('product_id', $productId);
                        }]);

            // Filter by product status
            if ($request->has('product_status')) {
                $query->whereHas('leadProducts', function($q) use ($productId, $request) {
                    $q->where('product_id', $productId)
                      ->where('status', $request->product_status);
                });
            }

            // Filter by lead status
            if ($request->has('lead_status')) {
                $query->where('status', $request->lead_status);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');

            if ($sortBy === 'product_interaction') {
                $query->join('lead_products', function($join) use ($productId) {
                    $join->on('leads.id', '=', 'lead_products.lead_id')
                         ->where('lead_products.product_id', $productId);
                })->orderBy('lead_products.last_interaction_at', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }

            $perPage = min($request->get('per_page', 15), 50);
            $leads = $query->paginate($perPage);

            $formattedLeads = $leads->items()->map(function($lead) use ($productId) {
                $leadProduct = $lead->leadProducts->first();
                return [
                    'lead_id' => $lead->id,
                    'contact' => [
                        'id' => $lead->contact->id,
                        'name' => $lead->contact->guest_name,
                        'phone' => $lead->contact->guest_phone,
                        'email' => $lead->contact->guest_email
                    ],
                    'company_name' => $lead->company_name,
                    'lead_status' => $lead->status,
                    'lead_score' => $lead->lead_score,
                    'product_status' => $leadProduct->status,
                    'is_primary_product' => $leadProduct->is_primary_product,
                    'quoted_price' => $leadProduct->quoted_price,
                    'last_interaction_at' => $leadProduct->last_interaction_at,
                    'created_at' => $lead->created_at
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name
                    ],
                    'leads' => $formattedLeads
                ],
                'pagination' => [
                    'current_page' => $leads->currentPage(),
                    'last_page' => $leads->lastPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total()
                ],
                'message' => 'Product leads retrieved successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving product leads', [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Product not found or error retrieving leads'
            ], 404);
        }
    }
}