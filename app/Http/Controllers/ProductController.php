<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductFaq;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\BillingService;
use App\Services\NurtureLibraryGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    private const LEAD_STATUSES = [
        'OUTREACHED',
        'REPLIED',
        'ENGAGED',
        'QUALIFIED',
        'PITCHED',
        'DEMO_SCHEDULED',
        'PROPOSAL_SENT',
        'NEGOTIATING',
    ];

    private const QUALIFIED_STATUSES = [
        'QUALIFIED',
        'PROPOSAL_SENT',
        'NEGOTIATING',
        'DEMO_SCHEDULED',
        'PITCHED',
    ];

    public function __construct()
    {
      
        $this->middleware('auth');
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with('faqs')
            ->withCount([
                'leadProducts as lead_products_count',
                'leadProducts as distinct_leads_count' => function ($query) {
                    $query->selectRaw('COUNT(DISTINCT lead_id)');
                }
            ])
            ->forUser(auth()->id());
        
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
        $categories = Product::forUser(auth()->id())->distinct()->pluck('category');
       
        // Get subscription plan and product limits
        $billingAccount = Auth::user()->business->billingAccount;
        $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
        $planLimits = config('safarichat_billing.plans.' . $currentPlan . '.limits', []);
        $subscription_plan = $currentPlan;
        $max_products = $planLimits['max_products'] ?? 1;
        $total_products = Product::forUser(auth()->id())->count();
       
        return view('service.products', compact('products', 'categories', 'subscription_plan', 'max_products', 'total_products'));
    }

    /**
     * Store a new product
     */
    public function store(StoreProductRequest $request)
    {
        try {
            // Check billing limits first
            $billingStatus = BillingService::getBillingStatus(Auth::id());
            if (!$billingStatus || !isset($billingStatus['limits']['products'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to verify subscription limits',
                    'upgrade_required' => true,
                    'feature' => 'products'
                ], 402);
            }

            $productLimits = $billingStatus['limits']['products'];
            if ($productLimits['current'] >= $productLimits['max']) {
                return response()->json([
                    'success' => false,
                    'message' => "Product limit reached. Your {$billingStatus['subscription']['plan']} plan allows {$productLimits['max']} products.",
                    'upgrade_required' => true,
                    'feature' => 'products',
                    'current_limit' => $productLimits['max'],
                    'current_usage' => $productLimits['current']
                ], 402);
            }

            DB::beginTransaction();
            
            $productData = $request->validated();
            
            // Add user and business ownership
            $productData['user_id'] = auth()->id();
            if (auth()->user()->business_id) {
                $productData['business_id'] = auth()->user()->business_id;
            }
            
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
            
            // Handle campaign attachment upload
            if ($request->hasFile('campaign_attachment')) {
                $campaignFile = $request->file('campaign_attachment');
                $campaignFileName = time() . '_campaign_' . $campaignFile->getClientOriginalName();
                $campaignPath = $campaignFile->storeAs('products/campaigns', $campaignFileName, 'public');
                $productData['campaign_attachment_path'] = $campaignPath;
            }
            
            // NOTE: RAG documents are handled separately via ProductAttachmentController
            // through /api/products/{id}/attachments endpoint after product creation
            // The frontend JavaScript handles RAG document uploads independently
            
            // Handle FAQ data - initialize as empty since frontend sends JSON format
            $faqQuestions = [];
            $faqAnswers = [];
            
            // Handle new FAQ JSON format from frontend (can be string or array)
            if (isset($productData['faqs'])) {
                $faqsData = null;
                
                // Handle if it's a JSON string
                if (is_string($productData['faqs'])) {
                    $faqsData = json_decode($productData['faqs'], true);
                }
                // Handle if it's already an array
                elseif (is_array($productData['faqs'])) {
                    $faqsData = $productData['faqs'];
                }
                
                // Process the FAQ data if valid
                if (is_array($faqsData)) {
                    $faqQuestions = [];
                    $faqAnswers = [];
                    foreach ($faqsData as $faq) {
                        if (isset($faq['question']) && isset($faq['answer'])) {
                            $faqQuestions[] = $faq['question'];
                            $faqAnswers[] = $faq['answer'];
                        }
                    }
                }
            }
            
            // Handle selling_points JSON data (can be string or array)
            if (isset($productData['selling_points'])) {
                if (is_string($productData['selling_points'])) {
                    $sellingPointsData = json_decode($productData['selling_points'], true);
                    if (is_array($sellingPointsData)) {
                        $productData['selling_points'] = $sellingPointsData;
                    } else {
                        unset($productData['selling_points']); // Remove invalid data
                    }
                }
                // If it's already an array, keep it as is
                elseif (!is_array($productData['selling_points'])) {
                    unset($productData['selling_points']); // Remove invalid data
                }
            }
            
            // Remove FAQ data from product data
            unset($productData['faq_questions'], $productData['faq_answers'], $productData['faqs']);
            
            // Create product
            $product = Product::create($productData);
            
            // Handle active campaign logic
            if (!empty($productData['is_active_campaign'])) {
                $product->setAsActiveCampaign();
            }
            
            // Add FAQs if provided
            $this->saveFAQs($product, $faqQuestions, $faqAnswers);
            
            // Auto-generate nurture messages for this product
            $nurtureMessages = collect([]);
            try {
                $generator = new NurtureLibraryGenerator();
                $nurtureMessages = $generator->generateForProduct($product);
                Log::info("Generated {$nurtureMessages->count()} nurture messages for product: {$product->name}");
            } catch (\Exception $e) {
                // Don't fail product creation if nurture generation fails
                Log::warning("Nurture message generation failed for product {$product->id}: " . $e->getMessage());
            }
            
            DB::commit();
            
            // Check if this is the user's first product and onboarding is active
            $isOnboarding = request('onboarding') === 'true' || request()->header('X-Onboarding') === 'true';
            $userProductCount = Product::forUser(auth()->id())->count();
            
            $response = [
                'success' => true,
                'message' => 'Product created successfully!',
                'product' => $product->load('faqs'),
                'nurture_messages_generated' => $nurtureMessages->count(),
                'nurture_messages' => $nurtureMessages
            ];
            
            // If this is their first product during onboarding, suggest next step
            if ($isOnboarding && $userProductCount === 1) {
                $response['onboarding'] = [
                    'first_product' => true,
                    'next_step' => 'ai_agent',
                    'next_step_url' => url('/service/jd?onboarding=true'),
                    'message' => '🎉 Great! Now let\'s set up your AI Sales Agent to handle customer conversations.'
                ];
            }
            
            return response()->json($response);
            
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
            $product = Product::with(['faqs', 'attachments'])->forUser(auth()->id())->findOrFail($id);
            
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
     * Display product-level performance and operations metrics.
     */
    public function manage($id)
    {
        $product = Product::forUser(auth()->id())->findOrFail($id);
        $userId = auth()->id();

        $leadIdsForProduct = DB::table('lead_products')
            ->where('product_id', $product->id)
            ->pluck('lead_id');

        $leadsQuery = DB::table('leads')
            ->whereIn('id', $leadIdsForProduct)
            ->where('user_id', $userId);

        $metrics = [
            'prospects' => (clone $leadsQuery)->where('status', 'NEW')->count(),
            'leads' => (clone $leadsQuery)->whereIn('status', self::LEAD_STATUSES)->count(),
            'qualified_leads' => (clone $leadsQuery)->whereIn('status', self::QUALIFIED_STATUSES)->count(),
            'customers' => (clone $leadsQuery)->where('status', 'CLOSED')->count(),
            'total_churned' => (clone $leadsQuery)->where('status', 'CHURNED')->count(),
            'total_lost' => (clone $leadsQuery)->where('status', 'LOST')->count(),
            'do_not_contact' => (clone $leadsQuery)->where('status', 'DO_NOT_CONTACT')->count(),
        ];

        $operations = [
            'total_messages_sent' => 0,
            'prospecting_messages' => 0,
            'lead_messages' => 0,
            'qualified_lead_messages' => 0,
        ];

        $contactForeignKey = null;
        if (Schema::hasColumn('outgoing_messages', 'business_contact_id')) {
            $contactForeignKey = 'business_contact_id';
        } elseif (Schema::hasColumn('outgoing_messages', 'events_guest_id')) {
            $contactForeignKey = 'events_guest_id';
        }

        if ($contactForeignKey !== null) {
            $latestLeadPerContact = DB::table('leads as l')
                ->join('lead_products as lp', 'lp.lead_id', '=', 'l.id')
                ->where('lp.product_id', $product->id)
                ->where('l.user_id', $userId)
                ->selectRaw('MAX(l.id) as lead_id, l.business_contact_id')
                ->groupBy('l.business_contact_id');

            $messageSegmentationQuery = DB::table('outgoing_messages as om')
                ->joinSub($latestLeadPerContact, 'latest_lead', function ($join) use ($contactForeignKey) {
                    $join->on("latest_lead.business_contact_id", '=', "om.{$contactForeignKey}");
                })
                ->join('leads as l', 'l.id', '=', 'latest_lead.lead_id')
                ->where('om.user_id', $userId);

            if (Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $messageSegmentationQuery->where(function ($query) {
                    $query->whereNotNull('om.sent_at')
                        ->orWhereIn('om.status', ['sent', 'delivered', 'read']);
                });
            } else {
                $messageSegmentationQuery->whereIn('om.status', ['sent', 'delivered', 'read']);
            }

            $segmented = $messageSegmentationQuery
                ->selectRaw('COUNT(DISTINCT om.id) as total_messages_sent')
                ->selectRaw("COUNT(DISTINCT CASE WHEN l.status = 'NEW' THEN om.id END) as prospecting_messages")
                ->selectRaw("COUNT(DISTINCT CASE WHEN l.status IN ('OUTREACHED','REPLIED','ENGAGED','QUALIFIED','PITCHED','DEMO_SCHEDULED','PROPOSAL_SENT','NEGOTIATING') THEN om.id END) as lead_messages")
                ->selectRaw("COUNT(DISTINCT CASE WHEN l.status IN ('QUALIFIED','PROPOSAL_SENT','NEGOTIATING','DEMO_SCHEDULED','PITCHED') THEN om.id END) as qualified_lead_messages")
                ->first();

            if ($segmented) {
                $operations = [
                    'total_messages_sent' => (int) ($segmented->total_messages_sent ?? 0),
                    'prospecting_messages' => (int) ($segmented->prospecting_messages ?? 0),
                    'lead_messages' => (int) ($segmented->lead_messages ?? 0),
                    'qualified_lead_messages' => (int) ($segmented->qualified_lead_messages ?? 0),
                ];
            }
        }

        $appointmentSummary = [
            'total_scheduled' => 0,
            'upcoming' => 0,
            'today' => 0,
            'this_week' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'pending' => 0,
            'confirmed' => 0,
        ];

        $upcomingAppointments = collect();

        $currentYear = now()->year;
        $monthlyLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $monthlyNewContacts = array_fill(0, 12, 0);
        $monthlyEngagementContacts = array_fill(0, 12, 0);

        if (Schema::hasTable('business_contacts')) {
            $newContactsRows = DB::table('business_contacts as bc')
                ->join('leads as l', 'l.business_contact_id', '=', 'bc.id')
                ->join('lead_products as lp', 'lp.lead_id', '=', 'l.id')
                ->where('lp.product_id', $product->id)
                ->where('l.user_id', $userId)
                ->whereYear('bc.created_at', $currentYear)
                ->selectRaw('EXTRACT(MONTH FROM bc.created_at) as month_num, COUNT(DISTINCT bc.id) as total')
                ->groupBy(DB::raw('EXTRACT(MONTH FROM bc.created_at)'))
                ->get();

            foreach ($newContactsRows as $row) {
                $index = (int) $row->month_num - 1;
                if ($index >= 0 && $index < 12) {
                    $monthlyNewContacts[$index] = (int) $row->total;
                }
            }
        }

        if ($contactForeignKey !== null) {
            $engagementQuery = DB::table('outgoing_messages as om')
                ->join('leads as l', 'l.business_contact_id', '=', "om.{$contactForeignKey}")
                ->join('lead_products as lp', 'lp.lead_id', '=', 'l.id')
                ->where('lp.product_id', $product->id)
                ->where('l.user_id', $userId)
                ->whereYear('om.created_at', $currentYear);

            if (Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $engagementQuery->where(function ($query) {
                    $query->whereNotNull('om.sent_at')
                        ->orWhereIn('om.status', ['sent', 'delivered', 'read']);
                });
            } else {
                $engagementQuery->whereIn('om.status', ['sent', 'delivered', 'read']);
            }

            $engagementRows = $engagementQuery
                ->selectRaw("EXTRACT(MONTH FROM om.created_at) as month_num, COUNT(DISTINCT om.{$contactForeignKey}) as total")
                ->groupBy(DB::raw('EXTRACT(MONTH FROM om.created_at)'))
                ->get();

            foreach ($engagementRows as $row) {
                $index = (int) $row->month_num - 1;
                if ($index >= 0 && $index < 12) {
                    $monthlyEngagementContacts[$index] = (int) $row->total;
                }
            }
        }

        if (Schema::hasTable('appointments')) {
            $appointmentScopedQuery = DB::table('appointments as a')
                ->join('leads as l', 'l.id', '=', 'a.lead_id')
                ->join('lead_products as lp', 'lp.lead_id', '=', 'l.id')
                ->where('lp.product_id', $product->id)
                ->where('l.user_id', $userId);

            $now = now();
            $weekEnd = now()->endOfWeek();

            $appointmentCounts = (clone $appointmentScopedQuery)
                ->selectRaw('COUNT(DISTINCT a.id) as total_scheduled')
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.scheduled_at > NOW() AND a.status IN ('pending','confirmed') THEN a.id END) as upcoming")
                ->selectRaw('COUNT(DISTINCT CASE WHEN DATE(a.scheduled_at) = CURRENT_DATE THEN a.id END) as today')
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.scheduled_at BETWEEN ? AND ? THEN a.id END) as this_week", [$now, $weekEnd])
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed")
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.status = 'cancelled' THEN a.id END) as cancelled")
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.status = 'no_show' THEN a.id END) as no_show")
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending")
                ->selectRaw("COUNT(DISTINCT CASE WHEN a.status = 'confirmed' THEN a.id END) as confirmed")
                ->first();

            if ($appointmentCounts) {
                $appointmentSummary = [
                    'total_scheduled' => (int) ($appointmentCounts->total_scheduled ?? 0),
                    'upcoming' => (int) ($appointmentCounts->upcoming ?? 0),
                    'today' => (int) ($appointmentCounts->today ?? 0),
                    'this_week' => (int) ($appointmentCounts->this_week ?? 0),
                    'completed' => (int) ($appointmentCounts->completed ?? 0),
                    'cancelled' => (int) ($appointmentCounts->cancelled ?? 0),
                    'no_show' => (int) ($appointmentCounts->no_show ?? 0),
                    'pending' => (int) ($appointmentCounts->pending ?? 0),
                    'confirmed' => (int) ($appointmentCounts->confirmed ?? 0),
                ];
            }

            $upcomingAppointments = (clone $appointmentScopedQuery)
                ->leftJoin('business_contacts as bc', 'bc.id', '=', 'l.business_contact_id')
                ->where('a.scheduled_at', '>=', now())
                ->select('a.id', 'a.title', 'a.status', 'a.appointment_type', 'a.scheduled_at', 'a.duration_minutes');

            $contactNameColumn = Schema::hasColumn('business_contacts', 'guest_name')
                ? 'bc.guest_name'
                : (Schema::hasColumn('business_contacts', 'name') ? 'bc.name' : null);

            $contactPhoneColumn = Schema::hasColumn('business_contacts', 'guest_phone')
                ? 'bc.guest_phone'
                : (Schema::hasColumn('business_contacts', 'phone_number') ? 'bc.phone_number' : null);

            if ($contactNameColumn !== null) {
                $upcomingAppointments->addSelect(DB::raw("{$contactNameColumn} as contact_name"));
            } else {
                $upcomingAppointments->addSelect(DB::raw("NULL as contact_name"));
            }

            if ($contactPhoneColumn !== null) {
                $upcomingAppointments->addSelect(DB::raw("{$contactPhoneColumn} as contact_phone"));
            } else {
                $upcomingAppointments->addSelect(DB::raw("NULL as contact_phone"));
            }

            $upcomingAppointments = $upcomingAppointments
                ->orderBy('a.scheduled_at', 'asc')
                ->limit(10)
                ->get();
        }

        $pipelineTotal = $metrics['prospects'] + $metrics['leads'] + $metrics['qualified_leads'] + $metrics['customers'];
        $closedUniverse = $metrics['customers'] + $metrics['total_lost'] + $metrics['total_churned'];

        $salesLeader = [
            'pipeline_total' => $pipelineTotal,
            'win_rate_percent' => $closedUniverse > 0
                ? round(($metrics['customers'] / $closedUniverse) * 100, 1)
                : 0.0,
            'qualified_rate_percent' => $metrics['leads'] > 0
                ? round(($metrics['qualified_leads'] / $metrics['leads']) * 100, 1)
                : 0.0,
            'appointment_coverage_percent' => $pipelineTotal > 0
                ? round(($appointmentSummary['total_scheduled'] / $pipelineTotal) * 100, 1)
                : 0.0,
            'no_show_rate_percent' => $appointmentSummary['total_scheduled'] > 0
                ? round(($appointmentSummary['no_show'] / $appointmentSummary['total_scheduled']) * 100, 1)
                : 0.0,
            'message_to_appointment_percent' => $operations['total_messages_sent'] > 0
                ? round(($appointmentSummary['total_scheduled'] / $operations['total_messages_sent']) * 100, 1)
                : 0.0,
        ];

        return view('service.product-manage', compact(
            'product',
            'metrics',
            'operations',
            'salesLeader',
            'appointmentSummary',
            'upcomingAppointments',
            'currentYear',
            'monthlyLabels',
            'monthlyNewContacts',
            'monthlyEngagementContacts'
        ));
    }

    /**
     * Update the specified product
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            DB::beginTransaction();
         
            $product = Product::forUser(auth()->id())->findOrFail($id);
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
            
            // Handle campaign attachment upload
            if ($request->hasFile('campaign_attachment')) {
                // Delete old campaign attachment if exists
                if ($product->campaign_attachment_path && Storage::disk('public')->exists($product->campaign_attachment_path)) {
                    Storage::disk('public')->delete($product->campaign_attachment_path);
                }
                
                $campaignFile = $request->file('campaign_attachment');
                $campaignFileName = time() . '_campaign_' . $campaignFile->getClientOriginalName();
                $campaignPath = $campaignFile->storeAs('products/campaigns', $campaignFileName, 'public');
                $productData['campaign_attachment_path'] = $campaignPath;
            }
            
            // NOTE: RAG documents are handled separately via ProductAttachmentController
            // through /api/products/{id}/attachments endpoint after product update
            // The frontend JavaScript handles RAG document uploads independently
            
            // Handle FAQ data - initialize as empty since frontend sends JSON format
            $faqQuestions = [];
            $faqAnswers = [];
            
            // Handle new FAQ JSON format from frontend (can be string or array)
            if (isset($productData['faqs'])) {
                $faqsData = null;
                
                // Handle if it's a JSON string
                if (is_string($productData['faqs'])) {
                    $faqsData = json_decode($productData['faqs'], true);
                }
                // Handle if it's already an array
                elseif (is_array($productData['faqs'])) {
                    $faqsData = $productData['faqs'];
                }
                
                // Process the FAQ data if valid
                if (is_array($faqsData)) {
                    $faqQuestions = [];
                    $faqAnswers = [];
                    foreach ($faqsData as $faq) {
                        if (isset($faq['question']) && isset($faq['answer'])) {
                            $faqQuestions[] = $faq['question'];
                            $faqAnswers[] = $faq['answer'];
                        }
                    }
                }
            }
            
            // Handle selling_points JSON data (can be string or array)
            if (isset($productData['selling_points'])) {
                if (is_string($productData['selling_points'])) {
                    $sellingPointsData = json_decode($productData['selling_points'], true);
                    if (is_array($sellingPointsData)) {
                        $productData['selling_points'] = $sellingPointsData;
                    } else {
                        unset($productData['selling_points']); // Remove invalid data
                    }
                }
                // If it's already an array, keep it as is
                elseif (!is_array($productData['selling_points'])) {
                    unset($productData['selling_points']); // Remove invalid data
                }
            }
            
            // Remove FAQ data from product data
            unset($productData['faq_questions'], $productData['faq_answers'], $productData['faqs']);
            
            // Update product
            $product->update($productData);
            
            // Handle active campaign logic
            if (!empty($productData['is_active_campaign'])) {
                $product->setAsActiveCampaign();
            } elseif (isset($productData['is_active_campaign']) && !$productData['is_active_campaign']) {
                $product->deactivateCampaign();
            }
            
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
            $product = Product::forUser(auth()->id())->findOrFail($id);
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
            $product = Product::with(['faqs', 'attachments'])->forUser(auth()->id())->findOrFail($id);
            
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
            $message = 'Bulk action completed.';

            switch ($action) {
                case 'activate':
                    Product::whereIn('id', $productIds)->forUser(auth()->id())->update(['status' => 'active']);
                    $message = "{$count} product(s) activated successfully!";
                    break;
                    
                case 'deactivate':
                    Product::whereIn('id', $productIds)->forUser(auth()->id())->update(['status' => 'inactive']);
                    $message = "{$count} product(s) deactivated successfully!";
                    break;
                    
                case 'delete':
                    Product::whereIn('id', $productIds)->forUser(auth()->id())->delete();
                    $message = "{$count} product(s) deleted successfully!";
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid bulk action.'
                    ], 422);
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
