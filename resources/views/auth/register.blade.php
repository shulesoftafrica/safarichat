@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
        font-family: 'Inter', sans-serif;
    }
    
    body {
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        margin: 0;
        padding: 0;
    }
    
    .registration-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    
    .registration-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 600px;
        position: relative;
        overflow: hidden;
    }
    
    .registration-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
    }
    
    .step-indicator {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .step-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .step-subtitle {
        color: #64748b;
        margin-bottom: 20px;
        font-size: 1rem;
    }
    
    .step-progress {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 30px;
    }
    
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .step-number.active {
        background: #25d366;
        color: white;
    }
    
    .step-number.completed {
        background: #10b981;
        color: white;
    }
    
    .step-number.inactive {
        background: #f1f5f9;
        color: #94a3b8;
    }
    
    .step-line {
        width: 40px;
        height: 2px;
        background: #e2e8f0;
        transition: background 0.3s ease;
    }
    
    .step-line.completed {
        background: #10b981;
    }
    
    .business-type-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .business-type-card {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .business-type-card:hover {
        border-color: #25d366;
        background: #f0fff4;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.15);
    }
    
    .business-type-card.selected {
        border-color: #25d366;
        background: #f0fff4;
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.15);
    }
    
    .business-type-card.selected::after {
        content: "✓";
        position: absolute;
        top: 12px;
        right: 12px;
        background: #25d366;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    .business-icon {
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        font-size: 1.8rem;
        color: #25d366;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .business-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .business-desc {
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.4;
    }
    
    .form-section {
        margin-bottom: 30px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        display: block;
        font-size: 0.95rem;
    }
    
    .form-label.required::after {
        content: " *";
        color: #ef4444;
    }
    
    .form-control {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 14px 16px;
        font-size: 1rem;
        transition: border-color 0.3s ease;
        background: #fafafa;
        width: 100%;
    }
    
    .form-control:focus {
        border-color: #25d366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        outline: none;
        background: white;
    }
    
    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 12px center;
        background-repeat: no-repeat;
        background-size: 16px;
        padding-right: 40px;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 12px;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    
    .checkbox-item:hover {
        background: #f0fff4;
        border-color: #25d366;
    }
    
    .checkbox-item input[type="checkbox"] {
        accent-color: #25d366;
    }
    
    .navigation-buttons {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #e2e8f0;
    }
    
    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
        color: #475569;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37, 211, 102, 0.3);
    }
    
    .btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .feature-preview {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #bae6fd;
    }
    
    .feature-title {
        font-weight: 600;
        color: #0c4a6e;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .feature-list li {
        padding: 6px 0;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .feature-list li::before {
        content: "✓";
        color: #25d366;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .registration-card {
            padding: 30px 20px;
            margin: 10px;
        }
        
        .business-type-grid {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .checkbox-group {
            grid-template-columns: 1fr;
        }
        
        .navigation-buttons {
            flex-direction: column;
            gap: 12px;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="registration-container">
    <div class="registration-card">
        <div id="wizard-app">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <h2 class="step-title">Complete Your Business Profile</h2>
                <p class="step-subtitle">Help us tailor WhatsApp solutions for your business</p>
                
                <div class="step-progress">
                    <div class="step-number active" id="step-1-indicator">1</div>
                    <div class="step-line" id="line-1"></div>
                    <div class="step-number inactive" id="step-2-indicator">2</div>
                    <div class="step-line" id="line-2"></div>
                    <div class="step-number inactive" id="step-3-indicator">3</div>
                </div>
            </div>

            <!-- Step 1: Business Type Selection -->
            <div id="step-1" class="step-content">
                <h3 style="text-align: center; margin-bottom: 30px; color: #1e293b;">What type of business do you run?</h3>
                
                <div class="business-type-grid">
                    <div class="business-type-card" data-type="retail">
                        <div class="business-icon">🏪</div>
                        <h4 class="business-title">Retail & Shop</h4>
                        <p class="business-desc">Clothing, electronics, general goods, supermarket</p>
                    </div>
                    
                    <div class="business-type-card" data-type="services">
                        <div class="business-icon">💼</div>
                        <h4 class="business-title">Services</h4>
                        <p class="business-desc">Salon, repair, cleaning, consulting, professional services</p>
                    </div>
                    
                    <div class="business-type-card" data-type="food">
                        <div class="business-icon">🍽️</div>
                        <h4 class="business-title">Food & Restaurant</h4>
                        <p class="business-desc">Restaurant, cafe, food delivery, catering</p>
                    </div>
                    
                    <div class="business-type-card" data-type="delivery">
                        <div class="business-icon">🚚</div>
                        <h4 class="business-title">Delivery & Logistics</h4>
                        <p class="business-desc">Courier, transport, logistics, shipping</p>
                    </div>
                    
                    <div class="business-type-card" data-type="healthcare">
                        <div class="business-icon">🏥</div>
                        <h4 class="business-title">Healthcare</h4>
                        <p class="business-desc">Clinic, pharmacy, medical services, wellness</p>
                    </div>
                    
                    <div class="business-type-card" data-type="education">
                        <div class="business-icon">📚</div>
                        <h4 class="business-title">Education & Training</h4>
                        <p class="business-desc">School, tutoring, training center, courses</p>
                    </div>
                    
                    <div class="business-type-card" data-type="real_estate">
                        <div class="business-icon">🏠</div>
                        <h4 class="business-title">Real Estate</h4>
                        <p class="business-desc">Property sales, rentals, construction, real estate agency</p>
                    </div>
                    
                    <div class="business-type-card" data-type="other">
                        <div class="business-icon">🏢</div>
                        <h4 class="business-title">Other Business</h4>
                        <p class="business-desc">Manufacturing, agriculture, finance, or other industry</p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Business Details -->
            <div id="step-2" class="step-content" style="display: none;">
                <h3 style="text-align: center; margin-bottom: 30px; color: #1e293b;">Tell us about your business</h3>
                
                <form id="business-form">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Business Name</label>
                                <input type="text" name="business_name" class="form-control" placeholder="Enter your business name" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required">Owner/Manager Name</label>
                                <input type="text" name="owner_name" class="form-control" placeholder="Your full name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Business Size</label>
                                <select name="business_size" class="form-control form-select" required>
                                    <option value="">Select business size</option>
                                    <option value="solo">Just me (Solo entrepreneur)</option>
                                    <option value="small">2-10 employees</option>
                                    <option value="medium">11-50 employees</option>
                                    <option value="large">50+ employees</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required">Monthly Customers</label>
                                <select name="customer_volume" class="form-control form-select" required>
                                    <option value="">How many customers per month?</option>
                                    <option value="1-50">1-50 customers</option>
                                    <option value="51-200">51-200 customers</option>
                                    <option value="201-500">201-500 customers</option>
                                    <option value="500+">500+ customers</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required">Primary Location</label>
                                <select name="region" class="form-control form-select" required>
                                    <option value="">Select your region</option>
                                    <option value="dar-es-salaam">Dar es Salaam</option>
                                    <option value="arusha">Arusha</option>
                                    <option value="mwanza">Mwanza</option>
                                    <option value="dodoma">Dodoma</option>
                                    <option value="mbeya">Mbeya</option>
                                    <option value="morogoro">Morogoro</option>
                                    <option value="tanga">Tanga</option>
                                    <option value="kilimanjaro">Kilimanjaro</option>
                                    <option value="tabora">Tabora</option>
                                    <option value="other">Other Region</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Years in Business</label>
                                <select name="years_in_business" class="form-control form-select">
                                    <option value="">Select experience</option>
                                    <option value="new">Just starting</option>
                                    <option value="1-2">1-2 years</option>
                                    <option value="3-5">3-5 years</option>
                                    <option value="5+">5+ years</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Current Customer Communication (check all that apply)</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="phone_calls" id="phone">
                                    <label for="phone">Phone calls</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="whatsapp_personal" id="whatsapp_personal">
                                    <label for="whatsapp_personal">Personal WhatsApp</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="sms" id="sms">
                                    <label for="sms">SMS</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="social_media" id="social">
                                    <label for="social">Social Media</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="email" id="email">
                                    <label for="email">Email</label>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="current_channels[]" value="in_person" id="in_person">
                                    <label for="in_person">In-person only</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Step 3: Business Goals & Features -->
            <div id="step-3" class="step-content" style="display: none;">
                <h3 style="text-align: center; margin-bottom: 30px; color: #1e293b;">What are your main business goals?</h3>
                
                <div class="form-section">
                    <div class="form-group">
                        <label class="form-label">Primary Goals (check all that apply)</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="increase_sales" id="increase_sales">
                                <label for="increase_sales">Increase sales</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="customer_retention" id="customer_retention">
                                <label for="customer_retention">Improve customer retention</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="reach_more_customers" id="reach_more">
                                <label for="reach_more">Reach more customers</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="save_time" id="save_time">
                                <label for="save_time">Save time on customer communication</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="professional_image" id="professional">
                                <label for="professional">Look more professional</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="business_goals[]" value="reduce_costs" id="reduce_costs">
                                <label for="reduce_costs">Reduce marketing costs</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Most Important WhatsApp Features</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="bulk_messaging" id="bulk_msg">
                                <label for="bulk_msg">Send messages to many customers at once</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="automated_responses" id="auto_response">
                                <label for="auto_response">Automated responses to common questions</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="customer_lists" id="customer_lists">
                                <label for="customer_lists">Organize customer contact lists</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="promotional_campaigns" id="promotions">
                                <label for="promotions">Send promotional campaigns</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="appointment_reminders" id="appointments">
                                <label for="appointments">Send appointment reminders</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" name="desired_features[]" value="payment_notifications" id="payments">
                                <label for="payments">Payment and order notifications</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Monthly Budget for Customer Communication</label>
                        <select name="budget_range" class="form-control form-select">
                            <option value="">Select budget range</option>
                            <option value="0-25000">TSh 0 - 25,000</option>
                            <option value="25000-50000">TSh 25,000 - 50,000</option>
                            <option value="50000-100000">TSh 50,000 - 100,000</option>
                            <option value="100000+">TSh 100,000+</option>
                        </select>
                    </div>
                </div>
                
                <!-- Feature Preview based on selections -->
                <div class="feature-preview" id="feature-preview" style="display: none;">
                    <div class="feature-title">
                        <i class="fas fa-magic"></i>
                        Recommended Features for Your Business
                    </div>
                    <ul class="feature-list" id="recommended-features">
                        <!-- Features will be populated by JavaScript -->
                    </ul>
                </div>
            </div>

            <!-- Hidden form fields -->
            <form id="registration-form" method="POST" action="{{ url('api/registerBusiness') }}" style="display: none;">
                @csrf
                <input type="hidden" name="phone" value="{{$phone}}">
                <input type="hidden" name="business_type" id="selected_business_type">
                <input type="hidden" name="business_data" id="business_data_json">
            </form>

            <!-- Navigation -->
            <div class="navigation-buttons">
                <button type="button" class="btn btn-secondary" id="back-btn" style="display: none;">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </button>
                
                <div style="flex: 1;"></div>
                
                <button type="button" class="btn btn-primary" id="next-btn" disabled>
                    Next
                    <i class="fas fa-arrow-right"></i>
                </button>
                
                <button type="button" class="btn btn-primary" id="complete-btn" style="display: none;">
                    Complete Registration
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentStep = 1;
    let selectedBusinessType = '';
    let businessData = {};
    
    const totalSteps = 3;
    
    // Business type recommendations
    const businessRecommendations = {
        retail: ['bulk_messaging', 'customer_lists', 'promotional_campaigns', 'payment_notifications'],
        services: ['appointment_reminders', 'automated_responses', 'customer_lists', 'promotional_campaigns'],
        food: ['bulk_messaging', 'promotional_campaigns', 'automated_responses', 'payment_notifications'],
        delivery: ['automated_responses', 'payment_notifications', 'customer_lists', 'appointment_reminders'],
        healthcare: ['appointment_reminders', 'automated_responses', 'customer_lists', 'payment_notifications'],
        education: ['bulk_messaging', 'automated_responses', 'appointment_reminders', 'promotional_campaigns'],
        real_estate: ['customer_lists', 'automated_responses', 'promotional_campaigns', 'appointment_reminders'],
        other: ['bulk_messaging', 'customer_lists', 'automated_responses', 'promotional_campaigns']
    };
    
    // Update step indicators
    function updateStepIndicators() {
        for (let i = 1; i <= totalSteps; i++) {
            const indicator = $(`#step-${i}-indicator`);
            const line = $(`#line-${i}`);
            
            if (i < currentStep) {
                indicator.removeClass('active inactive').addClass('completed');
                if (line.length) line.addClass('completed');
            } else if (i === currentStep) {
                indicator.removeClass('completed inactive').addClass('active');
            } else {
                indicator.removeClass('active completed').addClass('inactive');
                if (line.length) line.removeClass('completed');
            }
        }
    }
    
    // Show/hide steps
    function showStep(step) {
        $('.step-content').hide();
        $(`#step-${step}`).show();
        
        // Update navigation buttons
        $('#back-btn').toggle(step > 1);
        $('#next-btn').toggle(step < totalSteps);
        $('#complete-btn').toggle(step === totalSteps);
        
        updateStepIndicators();
        validateCurrentStep();
    }
    
    // Validate current step
    function validateCurrentStep() {
        let isValid = false;
        
        if (currentStep === 1) {
            isValid = selectedBusinessType !== '';
        } else if (currentStep === 2) {
            const form = $('#business-form')[0];
            const requiredFields = form.querySelectorAll('input[required], select[required]');
            isValid = Array.from(requiredFields).every(field => field.value.trim() !== '');
        } else if (currentStep === 3) {
            const goals = $('input[name="business_goals[]"]:checked').length;
            const features = $('input[name="desired_features[]"]:checked').length;
            isValid = goals > 0 && features > 0;
        }
        
        $('#next-btn, #complete-btn').prop('disabled', !isValid);
    }
    
    // Business type selection
    $('.business-type-card').on('click', function() {
        $('.business-type-card').removeClass('selected');
        $(this).addClass('selected');
        selectedBusinessType = $(this).data('type');
        $('#selected_business_type').val(selectedBusinessType);
        validateCurrentStep();
    });
    
    // Form field changes
    $(document).on('input change', 'input, select, textarea', function() {
        validateCurrentStep();
        
        // Update feature recommendations on step 3
        if (currentStep === 3) {
            updateFeatureRecommendations();
        }
    });
    
    // Update feature recommendations
    function updateFeatureRecommendations() {
        const selectedFeatures = [];
        $('input[name="desired_features[]"]:checked').each(function() {
            selectedFeatures.push($(this).val());
        });
        
        if (selectedFeatures.length > 0 && selectedBusinessType) {
            const recommendations = businessRecommendations[selectedBusinessType] || [];
            const matchingFeatures = recommendations.filter(feature => 
                selectedFeatures.includes(feature)
            );
            
            if (matchingFeatures.length > 0) {
                const featureNames = {
                    'bulk_messaging': 'Bulk WhatsApp messaging to all customers',
                    'automated_responses': 'AI-powered automatic responses',
                    'customer_lists': 'Advanced customer segmentation',
                    'promotional_campaigns': 'Promotional campaign tools',
                    'appointment_reminders': 'Automated appointment reminders',
                    'payment_notifications': 'Payment and order tracking'
                };
                
                let html = '';
                matchingFeatures.forEach(feature => {
                    html += `<li>${featureNames[feature] || feature}</li>`;
                });
                
                $('#recommended-features').html(html);
                $('#feature-preview').show();
            } else {
                $('#feature-preview').hide();
            }
        } else {
            $('#feature-preview').hide();
        }
    }
    
    // Navigation
    $('#next-btn').on('click', function() {
        if (currentStep < totalSteps) {
            currentStep++;
            showStep(currentStep);
        }
    });
    
    $('#back-btn').on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });
    
    // Complete registration
    $('#complete-btn').on('click', function() {
        // Collect all form data
        businessData = {
            business_type: selectedBusinessType,
            business_name: $('input[name="business_name"]').val(),
            owner_name: $('input[name="owner_name"]').val(),
            business_size: $('select[name="business_size"]').val(),
            customer_volume: $('select[name="customer_volume"]').val(),
            region: $('select[name="region"]').val(),
            years_in_business: $('select[name="years_in_business"]').val(),
            current_channels: [],
            business_goals: [],
            desired_features: [],
            budget_range: $('select[name="budget_range"]').val()
        };
        
        // Collect checkbox values
        $('input[name="current_channels[]"]:checked').each(function() {
            businessData.current_channels.push($(this).val());
        });
        
        $('input[name="business_goals[]"]:checked').each(function() {
            businessData.business_goals.push($(this).val());
        });
        
        $('input[name="desired_features[]"]:checked').each(function() {
            businessData.desired_features.push($(this).val());
        });
        
        // Set hidden form data and submit
        $('#business_data_json').val(JSON.stringify(businessData));
        
        // Show loading state
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating your account...');
        
        // Submit the form
        $('#registration-form').submit();
    });
    
    // Initialize
    showStep(1);
});
</script>

@endsection
