<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafariChat Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; }
        
        /* Header */
        .header { background: #007cba; color: white; padding: 15px 20px; display: flex; justify-content: space-between; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
        .header h1 { font-size: 24px; }
        .header a { color: white; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,0.1); border-radius: 4px; transition: background 0.3s; }
        .header a:hover { background: rgba(255,255,255,0.2); }
        
        /* Layout */
        .admin-layout { display: flex; min-height: 100vh; margin-top: 60px; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: white; box-shadow: 2px 0 5px rgba(0,0,0,0.1); position: fixed; left: 0; top: 60px; bottom: 0; overflow-y: auto; }
        .sidebar-nav { padding: 0; list-style: none; }
        .sidebar-nav li { border-bottom: 1px solid #eee; }
        .sidebar-nav a { 
            display: flex; 
            align-items: center; 
            padding: 20px 25px; 
            color: #495057; 
            text-decoration: none; 
            transition: all 0.3s; 
            font-weight: 500;
            font-size: 16px;
        }
        .sidebar-nav a:hover { background: #f8f9fa; color: #007cba; }
        .sidebar-nav a.active { background: #007cba; color: white; border-right: 4px solid #005580; }
        .sidebar-nav .icon { margin-right: 12px; font-size: 20px; width: 24px; }
        
        /* Main Content */
        .main-content { flex: 1; margin-left: 280px; padding: 30px; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        
        /* Cards and other styles remain the same */
        .card { background: white; padding: 25px; margin: 15px 0; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .stat-card { text-align: center; padding: 25px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 10px; }
        .stat-number { font-size: 2.2em; font-weight: bold; color: #007cba; margin-bottom: 8px; }
        .stat-label { color: #6c757d; font-size: 14px; font-weight: 500; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: 600; color: #495057; }
        tr:hover { background: #f8f9fa; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #495057; }
        input { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 6px; font-size: 16px; transition: border-color 0.3s; }
        input:focus { outline: none; border-color: #007cba; box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1); }
        .btn { display: inline-block; padding: 12px 24px; background: #007cba; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 500; transition: background 0.3s; text-decoration: none; }
        .btn:hover { background: #005580; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 6px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .error-row { background: #fff3cd !important; }
        .section-title { font-size: 20px; font-weight: 600; margin-bottom: 15px; color: #343a40; border-bottom: 2px solid #007cba; padding-bottom: 8px; }
        .health-status { display: flex; align-items: center; gap: 10px; }
        .status-indicator { width: 12px; height: 12px; border-radius: 50%; }
        .status-good { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🦁 SafariChat Admin Dashboard</h1>
        <a href="/admin/logout">Logout</a>
    </div>
    
    <!-- Admin Layout -->
    <div class="admin-layout">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="#" class="nav-link active" data-section="overview"><span class="icon">📊</span>System Overview</a></li>
                <li><a href="#" class="nav-link" data-section="pricing"><span class="icon">💰</span>Pricing Management</a></li>
                <li><a href="#" class="nav-link" data-section="health"><span class="icon">🏥</span>System Health</a></li>
                <li><a href="#" class="nav-link" data-section="billing-sync"><span class="icon">🔄</span>Billing Sync</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            @if(session('success'))
                <div class="success">✅ {{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="error">❌ {{ session('error') }}</div>
            @endif
        
        <!-- Overview Section -->
        <div id="overview" class="content-section active">
            <div class="card">
                <h2 class="section-title">Customer Analytics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['total_customers']) }}</div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['trial_customers']) }}</div>
                        <div class="stat-label">Trial Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['paid_customers']) }}</div>
                        <div class="stat-label">Paid Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['churned_customers']) }}</div>
                        <div class="stat-label">Churned Customers</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3 class="section-title">Revenue Metrics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">TZS {{ number_format($stats['total_collections']) }}</div>
                        <div class="stat-label">Total Collections</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['new_customers_30d']) }}</div>
                        <div class="stat-label">New Customers (30d)</div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h3 class="section-title">AI Usage Metrics</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['total_conversations']) }}</div>
                        <div class="stat-label">Total Conversations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['conversations_today']) }}</div>
                        <div class="stat-label">Conversations Today</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['total_input_tokens']) }}</div>
                        <div class="stat-label">Input Tokens Used</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($stats['total_output_tokens']) }}</div>
                        <div class="stat-label">Output Tokens Generated</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pricing Management Section -->
        <div id="pricing" class="content-section">
            <div class="card">
                <h2 class="section-title">💰 Billing API Pricing Management</h2>
                
                <div style="background: #e3f2fd; margin-bottom: 20px; padding: 15px; border-radius: 6px; border-left: 4px solid #2196F3;">
                    <strong>🔗 Billing API Integration:</strong> All pricing changes sync with the billing API and apply immediately to all customers. Current pricing is loaded from the billing system.
                </div>
                
                <form action="/admin/update-pricing" method="POST" id="pricing-form">
                    @csrf
                    
                    <h3 style="margin-bottom: 20px;">📱 Message Pricing</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>💬 Price Per Message (TZS)</label>
                            <input type="number" name="price_per_message" value="{{ $currentPricing['price_per_message'] ?? 100 }}" step="0.01" required>
                            <small style="color: #666;">Cost per AI message sent via billing API</small>
                        </div>
                        <div class="form-group">
                            <label>🆓 Free Messages Limit</label>
                            <input type="number" name="free_messages_limit" value="{{ $currentPricing['free_messages_limit'] ?? 100 }}" required>
                            <small style="color: #666;">Free messages for trial users</small>
                        </div>
                    </div>
                    
                    <h3 style="margin: 30px 0 20px 0;">💳 Subscription Plans (Billing API Sync)</h3>
                    <div class="form-grid">
                          <div class="form-group">
                            <label>📅 Free Trial (TZS)</label>
                            <input type="number" name="free_trial_price" value="{{ $currentPricing['free_trial_price'] ?? 15000 }}" step="0.01" required>
                            <small style="color: #666;">Used for legacy calculations</small>
                        </div>
                        <div class="form-group">
                            <label>🌱 Starter Plan (TZS/month)</label>
                            <input type="number" name="starter_price" value="{{ $currentPricing['starter_price'] ?? 15000 }}" step="0.01" required>
                            <small style="color: #666;">1,000 messages, 200 contacts</small>
                        </div>
                        <div class="form-group">
                            <label>🚀 Pro Plan (TZS/month)</label>
                            <input type="number" name="pro_price" value="{{ $currentPricing['pro_price'] ?? 45000 }}" step="0.01" required>
                            <small style="color: #666;">5,000 messages, 1,000 contacts</small>
                        </div>
                        <div class="form-group">
                            <label>👑 Premium Plan (TZS/month)</label>
                            <input type="number" name="premium_price" value="{{ $currentPricing['premium_price'] ?? 85000 }}" step="0.01" required>
                            <small style="color: #666;">Unlimited everything</small>
                        </div>
                      
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn" style="background: #28a745;">
                            🔄 Update Billing API & Apply Changes
                        </button>
                        <button type="button" class="btn" style="background: #17a2b8; margin-left: 10px;" onclick="syncFromBillingAPI()">
                            📥 Sync From Billing API
                        </button>
                        <span style="margin-left: 15px; color: #666; font-size: 14px;">
                            ⚠️ Changes sync with billing API and apply immediately
                        </span>
                    </div>
                </form>
                
                <h3 class="section-title" style="margin-top: 50px;">📊 Live Billing API Plan Comparison</h3>
                <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                    <strong>⚡ Live Data:</strong> Pricing shown below is fetched from billing API in real-time
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Messages/Month</th>
                            <th>Contacts</th>
                            <th>Products</th>
                            <th>WhatsApp Channels</th>
                            <th>Current API Price (TZS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>🆓 Trial</strong></td><td>{{ $currentPricing['free_messages_limit'] ?? 100 }}</td><td>50</td><td>5</td><td>1</td><td>0</td></tr>
                        <tr><td><strong>🌱 Starter</strong></td><td>1,000</td><td>200</td><td>25</td><td>2</td><td>{{ number_format($currentPricing['starter_price'] ?? 15000) }}</td></tr>
                        <tr><td><strong>🚀 Pro</strong></td><td>5,000</td><td>1,000</td><td>100</td><td>5</td><td>{{ number_format($currentPricing['pro_price'] ?? 45000) }}</td></tr>
                        <tr><td><strong>👑 Premium</strong></td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td><td>Unlimited</td><td>{{ number_format($currentPricing['premium_price'] ?? 85000) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- System Health Section -->
        <div id="health" class="content-section">
            <div class="card">
                <h2 class="section-title">🏥 System Health Monitoring</h2>
                
                <h3>Database Health</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">{{ $health['database_size'] ?? 'Unknown' }}</div>
                        <div class="stat-label">Database Size</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($health['total_tables'] ?? 0) }}</div>
                        <div class="stat-label">Total Tables</div>
                    </div>
                </div>
                
                @if(!empty($health['total_records']))
                <h3 style="margin-top: 30px;">Table Record Counts</h3>
                <div class="stats-grid">
                    @foreach($health['total_records'] as $table => $count)
                    <div class="stat-card">
                        <div class="stat-number">{{ number_format($count) }}</div>
                        <div class="stat-label">{{ ucfirst(str_replace('_', ' ', $table)) }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
                
                <h3 style="margin-top: 30px;">System Performance</h3>
                <table>
                    <tr>
                        <td><strong>PHP Memory Usage</strong></td>
                        <td>{{ $health['php_memory_usage'] ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td><strong>PHP Memory Limit</strong></td>
                        <td>{{ $health['php_memory_limit'] ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Laravel Version</strong></td>
                        <td>{{ $health['laravel_version'] ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Failed Jobs</strong></td>
                        <td>{{ number_format($health['failed_jobs'] ?? 0) }}</td>
                    </tr>
                </table>
                
                @if(isset($health['billing_status']))
                <h3 style="margin-top: 30px;">Billing System Status</h3>
                <table>
                    <tr>
                        <td><strong>Cache Working</strong></td>
                        <td>
                            <div class="health-status">
                                <div class="status-indicator {{ $health['billing_status']['cache_working'] ? 'status-good' : 'status-error' }}"></div>
                                {{ $health['billing_status']['cache_working'] ? 'Yes' : 'No' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Last Sync</strong></td>
                        <td>{{ $health['billing_status']['last_sync'] ?? 'Unknown' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Pending Syncs</strong></td>
                        <td>{{ $health['billing_status']['pending_syncs'] ?? 0 }}</td>
                    </tr>
                </table>
                @endif
                
                @if(!empty($health['recent_errors']))
                <h3 style="margin-top: 30px;">Recent Errors (Last 10)</h3>
                <table>
                    @foreach($health['recent_errors'] as $error)
                    <tr class="error-row">
                        <td style="font-family: monospace; font-size: 12px;">{{ $error }}</td>
                    </tr>
                    @endforeach
                </table>
                @endif
                
                <div style="margin-top: 30px;">
                    <form action="/admin/clear-cache" method="POST" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Clear all system caches? This may temporarily slow down the application.')">
                            🧹 Clear All Caches
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Billing Sync Section -->
        <div id="billing-sync" class="content-section">
            <div class="card">
                <h2 class="section-title">🔄 Billing API Sync Management</h2>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 25px;">
                    <strong>🔗 Billing API Status:</strong> Monitor and manage synchronization with the billing API system.
                </div>
                
                <div class="stats-grid" style="margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-number">{{ $health['billing_status']['cache_working'] ? '✅' : '❌' }}</div>
                        <div class="stat-label">API Connection</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ $health['billing_status']['pending_syncs'] ?? 0 }}</div>
                        <div class="stat-label">Pending Syncs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">{{ now()->format('H:i:s') }}</div>
                        <div class="stat-label">Last Check</div>
                    </div>
                </div>
                
                <h3>Billing API Actions</h3>
                <div style="display: flex; gap: 15px; margin: 20px 0;">
                    <button type="button" class="btn" onclick="testBillingAPI()">
                        🔍 Test API Connection
                    </button>
                    <button type="button" class="btn" style="background: #17a2b8;" onclick="syncAllCustomers()">
                        🔄 Sync All Customers
                    </button>
                    <button type="button" class="btn" style="background: #ffc107; color: #000;" onclick="refreshBillingCache()">
                        ♻️ Refresh Billing Cache
                    </button>
                </div>
                
                <div id="billing-sync-results" style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; display: none;">
                    <h4>Sync Results:</h4>
                    <pre id="sync-output" style="background: #fff; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 12px;"></pre>
                </div>
            </div>
        </div>
        
        </div>
    </div>
    
    <script>
        // Sidebar navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links and sections
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');
                
                // Show corresponding section
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
            });
        });
        
        // Billing API integration functions
        async function syncFromBillingAPI() {
            try {
                const response = await fetch('/admin/sync-from-billing-api');
                const data = await response.json();
                
                if (data.success) {
                    location.reload(); // Reload to show updated pricing
                } else {
                    alert('Sync failed: ' + data.message);
                }
            } catch (error) {
                alert('Error syncing with billing API: ' + error.message);
            }
        }
        
        async function testBillingAPI() {
            const resultsDiv = document.getElementById('billing-sync-results');
            const outputPre = document.getElementById('sync-output');
            
            resultsDiv.style.display = 'block';
            outputPre.textContent = 'Testing billing API connection...';
            
            try {
                const response = await fetch('/admin/test-billing-api');
                const data = await response.json();
                
                outputPre.textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                outputPre.textContent = 'Error: ' + error.message;
            }
        }
        
        async function syncAllCustomers() {
            const resultsDiv = document.getElementById('billing-sync-results');
            const outputPre = document.getElementById('sync-output');
            
            resultsDiv.style.display = 'block';
            outputPre.textContent = 'Syncing all customers with billing API...';
            
            try {
                const response = await fetch('/admin/sync-all-customers', { method: 'POST' });
                const data = await response.json();
                
                outputPre.textContent = JSON.stringify(data, null, 2);
            } catch (error) {
                outputPre.textContent = 'Error: ' + error.message;
            }
        }
        
        async function refreshBillingCache() {
            try {
                const response = await fetch('/admin/refresh-billing-cache', { method: 'POST' });
                const data = await response.json();
                
                if (data.success) {
                    alert('Billing cache refreshed successfully!');
                    location.reload();
                } else {
                    alert('Cache refresh failed: ' + data.message);
                }
            } catch (error) {
                alert('Error refreshing cache: ' + error.message);
            }
        }
    </script>
</body>
</html>