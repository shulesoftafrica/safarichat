<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\AiSalesAgent;
use App\Models\UserType;

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
        // Load products with relationships for current user
        $this->data['products'] = Product::with('faqs')->forUser(auth()->id())->orderBy('created_at', 'desc')->get();
        return view('service.index', $this->data);
    }

    public function jd(){
         $this->data['suppliers'] = [];
         // Get existing AI agent for the current user
         $existingAgent = \App\Models\AiSalesAgent::where('user_id', auth()->id())->latest()->first();
         $this->data['existingAgent'] = $existingAgent;
         // Get user types for the form
         $this->data['userTypes'] = \App\Models\UserType::active()->orderBy('name')->get();
         
         // Debug logging
         \Log::info('JD Page Loading', [
             'user_id' => auth()->id(),
             'existing_agent_id' => $existingAgent ? $existingAgent->id : null,
             'existing_agent_user_id' => $existingAgent ? $existingAgent->user_id : null,
             'user_authenticated' => auth()->check()
         ]);
         
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
                $products = Product::with('faqs')->forUser(auth()->id())->orderBy('created_at', 'desc')->get();
                return view('service.products', compact('products'));
                
            case 'job-description':
                // Get existing AI agent for the current user
                $existingAgent = \App\Models\AiSalesAgent::where('user_id', auth()->id())->latest()->first();
                // Get user types for the form
                $userTypes = \App\Models\UserType::active()->orderBy('name')->get();
                return view('service.job-description', compact('existingAgent', 'userTypes'));
                
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
