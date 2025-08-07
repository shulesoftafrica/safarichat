@extends('layouts.app')
@section('content')

 @php
                                                                // Simulate $booking for pay.blade.php
                                                                $booking =\App\Models\AdminBooking::where([
                                                                    'admin_package_id' => 4,
                                                                    'user_id' => Auth::user()->id,
                                                                ])->first();
                                                                $minimal = true;
                                                            @endphp

<div class="container">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('home') }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('message') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('channels') }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ __('message_channels') }}</h4>
            </div>
        </div>
    </div>
    <!-- end page title end breadcrumb -->

    <div class="row mb-4">
        <div class="col-sm-12">
            <div class="card">
                {{-- <div class="card-header"><strong>Available Subscription Packages</strong></div> --}}
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3" style="gap: 1rem;">
                        @php
                            // Check if user has paid for WhatsApp (admin_package_id = 4)
                            $hasWhatsappPayment = \App\Models\AdminPayment::where([
                                'user_id' => Auth::id(),
                                'method' => 'whatsapp',
                            ])->exists();

                            // Check if user has paid for BulkSMS (admin_package_id = 5 or method = 'bulksms')
                            $hasBulkSmsPayment = \App\Models\AdminPayment::where([
                                'user_id' => Auth::id(),
                                'method' => 'bulksms',
                            ])->exists();

                            $hasBulkSms = $instances->where('type', 'bulksms')->count() > 0;
                        @endphp

                        @if(!$hasWhatsappPayment)
                            <a href="{{ url('home/upgrade') }}" class="btn btn-success btn-sm d-flex align-items-center" style="background-color: #25D366; border-color: #25D366;">
                                <i class="fab fa-whatsapp mr-2" style="font-size: 1.2em;"></i>
                                {{ __('create_new_whatsapp_instance') }}
                            </a>
                        @else
                            <button class="btn btn-success btn-sm d-flex align-items-center" data-toggle="modal" data-target="#whatsapp" style="background-color: #25D366; border-color: #25D366;">
                                <i class="fab fa-whatsapp mr-2" style="font-size: 1.2em;"></i>
                                {{ __('create_new_whatsapp_instance') }}
                            </button>
                        @endif
                        <?php 
                        if(Auth::user()->bulksms_enabled==1){
                        ?>
                        @if(!$hasBulkSms)
                            @if(!$hasBulkSmsPayment)
                                <a href="{{ url('home/upgrade') }}" class="btn btn-primary btn-sm d-flex align-items-center" style="background-color: #007bff; border-color: #007bff;">
                                    <i class="fas fa-sms mr-2" style="font-size: 1.2em;"></i>
                                    {{ __('create_bulk-sms_instance') }}
                                </a>
                            @else
                                <button class="btn btn-primary btn-sm d-flex align-items-center" data-toggle="modal" data-target="#bulksms" style="background-color: #007bff; border-color: #007bff;">
                                    <i class="fas fa-sms mr-2" style="font-size: 1.2em;"></i>
                                    {{ __('create_bulk-sms_instance') }}
                                </button>
                            @endif
                        @endif

                        <?php } ?>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-info-circle"></i> {{ __('please_add_a_bulk-sms_sender_name_to_use_this_option') }}.
                    </small>
                     
                   <br/>
<div class="alert alert-info mt-3">
                    <strong>{{ __('note') }}:</strong>
