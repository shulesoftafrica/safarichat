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
        background: linear-gradient(135deg, #f8fafb 0%, #f1f5f9 100%);
        line-height: 1.6;
        color: var(--gray-800);
    }

    .corporate-page {
        min-height: 100vh;
        background: var(--light);
    }

    /* Navigation */
    .corporate-nav {
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

    .corporate-logo {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--primary);
        text-decoration: none;
    }

    .nav-links {
        display: flex;
        gap: 30px;
        list-style: none;
    }

    .nav-links a {
        color: var(--gray-700);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .nav-links a:hover {
        color: var(--primary);
    }

    .nav-cta {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: transform 0.3s ease;
    }

    .nav-cta:hover {
        transform: translateY(-2px);
        color: white;
        text-decoration: none;
    }

    /* Hero Section */
    .corporate-hero {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 100px 20px;
        text-align: center;
    }

    .hero-container {
        max-width: 1000px;
        margin: 0 auto;
    }

    .corporate-hero-title {
        font-size: 3.5rem;
        font-weight: 900;
        margin-bottom: 30px;
        line-height: 1.2;
    }

    .corporate-hero-subtitle {
        font-size: 1.4rem;
        margin-bottom: 20px;
        line-height: 1.6;
        opacity: 0.9;
    }

    .corporate-hero-support {
        font-size: 1.1rem;
        margin-bottom: 40px;
        line-height: 1.6;
        opacity: 0.8;
    }

    .corporate-hero-cta {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-corporate-primary {
        background: var(--secondary);
        color: var(--primary);
        padding: 18px 35px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-corporate-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 187, 51, 0.4);
        color: var(--primary);
        text-decoration: none;
    }

    .btn-corporate-secondary {
        background: transparent;
        color: white;
        padding: 18px 35px;
        border: 2px solid white;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-corporate-secondary:hover {
        background: white;
        color: var(--primary);
        transform: translateY(-3px);
        text-decoration: none;
    }

    /* Content Sections */
    .content-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .section {
        padding: 80px 0;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--primary);
        text-align: center;
        margin-bottom: 50px;
    }

    /* Urgency Section */
    .corporate-urgency {
        background: white;
        padding: 80px 0;
    }

    .urgency-quote {
        font-size: 1.6rem;
        font-style: italic;
        color: var(--primary);
        text-align: center;
        margin-bottom: 40px;
        font-weight: 600;
        max-width: 900px;
        margin-left: auto;
        margin-right: auto;
        border-left: 6px solid var(--secondary);
        padding-left: 30px;
    }

    .urgency-points {
        list-style: none;
        padding: 0;
        margin: 40px 0;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }

    .urgency-points li {
        font-size: 1.2rem;
        color: var(--gray-700);
        margin-bottom: 20px;
        padding-left: 40px;
        position: relative;
    }

    .urgency-points li::before {
        content: '⚡';
        font-size: 1.5rem;
        position: absolute;
        left: 0;
        color: var(--secondary);
    }

    .btn-urgency {
        background: var(--secondary);
        color: var(--primary);
        padding: 15px 30px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        margin: 40px auto 0;
        display: block;
        transition: all 0.3s ease;
    }

    .btn-urgency:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 187, 51, 0.4);
    }

    /* Transformation Section */
    .corporate-transformation {
        background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
        padding: 80px 0;
    }

    .transformation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }

    .transformation-card {
        background: white;
        padding: 40px 30px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-align: center;
        transition: all 0.3s ease;
    }

    .transformation-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    .transformation-number {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .transformation-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .transformation-card p {
        color: var(--gray-600);
        margin-bottom: 25px;
        line-height: 1.6;
    }

    .transformation-card ul {
        list-style: none;
        padding: 0;
        text-align: left;
    }

    .transformation-card li {
        color: var(--gray-700);
        margin-bottom: 10px;
        padding-left: 25px;
        position: relative;
        font-weight: 500;
    }

    .transformation-card li::before {
        content: '✓';
        color: var(--success);
        font-weight: bold;
        position: absolute;
        left: 0;
    }

    /* Package Details Section */
    .corporate-package-details {
        padding: 80px 0;
        background: white;
    }

    .package-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }

    .package-detail-card {
        background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .package-detail-card:hover {
        transform: translateY(-5px);
    }

    .package-detail-card h3 {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 25px;
    }

    .package-detail-card p {
        color: var(--gray-600);
        margin-bottom: 20px;
        line-height: 1.6;
    }

    .package-detail-card ul {
        list-style: none;
        padding: 0;
        margin: 25px 0;
    }

    .package-detail-card li {
        color: var(--gray-700);
        margin-bottom: 12px;
        padding-left: 30px;
        position: relative;
        font-weight: 500;
    }

    .package-detail-card li::before {
        content: '▸';
        color: var(--primary);
        font-weight: bold;
        position: absolute;
        left: 0;
        font-size: 1.2rem;
    }

    .package-detail-card .highlight {
        background: var(--secondary);
        color: var(--primary);
        padding: 20px;
        border-radius: 12px;
        font-weight: 600;
        margin-top: 25px;
        border-left: 4px solid var(--primary);
    }

    .package-detail-card .note {
        font-style: italic;
        color: var(--gray-500);
        font-size: 0.9rem;
        margin-top: 15px;
    }

    /* ROI Section */
    .corporate-roi {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 80px 0;
    }

    .corporate-roi .section-title {
        color: white;
    }

    .roi-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin: 50px 0;
    }

    .roi-stat {
        text-align: center;
        padding: 40px 30px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .roi-number {
        font-size: 4rem;
        margin-bottom: 20px;
    }

    .roi-stat h3 {
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 15px;
        color: var(--secondary);
    }

    .roi-stat p {
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 15px;
    }

    .roi-stat span {
        font-size: 1rem;
        opacity: 0.8;
    }

    .roi-credibility {
        font-size: 1.4rem;
        font-style: italic;
        text-align: center;
        margin-top: 50px;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.1);
        padding: 40px;
        border-radius: 20px;
        border-left: 6px solid var(--secondary);
    }

    /* Trust Section */
    .corporate-trust {
        padding: 80px 0;
        background: white;
    }

    .trust-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }

    .trust-feature {
        display: flex;
        gap: 25px;
        padding: 35px;
        background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .trust-feature:hover {
        transform: translateY(-5px);
    }

    .trust-icon {
        color: var(--success);
        font-size: 2rem;
        font-weight: bold;
        min-width: 40px;
    }

    .trust-feature h4 {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 15px;
    }

    .trust-feature p {
        color: var(--gray-600);
        line-height: 1.6;
        font-weight: 500;
    }

    /* Corporate Pricing Section */
    .corporate-pricing-section {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 80px 0;
        text-align: center;
    }

    .corporate-pricing-section .section-title {
        color: white;
    }

    .corporate-pricing-content {
        max-width: 800px;
        margin: 0 auto;
    }

    .corporate-pricing-content p {
        font-size: 1.2rem;
        margin-bottom: 25px;
    }

    .corporate-pricing-content ul {
        list-style: none;
        padding: 0;
        margin: 40px 0;
        text-align: left;
    }

    .corporate-pricing-content li {
        margin-bottom: 15px;
        padding-left: 30px;
        position: relative;
        font-size: 1.1rem;
    }

    .corporate-pricing-content li::before {
        content: '✓';
        color: var(--secondary);
        font-weight: bold;
        position: absolute;
        left: 0;
        font-size: 1.3rem;
    }

    .pricing-message {
        background: rgba(255, 255, 255, 0.1);
        padding: 35px;
        border-radius: 15px;
        font-size: 1.3rem;
        font-style: italic;
        margin: 40px 0;
        border-left: 6px solid var(--secondary);
        backdrop-filter: blur(10px);
    }

    .btn-corporate-proposal {
        background: var(--secondary);
        color: var(--primary);
        padding: 20px 40px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 30px;
    }

    .btn-corporate-proposal:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 187, 51, 0.4);
    }

    /* Final CTA Section */
    .corporate-final-cta {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 100px 20px;
        text-align: center;
    }

    .final-cta-title {
        font-size: 3rem;
        font-weight: 900;
        margin-bottom: 25px;
    }

    .final-cta-subtitle {
        font-size: 1.4rem;
        margin-bottom: 50px;
        opacity: 0.9;
    }

    .final-cta-buttons {
        display: flex;
        gap: 25px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .btn-corporate-meeting {
        background: var(--secondary);
        color: var(--primary);
        padding: 22px 45px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-corporate-meeting:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 187, 51, 0.4);
        color: var(--primary);
        text-decoration: none;
    }

    .btn-corporate-consultant {
        background: transparent;
        color: white;
        padding: 22px 45px;
        border: 2px solid white;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.2rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-corporate-consultant:hover {
        background: white;
        color: var(--primary);
        transform: translateY(-3px);
        text-decoration: none;
    }

    /* Footer */
    .corporate-footer {
        background: var(--gray-900);
        color: var(--gray-400);
        padding: 60px 20px 30px;
        text-align: center;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        gap: 40px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .footer-links a {
        color: var(--gray-400);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .footer-links a:hover {
        color: var(--primary);
        text-decoration: none;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 20px;
        }

        .nav-links {
            gap: 20px;
        }

        .corporate-hero-title {
            font-size: 2.5rem;
        }

        .transformation-grid {
            grid-template-columns: 1fr;
        }

        .package-details-grid {
            grid-template-columns: 1fr;
        }

        .trust-features {
            grid-template-columns: 1fr;
        }

        .final-cta-buttons {
            flex-direction: column;
            align-items: center;
        }

        .final-cta-title {
            font-size: 2rem;
        }

        .section-title {
            font-size: 2rem;
        }
    }
</style>

<div class="corporate-page">
    <!-- Navigation -->
    <nav class="corporate-nav">
        <div class="nav-container">
            <a href="/" class="corporate-logo">SafariChat Corporate</a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#pricing">Pricing</a></li>
                <li><a href="#roi">ROI</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <a href="/login" class="nav-cta">Get Started</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="corporate-hero">
        <div class="hero-container">
            <h1 class="corporate-hero-title">AI Sales Automation for Corporates — Faster Sales, Higher Conversions, Zero Delays.</h1>
            <p class="corporate-hero-subtitle">
                Transform your Sales Department now with AI Sales Agents that boost your sales team performance 10X — engaging customers, negotiating deals, converting leads, and booking meetings automatically, so your sales people only handle final delivery and follow-up.
            </p>
            <p class="corporate-hero-support">
                If YOU STILL sends Bulk-SMS and expect conversion, that ERA is gone. Adopt AI now and let AI systematically engage customers and drive sales number while you check metrics. From Lead Scoring, Followups, product comparisons, etc, The AI will cover.
            </p>
            <div class="corporate-hero-cta">
                <a href="#contact" class="btn-corporate-primary">Get Started Now</a>
                <a href="#features" class="btn-corporate-secondary">See How AI Will Transform Your Sales</a>
            </div>
        </div>
    </section>

    <!-- Urgency Section -->
    <section class="corporate-urgency">
        <div class="content-container">
            <blockquote class="urgency-quote">
                Your customers dont only expect instant replies, but also demands clear information. AI is no longer optional — it's the competitive advantage that decides who grows and who falls behind.
            </blockquote>
            <ul class="urgency-points">
                <li>Customers choose the company that replies <strong>first</strong>, not the one with the best product.</li>
                <li>Your human team can't respond 24/7 — but SafariChat AI can.</li>
                <li>Every unanswered message is <strong>lost revenue</strong>.</li>
            </ul>
            <button class="btn-urgency">See how your competitors are using AI right now</button>
        </div>
    </section>

    <!-- Transformation Section -->
    <section class="corporate-transformation" id="features">
        <div class="content-container">
            <h2 class="section-title">SafariChat becomes your always-available, perfectly trained AI Sales Rep — built for corporate scale.</h2>
            <div class="transformation-grid">
                <div class="transformation-card">
                    <div class="transformation-number">1️⃣</div>
                    <h3>Respond Instantly — Across All Departments</h3>
                    <p>SafariChat answers customers instantly on WhatsApp with accurate, compliant information.</p>
                    <ul>
                        <li>No queues</li>
                        <li>No missed messages</li>
                        <li>No delays</li>
                    </ul>
                </div>
                <div class="transformation-card">
                    <div class="transformation-number">2️⃣</div>
                    <h3>Qualify Leads Faster Than Any Human Team</h3>
                    <p>Our AI evaluates customer intent, budget, needs, and urgency in real time — pushing hot leads to your team immediately.</p>
                    <ul>
                        <li>Higher conversion</li>
                        <li>Shorter sales cycles</li>
                    </ul>
                </div>
                <div class="transformation-card">
                    <div class="transformation-number">3️⃣</div>
                    <h3>Automate Follow-Ups & Re-Engagement</h3>
                    <p>SafariChat never forgets a lead.</p>
                    <ul>
                        <li>Daily follow-ups</li>
                        <li>Cold lead reactivation</li>
                        <li>Abandoned conversation recovery</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Package Details Section -->
    <section class="corporate-package-details">
        <div class="content-container">
            <h2 class="section-title">Built for Banks, Telecoms, Insurance, Real Estate, Universities, and Large Institutions.</h2>
            <div class="package-details-grid">
                <div class="package-detail-card">
                    <h3>A. Custom AI Setup & Model Training</h3>
                    <p>Your corporate gets a fully customized AI Sales Agent trained on:</p>
                    <ul>
                        <li>All your products</li>
                        <li>Your pricing rules</li>
                        <li>Your compliance regulations</li>
                        <li>Your tone and communication standards</li>
                        <li>Your workflows and escalation rules</li>
                    </ul>
                    <p class="highlight">This is a one-time corporate setup investment, ensuring SafariChat behaves exactly like a top-performing corporate sales professional.</p>
                </div>
                <div class="package-detail-card">
                    <h3>B. Official WhatsApp Business Integration (Optional Add-On)</h3>
                    <p>Corporates may require:</p>
                    <ul>
                        <li>Official WhatsApp API</li>
                        <li>Green Tick Verification</li>
                        <li>High-volume outbound messaging</li>
                        <li>Multi-agent routing</li>
                        <li>Automated templates</li>
                    </ul>
                    <p>SafariChat helps your team set up everything end to end.</p>
                    <p class="note">Additional provider fees may apply, but we manage the full process.</p>
                </div>
                <div class="package-detail-card">
                    <h3>C. Corporate Sales Team Training</h3>
                    <p>Your sales and customer service teams receive hands-on training on:</p>
                    <ul>
                        <li>How to collaborate with the AI agent</li>
                        <li>How to interpret AI-qualified leads</li>
                        <li>How to take over conversations efficiently</li>
                        <li>How to close deals faster with AI support</li>
                        <li>How to maintain compliance while using automation</li>
                    </ul>
                    <p class="highlight">This ensures maximum ROI and seamless adoption.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ROI Section -->
    <section class="corporate-roi" id="roi">
        <div class="content-container">
            <h2 class="section-title">Proven results that executive teams care about.</h2>
            <div class="roi-stats">
                <div class="roi-stat">
                    <div class="roi-number">📈</div>
                    <h3>20–45%</h3>
                    <p>Increase in Conversions</p>
                    <span>Instant responses dramatically improve decision speed.</span>
                </div>
                <div class="roi-stat">
                    <div class="roi-number">⏱</div>
                    <h3>60–80%</h3>
                    <p>Reduction in Sales Workload</p>
                    <span>AI takes over repetitive inquiries and lead qualification.</span>
                </div>
                <div class="roi-stat">
                    <div class="roi-number">💰</div>
                    <h3>Millions</h3>
                    <p>Saved in Hiring & Operational Costs</p>
                    <span>Scale your customer engagement without expanding staff.</span>
                </div>
            </div>
            <blockquote class="roi-credibility">
                "SafariChat does the work of a 20-person sales team — with perfect consistency, unlimited capacity, and zero downtime."
            </blockquote>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="corporate-trust">
        <div class="content-container">
            <h2 class="section-title">Why Corporates Trust SafariChat</h2>
            <div class="trust-features">
                <div class="trust-feature">
                    <div class="trust-icon">✔</div>
                    <div>
                        <h4>Enterprise-ready architecture</h4>
                        <p>Handles thousands of messages daily with reliability.</p>
                    </div>
                </div>
                <div class="trust-feature">
                    <div class="trust-icon">✔</div>
                    <div>
                        <h4>Secure, compliant, multi-tenant infrastructure</h4>
                        <p>Built on encrypted communication and strict access controls.</p>
                    </div>
                </div>
                <div class="trust-feature">
                    <div class="trust-icon">✔</div>
                    <div>
                        <h4>Trained for your industry</h4>
                        <p>Financial services, education, real estate, healthcare, insurance, telecoms.</p>
                    </div>
                </div>
                <div class="trust-feature">
                    <div class="trust-icon">✔</div>
                    <div>
                        <h4>Zero downtime</h4>
                        <p>AI operates 24/7 across all time zones.</p>
                    </div>
                </div>
                <div class="trust-feature">
                    <div class="trust-icon">✔</div>
                    <div>
                        <h4>Works with your existing teams</h4>
                        <p>AI doesn't replace — it enhances.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Corporate Pricing Section -->
    <section class="corporate-pricing-section" id="pricing">
        <div class="content-container">
            <h2 class="section-title">Flexible, scalable pricing for organizations that demand high performance.</h2>
            <div class="corporate-pricing-content">
                <p><strong>SafariChat Corporate includes:</strong></p>
                <ul>
                    <li>Customized AI deployment</li>
                    <li>Enterprise-level message capacity</li>
                    <li>Additional AI messages at the lowest rate (TSh 75/message)</li>
                    <li>Optional WhatsApp API connectivity</li>
                    <li>Corporate onboarding & team training</li>
                </ul>
                <blockquote class="pricing-message">
                    "Corporate pricing is customized based on your message volume, number of departments, and required integrations. You only pay for the value you receive — and the ROI is immediate."
                </blockquote>
                <button class="btn-corporate-proposal">Request a Corporate Proposal</button>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="corporate-final-cta" id="contact">
        <div class="content-container">
            <h2 class="final-cta-title">Your Customers Are Messaging You Right Now. Are You Responding Instantly?</h2>
            <p class="final-cta-subtitle">
                Corporates that adopt AI today will dominate the next decade — those that delay will lose customers to faster competitors.
            </p>
            <div class="final-cta-buttons">
                <a href="mailto:corporate@safarichat.com" class="btn-corporate-meeting">🚀 Book a Corporate Strategy Meeting</a>
                <a href="tel:+255123456789" class="btn-corporate-consultant">📩 Talk to an AI Sales Consultant</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="corporate-footer">
        <div class="footer-content">
            <div class="footer-links">
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms-and-conditions">Terms of Service</a>
                <a href="/security">Security</a>
                <a href="/api">API Docs</a>
            </div>
            <p>&copy; {{date('Y')}} SafariChat. All rights reserved. Building the future of AI sales automation.</p>
        </div>
    </footer>
</div>

@endsection