@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{__('home')}}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{__('category')}}</a></li>
                        <li class="breadcrumb-item active">{{__('expenses')}}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">{{__('expense_history')}}</h4>
                    <p class="text-muted mb-3">{{__('list_of_expenses_done_to_support')}} <?= Auth::user()->name ?></p>
                    <p>  <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#myModal"  onclick=" $('#ProfileStep5').attr('action', '<?= url('expense/store/null') ?>');">
                            {{__('record_expense')}}
                        </button></p>
                    <br/>


                    <table  class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{__('name')}}</th>
                                <th>{{__('paid_amount')}}</th>
                                <th>{{__('payment_date')}}</th>
                                <th>{{__('payment_method')}}</th>
                                <th>{{__('expense_info')}}</th>
                                <th>{{__('actions')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_amount = 0;
                            $total_remains = 0;
                            $remains = 0;
                            $i = 1;
                            foreach ($budget_payments as $payment) {
                               
                                if(empty($payment->budget->service)) {
                                    continue; // Skip if service is not set
                                }
                                 $total_amount += $payment->amount;
                                $total_remains += $remains;
                                ?>
                                <tr>
                                    <td tabindex="1"><?= $i ?></td>
                                    <td tabindex="1"><span id="guest_name<?= $payment->id ?>">{{$payment->budget->service->name}}</span></td>
                                    <td tabindex="1"><span id="guest_pledge<?= $payment->id ?>" style="display: none;">{{$payment->amount}}</span>{{number_format($payment->amount)}}</td>
                                    <td tabindex="1"><span id="guest_date<?= $payment->id ?>" style="display: none;"><?= $payment->date ?></span><?= $payment->date ?></td>
                                    <td tabindex="1"><?= $payment->method ?></td>
                                    <td tabindex="1"><span id="guest_note<?= $payment->id ?>" style="display: none;"><?= $payment->note ?></span><?= $payment->note ?></td>
                                    <td tabindex="1">

                                        <a onclick="editGuest('<?= $payment->id ?>')" data-toggle="modal" href="#myModal"><i class="las la-pen text-info font-18"></i> {{__('edit')}}</a>
                                        <a href="<?= url('expense/destroy/' . $payment->id) ?>">
                                            <i class="las la-trash-alt text-danger font-18"></i> {{__('delete')}}</a></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            ?>


                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2"><strong>{{__('total')}}</strong></th>
                                <th><?= number_format($total_amount) ?></th>
                                <th colspan="2"></th>
                                <th></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row --> 

</div>


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog">
        <form class="modal-content start-here" id="ProfileStep5" action="" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{__('manage_expenses')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('service_name')}}</label>
                        <select class="form-control select2" name="service_id" id="edit_service" style="width:100%">
                            <?php foreach ($services as $service) { ?>
                                <option value="<?= $service->id ?>"><?= $service->name ?></option>
                            <?php } ?>
                        </select>
                    </div>
                   
                        <div class="form-group">
                             <label for="service" class=" col-form-label text-right">{{__('service_provider')}}</label>
                            <select id="business_service_id" name="business_service_id"  class="form-control select2" style="width:100% !important" itemid="business_service_id">

                            </select>
                            <a class="label label-default mb-2 mb-lg-0 badge badge-success" onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                                {{__('add_new_service_provider')}}
                            </a> <i class="dripicons-arrow-thin-right arrow"></i> <i class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                            <div class="collapse hide" id="collapseExample" style="">
                                <div class="card mb-0 card-body">
                                    <p class="mb-0 text-muted">{{__('provide_details_here')}}</p> 

                                    <div class="form-group row">
                                        <label for="example-text-input" class="col-sm-2 col-form-label text-right">{{__('name')}}</label>
                                        <div class="col-sm-10">
                                            <input class="form-control   @error('business_name') is-invalid @enderror" id="business_name" type="text" name="business_name" value="<?= old('business_name') ?>" >
                                            @error('business_name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                            <span class="hint">{{__('valid_business_name_or_common_name_that_people_identify')}}</span>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="example-email-input" class="col-sm-2 col-form-label text-right">{{__('phone')}}</label>
                                        <div class="col-sm-10">
                                            <input class="form-control   @error('business_phone') is-invalid @enderror" type="tel" name="business_phone" value="<?= old('business_phone') ?>" id="business_phone-input">
                                            @error('business_phone')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div> 
                                  
                                    <div class="modal-footer">
                                        <input   value="{{__('save_changes')}}" type="submit"  class="btn btn-success add_provider" onclick="return false">
                                    </div>

                                    <div class="alert icon-custom-alert alert-outline-warning" role="alert"> 
                                        {{__('if_this_business_exists_and_the_business_owner_creates_dikodiko_account_you_will_get_a_discount_of_tsh_5000')}} </div>
                                </div>
                            </div>
                        </div>
                  
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('paid_amount')}}</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required="" placeholder="{{__('amount_paid')}}">
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('payment_date')}}</label>
                        <input type="date" name="date" id="edit_payment_date" required="" class="form-control" placeholder="{{__('payment_date')}}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('payment_method')}}</label>
                        <select class="form-control" name="method">
                            <option value="M-pesa">{{__('m_pesa')}}</option>
                            <option value="Tigo-pesa">{{__('tigo_pesa')}}</option>
                            <option value="Airtel Money">{{__('airtel_money')}}</option>
                            <option value="Bank">{{__('bank_transfer')}}</option>
                            <option value="Others">{{__('others')}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">{{__('payment_descriptions')}}</label>
                        <textarea name="note" id="edit_expense_descriptions" required="" class="form-control" placeholder="{{__('any_descriptions_about_the_expense')}}"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= time() ?>" name="transaction_id"/>
                <span id="add_inputs"></span>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('close')}}</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">{{__('save')}}</button>
            </div>
        </form>


    </div>
</div>
<script type="text/javascript">
    function editGuest(a) {
        $('#edit_amount').val($('#guest_pledge' + a).text());
        $('#edit_payment_date').val($('#guest_date' + a).text());
        $('#edit_pledge').val(parseInt($('#guest_pledge' + a).text()));
        // $('#edit_service').val($('#guest_name'+a).text())
        $('#edit_expense_descriptions').val($('#guest_note' + a).text());
        $('#ProfileStep5').attr('action', '<?= url('expense/edit') ?>'+'/'+a);
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
    get_businesses = function () {
        $('#edit_service').change(function () {
            var val = $(this).val();
            get_business_service(val);
        });

    }
    get_business_service = function (val, tag = 'business_service_id') {
        $('#' + tag).html('<option></option>');
        $.ajax({
            type: 'POST',
            url: "<?= url('budget/getBusinessService') ?>/" + val,
            data: "service_id=" + val,
            dataType: "html",
            success: function (data) {
                $('#' + tag).html(data);
            }
        });
    }
    add_provider = function () {
        $('.add_provider').mousedown(function () {
            var service_id = $('#edit_service').val();
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
    $(document).ready(get_businesses);
    $(document).ready(add_provider);
</script>
@endsection