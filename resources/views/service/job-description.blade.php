@extends('layouts.app')
@section('content')
<div class="ai-sales-officer">
    <div class="container-fluid">
        <!-- Header -->
        <div class="reports-header mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="reports-title">
                        <i class="fas fa-robot"></i>
                        AI Sales Officer
                        <span class="ai-badge ms-3">
                            <i class="fas fa-brain me-1"></i>
                            AI Powered
                        </span>
                    </h1>
                    <p class="reports-subtitle mb-0">
                        Configure your intelligent WhatsApp sales assistant for automated customer engagement
                    </p>
                </div>
            </div>
        </div>
               <div class="main-layout d-flex">
            <!-- Sidebar Navigation (Compact) -->
            <nav class="sidebar shadow-sm">
                <ul class="sidebar-nav nav flex-column py-3">
                    <li>
                        <a href="{{ url('service/index') }}" class="nav-link{{ request()->is('service/index') ? ' active' : '' }}">
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ url('service/jd') }}" class="nav-link{{ request()->is('service/jd') ? ' active' : '' }}">
                            <span>Job Description</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Main Content Area -->
            <div class="content-area flex-grow-1 p-3 ms-3 mb-4" style="width:80%">
<div class="job-description-page">
    <div class="page-header">
        <h2 class="page-title">
            <i class="fas fa-clipboard-list"></i>
            AI Job Description Configuration
        </h2>
        <div class="header-actions">
            <button class="btn btn-outline-secondary" onclick="resetConfiguration()">
                <i class="fas fa-undo"></i>
                Reset to Default
            </button>
            <button class="btn btn-success" onclick="saveConfiguration()">
                <i class="fas fa-save"></i>
                Save Configuration
            </button>
        </div>
    </div>
    
    <form id="ai-agent-form" method="POST" action="{{ route('ai-agents.store') }}">
        @csrf
        <div class="configuration-wizard">
        <!-- Progress Steps -->
        <div class="steps-progress">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Assistant Info</div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Target Group</div>
            </div>
            
            <div class="step" data-step="5">
                <div class="step-number">5</div>
                <div class="step-label">Negotiation</div>
            </div>
            <div class="step" data-step="6">
                <div class="step-number">6</div>
                <div class="step-label">Fallback</div>
            </div>
            <div class="step" data-step="7">
                <div class="step-number">7</div>
                <div class="step-label">Terms & Review</div>
            </div>

        </div>
        
        <!-- Step 1: Assistant Information -->
        <div class="step-content active" id="step-1">
            <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-robot text-primary"></i>
                    Step 1: Assistant Information
                </h4>
                <p class="step-description">Give your AI sales assistant a name and define its basic identity.</p>
                
                <div class="form-group">
                    <label class="form-label">Assistant Name *</label>
                    <input type="text" class="form-control" name="assistant_name" placeholder="e.g., Sarah, Alex, SalesBot Pro" required>
                    <small class="text-muted">Choose a friendly name that customers will interact with</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contact Categories to Target *</label>
                    <p class="text-muted mb-3">Select which customer segments this assistant should focus on selling to:</p>
                    <div class="checkbox-group" id="user-types-container">
                        <!-- User types will be loaded dynamically -->
                        <div class="text-center py-3">
                           <label for="quantity" class=" col-form-label text-right">{{__('user_group')}}</label>
                        
                           <select class="form-control" name="event_guest_category_id" id="append_option">

                            @php
                                // Load guest categories from the database
                                $guest_categories = \App\Models\EventGuestCategory::all();
                            @endphp
                            @foreach ($guest_categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                           
                        </select>
                        </div>
                    </div>
                    <small class="text-muted">Your assistant will be optimized to sell to these specific customer types</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Assistant Description</label>
                    <textarea class="form-control" name="personality_description" rows="4" placeholder="Describe your assistant's personality and approach...">Meet {{ $assistantName ?? 'our AI assistant' }}, your dedicated WhatsApp sales consultant who understands the unique needs of African businesses. Professional, patient, and always ready to help you find the perfect solution for your business growth.</textarea>
                    <small class="text-muted">This helps define how your assistant will interact with customers</small>
                </div>
            </div>
              <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-clock text-primary"></i>
                    Step 3: Define Working Hours
                </h4>
                <p class="step-description">Set when your AI sales officer should be active and respond to customers.</p>
                
                <div class="form-group">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="always_available" checked>
                        <label class="form-check-label" for="always_available">
                            <strong>Available 24/7</strong>
                            <small class="d-block text-muted">AI will respond immediately at any time</small>
                        </label>
                    </div>
                </div>
                
                <div id="custom-hours" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Business Days *</label>
                            <div class="checkbox-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="monday" id="monday" checked>
                                    <label class="form-check-label" for="monday">Monday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="tuesday" id="tuesday" checked>
                                    <label class="form-check-label" for="tuesday">Tuesday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="wednesday" id="wednesday" checked>
                                    <label class="form-check-label" for="wednesday">Wednesday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="thursday" id="thursday" checked>
                                    <label class="form-check-label" for="thursday">Thursday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="friday" id="friday" checked>
                                    <label class="form-check-label" for="friday">Friday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="saturday" id="saturday">
                                    <label class="form-check-label" for="saturday">Saturday</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="business_days[]" value="sunday" id="sunday">
                                    <label class="form-check-label" for="sunday">Sunday</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Start Time *</label>
                                <input type="time" class="form-control" name="start_time" value="08:00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Time *</label>
                                <input type="time" class="form-control" name="end_time" value="18:00">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Time Zone *</label>
                                <select class="form-select" name="timezone">
                                    <option value="Africa/Nairobi" selected>East Africa Time (GMT+3)</option>
                                    <option value="Africa/Lagos">West Africa Time (GMT+1)</option>
                                    <option value="Africa/Cairo">Egypt Time (GMT+2)</option>
                                    <option value="Africa/Johannesburg">South Africa Time (GMT+2)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Out-of-Hours Message</label>
                    <textarea class="form-control" name="out_of_hours_message" rows="3" placeholder="Message to send when AI is not available...">Thank you for contacting us! Our AI assistant is currently offline. Our business hours are Monday-Friday, 8:00 AM - 6:00 PM EAT. We'll respond to your message as soon as we're back online.</textarea>
                </div>

                <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-language text-primary"></i>
                    Step 4: Choose Languages
                </h4>
                <p class="step-description">Select which languages your AI sales officer should support.</p>
                
                <div class="form-group">
                    <label class="form-label">Primary Language *</label>
                    <select class="form-select" name="primary_language" required>
                        <option value="en" selected>English</option>
                        <option value="sw">Swahili</option>
                        <option value="fr">French</option>
                        <option value="ar">Arabic</option>
                        <option value="pt">Portuguese</option>
                        <option value="am">Amharic</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Additional Languages</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="sw" id="lang-sw">
                            <label class="form-check-label" for="lang-sw">Swahili</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="fr" id="lang-fr">
                            <label class="form-check-label" for="lang-fr">French</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="ar" id="lang-ar">
                            <label class="form-check-label" for="lang-ar">Arabic</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="pt" id="lang-pt">
                            <label class="form-check-label" for="lang-pt">Portuguese</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="am" id="lang-am">
                            <label class="form-check-label" for="lang-am">Amharic</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="yo" id="lang-yo">
                            <label class="form-check-label" for="lang-yo">Yoruba</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="ig" id="lang-ig">
                            <label class="form-check-label" for="lang-ig">Igbo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="additional_languages[]" value="ha" id="lang-ha">
                            <label class="form-check-label" for="lang-ha">Hausa</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="auto_detect_language" checked>
                        <label class="form-check-label" for="auto_detect_language">
                            <strong>Auto-detect customer language</strong>
                            <small class="d-block text-muted">AI will automatically detect and respond in customer's language</small>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Language Fallback Message</label>
                    <textarea class="form-control" name="language_fallback_message" rows="3" placeholder="Message when language is not supported...">I understand you're writing in a language I don't fully support yet. I can communicate in English, Swahili, French, and Arabic. Could you please send your message in one of these languages? Thank you!</textarea>
                </div>
            </div>
                
            </div>
        </div>
        
        <!-- Step 2: Target Group -->
        <!-- Step 2: Target Group -->
        <div class="step-content" id="step-2">
            <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-users text-primary"></i>
                    Step 2: Define Target Approach
                </h4>
                <p class="step-description">Define how your AI sales officer should approach and communicate with customers.</p>
                
                <div class="form-group">
                    <label class="form-label">Primary Target Audience *</label>
                    <select class="form-select" name="target_audience" required>
                        <option value="">Select target audience</option>
                        <option value="small-businesses">Small Businesses (1-10 employees)</option>
                        <option value="medium-businesses">Medium Businesses (11-50 employees)</option>
                        <option value="enterprises">Large Enterprises (50+ employees)</option>
                        <option value="individuals">Individual Customers</option>
                        <option value="mixed">Mixed (All types)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Industry Focus</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="retail" id="retail">
                            <label class="form-check-label" for="retail">Retail & E-commerce</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="hospitality" id="hospitality">
                            <label class="form-check-label" for="hospitality">Hospitality & Tourism</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="healthcare" id="healthcare">
                            <label class="form-check-label" for="healthcare">Healthcare</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="education" id="education">
                            <label class="form-check-label" for="education">Education</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="finance" id="finance">
                            <label class="form-check-label" for="finance">Finance & Banking</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="technology" id="technology">
                            <label class="form-check-label" for="technology">Technology</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="industries[]" value="other" id="other">
                            <label class="form-check-label" for="other">Other</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Communication Tone *</label>
                    <select class="form-select" name="communication_tone" required>
                        <option value="">Select communication style</option>
                        <option value="professional">Professional & Formal</option>
                        <option value="friendly">Friendly & Casual</option>
                        <option value="consultative">Consultative & Advisory</option>
                        <option value="direct">Direct & To-the-point</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">AI Personality Description</label>
                    <textarea class="form-control" name="personality_description" rows="4" placeholder="Describe how the AI should behave and interact with customers...">Act as a professional and knowledgeable WhatsApp business consultant who understands the unique challenges faced by African SMEs. Be patient, culturally sensitive, and always focus on providing value to the customer.</textarea>
                </div>
            </div>
        </div>
        
       
        
    
        
        <!-- Step 5: Negotiation Behavior -->
        <div class="step-content" id="step-5">
            <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-handshake text-primary"></i>
                    Step 5: Set Negotiation Behavior
                </h4>
                <p class="step-description">Configure how your AI should handle pricing negotiations and special offers.</p>
                
                <div class="form-group">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="allow_negotiation" checked>
                        <label class="form-check-label" for="allow_negotiation">
                            <strong>Allow AI to negotiate prices?</strong>
                            <small class="d-block text-muted">Enable price negotiations within defined limits</small>
                        </label>
                    </div>
                </div>
                
                <div id="negotiation-settings">
                    <div class="form-group">
                        <label class="form-label">Maximum Discount Allowed *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="max_discount_allowed" min="0" max="50" value="15">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted">Maximum discount AI can offer (will use product-specific limits if lower)</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="accept_installments">
                            <label class="form-check-label" for="accept_installments">
                                <strong>Accept installment payments?</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div id="installment-settings" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Maximum Installments</label>
                                <select class="form-select" name="max_installments">
                                    <option value="2">2 payments</option>
                                    <option value="3" selected>3 payments</option>
                                    <option value="4">4 payments</option>
                                    <option value="6">6 payments</option>
                                    <option value="12">12 payments</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Minimum Down Payment</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="min_down_payment" min="10" max="100" value="50">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="stop_orders_low_stock" checked>
                            <label class="form-check-label" for="stop_orders_low_stock">
                                <strong>Stop accepting orders when stock is low?</strong>
                                <small class="d-block text-muted">Prevent overselling when inventory is running low</small>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Low Stock Threshold</label>
                        <input type="number" class="form-control" name="low_stock_threshold" min="1" max="100" value="5">
                        <small class="text-muted">Stop accepting orders when stock falls below this number</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Negotiation Script</label>
                    <textarea class="form-control" name="negotiation_script" rows="4" placeholder="How should AI handle price negotiations...">I understand you're looking for a better price. Let me see what I can do for you. Based on your needs and our current promotions, I can offer you a discount. Would you like me to check what discounts are available for your specific situation?</textarea>
                </div>
            </div>
        </div>
        
        <!-- Step 6: Fallback Number -->
        <div class="step-content" id="step-6">
            <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-phone text-primary"></i>
                    Step 6: Assign Fallback Number & Escalation Rules
                </h4>
                <p class="step-description">Set up human backup support and escalation procedures.</p>
                
                <div class="form-group">
                    <label class="form-label">Fallback Phone Number *</label>
                    <input type="tel" class="form-control" name="fallback_number" placeholder="+254700000000" required>
                    <small class="text-muted">Number to transfer customers when AI cannot help</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Fallback Person Name</label>
                    <input type="text" class="form-control" name="fallback_person" placeholder="e.g., John - Sales Manager">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Escalation Triggers</label>
                    <div class="checkbox-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="escalation_triggers[]" value="complex-questions" id="trigger1" checked>
                            <label class="form-check-label" for="trigger1">Complex technical questions</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="escalation_triggers[]" value="complaints" id="trigger2" checked>
                            <label class="form-check-label" for="trigger2">Customer complaints</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="escalation_triggers[]" value="large-orders" id="trigger3">
                            <label class="form-check-label" for="trigger3">Large orders (above threshold)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="escalation_triggers[]" value="payment-issues" id="trigger4" checked>
                            <label class="form-check-label" for="trigger4">Payment issues</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="escalation_triggers[]" value="angry-customer" id="trigger5" checked>
                            <label class="form-check-label" for="trigger5">Angry or frustrated customers</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Large Order Threshold (for escalation)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" name="large_order_threshold" min="0" value="1000">
                    </div>
                </div>
                
                <div class="urgency-section">
                    <h6 class="section-subtitle">
                        <i class="fas fa-clock"></i>
                        Urgency Rules & Smart Triggers
                    </h6>
                    
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="auto_followup" checked>
                            <label class="form-check-label" for="auto_followup">
                                <strong>Auto follow-up if customer doesn't reply?</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div id="followup-settings">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Follow-up after</label>
                                <select class="form-select" name="followup_delay">
                                    <option value="2">2 hours</option>
                                    <option value="6">6 hours</option>
                                    <option value="12">12 hours</option>
                                    <option value="24" selected>24 hours</option>
                                    <option value="48">48 hours</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Maximum follow-ups</label>
                                <select class="form-select" name="max_followups">
                                    <option value="1" selected>1 follow-up</option>
                                    <option value="2">2 follow-ups</option>
                                    <option value="3">3 follow-ups</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Follow-up Message</label>
                            <textarea class="form-control" name="followup_message" rows="3" placeholder="Follow-up message template...">Hi! I wanted to follow up on our conversation about [product/service]. Do you have any questions I can help you with? I'm here to assist you in finding the perfect solution for your business needs.</textarea>
                        </div>
                    </div>
                </div>
                
                <div class="notification-section">
                    <h6 class="section-subtitle">
                        <i class="fas fa-bell"></i>
                        Owner Notifications
                    </h6>
                    
                    <div class="form-group">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_on_deal" checked>
                            <label class="form-check-label" for="notify_on_deal">
                                <strong>Notify me when AI closes a deal</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notification Methods</label>
                        <div class="checkbox-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notification_methods[]" value="whatsapp" id="notify-whatsapp" checked>
                                <label class="form-check-label" for="notify-whatsapp">WhatsApp</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notification_methods[]" value="email" id="notify-email" checked>
                                <label class="form-check-label" for="notify-email">Email</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notification_methods[]" value="sms" id="notify-sms">
                                <label class="form-check-label" for="notify-sms">SMS</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Additional Notifications</label>
                        <div class="checkbox-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="additional_notifications[]" value="new-lead" id="notify-lead">
                                <label class="form-check-label" for="notify-lead">New qualified leads</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="additional_notifications[]" value="escalation" id="notify-escalation" checked>
                                <label class="form-check-label" for="notify-escalation">When AI escalates to human</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="additional_notifications[]" value="errors" id="notify-errors" checked>
                                <label class="form-check-label" for="notify-errors">System errors or issues</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Step 7: Terms & Conditions -->
        <div class="step-content" id="step-7">
            <div class="step-card">
                <h4 class="step-title">
                    <i class="fas fa-file-contract text-primary"></i>
                    Step 7: Terms & Conditions
                </h4>
                <p class="step-description">Please review and accept our terms and conditions to proceed.</p>
                
                <div class="terms-section">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                AI Sales Agent Service Agreement
                            </h6>
                            <p class="card-text">
                                By using our AI Sales Agent service, you agree to our terms of service, privacy policy, and acceptable use guidelines.
                            </p>
                            <div class="key-points mb-3">
                                <h6>Key Points:</h6>
                                <ul class="small">
                                    <li>Your data is protected and encrypted</li>
                                    <li>Service availability is 99.9% uptime target</li>
                                    <li>You can modify or cancel anytime</li>
                                    <li>Support is available during business hours</li>
                                    <li>Billing is monthly based on usage</li>
                                </ul>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('ai-agent-terms') }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Read Full Terms
                                </a>
                                <a href="{{ route('privacy-policy') }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Privacy Policy
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group mt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="accepted_terms" name="accepted_terms" required>
                        <label class="form-check-label" for="accepted_terms">
                            <strong>I have read and accept the Terms & Conditions and Privacy Policy *</strong>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="marketing_consent" name="marketing_consent">
                        <label class="form-check-label" for="marketing_consent">
                            I consent to receiving marketing communications about product updates and features
                        </label>
                    </div>
                </div>
                
                <div class="legal-notice mt-3 p-3 bg-warning bg-opacity-10 border border-warning rounded">
                    <small class="text-dark">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <strong>Legal Notice:</strong> By proceeding, you confirm that you have the authority to bind your organization to these terms and that you understand the AI service capabilities and limitations.
                    </small>
                </div>
                 <p class="step-description">Review your AI Sales Agent configuration before submitting.</p>
                
                <div id="configuration-summary">
                    <!-- Summary will be populated by JavaScript -->
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirm-settings" required>
                        <label class="form-check-label" for="confirm-settings">
                            I confirm that all settings are correct and want to activate this AI sales officer configuration.
                        </label>
                    </div>
                </div>
            </div>
        </div>
        

        
        <!-- Navigation Buttons -->
        <div class="wizard-navigation">
            <button class="btn btn-outline-secondary" id="prev-step" onclick="previousStep()" style="display: none;">
                <i class="fas fa-arrow-left"></i>
                Previous
            </button>
            <button class="btn btn-primary" id="next-step" onclick="nextStep()">
                Next
                <i class="fas fa-arrow-right"></i>
            </button>
            <button class="btn btn-success" id="save-config" onclick="finalSave()" style="display: none;">
                <i class="fas fa-save"></i>
                Save & Activate Configuration
            </button>
        </div>
    </div>
    </form>
