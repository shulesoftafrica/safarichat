<?php
$user = Auth::user();
$package = getPackage();

$try_period = $user->created_at;

$now = time();
$your_date = strtotime($try_period);
$datediff = $now - $your_date;
$days = round($datediff / (60 * 60 * 24));
$expired = 1;

$event= \App\Models\UsersEvent::where('user_id', $user->id)->first();

// Get user's current credits and subscription info
$currentCredits = $user->available_credits ?? 0;
$activeSubscription = $user->activeSubscription;
$missedOpportunities = 0; // This would be calculated from missed_automations table

// Detect user's country for payment method selection
$countryCode = $user->country_code ?? 'TZ';
$isInternational = !in_array($countryCode, ['TZ', 'KE', 'UG']);

// Get packages from database as per requirements
$adminPackages = \App\Models\AdminPackage::where('is_addon', 0)->get();
$packages = [];

foreach($adminPackages as $adminPkg) {
    // Get package features from admin_feature_packages table with values
    $packageFeatures = \DB::table('admin_feature_packages')
        ->join('admin_features', 'admin_feature_packages.admin_feature_id', '=', 'admin_features.id')
        ->where('admin_feature_packages.admin_package_id', $adminPkg->id)
        ->select('admin_features.name', 'admin_feature_packages.value', 'admin_feature_packages.description')
        ->get();
    
    // Process features with their values
    $featuresDisplay = [];
    foreach($packageFeatures as $feature) {
        $featureName = $feature->name;
        $featureValue = $feature->value;
        $featureDescription = $feature->description;
        
        // Determine display based on value
        if ($featureValue == '1') {
            $displayValue = '✓'; // Tick mark for enabled features
        } elseif ($featureValue == '0') {
            $displayValue = '✗'; // Cross mark for disabled features
        } else {
            // Show actual value for numeric features
            $displayValue = $featureValue;
        }
        
        // Use description if available, otherwise use feature name
        $displayName = !empty($featureDescription) ? $featureDescription : $featureName;
        
        $featuresDisplay[] = [
            'name' => $displayName,
            'value' => $displayValue,
            'enabled' => ($featureValue != '0')
        ];
    }
    
    // Map package types to recommended status
    $packageType = $adminPkg->package_type ?? 'winga';
    $isRecommended = ($packageType === 'pro');
    
    // Get pricing - use existing price field for TSH, calculate USD
    $priceTzs = (float) $adminPkg->price;
    $priceUsd = round($priceTzs / 2700, 0); // Approximate TSH to USD conversion
    
    // Format contacts and products display
    $maxContacts = $adminPkg->max_contacts ?? 0;
    $maxProducts = $adminPkg->max_products ?? 0;
    
    $contactsDisplay = ($maxContacts > 0) ? $maxContacts : 'Unlimited';
    $productsDisplay = ($maxProducts > 0) ? $maxProducts : 'Unlimited';
    
    $packages[] = [
        'id' => $adminPkg->id,
        'name' => $adminPkg->name,
        'package_type' => $packageType,
        'price_tzs' => $priceTzs,
        'price_usd' => $priceUsd,
        'contacts' => $contactsDisplay,
        'products' => $productsDisplay,
        'features' => !empty($featuresDisplay) ? $featuresDisplay : [
            ['name' => 'Basic features', 'value' => '✓', 'enabled' => true],
            ['name' => 'Customer support', 'value' => '✓', 'enabled' => true]
        ],
        'recommended' => $isRecommended
    ];
}

