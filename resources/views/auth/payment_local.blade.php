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
                        <li class="breadcrumb-item active">Payment</li>
                    </ol>
                </div>
                <h4 class="page-title">Payment</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="col-lg-12 col-sm-12 col-md-12 text-right">

                </div>
                <h4>&nbsp; &nbsp; Tanzanians Only</h4>
                <div class="card-body">

                    <!-- Tab panes -->
                    <div class="tab-content">



                        <div id="home" class="tab-pane clearfix col-lg-offset-1 p-3 active"  role="tabpanel">
                            <br/>
                            <h2>Its Simple, just send to</h2>

                            <p></p>

                            <div class="card-body">

                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="tab" href="#home" role="tab" aria-selected="true">M-Pesa</a>
                                    </li>


                                    <li class="nav-item">
                                        <a  class="nav-link" href="#report" data-toggle="tab" role="tab" aria-selected="false">TigoPesa</a>
                                    </li>
                                    <li class="nav-item">
                                        <a  class="nav-link" href="#manual" data-toggle="tab" role="tab" aria-selected="false">Airtel Money</a>
                                    </li>
                                    <li class="nav-item">
                                        <a  class="nav-link" href="#halopesa" data-toggle="tab" role="tab" aria-selected="false">HaloPesa</a>
                                    </li>


                                    <li class="nav-item">
                                        <a  class="nav-link" href="#mobile_banking" data-toggle="tab" role="tab" aria-selected="false"> Mobile Banking</a>
                                    </li>
                                </ul>

                                <!-- Tab panes -->
                                <div class="tab-content">



                                    <div id="home" class="tab-pane  col-lg-offset-1 p-3 active"  role="tabpanel">
                                        <br/>
                                        <h2>M-pesa Payment instructions</h2>

                                        <p></p>
                                        <ol>

                                            <li>Dial *150*00# to access your M-pesa Menu</li>
                                            <li>Select option  1: Send Money</li>
                                            <li>Select option 3: Bank </li>
                                            <li>Select option 2: NMB</li>
                                            <li>Enter Reference Number : <b><?= Illuminate\Support\Facades\Auth::user()->phone ?></b></li>
                                            <li>Enter account No <b>22510073754</b> </li>
                                            <li>Enter amount for your payment Tsh <b><?= isset($package->price) ? number_format($package->price) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                            <li>Enter pin to confirm </li>
                                            <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                                        </ol>
                                        <p></p>
                                        <p>

                                        </p>

                                    </div>
                                    <div id="report" class="tab-pane col-lg-offset-1">
                                        <br/>
                                        <h2>Tigo-pesa Payment instructions</h2>

                                        <p></p>
                                        <ol>
                                            <li>Dial *150*01# to access your Tigo pesa Menu</li>
                                            <li>Select option 7, for Financial Services</li>
                                            <li>Select option 1, for Tigopesa to Bank </li>
                                            <li>Select option 2, for NMB Bank </li>
                                            <li>Enter account No <b>22510073754</b> </li>
                                            <li>Enter amount for your payment Tsh <b><?= isset($package->price) ? number_format($package->price) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                            <li>Enter pin to confirm </li>
                                            <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                                        </ol>
                                        <p></p>


                                    </div>
                                    <div id="manual" class="tab-pane col-lg-offset-1">
                                        <br/>
                                        <h2>Airtel Money Payment</h2>
                                        <p></p>
                                        <p></p>
                                        <ol>
                                            <li>Dial *150*60# to access your Airtel-Money Menu</li>
                                            <li>Select option 6, for Financial Services</li>
                                            <li>Select option 3, for Send to Bank  </li>
                                            <li>Select option 1, for NMB BANK</li>
                                            <li>Enter account No <b>22510073754</b> </li>

                                            <li>Enter amount for your payment Tsh <b><?= isset($package->price) ? number_format($package->price) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                            <li>Enter pin to confirm </li>
                                            <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                                        </ol>

                                        <p></p>
                                        <p>

                                        </p>

                                    </div>
                                    <div id="halopesa" class="tab-pane col-lg-offset-1">
                                        <br/>
                                        <h2>HaloPesa Payment</h2>
                                        <p></p>
                                        <p></p>
                                        <ol>
                                            <li>Dial *150*88# to access your HaloPesa Menu</li>
                                            <li>Select 6 send money to Bank</li>
                                            <li>Select Bank: 1 for NMB BANK</li>
                                            <li>Enter account No <b>22510073754</b> </li>

                                            <li>Enter amount for your payment Tsh <b><?= isset($package->price) ? number_format($package->price) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>

                                            <li>Enter pin to confirm </li>
                                            <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                                        </ol>

                                        <p></p>


                                    </div>



                                    <div id="mobile_banking" class="tab-pane col-lg-offset-1">
                                        <br/>
                                        <h4>Send Money to Our Bank Account Directly</h4>

                                        <ul>

                                            <li>Use your preferable method to send Money to our bank Account, with these details </li>
                                            <li>
                                                <p></p>
                                                <p>BANK NAME: <b>NMB </b></p>
                                                <p>BRANCH: <b>MLIMANI CITY </b></p>
                                                <p>ACCOUNT NAME: <b>LAZACODE COMPANY LIMITED</b></p>
                                                <p>ACCOUNT NO: <b>22510073754</b></p>
                                            </li>
                                        </ul>

                                        <p></p>
                                        <p>

                                        </p>

                                    </div>

                                </div>        
                            </div><!--end card-body-->


                            <div class="alert alert-info">Kindly send the exactly amount. Any amount below will not get processed</div>
                        </div>

                    </div>        
                </div><!--end card-body-->
                <div class="modal-footer">
                    <a style="float:left"> For Help Call - <b><?php //$siteinfos->phone                  ?></b></a>
                    <a href="#" id="verify" order_id="4" class="btn btn-success" >Proceed </a>

                </div>
            </div>
            <div id="result_status"></div>
        </div><!--end row-->
    </div>


</div>
<script type="text/javascript">
    verify = function () {
        $('#verify').mousedown(function () {
            var val = $(this).attr('order_id');
            $.ajax({
                type: 'POST',
                url: "<?= url('payment/verify/') ?>/" + val,
                data: "token=" + val,
                dataType: "html",
                success: function (data) {
                    $('#result_status').html(data);
                }
            });
        })
    }
    $(document).ready(verify);
</script>
@endsection