</div>
                    <br/>
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    
                                      <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('instance_type') }}</th>
                                <th>{{ __('name') }}</th>
                                <th>{{ __('phone_number') }}</th>
                                <th>{{ __('date_registered') }}</th>
                                <th>{{ __('message_balance') }}</th>
                                <th>{{ __('status') }}</th>
                                <th>{{ __('actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($instances as $instance)
                            <tr>
                                <td>{{ ucfirst($instance->type) }}</td>
                                <td>{{ $instance->name }}</td>
                                <td>{{ $instance->phone_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($instance->created_at)->format('Y-m-d') }}</td>
                                @if($instance->type === 'bulksms')
                                    @php
                                        $smsStatus = \DB::table('users_sms_status')
                                            ->where('user_id', $instance->user_id)
                                            ->first();
                                    @endphp
                                    <td>{{ $smsStatus->message_left ?? 0 }}</td>
                                @else
                                    <td>{{ $instance->message_balance ?? 0 }}</td>
                                @endif
                                <td>
                                    @if($instance->type === 'whatsapp')
                                        @if($instance->is_paid == 0)
                                            <span class="badge badge-warning">{{ __('waiting_for_payment') }}</span>
                                        @elseif($instance->status == 1 && $instance->is_paid == 1)
                                            <span class="badge badge-success">{{ __('approved_&_running') }}</span>
                                        @elseif($instance->connect_status != 'qr' && $instance->is_paid == 1)
 @if((int)$instance->instance_id == 0)
                                        @php
                                            app('\App\Http\Controllers\Message')->createInstance();
                                        @endphp
@else
<button 
    class="btn btn-primary btn-sm" 
    onclick="getPairingCode('{{ $instance->instance_id }}', '{{ $instance->id }}')">
    <i class="fas fa-link mr-1"></i> {{ __('get_pairing_code') }}
</button>

<!-- Pairing Code Modal -->
<div class="modal fade" id="pairingCodeModal_{{ $instance->id }}" tabindex="-1" role="dialog" aria-labelledby="pairingCodeModalLabel_{{ $instance->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pairingCodeModalLabel_{{ $instance->id }}">{{ __('whatsapp_pairing_instructions') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="pairingCodeContent_{{ $instance->id }}">
                <div class="text-center">
                    <span class="spinner-border text-primary"></span>
                    <p>{{ __('loading_pairing_code') }}...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="window.location.reload();">{{ __('close') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
function getPairingCode(instanceId, modalId) {
    // Show modal
    $('#pairingCodeModal_' + modalId).modal('show');
    // Show loading
    $('#pairingCodeContent_' + modalId).html(
        `<div class="text-center">
            <span class="spinner-border text-primary"></span>
            <p>{{ __('loading_pairing_code') }}...</p>
        </div>`
    );
    // Ajax request
    $.ajax({
        url: "{{ url('message/requestPairCode') }}/" + instanceId,
        type: "GET",
        dataType: "json",
        success: function(response) {
            let html = `
                <ol>
                    <li>{{ __('open_whatsapp_on_your_phone') }}</li>
                    <li>{{ __('tap_menu_or_settings_and_select_linked_devices') }}</li>
                    <li>{{ __('tap_link_a_device') }}</li>
                    <li>{{ __('enter_the_pairing_code_below_when_prompted') }}</li>
                </ol>
            `;
            if(response.code){
                html += `<div class="alert alert-success text-center" style="font-size:1.5em;">
                    <strong>{{ __('pairing_code') }}:</strong> <span style="letter-spacing:2px;">${response.code}</span>
                </div>`;
            }
            if(response.qr){
                html += `<div class="text-center mb-2">
                    <img src="${response.qr}" alt="QR Code" style="max-width:200px;">
                    <div class="small text-muted mt-2">{{ __('scan_this_qr_code_if_prompted') }}</div>
                </div>`;
            }
            if(response.message){
                html += `<div class="alert alert-info mt-2">${response.message}</div>`;
            }
            $('#pairingCodeContent_' + modalId).html(html);
        },
        error: function(xhr) {
            $('#pairingCodeContent_' + modalId).html(
                `<div class="alert alert-danger">{{ __('failed_to_get_pairing_code') }}</div>`
            );
        }
    });
}
</script>
@endif
                                        <!-- <span class="badge badge-danger">{{ __('reconnecting') }}</span> -->
                                        @elseif($instance->connect_status == 'qr' && $instance->is_paid == 1)
                                            <span class="badge badge-success">{{ __('connected') }}</span>
                                        @else
                                          
                                            <span class="badge badge-secondary">{{ __('pending_approval') }}</span>
                                        @endif
                                    @else
                                        @if($instance->status)
                                            <span class="badge badge-success">{{ __('approved') }}</span>
                                        @else
                                            <span class="badge badge-secondary">{{ __('pending_approval') }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($instance->type === 'whatsapp')
                                        @if($instance->is_paid == 0)

 
                                            @if(empty($booking))
                                                <a href="{{ url('home/upgrade') }}" class="btn btn-warning btn-sm">
                                                    {{ __('pay') }}
                                                </a>
                                            @else
                                                <button 
                                                    class="btn btn-warning btn-sm" 
                                                    data-toggle="modal" 
                                                    data-target="#payModal{{$instance->id}}">
                                                    {{ __('pay') }}
                                                </button>
                                            @endif

                                            <!-- Payment Modal -->
                                            <div class="modal fade" id="payModal{{$instance->id}}" tabindex="-1" role="dialog" aria-labelledby="payModalLabel{{$instance->id}}" aria-hidden="true">
                                                <div class="modal-dialog modal-lg" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="payModalLabel{{$instance->id}}">{{ __('pay_for_whatsapp_instance') }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body p-0">
                                                            @php
                                                                // Simulate $booking for pay.blade.php
                                                                $booking =\App\Models\AdminBooking::where([
                                                                    'admin_package_id' => 4,
                                                                    'user_id' => Auth::user()->id,
                                                                ])->first();
                                                                $minimal = true;
                                                            @endphp
                                                            @include('payment.pay', ['booking' => $booking, 'minimal' => true])
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                  @endif
                                @if($instance->is_paid == 1)
                                    @if($instance->type === 'bulksms')
                                        <button 
                                            class="btn btn-info btn-sm" 
                                            data-toggle="modal" 
                                            data-target="#addbulksms">
                                            {{ __('buy_message') }}
                                        </button>

                                        <!-- Buy Bulk SMS Modal -->

                                    @elseif($instance->type === 'whatsapp')

                                    <!-- here we will enable this later after seing more sms are getting sent than normal -->
                                        <!-- <button 
                                            class="btn btn-info btn-sm" 
                                            data-toggle="modal" 
                                            data-target="#addBalanceWhatsapp{{$instance->id}}">
                                            {{ __('add_balance') }}
                                        </button> -->

                                        <!-- Add Balance Modal for WhatsApp -->
                                        <div class="modal fade" id="addBalanceWhatsapp{{$instance->id}}" tabindex="-1" role="dialog" aria-labelledby="addBalanceWhatsappLabel{{$instance->id}}" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <form method="POST" action="#">
                                                    @csrf
                                                    <input type="hidden" name="instance_id" value="{{ $instance->id }}">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="addBalanceWhatsappLabel{{$instance->id}}">{{ __('add_balance_to') }} {{ $instance->name }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label for="amount_{{$instance->id}}">{{ __('enter_amount_to_add') }}:</label>
                                                                <input type="number" min="1" class="form-control" name="amount" id="amount_{{$instance->id}}" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-primary">{{ __('add_balance') }}</button>
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('cancel') }}</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                                      <form action="{{ url('message/channelDelete', $instance->uuid) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('{{ __('delete_this_instance?') }}')">{{ __('delete') }}</button>
                                    </form>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">{{ __('no_message_instances_found.') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Buy Message Modal -->



        <div class="modal fade" id="whatsapp" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('enable/upgrade_whatsapp_integration') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card-body" id="whatsapp_content">
                        <div class="pricingTable1 text-center">
                            <div class="p-3 m-2">
                                <?php
                              
                                $whatsapp_price =\App\Models\AdminPackage::where('name','whatsapp')->first()->price;

                                ?>
                                <h3 class="amount amount-border d-inline-block">{{ __('tsh') }} <?= number_format($whatsapp_price) ?></h3>
                                <small class="font-12 text-muted">{{ __('per_one_sender') }}</small>
                            </div>
                            <hr class="hr-dashed">
                            <ul class="list-unstyled pricing-content-2 text-left py-3 border-0 mb-0">
                                <li><i class="fa fa-check text-success"></i> {{ __('send_and_receive_messages_to_members_via_whatsapp') }}</li>
                                <li><i class="fa fa-check text-success"></i> {{ __('send_photo_audio_videos_etc_to_event_member_via_your_whatsapp') }} </li>
                                <li><i class="fa fa-check text-success"></i> {{ __('engage_globarly,_not_limited_to_your_country') }}</li>
                                {{-- <li><i class="fa fa-check text-success"></i> <strong>Share invitation cards, contribution cards, and other documents easily</strong></li> --}}

                                <li><i class="fa fa-check text-success"></i> {{ __('send_almost_up_to_unlimited_messages_per_day') }}</li>
                               
                               
                                <li>{{ __('enter_your_whatsapp_number_below_to_proceed') }} 
                                    <input value="{{ Auth::user()->phone }}"  type="text" style="border: 1px solid #00a65a !important" placeholder="{{ __('enter_your_whatsapp_number_here') }}" class="form-control" id="whatsapp_phone_number" /></li>

                            </ul>

                            
                            <div class="pricing_footer">
                                <a href="javascript:void(0);" class="btn btn-success btn-block" id="enable_whatsapp_integration" role="button">{{ __('enable_whatsapp_integration') }} <span>{{ __('now!') }}</span></a>

                            </div>
                        </div><!--end pricingTable-->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onmousedown="window.location.reload()" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                </div>
            </div>
        </div>
    </div>
    
 <div class="modal fade" id="bulksms" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('bulk-sms_sender_name_registration') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="card-body" id="whatsapp_content">
                        <div class="alert alert-info">
                            <strong>{{ __('important') }}:</strong> {{ __('to_register_a_bulk-sms_sender_name,_you_must_submit_the_following_details_for_approval_by_tcra._the_approval_process_may_take_up_to_3_weeks.') }}
                        </div>
                        <div class="pricingTable1 text-center">
                            <div class="p-3 m-2">
                                <?php
                                $bulksms_price = \App\Models\AdminPackage::where('name','bulksms')->first()->price ?? 0;
                                ?>
                                <h3 class="amount amount-border d-inline-block">{{ __('tsh') }} <?= number_format($bulksms_price) ?></h3>
                                <small class="font-12 text-muted">{{ __('per_sender_name') }}</small>
                            </div>
                            <hr class="hr-dashed">

                            <form method="POST" action="#" enctype="multipart/form-data" class="text-left">
                                @csrf
                                <div class="form-group">
                                    <label for="sender_name">{{ __('sender_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" maxlength="11" pattern="^[A-Za-z0-9 ]{1,11}$" title="{{ __('maximum_11_characters._letters,_numbers,_and_spaces_only.') }}" class="form-control" id="sender_name" name="sender_name" required>
                                    <small class="form-text text-muted">{{ __('maximum_11_characters._no_special_characters_allowed.') }}</small>
                                </div>
                                <div class="form-group">
                                    <label for="nida_number">{{ __('nida_number') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nida_number" name="nida_number" required>
                                </div>
                                <div class="form-group">
                                    <label for="intro_letter">{{ __('introduction_letter') }} <span class="text-danger">*</span></label>
                                    <div>
                                        <a href="{{ asset('sample_documents/sample_intro_letter.docx') }}" target="_blank" class="btn btn-link btn-sm mb-2">
                                            <i class="fa fa-download"></i> {{ __('download_sample_introduction_letter') }}
                                        </a>
                                    </div>
                                    <input type="file" class="form-control-file" id="intro_letter" name="intro_letter" accept=".pdf,.doc,.docx" required>
                                    <small class="form-text text-muted">
                                        {{ __('the_letter_must_introduce_the_applicant_in_detail_and_include_a_sample_message_to_be_sent._accepted_formats:_pdf,_doc,_docx.') }}
                                    </small>
                                </div>
<?php
if (is_trial()) {
    echo '<a href="' . url('home/upgrade') . '" class="btn btn-warning btn-block">' . __('submit_sender_name_for_approval') . '</a>';
} else {
    echo '<button type="submit" class="btn btn-primary btn-block">' . __('submit_sender_name_for_approval') . '</button>';
}
?>

                          </form>

                          
                        </div><!--end pricingTable-->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onmousedown="window.location.reload()" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addbulksms" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('purchase_more_sms') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body" id="buy_sms_content">
                <div class="pricingTable1 text-center">
                    <p>{{ __('your_current_price') }}</p>
                    <?php
                    $package = getPackage();
                    $sms_price = 35;
                    if (!empty($package)) {
                        /**
                         * Disabled feature for market entry
                         */
//                        if ($package->admin_package_id == 1) {
//                            $sms_price = 35;
//                        } else if ($package->admin_package_id == 2) {
//                            $sms_price = 28;
//                        } else if ($package->admin_package_id == 3) {
//                            $sms_price = 20;
//                        }
                        //---end
                        $sms_price = 20;
                    }
                    ?>
                    <div class="p-3 m-2">
                        <h3 class="amount amount-border d-inline-block">{{ __('tsh') }} <?= $sms_price ?></h3>
                        <small class="font-12 text-muted">{{ __('per_sms') }}</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="code_verification">{{ __('enter_number_of_sms') }}</label>
                    <div class="input-group">
                        <input type="text" id="example-input2-group2"   name="code" class="form-control" placeholder="{{ __('number_of_sms') }}">
                        <span class="input-group-append">
                            <button type="button" class="btn  btn-sm btn-primary" id="buy_sms_btn">{{ __('submit') }}</button>
                        </span>
                    </div>                                                    
                </div>

            </div>
            <div class="modal-footer">
                <button type="button"  onmousedown="window.location.reload()" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
            </div>
        </div>
    </div>
</div>


</div>
<script type="text/javascript">
     $('#enable_whatsapp_integration').mousedown(function () {
            var phone = $('#whatsapp_phone_number').val();
            if (phone == '') {
                $('#whatsapp_phone_number').css('border', '2px solid red').after("<b class='text-red'>{{ __('please_enter_your_whatsapp_number_to_proceed') }}</b>");
            } else {
                //create a control number for this school to pay  
                $.ajax({
                    type: 'POST',
                    url: "<?= url('payment/createReference') ?>",
                    data: {phone: phone, type: 'whatsapp'},
                    dataType: "html",
                    beforeSend: function () {
                        $('#whatsapp_content').html('loading.....');
                    },
                    success: function (data) {
                        $('#whatsapp_content').html(data);
                    }
                });
            }
        });

    buy_sms = function () {
        $('#buy_sms_btn').mousedown(function () {
            var number_of_sms = $('#example-input2-group2').val();
            if (number_of_sms > 0) {
                $.ajax({
                    type: 'POST',
                    url: "<?= url('payment/createSMSReference') ?>",
                    data: {number_of_sms: number_of_sms, type: 'sms'},
                    dataType: "html",
                    success: function (data) {
                        $('#buy_sms_content').html(data);
                    }
                });
            }
        });
    }
    $(document).ready(buy_sms);
// Set package id and name in modal when Buy Messages is clicked
$('#buyMessageModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget)
    var packageId = button.data('package')
    var packageName = button.data('packagename')
    var modal = $(this)
    modal.find('#modal_package_id').val(packageId)
    modal.find('#modal_package_name').text(packageName)
});
</script>
@endsection
