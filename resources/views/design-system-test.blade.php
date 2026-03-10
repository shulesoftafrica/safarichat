@extends('layouts.app')

@section('content')
<div class="page-content" style="padding: 2rem;">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="dashboard-header" style="margin-bottom: 2rem;">
            <div class="dashboard-header-content">
                <div class="greeting">
                    <h1>Design System Test Page</h1>
                    <p>Testing Phase 1 implementation of the new UI/UX design system</p>
                </div>
                <div class="quick-actions">
                    <button class="btn-primary">
                        <i class="fas fa-plus"></i>
                        Primary Button
                    </button>
                    <button class="btn-secondary">
                        <i class="fas fa-cog"></i>
                        Secondary Button
                    </button>
                </div>
            </div>
        </div>

        <!-- Color Palette Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Color Palette</h3>
            </div>
            <div class="card-body">
                <div class="row" style="gap: 1rem;">
                    <div class="col-md-2">
                        <div style="background: var(--primary-brand); height: 100px; border-radius: var(--radius-lg);"></div>
                        <p style="margin-top: 0.5rem; font-size: var(--text-xs);">Primary Brand</p>
                    </div>
                    <div class="col-md-2">
                        <div style="background: var(--gray-100); height: 100px; border-radius: var(--radius-lg);"></div>
                        <p style="margin-top: 0.5rem; font-size: var(--text-xs);">Gray 100</p>
                    </div>
                    <div class="col-md-2">
                        <div style="background: var(--success-bg); height: 100px; border-radius: var(--radius-lg); border: 1px solid var(--success-border);"></div>
                        <p style="margin-top: 0.5rem; font-size: var(--text-xs);">Success</p>
                    </div>
                    <div class="col-md-2">
                        <div style="background: var(--warning-bg); height: 100px; border-radius: var(--radius-lg); border: 1px solid var(--warning-border);"></div>
                        <p style="margin-top: 0.5rem; font-size: var(--text-xs);">Warning</p>
                    </div>
                    <div class="col-md-2">
                        <div style="background: var(--error-bg); height: 100px; border-radius: var(--radius-lg); border: 1px solid var(--error-border);"></div>
                        <p style="margin-top: 0.5rem; font-size: var(--text-xs);">Error</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buttons Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Buttons</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                    <button class="btn-primary">Primary Button</button>
                    <button class="btn-secondary">Secondary Button</button>
                    <button class="btn-ghost">Ghost Button</button>
                    <button class="btn-danger">Danger Button</button>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem;">
                    <button class="btn-primary btn-sm">Small</button>
                    <button class="btn-primary">Default</button>
                    <button class="btn-primary btn-lg">Large</button>
                    <button class="btn-primary btn-xl">Extra Large</button>
                </div>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button class="btn-primary" disabled>Disabled Primary</button>
                    <button class="btn-secondary" disabled>Disabled Secondary</button>
                </div>
            </div>
        </div>

        <!-- Badges Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Badges & Status Indicators</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <span class="badge-status badge-success badge-dot">Active</span>
                    <span class="badge-status badge-warning badge-dot">Pending</span>
                    <span class="badge-status badge-error badge-dot">Failed</span>
                    <span class="badge-status badge-info badge-dot">Processing</span>
                    <span class="badge-status badge-neutral">Neutral</span>
                </div>
            </div>
        </div>

        <!-- Alerts Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Inline Alerts</h3>
            </div>
            <div class="card-body">
                <div class="alert-inline alert-success">
                    <div class="alert-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Success!</strong>
                        <p>Your action was completed successfully.</p>
                    </div>
                </div>

                <div class="alert-inline alert-warning">
                    <div class="alert-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Warning</strong>
                        <p>Please review this important information before continuing.</p>
                    </div>
                </div>

                <div class="alert-inline alert-error">
                    <div class="alert-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Error</strong>
                        <p>Something went wrong. Please try again.</p>
                    </div>
                </div>

                <div class="alert-inline alert-info">
                    <div class="alert-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="alert-content">
                        <strong>Information</strong>
                        <p>Here's some helpful information for you.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forms Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Form Elements</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">
                                Full Name
                                <span class="form-required">*</span>
                            </label>
                            <input type="text" class="form-input" placeholder="Enter your full name">
                            <p class="form-hint">This will be displayed on your profile</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-input error" value="invalid-email">
                            <p class="form-error">
                                <i class="fas fa-exclamation-circle"></i>
                                Please enter a valid email address
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Country</label>
                            <select class="form-select">
                                <option>Select your country</option>
                                <option>Kenya</option>
                                <option>Tanzania</option>
                                <option>Uganda</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Message</label>
                            <textarea class="form-textarea" rows="5" placeholder="Enter your message"></textarea>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms">
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Subscribe to newsletter
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button class="btn-primary">Submit Form</button>
                    <button class="btn-ghost">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Table Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Data Table</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="table-standard">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="table-cell-primary">#001</td>
                            <td>
                                <div class="table-cell-primary">John Doe</div>
                                <div class="table-cell-secondary">Customer</div>
                            </td>
                            <td>
                                <span class="badge-status badge-success badge-dot">Active</span>
                            </td>
                            <td>john@example.com</td>
                            <td>Jan 15, 2024</td>
                            <td>
                                <button class="btn-ghost btn-sm">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-cell-primary">#002</td>
                            <td>
                                <div class="table-cell-primary">Jane Smith</div>
                                <div class="table-cell-secondary">Lead</div>
                            </td>
                            <td>
                                <span class="badge-status badge-warning badge-dot">Pending</span>
                            </td>
                            <td>jane@example.com</td>
                            <td>Jan 16, 2024</td>
                            <td>
                                <button class="btn-ghost btn-sm">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-cell-primary">#003</td>
                            <td>
                                <div class="table-cell-primary">Bob Johnson</div>
                                <div class="table-cell-secondary">Customer</div>
                            </td>
                            <td>
                                <span class="badge-status badge-error badge-dot">Inactive</span>
                            </td>
                            <td>bob@example.com</td>
                            <td>Jan 17, 2024</td>
                            <td>
                                <button class="btn-ghost btn-sm">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-cell-primary">#004</td>
                            <td>
                                <div class="table-cell-primary">Alice Williams</div>
                                <div class="table-cell-secondary">Lead</div>
                            </td>
                            <td>
                                <span class="badge-status badge-info badge-dot">Processing</span>
                            </td>
                            <td>alice@example.com</td>
                            <td>Jan 18, 2024</td>
                            <td>
                                <button class="btn-ghost btn-sm">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Metrics/Bento Grid Test -->
        <div class="bento-grid">
            <div class="bento-item">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-label">Total Leads</div>
                <div class="metric-value">2,543</div>
                <div class="metric-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>12% from last month</span>
                </div>
            </div>

            <div class="bento-item">
                <div class="metric-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="metric-label">Messages Sent</div>
                <div class="metric-value">15,847</div>
                <div class="metric-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>8% from last month</span>
                </div>
            </div>

            <div class="bento-item">
                <div class="metric-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-label">Conversion Rate</div>
                <div class="metric-value">34.2%</div>
                <div class="metric-trend negative">
                    <i class="fas fa-arrow-down"></i>
                    <span>2% from last month</span>
                </div>
            </div>

            <div class="bento-item">
                <div class="metric-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="metric-label">Revenue</div>
                <div class="metric-value">$48,392</div>
                <div class="metric-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>23% from last month</span>
                </div>
            </div>
        </div>

        <!-- Typography Test -->
        <div class="card-standard" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3>Typography Scale</h3>
            </div>
            <div class="card-body">
                <h1 style="font-size: var(--text-5xl); font-weight: var(--font-bold); margin-bottom: 1rem;">Heading 1 - 5XL</h1>
                <h2 style="font-size: var(--text-4xl); font-weight: var(--font-bold); margin-bottom: 1rem;">Heading 2 - 4XL</h2>
                <h3 style="font-size: var(--text-3xl); font-weight: var(--font-semibold); margin-bottom: 1rem;">Heading 3 - 3XL</h3>
                <h4 style="font-size: var(--text-2xl); font-weight: var(--font-semibold); margin-bottom: 1rem;">Heading 4 - 2XL</h4>
                <h5 style="font-size: var(--text-xl); font-weight: var(--font-medium); margin-bottom: 1rem;">Heading 5 - XL</h5>
                <p style="font-size: var(--text-base); color: var(--gray-700); margin-bottom: 1rem;">Body text - Base size with normal weight. This is the standard paragraph text used throughout the application.</p>
                <p style="font-size: var(--text-sm); color: var(--gray-600); margin-bottom: 1rem;">Small text - Used for secondary information and helper text.</p>
                <p style="font-size: var(--text-xs); color: var(--gray-500);">Extra small text - Used for labels, timestamps, and metadata.</p>
            </div>
        </div>

        <!-- Empty State Test -->
        <div class="empty-state">
            <div class="empty-state-illustration">
                <i class="fas fa-inbox"></i>
            </div>
            <h3 class="empty-state-title">No messages yet</h3>
            <p class="empty-state-description">
                When you start receiving messages from your leads, they will appear here. 
                Get started by creating your first campaign.
            </p>
            <button class="btn-primary">
                <i class="fas fa-plus"></i>
                Create Campaign
            </button>
        </div>

        <!-- Loading Spinner Test -->
        <div class="card-standard">
            <div class="card-header">
                <h3>Loading States</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 2rem; align-items: center;">
                    <div>
                        <span class="loading-spinner"></span>
                    </div>
                    <div>
                        <button class="btn-primary" disabled>
                            <span class="loading-spinner"></span>
                            Loading...
                        </button>
                    </div>
                    <div>
                        <button class="btn-secondary" disabled>
                            <span class="loading-spinner"></span>
                            Processing
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
