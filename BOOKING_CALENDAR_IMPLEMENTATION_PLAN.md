# Booking Calendar System Implementation Plan

## Document Version
**Version:** 1.0  
**Date:** January 24, 2026  
**Author:** SafariChat Development Team  
**Status:** Approved for Implementation

---

## 1. Overview

### 1.1 Business Problem
Currently, the AI sales agent can create appointments without checking business availability, leading to:
- Double bookings and scheduling conflicts
- Appointments outside business hours
- No control over appointment types or durations
- Manual coordination required to manage availability
- No way to define different calendar types (demos, consultations, etc.)

### 1.2 Solution Approach: Dual System Architecture

We will implement a **dual system** that separates **availability management** from **actual bookings**:

```
┌─────────────────────────────────────────────────────────────┐
│                     DUAL SYSTEM DESIGN                       │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  BOOKING CALENDARS (What's Available)                        │
│  ├─ Business-defined availability templates                  │
│  ├─ Working hours, duration, buffer times                    │
│  └─ Max bookings per day/week                                │
│                          ↓                                    │
│  BOOKING SLOTS (Availability Reservations)                   │
│  ├─ Bridge between calendars and appointments                │
│  ├─ Prevents double bookings                                 │
│  └─ Tracks slot status (reserved/confirmed/cancelled)        │
│                          ↓                                    │
│  APPOINTMENTS (What's Actually Booked)                       │
│  ├─ Lead-specific scheduled meetings                         │
│  ├─ AI-created or manual bookings                            │
│  └─ Reminder notifications via WhatsApp                      │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

**Key Principle:** 
- **Booking Calendars** define WHEN and HOW appointments can be scheduled
- **Booking Slots** reserve specific time blocks to prevent conflicts
- **Appointments** represent the actual meeting with a specific lead

---

## 2. System Architecture

### 2.1 Three-Tier Structure

#### Tier 1: Booking Calendars (Availability Templates)
**Purpose:** Define business availability rules and constraints

**Features:**
- Calendar name (e.g., "Product Demos", "Technical Consultations")
- Appointment type (demo, consultation, follow_up, meeting, call)
- Default duration (15, 30, 45, 60 minutes)
- Working hours (start_time, end_time)
- Working days (JSON array: [1,2,3,4,5] for Monday-Friday)
- Buffer time between appointments (5, 10, 15 minutes)
- Max bookings per day/week
- Advance booking requirements (minimum/maximum days ahead)
- Status (active/inactive)

#### Tier 2: Booking Slots (Time Reservations)
**Purpose:** Reserve specific time blocks and prevent double bookings

**Features:**
- Links to booking calendar
- Links to business contact/lead
- Start time and end time
- Status (available, reserved, confirmed, completed, cancelled, no_show)
- Optional link to appointment record
- Booking method (ai_agent, manual, api)
- Cancellation tracking

#### Tier 3: Appointments (Actual Meetings)
**Purpose:** Track scheduled meetings with leads (EXISTING TABLE - NO CHANGES)

**Current Features (Keep As-Is):**
- Lead-specific meeting details
- Title, description, scheduled_at
- Status tracking (pending, confirmed, cancelled, completed, no_show)
- Reminder system (24h before via WhatsApp)
- AI-created via natural language processing
- Reschedule/cancel capabilities

---

## 3. Database Changes

### 3.1 New Table: booking_calendars

```sql
CREATE TABLE booking_calendars (
    id BIGSERIAL PRIMARY KEY,
    business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Calendar Identity
    name VARCHAR(100) NOT NULL,
    description TEXT,
    calendar_type VARCHAR(50) NOT NULL, -- 'demo', 'consultation', 'follow_up', 'meeting', 'call', 'custom'
    
    -- Appointment Settings
    default_duration_minutes INT NOT NULL DEFAULT 30,
    buffer_minutes INT NOT NULL DEFAULT 10, -- Time between appointments
    
    -- Availability Rules (JSON)
    availability_rules JSONB NOT NULL DEFAULT '{}',
    /* Example structure:
    {
        "working_hours": {
            "monday": {"start": "09:00", "end": "17:00"},
            "tuesday": {"start": "09:00", "end": "17:00"},
            "wednesday": {"start": "09:00", "end": "17:00"},
            "thursday": {"start": "09:00", "end": "17:00"},
            "friday": {"start": "09:00", "end": "17:00"},
            "saturday": null,
            "sunday": null
        },
        "breaks": [
            {"start": "12:00", "end": "13:00", "days": [1,2,3,4,5]}
        ],
        "blackout_dates": ["2026-12-25", "2026-01-01"]
    }
    */
    
    -- Booking Limits
    max_bookings_per_day INT DEFAULT NULL,
    max_bookings_per_week INT DEFAULT NULL,
    min_advance_hours INT DEFAULT 2, -- Minimum hours before booking
    max_advance_days INT DEFAULT 60, -- Maximum days in future to book
    
    -- Integration Settings
    allow_ai_booking BOOLEAN DEFAULT TRUE,
    allow_manual_booking BOOLEAN DEFAULT TRUE,
    require_confirmation BOOLEAN DEFAULT TRUE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    -- Indexes
    INDEX idx_booking_calendars_business (business_id),
    INDEX idx_booking_calendars_active (business_id, is_active)
);
```

### 3.2 New Table: booking_slots

```sql
CREATE TABLE booking_slots (
    id BIGSERIAL PRIMARY KEY,
    booking_calendar_id BIGINT NOT NULL REFERENCES booking_calendars(id) ON DELETE CASCADE,
    business_id BIGINT NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
    
    -- Contact/Lead Information
    business_contact_id BIGINT REFERENCES business_contacts(id) ON DELETE SET NULL,
    lead_id BIGINT REFERENCES leads(id) ON DELETE SET NULL,
    
    -- Time Slot
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    duration_minutes INT NOT NULL,
    
    -- Slot Status
    status VARCHAR(50) NOT NULL DEFAULT 'available', -- 'available', 'reserved', 'confirmed', 'completed', 'cancelled', 'no_show'
    
    -- Appointment Link (NULL until appointment confirmed)
    appointment_id BIGINT REFERENCES appointments(id) ON DELETE SET NULL,
    
    -- Booking Details
    booked_by_user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    booking_method VARCHAR(50), -- 'ai_agent', 'manual', 'api', 'self_service'
    booked_at TIMESTAMP,
    confirmed_at TIMESTAMP,
    
    -- Cancellation
    cancelled_at TIMESTAMP,
    cancellation_reason TEXT,
    
    -- Metadata
    notes TEXT,
    
    -- Timestamps
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    
    -- Indexes
    INDEX idx_booking_slots_calendar (booking_calendar_id),
    INDEX idx_booking_slots_business (business_id),
    INDEX idx_booking_slots_time (start_time, end_time),
    INDEX idx_booking_slots_status (status),
    INDEX idx_booking_slots_contact (business_contact_id),
    INDEX idx_booking_slots_appointment (appointment_id),
    
    -- Constraints
    CONSTRAINT chk_end_after_start CHECK (end_time > start_time),
    CONSTRAINT chk_valid_status CHECK (status IN ('available', 'reserved', 'confirmed', 'completed', 'cancelled', 'no_show'))
);
```

### 3.3 Modifications to Existing Table: appointments

**NO CHANGES REQUIRED** - Keep all existing functionality as-is.

The appointments table will continue to work exactly as before, but now appointments will be created alongside booking slots to prevent conflicts.

---

## 4. Code Changes Required

### 4.1 New Models

#### File: `app/Models/BookingCalendar.php`
**Purpose:** Manage business availability templates

**Key Methods:**
```php
// Availability checking
public function getAvailableSlots($date, $duration = null)
public function isTimeSlotAvailable($startTime, $duration)
public function getNextAvailableSlot($fromDate, $duration)