if(!empty($event)){
if (empty($package) && (int)is_trial()==0) {
    
    //check payments
    $expired = 1;
    if (!preg_match('/upgrade/', url()->current())) {
        ?>
        <div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0 rounded-3">
                <div class="modal-header bg-gradient-whatsapp text-white border-0 rounded-top">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle p-2 me-3">
                        <i class="mdi mdi-credit-card text-whatsapp fs-3"></i>
                    </div>
                    <div>
                        <h4 class="modal-title mb-0 fw-bold text-white" id="paymentModalLabel">Reactivate Your SafariChat Subscription</h4>
                        <small class="text-white-75">Choose a plan and resume your AI sales assistant</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-0">
                    <!-- Status Alert -->
                    <div class="status-alert-section p-4 border-bottom">
                        <div class="row align-items-center g-4">
                            <div class="col-md-6">
                                <div class="status-card credits-card">
                                    <div class="status-icon">
                                        <i class="mdi mdi-wallet fs-2"></i>
                                    </div>
                                    <div class="status-content">
                                        <h6 class="status-title">Credits Available</h6>
                                        <p class="status-description">
                                            You still have <span class="highlight-text"><?= number_format($currentCredits) ?> credits</span> waiting
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php if($missedOpportunities > 0): ?>
                            <div class="col-md-6">
                                <div class="status-card opportunities-card">
                                    <div class="status-icon">
                                        <i class="mdi mdi-account-multiple fs-2"></i>
                                    </div>
                                    <div class="status-content">
                                        <h6 class="status-title text-danger">Missed Opportunities</h6>
                                        <p class="status-description">
                                            <span class="highlight-text danger"><?= $missedOpportunities ?></span> potential customers waiting to be engaged
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Package Selection -->
                    <div class="p-4">
                        <h5 class="fw-bold mb-4 text-center">Select Your Plan</h5>
                        <div class="row g-4" id="packageSelection">
                            <?php foreach($packages as $pkg): ?>
                            <div class="col-md-6 col-lg-3">
                                <div class="card package-card h-100 border-2 position-relative" 
                                     data-package="<?= $pkg['id'] ?>"
                                     data-price-tzs="<?= $pkg['price_tzs'] ?>"
                                     data-price-usd="<?= $pkg['price_usd'] ?>"
                                     style="cursor: pointer; transition: all 0.3s;">
                                    
                                    <?php if($pkg['recommended']): ?>
                                    <div class="position-absolute top-0 start-50 translate-middle">
                                        <span class="badge bg-success px-3 py-1">Recommended</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-body text-center">
                                        <h5 class="card-title fw-bold text-whatsapp"><?= $pkg['name'] ?></h5>
                                        <div class="price-display mb-3">
                                            <?php if($isInternational): ?>
                                                <h3 class="text-primary mb-0">$<?= $pkg['price_usd'] ?></h3>
                                                <small class="text-muted">per month</small>
                                            <?php else: ?>
                                                <h3 class="text-primary mb-0">TSH <?= number_format($pkg['price_tzs']) ?></h3>
                                                <small class="text-muted">per month</small>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="limits mb-3">
                                            <div class="d-flex justify-content-between border-bottom py-1">
                                                <span>Contacts:</span>
                                                <strong><?= $pkg['contacts'] ?></strong>
                                            </div>
                                            <div class="d-flex justify-content-between py-1">
                                                <span>Products:</span>
                                                <strong><?= $pkg['products'] ?></strong>
                                            </div>
                                        </div>
                                        
                                        <ul class="list-unstyled small text-start">
                                            <?php foreach($pkg['features'] as $feature): ?>
                                            <li class="mb-1 d-flex justify-content-between align-items-center">
                                                <span class="<?= !$feature['enabled'] ? 'text-muted text-decoration-line-through' : '' ?>">
                                                    <?php if($feature['value'] === '✓'): ?>
                                                        <i class="mdi mdi-check text-success me-2"></i>
                                                    <?php elseif($feature['value'] === '✗'): ?>
                                                        <i class="mdi mdi-close text-danger me-2"></i>
                                                    <?php else: ?>
                                                        <i class="mdi mdi-circle-small text-primary me-2"></i>
                                                    <?php endif; ?>
                                                    <?= $feature['name'] ?>
                                                </span>
                                                <?php if($feature['value'] !== '✓' && $feature['value'] !== '✗'): ?>
                                                <strong class="text-primary"><?= $feature['value'] ?></strong>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Payment Method Selection -->
                    <div class="p-4 bg-light border-top" id="paymentMethodSection" style="display: none;">
                        <h5 class="fw-bold mb-4">Choose Payment Method</h5>
                        <div class="row g-3">
                            <!-- Lipa Number - Always available, recommended for Tanzania -->
                            <div class="col-md-6">
                                <div class="card payment-method-card border-2" data-method="lipa_number" style="cursor: pointer;">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-phone fs-1 text-success mb-3"></i>
                                        <h6 class="fw-bold">Lipa Number</h6>
                                        <p class="text-muted small mb-0">Pay with mobile money (M-Pesa, Tigo Pesa, etc.)</p>
                                        <?php if(!$isInternational): ?>
                                        <span class="badge bg-success mt-2">Recommended for Tanzania</span>
                                        <?php else: ?>
                                        <span class="badge bg-info mt-2">Available worldwide</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- Stripe - Always available -->
                            <div class="col-md-6">
                                <div class="card payment-method-card border-2" data-method="stripe" style="cursor: pointer;">
                                    <div class="card-body text-center">
                                        <i class="mdi mdi-credit-card fs-1 text-primary mb-3"></i>
                                        <h6 class="fw-bold">Credit/Debit Card</h6>
                                        <p class="text-muted small mb-0">Pay securely with Visa, Mastercard, etc.</p>
                                        <?php if($isInternational): ?>
                                        <span class="badge bg-primary mt-2">Recommended for International</span>
                                        <?php else: ?>
                                        <span class="badge bg-info mt-2">Alternative payment method</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lipa Number Payment -->
                    <div class="p-4 border-top" id="lipaNumberPayment" style="display: none;">
                        <div class="row justify-content-center">
                            <div class="col-lg-8 col-xl-6">
                                <!-- Payment Header -->
                                <div class="text-center mb-4">
                                    <div class="payment-icon-wrapper mb-3">
                                        <i class="mdi mdi-phone text-white fs-1"></i>
                                    </div>
                                    <h4 class="fw-bold text-dark mb-2">Mobile Money Payment</h4>
                                    <p class="text-muted mb-0">Send payment using your mobile money service</p>
                                </div>

                                <!-- Payment Instructions -->
                                <div class="payment-card mb-4">
                                    <div class="payment-card-header">
                                        <h6 class="mb-0 fw-semibold text-white">
                                            <i class="mdi mdi-numeric-1-circle me-2"></i>Send Payment
                                        </h6>
                                    </div>
                                    <div class="payment-card-body">
                                        <div class="amount-display mb-3">
                                            <span class="amount-label">Amount:</span>
                                            <span class="amount-value" id="lipaAmount">TSH 0</span>
                                        </div>
                                        <div class="lipa-number-display">
                                            <label class="form-label fw-semibold text-dark mb-2">Send to Lipa Number:</label>
                                            <div class="lipa-number-box">
                                                <span class="lipa-number">1086-9185</span>
                                                <button type="button" class="btn btn-sm btn-outline-primary copy-btn" onclick="copyLipaNumber()">
                                                    <i class="mdi mdi-content-copy"></i> Copy
                                                </button>
                                            </div>
                                        </div>
                                        <div class="payment-note">
                                            <i class="mdi mdi-information-outline text-info me-2"></i>
                                            <span>Your subscription will be automatically activated once payment is received.</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- QR Code Section -->
                                <div class="payment-card mb-4">
                                    <div class="payment-card-header">
                                        <h6 class="mb-0 fw-semibold text-white">
                                            <i class="mdi mdi-qrcode me-2"></i>QR Code Payment
                                        </h6>
                                    </div>
                                    <div class="payment-card-body text-center">
                                        <div class="qr-container">
                                            <div id="qrCodeContainer" class="qr-code-display">
                                                <div class="qr-placeholder">
                                                    <i class="mdi mdi-qrcode-scan fs-1 text-muted"></i>
                                                    <p class="text-muted mt-2 mb-0">QR Code will appear after package selection</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="qr-instruction">Scan with your mobile money app for easy payment</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="payment-actions">
                                    <div class="d-grid gap-3">
                                        <button id="payWithLipa" class="btn btn-primary btn-lg payment-btn">
                                            <i class="mdi mdi-phone me-2"></i>Initialize Lipa Payment
                                        </button>
                                        <button id="refreshPaymentStatus" class="btn btn-outline-primary btn-lg payment-btn">
                                            <i class="mdi mdi-refresh me-2"></i>Check Payment Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stripe Payment -->
                    <div class="p-4 border-top" id="stripePayment" style="display: none;">
                        <div class="text-center">
                            <h5 class="fw-bold mb-3">
                                <i class="mdi mdi-credit-card me-2 text-primary"></i>Secure Card Payment
                            </h5>
                            <p class="text-muted mb-4">You will be redirected to our secure payment processor</p>
                            
                            <div class="alert alert-info">
                                <strong>Payment Summary:</strong><br>
                                <span id="stripePackageName">Package Name</span> - <span id="stripeAmount">$0</span>/month
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button id="stripePaymentBtn" class="btn btn-primary btn-lg" disabled>
                                    <i class="mdi mdi-lock me-2"></i>Pay Securely with Stripe
                                </button>
                                <small class="text-muted">
                                    <i class="mdi mdi-shield-check me-1"></i>
                                    Secured by 256-bit SSL encryption
                                </small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Credit System Info -->
                    <div class="p-4 credit-info-section border-top">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="credit-info-content">
                                    <h6 class="fw-bold mb-3 text-dark">
                                        <i class="mdi mdi-information me-2 text-primary"></i>How Credits Work
                                    </h6>
                                    <div class="credit-rules">
                                        <div class="rule-item">
                                            <i class="mdi mdi-check-circle text-success me-2"></i>
                                            <span class="rule-text">1 TSH = 1 Credit = 4 AI Tokens</span>
                                        </div>
                                        <div class="rule-item">
                                            <i class="mdi mdi-check-circle text-success me-2"></i>
                                            <span class="rule-text">Credits roll over within active billing cycle</span>
                                        </div>
                                        <div class="rule-item">
                                            <i class="mdi mdi-check-circle text-success me-2"></i>
                                            <span class="rule-text">Extra payments become credit top-ups</span>
                                        </div>
                                        <div class="rule-item">
                                            <i class="mdi mdi-alert-circle text-warning me-2"></i>
                                            <span class="rule-text">Credits freeze when subscription is inactive</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="credit-balance-display">
                                    <div class="card credit-balance-card h-100">
                                        <div class="card-body text-center">
                                            <div class="balance-icon mb-2">
                                                <i class="mdi mdi-wallet fs-2"></i>
                                            </div>
                                            <div class="balance-label">Current Balance</div>
                                            <div class="balance-amount"><?= number_format($currentCredits) ?></div>
                                            <div class="balance-unit">Credits</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 rounded-bottom justify-content-center">
                    <small class="text-muted">
                        Need help? <a href="mailto:support@safarichat.africa" class="text-whatsapp">Contact Support: support@safarichat.africa</a>
                    </small>
                </div>
            </div>
            </div>
        </div>

        <style>
            /* Enhanced Payment Modal Styles */
            .bg-gradient-whatsapp {
                background: linear-gradient(135deg, #128c7e 0%, #25d366 50%, #34e89e 100%);
                box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            }
            
            .text-whatsapp {
                color: #075e54 !important;
            }
            
            .text-white-75 {
                color: rgba(255, 255, 255, 0.85) !important;
            }

            /* Modal Enhancements */
            .modal-content {
                border: none;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            }

            /* Enhanced Status Alert Styles */
            .status-alert-section {
                background: linear-gradient(135deg, #fefefe, #f8fafc);
            }

            .status-card {
                background: #ffffff;
                border-radius: 16px;
                padding: 1.5rem;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
                border: 1px solid #e2e8f0;
                display: flex;
                align-items: center;
                gap: 1rem;
                height: 100%;
                transition: transform 0.3s ease;
            }

            .status-card:hover {
                transform: translateY(-2px);
            }

            .credits-card {
                border-left: 4px solid #10b981;
            }

            .opportunities-card {
                border-left: 4px solid #ef4444;
            }

            .status-icon {
                flex-shrink: 0;
                width: 60px;
                height: 60px;
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
            }

            .credits-card .status-icon {
                background: linear-gradient(135deg, #10b981, #059669);
            }

            .opportunities-card .status-icon {
                background: linear-gradient(135deg, #ef4444, #dc2626);
            }

            .status-content {
                flex: 1;
                min-width: 0;
            }

            .status-title {
                font-size: 1rem;
                font-weight: 700;
                margin-bottom: 0.5rem;
                color: #1e293b;
            }

            .status-description {
                font-size: 0.9rem;
                color: #64748b;
                margin-bottom: 0;
                line-height: 1.5;
            }

            .highlight-text {
                font-weight: 700;
                color: #10b981;
            }

            .highlight-text.danger {
                color: #ef4444;
            }

            /* Package Cards */
            .package-card {
                transition: all 0.3s ease;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                background: #ffffff;
                overflow: hidden;
            }
            
            .package-card:hover {
                transform: translateY(-8px);
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
                border-color: #25d366;
            }
            
            .package-card.selected {
                border-color: #25d366 !important;
                background: linear-gradient(135deg, #f8fff9 0%, #e8f8ea 100%);
                transform: translateY(-8px);
                box-shadow: 0 15px 35px rgba(37, 211, 102, 0.2);
            }

            .package-card .card-body {
                padding: 1.5rem;
            }

            .package-card .card-title {
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 1rem;
            }

            .package-card .price-display h3 {
                font-size: 2rem;
                font-weight: 800;
                background: linear-gradient(135deg, #1e40af, #3b82f6);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .package-card .limits {
                background: #f8f9fa;
                border-radius: 8px;
                padding: 0.75rem;
                margin-bottom: 1rem;
            }

            .package-card .limits .d-flex {
                font-size: 0.9rem;
                padding: 0.25rem 0;
            }

            /* Payment Method Cards */
            .payment-method-card {
                transition: all 0.3s ease;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                cursor: pointer;
            }
            
            .payment-method-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                border-color: #6366f1;
            }
            
            .payment-method-card.selected {
                border-color: #25d366 !important;
                background: linear-gradient(135deg, #f8fff9 0%, #e8f8ea 100%);
                transform: translateY(-4px);
                box-shadow: 0 10px 25px rgba(37, 211, 102, 0.2);
            }

            /* Enhanced Payment Cards */
            .payment-icon-wrapper {
                width: 80px;
                height: 80px;
                border-radius: 20px;
                background: linear-gradient(135deg, #25d366, #128c7e);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
            }

            .payment-card {
                border: none;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
                background: #ffffff;
            }

            .payment-card-header {
                background: linear-gradient(135deg, #4f46e5, #6366f1);
                padding: 1rem 1.5rem;
                color: white;
            }

            .payment-card-body {
                padding: 1.5rem;
            }

            /* Amount Display */
            .amount-display {
                background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
                border-radius: 12px;
                padding: 1rem;
                text-align: center;
            }

            .amount-label {
                font-size: 0.9rem;
                color: #64748b;
                display: block;
                margin-bottom: 0.5rem;
            }

            .amount-value {
                font-size: 1.8rem;
                font-weight: 800;
                color: #0f172a;
                display: block;
            }

            /* Lipa Number Display */
            .lipa-number-display {
                margin-top: 1rem;
            }

            .lipa-number-box {
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: #f8fafc;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                padding: 1rem 1.25rem;
                margin-top: 0.5rem;
            }

            .lipa-number {
                font-size: 1.5rem;
                font-weight: 700;
                color: #0f172a;
                font-family: 'Monaco', 'Menlo', monospace;
                letter-spacing: 0.1em;
            }

            .copy-btn {
                font-size: 0.875rem;
                padding: 0.375rem 0.75rem;
                border-radius: 8px;
            }

            /* Payment Note */
            .payment-note {
                background: #f0f9ff;
                border: 1px solid #bae6fd;
                border-radius: 8px;
                padding: 0.75rem 1rem;
                margin-top: 1rem;
                font-size: 0.9rem;
                color: #0369a1;
            }

            /* QR Code Section */
            .qr-container {
                padding: 1rem;
            }

            .qr-code-display {
                width: 200px;
                height: 200px;
                margin: 0 auto;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #ffffff;
            }

            .qr-placeholder {
                text-align: center;
            }

            .qr-instruction {
                margin-top: 1rem;
                font-size: 0.9rem;
                color: #64748b;
                margin-bottom: 0;
            }

            /* Action Buttons */
            .payment-actions {
                margin-top: 2rem;
            }

            .payment-btn {
                font-weight: 600;
                padding: 0.875rem 1.5rem;
                border-radius: 12px;
                font-size: 1rem;
                transition: all 0.3s ease;
                border-width: 2px;
            }

            .payment-btn.btn-primary {
                background: linear-gradient(135deg, #4f46e5, #6366f1);
                border-color: #4f46e5;
            }

            .payment-btn.btn-primary:hover {
                background: linear-gradient(135deg, #4338ca, #4f46e5);
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(79, 70, 229, 0.3);
            }

            .payment-btn.btn-outline-primary {
                border-color: #6366f1;
                color: #6366f1;
            }

            .payment-btn.btn-outline-primary:hover {
                background: #6366f1;
                border-color: #6366f1;
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            }

            /* Credit Info Section */
            .credit-info-section {
                background: linear-gradient(135deg, #f0fdf4, #dcfce7);
                border-radius: 16px;
                padding: 1.5rem;
            }

            .credit-balance-card {
                background: linear-gradient(135deg, #16a34a, #22c55e);
                border-radius: 12px;
                color: white;
                box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
            }

            /* Typography Enhancements */
            h4, h5, h6 {
                font-weight: 700;
                line-height: 1.3;
            }

            .small, small {
                font-size: 0.875rem;
                line-height: 1.5;
            }

            /* Form Controls */
            .form-label {
                font-weight: 600;
                color: #334155;
                margin-bottom: 0.5rem;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .modal-xl {
                    margin: 0.5rem;
                }
                
                .payment-card-body {
                    padding: 1rem;
                }
                
                .amount-value {
                    font-size: 1.5rem;
                }
                
                .lipa-number {
                    font-size: 1.25rem;
                }
                
                .lipa-number-box {
                    flex-direction: column;
                    gap: 0.75rem;
                }
            }

            /* Loading States */
            .btn:disabled {
                opacity: 0.7;
                cursor: not-allowed;
            }

            .mdi-loading {
                animation: spin 1s linear infinite;
            }

            /* Enhanced Credit Info Styles */
            .credit-info-content {
                background: #ffffff;
                border-radius: 12px;
                padding: 1.5rem;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            }

            .credit-rules {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
            }

            .rule-item {
                display: flex;
                align-items: flex-start;
                padding: 0.5rem 0;
            }

            .rule-text {
                font-size: 0.95rem;
                color: #475569;
                line-height: 1.5;
                font-weight: 500;
            }

            .credit-balance-display {
                height: 100%;
            }

            .balance-icon {
                opacity: 0.9;
            }

            .balance-label {
                font-size: 0.85rem;
                opacity: 0.9;
                font-weight: 500;
                margin-bottom: 0.25rem;
            }

            .balance-amount {
                font-size: 1.75rem;
                font-weight: 800;
                line-height: 1.2;
            }

            .balance-unit {
                font-size: 0.9rem;
                opacity: 0.8;
                font-weight: 500;
            }

            /* JavaScript Functions */
        </style>
        
        <script type="text/javascript">
            // Copy Lipa Number function
            function copyLipaNumber() {
                const lipaNumber = '1086-9185';
                navigator.clipboard.writeText(lipaNumber).then(function() {
                    // Show success feedback
                    const copyBtn = document.querySelector('.copy-btn');
                    const originalText = copyBtn.innerHTML;
                    copyBtn.innerHTML = '<i class="mdi mdi-check"></i> Copied!';
                    copyBtn.classList.add('btn-success');
                    copyBtn.classList.remove('btn-outline-primary');
                    
                    setTimeout(() => {
                        copyBtn.innerHTML = originalText;
                        copyBtn.classList.remove('btn-success');
                        copyBtn.classList.add('btn-outline-primary');
                    }, 2000);
                }, function() {
                    // Fallback for older browsers
                    alert('Lipa Number: ' + lipaNumber);
                });
            }
        
        <script type="text/javascript">
            let selectedPackage = null;
            let selectedPaymentMethod = null;
            
            $(window).on('load', function () {
                $('#payment_model').modal({backdrop: 'static', keyboard: false, show: true});
            });
            
            // Package selection
            $('.package-card').click(function() {
                $('.package-card').removeClass('selected');
                $(this).addClass('selected');
                
                selectedPackage = {
                    id: $(this).data('package'),
                    price_tzs: $(this).data('price-tzs'),
                    price_usd: $(this).data('price-usd'),
                    name: $(this).find('.card-title').text()
                };
                
                $('#paymentMethodSection').slideDown();
                updatePaymentInfo();
            });
            
            // Payment method selection
            $('.payment-method-card').click(function() {
                $('.payment-method-card').removeClass('selected');
                $(this).addClass('selected');
                
                selectedPaymentMethod = $(this).data('method');
                
                // Hide all payment forms
                $('#lipaNumberPayment, #stripePayment').hide();
                
                // Show selected payment form
                if (selectedPaymentMethod === 'lipa_number') {
                    $('#lipaNumberPayment').slideDown();
                } else if (selectedPaymentMethod === 'stripe') {
                    $('#stripePayment').slideDown();
                }
                
                updatePaymentInfo();
            });
            
            function updatePaymentInfo() {
                if (!selectedPackage) return;
                
                const isInternational = <?= $isInternational ? 'true' : 'false' ?>;
                const price = isInternational ? selectedPackage.price_usd : selectedPackage.price_tzs;
                const currency = isInternational ? 'USD' : 'TSH';
                
                // Update Lipa Number payment info
                $('#lipaAmount').text('TSH ' + new Intl.NumberFormat().format(selectedPackage.price_tzs));
                $('#selectedPackageId').val(selectedPackage.id);
                $('#selectedAmount').val(selectedPackage.price_tzs);
                $('#amount_paid').attr('placeholder', selectedPackage.price_tzs);
                
                // Update Stripe payment info
                $('#stripePackageName').text(selectedPackage.name);
                if (isInternational) {
                    $('#stripeAmount').text('$' + selectedPackage.price_usd);
                } else {
                    $('#stripeAmount').text('TSH ' + new Intl.NumberFormat().format(selectedPackage.price_tzs));
                }
                $('#stripePaymentBtn').prop('disabled', false);
                
                // Generate QR code (placeholder - would integrate with actual QR service)
                generateQRCode();
            }
            
            function generateQRCode() {
                // This would integrate with actual QR code generation service
                $('#qrCodeContainer').html(`
                    <div class="text-center p-3">
                        <div style="width: 150px; height: 150px; background: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd;">
                            <span style="font-size: 12px; color: #666;">QR Code<br>TSH ${new Intl.NumberFormat().format(selectedPackage.price_tzs)}</span>
                        </div>
                    </div>
                `);
            }
            
            // Lipa Number form submission (removed - now using automatic verification)
            
            // Initialize Lipa Number payment
            $('#payWithLipa').click(function() {
                if (!selectedPackage) return;
                
                const btn = $(this);
                const originalText = btn.html();
                btn.html('<i class="mdi mdi-loading mdi-spin"></i> Initializing...').prop('disabled', true);
                
                $.ajax({
                    url: '<?= url('payment/initialize') ?>',
                    method: 'POST',
                    data: {
                        _token: '<?= csrf_token() ?>',
                        package_id: selectedPackage.id,
                        amount: selectedPackage.price_tzs,
                        gateway: 'lipa_number'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Payment initialized! Please send the amount to the Lipa Number shown above.');
                            // You could update the QR code here with actual data from response
                        } else {
                            alert('Failed to initialize payment: ' + (response.message || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Unable to initialize payment. Please try again.');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Payment status check
            $('#refreshPaymentStatus').click(function() {
                if (!selectedPackage) return;
                
                const btn = $(this);
                const originalText = btn.html();
                btn.html('<i class="mdi mdi-loading mdi-spin"></i> Checking...').prop('disabled', true);
                
                $.ajax({
                    url: '<?= url('payment/check-status') ?>',
                    method: 'POST',
                    data: {
                        _token: '<?= csrf_token() ?>',
                        package_id: selectedPackage.id,
                        amount: selectedPackage.price_tzs
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Payment confirmed! Your subscription is now active.');
                            location.reload();
                        } else {
                            alert('Payment not yet received. Please try again in a few moments.');
                        }
                    },
                    error: function() {
                        alert('Unable to check payment status. Please try again.');
                    },
                    complete: function() {
                        btn.html(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Stripe payment button
            $('#stripePaymentBtn').click(function() {
                if (!selectedPackage) return;
                
                const btn = $(this);
                const originalText = btn.html();
                btn.html('<i class="mdi mdi-loading mdi-spin"></i> Redirecting...').prop('disabled', true);
                
                // Create a form to submit to the payment initialize route
                const form = $('<form>')
                    .attr('method', 'POST')
                    .attr('action', '<?= url('payment/initialize') ?>')
                    .hide();
                
                form.append($('<input>').attr('type', 'hidden').attr('name', '_token').val('<?= csrf_token() ?>'));
                form.append($('<input>').attr('type', 'hidden').attr('name', 'package_id').val(selectedPackage.id));
                form.append($('<input>').attr('type', 'hidden').attr('name', 'amount').val(selectedPackage.price_tzs));
                form.append($('<input>').attr('type', 'hidden').attr('name', 'gateway').val('stripe'));
                form.append($('<input>').attr('type', 'hidden').attr('name', 'currency').val('usd'));
                
                $('body').append(form);
                form.submit();
            });
        </script>
    <?php }
}
}?>