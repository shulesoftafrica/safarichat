@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    .compose-container {
        font-family: 'Inter', sans-serif;
        background: var(--gray-50);
        min-height: 100vh;
        padding: 20px;
    }
    
    .compose-header {
        background: var(--primary-color);
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
    
    .form-control-modern.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }
    
    .form-control-modern.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }
    
    .valid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #28a745;
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

    /* ========== AI-POWERED SECTION STYLING ========== */
    
    /* Light Mode - AI Section */
    .alert-inline.alert-info {
        background: rgba(59, 130, 246, 0.1) !important;
        border: 1px solid rgba(59, 130, 246, 0.2) !important;
    }
    
    .ai-section-title {
        color: #1f2937 !important;
        font-weight: 700 !important;
    }
    
    .ai-section-title .fa-sparkles {
        color: #eab308 !important;
    }
    
    .ai-section-text {
        color: #374151 !important;
    }
    
    .ai-feature-list {
        color: #6a1b9a !important;
    }
    
    .ai-feature-list li {
        color: #6a1b9a !important;
    }
    
    .ai-feature-list b {
        color: #4a148c !important;
    }
    
    .ai-check-icon {
        color: #9c27b0 !important;
    }
    
    .ai-info-alert {
        background: #e1f5fe !important;
        color: #01579b !important;
        border: 1px solid #81d4fa !important;
    }
    
    .ai-info-alert b {
        color: #01579b !important;
    }
    
    .ai-info-alert .fa-info-circle {
        color: #0277bd !important;
    }

    /* Dark Mode Styles */
    .dark-mode .compose-container {
        background: #111827 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .compose-header {
        background: var(--primary-color) !important;
    }

    .dark-mode .compose-main {
        background: #1f2937 !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
    }

    .dark-mode .compose-form {
        background: #1f2937 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .form-label {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }

    .dark-mode .form-control-modern {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }

    .dark-mode .form-control-modern:focus {
        background: #374151 !important;
        border-color: #25d366 !important;
        color: #f7fafc !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }

    .dark-mode .form-control-modern.is-invalid {
        background: #374151 !important;
        border-color: #f56565 !important;
        color: #f7fafc !important;
    }

    .dark-mode .form-control-modern.is-valid {
        background: #374151 !important;
        border-color: #48bb78 !important;
        color: #f7fafc !important;
    }

    .dark-mode .invalid-feedback {
        color: #fca5a5 !important;
        font-weight: 500 !important;
    }

    .dark-mode .valid-feedback {
        color: #86efac !important;
        font-weight: 500 !important;
    }

    .dark-mode .recipient-card {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .recipient-card:hover {
        background: rgba(37, 211, 102, 0.15) !important;
        border-color: #25d366 !important;
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.25) !important;
    }

    .dark-mode .recipient-card.selected {
        background: rgba(37, 211, 102, 0.15) !important;
        border-color: #25d366 !important;
        box-shadow: 0 8px 25px rgba(37, 211, 102, 0.25) !important;
    }

    .dark-mode .recipient-title {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }

    .dark-mode .recipient-desc {
        color: #cbd5e0 !important;
        font-weight: 400 !important;
    }
    
    .dark-mode .recipient-icon {
        opacity: 0.95 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .dark-mode .contact-tags {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }

    .dark-mode .contact-tags:focus-within {
        background: #374151 !important;
        border-color: #25d366 !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }

    .dark-mode .contact-input {
        background: none !important;
        color: #f7fafc !important;
    }

    .dark-mode .contact-input::placeholder {
        color: #9ca3af !important;
        opacity: 0.8 !important;
    }

    .dark-mode .message-composer {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }

    .dark-mode .message-composer:focus-within {
        border-color: #25d366 !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }

    .dark-mode .message-attachments {
        background: #374151 !important;
        border-bottom: 1px solid #4b5563 !important;
    }

    .dark-mode .attachment-item {
        background: #1f2937 !important;
        border: 1px solid #4b5563 !important;
    }

    .dark-mode .attachment-icon {
        color: #9ca3af !important;
    }

    .dark-mode .hashtag-suggestions {
        background: #374151 !important;
        border-color: #4b5563 !important;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5) !important;
    }

    .dark-mode .hashtag-item {
        border-bottom: 1px solid #4b5563 !important;
    }

    .dark-mode .hashtag-item:hover,
    .dark-mode .hashtag-item.selected {
        background: rgba(37, 211, 102, 0.15) !important;
    }

    .dark-mode .hashtag-name {
        color: #34d399 !important;
        font-weight: 600 !important;
    }

    .dark-mode .hashtag-desc {
        color: #cbd5e0 !important;
    }

    .dark-mode .message-input {
        background: none !important;
        color: #f7fafc !important;
    }

    .dark-mode .message-input::placeholder {
        color: #9ca3af !important;
        opacity: 0.8 !important;
    }

    .dark-mode .action-btn {
        color: #cbd5e0 !important;
    }

    .dark-mode .action-btn:hover {
        background: #4b5563 !important;
        color: #f7fafc !important;
    }

    .dark-mode .send-btn {
        background: #25d366 !important;
        color: white !important;
        font-weight: 600 !important;
    }

    .dark-mode .send-btn:disabled {
        background: #4b5563 !important;
        color: #9ca3af !important;
    }

    .dark-mode .char-counter {
        color: #cbd5e0 !important;
    }

    .dark-mode .stats-bar {
        background: #374151 !important;
        border-top: 1px solid #4b5563 !important;
        color: #cbd5e0 !important;
    }

    .dark-mode .status-dot {
        background: #34d399 !important;
    }

    .dark-mode .floating-send {
        background: #25d366 !important;
        box-shadow: 0 4px 20px rgba(37, 211, 102, 0.5) !important;
    }

    .dark-mode .modal-content {
        background: #1f2937 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .modal-header {
        background: #374151 !important;
        border-bottom: 1px solid #4b5563 !important;
    }

    .dark-mode .modal-footer {
        background: #374151 !important;
        border-top: 1px solid #4b5563 !important;
    }

    .dark-mode .modal-body {
        background: #1f2937 !important;
    }

    .dark-mode .alert {
        border-color: #4b5563 !important;
    }

    .dark-mode .alert-danger {
        background: rgba(239, 68, 68, 0.2) !important;
        border-color: #ef4444 !important;
        color: #fca5a5 !important;
    }

    .dark-mode .alert-success {
        background: rgba(52, 211, 153, 0.2) !important;
        border-color: #34d399 !important;
        color: #86efac !important;
    }

    .dark-mode .alert-warning {
        background: rgba(251, 191, 36, 0.2) !important;
        border-color: #fbbf24 !important;
        color: #fcd34d !important;
    }

    .dark-mode .alert-info {
        background: rgba(99, 179, 237, 0.2) !important;
        border-color: #63b3ed !important;
        color: #93c5fd !important;
    }

    .dark-mode .close {
        color: #f7fafc !important;
        opacity: 1 !important;
    }

    .dark-mode .close:hover {
        color: #ffffff !important;
    }

    .dark-mode .text-muted {
        color: #cbd5e0 !important;
    }

    .dark-mode small {
        color: #cbd5e0 !important;
    }
    
    /* Dark Mode - AI Section Overrides */
    /* Give the AI Personalization banner a solid, readable background in dark mode.
       The default rgba(59,130,246,0.1) is only 10% opacity — nearly invisible on dark.
       Use a deeper blue with enough opacity so text is actually readable. */
    .dark-mode .alert-inline.alert-info {
        background: rgba(30, 58, 95, 0.85) !important;
        border: 1px solid rgba(99, 179, 237, 0.45) !important;
    }
    
    .dark-mode .ai-section-title {
        color: #f7fafc !important;
    }
    
    .dark-mode .ai-section-title .fa-sparkles {
        color: #fbbf24 !important;
    }
    
    .dark-mode .ai-section-text {
        color: #cbd5e0 !important;
    }
    
    .dark-mode .ai-section-text b {
        color: #f7fafc !important;
    }
    
    .dark-mode .ai-feature-list {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .ai-feature-list li {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .ai-feature-list b {
        color: #f7fafc !important;
    }
    
    .dark-mode .ai-check-icon {
        color: #34d399 !important;
    }
    
    .dark-mode .ai-info-alert {
        background: rgba(52, 211, 153, 0.15) !important;
        border: 1px solid rgba(52, 211, 153, 0.3) !important;
        color: #86efac !important;
    }
    
    .dark-mode .ai-info-alert b {
        color: #a7f3d0 !important;
    }
    
    .dark-mode .ai-info-alert .fa-info-circle {
        color: #34d399 !important;
    }

    .dark-mode .btn-outline-warning {
        color: #fbbf24 !important;
        border-color: #fbbf24 !important;
    }

    .dark-mode .btn-outline-warning:hover {
        background-color: #fbbf24 !important;
        color: #111827 !important;
    }

    .dark-mode .btn-success {
        background-color: #34d399 !important;
        border-color: #34d399 !important;
        color: #111827 !important;
        font-weight: 600 !important;
    }

    .dark-mode .card {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }

    .dark-mode .card-body {
        background: #374151 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode .container-fluid {
        background: transparent !important;
    }

    .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode h6 {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }

    .dark-mode p, .dark-mode span, .dark-mode div {
        color: #e2e8f0 !important;
    }

    .dark-mode select {
        background-color: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }

    .dark-mode select:focus {
        background-color: #374151 !important;
        border-color: #25d366 !important;
        color: #f7fafc !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }

    .dark-mode select option {
        background-color: #374151 !important;
        color: #f7fafc !important;
    }

    .dark-mode input[type="file"] {
        background-color: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }

    .dark-mode textarea {
        background-color: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }

    .dark-mode textarea:focus {
        background-color: #374151 !important;
        border-color: #25d366 !important;
        color: #f7fafc !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }

    .dark-mode ul {
        color: #e2e8f0 !important;
    }

    .dark-mode li {
        color: #e2e8f0 !important;
    }

    .dark-mode a {
        color: #60a5fa !important;
    }

    .dark-mode a:hover {
        color: #93c5fd !important;
    }

    .dark-mode .fas, .dark-mode .fab {
        color: inherit !important;
    }

    .dark-mode #loading-overlay > div > div {
        background: #1f2937 !important;
        color: #e2e8f0 !important;
    }

    .dark-mode #loading-overlay h5 {
        color: #f7fafc !important;
    }

    /* ========== IMPROVED DARK MODE READABILITY ========== */
    
    /* AI-Powered Section - Dark Mode */
    .dark-mode .alert-inline.alert-info {
        background: rgba(30, 58, 95, 0.85) !important;
        border: 1px solid rgba(99, 179, 237, 0.45) !important;
    }
    
    .dark-mode .alert-inline .card-body {
        background: transparent !important;
    }

    /* Ensure direct text children of the AI banner (not caught by class rules)
       are readable in dark mode — override the inherited blue from .alert-info */
    .dark-mode .alert-inline.alert-info .card-body * {
        color: #e2e8f0;
    }
    
    /* AI Section Title */
    .dark-mode .ai-section-title {
        color: #f7fafc !important;
        font-weight: 700 !important;
    }
    
    .dark-mode .ai-section-title .fa-sparkles {
        color: #fbbf24 !important;
    }
    
    /* AI Section Text */
    .dark-mode .ai-section-text {
        color: #cbd5e0 !important;
    }
    
    .dark-mode .ai-section-text b {
        color: #f7fafc !important;
    }
    
    /* AI Feature Lists */
    .dark-mode .ai-feature-list {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .ai-feature-list li {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .ai-feature-list b {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }
    
    /* Check Icons - Green in Dark Mode */
    .dark-mode .ai-check-icon {
        color: #34d399 !important;
    }
    
    /* Info Alert Box in AI Section */
    .dark-mode .ai-info-alert {
        background: rgba(52, 211, 153, 0.15) !important;
        border: 1px solid rgba(52, 211, 153, 0.3) !important;
        color: #86efac !important;
    }
    
    .dark-mode .ai-info-alert b {
        color: #f7fafc !important;
    }
    
    .dark-mode .ai-info-alert .fa-info-circle {
        color: #34d399 !important;
    }
    
    /* Legacy support for old selectors */
    .dark-mode .alert-inline ul {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .alert-inline ul li {
        color: #e2e8f0 !important;
    }
    
    .dark-mode .alert-inline ul li b {
        color: #f7fafc !important;
    }
    
    .dark-mode .alert-inline .fa-check-circle {
        color: #34d399 !important;
    }
    
    .dark-mode .alert-inline .alert.alert-info {
        background: rgba(52, 211, 153, 0.15) !important;
        border: 1px solid rgba(52, 211, 153, 0.3) !important;
        color: #86efac !important;
    }
    
    .dark-mode .alert-inline .alert.alert-info b {
        color: #f7fafc !important;
    }
    
    .dark-mode .alert-inline .alert.alert-info .fa-info-circle {
        color: #34d399 !important;
    }
    
    /* Recipient Cards - Improve description visibility */
    .dark-mode .recipient-desc {
        color: #cbd5e0 !important;
        font-weight: 400 !important;
    }
    
    .dark-mode .recipient-title {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }
    
    /* Recipient icon backgrounds - per-card dark mode overrides.
       The inline styles use light pastels (e.g. #dcfce7) which are invisible
       when the icon glyph color is forced to near-white by .dark-mode div.
       These rules override both background AND color for each card. */
    .dark-mode [data-value="1"] .recipient-icon {
        background: #14532d !important;
        color: #4ade80 !important;
        border-color: #15803d !important;
    }
    .dark-mode [data-value="2"] .recipient-icon {
        background: #1e3a5f !important;
        color: #60a5fa !important;
        border-color: #2563eb !important;
    }
    .dark-mode [data-value="6"] .recipient-icon {
        background: #3b0764 !important;
        color: #c084fc !important;
        border-color: #7c3aed !important;
    }
    .dark-mode [data-value="7"] .recipient-icon {
        background: #451a03 !important;
        color: #fbbf24 !important;
        border-color: #b45309 !important;
    }
    /* Ensure glyph icons inside inherit the corrected per-card color */
    .dark-mode .recipient-icon i {
        color: inherit !important;
    }
    
    /* Text muted improvements */
    .dark-mode .text-muted,
    .dark-mode small.text-muted {
        color: #cbd5e0 !important;
    }
    
    /* Compose subtitle */
    .dark-mode .compose-subtitle {
        color: #e2e8f0 !important;
        opacity: 0.95 !important;
    }
    
    /* Form labels - ensure they're bright enough */
    .dark-mode .form-label {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }
    
    .dark-mode .form-label i {
        color: #34d399 !important;
        margin-right: 4px !important;
    }
    
    /* Ensure all FontAwesome icons are visible */
    .dark-mode .fas,
    .dark-mode .fa,
    .dark-mode .fab,
    .dark-mode .far {
        opacity: 1 !important;
    }
    
    /* Compose header icons */
    .dark-mode .compose-header .fas {
        color: #ffffff !important;
    }
    
    /* All heading improvements */
    .dark-mode h1, .dark-mode h2, .dark-mode h3, .dark-mode h4, .dark-mode h5, .dark-mode h6 {
        color: #f7fafc !important;
        font-weight: 600 !important;
    }
    
    /* Placeholder text visibility */
    .dark-mode .form-control-modern::placeholder,
    .dark-mode input::placeholder,
    .dark-mode textarea::placeholder {
        color: #a0aec0 !important;
        opacity: 0.8 !important;
    }
    
    /* Better borders for inputs */
    .dark-mode .form-control-modern {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }
    
    .dark-mode .form-control-modern:focus {
        background: #374151 !important;
        border-color: #25d366 !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }
    
    /* Contact tags improvements */
    .dark-mode .contact-tags {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }
    
    .dark-mode .contact-tags:focus-within {
        background: #374151 !important;
        border-color: #25d366 !important;
        box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2) !important;
    }
    
    .dark-mode .contact-input {
        color: #f7fafc !important;
    }
    
    /* Message composer improvements */
    .dark-mode .message-composer {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }
    
    .dark-mode .message-input {
        color: #f7fafc !important;
    }
    
    /* Select dropdown improvements */
    .dark-mode select.form-control-modern {
        background: #374151 !important;
        border-color: #4b5563 !important;
        color: #f7fafc !important;
    }
    
    .dark-mode select.form-control-modern option {
        background: #374151 !important;
        color: #f7fafc !important;
    }
    
    /* Card backgrounds */
    .dark-mode .card {
        background: #374151 !important;
        border-color: #4b5563 !important;
    }
    
    .dark-mode .card-body {
        background: #374151 !important;
        color: #e2e8f0 !important;
    }
    
    /* Ensure Font Awesome icons don't inherit problematic colors */
    .dark-mode .fas:not(.ai-check-icon):not(.fa-sparkles):not(.fa-info-circle) {
        color: inherit !important;
    }
    
    /* Robot icon in AI section */
    .dark-mode .fa-robot {
        color: #ffffff !important;
    }
    
    /* Form section spacing and visibility */
    .dark-mode .form-section {
        margin-bottom: 32px !important;
    }
    
    /* Improve button visibility */
    .dark-mode .btn {
        font-weight: 500 !important;
    }
    
    .dark-mode .btn-primary {
        background-color: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
    }
    
    .dark-mode .btn-secondary {
        background-color: #6b7280 !important;
        border-color: #6b7280 !important;
        color: #ffffff !important;
    }
</style>

<div class="compose-container">
    <div class="container-fluid">
        <div class="compose-header">
            <h1 class="compose-title">
                <i class="fas fa-bullhorn"></i>
                {{ __('campaigns.create.title') }}
            </h1>
            <p class="compose-subtitle">{{ __('campaigns.create.subtitle') }}</p>
                        <div class="alert-inline alert-info my-4" style="border-radius:18px;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start" style="gap:18px;">
                                    <div style="border-radius:12px;width:56px;height:56px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(59, 130, 246, 0.25);flex-shrink:0;background:#3b82f6;">
                                        <i class="fas fa-robot" style="color:white;font-size:1.75rem;"></i>
                                    </div>
                                    <div style="flex:1;">
                                        <div class="ai-section-title" style="font-size:1.25rem;font-weight:700;margin-bottom:10px;">
                                            <i class="fas fa-sparkles"></i> {{ __('campaigns.create.ai_personalization') }}
                                        </div>
                                        <p class="ai-section-text" style="font-size:0.95rem;line-height:1.7;margin-bottom:15px;">
                                            {{ __('campaigns.create.ai_benefits_title') }}
                                        </p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="ai-feature-list" style="font-size:0.9rem;line-height:1.8;margin:0;padding-left:20px;">
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.analyzes_history') }}</li>
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.detects_language') }}</li>
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.personalizes_message') }}</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="ai-feature-list" style="font-size:0.9rem;line-height:1.8;margin:0;padding-left:20px;">
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.schedules_times') }}</li>
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.filters_sentiment') }}</li>
                                                    <li><i class="fas fa-check-circle ai-check-icon"></i> {{ __('campaigns.create.ai_benefits.increases_engagement') }}</li>
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="alert alert-info ai-info-alert mt-3 mb-0" style="border-radius:10px;font-size:0.9rem;">
                                            {{ __('campaigns.create.ai_how_it_works') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
        </div>
        
        <div class="compose-main">
            <!-- Error Display Section -->
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show m-4" role="alert">
                    <h6><i class="fas fa-exclamation-triangle"></i> {{ __('campaigns.validation.fix_errors') }}</h6>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-4" role="alert">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show m-4" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            <form class="compose-form" method="POST" action="{{ route('campaigns.store') }}" enctype="multipart/form-data" id="messageForm">
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
                                <div class="recipient-icon" style="background: #dcfce7; color: #16a34a;border:2px solid #86efac;">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <h3 class="recipient-title">{{ __('campaigns.create.all_contacts') }}</h3>
                                <p class="recipient-desc">{{ __('campaigns.create.all_contacts_desc') }}</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="recipient-card" data-value="2">
                                <div class="recipient-icon" style="background: #dbeafe; color: #2563eb;border:2px solid #60a5fa;">
                                    <i class="fas fa-filter"></i>
                                </div>
                                <h3 class="recipient-title">{{ __('campaigns.create.lead_status') }}</h3>
                                <p class="recipient-desc">{{ __('campaigns.create.lead_status_desc') }}</p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="recipient-card" data-value="6">
                                <div class="recipient-icon" style="background: #ede9fe; color: #7c3aed;border:2px solid #a78bfa;">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <h3 class="recipient-title">{{ __('campaigns.create.custom_numbers') }}</h3>
                                <p class="recipient-desc">{{ __('campaigns.create.custom_numbers_desc') }}</p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="recipient-card" data-value="7">
                                <div class="recipient-icon" style="background: #fef9c3; color: #ca8a04;border:2px solid #fbbf24;">
                                    <i class="fas fa-file-excel"></i>
                                </div>
                                <h3 class="recipient-title">{{ __('campaigns.create.upload_excel') }}</h3>
                                <p class="recipient-desc">{{ __('campaigns.create.upload_excel_desc') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="criteria" id="criteriaInput" required>
                    <div id="criteria-validation-feedback" class="invalid-feedback" style="display: none;"></div>
                </div>

                <!-- Lead Status Selection (Hidden by default) -->
                <div class="form-section" id="categorySection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> {{ __('campaigns.create.lead_status') }}
                    </label>
                    <select class="form-control-modern @error('lead_status') is-invalid @enderror" name="lead_status" id="categorySelect">
                        <option value="">{{ __('campaigns.create.lead_status_placeholder') }}</option>
                        @if(isset($lead_statuses))
                            @foreach ($lead_statuses as $status_value => $status_label)
                                <option value="{{ $status_value }}" {{ old('lead_status') == $status_value ? 'selected' : '' }}>{{ $status_label }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('lead_status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="category-validation-feedback" class="invalid-feedback" style="display: none;"></div>
                </div>

                <!-- Custom Numbers Input (Hidden by default) -->
                <div class="form-section" id="customNumbersSection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-phone"></i> {{ __('campaigns.create.enter_phone_numbers') }}
                    </label>
                    <div class="contact-tags @error('custom_numbers') is-invalid @enderror" id="contactTags">
                        <input type="text" class="contact-input" placeholder="{{ __('campaigns.create.contact_input_placeholder') }}" id="contactInput">
                    </div>
                    <input type="hidden" name="custom_numbers" id="customNumbersInput" value="{{ old('custom_numbers') }}">
                    @error('custom_numbers')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="custom-numbers-validation-feedback" class="invalid-feedback" style="display: none;"></div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-lightbulb"></i> 
                        {{ __('campaigns.create.phone_help_text') }}
                    </small>
                </div>

                <!-- Excel Upload Input (Hidden by default) -->
                <div class="form-section" id="excelUploadSection" style="display: none;">
                    <label class="form-label">
                        <i class="fas fa-file-excel"></i> {{ __('campaigns.create.upload_excel') }}
                    </label>
                    <input type="file" class="form-control-modern @error('excel_contacts') is-invalid @enderror" name="excel_contacts" id="excelContactsInput" accept=".xls,.xlsx,.csv">
                    @error('excel_contacts')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div id="excel-validation-feedback" class="invalid-feedback" style="display: none;"></div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i>
                        {{ __('campaigns.create.excel_help_text') }}
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
                                <div class="hashtag-desc">{{ __('campaigns.create.hashtag_name_desc') }}</div>
                            </div>
                        </div>
                        
                        <!-- Message Input -->
                        <div class="message-input-area">
                            <textarea 
                                class="message-input @error('message') is-invalid @enderror" 
                                placeholder="{{ __('campaigns.create.message_placeholder') }}"
                                name="message" 
                                id="messageInput"
                                rows="1"
                                required
                            >{{ old('message') }}</textarea>
                            
                            @error('message')
                                <div class="invalid-feedback position-absolute" style="bottom: -20px; left: 16px;">{{ $message }}</div>
                            @enderror
                            <div id="message-validation-feedback" class="invalid-feedback position-absolute" style="bottom: -20px; left: 16px; display: none;"></div>
                            
                            <div class="input-actions">
                                <!-- File Upload -->
                                <button type="button" class="action-btn" id="attachBtn" title="{{ __('campaigns.create.attach_files') }}">
                                    <i class="fas fa-paperclip"></i>
                                </button>
                                
                                <!-- Camera -->
                                <button type="button" class="action-btn" id="cameraBtn" title="{{ __('campaigns.create.take_photo') }}">
                                    <i class="fas fa-camera"></i>
                                </button>
                                
                                <!-- Emoji Picker -->
                                <button type="button" class="action-btn" id="emojiBtn" title="{{ __('campaigns.create.add_emoji') }}">
                                    <i class="fas fa-smile"></i>
                                </button>
                                
                                <!-- Audio -->
                                <button type="button" class="action-btn" id="audioBtn" title="{{ __('campaigns.create.record_audio') }}">
                                    <i class="fas fa-microphone"></i>
                                </button>
                                
                                <!-- Send Button -->
                                <button type="submit" class="action-btn send-btn" id="sendBtn" disabled title="{{ __('campaigns.create.send_message') }}">
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
                        <span id="wordCount">0 {{ __('campaigns.create.word_count') }}</span>
                        <span id="smsCount">1 {{ __('campaigns.create.sms_count') }}</span>
                        <span id="recipientCount">0 {{ __('campaigns.create.recipient_count') }}</span>
                    </div>
                    
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span>{{ __('campaigns.create.whatsapp_connected') }}</span>
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
            
            // Clear validation errors when switching criteria
            clearValidationErrors();
            
            updateRecipientCount();
        });
    });

    // Category Selection Validation
    document.getElementById('categorySelect').addEventListener('change', function() {
        if (this.value) {
            this.classList.remove('is-invalid');
            document.getElementById('category-validation-feedback').style.display = 'none';
        }
        updateRecipientCount();
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
            if (cleanNumber.length >= 9 && cleanNumber.length <= 15) {
                contactNumbers.push(value);
                createContactTag(value);
                contactInput.value = '';
                updateCustomNumbersInput();
                updateRecipientCount();
                
                // Clear validation errors
                document.getElementById('contactTags').classList.remove('is-invalid');
                document.getElementById('custom-numbers-validation-feedback').style.display = 'none';
            } else {
                // Show validation error
                showValidationError('custom-numbers', 'Invalid phone number format. Use country code (e.g., +255712345678)');
                document.getElementById('contactTags').classList.add('is-invalid');
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
            // Clear previous validation state
            this.classList.remove('is-invalid');
            document.getElementById('excel-validation-feedback').style.display = 'none';
            
            // Validate file type
            const validTypes = ['.xls', '.xlsx', '.csv'];
            const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
            
            if (!validTypes.includes(fileExtension)) {
                showValidationError('excel', 'Invalid file type. Only Excel files (.xls, .xlsx, .csv) are allowed');
                this.classList.add('is-invalid');
                this.value = '';
                excelFileName = '';
                updateRecipientCount();
                return;
            }
            
            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                showValidationError('excel', 'File size too large. Maximum 5MB allowed');
                this.classList.add('is-invalid');
                this.value = '';
                excelFileName = '';
                updateRecipientCount();
                return;
            }
            
            excelFileName = file.name;
            updateRecipientCount();
            
            // Show file selected feedback
            const fileLabel = this.parentElement.querySelector('.form-label');
            const originalText = fileLabel.innerHTML;
            fileLabel.innerHTML = `<i class="fas fa-file-excel text-success"></i> Selected: ${file.name}`;
        } else {
            excelFileName = '';
            updateRecipientCount();
        }
    });

    // Message Input Auto-resize
    messageInput.addEventListener('input', function() {
        // Clear validation errors
        this.classList.remove('is-invalid');
        document.getElementById('message-validation-feedback').style.display = 'none';
        
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
        // Clear previous validation states
        clearValidationErrors();
        
        let isValid = true;
        let errors = [];

        // Validate recipient selection
        if (!selectedCriteria) {
            isValid = false;
            errors.push('Please select who you want to message');
            showValidationError('criteria', 'Please select a recipient type');
        }

        // Validate lead status selection if needed
        if (selectedCriteria === '2') {
            const categorySelect = document.getElementById('categorySelect');
            if (!categorySelect.value) {
                isValid = false;
                errors.push('Please select a lead status');
                showValidationError('category', 'Please select a lead status');
                categorySelect.classList.add('is-invalid');
            }
        }

        // Validate custom numbers if needed
        if (selectedCriteria === '6') {
            if (contactNumbers.length === 0) {
                isValid = false;
                errors.push('Please enter at least one phone number');
                showValidationError('custom-numbers', 'Please enter at least one valid phone number');
                document.getElementById('contactTags').classList.add('is-invalid');
            } else {
                // Validate phone number format
                const invalidNumbers = contactNumbers.filter(number => {
                    const cleanNumber = number.replace(/\D/g, '');
                    return cleanNumber.length < 9 || cleanNumber.length > 15;
                });
                
                if (invalidNumbers.length > 0) {
                    isValid = false;
                    errors.push(`Invalid phone numbers: ${invalidNumbers.join(', ')}`);
                    showValidationError('custom-numbers', `Invalid phone numbers: ${invalidNumbers.join(', ')}`);
                    document.getElementById('contactTags').classList.add('is-invalid');
                }
            }
        }

        // Validate Excel file if needed
        if (selectedCriteria === '7') {
            const excelFile = document.getElementById('excelContactsInput').files[0];
            if (!excelFile) {
                isValid = false;
                errors.push('Please upload an Excel file');
                showValidationError('excel', 'Please upload an Excel file');
                document.getElementById('excelContactsInput').classList.add('is-invalid');
            } else {
                // Validate file type
                const validExtensions = ['.xls', '.xlsx', '.csv'];
                const fileExtension = excelFile.name.toLowerCase().substring(excelFile.name.lastIndexOf('.'));
                if (!validExtensions.includes(fileExtension)) {
                    isValid = false;
                    errors.push('Invalid file type. Only Excel files (.xls, .xlsx, .csv) are allowed');
                    showValidationError('excel', 'Invalid file type. Only Excel files (.xls, .xlsx, .csv) are allowed');
                    document.getElementById('excelContactsInput').classList.add('is-invalid');
                }
                
                // Validate file size (2MB limit to prevent 413 error)
                if (excelFile.size > 2 * 1024 * 1024) {
                    isValid = false;
                    const sizeMB = (excelFile.size / (1024 * 1024)).toFixed(2);
                    errors.push(`Excel file is too large (${sizeMB}MB). Maximum 2MB allowed`);
                    showValidationError('excel', `File size ${sizeMB}MB exceeds 2MB limit`);
                    document.getElementById('excelContactsInput').classList.add('is-invalid');
                }
            }
        }

        // Validate message content
        const messageText = messageInput.value.trim();
        if (!messageText && attachedFiles.length === 0) {
            isValid = false;
            errors.push('Please enter a message or attach files');
            showValidationError('message', 'Please enter a message or attach files');
            messageInput.classList.add('is-invalid');
        }

        // Validate message length
        if (messageText.length > 1000) {
            isValid = false;
            errors.push('Message is too long. Maximum 1000 characters allowed');
            showValidationError('message', 'Message is too long. Maximum 1000 characters allowed');
            messageInput.classList.add('is-invalid');
        }

        // Validate attached files
        if (attachedFiles.length > 0) {
            // Calculate total size of all attached files
            const totalFileSize = attachedFiles.reduce((sum, file) => sum + file.size, 0);
            const maxTotalSize = 8 * 1024 * 1024; // 8MB total limit to avoid 413 error
            
            if (totalFileSize > maxTotalSize) {
                isValid = false;
                const totalSizeMB = (totalFileSize / (1024 * 1024)).toFixed(2);
                errors.push(`Total file size (${totalSizeMB}MB) exceeds 8MB limit. Please reduce the number or size of files.`);
                showValidationError('message', `Total file size too large (${totalSizeMB}MB). Maximum 8MB total allowed.`);
            }
            
            const oversizedFiles = attachedFiles.filter(file => file.size > 5 * 1024 * 1024); // Reduced to 5MB per file
            if (oversizedFiles.length > 0) {
                isValid = false;
                const fileNames = oversizedFiles.map(f => f.name).join(', ');
                errors.push(`Files too large: ${fileNames}. Maximum 5MB per file.`);
                showValidationError('message', 'Some files are too large. Maximum 5MB per file.');
            }

            if (attachedFiles.length > 5) { // Reduced to 5 files max
                isValid = false;
                errors.push(`Too many files attached (${attachedFiles.length}). Maximum 5 files allowed.`);
                showValidationError('message', 'Too many files attached. Maximum 5 files allowed.');
            }
        }

        if (!isValid) {
            e.preventDefault();
            
            // Show error summary
            showErrorSummary(errors);
            
            // Scroll to first error
            const firstError = document.querySelector('.is-invalid');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
            
            return false;
        }

        // Always use AJAX submission for consistent error handling
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Add attached files to FormData if any
        if (attachedFiles.length > 0) {
            attachedFiles.forEach((file, index) => {
                formData.append('files[]', file);
            });
        }
        
        // Debug: Log form data
        console.log('Submitting campaign with criteria:', selectedCriteria);
        console.log('Form action:', this.action);
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
        }
        
        // Show loading state
        sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        sendBtn.disabled = true;
        floatingSendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        floatingSendBtn.disabled = true;
        
        // Add loading overlay
        showLoadingOverlay();
        
        // Submit with fetch
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            // Check if response is ok
            if (!response.ok) {
                return response.json().then(data => {
                    console.error('Error response data:', data);
                    throw { status: response.status, data: data };
                }).catch(err => {
                    console.error('Failed to parse error response:', err);
                    if (err.data) throw err;
                    throw { status: response.status, data: { message: 'Server error occurred' } };
                });
            }
            return response.json().then(data => {
                console.log('Success response data:', data);
                return data;
            });
        })
        .then(data => {
            // Remove loading overlay
            document.getElementById('loading-overlay')?.remove();
            
            if (data.success) {
                // Show success message
                alert(data.message || 'Campaign created successfully!');
                
                // Redirect to campaigns list
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '{{ route("campaigns.index") }}';
                }
            } else {
                // Handle validation errors
                if (data.errors) {
                    let errorMessages = [];
                    for (let field in data.errors) {
                        errorMessages = errorMessages.concat(data.errors[field]);
                    }
                    showErrorSummary(errorMessages);
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
                
                // Reset loading state
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                sendBtn.disabled = false;
                floatingSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
                floatingSendBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Remove loading overlay
            document.getElementById('loading-overlay')?.remove();
            
            // Show detailed error if available
            let errorMessage = 'An error occurred while sending the message. Please try again.';
            if (error.data && error.data.message) {
                errorMessage = error.data.message;
            } else if (error.data && error.data.errors) {
                let errorMessages = [];
                for (let field in error.data.errors) {
                    errorMessages = errorMessages.concat(error.data.errors[field]);
                }
                showErrorSummary(errorMessages);
                errorMessage = 'Please fix the errors and try again.';
            }
            
            alert(errorMessage);
            
            // Reset loading state
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            sendBtn.disabled = false;
            floatingSendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
            floatingSendBtn.disabled = false;
        });
        
        return false;
    });

    function clearValidationErrors() {
        // Remove validation classes
        const invalidElements = document.querySelectorAll('.is-invalid');
        invalidElements.forEach(element => {
            element.classList.remove('is-invalid');
        });

        // Hide validation feedback
        const feedbackElements = document.querySelectorAll('[id$="-validation-feedback"]');
        feedbackElements.forEach(element => {
            element.style.display = 'none';
        });

        // Hide error summary
        const errorSummary = document.getElementById('error-summary');
        if (errorSummary) {
            errorSummary.remove();
        }
    }

    function showValidationError(fieldType, message) {
        const feedbackElement = document.getElementById(`${fieldType}-validation-feedback`);
        if (feedbackElement) {
            feedbackElement.textContent = message;
            feedbackElement.style.display = 'block';
        }
    }

    function showErrorSummary(errors) {
        // Remove existing error summary
        const existingErrorSummary = document.getElementById('error-summary');
        if (existingErrorSummary) {
            existingErrorSummary.remove();
        }

        // Create error summary
        const errorSummary = document.createElement('div');
        errorSummary.id = 'error-summary';
        errorSummary.className = 'alert alert-danger alert-dismissible fade show';
        errorSummary.innerHTML = `
            <h6><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</h6>
            <ul class="mb-0">
                ${errors.map(error => `<li>${error}</li>`).join('')}
            </ul>
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span aria-hidden="true">&times;</span>
            </button>
        `;

        // Insert error summary at the top of the form
        const composeForm = document.querySelector('.compose-form');
        composeForm.insertBefore(errorSummary, composeForm.firstChild);
    }

    function showLoadingOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'loading-overlay';
        overlay.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">
                <div style="background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #25d366; margin-bottom: 15px;"></i>
                    <h5>Sending Message...</h5>
                    <p class="text-muted mb-0">Please wait while we process your request</p>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

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