</div>

      </div>
        </div>
    </div>
</div>

<style>
    .job-description-page {
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
    
    .header-actions {
        display: flex;
        gap: 1rem;
    }
    
    .configuration-wizard {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .steps-progress {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 2rem;
        overflow-x: auto;
    }
    
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        min-width: 120px;
        padding: 0 1rem;
        position: relative;
        color: #64748b;
    }
    
    .step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 15px;
        right: -50%;
        width: 100%;
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }
    
    .step.active {
        color: #6366f1;
    }
    
    .step.active .step-number {
        background: #6366f1;
        color: white;
    }
    
    .step.completed .step-number {
        background: #10b981;
        color: white;
    }
    
    .step.completed::after {
        background: #10b981;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
        position: relative;
        z-index: 2;
    }
    
    .step-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .step-content {
        display: none;
        padding: 2rem;
    }
    
    .step-content.active {
        display: block;
    }
    
    .step-card {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .step-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .step-description {
        color: #64748b;
        margin-bottom: 2rem;
        font-size: 1rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 0.75rem;
        margin-top: 0.5rem;
    }
    
    .form-check {
        padding-left: 0;
    }
    
    .form-check-input {
        margin-right: 0.5rem;
    }
    
    .form-check-label {
        font-weight: 500;
    }
    
    .form-check-label small {
        font-weight: 400;
        color: #64748b;
    }
    
    .section-subtitle {
        font-size: 1.125rem;
        font-weight: 600;
        color: #374151;
        margin: 2rem 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .wizard-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
    
    .configuration-summary {
        background: #f8fafc;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }
    
    .summary-section {
        margin-bottom: 1.5rem;
    }
    
    .summary-section:last-child {
        margin-bottom: 0;
    }
    
    .summary-title {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 1rem;
    }
    
    .summary-value {
        color: #64748b;
        margin-left: 1rem;
    }
    
    .urgency-section, .notification-section {
        background: #f1f5f9;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .header-actions {
            width: 100%;
        }
        
        .steps-progress {
            padding: 1rem;
        }
        
        .step {
            min-width: 80px;
            padding: 0 0.5rem;
        }
        
        .step-label {
            font-size: 0.65rem;
        }
        
        .step-content {
            padding: 1rem;
        }
        
        .wizard-navigation {
            padding: 1rem;
        }
        
        .checkbox-group {
            grid-template-columns: 1fr;
        }
    }
/* Use the application's base font and sizing for consistency */
body, .ai-sales-officer {
    font-family: inherit !important;
    font-size: 1rem;
    background: #f8fafc;
}

.ai-sales-officer {
    min-height: 100vh;
    padding-bottom: 24px;
}

.reports-header {
    background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
    border-radius: 14px;
    padding: 18px 18px 12px 18px;
    color: white;
    margin-bottom: 18px;
    box-shadow: 0 4px 16px rgba(37, 211, 102, 0.10);
}

.reports-title {
    font-size: 1.15rem;
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.reports-subtitle {
    font-size: 0.97rem;
    opacity: 0.92;
    margin-bottom: 0;
}

.ai-badge {
    font-size: 0.78rem;
    font-weight: 500;
    background: #0ea5e9 !important;
    color: #fff !important;
    border-radius: 10px;
    padding: 2px 8px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.main-layout {
    gap: 18px;
}

.sidebar {
    width: 140px;
    min-width: 120px;
    max-width: 140px;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    position: sticky;
    top: 24px;
    height: fit-content;
    padding: 0;
}

.sidebar-nav .nav-link {
    border: none;
    background: none;
    color: #334155;
    font-weight: 500;
    padding: 8px 10px;
    border-radius: 8px;
    transition: background 0.18s, color 0.18s;
    font-size: 0.98rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sidebar-nav .nav-link.active,
.sidebar-nav .nav-link:hover {
    background: #e0f2fe;
    color: #0ea5e9;
}

.sidebar-nav .nav-link .metric-icon {
    font-size: 1rem;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #f1f5f9;
    color: #64748b;
    margin-right: 0;
}

.content-area {
    min-height: 400px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    border: 1px solid #e2e8f0;
    position: relative;
    transition: box-shadow 0.18s;
    font-size: 1rem;
}

.tab-content {
    display: none;
    animation: fadeIn 0.3s;
}

.tab-content.active {
    display: block;
}

.loading {
    color: #64748b;
    font-size: 1rem;
}

@media (max-width: 991px) {
    .main-layout {
        flex-direction: column;
        gap: 12px;
    }
    .sidebar {
        width: 100%;
        min-width: unset;
        max-width: unset;
        position: static;
        margin-bottom: 10px;
    }
    .content-area {
        margin-left: 0 !important;
    }
}

@media (max-width: 600px) {
    .reports-header {
        padding: 10px;
    }
    .reports-title {
        font-size: 1rem;
    }
    .main-layout {
        gap: 6px;
    }
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px);}
    to { opacity: 1; transform: translateY(0);}
}
</style>

<script type="text/javascript">

    
//job description

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepVisibility();
            updateNavigationButtons();
            
            if (currentStep === totalSteps) {
                populateReviewStep();
            }
        }
    }
}

