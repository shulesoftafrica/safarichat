
# WhatsApp "Support is Here" Widget — Implementation Plan

## Objective
Develop a single, self-contained HTML/CSS/JS snippet for a WhatsApp support widget. The code must be easily embeddable in any website's footer (Laravel Blade, PHP, or static HTML) and require no external dependencies.

## Implementation Steps

### 1. Configuration Block
- At the top of the script, define two variables:
	- `WHATSAPP_NUMBER`: The WhatsApp phone number (string, e.g., "255XXXXXXXXX").
	- `WELCOME_MESSAGE`: The default message to prefill in WhatsApp chat.
- Clearly comment this section for easy editing by non-technical users.

### 2. HTML Structure
- Dynamically inject a single `<div>` with a unique ID (e.g., `shule-whatsapp-widget`) into the page body.
- The widget contains:
	- WhatsApp SVG icon (inline, or via CDN FontAwesome if allowed)
	- Text: "Support is here"
	- A small green animated "pulse" dot for live/active status

### 3. CSS Styling
- All styles are scoped under `#shule-whatsapp-widget` to avoid conflicts.
- Button is pill-shaped, white background, rounded corners, drop shadow, and fixed at bottom-right.
- On hover: slightly lift the button or darken the background for feedback.
- The pulse dot uses CSS animation for a live effect.

### 4. JavaScript Behavior
- On click: Open a new tab to `https://wa.me/[WHATSAPP_NUMBER]?text=[WELCOME_MESSAGE]` (URL-encoded).
- No external JS libraries are used; only vanilla JS.
- The script is self-initializing and does not pollute the global namespace.

### 5. Portability & Independence
- No jQuery, Bootstrap, or other dependencies.
- All code (HTML, CSS, JS) is bundled in one snippet.
- No global CSS classes or IDs except the widget's own unique ID.

### 6. Example Configuration Block
```js
// --- CONFIGURATION ---
const WHATSAPP_NUMBER = "255XXXXXXXXX";
const WELCOME_MESSAGE = "Hello ShuleSoft, I need help with...";
```

## Deliverable
- A single code block (HTML/CSS/JS) ready to copy-paste into any website.
- All requirements above must be met for easy integration, visual consistency, and robust operation.

---

## Implementation Code

### Complete WhatsApp Support Widget Snippet

Paste this code snippet at the bottom of your page, just before the closing `</body>` tag:

```html
<!-- WhatsApp Support Widget - Start -->
<script>
(function() {
    'use strict';
    
    // ========================================
    // --- CONFIGURATION ---
    // Edit these values to customize the widget
    // ========================================
    const WHATSAPP_NUMBER = "255XXXXXXXXX"; // Replace with your WhatsApp number (country code + number)
    const WELCOME_MESSAGE = "Hello ShuleSoft, I need help with..."; // Default message to send
    
    // ========================================
    // DO NOT EDIT BELOW THIS LINE
    // ========================================
    
    // Create widget HTML structure
    const widgetHTML = `
        <div id="shule-whatsapp-widget">
            <div id="shule-wa-pulse-dot"></div>
            <svg id="shule-wa-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
            </svg>
            <span id="shule-wa-text">Support is here</span>
        </div>
    `;
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWidget);
    } else {
        initWidget();
    }
    
    function initWidget() {
        // Inject widget HTML
        const container = document.createElement('div');
        container.innerHTML = widgetHTML;
        document.body.appendChild(container.firstElementChild);
        
        // Add click event listener
        const widget = document.getElementById('shule-whatsapp-widget');
        if (widget) {
            widget.addEventListener('click', handleClick);
        }
    }
    
    function handleClick() {
        const encodedMessage = encodeURIComponent(WELCOME_MESSAGE);
        const whatsappURL = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodedMessage}`;
        window.open(whatsappURL, '_blank', 'noopener,noreferrer');
    }
    
})();
</script>

<style>
/* WhatsApp Support Widget Styles - All scoped to avoid conflicts */
#shule-whatsapp-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    padding: 12px 20px;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

#shule-whatsapp-widget:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    background: #f8f9fa;
}

#shule-wa-icon {
    width: 24px;
    height: 24px;
    color: #25D366;
    flex-shrink: 0;
}

#shule-wa-text {
    font-size: 15px;
    font-weight: 500;
    color: #333333;
    white-space: nowrap;
    user-select: none;
}

#shule-wa-pulse-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 10px;
    height: 10px;
    background: #25D366;
    border-radius: 50%;
    animation: shule-wa-pulse 2s infinite;
}

@keyframes shule-wa-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0.7);
    }
    50% {
        box-shadow: 0 0 0 6px rgba(37, 211, 102, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(37, 211, 102, 0);
    }
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    #shule-whatsapp-widget {
        bottom: 16px;
        right: 16px;
        padding: 10px 16px;
    }
    
    #shule-wa-text {
        font-size: 14px;
    }
    
    #shule-wa-icon {
        width: 20px;
        height: 20px;
    }
}

/* Optional: Hide text on very small screens, show icon only */
@media (max-width: 480px) {
    #shule-wa-text {
        display: none;
    }
    
    #shule-whatsapp-widget {
        padding: 12px;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        justify-content: center;
    }
    
    #shule-wa-pulse-dot {
        top: 4px;
        right: 4px;
    }
}
</style>
<!-- WhatsApp Support Widget - End -->
```

---

## Usage Instructions

### For Laravel Blade Templates

Add to your main layout file (e.g., `resources/views/layouts/app.blade.php`) just before `</body>`:

```blade
<!-- WhatsApp Support Widget -->
@include('partials.whatsapp-widget')
```

Then create `resources/views/partials/whatsapp-widget.blade.php` and paste the complete widget code above.

### For Standard HTML/PHP

Paste the entire code snippet directly before the closing `</body>` tag in your template/footer file.

### Configuration

1. Open the file where you pasted the widget code
2. Find the `CONFIGURATION` section at the top of the `<script>` tag
3. Update these two values:
   - `WHATSAPP_NUMBER`: Your WhatsApp number with country code (e.g., "254712345678")
   - `WELCOME_MESSAGE`: The default message visitors will send

### Testing

1. Save your changes and refresh the page
2. You should see the widget at the bottom-right corner
3. Click it to test the WhatsApp link opens correctly
4. Verify the message is pre-filled as expected

---

## Features Implemented

✅ **Self-contained** - Single code snippet, no external dependencies  
✅ **Vanilla JS & CSS** - No jQuery, Bootstrap, or other libraries  
✅ **Scoped Styles** - All CSS uses unique IDs to prevent conflicts  
✅ **Easy Configuration** - Two variables at the top of the script  
✅ **Pill-shaped Design** - White background, rounded corners, drop shadow  
✅ **WhatsApp Icon** - Inline SVG (no external requests)  
✅ **Live Pulse Indicator** - Animated green dot  
✅ **Hover Effects** - Lift and background change on hover  
✅ **Mobile Responsive** - Adapts to smaller screens  
✅ **Opens WhatsApp** - New tab with pre-filled message  
✅ **Security** - Uses `noopener,noreferrer` for external links