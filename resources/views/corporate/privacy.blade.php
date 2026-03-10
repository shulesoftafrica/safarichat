@extends('layouts.app')
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    :root {
        --primary: #1F7A8C;
        --secondary: #FFBB33;
        --accent: #E5F3F5;
        --dark: #1A365D;
        --light: #F8FAFC;
        --success: #10B981;
        --gray-50: #F9FAFB;
        --gray-100: #F3F4F6;
        --gray-200: #E5E7EB;
        --gray-300: #D1D5DB;
        --gray-400: #9CA3AF;
        --gray-500: #6B7280;
        --gray-600: #4B5563;
        --gray-700: #374151;
        --gray-800: #1F2937;
        --gray-900: #111827;
    }
    
    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        background: var(--gray-50);
        line-height: 1.6;
        color: var(--gray-800);
    }

    .privacy-page {
        min-height: 100vh;
        background: var(--light);
    }

    /* Navigation */
    .privacy-nav {
        background: white;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .privacy-logo {
        font-weight: 800;
        font-size: 24px;
        color: var(--primary);
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        list-style: none;
        gap: 30px;
    }

    .nav-links a {
        color: var(--gray-600);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .nav-links a:hover {
        color: var(--primary);
    }

    .nav-cta {
        background: var(--primary);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .nav-cta:hover {
        background: var(--dark);
        transform: translateY(-1px);
    }

    /* Content */
    .privacy-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .privacy-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .privacy-title {
        font-size: 48px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .privacy-subtitle {
        font-size: 18px;
        color: var(--gray-600);
        margin-bottom: 10px;
    }

    .last-updated {
        font-size: 14px;
        color: var(--gray-500);
        font-style: italic;
    }

    .privacy-section {
        margin-bottom: 40px;
    }

    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 15px;
    }

    .section-content {
        font-size: 16px;
        line-height: 1.8;
        color: var(--gray-700);
        margin-bottom: 15px;
    }

    .section-list {
        margin: 15px 0;
        padding-left: 20px;
    }

    .section-list li {
        margin-bottom: 8px;
        color: var(--gray-700);
    }

    .contact-info {
        background: var(--accent);
        padding: 25px;
        border-radius: 12px;
        margin-top: 40px;
    }

    .contact-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 15px;
    }

    .contact-details {
        color: var(--gray-700);
        line-height: 1.6;
    }

    /* Footer */
    .privacy-footer {
        background: var(--gray-900);
        color: var(--gray-300);
        padding: 30px 0;
        margin-top: 60px;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        text-align: center;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .footer-links a {
        color: var(--gray-400);
        text-decoration: none;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 20px;
        }

        .nav-links {
            flex-wrap: wrap;
            justify-content: center;
        }

        .privacy-title {
            font-size: 36px;
        }

        .footer-links {
            flex-direction: column;
            gap: 15px;
        }
    }
</style>

<div class="privacy-page">
    <!-- Navigation -->
    <nav class="privacy-nav">
        <div class="nav-container">
            <a href="/" class="privacy-logo">SafariChat</a>
            <ul class="nav-links">
                <li><a href="/corporate">Corporate</a></li>
                <li><a href="/security">Security</a></li>
                <li><a href="/api">API Docs</a></li>
                <li><a href="/terms-and-conditions">Terms</a></li>
            </ul>
            <a href="/login" class="nav-cta">Get Started</a>
        </div>
    </nav>

    <!-- Content -->
    <div class="privacy-content">
        <div class="privacy-header">
            <h1 class="privacy-title">Privacy Policy</h1>
            <p class="privacy-subtitle">Your privacy and data protection are our top priority</p>
            <p class="last-updated">Last updated: {{ date('F d, Y') }}</p>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">1. Information We Collect</h2>
            <p class="section-content">
                SafariChat collects information to provide better services to our users and improve our AI-powered sales automation platform. We collect information in the following ways:
            </p>
            <ul class="section-list">
                <li><strong>Account Information:</strong> When you create an account, we collect your name, email address, phone number, and company details.</li>
                <li><strong>Usage Data:</strong> We collect information about how you use our services, including chat interactions, message volumes, and feature usage.</li>
                <li><strong>Technical Information:</strong> We collect device information, IP addresses, browser type, and system performance data to ensure optimal service delivery.</li>
                <li><strong>Customer Communications:</strong> Chat messages and customer interactions processed through our AI systems for service improvement.</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">2. How We Use Your Information</h2>
            <p class="section-content">
                We use the collected information for the following purposes:
            </p>
            <ul class="section-list">
                <li>Provide and maintain our AI sales automation services</li>
                <li>Improve and personalize your experience with our platform</li>
                <li>Process transactions and send billing-related communications</li>
                <li>Provide customer support and respond to your inquiries</li>
                <li>Analyze usage patterns to enhance our AI algorithms</li>
                <li>Send important updates about service changes or security notices</li>
                <li>Comply with legal obligations and enforce our Terms of Service</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">3. Data Security & Protection</h2>
            <p class="section-content">
                We implement robust security measures to protect your personal information:
            </p>
            <ul class="section-list">
                <li><strong>Encryption:</strong> All data is encrypted in transit and at rest using industry-standard AES-256 encryption</li>
                <li><strong>Access Controls:</strong> Strict access controls ensure only authorized personnel can access your data</li>
                <li><strong>Regular Audits:</strong> We conduct regular security audits and vulnerability assessments</li>
                <li><strong>Secure Infrastructure:</strong> Our servers are hosted in secure, SOC 2 certified data centers</li>
                <li><strong>Data Backup:</strong> Regular backups ensure data recovery in case of system failures</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">4. Data Sharing & Third Parties</h2>
            <p class="section-content">
                We do not sell your personal information. We may share information in the following limited circumstances:
            </p>
            <ul class="section-list">
                <li><strong>Service Providers:</strong> We work with trusted third-party service providers who help us deliver our services</li>
                <li><strong>WhatsApp Business API:</strong> When using WhatsApp integration, messages are processed through Meta's WhatsApp Business API</li>
                <li><strong>Legal Requirements:</strong> We may disclose information when required by law or to protect our rights and users</li>
                <li><strong>Business Transfers:</strong> In case of merger or acquisition, user information may be transferred as part of business assets</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">5. Data Retention & Deletion</h2>
            <p class="section-content">
                We retain your information only as long as necessary to provide our services and comply with legal obligations:
            </p>
            <ul class="section-list">
                <li>Account information is retained while your account is active</li>
                <li>Chat messages are retained for 12 months for service improvement and support purposes</li>
                <li>Usage analytics data is retained for 24 months</li>
                <li>Financial records are retained for 7 years as required by law</li>
                <li>You can request deletion of your personal data by contacting our support team</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">6. Your Rights & Choices</h2>
            <p class="section-content">
                You have the following rights regarding your personal information:
            </p>
            <ul class="section-list">
                <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
                <li><strong>Correction:</strong> Request correction of inaccurate or incomplete information</li>
                <li><strong>Deletion:</strong> Request deletion of your personal information (subject to legal requirements)</li>
                <li><strong>Portability:</strong> Request transfer of your data to another service provider</li>
                <li><strong>Opt-out:</strong> Unsubscribe from marketing communications at any time</li>
                <li><strong>Restrict Processing:</strong> Request limitation of how we process your information</li>
            </ul>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">7. International Data Transfers</h2>
            <p class="section-content">
                SafariChat is based in Tanzania, and we process data primarily within East Africa. However, some of our service providers may be located in other countries. When we transfer data internationally, we ensure appropriate safeguards are in place to protect your information according to applicable data protection laws.
            </p>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">8. Children's Privacy</h2>
            <p class="section-content">
                Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children under 18. If you become aware that a child has provided us with personal information, please contact us immediately, and we will take steps to remove such information.
            </p>
        </div>

        <div class="privacy-section">
            <h2 class="section-title">9. Changes to This Policy</h2>
            <p class="section-content">
                We may update this Privacy Policy from time to time to reflect changes in our practices or applicable laws. We will notify you of any material changes by:
            </p>
            <ul class="section-list">
                <li>Posting a notice on our website</li>
                <li>Sending an email notification to your registered email address</li>
                <li>Providing an in-app notification</li>
            </ul>
            <p class="section-content">
                Your continued use of our services after the changes take effect constitutes your acceptance of the revised Privacy Policy.
            </p>
        </div>

        <div class="contact-info">
            <h3 class="contact-title">Contact Us About Privacy</h3>
            <div class="contact-details">
                <p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>
                <br>
                <p><strong>Email:</strong> privacy@safarichat.ai</p>
                <p><strong>Phone:</strong> +255 123 456 789</p>
                <p><strong>Address:</strong> Safari Innovation Ltd, Dar es Salaam, Tanzania</p>
                <br>
                <p>We are committed to resolving any privacy-related concerns promptly and transparently.</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="privacy-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms-and-conditions">Terms of Service</a>
                <a href="/security">Security</a>
                <a href="/api">API Docs</a>
                <a href="/corporate">Corporate</a>
            </div>
            <p>&copy; {{date('Y')}} SafariChat. All rights reserved. Building the future of AI sales automation.</p>
        </div>
    </footer>
</div>

@endsection