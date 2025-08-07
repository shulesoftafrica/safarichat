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
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Profile</a></li>
                        <li class="breadcrumb-item active">settings</li>
                    </ol>
                </div>
                <h4 class="page-title">General Settings</h4>
            </div><!--end page-title-box-->
        </div><!--end col-->
    </div>
    <!-- end page title end breadcrumb -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    <h4 class="mt-0 header-title">List of items to be set</h4>
                    <p class="text-muted mb-3">Put the correct setting value to get the best out of the system
                    </p>


                    <div class="row">
                        <div class="col-sm-3">
                            <div class="nav flex-column nav-pills text-center" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <a class="nav-link waves-effect waves-light active" id="v-pills-home-tab" data-toggle="pill" href="#v-pills-home" role="tab" aria-controls="v-pills-home" aria-selected="true">User Accounts</a>
                                <!-- <a class="nav-link waves-effect waves-light" id="v-pills-profile-tab" data-toggle="pill" href="#v-pills-profile" role="tab" aria-controls="v-pills-profile" aria-selected="false">Events Settings</a> -->
                                <a class="nav-link waves-effect waves-light " id="v-pills-settings-tab" data-toggle="pill" href="#v-pills-settings" role="tab" aria-controls="v-pills-settings" aria-selected="false">Customer Category</a>
                            </div>
                        </div>
                        <div class="col-sm-9">
                            <div class="tab-content mo-mt-2" id="v-pills-tabContent">
                                <div class="tab-pane fade active show" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
                                    <div class="table-responsive">
                                        <h4 class="mt-0 header-title">Manage User Accounts</h4>
                                        <p class="text-muted mb-3">Each user account is able to login, and manage  activities, view reports and much more.. </p>
                                        <div>
                                            <p> 
                    

                                            </p>
                                        </div>
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Date Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($user_accounts as $account) {
                                                    ?>
                                                    <tr>
                                                        <th><?= $i ?></th>
                                                        <th><?= $account->user->name ?></th>
                                                        <th><?= $account->user->email ?></th>
                                                        <th><?= $account->user->phone ?></th>
                                                        <th><?= date('d M Y', strtotime($account->user->created_at)) ?></th>
                                                        <th>
                                                            <?php
                                                            if ($account->user->id == Auth::user()->id) {
                                                                ?>
                                                                <a onclick="editGuest('<?= $account->user->id ?>')" data-toggle="modal" href="#user_accounts"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php } ?>
                                                            <!-- <a href="#"> <i class="las la-trash-alt text-danger font-18"></i> Delete</a> -->
                                                        </th>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                                    <form class="form-parsley"  novalidate="" action="{{url('home/settings/null')}}" method="post">
                                        <div class="form-group">
                                            <label>Event Type</label>

                                            <?php
                                            $event_types = \App\Models\EventsType::all();
                                            ?>

                                            <?php
                                            $array_ = array();
                                            foreach ($event_types as $event_type) {
                                                $array_[$event_type->id] = $event_type->name;
                                            }
                                            echo form_dropdown("event_type_id", $array_, old("event_type_id", $event->event_type_id), "id='event_type_id'  onchange='setCriteria(this.value)' style='width:100% !important' class='form-control select2'   @error('event_type_id') is-invalid @enderror");
                                            ?>
                                            @error('event_type_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div><!--end form-group-->

                                        <div class="form-group">
                                            <label>Event Date</label>
                                            <input type="date" id="date" class="form-control  @error('date') is-invalid @enderror"  name="date" required="" value="<?= date('Y-m-d', strtotime($event->date)) ?>" placeholder="Event Date">
                                            @error('date')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div><!--end form-group-->
                                        <div id="partner_wedding" style="display: none">
                                            <div class="form-group">
                                                <label>Partner's Name</label>
                                                <div>
                                                    <input type="text" class="form-control  @error('partner_name') is-invalid @enderror" id="partner_name" parsley-type="name" name="partner_name" placeholder="Enter a valid Name">
                                                    @error('partner_name')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                    @enderror
                                                </div>
                                            </div><!--end form-group-->

                                            <div class="form-group">
                                                <label>Partner's Phone Number</label>
                                                <input parsley-type="number" type="text" class="form-control   @error('partner_phone') is-invalid @enderror" name="partner_phone" placeholder="Phone Number">
                                                @error('partner_phone')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                                @enderror
                                            </div><!--end form-group-->
                                        </div>
                                        <div class="form-group">
                                            <label>Location</label>
                                            <?php
                                            $districts = \App\Models\District::all();
                                            ?>

                                            <?php
                                            $array = array();
                                            foreach ($districts as $district) {
                                                $array[$district->id] = $district->name . ' - ' . $district->region->name;
                                            }
                                            echo form_dropdown("district_id", $array, old("district_id", $event->district_id), "id='district_id'  style='width:100% !important' class='form-control select2'   @error('district_id') is-invalid @enderror");
                                            ?>
                                            @error('district_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div><!--end form-group-->
                                        <div class="form-group">
                                            <label>Event Name</label>
                                            <input parsley-type="text" type="text" class="form-control  @error('name') is-invalid @enderror" id="event_name" value="<?= $event->name ?>" required="" name="name" placeholder="Event Name">
                                            @error('name')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div><!--end form-group-->

                                        <div class="form-group">
                                            <label>Event Live Url</label>
                                            <input parsley-type="text" type="text" class="form-control  @error('url') is-invalid @enderror" id="url" value="<?= $event->url ?>" required="" name="url" placeholder="Event Live Url">
                                            @error('url')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                            <span class="alert">To stream live, go to your youtube account, then click go live, create your event and specify time to start, then at the top of that youtube page, click share, a copy the url to paste in this input.
                                                Once you have saved, download a mobile app named streamlab, then login to your account, and once the time start, click go live and stream your event</span>
                                        </div><!--end form-group-->

                                        <div class="form-group mb-0">
                                            <button type="submit" class="btn btn-success waves-effect waves-light">
                                                Save
                                            </button>
                                            <input type="hidden" value="event" name="table"/>
                                            <input type="hidden" value="<?= strlen($event->uid) < 4 ? time() : $event->uid ?>" name="uid"/>
                                            <?= csrf_field() ?>
                                        </div>
                                    </form>

                                    <br/>
                                    <p>Generate Event Code for users to attend online. Do this only if you have added live url above</p>

                                    <button id="code_event" class="btn btn-secondary" onclick="$.get('<?= url('home/sendEventCode/null') ?>', {}, function (data) {
                                                alert(data)
                                            })">Generate Event Code</button>
                                </div>
                                <div class="tab-pane fade " id="v-pills-settings" role="tabpanel" aria-labelledby="v-pills-settings-tab">
                                    <h4 class="mt-0 header-title">Customer Categories</h4>
                                    <p class="text-muted mb-3">Manage list of Customer categories</p>
                                    <p>  <button type="button" class="btn btn-success" data-toggle="modal" data-target="#myModal">
                                            Add New Category
                                        </button></p>
                                    <!--<button type="button" class="btn btn-gradient-primary waves-effect waves-light" id="ajax-alert">Click me</button>-->
                                    <br/>
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Event Name</th>
                                                    <th>Customer Category</th>
                                                    <th>Total Customer</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $i = 1;
                                                foreach ($categories as $category) {
                                                    $total_guests = $category->event->eventsGuests()->where('event_guest_category_id', $category->id)->count();
                                                    ?>
                                                    <tr>
                                                        <th scope="row"><?= $i ?></th>
                                                        <th><?= $category->event->name ?></th>
                                                        <td><span id="category<?= $category->id ?>"><?= $category->name ?></span></td>
                                                        <th><?= $total_guests ?></th>
                                                        <td> 
                                                            <a onclick="editGuest('<?= $category->id ?>')" data-toggle="modal" href="#myModal"><i class="las la-pen text-info font-18"></i> Edit</a>
                                                            <?php
                                                            if ((int) $total_guests == 0) {
                                                                ?>
                                                                <a href="<?= url('guest/guestcategory/' . $category->id) ?>"><i class="las la-trash-alt text-danger font-18"></i> Delete</a>
                                                            <?php } else { ?>
                                                                <button type="button" class="btn btn-outline-light uitooltip" data-toggle="tooltip" data-placement="top" title="There are Customers in this category, You cannot delete. Delete first customers in this category if you want to delete it">
                                                                    <i class="las la-trash-alt text-danger font-18"></i> Delete
                                                                </button>
                                                            <?php } ?>

                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $i++;
                                                }
                                                ?>
                                            </tbody>
                                        </table><!--end /table-->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!--end card-body-->
            </div><!--end card-->
        </div> <!-- end col -->
    </div> <!-- end row -->

</div>
<div class="modal fade planner-modal-bx" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">New Category</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Category Name</label>
                        <input type="text" name="name" id="edit_guest_name" class="form-control" placeholder="Customer Category Name" required="">
                    </div>
                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="event_guest_category" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>

<div class="modal fade planner-modal-bx" id="user_accounts" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="display: none;">
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
    <div class="modal-dialog" role="document">
        <form class="modal-content start-here" id="ProfileStep5" action="<?= url('home/settings') ?>" method="post">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title mt-0" id="exampleModalLabel">Edit your information</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>

                <div class="modal-body">


                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Name</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group1" value="<?= Auth::user()->name ?>" name="name" class="form-control" placeholder="Name">

                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Email</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->email ?>" name="email" class="form-control" placeholder="Email">
                        </div>                                                    
                    </div>

                    <div class="form-group">
                        <label for="quantity" class=" col-form-label text-right">Phone</label>
                        <div class="input-group">
                            <input type="text" id="example-input2-group2" value="<?= Auth::user()->phone ?>" name="phone" class="form-control" placeholder="Phone">

                        </div>                                                    
                    </div>


                </div>
            </div>
            <div class="modal-footer text-center">
                <?= csrf_field() ?>
                <input type="hidden" id="edit_id" value="" name="edit"/>
                <input type="hidden" id="edit_guest" value="user" name="table"/>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-success" data-toggle="tooltip" data-placement="top">Save</button>
            </div>
        </form>


    </div>
</div>
<!-- Sweet-Alert  -->
<!--<script src="../plugins/sweet-alert2/sweetalert2.min.js"></script>
<script src="../assets/pages/jquery.sweet-alert.init.js"></script>-->

<script type="text/javascript">
    function editGuest(a) {
        $('#edit_guest_name').val($('#category' + a).text());
        $('#edit_id').val(a);
        //$('#ProfileStep5').attr('action', '<?= url('guest/edit/null') ?>');
    }
    function setCriteria(value) {
        return false;
    }
</script>
@endsection