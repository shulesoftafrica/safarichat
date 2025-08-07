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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{__('event')}}</a></li>
                        <li class="breadcrumb-item active">{{__('collections')}}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">{{__('collections')}}</h4>
                    <p class="text-muted mb-3">{{__('manage_list_of_payments')}}</p>
                    <p>  <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#myModal">
                           {{__('add_new_payment')}} 
                        </button></p>
                    <br/>
                    <?php
                    $package = getPackage();
                    $card = 0;
                    if (!empty($package)) {
                        /**
                         * Disabled feature for market entry
                         */
//                        if ($package->admin_package_id == 1) {
//                            $card = 0;
//                        } else if ($package->admin_package_id == 2) {
//                            $card = 1;
//                        } else if ($package->admin_package_id == 3) {
//                            $card = 1;
//                        }

                        /**
                         * ---end-----
                         */
                        $card=1;
                    }
                    ?>
                    <table id="mainTable" class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{__('name')}}</th>
                                <th>{{__('paid_amount')}}</th>
                                <th>{{__('payment_date')}}</th>
                                <th>{{__('payment_method')}}</th>
                                <!--<th>{{__('remained_amount')}}</th>-->
                                <th>{{__('action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_amount = 0;
                            $total_remains = 0;
                            $remains = 0;
                            $i = 1;
                            foreach ($payments as $payment) {
                                $total_amount += $payment->amount;
                                // $remains_amount=\App\Models\Payment::where('events_guest_id', $payment->eventsGuest->id)->sum('amount');
                                $remains = (float) $payment->eventsGuest->guest_pledge - (float) $payment->amount;
                                $total_remains += $remains;
                                ?>
                                <tr>
                                    <td tabindex="1"><?= $i ?></td>
                                    <td tabindex="1"><span id="guest_name<?= $payment->id ?>">{{$payment->eventsGuest->guest_name}}</span></td>
                                    <td tabindex="1"><span id="guest_pledge<?= $payment->id ?>" style="display: none;">{{$payment->amount}}</span>{{number_format($payment->amount)}}</td>
                                    <td tabindex="1"><?= $payment->date ?></td>
                                    <td tabindex="1"><?= $payment->method ?></td>
                                    <!--<td tabindex="1"><?= $remains ?></td>-->
                                    <td tabindex="1">


                                        <!--<a href="<?= (int) $card == 1 ? url('payment/card/' . $payment->id) : url('home/upgrade') ?>" > <i class="fa fa-file text-success font-18"></i> Card</a>-->
                                        <a href="<?= url('payment/destroy/' . $payment->id) ?>" 
                                            onclick="return confirm('{{ __('are_you_sure_you_want_to_delete_this_record') }}');">
                                            <i class="las la-trash-alt text-danger font-18"></i>{{__('delete')}}
                                        </a>
                                    </td>
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
                                <!--<th><?= $total_remains ?></th>-->
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
    <div class="modal-dialog" role="document">

        <form class="modal-content start-here" id="ProfileStep5" action="{{url('payment/store/null')}}" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{__('add_payment_details')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <select class="form-control select2" name="events_guests_id" style="width:100% !important" required="">
                            <?php foreach ($guests as $guest) { ?>
                                <option value="<?= $guest->id ?>"><?= $guest->guest_name ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="number" name="amount" id="edit_pledge" class="form-control" required="" placeholder="{{__('paid_amount')}}">
                    </div>

                    <div class="form-group">
                        <input type="date" name="date" id="edit_pledge" class="form-control" required="" placeholder="{{__('payment_date')}}" max="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-group">
                        <select class="form-control" name="method">
                            <option value="M-pesa">M-pesa</option>
                            <option value="Tigo-pesa">Tigo-pesa</option>
                            <option value="Airtel Money">Airtel-Money</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class='form-group row' >
                        <label for="sms_message" class="col-sm-2 col-form-label text-right">
                            <?= __("message") ?>
                        </label>
                        <div class="col-sm-10">
                            <textarea class="form-control" required="" style="resize:vertical" 
                                      id="smsbox" name="message" >
Hello #name
Kwa niaba ya {{Auth::user()->name}}, tunakushukuru sana  kwa mchango wako wa Tsh #paid_amount kwa ajili ya {{Auth::user()->name}}

Asante sana</textarea>
    <br/>
                            <div class="form-group row">
                            
                                <div class="col-sm-2 col-form-label text-right">
                                    <label for="form-field-select-0">
                                       {{__('message_source')}} </label>
                                </div>
                                <div class="col-sm-10">
                                    <select id="sms_keys_id" class="form-control select2"  name ="channels[]" multiple="" style="width:100% !important">
                                        <option value="bulksms">Bulk SMS</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <!--<option value="telegram">Telegram</option>-->
                                        {{-- <option value="email">Email</option>
                                        <option value="phone-sms" selected>Phone SMS</option> --}}
                                    </select>
                                </div>
                            </div>

                            <a class="label label-default mb-2 mb-lg-0 badge badge-success" onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample" aria-expanded="false" aria-controls="collapseExample">
                               {{__('hashtag_guide')}} 
                            </a> <i class="dripicons-arrow-thin-right arrow"></i> <i class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                            <div class="collapse hide" id="collapseExample" style="">
                                <div class="card mb-0 card-body">
                                    <p class="mb-0 text-muted">{{__('supported_hashtag_lists')}} </p> 
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#{{__('hashtag')}}</th>
                                                    <th>{{__('hashtag_meaning')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">#name</th>
                                                    <td>{{__('name')}}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">#pledge</th>
                                                    <td>{{__('pledge')}}</td>                                                
                                                </tr>
                                                <tr>
                                                    <th scope="row">
                                                        #paid_amount
                                                    </th>
                                                    <td>{{__('paid_amount')}}</td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">#balance</th>
                                                    <td>{{__('balance')}}(pledge-paid amount)</td>
                                                </tr>
                                                <tr>
                                                    <td scope="row">#days_remain</td>
                                                    <td>{{__('total_days_remains_to_finaldate')}}</td>                                                    
                                                </tr>
                                             <!--    <tr>
                                                    <td scope="row">#total_collection</td>
                                                    <td>Total Amount collected from Guests</td>                                                    
                                                </tr>
                                                <tr>
                                                    <td scope="row">#total_guests</td>
                                                    <td>Total Guest that will attend</td>                                                    
                                                </tr>-->

                                            </tbody>
                                        </table><!--end /table-->
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= time() ?>" name="transaction_id"/>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">{{__('save')}}</button>
            </div>
        </form>
    </div>

</div>

@endsection