// Validation
public function isWithinWorkingHours($datetime)
public function isWorkingDay($date)
public function hasReachedDailyLimit($date)
public function hasReachedWeeklyLimit($weekStart)

// Slot generation
public function generateSlotsForDateRange($startDate, $endDate)
public function getBusinessHoursForDay($date)

// Relationships
public function business()
public function user()
public function bookingSlots()
public function appointments() // Through booking_slots
```

#### File: `app/Models/BookingSlot.php`
**Purpose:** Manage time slot reservations

**Key Methods:**
```php
// Reservation management
public function reserve($contactId, $leadId, $bookedByUserId, $method = 'ai_agent')
public function confirm()
public function complete()
public function cancel($reason = null)
public function markNoShow()

// Validation
public static function checkConflicts($calendarId, $startTime, $endTime, $excludeSlotId = null)
public function isAvailable()
public function canBeModified()

// Appointment linking
public function createAppointment($appointmentData)
public function linkToAppointment($appointmentId)

// Relationships
public function bookingCalendar()
public function businessContact()
public function lead()
public function appointment()
public function bookedByUser()
```

### 4.2 Modified Services

#### File: `app/Services/AiWhatsAppService.php`
**Location:** Method `handleBookingRequest()` (around line 540)

**BEFORE:**
```php
// Direct appointment creation without availability check
$appointment = Appointment::createFromAiRequest([
    'lead_id' => $lead->id,
    'title' => $intent['appointment_type'] ?? 'Meeting',
    'scheduled_at' => $scheduledDate,
    'duration_minutes' => 30
]);
```

**AFTER:**
```php
// Step 1: Find appropriate booking calendar
$calendar = BookingCalendar::where('business_id', $this->business->id)
    ->where('calendar_type', $intent['appointment_type'] ?? 'demo')
    ->where('is_active', true)
    ->where('allow_ai_booking', true)
    ->first();

