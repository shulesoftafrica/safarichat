@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <!-- contact area -->
    <div class="content-block">
        <!-- Search Filter -->
        <div class="search-filter wadding-vanues-filter">
            <div class="container">
                <form class="filter-form" action="wedding-venues-search.html">
                    <div class="row">
                        <div class="col-lg-4 col-md-4 col-sm-6 col-6">
                            <input type="text" class="form-control" placeholder="We’re looking for">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                            <input type="text" class="form-control" placeholder="Near">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                            <input type="text" class="form-control" placeholder="Or Called">
                        </div>
                        <div class="col-lg-2 col-md-2 col-sm-6 col-6 d-flex">
                            <button class="btn btn-block gradient text-uppercase"> Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <input type="text" class="form-control" placeholder="We’re looking for" id="datetimepicker4">
        <!-- Search Filter END -->
        <div class="section-full bg-gray wadding-vanues-search">
            <div class="clearfix">
                <div class="row m-lr0 column-reverse-md">
                    <div class="col-xl-12 col-lg-12 p-lr0">
                        <div class="search-results-topbar">
                            <div class="search-results">
                                <h6 class="search-content"><span class="text-primary">36 of 559  </span>Wedding Venues in the United Kingdom</h6>
                                <a href="planner-shortlist.html" class="btn-link">Your Sortlist <span class="text-primary">(02)</span></a>
                                <select class="bs-select-hidden"> 
                                    <option>Our Favourites</option>
                                    <option>Most Popular</option>
                                    <option>Recently Added</option> 
                                </select><div class="btn-group bootstrap-select"><button type="button" class="btn dropdown-toggle btn-default" data-toggle="dropdown" title="Our Favourites"><span class="filter-option pull-left">Our Favourites</span>&nbsp;<span class="caret fa fa-caret-down"></span></button><div class="dropdown-menu open"><ul class="dropdown-menu inner" role="menu"><li data-original-index="0" class="selected"><a tabindex="0" class="" style="" data-tokens="null"><span class="text">Our Favourites</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li><li data-original-index="1"><a tabindex="0" class="" style="" data-tokens="null"><span class="text">Most Popular</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li><li data-original-index="2"><a tabindex="0" class="" style="" data-tokens="null"><span class="text">Recently Added</span><span class="glyphicon glyphicon-ok check-mark"></span></a></li></ul></div></div>
                                <a class="btn gray collapsed" role="button" data-toggle="collapse" href="#wadding-vanues-filter" aria-expanded="false" aria-controls="wadding-vanues-filter">Filter by</a>
                            </div>
                            <div class="filter-bx collapse fade" id="wadding-vanues-filter">
                                <form>
                                    <div class="form-group">
                                        <label class="label-title">Seated Dining Capacity</label>
                                        <div class="range-sliderbx">
                                            <div class="slider slider-horizontal" id="" style="margin-bottom: 0px;"><div class="slider-track"><div class="slider-track-low" style="left: 0px; width: 0%;"></div><div class="slider-selection tick-slider-selection" style="left: 0%; width: 15%;"></div><div class="slider-track-high" style="right: 0px; width: 85%;"></div></div><div class="tooltip tooltip-main top" role="presentation" style="left: 15%;"><div class="tooltip-arrow"></div><div class="tooltip-inner">5</div></div><div class="tooltip tooltip-min top" role="presentation"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div><div class="tooltip tooltip-max top" role="presentation"><div class="tooltip-arrow"></div><div class="tooltip-inner"></div></div><div class="slider-tick-label-container"><div class="slider-tick-label label-in-selection" style="width: 0px; position: absolute; left: 0%; margin-left: 0px;">$0</div><div class="slider-tick-label" style="width: 0px; position: absolute; left: 30%; margin-left: 0px;">$10</div><div class="slider-tick-label" style="width: 0px; position: absolute; left: 70%; margin-left: 0px;">$20</div><div class="slider-tick-label" style="width: 0px; position: absolute; left: 100%; margin-left: 0px;">$40</div></div><div class="slider-tick-container"><div class="slider-tick round in-selection" style="left: 0%;"></div><div class="slider-tick round" style="left: 30%;"></div><div class="slider-tick round" style="left: 70%;"></div><div class="slider-tick round" style="left: 100%;"></div><div class="slider-tick round"></div></div><div class="slider-handle min-slider-handle round" role="slider" aria-valuemin="0" aria-valuemax="10" aria-valuenow="5" tabindex="0" style="left: 15%;"></div><div class="slider-handle max-slider-handle round hide" role="slider" aria-valuemin="0" aria-valuemax="10" aria-valuenow="0" tabindex="0" style="left: 0%;"></div></div><input id="ex14" type="text" data-slider-ticks="[0, 10, 20, 40]" data-slider-ticks-snap-bounds="30" data-slider-ticks-labels="[&quot;$0&quot;, &quot;$10&quot;, &quot;$20&quot;, &quot;$40&quot;]" data-slider-ticks-positions="[0, 30, 70, 10]" class="ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all" data-value="5" value="5" style="display: none;">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="label-title">Your must-haves</label>
                                        <ul class="select-list clearfix list-inline">
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves1">
                                                    <label class="custom-control-label" for="must-haves1">Exclusive Use</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves2">
                                                    <label class="custom-control-label" for="must-haves2">Wedding Licence</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves3">
                                                    <label class="custom-control-label" for="must-haves3">On-Site Accommodation</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves4">
                                                    <label class="custom-control-label" for="must-haves4">Late Night Extension</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves5">
                                                    <label class="custom-control-label" for="must-haves5">Alcohol Licence</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves6">
                                                    <label class="custom-control-label" for="must-haves6">Late Night Extension</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves7">
                                                    <label class="custom-control-label" for="must-haves7">Alcohol Licence</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves8">
                                                    <label class="custom-control-label" for="must-haves8">Exclusive Use</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves9">
                                                    <label class="custom-control-label" for="must-haves9">Wedding Licence</label>
                                                </div>
                                            </li>
                                            <li>
                                                <div class="custom-control custom-checkbox checkbox-lg">
                                                    <input type="checkbox" class="custom-control-input" id="must-haves10">
                                                    <label class="custom-control-label" for="must-haves10">On-Site Accommodation</label>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="accordion form-accordion" id="accordionExample">
                                        <div class="card">
                                            <div class="card-header" id="headingOne">
                                                <a class="collapsed" href="javascript:;" data-toggle="collapse" data-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                                    Venue Types
                                                </a>
                                            </div>
                                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample">
                                                <div class="card-body">
                                                    <ul class="select-list clearfix list-inline">
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types1">
                                                                <label class="custom-control-label" for="venue-types1">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types2">
                                                                <label class="custom-control-label" for="venue-types2">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types3">
                                                                <label class="custom-control-label" for="venue-types3">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types4">
                                                                <label class="custom-control-label" for="venue-types4">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types5">
                                                                <label class="custom-control-label" for="venue-types5">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types6">
                                                                <label class="custom-control-label" for="venue-types6">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types7">
                                                                <label class="custom-control-label" for="venue-types7">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types8">
                                                                <label class="custom-control-label" for="venue-types8">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types9">
                                                                <label class="custom-control-label" for="venue-types9">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-types10">
                                                                <label class="custom-control-label" for="venue-types10">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header" id="headingTwo">
                                                <a class="collapsed" href="javascript:;" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                                    Venue Styles
                                                </a>
                                            </div>
                                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
                                                <div class="card-body">
                                                    <ul class="select-list clearfix list-inline">
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles1">
                                                                <label class="custom-control-label" for="venue-styles1">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles2">
                                                                <label class="custom-control-label" for="venue-styles2">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles3">
                                                                <label class="custom-control-label" for="venue-styles3">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles4">
                                                                <label class="custom-control-label" for="venue-styles4">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles5">
                                                                <label class="custom-control-label" for="venue-styles5">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles6">
                                                                <label class="custom-control-label" for="venue-styles6">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles7">
                                                                <label class="custom-control-label" for="venue-styles7">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles8">
                                                                <label class="custom-control-label" for="venue-styles8">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles9">
                                                                <label class="custom-control-label" for="venue-styles9">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-styles10">
                                                                <label class="custom-control-label" for="venue-styles10">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card">
                                            <div class="card-header" id="headingThree">
                                                <a class="collapsed" href="javascript:;" data-toggle="collapse" data-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                                    Venue Features
                                                </a>
                                            </div>
                                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                                                <div class="card-body">
                                                    <ul class="select-list clearfix list-inline">
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features1">
                                                                <label class="custom-control-label" for="venue-features1">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features2">
                                                                <label class="custom-control-label" for="venue-features2">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features3">
                                                                <label class="custom-control-label" for="venue-features3">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features4">
                                                                <label class="custom-control-label" for="venue-features4">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features5">
                                                                <label class="custom-control-label" for="venue-features5">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features6">
                                                                <label class="custom-control-label" for="venue-features6">Late Night Extension</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features7">
                                                                <label class="custom-control-label" for="venue-features7">Alcohol Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features8">
                                                                <label class="custom-control-label" for="venue-features8">Exclusive Use</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features9">
                                                                <label class="custom-control-label" for="venue-features9">Wedding Licence</label>
                                                            </div>
                                                        </li>
                                                        <li>
                                                            <div class="custom-control custom-checkbox checkbox-lg">
                                                                <input type="checkbox" class="custom-control-input" id="venue-features10">
                                                                <label class="custom-control-label" for="venue-features10">On-Site Accommodation</label>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center m-t10">
                                        <button class="btn gray">Apply filters (0)</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="bg-gray wadding-vanues-list">
                            <div class="row sp20">
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic1.jpg" alt="">
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic2.jpg" alt="">
                                            <div class="offer">
                                                <div class="event upcoming">Upcoming Event</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic3.jpg" alt="">
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic4.jpg" alt="">
                                            <div class="offer">
                                                <div class="event upcoming">Upcoming Event</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic5.jpg" alt="">
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic6.jpg" alt="">
                                            <div class="offer">
                                                <div class="event available">Deal Available</div>
                                                <div class="event upcoming">Upcoming Event</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic7.jpg" alt="">
                                            <div class="offer">
                                                <div class="event available">Deal Available</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic8.jpg" alt="">
                                            <div class="offer">
                                                <div class="event upcoming">Upcoming Event</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-6">
                                    <div class="listing-bx listing-sm">
                                        <div class="listing-media">
                                            <img src="images/listing/pic9.jpg" alt="">
                                            <div class="offer">
                                                <div class="event available">Deal Available</div>
                                                <div class="event upcoming">Upcoming Event</div>
                                            </div>
                                            <div class="media-info">
                                                <ul class="featured-star">
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                    <li><i class="fa fa-star"></i></li>
                                                </ul>
                                                <a class="like-btn" href="javascript:void(0)"><i class="fa fa-heart-o"></i></a>
                                            </div>
                                        </div>
                                        <div class="listing-info">
                                            <h3 class="title"><a href="listing-details.html">Wedding Venue Title Name</a></h3>
                                            <p class="location"><i class="fa fa-map-marker"></i> Ahmedabad, Gujarat.</p>
                                            <ul class="place-info">
                                                <li class="vendor-guest">
                                                    <span>Venue Vendor</span>
                                                    <h6 class="title">$120-$500</h6>
                                                </li>
                                                <li class="vendor-price">
                                                    <span>Guest</span>
                                                    <h6 class="title">120+</h6>
                                                </li>
                                            </ul>
                                            <a href="#" class="btn purple  btn-block gradient" data-toggle="modal" data-target="#exampleModal2">Request a brochure</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                  
                </div>
            </div>
        </div>
        <!-- Modal -->
        <div class="modal fade add-guest planner-modal-bx" id="exampleModal2" tabindex="-1" role="dialog" aria-labelledby="exampleModal2" aria-hidden="true">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
            </button>
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <h2 class="modal-title text-center">Send a message to Maidens Barn</h2>
                    <form>
                        <div class="form-group text-center">
                            <small class="small-bx">We will pass your details to the supplier so they can get back to you with a proposal.</small>
                        </div>
                        <div>
                            <ul class="popup-profile-info">
                                <li><strong>Email:</strong><span>kkgaur9736@gmail.com</span></li>
                                <li><strong>Names:</strong><span>kk gaur &amp; kk kuldeep</span></li>
                                <li><strong>Phone:</strong><span>[recommended]</span></li>
                                <li><strong>Ideal date:</strong><span>20th Feb 2020</span></li>
                                <li><strong>Estimated guests:</strong><span>[missing]</span></li>
                                <li><a class="collapsed btn-link" role="button" data-toggle="collapse" href="#edit" aria-expanded="false" aria-controls="edit">Edit <i class="fa fa-pencil"></i></a></li>
                            </ul>
                            <div class="filter-bx fade collapse gray-bx" id="edit" style="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Email address</label>
                                            <input type="text" class="form-control" placeholder="Add an email">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Phone number</label>
                                            <input type="text" class="form-control" placeholder="Add a phone number">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Your name</label>
                                            <input type="text" class="form-control" placeholder="">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Your partner's name</label>
                                            <input type="text" class="form-control" placeholder="">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Estimated guests</label>
                                            <input type="text" class="form-control" placeholder="">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="label-title">Ideal date</label>
                                            <input id="example_1" class="form-control" type="text"><div class="dpifs-fake-input"></div>	
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group gray-bx">
                            <label class="label-title">Your must-haves</label>
                            <ul class="select-list clearfix list-inline list-3">
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="more-info">
                                        <label class="custom-control-label" for="more-info">More info</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Brochure">
                                        <label class="custom-control-label" for="Brochure">Brochure</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Pricing-details">
                                        <label class="custom-control-label" for="Pricing-details">Pricing details</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Alternative-dates">
                                        <label class="custom-control-label" for="Alternative-dates">Alternative-dates</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Availability">
                                        <label class="custom-control-label" for="Availability">Availability</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Quote">
                                        <label class="custom-control-label" for="Quote">Quote</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Showround-date">
                                        <label class="custom-control-label" for="Showround-date">Showround date</label>
                                    </div>
                                </li>
                                <li>
                                    <div class="custom-control custom-checkbox checkbox-lg">
                                        <input type="checkbox" class="custom-control-input" id="Other">
                                        <label class="custom-control-label" for="Other">Other</label>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="form-group gray-bx">
                            <div class="text-center">
                                <a class="collapsed black btn-link" role="button" data-toggle="collapse" href="#maidens-barn" aria-expanded="false" aria-controls="maidens-barn"><i class="fa fa-plus-circle"></i> Add a custom message</a>
                            </div>
                            <div class="filter-bx fade collapse" id="maidens-barn" style="">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="m-t30">
                                            <label class="label-title">Edit your message below</label>
                                            <textarea class="form-control" placeholder="Add an email"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-center m-t30">
                                <button class="btn green gradient">Request brochure</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal End -->

    </div>
    <!-- contact area END -->
</div>
@endsection