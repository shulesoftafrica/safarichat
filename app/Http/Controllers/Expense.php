<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\EventsGuest;
use Auth;

class Expense extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        //$this->data['payments'] = \App\Models\Payment::whereIn('events_guests_id',EventsGuest::where('event_id', Auth::user()->usersEvents()->where('status', 1)->first()->event_id)->get(['id']))->get();
        $event_id = Auth::user()->event->id;
        // $this->data['budget_payments'] = \App\Models\BudgetPayment::whereIn('budget_id', \App\Models\Budget::where('event_id', $event_id)->get(['id']))->get();
        $this->data['budget_payments'] = \App\Models\BudgetPayment::where('created_by', Auth::user()->id)->get();
        $this->data['services'] = \App\Models\Service::all();
        return view('expense.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
     
//check if user has defined a budget and if not, then create a budget and add expense
        $event_id = Auth::user()->event->id;
        $budget = \App\Models\Budget::where('event_id', $event_id)->where('service_id', $request->service_id)->first();
        if (empty($budget)) {
            //create a budget
            $business_service_id =(int) $request->business_service_id > 0 ? $request->business_service_id : 1; //default business service id
              $budget = \App\Models\Budget::create([
                        'business_service_id' => $business_service_id,
                        'initial_price' => $request->amount,
                        'actual_price' => $request->amount,
                        'event_id' => $event_id,
                        'service_id' => $request->service_id,
                        'quantity' => 1

            ]);
        }
        \App\Models\BudgetPayment::create(['amount' => $request->amount, 'date' => $request->date, 'method' => $request->method, 'budget_id' => $budget->id, 'created_by' => Auth::user()->id, 'note' => $request->note]);
        return redirect()->back()->with('success', 'success');
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
        //
        $id=request()->segment(3);
        \App\Models\BudgetPayment::find($id)->update(request()->except('id'));
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
        \App\Models\BudgetPayment::find($id)->delete();
        return redirect()->back()->with('success', 'success');
    }

}
