
<div class="row">
    <div class="col-12">
        <div class="card">
            <?php
            if (empty($payment_complete)) {
                echo '<div class=" alert icon-custom-alert alert-outline-pink fade show" role="alert">Payment has not been reflected yes. If you have made a payment, kindly wait within 5 minutes then click proceed again.'
                . ' If problem persist kindly call us directly via  +255 734 952 586 </div>';
            } else {
                echo '<div class="alert icon-custom-alert alert-outline-success" role="alert"> Payment has been accepted succesfully. Kindly proceed and enjoy our services</div>'
                . '<br/>'
                . '<a href="'.url('/').'" class="btn btn-success">Return Home</a>';
                ?>
                <script>
                    function sleep(ms) {
                        return new Promise(resolve => setTimeout(resolve, ms));
                    }

                    async function demo() {
                       
                        await sleep(2000);
                        window.location.href='<?=url('/')?>';
                    }

                    demo();
                </script>
            <?php }
            ?>
        </div>

    </div><!--end row-->
</div>


