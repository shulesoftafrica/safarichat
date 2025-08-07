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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Business</a></li>
                        <li class="breadcrumb-item active">Requests</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Business Requests </h4>
                    <p class="text-muted mb-3">List of requests for services </p>

                    <br/>


                    <table  class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Name</th>
                                <th>Event Name</th>
                                <th>Contact Person</th>
                                <th>Event Date</th>
                                <th>Amount</th>
                                <th>Paid Amount</th>
                                <th>Remained</th>

                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            $i = 1;
                            $budget_price = 0;
                            $paid_total = 0;
                            $remained_total = 0;
                            $haystack = [];
                            foreach ($requests as $budget) {

                                $paid = $budget->budgetPayments()->sum('amount');
                                $remains = $budget->actual_price - $paid;

                                $paid_total += $paid;
                                $remained_total += $remains;

                                $budget_price += $budget->actual_price;
                                array_push($haystack, $budget->businessService->service_id);
                                ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $budget->businessService->service->name ?></span></td>
                                    <td><?= isset($budget->usersEvent->event->name) ? $budget->usersEvent->event->name : '' ?></td>
                                    <td><?= isset($budget->usersEvent->user->name) && isset($budget->usersEvent->user->phone) ? $budget->usersEvent->event->name . '<br/>' . $budget->usersEvent->user->phone : '' ?></td>
                                    <td><?php //date('d M Y', strtotime($budget->usersEvent->event->date)) ?></td>
                                    <td><?= number_format($budget->actual_price) ?></td>
                                    <td><?= number_format($paid) ?></td>
                                    <td><?= number_format($budget->actual_price - $budget->budgetPayments()->sum('amount')) ?></td>

                                </tr>
                                <?php
                                $i++;
                            }
                            ?>

                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2"><strong>TOTAL</strong></th>
                                <th></th>
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
@endsection