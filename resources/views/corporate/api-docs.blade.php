@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap');
    
    :root {
        --primary: #1F7A8C;
        --secondary: #FFBB33;
        --accent: #E5F3F5;
        --dark: #1A365D;
        --light: #F8FAFC;
        --success: #10B981;
        --danger: #EF4444;
        --warning: #F59E0B;
        --info: #3B82F6;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
    }
    
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        background: var(--gray-50);
        line-height: 1.6;
        color: var(--gray-800);
    }

    .api-page {
        min-height: 100vh;
        background: var(--light);
    }

    /* Navigation */
    .api-nav {
        background: white;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .api-logo {
        font-weight: 800;
        font-size: 24px;
        color: var(--primary);
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 30px;
    }

    .nav-links a {
        color: var(--gray-600);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .nav-links a:hover {
        color: var(--primary);
    }

    .nav-cta {
        background: var(--primary);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .nav-cta:hover {
        background: var(--dark);
        transform: translateY(-1px);
    }

    /* Content Layout */
    .api-content {
        display: flex;
        max-width: 1400px;
        margin: 0 auto;
        gap: 40px;
        padding: 40px 20px;
    }

    /* Sidebar */
    .api-sidebar {
        width: 280px;
        background: white;
        border-radius: 16px;
        padding: 30px;
        height: fit-content;
        position: sticky;
        top: 120px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
    }

    .sidebar-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .sidebar-nav {
        list-style: none;
    }

    .sidebar-nav li {
        margin-bottom: 8px;
    }

    .sidebar-nav a {
        color: var(--gray-600);
        text-decoration: none;
        font-size: 14px;
        padding: 8px 12px;
        border-radius: 6px;
        display: block;
        transition: all 0.3s ease;
    }

    .sidebar-nav a:hover,
    .sidebar-nav a.active {
        background: var(--accent);
        color: var(--primary);
    }

    /* Main Content */
    .api-main {
        flex: 1;
        background: white;
        border-radius: 16px;
        padding: 40px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
    }

    .api-header {
        margin-bottom: 40px;
    }

    .api-title {
        font-size: 42px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 15px;
    }

    .api-subtitle {
        font-size: 18px;
        color: var(--gray-600);
        margin-bottom: 20px;
    }

    .api-version {
        display: inline-block;
        background: var(--success);
        color: white;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Sections */
    .api-section {
        margin-bottom: 50px;
        scroll-margin-top: 120px;
    }

    .section-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--accent);
    }

    .section-content {
        font-size: 16px;
        line-height: 1.7;
        color: var(--gray-700);
        margin-bottom: 20px;
    }

    /* Code Blocks */
    .code-block {
        background: var(--gray-900);
        color: var(--gray-100);
        padding: 24px;
        border-radius: 12px;
        font-family: 'JetBrains Mono', Consolas, 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.5;
        overflow-x: auto;
        margin: 20px 0;
        position: relative;
    }

    .code-title {
        background: var(--primary);
        color: white;
        padding: 8px 16px;
        border-radius: 6px 6px 0 0;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 0;
    }

    .code-title + .code-block {
        border-radius: 0 0 12px 12px;
        margin-top: 0;
    }

    /* Endpoint Cards */
    .endpoint-grid {
        display: grid;
        gap: 25px;
        margin: 30px 0;
    }

    .endpoint-card {
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        padding: 25px;
        transition: all 0.3s ease;
    }

    .endpoint-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 20px rgba(31, 122, 140, 0.1);
    }

    .endpoint-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }

    .method-badge {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .method-get { background: var(--success); color: white; }
    .method-post { background: var(--info); color: white; }
    .method-put { background: var(--warning); color: white; }
    .method-delete { background: var(--danger); color: white; }

    .endpoint-url {
        font-family: 'JetBrains Mono', monospace;
        font-size: 16px;
        color: var(--dark);
        font-weight: 600;
    }

    .endpoint-description {
        color: var(--gray-700);
        margin-bottom: 20px;
    }

    /* Parameters Table */
    .params-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 14px;
    }

    .params-table th,
    .params-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid var(--gray-200);
    }

    .params-table th {
        background: var(--gray-50);
        font-weight: 600;
        color: var(--dark);
    }

    .param-name {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 600;
        color: var(--primary);
    }

    .param-type {
        background: var(--gray-100);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: var(--gray-700);
    }

    .param-required {
        color: var(--danger);
        font-size: 12px;
        font-weight: 600;
    }

    /* Response Examples */
    .response-example {
        margin: 20px 0;
    }

    .response-header {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 10px;
    }

    /* Authentication Section */
    .auth-info {
        background: var(--accent);
        padding: 25px;
        border-radius: 12px;
        border-left: 4px solid var(--primary);
        margin: 20px 0;
    }

    .auth-title {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 10px;
    }

    /* Quick Start */
    .quick-start {
        background: var(--gray-50);
        padding: 30px;
        border-radius: 12px;
        margin: 30px 0;
    }

    .quick-start-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .quick-start-steps {
        list-style: none;
        counter-reset: step-counter;
    }

    .quick-start-steps li {
        counter-increment: step-counter;
        margin-bottom: 15px;
        padding-left: 40px;
        position: relative;
    }

    .quick-start-steps li::before {
        content: counter(step-counter);
        position: absolute;
        left: 0;
        top: 0;
        background: var(--primary);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }

    /* Footer */
    .api-footer {
        background: var(--gray-900);
        color: var(--gray-300);
        padding: 30px 0;
        margin-top: 60px;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .footer-links a {
        color: var(--gray-400);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: white;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .api-content {
            flex-direction: column;
        }

        .api-sidebar {
            width: 100%;
            position: static;
            margin-bottom: 30px;
        }
    }

    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 20px;
        }

        .nav-links {
            flex-wrap: wrap;
            justify-content: center;
        }

        .api-title {
            font-size: 32px;
        }

        .api-main {
            padding: 25px;
        }

        .endpoint-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .params-table {
            font-size: 12px;
        }
    }
