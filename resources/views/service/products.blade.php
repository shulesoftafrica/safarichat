
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
                    @if($products ?? false)
                        @foreach($products as $product)
                        <tr data-product-id="{{ $product->id }}">
                            <td>
                                <input type="checkbox" class="product-checkbox" value="{{ $product->id }}" onchange="updateBulkActions()">
                            </td>
                            <td>
                                <div class="product-info d-flex align-items-center">
                                    <div class="product-image me-3">
                                        @if($product->hasImage())
                                            <img src="{{ $product->getImageFile() }}" alt="{{ $product->name }}" class="product-thumb">
                                        @else
                                            <div class="product-placeholder">
                                                <i class="fas fa-box"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="product-name">{{ $product->name }}</div>
                                        <div class="product-description">{{ Str::limit($product->description, 80) }}</div>
                                        <small class="text-muted">
                                            SKU: {{ $product->sku }} | {{ $product->category }}
                                            @if($product->hasAttachment())
                                                <a href="{{ $product->attachment_url }}" target="_blank" class="ms-2">
                                                    <i class="fas fa-file-pdf text-danger"></i>
                                                </a>
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="pricing-info">
                                    <div class="retail-price">${{ number_format($product->retail_price, 2) }}<small>/month</small></div>
                                    <div class="wholesale-price">${{ number_format($product->wholesale_price, 2) }}<small>/month (wholesale)</small></div>
                                    <div class="discount-info">Max discount: {{ $product->max_discount }}%</div>
                                </div>
                            </td>
                            <td>
                                <div class="stock-info">
                                    <span class="stock-quantity">{{ $product->quantity ?? 'Unlimited' }}</span>
                                    <div class="stock-status text-{{ $product->stock_status_color }}">{{ $product->stock_status_text }}</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'secondary' : 'warning') }} status-badge">
                                    {{ ucfirst($product->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="product-tags">
                                    @if($product->tags)
                                        @foreach($product->tags as $tag)
                                            @php
                                                $badgeClass = match($tag) {
                                                    'hot-deal' => 'bg-danger',
                                                    'featured' => 'bg-info',
                                                    'new-arrival' => 'bg-primary',
                                                    'limited-stock' => 'bg-warning',
                                                    'bestseller' => 'bg-success',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('-', ' ', $tag)) }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewProduct({{ $product->id }})" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editProduct({{ $product->id }})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct({{ $product->id }})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    @else
                        <!-- Sample data when no products exist -->
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No products found. Click "Add New Product" to get started.</p>
                            </td>
                        </tr>
                    @endif
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
                    @csrf
                    <input type="hidden" id="productId" name="id">
                    
                    <!-- Product Basic Information -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Basic Information
                        </h6>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description *</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
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
                            <div class="col-md-4">
                                <!-- Product Image Upload -->
                                <div class="mb-3">
                                    <label class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="productImageInput" name="image" accept="image/*">
                                    <small class="text-muted">Max 5MB, JPG/PNG only</small>
                                </div>
                                <div id="imagePreview" style="display: none;">
                                    <img id="previewImg" class="img-thumbnail" style="max-width: 200px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeImagePreview()">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="form-section">
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
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="quantity" min="0" placeholder="Leave empty for unlimited">
                                    <small class="text-muted">Leave empty for unlimited stock</small>
                                </div>
                            </div>
                            <div class="col-md-6">
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
                    <div class="form-section">
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

                    <!-- Product Attachment -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-paperclip"></i>
                            Additional Files
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Product Brochure/Manual (PDF)</label>
                            <input type="file" class="form-control" id="productAttachmentInput" name="attachment" accept=".pdf">
                            <small class="text-muted">Max 10MB, PDF only</small>
                        </div>
                        <div id="attachmentPreview" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-file-pdf text-danger"></i>
                                <span id="attachmentName"></span>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeAttachmentPreview()">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
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
    <div class="modal-dialog modal-lg">
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
    
    .faq-item {
        background: white;
        padding: 1rem;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
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
    }
</style>

<!-- Make sure Bootstrap JS is loaded before your custom scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Global variables
let currentProductId = null;

// Image and attachment preview functions
document.addEventListener('DOMContentLoaded', function() {

    // Image preview functionality
    const imageInput = document.getElementById('productImageInput');
    const attachmentInput = document.getElementById('productAttachmentInput');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Image file size must be less than 5MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file');
                    this.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    if (attachmentInput) {
        attachmentInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file size (10MB)
                if (file.size > 10 * 1024 * 1024) {
                    alert('PDF file size must be less than 10MB');
                    this.value = '';
                    return;
                }
                
                // Validate file type
                if (file.type !== 'application/pdf') {
                    alert('Please select a PDF file only');
                    this.value = '';
                    return;
                }
                
                // Show preview
                document.getElementById('attachmentName').textContent = file.name;
                document.getElementById('attachmentPreview').style.display = 'block';
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
            
            // Clear previews
            removeImagePreview();
            removeAttachmentPreview();
            
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

function removeImagePreview() {
    const imageInput = document.getElementById('productImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageInput) imageInput.value = '';
    if (imagePreview) imagePreview.style.display = 'none';
    if (previewImg) previewImg.src = '';
}

function removeAttachmentPreview() {
    const attachmentInput = document.getElementById('productAttachmentInput');
    const attachmentPreview = document.getElementById('attachmentPreview');
    const attachmentName = document.getElementById('attachmentName');
    
    if (attachmentInput) attachmentInput.value = '';
    if (attachmentPreview) attachmentPreview.style.display = 'none';
    if (attachmentName) attachmentName.textContent = '';
}

// Enhanced form validation
function validateForm() {
    const form = document.getElementById('addProductForm');
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    // Validate prices
    const retailPrice = parseFloat(document.querySelector('[name="retail_price"]').value);
    const wholesalePrice = parseFloat(document.querySelector('[name="wholesale_price"]').value);
    
    if (wholesalePrice >= retailPrice) {
        alert('Wholesale price must be less than retail price');
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
    
    // Show loading state
    const saveButton = document.querySelector('#addProductModal .btn-primary');
    const originalText = saveButton.innerHTML;
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveButton.disabled = true;
    
    // Determine if this is an update or create
    const url = productId ? `/api/products/${productId}` : '/api/products';
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
            
            // Show success message
            showNotification('Product saved successfully!', 'success');
            
            // Reload the page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
    fetch(`/api/products/${productId}`)
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
    fetch(`/api/products/${productId}/edit`)
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
    
    fetch(`/api/products/${productId}`, {
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
    
    fetch('/api/products/bulk-action', {
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
    modalTitle.innerHTML = '<i class="fas fa-edit"></i> Edit Product';
    
    // Update save button
    const saveButton = document.querySelector('#addProductModal .btn-primary');
    saveButton.innerHTML = '<i class="fas fa-save"></i> Update Product';
    
    // Populate form fields
    document.getElementById('productId').value = product.id;
    document.querySelector('[name="name"]').value = product.name || '';
    document.querySelector('[name="description"]').value = product.description || '';
    document.querySelector('[name="sku"]').value = product.sku || '';
    document.querySelector('[name="category"]').value = product.category || '';
    document.querySelector('[name="retail_price"]').value = product.retail_price || '';
    document.querySelector('[name="wholesale_price"]').value = product.wholesale_price || '';
    document.querySelector('[name="max_discount"]').value = product.max_discount || 0;
    document.querySelector('[name="quantity"]').value = product.quantity || '';
    document.querySelector('[name="status"]').value = product.status || 'active';
    
    // Populate tags
    const tagCheckboxes = document.querySelectorAll('[name="tags[]"]');
    tagCheckboxes.forEach(checkbox => {
        checkbox.checked = product.tags && product.tags.includes(checkbox.value);
    });
    
    // Populate FAQs
    const faqContainer = document.getElementById('faqContainer');
    faqContainer.innerHTML = '';
    
    if (product.faqs && product.faqs.length > 0) {
        product.faqs.forEach((faq, index) => {
            addFAQ();
            const faqItems = faqContainer.children;
            const lastFaqItem = faqItems[faqItems.length - 1];
            lastFaqItem.querySelector('[name$="[question]"]').value = faq.question || '';
            lastFaqItem.querySelector('[name$="[answer]"]').value = faq.answer || '';
        });
    }
}

function displayProductDetails(product) {
    const content = document.getElementById('productDetailsContent');
    const tagsHtml = product.tags && product.tags.length > 0 
        ? product.tags.map(tag => `<span class="badge bg-primary me-1">${tag}</span>`).join('')
        : '<span class="text-muted">No tags</span>';
    
    const faqsHtml = product.faqs && product.faqs.length > 0
        ? product.faqs.map(faq => `
            <div class="faq-item mb-2">
                <strong>Q: ${faq.question}</strong><br>
                <span class="text-muted">A: ${faq.answer}</span>
            </div>
        `).join('')
        : '<span class="text-muted">No FAQs available</span>';
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-8">
                <h5>${product.name}</h5>
                <p class="text-muted">${product.description}</p>
                
                <div class="row mb-3">
                    <div class="col-sm-6">
                        <strong>SKU:</strong> ${product.sku}<br>
                        <strong>Category:</strong> ${product.category}<br>
                        <strong>Status:</strong> <span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">${product.status}</span>
                    </div>
                    <div class="col-sm-6">
                        <strong>Retail Price:</strong> $${product.retail_price}<br>
                        <strong>Wholesale Price:</strong> $${product.wholesale_price}<br>
                        <strong>Stock:</strong> ${product.quantity || 'Unlimited'}
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Tags:</strong><br>
                    ${tagsHtml}
                </div>
                
                <div class="mb-3">
                    <strong>FAQs:</strong><br>
                    ${faqsHtml}
                </div>
            </div>
            <div class="col-md-4">
                ${product.image_url ? `<img src="${product.image_url}" class="img-fluid rounded">` : '<div class="text-center text-muted">No image available</div>'}
                ${product.attachment_url ? `<a href="${product.attachment_url}" target="_blank" class="btn btn-outline-primary btn-sm mt-2"><i class="fas fa-file-pdf"></i> View Attachment</a>` : ''}
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

</script>
