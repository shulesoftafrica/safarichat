{{-- filepath: resources\views\message\channel.blade.php --}}
@extends('layouts.app')
@section('content')
<div class="container">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Booking</a></li>
                        <li class="breadcrumb-item active">Show</li>
                    </ol>
                </div>
                <h4 class="page-title">Manage Bookings</h4>
            </div>
        </div>
    </div>
    <!-- end page title end breadcrumb -->

    <div class="row mb-4">
        <div class="col-sm-12">
         
                   @include('payment.pay')
          </div>   
    </div>

</div>
@endsection



