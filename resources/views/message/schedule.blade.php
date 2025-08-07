@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <!-- Page-Title -->
        <div class="row">
            <div class="col-sm-12">
                <div class="page-title-box">
                    <div class="float-right">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('home') }}</a></li>
                            <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('message') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('schedule') }}</li>
                        </ol>
                    </div>
                </div><!--end page-title-box-->
            </div><!--end col-->
        </div>
        <!-- end page title end breadcrumb -->
        <div class="row">
            <div class="col-xl-12 col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-body">

                        <h4 class="mt-0 header-title">{{ __('sms_schedules') }}</h4>
                        <p class="text-muted mb-3">{{ __('create_a_message_that_you_want_to_be_sent_later_to_your_users') }}
                        </p>
                        <p>
                            @php
                                $channels = [];

                            @endphp
                            @if (count($channels)>0)
                                <button type="button" class="btn btn-success" data-toggle="modal"
                                    data-target="#exampleModal">
                                    {{ __('schedule_a_message') }}
                                </button>
                                @else
                                <a href="{{ url('home/upgrade') }}" class="btn btn-success">
                                    {{ __('schedule_a_message') }}
                                </a>
                            @endif
                        </p>
                        <br />
                        <div class="table-responsive">

                            </table><!--end /table-->

                            <table id="example1" class="table mb-0 dataTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('title') }}</th>
                                        <th>{{ __('message') }}</th>
                                        <th>{{ __('day_date') }}</th>
                                        <th>{{ __('time') }}</th>
                                        <th>{{ __('end_date') }}</th>
                                        <th>{{ __('send_to') }}</th>
                                        <th>{{ __('type') }}</th>
                                        <th>{{ __('channels') }}</th>
                                        <th>{{ __('action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                if (isset($schedules) && !empty($schedules)) {

                                    $i = 1;
                                    foreach ($schedules as $reminder) {
                                        ?>
                                    <tr>
                                        <td data-title="{{ __('title') }}">
                                            <?php echo $i; ?>
                                        </td>
                                        <td data-title="{{ __('title') }}">
                                            <?php echo ucfirst($reminder->title); ?>
                                        </td>
                                        <td data-title="{{ __('message') }}">
                                            <?php echo ucfirst($reminder->message); ?>
                                        </td>

                                        <td data-title="{{ __('date') }}">
                                            <?php
                                            echo (int) date('Y', strtotime($reminder->date)) == 1970 ? $reminder->days : date('Y-m-d', strtotime($reminder->date));
                                            ?>
                                        </td>
                                        <td data-title="{{ __('date') }}">
                                            <?php
                                            echo date('h:i', strtotime($reminder->time));
                                            ?>
                                        </td>

                                        <td data-title="{{ __('date') }}">
                                            <?php
                                            echo date('Y', strtotime($reminder->last_date)) == 1970 ? '' : date('d M Y h:i', strtotime($reminder->last_date));
                                            ?>
                                        </td>

                                        <td data-title="{{ __('users') }}">

                                            <a href="#" class="label label-default mb-2 mb-lg-0 badge badge-success"
                                                onclick="$('.user_arrow').toggle()" data-toggle="modal"
                                                data-animation="bounce" data-target=".user_list<?= $reminder->id ?>">
                                                {{ __('show_users') }}
                                            </a> <i class="dripicons-arrow-thin-right user_arrow"></i> <i
                                                class="dripicons-arrow-thin-down user_arrow" style="display: none"></i>



                                            <div class="modal fade user_list<?= $reminder->id ?>" tabindex="-1"
                                                role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title mt-0" id="myLargeModalLabel">
                                                                {{ __('list_of_users') }}</h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-hidden="true">×</button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php foreach ($reminder->guests() as $guest) { ?>
                                                            <span
                                                                class="badge badge-info">{{ ucfirst($guest->guest_name) }}</span>
                                                            <?php } ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">{{ __('close') }}</button>
                                                        </div>
                                                    </div><!-- /.modal-content -->
                                                </div><!-- /.modal-dialog -->
                                            </div>

                                        </td>
                                        <td data-title="{{ __('is_repeated') }}">
                                            <?php
                                            echo (int) $reminder->is_repeated == 1 ? __('repeated') : __('one_time');
                                            ?>
                                        </td>
                                        <td data-title="{{ __('message') }}">
                                            <?php echo ucfirst($reminder->channels); ?>
                                        </td>
                                        <td data-title="{{ __('action') }}">
                                            <!-- <a href="<?php echo url('message/editReminder/' . $reminder->id, __('edit')); ?>"><i class="las la-pen text-info font-18"></i> {{ __('edit') }}</a> -->
                                            <a href="<?php echo url('message/deleteReminder/' . $reminder->id, __('delete')); ?>"
                                                onclick="return confirm('{{ __('are_you_sure_you_want_to_delete_this_schedule') }}');"><i
                                                    class="las la-trash-alt text-danger font-18"></i>
                                                {{ __('delete') }}</a>



                                        </td>
                                    </tr>
                                    <?php
                                        $i++;
                                    }
                                }
                                ?>
                                </tbody>
                            </table>
                        </div><!--end /tableresponsive-->
                    </div><!--end card-body-->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade planner-modal-bx" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModal"
        aria-hidden="true" style="display: none;">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
        </button>
        <div class="modal-dialog" role="document">
            <form style="" class="form-horizontal" role="form" method="post"
                action="<?= url('message/schedule') ?>">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title mt-0" id="exampleModalLabel">{{ __('schedule_a_message') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">

                        <div class="col-lg-12">


                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label text-right">
                                    {{ __('title') }}
                                </label>
                                <div class="col-sm-10">
                                    <input type="text" required="" class="form-control" name="title"
                                        value="<?= old('title') ?>" id="title"
                                        placeholder="{{ __('reminder_title') }}">
                                </div>

                            </div>

                            <div class='form-group row'>
                                <label for="sms_message" class="col-sm-2 col-form-label text-right">
                                    {{ __('message') }}
                                </label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" required="" style="resize:vertical" id="smsbox" name="message"><?= old('sms_message') ?></textarea>

                                    <a class="label label-default mb-2 mb-lg-0 badge badge-success"
                                        onclick="$('.arrow').toggle()" data-toggle="collapse" href="#collapseExample"
                                        aria-expanded="false" aria-controls="collapseExample">
                                        {{ __('hashtag_guide') }}
                                    </a> <i class="dripicons-arrow-thin-right arrow"></i> <i
                                        class="dripicons-arrow-thin-down arrow" style="display: none"></i>
                                    <div class="collapse hide" id="collapseExample" style="">
                                        <div class="card mb-0 card-body">
                                            <p class="mb-0 text-muted">{{ __('supported_harshtag_lists') }}</p>
                                            <div class="table-responsive">
                                                <table class="table mb-0">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>{{ __('hashtag') }}</th>
                                                            <th>{{ __('meaning_it_will_pick') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <th scope="row">#name</th>
                                                            <td>{{ __('guest_name') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">#pledge</th>
                                                            <td>{{ __('pledge_amount') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">
                                                                #paid_amount
                                                            </th>
                                                            <td>{{ __('paid_amount') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <th scope="row">#balance</th>
                                                            <td>{{ __('remaining_amount_pledge_paid_amount') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td scope="row">#days_remain</td>
                                                            <td>{{ __('total_days_remains_up_to_the_event_date') }}</td>
                                                        </tr>
                                                        <!--    <tr>
                                                            <td scope="row">#total_collection</td>
                                                            <td>Total Amount collected from Guests</td>
                                                        </tr>
                                                        <tr>
                                                            <td scope="row">#total_guests</td>
                                                            <td>Total Guest that will attend</td>
                                                        </tr>-->

                                                    </tbody>
                                                </table><!--end /table-->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class='form-group row'>
                                <label for="email_user" class="col-sm-2 col-form-label text-right">
                                    {{ __('category') }}
                                </label>
                                <div class="col-sm-10">
                                    <select class="form-control select2" id="category_id" name="event_guest_category_id"
                                        style='width:100% !important'>
                                        <option value="0">{{ __('all') }}</option>
                                        <?php foreach ($usertypes as $usertype) { ?>
                                        <option value="<?= $usertype->id ?>"><?= $usertype->name ?></option>
                                        <?php }
                                    ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-2">
                                    <label for="form-field-select-0">
                                        {{ __('sub_category') }}
                                    </label>
                                </div>
                                <div class="col-sm-10">
                                    <select id="usertype_id" class="form-control" name ="criteria">
                                        <option value="{{ old('criteria') }}">&nbsp;</option>
                                        <option value="1">{{ __('all_guests') }}</option>
                                        <option value="3">{{ __('full_paid_guest') }}</option>
                                        <option value="4">{{ __('non_paid_guest') }}</option>
                                        <option value="5">{{ __('partially_paid_guest') }}</option>
                                        <option value="6">{{ __('custom_selection') }}</option>
                                    </select>
                                </div>
                                <div class="has-error">
                                    <?php if (form_error($errors, 'criteria')): ?>
                                    <?php echo form_error($errors, 'criteria'); ?>
                                    <?php endif ?>
                                </div>
                            </div>
                            <div class="form-group row" id="users_input">
                                <label class="col-sm-2 col-form-label text-right">
                                    <span id="exclude_status">{{ __('exclude') }}</span> {{ __('users') }}
                                </label>

                                <div class="col-sm-10">
                                    <select class="form-control select2" id="user_id" style='width:100% !important; '
                                        name="users[]" multiple="">

                                        <!--<option value="0">{{ __('all') }}</option>-->

                                    </select>

                                    <span class="spinner-border spinner-border-sm" id="loader_tag" style="display:none"
                                        role="status" aria-hidden="true">{{ __('loading') }}...</span>


                                </div>

                            </div>

                            <div class="form-group row">
                                <label class="col-sm-2 col-form-label text-right">
                                    {{ __('type') }}
                                </label>
                                <div class="col-sm-10">
                                    <input type="radio" class=" radio-inline repeat" name="is_repeated" value="1"
                                        id="repeated_yes"> {{ __('repeated') }},
                                    <input type="radio" class=" radio-inline repeat" name="is_repeated" value="0"
                                        id="repeated_no"> {{ __('one_time') }}

                                </div>

                            </div>

                            <div class="form-group row" id="one_time" style="display: none">
                                <label class="col-sm-2 col-form-label text-right">
                                    {{ __('schedule_date') }}
                                </label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control calendar" name="date"
                                        value="<?= old('date') ?>" id="date"
                                        placeholder="{{ __('reminder_date') }}" min="<?= date('Y-m-d') ?>">

                                </div>

                            </div>

                            <div class="form-group row" id="repeated" style="display: none">
                                <label class="col-sm-2 col-form-label text-right">
                                    {{ __('schedule_days') }}
                                </label>
                                <div class="col-sm-10">
                                    <?php
                                    $days = ['Monday' => __('monday'), 'Tuesday' => __('tuesday'), 'Wednesday' => __('wednesday'), 'Thursday' => __('thursday'), 'Friday' => __('friday'), 'Saturday' => __('saturday'), 'Sunday' => __('sunday')];
                                    echo form_dropdown('days[]', $days, old('days'), "id='days' class='select2_multiple form-control select2' style='width:100% !important' multiple='multiple'");
                                    ?>
                                </div>

                            </div>

                            <div class="form-group row" id="last_date" style="display: none">
                                <label class="col-sm-2 col-form-label text-right">
                                    {{ __('last_date') }}
                                </label>
                                <div class="col-sm-10">
                                    <input type="date" class="form-control" name="last_date"
                                        value="<?= old('date') ?>" id="last_date"
                                        placeholder="{{ __('reminder_date') }}">

                                </div>

                            </div>

                            <div class="form-group row">
                                <label for="examfrom" class="col-sm-2 col-form-label text-right">
                                    {{ __('time') }} <span class="red">*</span>
                                </label>
                                <div class="col-sm-10">
                                    <input type="time" class="form-control" id="schedule_time" name="time"
                                        value="" autocomplete="off">
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-2 col-form-label text-right">
                                    <label for="form-field-select-0">
                                        {{ __('source') }}</label>
                                </div>
                                <div class="col-sm-10">
                                    <select id="sms_keys_id" class="form-control select2" name ="channels[]"
                                        multiple="" style="width:100% !important">
                                        <option value="quick-sms">{{ __('quick_sms') }}</option>
                                        <option value="whatsapp">{{ __('whatsapp') }}</option>
                                        <!--<option value="telegram">{{ __('telegram') }}</option>-->
                                        <!-- <option value="email">{{ __('email') }}</option> -->
                                        <!-- <option value="phone-sms">{{ __('phone_sms') }}</option> -->
                                    </select>
                                </div>
                            </div>
                            <?= csrf_field() ?>

                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-dismiss="modal">{{ __('close') }}</button>
                        {{-- ?php --}}
                        {{-- $package = getPackage(); --}}

                        {{-- if (!empty($package) && (int) is_trial() == 0) {

                         /**
                         * Disabled feature for market entry
                         */
                         $max=0;
                       if ($package->admin_package_id == 1) {
                           $max = 0;
                       } else if ($package->admin_package_id == 2) {
                           $max = 2;
                       } else if ($package->admin_package_id == 3) {
                           $max = 5;
                       }
                        //-------------end-------------
                       
                        if ($max == 0) { --}}
                        {{-- ?> --}}
                        {{-- <a type="button" class="btn btn-primary"   href="<?= url('home/upgrade') ?>">
                                {{ __('save_changes') }}
                                <i class="mdi mdi-lock"></i>
                            </a> --}}

                        {{-- {{-- ?php --}}
                        {{-- } 
                          else {
                           ?>
                            --}}

                        <input value="{{ __('save_changes') }}" type="submit" class="btn btn-primary">
                        {{-- ?php
                    //     }
                    // } else {
                    //     ?> --}}

                        {{-- //     <a type="button" class="btn btn-primary"   href="?= url('home/upgrade') ?>">
                    //         {{ __('save_changes') }}
                    //         <i class="mdi mdi-lock"></i>
                    //     </a>
                    // ?php } ?> --}}

                    </div>
                </div>
            </form>
        </div>

    </div>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript">
        $('.repeat').click(function() {
            if ($('#repeated_yes').is(':checked')) {
                $('#repeated').show();
                $('#last_date').show();
                $('#one_time').hide();
            }
            if ($('#repeated_no').is(':checked')) {
                $('#repeated').hide();
                $('#last_date').hide();
                $('#one_time').show();
            }
        });
        //  $('#schedule_time').timepicker();
        usertype_id = function() {
            $('#usertype_id').change(function(event) {
                var criteria_id = $(this).val();
                if (criteria_id === '') {
                    return false;
                }
                if (criteria_id === '0' || criteria_id === '6') {
                    $('#exclude_status').html('{{ __('select') }}').addClass('label label-warning');
                } else {
                    $('#exclude_status').html('{{ __('exclude') }}').removeClass('label label-warning');
                }
                $('#loader_tag').show();
                var final_criteria = criteria_id === '6' ? 1 : criteria_id;
                $.ajax({
                    type: 'POST',
                    url: "<?= url('message/callUsers') ?>",
                    data: {
                        "criteria": final_criteria,
                        category_id: $('#category_id').val()
                    },
                    dataType: "html",
                    success: function(data) {
                        $('#user_id').html(data);
                        $('#loader_tag').hide();
                    }
                });

            });
        }
        $(document).ready(usertype_id);


        $('#template_id').change(function() {
            var templateID = $(this).val();
            var box = $(this).attr('data-type');
            $.ajax({
                type: 'POST',
                url: "<?= url('mailandsms/getTemplateContent') ?>",
                data: "templateID=" + templateID,
                dataType: "html",
                success: function(data) {
                    $('#smsbox').html(data);
                }
            });

        })
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@endsection
