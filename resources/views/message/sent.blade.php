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
                        <li class="breadcrumb-item active">{{ __('sms_sent') }}</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">{{ __('sms_sent') }}</h4>
                    <p class="text-muted mb-3">{{ __('manage_list_of_nessage_sent') }}</p>
                    <div class="row">
                        <div class="col-4 align-self-center">  </div>
                        <div class="col-4 align-self-center">
                            <div class="form-group">
                                <label>{{ __('select_channel') }}</label>
                                <select class="form-control" name="channel" onchange="window.location.href = '<?= url('message/sent') ?>/' + this.value">
                                    <option value=""></option>
                                    <!-- <option value="phone-sms">{{ __('phone_sms') }}</option> -->
                                    <option value="1">{{ __('quick_sms') }}</option>
                                    <option value="2">{{ __('whatsapp') }}</option>
                                    <!-- <option value="email">{{ __('email') }}</option> -->
                                </select>
                            </div>
                        </div>                    
                    </div>
                    <br/>

                    <table id="mainTable" class="table table-striped  mb-0 dataTable" style="cursor: pointer;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('phone') }}</th>
                                <th>{{ __('body') }}</th>
                                <th>{{ __('type') }}</th>
                                <th>{{ __('status') }}</th>
                                <th>{{ __('actions') }}</th>
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
                                    <td>{{ __('actions') }}</td>
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