@extends('layouts.app')
@section('content')
<div class="container mt-4">
    
    <h2>Edit WhatsApp Instance</h2>
    <form method="POST" action="{{ url('/whatsapp/instances/' . $instance->id . '/update') }}">
        @csrf
        <div class="mb-3">
            <label for="instance_name" class="form-label">Instance Name</label>
            <input type="text" class="form-control" id="instance_name" name="instance_name" value="{{ old('instance_name', $instance->instance_name) }}">
        </div>
        <div class="mb-3">
            <label for="display_name" class="form-label">Display Name</label>
            <input type="text" class="form-control" id="display_name" name="display_name" value="{{ old('display_name', $instance->display_name) }}">
        </div>
        <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $instance->phone_number) }}">
            <div class="form-text text-warning">
                <strong>Warning:</strong> Changing this number will <u>not</u> change the WhatsApp number. If you want to change the WhatsApp number, disconnect then connect it again.
            </div>
        </div>
        <div class="mb-3">
            <label for="instance_description" class="form-label">Description</label>
            <textarea class="form-control" id="instance_description" name="instance_description">{{ old('instance_description', $instance->instance_description) }}</textarea>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_primary" name="is_primary" value="1" {{ old('is_primary', $instance->is_primary) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_primary">Primary Instance</label>
        </div>
        <button type="submit" class="btn btn-primary">Update Instance</button>
        <a href="{{ url('/whatsapp/instances') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>
@endsection