let currentStep = 1;
const totalSteps = 8;

function initializeJobDescription() {
    // Initialize form interactions
    setupFormInteractions();
    updateStepVisibility();
    updateNavigationButtons();
    
    // Load user types for contact category dropdown
    loadUserTypes();
}

function loadUserTypes() {
    fetch('/api/user-types')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('contact_category');
                if (select) {
                    // Clear existing options except the first one
                    select.innerHTML = '<option value="">-- Select Contact Category --</option>';
                    
                    // Add user types as options
                    data.data.forEach(userType => {
                        const option = document.createElement('option');
                        option.value = userType.id;
                        option.textContent = userType.name;
                        option.title = userType.description;
                        select.appendChild(option);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error loading user types:', error);
        });
}

function setupFormInteractions() {
    // Always available toggle
    document.getElementById('always_available').addEventListener('change', function() {
        const customHours = document.getElementById('custom-hours');
        customHours.style.display = this.checked ? 'none' : 'block';
    });
    
    // Negotiation toggle
    document.getElementById('allow_negotiation').addEventListener('change', function() {
        const negotiationSettings = document.getElementById('negotiation-settings');
        negotiationSettings.style.display = this.checked ? 'block' : 'none';
    });
    
    // Installments toggle
    document.getElementById('accept_installments').addEventListener('change', function() {
        const installmentSettings = document.getElementById('installment-settings');
        installmentSettings.style.display = this.checked ? 'block' : 'none';
    });
    
    // Follow-up toggle
    document.getElementById('auto_followup').addEventListener('change', function() {
        const followupSettings = document.getElementById('followup-settings');
        followupSettings.style.display = this.checked ? 'block' : 'none';
    });
}



function previousStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStepVisibility();
        updateNavigationButtons();
    }
}

