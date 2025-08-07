@extends('layouts.app')

@section('content')
<div class="support-container">
    <!-- Header -->
    <div class="support-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="support-title">
                        <i class="fas fa-life-ring"></i>
                        <h1>SafariChat Support Center</h1>
                        <p>Everything you need to master your WhatsApp business platform</p>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="user-context">
                        @if($has_whatsapp && $whatsapp_connected)
                            <span class="status-badge success">
                                <i class="fas fa-check-circle"></i> WhatsApp Connected
                            </span>
                        @elseif($has_whatsapp)
                            <span class="status-badge warning">
                                <i class="fas fa-exclamation-triangle"></i> Setup Required
                            </span>
                        @else
                            <span class="status-badge info">
                                <i class="fas fa-info-circle"></i> Getting Started
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Quick Start Guide -->
                <div class="help-section" id="getting-started">
                    <div class="section-header">
                        <h2><i class="fas fa-rocket"></i> Getting Started</h2>
                        <p>Follow these steps to set up your WhatsApp business platform</p>
                    </div>

                    <div class="quick-steps">
                        <div class="step-item {{ $has_whatsapp ? 'completed' : 'current' }}">
                            <div class="step-icon">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div class="step-content">
                                <h4>1. Connect WhatsApp</h4>
                                <p>Link your WhatsApp Business account to start sending messages</p>
                                @if(!$has_whatsapp)
                                    <a href="/auth/business/setup" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus"></i> Setup WhatsApp
                                    </a>
                                @elseif(!$whatsapp_connected)
                                    <a href="/auth/business/setup" class="btn btn-warning btn-sm">
                                        <i class="fas fa-link"></i> Complete Setup
                                    </a>
                                @else
                                    <span class="text-success"><i class="fas fa-check"></i> Connected</span>
                                @endif
                            </div>
                        </div>

                        <div class="step-item {{ $total_contacts > 0 ? 'completed' : ($has_whatsapp && $whatsapp_connected ? 'current' : 'pending') }}">
                            <div class="step-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="step-content">
                                <h4>2. Add Contacts</h4>
                                <p>Import or manually add customer contacts ({{ number_format($total_contacts) }} contacts)</p>
                                @if($total_contacts == 0)
                                    <a href="{{ url('/guest') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-user-plus"></i> Add Contacts
                                    </a>
                                @else
                                    <a href="{{ url('/guest') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye"></i> Manage Contacts
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="step-item {{ $has_sent_messages ? 'completed' : ($total_contacts > 0 ? 'current' : 'pending') }}">
                            <div class="step-icon">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                            <div class="step-content">
                                <h4>3. Send Messages</h4>
                                <p>Start engaging customers with WhatsApp messages ({{ number_format($messages_sent) }} sent)</p>
                                @if(!$has_sent_messages && $total_contacts > 0)
                                    <a href="{{ url('/message') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane"></i> Send First Message
                                    </a>
                                @elseif($has_sent_messages)
                                    <a href="{{ url('/message') }}" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-paper-plane"></i> Send More Messages
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div class="step-item {{ $has_sent_messages ? 'current' : 'pending' }}">
                            <div class="step-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="step-content">
                                <h4>4. Track Results</h4>
                                <p>Monitor your WhatsApp business performance and ROI</p>
                                @if($has_sent_messages)
                                    <a href="{{ url('/message/report') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-chart-bar"></i> View Analytics
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Core Features Guide -->
                <div class="help-section" id="features">
                    <div class="section-header">
                        <h2><i class="fas fa-star"></i> Key Features</h2>
                        <p>Master the essential tools of your WhatsApp business platform</p>
                    </div>

                    <div class="feature-grid">
                        <div class="feature-card">
                            <div class="feature-icon whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <h4>WhatsApp Messaging</h4>
                            <p>Send personalized messages, images, documents, and media to your customers instantly.</p>
                            <ul class="feature-tips">
                                <li>Support for text, images, documents, and videos</li>
                                <li>Bulk messaging to multiple contacts</li>
                                <li>Message templates and personalization</li>
                                <li>Real-time delivery status tracking</li>
                            </ul>
                            <a href="{{ url('/message') }}" class="btn btn-outline-success btn-sm">Start Messaging</a>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon contacts">
                                <i class="fas fa-address-book"></i>
                            </div>
                            <h4>Contact Management</h4>
                            <p>Organize and manage your customer database with advanced filtering and categorization.</p>
                            <ul class="feature-tips">
                                <li>Import contacts from CSV files & vcf files</li>
                                <li>Sync contacts from Google Contacts</li>
                                <li>Create custom contact categories</li>
                                <li>Track payment status and pledges</li>
                            </ul>
                            <a href="{{ url('/guest') }}" class="btn btn-outline-primary btn-sm">Manage Contacts</a>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon analytics">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4>Business Analytics</h4>
                            <p>Track your ROI, customer engagement, and message performance with detailed reports.</p>
                            <ul class="feature-tips">
                                <li>Real-time message delivery statistics</li>
                                <li>Customer response rate tracking</li>
                                <li>ROI and cost analysis</li>
                                <li>Exportable business reports</li>
                            </ul>
                            <a href="{{ url('/message/report') }}" class="btn btn-outline-info btn-sm">View Analytics</a>
                        </div>

                        <div class="feature-card">
                            <div class="feature-icon incoming">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <h4>Incoming Messages</h4>
                            <p>Monitor and respond to customer messages with intelligent auto-reply and conversation management.</p>
                            <ul class="feature-tips">
                                <li>Real-time incoming message notifications</li>
                                <li>Auto-reply for instant responses</li>
                                <li>Conversation history tracking</li>
                                <li>Customer contact creation from unknown numbers</li>
                            </ul>
                            <a href="{{ url('/whatsapp/incoming-messages') }}" class="btn btn-outline-warning btn-sm">View Messages</a>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="help-section" id="faq">
                    <div class="section-header">
                        <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
                        <p>Find answers to common questions about using SafariChat</p>
                    </div>

                    <div class="faq-container">
                        <!-- WhatsApp Setup FAQs -->
                        <div class="faq-category">
                            <h3><i class="fab fa-whatsapp"></i> WhatsApp Setup</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do I connect my WhatsApp to SafariChat?
                                </div>
                                <div class="faq-answer">
                                    <p>To connect your WhatsApp:</p>
                                    <ol>
                                        <li>Go to <strong>WhatsApp Setup</strong> from your dashboard</li>
                                        <li>Create a new WhatsApp instance with your phone number</li>
                                        <li>Scan the QR code or enter the pairing code on your phone</li>
                                        <li>Your WhatsApp will be connected and ready for messaging</li>
                                    </ol>
                                    <p><strong>Note:</strong> You need an active WhatsApp account on your phone number.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    What is a WhatsApp instance?
                                </div>
                                <div class="faq-answer">
                                    <p>A WhatsApp instance is your dedicated connection that allows SafariChat to send and receive messages through your WhatsApp account. Each phone number needs its own instance.</p>
                                    <p><strong>Key points:</strong></p>
                                    <ul>
                                        <li>One instance per phone number</li>
                                        <li>Maintains your WhatsApp connection</li>
                                        <li>Enables automated messaging</li>
                                        <li>Tracks message delivery and responses</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    Why is my WhatsApp not connecting?
                                </div>
                                <div class="faq-answer">
                                    <p>Common connection issues and solutions:</p>
                                    <ul>
                                        <li><strong>Phone not connected:</strong> Ensure your phone has internet and WhatsApp is running</li>
                                        <li><strong>Wrong pairing code:</strong> Generate a new pairing code and try again</li>
                                        <li><strong>Multiple devices:</strong> WhatsApp only allows limited linked devices</li>
                                        <li><strong>Account restrictions:</strong> Ensure your WhatsApp account is in good standing</li>
                                    </ul>
                                    <p>If issues persist, contact our support team.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Messaging FAQs -->
                        <div class="faq-category">
                            <h3><i class="fas fa-paper-plane"></i> Messaging</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How many messages can I send per day?
                                </div>
                                <div class="faq-answer">
                                    <p>Message limits depend on your account status and WhatsApp's policies:</p>
                                    <ul>
                                        <li><strong>New accounts:</strong> Start with ~250 messages per day</li>
                                        <li><strong>Established accounts:</strong> Up to 1,000+ messages per day</li>
                                        <li><strong>Business verified:</strong> Higher limits based on engagement</li>
                                    </ul>
                                    <p><strong>Tips to increase limits:</strong></p>
                                    <ul>
                                        <li>Maintain high engagement rates</li>
                                        <li>Avoid spam complaints</li>
                                        <li>Use opt-in messaging practices</li>
                                        <li>Verify your business with WhatsApp</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    Can I send images and documents?
                                </div>
                                <div class="faq-answer">
                                    <p>Yes! SafariChat supports all major WhatsApp media types:</p>
                                    <ul>
                                        <li><strong>Images:</strong> JPG, PNG up to 16MB</li>
                                        <li><strong>Documents:</strong> PDF, DOC, XLS up to 100MB</li>
                                        <li><strong>Videos:</strong> MP4, AVI up to 16MB</li>
                                        <li><strong>Audio:</strong> MP3, WAV up to 16MB</li>
                                    </ul>
                                    <p>You can upload media when composing messages and add captions for context.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do I personalize messages?
                                </div>
                                <div class="faq-answer">
                                    <p>Use these personalization variables in your messages:</p>
                                    <ul>
                                        <li><strong>{name}</strong> - Customer's name</li>
                                        <li><strong>{phone}</strong> - Phone number</li>
                                        <li><strong>{email}</strong> - Email address</li>
                                        <!-- <li><strong>{pledge}</strong> - Pledge amount</li> -->
                                        <li><strong>{category}</strong> - Contact category</li>
                                    </ul>
                                    <p><strong>Example:</strong> "Hello {name}, thank you for your business!"</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Management FAQs -->
                        <div class="faq-category">
                            <h3><i class="fas fa-users"></i> Contact Management</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do I import contacts from a CSV file?
                                </div>
                                <div class="faq-answer">
                                    <p>To import contacts from CSV:</p>
                                    <ol>
                                        <li>Go to <strong>Contact Management</strong></li>
                                        <li>Click <strong>"Import from CSV"</strong></li>
                                        <li>Download the CSV template</li>
                                        <li>Fill in your contact data (Name, Phone, Email, Category)</li>
                                        <li>Upload your completed CSV file</li>
                                        <li>Review and confirm the import</li>
                                    </ol>
                                    <p><strong>CSV Format:</strong> Name, Phone, Email, Pledge, Category</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    Can I sync contacts from Google Contacts?
                                </div>
                                <div class="faq-answer">
                                    <p>Yes! SafariChat integrates with Google Contacts:</p>
                                    <ol>
                                        <li>Go to Contact Management</li>
                                        <li>Click <strong>"Sync from Google"</strong></li>
                                        <li>Authorize SafariChat to access your Google Contacts</li>
                                        <li>Select which contacts to import</li>
                                        <li>Contacts will be automatically added to your system</li>
                                    </ol>
                                    <p>The sync preserves names, phone numbers, and email addresses.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do contact categories work?
                                </div>
                                <div class="faq-answer">
                                    <p>Categories help organize your contacts for targeted messaging:</p>
                                    <ul>
                                        <li><strong>Create categories</strong> like "VIP Customers", "Prospects", "Members"</li>
                                        <li><strong>Assign contacts</strong> to appropriate categories</li>
                                        <li><strong>Filter messaging</strong> by category for targeted campaigns</li>
                                        <li><strong>Track performance</strong> by category in analytics</li>
                                    </ul>
                                    <p>You can create unlimited categories and move contacts between them.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics & Reporting FAQs -->
                        <div class="faq-category">
                            <h3><i class="fas fa-chart-bar"></i> Analytics & Reporting</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How is ROI calculated?
                                </div>
                                <div class="faq-answer">
                                    <p>ROI (Return on Investment) calculation:</p>
                                    <ul>
                                        <li><strong>Messaging Cost:</strong> Number of messages × cost per message</li>
                                        <li><strong>Estimated Revenue:</strong> Based on customer responses and engagement</li>
                                        <li><strong>ROI Formula:</strong> (Revenue - Cost) / Cost × 100</li>
                                    </ul>
                                    <p><strong>Example:</strong> If you spend Tsh 50,000 on messages and generate Tsh 200,000 in responses, your ROI is 300%.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    What analytics can I track?
                                </div>
                                <div class="faq-answer">
                                    <p>SafariChat provides comprehensive analytics:</p>
                                    <ul>
                                        <li><strong>Message Metrics:</strong> Sent, delivered, read, failed messages</li>
                                        <li><strong>Engagement:</strong> Response rates, conversation counts</li>
                                        <li><strong>Financial:</strong> ROI, messaging costs, revenue estimates</li>
                                        <li><strong>Performance:</strong> Success rates, customer reach</li>
                                        <li><strong>Trends:</strong> Daily, weekly, monthly patterns</li>
                                    </ul>
                                    <p>All data can be exported as professional reports.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do I export reports?
                                </div>
                                <div class="faq-answer">
                                    <p>To export business reports:</p>
                                    <ol>
                                        <li>Go to <strong>Analytics Dashboard</strong></li>
                                        <li>Click <strong>"Export Report"</strong></li>
                                        <li>Choose your preferred date range</li>
                                        <li>Select report format (PDF, CSV, or Text)</li>
                                        <li>Download the generated report</li>
                                    </ol>
                                    <p>Reports include performance summaries, recommendations, and growth insights.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Billing & Pricing FAQs -->
                        <div class="faq-category">
                            <h3><i class="fas fa-credit-card"></i> Billing & Pricing</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How much does it cost to send WhatsApp messages?
                                </div>
                                <div class="faq-answer">
                                    <p>SafariChat WhatsApp messaging costs:</p>
                                    <ul>
                                        <li><strong>Text Messages:</strong> Tsh 50,000/= per month, unlimited message</li>
                                        <li><strong>Media Messages:</strong> Tsh 50,000/= per month,per message (images, documents)</li>
                                        <li><strong>Bulk Discounts:</strong> Not applicable</li>
                                        <li><strong>No Setup Fees:</strong> Free WhatsApp connection</li>
                                    </ul>
                                    <p>You only pay per month and we handle your workload.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    What payment methods do you accept?
                                </div>
                                <div class="faq-answer">
                                    <p>We accept multiple payment methods:</p>
                                    <ul>
                                        <li><strong>Mobile Money:</strong> M-Pesa, Tigo Pesa, Airtel Money</li>
                                        <li><strong>Bank Transfer:</strong> All major Tanzanian banks</li>
                                        <li><strong>Credit/Debit Cards:</strong> Visa, Mastercard</li>
                                        <li><strong>USSD:</strong> *150*00# for quick payments</li>
                                    </ul>
                                    <p>All payments are processed securely with instant activation.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    Do you offer refunds?
                                </div>
                                <div class="faq-answer">
                                    <p>Our refund policy:</p>
                                    <ul>
                                        <li><strong>Unused Credits:</strong> Full refund within 30 days</li>
                                        <li><strong>Technical Issues:</strong> Compensation for platform downtime</li>
                                        <li><strong>Failed Messages:</strong> Automatic credit for undelivered messages</li>
                                        <li><strong>Billing Errors:</strong> Immediate correction and refund</li>
                                    </ul>
                                    <p>Contact support for any billing concerns - we're here to help!</p>
                                </div>
                            </div>
                        </div>

                        <!-- Technical Support FAQs -->
                        <div class="faq-category">
                            <h3><i class="fas fa-cog"></i> Technical Support</h3>
                            
                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    What should I do if messages are not sending?
                                </div>
                                <div class="faq-answer">
                                    <p>Troubleshooting message delivery issues:</p>
                                    <ol>
                                        <li><strong>Check WhatsApp Connection:</strong> Ensure your instance is connected</li>
                                        <li><strong>Verify Phone Numbers:</strong> Use correct international format (+255...)</li>
                                        <li><strong>Account Balance:</strong> Ensure sufficient credits in your account</li>
                                        <li><strong>Message Content:</strong> Avoid spam-like content or excessive links</li>
                                        <li><strong>Rate Limits:</strong> Don't exceed daily message limits</li>
                                    </ol>
                                    <p>Check the message status in your dashboard for specific error details.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    How do I check message delivery status?
                                </div>
                                <div class="faq-answer">
                                    <p>Track message delivery in several ways:</p>
                                    <ul>
                                        <li><strong>Message History:</strong> View individual message status</li>
                                        <li><strong>Analytics Dashboard:</strong> Overall delivery statistics</li>
                                        <li><strong>Real-time Updates:</strong> Live status updates in your dashboard</li>
                                        <li><strong>Status Indicators:</strong> Sent, Delivered, Read, Failed</li>
                                    </ul>
                                    <p>You can also export delivery reports for detailed analysis.</p>
                                </div>
                            </div>

                            <div class="faq-item">
                                <div class="faq-question">
                                    <i class="fas fa-chevron-down"></i>
                                    Is my data secure?
                                </div>
                                <div class="faq-answer">
                                    <p>SafariChat takes data security seriously:</p>
                                    <ul>
                                        <li><strong>Encryption:</strong> All data encrypted in transit and at rest</li>
                                        <li><strong>Access Control:</strong> Strict user authentication and authorization</li>
                                        <li><strong>Data Privacy:</strong> Compliance with GDPR and local regulations</li>
                                        <li><strong>Backup:</strong> Regular automated backups with disaster recovery</li>
                                        <li><strong>Monitoring:</strong> 24/7 security monitoring and alerts</li>
                                    </ul>
                                    <p>Your customer data and messages are always protected.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-lightning-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions">
                        @if(!$has_whatsapp)
                            <a href="{{ url('/auth/business/setup') }}" class="quick-action whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                <span>Setup WhatsApp</span>
                            </a>
                        @endif

                        <a href="{{ url('/guest') }}" class="quick-action contacts">
                            <i class="fas fa-users"></i>
                            <span>Manage Contacts</span>
                        </a>

                        <a href="{{ url('/message') }}" class="quick-action messaging">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send Messages</span>
                        </a>

                        <a href="{{ url('/message/report') }}" class="quick-action analytics">
                            <i class="fas fa-chart-bar"></i>
                            <span>View Analytics</span>
                        </a>

                        <a href="{{ url('/whatsapp/incoming-messages') }}" class="quick-action inbox">
                            <i class="fas fa-inbox"></i>
                            <span>View Inbox</span>
                        </a>
                    </div>
                </div>

                <!-- Support Ticket -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-ticket-alt"></i> Need More Help?</h3>
                    <p>Can't find the answer you're looking for? Our support team is here to help!</p>

                    <form method="POST" action="{{ url('/support') }}" class="support-form">
                        @csrf
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="topic" class="form-control" placeholder="Brief description of your issue" required>
                        </div>
                        <div class="form-group">
                            <label>Details</label>
                            <textarea name="details" class="form-control" rows="4" placeholder="Please provide as much detail as possible..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-paper-plane"></i> Submit Ticket
                        </button>
                    </form>
                    
                    <div class="contact-info">
                        <h4>Other Ways to Reach Us</h4>
                        <!-- <p><i class="fas fa-phone"></i> <strong>Phone:</strong> +255 700 000 000</p> -->
                        <p><i class="fas fa-envelope"></i> <strong>Email:</strong> support@safarichat.africa</p>
                        <!-- <p><i class="fab fa-whatsapp"></i> <strong>WhatsApp:</strong> +255 700 000 000</p> -->
                        <p><i class="fas fa-clock"></i> <strong>Hours:</strong> 8AM - 6PM EAT</p>
                    </div>
                </div>

                <!-- System Status -->
                <div class="sidebar-card">
                    <h3><i class="fas fa-server"></i> Your Account Status</h3>
                    <div class="status-overview">
                        <div class="status-item">
                            <span class="status-label">WhatsApp Connection</span>
                            @if($whatsapp_connected)
                                <span class="status-value success">Connected</span>
                            @elseif($has_whatsapp)
                                <span class="status-value warning">Setup Required</span>
                            @else
                                <span class="status-value info">Not Setup</span>
                            @endif
                        </div>
                        <div class="status-item">
                            <span class="status-label">Total Contacts</span>
                            <span class="status-value">{{ number_format($total_contacts) }}</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Messages Sent</span>
                            <span class="status-value">{{ number_format($messages_sent) }}</span>
                        </div>
                        <div class="status-item">
                            <span class="status-label">Account Type</span>
                            <span class="status-value">Business</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.support-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e3f2fd 100%);
}

