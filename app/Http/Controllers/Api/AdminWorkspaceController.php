<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WaSenderController;
use App\Models\AiSalesAgent;
use App\Models\Business;
use App\Models\Product;
use App\Models\ProductAttachment;
use App\Models\ProductFaq;
use App\Models\User;
use App\Models\WebCatalogConnection;
use App\Models\WhatsappInstance;
use App\Services\BillingService;
use App\Services\NurtureLibraryGenerator;
use App\Services\WebCatalogImporter;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * The ShuleSoft admin panel's client workspace ("Impersonate" there): an admin fixes a
 * client's business profile, AI agent, catalog, AI documents, team and WhatsApp connection
 * without signing in to SafariChat. Every route sits behind VerifyAdminPanelSignature
 * (routes/admin_panel.php), is scoped to one business's owner, and goes through SafariChat's
 * own models, controllers and services so side effects (campaign exclusivity, RAG processing,
 * nurture messages, invitations) match the client's own dashboard.
 *
 * Requests are signed JSON, so files travel as {"name": "...", "data": "<base64>"} and are
 * turned back into UploadedFile objects here. Responses carry before/after values for the
 * admin panel's audit log.
 */
class AdminWorkspaceController extends Controller
{
    /** Same rules as AiSalesAgentController::validateAgentData for the fields offered here. */
    const AGENT_RULES = [
        'assistant_name' => 'required|string|max:255',
        'target_audience' => 'required|string|in:small-businesses,medium-businesses,enterprises,individuals,mixed',
        'communication_tone' => 'required|string|in:professional,friendly,consultative,direct',
        'personality_description' => 'nullable|string|max:1000',
        'primary_language' => 'required|string|in:en,sw,fr,ar,pt,am',
        'always_available' => 'boolean',
        'allow_negotiation' => 'boolean',
        'max_discount_allowed' => 'nullable|integer|min:0|max:50',
        'fallback_person' => 'nullable|string|max:255',
        'fallback_number' => 'required|string|max:20',
        'out_of_hours_message' => 'nullable|string|max:500',
    ];

    /** product_attachments.attachment_type CHECK constraint values. */
    const DOCUMENT_TYPES = ['brochure', 'manual', 'profile', 'case_study', 'certificate', 'contract_template', 'technical_spec', 'other'];

    /** Product columns the workspace product form writes (see saveProduct). */
    const PRODUCT_FIELDS = [
        'product_type', 'name', 'description', 'minimal_description', 'category', 'sku', 'status',
        'retail_price', 'wholesale_price', 'max_discount', 'min_negotiable_price', 'quantity', 'tags',
        'service_delivery_type', 'service_duration_days', 'pricing_type', 'hourly_rate', 'service_tiers',
        'service_deliverables', 'prerequisites', 'requires_consultation', 'selling_points',
        'is_active_campaign', 'campaign_hook_text', 'campaign_pain_point',
    ];

    private function owner(int $businessId): array
    {
        $business = Business::find($businessId);
        $owner = $business ? User::find($business->user_id) : null;
        abort_if(!$owner, 404, 'This business has no owner account');

        return [$business, $owner];
    }

    private function ownedProduct(User $owner, int $productId): Product
    {
        $product = Product::where('id', $productId)->where('user_id', $owner->id)->first();
        abort_if(!$product, 404, 'Product not found for this client');

        return $product;
    }

    private function invalid($v)
    {
        return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
    }

    private function refuse(string $message, int $status = 422)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    private function done(Request $request, int $businessId, string $what, array $before, array $after, array $extra = [])
    {
        Log::info("Admin panel workspace: {$what}", ['business_id' => $businessId, 'admin' => $request->input('admin'), 'before' => $before, 'after' => $after]);

        return response()->json(['success' => true, 'before' => $before, 'after' => $after] + $extra);
    }

    /** A {"name","data"} upload from the signed JSON body as an UploadedFile (null when absent). */
    private function upload($file): ?UploadedFile
    {
        if (!is_array($file) || empty($file['data']) || empty($file['name'])) {
            return null;
        }
        $bytes = base64_decode((string) $file['data'], true);
        abort_if($bytes === false, 422, 'A file could not be read');

        $path = tempnam(sys_get_temp_dir(), 'adminws');
        file_put_contents($path, $bytes);

        // test = true: the file did not come through PHP's upload handling, so is_uploaded_file() would fail.
        return new UploadedFile($path, basename((string) $file['name']), null, null, true);
    }

