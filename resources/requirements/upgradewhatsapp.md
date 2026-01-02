### Pricing Controls Requirement

Implement a reusable modal component, preferably placed in `resources/views/layouts/app.blade.php` at the footer. This modal should display dynamically based on the user's current subscription package.

**Behavior:**
- When a user attempts to access a feature not included in their current package, display the modal.
- The modal must clearly show:
    - The user's current subscription package.
    - Options to upgrade to a higher package.
    - Option to purchase additional credits.
- The modal should allow the user to proceed with an upgrade and make a payment using the provided billing solution.
- After the user confirms the upgrade, refetch the subscription status from the billing API to verify the new package.

**Goal:**  
Ensure the modal is clear, comprehensive, and supports all upgrade and payment flows.