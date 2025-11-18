@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>
                        Privacy Policy for AI Sales Agent
                    </h3>
                </div>
                <div class="card-body">
                    <div class="terms-content">
                        <h4>1. Introduction</h4>
                        <p>
                            This Privacy Policy explains how we collect, use, store, and protect your information when you use our AI Sales Agent service. By using this service, you agree to the terms outlined below.
                        </p>

                        <h4>2. Information We Collect</h4>
                        <ul>
                            <li><strong>Customer Data:</strong> Messages, inquiries, and interactions with the AI agent via WhatsApp or other supported channels.</li>
                            <li><strong>Business Data:</strong> Product details, pricing, and business contact information provided for AI agent configuration.</li>
                            <li><strong>Usage Data:</strong> Logs of interactions, timestamps, and system performance metrics.</li>
                        </ul>

                        <h4>3. How We Use Your Information</h4>
                        <ul>
                            <li>To provide and improve the AI Sales Agent service</li>
                            <li>To generate reports and analytics for business insights</li>
                            <li>To respond to customer and business inquiries</li>
                            <li>To ensure compliance with legal and regulatory requirements</li>
                        </ul>

                        <h4>4. Data Sharing and Disclosure</h4>
                        <ul>
                            <li>We do <strong>not</strong> sell or rent your data to third parties</li>
                            <li>Data may be shared with trusted service providers for hosting, analytics, or support, under strict confidentiality agreements</li>
                            <li>We may disclose information if required by law or to protect our rights and users’ safety</li>
                        </ul>

                        <h4>5. Data Security</h4>
                        <ul>
                            <li>All customer and business data is encrypted in transit and at rest</li>
                            <li>Access to data is restricted to authorized personnel only</li>
                            <li>Regular security audits and vulnerability assessments are conducted</li>
                        </ul>

                        <h4>6. Data Retention</h4>
                        <ul>
                            <li>Customer conversation data is retained for as long as necessary to provide the service and for up to 90 days after service termination</li>
                            <li>Business configuration data is retained until you request deletion or terminate the service</li>
                        </ul>

                        <h4>7. User Rights</h4>
                        <ul>
                            <li>You may request access to, correction, or deletion of your data at any time</li>
                            <li>You may withdraw consent for data processing, which may affect service availability</li>
                            <li>Contact us at any time to exercise your privacy rights</li>
                        </ul>

                        <h4>8. Use of AI and Automated Decision-Making</h4>
                        <ul>
                            <li>The AI Sales Agent uses automated algorithms to respond to customer inquiries and process orders</li>
                            <li>All automated actions are based on business-configured rules and parameters</li>
                            <li>Escalation to human agents is available for complex or sensitive issues</li>
                        </ul>

                        <h4>9. International Data Transfers</h4>
                        <ul>
                            <li>Data may be processed and stored in countries outside your own, but always with adequate safeguards in place</li>
                        </ul>

                        <h4>10. Children’s Privacy</h4>
                        <ul>
                            <li>The AI Sales Agent service is not intended for use by children under 18</li>
                            <li>We do not knowingly collect personal information from children</li>
                        </ul>

                        <h4>11. Changes to This Policy</h4>
                        <p>
                            We may update this Privacy Policy from time to time. Changes will be communicated with at least 30 days’ notice. Continued use of the service after changes constitutes acceptance of the new policy.
                        </p>

                        <h4>12. Contact Information</h4>
                        <p>
                            For questions or concerns about this Privacy Policy or your data:
                        </p>
                        <ul>
                            <li>Email: support@safarichat.africa</li>
                            <li>Phone: +255 655 406 004</li>
                            <li>Address: Dar es Salaam, Tanzania</li>
                        </ul>

                        <div class="terms-footer mt-4 p-3 bg-light rounded">
                            <p class="mb-0"><strong>Last Updated:</strong> {{ date('F j, Y') }}</p>
                            <p class="mb-0"><small class="text-muted">By using our AI Sales Agent service, you acknowledge that you have read, understood, and agree to this Privacy Policy.</small></p>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Policy
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