function updateStepVisibility() {
    // Update step indicators
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active', 'completed');
        
        if (stepNumber === currentStep) {
            step.classList.add('active');
        } else if (stepNumber < currentStep) {
            step.classList.add('completed');
        }
    });
    
    // Update step content
    document.querySelectorAll('.step-content').forEach((content, index) => {
        const stepNumber = index + 1;
        content.classList.toggle('active', stepNumber === currentStep);
    });
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById('prev-step');
    const nextBtn = document.getElementById('next-step');
    const saveBtn = document.getElementById('save-config');
    
    prevBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'inline-flex' : 'none';
    saveBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
}

function validateCurrentStep() {
    const currentStepElement = document.getElementById(`step-${currentStep}`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    // Special validation for Terms & Conditions step (step 7)
    if (currentStep === 7) {
        const termsCheckbox = document.getElementById('accepted_terms');
        if (!termsCheckbox.checked) {
            termsCheckbox.focus();
            showNotification('You must accept the Terms & Conditions to proceed', 'warning');
            return false;
        }
    }
    
    for (let field of requiredFields) {
        if (!field.value.trim() && field.type !== 'checkbox') {
            field.focus();
            showNotification(`Please fill in the required field: ${field.previousElementSibling?.textContent || field.name}`, 'warning');
            return false;
        }
        
        if (field.type === 'checkbox' && field.required && !field.checked) {
            field.focus();
            showNotification(`Please check the required field: ${field.nextElementSibling?.textContent || field.name}`, 'warning');
            return false;
        }
    }
    
    return true;
}

function generateSummary() {
    const summary = document.getElementById('configuration-summary');
    const formData = new FormData(document.querySelector('.configuration-wizard'));
    
    let summaryHTML = '<div class="configuration-summary">';
    
    // Target Group Summary
    summaryHTML += `
        <div class="summary-section">
            <div class="summary-title">🎯 Target Group</div>
            <div class="summary-value">Audience: ${getSelectValue('target_audience')}</div>
            <div class="summary-value">Tone: ${getSelectValue('communication_tone')}</div>
        </div>
    `;
    
    // Working Hours Summary
    const alwaysAvailable = document.getElementById('always_available').checked;
    summaryHTML += `
        <div class="summary-section">
            <div class="summary-title">⏰ Working Hours</div>
            <div class="summary-value">${alwaysAvailable ? 'Available 24/7' : 'Custom hours set'}</div>
        </div>
    `;
    
    // Languages Summary
    const primaryLang = getSelectValue('primary_language');
    const additionalLangs = getCheckedValues('additional_languages[]');
    summaryHTML += `
        <div class="summary-section">
            <div class="summary-title">🌍 Languages</div>
            <div class="summary-value">Primary: ${primaryLang}</div>
            ${additionalLangs.length ? `<div class="summary-value">Additional: ${additionalLangs.join(', ')}</div>` : ''}
        </div>
    `;
    
    // Negotiation Summary
    const allowNegotiation = document.getElementById('allow_negotiation').checked;
    summaryHTML += `
        <div class="summary-section">
            <div class="summary-title">🤝 Negotiation</div>
            <div class="summary-value">${allowNegotiation ? 'Enabled' : 'Disabled'}</div>
            ${allowNegotiation ? `<div class="summary-value">Max discount: ${getInputValue('max_discount_allowed')}%</div>` : ''}
        </div>
    `;
    
    // Fallback Summary
    summaryHTML += `
        <div class="summary-section">
            <div class="summary-title">📞 Fallback & Notifications</div>
            <div class="summary-value">Fallback: ${getInputValue('fallback_number')}</div>
            <div class="summary-value">Methods: ${getCheckedValues('notification_methods[]').join(', ')}</div>
        </div>
    `;
    
    summaryHTML += '</div>';
    summary.innerHTML = summaryHTML;
}

function getSelectValue(name) {
    const select = document.querySelector(`select[name="${name}"]`);
    return select ? select.selectedOptions[0].text : '';
}

function getInputValue(name) {
    const input = document.querySelector(`input[name="${name}"]`);
    return input ? input.value : '';
}

function getCheckedValues(name) {
    const checkboxes = document.querySelectorAll(`input[name="${name}"]:checked`);
    return Array.from(checkboxes).map(cb => cb.nextElementSibling.textContent);
}

function finalSave() {
    const confirmCheckbox = document.getElementById('confirm-settings');
    if (!confirmCheckbox.checked) {
        showNotification('Please confirm the settings before saving.', 'warning');
        return;
    }
    
    const saveBtn = document.getElementById('save-config');
    const originalText = saveBtn.innerHTML;
    
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving Configuration...';
    saveBtn.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
        showNotification('AI Sales Officer configuration saved successfully!', 'success');
        
        // Reset to first step after successful save
        setTimeout(() => {
            currentStep = 1;
            updateStepVisibility();
            updateNavigationButtons();
            confirmCheckbox.checked = false;
        }, 2000);
    }, 3000);
}