</style>

<div class="api-page">
    <!-- Navigation -->
    <nav class="api-nav">
        <div class="nav-container">
            <a href="/" class="api-logo">SafariChat</a>
            <ul class="nav-links">
                <li><a href="/corporate">Corporate</a></li>
                <li><a href="/privacy">Privacy Policy</a></li>
                <li><a href="/security">Security</a></li>
                <li><a href="/terms-and-conditions">Terms</a></li>
            </ul>
            <a href="/login" class="nav-cta">Get Started</a>
        </div>
    </nav>

    <!-- Content -->
    <div class="api-content">
        <!-- Sidebar -->
        <div class="api-sidebar">
            <h3 class="sidebar-title">API Documentation</h3>
            <ul class="sidebar-nav">
                <li><a href="#overview" class="active">Overview</a></li>
                <li><a href="#authentication">Authentication</a></li>
                <li><a href="#quick-start">Quick Start</a></li>
                <li><a href="#endpoints">Endpoints</a></li>
                <li><a href="#messaging">Messaging API</a></li>
                <li><a href="#contacts">Contacts API</a></li>
                <li><a href="#analytics">Analytics API</a></li>
                <li><a href="#webhooks">Webhooks</a></li>
                <li><a href="#errors">Error Handling</a></li>
                <li><a href="#rate-limits">Rate Limits</a></li>
                <li><a href="#sdks">SDKs & Libraries</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="api-main">
            <div class="api-header">
                <h1 class="api-title">API Documentation</h1>
                <p class="api-subtitle">
                    Integrate SafariChat's AI-powered messaging capabilities into your applications with our comprehensive REST API.
                </p>
                <span class="api-version">v2.1</span>
            </div>

            <!-- Overview -->
            <section id="overview" class="api-section">
                <h2 class="section-title">Overview</h2>
                <p class="section-content">
                    The SafariChat API provides programmatic access to our AI sales automation platform. You can send messages, manage contacts, analyze conversations, and integrate with your existing systems. Our API is RESTful, uses JSON for data exchange, and requires API key authentication.
                </p>
                <p class="section-content">
                    <strong>Base URL:</strong> <code>https://api.safarichat.ai/v2</code>
                </p>
            </section>

            <!-- Authentication -->
            <section id="authentication" class="api-section">
                <h2 class="section-title">Authentication</h2>
                <p class="section-content">
                    SafariChat API uses API keys for authentication. You can generate and manage your API keys from your dashboard.
                </p>
                
                <div class="auth-info">
                    <div class="auth-title">🔑 Authentication Header</div>
                    <p>Include your API key in the Authorization header of every request:</p>
                </div>

                <div class="code-title">Request Header</div>
                <div class="code-block">Authorization: Bearer YOUR_API_KEY
