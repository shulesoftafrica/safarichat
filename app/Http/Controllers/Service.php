<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
