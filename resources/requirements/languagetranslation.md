Act as a Senior Laravel Developer. I need to refactor this application to support multiple languages (i18n). Currently, the UI text is hard-coded into the HTML/Blade files.

Task:

Extract Strings: Scan the provided file and identify all user-facing text (titles, labels, button text, and placeholder messages).

Create/update Language File: Generate a PHP array for an English language file (en/messages.php). Use logical, nested keys (e.g., 'dashboard.welcome_header' => 'Hello! Ready to connect...').

Replace in HTML: Replace the hard-coded text in the Blade file with the Laravel translation helper: {{ __('messages.key_name') }}.

Refinement Rules:

Maintain the existing HTML structure exactly.

For dynamic text (e.g., 'You have 21 contacts'), use placeholders in the language file like 'contact_count' => 'You have :count contacts' and pass the variable in the Blade file.

Ensure all attributes like alt tags and placeholders are also localized.

Please start by extracting the text from the current file and showing me the proposed messages.php structure