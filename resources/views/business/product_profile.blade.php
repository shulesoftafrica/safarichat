@extends('layouts.app')
@section('content')
<div class="container-fluid">
                    <!-- Page-Title -->
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="page-title-box">
                                <div class="float-right">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Business</a></li>
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Service</a></li>
                                        <li class="breadcrumb-item active"><?=$business->service->name?></li>
                                    </ol>
                                </div>
                                <h4 class="page-title"><?=$business->service->name?></h4>
                            </div><!--end page-title-box-->
                        </div><!--end col-->
                    </div>
                    <!-- end page title end breadcrumb -->
                    <div class="row">  
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <img src="<?=asset('images/about/pic1.jpg')?>" alt="" class="img-fluid">

                                    <ul class="list-group">
                                        <li class="list-group-item align-items-center d-flex">
                                            <a href="#">
                                                <div class="media-body align-self-center"> 
                                                    <h5 class="m-0 font-13">Descriptions</h5>
                                                    <p class="text-muted mb-0">Today 07:30 AM</p>                                                                                             
                                                </div><!--end media body-->
                                            </a>
                                        </li>
                                         <li class="list-group-item align-items-center ">
                                           <a href="#">
                                                <div class="media-body align-self-center"> 
                                                    <h5 class="m-0 font-13">Photos/Images</h5>
                                                    <p class="text-muted mb-0">01 June 2019 - 09:30 AM</p>                                                                                           
                                                </div><!--end media body-->
                                            </a>
                                        </li>
                                        <li class="list-group-item align-items-center ">
                                            <a href="#" class="media">
                                               <div class="media-body align-self-center"> 
                                                    <h5 class="m-0 font-13">Page Ratings</h5>
                                                    <p class="text-muted mb-0">Today 12:30 PM</p>                                                                                            
                                                </div><!--end media body-->
                                            </a>
                                        </li>
                                        <li class="list-group-item align-items-center">
                                            <a href="#" class="media">
                                                <div class="media-body align-self-center"> 
                                                    <h5 class="m-0 font-13">Comments and Feedback</h5>
                                                    <p class="text-muted mb-0">Tomorrow 10:30 AM</p>                                                                                           
                                                </div><!--end media body-->
                                            </a>
                                        </li>
                                       
                                       
                                    </ul> 
                                </div><!--end card-body-->
                            </div><!--end card-->
                        </div><!--end col-->                      
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-body">
                                    <div class="main_content"></div>
                                    
                                </div><!--end card-body-->
                            </div><!--end card-->
                        </div><!--end col-->
                    </div><!-- End row -->

</div>
<script type="text/javascript">
    load_page=function(){
        $('').mousedown(function(){
            $.ajax({
                method:'POST',
                url:'',
                data:{},
                success:function(data){
                    alert(data);
                }
            });
        });
    }
    $(document).ready(load_page);
    load_contact = function () {
        $.getJSON('https://www.google.com/m8/feeds/contacts/default/full/?access_token=' +
                authResult.access_token + "&alt=json&callback=?", function (result) {
                    console.log(JSON.stringify(result));
                });
    }
    //  $(document).ready(load_contact);
</script>
@endsection