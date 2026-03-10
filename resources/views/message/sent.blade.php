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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('event') }}</a></li>
                        <li class="breadcrumb-item active">{{ __("messaging.sent_title") }}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">{{ __("messaging.sent.table_title") }}</h4>
                    <p class="text-muted mb-3">{{ __("messaging.sent_subtitle") }}</p>
                    <div class="row">
                        <div class="col-4 align-self-center">  </div>
                        <div class="col-4 align-self-center">
                            <div class="form-group">
                                <label>{{ __("messaging.sent.select_channel") }}</label>
                                <select class="form-control" name="channel" onchange="window.location.href = '<?= url('message/sent') ?>/' + this.value">
                                    <option value=""></option>
                                    <!-- <option value="phone-sms">{{ __('phone_sms') }}</option> -->
                                    <option value="1">{{ __("messaging.sent.quick_sms") }}</option>
                                    <option value="2">{{ __("messaging.sent.whatsapp") }}</option>
                                    <!-- <option value="email">{{ __('email') }}</option> -->
                                </select>
                            </div>
                        </div>                    
                    </div>
                    <br/>

                    <table id="mainTable" class="table-standard mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __("messaging.sent.phone") }}</th>
                                <th>{{ __("messaging.sent.body") }}</th>
                                <th>{{ __("messaging.sent.type") }}</th>
                                <th>{{ __("messaging.sent.status") }}</th>
                                <th>{{ __("messaging.sent.actions") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message) { ?>
                                <tr>
                                    <td>#</td>
                                    <td><?=$message->phone?></td>
                                    <td><?=$message->body?></td>
                                    <td><?=$message->type?></td>
                                    <td><?=$message->status?></td>
                                    <td>{{ __("messaging.sent.actions") }}</td>
                                </tr>
                            <?php }
                            ?>

                        </tbody>
                        <tfoot>

                        </tfoot>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row --> 

</div>

@endsection