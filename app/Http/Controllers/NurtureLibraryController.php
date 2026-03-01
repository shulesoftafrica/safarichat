<?php

namespace App\Http\Controllers;

use App\Models\NurtureLibrary;
use App\Models\Product;
use App\Services\NurtureLibraryGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NurtureLibraryController extends Controller
{
    protected $generator;

    public function __construct(NurtureLibraryGenerator $generator)
    {
        // Temporarily disable auth middleware for testing
        // $this->middleware('auth');
        $this->generator = $generator;
    }

    /**
     * Generate nurture messages for a product
     * 
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateForProduct(Product $product)
    {
        // Verify ownership
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $messages = $this->generator->generateForProduct($product);
            
            return response()->json([
                'success' => true,
                'count' => $messages->count(),
                'messages' => $messages,
                'message' => "Generated {$messages->count()} nurture messages for {$product->name}"
            ]);
            
        } catch (\Exception $e) {
            Log::error("Nurture generation failed for product {$product->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate nurture messages. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Regenerate nurture messages for a product
     * (Keeps high performers, generates new ones)
     * 
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function regenerateForProduct(Product $product)
    {
        // Verify ownership
        if ($product->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $result = $this->generator->regenerateForProduct($product);
            
            return response()->json([
                'success' => true,
                'kept' => $result['kept'],
                'deleted' => $result['deleted'],
                'generated' => $result['generated'],
                'total' => $result['total'],
                'message' => "Kept {$result['kept']} high performers, generated {$result['generated']} new messages"
            ]);
            
        } catch (\Exception $e) {
            Log::error("Nurture regeneration failed for product {$product->id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to regenerate nurture messages. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all nurture messages for a product
     * 
     * @param int $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($product)
    {
        try {
            Log::info('Nurture Messages Index - Auth check', [
                'auth_id' => Auth::id(),
                'auth_check' => Auth::check(),
                'product_id' => $product,
                'user' => Auth::user() ? Auth::user()->id : 'null'
            ]);
            
            // For now, bypass ownership check during testing
            $productModel = Product::where('id', $product)->firstOrFail();
            
            // If auth is working, check ownership
            if (Auth::check() && $productModel->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $messages = $productModel->nurtureMessages()->get();

            return response()->json([
                'success' => true,
                'count' => $messages->count(),
                'messages' => $messages
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Product not found", [
                'product_id' => $product,
                'auth_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Failed to fetch nurture messages for product {$product}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load nurture messages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific nurture message
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $message = NurtureLibrary::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Nurture message not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Create a custom nurture message
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'content_type' => 'required|in:case_study,tip,insight,video,testimonial',
            'content_body' => 'required|string|max:500',
            'language' => 'required|in:en,sw',
            'tone' => 'nullable|in:casual,formal,friendly',
            'target_industry' => 'nullable|string|max:100',
            'target_job_title' => 'nullable|string|max:100',
            'target_pain_point' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify product ownership
        $product = Product::where('id', $request->product_id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or unauthorized'
            ], 403);
        }

        try {
            $message = NurtureLibrary::create([
                'user_id' => Auth::id(),
                'business_id' => Auth::user()->business_id,
                'product_id' => $request->product_id,
                'is_business_level' => false,
                'title' => $request->title,
                'content_type' => $request->content_type,
                'content_body' => $request->content_body,
                'language' => $request->language,
                'tone' => $request->tone ?? 'casual',
                'target_industry' => $request->target_industry,
                'target_job_title' => $request->target_job_title,
                'target_pain_point' => $request->target_pain_point,
                'usage_count' => 0,
                'success_rate' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nurture message created successfully',
                'data' => $message
            ], 201);
            
        } catch (\Exception $e) {
            Log::error("Failed to create nurture message: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create nurture message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a nurture message
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $message = NurtureLibrary::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Nurture message not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content_type' => 'sometimes|required|in:case_study,tip,insight,video,testimonial',
            'content_body' => 'sometimes|required|string|max:500',
            'language' => 'sometimes|required|in:en,sw',
            'tone' => 'nullable|in:casual,formal,friendly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $message->update($request->only([
                'title', 'content_type', 'content_body', 'language', 'tone',
                'target_industry', 'target_job_title', 'target_pain_point'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Nurture message updated successfully',
                'data' => $message->fresh()
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to update nurture message: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update nurture message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a nurture message
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $message = NurtureLibrary::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Nurture message not found'
            ], 404);
        }

        try {
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'Nurture message deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to delete nurture message: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete nurture message',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

