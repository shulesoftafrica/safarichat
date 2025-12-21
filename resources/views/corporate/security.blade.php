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
        --danger: #EF4444;
        --warning: #F59E0B;
        --info: #3B82F6;
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
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        line-height: 1.6;
        color: var(--gray-800);
    }

    .security-page {
        min-height: 100vh;
        background: var(--light);
    }

    /* Navigation */
    .security-nav {
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

    .security-logo {
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
    .security-content {
        max-width: 1000px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .security-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .security-title {
        font-size: 48px;
        font-weight: 800;
        color: var(--dark);
        margin-bottom: 20px;
    }

    .security-subtitle {
        font-size: 20px;
        color: var(--gray-600);
        margin-bottom: 30px;
    }

    .security-badges {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 30px;
    }

    .security-badge {
        background: var(--success);
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
    }

    .security-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }

    .security-card {
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .security-card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        font-size: 48px;
        margin-bottom: 20px;
        display: block;
    }

    .card-title {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 15px;
    }

    .card-description {
        font-size: 16px;
        color: var(--gray-700);
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .card-features {
        list-style: none;
        padding: 0;
    }

    .card-features li {
        margin-bottom: 8px;
        color: var(--gray-600);
        position: relative;
        padding-left: 20px;
    }

    .card-features li::before {
        content: "✓";
        position: absolute;
        left: 0;
        color: var(--success);
        font-weight: bold;
    }

    /* Compliance Section */
    .compliance-section {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
        margin: 40px 0;
    }

    .compliance-title {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 25px;
        text-align: center;
    }

    .compliance-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
    }

    .compliance-item {
        text-align: center;
        padding: 20px;
        border: 2px solid var(--accent);
        border-radius: 12px;
        transition: border-color 0.3s ease;
    }

    .compliance-item:hover {
        border-color: var(--primary);
    }

    .compliance-icon {
        font-size: 32px;
        margin-bottom: 15px;
        display: block;
    }

    .compliance-name {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .compliance-desc {
        font-size: 14px;
        color: var(--gray-600);
    }

    /* Security Measures */
    .measures-section {
        margin: 60px 0;
    }

    .measures-title {
        font-size: 32px;
        font-weight: 800;
        color: var(--dark);
        text-align: center;
        margin-bottom: 40px;
    }

    .measures-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 25px;
    }

    .measure-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    }

    .measure-icon {
        font-size: 24px;
        color: var(--primary);
        flex-shrink: 0;
    }

    .measure-content h4 {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .measure-content p {
        font-size: 14px;
        color: var(--gray-600);
        line-height: 1.5;
    }

    /* Contact Section */
    .security-contact {
        background: var(--dark);
        color: white;
        padding: 50px;
        border-radius: 16px;
        text-align: center;
        margin-top: 60px;
    }

    .contact-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .contact-description {
        font-size: 16px;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .contact-info {
        display: flex;
        justify-content: center;
        gap: 40px;
        flex-wrap: wrap;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .contact-item span {
        font-size: 18px;
    }

    /* Footer */
    .security-footer {
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

        .security-title {
            font-size: 36px;
        }

        .security-grid {
            grid-template-columns: 1fr;
        }

        .contact-info {
            flex-direction: column;
            gap: 20px;
        }
    }
</style>

<div class="security-page">
    <!-- Navigation -->
    <nav class="security-nav">
        <div class="nav-container">
            <a href="/" class="security-logo">SafariChat</a>
            <ul class="nav-links">
                <li><a href="/corporate">Corporate</a></li>
                <li><a href="/privacy">Privacy Policy</a></li>
                <li><a href="/api">API Docs</a></li>
                <li><a href="/terms-and-conditions">Terms</a></li>
            </ul>
            <a href="/login" class="nav-cta">Get Started</a>
        </div>
    </nav>

    <!-- Content -->
    <div class="security-content">
        <div class="security-header">
            <h1 class="security-title">Enterprise-Grade Security</h1>
            <p class="security-subtitle">
                Your data security is our highest priority. We implement multiple layers of protection to ensure your business communications and customer data remain safe and secure.
            </p>
            <div class="security-badges">
                <span class="security-badge">SOC 2 Certified</span>
                <span class="security-badge">ISO 27001 Compliant</span>
                <span class="security-badge">GDPR Compliant</span>
                <span class="security-badge">256-bit Encryption</span>
            </div>
        </div>

        <!-- Security Features Grid -->
        <div class="security-grid">
            <div class="security-card">
                <span class="card-icon">🔐</span>
                <h3 class="card-title">End-to-End Encryption</h3>
                <p class="card-description">
                    All data transmission and storage is protected with industry-standard AES-256 encryption, ensuring your conversations remain private.
                </p>
                <ul class="card-features">
                    <li>256-bit AES encryption for data at rest</li>
                    <li>TLS 1.3 for data in transit</li>
                    <li>Encrypted backup storage</li>
                    <li>Key rotation every 90 days</li>
                </ul>
            </div>

            <div class="security-card">
                <span class="card-icon">🛡️</span>
                <h3 class="card-title">Infrastructure Security</h3>
                <p class="card-description">
                    Our infrastructure is hosted on secure, certified cloud platforms with multiple layers of protection against threats.
                </p>
                <ul class="card-features">
                    <li>SOC 2 Type II certified data centers</li>
                    <li>24/7 security monitoring</li>
                    <li>DDoS protection and mitigation</li>
                    <li>Regular vulnerability assessments</li>
                </ul>
            </div>

            <div class="security-card">
                <span class="card-icon">👥</span>
                <h3 class="card-title">Access Control</h3>
                <p class="card-description">
                    Strict access controls ensure only authorized personnel can access your data, with comprehensive audit trails.
                </p>
                <ul class="card-features">
                    <li>Multi-factor authentication required</li>
                    <li>Role-based access control (RBAC)</li>
                    <li>Regular access reviews</li>
                    <li>Complete audit logging</li>
                </ul>
            </div>

            <div class="security-card">
                <span class="card-icon">🔄</span>
                <h3 class="card-title">Data Backup & Recovery</h3>
                <p class="card-description">
                    Automated, encrypted backups ensure your data is always recoverable with minimal downtime.
                </p>
                <ul class="card-features">
                    <li>Automated daily backups</li>
                    <li>Geo-redundant storage</li>
                    <li>Point-in-time recovery</li>
                    <li>99.9% uptime guarantee</li>
                </ul>
            </div>

            <div class="security-card">
                <span class="card-icon">📊</span>
                <h3 class="card-title">Monitoring & Analytics</h3>
                <p class="card-description">
                    Advanced monitoring systems detect and respond to security threats in real-time.
                </p>
                <ul class="card-features">
                    <li>Real-time threat detection</li>
                    <li>Anomaly detection algorithms</li>
                    <li>Security incident response team</li>
                    <li>Comprehensive logging</li>
                </ul>
            </div>

            <div class="security-card">
                <span class="card-icon">🔒</span>
                <h3 class="card-title">Privacy Protection</h3>
                <p class="card-description">
                    We follow strict data privacy guidelines and give you complete control over your data.
                </p>
                <ul class="card-features">
                    <li>GDPR compliance</li>
                    <li>Data minimization principles</li>
                    <li>Customer data ownership</li>
                    <li>Right to delete data</li>
                </ul>
            </div>
        </div>

        <!-- Compliance Section -->
        <div class="compliance-section">
            <h2 class="compliance-title">Compliance & Certifications</h2>
            <div class="compliance-grid">
                <div class="compliance-item">
                    <span class="compliance-icon">🏆</span>
                    <div class="compliance-name">SOC 2 Type II</div>
                    <div class="compliance-desc">Security, availability, and confidentiality controls</div>
                </div>
                <div class="compliance-item">
                    <span class="compliance-icon">🌍</span>
                    <div class="compliance-name">ISO 27001</div>
                    <div class="compliance-desc">International security management standards</div>
                </div>
                <div class="compliance-item">
                    <span class="compliance-icon">🇪🇺</span>
                    <div class="compliance-name">GDPR</div>
                    <div class="compliance-desc">European data protection regulations</div>
                </div>
                <div class="compliance-item">
                    <span class="compliance-icon">🏥</span>
                    <div class="compliance-name">HIPAA Ready</div>
                    <div class="compliance-desc">Healthcare data protection compliance</div>
                </div>
                <div class="compliance-item">
                    <span class="compliance-icon">🔐</span>
                    <div class="compliance-name">WhatsApp Certified</div>
                    <div class="compliance-desc">Official WhatsApp Business API partner</div>
                </div>
                <div class="compliance-item">
                    <span class="compliance-icon">🛡️</span>
                    <div class="compliance-name">PCI DSS</div>
                    <div class="compliance-desc">Payment card industry security standards</div>
                </div>
            </div>
        </div>

        <!-- Security Measures -->
        <div class="measures-section">
            <h2 class="measures-title">Additional Security Measures</h2>
            <div class="measures-grid">
                <div class="measure-item">
                    <span class="measure-icon">🔍</span>
                    <div class="measure-content">
                        <h4>Penetration Testing</h4>
                        <p>Regular third-party security assessments to identify and fix vulnerabilities</p>
                    </div>
                </div>
                <div class="measure-item">
                    <span class="measure-icon">🎓</span>
                    <div class="measure-content">
                        <h4>Security Training</h4>
                        <p>All employees receive regular security awareness training and certification</p>
                    </div>
                </div>
                <div class="measure-item">
                    <span class="measure-icon">📋</span>
                    <div class="measure-content">
                        <h4>Incident Response</h4>
                        <p>Comprehensive incident response plan with 24/7 security team availability</p>
                    </div>
                </div>
                <div class="measure-item">
                    <span class="measure-icon">🔄</span>
                    <div class="measure-content">
                        <h4>Regular Updates</h4>
                        <p>Automated security patches and system updates to maintain protection</p>
                    </div>
                </div>
                <div class="measure-item">
                    <span class="measure-icon">📱</span>
                    <div class="measure-content">
                        <h4>Mobile Security</h4>
                        <p>Secure mobile applications with biometric authentication options</p>
                    </div>
                </div>
                <div class="measure-item">
                    <span class="measure-icon">🌐</span>
                    <div class="measure-content">
                        <h4>Network Security</h4>
                        <p>Advanced firewall protection and network segmentation</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Contact -->
        <div class="security-contact">
            <h2 class="contact-title">Security Questions or Concerns?</h2>
            <p class="contact-description">
                Our security team is available 24/7 to address any security-related inquiries or incidents. 
                We take all security matters seriously and respond promptly.
            </p>
            <div class="contact-info">
                <div class="contact-item">
                    <span>📧</span>
                    <span>security@safarichat.com</span>
                </div>
                <div class="contact-item">
                    <span>🚨</span>
                    <span>Emergency: +255 123 456 789</span>
                </div>
                <div class="contact-item">
                    <span>💬</span>
                    <span>24/7 Security Helpdesk</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="security-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms-and-conditions">Terms of Service</a>
                <a href="/security">Security</a>
                <a href="/api">API Docs</a>
                <a href="/corporate">Corporate</a>
            </div>
            <p>&copy; {{date('Y')}} SafariChat. All rights reserved. Your security is our priority.</p>
        </div>
    </footer>
</div>

@endsection