@extends('layouts.app')
@section('content')
<div class="container my-5">
    <h2 class="mb-4 text-center">SafariChat Terms and Conditions</h2>
    <p class="text-center text-muted mb-5">
        SafariChat is a service provided by <strong>Safari Innovations Limited</strong> in partnership with the Shulesoft Limited team. By using our services, you agree to the following terms and conditions.
    </p>

    <!-- 1. WhatsApp Terms and Conditions -->
    <div class="mb-5">
        <h4>1. WhatsApp Terms and Conditions</h4>
        <p>
            SafariChat provides WhatsApp messaging services through third-party APIs. By using our WhatsApp services, you agree to comply with all applicable WhatsApp policies and the following terms:
        </p>
        <ul>
            <li>
                <strong>Permitted Use:</strong> You may only use the WhatsApp messaging API for lawful, non-abusive, and non-spam communications. All messages must comply with WhatsApp’s Acceptable Use Policy and local regulations.
            </li>
            <li>
                <strong>Message Delivery:</strong> SafariChat does not guarantee the delivery of messages, as delivery depends on the recipient’s device, network, and WhatsApp’s own infrastructure.
            </li>
            <li>
                <strong>Content Restrictions:</strong> You must not send unsolicited, fraudulent, or illegal content. SafariChat reserves the right to suspend or terminate accounts found in violation.
            </li>
            <li>
                <strong>API Usage:</strong> Usage of the WhatsApp API is subject to fair usage limits and technical restrictions as described in the <a href="#" target="_blank" rel="noopener">Whatsapp documentation</a>.
            </li>
            <li>
                <strong>Indemnification:</strong> You agree to indemnify and hold harmless Safari Innovations Limited, Shulesoft Limited, and their partners from any claims arising from misuse of the WhatsApp service.
            </li>
            <li>
                <strong>Service Changes:</strong> SafariChat may modify, suspend, or discontinue WhatsApp services at any time without prior notice.
            </li>
        </ul>
    </div>

    <!-- 2. BulkSMS Terms and Conditions -->
    <div class="mb-5">
        <h4>2. BulkSMS Terms and Conditions</h4>
        <p>
            SafariChat acts solely as a reseller of BulkSMS services. By using our BulkSMS platform, you acknowledge and agree to the following:
        </p>
        <ul>
            <li>
                <strong>Third-Party Service:</strong> BulkSMS delivery and reliability are subject to the terms, conditions, and technical limitations of the upstream BulkSMS provider which are in fact, telecommunications companies.
            </li>
            <li>
                <strong>Reseller Disclaimer:</strong> Safari Innovations Limited and its partners are not liable for delays, failures, or losses resulting from the BulkSMS provider’s systems or networks.
            </li>
            <li>
                <strong>Permitted Use:</strong> You must not use the BulkSMS service for unsolicited marketing, spam, or any unlawful activity. Accounts found in violation may be suspended or terminated without notice.
            </li>
            <li>
                <strong>Refunds and Credits:</strong> SafariChat provide no refunds.
            </li>
            <li>
                <strong>Compliance:</strong> You are responsible for ensuring your use of BulkSMS complies with all applicable laws and regulations in your jurisdiction.
            </li>
        </ul>
    </div>

    <!-- 3. Data Protection and Privacy -->
    <div class="mb-5">
        <h4>3. Data Protection and Privacy</h4>
        <p>
            SafariChat is committed to protecting your data in accordance with international data protection standards, including but not limited to the General Data Protection Regulation (GDPR).
        </p>
        <ul>
            <li>
                <strong>Data Collection:</strong> We collect only the data necessary to provide our services and improve user experience.
            </li>
            <li>
                <strong>Data Usage:</strong> Your data will not be sold or shared with third parties except as required to deliver our services or as required by law.
            </li>
            <li>
                <strong>Security:</strong> We implement appropriate technical and organizational measures to safeguard your data against unauthorized access, loss, or misuse.
            </li>
            <li>
                <strong>Data Retention:</strong> Personal data is retained only as long as necessary for the purposes for which it was collected or as required by law.
            </li>
            <li>
                <strong>User Rights:</strong> You have the right to access, correct, or request deletion of your personal data. For any data protection inquiries, please contact our support team.
            </li>
            <li>
                <strong>Partnership:</strong> SafariChat operates under Safari Innovations Limited and works in partnership with the Shulesoft Limited team to ensure the highest standards of data protection and service delivery.
            </li>
        </ul>
    </div>

    <div class="text-muted text-center small">
        &copy; {{ date('Y') }} Safari Innovations Limited. All rights reserved.
    </div>
</div>
@endsection