@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Service</a></li>
                        <li class="breadcrumb-item active">Search</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Search for Service Providers</h4>
                    <p class="text-muted mb-3">Find your best search provider for your event</p>
                    <div class="search-filter wadding-vanues-filter">
                        <div class="container">
                            <form class="filter-form" action="<?= url()->current() . '/null' ?>" method="GET">
                                <div class="row">
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-6">
                                        <label>Looking For</label>
                                        <select id="" name="service_id" class="form-control select2" style="width:100% !important" itemid="service_id">
                                            <?php
                                            foreach ($services as $service) {
                                                ?>
                                                <option value="<?= $service->id ?>"><?= $service->name ?></option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                                        <label>Within My</label>
                                        <select class="form-control" name="within">
                                            <option value="ward">Ward</option>
                                            <option value="district">District</option>
                                            <option value="region">Region</option>
                                            <option value="country">Country</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                                        <label>Order By</label>
                                        <select class="form-control" name="within">
                                            <option value="low_price">Lowest Price</option>
                                            <option value="high_price">Highest Price</option>
                                            <option value="most_rated">Most Rated</option>
                                            <option value="lead_rated">Least Rated</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-2 col-sm-6 col-6 d-flex">
                                        <label></label>
                                        <?= csrf_field() ?>
                                        <button class="btn btn-success btn-xs" type="submit"> Search</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <br/>


                    <hr/>
                    <?php if (isset($businesses) && !empty($businesses)) { ?>

                        <!-- Page-Title -->
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="page-title-box">                          
                                    <h4 class="page-title">Blogs</h4>
                                </div><!--end page-title-box-->
                            </div><!--end col-->
                        </div>
                        <!-- end page title end breadcrumb -->
                        <div class="row">
                            <?php
                            foreach ($businesses as $business) {
                                ?>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="blog-card">
                                                <img src="../assets/images/small/img-9.jpg" alt="" class="img-fluid">
                                                <span class="badge badge-purple px-3 py-2 bg-soft-secondary font-weight-semibold mt-3"><?=$business->business->name?></span>   
                                                <h4 class="my-3">
                                                    <a href="" class=""><?=$business->service_name?></a>
                                                </h4>
                                                <p class="text-muted"><?=$business->details?>.</p>
                                                <hr class="hr-dashed">
                                                <div class="d-flex justify-content-between">
                                                    <div class="meta-box">
                                                        <div class="media">
                                                            <img src="../assets/images/users/user-1.png" alt="" class="thumb-sm rounded-circle mr-2">                                       
                                                            <div class="media-body align-self-center text-truncate">
                                                                <h6 class="mt-0 mb-1 text-dark">Donald Gardner</h6>
                                                                <ul class="p-0 list-inline mb-0">
                                                                    <li class="list-inline-item">26 mars 2020</li>
                                                                    <li class="list-inline-item">by <a href="">admin</a></li>
                                                                </ul>
                                                            </div><!--end media-body-->
                                                        </div>                                            
                                                    </div><!--end meta-box-->
                                                    <div class="align-self-center">
                                                        <a href="#" class="text-primary">Read more <i class="fas fa-long-arrow-alt-right"></i></a>
                                                    </div>
                                                </div>                                        
                                            </div><!--end blog-card--> 

                                        </div><!--end card-body-->
                                    </div><!--end card-->
                                </div> <!--end col-->
                            <?php } ?>
                         
                         
                        </div><!--end row-->


                        <?php
                    } else {
                        echo '<div class="alert alert-info">No Search Results </div>';
                    }
                    ?>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row --> 

</div>

<script type="text/javascript">
    function editGuest(a) {
        $('#edit_amount').val($('#guest_pledge' + a).text());
        $('#edit_payment_date').val($('#guest_date' + a).text());
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        // $('#edit_service').val($('#guest_name'+a).text())
        $('#edit_descriptions').val($('#guest_note' + a).text());
        $('#ProfileStep5').attr('action', '<?= url('expense/edit/null') ?>');
        var option = $('<option></option>').attr("value", a).text($('#guest_name' + a).text());
        $("#edit_service").empty().append(option);
        $('#add_inputs').html('<input type="hidden" value="' + a + '" name="id"/>');
    }
    load_contact = function () {
        $.getJSON('https://www.google.com/m8/feeds/contacts/default/full/?access_token=' +
                authResult.access_token + "&alt=json&callback=?", function (result) {
                    console.log(JSON.stringify(result));
                });
    }
    //  $(document).ready(load_contact);
</script>
@endsection

