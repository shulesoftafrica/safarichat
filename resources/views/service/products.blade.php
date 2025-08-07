<div class="products-page">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-box"></i>
            Product Management
        </h2>
        <button class="btn btn-primary btn-add-product" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus"></i>
            Add New Product
        </button>
    </div>
    
    <div class="products-table-container">
        <div class="table-responsive">
            <table class="table table-hover" id="productsTable">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Pricing</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Tags</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-name">WhatsApp Business Package Pro</div>
                                <div class="product-description">Complete WhatsApp automation solution with AI integration for businesses</div>
                                <small class="text-muted">SKU: WBP-001</small>
                            </div>
                        </td>
                        <td>
                            <div class="pricing-info">
                                <div class="retail-price">$299<small>/month</small></div>
                                <div class="wholesale-price">$249<small>/month (wholesale)</small></div>
                                <div class="discount-info">Max discount: 15%</div>
                            </div>
                        </td>
                        <td>
                            <div class="stock-info">
                                <span class="stock-quantity">Unlimited</span>
                                <div class="stock-status text-success">In Stock</div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success status-badge">Active</span>
                        </td>
                        <td>
                            <div class="product-tags">
                                <span class="badge bg-danger">Hot Deal</span>
                                <span class="badge bg-info">Featured</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewProduct(1)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editProduct(1)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(1)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-name">Basic SMS Package</div>
                                <div class="product-description">Essential SMS messaging features for small businesses</div>
                                <small class="text-muted">SKU: SMS-001</small>
                            </div>
                        </td>
                        <td>
                            <div class="pricing-info">
                                <div class="retail-price">$99<small>/month</small></div>
                                <div class="wholesale-price">$79<small>/month (wholesale)</small></div>
                                <div class="discount-info">Max discount: 10%</div>
                            </div>
                        </td>
                        <td>
                            <div class="stock-info">
                                <span class="stock-quantity">25</span>
                                <div class="stock-status text-warning">Low Stock</div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-success status-badge">Active</span>
                        </td>
                        <td>
                            <div class="product-tags">
                                <span class="badge bg-warning">Limited Stock</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewProduct(2)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editProduct(2)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(2)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <div class="product-info">
                                <div class="product-name">Enterprise Communication Suite</div>
                                <div class="product-description">Full-scale WhatsApp and SMS automation with advanced analytics</div>
                                <small class="text-muted">SKU: ECS-001</small>
                            </div>
                        </td>
                        <td>
                            <div class="pricing-info">
                                <div class="retail-price">$599<small>/month</small></div>
                                <div class="wholesale-price">$499<small>/month (wholesale)</small></div>
                                <div class="discount-info">Max discount: 20%</div>
                            </div>
                        </td>
                        <td>
                            <div class="stock-info">
                                <span class="stock-quantity">5</span>
                                <div class="stock-status text-danger">Very Low</div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary status-badge">Inactive</span>
                        </td>
                        <td>
                            <div class="product-tags">
                                <span class="badge bg-primary">New Arrival</span>
                                <span class="badge bg-warning">Limited Stock</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewProduct(3)" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning" onclick="editProduct(3)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(3)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
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
                <form id="addProductForm">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="fas fa-info-circle"></i>
                                    Basic Information
                                </h6>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" name="product_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">SKU *</label>
                                    <input type="text" class="form-control" name="sku" placeholder="e.g., WBP-001" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select class="form-select" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Communication">Communication</option>
                                        <option value="Marketing">Marketing</option>
                                        <option value="Enterprise">Enterprise</option>
                                        <option value="Analytics">Analytics</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoGenerateDescription">
                                        <label class="form-check-label" for="autoGenerateDescription">
                                            <i class="fas fa-robot text-primary"></i>
                                            Let AI auto-generate description?
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="descriptionField">
                                    <label class="form-label">Key Description *</label>
                                    <textarea class="form-control" name="description" rows="4" placeholder="Describe the main features and benefits of this product..." required></textarea>
                                </div>
                                
                                <div class="mb-3" id="minimalDescriptionField" style="display: none;">
                                    <label class="form-label">Minimal Info for AI *</label>
                                    <input type="text" class="form-control" name="minimal_description" placeholder="e.g., WhatsApp automation tool for businesses">
                                    <small class="text-muted">AI will generate detailed description from this</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="form-section">
                                <h6 class="section-title">
                                    <i class="fas fa-dollar-sign"></i>
                                    Pricing & Stock
                                </h6>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Retail Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="retail_price" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Wholesale Price *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="wholesale_price" step="0.01" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Max Discount % *</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="max_discount" min="0" max="100" required>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Quantity Available</label>
                                        <input type="number" class="form-control" name="quantity" min="0" placeholder="Leave empty for unlimited">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Product Tags</label>
                                    <div class="tag-options">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="new-arrival" id="tag1">
                                            <label class="form-check-label" for="tag1">New Arrival</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="hot-deal" id="tag2">
                                            <label class="form-check-label" for="tag2">Hot Deal</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="limited-stock" id="tag3">
                                            <label class="form-check-label" for="tag3">Limited Stock</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="featured" id="tag4">
                                            <label class="form-check-label" for="tag4">Featured</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="tags[]" value="bestseller" id="tag5">
                                            <label class="form-check-label" for="tag5">Bestseller</label>
                                        </div>
                                    </div>
                                </div>
                                
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
                    
                    <!-- FAQ Section -->
                    <div class="form-section">
                        <h6 class="section-title">
                            <i class="fas fa-question-circle"></i>
                            Frequently Asked Questions
                        </h6>
                        
                        <div id="faqContainer">
                            <div class="faq-item mb-3">
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="faq_questions[]" placeholder="Question">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" name="faq_answers[]" placeholder="Answer">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFAQ(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addFAQ()">
                            <i class="fas fa-plus"></i>
                            Add FAQ
                        </button>
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

