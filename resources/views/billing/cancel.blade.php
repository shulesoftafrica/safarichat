@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow text-center">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="fas fa-times-circle fa-5x text-warning"></i>
                    </div>
                    
                    <h2 class="text-warning mb-3">Payment Cancelled</h2>
                    
                    <p class="lead mb-4">
                        Your payment was cancelled. No charges were made to your account.
                    </p>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        You can try upgrading again anytime you're ready.
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('ai-agents.index') }}" class="btn btn-primary">
                            <i class="fas fa-robot"></i> Back to AI Agents
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-primary">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection