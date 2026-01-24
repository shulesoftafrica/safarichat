<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingSlot;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments
     */
    public function index()
    {
        $business_id = Auth::user()->business->id;
        
        // Get appointments with relationships
        $appointments = Appointment::with(['lead.businessContact', 'createdBy'])
            ->whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->when(request('status'), function($q) {
                $q->where('status', request('status'));
            })
            ->when(request('type'), function($q) {
                $q->where('appointment_type', request('type'));
            })
            ->when(request('from_date'), function($q) {
                $q->whereDate('scheduled_at', '>=', request('from_date'));
            })
            ->when(request('to_date'), function($q) {
                $q->whereDate('scheduled_at', '<=', request('to_date'));
            })
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);
        
        // Calculate statistics
        $stats = [
            'upcoming' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->where('status', 'confirmed')
            ->where('scheduled_at', '>', now())
            ->count(),
            
            'pending' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->where('status', 'pending')
            ->where('scheduled_at', '>', now())
            ->count(),
            
            'completed_this_month' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->where('status', 'completed')
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->count(),
            
            'no_show_rate' => $this->calculateNoShowRate($business_id),
        ];
        
        return view('appointments.index', compact('appointments', 'stats'));
    }
    
    /**
     * Display the specified appointment
     */
    public function show($id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::with(['lead.businessContact', 'createdBy'])
            ->whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })
            ->findOrFail($id);
        
        // Get linked booking slot if exists
        $bookingSlot = BookingSlot::where('appointment_id', $id)
            ->with('bookingCalendar')
            ->first();
        
        return view('appointments.show', compact('appointment', 'bookingSlot'));
    }
    
    /**
     * Confirm a pending appointment
     */
    public function confirm($id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $appointment->confirm();
        
        // Also confirm the booking slot if exists
        $bookingSlot = BookingSlot::where('appointment_id', $id)->first();
        if ($bookingSlot) {
            $bookingSlot->confirm();
        }
        
        return redirect()->back()->with('success', 'Appointment confirmed successfully');
    }
    
    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, $id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $reason = $request->input('reason', 'Cancelled by business');
        
        $appointment->cancel();
        
        // Free up booking slot if exists
        $bookingSlot = BookingSlot::where('appointment_id', $id)->first();
        if ($bookingSlot) {
            $bookingSlot->cancel($reason);
        }
        
        return redirect()->back()->with('success', 'Appointment cancelled successfully');
    }
    
    /**
     * Mark appointment as completed
     */
    public function complete($id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $appointment->complete();
        
        // Also mark booking slot as completed
        $bookingSlot = BookingSlot::where('appointment_id', $id)->first();
        if ($bookingSlot) {
            $bookingSlot->complete();
        }
        
        return redirect()->back()->with('success', 'Appointment marked as completed');
    }
    
    /**
     * Mark appointment as no-show
     */
    public function markNoShow($id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $appointment->markNoShow();
        
        // Also mark booking slot as no-show
        $bookingSlot = BookingSlot::where('appointment_id', $id)->first();
        if ($bookingSlot) {
            $bookingSlot->markNoShow();
        }
        
        return redirect()->back()->with('warning', 'Appointment marked as no-show');
    }
    
    /**
     * Reschedule an appointment
     */
    public function reschedule(Request $request, $id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })->findOrFail($id);
        
        $newDateTime = $request->input('new_datetime');
        
        if (!$newDateTime) {
            return redirect()->back()->with('error', 'New date/time is required');
        }
        
        // Cancel old booking slot
        $oldBookingSlot = BookingSlot::where('appointment_id', $id)->first();
        if ($oldBookingSlot) {
            $oldBookingSlot->cancel('Rescheduled to ' . $newDateTime);
        }
        
        // Reschedule appointment
        $appointment->reschedule($newDateTime);
        
        return redirect()->back()->with('success', 'Appointment rescheduled successfully');
    }
    
    /**
     * Calculate no-show rate for the business
     */
    private function calculateNoShowRate($business_id)
    {
        $total = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })
        ->whereIn('status', ['completed', 'no_show'])
        ->whereMonth('scheduled_at', now()->month)
        ->whereYear('scheduled_at', now()->year)
        ->count();
        
        if ($total == 0) {
            return 0;
        }
        
        $noShows = Appointment::whereHas('lead', function($q) use ($business_id) {
            $q->where('business_id', $business_id);
        })
        ->where('status', 'no_show')
        ->whereMonth('scheduled_at', now()->month)
        ->whereYear('scheduled_at', now()->year)
        ->count();
        
        return round(($noShows / $total) * 100, 1);
    }
}