<script>
function initializeProducts() {
    // Initialize DataTable
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('#productsTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [5] }
            ],
            language: {
                search: "Search products:",
                lengthMenu: "Show _MENU_ products",
                info: "Showing _START_ to _END_ of _TOTAL_ products"
            }
        });
    }
    
    // AI description toggle
    document.getElementById('autoGenerateDescription').addEventListener('change', function() {
        const descriptionField = document.getElementById('descriptionField');
        const minimalField = document.getElementById('minimalDescriptionField');
        
        if (this.checked) {
            descriptionField.style.display = 'none';
            minimalField.style.display = 'block';
            document.querySelector('[name="description"]').required = false;
            document.querySelector('[name="minimal_description"]').required = true;
        } else {
            descriptionField.style.display = 'block';
            minimalField.style.display = 'none';
            document.querySelector('[name="description"]').required = true;
            document.querySelector('[name="minimal_description"]').required = false;
        }
    });
}

function addFAQ() {
    const container = document.getElementById('faqContainer');
    const faqItem = document.createElement('div');
    faqItem.className = 'faq-item mb-3';
    faqItem.innerHTML = `
        <div class="row">
            <div class="col-md-5">
                <input type="text" class="form-control" name="faq_questions[]" placeholder="Question">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control" name="faq_answers[]" placeholder="Answer">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFAQ(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(faqItem);
}

function removeFAQ(button) {
    button.closest('.faq-item').remove();
}

function saveProduct() {
    const form = document.getElementById('addProductForm');
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show loading
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('addProductModal'));
        modal.hide();
        
        // Reset form
        form.reset();
        document.getElementById('descriptionField').style.display = 'block';
        document.getElementById('minimalDescriptionField').style.display = 'none';
        
        // Clear FAQ container except first item
        const faqContainer = document.getElementById('faqContainer');
        const faqItems = faqContainer.querySelectorAll('.faq-item');
        for (let i = 1; i < faqItems.length; i++) {
            faqItems[i].remove();
        }
        
        // Restore button
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show success message
        showNotification('Product added successfully!', 'success');
        
        // Reload table
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#productsTable')) {
            $('#productsTable').DataTable().ajax.reload();
        }
    }, 2000);
}

function viewProduct(id) {
    const modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    const content = document.getElementById('productDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
            <p class="mt-2">Loading product details...</p>
        </div>
    `;
    
    modal.show();
    
    // Simulate API call
    setTimeout(() => {
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Basic Information</h6>
                    <table class="table table-borderless">
                        <tr><td><strong>Name:</strong></td><td>WhatsApp Business Package Pro</td></tr>
                        <tr><td><strong>SKU:</strong></td><td>WBP-001</td></tr>
                        <tr><td><strong>Category:</strong></td><td>Communication</td></tr>
                        <tr><td><strong>Status:</strong></td><td><span class="badge bg-success">Active</span></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Pricing & Stock</h6>
                    <table class="table table-borderless">
                        <tr><td><strong>Retail Price:</strong></td><td>$299/month</td></tr>
                        <tr><td><strong>Wholesale Price:</strong></td><td>$249/month</td></tr>
                        <tr><td><strong>Max Discount:</strong></td><td>15%</td></tr>
                        <tr><td><strong>Stock:</strong></td><td>Unlimited</td></tr>
                    </table>
                </div>
            </div>
            <div class="mt-3">
                <h6>Description</h6>
                <p>Complete WhatsApp automation solution with AI integration for businesses</p>
            </div>
            <div class="mt-3">
                <h6>Tags</h6>
                <span class="badge bg-danger me-1">Hot Deal</span>
                <span class="badge bg-info">Featured</span>
            </div>
        `;
    }, 1000);
}

function editProduct(id) {
    showNotification('Edit product functionality coming soon...', 'info');
}

function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        showNotification('Product deleted successfully!', 'success');
        
        // Reload table
        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#productsTable')) {
            $('#productsTable').DataTable().ajax.reload();
        }
    }
}

function editProductFromView() {
    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewProductModal'));
    viewModal.hide();
    showNotification('Edit product functionality coming soon...', 'info');
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : type === 'info' ? 'alert-info' : 'alert-warning';
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show`;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.borderRadius = '8px';
    notification.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}
</script>
