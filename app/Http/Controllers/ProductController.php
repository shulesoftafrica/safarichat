<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductFaq;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
      
        $this->middleware('auth');
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with('faqs');
        
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }
        
        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }
        
        // Filter by category
        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }
        
        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->inStock();
                    break;
                case 'low_stock':
                    $query->where('quantity', '<=', 25)->where('quantity', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('quantity', 0);
                    break;
            }
        }
        
        // Order by
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);
        
        if ($request->ajax()) {
            $products = $query->paginate(10);
            return response()->json([
                'success' => true,
                'data' => $products,
                'html' => view('products.partials.table-rows', compact('products'))->render()
            ]);
        }
        
        $products = $query->paginate(10);
        $categories = Product::distinct()->pluck('category');
        
        return view('service.products', compact('products', 'categories'));
    }

    /**
     * Store a new product
     */
    public function store(StoreProductRequest $request)
    {
        
        try {
            DB::beginTransaction();
            
            $productData = $request->validated();
            
            // Handle AI description generation
            if ($productData['ai_generated_description'] && !empty($productData['minimal_description'])) {
                $productData['description'] = $this->generateAIDescription($productData['minimal_description']);
            }
            
            // Handle file uploads
            if ($request->hasFile('product_image')) {
                $imageFile = $request->file('product_image');
                $imageName = time() . '_' . $imageFile->getClientOriginalName();
                $imagePath = $imageFile->storeAs('products/images', $imageName, 'public');
                $productData['image_path'] = $imagePath;
                $productData['image_original_name'] = $imageFile->getClientOriginalName();
            }
            
            if ($request->hasFile('product_attachment')) {
                $attachmentFile = $request->file('product_attachment');
                $attachmentName = time() . '_' . $attachmentFile->getClientOriginalName();
                $attachmentPath = $attachmentFile->storeAs('products/attachments', $attachmentName, 'public');
                $productData['attachment_path'] = $attachmentPath;
                $productData['attachment_original_name'] = $attachmentFile->getClientOriginalName();
            }
            
            // Remove FAQ data from product data
            $faqQuestions = $productData['faq_questions'] ?? [];
            $faqAnswers = $productData['faq_answers'] ?? [];
            unset($productData['faq_questions'], $productData['faq_answers']);
            
            // Create product
            $product = Product::create($productData);
            
            // Add FAQs if provided
            $this->saveFAQs($product, $faqQuestions, $faqAnswers);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully!',
                'product' => $product->load('faqs')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product. Please try again.',
                'errors' => ['general' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        try {
            $product = Product::with('faqs')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }
    }

    /**
     * Update the specified product
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $product = Product::findOrFail($id);
            $productData = $request->validated();
            
            // Handle AI description generation
            if ($productData['ai_generated_description'] && !empty($productData['minimal_description'])) {
                $productData['description'] = $this->generateAIDescription($productData['minimal_description']);
            }
            
            // Handle file uploads
            if ($request->hasFile('product_image')) {
                // Delete old image if exists
                if ($product->image_path && Storage::disk('public')->exists($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }
                
                $imageFile = $request->file('product_image');
                $imageName = time() . '_' . $imageFile->getClientOriginalName();
                $imagePath = $imageFile->storeAs('products/images', $imageName, 'public');
                $productData['image_path'] = $imagePath;
                $productData['image_original_name'] = $imageFile->getClientOriginalName();
            }
            
            if ($request->hasFile('product_attachment')) {
                // Delete old attachment if exists
                if ($product->attachment_path && Storage::disk('public')->exists($product->attachment_path)) {
                    Storage::disk('public')->delete($product->attachment_path);
                }
                
                $attachmentFile = $request->file('product_attachment');
                $attachmentName = time() . '_' . $attachmentFile->getClientOriginalName();
                $attachmentPath = $attachmentFile->storeAs('products/attachments', $attachmentName, 'public');
                $productData['attachment_path'] = $attachmentPath;
                $productData['attachment_original_name'] = $attachmentFile->getClientOriginalName();
            }
            
            // Remove FAQ data from product data
            $faqQuestions = $productData['faq_questions'] ?? [];
            $faqAnswers = $productData['faq_answers'] ?? [];
            unset($productData['faq_questions'], $productData['faq_answers']);
            
            // Update product
            $product->update($productData);
            
            // Update FAQs
            $product->faqs()->delete(); // Remove existing FAQs
            $this->saveFAQs($product, $faqQuestions, $faqAnswers);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'product' => $product->fresh(['faqs'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product. Please try again.',
                'errors' => ['general' => [$e->getMessage()]]
            ], 422);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $productName = $product->name;
            
            $product->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Product '{$productName}' deleted successfully!"
            ]);
            
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product. Please try again.'
            ], 422);
        }
    }

    /**
     * Get product for editing
     */
    public function edit($id)
    {
        try {
            $product = Product::with('faqs')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'product' => $product
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
        }
    }

    /**
     * Bulk actions for products
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        try {
            $productIds = $request->product_ids;
            $action = $request->action;
            $count = count($productIds);

            switch ($action) {
                case 'activate':
                    Product::whereIn('id', $productIds)->update(['status' => 'active']);
                    $message = "{$count} product(s) activated successfully!";
                    break;
                    
                case 'deactivate':
                    Product::whereIn('id', $productIds)->update(['status' => 'inactive']);
                    $message = "{$count} product(s) deactivated successfully!";
                    break;
                    
                case 'delete':
                    Product::whereIn('id', $productIds)->delete();
                    $message = "{$count} product(s) deleted successfully!";
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk action failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk action failed. Please try again.'
            ], 422);
        }
    }

    /**
     * Save FAQs for a product
     */
    private function saveFAQs(Product $product, array $questions, array $answers)
    {
        $faqs = [];
        $questions = array_values(array_filter($questions));
        $answers = array_values(array_filter($answers));
        
        for ($i = 0; $i < count($questions) && $i < count($answers); $i++) {
            if (!empty($questions[$i]) && !empty($answers[$i])) {
                $faqs[] = [
                    'product_id' => $product->id,
                    'question' => $questions[$i],
                    'answer' => $answers[$i],
                    'sort_order' => $i,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }
        
        if (!empty($faqs)) {
            ProductFaq::insert($faqs);
        }
    }

    /**
     * Generate AI description (placeholder - implement actual AI service)
     */
    private function generateAIDescription($minimalDescription)
    {
        // Placeholder for AI description generation
        // In a real implementation, you would call an AI service like OpenAI
        
        $templates = [
            "Introducing {product}, a comprehensive solution designed to {benefit}. This innovative product offers advanced features that streamline your workflow and enhance productivity. Perfect for businesses looking to optimize their operations and achieve better results.",
            
            "Experience the power of {product}, expertly crafted to deliver exceptional {benefit}. With cutting-edge technology and user-friendly design, this product transforms the way you work. Ideal for professionals who demand reliability and performance.",
            
            "Discover {product}, the ultimate tool for {benefit}. Built with industry-leading standards and packed with features that matter, this product ensures you stay ahead of the competition. A must-have for modern businesses seeking growth and efficiency."
        ];
        
        $template = $templates[array_rand($templates)];
        
        // Simple keyword replacement
        $keywords = explode(' ', strtolower($minimalDescription));
        $product = ucwords(implode(' ', array_slice($keywords, 0, 3)));
        $benefit = implode(' ', array_slice($keywords, -3));
        
        return str_replace(['{product}', '{benefit}'], [$product, $benefit], $template);
    }
}
