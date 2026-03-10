Based on the comprehensive screenshots and page data provided, your application has a robust feature set—including Sales Campaigns , Product Management , and Advanced Sales Reports —but it suffers from "visual noise" caused by high-contrast banners and inconsistent component styles.

To transform this into a professional, enterprise-grade platform that any serious business would trust, use the following structured prompt.

---

## The Master Refinement Prompt

> **"Act as a Senior Full-Stack Product Designer. I have an existing AI Sales Agent platform called SafariChat. I need you to refine the entire UX/UI to achieve a professional, 'neutral-enterprise' aesthetic that prioritizes consistency and data clarity.**
> ### 1. Global Visual Language & Consistency
> 
> 
> * **Color Palette:** Standardize on a **high-trust neutral base**. Use a light-grey background (`#F9FAFB`) with pure white (`#FFFFFF`) cards.
> * **Primary Action:** Replace varying greens and purples with one single **Primary Brand Color** (e.g., a deep Indigo or Slate) for all main buttons like 'Create New Campaign' or 'Get Started'.
> 
> 
> * **Typography:** Use a clean sans-serif stack (e.g., **Inter** or **Geist**). Establish a strict scale: 14px for table data, 16px for labels, and 24px for page headers.
> * **Border Radius:** Standardize every card, input, and button to a **12px radius** (rounded-xl) for a modern, soft feel.
> 
> 
> ### 2. Layout & Header Refinement
> 
> 
> * **Banner Reduction:** Replace the large, saturated green 'Hello!' banner and red 'Credit' alerts  with thin, sophisticated **'inline-alerts'** or a clean **'Metric Ribbon'**.
> * **The Sidebar:** Clean up the 'CATEGORY' menu. Ensure all icons have the same stroke weight (1.5px) and increase vertical spacing between items for better scannability.
> 
> 
> ### 3. Component Standardization
> 
> 
> * **Tables (Customers/Products):** Refine the 'List of Customers' and 'Product Management'  tables. Use a **zebra-stripe** background, remove heavy borders, and use **subtle status pills** (e.g., light green background with dark green text for 'Active' ).
> * **Modals:** Redesign the 'Upload Guest Details' and 'Send Message'  modals. They should use a **backdrop-blur (6px)** and have clearly defined primary (solid) and secondary (ghost/outline) buttons.
> * **Bento Metrics:** Align the dashboard cards (Active Status, Credits, Contacts)  into a perfectly symmetrical **Bento Grid** with consistent 24px internal padding.
> 
> 
> ### 4. UX Friction Points
> 
> 
> * **Action Overload:** In the Customer table, group the multiple colorful action buttons into a single **'Actions' dropdown menu** to reduce visual clutter.
> * **Empty States:** For pages like 'No Campaigns Yet', create a centered, illustrated empty state with a single, clear 'Create Campaign' call-to-action.
> 
> 
> **Please provide the updated CSS/Tailwind configuration and a template for a standardized Dashboard page that follows these rules."**

---

### Why this works for a "Serious" Platform:

* 
**Reduced Cognitive Load:** By moving away from large blocks of bright color (green/red/orange), you allow the user to focus on the **data** (736,849 Credits ) rather than the interface.


* **Trust through Neutrality:** Professional users trust interfaces that look stable. A neutral-grey and white "SaaS" look is the current industry standard for reliability.
* **Visual Hierarchy:** Using one primary color for "The Next Step" makes the app feel intuitive, guiding the user naturally through the sales flow.
