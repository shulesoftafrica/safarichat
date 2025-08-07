@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- Page-Title -->
    <div class="row">
        <div class="col-sm-12">
            <div class="page-title-box">
                <div class="float-right">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Home</a></li>
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Event</a></li>
                        <li class="breadcrumb-item active">Collections</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Payment Collections</h4>
                    <p class="text-muted mb-3">Manage list of payments collections </p>
               
                    <br/>
                    <data>
                        <embed src="http://localhost/dikodiko/storage/uploads/card.pdf" width="100%" height="500"/>
                    </data>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row --> 

</div>

@endsection