@extends('layouts.app')
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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Event</a></li>
                        <li class="breadcrumb-item active">Expenses</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Expense  History</h4>
                    <p class="text-muted mb-3">List of expenses done to support <?= Auth::user()->usersEvents()->where('status', 1)->first()->event->name ?></p>
                    <p>  <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#myModal"  onclick=" $('#ProfileStep5').attr('action', '<?= url('expense/store/null') ?>');">
                            Record Expense
                        </button></p>
                    <br/>


                    <table  class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Paid Amount</th>
                                <th>Payment Date</th>
                                <th>Payment Method</th>
                                <th>Expense Info </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_amount = 0;
                            $total_remains = 0;
                            $remains = 0;
                            $i = 1;
                            foreach ($budget_payments as $payment) {
                                $total_amount += $payment->amount;
                                $total_remains += $remains;
                                ?>
                                <tr>
                                    <td tabindex="1"><?= $i ?></td>
                                    <td tabindex="1"><span id="guest_name<?= $payment->id ?>">{{$payment->budget->businessService->service->name}}</span></td>
                                    <td tabindex="1"><span id="guest_pledge<?= $payment->id ?>" style="display: none;">{{$payment->amount}}</span>{{number_format($payment->amount)}}</td>
                                    <td tabindex="1"><span id="guest_date<?= $payment->id ?>" style="display: none;"><?= $payment->date ?></span><?= $payment->date ?></td>
                                    <td tabindex="1"><?= $payment->method ?></td>
                                    <td tabindex="1"><span id="guest_note<?= $payment->id ?>" style="display: none;"><?= $payment->note ?></span><?= $payment->note ?></td>
                                    <td tabindex="1">

                                        <a onclick="editGuest('<?= $payment->id ?>')" data-toggle="modal" href="#myModal"><i class="las la-pen text-info font-18"></i> Edit</a>
                                        <a href="<?= url('expense/destroy/' . $payment->id) ?>">
                                            <i class="las la-trash-alt text-danger font-18"></i> Delete</a></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            ?>

                          
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2"><strong>TOTAL</strong></th>
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
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Manage Expenses</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Service Name</label>
                        <select class="form-control select2" name="service_id" id="edit_service" style="width:100%">
                            <?php foreach ($services as $service) { ?>
                                <option value="<?= $service->id ?>"><?= $service->name ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Paid Amount</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" required="" placeholder="Amount Paid">
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Payment Date</label>
                        <input type="date" name="date" id="edit_payment_date" required="" class="form-control" placeholder="Payment Date">
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Payment Method</label>
                        <select class="form-control" name="method">
                            <option value="M-pesa">M-pesa</option>
                            <option value="Tigo-pesa">Tigo-pesa</option>
                            <option value="Airtel Money">Airtel-Money</option>
                            <option value="Bank">Bank Transfer</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Payment Descriptions</label>
                        <textarea name="note" id="edit_descriptions" required="" class="form-control" placeholder="Any Descriptions about the expense"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= time() ?>" name="transaction_id"/>
                <span id="add_inputs"></span>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
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
        $('#edit_descriptions').val($('#guest_note' + a).text());
        $('#ProfileStep5').attr('action', '<?= url('expense/edit/null') ?>');
        var option = $('<option></option>').attr("value", a).text($('#guest_name'+a).text());
        $("#edit_service").empty().append(option);
        $('#add_inputs').html('<input type="hidden" value="'+a+'" name="id"/>');
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