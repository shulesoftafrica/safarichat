<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingSlot;
use App\Models\BookingCalendar;
use App\Models\BusinessContact;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments
     */
    public function index()
    {
        $business_id = Auth::user()->business->id;
        
        // Get appointments with relationships
        $appointments = Appointment::with(['lead.contact', 'createdBy'])
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
     * Store a new appointment
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
            'appointment_type' => 'required|in:demo,consultation,follow_up,meeting,call',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        $business_id = Auth::user()->business->id;
        $user_id = Auth::id();
        
        try {
            DB::beginTransaction();
            
            // Combine date and time
            $scheduledAt = Carbon::parse($request->appointment_date . ' ' . $request->appointment_time);
            $duration = $request->duration_minutes ?? 30;
            $endsAt = $scheduledAt->copy()->addMinutes($duration);
            
            // Find or create lead/contact
            $phone = $this->formatPhoneNumber($request->customer_phone);
            
            $contact = BusinessContact::where('business_id', $business_id)
                ->where('guest_phone', $phone)
                ->first();
            
            if (!$contact) {
                $contact = BusinessContact::create([
                    'business_id' => $business_id,
                    'guest_name' => $request->customer_name,
                    'guest_phone' => $phone,
                ]);
            }
            
            $lead = Lead::where('business_id', $business_id)
                ->where('business_contact_id', $contact->id)
                ->first();
            
            if (!$lead) {
                // Get or create default AI sales agent for manual bookings
                $aiAgent = \App\Models\AiSalesAgent::where('business_id', $business_id)
                    ->where('is_active', true)
                    ->first();
                
                if (!$aiAgent) {
                    // Create a default AI agent for manual bookings if none exists
                    $aiAgent = \App\Models\AiSalesAgent::create([
                        'business_id' => $business_id,
                        'name' => 'Manual Booking Agent',
                        'is_active' => true,
                    ]);
                }
                
                $lead = Lead::create([
                    'business_id' => $business_id,
                    'business_contact_id' => $contact->id,
                    'ai_sales_agent_id' => $aiAgent->id,
                    'phone_number' => $phone,
                    'status' => 'NEW',
                    'source' => 'manual_booking',
                ]);
            }
            
            // Check for available booking calendar
            $bookingCalendar = BookingCalendar::where('business_id', $business_id)
                ->where('is_active', true)
                ->first();
            
            if ($bookingCalendar) {
                // Check availability
                $isAvailable = $bookingCalendar->isSlotAvailable($scheduledAt, $duration);
                
                if (!$isAvailable) {
                    DB::rollBack();
                    return redirect()->back()
                        ->with('error', 'The selected time slot is not available. Please choose a different time.')
                        ->withInput();
                }
            }
            
            // Create the appointment
            $appointment = Appointment::create([
                'lead_id' => $lead->id,
                'scheduled_at' => $scheduledAt,
                'duration_minutes' => $duration,
                'appointment_type' => $request->appointment_type,
                'title' => $request->title ?? ucfirst($request->appointment_type) . ' with ' . $request->customer_name,
                'description' => $request->description,
                'status' => 'confirmed',
                'created_by' => $user_id,
            ]);
            
            // Create booking slot if calendar exists
            if ($bookingCalendar) {
                BookingSlot::create([
                    'booking_calendar_id' => $bookingCalendar->id,
                    'appointment_id' => $appointment->id,
                    'start_time' => $scheduledAt,
                    'end_time' => $endsAt,
                    'status' => 'confirmed',
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $phone,
                    'notes' => $request->description,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('appointments.index')
                ->with('success', 'Appointment booked successfully for ' . $scheduledAt->format('M d, Y \a\t g:i A'));
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error booking appointment: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Format phone number
     */
    private function formatPhoneNumber($phone)
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with country code
        if (substr($phone, 0, 1) === '0') {
            $phone = '255' . substr($phone, 1);
        }
        
        // If doesn't start with +, add it
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Display the specified appointment
     */
    public function show($id)
    {
        $business_id = Auth::user()->business->id;
        
        $appointment = Appointment::with(['lead.contact', 'createdBy'])
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
