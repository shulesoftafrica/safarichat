@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-tasks"></i> Queue System Testing</h4>
                    <p class="mb-0 text-muted">Test message processing via queue system</p>
                </div>
                <div class="card-body">
                    <!-- Queue Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5>Queue Statistics</h5>
                            <div class="row" id="queueStats">
                                <div class="col-md-3">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h3 id="pendingJobs">-</h3>
                                            <p>Pending Jobs</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body text-center">
                                            <h3 id="failedJobs">-</h3>
                                            <p>Failed Jobs</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h3 id="highPriorityJobs">-</h3>
                                            <p>High Priority</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h3 id="messageJobs">-</h3>
                                            <p>Message Queue</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Forms -->
                    <div class="row">
                        <!-- Test Outgoing Message -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-paper-plane"></i> Test Outgoing Message Queue</h5>
                                </div>
                                <div class="card-body">
                                    <form id="testOutgoingForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" name="phone_number" 
                                                   placeholder="e.g., +255123456789" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Message</label>
                                            <textarea class="form-control" name="message" rows="3" 
                                                      placeholder="Test message via queue" required>Hello! This is a test message sent via queue system.</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Instance ID (Optional)</label>
                                            <input type="text" class="form-control" name="instance_id" 
                                                   placeholder="WhatsApp instance ID">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Queue Outgoing Message
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Test Incoming Message -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-inbox"></i> Test Incoming Message Processing</h5>
                                </div>
                                <div class="card-body">
                                    <form id="testIncomingForm">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Instance ID</label>
                                            <input type="text" class="form-control" name="instance_id" 
                                                   placeholder="WhatsApp instance ID" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="text" class="form-control" name="phone_number" 
                                                   placeholder="e.g., +255123456789" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Incoming Message</label>
                                            <textarea class="form-control" name="message" rows="3" 
                                                      placeholder="Simulate incoming message" required>Hello, I need help with pricing</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-download"></i> Simulate Incoming Message
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Queue Control -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-cog"></i> Queue Management</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Queue Worker Status</h6>
                                            <div class="alert alert-info">
                                                <p><strong>To start queue worker (for testing):</strong></p>
                                                <code>php artisan queue:work --queue=high_priority,messages,bulk_messages,default</code>
                                                <br><br>
                                                <p><strong>For production with Redis:</strong></p>
                                                <code>php artisan queue:work redis --queue=high_priority,messages,bulk_messages,default</code>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Queue Actions</h6>
                                            <button class="btn btn-warning me-2" onclick="clearFailedJobs()">
                                                <i class="fas fa-trash"></i> Clear Failed Jobs
                                            </button>
                                            <button class="btn btn-info me-2" onclick="retryFailedJobs()">
                                                <i class="fas fa-redo"></i> Retry Failed Jobs
                                            </button>
                                            <button class="btn btn-secondary" onclick="refreshStats()">
                                                <i class="fas fa-sync"></i> Refresh Stats
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Jobs -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Recent Jobs</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Queue</th>
                                                    <th>Attempts</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody id="recentJobs">
                                                <tr>
                                                    <td colspan="4" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Recent Failed Jobs</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Queue</th>
                                                    <th>Failed At</th>
                                                </tr>
                                            </thead>
                                            <tbody id="recentFailedJobs">
                                                <tr>
                                                    <td colspan="3" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Results -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>Test Results</h6>
                                </div>
                                <div class="card-body">
                                    <div id="testResults" style="max-height: 300px; overflow-y: auto;">
                                        <p class="text-muted">Test results will appear here...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh queue stats every 5 seconds
setInterval(refreshStats, 5000);

// Load initial stats
document.addEventListener('DOMContentLoaded', function() {
    refreshStats();
});

// Refresh queue statistics
function refreshStats() {
    fetch('/api/waapi/queue-stats', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStats(data.data);
        }
    })
    .catch(error => {
        console.error('Error fetching queue stats:', error);
    });
}

