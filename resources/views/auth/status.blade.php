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
                        <li class="breadcrumb-item active">Setup</li>
                    </ol>
                </div>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>

    <div class="row">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="mt-0 header-title">Status</h4>
                    <p class="text-muted mb-3">This is the final status of activity</p>

                    <div class="row">
                        <?=$status?>
                    </div>


                </div>


                <br/>
            </div>
        </div>
        <div class="col-lg-2"></div>
    </div> <!-- end col -->
</div> <!-- end row --> 

</div>
<script>

</script>

@endsection