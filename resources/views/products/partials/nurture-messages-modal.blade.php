{{-- resources/views/products/partials/nurture-messages-modal.blade.php --}}

<div class="modal fade" id="nurtureMessagesModal" tabindex="-1" role="dialog" aria-labelledby="nurtureMessagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="nurtureMessagesModalLabel">
                    <i class="fas fa-gift"></i> AI Generated Nurture Messages for "<span id="productNameDisplay"></span>"
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body">
                <!-- Success Alert -->
                <div class="alert alert-success" id="nurtureSuccessAlert">
                    <strong><i class="fas fa-check-circle"></i> Product Saved!</strong> 
                    AI generated <strong><span id="nurtureMessageCount">0</span></strong> nurture messages.
                    <br>
                    <small>These will automatically be used when contacts ghost during sales conversations.</small>
                </div>

                <!-- Action Buttons -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div>
                        <button class="btn btn-sm btn-success" id="approveAllNurture">
                            <i class="fas fa-check-double"></i> Approve All
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="regenerateAllNurture">
                            <i class="fas fa-sync-alt"></i> Regenerate All
                        </button>
                    </div>
                    <button class="btn btn-sm btn-primary" id="addCustomNurtureMessage">
                        <i class="fas fa-plus"></i> Add Custom Message
                    </button>
                </div>

                <!-- Loading State -->
                <div id="nurtureLoadingState" class="text-center py-5" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Generating nurture messages...</p>
                </div>

                <!-- Messages List -->
                <div id="nurtureMessagesList">
                    <!-- Messages will be inserted here dynamically -->
                </div>

                <!-- Custom Message Form (Initially Hidden) -->
                <div id="customNurtureMessageForm" class="card mt-3" style="display: none;">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-edit"></i> Add Custom Nurture Message</h6>
                    </div>
                    <div class="card-body">
                        <form id="newNurtureMessageForm">
                            <input type="hidden" id="customNurtureProductId" name="product_id">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nurtureTitle">Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" id="nurtureTitle" class="form-control" 
                                               placeholder="e.g., Case Study: 75% Faster Registration" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="nurtureContentType">Content Type <span class="text-danger">*</span></label>
                                        <select name="content_type" id="nurtureContentType" class="form-control" required>
                                            <option value="case_study">📊 Case Study</option>
                                            <option value="tip">💡 Quick Tip</option>
                                            <option value="insight">🔍 Industry Insight</option>
                                            <option value="testimonial">⭐ Testimonial</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="nurtureLanguage">Language <span class="text-danger">*</span></label>
                                        <select name="language" id="nurtureLanguage" class="form-control" required>
                                            <option value="en">English</option>
                                            <option value="sw">Swahili</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="nurtureContentBody">Message Content (2-3 sentences, value-first) <span class="text-danger">*</span></label>
                                <textarea name="content_body" id="nurtureContentBody" class="form-control" rows="3" required
                                    placeholder="St. Mary's Primary cut registration time by 75% using SMS auto-confirmation. Parents now get instant feedback. Thought helpful for intake season! 😊"></textarea>
                                <small class="form-text text-muted">
                                    ⚠️ DO NOT use: "I hope this finds you well", "following up", "please let me know"
                                </small>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nurtureTargetIndustry">Target Industry</label>
                                        <input type="text" name="target_industry" id="nurtureTargetIndustry" class="form-control" 
                                               placeholder="e.g., Education">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nurtureTargetJobTitle">Target Job Title</label>
                                        <input type="text" name="target_job_title" id="nurtureTargetJobTitle" class="form-control" 
                                               placeholder="e.g., School Director">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nurtureTone">Tone</label>
                                        <select name="tone" id="nurtureTone" class="form-control">
                                            <option value="casual">Casual</option>
                                            <option value="friendly">Friendly</option>
                                            <option value="formal">Formal</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-secondary mr-2" id="cancelCustomNurture">
                                    Cancel
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Save Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">
                    <i class="fas fa-check"></i> Done - Continue
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Nurture Message Card Template -->
<template id="nurtureMessageTemplate">
    <div class="card mb-2 nurture-message-card" data-message-id="">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <span class="badge badge-info content-type-badge"></span>
                <strong class="message-title ml-2"></strong>
                <span class="badge badge-secondary ml-2 language-badge"></span>
            </div>
            <div>
                <button class="btn btn-sm btn-warning edit-nurture-btn" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger delete-nurture-btn" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <p class="message-content mb-2"></p>
            <div class="message-metadata">
                <small class="text-muted">
                    <i class="fas fa-bullseye"></i> Target: <span class="target-info"></span> •
                    <i class="fas fa-comments"></i> Tone: <span class="tone-info"></span>
                </small>
            </div>
        </div>
    </div>
</template>

<script>
// Global variable to store current product ID and messages
let currentNurtureProductId = null;
let currentNurtureMessages = [];

// Show nurture messages modal
function showNurtureMessagesModal(productId, productName, messages) {
    currentNurtureProductId = productId;
    currentNurtureMessages = messages || [];
    
    $('#productNameDisplay').text(productName);
    $('#nurtureMessageCount').text(messages ? messages.length : 0);
    $('#customNurtureProductId').val(productId);
    
    // Render messages
    renderNurtureMessages(messages);
    
    // Show modal
    $('#nurtureMessagesModal').modal('show');
}