function updateStats(stats) {
    document.getElementById('pendingJobs').textContent = stats.pending_jobs;
    document.getElementById('failedJobs').textContent = stats.failed_jobs;
    
    // Update queue breakdown
    let highPriority = 0;
    let messages = 0;
    
    stats.queue_breakdown.forEach(queue => {
        if (queue.queue === 'high_priority') {
            highPriority = queue.count;
        } else if (queue.queue === 'messages') {
            messages = queue.count;
        }
    });
    
    document.getElementById('highPriorityJobs').textContent = highPriority;
    document.getElementById('messageJobs').textContent = messages;
    
    // Update recent jobs table
    updateRecentJobs(stats.recent_jobs);
    updateRecentFailedJobs(stats.recent_failed_jobs);
}

function updateRecentJobs(jobs) {
    const tbody = document.getElementById('recentJobs');
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No recent jobs</td></tr>';
        return;
    }
    
    tbody.innerHTML = jobs.map(job => `
        <tr>
            <td>${job.id}</td>
            <td><span class="badge bg-primary">${job.queue}</span></td>
            <td>${job.attempts}</td>
            <td>${new Date(job.created_at * 1000).toLocaleTimeString()}</td>
        </tr>
    `).join('');
}

function updateRecentFailedJobs(jobs) {
    const tbody = document.getElementById('recentFailedJobs');
    if (jobs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center">No failed jobs</td></tr>';
        return;
    }
    
    tbody.innerHTML = jobs.map(job => `
        <tr>
            <td>${job.id}</td>
            <td><span class="badge bg-danger">${job.queue}</span></td>
            <td>${new Date(job.failed_at).toLocaleTimeString()}</td>
        </tr>
    `).join('');
}

// Test outgoing message
document.getElementById('testOutgoingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    addTestResult('info', 'Queuing outgoing message...');
    
    fetch('/api/waapi/test-queue-message', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTestResult('success', 'Outgoing message queued successfully: ' + JSON.stringify(data.data));
            this.reset();
            refreshStats();
        } else {
            addTestResult('error', 'Error queuing outgoing message: ' + data.message);
        }
    })
    .catch(error => {
        addTestResult('error', 'Network error: ' + error.message);
    });
});

// Test incoming message
document.getElementById('testIncomingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    addTestResult('info', 'Simulating incoming message...');
    
    fetch('/api/waapi/test-incoming-message', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addTestResult('success', 'Incoming message simulated successfully: ' + JSON.stringify(data.data));
            this.reset();
            refreshStats();
        } else {
            addTestResult('error', 'Error simulating incoming message: ' + data.message);
        }
    })
    .catch(error => {
        addTestResult('error', 'Network error: ' + error.message);
    });
});

function addTestResult(type, message) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    
    const alertClass = type === 'success' ? 'alert-success' : 
                     type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const resultHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <small class="text-muted">${timestamp}</small><br>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    resultsDiv.insertAdjacentHTML('afterbegin', resultHTML);
}

function clearFailedJobs() {
    if (confirm('Are you sure you want to clear all failed jobs?')) {
        fetch('/api/waapi/clear-failed-jobs', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            addTestResult('success', 'Failed jobs cleared');
            refreshStats();
        })
        .catch(error => {
            addTestResult('error', 'Error clearing failed jobs: ' + error.message);
        });
    }
}

function retryFailedJobs() {
    if (confirm('Are you sure you want to retry all failed jobs?')) {
        fetch('/api/waapi/retry-failed-jobs', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            addTestResult('success', 'Failed jobs retried');
            refreshStats();
        })
        .catch(error => {
            addTestResult('error', 'Error retrying failed jobs: ' + error.message);
        });
    }
}
</script>

<style>
.card {
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    border: none;
}

.alert {
    margin-bottom: 0.5rem;
}

#testResults .alert {
    padding: 0.5rem;
    font-size: 0.9rem;
}

.table-sm td, .table-sm th {
    padding: 0.3rem;
    font-size: 0.85rem;
}

.badge {
    font-size: 0.7rem;
}
</style>
@endsection
