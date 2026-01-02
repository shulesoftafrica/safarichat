@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow text-center">
                <div class="card-body py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    
                    <h2 class="text-success mb-3">Payment Successful!</h2>
                    
                    <p class="lead mb-4">
                        Your upgrade to <strong>{{ ucfirst($plan) }} Plan</strong> has been completed successfully.
                    </p>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        Your new features are now active and ready to use!
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="{{ route('ai-agents.index') }}" class="btn btn-primary">
                            <i class="fas fa-robot"></i> Go to AI Agents
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