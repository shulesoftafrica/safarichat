@extends('layouts.app')
@section('content')
<div class="container">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Profile</a></li>
                        <li class="breadcrumb-item active">Pricing</li>
                    </ol>
                </div>
                <h4 class="page-title">Pricing</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <!--    <div class="row"><div class="col-lg-9"></div><div class="col-lg-3">
                <p align="right"><a href="#" class="btn btn-warning btn-block btn-skew btn-outline-dashed py-2" role="alert" data-toggle="modal" data-target="#discount">Request a Discount</a></p>
            </div>
        </div>-->
    <?php
    $total_discount = 0;
    //  $user_discounts = \App\Models\DiscountRequest::where('user_id', Auth::user()->id)->get();
    // if (!empty($user_discounts)) {
    if (false) {
        ?>
        <div class="table-responsive">
            <table class="table  table-bordered dataTable" id="makeEditable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Phone Requested</th>
                        <th>Status</th>
                        <th>Discount Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total_discount = 0;
                    $i = 1;

                    foreach ($user_discounts as $discount) {
                        $total_discount += $discount->status == 0 ? 0 : 5000;
                        ?>
                        <tr>
                            <td>{{$i}}</td>
                            <td><?= date('d M Y H:i', strtotime($discount->created_at)) ?></td>
                            <td>{{$discount->phone}}</td>
                            <td>{{$discount->status==0 ? 'Not Registered':'Registered'}}</td>
                            <td>{{$discount->status==0 ? 0:5000}}</td>
                        </tr>
                        <?php
                        $i++;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th>Total</th>
                        <th></th>
                        <th></th>
                        <th name="buttons">Tsh {{number_format($total_discount)}}</th>
                    </tr>
            </table>

        </div>
    <?php } ?>
    <div class="row">

        <?php
        $get_paid_package = getPackage();
       
        $available_package_id = 0;
        if (!empty($get_paid_package)) {
           // $available_package_id = $get_paid_package->admin_package_id;
        }
        $i=1;
        foreach ($packages as $package) {
           //  if($i ==2)                 continue;
            ?>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                         <!--<span class="badge badge-pink a-animate-blink mt-0">POPULAR</span>-->
                        <div class="pricingTable1 text-center">
                            <h6 class="title1"><?= $package->name ?></h6>
                            <p class="text-muted px-3 mb-0"><?= $package->description ?>.</p>
                            <div class="p-1 m-2">
                                <h3 class="amount amount-border d-inline-block">Tsh <?= number_format($package->price - $total_discount) ?></h3>
                                <small class="font-12 text-muted">/Once Off</small>
                            </div>
                            <hr class="hr-dashed">
                            <ul class="list-unstyled pricing-content-1 text-left py-1 border-0 mb-3">
                                <?php
                                $features = \App\Models\AdminFeaturePackage::whereAdminPackageId($package->id)->get();
                                foreach ($features as $feature) {
                                    if(in_array($feature->adminFeature->id, [1,3,8,7,8,11,6,9])) {
                                        continue;
                                    }
                                    ?>
                                    <li><?= $feature->value == '0' ? '<i class="typcn typcn-times" style="font-size: 18px;
    color: red;"></i>' : '<i class="typcn typcn-tick" style="font-size: 18px;
    color: green;"></i>' ?> <?= $feature->value . ' - ' . $feature->description ?></li>
                                <?php } ?>

                            </ul>
                            <?php
                          //  if ($package->id != $available_package_id) {
                                ?>
                                <form  action="<?= url('home/createAddonInvoice/' . $package->id) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" value="<?= $package->id ?>" name="package_id"/>
                                    <input type="hidden" name="amount" value="<?= $package->price - $total_discount ?>">
                                    <button type="submit" class="btn btn-primary btn-block btn-skew btn-outline-dashed py-2">Select the Plan</button>
                                </form>
                            <?php // } ?>
                        </div><!--end pricingTable-->
                    </div><!--end card-body-->
                </div> <!--end card-->                                   
            </div><!--end col-->
        <?php $i++; } ?>

    </div><!--end row-->

    <!--    <div class="row"><div class="col-lg-9"></div><div class="col-lg-3">
                <p align="right"><a href="#" class="btn btn-warning btn-block btn-skew btn-outline-dashed py-2"  role="alert" data-toggle="modal" data-target="#discount">Request a Discount</a></p>
            </div>
        </div>-->

</div>


<div class="modal fade" id="discount" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="exampleModalLabel">Get your discount</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="pricingTable1 text-center">
                    <p>Invite user(s) who has an event (wedding, send-off etc) and get a discount of Tsh 5,000 per each user who sign up</p>

                    <div class="p-3 m-2">
                        <h3 class="amount amount-border d-inline-block">Tsh 5,000</h3>
                        <small class="font-12 text-muted">/User</small>
                    </div>
                </div>
                <div class="form-group"  id="discount_content">
                    <label for="code_verification">Enter phone Number of the one you invite</label>
                    <div class="input-group">
                        <input type="text" id="phone"  name="phone" class="form-control" placeholder="Valid Phone Number">
                        <span class="input-group-append">
                            <button type="button" class="btn  btn-sm btn-primary" id="get_discount">Submit</button>
                        </span>
                    </div>                                                    
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    load_contact = function () {
        $.getJSON('https://www.google.com/m8/feeds/contacts/default/full/?access_token=' +
                authResult.access_token + "&alt=json&callback=?", function (result) {
                    console.log(JSON.stringify(result));
                });
    }
    //  $(document).ready(load_contact);
    get_discount = function () {
        $('#get_discount').mousedown(function () {
            var phone = $('#phone').val();
            if (phone !== '') {
                $.ajax({
                    type: 'POST',
                    url: "<?= url('payment/createDiscount/null') ?>",
                    data: {phone: phone},
                    dataType: "html",
                    success: function (data) {
                        $('#discount_content').html(data);
                    }
                });
            }
        });
    }
    $(document).ready(get_discount);
</script>
@endsection