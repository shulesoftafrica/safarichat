<?php

namespace App\Http\Controllers;

use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingCalendarController extends Controller
{
    /**
     * Display a listing of booking calendars
     */
    public function index()
    {
        $business_id = Auth::user()->business->id;
        
        $calendars = BookingCalendar::where('business_id', $business_id)
            ->with('user')
            ->withCount(['bookingSlots as upcoming_bookings' => function($q) {
                $q->where('status', 'confirmed')
                  ->where('start_time', '>', now());
            }])
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Check if user can create more calendars
        $limitCheck = BillingService::canCreateBookingCalendar(Auth::user());
        
        return view('booking-calendars.index', compact('calendars', 'limitCheck'));
    }
    
    /**
     * Show the form for creating a new booking calendar
     */
    public function create()
    {
        // Check subscription limits
        $limitCheck = BillingService::canCreateBookingCalendar(Auth::user());
        
        if (!$limitCheck['can_create']) {
            return redirect()->route('booking-calendars.index')
                ->with('error', $limitCheck['message']);
        }
        
        return view('booking-calendars.create');
    }
    
    /**
     * Store a newly created booking calendar
     */
    public function store(Request $request)
    {
        // Check subscription limits
        $limitCheck = BillingService::canCreateBookingCalendar(Auth::user());
        
        if (!$limitCheck['can_create']) {
            return redirect()->back()
                ->with('error', $limitCheck['message'])
                ->withInput();
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'calendar_type' => 'required|in:demo,consultation,follow_up,meeting,call,custom',
            'default_duration_minutes' => 'required|integer|min:15|max:480',
            'buffer_minutes' => 'required|integer|min:0|max:60',
            'max_bookings_per_day' => 'nullable|integer|min:1',
            'max_bookings_per_week' => 'nullable|integer|min:1',
            'min_advance_hours' => 'required|integer|min:0',
            'max_advance_days' => 'required|integer|min:1|max:365',
            'working_hours' => 'required|array',
            'breaks' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = Auth::user();
        $business_id = $user->business->id;
        
        // Build availability rules
        $availabilityRules = [
            'working_hours' => $request->input('working_hours', []),
            'breaks' => $request->input('breaks', []),
            'blackout_dates' => $request->input('blackout_dates', []),
        ];
        
        $calendar = BookingCalendar::create([
            'business_id' => $business_id,
            'user_id' => $user->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'calendar_type' => $request->input('calendar_type'),
            'default_duration_minutes' => $request->input('default_duration_minutes'),
            'buffer_minutes' => $request->input('buffer_minutes'),
            'availability_rules' => $availabilityRules,
            'max_bookings_per_day' => $request->input('max_bookings_per_day'),
            'max_bookings_per_week' => $request->input('max_bookings_per_week'),
            'min_advance_hours' => $request->input('min_advance_hours'),
            'max_advance_days' => $request->input('max_advance_days'),
            'allow_ai_booking' => $request->boolean('allow_ai_booking', true),
            'allow_manual_booking' => $request->boolean('allow_manual_booking', true),
            'require_confirmation' => $request->boolean('require_confirmation', true),
            'is_active' => true,
        ]);
        
        return redirect()->route('booking-calendars.index')
            ->with('success', 'Booking calendar created successfully');
    }
    
    /**
     * Show the form for editing the specified booking calendar
     */
    public function edit($id)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->findOrFail($id);
        
        return view('booking-calendars.edit', compact('calendar'));
    }
    
    /**
     * Update the specified booking calendar
     */
    public function update(Request $request, $id)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'calendar_type' => 'required|in:demo,consultation,follow_up,meeting,call,custom',
            'default_duration_minutes' => 'required|integer|min:15|max:480',
            'buffer_minutes' => 'required|integer|min:0|max:60',
            'max_bookings_per_day' => 'nullable|integer|min:1',
            'max_bookings_per_week' => 'nullable|integer|min:1',
            'min_advance_hours' => 'required|integer|min:0',
            'max_advance_days' => 'required|integer|min:1|max:365',
            'working_hours' => 'required|array',
            'breaks' => 'nullable|array',
            'blackout_dates' => 'nullable|array',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Build availability rules
        $availabilityRules = [
            'working_hours' => $request->input('working_hours', []),
            'breaks' => $request->input('breaks', []),
            'blackout_dates' => $request->input('blackout_dates', []),
        ];
        
        $calendar->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'calendar_type' => $request->input('calendar_type'),
            'default_duration_minutes' => $request->input('default_duration_minutes'),
            'buffer_minutes' => $request->input('buffer_minutes'),
            'availability_rules' => $availabilityRules,
            'max_bookings_per_day' => $request->input('max_bookings_per_day'),
            'max_bookings_per_week' => $request->input('max_bookings_per_week'),
            'min_advance_hours' => $request->input('min_advance_hours'),
            'max_advance_days' => $request->input('max_advance_days'),
            'allow_ai_booking' => $request->boolean('allow_ai_booking'),
            'allow_manual_booking' => $request->boolean('allow_manual_booking'),
            'require_confirmation' => $request->boolean('require_confirmation'),
        ]);
        
        return redirect()->route('booking-calendars.index')
            ->with('success', 'Booking calendar updated successfully');
    }
    
    /**
     * Remove the specified booking calendar
     */
    public function destroy($id)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->findOrFail($id);
        
        // Check if calendar has upcoming bookings
        $upcomingBookings = BookingSlot::where('booking_calendar_id', $id)
            ->whereIn('status', ['reserved', 'confirmed'])
            ->where('start_time', '>', now())
            ->count();
        
        if ($upcomingBookings > 0) {
            return redirect()->back()
                ->with('error', "Cannot delete calendar with {$upcomingBookings} upcoming booking(s)");
        }
        
        $calendar->delete();
        
        return redirect()->route('booking-calendars.index')
            ->with('success', 'Booking calendar deleted successfully');
    }
    
    /**
     * Toggle calendar active status
     */
    public function toggle($id)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->findOrFail($id);
        
        $calendar->update([
            'is_active' => !$calendar->is_active
        ]);
        
        $status = $calendar->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Calendar {$status} successfully");
    }
    
    /**
     * Preview available slots for a calendar
     */
    public function preview($id)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->findOrFail($id);
        
        $startDate = request('start_date', now()->format('Y-m-d'));
        $endDate = request('end_date', now()->addDays(7)->format('Y-m-d'));
        
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $slots = $calendar->generateSlotsForDateRange($start, $end);
        $totalSlots = array_sum(array_map('count', $slots));
        
        // If JSON is requested (for API), return JSON
        if (request()->wantsJson() || request()->has('format') && request('format') === 'json') {
            return response()->json([
                'success' => true,
                'calendar' => $calendar->name,
                'slots' => $slots,
                'total_slots' => $totalSlots
            ]);
        }
        
        // Otherwise return the view
        return view('booking-calendars.preview', compact('calendar', 'slots', 'totalSlots', 'startDate', 'endDate'));
    }
}
