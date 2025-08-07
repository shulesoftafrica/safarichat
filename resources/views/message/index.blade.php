@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .compose-container {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 20px;
    }
    
    .compose-header {
        background: linear-gradient(135deg, #25d366 0%, #20c759 100%);
        border-radius: 20px 20px 0 0;
        padding: 30px;
        color: white;
        margin-bottom: 0;
    }
    
    .compose-title {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .compose-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        margin: 0;
    }
    
    .compose-main {
        background: white;
        border-radius: 0 0 20px 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .compose-form {
        padding: 40px;
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
    }
    
    .form-control-modern {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 16px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: #fafafa;
        width: 100%;
    }
    
    .form-control-modern:focus {
        border-color: #25d366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        outline: none;
        background: white;
    }
    
    .recipient-card {
        background: #f8fafc;
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 20px;
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
        margin-bottom: 16px;
    }
    
    .recipient-card:hover {
        border-color: #25d366;
        background: #f0fff4;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.15);
    }
    
    .recipient-card.selected {
        border-color: #25d366;
        background: #f0fff4;
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.15);
    }
    
    .recipient-card.selected::after {
        content: "✓";
        position: absolute;
        top: 15px;
        right: 15px;
        background: #25d366;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }
    
    .recipient-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
        font-size: 20px;
    }
    
    .recipient-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0 0 4px 0;
        color: #111827;
    }
    
    .recipient-desc {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
    }
    
    .contact-input-section {
        position: relative;
    }
    
    .contact-tags {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        background: #fafafa;
        min-height: 56px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        transition: all 0.3s ease;
    }
    
    .contact-tags:focus-within {
        border-color: #25d366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
        background: white;
    }
    
    .contact-tag {
        background: #25d366;
        color: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .contact-tag .remove {
        cursor: pointer;
        font-weight: bold;
        opacity: 0.8;
    }
    
    .contact-tag .remove:hover {
        opacity: 1;
    }
    
    .contact-input {
        border: none;
        outline: none;
        background: none;
        flex: 1;
        min-width: 200px;
        padding: 8px 0;
        font-size: 1rem;
    }
    
    .message-composer {
        border: 2px solid #e5e7eb;
        border-radius: 18px;
        background: white;
        position: relative;
        transition: all 0.3s ease;
        overflow: hidden;
    }
    
    .message-composer:focus-within {
        border-color: #25d366;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
    }
    
    .message-attachments {
        display: none;
        padding: 16px 16px 0 16px;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .attachment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 12px;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .attachment-item {
        position: relative;
        background: #f8fafc;
        border-radius: 8px;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .attachment-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .attachment-icon {
        font-size: 24px;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    
    .attachment-remove {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .hashtag-suggestions {
        display: none;
        position: absolute;
        bottom: 100%;
        left: 16px;
        right: 16px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .hashtag-item {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }
    
    .hashtag-item:last-child {
        border-bottom: none;
    }
    
    .hashtag-item:hover,
    .hashtag-item.selected {
        background: #f0fff4;
    }
    
    .hashtag-name {
        font-weight: 600;
        color: #25d366;
        margin-bottom: 2px;
    }
    
    .hashtag-desc {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .message-input-area {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        padding: 16px;
    }
    
    .message-input {
        flex: 1;
        border: none;
        outline: none;
        resize: none;
        font-size: 1rem;
        line-height: 1.5;
        padding: 12px 0;
        background: none;
        min-height: 24px;
        max-height: 120px;
        font-family: inherit;
    }
    
    .input-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .action-btn {
        background: none;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #6b7280;
    }
    
    .action-btn:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .send-btn {
        background: #25d366 !important;
        color: white !important;
    }
    
    .send-btn:disabled {
        background: #e5e7eb !important;
        color: #9ca3af !important;
        cursor: not-allowed;
    }
    
    .send-btn:not(:disabled):hover {
        background: #20c759 !important;
        transform: scale(1.05);
    }
    
    .char-counter {
        position: absolute;
        bottom: 8px;
        right: 16px;
        font-size: 0.75rem;
        color: #9ca3af;
        pointer-events: none;
    }
    
    .file-input-hidden {
        display: none;
    }
    
    .stats-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .message-stats {
        display: flex;
        gap: 20px;
    }
    
    .status-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .status-dot {
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .floating-send {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 56px;
        height: 56px;
        background: #25d366;
        color: white;
        border: none;
        border-radius: 50%;
        box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        z-index: 1000;
        transition: all 0.3s ease;
    }
    
    .floating-send:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 30px rgba(37, 211, 102, 0.6);
    }
    
    @media (max-width: 768px) {
        .compose-container {
            padding: 10px;
        }
        
        .compose-form {
            padding: 20px;
        }
        
        .compose-title {
            font-size: 1.5rem;
        }
        
        .recipient-card {
            margin-bottom: 12px;
        }
        
        .message-input-area {
            padding: 12px;
        }
        
        .attachment-grid {
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        }
    }
</style>

<div class="compose-container">
    <div class="container-fluid">
        <div class="compose-header">
            <h1 class="compose-title">
                <i class="fab fa-whatsapp"></i>
                WhatsApp Message Composer
            </h1>
            <p class="compose-subtitle">Send personalized WhatsApp messages to your contacts instantly</p>
        </div>
        
        <div class="compose-main">
            <form class="compose-form" method="POST" action="{{ url('message/store') }}" enctype="multipart/form-data" id="messageForm">
                @csrf
                
                <!-- Hidden input to force WhatsApp only -->
                <input type="hidden" name="source[]" value="whatsapp">
                
                <!-- Recipients Selection -->
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-users"></i> Who do you want to message?
                    </label>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="recipient-card" data-value="1">
                                <div class="recipient-icon" style="background: #dcfce7; color: #16a34a;">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <h3 class="recipient-title">All Contacts</h3>
                                <p class="recipient-desc">Send to everyone in your contact list</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="recipient-card" data-value="2">
                                <div class="recipient-icon" style="background: #dbeafe; color: #2563eb;">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <h3 class="recipient-title">Select Category</h3>
                                <p class="recipient-desc">Choose specific groups or categories</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="recipient-card" data-value="6">
                                <div class="recipient-icon" style="background: #ede9fe; color: #7c3aed;">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h3 class="recipient-title">Custom Numbers</h3>
                                <p class="recipient-desc">Enter specific phone numbers manually</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="recipient-card" data-value="7">
                                <div class="recipient-icon" style="background: #fef9c3; color: #ca8a04;">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <h3 class="recipient-title">Upload Excel</h3>
                                <p class="recipient-desc">Upload an Excel file with phone numbers</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="criteria" id="criteriaInput" required>
                </div>

                <!-- Category Selection (Hidden by default) -->
                <div class="form-section" id="categorySection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Select Customer Category
                    </label>
                    <select class="form-control-modern" name="event_guest_category_id" id="categorySelect">
                        <option value="">Choose a category...</option>
                        @if(isset($guest_categories))
                            @foreach ($guest_categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Custom Numbers Input (Hidden by default) -->
                <div class="form-section" id="customNumbersSection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> Enter Phone Numbers
                    </label>
                    <div class="contact-tags" id="contactTags">
                        <input type="text" class="contact-input" placeholder="Type phone numbers separated by comma or space..." id="contactInput">
                    </div>
                    <input type="hidden" name="custom_numbers" id="customNumbersInput">
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-lightbulb"></i> 
                        Enter numbers with country code (e.g., +255712345678)
                    </small>
                </div>

                <!-- Excel Upload Input (Hidden by default) -->
                <div class="form-section" id="excelUploadSection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-file-excel"></i> Upload Excel File
                    </label>
                    <input type="file" class="form-control-modern" name="excel_contacts" id="excelContactsInput" accept=".xls,.xlsx,.csv">
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i>
                        Upload an Excel file (.xls, .xlsx, .csv) with a column containing name (optional) as name, and phone number as phone (Mandatory).
                    </small>
                </div>

                <!-- Message Composer -->
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-pen"></i> Your Message
                    </label>
                    
                    <div class="message-composer" id="messageComposer">
                        <!-- Attachments Preview -->
                        <div class="message-attachments" id="attachmentsArea">
                            <div class="attachment-grid" id="attachmentGrid"></div>
                        </div>
                        
                        <!-- Hashtag Suggestions -->
                        <div class="hashtag-suggestions" id="hashtagSuggestions">
                            <div class="hashtag-item" data-hashtag="#name">
                                <div class="hashtag-name">#name</div>
                                <div class="hashtag-desc">Customer's full name</div>
                            </div>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="message-input-area">
                            <textarea 
                                class="message-input" 
                                placeholder="Type your message here... Use #name for hashtag customer name"
                                name="message" 
                                id="messageInput"
                                rows="1"
                                required
                            ></textarea>
                            
                            <div class="input-actions">
                                <!-- File Upload -->
                                <button type="button" class="action-btn" id="attachBtn" title="Attach files">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                
                                <!-- Camera -->
                                <button type="button" class="action-btn" id="cameraBtn" title="Take photo">
                                    <i class="fas fa-camera"></i>
                                </button>
                                
                                <!-- Audio -->
                                <button type="button" class="action-btn" id="audioBtn" title="Record audio">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                
                                <!-- Send Button -->
                                <button type="submit" class="action-btn send-btn" id="sendBtn" disabled title="Send message">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Character Counter -->
                        <div class="char-counter" id="charCounter">0/1000</div>
                    </div>
                    
                    <!-- Hidden File Inputs -->
                    <input type="file" class="file-input-hidden" id="fileInput" multiple accept="image/*,video/*,audio/*,.pdf,.doc,.docx" name="files">
                    <input type="file" class="file-input-hidden" id="cameraInput" accept="image/*" capture="camera">
                    <input type="file" class="file-input-hidden" id="audioInput" accept="audio/*">
                </div>

                <!-- Message Stats -->
                <div class="stats-bar">
                    <div class="message-stats">
                        <span id="wordCount">0 words</span>
                        <span id="smsCount">1 SMS</span>
                        <span id="recipientCount">0 recipients</span>
                    </div>
                    
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span>WhatsApp Connected</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Floating Send Button (Mobile) -->
<button class="floating-send d-md-none" id="floatingSendBtn" style="display: none;">
    <i class="fas fa-paper-plane"></i>
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const recipientCards = document.querySelectorAll('.recipient-card');
    const criteriaInput = document.getElementById('criteriaInput');
    const categorySection = document.getElementById('categorySection');
    const customNumbersSection = document.getElementById('customNumbersSection');
    const excelUploadSection = document.getElementById('excelUploadSection');
    const contactInput = document.getElementById('contactInput');
    const contactTags = document.getElementById('contactTags');
    const customNumbersInput = document.getElementById('customNumbersInput');
    const messageInput = document.getElementById('messageInput');
    const hashtagSuggestions = document.getElementById('hashtagSuggestions');
    const attachmentsArea = document.getElementById('attachmentsArea');
    const attachmentGrid = document.getElementById('attachmentGrid');
    const sendBtn = document.getElementById('sendBtn');
    const floatingSendBtn = document.getElementById('floatingSendBtn');
    const charCounter = document.getElementById('charCounter');
    const wordCount = document.getElementById('wordCount');
    const smsCount = document.getElementById('smsCount');
    const recipientCount = document.getElementById('recipientCount');

    // File inputs
    const fileInput = document.getElementById('fileInput');
    const cameraInput = document.getElementById('cameraInput');
    const audioInput = document.getElementById('audioInput');
    const excelContactsInput = document.getElementById('excelContactsInput');

    // State
    let selectedCriteria = '';
    let contactNumbers = [];
    let attachedFiles = [];
    let hashtagIndex = -1;
    let excelFileName = '';

    // Recipient Card Selection
    recipientCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove previous selection
            recipientCards.forEach(c => c.classList.remove('selected'));
            
            // Add selection to clicked card
            this.classList.add('selected');
            
            const value = this.dataset.value;
            selectedCriteria = value;
            criteriaInput.value = value;
            
            // Show/hide relevant sections
            categorySection.style.display = value === '2' ? 'block' : 'none';
            customNumbersSection.style.display = value === '6' ? 'block' : 'none';
            excelUploadSection.style.display = value === '7' ? 'block' : 'none';
            
            updateRecipientCount();
        });
    });

    // Custom Numbers Input
    contactInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
            e.preventDefault();
            addContactNumber();
        }
    });

    contactInput.addEventListener('blur', addContactNumber);

    function addContactNumber() {
        const value = contactInput.value.trim();
        if (value && !contactNumbers.includes(value)) {
            // Validate phone number format
            const cleanNumber = value.replace(/\D/g, '');
            if (cleanNumber.length >= 9) {
                contactNumbers.push(value);
                createContactTag(value);
                contactInput.value = '';
                updateCustomNumbersInput();
                updateRecipientCount();
            }
        }
    }

    function createContactTag(number) {
        const tag = document.createElement('div');
        tag.className = 'contact-tag';
        tag.innerHTML = `
            ${number}
            <span class="remove" onclick="removeContactNumber('${number}')">&times;</span>
        `;
        contactTags.insertBefore(tag, contactInput);
    }

    window.removeContactNumber = function(number) {
        contactNumbers = contactNumbers.filter(n => n !== number);
        updateCustomNumbersInput();
        updateRecipientCount();
        
        // Remove tag from DOM
        const tags = contactTags.querySelectorAll('.contact-tag');
        tags.forEach(tag => {
            if (tag.textContent.includes(number)) {
                tag.remove();
            }
        });
    };

    function updateCustomNumbersInput() {
        customNumbersInput.value = contactNumbers.join(',');
    }

    // Excel Upload Handler
    excelContactsInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            excelFileName = file.name;
            updateRecipientCount();
            
            // Show file selected feedback
            const fileLabel = this.parentElement.querySelector('.form-label');
            const originalText = fileLabel.innerHTML;
            fileLabel.innerHTML = `<i class="fas fa-file-excel text-success"></i> Selected: ${file.name}`;
            
            // Validate file type
            const validTypes = ['.xls', '.xlsx', '.csv'];
            const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
            
            if (!validTypes.includes(fileExtension)) {
                alert('Please select a valid Excel file (.xls, .xlsx, .csv)');
                this.value = '';
                excelFileName = '';
                fileLabel.innerHTML = originalText;
                updateRecipientCount();
            }
        } else {
            excelFileName = '';
            updateRecipientCount();
        }
    });

    // Message Input Auto-resize
    messageInput.addEventListener('input', function() {
        // Auto-resize
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        
        // Update counters
        updateCounters();
        
        // Toggle send button
        const hasContent = this.value.trim().length > 0;
        sendBtn.disabled = !hasContent || !selectedCriteria;
        floatingSendBtn.style.display = hasContent && window.innerWidth < 768 ? 'block' : 'none';
        
        // Handle hashtag suggestions
        handleHashtagSuggestions();
    });

    // Hashtag Suggestions
    function handleHashtagSuggestions() {
        const text = messageInput.value;
        const cursorPos = messageInput.selectionStart;
        const textBeforeCursor = text.substring(0, cursorPos);
        const hashtagMatch = textBeforeCursor.match(/#(\w*)$/);
        
        if (hashtagMatch) {
            const query = hashtagMatch[1].toLowerCase();
            const suggestions = Array.from(hashtagSuggestions.children).filter(item => {
                const hashtag = item.dataset.hashtag.toLowerCase();
                return hashtag.includes(query);
            });
            
            if (suggestions.length > 0) {
                hashtagSuggestions.style.display = 'block';
                suggestions.forEach((item, index) => {
                    item.classList.toggle('selected', index === hashtagIndex);
                });
            } else {
                hashtagSuggestions.style.display = 'none';
            }
        } else {
            hashtagSuggestions.style.display = 'none';
            hashtagIndex = -1;
        }
    }

    // Hashtag Selection
    messageInput.addEventListener('keydown', function(e) {
        if (hashtagSuggestions.style.display === 'block') {
            const suggestions = hashtagSuggestions.querySelectorAll('.hashtag-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                hashtagIndex = Math.min(hashtagIndex + 1, suggestions.length - 1);
                updateHashtagSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                hashtagIndex = Math.max(hashtagIndex - 1, 0);
                updateHashtagSelection();
            } else if (e.key === 'Enter' && hashtagIndex >= 0) {
                e.preventDefault();
                insertHashtag(suggestions[hashtagIndex].dataset.hashtag);
            } else if (e.key === 'Escape') {
                hashtagSuggestions.style.display = 'none';
                hashtagIndex = -1;
            }
        }
    });

    function updateHashtagSelection() {
        const suggestions = hashtagSuggestions.querySelectorAll('.hashtag-item');
        suggestions.forEach((item, index) => {
            item.classList.toggle('selected', index === hashtagIndex);
        });
    }

    // Click to insert hashtag
    hashtagSuggestions.addEventListener('click', function(e) {
        const item = e.target.closest('.hashtag-item');
        if (item) {
            insertHashtag(item.dataset.hashtag);
        }
    });

    function insertHashtag(hashtag) {
        const text = messageInput.value;
        const cursorPos = messageInput.selectionStart;
        const textBeforeCursor = text.substring(0, cursorPos);
        const textAfterCursor = text.substring(cursorPos);
        
        // Find the # position
        const hashPos = textBeforeCursor.lastIndexOf('#');
        const newText = textBeforeCursor.substring(0, hashPos) + hashtag + ' ' + textAfterCursor;
        
        messageInput.value = newText;
        messageInput.focus();
        
        // Set cursor position after hashtag
        const newCursorPos = hashPos + hashtag.length + 1;
        messageInput.setSelectionRange(newCursorPos, newCursorPos);
        
        hashtagSuggestions.style.display = 'none';
        hashtagIndex = -1;
        
        updateCounters();
    }

    // File Attachments
    document.getElementById('attachBtn').addEventListener('click', () => fileInput.click());
    document.getElementById('cameraBtn').addEventListener('click', () => cameraInput.click());
    document.getElementById('audioBtn').addEventListener('click', () => audioInput.click());

    [fileInput, cameraInput, audioInput].forEach(input => {
        input.addEventListener('change', function(e) {
            Array.from(e.target.files).forEach(file => {
                addAttachment(file);
            });
        });
    });

    function addAttachment(file) {
        if (attachedFiles.length >= 10) {
            alert('You can attach maximum 10 files');
            return;
        }

        attachedFiles.push(file);
        createAttachmentPreview(file);
        attachmentsArea.style.display = 'block';
    }

    function createAttachmentPreview(file) {
        const item = document.createElement('div');
        item.className = 'attachment-item';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.className = 'attachment-preview';
            img.src = URL.createObjectURL(file);
            item.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'attachment-icon';
            
            if (file.type.startsWith('video/')) {
                icon.innerHTML = '<i class="fas fa-video"></i>';
            } else if (file.type.startsWith('audio/')) {
                icon.innerHTML = '<i class="fas fa-music"></i>';
            } else if (file.type === 'application/pdf') {
                icon.innerHTML = '<i class="fas fa-file-pdf"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-file"></i>';
            }
            
            item.appendChild(icon);
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'attachment-remove';
        removeBtn.innerHTML = '&times;';
        removeBtn.onclick = () => removeAttachment(file, item);
        
        item.appendChild(removeBtn);
        attachmentGrid.appendChild(item);
    }

    function removeAttachment(file, element) {
        attachedFiles = attachedFiles.filter(f => f !== file);
        element.remove();
        
        if (attachedFiles.length === 0) {
            attachmentsArea.style.display = 'none';
        }
    }

    // Update Counters
    function updateCounters() {
        const text = messageInput.value;
        const chars = text.length;
        const words = text.trim() ? text.trim().split(/\s+/).length : 0;
        const sms = Math.ceil(chars / 160) || 1;
        
        charCounter.textContent = `${chars}/1000`;
        wordCount.textContent = `${words} words`;
        smsCount.textContent = `${sms} SMS`;
        
        // Change color based on character limit
        if (chars > 1000) {
            charCounter.style.color = '#dc2626';
        } else if (chars > 800) {
            charCounter.style.color = '#f59e0b';
        } else {
            charCounter.style.color = '#9ca3af';
        }
    }

    function updateRecipientCount() {
        let count = 0;
        
        if (selectedCriteria === '1') {
            count = {{ $guests ?? 0 }}; // Total contacts from backend
        } else if (selectedCriteria === '6') {
            count = contactNumbers.length;
        } else if (selectedCriteria === '2') {
            count = 'Selected category';
        } else if (selectedCriteria === '7') {
            count = excelFileName ? `Excel: ${excelFileName}` : 'Upload Excel file';
        }
        
        recipientCount.textContent = typeof count === 'number' ? `${count} recipients` : count;
    }

    // Form Submission
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        if (!selectedCriteria) {
            e.preventDefault();
            alert('Please select who you want to message');
            return;
        }
        
        if (!messageInput.value.trim()) {
            e.preventDefault();
            alert('Please enter a message');
            return;
        }
        
        // Show loading state
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;
    });

    // Floating send button
    floatingSendBtn.addEventListener('click', function() {
        document.getElementById('messageForm').submit();
    });

    // Initialize
    updateCounters();
    updateRecipientCount();
});
</script>

@endsection