    // ── Business & AI agent ─────────────────────────────────────────────────

    /** POST businesses/{id}/workspace/business — the profile Home@settings (table=business) edits. Email stays: it is the Billing ID. */
    public function business(Request $request, int $businessId)
    {
        [$business] = $this->owner($businessId);
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'descriptions' => 'nullable|string|max:5000',
        ]);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $fields = ['name', 'phone', 'website', 'descriptions'];
        $before = $business->only($fields);
        $business->update($request->only($fields));

        return $this->done($request, $businessId, 'business updated', $before, $business->fresh()->only($fields));
    }

    /**
     * POST businesses/{id}/workspace/agent — the owner's AI sales agent (latest one). Ignored
     * contacts live on the preferred WhatsApp line, as AiSalesAgentController::syncIgnoredContactsFromLine stores them.
     */
    public function agent(Request $request, int $businessId)
    {
        [, $owner] = $this->owner($businessId);
        $v = Validator::make($request->all(), self::AGENT_RULES + ['ignored_contacts_line' => 'nullable|string|max:2000']);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $agent = AiSalesAgent::where('user_id', $owner->id)->orderByDesc('id')->first();
        if (!$agent) {
            return $this->refuse('This client has not set up an AI agent yet.', 404);
        }

        $fields = array_keys(self::AGENT_RULES);
        $before = $agent->only($fields);
        $after = [];
        DB::transaction(function () use ($request, $agent, $owner, $fields, &$before, &$after) {
            $agent->update($request->only($fields));
            $after = $agent->fresh()->only($fields);

            $instance = WhatsappInstance::where('user_id', $owner->id)->orderByDesc('is_primary')->orderBy('created_at')->first();
            if ($instance && $request->has('ignored_contacts_line')) {
                $phones = collect(preg_split('/[\n,]+/', (string) $request->input('ignored_contacts_line')) ?: [])
                    ->map(fn ($p) => preg_replace('/[^0-9]/', '', trim((string) $p)))
                    ->filter()->unique()->values();
                // Keep the labels set through WaSenderController@updateIgnoredContacts (one older row
                // wraps the list as {"ignored_contacts": [...]}).
                $current = $instance->ignored_contacts ?? [];
                $labels = collect($current['ignored_contacts'] ?? $current)->filter(fn ($c) => is_array($c) && !empty($c['label']))
                    ->mapWithKeys(fn ($c) => [preg_replace('/[^0-9]/', '', (string) ($c['phone'] ?? '')) => $c['label']]);
                $before['ignored_contacts'] = $current;
                $instance->update(['ignored_contacts' => $phones->map(fn ($p) => isset($labels[$p]) ? ['phone' => $p, 'label' => $labels[$p]] : ['phone' => $p])->all()]);
                $after['ignored_contacts'] = $instance->ignored_contacts;
            }
        });

        return $this->done($request, $businessId, 'agent updated', $before, $after);
    }

    // ── Products & services ─────────────────────────────────────────────────

    /**
     * POST businesses/{id}/workspace/products (create) or products/{productId} (update).
     * Mirrors ProductController@store/@update: StoreProductRequest's rules, files on the public
     * disk under the same paths, campaign exclusivity, FAQs replaced from the form, and nurture
     * messages generated for a new product. Unlike the dashboard form it also keeps the service
     * pricing fields (hourly rate, tiers, deliverables, prerequisites) and the minimum
     * negotiable price, which the AI reads (OpenAiService) but the dashboard form drops.
     */
    public function saveProduct(Request $request, int $businessId, ?int $productId = null)
    {
        [$business, $owner] = $this->owner($businessId);
        $product = $productId ? $this->ownedProduct($owner, $productId) : null;

        $service = $request->input('product_type') === 'service';
        $pricing = (string) $request->input('pricing_type');
        $rules = [
            'product_type' => 'required|in:tangible,service',
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'minimal_description' => 'nullable|string|max:500',
            'category' => 'required|string|max:100',
            'status' => 'required|in:active,inactive,draft',
            'sku' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'selling_points' => 'required|array|min:1',
            'selling_points.*' => 'string|max:255',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string|max:1000',
            'faqs.*.answer' => 'required|string|max:5000',
            'is_active_campaign' => 'boolean',
            'campaign_hook_text' => 'nullable|required_if:is_active_campaign,true|string|max:1000',
            'campaign_pain_point' => 'nullable|required_if:is_active_campaign,true|string|max:1000',
        ];
        if ($service) {
            $rules += [
                'service_delivery_type' => 'required|in:digital,physical,hybrid,consultation',
                'service_duration_days' => 'nullable|integer|min:0|max:36500',
                'pricing_type' => 'required|in:one_time,monthly,yearly,per_hour,per_project,tiered',
                'retail_price' => (in_array($pricing, ['one_time', 'monthly', 'yearly', 'per_project'], true) ? 'required' : 'nullable') . '|numeric|min:0|max:999999999',
                'hourly_rate' => ($pricing === 'per_hour' ? 'required' : 'nullable') . '|numeric|min:0|max:999999999',
                'service_tiers' => ($pricing === 'tiered' ? 'required|array|min:1' : 'nullable|array'),
                'service_tiers.*.name' => 'required|string|max:100',
                'service_tiers.*.price' => 'required|numeric|min:0',
                'service_tiers.*.description' => 'nullable|string|max:255',
                'service_deliverables' => 'nullable|array',
                'service_deliverables.*' => 'string|max:500',
                'prerequisites' => 'nullable|array',
                'prerequisites.*' => 'string|max:500',
                'requires_consultation' => 'boolean',
            ];
        } else {
            $rules += [
                'retail_price' => 'required|numeric|min:0|max:999999.99',
                'wholesale_price' => 'required|numeric|min:0|lte:retail_price',
                'max_discount' => 'nullable|integer|min:0|max:100',
                'min_negotiable_price' => 'nullable|numeric|min:0|lt:retail_price',
                'quantity' => 'nullable|integer|min:0',
            ];
        }
        $v = Validator::make($request->all(), $rules, [
            'wholesale_price.lte' => 'The wholesale price cannot be above the retail price.',
            'min_negotiable_price.lt' => 'The minimum negotiable price must be below the retail price.',
            'selling_points.required' => 'Add at least one key selling point.',
        ]);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $image = $this->upload($request->input('product_image'));
        $campaignFile = $this->upload($request->input('campaign_attachment'));
        $files = Validator::make(['product_image' => $image, 'campaign_attachment' => $campaignFile], [
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'campaign_attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);
        if ($files->fails()) {
            return $this->invalid($files);
        }

        // SKU: kept unique across SafariChat like StoreProductRequest; generated as the dashboard form does.
        $sku = trim((string) $request->input('sku')) ?: ($product->sku ?? null);
        if (!$sku && !$service) {
            $sku = 'SKU-' . substr((string) round(microtime(true) * 1000), -8) . '-' . random_int(1000, 9999);
        }
        if ($sku && Product::where('sku', $sku)->when($product, fn ($q) => $q->where('id', '<>', $product->id))->exists()) {
            return $this->refuse('That SKU is already used by another product.');
        }

        if (!$product) {
            $limits = BillingService::getBillingStatus($owner->id)['limits']['products'] ?? null;
            if ($limits && $limits['current'] >= $limits['max']) {
                return $this->refuse("Product limit reached: this client's plan allows {$limits['max']} products.", 402);
            }
        }

        $data = $request->only(self::PRODUCT_FIELDS);
        $data['sku'] = $sku;
        $data['is_active_campaign'] = $request->boolean('is_active_campaign');
        if ($service) {
            $data['wholesale_price'] = $data['retail_price'] ?? null;
            $data['requires_consultation'] = $request->boolean('requires_consultation');
            $data['service_tiers'] = $pricing === 'tiered' ? array_values($data['service_tiers'] ?? []) : null;
            $data['service_deliverables'] = array_values(array_filter($data['service_deliverables'] ?? []));
            $data['prerequisites'] = array_values(array_filter($data['prerequisites'] ?? []));
        }
        $data['selling_points'] = array_values(array_filter($data['selling_points'] ?? []));
        $data['tags'] = array_values($data['tags'] ?? []);
        $minPrice = $data['min_negotiable_price'] ?? null;
        unset($data['min_negotiable_price']); // not in Product::$fillable

        $before = $product ? $product->only(self::PRODUCT_FIELDS) + ['faqs' => $product->faqs()->count()] : [];

        DB::transaction(function () use (&$product, $data, $minPrice, $image, $campaignFile, $request, $owner, $business) {
            if ($image) {
                if ($product && $product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $image->storeAs('products/images', time() . '_' . $image->getClientOriginalName(), 'public');
                $data['image_original_name'] = $image->getClientOriginalName();
            }
            if ($campaignFile) {
                if ($product && $product->campaign_attachment_path) {
                    Storage::disk('public')->delete($product->campaign_attachment_path);
                }
                $data['campaign_attachment_path'] = $campaignFile->storeAs('products/campaigns', time() . '_campaign_' . $campaignFile->getClientOriginalName(), 'public');
            }

            if ($product) {
                $product->update($data);
            } else {
                $product = Product::create($data + ['user_id' => $owner->id, 'business_id' => $business->id]);
            }
            $product->forceFill(['min_negotiable_price' => $minPrice])->save();

            if ($data['is_active_campaign']) {
                $product->setAsActiveCampaign();
            } elseif (!$product->wasRecentlyCreated) {
                $product->deactivateCampaign();
            }

            $product->faqs()->delete();
            foreach (array_values($request->input('faqs', [])) as $i => $faq) {
                ProductFaq::create(['product_id' => $product->id, 'question' => $faq['question'], 'answer' => $faq['answer'], 'sort_order' => $i]);
            }
        });

        $nurture = 0;
        if (!$productId) {
            try {
                $nurture = (new NurtureLibraryGenerator())->generateForProduct($product)->count();
            } catch (\Throwable $e) {
                // Throwable: an Error here (e.g. a missing OpenAI package) must not fail a product that is already saved.
                Log::warning("Nurture message generation failed for product {$product->id}: " . $e->getMessage());
            }
        }

        $product->refresh();

        return $this->done($request, $businessId, $productId ? 'product updated' : 'product created', $before,
            $product->only(self::PRODUCT_FIELDS) + ['id' => $product->id, 'faqs' => $product->faqs()->count()],
            ['product_id' => $product->id, 'nurture_messages_generated' => $nurture]);
    }

    /** POST businesses/{id}/workspace/products/{productId}/duplicate — a copy (FAQs and image too; AI documents are not copied). */
    public function duplicateProduct(Request $request, int $businessId, int $productId)
    {
        [, $owner] = $this->owner($businessId);
        $source = $this->ownedProduct($owner, $productId);

        $limits = BillingService::getBillingStatus($owner->id)['limits']['products'] ?? null;
        if ($limits && $limits['current'] >= $limits['max']) {
            return $this->refuse("Product limit reached: this client's plan allows {$limits['max']} products.", 402);
        }

        $copy = DB::transaction(function () use ($source) {
            $copy = $source->replicate(['external_source', 'external_ref', 'external_synced_at']);
            $copy->name = Str::limit($source->name . ' (copy)', 255, '');
            $copy->sku = $source->sku ? 'SKU-' . substr((string) round(microtime(true) * 1000), -8) . '-' . random_int(1000, 9999) : null;
            $copy->is_active_campaign = false;
            foreach (['image_path' => 'products/images', 'campaign_attachment_path' => 'products/campaigns'] as $column => $dir) {
                if ($source->$column && Storage::disk('public')->exists($source->$column)) {
                    $copy->$column = $dir . '/' . time() . '_copy_' . basename($source->$column);
                    Storage::disk('public')->copy($source->$column, $copy->$column);
                } else {
                    $copy->$column = null;
                }
            }
            $copy->save();
            foreach ($source->faqs()->orderBy('sort_order')->get() as $faq) {
                ProductFaq::create(['product_id' => $copy->id, 'question' => $faq->question, 'answer' => $faq->answer, 'sort_order' => $faq->sort_order]);
            }

            return $copy;
        });

        return $this->done($request, $businessId, 'product duplicated', ['source_id' => $source->id], ['id' => $copy->id, 'name' => $copy->name], ['product_id' => $copy->id]);
    }

    /** POST businesses/{id}/workspace/products/{productId}/status — activate / deactivate, as ProductController@bulkAction does. */
    public function productStatus(Request $request, int $businessId, int $productId)
    {
        [, $owner] = $this->owner($businessId);
        $product = $this->ownedProduct($owner, $productId);
        $v = Validator::make($request->all(), ['status' => 'required|in:active,inactive']);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $before = ['status' => $product->status];
        $product->update(['status' => $request->input('status')]);

        return $this->done($request, $businessId, 'product status changed', $before, ['status' => $product->status]);
    }

    /**
     * POST businesses/{id}/workspace/products/{productId}/delete — ProductController@destroy:
     * a hard delete; the database cascades FAQs, documents, vectors and lead links.
     */
    public function deleteProduct(Request $request, int $businessId, int $productId)
    {
        [, $owner] = $this->owner($businessId);
        $product = $this->ownedProduct($owner, $productId);
        $before = $product->only(['id', 'name', 'sku', 'product_type', 'retail_price', 'status'])
            + ['leads' => DB::table('lead_products')->where('product_id', $product->id)->distinct()->count('lead_id')];

        $files = array_filter([$product->image_path, $product->campaign_attachment_path]);
        $product->delete();
        Storage::disk('public')->delete($files);

        return $this->done($request, $businessId, 'product deleted', $before, []);
    }

    /**
     * POST businesses/{id}/workspace/products/import — Excel / CSV, through
     * ProductController's own row normalisation and upsert (a repeated sku updates that product).
     */
    public function importProducts(Request $request, int $businessId)
    {
        [$business, $owner] = $this->owner($businessId);
        $file = $this->upload($request->input('file'));
        $v = Validator::make(['file' => $file], ['file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240']);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        try {
            $sheet = Excel::toArray([], $file)[0] ?? [];
        } catch (\Throwable $e) {
            return $this->refuse('Could not read that file. Upload a valid .xlsx, .xls or .csv.');
        }
        $headers = array_shift($sheet) ?: [];
        $rows = array_map(fn ($row) => array_combine($headers, array_pad(array_slice($row, 0, count($headers)), count($headers), null)), $sheet);

        $products = app(ProductController::class);
        $rows = $products->normalizeProductRows($rows);
        if (empty($rows)) {
            return $this->refuse('No products found. The file needs a "name" column with values.');
        }

        [$created, $updated, $skipped] = $products->importRows($rows, $owner->id, $business->id);

        return $this->done($request, $businessId, 'products imported', [], compact('created', 'updated', 'skipped'),
            ['message' => "Import complete: {$created} added, {$updated} updated" . ($skipped ? ", {$skipped} skipped (missing name)" : '') . '.']);
    }

    /** POST businesses/{id}/workspace/products/web-preview — WebCatalogImporter::preview (nothing is saved). */
    public function webPreview(Request $request, int $businessId, WebCatalogImporter $importer)
    {
        $this->owner($businessId);
        $v = Validator::make($request->all(), ['url' => 'required|string|max:500']);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $preview = $importer->preview($request->input('url'));

        return response()->json(['success' => (bool) ($preview['ok'] ?? false)] + $preview);
    }

    /** POST businesses/{id}/workspace/products/web-import — as ProductController@webImport, remembered for the nightly sync. */
    public function webImport(Request $request, int $businessId, WebCatalogImporter $importer)
    {
        [$business, $owner] = $this->owner($businessId);
        $v = Validator::make($request->all(), ['url' => 'required|string|max:500']);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $result = $importer->import($business->id, $owner->id, $request->input('url'), 200);
        if (!$result['ok']) {
            return $this->refuse($result['message']);
        }

        WebCatalogConnection::updateOrCreate(
            ['business_id' => $business->id, 'url' => rtrim(trim($request->input('url')), '/')],
            [
                'status' => 'connected',
                'last_synced_at' => now(),
                'product_count' => Product::where('business_id', $business->id)->where('external_source', 'web')->count(),
                'last_error' => null,
            ]
        );

        return $this->done($request, $businessId, 'products imported from link', [], $result, ['message' => $result['message']]);
    }

    // ── AI documents (RAG) ──────────────────────────────────────────────────

    /**
     * POST businesses/{id}/workspace/products/{productId}/documents — through
     * ProductAttachmentController@store, so files are stored and queued for the AI to read
     * exactly as the dashboard's uploads are.
     */
    public function uploadDocuments(Request $request, int $businessId, int $productId)
    {
        [, $owner] = $this->owner($businessId);
        $product = $this->ownedProduct($owner, $productId);
        $v = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'attachment_type' => 'required|in:' . implode(',', self::DOCUMENT_TYPES),
        ]);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $files = array_values(array_filter(array_map(fn ($f) => $this->upload($f), $request->input('files'))));
        $count = count($files);
        $upload = Request::create('/', 'POST', [
            'attachment_types' => array_fill(0, $count, $request->input('attachment_type')),
            'is_public' => array_fill(0, $count, 0),
            'descriptions' => array_fill(0, $count, 'RAG document for AI processing'),
        ], [], ['files' => $files]);

        $response = app(ProductAttachmentController::class)->store($upload, $product);
        $result = $response->getData(true);
        if (!($result['success'] ?? false)) {
            $first = collect($result['errors'] ?? [])->flatten()->first();

            return $this->refuse($first ?: ($result['message'] ?? 'Upload failed'), $response->getStatusCode());
        }

        return $this->done($request, $businessId, 'documents uploaded', [],
            ['product_id' => $product->id, 'files' => collect($result['files'])->pluck('filename')->all()], ['message' => $result['message']]);
    }

    private function ownedDocument(User $owner, int $productId, int $attachmentId): array
    {
        $product = $this->ownedProduct($owner, $productId);
        $attachment = ProductAttachment::where('id', $attachmentId)->where('product_id', $product->id)->first();
        abort_if(!$attachment, 404, 'Document not found for this product');

        return [$product, $attachment];
    }

    /** POST businesses/{id}/workspace/products/{productId}/documents/{attachmentId}/reprocess — ProductAttachmentController@reprocessRAG. */
    public function reprocessDocument(Request $request, int $businessId, int $productId, int $attachmentId)
    {
        [, $owner] = $this->owner($businessId);
        [$product, $attachment] = $this->ownedDocument($owner, $productId, $attachmentId);
        $before = $attachment->only(['processing_status', 'vector_count']);

        // reprocessRAG deletes what the AI learned before the job looks for the file, so a missing
        // file would leave the AI with nothing. Refuse instead and keep what it has.
        if (!is_file($attachment->absolute_path)) {
            return $this->refuse('The original file is no longer on the server, so it cannot be re-read. The AI keeps what it already learned from it; to replace it, remove the document and upload it again.');
        }

        $response = app(ProductAttachmentController::class)->reprocessRAG($product, $attachment);
        $result = $response->getData(true);
        if (!($result['success'] ?? false)) {
            return $this->refuse($result['message'] ?? 'Could not re-read the document', $response->getStatusCode());
        }

        return $this->done($request, $businessId, 'document reprocessing', $before, $attachment->fresh()->only(['processing_status', 'vector_count']));
    }

    /**
     * POST businesses/{id}/workspace/products/{productId}/documents/{attachmentId}/delete —
     * ProductAttachmentController@destroy (removes the AI's vectors too). The model hook deletes
     * the file on the default disk, but uploads live on the public disk, so it is removed here.
     */
    public function deleteDocument(Request $request, int $businessId, int $productId, int $attachmentId)
    {
        [, $owner] = $this->owner($businessId);
        [$product, $attachment] = $this->ownedDocument($owner, $productId, $attachmentId);
        $before = $attachment->only(['id', 'original_filename', 'attachment_type', 'vector_count']);
        $path = $attachment->file_path;

        $response = app(ProductAttachmentController::class)->destroy($product, $attachment);
        $result = $response->getData(true);
        if (!($result['success'] ?? false)) {
            return $this->refuse($result['message'] ?? 'Could not remove the document', $response->getStatusCode());
        }
        Storage::disk('public')->delete($path);

        return $this->done($request, $businessId, 'document removed', $before, []);
    }

    // ── Team ────────────────────────────────────────────────────────────────

    /**
     * POST businesses/{id}/workspace/users — add a team member the way Home@storeTeamMember
     * does: plan user limit, unique phone (the phone is the login), no password (WhatsApp OTP
     * login), and a WhatsApp invitation.
     */
    public function addUser(Request $request, int $businessId)
    {
        [$business, $owner] = $this->owner($businessId);
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'role' => 'required|in:member,manager,admin',
        ]);
        if ($v->fails()) {
            return $this->invalid($v);
        }

        $plan = optional($business->billingAccount)->subscription_plan ?? 'trial';
        $maxUsers = config("safarichat_billing.plans.{$plan}.limits.whatsapp_channels") ?? 1;
        $current = User::where('id', $business->user_id)->orWhere('parent_business_id', $business->id)->count();
        if ($current >= $maxUsers) {
            return $this->refuse("User limit reached: the {$plan} plan allows {$maxUsers} user(s).");
        }

        $phone = function_exists('sanitize_phone_number') ? sanitize_phone_number($request->input('phone')) : $request->input('phone');
        if (User::where('phone', $phone)->exists()) {
            return $this->refuse('A user with this phone number already exists, so a second account cannot be created for it.');
        }

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $phone,
            'password' => Hash::make(Str::random(40)),
            'parent_business_id' => $business->id,
            'role' => $request->input('role'),
            'uuid' => (string) Str::uuid(),
        ]);

        $invited = $this->sendTeamInvite($user, $business);

        return $this->done($request, $businessId, 'team member added', [], $user->only(['id', 'name', 'phone', 'email', 'role']), ['invited' => $invited]);
    }

    /** Home@sendTeamInvite's message; "SafariChat support" is named as the one who added them. */
    private function sendTeamInvite(User $user, Business $business): bool
    {
        try {
            $loginUrl = rtrim(config('app.url', 'https://safarichat.ai'), '/') . '/login';
            $roleLabel = ucfirst($user->role ?? 'member');
            $message = "Hello {$user->name}! 👋\n\n"
                . "You've been added to *{$business->name}* on SafariChat by SafariChat support as a *{$roleLabel}*.\n\n"
                . "To log in:\n"
                . "1️⃣ Open {$loginUrl}\n"
                . "2️⃣ Enter your phone number *{$user->phone}*\n"
                . "3️⃣ You'll get a one-time code here on WhatsApp — enter it to sign in.\n\n"
                . "No password needed. Welcome aboard! 🎉";

            app(\App\Services\SystemWhatsAppService::class)->sendGenericMessage($user->phone, $message, 'system_notification');

            return true;
        } catch (\Throwable $e) {
            Log::warning('Admin panel workspace: team invitation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /** POST businesses/{id}/workspace/users/{userId}/delete — team members only, never the owner (Home@deleteTeamMember). */
    public function deleteUser(Request $request, int $businessId, int $userId)
    {
        [$business] = $this->owner($businessId);
        $user = User::where('id', $userId)->where('parent_business_id', $business->id)->first();
        abort_if(!$user, 404, 'Team member not found for this client (the owner cannot be removed)');

        $before = $user->only(['id', 'name', 'phone', 'email', 'role']);
        $user->delete();

        return $this->done($request, $businessId, 'team member removed', $before, []);
    }

    // ── WhatsApp ────────────────────────────────────────────────────────────

    /**
     * POST businesses/{id}/workspace/whatsapp/{instanceId}/{action} — action is "qr" (new QR code
     * for the client to scan) or "status" (check with WaSender whether they are connected).
     * Runs WaSenderController's own sessionQr / checkSessionStatus as the owner for this request
     * only (no session is started), so the connect flow stays exactly the client's.
     */
    public function whatsapp(Request $request, int $businessId, int $instanceId, string $action)
    {
        [, $owner] = $this->owner($businessId);
        $instance = WhatsappInstance::where('id', $instanceId)->where('user_id', $owner->id)->first();
        abort_if(!$instance, 404, 'WhatsApp line not found for this client');

        Auth::guard('web')->setUser($owner);
        $controller = app(WaSenderController::class);
        $response = $action === 'qr'
            ? $controller->sessionQr($request, $instance->instance_id)
            : $controller->checkSessionStatus($request, $instance->instance_id);
        $data = $response->getData(true);

        Log::info("Admin panel workspace: WhatsApp {$action}", [
            'business_id' => $businessId, 'instance_id' => $instanceId, 'admin' => $request->input('admin'),
            'success' => $data['success'] ?? false, 'status' => $data['status'] ?? null,
        ]);

        return response()->json($data + ['before' => ['status' => $instance->status], 'after' => ['status' => $instance->fresh()->status]], $response->getStatusCode());
    }
}