if (!$calendar) {
    // Fallback to direct appointment if no calendar configured
    return $this->createDirectAppointment($lead, $intent);
}

// Step 2: Check availability
$requestedTime = Carbon::parse($scheduledDate);
$duration = $intent['duration_minutes'] ?? $calendar->default_duration_minutes;

if (!$calendar->isTimeSlotAvailable($requestedTime, $duration)) {
    // Suggest alternative times
    $alternatives = $calendar->getNextAvailableSlot($requestedTime, $duration);
    return $this->suggestAlternativeTimes($lead, $alternatives);
}

// Step 3: Reserve booking slot
$bookingSlot = BookingSlot::create([
    'booking_calendar_id' => $calendar->id,
    'business_id' => $this->business->id,
    'business_contact_id' => $lead->business_contact_id,
    'lead_id' => $lead->id,
    'start_time' => $requestedTime,
    'end_time' => $requestedTime->copy()->addMinutes($duration),
    'duration_minutes' => $duration,
    'status' => 'reserved',
    'booking_method' => 'ai_agent',
    'booked_by_user_id' => $this->user->id,
    'booked_at' => now()
]);

// Step 4: Create appointment (existing logic)
$appointment = Appointment::createFromAiRequest([
    'lead_id' => $lead->id,
    'title' => $intent['appointment_type'] ?? 'Meeting',
    'scheduled_at' => $requestedTime,
    'duration_minutes' => $duration
]);

// Step 5: Link slot to appointment
$bookingSlot->linkToAppointment($appointment->id);
$bookingSlot->confirm();

