<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-12 mb-3">
        <a href="javascript:void(0)" onclick="$('#bookingModal').modal('show')" class="btn-primary">
            <i class="fas fa-plus-circle mr-2"></i>{{ __("appointments.actions.book_new") }}
        </a>
    </div>
    
    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">{{ __("appointments.stats.upcoming") }}</h6>
                        <h3 class="mb-0 text-primary">{{ $stats['upcoming'] ?? 0 }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar-day fa-2x text-primary opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">{{ __("appointments.stats.pending") }}</h6>
                        <h3 class="mb-0 text-warning">{{ $stats['pending'] ?? 0 }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">{{ __("appointments.stats.completed") }}</h6>
                        <h3 class="mb-0 text-success">{{ $stats['completed'] ?? 0 }}</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">{{ __("appointments.stats.no_show_rate") }}</h6>
                        <h3 class="mb-0 text-danger">{{ number_format($stats['no_show_rate'] ?? 0, 1) }}%</h3>
                    </div>
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-times fa-2x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('appointments.index') }}" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __("appointments.filters.status_label") }}</label>
                    <select name="status" class="form-control" onchange="document.getElementById('filterForm').submit()">
                        <option value="">{{ __("appointments.filters.all_statuses") }}</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __("appointments.status.pending") }}</option>
                        <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>{{ __("appointments.status.confirmed") }}</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __("appointments.status.completed") }}</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __("appointments.status.cancelled") }}</option>
                        <option value="no_show" {{ request('status') == 'no_show' ? 'selected' : '' }}>{{ __("appointments.status.no_show") }}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">{{ __("appointments.filters.type_label") }}</label>
                    <select name="type" class="form-control" onchange="document.getElementById('filterForm').submit()">
                        <option value="">{{ __("appointments.filters.all_types") }}</option>
                        <option value="demo" {{ request('type') == 'demo' ? 'selected' : '' }}>{{ __("appointments.types.demo") }}</option>
                        <option value="consultation" {{ request('type') == 'consultation' ? 'selected' : '' }}>{{ __("appointments.types.consultation") }}</option>
                        <option value="follow_up" {{ request('type') == 'follow_up' ? 'selected' : '' }}>{{ __("appointments.types.follow_up") }}</option>
                        <option value="meeting" {{ request('type') == 'meeting' ? 'selected' : '' }}>{{ __("appointments.types.meeting") }}</option>
                        <option value="call" {{ request('type') == 'call' ? 'selected' : '' }}>{{ __("appointments.types.call") }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __("appointments.filters.from_date_label") }}</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}" onchange="document.getElementById('filterForm').submit()">
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __("appointments.filters.to_date_label") }}</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}" onchange="document.getElementById('filterForm').submit()">
                </div>

                <div class="col-md-2">
                    <a href="{{ route('appointments.index') }}" class="btn btn-secondary btn-block">
                        <i class="fas fa-redo mr-1"></i>{{ __("appointments.actions.reset") }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Appointments Table -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list mr-2"></i>{{ __("appointments.table.title") }}
            </h5>
            <span class="badge badge-primary badge-pill">{{ $appointments->total() }} {{ __("appointments.stats.total") }}</span>
        </div>
    </div>
    <div class="card-body p-0">
        @if($appointments->count() > 0)
        <div class="table-responsive">
            <table class="table-standard mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>{{ __("appointments.table.date_time") }}</th>
                        <th>{{ __("appointments.table.customer") }}</th>
                        <th>{{ __("appointments.table.type") }}</th>
                        <th>{{ __("appointments.table.duration") }}</th>
                        <th>{{ __("appointments.table.status") }}</th>
                        <th>{{ __("appointments.table.booking_slot") }}</th>
                        <th class="text-center">{{ __("appointments.table.actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($appointments as $appointment)
                    <tr>
                        <td>
                            <div>
                                <strong>{{ \Carbon\Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time)->format('M d, Y') }}</strong>
                            </div>
                            <small class="text-muted">
                                <i class="far fa-clock mr-1"></i>{{ \Carbon\Carbon::parse($appointment->appointment_time)->format('g:i A') }}
                            </small>
                        </td>
                        <td>
                            <div>{{ $appointment->lead->name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $appointment->lead->phone ?? '' }}</small>
                        </td>
                        <td>
                            <span class="badge badge-soft-primary">{{ ucfirst(str_replace('_', ' ', $appointment->appointment_type)) }}</span>
                        </td>
                        <td>{{ $appointment->duration_minutes ?? 60 }} min</td>
                        <td>
                            @php
                                $statusColors = [
                                    'pending' => 'warning',
                                    'confirmed' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary',
                                    'no_show' => 'danger'
                                ];
                                $color = $statusColors[$appointment->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ ucfirst($appointment->status) }}</span>
                        </td>
                        <td>
                            @if($appointment->bookingSlot)
                                <i class="fas fa-check-circle text-success" title="{{ __("appointments.slot.reserved") }}"></i>
                                <small class="text-muted">{{ ucfirst($appointment->bookingSlot->status) }}</small>
                            @else
                                <i class="fas fa-exclamation-triangle text-warning" title="{{ __("appointments.slot.no_slot") }}"></i>
                                <small class="text-muted">{{ __("appointments.slot.legacy") }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('appointments.show', $appointment->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __("appointments.actions.view_details") }}">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @if($appointment->status == 'pending')
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="confirmAppointment({{ $appointment->id }})" title="{{ __("appointments.actions.confirm") }}">
                                    <i class="fas fa-check"></i>
                                </button>
                                @endif
                                
                                @if(in_array($appointment->status, ['pending', 'confirmed']))
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelAppointment({{ $appointment->id }})" title="{{ __("appointments.actions.cancel") }}">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <p class="text-muted">{{ __("appointments.empty.no_appointments") }}</p>
            @if(request()->hasAny(['status', 'type', 'from_date', 'to_date']))
            <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-primary">{{ __("appointments.actions.clear_filters") }}</a>
            @endif
        </div>
        @endif
    </div>
    
    @if($appointments->hasPages())
    <div class="card-footer bg-white">
        {{ $appointments->links() }}
    </div>
    @endif
</div>
