<div class="product-details-view">
    <div class="row">
        <!-- Product Image -->
        <div class="col-md-4">
            <div class="product-image-section">
                @if($product->hasImage())
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded shadow-sm product-detail-image">
                @else
                    <div class="product-placeholder-large text-center p-5 bg-light rounded">
                        <i class="fas fa-box fa-4x text-muted mb-3"></i>
                        <p class="text-muted">No image available</p>
                    </div>
                @endif
                
                @if($product->hasAttachment())
                    <div class="mt-3">
                        <a href="{{ $product->attachment_url }}" target="_blank" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fas fa-file-pdf me-2"></i>
                            Download Product Document
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Product Information -->
        <div class="col-md-8">
            <div class="product-info-section">
                <!-- Basic Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Basic Information
                        </h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-bold text-muted" style="width: 40%;">Name:</td>
                                <td>{{ $product->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">SKU:</td>
                                <td><code>{{ $product->sku }}</code></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Category:</td>
                                <td>
                                    <span class="badge bg-info">{{ $product->category }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Status:</td>
                                <td>
                                    <span class="badge bg-{{ $product->status === 'active' ? 'success' : ($product->status === 'inactive' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($product->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-dollar-sign me-2"></i>
                            Pricing & Stock
                        </h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td class="fw-bold text-muted" style="width: 50%;">Retail Price:</td>
                                <td class="fw-bold text-success">${{ number_format($product->retail_price, 2) }}/month</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Wholesale Price:</td>
                                <td class="fw-bold text-info">${{ number_format($product->wholesale_price, 2) }}/month</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Max Discount:</td>
                                <td><span class="badge bg-warning">{{ $product->max_discount }}%</span></td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-muted">Stock:</td>
                                <td>
                                    <span class="badge bg-{{ $product->stock_status_color }}">
                                        {{ $product->quantity ?? 'Unlimited' }}
                                    </span>
                                    <small class="text-{{ $product->stock_status_color }} ms-2">
                                        ({{ $product->stock_status_text }})
                                    </small>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <h6 class="text-dark mb-3">
                        <i class="fas fa-align-left me-2"></i>
                        Description
                    </h6>
                    <div class="description-content p-3 bg-light rounded">
                        <p class="mb-0">{{ $product->description }}</p>
                        @if($product->ai_generated_description)
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-robot me-1"></i>
                                This description was generated using AI
                            </small>
                        @endif
                    </div>
                </div>

                <!-- Tags -->
                @if($product->tags && count($product->tags) > 0)
                    <div class="mb-4">
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-tags me-2"></i>
                            Tags
                        </h6>
                        <div class="tags-container">
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
                                <span class="badge {{ $badgeClass }} me-2 mb-2">
                                    <i class="fas fa-tag me-1"></i>
                                    {{ ucfirst(str_replace('-', ' ', $tag)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- FAQs Section -->
    @if($product->faqs && $product->faqs->count() > 0)
        <div class="mt-4 pt-4 border-top">
            <h6 class="text-purple mb-3">
                <i class="fas fa-question-circle me-2"></i>
                Frequently Asked Questions
            </h6>
            <div class="accordion" id="faqAccordion{{ $product->id }}">
                @foreach($product->faqs as $index => $faq)
                    <div class="accordion-item mb-2">
                        <h2 class="accordion-header" id="heading{{ $product->id }}_{{ $index }}">
                            <button class="accordion-button {{ $index !== 0 ? 'collapsed' : '' }}" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse{{ $product->id }}_{{ $index }}" 
                                    aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" 
                                    aria-controls="collapse{{ $product->id }}_{{ $index }}">
                                <i class="fas fa-question me-2 text-primary"></i>
                                {{ $faq->question }}
                            </button>
                        </h2>
                        <div id="collapse{{ $product->id }}_{{ $index }}" 
                             class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                             aria-labelledby="heading{{ $product->id }}_{{ $index }}" 
                             data-bs-parent="#faqAccordion{{ $product->id }}">
                            <div class="accordion-body">
                                <i class="fas fa-answer me-2 text-success"></i>
                                {{ $faq->answer }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Product Metadata -->
    <div class="mt-4 pt-3 border-top">
        <div class="row text-muted small">
            <div class="col-md-6">
                <i class="fas fa-calendar-plus me-1"></i>
                <strong>Created:</strong> {{ $product->created_at->format('M d, Y \a\t h:i A') }}
            </div>
            <div class="col-md-6">
                <i class="fas fa-calendar-edit me-1"></i>
                <strong>Last Updated:</strong> {{ $product->updated_at->format('M d, Y \a\t h:i A') }}
            </div>
        </div>
    </div>
</div>

<style>
.product-detail-image {
    max-height: 300px;
    width: 100%;
    object-fit: cover;
}

.product-placeholder-large {
    height: 300px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.description-content {
    max-height: 150px;
    overflow-y: auto;
}

.tags-container {
    line-height: 2;
}

.text-purple {
    color: #6f42c1 !important;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f3ff;
    color: #0066cc;
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.table td {
    padding: 0.375rem 0.75rem;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .product-detail-image {
        max-height: 200px;
    }
    
    .product-placeholder-large {
        height: 200px;
    }
    
    .table td {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
}
</style>
