@extends('layouts.app')
@section('content')

<div class="container-fluid">
                   

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-6 align-self-center">
                        @php
                            // $business_service->images is a comma-separated string of image URLs
                            $images = [];
                            if (!empty($business_service->images)) {
                                $images = array_filter(array_map('trim', explode(',', $business_service->images)));
                            }
                            if (empty($images)) {
                                $images = ['http://metrica.laravel.themesbrand.com/assets/images/products/02.png'];
                            }
                        @endphp

                        <div id="serviceImagesCarousel" class="carousel slide shadow rounded" data-bs-ride="carousel" style="background: #f8f9fa; max-width: 420px; margin: 0 auto;">
                            <div class="carousel-inner text-center">
                                @foreach($images as $idx => $img)
                                    <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                                        <img src="{{ $img }}" alt="Service Image {{ $idx+1 }}" class="mx-auto d-block rounded img-fluid" style="object-fit:contain;max-height:320px;background:#fff;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                var carousel = document.querySelector('#serviceImagesCarousel');
                                if (carousel) {
                                    var bsCarousel = new bootstrap.Carousel(carousel, {
                                        interval: 2500,
                                        ride: 'carousel',
                                        pause: false,
                                        wrap: true
                                    });
                                }
                            });
                        </script>
                    </div>
                    <!--end col-->
                    <div class="col-lg-6 align-self-center">
                        <div class="">
                            <span class="badge badge-soft-danger font-13 fw-semibold mb-2">{{$service->serviceCategory->name}}</span>
                            <h5 class="font-24 mb-0">{{$service->name}}</h5>
                            <p class="text-muted mb-2">Offered By: {{$business->name}}</p>
                            <br/>
                            <p class="text-muted mb-2">Location: {{$business->address}}</p>
                            <ul class="list-inline mb-2">
                                <br/>
                            </ul>
                            <h6 class="font-20 fw-bold">Tsh {{number_format($business_service->price)}} </h6>
                            <h6 class="font-13">Detail :</h6>
                            <p class="text-muted">{{ $business_service->details }}</p>
                            </p>
                           
                            <div class="mt-3">
                                <div class="d-flex align-items-center">
                                    <span class="me-2 fw-bold text-success" style="font-size: 1.2rem;">
                                        <i class="fab fa-whatsapp"></i> WhatsApp: 
                                    </span>
                                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $business->phone) }}" 
                                        class="fw-bold text-success ps-2" 
                                        style="font-size: 1.1rem; padding-left: 12px; text-decoration: underline;">
                                         {{ $business->phone ?? 'Not Available' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--end col-->
                </div>
                <!--end row-->
            </div>
            <!--end card-body-->
        </div>
        <!--end card-->
    </div>
    <!--end col-->
</div>


<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="mt-0">Related Services and Service Providers</h5>
                <p class="text-muted mb-3 font-14">Other services that might interest you.</p>
                </p>
            </div>
            <!--end card-body-->
        </div>
        <!--end card-->
        <div class="row">
<?php $relatedServices= \App\Models\BusinessService::where('service_id',$service->id)->get(); ?>
            @foreach($relatedServices as $related)
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            @if($related->discount)
                                <div class="ribbon1 rib1-primary">
                                    <span class="text-white text-center rib1-primary">{{ $related->discount }}% off</span>
                                </div>
                            @endif
                            <!--end ribbon-->
                            <img src="{{ $related->image_url ?? 'http://metrica.laravel.themesbrand.com/assets/images/products/05.png' }}" alt="" class="d-block mx-auto my-4" height="150">
                            <div class="d-flex justify-content-between align-items-center my-4">
                                <div>
                                    <p class="text-muted mb-2">{{ $related->serviceCategory->name ?? '' }}</p>
                                    <a href="{{ url('business.profile', $related->id) }}" class="header-title">{{ $related->name }}</a>
                                </div>
                                <div>
                                    <h4 class="text-dark mt-0 mb-2">
                                        Tsh {{ number_format($related->price) }}
                                        @if($related->old_price)
                                            <small class="text-muted font-14"><del>Tsh {{ number_format($related->old_price) }}</del></small>
                                        @endif
                                    </h4>
                                    <ul class="list-inline mb-0 product-review align-self-center">
                                        @for($i = 0; $i < floor($related->rating ?? 4.5); $i++)
                                            <li class="list-inline-item"><i class="la la-star text-warning font-16"></i></li>
                                        @endfor
                                        @if(isset($related->rating) && $related->rating - floor($related->rating) >= 0.5)
                                            <li class="list-inline-item"><i class="la la-star-half-o text-warning font-16 ms-n2"></i></li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="{{ url('business/product/view', $related->id) }}" class="btn btn-sm btn-primary shadow-sm" style="font-size: 1.1rem;">
                                    <i class="la la-eye me-1"></i> View Service
                                </a></div>
                        </div>
                        <!--end card-body-->
                    </div>
                    <!--end card-->
                </div>
            @endforeach
           
        </div>
        <!--end row-->
    </div>
   
</div>
<!--end row-->

                   
                </div>

@endsection