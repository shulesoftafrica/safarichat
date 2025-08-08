<?php
$package = getPackage();

$try_period = Auth::user()->created_at;

$now = time();
$your_date = strtotime($try_period);
$datediff = $now - $your_date;
$days = round($datediff / (60 * 60 * 24));
$expired = 1;

$event= \App\Models\UsersEvent::where('user_id',Auth::user()->id)->first();

if(!empty($event)){
if (empty($package) && (int)is_trial()==0) {
    
    //check payments
    $expired = 1;
    if (!preg_match('/upgrade/', url()->current())) {
        ?>
        <div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0 rounded-3">
                <div class="modal-header bg-gradient-whatsapp text-dark border-0 rounded-top">
                <div class="d-flex align-items-center w-100">
                    <div class="bg-white rounded-circle p-2 me-3 border border-success">
                    <!-- <img src="{{ asset('images/payment-icon.svg') }}" alt="Payment" width="40" height="40"> -->
                    </div>
                    <div>
                    <h4 class="modal-title mb-0 fw-bold text-dark" id="paymentModalLabel">Activate Your Monthly Subscription</h4>
                    <small class="text-dark-50">Enjoy uninterrupted access to all premium features</small>
                    </div>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                <div class="text-center mb-4">
                    <span class="badge bg-success fs-6 mb-2">Monthly Plan</span>
                    <h2 class="fw-bold text-whatsapp mb-1">TSH 50,000 <small class="fs-6 text-muted">/ month</small></h2>
                    <div class="text-muted mb-2">3 days free trial for new users</div>
                </div>
                <form id="paymentForm" action="<?= url('payment/verify') ?>" method="POST" autocomplete="off">
                    @csrf
                    <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Step 1: Send Payment</label>
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-2">
                        <i class="mdi mdi-phone fs-4 text-whatsapp"></i>
                        <div>
                        Send <strong class="text-whatsapp">TSH 50,000</strong> to <span class="text-whatsapp fw-bold">LIPA NAMBA: 1086-9185</span>
                        <br>
                        <small class="text-dark">After payment, you'll receive an SMS with a Receipt/Ref Number.</small>
                        </div>
                    </div>
                    </div>
                    <div class="mb-3">
                    <label for="reference_number" class="form-label fw-semibold text-dark">Step 2: Enter Reference Number</label>
                    <input type="text" 
                           class="form-control form-control-lg rounded-pill border-success" 
                           id="reference_number" 
                           name="reference_number" 
                           placeholder="e.g. MPESA123ABC"
                           required>
                    <small class="form-text text-muted">Enter the transaction reference number from your payment SMS.</small>
                    </div>
                    <div class="mb-3">
                    <label for="amount_paid" class="form-label fw-semibold text-dark">Amount Paid (TSH)</label>
                    <input type="number" 
                           class="form-control form-control-lg rounded-pill border-success" 
                           id="amount_paid" 
                           name="amount_paid" 
                           placeholder="50000"
                           min="1"
                           required>
                    <small class="form-text text-muted">Enter the exact amount you sent.</small>
                    </div>
                    <div class="mb-3">
                    <div class="alert alert-light border border-success shadow-sm">
                        <div class="d-flex align-items-center justify-content-between">
                        <strong class="text-whatsapp">Payment Rules</strong>
                        <a href="#" id="showPaymentRules" class="text-decoration-underline small text-whatsapp">Read more</a>
                        </div>
                        <ul class="mb-0 mt-2 ps-3 small" id="paymentRulesList" style="display:none; color: #000;">
                            <li style="color: #000;">Monthly subscription: <strong class="text-whatsapp">TSH 50,000</strong></li>
                            <li style="color: #000;">Payments below <strong class="text-whatsapp">TSH 50,000</strong> will not activate service</li>
                            <li style="color: #000;">Extra payments will be credited to future months</li>
                            <li style="color: #000;">New users get <strong class="text-whatsapp">3 days free trial</strong></li>
                        </ul>
                    </div>
                    </div>
                    <div class="d-grid">
                    <button type="submit" class="btn btn-whatsapp btn-lg rounded-pill shadow-sm">
                        <i class="mdi mdi-check-circle-outline me-1"></i> Verify & Activate
                    </button>
                    </div>
                </form>
                </div>
                <div class="modal-footer bg-light border-0 rounded-bottom justify-content-center">
                <small class="text-muted">Need help? <a href="mailto:support@safarichat.africa" class="text-whatsapp">Contact Support : support@safarichat.africa</a></small>
                </div>
            </div>
            </div>
        </div>
        <style>
            .bg-gradient-whatsapp {
            background: linear-gradient(90deg, #075e54 0%, #25d366 100%);
            }
            .text-whatsapp {
            color: #075e54 !important;
            }
            .btn-whatsapp {
            background: linear-gradient(90deg, #25d366 0%, #075e54 100%);
            color: #fff !important;
            border: none;
            }
            .btn-whatsapp:hover {
            background: linear-gradient(90deg, #128c7e 0%, #25d366 100%);
            color: #fff !important;
            }
            .modal-content {
            border-radius: 1.25rem;
            }
            .form-control-lg {
            font-size: 1.1rem;
            padding: 0.75rem 1.25rem;
            }
            .rounded-pill {
            border-radius: 50rem !important;
            }
            .border-success {
            border-color: #25d366 !important;
            }
            .alert-success {
            background-color: #e7f9f1;
            color: #075e54;
            border-color: #25d366;
            }
            .bg-success {
            background-color: #25d366 !important;
            }
            .fw-bold {
            font-weight: 700 !important;
            }
            .fw-semibold {
            font-weight: 600 !important;
            }
        </style>
        <script>
            $(document).on('click', '#showPaymentRules', function(e) {
            e.preventDefault();
            $('#paymentRulesList').slideToggle();
            $(this).text(function(i, text){
                return text === "Read more" ? "Hide rules" : "Read more";
            });
            });
        </script>
        
        <script type="text/javascript">
            $(window).on('load', function () {
                $('#payment_model').modal({backdrop: 'static', keyboard: false, show: true});
            });
            
            $('#paymentForm').on('submit', function(e) {
                e.preventDefault();
                
                var referenceNumber = $('#reference_number').val();
                var amountPaid = $('#amount_paid').val();
                
                if (!referenceNumber || !amountPaid) {
                    alert('Please fill in all required fields');
                    return;
                }
                
                // Show loading state
                var submitBtn = $('button[type="submit"]');
                var originalText = submitBtn.html();
                submitBtn.html('<i class="mdi mdi-loading mdi-spin"></i> Verifying...').prop('disabled', true);
                
                // Submit form
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            alert('Payment verified successfully! Your subscription is now active.');
                            location.reload();
                        } else {
                            alert('Payment verification failed: ' + response.message);
                            submitBtn.html(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                        submitBtn.html(originalText).prop('disabled', false);
                    }
                });
            });
        </script>
    <?php }
}
}?>