Content-Type: application/json</div>

                <div class="quick-start">
                    <h3 class="quick-start-title">Getting Your API Key</h3>
                    <ol class="quick-start-steps">
                        <li>Log in to your SafariChat dashboard</li>
                        <li>Navigate to Settings → API Keys</li>
                        <li>Click "Generate New API Key"</li>
                        <li>Copy and securely store your API key</li>
                        <li>Use the key in your Authorization header</li>
                    </ol>
                </div>
            </section>

            <!-- Quick Start -->
            <section id="quick-start" class="api-section">
                <h2 class="section-title">Quick Start</h2>
                <p class="section-content">
                    Get started with the SafariChat API in minutes. Here's a simple example to send your first AI-powered message:
                </p>

                <div class="code-title">Example: Send a Message</div>
                <div class="code-block">curl -X POST "https://api.safarichat.ai/v2/messages/send" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "+255123456789",
    "message": "Hello! This is an AI-powered message from SafariChat.",
    "ai_enabled": true
  }'</div>

                <div class="response-example">
                    <div class="response-header">Response:</div>
                    <div class="code-block">{
  "success": true,
  "message_id": "msg_abc123def456",
  "status": "sent",
  "ai_processed": true,
  "timestamp": "2024-12-20T10:30:00Z"
}</div>
                </div>
            </section>

            <!-- Endpoints -->
            <section id="endpoints" class="api-section">
                <h2 class="section-title">Core Endpoints</h2>
                <p class="section-content">
                    Here are the main API endpoints available in SafariChat API v2.1:
                </p>

                <div class="endpoint-grid">
                    <!-- Send Message -->
                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <span class="method-badge method-post">POST</span>
                            <span class="endpoint-url">/messages/send</span>
                        </div>
                        <p class="endpoint-description">Send an AI-powered message to a contact</p>
                        
                        <table class="params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">to</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><span class="param-required">Required</span></td>
                                    <td>Recipient phone number (E.164 format)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">message</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><span class="param-required">Required</span></td>
                                    <td>Message content</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">ai_enabled</span></td>
                                    <td><span class="param-type">boolean</span></td>
                                    <td>Optional</td>
                                    <td>Enable AI processing (default: true)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Get Messages -->
                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <span class="method-badge method-get">GET</span>
                            <span class="endpoint-url">/messages</span>
                        </div>
                        <p class="endpoint-description">Retrieve message history and conversation data</p>
                        
                        <table class="params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">contact</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td>Optional</td>
                                    <td>Filter by contact phone number</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">limit</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td>Optional</td>
                                    <td>Number of messages (max: 100)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">offset</span></td>
                                    <td><span class="param-type">integer</span></td>
                                    <td>Optional</td>
                                    <td>Pagination offset</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Create Contact -->
                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <span class="method-badge method-post">POST</span>
                            <span class="endpoint-url">/contacts</span>
                        </div>
                        <p class="endpoint-description">Add a new contact to your address book</p>
                        
                        <table class="params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">phone</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td><span class="param-required">Required</span></td>
                                    <td>Contact phone number</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">name</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td>Optional</td>
                                    <td>Contact full name</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">tags</span></td>
                                    <td><span class="param-type">array</span></td>
                                    <td>Optional</td>
                                    <td>Contact tags for organization</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Analytics -->
                    <div class="endpoint-card">
                        <div class="endpoint-header">
                            <span class="method-badge method-get">GET</span>
                            <span class="endpoint-url">/analytics/summary</span>
                        </div>
                        <p class="endpoint-description">Get conversation analytics and performance metrics</p>
                        
                        <table class="params-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Type</th>
                                    <th>Required</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="param-name">period</span></td>
                                    <td><span class="param-type">string</span></td>
                                    <td>Optional</td>
                                    <td>Time period (day, week, month)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">start_date</span></td>
                                    <td><span class="param-type">date</span></td>
                                    <td>Optional</td>
                                    <td>Start date (ISO 8601 format)</td>
                                </tr>
                                <tr>
                                    <td><span class="param-name">end_date</span></td>
                                    <td><span class="param-type">date</span></td>
                                    <td>Optional</td>
                                    <td>End date (ISO 8601 format)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Webhooks -->
            <section id="webhooks" class="api-section">
                <h2 class="section-title">Webhooks</h2>
                <p class="section-content">
                    Webhooks allow you to receive real-time notifications about events in your SafariChat account. Configure webhook endpoints to receive instant updates about message deliveries, AI responses, and conversation events.
                </p>

                <div class="code-title">Webhook Configuration</div>
                <div class="code-block">curl -X POST "https://api.safarichat.ai/v2/webhooks" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://safarichat.ai/webhook/safarichat",
    "events": ["message.received", "message.sent", "ai.response"],
    "active": true
  }'</div>

                <p class="section-content"><strong>Available Events:</strong></p>
                <ul class="section-list">
                    <li><code>message.received</code> - New message received from contact</li>
                    <li><code>message.sent</code> - Message successfully sent</li>
                    <li><code>ai.response</code> - AI has generated a response</li>
                    <li><code>contact.created</code> - New contact added</li>
                    <li><code>conversation.started</code> - New conversation initiated</li>
                </ul>
            </section>

            <!-- Error Handling -->
            <section id="errors" class="api-section">
                <h2 class="section-title">Error Handling</h2>
                <p class="section-content">
                    The SafariChat API uses conventional HTTP response codes to indicate success or failure. Error responses include detailed error messages to help you debug issues.
                </p>

                <table class="params-table">
                    <thead>
                        <tr>
                            <th>HTTP Code</th>
                            <th>Error Type</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>200</strong></td>
                            <td>Success</td>
                            <td>Request completed successfully</td>
                        </tr>
                        <tr>
                            <td><strong>400</strong></td>
                            <td>Bad Request</td>
                            <td>Invalid request parameters</td>
                        </tr>
                        <tr>
                            <td><strong>401</strong></td>
                            <td>Unauthorized</td>
                            <td>Invalid or missing API key</td>
                        </tr>
                        <tr>
                            <td><strong>429</strong></td>
                            <td>Rate Limited</td>
                            <td>Too many requests</td>
                        </tr>
                        <tr>
                            <td><strong>500</strong></td>
                            <td>Server Error</td>
                            <td>Internal server error</td>
                        </tr>
                    </tbody>
                </table>

                <div class="response-example">
                    <div class="response-header">Error Response Format:</div>
                    <div class="code-block">{
  "success": false,
  "error": {
    "code": "INVALID_PHONE",
    "message": "Phone number format is invalid",
    "details": "Phone number must be in E.164 format (+255xxxxxxxxx)"
  }
}</div>
                </div>
            </section>

            <!-- Rate Limits -->
            <section id="rate-limits" class="api-section">
                <h2 class="section-title">Rate Limits</h2>
                <p class="section-content">
                    To ensure fair usage and optimal performance, the SafariChat API implements rate limiting:
                </p>

                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Rate Limit</th>
                            <th>Burst Limit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Starter</strong></td>
                            <td>100 requests/minute</td>
                            <td>200 requests/minute</td>
                        </tr>
                        <tr>
                            <td><strong>Professional</strong></td>
                            <td>500 requests/minute</td>
                            <td>1000 requests/minute</td>
                        </tr>
                        <tr>
                            <td><strong>Enterprise</strong></td>
                            <td>2000 requests/minute</td>
                            <td>5000 requests/minute</td>
                        </tr>
                    </tbody>
                </table>

                <p class="section-content">
                    Rate limit headers are included in every response:
                </p>

                <div class="code-block">X-RateLimit-Limit: 500
