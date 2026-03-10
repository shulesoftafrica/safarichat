<div class="row">
    <!-- Basic Information -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="name">Calendar Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $calendar->name ?? '') }}" required>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $calendar->description ?? '') }}</textarea>
                    @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="calendar_type">Calendar Type <span class="text-danger">*</span></label>
                    <select name="calendar_type" id="calendar_type" class="form-control @error('calendar_type') is-invalid @enderror" required>
                        <option value="">Select Type</option>
                        <option value="demo" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'demo' ? 'selected' : '' }}>Demo</option>
                        <option value="consultation" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'consultation' ? 'selected' : '' }}>Consultation</option>
                        <option value="follow_up" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'follow_up' ? 'selected' : '' }}>Follow Up</option>
                        <option value="meeting" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'meeting' ? 'selected' : '' }}>Meeting</option>
                        <option value="call" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'call' ? 'selected' : '' }}>Call</option>
                        <option value="custom" {{ old('calendar_type', $calendar->calendar_type ?? '') == 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                    @error('calendar_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Time Settings -->
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-clock mr-2"></i>Time Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="default_duration_minutes">Default Duration (minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="default_duration_minutes" id="default_duration_minutes" 
                           class="form-control @error('default_duration_minutes') is-invalid @enderror" 
                           value="{{ old('default_duration_minutes', $calendar->default_duration_minutes ?? 60) }}" 
                           min="15" max="480" required>
                    <small class="text-muted">15 to 480 minutes (8 hours)</small>
                    @error('default_duration_minutes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="buffer_minutes">Buffer Time (minutes) <span class="text-danger">*</span></label>
                    <input type="number" name="buffer_minutes" id="buffer_minutes" 
                           class="form-control @error('buffer_minutes') is-invalid @enderror" 
                           value="{{ old('buffer_minutes', $calendar->buffer_minutes ?? 15) }}" 
                           min="0" max="60" required>
                    <small class="text-muted">Break time between appointments</small>
                    @error('buffer_minutes')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="min_advance_hours">Minimum Advance Notice (hours) <span class="text-danger">*</span></label>
                    <input type="number" name="min_advance_hours" id="min_advance_hours" 
                           class="form-control @error('min_advance_hours') is-invalid @enderror" 
                           value="{{ old('min_advance_hours', $calendar->min_advance_hours ?? 2) }}" 
                           min="0" required>
                    <small class="text-muted">How far in advance bookings must be made</small>
                    @error('min_advance_hours')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="max_advance_days">Maximum Advance Booking (days) <span class="text-danger">*</span></label>
                    <input type="number" name="max_advance_days" id="max_advance_days" 
                           class="form-control @error('max_advance_days') is-invalid @enderror" 
                           value="{{ old('max_advance_days', $calendar->max_advance_days ?? 30) }}" 
                           min="1" max="365" required>
                    <small class="text-muted">How far ahead customers can book</small>
                    @error('max_advance_days')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Limits -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0"><i class="fas fa-limit mr-2"></i>Booking Limits</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_bookings_per_day">Max Bookings Per Day</label>
                    <input type="number" name="max_bookings_per_day" id="max_bookings_per_day" 
                           class="form-control @error('max_bookings_per_day') is-invalid @enderror" 
                           value="{{ old('max_bookings_per_day', $calendar->max_bookings_per_day ?? '') }}" min="1">
                    <small class="text-muted">Leave empty for unlimited</small>
                    @error('max_bookings_per_day')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="max_bookings_per_week">Max Bookings Per Week</label>
                    <input type="number" name="max_bookings_per_week" id="max_bookings_per_week" 
                           class="form-control @error('max_bookings_per_week') is-invalid @enderror" 
                           value="{{ old('max_bookings_per_week', $calendar->max_bookings_per_week ?? '') }}" min="1">
                    <small class="text-muted">Leave empty for unlimited</small>
                    @error('max_bookings_per_week')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Working Hours -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-business-time mr-2"></i>Working Hours <span class="text-danger">*</span></h5>
    </div>
    <div class="card-body">
        @php
            $workingHours = old('working_hours', $calendar->availability_rules['working_hours'] ?? [
                ['day' => 'monday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'tuesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'wednesday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'thursday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'friday', 'enabled' => true, 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'saturday', 'enabled' => false, 'start' => '09:00', 'end' => '13:00'],
                ['day' => 'sunday', 'enabled' => false, 'start' => '09:00', 'end' => '13:00'],
            ]);
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        @endphp
        
        <div id="working-hours-container">
            @foreach($workingHours as $index => $hours)
            <div class="row align-items-center mb-2">
                <div class="col-md-2">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" 
                               id="day_enabled_{{ $index }}" 
                               name="working_hours[{{ $index }}][enabled]" 
                               value="1"
                               {{ ($hours['enabled'] ?? false) ? 'checked' : '' }}
                               onchange="toggleDayInputs({{ $index }})">
                        <input type="hidden" name="working_hours[{{ $index }}][day]" value="{{ $hours['day'] }}">
                        <label class="custom-control-label" for="day_enabled_{{ $index }}">
                            {{ ucfirst($hours['day']) }}
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Start</span>
                        </div>
                        <input type="time" name="working_hours[{{ $index }}][start]" 
                               class="form-control day-time-input-{{ $index }}" 
                               value="{{ $hours['start'] ?? '' }}"
                               {{ !($hours['enabled'] ?? false) ? 'disabled' : '' }}>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">End</span>
                        </div>
                        <input type="time" name="working_hours[{{ $index }}][end]" 
                               class="form-control day-time-input-{{ $index }}" 
                               value="{{ $hours['end'] ?? '' }}"
                               {{ !($hours['enabled'] ?? false) ? 'disabled' : '' }}>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Breaks (Optional) -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-secondary text-white">
        <h5 class="mb-0"><i class="fas fa-coffee mr-2"></i>Break Times (Optional)</h5>
    </div>
    <div class="card-body">
        <div id="breaks-container">
            @php
                $breaks = old('breaks', $calendar->availability_rules['breaks'] ?? []);
            @endphp
            
            @forelse($breaks as $index => $break)
            <div class="row align-items-center mb-2 break-row">
                <div class="col-md-5">
                    <input type="time" name="breaks[{{ $index }}][start]" class="form-control" 
                           value="{{ $break['start'] }}" placeholder="Start Time">
                </div>
                <div class="col-md-5">
                    <input type="time" name="breaks[{{ $index }}][end]" class="form-control" 
                           value="{{ $break['end'] }}" placeholder="End Time">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn-sm btn-danger" onclick="removeBreak(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            @empty
            @endforelse
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addBreak()">
            <i class="fas fa-plus mr-1"></i>Add Break
        </button>
    </div>
</div>

<!-- Booking Options -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="fas fa-cog mr-2"></i>Booking Options</h5>
    </div>
    <div class="card-body">
        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="allow_ai_booking" name="allow_ai_booking" value="1"
                   {{ old('allow_ai_booking', $calendar->allow_ai_booking ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="allow_ai_booking">
                <strong>Allow AI Agent Booking</strong>
                <br><small class="text-muted">Let AI agent automatically schedule appointments</small>
            </label>
        </div>

        <div class="custom-control custom-switch mb-3">
            <input type="checkbox" class="custom-control-input" id="allow_manual_booking" name="allow_manual_booking" value="1"
                   {{ old('allow_manual_booking', $calendar->allow_manual_booking ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="allow_manual_booking">
                <strong>Allow Manual Booking</strong>
                <br><small class="text-muted">Enable manual appointment scheduling through UI</small>
            </label>
        </div>

        <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="require_confirmation" name="require_confirmation" value="1"
                   {{ old('require_confirmation', $calendar->require_confirmation ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="require_confirmation">
                <strong>Require Confirmation</strong>
                <br><small class="text-muted">Bookings need manual confirmation before being finalized</small>
            </label>
        </div>
    </div>
</div>

@push('scripts')
<script>
let breakCounter = {{ count($breaks ?? []) }};

function toggleDayInputs(index) {
    const checkbox = document.getElementById('day_enabled_' + index);
    const inputs = document.querySelectorAll('.day-time-input-' + index);
    
    inputs.forEach(input => {
        input.disabled = !checkbox.checked;
    });
}

function addBreak() {
    const container = document.getElementById('breaks-container');
    const html = `
        <div class="row align-items-center mb-2 break-row">
            <div class="col-md-5">
                <input type="time" name="breaks[${breakCounter}][start]" class="form-control" placeholder="Start Time">
            </div>
            <div class="col-md-5">
                <input type="time" name="breaks[${breakCounter}][end]" class="form-control" placeholder="End Time">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn-sm btn-danger" onclick="removeBreak(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    breakCounter++;
}

function removeBreak(button) {
    button.closest('.break-row').remove();
}
</script>
@endpush
