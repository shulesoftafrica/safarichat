<?php
    $errors = $errors ?? session()->get('errors', new \Illuminate\Support\ViewErrorBag);
?>


<?php $__env->startSection('content'); ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header text-center">
                    <h5><?php echo e(__('Verify Your Phone Number')); ?></h5>
                </div>
                <div class="card-body">
                    <p class="mb-3 text-center">
                        <?php echo e(__('We have sent a One-Time Password (OTP) to your mobile number:')); ?><br>
                        <strong class="h5"><?php echo e($phone ?? '+62 812-3456-7890'); ?></strong>
                    </p>
                    <p class="text-muted small text-center">
                        <?php echo e(__('Please enter the 6-digit OTP you received via WhatsApp. Make sure your phone is connected to the internet and WhatsApp is running smoothly.')); ?>

                    </p>

                    <?php if(isset($message) && strlen($message)>5): ?>
                        <div class="alert alert-danger text-center">
                            <?php echo e($message); ?>

                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo e(url('setup/otpverify')); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="form-group mb-4">
                            <label for="otp" class="form-label"><?php echo e(__('OTP Code')); ?></label>
                            <input id="otp" type="text" maxlength="6" class="form-control text-center <?php $__errorArgs = ['otp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="otp" required autofocus pattern="\d{6}" placeholder="Enter 6-digit OTP">
                            <?php $__errorArgs = ['otp'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
<input type="hidden" name="email" value="<?php echo e($phone); ?>">
                        <button type="submit" class="btn btn-success w-100 mb-2">
                            <?php echo e(__('Verify OTP')); ?>

                        </button>
                    </form>

                    <div class="text-center mt-3">
                        <span id="resend-info" class="text-muted small">
                            <?php echo e(__('Didn\'t receive the code?')); ?>

                        </span>
                        <button id="resend-btn" class="btn btn-link p-0" style="display:none;" onclick="document.getElementById('resend-form').submit();">
                            <?php echo e(__('Resend OTP')); ?>

                        </button>
                        <span id="timer" class="text-danger small"></span>
                        <form id="resend-form" method="POST" action="<?php echo e(url('api/otp')); ?>" style="display:none;">
                            <input type="hidden" name="email" value="<?php echo e($phone); ?>">
                            <?php echo csrf_field(); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // 3 minutes countdown
    let resendBtn = document.getElementById('resend-btn');
    let timerSpan = document.getElementById('timer');
    let resendForm = document.getElementById('resend-form');
    let countdown = 180; // seconds

    function updateTimer() {
        if (countdown > 0) {
            let min = Math.floor(countdown / 60);
            let sec = countdown % 60;
            timerSpan.textContent = ` (Resend available in ${min}:${sec.toString().padStart(2, '0')})`;
            resendBtn.style.display = 'none';
            countdown--;
            setTimeout(updateTimer, 1000);
        } else {
            timerSpan.textContent = '';
            resendBtn.style.display = 'inline';
        }
    }

    updateTimer();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\safarichat\resources\views/auth/verify.blade.php ENDPATH**/ ?>