// Update lead status
$lead->update(['status' => 'DEMO_SCHEDULED']);
```

### 4.3 New Controllers

#### File: `app/Http/Controllers/BookingCalendarController.php`
**Purpose:** Manage booking calendars CRUD operations

**Methods:**
```php
public function index()      // List all calendars
public function create()     // Show create form
public function store()      // Save new calendar
public function edit($id)    // Show edit form
public function update($id)  // Update calendar
public function destroy($id) // Delete calendar
public function toggle($id)  // Activate/deactivate
public function preview($id) // Preview available slots
```

#### File: `app/Http/Controllers/BookingSlotController.php`
**Purpose:** Manage booking slots and manual bookings

**Methods:**
```php
public function available()       // Get available slots for date range
public function reserve()         // Reserve a slot (manual booking)
public function confirm($id)      // Confirm reservation
public function cancel($id)       // Cancel booking
public function reschedule($id)   // Move to different slot
```

### 4.4 Modified Controllers

#### File: `app/Http/Controllers/Guest.php`
**No direct changes required** - Contact management continues as-is

#### File: `app/Http/Controllers/AiSalesAgentController.php`
**Addition:** Display booking calendar status on AI agents page

```php
public function index() {
    // ... existing code ...
    
    // Add booking calendar info
    $this->data['booking_calendars'] = BookingCalendar::where('business_id', $business_id)
        ->where('is_active', true)
        ->get();
        
    $this->data['total_upcoming_slots'] = BookingSlot::where('business_id', $business_id)
        ->where('status', 'confirmed')
        ->where('start_time', '>', now())
        ->count();
    
    return view('service.ai-agents.index', $this->data);
}
```

### 4.5 Navigation & UI Access

#### File: `resources/views/layouts/nav.blade.php`
**Purpose:** Add navigation menu items for easy access to appointments and booking calendars

**Addition:** New menu items in main navigation (after Products, Sales Agents)

```blade
<!-- Appointments Menu Item -->
<li class="nav-item">
    <a href="{{ route('appointments.index') }}" class="nav-link {{ request()->is('appointments*') ? 'active' : '' }}">
        <i class="fas fa-calendar-check" style="color: #10b981;"></i>
        <span>Appointments</span>
        @if(isset($pendingAppointmentsCount) && $pendingAppointmentsCount > 0)
            <span class="badge badge-warning ml-2">{{ $pendingAppointmentsCount }}</span>
        @endif
    </a>
</li>

<!-- Booking Calendars Menu Item -->
<li class="nav-item">
    <a href="{{ route('booking-calendars.index') }}" class="nav-link {{ request()->is('booking-calendars*') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt" style="color: #3b82f6;"></i>
        <span>Booking Calendars</span>
    </a>
</li>
```

**View Composer for Pending Count Badge:**
Add to `app/Providers/AppServiceProvider.php` in the `boot()` method:

```php
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Appointment;

View::composer('layouts.nav', function ($view) {
    if (Auth::check()) {
        $user = Auth::user();
        $pendingAppointmentsCount = Appointment::whereHas('lead', function($q) use ($user) {
            $q->where('business_id', $user->business->id);
        })
        ->where('status', 'pending')
        ->where('scheduled_at', '>', now())
        ->count();
        
        $view->with('pendingAppointmentsCount', $pendingAppointmentsCount);
    }
});
```

#### New Controller: `app/Http/Controllers/AppointmentController.php`
**Purpose:** Manage all appointments (view, confirm, cancel, reschedule)

**Key Methods:**
```php
public function index()      // List all appointments with filters
public function show($id)    // View appointment details
public function confirm($id) // Confirm pending appointment
public function cancel($id)  // Cancel appointment
public function complete($id) // Mark as completed
public function markNoShow($id) // Mark as no-show
public function reschedule($id) // Reschedule to different time
```

**Full Implementation:**
```php
namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    public function index()
    {
        $business_id = Auth::user()->business->id;
        
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
            ->orderBy('scheduled_at', 'desc')
            ->paginate(20);
        
        $stats = [
            'upcoming' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->where('status', 'confirmed')->where('scheduled_at', '>', now())->count(),
            
            'pending' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->where('status', 'pending')->count(),
            
            'completed_this_month' => Appointment::whereHas('lead', function($q) use ($business_id) {
                $q->where('business_id', $business_id);
            })->where('status', 'completed')->whereMonth('scheduled_at', now()->month)->count(),
        ];
        
        return view('appointments.index', compact('appointments', 'stats'));
    }
    
    public function show($id)
    {
        $appointment = Appointment::with(['lead.businessContact', 'createdBy'])->findOrFail($id);
        return view('appointments.show', compact('appointment'));
    }
    
    public function confirm($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->confirm();
        
        return redirect()->back()->with('success', 'Appointment confirmed');
    }
    
    public function cancel(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->cancel();
        
        // Free up booking slot if exists
        $slot = BookingSlot::where('appointment_id', $id)->first();
        if ($slot) {
            $slot->cancel($request->input('reason', 'Cancelled by business'));
        }
        
        return redirect()->back()->with('success', 'Appointment cancelled');
    }
}
```

### 4.6 New Views

#### File: `resources/views/appointments/index.blade.php`
**Purpose:** List all AI-scheduled appointments with management actions

**Features:**
- Statistics cards (upcoming, pending, completed)
- Filter by status, type, date range
- Table view with: Lead name, phone, appointment type, date/time, status
- Quick actions: View, Confirm, Cancel, Mark Complete
- AI-created indicator badge
- WhatsApp contact link
- Pagination

#### File: `resources/views/appointments/show.blade.php`
**Purpose:** Detailed appointment view

**Features:**
- Full appointment details
- Lead/contact information with WhatsApp link
- Status timeline
- Action buttons (Confirm, Cancel, Reschedule, Complete, No-Show)
- Notes section
- Linked booking calendar info (if applicable)
- Created by info (AI vs Manual)

### 4.7 New Routes

#### File: `routes/web.php`
```php
// Appointment Management (NEW - Main navigation link)
Route::middleware(['auth'])->prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/{id}', [AppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/{id}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/{id}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/{id}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/{id}/no-show', [AppointmentController::class, 'markNoShow'])->name('appointments.no-show');
    Route::post('/{id}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
});

