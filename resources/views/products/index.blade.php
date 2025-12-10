@extends('layouts.app')
@section('content')

<div class="products-management">
    <div class="container-fluid">
        <!-- Header -->
        <div class="reports-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="reports-title">
                        <i class="fas fa-box"></i>
                        Product Management
                        <span class="product-badge ms-3">
                            <i class="fas fa-shopping-cart me-1"></i>
                            Inventory
                        </span>
                    </h1>
                    <p class="reports-subtitle mb-0">
                        Manage your products, inventory, and product information for AI sales automation
                    </p>
                </div>
            </div>
        </div>

        <div class="main-layout">
            <!-- Main Content Area -->
            <div class="content-area p-3">
                @include('service.products')
            </div>
        </div>
    </div>
</div>

<style>
.products-management {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.reports-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.reports-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
}

.reports-title i {
    margin-right: 1rem;
    font-size: 2.5rem;
}

.product-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.reports-subtitle {
    margin-top: 0.5rem;
    opacity: 0.9;
    font-size: 1.1rem;
}

.content-area {
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    min-height: 500px;
}

/* Ensure the products page styles are consistent */
.products-page .page-header {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.products-page .page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
}

.products-page .btn-add-product {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 500;
}

.products-page .btn-add-product:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
}
</style>

@endsection