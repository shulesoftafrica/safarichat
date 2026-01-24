<?php

namespace App\Http\Controllers;

use App\Models\BookingCalendar;
use App\Models\BookingSlot;
use App\Models\BusinessContact;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingSlotController extends Controller
{
    /**
     * Get available slots for a calendar
     */
    public function available(Request $request, $calendarId)
    {
        $business_id = Auth::user()->business->id;
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->where('is_active', true)
            ->findOrFail($calendarId);
        
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'duration' => 'nullable|integer|min:15'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $date = Carbon::parse($request->input('date'));
        $duration = $request->input('duration', $calendar->default_duration_minutes);
        
        $availableSlots = $calendar->getAvailableSlots($date, $duration);
        
        return response()->json([
            'success' => true,
            'calendar' => [
                'id' => $calendar->id,
                'name' => $calendar->name,
                'type' => $calendar->calendar_type,
            ],
            'date' => $date->format('Y-m-d'),
            'duration_minutes' => $duration,
            'available_slots' => $availableSlots,
            'total_available' => count($availableSlots)
        ]);
    }
    
    /**
     * Reserve a booking slot (manual booking)
     */
    public function reserve(Request $request)
    {
        $business_id = Auth::user()->business->id;
        
        $validator = Validator::make($request->all(), [
            'booking_calendar_id' => 'required|exists:booking_calendars,id',
            'business_contact_id' => 'nullable|exists:business_contacts,id',
            'start_time' => 'required|date',
            'duration_minutes' => 'required|integer|min:15',
            'contact_name' => 'required_without:business_contact_id|string',
            'contact_phone' => 'required_without:business_contact_id|string',
            'contact_email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $calendar = BookingCalendar::where('business_id', $business_id)
            ->where('is_active', true)
            ->findOrFail($request->input('booking_calendar_id'));
        
        $startTime = Carbon::parse($request->input('start_time'));
        $durationMinutes = $request->input('duration_minutes');
        $endTime = $startTime->copy()->addMinutes($durationMinutes);
        
        // Check if slot is available
        if (!$calendar->isTimeSlotAvailable($startTime, $durationMinutes)) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot is not available'
            ], 409);
        }
        
        // Check for conflicts
        $conflicts = BookingSlot::checkConflicts($calendar->id, $startTime, $endTime);
        if ($conflicts->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Time slot conflicts with existing booking',
                'conflicts' => $conflicts->count()
            ], 409);
        }
        
        DB::beginTransaction();
        
        try {
            // Get or create business contact
            $contactId = $request->input('business_contact_id');
            
            if (!$contactId && $request->input('contact_phone')) {
                // Create temporary contact
                $contact = BusinessContact::firstOrCreate([
                    'business_id' => $business_id,
                    'phone' => $request->input('contact_phone')
                ], [
                    'name' => $request->input('contact_name'),
                    'email' => $request->input('contact_email'),
                ]);
                $contactId = $contact->id;
            }
            
            // Reserve the slot
            $slot = BookingSlot::reserve(
                $calendar->id,
                $startTime,
                $endTime,
                $contactId,
                [
                    'contact_name' => $request->input('contact_name'),
                    'contact_phone' => $request->input('contact_phone'),
                    'contact_email' => $request->input('contact_email'),
                    'notes' => $request->input('notes'),
                    'booking_source' => 'manual',
                ]
            );
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Booking slot reserved successfully',
                'booking_slot' => $slot->load('bookingCalendar')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reserve booking slot: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Confirm a reserved booking slot
     */
    public function confirm($id)
    {
        $business_id = Auth::user()->business->id;
        
        $slot = BookingSlot::whereHas('bookingCalendar', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        if ($slot->status !== 'reserved') {
            return response()->json([
                'success' => false,
                'message' => 'Only reserved slots can be confirmed'
            ], 400);
        }
        
        $slot->confirm();
        
        return response()->json([
            'success' => true,
            'message' => 'Booking slot confirmed',
            'booking_slot' => $slot->fresh()->load('appointment')
        ]);
    }
    
    /**
     * Cancel a booking slot
     */
    public function cancel(Request $request, $id)
    {
        $business_id = Auth::user()->business->id;
        
        $slot = BookingSlot::whereHas('bookingCalendar', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        if (!in_array($slot->status, ['reserved', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only reserved or confirmed slots can be cancelled'
            ], 400);
        }
        
        $reason = $request->input('cancellation_reason');
        $slot->cancel($reason);
        
        return response()->json([
            'success' => true,
            'message' => 'Booking slot cancelled',
            'booking_slot' => $slot->fresh()
        ]);
    }
    
    /**
     * Reschedule a booking slot
     */
    public function reschedule(Request $request, $id)
    {
        $business_id = Auth::user()->business->id;
        
        $slot = BookingSlot::whereHas('bookingCalendar', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'new_start_time' => 'required|date|after:now',
            'new_duration_minutes' => 'nullable|integer|min:15',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $newStartTime = Carbon::parse($request->input('new_start_time'));
        $newDuration = $request->input('new_duration_minutes', $slot->duration_minutes);
        $newEndTime = $newStartTime->copy()->addMinutes($newDuration);
        
        $calendar = $slot->bookingCalendar;
        
        // Check if new slot is available
        if (!$calendar->isTimeSlotAvailable($newStartTime, $newDuration)) {
            return response()->json([
                'success' => false,
                'message' => 'New time slot is not available'
            ], 409);
        }
        
        // Check for conflicts (exclude current slot)
        $conflicts = BookingSlot::checkConflicts($calendar->id, $newStartTime, $newEndTime, $slot->id);
        if ($conflicts->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'New time slot conflicts with existing booking'
            ], 409);
        }
        
        DB::beginTransaction();
        
        try {
            // Cancel old slot
            $slot->cancel('Rescheduled to new time');
            
            // Create new slot
            $newSlot = BookingSlot::reserve(
                $calendar->id,
                $newStartTime,
                $newEndTime,
                $slot->business_contact_id,
                array_merge($slot->metadata ?? [], [
                    'rescheduled_from' => $slot->id,
                    'rescheduled_at' => now()->toIso8601String(),
                ])
            );
            
            // Link appointment if exists
            if ($slot->appointment_id) {
                $newSlot->linkToAppointment($slot->appointment_id);
                $newSlot->confirm();
                
                // Update appointment times
                $appointment = $slot->appointment;
                $appointment->update([
                    'appointment_date' => $newStartTime->format('Y-m-d'),
                    'appointment_time' => $newStartTime->format('H:i:s'),
                ]);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Booking slot rescheduled successfully',
                'old_slot' => $slot->fresh(),
                'new_slot' => $newSlot->load('bookingCalendar', 'appointment')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reschedule: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get booking slot details
     */
    public function show($id)
    {
        $business_id = Auth::user()->business->id;
        
        $slot = BookingSlot::with([
            'bookingCalendar',
            'appointment',
            'businessContact'
        ])
        ->whereHas('bookingCalendar', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })
        ->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'booking_slot' => $slot
        ]);
    }
    
    /**
     * List all booking slots with filters
     */
    public function index(Request $request)
    {
        $business_id = Auth::user()->business->id;
        
        $query = BookingSlot::with([
            'bookingCalendar',
            'appointment',
            'businessContact'
        ])
        ->whereHas('bookingCalendar', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        });
        
        // Filter by calendar
        if ($request->filled('calendar_id')) {
            $query->where('booking_calendar_id', $request->input('calendar_id'));
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_time', '>=', Carbon::parse($request->input('start_date')));
        }
        
        if ($request->filled('end_date')) {
            $query->where('end_time', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }
        
        // Default: show upcoming slots
        if (!$request->filled('start_date') && !$request->filled('end_date')) {
            $query->where('start_time', '>=', now());
        }
        
        $slots = $query->orderBy('start_time', 'asc')
            ->paginate($request->input('per_page', 25));
        
        return response()->json([
            'success' => true,
            'booking_slots' => $slots
        ]);
    }
}
