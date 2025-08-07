@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('home') }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('category') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('budget') }}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">{{ __('event_budget') }}</h4>
                    <p class="text-muted mb-3">{{ __('manage_your_budget_by_adding_new_item_edit_or_delete_the_item_below') }}</p>
                    <p>  <button type="button" class="btn btn-success" data-toggle="modal" data-target="#exampleModal">
                           {{ __('add_new_item') }}
                        </button></p>
                    <br/>
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 table-centered dataTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('item_name') }}</th>
                                    <th>{{ __('quantity') }}</th>
                                     <th>{{ __('unit_price') }}</th>
                                    <th>{{ __('total_budget_amount') }}</th>
                                    <th>{{ __('status') }}</th>
                                    <th>{{ __('paid_amount') }}</th>
                                    <th>{{ __('remained') }}</th>
                                    <th>{{ __('action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                $budget_price = 0;
                                $paid_total = 0;
                                $remained_total = 0;
                                $haystack = [];
                                foreach ($budgets as $budget) {

                                    $paid = $budget->budgetPayments()->sum('amount');
                                    $remains = $budget->actual_price - $paid;

                                    $paid_total += $paid;
                                    $remained_total += $remains;

                                    $budget_price += $budget->actual_price;
                                    array_push($haystack, $budget->businessService->service_id);
                                    ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td>
                                            <span id="service_name<?= $budget->id ?>">
                                                <?= isset($budget->service) ? $budget->service->name : '' ?>
                                            </span>
                                        </td>
                                        <td><span id="quantity_value<?= $budget->id ?>"><?= $budget->quantity ?></span></td>
                                        <td><span id="unit_price<?= $budget->id ?>"><?= number_format($budget->actual_price/$budget->quantity) ?></span></td>
                                        <td><span id="total_price<?= $budget->id ?>" style="display:none"><?= ($budget->actual_price) ?></span><?= number_format($budget->actual_price) ?></td>
                                        <td><span class="badge badge-soft-<?= (int) $budget->approved == 1 ? 'success' : 'warning' ?>">
                                                <?= (int) $budget->approved == 1 ? __('approved') : __('not_approved') ?>
                                            </span></td>
                                        <td><?= number_format($paid) ?></td>
                                        <td><?= number_format($budget->actual_price - $budget->budgetPayments()->sum('amount')) ?></td>
                                        <td>
                                            <a href="#"  onclick="editGuest('<?= $budget->id ?>','<?=$budget->businessService->service_id?>')"  data-toggle="modal" data-target="#modal_edit"><i class="las la-pen text-info font-18"></i></a>
                                            <a href="<?= url('budget/destroy/' . $budget->id) ?>" onclick="return confirm('Are you sure you want to delete this record?');"><i class="las la-trash-alt text-danger font-18"></i></a>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4">{{ __('total') }}</td>
                                    <td><?= number_format($budget_price) ?></td>
                                    <td></td>
                                    <td><?= number_format($paid_total) ?></td>
                                    <td><?= number_format($remained_total) ?></td>
                                    <td colspan="1">
                                    </td>
                                </tr>
                            </tfoot>

                            </tbody>
                        </table><!--end /table-->
                    </div><!--end /tableresponsive-->
                </div><!--end card-body-->
            </div>
        </div>
    </div>
</div>

<div class="modal fade planner-modal-bx" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModal" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form action="<?= url('budget/create/null') ?>" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('add_new_budget_item') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <label for="service" class=" col-form-label text-right">{{ __('service') }}</label>
                            <div class="form-group">
                                <select id="service_id" name="service_id" class="form-control select2" style="width:100% !important" itemid="service_id">
                                    <?php
                                    foreach ($services as $service) {
                                        if (!in_array($service->id, $haystack)) {
                                            ?>
                                            <option value="<?= $service->id ?>"><?= $service->name ?></option>
                                            <?php
                                        }
                                    }
                                    ?>
                                </select>

                            </div>
                        </div>
                        <!-- <div class="col-md-12">
                            <label for="service" class=" col-form-label text-right">{{ __('service_provider') }}</label>
                            <div class="form-group">
                                <select id="business_service_id" name="business_service_id" required="" class="form-control select2" style="width:100% !important" itemid="business_service_id">

                                </select>
                                <a class="label label-default mb-2 mb-lg-0 badge badge-success" onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                                    {{ __('add_new_service_provider') }}
                                </a> <i class="dripicons-arrow-thin-right arrow"></i> <i class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                                <div class="collapse hide" id="collapseExample" style="">
                                    <div class="card mb-0 card-body">
                                        <p class="mb-0 text-muted">{{ __('provide_details_here') }}</p> 

                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label text-right">{{ __('name') }}</label>
                                            <div class="col-sm-10">
                                                <input class="form-control   @error('business_name') is-invalid @enderror" id="business_name" type="text" name="business_name" value="<?= old('business_name') ?>" >
                                                @error('business_name')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                                <span class="hint">{{ __('valid_business_name_or_common_name_that_people_identify') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="example-email-input" class="col-sm-2 col-form-label text-right">{{ __('phone') }}</label>
                                            <div class="col-sm-10">
                                                <input class="form-control   @error('business_phone') is-invalid @enderror" type="tel" name="business_phone" value="<?= old('business_phone') ?>" id="business_phone-input">
                                                @error('business_phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div> 
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label text-right">{{ __('major_service') }}</label>
                                            <div class="col-sm-10">
                                                <select class="form-control service_id" id="service_id_provider" name="service_id">
                                                    <option>{{ __('select') }}</option>
                                                    <?php
                                                    $services = \App\Models\Service::all();
                                                    foreach ($services as $service) {
                                                        ?>
                                                        <option value="<?= $service->id ?>"><?= $service->name ?></option>
                                                    <?php }
                                                    ?>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <input   value="{{ __('save_changes') }}" type="submit"  class="btn btn-success add_provider" onclick="return false">
                                        </div>

                                        <div class="alert icon-custom-alert alert-outline-warning" role="alert"> 
                                            {{ __('if_this_business_exists_and_the_business_owner_creates_dikodiko_account_you_will_get_a_discount_of_tsh_5000') }} </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                        <div class="col-md-6">
                            <label for="quantity" class="col-sm-2 col-form-label text-right">{{ __('quantity') }}</label>
                            <div class="form-group">
                                <input type="number" class="form-control" required="" name="quantity" placeholder="{{ __('quantity') }}" value="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="col-sm-4 col-form-label text-right">{{ __('unit_price') }}</label>
                            <div class="form-group">
                                <input type="number" class="form-control" required="" name="unit_price" placeholder="{{ __('price_per_item') }}">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                    <button value="Save" type="submit" class="btn btn-primary">{{ __('save') }}</button>
                </div>
            </div>
        </form>
    </div>

</div>

<div class="modal fade planner-modal-bx" id="modal_edit" tabindex="-1" role="dialog" aria-labelledby="exampleModal" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form action="<?= url('budget/edit/null') ?>" id="ProfileStep5" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('edit_budget_item') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-12">
                            <label for="service" class=" col-form-label text-right">{{ __('service') }}</label>
                            <div class="form-group">
                                <input type="text" class="form-control" value="" id="service_name" required="" disabled="" placeholder="{{ __('estimated_price') }}">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label for="service" class=" col-form-label text-right">{{ __('service_provider') }}</label>
                            <div class="form-group">
                                <select id="business_service_id_edit" name="business_service_id" class="form-control select2" style="width:100% !important" itemid="business_service_id">
                                   
                                </select>
                                <a class="label label-default mb-2 mb-lg-0 badge badge-success" onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                                    {{ __('add_new_service_provider') }}
                                </a> <i class="dripicons-arrow-thin-right arrow"></i> <i class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                                <div class="collapse hide" id="collapseExample" style="">
                                    <div class="card mb-0 card-body">
                                        <p class="mb-0 text-muted">{{ __('provide_details_here') }}</p> 

                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-sm-2 col-form-label text-right">{{ __('name') }}</label>
                                            <div class="col-sm-10">
                                                <input class="form-control   @error('business_name') is-invalid @enderror" id="business_name_edit"  type="text" name="business_name" value="<?= old('business_name') ?>">
                                                @error('business_name')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                                <span class="hint">{{ __('valid_business_name_or_common_name_that_people_identify') }}</span>
                                            </div>
                                        </div>

                                        <div class="form-group row">
                                            <label for="example-email-input" class="col-sm-2 col-form-label text-right">{{ __('phone') }}</label>
                                            <div class="col-sm-10">
                                                <input class="form-control   @error('business_phone') is-invalid @enderror" type="tel" name="business_phone" value="<?= old('business_phone') ?>" id="business_phone-input_edit">
                                                @error('business_phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div>
                                        </div> 
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label text-right">{{ __('major_service') }}</label>
                                            <div class="col-sm-10">
                                                <select class="form-control " id="service_id_provider_edit" name="service_id">
                                                    <option>{{ __('select') }}</option>
                                                    <?php
                                                    $services = \App\Models\Service::all();
                                                    foreach ($services as $service) {
                                                        ?>
                                                        <option value="<?= $service->id ?>"><?= $service->name ?></option>
                                                    <?php }
                                                    ?>

                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <input   value="{{ __('save_changes') }}" type="submit"   class="btn btn-success add_provider" onclick="return false">
                                        </div>

                                        <div class="alert icon-custom-alert alert-outline-warning" role="alert"> 
                                            We might call this business later to verify its existance, though it will not affect your account</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="col-sm-2 col-form-label text-right">Quantity</label>
                            <div class="form-group">
                                <input type="number" class="form-control" id="quantity_value" required="" name="quantity" placeholder="Quantity" value="1">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="price" class="col-sm-4 col-form-label text-right">Unit Price</label>
                            <div class="form-group">
                                <input type="number" class="form-control" id="unit_price" required="" name="unit_price" placeholder="Price per Item">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <?= csrf_field() ?>
                    <input type='hidden' id="tag_id" value="" name='tag_id'/>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button value="Save" type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>

</div>
<script type="text/javascript">
    function editGuest(a,b) {
        $('#service_name').val($('#service_name' + a).text());
        $('#quantity_value').val($('#quantity_value' + a).text());
        // Extract the string, remove commas, then convert to float
        var unitPriceStr = $('#unit_price' + a).text().replace(/,/g, '');
        $('#unit_price').val(parseFloat(unitPriceStr));
        $('#edit_service').val($('#guest_name' + a).text());
        $('#tag_id').val(a);
        $('#ProfileStep5').attr('action', '<?= url('budget/edit') ?>'+'/'+a);
        get_business_service(b,'business_service_id_edit');
    }
    get_businesses = function () {
        $('#service_id').change(function () {
            var val = $(this).val();
            get_business_service(val);
        });

    }
    get_business_service = function (val,tag='business_service_id') {
        $.ajax({
            type: 'POST',
            url: "<?= url('budget/getBusinessService') ?>/" + val,
            data: "service_id=" + val,
            dataType: "html",
            success: function (data) {
                $('#'+tag).html(data);
            }
        });
    }
    add_provider = function () {
        $('.add_provider').mousedown(function () {
            var service_id = $('#service_id_provider').val();
            var name = $('#business_name').val();
            var phone = $('#business_phone-input').val();

            $.ajax({
                type: 'POST',
                url: "<?= url('home/registerBusinessService') ?>/" + service_id,
                data: {service_id: service_id, name: name, phone: phone},
                dataType: "html",
                success: function (data) {
                    $('#collapseExample').removeClass('show').after(data);
                    get_business_service(service_id);
                }
            });
        })
    }
     add_provider2 = function () {
        $('.add_provider').mousedown(function () {
            var service_id = $('#service_id_provider_edit').val();
            var name = $('#business_name_edit').val();
            var phone = $('#business_phone-input_edit').val();

            $.ajax({
                type: 'POST',
                url: "<?= url('home/registerBusinessService') ?>/" + service_id,
                data: {service_id: service_id, name: name, phone: phone},
                dataType: "html",
                success: function (data) {
                    $('#collapseExample').removeClass('show').after(data);
                    get_business_service(service_id);
                }
            });
        })
    }
    $(document).ready(get_businesses);
    $(document).ready(add_provider);
     $(document).ready(add_provider2);
</script>
@endsection