X-RateLimit-Remaining: 487
X-RateLimit-Reset: 1609459200</div>
            </section>

            <!-- SDKs -->
            <section id="sdks" class="api-section">
                <h2 class="section-title">SDKs & Libraries</h2>
                <p class="section-content">
                    We provide official SDKs and libraries to make integration easier:
                </p>

                <div class="endpoint-grid">
                    <div class="endpoint-card">
                        <h4>🐘 PHP SDK</h4>
                        <p>Official PHP library for Laravel and other PHP frameworks</p>
                        <div class="code-block">composer require safarichat/php-sdk</div>
                    </div>

                    <div class="endpoint-card">
                        <h4>🟢 Node.js SDK</h4>
                        <p>JavaScript/TypeScript SDK for Node.js applications</p>
                        <div class="code-block">npm install safarichat-node</div>
                    </div>

                    <div class="endpoint-card">
                        <h4>🐍 Python SDK</h4>
                        <p>Python library for Django and Flask applications</p>
                        <div class="code-block">pip install safarichat</div>
                    </div>

                    <div class="endpoint-card">
                        <h4>☕ Java SDK</h4>
                        <p>Java library for Spring Boot and other frameworks</p>
                        <div class="code-block">implementation 'com.safarichat:java-sdk:2.1.0'</div>
                    </div>
                </div>

                <div class="auth-info">
                    <div class="auth-title">💡 Need Help?</div>
                    <p>Check out our <a href="https://github.com/safarichat" style="color: var(--primary);">GitHub repositories</a> for code examples, tutorials, and community support.</p>
                </div>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer class="api-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms-and-conditions">Terms of Service</a>
                <a href="/security">Security</a>
                <a href="/api">API Docs</a>
                <a href="/corporate">Corporate</a>
            </div>
            <p>&copy; {{date('Y')}} SafariChat. All rights reserved. Building the future of AI sales automation.</p>
        </div>
    </footer>
</div>

@endsection