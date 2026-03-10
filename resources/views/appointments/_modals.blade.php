<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>{{ __("appointments.modals.confirm.title") }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="confirmForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>{{ __("appointments.modals.confirm.message") }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal">{{ __("appointments.actions.cancel") }}</button>
                    <button type="submit" class="btn-primary">{{ __("appointments.actions.confirm_appointment") }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>{{ __("appointments.modals.cancel.title") }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>{{ __("appointments.modals.cancel.reason_label") }}</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" placeholder="{{ __("appointments.modals.cancel.reason_placeholder") }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" data-dismiss="modal">{{ __("appointments.actions.close") }}</button>
                    <button type="submit" class="btn-danger">{{ __("appointments.actions.cancel_appointment") }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Book New Appointment Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus mr-2"></i>{{ __("appointments.modals.booking.title") }}</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('appointments.store') }}" method="POST" id="bookingForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.customer_name") }} <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control" required placeholder="{{ __("appointments.form.customer_name_placeholder") }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.customer_phone") }} <span class="text-danger">*</span></label>
                                <input type="text" name="customer_phone" class="form-control" required placeholder="{{ __("appointments.form.customer_phone_placeholder") }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.appointment_date") }} <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointmentDate" class="form-control" required min="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.appointment_time") }} <span class="text-danger">*</span></label>
                                <input type="time" name="appointment_time" id="appointmentTime" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.appointment_type") }} <span class="text-danger">*</span></label>
                                <select name="appointment_type" class="form-control" required>
                                    <option value="">{{ __("appointments.form.select_type") }}</option>
                                    <option value="demo">{{ __("appointments.types.demo") }}</option>
                                    <option value="consultation">{{ __("appointments.types.consultation") }}</option>
                                    <option value="follow_up">{{ __("appointments.types.follow_up") }}</option>
                                    <option value="meeting">{{ __("appointments.types.meeting") }}</option>
                                    <option value="call">{{ __("appointments.types.call") }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __("appointments.form.duration") }}</label>
                                <select name="duration_minutes" class="form-control">
                                    <option value="15">{{ __("appointments.duration.15") }}</option>
                                    <option value="30" selected>{{ __("appointments.duration.30") }}</option>
                                    <option value="45">{{ __("appointments.duration.45") }}</option>
                                    <option value="60">{{ __("appointments.duration.60") }}</option>
                                    <option value="90">{{ __("appointments.duration.90") }}</option>
                                    <option value="120">{{ __("appointments.duration.120") }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>{{ __("appointments.form.title") }}</label>
                        <input type="text" name="title" class="form-control" placeholder="{{ __("appointments.form.title_placeholder") }}">
                    </div>

                    <div class="form-group">
                        <label>{{ __("appointments.form.description") }}</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="{{ __("appointments.form.description_placeholder") }}"></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>{{ __("appointments.modals.booking.note_title") }}</strong> {{ __("appointments.modals.booking.note_text") }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __("appointments.actions.cancel") }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check mr-1"></i>{{ __("appointments.actions.book_appointment") }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
