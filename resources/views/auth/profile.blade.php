@extends('layouts.app')
@section('content')


<div class="row">
    <div class="col-lg-12 col-xl-9 mx-auto">
        <div class="card">
            <div class="card-body">
             

                <div class="">
                    <form class="form-horizontal form-material mb-0">
                        <div class="form-group">
                            <input type="text" placeholder="Event Name" class="form-control">
                        </div>

                        <div class="form-group row">
                            <div class="col-md-4">
                                <input type="email" placeholder="Email" class="form-control" name="example-email" id="example-email">
                            </div>
                            <div class="col-md-4">
                                <input type="password" placeholder="password" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <input type="password" placeholder="Re-password" class="form-control">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <input type="text" placeholder="Phone No" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <select class="form-control">
                                    <option>London</option>
                                    <option>India</option>
                                    <option>Usa</option>
                                    <option>Canada</option>
                                    <option>Thailand</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <textarea rows="5" placeholder="Message" class="form-control"></textarea>
                            <button class="btn btn-gradient-primary btn-sm px-4 mt-3 float-right mb-0">Update Profile</button>
                        </div>
                    </form>
                </div>
            </div>                                            
        </div>
    </div> <!--end col-->                                          
</div><!--end row-->

@endsection