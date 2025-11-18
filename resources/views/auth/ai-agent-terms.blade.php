@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-file-contract me-2"></i>
                        AI Sales Agent Terms and Conditions
                    </h3>
                </div>
                <div class="card-body">
                    <div class="terms-content">
                        <h4>1. Service Description</h4>
                        <p>The AI Sales Agent service provides automated WhatsApp-based sales assistance powered by artificial intelligence. This service is designed to help businesses engage with customers, provide product information, and facilitate sales transactions.</p>

                        <h4>2. User Responsibilities</h4>
                        <ul>
                            <li>Provide accurate product information and pricing</li>
                            <li>Ensure fallback contact information is current and monitored</li>
                            <li>Respond promptly to escalated customer inquiries</li>
                            <li>Comply with WhatsApp Business policies and local regulations</li>
                            <li>Monitor AI agent performance and make necessary adjustments</li>
                        </ul>

                        <h4>3. AI Agent Capabilities and Limitations</h4>
                        <p><strong>The AI Agent Can:</strong></p>
                        <ul>
                            <li>Respond to customer inquiries about products and services</li>
                            <li>Provide pricing information and negotiate within set limits</li>
                            <li>Process orders and payment information</li>
                            <li>Schedule follow-ups and escalate complex issues</li>
                            <li>Operate in multiple languages as configured</li>
                        </ul>

                        <p><strong>The AI Agent Cannot:</strong></p>
                        <ul>
                            <li>Handle complex technical support issues beyond its training</li>
                            <li>Make decisions outside configured parameters</li>
                            <li>Guarantee successful sales conversions</li>
                            <li>Replace human judgment in complex situations</li>
                        </ul>

                        <h4>4. Data Privacy and Security</h4>
                        <ul>
                            <li>Customer conversation data is stored securely and encrypted</li>
                            <li>Data is used only for improving service quality and generating reports</li>
                            <li>Customer personal information is protected according to applicable privacy laws</li>
                            <li>Users can request data deletion at any time</li>
                        </ul>

                        <h4>5. Service Availability</h4>
                        <ul>
                            <li>We strive for 99.9% uptime but cannot guarantee uninterrupted service</li>
                            <li>Scheduled maintenance will be announced in advance</li>
                            <li>Users are responsible for having fallback procedures during outages</li>
                        </ul>

                        <h4>6. Pricing and Billing</h4>
                        <ul>
                            <li>Service pricing is based on message volume and features used</li>
                            <li>Billing is monthly in advance unless otherwise agreed</li>
                            <li>Changes to pricing will be communicated 30 days in advance</li>
                            <li>Refunds are available according to our refund policy</li>
                        </ul>

                        <h4>7. Acceptable Use Policy</h4>
                        <p>Users agree not to use the AI Sales Agent for:</p>
                        <ul>
                            <li>Sending spam or unsolicited messages</li>
                            <li>Promoting illegal products or services</li>
                            <li>Harassing or abusing customers</li>
                            <li>Violating WhatsApp Terms of Service</li>
                            <li>Attempting to reverse engineer or hack the system</li>
                        </ul>

                        <h4>8. Liability and Disclaimers</h4>
                        <ul>
                            <li>The service is provided "as is" without warranties</li>
                            <li>We are not liable for lost sales or business due to service issues</li>
                            <li>Users are responsible for compliance with local laws and regulations</li>
                            <li>Our liability is limited to the service fees paid in the preceding month</li>
                        </ul>

                        <h4>9. Termination</h4>
                        <ul>
                            <li>Either party may terminate the service with 30 days notice</li>
                            <li>We may suspend service immediately for violations of these terms</li>
                            <li>Upon termination, data will be retained for 90 days then deleted</li>
                            <li>Users can export their data before termination</li>
                        </ul>

                        <h4>10. Support and Escalation</h4>
                        <ul>
                            <li>Technical support is available during business hours</li>
                            <li>Emergency support is available for critical issues</li>
                            <li>Users should provide fallback contact methods for escalations</li>
                            <li>Response times depend on support tier and issue complexity</li>
                        </ul>

                        <h4>11. Modifications to Terms</h4>
                        <p>We reserve the right to modify these terms with 30 days advance notice. Continued use of the service after modifications constitutes acceptance of the new terms.</p>

                        <h4>12. Governing Law</h4>
                        <p>These terms are governed by the laws of Kenya. Any disputes will be resolved through arbitration in Nairobi, Kenya.</p>

                        <h4>13. Contact Information</h4>
                        <p>For questions about these terms or the service:</p>
                        <ul>
                            <li>Email: support@safarichat.africa</li>
                            <li>Phone: +255 655 406 004</li>
                            <li>Address: Dar es Salaam, Tanzania</li>
                        </ul>

                        <div class="terms-footer mt-4 p-3 bg-light rounded">
                            <p class="mb-0"><strong>Last Updated:</strong> {{ date('F j, Y') }}</p>
                            <p class="mb-0"><small class="text-muted">By using our AI Sales Agent service, you acknowledge that you have read, understood, and agree to be bound by these terms and conditions.</small></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Terms
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.terms-content h4 {
    color: #2c3e50;
    margin-top: 2rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #3498db;
}

.terms-content h4:first-child {
    margin-top: 0;
}

.terms-content ul {
    margin-bottom: 1.5rem;
}

.terms-content li {
    margin-bottom: 0.5rem;
}

.terms-footer {
    border-left: 4px solid #3498db;
}

@media print {
    .card-header, .card-footer, .btn {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>
@endsection
