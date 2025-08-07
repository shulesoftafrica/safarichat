@extends('layouts.app')
@section('content')
<style>
    .ai-sales-officer {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
        min-height: 100vh;
        padding: 2rem 0;
    }
    
    .page-header {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="1" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1.5" fill="white" opacity="0.1"/></svg>');
    }
    
    .page-header-content {
        position: relative;
        z-index: 1;
    }
    
    .page-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .ai-badge {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .page-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .main-layout {
        display: flex;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        min-height: 700px;
    }
    
    .sidebar {
        width: 280px;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        padding: 0;
    }
    
    .sidebar-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .nav-item {
        border-bottom: 1px solid #e2e8f0;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem 2rem;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        background: none;
        border: none;
        width: 100%;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
        cursor: pointer;
    }
    
    .nav-link:hover {
        background: #f1f5f9;
        color: #334155;
    }
    
    .nav-link.active {
        background: white;
        color: #6366f1;
        border-left-color: #6366f1;
        box-shadow: 2px 0 10px rgba(99, 102, 241, 0.1);
    }
    
    .nav-icon {
        font-size: 1.2rem;
        width: 20px;
        text-align: center;
    }
    
    .content-area {
        flex: 1;
        padding: 2rem;
        background: white;
        overflow-y: auto;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .loading {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 300px;
        color: #64748b;
        font-size: 1.1rem;
    }
    
    .loading i {
        margin-right: 0.5rem;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .main-layout {
            flex-direction: column;
        }
        
        .sidebar {
            width: 100%;
            border-right: none;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sidebar-nav {
            display: flex;
            overflow-x: auto;
        }
        
        .nav-item {
            border-bottom: none;
            border-right: 1px solid #e2e8f0;
            flex-shrink: 0;
        }
        
        .nav-item:last-child {
            border-right: none;
        }
        
        .content-area {
            padding: 1rem;
        }
        
        .page-title {
            font-size: 2rem;
        }
    }
</style>

<div class="ai-sales-officer">
    <div class="container-fluid">
        <div class="page-header">
            <div class="page-header-content">
                <div class="container-fluid">
                    <h1 class="page-title">
                        <i class="fas fa-robot"></i>
                        AI Sales Officer
                        <span class="ai-badge">
                            <i class="fas fa-brain"></i>
                            AI Powered
                        </span>
                    </h1>
                    <p class="page-subtitle">Configure your intelligent WhatsApp sales assistant for automated customer engagement</p>
                </div>
            </div>
        </div>

        <div class="main-layout">
            <div class="sidebar">
                <ul class="sidebar-nav">
                    <li class="nav-item">
                        <button class="nav-link active" data-tab="products">
                            <i class="fas fa-box nav-icon"></i>
                            <span>Products</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-tab="job-description">
                            <i class="fas fa-clipboard-list nav-icon"></i>
                            <span>Job Description</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="content-area">
                <div id="products" class="tab-content active">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        Loading products...
                    </div>
                </div>
                
                <div id="job-description" class="tab-content">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        Loading job description...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Load initial content
    loadTabContent('products');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Update nav active state
            navLinks.forEach(nav => nav.classList.remove('active'));
            this.classList.add('active');
            
            // Update content active state
            tabContents.forEach(content => content.classList.remove('active'));
            document.getElementById(targetTab).classList.add('active');
            
            // Load content if not already loaded
            loadTabContent(targetTab);
        });
    });
    
    function loadTabContent(tabName) {
        const contentDiv = document.getElementById(tabName);
        
        // Check if content is already loaded (has more than just loading div)
        if (contentDiv.children.length === 1 && contentDiv.querySelector('.loading')) {
            const url = `{{ route('service.tab-content') }}?tab=${tabName}`;
            
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    contentDiv.innerHTML = html;
                    
                    // Initialize tab-specific functions
                    if (tabName === 'products' && typeof initializeProducts === 'function') {
                        initializeProducts();
                    } else if (tabName === 'job-description' && typeof initializeJobDescription === 'function') {
                        initializeJobDescription();
                    }
                })
                .catch(error => {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error loading content. Please try again.
                        </div>
                    `;
                });
        }
    }
});
</script>

@endsection
