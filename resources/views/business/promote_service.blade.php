
@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <br/>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Business</a></li>
                        <li class="breadcrumb-item active">Service</li>
                    </ol>
                    <br/>
                </div> 
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

 
    <div class="row">
        
     
            <div class="modal-dialog" id="modal_promotion_page">
                <form class="modal-content start-here" id="ProfileStep5" action="<?= url('business/promotion/null') ?>" method="POST">

                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title mt-0" id="exampleModalLabel">Service Promotion</h5>
                           
                        </div>

                        <div class="modal-body">
                            <div class="form-group alert icon-custom-alert alert-outline-success">
                                You are about to generate more revenue by letting hundreds of events owners know and opt for your service
                            </div>

                            <div class="form-group">
                                <label for="quantity" class=" col-form-label text-right">Select Promotion type</label>

                                <table>
                                    <tr>
                                        <td><input type="radio" class="radio_check" name="type" value="5000"/></td>
                                        <td>Basic</td>
                                        <td>
                                            <ul>
                                                <li>Only text message will be sent to almost all event owners requested</li>
                                                <li>Message will be sent once and users in normal text without any multimedia (image, video, link etc)</li>
                                            </ul>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><input type="radio" class="radio_check" name="type" value="10000" checked=""/></td>
                                        <td>Advanced</td>
                                        <td>
                                            <ul>
                                                <li>Message will be sent to selected event owners in the location you need</li>
                                                <li>Message will be sent with embedded multimedia (image, video, link etc) for event owners to view and interact</li>
                                            </ul>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="form-group">
                                <label for="quantity" class=" col-form-label text-right">Number of Event Owners to be reached</label>
                                <input type="number" name="number" min="1" id="total_number" class="form-control" required="" value="1" placeholder="Number">
                            </div>

                            <div class="form-group">
                                <label for="quantity" class=" col-form-label text-right">Total Price</label>
                                <input type="text" name="total_price" id="total_price" disabled="" class="form-control" value="10,000">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer text-center">
                        <?= csrf_field() ?>
                        <span id="add_inputs"></span>
                        <button type="submit" class="btn btn-success" id="proceed_with_payment" data-toggle="tooltip" data-placement="top">Proceed</button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        get_total_price = function () {
            $('.radio_check').change(function () {
                var val = $(this).val();
                var number = $('#total_number').val();
                var price = parseInt(val * number).toLocaleString();
                $('#total_price').val(price);
            });

            $('#total_number').bind('keyup mouseup', function (e) {
                var number = $(this).val();
                var val = $('.radio_check:checked').val();
                var price = parseInt(val * number).toLocaleString();

                $('#total_price').val(price);
            });

//            $('#proceed_with_payment').mousedown(function () {
//                var val = $('.radio_check:checked').val();
//                var total_price = $('#total_price').val();
//                var number = $('#total_number').val();
//                $.ajax({
//                    type: 'POST',
//                    url: "<?= url('business/promotion/null') ?>",
//                    data: {val: val, total_price: total_price, number: number},
//                    dataType: "html",
//                    success: function (data) {
//                        $('#modal_promotion_page').html(data)
//                    }
//                });
//            });
        }
        $(document).ready(get_total_price);
    </script>
    @endsection