// Render nurture messages
function renderNurtureMessages(messages) {
    const container = $('#nurtureMessagesList');
    container.empty();
    
    if (!messages || messages.length === 0) {
        container.html('<div class="alert alert-info">No nurture messages generated yet.</div>');
        return;
    }
    
    messages.forEach(msg => {
        const template = $('#nurtureMessageTemplate').contents().clone();
        
        template.attr('data-message-id', msg.id);
        template.find('.content-type-badge').text(formatContentType(msg.content_type));
        template.find('.message-title').text(msg.title);
        template.find('.language-badge').text(msg.language === 'sw' ? 'Swahili' : 'English');
        template.find('.message-content').text(msg.content_body);
        template.find('.target-info').text(msg.target_job_title || 'All roles');
        template.find('.tone-info').text(msg.tone || 'casual');
        
        container.append(template);
    });
}

// Format content type for display
function formatContentType(type) {
    const types = {
        'case_study': '📊 Case Study',
        'tip': '💡 Tip',
        'insight': '🔍 Insight',
        'testimonial': '⭐ Testimonial',
        'video': '🎥 Video'
    };
    return types[type] || type;
}

// Add custom message button
$('#addCustomNurtureMessage').click(function() {
    $('#customNurtureMessageForm').slideDown();
});

// Cancel custom message
$('#cancelCustomNurture').click(function() {
    $('#customNurtureMessageForm').slideUp();
    $('#newNurtureMessageForm')[0].reset();
});

// Submit custom message
$('#newNurtureMessageForm').submit(function(e) {
    e.preventDefault();
    
    const formData = $(this).serialize();
    
    $.ajax({
        url: '/api/nurture-library',
        type: 'POST',
        data: formData,
        success: function(response) {
            if (response.success) {
                toastr.success('Custom nurture message added successfully!');
                currentNurtureMessages.push(response.data);
                renderNurtureMessages(currentNurtureMessages);
                $('#customNurtureMessageForm').slideUp();
                $('#newNurtureMessageForm')[0].reset();
            }
        },
        error: function(xhr) {
            toastr.error('Failed to add custom message. Please try again.');
            console.error(xhr.responseJSON);
        }
    });
});

// Regenerate all messages
$('#regenerateAllNurture').click(function() {
    if (!confirm('Regenerate all messages? High-performing messages (>20% reply rate) will be kept.')) {
        return;
    }
    
    $('#nurtureLoadingState').show();
    $('#nurtureMessagesList').hide();
    
    $.ajax({
        url: `/api/products/${currentNurtureProductId}/nurture-messages/regenerate`,
        type: 'POST',
        success: function(response) {
            toastr.success(response.message);
            
            // Reload messages
            $.get(`/api/products/${currentNurtureProductId}/nurture-messages`, function(data) {
                currentNurtureMessages = data.messages;
                renderNurtureMessages(data.messages);
                $('#nurtureMessageCount').text(data.messages.length);
                $('#nurtureLoadingState').hide();
                $('#nurtureMessagesList').show();
            });
        },
        error: function(xhr) {
            toastr.error('Failed to regenerate messages.');
            $('#nurtureLoadingState').hide();
            $('#nurtureMessagesList').show();
        }
    });
});

// Delete message
$(document).on('click', '.delete-nurture-btn', function() {
    if (!confirm('Delete this nurture message?')) return;
    
    const card = $(this).closest('.nurture-message-card');
    const messageId = card.data('message-id');
    
    $.ajax({
        url: `/api/nurture-library/${messageId}`,
        type: 'DELETE',
        success: function(response) {
            card.fadeOut(function() {
                $(this).remove();
                currentNurtureMessages = currentNurtureMessages.filter(m => m.id !== messageId);
                $('#nurtureMessageCount').text(currentNurtureMessages.length);
                toastr.success('Nurture message deleted');
            });
        },
        error: function() {
            toastr.error('Failed to delete message');
        }
    });
});

// Edit message (simplified version - could expand to inline editing)
$(document).on('click', '.edit-nurture-btn', function() {
    const messageId = $(this).closest('.nurture-message-card').data('message-id');
    const message = currentNurtureMessages.find(m => m.id === messageId);
    
    if (message) {
        // Populate form with message data
        $('#nurtureTitle').val(message.title);
        $('#nurtureContentType').val(message.content_type);
        $('#nurtureLanguage').val(message.language);
        $('#nurtureContentBody').val(message.content_body);
        $('#nurtureTargetIndustry').val(message.target_industry);
        $('#nurtureTargetJobTitle').val(message.target_job_title);
        $('#nurtureTone').val(message.tone);
        
        // Show form and change submit to update
        $('#customNurtureMessageForm').slideDown();
        // TODO: Change form action to PUT /api/nurture-library/{id}
    }
});

// Handle modal close - refresh page to show updated product
$('#nurtureMessagesModal').on('hidden.bs.modal', function () {
    // Check if we're in onboarding mode
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('onboarding') === 'true') {
        window.location.href = '/dashboard?onboarding_completed=products';
    } else {
        window.location.reload();
    }
});
</script>

<style>
.nurture-message-card {
    transition: all 0.3s ease;
}

.nurture-message-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.content-type-badge {
    font-size: 0.85em;
}

.language-badge {
    font-size: 0.75em;
}

#nurtureLoadingState i {
    color: #007bff;
}
</style>