// Booking Calendar Management
Route::middleware(['auth'])->prefix('booking-calendars')->group(function () {
    Route::get('/', [BookingCalendarController::class, 'index'])->name('booking-calendars.index');
    Route::get('/create', [BookingCalendarController::class, 'create'])->name('booking-calendars.create');
    Route::post('/', [BookingCalendarController::class, 'store'])->name('booking-calendars.store');
    Route::get('/{id}/edit', [BookingCalendarController::class, 'edit'])->name('booking-calendars.edit');
    Route::put('/{id}', [BookingCalendarController::class, 'update'])->name('booking-calendars.update');
    Route::delete('/{id}', [BookingCalendarController::class, 'destroy'])->name('booking-calendars.destroy');
    Route::post('/{id}/toggle', [BookingCalendarController::class, 'toggle'])->name('booking-calendars.toggle');
});
```

#### File: `routes/api.php`
```php
// Booking Calendars API
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/booking-calendars/available-slots', [BookingSlotController::class, 'available']);
    Route::post('/booking-slots/reserve', [BookingSlotController::class, 'reserve']);
    Route::post('/booking-slots/{id}/confirm', [BookingSlotController::class, 'confirm']);
    Route::post('/booking-slots/{id}/cancel', [BookingSlotController::class, 'cancel']);
    
    // Appointments API
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
});
```

---

## 5. Subscription Plan Integration

### 5.1 Billing Limits

#### File: `config/safarichat_billing.php`
**Modification:** Add booking calendar limits to each plan

```php
'plans' => [
    'trial' => [
        'limits' => [
            'max_contacts' => 10,
            'max_products' => 1,
            'max_booking_calendars' => 0, // ← NEW: No calendars on trial
            'max_bookings_per_month' => 0,
        ],
        'permissions' => [
            'booking_calendars' => false, // ← NEW: Feature disabled
        ],
    ],
    'starter' => [
        'limits' => [
            'max_contacts' => 50,
            'max_products' => 5,
            'max_booking_calendars' => 1, // ← NEW: 1 calendar type
            'max_bookings_per_month' => 50,
        ],
        'permissions' => [
            'booking_calendars' => true, // ← NEW: Feature enabled
        ],
    ],
    'pro' => [
        'limits' => [
            'max_contacts' => 150,
            'max_products' => 50,
            'max_booking_calendars' => 5, // ← NEW: 5 calendar types
            'max_bookings_per_month' => 200,
        ],
        'permissions' => [
            'booking_calendars' => true,
        ],
    ],
    'premium' => [
        'limits' => [
            'max_contacts' => 400,
            'max_products' => 200,
            'max_booking_calendars' => -1, // ← NEW: Unlimited
            'max_bookings_per_month' => -1, // Unlimited
        ],
        'permissions' => [
            'booking_calendars' => true,
        ],
    ],
],
```

### 5.2 BillingService Updates

#### File: `app/Services/BillingService.php`
**Addition:** Calendar creation limit checking

```php
public static function canCreateBookingCalendar($user) {
    $billingAccount = $user->business->billingAccount;
    $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
    $planLimits = config("safarichat_billing.plans.{$currentPlan}.limits", []);
    
    $maxCalendars = $planLimits['max_booking_calendars'] ?? 0;
    
    // Check if feature is allowed
    if ($maxCalendars === 0) {
        return [
            'can_create' => false,
            'current' => 0,
            'max' => 0,
            'plan' => $currentPlan,
            'message' => 'Booking calendars are not available on your current plan. Upgrade to Starter or higher.',
            'upgrade_required' => true
        ];
    }
    
    // Unlimited calendars
    if ($maxCalendars === -1) {
        return [
            'can_create' => true,
            'current' => BookingCalendar::where('business_id', $user->business->id)->count(),
            'max' => -1,
            'plan' => $currentPlan,
            'message' => 'You have unlimited booking calendars.'
        ];
    }
    
    // Check current count
    $currentCount = BookingCalendar::where('business_id', $user->business->id)->count();
    
    return [
        'can_create' => $currentCount < $maxCalendars,
        'current' => $currentCount,
        'max' => $maxCalendars,
        'plan' => $currentPlan,
        'message' => $currentCount >= $maxCalendars 
            ? "You have reached your booking calendar limit ({$maxCalendars} calendars). Please upgrade to create more."
            : "You can create " . ($maxCalendars - $currentCount) . " more booking calendar(s).",
        'upgrade_required' => $currentCount >= $maxCalendars
    ];
}
```

---

## 6. User Workflows

### 6.1 Business Owner: Creating a Booking Calendar

**Steps:**
1. Navigate to **Settings → Booking Calendars**
2. Click **"Create New Calendar"**
3. Fill in calendar details:
   - Name: "Product Demos"
   - Type: Demo
   - Duration: 30 minutes
   - Buffer time: 10 minutes
   - Working hours: Monday-Friday, 9 AM - 5 PM
   - Max bookings per day: 8
4. Save calendar
5. Calendar becomes active and available for AI bookings

**Result:**
- AI can now suggest demo slots within defined availability
- No double bookings possible
- All demos fit within business hours

### 6.2 AI Agent: Scheduling an Appointment

**Scenario:** Lead says "Can we schedule a demo for tomorrow at 2 PM?"

**AI Process:**
1. Parse intent: booking request for demo
2. Extract time: tomorrow 2:00 PM
3. Find booking calendar: type = "demo"
4. Check availability:
   - Is tomorrow a working day? ✓
   - Is 2 PM within working hours? ✓
   - Is slot already booked? ✗
   - Within advance booking limits? ✓
5. Reserve slot:
   - Create BookingSlot (status: reserved)
   - Create Appointment (status: pending)
   - Link slot to appointment
6. Respond to lead: "Great! I've scheduled your demo for tomorrow at 2 PM. You'll receive a confirmation shortly."
7. Update lead status to DEMO_SCHEDULED

**If Slot Unavailable:**
1. Find next 3 available slots
2. Suggest alternatives: "That time is already booked. I have availability at 3 PM, 4 PM tomorrow, or 10 AM the day after. Which works best?"

### 6.3 Lead Cancellation Flow

**Scenario:** Lead says "I need to cancel my demo"

**AI Process:**
1. Find appointment for lead
2. Get linked booking slot
3. Cancel both:
   - BookingSlot.cancel(reason: "Lead requested cancellation")
   - Appointment.cancel()
4. Free up time slot for others
5. Update lead status
6. Confirm: "Your demo has been cancelled. Would you like to reschedule?"

### 6.4 Manual Booking by Business Owner

**Steps:**
1. Go to **Contacts** page
2. Select contact
3. Click **"Schedule Appointment"**
4. Select calendar type (Demo/Consultation/etc.)
5. View available time slots
6. Click desired slot
7. Add notes (optional)
8. Click **"Book Appointment"**

**System Actions:**
- Creates BookingSlot (status: confirmed)
- Creates Appointment
- Sends WhatsApp notification to lead
- Schedules reminder for 24h before

---

## 7. Integration Points Summary

### 7.1 Components Modified
| Component | File Path | Modification Type | Impact |
|-----------|-----------|------------------|--------|
| AiWhatsAppService | `app/Services/AiWhatsAppService.php` | **Modified** | Add calendar availability checking |
| Appointment Model | `app/Models/Appointment.php` | **No Changes** | Continue working as-is |
| BillingService | `app/Services/BillingService.php` | **Modified** | Add calendar limit checking |
| Billing Config | `config/safarichat_billing.php` | **Modified** | Add calendar limits per plan |
| AI Agents Page | `resources/views/service/ai-agents/index.blade.php` | **Modified** | Display calendar status |
| Navigation Menu | `resources/views/layouts/nav.blade.php` | **Modified** | Add Appointments & Calendars links |
| AppServiceProvider | `app/Providers/AppServiceProvider.php` | **Modified** | Add pending appointments badge count |
| Guest Controller | `app/Http/Controllers/Guest.php` | **No Changes** | No impact |

### 7.2 Components Created
| Component | File Path | Purpose |
|-----------|-----------|---------|
| BookingCalendar Model | `app/Models/BookingCalendar.php` | **NEW** - Manage availability templates |
| BookingSlot Model | `app/Models/BookingSlot.php` | **NEW** - Manage time reservations |
| BookingCalendarController | `app/Http/Controllers/BookingCalendarController.php` | **NEW** - CRUD for calendars |
| BookingSlotController | `app/Http/Controllers/BookingSlotController.php` | **NEW** - Slot management |
| AppointmentController | `app/Http/Controllers/AppointmentController.php` | **NEW** - Appointment UI management |
| Appointments Index View | `resources/views/appointments/index.blade.php` | **NEW** - List all appointments |
| Appointment Detail View | `resources/views/appointments/show.blade.php` | **NEW** - View/manage single appointment |
| Calendars Index View | `resources/views/booking-calendars/index.blade.php` | **NEW** - List calendars |
| Calendar Form View | `resources/views/booking-calendars/form.blade.php` | **NEW** - Create/edit calendar |
| Available Slots View | `resources/views/booking-calendars/available-slots.blade.php` | **NEW** - Show available times |

### 7.3 Database Migrations
| Migration | Purpose |
|-----------|---------|
| `2026_01_24_create_booking_calendars_table.php` | Create booking_calendars table |
| `2026_01_24_create_booking_slots_table.php` | Create booking_slots table |

---

## 8. Testing Strategy

### 8.1 Unit Tests
```php
// Test BookingCalendar availability checking
public function test_calendar_identifies_available_slots()
public function test_calendar_respects_working_hours()
public function test_calendar_respects_daily_limits()
public function test_calendar_handles_blackout_dates()

