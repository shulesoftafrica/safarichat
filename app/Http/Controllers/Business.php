<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use DB;

class Business extends Controller {

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('business.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function summary() {
        $business_service = \App\Models\BusinessService::where('business_id', Auth::user()->business->id);
        $budget = \App\Models\Budget::whereIn('business_service_id', $business_service->get(['id']));
        $this->data['requests'] = $budget->get();
        $this->data['amount'] = $budget->sum('actual_price');
        $this->data['business_services'] = $business_service->get();
        $this->data['page_viewers'] = \App\Models\PageViewer::whereIn('business_service_id', $business_service->get(['id']))->offset(0)
                ->limit(5)
                ->get();
        $this->data['reports'] = DB::select("select sum(amount), extract(month from created_at)||'-'||extract(year from created_at) as month_date from budget_payments where"
                        . " budget_id in (select id from budgets where business_service_id in (select id from business_services where business_id=" . Auth::user()->business->id . " )) group by month_date order by month_date asc ");

        return view('business.summary', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function request() {
        $business_service = \App\Models\BusinessService::where('business_id', Auth::user()->business->id);
        $budget = \App\Models\Budget::whereIn('business_service_id', $business_service->get(['id']));
        $this->data['requests'] = $budget->get();
        return view('business.request', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function product() {
        $id = request()->segment(4);
        $page = request()->segment(3);
        if ($page == 'view') {
            return $this->serviceProfile($id);
        } else if($page=='delete'){
            
            $service = \App\Models\Service::findOrFail($id);
            if ($service) {
                $folder = "storage/uploads/business_images/";
                if ($service->images) {
                    $images = explode(',', $service->images);
                    foreach ($images as $image) {
                        if (is_file($folder . $image)) {
                            unlink($folder . $image);
                        } elseif (is_file($image)) {
                            unlink($image);
                        }
                    }
                }
                $service->delete();
                return redirect('business/product')->with('success', 'success: Service deleted successfully');
            } else {
                return redirect('business/product')->with('error', 'error: You are not allowed to delete this service');
            }
        } else {
            $this->data['services'] = \App\Models\BusinessService::where('business_id', Auth::user()->business->id)->get();
            return view('business.product', $this->data);
        }
    }

    private function serviceProfile($id) {

        $business_info = \App\Models\BusinessService::where('business_id', Auth::user()->business->id)->where('service_id', $id);
        $this->data['business_service'] = $business_info->firstOrFail();
        $this->data['service'] = $this->data['business_service']->service;
        $this->data['business'] =Auth::user()->business;
        $budget = \App\Models\Budget::whereIn('business_service_id', $business_info->get(['id']));
        $this->data['bookings_payments_lists'] = $budget_payment = \App\Models\BudgetPayment::whereIn('budget_id', $budget->get(['id']));
        $this->data['amount'] = $budget_payment->whereMonth('created_at', date('m'))->whereYear('created_at', date('Y'))->sum('amount');
        $this->data['total_amount'] = \App\Models\BudgetPayment::whereIn('budget_id', $budget->get(['id']))->sum('amount');
        $this->data['page_viewers'] = \App\Models\PageViewer::where('business_service_id', $this->data['business']->id)->whereMonth('created_at', date('m'))->count();
        $this->data['bookings'] = $budget->count();
        $this->data['bookings_payment'] = $budget_payment->count();
        $this->data['reports'] = DB::select("select count(*), extract(month from created_at)||'-'||extract(year from created_at) as month_date from page_viewers where"
                        . "  business_service_id=" . $this->data['business']->id . " group by month_date order by month_date asc ");


        return view('business.profile', $this->data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function promote() {
        $this->data['data'] = [];
        return view('business.promote_service', $this->data);
    }

    public function promotion() {
        $unit_price = request('type');
        $number_of_users = request('number');
        $total_price = $unit_price * $number_of_users;
        //create a promotion tab
        $admin_package = \App\Models\AdminPackage::firstOrCreate([
                    'name' => 'promotion',
                    'is_addon' => 1
        ]);
        $payment = new \App\Http\Controllers\Payment();
        $param = [
            'uid' => time() . substr(str_shuffle('abcdefghkmnpqrstqrstvwoij'), 0, 3),
            'business_id' => Auth::user()->business->id,
            'promotion_type' => $unit_price == 10000 ? 'Advanced' : 'Basic',
            'total_users' => $number_of_users
        ];
        return $payment->createReference($admin_package->id, 'promotion', (float) str_replace(',', null, $total_price), $param);
    }

    public function createService() {
        $imageUrls = [];
 
        // If images are uploaded as files
        if (request()->hasFile('images')) {
            $files = request()->file('images');
            // Handle both single and multiple file uploads
            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $folder = "storage/uploads/business_images/";
                    if (!is_dir($folder)) {
                        mkdir($folder, 0777, true);
                    }
                    $name = time() . rand(1000, 9999) . '.' . $file->guessClientExtension();
                    $file->move($folder, $name);
                    $imageUrls[] = $folder . $name;
                }
            }
        }
        // If images are sent as filenames (strings)
        elseif (is_array(request('images'))) {
            foreach (request('images') as $image) {
                $imageUrls[] = $image;
            }
        }
    
        $data = array_merge(['business_id' => Auth::user()->business->id], request()->except('_token', 'images'));
       
        if (!empty($imageUrls)) {
            $data['images'] = implode(',', $imageUrls);
        }
    
        $business_service = \App\Models\BusinessService::firstOrCreate($data);
        //later on we will log and show records to admin if new service has been added
        return redirect('business/product/view/' . $business_service->service_id)->with('success', 'success: Proceed to customize your service page');}

    public function uploadFile() {
        $file = request()->file('file');

        $folder = "storage/uploads/media/";
        !is_dir($folder) ? mkdir($folder, 0777, true) : '';

        $name = time() . rand(4343, 3243434) . 'dkdk.' . $file->guessClientExtension();
        $move = $file->move($folder, $name);
        $path = $folder . $name;
        if ($move) {
            \App\Models\File::firstOrCreate([
                'mime' => $file->getClientOriginalExtension(),
                'name' => $name,
                'size' => $_FILES['file']['size'],
                'caption' => request('details'),
                'url' => $path,
                'file_album_id' => request('album_id')
            ]);
            return redirect()->back()->with('success', 'success');
        } else {
            return redirect()->back()->with('error', 'error: File failed to be uploaded, try again later');
        }
    }

    public function createAlbum() {
        $name = request('name');
        if (strlen($name) > 3) {
            \App\Models\FileAlbum::firstOrCreate(['name' => trim($name), 'user_id' => Auth::user()->id]);
            $albums = \App\Models\FileAlbum::whereUserId(Auth::user()->id)->get();
            $result = ' <select class="form-control select2" name="album_id" id="edit_album" style="width:100%">';
            foreach ($albums as $album) {
                $result .= '<option value="' . $album->id . '">' . $album->name . '</option>';
            }
            $result .= '</select>';
            echo $result;
        }
    }

    public function updateProfile() {
        $file = request()->file('service_logo');
        $business_service = \App\Models\BusinessService::find(request('id'));
        if ($file) {
            $folder = "storage/uploads/media/";
            !is_dir($folder) ? mkdir($folder, 0777, true) : '';
            $name = time() . rand(4343, 3243434) . 'dkdk.' . $file->guessClientExtension();
            $file->move($folder, $name);
            $path = $folder . $name;
            $object = array_merge(['service_logo' => $path], request()->except('service_logo'));
            is_file($business_service->service_logo) ? unlink($business_service->service_logo):'';
        } else {
            $object = request()->except('service_logo');
        }
        $business_service->update($object);
        return redirect()->back()->with('success', 'success');
    }

}
