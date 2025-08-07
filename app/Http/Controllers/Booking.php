<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\AdminBooking; // Assuming you have a model named AdminBooking for bookings

class Booking extends Controller {

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        return view('guest.index');
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
        $booking = AdminBooking::find($id);
        $setlayout = 1;
        if ($booking) {
            return view('booking.show', compact('booking','setlayout'));
        } else {
            return redirect()->back()->with('error', 'Booking not found.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        //
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
    public function destroy($id) {
        $booking = AdminBooking::find($id);
        if ($booking) {
            $booking->delete();
            return redirect()->back()->with('success', 'Booking deleted successfully.');
        } else {
            return redirect()->back()->with('error', 'Booking not found.');
        }
    }

}
