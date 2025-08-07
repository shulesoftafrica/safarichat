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
                        <li class="breadcrumb-item active">Collections</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Customer Payment Collections</h4>
                    <p class="text-muted mb-3">Manage list of payments made by our customers to access our service </p>
                    <p>  <button type="button" class="btn btn-success"  data-toggle="modal" data-target="#myModal">
                            Add New Payment
                        </button></p>
                    <br/>
                    <?php
                    ?>
                    <table id="mainTable" class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Paid Amount</th>
                                <th>Payment Date</th>
                                <th>Payment Method</th>
                                <!--<th>Remained Amount</th>-->
                                <th>Actions</th>
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
                                ?>
                                <tr>
                                    <td tabindex="1"><?= $i ?></td>
                                    <td tabindex="1"><span id="guest_name<?= $payment->id ?>">{{$payment->user->name}}</span></td>
                                    <td tabindex="1"><span id="guest_pledge<?= $payment->id ?>" style="display: none;">{{$payment->amount}}</span>{{number_format($payment->amount)}}</td>
                                    <td tabindex="1"><?= $payment->created_at ?></td>
                                    <td tabindex="1"><?= $payment->method ?></td>
                                    <!--<td tabindex="1"><?= $remains ?></td>-->
                                    <td tabindex="1">


                                        <a href="<?= url('home/paymentdestroy/' . $payment->id) ?>" ><i class="las la-trash-alt text-danger font-18"></i>Delete</a>
                                    </td>
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

        <form class="modal-content start-here" id="ProfileStep5" action="{{url('home/payments')}}" method="post">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Add Payment Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">

                    <div class="form-group">
                        <select class="form-control select2" name="user_id" style="width:100% !important" required="">
                            <?php foreach ($users as $user) { ?>
                                <option value="<?= $user->id ?>"><?= $user->name ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <input type="number" name="amount" id="edit_pledge" class="form-control" required="" placeholder="Amount Paid">
                    </div>

                    <div class="form-group">
                        <input type="date" name="date" id="edit_pledge" class="form-control" required="" placeholder="Payment Date">
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

                        <div class="col-sm-12">

                            <input type="text" name="transaction_id" id="transaction_id" class="form-control" required="" placeholder="Transaction ID">

                        </div>

                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" value="<?= time() ?>" name="transaction_ids"/>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>
    </div>

</div>

@endsection