// Test BookingSlot conflict detection
public function test_slot_prevents_double_booking()
public function test_slot_allows_back_to_back_with_buffer()
public function test_slot_cancellation_frees_time()

// Test BillingService calendar limits
public function test_trial_plan_cannot_create_calendars()
public function test_starter_plan_limited_to_one_calendar()
public function test_premium_plan_has_unlimited_calendars()
```

### 8.2 Integration Tests
```php
// Test AI booking flow
public function test_ai_creates_slot_before_appointment()
public function test_ai_suggests_alternatives_when_unavailable()
public function test_ai_updates_lead_status_after_booking()

// Test manual booking flow
public function test_business_owner_can_book_available_slot()
public function test_booking_sends_whatsapp_notification()
public function test_reminder_sent_24h_before_appointment()
```

### 8.3 User Acceptance Testing
1. **Calendar Creation:** Business owner creates demo calendar with working hours
2. **AI Booking:** Lead requests appointment via WhatsApp
3. **Conflict Prevention:** Second lead requests same time slot
4. **Alternative Suggestion:** AI suggests next available slot
5. **Cancellation:** Lead cancels, slot becomes available again
6. **Reminder:** WhatsApp reminder sent 24h before appointment

---

## 9. Rollout Plan

### Phase 1: Database & Models (Week 1)
- [ ] Create migration files
- [ ] Run migrations on staging
- [ ] Create BookingCalendar model
- [ ] Create BookingSlot model
- [ ] Write unit tests for models

### Phase 2: Controllers & Services (Week 2)
- [ ] Create BookingCalendarController
- [ ] Create BookingSlotController
- [ ] Modify AiWhatsAppService
- [ ] Modify BillingService
- [ ] Add routes

### Phase 3: Views & UI (Week 3)
- [ ] Add navigation menu items (Appointments & Booking Calendars)
- [ ] Add pending appointments badge to navigation
- [ ] Create AppointmentController with all CRUD methods
- [ ] Create appointments index view (list with filters and stats)
- [ ] Create appointment detail view (with action buttons)
- [ ] Create calendar management UI
- [ ] Create available slots calendar view
- [ ] Add calendar status to AI agents page
- [ ] Add manual booking interface to contacts page
- [ ] Update AppServiceProvider with view composer for badge count

### Phase 4: Integration & Testing (Week 4)
- [ ] Integration testing with AI agent
- [ ] Test appointment reminders still work
- [ ] Test conflict prevention
- [ ] Test subscription limits
- [ ] User acceptance testing

### Phase 5: Deployment (Week 5)
- [ ] Deploy to production
- [ ] Monitor for issues
- [ ] Train users on new feature
- [ ] Collect feedback

---

## 10. Success Metrics

### 10.1 Technical Metrics
- **Zero double bookings** - No conflicting appointments in same slot
- **100% AI compliance** - All AI bookings check availability first
- **<500ms slot lookup** - Fast availability checking
- **99.9% reminder delivery** - WhatsApp reminders sent successfully

### 10.2 Business Metrics
- **Reduced manual coordination** - 80% fewer scheduling conflicts
- **Increased bookings** - 30% more appointments due to clear availability
- **Higher show rates** - 20% improvement from better scheduling
- **User satisfaction** - 90%+ satisfaction with booking experience

---

## 11. Future Enhancements

### 11.1 Phase 2 Features (Q2 2026)
- **Calendar sync:** Google Calendar / Outlook integration
- **Team calendars:** Multiple team members with shared availability
- **Round-robin booking:** Distribute appointments across team
- **Custom availability:** Override working hours for specific dates
- **Waiting list:** Auto-book when slot becomes available

### 11.2 Phase 3 Features (Q3 2026)
- **Self-service booking:** Public booking link for leads
- **Video conferencing:** Auto-create Zoom/Teams links
- **Appointment types pricing:** Different rates per calendar type
- **Advanced analytics:** Booking trends, no-show rates, peak times
- **SMS reminders:** Alternative to WhatsApp for reminders

---

## 12. Appendix

### 12.1 Key Definitions

**Booking Calendar:** A template defining when and how appointments can be scheduled for a specific type of meeting (e.g., demos, consultations).

**Booking Slot:** A reserved time block that prevents double bookings. Links a specific time in a booking calendar to a lead/contact.

**Appointment:** The actual scheduled meeting with a lead. Contains meeting details, reminders, and status tracking.

**Buffer Time:** Minimum time between appointments to prevent back-to-back scheduling and allow for overruns.

**Blackout Date:** A date when no appointments can be scheduled (e.g., holidays, company events).

**Working Hours:** The time range during which appointments can be scheduled on a given day.

### 12.2 Database Relationship Diagram

```
businesses (existing)
    ├── booking_calendars (NEW)
    │       ├── booking_slots (NEW)
    │       │       └── appointments (EXISTING)
    │       └── availability_rules (JSON)
    │
    ├── business_contacts (existing)
    │       └── booking_slots (NEW)
    │
    └── leads (existing)
            ├── booking_slots (NEW)
            └── appointments (EXISTING)
```

### 12.3 Status Flow Diagrams

**Booking Slot Status:**
```
available → reserved → confirmed → completed
                ↓           ↓
            cancelled   no_show
```

**Appointment Status (Unchanged):**
```
pending → confirmed → completed
    ↓         ↓
cancelled  no_show
```

---

## Document Control

**Review History:**
| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-01-24 | Development Team | Initial implementation plan |

**Approvals:**
- [ ] Technical Lead: _________________ Date: _______
- [ ] Product Manager: _______________ Date: _______
- [ ] Business Owner: ________________ Date: _______

**Related Documents:**
- `BILLING_IMPLEMENTATION_README.md` - Subscription billing system
- `SMART_FOLLOWUP_SYSTEM.md` - AI follow-up automation
- `database/migrations/2026_01_09_152259_create_appointments_table.php` - Existing appointments schema

---

**End of Document**
