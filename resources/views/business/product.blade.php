@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <br/>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Business</a></li>
                        <li class="breadcrumb-item active">Service</li>
                    </ol>
                    <br/>
                </div> <br/> <br/>
                <hr/>
                <h4 class="page-title">Business Service</h4>
                <hr/>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <!-- end page title end breadcrumb -->
    <p>  <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#myModal">
            Add New Service
        </button></p>
    <br/>
    <div class="row">

        <?php
        $haystack = [];
        if (!empty($services)) {

            foreach ($services as $service) {
                array_push($haystack, $service->service->id);
                ?>
                <div class="col-md-4">
                    <div class="card h-100 d-flex flex-column align-items-stretch" style="min-height: 480px;">
                        <div class="card-body d-flex flex-column justify-content-between" style="height: 430px;">
                            <?php if ($service->offer) { ?>
                                <div class="ribbon1 rib1-warning">
                                    <span class="text-white text-center rib1-warning"><?= $service->offer ?> off</span>                                        
                                </div>
                            <?php } ?>
                            <?php
                                $images = explode(',', $service->images);
                                $image = !empty($images[0]) ? trim($images[0]) : null;
                            ?>
                            <div class="d-flex justify-content-center align-items-center mb-3" style="height: 170px;">
                                <img src="<?= $image ? url($image) : asset('images/pic3.png') ?>" alt="" style="max-height: 160px; max-width: 160px; object-fit: contain;">
                            </div>
                            <div class="d-flex justify-content-between align-items-center my-3 flex-grow-1">
                                <div>
                                    <p class="text-muted mb-2"><?=  $service->service->name ?></p>
                                    <h5><?=$service->service_name ?></h5>
                                    <a href="#" class="header-title"><?= $service->service->descriptions ?></a>
                                </div>
                                <div>
                                    <h4 class="text-dark mt-0 mb-2">Tsh <?= number_format($service->price) ?></h4>  
                                </div>      
                            </div> 
                            <div class="mt-auto">
                                <a class="btn btn-outline-info btn-block mb-2" href="<?= url('business/product/view/' . $service->service_id) ?>">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a class="btn btn-outline-primary btn-block mb-2" href="<?= url('business/product/edit/' . $service->service_id) ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form action="<?= url('business/product/delete/' . $service->service->id) ?>" method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <?= method_field('DELETE') ?>
                                    <button type="submit" class="btn btn-outline-danger btn-block mb-2" onclick="return confirm('Are you sure you want to delete this service?');">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                                <?php
                                $promotion = $service->promotions()->sum('total_users');
                                $total_reaches = \App\Models\PromotionReach::whereIn('promotion_id', $service->promotions()->get(['id']))->count();
                                if ((int) $promotion > (int) $total_reaches) {
                                    // <a class="btn btn-soft-warning btn-block" href="#">Promoted</a>
                                } else { ?>
                                    {{-- <a class="btn btn-soft-success btn-block" href="<?= url('business/promote/' . $service->service_id) ?>">Promote</a> --}}
                              <?php  } 
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>

    </div>  <!--end row-->

</div>
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog">
        <form class="modal-content start-here" id="ProfileStep5" action="<?= url('business/createService') ?>" method="post" method="POST" enctype="multipart/form-data" >

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Create a new Service</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Service Category</label>
                        <select class="form-control select2" name="service_id" id="edit_service" style="width:100%">

                            <?php
                            $more_services = \App\Models\Service::all();
                            foreach ($more_services as $more_service) {
                                if (!in_array($more_service->id, $haystack)) {
                                    ?>
                                    <option value="<?= $more_service->id ?>"><?= $more_service->name ?></option>
                                    <?php
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Service Name</label>
                        <input type="text" name="service_name" id="edit_payment_date" required="" class="form-control" placeholder="">
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Service Descriptions</label>
                        <textarea name="details" id="edit_descriptions" required="" class="form-control" placeholder="Explain how the best your service is"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Standard Price</label>
                        <input type="number" name="price" id="edit_amount" class="form-control" required="" placeholder="">
                    </div>
                    <div class="form-group">
                        <label for="image" class="col-form-label text-right">Product Image</label>
                        <input type="file" name="images[]" id="image" class="form-control" accept="image/*" multiple="true">
                    </div>

                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <span id="add_inputs"></span>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>

@endsection