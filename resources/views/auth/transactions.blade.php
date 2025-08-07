@extends('layouts.app')@section('content')
<div class="container"> <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('home') }}</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">{{ __('message') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('transactions') }}</li>
                    </ol>
                </div>
                <h4 class="page-title">{{ __('transactions') }}</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">{{ __('transactions') }}</h4>
                    <p class="text-muted mb-3">{{ __('manage_list_of_transactions') }}</p>
                    <table id="datatable" class="table table-bordered dt-responsive nowrap"
                        style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>Channel Name</th>
                                <th>{{ __('date') }}</th>
                                <th>{{ __('reference') }}</th>
                                <th>{{ __('amount') }}</th>
                                <th>{{ __('status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                            
                                <tr>
                                    <td>{{ $transaction->addon_id == 2 ? 'Bulk SMS' : ($transaction->addon_id == 4 ? 'Whatsapp' : 'Unknown Channel') }}</td>
                                    <td>{{ $transaction->created_at }}</td>
                                    <td>{{ $transaction->reference }}</td>
                                    <td>{{ $transaction->amount }}</td>
                                    <td>
                                        @if ($transaction->status == 0)
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif ($transaction->status == 1)
                                            <span class="badge badge-success">Paid</span>
                                        @else
                                            <span class="badge badge-danger">Failed</span>

                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
