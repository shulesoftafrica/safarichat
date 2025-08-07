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
                    <h4 class="mt-0 header-title">Compaign Setup</h4>
                    <p class="text-muted mb-3">Setup your Compaign details to easily get started</p>

                    <form class="form-parsley"  novalidate="" action="{{url('home/createEvent')}}" method="post">
                        <div class="form-group">
                            <label>Compaign Type</label>
                            <select class="form-control  @error('event_type_id') is-invalid @enderror" name="event_type_id" id="event_type_id" onchange="setCriteria(this.value)">
                                <option value=""></option>
                                <?php
                                $event_types = \App\Models\EventsType::where('status', 1)->get();
                                foreach ($event_types as $event_type) {
                                    ?>
                                    <option value="<?= $event_type->id ?>" data-name="<?= $event_type->name ?>"><?= $event_type->name ?></option>
                                <?php }
                                ?>
                            </select>
                            @error('event_type_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div><!--end form-group-->

                        <div class="form-group">
                            <label>Compaign End Date</label>
                            <input type="date" id="date" class="form-control  @error('date') is-invalid @enderror" name="date" required="" placeholder="Event Date" min="{{ date('Y-m-d') }}">
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
                            <label>Main Location</label>
                            <select class="form-control select2  @error('district_id') is-invalid @enderror" name="district_id">
                                <?php
                                $districts = \App\Models\District::all();
                                foreach ($districts as $district) {
                                    ?>
                                    <option value="<?= $district->id ?>"><?= $district->name . ' - ' . $district->region->name ?></option>
                                <?php }
                                ?>

                            </select>
                            @error('district_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div><!--end form-group-->
                        <div class="form-group">
                            <label>Event Name</label>
                            <input parsley-type="text" type="text" class="form-control  @error('name') is-invalid @enderror" id="event_name" value="{{Auth::user()->name}}" required="" name="name" placeholder="Event Name">
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div><!--end form-group-->


                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-success waves-effect waves-light">
                                Save
                            </button>
                            <button type="reset" class="btn btn-gradient-danger waves-effect m-l-5">
                                Cancel
                            </button>  
                            <?= csrf_field() ?>
                        </div>
                    </form>


                </div>


                <br/>
            </div>
        </div>
        <div class="col-lg-2"></div>
    </div> <!-- end col -->
</div> <!-- end row --> 

</div>
<script>
    function setCriteria(value) {
        if (value == '1' || value == '3') {
            $('#partner_wedding').show('slow');
        } else {
            $('#partner_wedding').hide('slow');
        }
    }
    partner_name = function () {
        $('#partner_name').keyup(function (e) {
            var val = $(this).val();
            var event_type = $("#event_type_id option:selected").text();
            $('#event_name').val(val + ' & ' + '<?= Auth::user()->name ?>' + ' ' + event_type);
        });
    }
    $(document).ready(partner_name);
</script>

@endsection