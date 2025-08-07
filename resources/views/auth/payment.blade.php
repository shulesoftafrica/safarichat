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
                    <!--<h4>You have a Card ?</h4>-->
                    <?php
                    ?>
                                      <!--<form action="<?= base64_decode($booking->payment_gateway_url) ?>">-->
                                              <!--<script src="https://checkout.flutterwave.com/v3.js"></script>-->
<!--                    <a href="<?= base64_decode($booking->payment_gateway_url) ?>" target="_blank"  class="btn btn-sm btn-success" style="width:20em;">Pay Now  <i class="fa fa-arrow"></i></a>
                    <p><img src="<?= url('public/images/cards.png') ?>" width="276"></p>-->
                    <!--</form>-->
                    <?php
                    ?>
                </div>
                <h4>&nbsp; &nbsp; Tanzanians Only</h4>
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
                            <a  class="nav-link" href="#ezypesa" data-toggle="tab" role="tab" aria-selected="false">EZYPESA</a>
                        </li>
                        <li class="nav-item">
                            <a  class="nav-link" href="#tpesa" data-toggle="tab" role="tab" aria-selected="false">T-Pesa</a>
                        </li>
                        <li class="nav-item">
                            <a  class="nav-link" href="#selcom_card" data-toggle="tab" role="tab" aria-selected="false">SELCOM-CARD</a>
                        </li>
                        <li class="nav-item">
                            <a  class="nav-link" href="#mobile_banking" data-toggle="tab" role="tab" aria-selected="false"> Mobile Banking</a>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content">



                        <div id="home" class="tab-pane clearfix col-lg-offset-1 p-3 active"  role="tabpanel">
                            <br/>
                            <h2>M-pesa Payment instructions</h2>

                            <p></p>
                            <ol>

                                <li>Dial *150*00# to access your M-pesa Menu</li>
                                <li>Select option 4,Lipa by M-pesa</li>
                                <li>Select option 4, to enter business number </li>
                                <li>Enter Business Number <b> 123123</b></li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
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
                                <li>Select option 5, for Merchant payment</li>
                                <li>Select option 2, Pay Mastercard QR Merchant </li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
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
                                <li>Select option 5, for payment</li>
                                <li>Select option 1, for Merchant payments  </li>
                                <li>Select option 1, Pay with Mastercard QR</li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
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
                                <li>Select option 5, for payment</li>
                                <li>Select option 3, Pay with Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>

                                <li>Enter pin to confirm </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>
                        <div id="ezypesa" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>EzyPesa Payment</h2>
                            <p></p>
                            <p></p>
                            <ol>
                                <li>Dial *150*02# to access your EzyPesa Menu</li>
                                <li>Select option 5, for payment</li>
                                <li>Select option 1, for Lipa Hapa</li>
                                <li>Select option 2, Pay with Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>

                                <li>Enter pin to confirm </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>

                        <div id="tpesa" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>T-Pesa Payment</h2>
                            <p></p>
                            <p></p>
                            <ol>
                                <li>Dial *150*71# to access your T-Pesa Menu</li>
                                <li>Select option 6, for payment</li>

                                <li>Select option 2, Pay with Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>

                                <li>Enter pin to confirm </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>
                        <div id="selcom_card" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>Selcom Card Payment</h2>
                            <p></p>
                            <p></p>
                            <ol>
                                <li>Dial *150*50# to access your Selcom Card Menu</li>
                                <li>Enter pin to confirm </li>

                                <li>Select option 2, Pay with Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>

                                <li>Confirm Payments by entering 1 </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>
                        <div id="mobile_banking" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>Pay with mobile banking or download Masterpass Tanzania App</h2>
                            <p><img src="<?= url('public/images/banks.JPG') ?>"/></p>
                            <p></p>
                            <ol>
                                <li>Dial  your bank's USSD code </li>
                                <li>Enter pin to confirm </li>

                                <li>Select Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= isset($booking->token) ? $booking->token : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>' ?></b></li>
                                <li>Confirm Payments by entering 1 </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>

                    </div>        
                </div><!--end card-body-->
                <div class="modal-footer">
                    <a style="float:left"> For Help Call - <b><?php //$siteinfos->phone          ?></b></a>

                    <?php if (isset($booking->order_id)) { ?>
                        <a href="<?= url('payment/cancelPayment/' . $booking->order_id) ?>" class="btn btn-default">Cancel </a>
                        <a href="#" id="verify" order_id="{{$booking->order_id}}"  class="btn btn-success" >Proceed </a>
                    <?php } else { ?>
                        <a href="#" id="verify" order_id="{{$booking->order_id}}" class="btn btn-success" >Proceed </a>

                    <?php }
                    ?> 

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
