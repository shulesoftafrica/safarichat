<?php
$package = getPackage();

$try_period = Auth::user()->created_at;

$now = time();
$your_date = strtotime($try_period);
$datediff = $now - $your_date;
$days = round($datediff / (60 * 60 * 24));
$expired = 0;



$event= \App\Models\UsersEvent::where('user_id',Auth::user()->id)->first();

if(!empty($event)){
if (empty($package) && (int)is_trial()==0) {
    
    //check payments
    $expired = 1;
    if (!preg_match('/upgrade/', url()->current())) {
        ?>
        <div class="modal fade" id="payment_model" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="exampleModalLabel">Account Upgrade</h5>
                        <!--                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">×</span>
                                        </button>-->
                    </div>
                    <div class="modal-body">
                        <p>Your account has been expired <?= $days > 4 ? ($days - 3) . ' Days Ago' : '' ?> </p>
                        <div class="form-group">
                            <div class="alert alert-outline-warning alert-warning-shadow mb-0 alert-dismissible fade show" role="alert">
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true"><i class="mdi mdi-close"></i></span>
                                </button>
                                <strong>Way Forward !</strong> 
                                Your  Free trial ended at <?=date('d M Y H:i',strtotime($try_period))?>. 
                                We hope you enjoyed using DikoDiko
                                as much as we loved building it for you.  

                                If you are ready to let DikoDiko helps you to manage your event, click on the button right below.
                            </div>

                        </div>

                        <hr/>
                        <p>NB; Once you pay for the package, your account will be updated automatically within few minutes</p>
                    </div>
                    <div class="modal-footer">
                        <a type="button" class="btn btn-success" href="<?= url('home/upgrade') ?>">Pay for the Service</a>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            $(window).on('load', function () {
                $('#payment_model').modal({backdrop: 'static', keyboard: false, show: true});
            });
        </script>
    <?php }
}
}?>