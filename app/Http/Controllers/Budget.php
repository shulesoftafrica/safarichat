<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\Service;
use \App\Models\Budget as UserBudget; //avoud double naming
use \App\Models\BudgetPayment;
use Auth;

class Budget extends Controller {

    public function __construct() {
       
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $event_id = Auth::user()->usersEvents()->orderBy('id', 'desc')->first()->event->id;
        $this->data['services'] = Service::all();
        $this->data['budgets'] = UserBudget::where('event_id', $event_id)->get();
        return view('budget.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        
        $service_id = request('business_service_id');
        $event_id = Auth::user()->event->id;
        $business_service = \App\Models\BusinessService::find($service_id);
       
        //if (empty($business_service)) {
           // return redirect()->back()->with('error', 'Error: You need to specify supplier details');
      //  }
        $price = request('unit_price') * request('quantity');
        $business_service_id=!empty($business_service) ? $business_service->id : 1;
        UserBudget::create([
            'business_service_id' => $business_service_id,
            'initial_price' => $price,
            'service_id' => request('service_id'),
            'actual_price' => $price,
            'event_id' => $event_id,
            'quantity' => (int) request('quantity')
        ]);
        return redirect()->back()->with('success', 'success');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit() {
        
        $price = request('unit_price') * request('quantity');
        UserBudget::find(request('tag_id'))->update([
            'actual_price' => $price,
            'quantity' => (int) request('quantity')
        ]);
        return redirect()->back()->with('success', 'success');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy() {
        $id = request()->segment(3);
        UserBudget::find($id)->delete();
        return redirect()->back()->with('success', 'success');
    }

    public function getBusinessService() {
        $service_id = request('service_id');
        $event_id = Auth::user()->usersEvents()->orderBy('id', 'desc')->first()->event->id;
        $budget = UserBudget::where('event_id', $event_id)->whereIn('business_service_id', \App\Models\BusinessService::where('service_id', $service_id)->get(['id']))->first();
        if ((int) $service_id > 0) {
            $business_services = \App\Models\BusinessService::where('service_id', $service_id)->get();
            $result = '  <select id="" name="business_service_id" class="form-control select2" style="width:100% !important" itemid="business_service_id">';

            foreach ($business_services as $business_service) {
                $selected = !empty($budget) && $budget->business_service_id == $business_service->id ? 'selected' : '';
                $result .= ' <option value="' . $business_service->id . '" ' . $selected . '>' . $business_service->business->name . '</option>';
            }
            $result .= ' </select>';
            echo $result;
        }
    }

    public function checkBudget() {
        $event_id = Auth::user()->usersEvents()->orderBy('id', 'desc')->first()->event->id;
        $budget = \App\Models\Budget::where('event_id', $event_id)
                        ->whereIn('business_service_id', \App\Models\BusinessService::where('service_id', request('service_id'))->get(['id']))->first();
        if (empty($budget)) {
            return $this->getBusinessService();
        } else {
            $result = '  <select id="" name="business_service_id" class="form-control select2" style="width:100% !important" itemid="business_service_id">';
            $result .= ' <option value="' . $budget->business_service_id . '">' . $budget->businessService->service_name . '</option>';
            $result .= ' </select>';
            echo $result;
        }
    }

}
