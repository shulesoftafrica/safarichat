
<?php $__env->startSection('content'); ?>
<div class="products-page">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-box"></i>
            Product Management
        </h2>
        <button type="button" class="btn btn-primary btn-add-product" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i>
            Add New Product
        </button>
    </div>
    
    <!-- Search and Filter Controls -->
    <div class="table-controls mb-3">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            <div class="col-md-5">
                <div id="bulkActions" class="bulk-actions" style="display: none;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">
                            <span id="selectedCount">0</span> item(s) selected
                        </span>
                        <select class="form-select form-select-sm" id="bulkActionSelect" style="width: auto;">
                            <option value="">Choose action...</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button class="btn btn-primary btn-sm" onclick="executeBulkAction()">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="products-table-container">
        <div class="table-responsive">
            <table class="table table-hover" id="productsTable">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="selectAll" onchange="selectAllProducts()">
                        </th>
                        <th>Product Details</th>
                        <th>Pricing</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($products ?? false): ?>
                        <?php $__currentLoopData = $products; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr data-product-id="<?php echo e($product->id); ?>">
                            <td>
                                <input type="checkbox" class="product-checkbox" value="<?php echo e($product->id); ?>" onchange="updateBulkActions()">
                            </td>
                            <td>
                                <div class="product-info d-flex align-items-center">
                                    <div class="product-image me-3">
                                        <?php if($product->hasImage()): ?>
                                            <img src="<?php echo e($product->getImageFile()); ?>" alt="<?php echo e($product->name); ?>" class="product-thumb">
                                        <?php else: ?>
                                            <div class="product-placeholder">
                                                <i class="fas fa-box"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="product-name"><?php echo e($product->name); ?></div>
                                        <div class="product-description"><?php echo e(Str::limit($product->description, 80)); ?></div>
                                        <small class="text-muted">
                                            SKU: <?php echo e($product->sku); ?> | <?php echo e($product->category); ?>

                                            <?php if($product->hasAttachment()): ?>
                                                <a href="<?php echo e($product->attachment_url); ?>" target="_blank" class="ms-2">
                                                    <i class="fas fa-file-pdf text-danger"></i>
                                                </a>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="pricing-info">
                                    <div class="retail-price">$<?php echo e(number_format($product->retail_price, 2)); ?><small>/month</small></div>
                                    <div class="wholesale-price">$<?php echo e(number_format($product->wholesale_price, 2)); ?><small>/month (wholesale)</small></div>
                                    <div class="discount-info">Max discount: <?php echo e($product->max_discount); ?>%</div>
                                </div>
                            </td>
                            <td>
                                <div class="stock-info">
                                    <span class="stock-quantity"><?php echo e($product->quantity ?? 'Unlimited'); ?></span>
                                    <div class="stock-status text-<?php echo e($product->stock_status_color); ?>"><?php echo e($product->stock_status_text); ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo e($product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'secondary' : 'warning')); ?> status-badge">
                                    <?php echo e(ucfirst($product->status)); ?>

                                </span>
                            </td>
                            <td>
                                <div class="product-tags">
                                    <?php if($product->tags): ?>
                                        <?php $__currentLoopData = $product->tags; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tag): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <?php
                                                $badgeClass = match($tag) {
                                                    'hot-deal' => 'bg-danger',
                                                    'featured' => 'bg-info',
                                                    'new-arrival' => 'bg-primary',
                                                    'limited-stock' => 'bg-warning',
                                                    'bestseller' => 'bg-success',
                                                    default => 'bg-secondary'
                                                };
                                            ?>
                                            <span class="badge <?php echo e($badgeClass); ?>"><?php echo e(ucfirst(str_replace('-', ' ', $tag))); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProduct(<?php echo e($product->id); ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editProduct(<?php echo e($product->id); ?>)" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo e($product->id); ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <!-- Sample data when no products exist -->
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No products found. Click "Add New Product" to get started.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>



<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i>
                    Add New Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addProductForm" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="productId" name="id">
                    
                    <!-- Product Type and Basic Information -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Product Type & Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Product Type *</label>
                                <div class="product-type-selector">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="product_type" id="type_tangible" value="tangible" required>
                                        <label class="form-check-label" for="type_tangible">
                                            <i class="fas fa-box"></i> Tangible Product
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="product_type" id="type_service" value="service" required>
                                        <label class="form-check-label" for="type_service">
                                            <i class="fas fa-cogs"></i> Service
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label" id="nameLabel">Product Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Minimal Description</label>
                                    <textarea class="form-control" name="minimal_description" rows="2" placeholder="Brief description for quick reference"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6" id="skuField">
                                        <div class="mb-3">
                                            <label class="form-label">SKU *</label>
                                            <input type="text" class="form-control" name="sku" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Category *</label>
                                            <select class="form-select" name="category" required>
                                                <option value="">Select Category</option>
                                                <option value="Software">Software</option>
                                                <option value="Hardware">Hardware</option>
                                                <option value="Service">Service</option>
                                                <option value="Digital Product">Digital Product</option>
                                                <option value="Physical Product">Physical Product</option>
                                                <option value="Subscription">Subscription</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4" id="imageUploadSection">
                                <!-- Product Image Upload -->
                                <div class="mb-3">
                                    <label class="form-label">Product Images</label>
                                    <input type="file" class="form-control" id="productImageInput" name="images[]" accept="image/*" multiple>
                                    <small class="text-muted">Max 5MB each, JPG/PNG only. Multiple images allowed.</small>
                                </div>
                                <div id="imagePreview" style="display: none;">
                                    <!-- Image previews will appear here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Service-Specific Fields -->
                    <div class="form-section service-fields" style="display: none;">
                        <h6 class="section-title">
                            <i class="fas fa-cogs"></i>
                            Service Configuration
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Service Delivery Type</label>
                                    <select class="form-select" name="service_delivery_type">
                                        <option value="">Select delivery type</option>
                                        <option value="digital">Digital</option>
                                        <option value="physical">Physical</option>
                                        <option value="hybrid">Hybrid</option>
                                        <option value="consultation">Consultation</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Service Duration (Days)</label>
                                    <input type="number" class="form-control" name="service_duration_days" min="1" placeholder="e.g., 30, 365">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Pricing Type *</label>
                                    <select class="form-select" name="pricing_type" required>
                                        <option value="">Select pricing type</option>
                                        <option value="one_time">One-time Payment</option>
                                        <option value="monthly">Monthly Subscription</option>
                                        <option value="yearly">Yearly Subscription</option>
                                        <option value="per_hour">Per Hour</option>
                                        <option value="per_project">Per Project</option>
                                        <option value="tiered">Tiered Pricing</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hourly Rate (TSH)</label>
                                    <input type="number" class="form-control" name="hourly_rate" step="0.01" min="0" placeholder="For hourly services">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Service Tiers Section -->
                        <div class="service-tiers-section" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Service Pricing Tiers</label>
                                <div id="serviceTiersContainer">
                                    <div class="tier-item mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Tier Name</label>
                                                <input type="text" class="form-control" name="tier_names[]" placeholder="e.g., Basic, Standard, Premium">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Price (TSH)</label>
                                                <input type="number" class="form-control" name="tier_prices[]" step="0.01" min="0">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Description</label>
                                                <input type="text" class="form-control" name="tier_descriptions[]" placeholder="e.g., Up to 200 users">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-success" onclick="addServiceTier()">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Define different pricing tiers for your service (e.g., TSH 70,000 for 200 users, TSH 95,000 for 500 users)</small>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label class="form-label">Service Deliverables</label>
                                    <textarea class="form-control" name="service_deliverables" rows="3" placeholder="What will be delivered to the customer (JSON format or plain text)"></textarea>
                                    <small class="text-muted">Describe what the customer will receive</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Service Tiers</label>
                                    <textarea class="form-control" name="service_tiers" rows="2" placeholder="Basic, Standard, Premium tiers (JSON format)"></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Prerequisites</label>
                                    <textarea class="form-control" name="prerequisites" rows="2" placeholder="Requirements before service delivery"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="requires_consultation" value="1" id="requires_consultation">
                                    <label class="form-check-label" for="requires_consultation">
                                        Requires Initial Consultation
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="form-section" id="pricingInventorySection">
                        <h6 class="section-title">
                            <i class="fas fa-dollar-sign"></i>
                            Pricing & Inventory
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Retail Price (TSH) *</label>
                                    <input type="number" class="form-control" name="retail_price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Wholesale Price (TSH) *</label>
                                    <input type="number" class="form-control" name="wholesale_price" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Max Discount (%)</label>
                                    <input type="number" class="form-control" name="max_discount" min="0" max="100" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Min Negotiable Price (TSH)</label>
                                    <input type="number" class="form-control" name="min_negotiable_price" step="0.01" min="0" placeholder="Lowest acceptable price">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="quantity" min="0" placeholder="Leave empty for unlimited">
                                    <small class="text-muted">Leave empty for unlimited stock</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Tags -->
                    <div class="form-section" id="productTagsSection">
                        <h6 class="section-title">
                            <i class="fas fa-tags"></i>
                            Product Tags
                        </h6>
                        <div class="tag-options">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="featured" id="tag_featured">
                                <label class="form-check-label" for="tag_featured">Featured</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="bestseller" id="tag_bestseller">
                                <label class="form-check-label" for="tag_bestseller">Bestseller</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="new-arrival" id="tag_new">
                                <label class="form-check-label" for="tag_new">New Arrival</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="hot-deal" id="tag_hot">
                                <label class="form-check-label" for="tag_hot">Hot Deal</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="tags[]" value="limited-stock" id="tag_limited">
                                <label class="form-check-label" for="tag_limited">Limited Stock</label>
                            </div>
                        </div>
                    </div>

                    <!-- RAG Document Management -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-brain"></i>
                            RAG Document Management
                            <span class="badge bg-info ms-2">AI-Enhanced</span>
                        </h6>
                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>RAG Enhancement:</strong> Upload documents to enhance AI responses with product-specific knowledge. Supports PDF, Word, and text files.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Product Documentation</label>
                            <input type="file" class="form-control" id="ragDocuments" name="rag_documents[]" multiple accept=".pdf,.doc,.docx,.txt,.md">
                            <small class="text-muted">Max 10MB per file. Supports: PDF, Word, Text, Markdown. Multiple files allowed.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Document Type</label>
                                    <select class="form-select" name="attachment_type">
                                        <option value="documentation">Product Documentation</option>
                                        <option value="manual">User Manual</option>
                                        <option value="specification">Technical Specifications</option>
                                        <option value="brochure">Marketing Brochure</option>
                                        <option value="faq">FAQ Document</option>
                                        <option value="tutorial">Tutorial/Guide</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" name="enable_rag_processing" value="1" id="enable_rag_processing" checked>
                                    <label class="form-check-label" for="enable_rag_processing">
                                        <i class="fas fa-robot"></i> Enable AI Document Processing
                                    </label>
                                    <small class="form-text text-muted d-block">Process documents for AI-enhanced customer responses</small>
                                </div>
                            </div>
                        </div>
                        <div id="ragDocumentPreviews" class="mt-3">
                            <!-- Document previews will appear here -->
                        </div>
                    </div>

                    <!-- AI Configuration -->
                    <div class="form-section" id="aiSalesConfigSection">
                        <h6 class="section-title">
                            <i class="fas fa-robot"></i>
                            AI Sales Configuration
                            <span class="badge bg-success ms-2">Smart Selling</span>
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">AI Sales Prompt</label>
                            <textarea class="form-control" name="ai_prompt" rows="4" placeholder="Describe how AI should present this product to customers...">
You are a knowledgeable sales assistant for [PRODUCT_NAME]. Highlight the key benefits, answer customer questions, and guide them towards making a purchase. Be helpful, professional, and persuasive while addressing their specific needs.</textarea>
                            <small class="text-muted">This prompt will guide AI responses for sales conversations about this product</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="ai_enabled" value="1" id="ai_enabled" checked>
                                    <label class="form-check-label" for="ai_enabled">
                                        <i class="fas fa-magic"></i> Enable AI Sales Assistant
                                    </label>
                                    <small class="form-text text-muted d-block">Allow AI to handle customer inquiries about this product</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="auto_rag_enhancement" value="1" id="auto_rag_enhancement" checked>
                                    <label class="form-check-label" for="auto_rag_enhancement">
                                        <i class="fas fa-brain"></i> Auto RAG Enhancement
                                    </label>
                                    <small class="form-text text-muted d-block">Automatically enhance AI responses with document knowledge</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Key Selling Points</label>
                            <div id="sellingPointsContainer">
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="selling_points[]" placeholder="Enter a key selling point">
                                    <button type="button" class="btn btn-outline-success" onclick="addSellingPoint()">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Add key selling points that AI will emphasize during sales conversations</small>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-question-circle"></i>
                            Frequently Asked Questions
                            <button type="button" class="btn btn-outline-primary btn-sm ms-auto" onclick="addFAQ()">
                                <i class="fas fa-plus"></i> Add FAQ
                            </button>
                        </h6>
                        <div id="faqContainer">
                            <!-- FAQ items will be added here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProduct()">
                    <i class="fas fa-save"></i>
                    Save Product
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i>
                    Product Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailsContent">
                <!-- Product details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="editProductFromView()">
                    <i class="fas fa-edit"></i>
                    Edit Product
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .products-page {
        padding: 0;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .btn-add-product {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-add-product:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
    }
    
    .products-table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .table {
        margin: 0;
    }
    
    .table thead th {
        background: #f8fafc;
        border: none;
        padding: 1rem;
        font-weight: 600;
        color: #475569;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .table tbody td {
        padding: 1.25rem 1rem;
        border: none;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    
    .table tbody tr:hover {
        background: #f8fafc;
    }
    
    .product-info .product-name {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }
    
    .product-info .product-description {
        color: #64748b;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
    }
    
    .product-info {
        align-items: flex-start !important;
    }
    
    .product-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    .product-placeholder {
        width: 50px;
        height: 50px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 1.2rem;
    }
    
    .table-controls {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    .bulk-actions {
        background: #e0f2fe;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        border: 1px solid #0ea5e9;
    }
    
    .pricing-info .retail-price {
        font-weight: 700;
        color: #059669;
        font-size: 1.1rem;
    }
    
    .pricing-info .wholesale-price {
        color: #0369a1;
        font-size: 0.875rem;
    }
    
    .pricing-info .discount-info {
        color: #dc2626;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .stock-info .stock-quantity {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }
    
    .stock-info .stock-status {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-badge {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .product-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    
    .product-tags .badge {
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .action-buttons .btn {
        width: 36px;
        height: 36px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    .form-section {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .tag-options {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .product-type-selector {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .product-type-selector .form-check-label {
        padding: 0.75rem 1.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
    }
    
    .product-type-selector .form-check-input:checked + .form-check-label {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        border-color: #6366f1;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }
    
    .service-fields {
        border: 2px dashed #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    }
    
    .service-fields .section-title {
        color: #059669;
    }
    
    .rag-preview-item {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: between;
    }
    
    .rag-preview-item .file-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-grow: 1;
    }
    
    .rag-preview-item .file-icon {
        font-size: 1.5rem;
    }
    
    .selling-point-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .ai-config-badge {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
    }
    
    .rag-badge {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 12px;
        font-weight: 600;
    }
    
    .form-section.ai-enhanced {
        border: 2px solid #3b82f6;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }
    
    .form-section.ai-enhanced .section-title {
        color: #1d4ed8;
    }
    
    .processing-indicator {
        display: inline-block;
        width: 1rem;
        height: 1rem;
        border: 2px solid #e2e8f0;
        border-top: 2px solid #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .document-item {
        transition: all 0.3s ease;
    }
    
    .document-item:hover {
        background: #e8f4ff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .document-item .file-icon {
        transition: all 0.3s ease;
    }
    
    .document-item:hover .file-icon {
        transform: scale(1.1);
    }
    
    #productDetailsContent .card {
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    #productDetailsContent .card-header {
        font-weight: 600;
        border-bottom: 2px solid #e2e8f0;
    }
    
    #productDetailsContent img {
        border: 3px solid #f1f5f9;
        transition: all 0.3s ease;
    }
    
    #productDetailsContent img:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    
    .faq-item {
        transition: all 0.2s ease;
    }
    
    .faq-item:hover {
        background: #f8fafc !important;
        transform: translateX(5px);
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        #productDetailsContent .row {
            flex-direction: column-reverse;
        }
    }
</style>

<!-- Make sure Bootstrap JS is loaded before your custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global variables
let currentProductId = null;
let uploadedDocuments = [];
let sellingPointsCount = 1;

// Product type management
function toggleProductTypeFields() {
    const productType = document.querySelector('input[name="product_type"]:checked')?.value;
    const serviceFields = document.querySelector('.service-fields');
    const imageUploadSection = document.getElementById('imageUploadSection');
    const skuField = document.getElementById('skuField');
    const productTagsSection = document.getElementById('productTagsSection');
    const pricingInventorySection = document.getElementById('pricingInventorySection');
    const aiSalesConfigSection = document.getElementById('aiSalesConfigSection');
    const nameLabel = document.getElementById('nameLabel');
    const serviceTiersSection = document.querySelector('.service-tiers-section');
    
    // Get fields that need dynamic required management
    const retailPriceField = document.querySelector('[name="retail_price"]');
    const wholesalePriceField = document.querySelector('[name="wholesale_price"]');
    const statusField = document.querySelector('[name="status"]');
    const skuInput = document.querySelector('[name="sku"]');
    
    if (productType === 'service') {
        // Show service fields
        serviceFields.style.display = 'block';
        
        // Hide sections not applicable to services
        if (imageUploadSection) imageUploadSection.style.display = 'none';
        if (pricingInventorySection) pricingInventorySection.style.display = 'none';
        if (aiSalesConfigSection) aiSalesConfigSection.style.display = 'none';
        if (productTagsSection) productTagsSection.style.display = 'none';
        
        // Hide SKU field for services
        if (skuField) {
            skuField.style.display = 'none';
            if (skuInput) skuInput.removeAttribute('required');
        }
        
        // Remove required from hidden pricing fields
        if (retailPriceField) retailPriceField.removeAttribute('required');
        if (wholesalePriceField) wholesalePriceField.removeAttribute('required');
        if (statusField) statusField.removeAttribute('required');
        
        // Change label to "Service Name"
        if (nameLabel) nameLabel.textContent = 'Service Name *';
        
        // Make service fields required
        serviceFields.querySelectorAll('select, input').forEach(field => {
            if (field.name === 'service_delivery_type' || field.name === 'pricing_type') {
                field.setAttribute('required', 'required');
            }
        });
    } else {
        // Hide service fields
        serviceFields.style.display = 'none';
        
        // Show sections applicable to products
        if (imageUploadSection) imageUploadSection.style.display = 'block';
        if (pricingInventorySection) pricingInventorySection.style.display = 'block';
        if (aiSalesConfigSection) aiSalesConfigSection.style.display = 'block';
        if (productTagsSection) productTagsSection.style.display = 'block';
        
        // Show SKU field for products
        if (skuField) {
            skuField.style.display = 'block';
            if (skuInput) skuInput.setAttribute('required', 'required');
        }
        
        // Add required back to pricing fields for products
        if (retailPriceField) retailPriceField.setAttribute('required', 'required');
        if (wholesalePriceField) wholesalePriceField.setAttribute('required', 'required');
        if (statusField) statusField.setAttribute('required', 'required');
        
        // Change label to "Product Name"
        if (nameLabel) nameLabel.textContent = 'Product Name *';
        
        // Hide service tiers section
        if (serviceTiersSection) serviceTiersSection.style.display = 'none';
        
        // Remove required from service fields
        serviceFields.querySelectorAll('select, input').forEach(field => {
            field.removeAttribute('required');
        });
    }
}

// Pricing type management for services
function togglePricingFields() {
    const pricingType = document.querySelector('[name="pricing_type"]')?.value;
    const serviceTiersSection = document.querySelector('.service-tiers-section');
    
    if (pricingType === 'tiered') {
        serviceTiersSection.style.display = 'block';
    } else {
        serviceTiersSection.style.display = 'none';
    }
}

// Service tiers management
let serviceTierCount = 1;

function addServiceTier() {
    serviceTierCount++;
    const container = document.getElementById('serviceTiersContainer');
    const tierHtml = `
        <div class="tier-item mb-3 p-3 border rounded">
            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Tier Name</label>
                    <input type="text" class="form-control" name="tier_names[]" placeholder="e.g., Basic, Standard, Premium">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price (TSH)</label>
                    <input type="number" class="form-control" name="tier_prices[]" step="0.01" min="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="tier_descriptions[]" placeholder="e.g., Up to 200 users">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger" onclick="removeServiceTier(this)">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', tierHtml);
}

function removeServiceTier(button) {
    if (document.querySelectorAll('.tier-item').length > 1) {
        button.closest('.tier-item').remove();
    } else {
        alert('At least one tier is required for tiered pricing.');
    }
}

// Document upload management
let documentUploadTypes = {};

function addDocumentUpload() {
    const selector = document.getElementById('documentTypeSelector');
    const selectedType = selector.value;
    
    if (!selectedType) return;
    
    // Check if this document type already exists
    if (documentUploadTypes[selectedType]) {
        alert('This document type is already added. You can upload multiple files to the existing section.');
        selector.value = '';
        return;
    }
    
    const container = document.getElementById('documentUploadContainer');
    const typeName = selector.options[selector.selectedIndex].text;
    
    const uploadSectionHtml = `
        <div class="document-type-section mb-4 p-3 border rounded" data-type="${selectedType}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">
                    <i class="fas fa-file-alt text-primary"></i>
                    ${typeName}
                </h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDocumentType('${selectedType}')">
                    <i class="fas fa-times"></i> Remove
                </button>
            </div>
            <div class="mb-2">
                <input type="file" class="form-control" name="documents_${selectedType}[]" multiple accept=".pdf,.doc,.docx,.txt,.md" onchange="handleDocumentUpload(this, '${selectedType}')">
                <small class="text-muted">Max 10MB per file. Supports: PDF, Word, Text, Markdown. Multiple files allowed.</small>
            </div>
            <div class="document-previews-${selectedType}">
                <!-- File previews will appear here -->
            </div>
            <input type="hidden" name="document_types[]" value="${selectedType}">
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', uploadSectionHtml);
    documentUploadTypes[selectedType] = true;
    selector.value = '';
}

function removeDocumentType(type) {
    const section = document.querySelector(`.document-type-section[data-type="${type}"]`);
    if (section) {
        section.remove();
        delete documentUploadTypes[type];
    }
}

function handleDocumentUpload(input, documentType) {
    const files = Array.from(input.files);
    const previewContainer = document.querySelector(`.document-previews-${documentType}`);
    
    files.forEach((file, index) => {
        if (validateRagDocument(file)) {
            const preview = createDocumentPreview(file, `${documentType}_${index}`, documentType);
            previewContainer.appendChild(preview);
        }
    });
}

// Selling points management
function addSellingPoint() {
    sellingPointsCount++;
    const container = document.getElementById('sellingPointsContainer');
    const newPoint = document.createElement('div');
    newPoint.className = 'input-group mb-2 selling-point-item';
    newPoint.innerHTML = `
        <input type="text" class="form-control" name="selling_points[]" placeholder="Enter a key selling point">
        <button type="button" class="btn btn-outline-danger" onclick="removeSellingPoint(this)">
            <i class="fas fa-minus"></i>
        </button>
    `;
    container.appendChild(newPoint);
}

function removeSellingPoint(button) {
    button.closest('.selling-point-item').remove();
}

// RAG document management
function handleRagDocuments(input) {
    const files = Array.from(input.files);
    const previewContainer = document.getElementById('ragDocumentPreviews');
    
    files.forEach((file, index) => {
        // Validate file
        if (!validateRagDocument(file)) {
            return;
        }
        
        // Create preview
        const preview = createDocumentPreview(file, index);
        previewContainer.appendChild(preview);
        
        // Store file reference
        uploadedDocuments.push({
            file: file,
            index: index,
            processed: false
        });
    });
}

function validateRagDocument(file) {
    // Check file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
        alert(`File "${file.name}" is too large. Maximum size is 10MB.`);
        return false;
    }
    
    // Check file type
    const allowedTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'text/markdown'
    ];
    
    if (!allowedTypes.includes(file.type)) {
        alert(`File "${file.name}" is not a supported format. Please use PDF, Word, Text, or Markdown files.`);
        return false;
    }
    
    return true;
}

function createDocumentPreview(file, index) {
    const preview = document.createElement('div');
    preview.className = 'rag-preview-item';
    preview.id = `rag-preview-${index}`;
    
    const icon = getFileIcon(file.type);
    const fileSize = formatFileSize(file.size);
    
    preview.innerHTML = `
        <div class="file-info">
            <div class="file-icon ${getFileIconClass(file.type)}">
                <i class="${icon}"></i>
            </div>
            <div class="file-details">
                <div class="file-name">${file.name}</div>
                <div class="file-meta text-muted">${fileSize} • Ready for processing</div>
            </div>
        </div>
        <div class="file-actions">
            <span class="badge bg-info me-2">AI Ready</span>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDocumentPreview(${index})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    return preview;
}

function getFileIcon(mimeType) {
    switch (mimeType) {
        case 'application/pdf':
            return 'fas fa-file-pdf';
        case 'application/msword':
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            return 'fas fa-file-word';
        case 'text/plain':
        case 'text/markdown':
            return 'fas fa-file-alt';
        default:
            return 'fas fa-file';
    }
}

function getFileIconClass(mimeType) {
    switch (mimeType) {
        case 'application/pdf':
            return 'text-danger';
        case 'application/msword':
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            return 'text-primary';
        case 'text/plain':
        case 'text/markdown':
            return 'text-success';
        default:
            return 'text-muted';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function removeDocumentPreview(buttonOrIndex, fileId) {
    // Handle both old index-based calls and new button-based calls
    if (typeof buttonOrIndex === 'number') {
        // Legacy index-based removal
        const preview = document.getElementById(`rag-preview-${buttonOrIndex}`);
        if (preview) {
            preview.remove();
        }
        
        // Remove from uploaded documents array
        uploadedDocuments = uploadedDocuments.filter(doc => doc.index !== buttonOrIndex);
    } else if (buttonOrIndex && buttonOrIndex.closest) {
        // New button-based removal
        buttonOrIndex.closest('.document-preview-item').remove();
    }
}

// Image and attachment preview functions
document.addEventListener('DOMContentLoaded', function() {

    // Image preview functionality for multiple images
    const imageInput = document.getElementById('productImageInput');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            const previewContainer = document.getElementById('imagePreview');
            previewContainer.innerHTML = ''; // Clear previous previews
            
            if (files.length > 0) {
                previewContainer.style.display = 'block';
                
                files.forEach((file, index) => {
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert(`Image "${file.name}" is too large. Maximum size is 5MB.`);
                        return;
                    }
                    
                    // Validate file type
                    if (!file.type.startsWith('image/')) {
                        alert(`"${file.name}" is not a valid image file.`);
                        return;
                    }
                    
                    // Create preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'image-preview-item mb-2 d-inline-block me-2';
                        previewDiv.innerHTML = `
                            <div class="position-relative">
                                <img src="${e.target.result}" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0" onclick="removeImagePreview(this)" style="margin: 2px;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <small class="d-block text-center text-muted">${file.name}</small>
                        `;
                        previewContainer.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(file);
                });
            } else {
                previewContainer.style.display = 'none';
            }
        });
    }

    // Search functionality
    const searchInput = document.getElementById('productSearch');
    if (searchInput) {
        searchInput.addEventListener('input', searchProducts);
    }
    
    // Filter functionality
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', filterByStatus);
    }
    
    // Reset form when modal is hidden
    const addProductModal = document.getElementById('addProductModal');
    if (addProductModal) {
        addProductModal.addEventListener('hidden.bs.modal', function () {
            // Reset form
            document.getElementById('addProductForm').reset();
            document.getElementById('productId').value = '';
            currentProductId = null;
            
            // Reset modal title and button
            const modalTitle = document.querySelector('#addProductModal .modal-title');
            modalTitle.innerHTML = '<i class="fas fa-plus"></i> Add New Product';
            
            const saveButton = document.querySelector('#addProductModal .btn-primary');
            if (saveButton) {
                saveButton.innerHTML = '<i class="fas fa-save"></i> Save Product';
                saveButton.setAttribute('onclick', 'saveProduct()');
            }
            
            // Clear FAQ container
            document.getElementById('faqContainer').innerHTML = '';
            
            // Clear selling points (keep one)
            const sellingPointsContainer = document.getElementById('sellingPointsContainer');
            sellingPointsContainer.innerHTML = `
                <div class="input-group mb-2">
                    <input type="text" class="form-control" name="selling_points[]" placeholder="Enter a key selling point">
                    <button type="button" class="btn btn-outline-success" onclick="addSellingPoint()">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
            sellingPointsCount = 1;
            
            // Clear RAG document previews
            document.getElementById('ragDocumentPreviews').innerHTML = '';
            uploadedDocuments = [];
            
            // Reset product type fields
            document.querySelector('.service-fields').style.display = 'none';
            
            // Clear previews
            removeImagePreview();
            
            // Add one FAQ by default for new products
            if (!currentProductId) {
                addFAQ();
            }
        });
    }
    
    // Add initial FAQ when modal opens for new product
    const addProductButton = document.querySelector('[data-bs-target="#addProductModal"]');
    if (addProductButton) {
        addProductButton.addEventListener('click', function() {
            setTimeout(() => {
                if (!document.getElementById('productId').value) {
                    addFAQ();
                }
            }, 100);
        });
    }
});

function removeImagePreview(button) {
    // Remove specific image preview
    if (button) {
        button.closest('.image-preview-item').remove();
        
        // Check if any previews remain
        const previewContainer = document.getElementById('imagePreview');
        if (previewContainer && previewContainer.children.length === 0) {
            previewContainer.style.display = 'none';
            // Clear the file input
            const imageInput = document.getElementById('productImageInput');
            if (imageInput) imageInput.value = '';
        }
    } else {
        // Remove all image previews
        const imageInput = document.getElementById('productImageInput');
        const imagePreview = document.getElementById('imagePreview');
        
        if (imageInput) imageInput.value = '';
        if (imagePreview) {
            imagePreview.style.display = 'none';
            imagePreview.innerHTML = '';
        }
    }
}

// Enhanced form validation
function validateForm() {
    const form = document.getElementById('addProductForm');
    if (!form) return false;
    
    // Get product type
    const productType = document.querySelector('input[name="product_type"]:checked');
    if (!productType) {
        alert('Please select a product type (Tangible Product or Service)');
        return false;
    }
    
    let isValid = true;
    
    // Check required fields that are currently visible and required
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        // Only validate if the field is visible (parent section is not hidden)
        const fieldContainer = field.closest('.form-section, .service-fields, #skuField');
        const isFieldVisible = fieldContainer ? window.getComputedStyle(fieldContainer).display !== 'none' : true;
        
        if (isFieldVisible && !field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validate service-specific fields if service is selected
    if (productType.value === 'service') {
        const serviceDeliveryType = document.querySelector('[name="service_delivery_type"]');
        const pricingType = document.querySelector('[name="pricing_type"]');
        
        if (serviceDeliveryType && !serviceDeliveryType.value) {
            serviceDeliveryType.classList.add('is-invalid');
            alert('Please select a service delivery type');
            isValid = false;
        }
        
        if (pricingType && !pricingType.value) {
            pricingType.classList.add('is-invalid');
            alert('Please select a pricing type for the service');
            isValid = false;
        }
        
        // For services, validate service tier pricing if tiered is selected
        if (pricingType && pricingType.value === 'tiered') {
            const tierNames = document.querySelectorAll('[name="tier_names[]"]');
            const tierPrices = document.querySelectorAll('[name="tier_prices[]"]');
            
            if (tierNames.length === 0 || tierPrices.length === 0) {
                alert('Please add at least one service tier for tiered pricing');
                isValid = false;
            }
        }
    } else {
        // Validate product-specific fields for tangible products
        const retailPriceField = document.querySelector('[name="retail_price"]');
        const wholesalePriceField = document.querySelector('[name="wholesale_price"]');
        
        if (retailPriceField && wholesalePriceField) {
            const retailPrice = parseFloat(retailPriceField.value);
            const wholesalePrice = parseFloat(wholesalePriceField.value);
            const minNegotiablePriceField = document.querySelector('[name="min_negotiable_price"]');
            const minNegotiablePrice = minNegotiablePriceField ? parseFloat(minNegotiablePriceField.value) : 0;
            
            if (retailPrice && wholesalePrice && wholesalePrice >= retailPrice) {
                alert('Wholesale price must be less than retail price');
                isValid = false;
            }
            
            if (minNegotiablePrice && wholesalePrice && minNegotiablePrice >= wholesalePrice) {
                alert('Minimum negotiable price must be less than wholesale price');
                isValid = false;
            }
        }
    }
    
    // Validate selling points (at least one) - applies to both products and services
    const sellingPoints = document.querySelectorAll('[name="selling_points[]"]');
    const hasSellingPoint = Array.from(sellingPoints).some(point => point.value.trim());
    
    if (!hasSellingPoint) {
        alert('Please add at least one key selling point');
        isValid = false;
    }
    
    return isValid;
}

// Enhanced search function
function searchProducts() {
    const searchTerm = document.getElementById('productSearch')?.value.toLowerCase() || '';
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    
    tableRows.forEach(row => {
        if (row.cells.length < 6) return; // Skip header or invalid rows
        
        const productName = row.cells[0]?.textContent.toLowerCase() || '';
        const pricing = row.cells[1]?.textContent.toLowerCase() || '';
        const status = row.cells[3]?.textContent.toLowerCase() || '';
        
        if (productName.includes(searchTerm) || pricing.includes(searchTerm) || status.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Enhanced filter function
function filterByStatus() {
    const selectedStatus = document.getElementById('statusFilter')?.value.toLowerCase() || '';
    const tableRows = document.querySelectorAll('#productsTable tbody tr');
    
    tableRows.forEach(row => {
        if (row.cells.length < 6) return; // Skip header or invalid rows
        
        const statusCell = row.cells[3];
        if (!statusCell) return;
        
        const statusText = statusCell.textContent.trim().toLowerCase();
        
        if (!selectedStatus || statusText.includes(selectedStatus)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// FAQ Management Functions
function addFAQ() {
    const faqContainer = document.getElementById('faqContainer');
    const faqCount = faqContainer.children.length + 1;
    
    const faqHtml = `
        <div class="faq-item mb-3" data-faq-index="${faqCount}">
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Question</label>
                    <input type="text" class="form-control" name="faqs[${faqCount}][question]" placeholder="Enter question">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Answer</label>
                    <textarea class="form-control" name="faqs[${faqCount}][answer]" rows="2" placeholder="Enter answer"></textarea>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFAQ(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    faqContainer.insertAdjacentHTML('beforeend', faqHtml);
}

function removeFAQ(button) {
    button.closest('.faq-item').remove();
}

// Product CRUD Functions
function saveProduct() {
    if (!validateForm()) {
        return;
    }
    
    const form = document.getElementById('addProductForm');
    const formData = new FormData(form);
    const productId = document.getElementById('productId').value;
    
    // Process selling points as JSON
    const sellingPoints = Array.from(document.querySelectorAll('[name="selling_points[]"]'))
        .map(input => input.value.trim())
        .filter(value => value);
    
    formData.delete('selling_points[]');
    formData.append('selling_points', JSON.stringify(sellingPoints));
    
    // Process service deliverables and tiers as JSON if they contain structured data
    const serviceDeliverables = document.querySelector('[name="service_deliverables"]')?.value;
    const serviceTiers = document.querySelector('[name="service_tiers"]')?.value;
    
    if (serviceDeliverables) {
        try {
            // Try to parse as JSON, if it fails, treat as plain text
            JSON.parse(serviceDeliverables);
        } catch (e) {
            // Convert plain text to simple array
            const deliverablesList = serviceDeliverables.split('\n').filter(item => item.trim());
            formData.set('service_deliverables', JSON.stringify(deliverablesList));
        }
    }
    
    if (serviceTiers) {
        try {
            JSON.parse(serviceTiers);
        } catch (e) {
            // Convert plain text to simple array
            const tiersList = serviceTiers.split('\n').filter(item => item.trim());
            formData.set('service_tiers', JSON.stringify(tiersList));
        }
    }
    
    // Add RAG processing flag
    const enableRagProcessing = document.getElementById('enable_rag_processing')?.checked;
    formData.append('enable_rag_processing', enableRagProcessing ? '1' : '0');
    
    // Add AI configuration
    const aiEnabled = document.getElementById('ai_enabled')?.checked;
    const autoRagEnhancement = document.getElementById('auto_rag_enhancement')?.checked;
    
    formData.append('ai_enabled', aiEnabled ? '1' : '0');
    formData.append('auto_rag_enhancement', autoRagEnhancement ? '1' : '0');
    
    // Show loading state
    const saveButton = document.querySelector('#addProductModal .btn-primary');
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Product & Processing Documents...';
    saveButton.disabled = true;
    
    // Determine if this is an update or create
    const url = productId ? `<?php echo e(url('/api/products')); ?>/${productId}` : '<?php echo e(url('/api/products')); ?>';
    const method = productId ? 'PUT' : 'POST';
    
    if (method === 'PUT') {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST', // Always POST because of FormData with _method
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal
            const modalElem = document.getElementById('addProductModal');
            const modal = bootstrap.Modal.getInstance(modalElem);
            modal.hide();
            
            // Show success message with RAG processing info
            let message = productId ? 'Product updated successfully!' : 'Product created successfully!';
            if (uploadedDocuments.length > 0) {
                message += ` ${uploadedDocuments.length} document(s) are being processed for AI enhancement.`;
            }
            
            showNotification(message, 'success');
            
            // Reload the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showNotification(data.message || 'Error saving product', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error saving product', 'error');
    })
    .finally(() => {
        // Restore button state
        saveButton.innerHTML = originalText;
        saveButton.disabled = false;
    });
}

function viewProduct(productId) {
    fetch(`<?php echo e(url('/api/products')); ?>/${productId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayProductDetails(data.product);
            const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
            modal.show();
        } else {
            showNotification('Error loading product details', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error loading product details', 'error');
    });
}

function editProduct(productId) {
    fetch(`<?php echo e(url('/api/products')); ?>/${productId}/edit`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            populateEditForm(data.product);
            const modal = new bootstrap.Modal(document.getElementById('addProductModal'));
            modal.show();
        } else {
            showNotification('Error loading product for editing', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error loading product for editing', 'error');
    });
}

function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        return;
    }
    
    fetch(`<?php echo e(url('/api/products')); ?>/${productId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product deleted successfully!', 'success');
            // Remove the row from table
            const row = document.querySelector(`tr[data-product-id="${productId}"]`);
            if (row) {
                row.remove();
            }
        } else {
            showNotification(data.message || 'Error deleting product', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting product', 'error');
    });
}

// Bulk Actions
function selectAllProducts() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    
    productCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedCheckboxes.length > 0) {
        bulkActions.style.display = 'block';
        selectedCount.textContent = selectedCheckboxes.length;
    } else {
        bulkActions.style.display = 'none';
    }
}

function executeBulkAction() {
    const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
    const action = document.getElementById('bulkActionSelect').value;
    
    if (!action) {
        showNotification('Please select an action', 'warning');
        return;
    }
    
    if (selectedCheckboxes.length === 0) {
        showNotification('Please select at least one product', 'warning');
        return;
    }
    
    const productIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (!confirm(`Are you sure you want to ${action} ${productIds.length} product(s)?`)) {
        return;
    }
    
    fetch('<?php echo e(url('/api/products/bulk-action')); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify({
            action: action,
            product_ids: productIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Bulk ${action} completed successfully!`, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Error performing bulk action', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error performing bulk action', 'error');
    });
}

// Helper Functions
function populateEditForm(product) {
    // Update modal title
    const modalTitle = document.querySelector('#addProductModal .modal-title');
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Product';
    }
    
    // Update save button
    const saveButton = document.querySelector('#addProductModal .btn-primary');
    if (saveButton) {
        saveButton.innerHTML = '<i class="fas fa-save"></i> Update Product';
    }
    
    // Populate basic fields with null checks
    const productIdField = document.getElementById('productId');
    if (productIdField) productIdField.value = product.id;
    
    const nameField = document.querySelector('[name="name"]');
    if (nameField) nameField.value = product.name || '';
    
    const descriptionField = document.querySelector('[name="description"]');
    if (descriptionField) descriptionField.value = product.description || '';
    
    const minimalDescriptionField = document.querySelector('[name="minimal_description"]');
    if (minimalDescriptionField) minimalDescriptionField.value = product.minimal_description || '';
    
    const skuField = document.querySelector('[name="sku"]');
    if (skuField) skuField.value = product.sku || '';
    
    const categoryField = document.querySelector('[name="category"]');
    if (categoryField) categoryField.value = product.category || '';
    
    // Populate product type
    if (product.product_type) {
        const productTypeRadio = document.querySelector(`[name="product_type"][value="${product.product_type}"]`);
        if (productTypeRadio) {
            productTypeRadio.checked = true;
            toggleProductTypeFields();
        }
    }
    
    // Populate service fields
    if (product.product_type === 'service') {
        const serviceDeliveryTypeField = document.querySelector('[name="service_delivery_type"]');
        if (serviceDeliveryTypeField) serviceDeliveryTypeField.value = product.service_delivery_type || '';
        
        const serviceDurationField = document.querySelector('[name="service_duration_days"]');
        if (serviceDurationField) serviceDurationField.value = product.service_duration_days || '';
        
        const pricingTypeField = document.querySelector('[name="pricing_type"]');
        if (pricingTypeField) pricingTypeField.value = product.pricing_type || '';
        
        const hourlyRateField = document.querySelector('[name="hourly_rate"]');
        if (hourlyRateField) hourlyRateField.value = product.hourly_rate || '';
        
        const serviceDeliverablesField = document.querySelector('[name="service_deliverables"]');
        if (serviceDeliverablesField) serviceDeliverablesField.value = product.service_deliverables || '';
        
        const serviceTiersField = document.querySelector('[name="service_tiers"]');
        if (serviceTiersField) serviceTiersField.value = product.service_tiers || '';
        
        const prerequisitesField = document.querySelector('[name="prerequisites"]');
        if (prerequisitesField) prerequisitesField.value = product.prerequisites || '';
        
        const requiresConsultation = document.querySelector('[name="requires_consultation"]');
        if (requiresConsultation) {
            requiresConsultation.checked = product.requires_consultation;
        }
    }
    
    // Populate pricing fields with null checks
    const retailPriceField = document.querySelector('[name="retail_price"]');
    if (retailPriceField) retailPriceField.value = product.retail_price || '';
    
    const wholesalePriceField = document.querySelector('[name="wholesale_price"]');
    if (wholesalePriceField) wholesalePriceField.value = product.wholesale_price || '';
    
    const minNegotiableField = document.querySelector('[name="min_negotiable_price"]');
    if (minNegotiableField) minNegotiableField.value = product.min_negotiable_price || '';
    
    const maxDiscountField = document.querySelector('[name="max_discount"]');
    if (maxDiscountField) maxDiscountField.value = product.max_discount || 0;
    
    const quantityField = document.querySelector('[name="quantity"]');
    if (quantityField) quantityField.value = product.quantity || '';
    
    const lowStockField = document.querySelector('[name="low_stock_threshold"]');
    if (lowStockField) lowStockField.value = product.low_stock_threshold || 10;
    
    const conversionRateField = document.querySelector('[name="conversion_rate"]');
    if (conversionRateField) conversionRateField.value = product.conversion_rate || 0;
    
    const statusField = document.querySelector('[name="status"]');
    if (statusField) statusField.value = product.status || 'active';
    
    // Populate AI fields with null checks
    const aiPromptField = document.querySelector('[name="ai_prompt"]');
    if (aiPromptField) aiPromptField.value = product.ai_prompt || '';
    
    const aiEnabled = document.getElementById('ai_enabled');
    if (aiEnabled) {
        aiEnabled.checked = product.ai_enabled;
    }
    
    const autoRagEnhancement = document.getElementById('auto_rag_enhancement');
    if (autoRagEnhancement) {
        autoRagEnhancement.checked = product.auto_rag_enhancement !== false;
    }
    
    const enableRagProcessing = document.getElementById('enable_rag_processing');
    if (enableRagProcessing) {
        enableRagProcessing.checked = product.enable_rag_processing !== false;
    }
    
    // Populate tags
    const tagCheckboxes = document.querySelectorAll('[name="tags[]"]');
    tagCheckboxes.forEach(checkbox => {
        checkbox.checked = product.tags && product.tags.includes(checkbox.value);
    });
    
    // Populate selling points
    const sellingPointsContainer = document.getElementById('sellingPointsContainer');
    if (sellingPointsContainer) {
        sellingPointsContainer.innerHTML = '';
        
        let sellingPoints = [];
        if (product.selling_points) {
            try {
                sellingPoints = typeof product.selling_points === 'string' 
                    ? JSON.parse(product.selling_points) 
                    : product.selling_points;
            } catch (e) {
                sellingPoints = [product.selling_points];
            }
        }
        
        if (sellingPoints.length === 0) {
            sellingPoints = [''];
        }
        
        sellingPoints.forEach((point, index) => {
            const pointHtml = `
                <div class="input-group mb-2 selling-point-item">
                    <input type="text" class="form-control" name="selling_points[]" placeholder="Enter a key selling point" value="${point || ''}">
                    <button type="button" class="btn btn-outline-${index === 0 ? 'success' : 'danger'}" onclick="${index === 0 ? 'addSellingPoint()' : 'removeSellingPoint(this)'}">
                        <i class="fas fa-${index === 0 ? 'plus' : 'minus'}"></i>
                    </button>
                </div>
            `;
            sellingPointsContainer.insertAdjacentHTML('beforeend', pointHtml);
        });
        
        sellingPointsCount = sellingPoints.length;
    }
    
    // Populate FAQs
    const faqContainer = document.getElementById('faqContainer');
    if (faqContainer) {
        faqContainer.innerHTML = '';
        
        if (product.faqs && product.faqs.length > 0) {
            product.faqs.forEach((faq, index) => {
                addFAQ();
                const faqItems = faqContainer.children;
                const lastFaqItem = faqItems[faqItems.length - 1];
                const questionField = lastFaqItem.querySelector('[name$="[question]"]');
                const answerField = lastFaqItem.querySelector('[name$="[answer]"]');
                if (questionField) questionField.value = faq.question || '';
                if (answerField) answerField.value = faq.answer || '';
            });
        } else {
            addFAQ(); // Add one FAQ by default
        }
    }
    
    // Handle existing attachments/documents
    if (product.attachments && product.attachments.length > 0) {
        const ragDocumentPreviews = document.getElementById('ragDocumentPreviews');
        ragDocumentPreviews.innerHTML = '';
        
        product.attachments.forEach((attachment, index) => {
            const existingPreview = document.createElement('div');
            existingPreview.className = 'rag-preview-item existing-document';
            existingPreview.innerHTML = `
                <div class="file-info">
                    <div class="file-icon ${getFileIconClass(attachment.mime_type)}">
                        <i class="${getFileIcon(attachment.mime_type)}"></i>
                    </div>
                    <div class="file-details">
                        <div class="file-name">${attachment.file_name}</div>
                        <div class="file-meta text-muted">
                            ${formatFileSize(attachment.file_size)} • 
                            <span class="badge bg-${attachment.processing_status === 'completed' ? 'success' : attachment.processing_status === 'processing' ? 'warning' : 'secondary'}">
                                ${attachment.processing_status || 'pending'}
                            </span>
                            ${attachment.vector_count ? ` • ${attachment.vector_count} vectors` : ''}
                        </div>
                    </div>
                </div>
                <div class="file-actions">
                    <a href="${attachment.file_path}" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                        <i class="fas fa-download"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning me-2" onclick="reprocessDocument(${attachment.id})" title="Reprocess for RAG">
                        <i class="fas fa-sync"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExistingDocument(${attachment.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            ragDocumentPreviews.appendChild(existingPreview);
        });
    }
}

// Additional helper functions for document management
function reprocessDocument(attachmentId) {
    if (!confirm('Reprocess this document for RAG enhancement?')) {
        return;
    }
    
    fetch(`<?php echo e(url('/api/products/attachments')); ?>/${attachmentId}/reprocess`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Document reprocessing started!', 'success');
            // Update status indicator
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Error reprocessing document', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error reprocessing document', 'error');
    });
}

function removeExistingDocument(attachmentId) {
    if (!confirm('Delete this document permanently?')) {
        return;
    }
    
    fetch(`<?php echo e(url('/api/products/attachments')); ?>/${attachmentId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                           document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Document deleted successfully!', 'success');
            // Remove from UI
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.message || 'Error deleting document', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting document', 'error');
    });
}

function displayProductDetails(product) {
    const content = document.getElementById('productDetailsContent');
    const tagsHtml = product.tags && product.tags.length > 0 
        ? product.tags.map(tag => `<span class="badge bg-primary me-1">${tag}</span>`).join('')
        : '<span class="text-muted">No tags</span>';
    
    const faqsHtml = product.faqs && product.faqs.length > 0
        ? product.faqs.map(faq => `
            <div class="faq-item mb-2 p-2 bg-light rounded">
                <strong><i class="fas fa-question-circle text-primary"></i> Q: ${faq.question}</strong><br>
                <span class="text-muted ms-3"><i class="fas fa-arrow-right text-success"></i> A: ${faq.answer}</span>
            </div>
        `).join('')
        : '<span class="text-muted">No FAQs available</span>';
    
    // Build documents HTML
    const documentsHtml = product.attachments && product.attachments.length > 0
        ? product.attachments.map(doc => {
            const icon = getFileIcon(doc.mime_type);
            const iconClass = getFileIconClass(doc.mime_type);
            const statusBadge = doc.processing_status === 'completed' ? 'success' : 
                              doc.processing_status === 'processing' ? 'warning' : 
                              doc.processing_status === 'failed' ? 'danger' : 'secondary';
            const fileSize = formatFileSize(doc.file_size);
            
            return `
                <div class="document-item p-3 mb-2 bg-light rounded border">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="file-icon ${iconClass} me-3" style="font-size: 2rem;">
                                <i class="${icon}"></i>
                            </div>
                            <div>
                                <div class="fw-bold">${doc.file_name}</div>
                                <small class="text-muted">
                                    ${fileSize} • ${doc.attachment_type || 'Document'}
                                    ${doc.vector_count ? ` • ${doc.vector_count} AI vectors` : ''}
                                </small>
                                <div class="mt-1">
                                    <span class="badge bg-${statusBadge}">${doc.processing_status || 'pending'}</span>
                                    ${doc.processed_at ? `<small class="text-muted ms-2">Processed: ${new Date(doc.processed_at).toLocaleDateString()}</small>` : ''}
                                </div>
                            </div>
                        </div>
                        <div>
                            <a href="/storage/${doc.file_path}" target="_blank" class="btn btn-sm btn-outline-primary" title="Download">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }).join('')
        : '<div class="text-muted text-center p-3 bg-light rounded"><i class="fas fa-folder-open"></i> No documents uploaded</div>';
    
    // Build selling points HTML
    let sellingPointsHtml = '';
    if (product.selling_points) {
        try {
            const points = typeof product.selling_points === 'string' 
                ? JSON.parse(product.selling_points) 
                : product.selling_points;
            
            if (points && points.length > 0) {
                sellingPointsHtml = points.map(point => 
                    `<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>${point}</li>`
                ).join('');
            }
        } catch (e) {
            sellingPointsHtml = '<li class="text-muted">No selling points available</li>';
        }
    } else {
        sellingPointsHtml = '<li class="text-muted">No selling points available</li>';
    }
    
    // Build service info HTML if applicable
    const serviceInfoHtml = product.product_type === 'service' ? `
        <div class="card mb-3 border-success">
            <div class="card-header bg-success text-white">
                <i class="fas fa-cogs"></i> Service Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Delivery Type:</strong> ${product.service_delivery_type || 'N/A'}<br>
                        <strong>Duration:</strong> ${product.service_duration_days ? product.service_duration_days + ' days' : 'N/A'}<br>
                        <strong>Pricing Type:</strong> ${product.pricing_type || 'N/A'}<br>
                    </div>
                    <div class="col-md-6">
                        <strong>Hourly Rate:</strong> ${product.hourly_rate ? '$' + product.hourly_rate : 'N/A'}<br>
                        <strong>Consultation Required:</strong> ${product.requires_consultation ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-secondary">No</span>'}<br>
                    </div>
                </div>
            </div>
        </div>
    ` : '';
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <h4 class="mb-2">${product.name}</h4>
                    <span class="badge bg-${product.product_type === 'service' ? 'success' : 'primary'} me-2">
                        <i class="fas fa-${product.product_type === 'service' ? 'cogs' : 'box'}"></i>
                        ${product.product_type === 'service' ? 'Service' : 'Product'}
                    </span>
                    <span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">
                        ${product.status}
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Description:</strong>
                    <p class="text-muted">${product.description || 'No description available'}</p>
                </div>
                
                ${product.ai_description ? `
                    <div class="mb-3">
                        <strong><i class="fas fa-robot text-primary"></i> AI Description:</strong>
                        <p class="text-muted">${product.ai_description}</p>
                    </div>
                ` : ''}
                
                ${serviceInfoHtml}
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="fas fa-dollar-sign"></i> Pricing Information
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>SKU:</strong> ${product.sku || 'N/A'}<br>
                                <strong>Category:</strong> ${product.category || 'N/A'}<br>
                                <strong>Retail Price:</strong> <span class="text-success fw-bold">$${product.retail_price || '0.00'}</span><br>
                                <strong>Wholesale Price:</strong> <span class="text-primary">$${product.wholesale_price || '0.00'}</span><br>
                            </div>
                            <div class="col-sm-6">
                                ${product.min_negotiable_price ? `<strong>Min Negotiable:</strong> $${product.min_negotiable_price}<br>` : ''}
                                <strong>Max Discount:</strong> ${product.max_discount || 0}%<br>
                                <strong>Stock:</strong> ${product.quantity || 'Unlimited'}<br>
                                ${product.low_stock_threshold ? `<strong>Low Stock Alert:</strong> ${product.low_stock_threshold}<br>` : ''}
                                ${product.conversion_rate ? `<strong>Conversion Rate:</strong> ${product.conversion_rate}%<br>` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-star text-warning"></i> Key Selling Points:</strong>
                    <ul class="mt-2">
                        ${sellingPointsHtml}
                    </ul>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-tags"></i> Tags:</strong><br>
                    ${tagsHtml}
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-question-circle"></i> FAQs:</strong>
                    <div class="mt-2">
                        ${faqsHtml}
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <i class="fas fa-image"></i> Product Image
                    </div>
                    <div class="card-body text-center">
                        ${product.image_url 
                            ? `<img src="${product.image_url}" class="img-fluid rounded shadow-sm" alt="${product.name}" style="max-height: 300px;">` 
                            : `<div class="text-muted p-5 bg-light rounded">
                                <i class="fas fa-image fa-3x mb-3"></i><br>
                                No image available
                               </div>`
                        }
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-file-alt"></i> Uploaded Documents
                        ${product.attachments && product.attachments.length > 0 
                            ? `<span class="badge bg-primary float-end">${product.attachments.length}</span>` 
                            : ''
                        }
                    </div>
                    <div class="card-body">
                        ${documentsHtml}
                    </div>
                </div>
            </div>
        </div>
    `;
}

function editProductFromView() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('viewProductModal'));
    modal.hide();
    // Get product ID from the current view and edit it
    // This would need to be implemented based on how you store the current product ID
}

function showNotification(message, type = 'info') {
    // Simple notification function - you can enhance this with a proper notification library
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.textContent.includes(message)) {
                alert.remove();
            }
        });
    }, 5000);
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set default product type to tangible
    const defaultType = document.getElementById('type_tangible');
    if (defaultType) {
        defaultType.checked = true;
    }
    
    // Initialize product type fields
    toggleProductTypeFields();
    
    // Add event listeners for product type change
    document.querySelectorAll('input[name="product_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            toggleProductTypeFields();
        });
    });
    
    const pricingTypeSelect = document.querySelector('[name="pricing_type"]');
    if (pricingTypeSelect) {
        pricingTypeSelect.addEventListener('change', togglePricingFields);
    }
});

// Create document preview element
function createDocumentPreview(file, fileId, documentType) {
    const preview = document.createElement('div');
    preview.className = 'document-preview-item d-flex align-items-center mb-2 p-2 border rounded';
    preview.innerHTML = `
        <div class="me-3">
            <i class="fas fa-file-alt text-primary fs-4"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold">${file.name}</div>
            <small class="text-muted">${formatFileSize(file.size)}</small>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeDocumentPreview(this, '${fileId}')">
            <i class="fas fa-times"></i>
        </button>
    `;
    return preview;
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\safarichat\resources\views/service/products.blade.php ENDPATH**/ ?>