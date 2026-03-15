# SafariChat Component Library

**Version:** 1.0.0  
**Date:** March 6, 2026  
**Status:** Production Ready

---

## 📋 Table of Contents

1. [Introduction](#introduction)
2. [Buttons](#buttons)
3. [Cards](#cards)
4. [Alerts & Notifications](#alerts--notifications)
5. [Badges & Pills](#badges--pills)
6. [Tables](#tables)
7. [Forms](#forms)
8. [Modals & Dialogs](#modals--dialogs)
9. [Navigation](#navigation)
10. [Empty States](#empty-states)
11. [Loading States](#loading-states)
12. [Statistics & Metrics](#statistics--metrics)

---

## 🎯 Introduction

This component library documents all standardized UI components used across SafariChat. All components follow the design system tokens defined in `resources/css/design-system.css` and maintain consistent styling, behavior, and accessibility standards.

### Design Principles

✅ **Consistent:** All instances of a component look and behave the same  
✅ **Accessible:** WCAG 2.1 AA compliant with proper ARIA labels  
✅ **Responsive:** Works seamlessly across all device sizes  
✅ **Themeable:** Supports dark mode via design system variables  
✅ **Reusable:** Copy-paste ready code snippets  

---

## 🔘 Buttons

### Primary Button

**Use for:** Main call-to-action (CTA), primary user actions

```html
<button style="
    background: var(--primary-brand);
    color: white;
    padding: var(--space-3) var(--space-6);
    border: none;
    border-radius: var(--radius-base);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: background var(--transition-base);
"
onmouseover="this.style.background='var(--primary-hover)'"
onmouseout="this.style.background='var(--primary-brand)'">
    Save Changes
</button>
```

**Alternative with class:**
```html
<button class="btn btn-primary">Save Changes</button>
```

---

### Secondary Button

**Use for:** Secondary actions, less important CTAs

```html
<button style="
    background: transparent;
    color: var(--primary-brand);
    border: 2px solid var(--primary-brand);
    padding: var(--space-3) var(--space-6);
    border-radius: var(--radius-base);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: all var(--transition-base);
"
onmouseover="this.style.background='var(--primary-light)'"
onmouseout="this.style.background='transparent'">
    Cancel
</button>
```

**Alternative with class:**
```html
<button class="btn btn-outline-primary">Cancel</button>
```

---

###Danger Button

**Use for:** Destructive actions (delete, remove, cancel subscriptions)

```html
<button style="
    background: var(--error-bg);
    color: var(--error-text);
    border: 1px solid var(--error-border);
    padding: var(--space-3) var(--space-6);
    border-radius: var(--radius-base);
    font-size: var(--text-base);
    font-weight: var(--font-semibold);
    cursor: pointer;
    transition: all var(--transition-base);
"
onmouseover="this.style.background='var(--error-hover)'"
onmouseout="this.style.background='var(--error-bg)'">
    Delete Campaign
</button>
```

---

### Icon Button

**Use for:** Actions with icons, compact interfaces

```html
<button style="
    background: transparent;
    color: var(--gray-600);
    border: 1px solid var(--gray-200);
    padding: var(--space-2);
    border-radius: var(--radius-base);
    font-size: var(--text-lg);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    transition: all var(--transition-base);
"
onmouseover="this.style.background='var(--gray-50)'"
onmouseout="this.style.background='transparent'">
    <i class="ti-pencil"></i>
</button>
```

---

### Button Sizes

**Large Button:**
```html
<button class="btn btn-primary btn-lg" style="padding: var(--space-4) var(--space-8); font-size: var(--text-lg);">
    Large Action
</button>
```

**Small Button:**
```html
<button class="btn btn-primary btn-sm" style="padding: var(--space-2) var(--space-4); font-size: var(--text-sm);">
    Small Action
</button>
```

---

## 🎴 Cards

### Standard Card

**Use for:** General content containers

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    box-shadow: var(--shadow-base);
">
    <h3 style="
        color: var(--gray-900);
        font-size: var(--text-xl);
        font-weight: var(--font-semibold);
        margin-bottom: var(--space-2);
    ">
        Card Title
    </h3>
    <p style="
        color: var(--gray-600);
        font-size: var(--text-base);
        line-height: var(--leading-normal);
        margin: 0;
    ">
        Card body content goes here with descriptive text.
    </p>
</div>
```

---

### Stat Card (Dashboard Metrics)

**Use for:** Displaying key metrics, statistics

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    box-shadow: var(--shadow-sm);
">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <p style="color: var(--gray-500); font-size: var(--text-sm); margin: 0;">
                Total Campaigns
            </p>
            <h2 style="color: var(--gray-900); font-size: var(--text-3xl); font-weight: var(--font-bold); margin: var(--space-2) 0 0;">
                247
            </h2>
        </div>
        <div style="
            background: var(--success-bg);
            color: var(--success-text);
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-base);
            font-size: var(--text-sm);
            font-weight: var(--font-medium);
        ">
            +12%
        </div>
    </div>
    <p style="color: var(--gray-500); font-size: var(--text-xs); margin: var(--space-3) 0 0;">
        Updated 5 minutes ago
    </p>
</div>
```

---

### Hover Card

**Use for:** Interactive cards with hover effects

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: all var(--transition-base);
"
onmouseover="this.style.boxShadow='var(--shadow-lg)'; this.style.transform='translateY(-2px)';"
onmouseout="this.style.boxShadow='var(--shadow-sm)'; this.style.transform='translateY(0)';">
    <h4 style="color: var(--gray-900); font-weight: var(--font-semibold);">
        Clickable Card
    </h4>
    <p style="color: var(--gray-600); margin: var(--space-2) 0 0;">
        Hover to see elevation effect
    </p>
</div>
```

---

## 🔔 Alerts & Notifications

### Success Alert

**Use for:** Successful operations, confirmations

```html
<div style="
    background: var(--success-bg);
    border: 1px solid var(--success-border);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
">
    <div style="
        background: var(--success-text);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    ">
        ✓
    </div>
    <div>
        <h4 style="color: var(--success-text); font-weight: var(--font-semibold); margin:0 0 var(--space-1) 0; font-size: var(--text-base);">
            Success!
        </h4>
        <p style="color: var(--success-text); margin: 0; font-size: var(--text-sm);">
            Your campaign has been sent successfully.
        </p>
    </div>
</div>
```

---

### Warning Alert

**Use for:** Warnings, important notices

```html
<div style="
    background: var(--warning-bg);
    border: 1px solid var(--warning-border);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
">
    <div style="
        background: var(--warning-text);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    ">
        ⚠
    </div>
    <div>
        <h4 style="color: var(--warning-text); font-weight: var(--font-semibold); margin: 0 0 var(--space-1) 0; font-size: var(--text-base);">
            Low Balance
        </h4>
        <p style="color: var(--warning-text); margin: 0; font-size: var(--text-sm);">
            You have only 100 credits remaining. Add more credits to continue.
        </p>
    </div>
</div>
```

---

### Error Alert

**Use for:** Errors, failures, critical issues

```html
<div style="
    background: var(--error-bg);
    border: 1px solid var(--error-border);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
">
    <div style="
        background: var(--error-text);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    ">
        ✕
    </div>
    <div>
        <h4 style="color: var(--error-text); font-weight: var(--font-semibold); margin: 0 0 var(--space-1) 0; font-size: var(--text-base);">
            Error
        </h4>
        <p style="color: var(--error-text); margin: 0; font-size: var(--text-sm);">
            Failed to send message. Please check your connection and try again.
        </p>
    </div>
</div>
```

---

### Info Alert

**Use for:** Informational messages, tips

```html
<div style="
    background: var(--info-bg);
    border: 1px solid var(--info-border);
    border-radius: var(--radius-lg);
    padding: var(--space-4);
    display: flex;
    align-items: center;
    gap: var(--space-3);
">
    <div style="
        background: var(--info-text);
        color: white;
        width: 24px;
        height: 24px;
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    ">
        ℹ
    </div>
    <div>
        <h4 style="color: var(--info-text); font-weight: var(--font-semibold); margin: 0 0 var(--space-1) 0; font-size: var(--text-base);">
            New Features Available
        </h4>
        <p style="color: var(--info-text); margin: 0; font-size: var(--text-sm);">
            Check out our new AI agent builder in the services section.
        </p>
    </div>
</div>
```

---

### Inline Alert (Compact)

**Use for:** Form validation, contextual messages

```html
<div style="
    background: var(--error-bg);
    border-left: 4px solid var(--error-text);
    padding: var(--space-3);
    border-radius: var(--radius-base);
">
    <p style="color: var(--error-text); font-size: var(--text-sm); margin: 0;">
        Please enter a valid email address.
    </p>
</div>
```

---

## 🏷️ Badges & Pills

### Status Badge (Active)

```html
<span style="
    background: var(--success-bg);
    color: var(--success-text);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
    display: inline-flex;
    align-items: center;
    gap: var(--space-1);
">
    <span style="
        width: 6px;
        height: 6px;
        background: var(--success-text);
        border-radius: var(--radius-full);
    "></span>
    Active
</span>
```

---

### Status Badge (Paused)

```html
<span style="
    background: var(--gray-100);
    color: var(--gray-600);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
">
    Paused
</span>
```

---

### Status Badge (Scheduled)

```html
<span style="
    background: var(--info-bg);
    color: var(--info-text);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
">
    Scheduled
</span>
```

---

### Status Badge (Error)

```html
<span style="
    background: var(--error-bg);
    color: var(--error-text);
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-medium);
">
    Failed
</span>
```

---

### Count Badge

**Use for:** Notification counts, unread indicators

```html
<span style="
    background: var(--primary-brand);
    color: white;
    padding: var(--space-0-5) var(--space-2);
    border-radius: var(--radius-full);
    font-size: var(--text-xs);
    font-weight: var(--font-bold);
    min-width: 20px;
    text-align: center;
">
    23
</span>
```

---

## 📊 Tables

### Zebra-Stripe Table

**Use for:** Data tables, listings

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    overflow: hidden;
">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
                <th style="
                    padding: var(--space-4);
                    text-align: left;
                    color: var(--gray-700);
                    font-size: var(--text-sm);
                    font-weight: var(--font-semibold);
                    text-transform: uppercase;
                    letter-spacing: var(--tracking-wide);
                ">
                    Campaign Name
                </th>
                <th style="
                    padding: var(--space-4);
                    text-align: left;
                    color: var(--gray-700);
                    font-size: var(--text-sm);
                    font-weight: var(--font-semibold);
                    text-transform: uppercase;
                    letter-spacing: var(--tracking-wide);
                ">
                    Status
                </th>
                <th style="
                    padding: var(--space-4);
                    text-align: right;
                    color: var(--gray-700);
                    font-size: var(--text-sm);
                    font-weight: var(--font-semibold);
                    text-transform: uppercase;
                    letter-spacing: var(--tracking-wide);
                ">
                    Sent
                </th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid var(--gray-200);">
                <td style="padding: var(--space-4); color: var(--gray-900); font-size: var(--text-sm); font-weight: var(--font-medium);">
                    Welcome Campaign
                </td>
                <td style="padding: var(--space-4);">
                    <span style="background: var(--success-bg); color: var(--success-text); padding: var(--space-1) var(--space-2); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: var(--font-medium);">
                        Active
                    </span>
                </td>
                <td style="padding: var(--space-4); text-align: right; color: var(--gray-600); font-size: var(--text-sm);">
                    1,247
                </td>
            </tr>
            <tr style="background: var(--gray-50); border-bottom: 1px solid var(--gray-200);">
                <td style="padding: var(--space-4); color: var(--gray-900); font-size: var(--text-sm); font-weight: var(--font-medium);">
                    Follow-up Campaign
                </td>
                <td style="padding: var(--space-4);">
                    <span style="background: var(--gray-100); color: var(--gray-600); padding: var(--space-1) var(--space-2); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: var(--font-medium);">
                        Paused
                    </span>
                </td>
                <td style="padding: var(--space-4); text-align: right; color: var(--gray-600); font-size: var(--text-sm);">
                    842
                </td>
            </tr>
            <tr style="border-bottom: 1px solid var(--gray-200);">
                <td style="padding: var(--space-4); color: var(--gray-900); font-size: var(--text-sm); font-weight: var(--font-medium);">
                    Newsletter March
                </td>
                <td style="padding: var(--space-4);">
                    <span style="background: var(--info-bg); color: var(--info-text); padding: var(--space-1) var(--space-2); border-radius: var(--radius-full); font-size: var(--text-xs); font-weight: var(--font-medium);">
                        Scheduled
                    </span>
                </td>
                <td style="padding: var(--space-4); text-align: right; color: var(--gray-600); font-size: var(--text-sm);">
                    0
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

---

### Compact Table (No Borders)

**Use for:** Simple data displays

```html
<table style="width: 100%; border-collapse: collapse;">
    <tbody>
        <tr>
            <td style="padding: var(--space-2) 0; color: var(--gray-600); font-size: var(--text-sm);">
                Total Sent:
            </td>
            <td style="padding: var(--space-2) 0; text-align: right; color: var(--gray-900); font-size: var(--text-sm); font-weight: var(--font-semibold);">
                12,459
            </td>
        </tr>
        <tr>
            <td style="padding: var(--space-2) 0; color: var(--gray-600); font-size: var(--text-sm);">
                Delivered:
            </td>
            <td style="padding: var(--space-2) 0; text-align: right; color: var(--success-text); font-size: var(--text-sm); font-weight: var(--font-semibold);">
                12,203
            </td>
        </tr>
        <tr>
            <td style="padding: var(--space-2) 0; color: var(--gray-600); font-size: var(--text-sm);">
                Failed:
            </td>
            <td style="padding: var(--space-2) 0; text-align: right; color: var(--error-text); font-size: var(--text-sm); font-weight: var(--font-semibold);">
                256
            </td>
        </tr>
    </tbody>
</table>
```

---

## 📝 Forms

### Text Input

```html
<div style="margin-bottom: var(--space-4);">
    <label style="
        display: block;
        color: var(--gray-700);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        margin-bottom: var(--space-2);
    ">
        Campaign Name
    </label>
    <input 
        type="text" 
        placeholder="Enter campaign name"
        style="
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-base);
            font-size: var(--text-base);
            color: var(--gray-900);
            background: var(--white);
            transition: border-color var(--transition-base);
        "
        onfocus="this.style.borderColor='var(--primary-brand)'; this.style.outline='none';"
        onblur="this.style.borderColor='var(--gray-200)';"
    >
</div>
```

---

### Text Area

```html
<div style="margin-bottom: var(--space-4);">
    <label style="
        display: block;
        color: var(--gray-700);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        margin-bottom: var(--space-2);
    ">
        Message
    </label>
    <textarea 
        rows="4"
        placeholder="Enter your message"
        style="
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-base);
            font-size: var(--text-base);
            color: var(--gray-900);
            background: var(--white);
            font-family: var(--font-primary);
            resize: vertical;
            transition: border-color var(--transition-base);
        "
        onfocus="this.style.borderColor='var(--primary-brand)'; this.style.outline='none';"
        onblur="this.style.borderColor='var(--gray-200)';"
    ></textarea>
</div>
```

---

### Select Dropdown

```html
<div style="margin-bottom: var(--space-4);">
    <label style="
        display: block;
        color: var(--gray-700);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        margin-bottom: var(--space-2);
    ">
        Campaign Type
    </label>
    <select 
        style="
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-base);
            font-size: var(--text-base);
            color: var(--gray-900);
            background: var(--white);
            cursor: pointer;
        "
        onfocus="this.style.borderColor='var(--primary-brand)'; this.style.outline='none';"
        onblur="this.style.borderColor='var(--gray-200)';"
    >
        <option value="">Select type</option>
        <option value="welcome">Welcome Campaign</option>
        <option value="followup">Follow-up Campaign</option>
        <option value="newsletter">Newsletter</option>
    </select>
</div>
```

---

### Checkbox

```html
<label style="
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: var(--space-2);
">
    <input 
        type="checkbox" 
        style="
            width: 18px;
            height: 18px;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-sm);
            cursor: pointer;
        "
    >
    <span style="color: var(--gray-700); font-size: var(--text-sm);">
        I agree to the terms and conditions
    </span>
</label>
```

---

### Input with Error

```html
<div style="margin-bottom: var(--space-4);">
    <label style="
        display: block;
        color: var(--gray-700);
        font-size: var(--text-sm);
        font-weight: var(--font-medium);
        margin-bottom: var(--space-2);
    ">
        Email Address
    </label>
    <input 
        type="email" 
        value="invalid-email"
        style="
            width: 100%;
            padding: var(--space-3);
            border: 2px solid var(--error-border);
            border-radius: var(--radius-base);
            font-size: var(--text-base);
            color: var(--error-text);
            background: var(--error-bg);
        "
    >
    <small style="
        display: block;
        color: var(--error-text);
        font-size: var(--text-xs);
        margin-top: var(--space-1);
    ">
        Please enter a valid email address
    </small>
</div>
```

---

## 🪟 Modals & Dialogs

### Standard Modal

```html
<!-- Modal Backdrop -->
<div style="
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: var(--z-modal-backdrop);
">
    <!-- Modal Container -->
    <div style="
        background: var(--white);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-2xl);
        max-width: 500px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        z-index: var(--z-modal);
    ">
        <!-- Modal Header -->
        <div style="
            padding: var(--space-6);
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        ">
            <h3 style="
                color: var(--gray-900);
                font-size: var(--text-xl);
                font-weight: var(--font-semibold);
                margin: 0;
            ">
                Confirm Action
            </h3>
            <button style="
                background: transparent;
                border: none;
                color: var(--gray-400);
                font-size: var(--text-2xl);
                cursor: pointer;
                padding: 0;
                line-height: 1;
            ">
                ×
            </button>
        </div>

        <!-- Modal Body -->
        <div style="padding: var(--space-6);">
            <p style="
                color: var(--gray-600);
                font-size: var(--text-base);
                line-height: var(--leading-relaxed);
                margin: 0;
            ">
                Are you sure you want to delete this campaign? This action cannot be undone.
            </p>
        </div>

        <!-- Modal Footer -->
        <div style="
            padding: var(--space-6);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: var(--space-3);
        ">
            <button style="
                background: transparent;
                color: var(--gray-600);
                border: 1px solid var(--gray-300);
                padding: var(--space-3) var(--space-6);
                border-radius: var(--radius-base);
                font-weight: var(--font-semibold);
                cursor: pointer;
            ">
                Cancel
            </button>
            <button style="
                background: var(--error-bg);
                color: var(--error-text);
                border: 1px solid var(--error-border);
                padding: var(--space-3) var(--space-6);
                border-radius: var(--radius-base);
                font-weight: var(--font-semibold);
                cursor: pointer;
            ">
                Delete
            </button>
        </div>
    </div>
</div>
```

---

## 🧭 Navigation

### Breadcrumbs

```html
<nav style="
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-4) 0;
">
    <a href="#" style="
        color: var(--gray-500);
        font-size: var(--text-sm);
        text-decoration: none;
        transition: color var(--transition-base);
    "
    onmouseover="this.style.color='var(--primary-brand)'"
    onmouseout="this.style.color='var(--gray-500)'">
        Dashboard
    </a>
    <span style="color: var(--gray-400);">/</span>
    <a href="#" style="
        color: var(--gray-500);
        font-size: var(--text-sm);
        text-decoration: none;
        transition: color var(--transition-base);
    "
    onmouseover="this.style.color='var(--primary-brand)'"
    onmouseout="this.style.color='var(--gray-500)'">
        Campaigns
    </a>
    <span style="color: var(--gray-400);">/</span>
    <span style="color: var(--gray-900); font-size: var(--text-sm); font-weight: var(--font-medium);">
        Welcome Campaign
    </span>
</nav>
```

---

## 🗂️ Empty States

### Standard Empty State

```html
<div style="
    background: var(--white);
    border: 2px dashed var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-12) var(--space-6);
    text-align: center;
">
    <div style="
        width: 80px;
        height: 80px;
        background: var(--gray-100);
        border-radius: var(--radius-full);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-4);
    ">
        <i class="ti-folder" style="font-size: 36px; color: var(--gray-400);"></i>
    </div>
    <h3 style="
        color: var(--gray-900);
        font-size: var(--text-xl);
        font-weight: var(--font-semibold);
        margin: 0 0 var(--space-2);
    ">
        No campaigns yet
    </h3>
    <p style="
        color: var(--gray-600);
        font-size: var(--text-base);
        margin: 0 0 var(--space-6);
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
    ">
        Get started by creating your first campaign to engage with your customers.
    </p>
    <button style="
        background: var(--primary-brand);
        color: white;
        padding: var(--space-3) var(--space-6);
        border: none;
        border-radius: var(--radius-base);
        font-weight: var(--font-semibold);
        cursor: pointer;
    ">
        Create Campaign
    </button>
</div>
```

---

## ⏳ Loading States

### Spinner

```html
<div style="
    width: 40px;
    height: 40px;
    border: 4px solid var(--gray-200);
    border-top-color: var(--primary-brand);
    border-radius: var(--radius-full);
    animation: spin 1s linear infinite;
">
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
```

---

### Loading Skeleton

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
">
    <!-- Title skeleton -->
    <div style="
        height: 24px;
        background: var(--gray-100);
        border-radius: var(--radius-base);
        margin-bottom: var(--space-4);
        width: 60%;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    "></div>
    
    <!-- Text skeleton -->
    <div style="
        height: 16px;
        background: var(--gray-100);
        border-radius: var(--radius-base);
        margin-bottom: var(--space-2);
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    "></div>
    <div style="
        height: 16px;
        background: var(--gray-100);
        border-radius: var(--radius-base);
        width: 80%;
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    "></div>
</div>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
```

---

## 📈 Statistics & Metrics

### Progress Bar

```html
<div style="margin-bottom: var(--space-4);">
    <div style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: var(--space-2);
    ">
        <span style="color: var(--gray-700); font-size: var(--text-sm); font-weight: var(--font-medium);">
            Campaign Progress
        </span>
        <span style="color: var(--gray-600); font-size: var(--text-sm);">
            75%
        </span>
    </div>
    <div style="
        height: 8px;
        background: var(--gray-200);
        border-radius: var(--radius-full);
        overflow: hidden;
    ">
        <div style="
            height: 100%;
            width: 75%;
            background: var(--primary-brand);
            border-radius: var(--radius-full);
            transition: width var(--transition-slow);
        "></div>
    </div>
</div>
```

---

### Metric Card with Trend

```html
<div style="
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    padding: var(--space-6);
">
    <p style="
        color: var(--gray-500);
        font-size: var(--text-sm);
        margin: 0 0 var(--space-2);
    ">
        Messages Sent Today
    </p>
    <div style="display: flex; align-items: baseline; gap: var(--space-3); margin-bottom: var(--space-2);">
        <h2 style="
            color: var(--gray-900);
            font-size: var(--text-4xl);
            font-weight: var(--font-bold);
            margin: 0;
            line-height: 1;
        ">
            1,247
        </h2>
        <span style="
            background: var(--success-bg);
            color: var(--success-text);
            padding: var(--space-1) var(--space-2);
            border-radius: var(--radius-base);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
        ">
            ↑ 12.5%
        </span>
    </div>
    <p style="
        color: var(--gray-500);
        font-size: var(--text-xs);
        margin: 0;
    ">
        vs. yesterday (1,108)
    </p>
</div>
```

---

## 📌 Usage Guidelines

### Component Selection

- **Buttons:** Use `btn-primary` for main actions, `btn-outline-primary` for secondary
- **Cards:** Use `--radius-lg` (12px) for consistency
- **Alerts:** Choose semantic colors (success/warning/error/info) appropriately
- **Badges:** Use soft backgrounds with dark text for better readability
- **Tables:** Apply zebra-striping for tables with 5+ rows
- **Forms:** Always include labels and focus states
- **Modals:** Center on screen, use backdrop, include close button
- **Empty States:** Provide clear CTAs to guide users

### Accessibility Checklist

✅ All interactive elements have `:focus` states  
✅ Color contrast meets WCAG 2.1 AA (4.5:1 minimum)  
✅ Forms have associated `<label>` elements  
✅ Buttons use semantic HTML (`<button>` not `<div>`)  
✅ Modals trap focus and can be closed with Escape key  
✅ Status indicators don't rely on color alone (use text/icons)  

---

**Last Updated:** March 6, 2026  
**Version:** 1.0.0  
**Status:** ✅ Production Ready
