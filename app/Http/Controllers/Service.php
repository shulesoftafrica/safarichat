<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class Service extends Controller {

        public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $this->data['suppliers'] = [];
        return view('service.index', $this->data);
    }

    public function jd(){
         $this->data['suppliers'] = [];
        return view('service.job-description', $this->data); 
    }

    /**
     * Handle tab content loading
     */
    public function getTabContent(Request $request)
    {
        $tab = $request->get('tab', 'products');
        
        // Security: only allow specific tab names
        $allowedTabs = ['products', 'job-description'];
        if (!in_array($tab, $allowedTabs)) {
            $tab = 'products';
        }
        
        switch ($tab) {
            case 'products':
                $products = Product::with('faqs')->orderBy('created_at', 'desc')->get();
                return view('service.products', compact('products'));
                
            case 'job-description':
                return view('service.job-description');
                
            default:
                return response()->json(['error' => 'Tab content not found'], 404);
        }
    }

    /**
     * Show the form for search a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search() {
        $this->data['services'] = \App\Models\Service::all();
        if ((int) request('service_id') > 0) {
            $this->data['businesses'] = \App\Models\BusinessService::where('service_id', (int) request('service_id'))->get();
        }
        return view('service.search', $this->data);
    }

    /**
     * selected a created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function selected() {
        $this->data['suppliers'] = [];
        return view('service.selected', $this->data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        $this->data['suppliers'] = [];
        return view('service.show', $this->data);
    }
}
