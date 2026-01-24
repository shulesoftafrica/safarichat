# Manual Appointment Booking Guide

## Overview
Added manual booking interface to allow users to create appointments with specific date and time selection, addressing the UX gap where only AI-scheduled appointments were possible.

## Features Added

### 1. **Book New Appointment Button**
- Located at the top of the Appointments page
- Opens a comprehensive booking modal

### 2. **Booking Modal Form Fields**
- **Customer Name** (required): Customer's full name
- **Customer Phone** (required): Phone number (auto-formatted to +255 format)
- **Appointment Date** (required): Date picker with minimum date of today
- **Appointment Time** (required): Time picker for specific time selection
- **Appointment Type** (required): Dropdown with options:
  - Demo
  - Consultation
  - Follow Up
  - Meeting
  - Call
- **Duration**: Selector with options (default 30 minutes):
  - 15 minutes
  - 30 minutes
  - 45 minutes
  - 60 minutes
  - 90 minutes
  - 120 minutes
- **Title** (optional): Custom appointment title
- **Description/Notes** (optional): Additional notes

### 3. **Backend Processing**

#### AppointmentController@store Method
The new `store()` method handles:

1. **Validation**: All required fields validated
2. **Contact Management**: 
   - Searches for existing BusinessContact by phone
   - Creates new contact if not found
3. **Lead Creation**:
   - Searches for existing Lead
   - Creates new lead with source 'manual_booking' if not found
4. **Calendar Availability Check**:
   - Finds active BookingCalendar for the business
   - Checks if the selected time slot is available
   - Returns error if slot conflicts with existing bookings
5. **Appointment Creation**:
   - Status automatically set to 'confirmed'
   - Creates appointment with all details
6. **BookingSlot Reservation**:
   - Creates matching booking slot if calendar exists
   - Prevents double-booking
7. **Transaction Safety**:
   - Uses database transactions for data consistency
   - Rolls back on any error

## How to Use

### For Users:
1. Navigate to **Appointments** menu
2. Click **Book New Appointment** button (top right)
3. Fill in the form:
   - Enter customer details
   - Select desired date and time
   - Choose appointment type and duration
   - Add optional title/notes
4. Click **Book Appointment**
5. System confirms booking or shows error if time slot unavailable

### Availability Checking
- System automatically checks against active BookingCalendar
- Validates against:
  - Business hours (working hours set in calendar)
  - Break times
  - Existing appointments/reservations
  - Buffer time between appointments
- If no calendar exists, booking proceeds without availability check

### Success Flow
```
User submits form
  ↓
Validation passes
  ↓
Find/Create Contact & Lead
  ↓
Check calendar availability
  ↓
Create Appointment (status: confirmed)
  ↓
Reserve BookingSlot
  ↓
Redirect with success message
```

### Error Handling
- **Validation Errors**: Shows specific field errors in modal
- **Time Slot Conflict**: "The selected time slot is not available. Please choose a different time."
- **System Errors**: Displays error message with details
- All errors preserve form input for easy correction

## Technical Details

### Files Modified
1. **resources/views/appointments/_appointments_list.blade.php**
   - Added "Book New Appointment" button

2. **resources/views/appointments/_modals.blade.php**
   - Added comprehensive booking modal

3. **app/Http/Controllers/AppointmentController.php**
   - Added `store()` method with full booking logic
   - Added `formatPhoneNumber()` helper method
   - Added imports for BookingCalendar, BusinessContact, DB, Carbon

4. **routes/web.php**
   - Added `POST /appointments` route for appointments.store

### Database Flow
```
BookingSlot ←→ Appointment ←→ Lead ←→ BusinessContact
     ↓                              ↓
BookingCalendar               Business
```

### Phone Number Formatting
- Removes all non-numeric characters
- Converts leading 0 to 255 (Tanzania format)
- Adds + prefix if missing
- Example: `0712345678` → `+255712345678`

## Integration with Existing System

### Compatible with:
- ✅ AI-scheduled appointments
- ✅ Booking Calendar availability rules
- ✅ BookingSlot conflict detection
- ✅ Lead management system
- ✅ Business contact management
- ✅ Appointment status workflow (confirm/cancel/complete/no-show)

### New Appointment Properties:
- **source**: Set to 'manual_booking' for leads created via this interface
- **status**: Automatically set to 'confirmed' (not 'pending' like AI bookings)
- **created_by**: Set to current user ID

## Future Enhancements (Optional)

1. **Calendar Integration**: 
   - Show calendar view with available/unavailable time slots
   - Visual date/time picker showing real-time availability

2. **Recurring Appointments**:
   - Option to create recurring appointments
   - Daily/weekly/monthly patterns

3. **Reminder Settings**:
   - Configure email/SMS reminders when booking
   - Customizable reminder timing

4. **Customer Portal**:
   - Allow customers to book their own appointments
   - Public booking page

5. **Time Zone Support**:
   - Handle different time zones
   - Show appointments in user's local time

## Testing Checklist

- [ ] Open Appointments page
- [ ] Click "Book New Appointment" button
- [ ] Modal opens correctly
- [ ] Fill form with valid data
- [ ] Submit form
- [ ] Appointment created successfully
- [ ] Appointment appears in list
- [ ] BookingSlot created (if calendar exists)
- [ ] Try booking conflicting time → error shown
- [ ] Try booking without required fields → validation errors shown
- [ ] Phone number formatted correctly
- [ ] Contact/Lead created or existing one used

## Conclusion

The manual booking interface fills the critical UX gap by allowing users to:
- Specify exact date and time for appointments
- Create appointments without waiting for AI scheduling
- Manually enter customer appointments from phone calls or in-person requests
- Ensure calendar availability before committing to a time

This complements the AI-powered scheduling system while giving users full control when needed.
