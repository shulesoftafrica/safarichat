@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-comments"></i> Incoming WhatsApp Messages
                    </h4>
                    <div class="d-flex gap-2">
                        <select id="instanceSelect" class="form-select">
                            <option value="">Select WhatsApp Instance</option>
                            @foreach($whatsappInstances as $instance)
                                <option value="{{ $instance->instance_id }}">{{ $instance->name ?? $instance->instance_id }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary" id="refreshMessages">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                        <button class="btn btn-success" id="processMessages">
                            <i class="fas fa-play"></i> Process Messages
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filter Options -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">All Status</option>
                                <option value="received">Received</option>
                                <option value="processed">Processed</option>
                                <option value="replied">Replied</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="phoneFilter" class="form-control" placeholder="Filter by phone number">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="dateFilter" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary" id="clearFilters">Clear Filters</button>
                        </div>
                    </div>

                    <!-- Messages Table -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="messagesTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Time</th>
                                    <th>Phone Number</th>
                                    <th>Contact Name</th>
                                    <th>Message</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Auto Reply</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Messages will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div id="messageCount">
                            Showing 0 messages
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="pagination">
                                <!-- Pagination will be loaded here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Message Details Modal -->
<div class="modal fade" id="messageDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="messageDetailsContent">
                <!-- Message details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="replyToMessage">Reply</button>
                <button type="button" class="btn btn-success" id="markAsProcessed">Mark as Processed</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Reply</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="replyForm">
                    <div class="mb-3">
                        <label for="replyText" class="form-label">Message</label>
                        <textarea class="form-control" id="replyText" rows="4" required></textarea>
                    </div>
                    <input type="hidden" id="replyToPhone">
                    <input type="hidden" id="replyInstanceId">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="sendReply">Send Reply</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<style>
.message-preview {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-badge {
    font-size: 0.75rem;
}

.auto-reply-indicator {
    color: #28a745;
    font-size: 0.8rem;
}

.table th {
    border-top: none;
}

.table td {
    vertical-align: middle;
}

.instance-status {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 5px;
}

.instance-status.connected {
    background-color: #28a745;
}

.instance-status.disconnected {
    background-color: #dc3545;
}
</style>

<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentInstanceId = '';
    
    // Load messages when instance is selected
    $('#instanceSelect').change(function() {
        currentInstanceId = $(this).val();
        if (currentInstanceId) {
            loadMessages();
        } else {
            $('#messagesTable tbody').empty();
            $('#messageCount').text('Showing 0 messages');
        }
    });
    
    // Refresh messages
    $('#refreshMessages').click(function() {
        if (currentInstanceId) {
            loadMessages();
        } else {
            alert('Please select a WhatsApp instance first');
        }
    });
    
    // Process messages from WAAPI
    $('#processMessages').click(function() {
        if (!currentInstanceId) {
            alert('Please select a WhatsApp instance first');
            return;
        }
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.post(`{{url('api/waapi/process-messages/${currentInstanceId}')}}`)
            .done(function(response) {
                if (response.status=='success') {
                    alert(`Successfully processed  messages`);
                    loadMessages();
                } else {
                    alert('Failed to process messages: ' + response.message);
                }
            })
            .fail(function() {
                alert('Error processing messages');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-play"></i> Process Messages');
            });
    });
    
    // Apply filters
    $('#statusFilter, #phoneFilter, #dateFilter').on('change keyup', function() {
        if (currentInstanceId) {
            loadMessages();
        }
    });
    
    // Clear filters
    $('#clearFilters').click(function() {
        $('#statusFilter').val('');
        $('#phoneFilter').val('');
        $('#dateFilter').val('');
        if (currentInstanceId) {
            loadMessages();
        }
    });
    
    // View message details
    $(document).on('click', '.view-message', function() {
        const messageData = $(this).data('message');
        showMessageDetails(messageData);
    });
    
    // Reply to message
    $(document).on('click', '.reply-message', function() {
        const messageData = $(this).data('message');
        showReplyModal(messageData);
    });
    
    // Mark as processed
    $(document).on('click', '.mark-processed', function() {
        const messageId = $(this).data('message-id');
        markAsProcessed(messageId);
    });
    
    // Send reply
    $('#sendReply').click(function() {
        sendReply();
    });
    
    function loadMessages(page = 1) {
        const params = {
            limit: 20,
            offset: (page - 1) * 20,
            status: $('#statusFilter').val(),
            phone: $('#phoneFilter').val(),
            date: $('#dateFilter').val()
        };
        
        $.get(`{{url('/api/waapi/incoming-messages/${currentInstanceId}')}}`, params)
            .done(function(response) {
                if (response.success) {
                    displayMessages(response.messages);
                    updatePagination(response.total, page);
                } else {
                    alert('Failed to load messages: ' + response.message);
                }
            })
            .fail(function() {
                alert('Error loading messages');
            });
    }
    
    function displayMessages(messages) {
        const tbody = $('#messagesTable tbody');
        tbody.empty();
        
        if (messages.length === 0) {
            tbody.append('<tr><td colspan="8" class="text-center">No messages found</td></tr>');
            return;
        }
        
        messages.forEach(function(message) {
            const row = `
                <tr>
                    <td>${formatDateTime(message.received_at)}</td>
                    <td>
                        <a href="#" class="text-decoration-none">${message.phone_number}</a>
                    </td>
                    <td>${message.guest ? message.guest.name || 'Unknown' : 'Unknown'}</td>
                    <td>
                        <div class="message-preview" title="${escapeHtml(message.message_body)}">
                            ${escapeHtml(message.message_body)}
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-info">${message.message_type}</span>
                    </td>
                    <td>
                        <span class="badge status-badge ${getStatusClass(message.status)}">${message.status}</span>
                    </td>
                    <td>
                        ${message.auto_reply ? '<i class="fas fa-check auto-reply-indicator" title="Auto-replied"></i>' : ''}
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary btn-sm view-message" data-message='${JSON.stringify(message)}' title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-success btn-sm reply-message" data-message='${JSON.stringify(message)}' title="Reply">
                                <i class="fas fa-reply"></i>
                            </button>
                            ${message.status !== 'processed' ? `<button class="btn btn-outline-warning btn-sm mark-processed" data-message-id="${message.id}" title="Mark as Processed">
                                <i class="fas fa-check"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
        
        $('#messageCount').text(`Showing ${messages.length} messages`);
    }
    
    function getStatusClass(status) {
        switch (status) {
            case 'received': return 'bg-primary';
            case 'processed': return 'bg-success';
            case 'replied': return 'bg-info';
            default: return 'bg-secondary';
        }
    }
    
    function formatDateTime(datetime) {
        return new Date(datetime).toLocaleString();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showMessageDetails(message) {
        const content = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Message ID:</strong> ${message.message_id}<br>
                    <strong>Phone:</strong> ${message.phone_number}<br>
                    <strong>Contact:</strong> ${message.guest ? message.guest.name || 'Unknown' : 'Unknown'}<br>
                    <strong>Type:</strong> ${message.message_type}<br>
                    <strong>Status:</strong> <span class="badge ${getStatusClass(message.status)}">${message.status}</span><br>
                    <strong>Received:</strong> ${formatDateTime(message.received_at)}<br>
                    <strong>Auto Reply:</strong> ${message.auto_reply ? 'Yes' : 'No'}
                </div>
                <div class="col-md-6">
                    <strong>Chat ID:</strong> ${message.chat_id}<br>
                    ${message.media_data ? `<strong>Media:</strong> ${message.media_data}<br>` : ''}
                    ${message.metadata ? `<strong>Metadata:</strong> <pre>${JSON.stringify(JSON.parse(message.metadata), null, 2)}</pre>` : ''}
                </div>
            </div>
            <hr>
            <div>
                <strong>Message Content:</strong>
                <div class="border p-3 mt-2 bg-light">
                    ${escapeHtml(message.message_body)}
                </div>
            </div>
        `;
        
        $('#messageDetailsContent').html(content);
        $('#messageDetailsModal').modal('show');
    }
    
    function showReplyModal(message) {
        $('#replyToPhone').val(message.phone_number);
        $('#replyInstanceId').val(currentInstanceId);
        $('#replyText').val('');
        $('#replyModal').modal('show');
    }
    
    function sendReply() {
        const phone = $('#replyToPhone').val();
        const message = $('#replyText').val();
        const instanceId = $('#replyInstanceId').val();
        
        if (!message.trim()) {
            alert('Please enter a message');
            return;
        }
        
        const btn = $('#sendReply');
        btn.prop('disabled', true).text('Sending...');
        
        $.post('/guest/sendMessage', {
            phone: phone,
            message: message,
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                alert('Reply sent successfully');
                $('#replyModal').modal('hide');
                loadMessages();
            } else {
                alert('Failed to send reply: ' + response.message);
            }
        })
        .fail(function() {
            alert('Error sending reply');
        })
        .always(function() {
            btn.prop('disabled', false).text('Send Reply');
        });
    }
    
    function markAsProcessed(messageId) {
        $.post(`{{url('/api/waapi/mark-processed/${messageId}')}}`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        })
        .done(function(response) {
            if (response.success) {
                loadMessages();
            } else {
                alert('Failed to mark as processed: ' + response.message);
            }
        })
        .fail(function() {
            alert('Error marking message as processed');
        });
    }
    
    function updatePagination(total, currentPage) {
        const totalPages = Math.ceil(total / 20);
        const pagination = $('#pagination');
        pagination.empty();
        
        if (totalPages <= 1) return;
        
        // Previous button
        pagination.append(`
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Previous</a>
            </li>
        `);
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === currentPage || i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                pagination.append(`
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            } else if (i === currentPage - 3 || i === currentPage + 3) {
                pagination.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }
        
        // Next button
        pagination.append(`
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Next</a>
            </li>
        `);
    }
    
    // Handle pagination clicks
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && page !== currentPage) {
            currentPage = page;
            loadMessages(page);
        }
    });
});
</script>
@endsection
