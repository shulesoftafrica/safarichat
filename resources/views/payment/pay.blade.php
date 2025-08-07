<style>
    .tab-pane{
        margin-left: 10%
    }
</style>
<?php

$reference = isset($booking->reference) ? $booking->reference : '<span style="color:red;"><u> Not Defined</u></span>';
$amount = isset($booking->amount) ? number_format($booking->amount) : '<span style="color:red;"><u> Not Defined</u></span>';
?>
<div class="box">

    <?php if (!isset($minimal)) { ?>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">

                    <h4 class="modal-title" id="myModalLabel">Payment Methods</h4>
                </div>
                <div class="modal-body">
                    <h4>Choose your Preferred  method below</h4>
                    <br/>
                <?php } ?> 
                <div class=" ">
                    <header>
                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a href="#stats"  class="nav-link active" data-toggle="tab" aria-expanded="true">CRDB SIM BANKING (Auto)</a>
                            </li>
                            <li class="nav-item">
                                <a href="#report"  class="nav-link " data-toggle="tab" aria-expanded="false">CRDB WAKALA</a>
                            </li>
                            <li class="nav-item">
                                <a href="#manual"  class="nav-link " data-toggle="tab" aria-expanded="false">Bank Transfer</a>
                            </li>
                            <!-- <li class="nav-item">
                                <a href="#halopesa"  class="nav-link " data-toggle="tab" aria-expanded="false">HaloPesa</a>
                            </li>
                            <li class="nav-item">
                                <a href="#ezypesa" class="nav-link " data-toggle="tab" aria-expanded="false">EZYPESA</a>
                            </li>
                            <li class="nav-item">
                                <a href="#tpesa" class="nav-link " data-toggle="tab" aria-expanded="false">T-Pesa</a>
                            </li>
                            <li class="nav-item">
                                <a href="#selcom_card" class="nav-link " data-toggle="tab" aria-expanded="false">SELCOM-CARD</a>
                            </li>
                            <li class="nav-item">
                                <a href="#mobile_banking" class="nav-link " data-toggle="tab" aria-expanded="false"> Mobile Banking</a>
                            </li> -->
                        </ul>
                    </header>
                    <div class="body tab-content">
                        <div id="stats" class="tab-pane clearfix col-lg-offset-1 active">
                            <br/>
                            <h2>CRDB SIM BANKING  Payment instructions</h2>

                            <p></p>
                            <ol>

                                <li>Login into your SIM Banking Mobile App</li>
                                <li>On payment services, select Education</li>
                                <li>Enter Reference Number : <b><?=$reference ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= $amount?></b></li>
                                <li>Enter pin to confirm </li>
                                <li>Once you get SMS confirmation </li>
                            </ol>
                            <p></p>
                            <p>

                            </p>

                        </div>
                        <div id="report" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>CRDB WAKALA Payment instructions</h2>

                            <p></p>
                            <ol>
               
                                <li>Find any CRDB WAKALA nearby</li>
                                <li>Give wakala this Reference Number for School Fee Payment: <b><?= $reference?></b></li>
                                <li>Enter amount for your payment Tsh <b><?=$amount?></b></li>
                                <li>Once you get SMS confirmation</li>
                            </ol>
                            <p></p>


                        </div>
                        <div id="manual" class="tab-pane col-lg-offset-1">
                            <br/>
                            <h2>Send Money to these Bank Details</h2>
                            <p></p>
                            <p></p>
                            <ol>
                                <li>Please deposit <b><?= $amount ?></b> into one of the following bank accounts:</li>
                                <ul>
                                    <li>
                                        <b>Bank Name:</b> NMB Bank<br>
                                        <b>Account Name:</b> ShuleSoft Limited<br>
                                        <b>Account Number:</b> 22510077805
                                    </li>
                                    <p>Or</p>
                                    <li>
                                        <b>Bank Name:</b> CRDB BANK PLC<br>
                                        <b>Account Name:</b> ShuleSoft Limited<br>
                                        <b>Account Number:</b> 0150865554300
                                    </li>
                                </ul>
                                <li>Use your Reference Number: <b><?= $reference ?></b> when making the deposit.</li>
                                <li>Once you have made the payment, keep the deposit slip or confirmation for your records.
                                    Your payment will be processed within 3 hours from the time of deposit.</li>
                                </li>
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
                                <li>Enter Reference Number : <b><?=$reference ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= $amount ?></b></li>

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
                                <li>Enter Reference Number : <b><?= $reference ?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= $amount?></b></li>

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
                                <li>Enter Reference Number : <b><?= $reference?></b></li>
                                <li>Enter amount for your payment Tsh <b><?=$amount ?></b></li>

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
                                <li>Enter Reference Number : <b><?=$reference?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= $amount?></b></li>

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
                            <p><img src="<?= url('public/assets/images/banks.JPG') ?>"/></p>
                            <p></p>
                            <ol>
                                <li>Dial  your bank's USSD code </li>
                                <li>Enter pin to confirm </li>

                                <li>Select Mastercard QR</li>
                                <li>Enter Reference Number : <b><?= $reference?></b></li>
                                <li>Enter amount for your payment Tsh <b><?= $amount ?></b></li>
                                <li>Confirm Payments by entering 1 </li>
                                <li>Once you get SMS confirmation, click "Proceed" to continue </li>
                            </ol>

                            <p></p>
                            <p>

                            </p>

                        </div>
                    </div>
                </div>
                <?php if (!isset($minimal)) { ?>
                </div>
                <div class="modal-footer">
                    <a style="float:left"> For Help Call - <b></b></a>

                    <?php if (isset($booking->order_id)) { ?>
                        <a href="<?= url('payment/cancelPayment/' . $booking->order_id) ?>" class="btn btn-default">Cancel </a>
                        <a href="<?= url('payment/validate/1') ?>" class="btn btn-success" >Proceed </a>
                    <?php } else { ?>
                        <a href="<?= url('payment/validate/1') ?>" class="btn btn-success" >Proceed </a>

                    <?php }
                    ?>
                </div>

            </div>
        </div>


        <?php }
    ?>

</div>

</div>