function saveConfiguration() {
    showNotification('Quick save completed!', 'success');
}

function resetConfiguration() {
    if (confirm('Are you sure you want to reset all configuration to default values? This will clear all your current settings.')) {
        // Reset form
        document.querySelectorAll('.configuration-wizard input, .configuration-wizard select, .configuration-wizard textarea').forEach(field => {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = field.defaultChecked;
            } else {
                field.value = field.defaultValue;
            }
        });
        
        // Reset to first step
        currentStep = 1;
        updateStepVisibility();
        updateNavigationButtons();
        setupFormInteractions();
        
        showNotification('Configuration reset to default values.', 'info');
    }
}

function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'warning' ? 'alert-warning' : 
                     type === 'info' ? 'alert-info' : 'alert-danger';
    
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

function populateReviewStep() {
    // Populate Assistant Information
    document.getElementById('review-assistant-name').textContent = 
        document.getElementById('assistant_name')?.value || '-';
    const selectedCategory = document.getElementById('contact_category');
    document.getElementById('review-contact-category').textContent = 
        selectedCategory?.selectedOptions[0]?.text || '-';
    
    // Populate Company Details (from existing fields if they exist)
    document.getElementById('review-company-name').textContent = 
        document.querySelector('input[name="company_name"]')?.value || '-';
    document.getElementById('review-company-industry').textContent = 
        document.querySelector('select[name="company_industry"]')?.selectedOptions[0]?.text || '-';
    document.getElementById('review-company-size').textContent = 
        document.querySelector('select[name="company_size"]')?.selectedOptions[0]?.text || '-';
    
    // Populate Products & Goals
    document.getElementById('review-products').textContent = 
        document.querySelector('textarea[name="products_services"]')?.value || '-';
    document.getElementById('review-goals').textContent = 
        document.querySelector('textarea[name="sales_goals"]')?.value || '-';
    
    // Populate Target Audience
    document.getElementById('review-demographics').textContent = 
        document.querySelector('textarea[name="target_demographics"]')?.value || '-';
    document.getElementById('review-pain-points').textContent = 
        document.querySelector('textarea[name="pain_points"]')?.value || '-';
    
    // Populate Configuration
    document.getElementById('review-tone').textContent = 
        document.querySelector('select[name="communication_tone"]')?.selectedOptions[0]?.text || '-';
    document.getElementById('review-style').textContent = 
        document.querySelector('select[name="communication_style"]')?.selectedOptions[0]?.text || '-';
    document.getElementById('review-personality').textContent = 
        document.querySelector('select[name="personality_type"]')?.selectedOptions[0]?.text || '-';
    
    // Populate Keywords
    const keywords = document.querySelector('textarea[name="keywords"]')?.value;
    document.getElementById('review-keywords').textContent = 
        keywords ? keywords.split(',').slice(0, 3).join(', ') + (keywords.split(',').length > 3 ? '...' : '') : '-';
    
    // Populate Availability
    const alwaysAvailable = document.getElementById('always_available')?.checked;
    document.getElementById('review-availability').textContent = 
        alwaysAvailable ? '24/7 Available' : 'Custom Schedule';
    
    // Populate Terms Status
    const termsAccepted = document.getElementById('accepted_terms')?.checked;
    document.getElementById('review-terms').textContent = 
        termsAccepted ? 'Accepted' : 'Not Accepted';
}

function submitConfiguration() {
    const form = document.getElementById('ai-agent-form');
    const formData = new FormData(form);
    
    // Show loading state
    const submitBtn = document.getElementById('next-step');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    submitBtn.disabled = true;
    
    // Submit using fetch API
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('AI Sales Agent configured successfully!', 'success');
            // Redirect to dashboard or agents list
            setTimeout(() => {
                window.location.href = data.redirect || '/dashboard';
            }, 2000);
        } else {
            throw new Error(data.message || 'Configuration failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error submitting configuration: ' + error.message, 'error');
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            currentStep++;
            updateStepVisibility();
            updateNavigationButtons();
            
            if (currentStep === totalSteps) {
                populateReviewStep();
            }
        } else {
            // Submit the form
            submitConfiguration();
        }
    }
}
</script>
@endsection