.support-header {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 0;
}

.support-title h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.support-title p {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.support-title i {
    font-size: 2rem;
    margin-right: 1rem;
    color: #60a5fa;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.status-badge.success {
    background: #dcfce7;
    color: #16a34a;
}

.status-badge.warning {
    background: #fef3c7;
    color: #d97706;
}

.status-badge.info {
    background: #dbeafe;
    color: #2563eb;
}

.help-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.section-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f1f5f9;
}

.section-header h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.section-header h2 i {
    color: #3b82f6;
    margin-right: 0.5rem;
}

.section-header p {
    color: #64748b;
    margin: 0;
    font-size: 1.05rem;
}

/* Quick Steps */
.quick-steps {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.step-item {
    display: flex;
    align-items: flex-start;
    padding: 1.5rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.step-item.completed {
    background: #f0fdf4;
    border-color: #22c55e;
}

.step-item.current {
    background: #fef3c7;
    border-color: #f59e0b;
}

.step-item.pending {
    background: #f8fafc;
    border-color: #e2e8f0;
}

.step-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1.5rem;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.step-item.completed .step-icon {
    background: #22c55e;
    color: white;
}

.step-item.current .step-icon {
    background: #f59e0b;
    color: white;
}

.step-item.pending .step-icon {
    background: #e2e8f0;
    color: #64748b;
}

.step-content h4 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.step-content p {
    color: #64748b;
    margin-bottom: 1rem;
}

/* Feature Grid */
.feature-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.feature-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.feature-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.feature-icon.whatsapp {
    background: #dcfce7;
    color: #16a34a;
}

.feature-icon.contacts {
    background: #dbeafe;
    color: #2563eb;
}

.feature-icon.analytics {
    background: #f3e8ff;
    color: #9333ea;
}

.feature-icon.incoming {
    background: #fef3c7;
    color: #d97706;
}

.feature-card h4 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.feature-card p {
    color: #64748b;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.feature-tips {
    list-style: none;
    padding: 0;
    margin-bottom: 1rem;
}

.feature-tips li {
    color: #64748b;
    margin-bottom: 0.5rem;
    padding-left: 1.5rem;
    position: relative;
}

.feature-tips li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #22c55e;
    font-weight: bold;
}

/* FAQ Styles */
.faq-container {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.faq-category h3 {
    font-size: 1.3rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #f1f5f9;
}

.faq-category h3 i {
    color: #3b82f6;
    margin-right: 0.5rem;
}

.faq-item {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    overflow: hidden;
}

.faq-question {
    padding: 1rem 1.5rem;
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.faq-question:hover {
    background: #e2e8f0;
}

.faq-question i {
    margin-right: 1rem;
    color: #64748b;
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 1.5rem;
    background: white;
    display: none;
    border-top: 1px solid #e2e8f0;
}

.faq-item.active .faq-answer {
    display: block;
}

.faq-answer p {
    margin-bottom: 1rem;
    line-height: 1.6;
    color: #374151;
}

.faq-answer ul, .faq-answer ol {
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.faq-answer li {
    margin-bottom: 0.5rem;
    color: #4b5563;
    line-height: 1.6;
}

.faq-answer strong {
    color: #1f2937;
}

/* Sidebar Styles */
.sidebar-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
}

.sidebar-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 1rem;
}

.sidebar-card h3 i {
    color: #3b82f6;
    margin-right: 0.5rem;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quick-action {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.quick-action:hover {
    text-decoration: none;
    transform: translateX(4px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.quick-action.whatsapp:hover {
    background: #dcfce7;
    border-color: #22c55e;
    color: #16a34a;
}

.quick-action.contacts:hover {
    background: #dbeafe;
    border-color: #3b82f6;
    color: #1d4ed8;
}

.quick-action.messaging:hover {
    background: #f3e8ff;
    border-color: #9333ea;
    color: #7c3aed;
}

.quick-action.analytics:hover {
    background: #fef3c7;
    border-color: #f59e0b;
    color: #d97706;
}

.quick-action.inbox:hover {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

.quick-action i {
    margin-right: 0.75rem;
    font-size: 1.1rem;
}

.support-form .form-group {
    margin-bottom: 1rem;
}

.support-form label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    display: block;
}

.support-form .form-control {
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.75rem;
    transition: border-color 0.3s ease;
}

.support-form .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.contact-info {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.contact-info h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
}

.contact-info p {
    margin-bottom: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.contact-info i {
    width: 20px;
    color: #9ca3af;
}

.status-overview {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.status-item:last-child {
    border-bottom: none;
}

.status-label {
    font-weight: 500;
    color: #6b7280;
    font-size: 0.9rem;
}

.status-value {
    font-weight: 600;
    color: #374151;
}

.status-value.success {
    color: #16a34a;
}

.status-value.warning {
    color: #d97706;
}

.status-value.info {
    color: #2563eb;
}

/* Responsive */
@media (max-width: 768px) {
    .support-title h1 {
        font-size: 2rem;
    }
    
    .feature-grid {
        grid-template-columns: 1fr;
    }
    
    .step-item {
        flex-direction: column;
        text-align: center;
    }
    
    .step-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

/* Success message styling */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
}

.alert-success {
    background: #f0fdf4;
    border-color: #22c55e;
    color: #15803d;
}

.alert-success strong {
    color: #166534;
}
</style>

<script>
// FAQ Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const faqItem = this.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close all other FAQ items
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Toggle current item
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    console.log('SafariChat Support Center initialized successfully');
});
</script>
@endsection
