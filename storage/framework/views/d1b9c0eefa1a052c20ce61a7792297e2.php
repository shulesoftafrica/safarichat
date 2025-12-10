
<?php $__env->startSection('content'); ?>

<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Profile</a></li>
                        <li class="breadcrumb-item active">settings</li>
                    </ol>
                </div>
                <h4 class="page-title">General Settings</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">List of items to be set</h4>
                    <p class="text-muted mb-3">Put the correct setting value to get the best out of the system
                    </p>


                    <div class="row">
                        <div class="col-sm-3">
                            <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <a class="nav-link waves-effect waves-light active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">User Accounts</a>
                                <a class="nav-link waves-effect waves-light " id="v-pills-subscription-tab" data-toggle="pill" href="#v-pills-subscription" role="tab" aria-controls="v-pills-subscription" aria-selected="false">Subscription & Billing</a>
                                <a class="nav-link waves-effect waves-light " id="v-pills-settings-tab" data-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings" aria-selected="false">Customer Category</a>
                                <a class="nav-link waves-effect waves-light " id="v-pills-business-tab" data-toggle="pill" href="#v-pills-business" role="tab" aria-controls="v-pills-business" aria-selected="false">Business Settings</a>
                            </div>
                        </div>
                        <div class="col-sm-9">
                            <div class="tab-content mo-mt-2" id="v-pills-tabContent">
                                <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="table-responsive">
                                        <h4 class="mt-0 header-title">Manage User Accounts</h4>
                                        <p class="text-muted mb-3">Each user account is able to login, and manage  activities, view reports and much more.. </p>
                                        <div>
                                            <p> 
                    

                                            </p>
                                        </div>
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Date Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($user_accounts as $account) {
                                                    ?>
                                                    <tr>
                                                        <th><?= $i ?></th>
                                                        <th><?= $account->user->name ?></th>
                                                        <th><?= $account->user->email ?></th>
                                                        <th><?= $account->user->phone ?></th>
                                                        <th><?= date('d M Y', strtotime($account->user->created_at)) ?></th>
                                                        <th>
                                                            <?php
                                                            if ($account->user->id == Auth::user()->id) {
                                                                ?>
                                                                <a onclick="editGuest('<?= $account->user->id ?>')" data-toggle="modal" href="#user_accounts"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php } ?>
                                                            <!-- <a href="#"> <i class="las la-trash-alt text-danger font-18"></i> Delete</a> -->
                                                        </th>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Subscription & Billing Tab -->
                                <div class="tab-pane fade" id="v-pills-subscription" role="tabpanel" aria-labelledby="v-pills-subscription-tab">
                                    <h4 class="mt-0 header-title">Subscription & Billing</h4>
                                    <p class="text-muted mb-3">Manage your subscription, view usage, and handle billing</p>
                                    
                                    <!-- Current Subscription Status -->
                                    <div class="row mb-4">
                                        <div class="col-lg-8">
                                            <div class="card">
                                                <div class="card-body">
                                                    <h5 class="card-title">Current Plan</h5>
                                                    <?php if(Auth::user()->subscription_status === 'trial'): ?>
                                                        <div class="alert alert-info">
                                                            <h6><i class="las la-clock"></i> Free Trial Active</h6>
                                                            <p>Trial expires: <?php echo e(Auth::user()->trial_ends_at ? Auth::user()->trial_ends_at->format('M d, Y H:i') : 'N/A'); ?></p>
                                                            <p>Days remaining: <?php echo e(Auth::user()->trial_ends_at ? Auth::user()->trial_ends_at->diffInDays(now()) : 0); ?></p>
                                                        </div>
                                                    <?php elseif(Auth::user()->subscription_status === 'active'): ?>
                                                        <div class="alert alert-success">
                                                            <h6><i class="las la-check-circle"></i> Active Subscription</h6>
                                                            <?php $activeSubscription = Auth::user()->activeSubscription; ?>
                                                            <p>Plan: <strong><?php echo e($activeSubscription->adminPackage->name ?? 'N/A'); ?></strong></p>
                                                            <p>Next billing: <?php echo e($activeSubscription->ends_at ? $activeSubscription->ends_at->format('M d, Y') : 'N/A'); ?></p>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="alert alert-warning">
                                                            <h6><i class="las la-exclamation-triangle"></i> Subscription Inactive</h6>
                                                            <p>Your subscription has expired. Reactivate to continue using AI features.</p>
                                                            <button class="btn btn-primary btn-sm" onclick="showPaywallModal()">Reactivate Now</button>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Credit Balance -->
                                                    <div class="mt-3">
                                                        <h6>Available Credits</h6>
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo e(min(100, (Auth::user()->available_credits / 1000) * 100)); ?>%">
                                                                <?php echo e(Auth::user()->available_credits); ?> Credits
                                                            </div>
                                                        </div>
                                                        <small class="text-muted">1 Credit = 4 AI Tokens</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-4">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <h5>Quick Actions</h5>
                                                    <button class="btn btn-primary btn-block mb-2" onclick="showUpgradeModal()">
                                                        <i class="las la-arrow-up"></i> Upgrade Plan
                                                    </button>
                                                    <button class="btn btn-success btn-block mb-2" onclick="showCreditTopup()">
                                                        <i class="las la-plus"></i> Buy Credits
                                                    </button>
                                                    <button class="btn btn-info btn-block" onclick="showBillingHistory()">
                                                        <i class="las la-history"></i> Billing History
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Package Comparison -->
                                    <div class="row">
                                        <div class="col-12">
                                            <h5>Available Packages</h5>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>Package</th>
                                                            <th>Price (Monthly)</th>
                                                            <th>Contacts</th>
                                                            <th>Products</th>
                                                            <th>Features</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td><strong>Winga</strong></td>
                                                            <td>$29/month</td>
                                                            <td>50 contacts</td>
                                                            <td>3 products</td>
                                                            <td>Basic reporting</td>
                                                            <td><button class="btn btn-sm btn-primary" onclick="selectPackage('winga', 29)">Select</button></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Pro</strong></td>
                                                            <td>$149/month</td>
                                                            <td>150 contacts</td>
                                                            <td>50 products</td>
                                                            <td>Full reporting</td>
                                                            <td><button class="btn btn-sm btn-primary" onclick="selectPackage('pro', 149)">Select</button></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Enterprise</strong></td>
                                                            <td>$399/month</td>
                                                            <td>300 contacts</td>
                                                            <td>200 products</td>
                                                            <td>Full reporting + Priority support</td>
                                                            <td><button class="btn btn-sm btn-primary" onclick="selectPackage('enterprise', 399)">Select</button></td>
                                                        </tr>
                                                        <tr>
                                                            <td><strong>Corporate</strong></td>
                                                            <td>Custom pricing</td>
                                                            <td>Unlimited</td>
                                                            <td>Unlimited</td>
                                                            <td>API access + Custom workflows + Dedicated support</td>
                                                            <td><button class="btn btn-sm btn-info" onclick="contactSales()">Contact Sales</button></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                    <h4 class="mt-0 header-title">Business Settings</h4>
                                    <p class="text-muted mb-3">Configure your business information and preferences.</p>
                                    <p>Business settings are now managed through the dedicated Business Profile section.</p>
                                </div>
                                <div class="tab-pane fade" id="v-pills-business" role="tabpanel" aria-labelledby="v-pills-business-tab">
                                    <form class="form-parsley"  novalidate="" action="<?php echo e(url('home/settings')); ?>" method="post">
                                        <div class="form-group">
                                            <label>Business Name</label>
                                            <input type="text" class="form-control" name="name" value="<?php echo e($business->name ?? ''); ?>" placeholder="Business Name">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo e($business->email ?? ''); ?>" placeholder="Business Email">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Phone</label>
                                            <input type="text" class="form-control" name="phone" value="<?php echo e($business->phone ?? ''); ?>" placeholder="Business Phone">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Business Description</label>
                                            <textarea class="form-control" name="descriptions" rows="4" placeholder="Describe your business"><?php echo e($business->descriptions ?? ''); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Website URL</label>
                                            <input type="url" class="form-control" name="website" value="<?php echo e($business->website ?? ''); ?>" placeholder="https://example.com">
                                        </div>

                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-success waves-effect waves-light">
                                                Save Business Settings
                                            </button>
                                            <input type="hidden" value="business" name="table"/>
                                            <?= csrf_field() ?>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane fade " id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                    <h4 class="mt-0 header-title">Customer Categories</h4>
                                    <p class="text-muted mb-3">Manage list of Customer categories</p>
                                    <p>  <button type="button" class="btn btn-success" data-toggle="modal" data-target="#myModal">
                                            Add New Category
                                        </button></p>
                                    <!--<button type="button" class="btn btn-gradient-primary waves-effect waves-light" id="ajax-alert">Click me</button>-->
                                    <br/>
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Event Name</th>
                                                    <th>Customer Category</th>
                                                    <th>Total Customer</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($categories as $category) {
                                                    // Check if category has a business, fallback to 0 if no business
                                                    $total_guests = $category->business ? $category->business->businessGuests()->where('event_guest_category_id', $category->id)->count() : 0;
                                                    ?>
                                                    <tr>
                                                        <th scope="row"><?= $i ?></th>
                                                        <th><?= $category->business ? $category->business->name : 'Legacy Category' ?></th>
                                                        <td><span id="category<?= $category->id ?>"><?= $category->name ?></span></td>
                                                        <th><?= $total_guests ?></th>
                                                        <td> 
                                                            <a onclick="editGuest('<?= $category->id ?>')" data-toggle="modal" href="#myModal"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php
                                                            if ((int) $total_guests == 0) {
                                                                ?>
                                                                <a href="<?= url('guest/guestcategory/' . $category->id) ?>"><i class="las la-trash-alt text-danger font-18"></i> Delete</a>
                                                            <?php } else { ?>
                                                                <button type="button" class="btn btn-outline-light uitooltip" data-toggle="tooltip" data-placement="top" title="There are Customers in this category, You cannot delete. Delete first customers in this category if you want to delete it">
                                                                    <i class="las la-trash-alt text-danger font-18"></i> Delete
                                                                </button>
                                                            <?php } ?>

                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>
                                        </table><!--end /table-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!--end card-body-->
            </div><!--end card-->
        </div> <!-- end col -->
    </div> <!-- end row -->

</div>
<div class="modal fade planner-modal-bx" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Category Name</label>
                        <input type="text" name="name" id="edit_guest_name" class="form-control" placeholder="Customer Category Name" required="">
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="event_guest_category" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>

<div class="modal fade planner-modal-bx" id="user_accounts" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="<?= url('home/settings') ?>" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Edit your information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">


                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Name</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group1" value="<?= Auth::user()->name ?>" name="name" class="form-control" placeholder="Name">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Email</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->email ?>" name="email" class="form-control" placeholder="Email">
                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Phone</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->phone ?>" name="phone" class="form-control" placeholder="Phone">

                        </div>                                                    
                    </div>


                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="user" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>
<!-- Sweet-Alert  -->
<!--<script src="../plugins/sweet-alert2/sweetalert2.min.js"></script>
<script src="../assets/pages/jquery.sweet-alert.init.js"></script>-->

<script type="text/javascript">
    function editGuest(a) {
        $('#edit_guest_name').val($('#category' + a).text());
        $('#edit_id').val(a);
    }
    
    function setCriteria(value) {
        return false;
    }
    
    // Subscription Management Functions
    function showPaywallModal() {
        checkSubscriptionStatus().then(data => {
            if (data.status === 'inactive' || data.status === 'expired') {
                createPaywallModal(data);
            }
        });
    }
    
    function showUpgradeModal() {
        $('#packageSelectionModal').modal('show');
    }
    
    function showCreditTopup() {
        $('#creditTopupModal').modal('show');
    }
    
    function showBillingHistory() {
        $('#billingHistoryModal').modal('show');
        loadBillingHistory();
    }
    
    function selectPackage(packageType, price) {
        if (confirm(`Upgrade to ${packageType.toUpperCase()} package for $${price}/month?`)) {
            initiatePayment(packageType, price);
        }
    }
    
    function contactSales() {
        alert('Contact sales@shulesoft.africa for custom Enterprise pricing');
    }
    
    async function checkSubscriptionStatus() {
        try {
            const response = await fetch('/api/subscription/status', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            return await response.json();
        } catch (error) {
            console.error('Error checking subscription:', error);
            return { status: 'error' };
        }
    }
    
    function createPaywallModal(data) {
        const modalHTML = `
            <div class="modal fade" id="paywallModal" tabindex="-1" role="dialog" aria-labelledby="paywallModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-warning">
                            <h5 class="modal-title" id="paywallModalLabel">
                                <i class="las la-exclamation-triangle"></i> Reactivate Your AI Sales Agent
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <h6><i class="las la-info-circle"></i> Your Credits Are Waiting!</h6>
                                <p>You still have <strong>${data.available_credits || 0} credits</strong> available.</p>
                            </div>
                            
                            ${data.missed_automations && data.missed_automations.length > 0 ? `
                                <div class="alert alert-warning">
                                    <h6><i class="las la-clock"></i> Missed Opportunities Today:</h6>
                                    <ul class="mb-0">
                                        ${data.missed_automations.map(item => `<li>${item.type}: ${item.target}</li>`).join('')}
                                    </ul>
                                </div>
                            ` : ''}
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Choose Your Package:</h6>
                                    <div class="list-group">
                                        <div class="list-group-item">
                                            <strong>Winga - $29/month</strong><br>
                                            50 contacts, 3 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('winga', 29)">Select</button>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Pro - $149/month</strong><br>
                                            150 contacts, 50 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('pro', 149)">Select</button>
                                        </div>
                                        <div class="list-group-item">
                                            <strong>Enterprise - $399/month</strong><br>
                                            300 contacts, 200 products
                                            <button class="btn btn-sm btn-primary float-right" onclick="selectPackage('enterprise', 399)">Select</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6>Payment Method:</h6>
                                    ${data.country_code === 'TZ' ? `
                                        <div class="text-center">
                                            <h6>Lipa Namba Payment</h6>
                                            ${data.payment_options && data.payment_options.qr_code ? 
                                                `<img src="${data.payment_options.qr_code}" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">` : 
                                                '<div class="alert alert-info">QR code will be generated after package selection</div>'
                                            }
                                            ${data.payment_options && data.payment_options.merchant_id ? 
                                                `<p><strong>Lipa Namba:</strong> ${data.payment_options.merchant_id}</p>` : 
                                                ''
                                            }
                                        </div>
                                    ` : `
                                        <div class="text-center">
                                            <h6>International Payment</h6>
                                            <p>Secure payment via Stripe</p>
                                            <i class="las la-credit-card" style="font-size: 48px; color: #007bff;"></i>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-success" onclick="checkPaymentStatus()">
                                <i class="las la-sync"></i> Check Payment Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        $('#paywallModal').remove();
        $('body').append(modalHTML);
        $('#paywallModal').modal('show');
    }
    
    async function initiatePayment(packageType, price) {
        try {
            const response = await fetch('/subscription/initiate-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ package: packageType, amount: price })
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (result.gateway === 'stripe') {
                    window.location.href = result.checkout_url;
                } else {
                    // Update Lipa Number details in modal
                    updateLipaNumberDetails(result.merchant_id, result.qr_code);
                }
            } else {
                alert('Payment initiation failed: ' + result.message);
            }
        } catch (error) {
            console.error('Payment initiation error:', error);
            alert('Failed to initiate payment. Please try again.');
        }
    }
    
    function updateLipaNumberDetails(merchantId, qrCode) {
        // Update the modal with new payment details
        const paymentSection = document.querySelector('#paywallModal .col-md-6:last-child');
        if (paymentSection) {
            paymentSection.innerHTML = `
                <h6>Payment Method:</h6>
                <div class="text-center">
                    <h6>Lipa Namba Payment</h6>
                    <img src="${qrCode}" alt="QR Code" class="img-fluid mb-2" style="max-width: 200px;">
                    <p><strong>Lipa Namba:</strong> ${merchantId}</p>
                    <div class="alert alert-info">
                        <small>Scan QR code or use Lipa Namba number above to complete payment</small>
                    </div>
                </div>
            `;
        }
    }
    
    async function checkPaymentStatus() {
        const btn = document.querySelector('[onclick="checkPaymentStatus()"]');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="las la-spinner la-spin"></i> Checking...';
        btn.disabled = true;
        
        try {
            const response = await fetch('/subscription/check-payment-status', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                $('#paywallModal').modal('hide');
                location.reload(); // Refresh page to show updated subscription
            } else {
                alert('Payment not yet received. Please complete payment and try again.');
            }
        } catch (error) {
            console.error('Payment check error:', error);
            alert('Failed to check payment status.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    
    async function loadBillingHistory() {
        try {
            const response = await fetch('/subscription/billing-history', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            const history = await response.json();
            
            let historyHTML = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th></tr></thead><tbody>';
            
            if (history.length > 0) {
                history.forEach(payment => {
                    historyHTML += `
                        <tr>
                            <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                            <td>${payment.description}</td>
                            <td>$${payment.amount}</td>
                            <td><span class="badge badge-${payment.status === 'completed' ? 'success' : 'warning'}">${payment.status}</span></td>
                        </tr>
                    `;
                });
            } else {
                historyHTML += '<tr><td colspan="4" class="text-center">No billing history found</td></tr>';
            }
            
            historyHTML += '</tbody></table></div>';
            
            document.getElementById('billingHistoryContent').innerHTML = historyHTML;
        } catch (error) {
            console.error('Error loading billing history:', error);
            document.getElementById('billingHistoryContent').innerHTML = '<div class="alert alert-danger">Failed to load billing history</div>';
        }
    }
    
    // Auto-check subscription status on page load
    document.addEventListener('DOMContentLoaded', function() {
        checkSubscriptionStatus().then(data => {
            if (data.status === 'inactive' || data.status === 'expired') {
                // Show a smaller notification instead of full modal on page load
                const notification = document.createElement('div');
                notification.className = 'alert alert-warning alert-dismissible fade show';
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.innerHTML = `
                    <strong>Subscription Inactive!</strong> Your AI agent is not responding to customers.
                    <button class="btn btn-sm btn-primary ml-2" onclick="showPaywallModal()">Reactivate</button>
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                `;
                document.body.appendChild(notification);
                
                // Auto-hide after 10 seconds
                setTimeout(() => notification.remove(), 10000);
            }
        });
    });
</script>

<!-- Subscription Modals -->
<div class="modal fade" id="billingHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Billing History</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="billingHistoryContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="creditTopupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buy Credits</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Purchase additional credits for AI conversations</p>
                <div class="form-group">
                    <label>Credit Amount</label>
                    <select class="form-control" id="creditAmount">
                        <option value="100">100 Credits - $25</option>
                        <option value="500">500 Credits - $100</option>
                        <option value="1000">1000 Credits - $180</option>
                        <option value="2000">2000 Credits - $320</option>
                    </select>
                </div>
                <p class="text-muted"><small>1 Credit = 4 AI Tokens</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="buyCreditPackage()">Purchase Credits</button>
            </div>
        </div>
    </div>
</div>

<script>
function buyCreditPackage() {
    const amount = document.getElementById('creditAmount').value;
    const prices = { 100: 25, 500: 100, 1000: 180, 2000: 320 };
    const price = prices[amount];
    
    if (confirm(`Purchase ${amount} credits for $${price}?`)) {
        initiatePayment('credit_topup', price);
    }
}
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\safarichat\resources\views/auth/settings.blade.php ENDPATH**/ ?>