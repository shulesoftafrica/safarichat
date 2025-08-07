@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Business</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Summary</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
                <h4 class="page-title">Business Summary</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-lg-6">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4 align-self-center">
                                    <div class="icon-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers align-self-center icon-lg icon-dual-warning"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
                                    </div> 
                                </div>
                                <div class="col-8 align-self-center text-right">
                                    <div class="ml-2">
                                        <p class="mb-1 text-muted">Active Requests</p>
                                        <h3 class="mt-0 mb-1 font-weight-semibold"><?= count($requests) ?></h3>                                                                                                                                           
                                    </div>
                                </div>                    
                            </div>
                            <div class="progress mt-2" style="height:3px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 55%;" aria-valuenow="55" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div><!--end card-body-->
                    </div><!--end card-->
                </div><!--end col-->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4 align-self-center">
                                    <div class="icon-info">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div> 
                                </div>
                                <div class="col-8 align-self-center text-right">
                                    <div class="ml-2">
                                        <p class="mb-1 text-muted">Request Amount</p>
                                        <h3 class="mt-0 mb-1 font-weight-semibold d-inline-block"><?= number_format($amount) ?></h3>
                                        <span class="badge badge-soft-success mt-1 shadow-none">Tsh</span>                                                                                                                                     
                                    </div>
                                </div>                    
                            </div>
                            <div class="progress mt-2" style="height:3px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 48%;" aria-valuenow="48" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div><!--end card-body-->
                    </div><!--end card-->
                </div><!--end col-->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-4 align-self-center">
                                    <div class="icon-info">
                                        <i class="fab fa-servicestack"></i>
                                    </div> 
                                </div>
                                <div class="col-8 align-self-center text-right">
                                    <div class="ml-2">
                                        <p class="mb-1 text-muted">Active Services</p>
                                        <h3 class="mt-0 mb-1 font-weight-semibold"><?= count($business_services) ?></h3>                                                                                                                                           
                                    </div>
                                </div>                    
                            </div>
                            <div class="progress mt-2" style="height:3px;">
                                <div class="progress-bar bg-purple" role="progressbar" style="width: 39%;" aria-valuenow="39" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div><!--end card-body-->
                    </div><!--end card-->
                </div><!--end col-->
            </div><!--end row-->
            <div class="card">
                <div class="card-body">
                    <div class="apexchart-wrapper" style="position: relative;">
                        <div id="budgets_chart" class="apex-charts" style="min-height: 335px;">

                            <div class="col-sm-12">

                                <script type="text/javascript">
                                    $(function () {

                                        $('#graph_content').highcharts({
                                            chart: {
                                                type: 'bar'
                                            },
                                            title: {
                                                text: "Payment Collected Per Month"
                                            },
                                            subtitle: {
                                                text: 'report is based on date recorded by event owners in the system'
                                            },
                                            xAxis: {
                                                type: 'category'
                                            },
                                            yAxis: {
                                                title: {
                                                    text: 'Amount (Tsh)'
                                                }

                                            },
                                            legend: {
                                                enabled: false
                                            },
                                            plotOptions: {
                                                series: {
                                                    borderWidth: 0,
                                                    dataLabels: {
                                                        enabled: true,
                                                        format: '{point.y:.1f}<?= isset($format) ? $format : '' ?>'
                                                    }
                                                }
                                            },

                                            tooltip: {
                                                headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
                                                pointFormat: '<span style="color:{point.color}">{point.name}</span>: Tsh<b>{point.y:.2f}<?= isset($format) ? $format : '' ?></b> of total<br/>'
                                            },

                                            series: [{
                                                    name: 'Month -Year',
                                                    colorByPoint: true,
                                                    data: [
<?php
$i = 0;
foreach ($reports as $value) {
    ?>
                                                            {
                                                                name: '<?= strtoupper($value->month_date) ?>',
                                                                y: <?= $value->sum ?>,
                                                                drilldown: '<?= $value->month_date ?>'
                                                            },
    <?php
    $i++;
}
?>
                                                    ]
                                                }]
                                        });
                                    });



                                </script>
                                <script src="<?= asset('assets/js/highchart.js') ?>"></script>
                                <script src="<?= asset('assets/js/exporting.js') ?>"></script>

                                <div id="graph_content" style="min-width: 360px; max-width: 900px; height: 400px; margin: 0 auto"></div>
                            </div>
                        </div>
                        <div class="resize-triggers"><div class="expand-trigger"><div style="width: 551px; height: 321px;"></div></div><div class="contract-trigger"></div></div></div>
                </div><!--end card-body-->                                                                                             
            </div><!--end card-->
        </div><!--end col-->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">

                    <!-- Nav tabs -->
                    <ul class="nav-border nav nav-pills" role="tablist">
                        <?php
                        $i = 0;
                        foreach ($business_services as $service) {
                            ?>
                            <li class="nav-item">
                                <a class="nav-link font-weight-semibold <?= $i == 0 ? 'active' : '' ?>" data-toggle="tab" href="#service_tab<?= $service->id ?>" role="tab" aria-selected="<?= $i == 0 ? 'true' : 'false' ?>">
                                    <?= strlen($service->service->name) < 3 ? $service->business->name : $service->service->name ?>
                                </a>
                            </li>
                        <?php $i++; }
                        ?>

                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">

                        <?php
                        $z = 0;
                        foreach ($business_services as $service) {
                            ?>
                            <div class="tab-pane p-3 <?= $z == 0 ? 'active' : '' ?>" id="service_tab<?= $service->id ?>" role="tabpanel">  
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="media mb-3">
                                            <img src="<?= strlen($service->service_logo) > 6 ? url($service->service_logo) : asset(ROOT.'assets/images/users/user-4.jpg') ?>" alt="" class="thumb-lg rounded-circle">                                      
                                                <div class="media-body align-self-center text-truncate ml-3">

                                                    <h4 class="mt-0 mb-1 font-weight-semibold text-dark font-18"> <?= strlen($service->service_name) < 3 && !empty($service->business) ? '' : $service->service_name ?></h4>   
                                                    <p class="text-muted  mb-0 font-14"><span class="text-dark">Type : </span> <?= $service->service->name ?></p>                                         
                                                </div><!--end media-body-->
                                        </div>       
                                    </div><!--end col-->
                                    <div class="col-md-6 text-lg-right">
                                        <h6 class="font-weight-semibold">Registered : <span class="text-muted font-weight-normal"> <?= date('d M Y', strtotime($service->created_at)) ?></span></h6>
                                    </div><!--end col-->
                                </div><!--end row-->


                                <div class="task-box mt-3">
                                    <div class="task-priority-icon"><i class="fas fa-check text-danger"></i></div>                                                
                                    <div class="mt-5 d-flex justify-content-between">
                                        <h6 class="font-weight-semibold">Descriptions: <span class="text-muted font-weight-normal"></span></h6>
                                    </div>
                                    <p class="text-muted mt-4 mb-1"><?= $service->details ?>
                                    </p>
                                    <p class="text-muted text-right mb-1"></p>
                                    <div class="progress mb-4" style="height: 4px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%;" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div class="img-group">
                                            <?php
                                            $users = $service->budgets()->take(4)->get();
                                            foreach ($users as $user) {
                                                ?>
                                                <a class="user-avatar user-avatar-group" href="#">
                                                    <img src="../assets/images/users/user-6.jpg" alt="user" class="rounded-circle thumb-xs">
                                                </a>
                                            <?php } ?>

                                            <a href="" class="avatar-box thumb-xs align-self-center">
                                                <span class="avatar-title bg-soft-info rounded-circle font-13 font-weight-normal">+<?= $service->budgets()->count() ?></span>
                                            </a>                    
                                        </div><!--end img-group--> 
                                        <ul class="list-inline mb-0 align-self-center">                                                                    

                                            <!--                                            <li class="list-item d-inline-block">
                                                                                            <a class="" href="#">
                                                                                                <i class="mdi mdi-comment-outline text-primary font-15"></i>
                                                                                                <span class="text-muted font-weight-bold">3</span>
                                                                                            </a>                                                                               
                                                                                        </li>-->
                                            <li class="list-item d-inline-block">
                                                <a class="ml-2" href="<?= url('business/product/view') . '/' . $service->service_id ?>">
                                                    <i class="mdi mdi-pencil-outline text-muted font-18"></i>
                                                </a>                                                                               
                                            </li>

                                        </ul>
                                    </div>                                        
                                </div><!--end task-box-->                                           
                            </div><!--end tab-pane-->

                        <?php $z++; }
                        ?>
                    </div>        
                </div><!--end card-body-->
            </div><!--end card-->
        </div><!--end col-->

    </div><!--end row-->

    <div class="row">
        <div class="col-lg-4">
            <div class="card">                                       
                <div class="card-body"> 
                    <h4 class="header-title mt-0 mb-4">Activities</h4>
                    <div class="slimScrollDiv" style="position: relative; overflow: hidden; width: auto; height: 722.4px;"><div class="slimscroll crm-dash-activity" style="overflow: hidden; width: auto; height: 722.4px;">
                            <div class="activity">

                                <?php
                                foreach ($page_viewers as $page) {
                                    ?>
                                    <div class="activity-info">
                                        <div class="icon-info-activity">
                                            <i class="mdi mdi-timer-off bg-soft-pink"></i>
                                        </div>
                                        <div class="activity-info-text">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h6 class="m-0  w-75"><?= $page->user->name ?></h6>
                                                <span class="text-muted font-12"><?= timeAgo($page->created_at) ?></span>
                                            </div>
                                            <p class="text-muted mt-3"><?= $page->businessService->service_name ?></p> 

                                        </div>
                                    </div>
                                <?php } ?>


                            </div><!--end activity-->
                        </div><div class="slimScrollBar" style="background: rgba(162, 177, 208, 0.13); width: 7px; position: absolute; top: 0px; opacity: 1; display: block; border-radius: 7px; z-index: 99; right: 1px; height: 351.411px;"></div><div class="slimScrollRail" style="width: 7px; height: 100%; position: absolute; top: 0px; display: none; border-radius: 7px; background: rgb(51, 51, 51); opacity: 0.2; z-index: 90; right: 1px;"></div></div><!--end crm-dash-activity-->
                </div>  <!--end card-body-->                                     
            </div><!--end card--> 
        </div><!--end col--> 
        <div class="col-lg-8">
            <div class="card">                                
                <div class="card-body">
                    <h4 class="mt-0 mb-3 header-title">User Service Requests</h4>           
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Service</th>
                                    <th>Request Date</th>
                                    <th>Event Date</th>
                                    <!--<th>Status</th>-->
                                    <!--<th>Payment Status</th>-->
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
                                        <td><span><?= $budget->businessService->service->name ?></span></td>
                                        <td><span><?= $budget->created_at ?></span></td>
                                        <td><span ><?= date('d M Y', strtotime($budget->usersEvent->event->date)) ?></span></td>
                                        
<!--                                        <td><span class="badge badge-soft-<?= (int) $budget->approved == 1 ? 'success' : 'warning' ?>">
                                                <?= (int) $budget->approved == 1 ? 'Approved' : 'Not Approved' ?>
                                            </span></td>-->

<!--                                        <td>
                                            <small class="float-right ml-2 pt-1 font-10">92%</small>
                                            <div class="progress mt-2" style="height:5px;">
                                                <div class="progress-bar bg-secondary" role="progressbar" style="width: 92%;" aria-valuenow="92" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>-->
                                    </tr>
                                    <?php
                                    $i++;
                                }
                                ?>

                            </tbody>
                        </table>
                        <div class="text-right">
                            <a href="<?= url('business/request') ?>" class="">View All<i class="dripicons-arrow-thin-right ml-2"></i></a>
                        </div>                                                
                    </div><!--end table-responsive--> 
                </div><!--end card-body-->                                                                                                        
            </div><!--end card-->
        </div><!--end col-->     
    </div><!--end row--> 
</div>
@endsection