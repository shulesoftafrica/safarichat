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
