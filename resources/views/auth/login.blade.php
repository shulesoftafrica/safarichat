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
    
    /* Layout */
    .modern-layout {
        min-height: 100vh;
        display: flex;
    }
    
    .content-section {
        flex: 1;
        overflow-y: auto;
        margin-right: 450px;
        background: var(--light);
    }
    
    .sticky-login {
        width: 450px;
        background: white;
        box-shadow: -8px 0 32px rgba(0, 0, 0, 0.12);
        position: fixed;
        right: 0;
        top: 0;
        height: 100vh;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        z-index: 1000;
        border-left: 1px solid var(--gray-200);
    }
    
    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, var(--primary) 0%, #2C5AA0 100%);
        color: white;
        padding: 100px 80px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        opacity: 0.5;
    }
    
    .hero-content {
        position: relative;
        z-index: 2;
        max-width: 900px;
        margin: 0 auto;
    }
    
    .hero-icon {
        width: 120px;
        height: 120px;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        border-radius: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 40px;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .hero-title {
        font-size: 4rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 24px;
        background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .hero-subtitle {
        font-size: 1.5rem;
        opacity: 0.95;
        margin-bottom: 40px;
        line-height: 1.6;
        font-weight: 400;
    }
    
    .hero-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        margin-bottom: 50px;
        flex-wrap: wrap;
    }
    
    .btn-hero-primary {
        background: var(--secondary);
        color: var(--dark);
        padding: 18px 36px;
        border-radius: 15px;
        font-weight: 700;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-hero-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(255, 187, 51, 0.4);
    }
    
    .btn-hero-secondary {
        background: transparent;
        color: white;
        padding: 18px 36px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
        border: 2px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-hero-secondary:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.5);
        transform: translateY(-3px);
        color: white;
        text-decoration: none;
    }
    
    .trust-indicators {
        font-size: 1rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }
    
    /* Track Record Section */
    .track-record {
        background: white;
        padding: 80px 80px;
        text-align: center;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }
    
    .stat-card {
        text-align: center;
    }
    
    .stat-number {
        font-size: 3.5rem;
        font-weight: 900;
        color: var(--primary);
        margin-bottom: 8px;
        display: block;
    }
    
    .stat-label {
        color: var(--gray-600);
        font-weight: 500;
        font-size: 1.1rem;
    }
    
    /* Problems & Solutions */
    .problems-solutions {
        background: var(--gray-50);
        padding: 80px 80px;
    }
    
    .section-title {
        font-size: 3rem;
        font-weight: 800;
        color: var(--dark);
        text-align: center;
        margin-bottom: 60px;
        line-height: 1.2;
    }
    
    .problems-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .problem-card {
        background: #FEF2F2;
        border: 2px solid #FECACA;
        border-radius: 20px;
        padding: 40px;
    }
    
    .solution-card {
        background: #F0FDF4;
        border: 2px solid #BBF7D0;
        border-radius: 20px;
        padding: 40px;
    }
    
    .card-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 30px;
    }
    
    .problem-card .card-title {
        color: #DC2626;
    }
    
    .solution-card .card-title {
        color: #059669;
    }
    
    .problem-list, .solution-list {
        list-style: none;
    }
    
    .problem-list li, .solution-list li {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        font-size: 1.1rem;
        line-height: 1.6;
    }
    
    .problem-list li::before {
        content: '❌';
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .solution-list li::before {
        content: '✅';
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    /* Features Section */
    .features-section {
        background: white;
        padding: 80px 80px;
    }
    
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 40px;
        margin-top: 60px;
    }
    
    .feature-card {
        background: white;
        border: 2px solid var(--gray-100);
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .feature-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .feature-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 25px 50px rgba(31, 122, 140, 0.15);
        border-color: var(--primary);
    }
    
    .feature-card:hover::before {
        transform: scaleX(1);
    }
    
    .feature-icon {
        font-size: 4rem;
        margin-bottom: 24px;
        display: block;
    }
    
    .feature-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 16px;
        line-height: 1.3;
    }
    
    .feature-desc {
        color: var(--gray-600);
        font-size: 1.05rem;
        line-height: 1.6;
    }
    
    /* Industries Section */
    .industries-section {
        background: var(--gray-50);
        padding: 80px 80px;
        text-align: center;
    }
    
    .industries-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-top: 60px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .industry-card {
        background: white;
        border-radius: 20px;
        padding: 40px 30px;
        text-align: center;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
    }
    
    .industry-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }
    
    .industry-icon {
        font-size: 3.5rem;
        margin-bottom: 20px;
        display: block;
    }
    
    .industry-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 12px;
    }
    
    .industry-desc {
        color: var(--gray-600);
        font-size: 0.95rem;
        line-height: 1.5;
    }
    
    /* Pricing Section */
    .pricing-section {
        background: white;
        padding: 80px 80px;
    }
    
    .corporate-package-section {
        margin-top: 60px;
        padding: 40px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        text-align: center;
        border: 3px solid var(--primary);
        position: relative;
        overflow: hidden;
    }
    
    .corporate-package-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(31, 122, 140, 0.1), transparent);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .corporate-badge {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .corporate-title {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 15px;
    }
    
    .corporate-description {
        font-size: 1.1rem;
        color: var(--gray-600);
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .corporate-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .corporate-feature {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .corporate-feature i {
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .corporate-feature span {
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .btn-corporate {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 15px 30px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-corporate:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(31, 122, 140, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .currency-switcher {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin: 30px 0 40px;
    }
    
    .currency-switcher-label {
        font-weight: 600;
        color: var(--gray-700);
        font-size: 1rem;
    }
    
    .currency-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .currency-btn {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
        min-width: 120px;
    }
    
    .currency-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(31, 122, 140, 0.3);
    }
    
    .currency-dropdown-content {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid var(--gray-200);
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        margin-top: 8px;
    }
    
    .currency-dropdown.active .currency-dropdown-content {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .currency-option {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        color: var(--gray-700);
        text-decoration: none;
        transition: all 0.3s ease;
        border-bottom: 1px solid var(--gray-100);
        font-weight: 500;
    }
    
    .currency-option:last-child {
        border-bottom: none;
    }
    
    .currency-option:hover {
        background: var(--accent);
        color: var(--primary);
        text-decoration: none;
    }
    
    .currency-option.active {
        background: var(--primary);
        color: white;
    }
    
    .currency-flag {
        width: 20px;
        height: 15px;
        border-radius: 3px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
    }
    
    .flag-usd {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 55 30"><rect width="55" height="30" fill="%23B22234"/><path d="M0,3.5H55M0,7H55M0,10.5H55M0,14H55M0,17.5H55M0,21H55M0,24.5H55M0,28H55" stroke="white" stroke-width="1"/><rect width="22" height="16" fill="%23002868"/></svg>');
    }
    
    .flag-tsh {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 900 600"><rect width="900" height="600" fill="%23009639"/><path d="M0,0L900,600M0,600L900,0" stroke="%23FCD116" stroke-width="60"/><path d="M0,0L900,600M0,600L900,0" stroke="%23000" stroke-width="40"/><path d="M0,0L900,600M0,600L900,0" stroke="%23CE1126" stroke-width="20"/></svg>');
    }
    
    .flag-ngn {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30"><rect width="20" height="30" fill="%23008751"/><rect x="20" width="20" height="30" fill="white"/><rect x="40" width="20" height="30" fill="%23008751"/></svg>');
    }
    
    .flag-brl {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 42"><rect width="60" height="42" fill="%23009639"/><path d="M30,5L55,21L30,37L5,21Z" fill="%23FEDD00"/><circle cx="30" cy="21" r="8" fill="%23012169"/></svg>');
    }
    
    .flag-inr {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><rect width="60" height="13.33" fill="%23FF9933"/><rect y="13.33" width="60" height="13.33" fill="white"/><rect y="26.66" width="60" height="13.33" fill="%23138808"/></svg>');
    }
    
    .flag-eur {
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 40"><rect width="60" height="40" fill="%23003399"/><g fill="%23FFCC00"><circle cx="30" cy="20" r="2"/></g></svg>');
    }
    
    .exchange-rate-info {
        text-align: center;
        margin-top: 15px;
        font-size: 0.9rem;
        color: var(--gray-500);
    }
    
    .exchange-rate-info .rate {
        font-weight: 600;
        color: var(--primary);
    }
    
    .corporate-package-section {
        margin-top: 60px;
        padding: 40px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 20px;
        text-align: center;
        border: 3px solid var(--primary);
        position: relative;
        overflow: hidden;
    }
    
    .corporate-package-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(31, 122, 140, 0.1), transparent);
        animation: shimmer 3s infinite;
    }
    
    @keyframes shimmer {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    
    .corporate-badge {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .corporate-title {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 15px;
    }
    
    .corporate-description {
        font-size: 1.1rem;
        color: var(--gray-600);
        margin-bottom: 30px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .corporate-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .corporate-feature {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .corporate-feature i {
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .corporate-feature span {
        font-weight: 600;
        color: var(--gray-700);
    }
    
    .btn-corporate {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 15px 30px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.1rem;
        display: inline-block;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
    }
    
    .btn-corporate:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(31, 122, 140, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 30px;
        margin-top: 60px;
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .pricing-card {
        background: white;
        border: 2px solid var(--gray-200);
        border-radius: 25px;
        padding: 40px 30px;
        position: relative;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .pricing-card.featured {
        border-color: var(--primary);
        transform: scale(1.05);
        box-shadow: 0 20px 40px rgba(31, 122, 140, 0.2);
    }
    
    .pricing-badge {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--secondary);
        color: var(--dark);
        padding: 8px 24px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 700;
    }
    
    .pricing-badge.popular {
        background: var(--primary);
        color: white;
    }
    
    .pricing-badge.best-value {
        background: var(--success);
        color: white;
    }
    
    .free-trial-label {
        position: absolute;
        top: 25px;
        right: 20px;
        background: linear-gradient(135deg, #ff6b35 0%, #e85a2b 100%);
        color: white;
        padding: 6px 12px;
        border-radius: 15px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        animation: pulse 2s infinite;
        z-index: 10;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .pricing-title {
        font-size: 1.6rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 10px;
    }
    
    .pricing-amount {
        font-size: 3rem;
        font-weight: 900;
        color: var(--primary);
        margin-bottom: 8px;
    }
    
    .pricing-period {
        color: var(--gray-600);
        margin-bottom: 30px;
        font-size: 1.1rem;
    }
    
    .pricing-features {
        list-style: none;
        margin-bottom: 30px;
        text-align: left;
    }
    
    .pricing-features li {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        font-size: 1rem;
    }
    
    .pricing-features li::before {
        content: '✓';
        color: var(--success);
        font-weight: bold;
        margin-right: 12px;
        flex-shrink: 0;
    }
    
    .btn-pricing {
        width: 100%;
        background: var(--gray-600);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-pricing:hover {
        background: var(--gray-700);
        transform: translateY(-2px);
    }
    
    .featured .btn-pricing {
        background: var(--primary);
    }
    
    .featured .btn-pricing:hover {
        background: #166975;
    }
    
    /* Testimonials */
    .testimonials-section {
        background: var(--gray-50);
        padding: 80px 80px;
        overflow: hidden;
    }
    
    .testimonials-carousel {
        position: relative;
        max-width: 100%;
        overflow: hidden;
        margin-top: 60px;
    }
    
    .testimonials-track {
        display: flex;
        gap: 40px;
        transition: transform 0.5s ease-in-out;
        will-change: transform;
    }
    
    .testimonial {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        position: relative;
        flex: 0 0 400px;
        min-height: 280px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    
    .testimonial::before {
        content: '"';
        font-size: 6rem;
        color: var(--primary);
        opacity: 0.1;
        position: absolute;
        top: 10px;
        left: 20px;
        font-family: serif;
    }
    
    .testimonial-text {
        font-size: 1.1rem;
        color: var(--gray-700);
        margin-bottom: 24px;
        font-style: italic;
        line-height: 1.7;
        position: relative;
        z-index: 2;
        flex-grow: 1;
    }
    
    .testimonial-author {
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
        font-size: 1.1rem;
    }
    
    .testimonial-role {
        color: var(--gray-600);
        font-size: 0.95rem;
    }
    
    .testimonial-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        margin-top: 40px;
    }
    
    .testimonial-btn {
        background: var(--gray-200);
        border: none;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        color: var(--gray-600);
    }
    
    .testimonial-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(31, 122, 140, 0.3);
    }
    
    .testimonial-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .testimonial-dots {
        display: flex;
        gap: 12px;
    }
    
    .testimonial-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--gray-300);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .testimonial-dot.active {
        background: var(--primary);
        transform: scale(1.2);
    }
    
    .testimonial-auto-scroll {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 20px;
        justify-content: center;
    }
    
    .auto-scroll-toggle {
        background: none;
        border: 2px solid var(--gray-300);
        border-radius: 20px;
        padding: 8px 16px;
        font-size: 0.9rem;
        color: var(--gray-600);
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .auto-scroll-toggle.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    
    /* Login Form */
    .login-logo {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .login-logo img {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        box-shadow: 0 8px 25px rgba(31, 122, 140, 0.2);
    }
    
    .login-title {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--dark);
        text-align: center;
        margin-bottom: 12px;
        line-height: 1.2;
    }
    
    .login-subtitle {
        color: var(--gray-600);
        text-align: center;
        margin-bottom: 40px;
        font-size: 1.1rem;
        line-height: 1.5;
    }
    
    .form-group {
        margin-bottom: 30px;
    }
    
    .form-label {
        font-weight: 700;
        color: var(--gray-700);
        margin-bottom: 10px;
        display: block;
        font-size: 1rem;
    }
    
    .form-control {
        width: 100%;
        border: 2px solid var(--gray-200);
        border-radius: 15px;
        padding: 18px 20px;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        background: var(--gray-50);
    }
    
    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(31, 122, 140, 0.1);
        outline: none;
        background: white;
    }
    
    .btn-proceed {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        border: none;
        border-radius: 15px;
        padding: 20px;
        font-size: 1.2rem;
        font-weight: 700;
        color: white;
        width: 100%;
        transition: all 0.3s ease;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-proceed:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(31, 122, 140, 0.3);
        color: white;
    }
    
    .legal-text {
        text-align: center;
        font-size: 0.9rem;
        color: var(--gray-500);
        margin-top: 25px;
        line-height: 1.6;
    }
    
    .legal-text a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    
    .legal-text a:hover {
        text-decoration: underline;
    }
    
    .trial-highlight {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
        padding: 4px 12px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        margin-top: 10px;
        display: inline-block;
    }
    
    /* Footer Styles */
    .footer-section {
        background: var(--gray-900);
        color: var(--gray-300);
        padding: 80px 80px 0;
    }
    
    .footer-content {
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1.5fr 1fr;
        gap: 60px;
        margin-bottom: 60px;
    }
    
    .footer-column h3.footer-title {
        color: white;
        font-size: 1.8rem;
        font-weight: 800;
        margin-bottom: 16px;
    }
    
    .footer-column h4.footer-heading {
        color: white;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .footer-description {
        color: var(--gray-400);
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 30px;
    }
    
    .footer-social {
        display: flex;
        gap: 16px;
    }
    
    .social-link {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        background: var(--gray-800);
        color: var(--gray-400);
        border-radius: 12px;
        transition: all 0.3s ease;
        text-decoration: none;
    }
    
    .social-link:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }
    
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-links li {
        margin-bottom: 16px;
    }
    
    .footer-link {
        color: var(--gray-400);
        text-decoration: none;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .footer-link:hover {
        color: var(--primary);
        text-decoration: none;
        transform: translateX(4px);
    }
    
    .link-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }
    
    .footer-compliance {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .compliance-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }
    
    .compliance-icon {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }
    
    .compliance-link {
        color: var(--gray-400);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }
    
    .compliance-link:hover {
        color: var(--primary);
        text-decoration: none;
    }
    
    .footer-bottom {
        border-top: 1px solid var(--gray-800);
        padding: 30px 0;
    }
    
    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .footer-copyright p {
        color: var(--gray-500);
        font-size: 0.9rem;
        margin: 0;
    }
    
    .footer-legal {
        display: flex;
        gap: 24px;
    }
    
    .legal-link {
        color: var(--gray-500);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }
    
    .legal-link:hover {
        color: var(--primary);
        text-decoration: none;
    }
    
    /* Language Switcher */
    .language-switcher {
        position: absolute;
        top: 30px;
        right: 30px;
        z-index: 100;
    }
    
    .language-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .language-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        padding: 12px 16px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        min-width: 120px;
        justify-content: space-between;
    }
    
    .language-btn:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-2px);
    }
    
    .language-dropdown-content {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        min-width: 200px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        border-radius: 15px;
        z-index: 1000;
        border: 1px solid var(--gray-200);
        overflow: hidden;
        margin-top: 8px;
    }
    
    .language-dropdown.active .language-dropdown-content {
        display: block;
        animation: fadeInDown 0.3s ease;
    }
    
    .language-option {
        padding: 12px 16px;
        color: var(--gray-700);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        transition: all 0.3s ease;
        border-bottom: 1px solid var(--gray-100);
    }
    
    .language-option:last-child {
        border-bottom: none;
    }
    
    .language-option:hover {
        background: var(--gray-50);
        color: var(--primary);
        text-decoration: none;
    }
    
    .language-option.active {
        background: var(--accent);
        color: var(--primary);
        font-weight: 700;
    }
    
    .flag-icon {
        width: 24px;
        height: 18px;
        border-radius: 3px;
        display: inline-block;
        background-size: cover;
        background-position: center;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .flag-en { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjE4IiBmaWxsPSIjMDA1MkZGIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNMCAwaDE2djEySDBWMHoiIGZpbGw9IiNGRkZGRkYiLz4KPHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTIiPgo8cmVjdCB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIGZpbGw9IiMwMDUyRkYiLz4KPC9zdmc+Cjwvc3ZnPgo='); }
    .flag-es { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjE4IiBmaWxsPSIjRkZEOTAwIi8+CjxyZWN0IHk9IjMiIHdpZHRoPSIyNCIgaGVpZ2h0PSIzIiBmaWxsPSIjRkYwMDAwIi8+CjxyZWN0IHk9IjEyIiB3aWR0aD0iMjQiIGhlaWdodD0iMyIgZmlsbD0iI0ZGMDAwMCIvPgo8L3N2Zz4K'); }
    .flag-pt { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjE4IiBmaWxsPSIjRkZGRkZGIi8+CjxyZWN0IHdpZHRoPSIxMCIgaGVpZ2h0PSIxOCIgZmlsbD0iIzAwNTJGRiIvPgo8cmVjdCB4PSIxNCIgd2lkdGg9IjEwIiBoZWlnaHQ9IjE4IiBmaWxsPSIjMDBGRjAwIi8+Cjwvc3ZnPgo='); }
    .flag-hi { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjYiIGZpbGw9IiNGRjk5MDAiLz4KPHJlY3QgeT0iNiIgd2lkdGg9IjI0IiBoZWlnaHQ9IjYiIGZpbGw9IiNGRkZGRkYiLz4KPHJlY3QgeT0iMTIiIHdpZHRoPSIyNCIgaGVpZ2h0PSI2IiBmaWxsPSIjMDA1MkZGIi8+Cjwvc3ZnPgo='); }
    .flag-ar { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjI0IiBoZWlnaHQ9IjYiIGZpbGw9IiMwMDUyRkYiLz4KPHJlY3QgeT0iNiIgd2lkdGg9IjI0IiBoZWlnaHQ9IjYiIGZpbGw9IiNGRkZGRkYiLz4KPHJlY3QgeT0iMTIiIHdpZHRoPSIyNCIgaGVpZ2h0PSI2IiBmaWxsPSIjRkYwMDAwIi8+Cjwvc3ZnPgo='); }
    .flag-fr { background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMTgiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgiIGhlaWdodD0iMTgiIGZpbGw9IiMwMDUyRkYiLz4KPHJlY3QgeD0iOCIgd2lkdGg9IjgiIGhlaWdodD0iMTgiIGZpbGw9IiNGRkZGRkYiLz4KPHJlY3QgeD0iMTYiIHdpZHRoPSI4IiBoZWlnaHQ9IjE4IiBmaWxsPSIjRkYwMDAwIi8+Cjwvc3ZnPgo='); }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Mobile responsive for language switcher */
    @media (max-width: 968px) {
        .language-switcher {
            top: 20px;
            right: 20px;
        }
        
        .language-btn {
            padding: 10px 12px;
            font-size: 0.85rem;
            min-width: 100px;
        }
        
        .language-dropdown-content {
            min-width: 180px;
        }
    }
    
    /* Mobile Responsive */
    @media (max-width: 1200px) {
        .sticky-login {
            width: 400px;
        }
        .content-section {
            margin-right: 400px;
        }
    }
    
    @media (max-width: 968px) {
        .modern-layout {
            flex-direction: column;
        }
        
        .sticky-login {
            position: relative;
            width: 100%;
            height: auto;
            order: 1;
            padding: 40px 30px;
        }
        
        .content-section {
            margin-right: 0;
            order: 2;
        }
        
        .hero-section,
        .track-record,
        .problems-solutions,
        .features-section,
        .industries-section,
        .pricing-section,
        .testimonials-section {
            padding: 60px 40px;
        }
        
        .footer-section {
            padding: 60px 40px 0;
        }
        
        .footer-grid {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .testimonials-carousel {
            margin-top: 40px;
        }
        
        .testimonial {
            flex: 0 0 320px;
            min-height: 250px;
            padding: 30px;
        }
        
        .hero-title {
            font-size: 3rem;
        }
        
        .section-title {
            font-size: 2.5rem;
        }
        
        .problems-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .industries-grid {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        .pricing-card.featured {
            transform: none;
        }
        
        .hero-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .btn-hero-primary,
        .btn-hero-secondary {
            width: 280px;
        }
    }
    
    @media (max-width: 640px) {
        .hero-section,
        .track-record,
        .problems-solutions,
        .features-section,
        .industries-section,
        .pricing-section,
        .testimonials-section {
            padding: 40px 20px;
        }
        
        .footer-section {
            padding: 40px 20px 0;
        }
        
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 40px;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
        }
        
        .footer-legal {
            justify-content: center;
        }
        
        .testimonials-section {
            padding: 40px 20px;
        }
        
        .testimonial {
            flex: 0 0 280px;
            min-height: 220px;
            padding: 25px;
        }
        
        .testimonial-controls {
            margin-top: 30px;
            gap: 15px;
        }
        
        .testimonial-btn {
            width: 40px;
            height: 40px;
        }
        
        .hero-title {
            font-size: 2.5rem;
        }
        
        .section-title {
            font-size: 2rem;
        }
        
        .testimonials-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    /* Corporate Details Section Styles */
    .corporate-details-section {
        background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
        padding: 80px 60px;
    }
    
    .corporate-hero {
        text-align: center;
        margin-bottom: 80px;
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .corporate-hero-title {
        font-size: 2.8rem;
        font-weight: 900;
        color: var(--primary);
        margin-bottom: 30px;
        line-height: 1.2;
    }
    
    .corporate-hero-subtitle {
        font-size: 1.3rem;
        color: var(--gray-600);
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .corporate-hero-support {
        font-size: 1.1rem;
        color: var(--gray-500);
        margin-bottom: 40px;
        line-height: 1.6;
    }
    
    .corporate-hero-cta {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-corporate-primary {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 18px 35px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-corporate-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(31, 122, 140, 0.4);
    }
    
    .btn-corporate-secondary {
        background: transparent;
        color: var(--primary);
        padding: 18px 35px;
        border: 2px solid var(--primary);
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-corporate-secondary:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-3px);
    }
    
    .corporate-urgency {
        background: white;
        padding: 60px;
        border-radius: 20px;
        margin-bottom: 80px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        text-align: center;
    }
    
    .urgency-quote {
        font-size: 1.4rem;
        font-style: italic;
        color: var(--primary);
        margin-bottom: 30px;
        font-weight: 600;
        border-left: 4px solid var(--secondary);
        padding-left: 30px;
        text-align: left;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .urgency-points {
        list-style: none;
        padding: 0;
        margin: 30px 0;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }
    
    .urgency-points li {
        font-size: 1.1rem;
        color: var(--gray-700);
        margin-bottom: 15px;
        padding-left: 30px;
        position: relative;
        text-align: left;
    }
    
    .urgency-points li::before {
        content: '•';
        color: var(--secondary);
        font-size: 1.5rem;
        position: absolute;
        left: 0;
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
        margin-top: 20px;
        transition: all 0.3s ease;
    }
    
    .btn-urgency:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 187, 51, 0.4);
    }
    
    .corporate-transformation {
        margin-bottom: 80px;
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
        font-size: 3rem;
        margin-bottom: 20px;
    }
    
    .transformation-card h3 {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 15px;
    }
    
    .transformation-card p {
        color: var(--gray-600);
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .transformation-card ul {
        list-style: none;
        padding: 0;
        text-align: left;
    }
    
    .transformation-card li {
        color: var(--gray-700);
        margin-bottom: 8px;
        padding-left: 20px;
        position: relative;
    }
    
    .transformation-card li::before {
        content: '✓';
        color: var(--success);
        font-weight: bold;
        position: absolute;
        left: 0;
    }
    
    .corporate-package-details {
        margin-bottom: 80px;
    }
    
    .package-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 40px;
        margin-top: 50px;
    }
    
    .package-detail-card {
        background: white;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .package-detail-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 20px;
    }
    
    .package-detail-card p {
        color: var(--gray-600);
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .package-detail-card ul {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    
    .package-detail-card li {
        color: var(--gray-700);
        margin-bottom: 10px;
        padding-left: 25px;
        position: relative;
    }
    
    .package-detail-card li::before {
        content: '▸';
        color: var(--primary);
        font-weight: bold;
        position: absolute;
        left: 0;
    }
    
    .package-detail-card .highlight {
        background: var(--secondary);
        color: var(--primary);
        padding: 15px;
        border-radius: 10px;
        font-weight: 600;
        margin-top: 20px;
    }
    
    .package-detail-card .note {
        font-style: italic;
        color: var(--gray-500);
        font-size: 0.9rem;
    }
    
    .corporate-roi {
        background: white;
        padding: 60px;
        border-radius: 20px;
        margin-bottom: 80px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .roi-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 40px;
        margin: 50px 0;
    }
    
    .roi-stat {
        text-align: center;
        padding: 30px;
        border-radius: 15px;
        background: linear-gradient(135deg, #f8f9fa 0%, white 100%);
    }
    
    .roi-number {
        font-size: 3rem;
        margin-bottom: 15px;
    }
    
    .roi-stat h3 {
        font-size: 2.5rem;
        font-weight: 900;
        color: var(--primary);
        margin-bottom: 10px;
    }
    
    .roi-stat p {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 10px;
    }
    
    .roi-stat span {
        color: var(--gray-500);
        font-size: 0.95rem;
    }
    
    .roi-credibility {
        font-size: 1.3rem;
        font-style: italic;
        color: var(--primary);
        text-align: center;
        margin-top: 40px;
        font-weight: 600;
        border: 3px solid var(--secondary);
        padding: 30px;
        border-radius: 15px;
    }
    
    .corporate-trust {
        margin-bottom: 80px;
    }
    
    .trust-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
        margin-top: 50px;
    }
    
    .trust-feature {
        display: flex;
        gap: 20px;
        padding: 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .trust-icon {
        color: var(--success);
        font-size: 1.5rem;
        font-weight: bold;
        min-width: 30px;
    }
    
    .trust-feature h4 {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }
    
    .trust-feature p {
        color: var(--gray-600);
        line-height: 1.6;
    }
    
    .corporate-pricing-section {
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 60px;
        border-radius: 20px;
        margin-bottom: 80px;
        text-align: center;
    }
    
    .corporate-pricing-section h2 {
        color: white;
    }
    
    .corporate-pricing-content {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .corporate-pricing-content p {
        font-size: 1.1rem;
        margin-bottom: 20px;
    }
    
    .corporate-pricing-content ul {
        list-style: none;
        padding: 0;
        margin: 30px 0;
        text-align: left;
    }
    
    .corporate-pricing-content li {
        margin-bottom: 12px;
        padding-left: 25px;
        position: relative;
    }
    
    .corporate-pricing-content li::before {
        content: '✓';
        color: var(--secondary);
        font-weight: bold;
        position: absolute;
        left: 0;
    }
    
    .pricing-message {
        background: rgba(255, 255, 255, 0.1);
        padding: 30px;
        border-radius: 15px;
        font-size: 1.2rem;
        font-style: italic;
        margin: 30px 0;
        border-left: 4px solid var(--secondary);
    }
    
    .btn-corporate-proposal {
        background: var(--secondary);
        color: var(--primary);
        padding: 18px 35px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
    }
    
    .btn-corporate-proposal:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 187, 51, 0.4);
    }
    
    .corporate-final-cta {
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 80px 60px;
        border-radius: 20px;
        text-align: center;
    }
    
    .final-cta-title {
        font-size: 2.5rem;
        font-weight: 900;
        margin-bottom: 20px;
        color: white;
    }
    
    .final-cta-subtitle {
        font-size: 1.3rem;
        margin-bottom: 40px;
        color: #bdc3c7;
    }
    
    .final-cta-buttons {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .btn-corporate-meeting {
        background: linear-gradient(135deg, var(--secondary) 0%, #e6a82e 100%);
        color: var(--primary);
        padding: 20px 40px;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-corporate-meeting:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(255, 187, 51, 0.4);
    }
    
    .btn-corporate-consultant {
        background: transparent;
        color: white;
        padding: 20px 40px;
        border: 2px solid white;
        border-radius: 12px;
        font-weight: 700;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-corporate-consultant:hover {
        background: white;
        color: var(--primary);
        transform: translateY(-3px);
    }
    
    /* Responsive Design for Corporate Section */
    @media (max-width: 768px) {
        .corporate-details-section {
            padding: 40px 20px;
        }
        
        .corporate-hero-title {
            font-size: 2.2rem;
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
        
        .corporate-pricing-section {
            padding: 40px 20px;
        }
        
        .final-cta-buttons {
            flex-direction: column;
            align-items: center;
        }
        
        .final-cta-title {
            font-size: 1.8rem;
        }
    }

    /* AI vs Human Comparison Styles */
    .comparison-section {
        padding: 80px 0;
        background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    }

    .comparison-table {
        max-width: 1000px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .comparison-header {
        display: grid;
        grid-template-columns: 200px 1fr 1fr;
        background: linear-gradient(135deg, var(--primary) 0%, #166975 100%);
        color: white;
        padding: 30px;
    }

    .comparison-header h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 10px 0 5px;
        color: white;
    }

    .agent-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .winner-badge {
        background: var(--secondary);
        color: var(--primary);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        margin-top: 5px;
        display: inline-block;
    }

    .comparison-ai, .comparison-human {
        text-align: center;
    }

    .comparison-row {
        display: grid;
        grid-template-columns: 200px 1fr 1fr;
        border-bottom: 1px solid #eee;
        min-height: 80px;
        align-items: center;
    }

    .comparison-row:nth-child(even) {
        background: #f8f9fa;
    }

    .comparison-category {
        padding: 20px;
        font-weight: 600;
        color: var(--primary);
        border-right: 1px solid #eee;
    }

    .comparison-ai, .comparison-human {
        padding: 20px;
        text-align: center;
    }

    .comparison-ai.winner {
        background: linear-gradient(135deg, #e8f5e8 0%, #f0f9f0 100%);
        position: relative;
    }

    .comparison-ai.winner::before {
        content: '🏆';
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.2rem;
    }

    .comparison-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 5px;
    }

    .comparison-ai.winner .comparison-value {
        color: var(--success);
    }

    .comparison-detail {
        font-size: 0.9rem;
        color: var(--gray-600);
        line-height: 1.4;
    }

    .comparison-footer {
        background: linear-gradient(135deg, var(--secondary) 0%, #e6a82e 100%);
        color: var(--primary);
        padding: 30px;
        text-align: center;
    }

    .comparison-summary h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: var(--primary);
    }

    .comparison-summary p {
        font-size: 1.1rem;
        margin: 0;
        color: var(--primary);
        opacity: 0.9;
    }

    /* Mobile responsive for comparison table */
    @media (max-width: 768px) {
        .comparison-header {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 20px;
        }

        .comparison-row {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 15px;
        }

        .comparison-category {
            border-right: none;
            border-bottom: 1px solid #eee;
            background: var(--primary);
            color: white;
            font-size: 1.1rem;
        }

        .comparison-ai, .comparison-human {
            border-bottom: 1px solid #eee;
        }

        .comparison-ai.winner::before {
            top: 5px;
            right: 5px;
        }
    }
</style>

<div class="modern-layout">
    <!-- Main Content Section (Scrollable) -->
    <div class="content-section">
        
        <!-- Hero Section -->
        <section class="hero-section">
            <!-- Language Switcher -->
            <div class="language-switcher">
                <div class="language-dropdown" id="languageDropdown">
                    <button class="language-btn" id="languageBtn">
                        <span class="flag-icon flag-en" id="currentFlag"></span>
                        <span id="currentLang">English</span>
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    <div class="language-dropdown-content" id="languageOptions">
                        <a href="#" class="language-option active" data-lang="en" data-flag="flag-en">
                            <span class="flag-icon flag-en"></span>
                            <span>English</span>
                        </a>
                        <a href="#" class="language-option" data-lang="es" data-flag="flag-es">
                            <span class="flag-icon flag-es"></span>
                            <span>Español</span>
                        </a>
                        <a href="#" class="language-option" data-lang="pt" data-flag="flag-pt">
                            <span class="flag-icon flag-pt"></span>
                            <span>Português</span>
                        </a>
                        <a href="#" class="language-option" data-lang="hi" data-flag="flag-hi">
                            <span class="flag-icon flag-hi"></span>
                            <span>हिंदी</span>
                        </a>
                        <a href="#" class="language-option" data-lang="ar" data-flag="flag-ar">
                            <span class="flag-icon flag-ar"></span>
                            <span>العربية</span>
                        </a>
                        <a href="#" class="language-option" data-lang="fr" data-flag="flag-fr">
                            <span class="flag-icon flag-fr"></span>
                            <span>Français</span>
                        </a>
                        <a href="#" class="language-option" data-lang="sw" data-flag="flag-sw">
                            <span class="flag-icon flag-sw"></span>
                            <span>Kiswahili</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="hero-content">
                <div class="hero-icon">
                    <svg width="60" height="60" fill="white" viewBox="0 0 24 24">
                        <path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zm-2 12H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                    </svg>
                </div>
                
                <h1 class="hero-title fade-in">
                    Hi, I'm your new AI Sales Agent
                </h1>
                
                <p class="hero-subtitle fade-in">
                    You don't have to chat all day to make sales. I talk to your customers, answer their questions, negotiate offers, and qualify leads automatically — even when you're offline. Let me handle the conversations while you handle the growth
                </p>
                
                <div class="hero-buttons fade-in">
                    <a href="#login" class="btn-hero-primary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        Meet Your New Sales Rep
                    </a>
                    <a href="#calculator" class="btn-hero-secondary">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                        </svg>
                        See How Much I'll Earn You
                    </a>
                </div>
                
                <div class="trust-indicators fade-in">
                    Wherever your customers are and whatever language they speak — Kiswahili, English, Arabic, Spanish, Hausa, or local dialects — I'm ready to serve them instantly. Available 24/7/365. Just give me your product, and I'll make sure no sale is ever lost again.
                </div>
            </div>
        </section>

        <!-- AI vs Human Comparison Section -->
        <section class="comparison-section">
            <h2 class="section-title">AI Sales Agent vs Human Sales Agent</h2>
            <p style="font-size: 1.2rem; color: var(--gray-600); max-width: 700px; margin: 0 auto 50px; text-align: center;">
                See why businesses are choosing AI over traditional sales teams
            </p>
            
            <div class="comparison-table">
                <div class="comparison-header">
                    <div class="comparison-category"></div>
                    <div class="comparison-ai">
                        <div class="agent-icon ai-icon">🤖</div>
                        <h3>AI Sales Agent</h3>
                        <span class="winner-badge">WINNER</span>
                    </div>
                    <div class="comparison-human">
                        <div class="agent-icon human-icon">👨‍💼</div>
                        <h3>Human Sales Agent</h3>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Availability</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">24/7/365</div>
                        <div class="comparison-detail">Never sleeps, always ready</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">8 hours/day</div>
                        <div class="comparison-detail">Weekends, holidays, sick days off</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Cost per Month</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">$29 - $399</div>
                        <div class="comparison-detail">Only pay for messages used</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">$2,000 - $8,000</div>
                        <div class="comparison-detail">Salary + benefits + training</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Response Time</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">Instant</div>
                        <div class="comparison-detail">Responds in seconds</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">Minutes to Hours</div>
                        <div class="comparison-detail">Depends on availability</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Languages Supported</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">50+ Languages</div>
                        <div class="comparison-detail">Kiswahili, English, Arabic, Spanish, and more</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">1-3 Languages</div>
                        <div class="comparison-detail">Limited to personal knowledge</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Consistency</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">100% Consistent</div>
                        <div class="comparison-detail">Same quality every interaction</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">Variable</div>
                        <div class="comparison-detail">Mood, energy, experience dependent</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Scaling</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">Unlimited</div>
                        <div class="comparison-detail">Handle 1000s of customers simultaneously</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">1-to-1</div>
                        <div class="comparison-detail">Can only talk to one customer at a time</div>
                    </div>
                </div>
                
                <div class="comparison-row">
                    <div class="comparison-category">
                        <strong>Training Required</strong>
                    </div>
                    <div class="comparison-ai winner">
                        <div class="comparison-value">None</div>
                        <div class="comparison-detail">Ready to sell immediately</div>
                    </div>
                    <div class="comparison-human">
                        <div class="comparison-value">Weeks to Months</div>
                        <div class="comparison-detail">Product training, sales training, onboarding</div>
                    </div>
                </div>
                
                <div class="comparison-footer">
                    <div class="comparison-summary">
                        <h3>The Clear Choice</h3>
                        <p>AI Sales Agent wins in every category that matters for your business growth. Save money, increase availability, and scale without limits.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Problems & Solutions -->
        <section class="problems-solutions">
            <h2 class="section-title">From Challenges to Growth</h2>
            
            <div class="problems-grid">
                <div class="problem-card">
                    <h3 class="card-title">Your Current Sales Challenges</h3>
                    <ul class="problem-list">
                        <li>Missing leads during off-hours</li>
                        <li>Inconsistent follow-up timing</li>
                        <li>Overwhelmed sales team</li>
                        <li>Lost deals due to slow response</li>
                        <li>Manual qualification process</li>
                        <li>No weekend/holiday coverage</li>
                    </ul>
                </div>
                
                <div class="solution-card">
                    <h3 class="card-title">How I Solve Them for You</h3>
                    <ul class="solution-list">
                        <li>I never miss a lead - 24/7 availability</li>
                        <li>Instant, consistent professional follow-ups</li>
                        <li>I handle the heavy lifting for your team</li>
                        <li>Sub-60-second response times globally</li>
                        <li>AI-powered instant lead scoring</li>
                        <li>Working while you rest and recharge</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Core Skills Section -->
        <section class="features-section">
            <h2 class="section-title">My Core Sales Skills</h2>
            <p style="font-size: 1.2rem; color: var(--gray-600); max-width: 700px; margin: 0 auto 40px; text-align: center;">
                I bring the expertise of a senior sales professional with the consistency of AI automation.
            </p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎯</div>
                    <h3 class="feature-title">Expert Lead Qualification</h3>
                    <p class="feature-desc">I instantly assess every prospect using 15+ proven sales indicators, scoring them by purchase intent so you focus on the hottest opportunities first.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🤝</div>
                    <h3 class="feature-title">Professional Deal Closing</h3>
                    <p class="feature-desc">I handle complete sales cycles from first contact to signed contracts, negotiating prices within your guidelines using industry best practices.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">🌍</div>
                    <h3 class="feature-title">Global 24/7 Support</h3>
                    <p class="feature-desc">I work across all time zones in multiple languages (English, Spanish, Portuguese, Hindi, Arabic, French), never missing opportunities.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3 class="feature-title">Performance Tracking</h3>
                    <p class="feature-desc">I measure my results like any sales rep - tracking conversions, revenue generated, response times, and ROI to prove my value to your business.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3 class="feature-title">Smart Team Collaboration</h3>
                    <p class="feature-desc">I know exactly when deals need your personal touch, providing complete conversation history when handing off warm, qualified leads to your team.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">💼</div>
                    <h3 class="feature-title">Opportunity Management</h3>
                    <p class="feature-desc">I run systematic outreach campaigns, win back dormant customers, and follow up persistently but professionally when prospects go quiet.</p>
                </div>
            </div>
        </section>

        <!-- Industries Section -->
        <section class="industries-section">
            <h2 class="section-title">Industries Where I Excel</h2>
            <p style="font-size: 1.2rem; color: var(--gray-600); max-width: 700px; margin: 0 auto 40px; text-align: center;">
                I've specialized in diverse sectors, adapting my approach to each industry's unique needs.
            </p>
            
            <div class="industries-grid">
                <div class="industry-card">
                    <div class="industry-icon">🏦</div>
                    <h3 class="industry-title">Financial Services</h3>
                    <p class="industry-desc">I've helped banks automate loan applications and customer onboarding with proven compliance.</p>
                </div>
                
                <div class="industry-card">
                    <div class="industry-icon">🎓</div>
                    <h3 class="industry-title">Education</h3>
                    <p class="industry-desc">I handle student inquiries, course enrollment, and parent communications expertly across institutions.</p>
                </div>
                
                <div class="industry-card">
                    <div class="industry-icon">🛒</div>
                    <h3 class="industry-title">E-commerce</h3>
                    <p class="industry-desc">I recommend products, support orders, and recover abandoned purchases with personalized follow-ups.</p>
                </div>
                
                <div class="industry-card">
                    <div class="industry-icon">💼</div>
                    <h3 class="industry-title">Professional Services</h3>
                    <p class="industry-desc">I book appointments, schedule consultations, and manage follow-ups flawlessly for service businesses.</p>
                </div>
                
                <div class="industry-card">
                    <div class="industry-icon">🏥</div>
                    <h3 class="industry-title">Healthcare</h3>
                    <p class="industry-desc">I handle patient inquiries, appointment scheduling, and follow-up care communications professionally.</p>
                </div>
                
                <div class="industry-card">
                    <div class="industry-icon">🏠</div>
                    <h3 class="industry-title">Real Estate</h3>
                    <p class="industry-desc">I qualify prospects, schedule viewings, and nurture leads through complex purchase decisions.</p>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="pricing-section">
            <h2 class="section-title">Simple, Transparent Pricing</h2>
            <p style="font-size: 1.2rem; color: var(--gray-600); max-width: 700px; margin: 0 auto 10px; text-align: center;">
                Only pay for the AI messages you use. Higher plans include more AI sales messages at a lower cost per message.
            </p>
            <p style="font-size: 1rem; color: var(--gray-500); max-width: 600px; margin: 0 auto 20px; text-align: center;">
                SafariChat helps you close deals — every AI message is a real sales interaction that moves your customers toward buying.
            </p>
            
            <!-- Currency Switcher -->
            <div class="currency-switcher">
                <span class="currency-switcher-label">Choose your currency:</span>
                <div class="currency-dropdown" id="currencyDropdown">
                    <button class="currency-btn" id="currencyBtn">
                        <span class="currency-flag flag-tsh" id="currentCurrencyFlag"></span>
                        <span id="currentCurrency">TSh</span>
                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 10l5 5 5-5z"/>
                        </svg>
                    </button>
                    <div class="currency-dropdown-content" id="currencyOptions">
                        <a href="#" class="currency-option active" data-currency="TSh" data-flag="flag-tsh" data-rate="1">
                            <span class="currency-flag flag-tsh"></span>
                            <span>Tanzanian Shilling (TSh)</span>
                        </a>
                        <a href="#" class="currency-option" data-currency="USD" data-flag="flag-usd" data-rate="0.0004">
                            <span class="currency-flag flag-usd"></span>
                            <span>US Dollar (USD)</span>
                        </a>
                        <a href="#" class="currency-option" data-currency="NGN" data-flag="flag-ngn" data-rate="0.61">
                            <span class="currency-flag flag-ngn"></span>
                            <span>Nigerian Naira (NGN)</span>
                        </a>
                        <a href="#" class="currency-option" data-currency="BRL" data-flag="flag-brl" data-rate="0.002">
                            <span class="currency-flag flag-brl"></span>
                            <span>Brazilian Real (BRL)</span>
                        </a>
                        <a href="#" class="currency-option" data-currency="INR" data-flag="flag-inr" data-rate="0.034">
                            <span class="currency-flag flag-inr"></span>
                            <span>Indian Rupee (INR)</span>
                        </a>
                        <a href="#" class="currency-option" data-currency="EUR" data-flag="flag-eur" data-rate="0.00037">
                            <span class="currency-flag flag-eur"></span>
                            <span>Euro (EUR)</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="exchange-rate-info" id="exchangeRateInfo">
                <span>Exchange rate: 1 TSh = <span class="rate">1.00 TSh</span> | Last updated: <span id="lastUpdated">Now</span></span>
            </div>
            
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="pricing-badge">Starter</div>
                    <div class="free-trial-label">🎁 3 Days Free Trial</div>
                    <h3 class="pricing-title">Winga</h3>
                    <div class="pricing-amount" data-base-price="49700"><span class="currency-symbol">TSh</span> <span class="price-value">49,700</span></div>
                    <div class="pricing-period">497 AI messages/month • <span class="currency-symbol">TSh</span> <span class="per-message-price">100</span>/message</div>
                    <ul class="pricing-features">
                        <li>Perfect for startups & small shops</li>
                        <li>Basic sales conversations</li>
                        <li>1 WhatsApp instance</li>
                        <li>Email support</li>
                        <li>Multi-language support</li>
                        <li>Real-time analytics</li>
                    </ul>
                    <button class="btn-pricing">Start Working Now</button>
                </div>
                
                <div class="pricing-card featured">
                    <div class="pricing-badge popular">⭐ Most Popular</div>
                    <div class="free-trial-label">🎁 3 Days Free Trial</div>
                    <h3 class="pricing-title">Pro</h3>
                    <div class="pricing-amount" data-base-price="93700"><span class="currency-symbol">TSh</span> <span class="price-value">93,700</span></div>
                    <div class="pricing-period">1,041 AI messages/month • <span class="currency-symbol">TSh</span> <span class="per-message-price">90</span>/message</div>
                    <ul class="pricing-features">
                        <li>Growing businesses & schools</li>
                        <li>Advanced conversation handling</li>
                        <li>2 WhatsApp instances</li>
                        <li>Phone + email support</li>
                        <li>CRM integration</li>
                        <li>Performance reporting</li>
                    </ul>
                    <button class="btn-pricing">Start Working Now</button>
                </div>
                
                <div class="pricing-card">
                    <div class="pricing-badge best-value">🏆 Best Value</div>
                    <div class="free-trial-label">🎁 3 Days Free Trial</div>
                    <h3 class="pricing-title">Enterprise</h3>
                    <div class="pricing-amount" data-base-price="123600"><span class="currency-symbol">TSh</span> <span class="price-value">123,600</span></div>
                    <div class="pricing-period">1,545 AI messages/month • <span class="currency-symbol">TSh</span> <span class="per-message-price">80</span>/message</div>
                    <ul class="pricing-features">
                        <li>High-volume organizations</li>
                        <li>Full sales automation</li>
                        <li>5 WhatsApp instances</li>
                        <li>Priority phone support</li>
                        <li>Custom training</li>
                        <li>2-hour SLA</li>
                    </ul>
                    <button class="btn-pricing">Start Working Now</button>
                </div>
            </div>
            
            <!-- Corporate Package Section -->
            <div class="corporate-package-section">
                <div class="corporate-badge">
                    🏢 ENTERPRISE SOLUTION
                </div>
                <h3 class="corporate-title">Unlimited / Corporate</h3>
                <p class="corporate-description">
                    <strong>Custom pricing</strong> for banks, telecoms, big schools, government, ecommerce platforms.
                </p>
                <div class="corporate-features">
                    <div class="corporate-feature">
                        <i class="fas fa-infinity"></i>
                        <span>Unlimited AI messages</span>
                    </div>
                    <div class="corporate-feature">
                        <i class="fas fa-cogs"></i>
                        <span>Custom AI training</span>
                    </div>
                    <div class="corporate-feature">
                        <i class="fas fa-shield-alt"></i>
                        <span>Enterprise security</span>
                    </div>
                    <div class="corporate-feature">
                        <i class="fas fa-users"></i>
                        <span>Team training included</span>
                    </div>
                </div>
                <a href="/corporate" class="btn-corporate">Learn More About Corporate Pricing</a>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials-section">
            <h2 class="section-title">What My Clients Say About Working With Me</h2>
            
            <div class="testimonials-carousel">
                <div class="testimonials-track" id="testimonialsTrack">
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "Since hiring this AI Sales Agent, our loan application responses improved by 40% and we're closing 35% more deals. It handles complex financial conversations better than some of our junior staff."
                        </p>
                        <div>
                            <div class="testimonial-author">Sarah Mwangi</div>
                            <div class="testimonial-role">Operations Director, Premier Bank Tanzania</div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "Our enrollment increased by 60% after implementing the AI agent. Parents get instant responses about courses, fees, and schedules. It's like having our best counselor available 24/7."
                        </p>
                        <div>
                            <div class="testimonial-author">Dr. James Okonkwo</div>
                            <div class="testimonial-role">Principal, Lagos International School</div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "We went from losing weekend sales to capturing every opportunity. The AI agent recovered $50,000 in abandoned cart value in the first month alone. ROI was immediate."
                        </p>
                        <div>
                            <div class="testimonial-author">Maria Santos</div>
                            <div class="testimonial-role">E-commerce Manager, BrazilMart</div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "The AI handles price negotiations brilliantly and knows exactly when to involve our human sales team. Our closing rate improved by 45% in just 3 months."
                        </p>
                        <div>
                            <div class="testimonial-author">Carlos Silva</div>
                            <div class="testimonial-role">CEO, TechSolutions Brasil</div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "We've seen a 40% increase in qualified leads and our response time improved from hours to seconds. Students get instant answers about courses and enrollment."
                        </p>
                        <div>
                            <div class="testimonial-author">Dr. Grace Mwangi</div>
                            <div class="testimonial-role">Director, Nairobi Business Institute</div>
                        </div>
                    </div>
                    
                    <div class="testimonial">
                        <p class="testimonial-text">
                            "Our AI Sales Agent helped us automate 80% of our loan inquiries and increased approvals by 35%. It's like having a senior sales professional working 24/7."
                        </p>
                        <div>
                            <div class="testimonial-author">Ahmed Hassan</div>
                            <div class="testimonial-role">Operations Manager, Mashimoni Credit Bank</div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-controls">
                    <button class="testimonial-btn" id="prevBtn" aria-label="Previous testimonial">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    
                    <div class="testimonial-dots" id="testimonialDots"></div>
                    
                    <button class="testimonial-btn" id="nextBtn" aria-label="Next testimonial">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                            <polyline points="9 6 15 12 9 18"></polyline>
                        </svg>
                    </button>
                </div>
                
                <div class="testimonial-auto-scroll">
                    <button class="auto-scroll-toggle active" id="autoScrollToggle">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Auto-scroll enabled
                    </button>
                </div>
            </div>
        </section>

        <!-- Footer Section -->
        <footer class="footer-section">
            <div class="footer-content">
                <div class="footer-grid">
                    <!-- Company Info -->
                    <div class="footer-column">
                        <h3 class="footer-title">SafariChat</h3>
                        <p class="footer-description">
                            Your personal AI Sales Agent that closes deals 24/7 across Tanzania, Nigeria, Brazil, Indonesia, India, and beyond.
                        </p>
                        <div class="footer-social">
                            <a href="#" class="social-link" aria-label="LinkedIn">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                            </a>
                            <a href="#" class="social-link" aria-label="Twitter">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- For Developers -->
                    <div class="footer-column">
                        <h4 class="footer-heading">For Developers</h4>
                        <ul class="footer-links">
                            <li><a href="/api/docs" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14,2 14,8 20,8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                    <polyline points="10,9 9,9 8,9"/>
                                </svg>
                                API Documentation
                            </a></li>
                            <li><a href="/webhooks" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                                    <polyline points="16,6 12,2 8,6"/>
                                    <line x1="12" y1="2" x2="12" y2="15"/>
                                </svg>
                                Webhook Integration
                            </a></li>
                            <li><a href="/sdk" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <polyline points="16 18 22 12 16 6"/>
                                    <polyline points="8 6 2 12 8 18"/>
                                </svg>
                                SDK & Libraries
                            </a></li>
                        </ul>
                    </div>

                    <!-- Compliance & Security -->
                    <div class="footer-column">
                        <h4 class="footer-heading">Security & Compliance</h4>
                        <ul class="footer-compliance">
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--success)" viewBox="0 0 24 24" class="compliance-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <a href="/compliance/gdpr" class="compliance-link">GDPR Compliant</a>
                            </li>
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--success)" viewBox="0 0 24 24" class="compliance-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <a href="/terms/whatsapp" class="compliance-link">WhatsApp Terms & Conditions</a>
                            </li>
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--success)" viewBox="0 0 24 24" class="compliance-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <a href="/security/encryption" class="compliance-link">Enterprise-level Encryption</a>
                            </li>
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--success)" viewBox="0 0 24 24" class="compliance-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <a href="/security/infrastructure" class="compliance-link">Secure Data Hosting</a>
                            </li>
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--primary)" viewBox="0 0 24 24" class="compliance-icon">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                                <a href="/compliance/certifications" class="compliance-link">SOC2 & ISO Certification (Planned)</a>
                            </li>
                            <li class="compliance-item">
                                <svg width="16" height="16" fill="var(--success)" viewBox="0 0 24 24" class="compliance-icon">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <a href="/security/2fa" class="compliance-link">2FA Authentication</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Company -->
                    <div class="footer-column">
                        <h4 class="footer-heading">Company</h4>
                        <ul class="footer-links">
                            <li><a href="/about" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                About SafariChat
                            </a></li>
                            <li><a href="/careers" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                    <line x1="8" y1="21" x2="16" y2="21"/>
                                    <line x1="12" y1="17" x2="12" y2="21"/>
                                </svg>
                                Careers
                            </a></li>
                            <li><a href="/contact" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                                Contact Us
                            </a></li>
                            <li><a href="/support" class="footer-link">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" class="link-icon">
                                    <path d="M9 12l2 2 4-4"/>
                                    <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/>
                                </svg>
                                Support Center
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Bottom -->
                <div class="footer-bottom">
                    <div class="footer-bottom-content">
                        <div class="footer-copyright">
                            <p>&copy; 2025 SafariChat. All rights reserved.</p>
                        </div>
                        <div class="footer-legal">
                            <a href="/privacy" class="legal-link">Privacy Policy</a>
                            <a href="/terms" class="legal-link">Terms of Service</a>
                            <a href="/cookies" class="legal-link">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Sticky Login Section -->
    <div class="sticky-login" id="login">
        <div class="login-logo">
            <img src="{{ asset(ROOT.'assets/images/safarichat.png')}}" alt="safarichat Logo">
        </div>
        
        <h2 class="login-title">Meet Your New AI Sales Rep</h2>
        <p class="login-subtitle">Enter your WhatsApp number to start working with me</p>

        {{-- Error Messages --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px; margin-bottom: 24px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form method="POST" action="{{ url('setup/otp') }}" id="loginForm">
            @csrf
            
            <div class="form-group">
                <label for="phone2" class="form-label">
                    <i class="fab fa-whatsapp" style="color: #25d366; margin-right: 8px;"></i>
                    WhatsApp Number
                </label>
                <div class="input-group">
                    <input
                        id="phone2"
                        name="email"
                        type="tel"
                        class="form-control @error('email') is-invalid @enderror"
                        placeholder="Enter WhatsApp number"
                        value="{{ old('email') }}"
                        autocomplete="off"
                        required
                        autofocus
                    >
                    <input type="hidden" id="country_code2" name="country_code">
                    <input type="hidden" id="country_name2" name="country_name">
                    <input type="hidden" id="country_abbr2" name="country_abbr">
                </div>
                <span id="error-msg2" class="text-danger" style="font-size: 0.85rem; display: none;"></span>
                @error('email')
                    <div class="invalid-feedback d-block" role="alert">
                        <strong>{{ $message }}</strong>
                    </div>
                @enderror
            </div>

            <button class="btn btn-proceed" type="submit" id="loginButton">
                Start Working Now
                <i class="fas fa-arrow-right ml-2"></i>
                <span class="spinner-border spinner-border-sm ml-2 d-none" role="status" aria-hidden="true" id="loadingSpinner"></span>
            </button>
            
            <div class="legal-text">
                By clicking Start, you agree to our
                <a href="{{ url('/terms-and-conditions') }}" target="_blank">Terms and Conditions</a>.
                <br><strong>3 Days Free Trial</strong> • No setup fees • Cancel anytime
            </div>
        </form>
    </div>
</div>

{{-- External Styles and Scripts for intl-tel-input --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>

<script type="text/javascript">
    // Wait for the intlTelInput library to load
    function initializePhoneValidation() {
        if (typeof window.intlTelInput === 'undefined') {
            console.log('intlTelInput not loaded yet, retrying...');
            setTimeout(initializePhoneValidation, 100);
            return;
        }
        
        validate_phone2();
    }

    // Intl-Tel-Input validation logic
    var validate_phone2 = function () {
        var input = document.querySelector("#phone2"),
            errorMsg = document.querySelector("#error-msg2");

        if (!input) {
            console.error('Phone input element not found');
            return;
        }

        var errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

        try {
            var iti = window.intlTelInput(input, {
                utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js",
                preferredCountries: ['tz'], // Set preferred country to Tanzania
                separateDialCode: true, // Show dial code separately
                initialCountry: "tz", // Default to Tanzania
                autoInsertDialCode: true,
                formatOnDisplay: true,
                nationalMode: false,
                placeholderNumberType: "MOBILE"
            });

            var reset = function () {
                input.classList.remove("is-invalid", "is-valid");
                errorMsg.innerHTML = "";
                errorMsg.style.display = "none";
            };

            // on blur: validate
            input.addEventListener('blur', function () {
                reset();
                if (input.value.trim()) {
                    if (iti.isValidNumber()) {
                        input.classList.add("is-valid");
                        // Update hidden fields
                        var countryData = iti.getSelectedCountryData();
                        var fullNumber = iti.getNumber();
                        
                        document.getElementById("country_code2").value = countryData.dialCode;
                        document.getElementById("country_name2").value = countryData.name;
                        document.getElementById("country_abbr2").value = countryData.iso2;
                        
                        // Update the input value with the full international number
                        input.value = fullNumber;
                    } else {
                        input.classList.add("is-invalid");
                        var errorCode = iti.getValidationError();
                        errorMsg.innerHTML = errorMap[errorCode] || "Invalid number";
                        errorMsg.style.display = "block";
                    }
                }
            });

            // on keyup / change flag: reset
            input.addEventListener('change', reset);
            input.addEventListener('keyup', reset);

            // Handle country change
            input.addEventListener('countrychange', function() {
                reset();
                var countryData = iti.getSelectedCountryData();
                document.getElementById("country_code2").value = countryData.dialCode;
                document.getElementById("country_name2").value = countryData.name;
                document.getElementById("country_abbr2").value = countryData.iso2;
            });

            console.log('Phone validation initialized successfully');
            
        } catch (error) {
            console.error('Error initializing intlTelInput:', error);
            // Fallback: just use regular input validation
            input.addEventListener('blur', function() {
                if (input.value.trim()) {
                    // Basic validation for phone numbers
                    var phoneRegex = /^[\+]?[\d\s\-\(\)]+$/;
                    if (phoneRegex.test(input.value.trim())) {
                        input.classList.add("is-valid");
                        input.classList.remove("is-invalid");
                        errorMsg.style.display = "none";
                    } else {
                        input.classList.add("is-invalid");
                        input.classList.remove("is-valid");
                        errorMsg.innerHTML = "Please enter a valid phone number";
                        errorMsg.style.display = "block";
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        // Initialize phone validation when document is ready
        initializePhoneValidation();

        // Login Button Loading State
        $('#loginForm').on('submit', function(e) {
            const phoneInput = $('#phone2');
            const phoneValue = phoneInput.val().trim();
            
            // Basic validation before submit
            if (!phoneValue) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }

            const loginButton = $('#loginButton');
            const loadingSpinner = $('#loadingSpinner');

            loginButton.attr('disabled', true);
            loadingSpinner.removeClass('d-none');
            
            // Set a timeout to re-enable button in case of network issues
            setTimeout(function() {
                loginButton.attr('disabled', false);
                loadingSpinner.addClass('d-none');
            }, 10000); // 10 seconds timeout
        });
        
        // Smooth scrolling for anchor links (for the content section)
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add animation classes on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card, .stat-card, .industry-card, .pricing-card, .testimonial').forEach(el => {
            observer.observe(el);
        });
        
        // Testimonial Carousel functionality
        const testimonialsTrack = document.getElementById('testimonialsTrack');
        const testimonials = document.querySelectorAll('.testimonial');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const dotsContainer = document.getElementById('testimonialDots');
        const autoScrollToggle = document.getElementById('autoScrollToggle');
        
        let currentIndex = 0;
        let autoScrollInterval;
        let isAutoScrollEnabled = true;
        
        if (testimonialsTrack && testimonials.length > 0) {
            // Calculate how many testimonials can fit on screen
            const getVisibleCount = () => {
                const containerWidth = testimonialsTrack.parentElement.offsetWidth;
                const testimonialWidth = 400 + 40; // testimonial width + gap
                return Math.floor(containerWidth / testimonialWidth) || 1;
            };
            
            // Create dots
            const createDots = () => {
                dotsContainer.innerHTML = '';
                const visibleCount = getVisibleCount();
                const maxIndex = Math.max(0, testimonials.length - visibleCount);
                
                for (let i = 0; i <= maxIndex; i++) {
                    const dot = document.createElement('button');
                    dot.className = `testimonial-dot ${i === 0 ? 'active' : ''}`;
                    dot.addEventListener('click', () => goToSlide(i));
                    dotsContainer.appendChild(dot);
                }
            };
            
            // Update testimonial position
            const updateTestimonials = () => {
                const testimonialWidth = 400 + 40; // width + gap
                const translateX = -currentIndex * testimonialWidth;
                testimonialsTrack.style.transform = `translateX(${translateX}px)`;
                
                // Update dots
                const dots = dotsContainer.querySelectorAll('.testimonial-dot');
                dots.forEach((dot, index) => {
                    dot.classList.toggle('active', index === currentIndex);
                });
                
                // Update button states
                const visibleCount = getVisibleCount();
                const maxIndex = Math.max(0, testimonials.length - visibleCount);
                
                prevBtn.disabled = currentIndex === 0;
                nextBtn.disabled = currentIndex >= maxIndex;
            };
            
            // Go to specific slide
            const goToSlide = (index) => {
                const visibleCount = getVisibleCount();
                const maxIndex = Math.max(0, testimonials.length - visibleCount);
                currentIndex = Math.max(0, Math.min(index, maxIndex));
                updateTestimonials();
            };
            
            // Next slide
            const nextSlide = () => {
                const visibleCount = getVisibleCount();
                const maxIndex = Math.max(0, testimonials.length - visibleCount);
                if (currentIndex < maxIndex) {
                    currentIndex++;
                } else if (isAutoScrollEnabled) {
                    currentIndex = 0; // Loop back to beginning
                }
                updateTestimonials();
            };
            
            // Previous slide
            const prevSlide = () => {
                if (currentIndex > 0) {
                    currentIndex--;
                } else if (isAutoScrollEnabled) {
                    const visibleCount = getVisibleCount();
                    currentIndex = Math.max(0, testimonials.length - visibleCount);
                }
                updateTestimonials();
            };
            
            // Auto scroll functionality
            const startAutoScroll = () => {
                if (autoScrollInterval) clearInterval(autoScrollInterval);
                autoScrollInterval = setInterval(nextSlide, 4000);
            };
            
            const stopAutoScroll = () => {
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            };
            
            // Toggle auto scroll
            const toggleAutoScroll = () => {
                isAutoScrollEnabled = !isAutoScrollEnabled;
                
                if (isAutoScrollEnabled) {
                    autoScrollToggle.classList.add('active');
                    autoScrollToggle.innerHTML = `
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                            <polygon points="5 3 19 12 5 21 5 3"></polygon>
                        </svg>
                        Auto-scroll enabled
                    `;
                    startAutoScroll();
                } else {
                    autoScrollToggle.classList.remove('active');
                    autoScrollToggle.innerHTML = `
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24" style="margin-right: 6px;">
                            <rect x="6" y="4" width="4" height="16"></rect>
                            <rect x="14" y="4" width="4" height="16"></rect>
                        </svg>
                        Auto-scroll paused
                    `;
                    stopAutoScroll();
                }
            };
            
            // Event listeners
            if (prevBtn) prevBtn.addEventListener('click', prevSlide);
            if (nextBtn) nextBtn.addEventListener('click', nextSlide);
            if (autoScrollToggle) autoScrollToggle.addEventListener('click', toggleAutoScroll);
            
            // Touch/swipe support
            let startX = 0;
            let isDragging = false;
            
            testimonialsTrack.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isDragging = true;
                stopAutoScroll();
            }, { passive: true });
            
            testimonialsTrack.addEventListener('touchend', (e) => {
                if (!isDragging) return;
                
                const endX = e.changedTouches[0].clientX;
                const diffX = startX - endX;
                
                if (Math.abs(diffX) > 50) { // Minimum swipe distance
                    if (diffX > 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                }
                
                isDragging = false;
                if (isAutoScrollEnabled) startAutoScroll();
            }, { passive: true });
            
            // Pause auto-scroll on hover
            testimonialsTrack.addEventListener('mouseenter', stopAutoScroll);
            testimonialsTrack.addEventListener('mouseleave', () => {
                if (isAutoScrollEnabled) startAutoScroll();
            });
            
            // Handle window resize
            window.addEventListener('resize', () => {
                createDots();
                goToSlide(0); // Reset to first slide on resize
            });
            
            // Initialize
            createDots();
            updateTestimonials();
            if (isAutoScrollEnabled) startAutoScroll();
        }
        
        // Currency Switcher Functionality
        const currencyDropdown = document.getElementById('currencyDropdown');
        const currencyBtn = document.getElementById('currencyBtn');
        const currencyOptions = document.getElementById('currencyOptions');
        const currentCurrency = document.getElementById('currentCurrency');
        const currentCurrencyFlag = document.getElementById('currentCurrencyFlag');
        const exchangeRateInfo = document.getElementById('exchangeRateInfo');
        const lastUpdated = document.getElementById('lastUpdated');
        
        let selectedCurrency = localStorage.getItem('selectedCurrency') || 'TSh';
        let exchangeRates = {
            'TSh': 1,
            'USD': 0.0004,
            'NGN': 0.61,
            'BRL': 0.002,
            'INR': 0.034,
            'EUR': 0.00037
        };
        
        // Currency symbols mapping
        const currencySymbols = {
            'TSh': 'TSh',
            'USD': '$',
            'NGN': '₦',
            'BRL': 'R$',
            'INR': '₹',
            'EUR': '€'
        };
        
        // Currency names mapping
        const currencyNames = {
            'TSh': 'Tanzanian Shilling',
            'USD': 'US Dollar',
            'NGN': 'Nigerian Naira', 
            'BRL': 'Brazilian Real',
            'INR': 'Indian Rupee',
            'EUR': 'Euro'
        };
        
        // Format number with appropriate decimal places for currency
        const formatPrice = (amount, currency) => {
            const decimals = ['USD', 'EUR'].includes(currency) ? 2 : 0;
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(amount);
        };
        
        // Update currency display
        const updateCurrencyDisplay = (currency) => {
            currentCurrency.textContent = currency;
            currentCurrencyFlag.className = `currency-flag flag-${currency.toLowerCase()}`;
            
            // Update active state in dropdown
            currencyOptions.querySelectorAll('.currency-option').forEach(option => {
                option.classList.toggle('active', option.dataset.currency === currency);
            });
        };
        
        // Update all pricing with new currency
        const updatePricing = (currency) => {
            const rate = exchangeRates[currency];
            const symbol = currencySymbols[currency];
            
            // Update all price elements
            document.querySelectorAll('.pricing-amount').forEach(priceElement => {
                const basePrice = parseInt(priceElement.dataset.basePrice);
                const convertedPrice = basePrice * rate;
                const formattedPrice = formatPrice(convertedPrice, currency);
                
                priceElement.querySelector('.currency-symbol').textContent = symbol;
                priceElement.querySelector('.price-value').textContent = formattedPrice;
            });
            
            // Update per-message prices
            const perMessagePrices = [100, 90, 80]; // Base prices in TSh
            document.querySelectorAll('.per-message-price').forEach((element, index) => {
                const basePrice = perMessagePrices[index];
                const convertedPrice = basePrice * rate;
                const formattedPrice = formatPrice(convertedPrice, currency);
                
                element.textContent = formattedPrice;
            });
            
            // Update all currency symbols in pricing periods
            document.querySelectorAll('.pricing-period .currency-symbol').forEach(symbolElement => {
                symbolElement.textContent = symbol;
            });
            
            // Update exchange rate info
            if (currency === 'TSh') {
                exchangeRateInfo.innerHTML = `<span>Base currency: <span class="rate">Tanzanian Shilling (TSh)</span></span>`;
            } else {
                const rateText = currency === 'USD' || currency === 'EUR' ? rate.toFixed(6) : rate.toFixed(4);
                exchangeRateInfo.innerHTML = `<span>Exchange rate: 1 TSh = <span class="rate">${rateText} ${currency}</span> | Last updated: <span id="lastUpdated">Now</span></span>`;
            }
            
            // Update timestamp
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            const updatedElement = document.getElementById('lastUpdated');
            if (updatedElement) {
                updatedElement.textContent = timeString;
            }
        };
        
        // Simulate fetching live exchange rates (in real app, this would be an API call)
        const updateExchangeRates = () => {
            // Mock slight variations to simulate live rates
            const variations = {
                'USD': 0.0004 + (Math.random() - 0.5) * 0.00002,
                'NGN': 0.61 + (Math.random() - 0.5) * 0.02,
                'BRL': 0.002 + (Math.random() - 0.5) * 0.0001,
                'INR': 0.034 + (Math.random() - 0.5) * 0.001,
                'EUR': 0.00037 + (Math.random() - 0.5) * 0.00002
            };
            
            Object.keys(variations).forEach(currency => {
                if (currency !== 'TSh') {
                    exchangeRates[currency] = Math.max(0.0001, variations[currency]);
                }
            });
            
            // Update display if not using base currency
            if (selectedCurrency !== 'TSh') {
                updatePricing(selectedCurrency);
            }
        };
        
        if (currencyDropdown) {
            // Toggle dropdown
            currencyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                currencyDropdown.classList.toggle('active');
            });
            
            // Handle currency selection
            currencyOptions.addEventListener('click', (e) => {
                e.preventDefault();
                if (e.target.closest('.currency-option')) {
                    const option = e.target.closest('.currency-option');
                    const newCurrency = option.dataset.currency;
                    
                    selectedCurrency = newCurrency;
                    localStorage.setItem('selectedCurrency', newCurrency);
                    
                    updateCurrencyDisplay(newCurrency);
                    updatePricing(newCurrency);
                    currencyDropdown.classList.remove('active');
                }
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!currencyDropdown.contains(e.target)) {
                    currencyDropdown.classList.remove('active');
                }
            });
            
            // Initialize currency on page load
            updateCurrencyDisplay(selectedCurrency);
            updatePricing(selectedCurrency);
            
            // Update exchange rates every 5 minutes
            setInterval(updateExchangeRates, 300000);
            
            // Initial rate update
            updateExchangeRates();
        }
        
        // Pricing button click handlers
        document.querySelectorAll('.btn-pricing').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Smooth scroll to login form
                const loginForm = document.querySelector('.auth-form');
                if (loginForm) {
                    loginForm.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    
                    // Focus on phone input after scroll
                    setTimeout(() => {
                        const phoneInput = document.getElementById('phone');
                        if (phoneInput) {
                            phoneInput.focus();
                            // Add a subtle highlight effect
                            phoneInput.style.boxShadow = '0 0 0 3px rgba(31, 122, 140, 0.3)';
                            setTimeout(() => {
                                phoneInput.style.boxShadow = '';
                            }, 2000);
                        }
                    }, 800);
                }
            });
        });
        
        // Language Switcher Functionality
        const translations = {
            en: {
                heroTitle: "Hi, I'm your new AI Sales Agent",
                heroSubtitle: "I close deals 24/7 while you focus on growing your business. I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals.",
                meetSalesRep: "Meet Your New Sales Rep",
                seeEarnings: "See How Much I'll Earn You",
                trustIndicators: "Wherever your customers are and whatever language they speak — Kiswahili, English, Arabic, Spanish, Hausa, or local dialects — I'm ready to serve them instantly. Available 24/7/365. Just give me your product, and I'll make sure no sale is ever lost again.",
                trackRecordTitle: "AI Sales Agent vs Human Sales Agent",
                trackRecordDesc: "See why businesses are choosing AI over traditional sales teams",
                businessesServed: "Businesses Successfully Served",
                conversationsHandled: "Conversations Handled",
                dealsTracked: "Deals Tracked & Closed",
                conversionIncrease: "Average Conversion Increase",
                challengesToGrowth: "From Challenges to Growth",
                currentChallenges: "Your Current Sales Challenges",
                howSolve: "How I Solve Them for You",
                coreSkills: "My Core Sales Skills",
                coreSkillsDesc: "I bring the expertise of a senior sales professional with the consistency of AI automation.",
                industriesTitle: "Industries Where I Excel",
                industriesDesc: "I've specialized in diverse sectors, adapting my approach to each industry's unique needs.",
                pricingTitle: "Simple, Transparent Pricing",
                pricingDesc: "Only pay for the AI messages you use. Higher plans include more AI sales messages at a lower cost per message.",
                pricingNote: "SafariChat helps you close deals — every AI message is a real sales interaction that moves your customers toward buying.",
                testimonialsTitle: "What My Clients Say About Working With Me",
                loginTitle: "Meet Your New AI Sales Rep",
                loginSubtitle: "Enter your WhatsApp number to start working with me",
                whatsappNumber: "WhatsApp Number",
                startWorking: "Start Working With Now",
                termsAgreement: "By clicking Start, you agree to our",
                termsConditions: "Terms and Conditions",
                freeTrial: "3 Days Free Trial"
            },
            es: {
                heroTitle: "Hola, soy tu nuevo Agente de Ventas IA",
                heroSubtitle: "Cierro negocios 24/7 mientras te enfocas en hacer crecer tu negocio. Manejo conversaciones de ventas completas, califico tus prospectos, negocio los mejores precios y te entrego negocios listos para cerrar.",
                meetSalesRep: "Conoce a Tu Nuevo Representante de Ventas",
                seeEarnings: "Ve Cuánto Te Haré Ganar",
                trustIndicators: "Donde quiera que estén tus clientes y cualquier idioma que hablen — Kiswahili, Inglés, Árabe, Español, Hausa, o dialectos locales — estoy listo para servirles al instante. Disponible 24/7/365. Solo dame tu producto y me aseguraré de que nunca se pierda una venta.",
                trackRecordTitle: "Agente de Ventas IA vs Agente de Ventas Humano",
                trackRecordDesc: "Ve por qué las empresas están eligiendo IA sobre los equipos de ventas tradicionales",
                challengesToGrowth: "De Desafíos al Crecimiento",
                coreSkills: "Mis Habilidades Principales de Ventas",
                industriesTitle: "Industrias Donde Sobresalgo",
                pricingTitle: "Precios Simples y Transparentes",
                testimonialsTitle: "Lo Que Mis Clientes Dicen Sobre Trabajar Conmigo",
                loginTitle: "Conoce a Tu Nuevo Representante de Ventas IA",
                loginSubtitle: "Ingresa tu número de WhatsApp para empezar a trabajar conmigo",
                whatsappNumber: "Número de WhatsApp",
                startWorking: "Empezar a Trabajar Con Mi Agente de Ventas IA",
                termsAgreement: "Al hacer clic en Empezar, aceptas nuestros",
                termsConditions: "Términos y Condiciones",
                freeTrial: "3 Días de Prueba Gratis"
            },
            pt: {
                heroTitle: "Olá, eu sou seu novo Agente de Vendas IA",
                heroSubtitle: "Fecho negócios 24/7 enquanto você foca em fazer seu negócio crescer. Conduzo conversas de vendas completas, qualifico seus prospects, negocio os melhores preços e entrego negócios prontos para fechar.",
                meetSalesRep: "Conheça Seu Novo Representante de Vendas",
                seeEarnings: "Veja Quanto Vou Te Fazer Ganhar",
                trustIndicators: "Onde quer que seus clientes estejam e qualquer idioma que falem — Kiswahili, Inglês, Árabe, Espanhol, Hauçá, ou dialetos locais — estou pronto para servi-los instantaneamente. Disponível 24/7/365. Apenas me dê seu produto e garantirei que nenhuma venda seja perdida novamente.",
                trackRecordTitle: "Agente de Vendas IA vs Agente de Vendas Humano",
                trackRecordDesc: "Veja por que as empresas estão escolhendo IA ao invés de equipes de vendas tradicionais",
                challengesToGrowth: "De Desafios ao Crescimento",
                coreSkills: "Minhas Habilidades Principais de Vendas",
                industriesTitle: "Indústrias Onde Me Destaco",
                pricingTitle: "Preços Simples e Transparentes",
                testimonialsTitle: "O Que Meus Clientes Dizem Sobre Trabalhar Comigo",
                loginTitle: "Conheça Seu Novo Representante de Vendas IA",
                loginSubtitle: "Digite seu número do WhatsApp para começar a trabalhar comigo",
                whatsappNumber: "Número do WhatsApp",
                startWorking: "Começar a Trabalhar Com Meu Agente de Vendas IA",
                termsAgreement: "Ao clicar em Começar, você concorda com nossos",
                termsConditions: "Termos e Condições",
                freeTrial: "3 Dias de Teste Grátis"
            },
            hi: {
                heroTitle: "नमस्ते, मैं आपका नया AI सेल्स एजेंट हूं",
                heroSubtitle: "मैं 24/7 डील्स बंद करता हूं जबकि आप अपने व्यवसाय को बढ़ाने पर फोकस करते हैं। मैं पूर्ण सेल्स बातचीत संभालता हूं, आपकी संभावनाओं को योग्य बनाता हूं, सर्वोत्तम मूल्य पर बातचीत करता हूं।",
                meetSalesRep: "अपने नए सेल्स रेप से मिलें",
                seeEarnings: "देखें कि मैं आपको कितना कमाऊंगा",
                trustIndicators: "आपके ग्राहक जहाँ भी हों और जो भी भाषा बोलते हों — किस्वाहिली, अंग्रेजी, अरबी, स्पैनिश, हौसा, या स्थानीय बोलियाँ — मैं तुरंत उनकी सेवा करने के लिए तैयार हूँ। 24/7/365 उपलब्ध। बस मुझे अपना उत्पाद दें और मैं सुनिश्चित करूंगा कि कोई भी बिक्री फिर कभी न खोए।",
                trackRecordTitle: "AI सेल्स एजेंट बनाम ह्यूमन सेल्स एजेंट",
                trackRecordDesc: "देखें कि व्यवसाय पारंपरिक सेल्स टीमों के बजाय AI क्यों चुन रहे हैं",
                challengesToGrowth: "चुनौतियों से विकास तक",
                coreSkills: "मेरे मुख्य सेल्स कौशल",
                industriesTitle: "उद्योग जहां मैं उत्कृष्ट हूं",
                pricingTitle: "सरल, पारदर्शी मूल्य निर्धारण",
                testimonialsTitle: "मेरे क्लाइंट मेरे साथ काम के बारे में क्या कहते हैं",
                loginTitle: "अपने नए AI सेल्स रेप से मिलें",
                loginSubtitle: "मेरे साथ काम शुरू करने के लिए अपना WhatsApp नंबर दर्ज करें",
                whatsappNumber: "WhatsApp नंबर",
                startWorking: "मेरे AI सेल्स एजेंट के साथ काम शुरू करें",
                termsAgreement: "शुरू पर क्लिक करके, आप हमारी",
                termsConditions: "नियम और शर्तों",
                freeTrial: "3 दिन का मुफ्त परीक्षण"
            },
            ar: {
                heroTitle: "مرحباً، أنا وكيل المبيعات الذكي الجديد",
                heroSubtitle: "أقوم بإغلاق الصفقات 24/7 بينما تركز على نمو عملك. أتعامل مع محادثات المبيعات الكاملة وأؤهل عملاءك المحتملين.",
                meetSalesRep: "التقِ بممثل المبيعات الجديد",
                seeEarnings: "انظر كم سأجعلك تكسب",
                trustIndicators: "أينما كان عملاؤك وأي لغة يتحدثون — الكيسواحيلية، الإنجليزية، العربية، الإسبانية، الهوسا، أو اللهجات المحلية — أنا مستعد لخدمتهم فوراً. متاح 24/7/365. فقط أعطني منتجك وسأتأكد من عدم ضياع أي بيع مرة أخرى.",
                trackRecordTitle: "وكيل مبيعات ذكي مقابل وكيل مبيعات بشري",
                trackRecordDesc: "انظر لماذا تختار الشركات الذكاء الاصطناعي بدلاً من فرق المبيعات التقليدية",
                challengesToGrowth: "من التحديات إلى النمو",
                coreSkills: "مهارات المبيعات الأساسية",
                industriesTitle: "الصناعات التي أتفوق فيها",
                pricingTitle: "تسعير بسيط وشفاف",
                testimonialsTitle: "ما يقوله عملائي عن العمل معي",
                loginTitle: "التقِ بممثل المبيعات الذكي الجديد",
                loginSubtitle: "أدخل رقم WhatsApp لبدء العمل معي",
                whatsappNumber: "رقم WhatsApp",
                startWorking: "ابدأ العمل مع وكيل المبيعات الذكي",
                termsAgreement: "بالنقر على ابدأ، فإنك توافق على",
                termsConditions: "الشروط والأحكام",
                freeTrial: "تجربة مجانية لمدة 3 أيام"
            },
            fr: {
                heroTitle: "Salut, je suis votre nouvel Agent de Vente IA",
                heroSubtitle: "Je conclus des affaires 24h/24 et 7j/7 pendant que vous vous concentrez sur le développement de votre entreprise.",
                meetSalesRep: "Rencontrez Votre Nouveau Représentant",
                seeEarnings: "Voyez Combien Je Vous Ferai Gagner",
                trustIndicators: "Où que soient vos clients et quelle que soit la langue qu'ils parlent — Kiswahili, Anglais, Arabe, Espagnol, Hausa, ou dialectes locaux — je suis prêt à les servir instantanément. Disponible 24h/24 7j/7. Donnez-moi simplement votre produit et je m'assurerai qu'aucune vente ne soit jamais perdue.",
                trackRecordTitle: "Agent de Vente IA vs Agent de Vente Humain",
                trackRecordDesc: "Voyez pourquoi les entreprises choisissent l'IA plutôt que les équipes de vente traditionnelles",
                challengesToGrowth: "Des Défis à la Croissance",
                coreSkills: "Mes Compétences Principales de Vente",
                industriesTitle: "Industries Où J'Excelle",
                pricingTitle: "Tarification Simple et Transparente",
                testimonialsTitle: "Ce Que Mes Clients Disent de Moi",
                loginTitle: "Rencontrez Votre Nouveau Agent IA",
                loginSubtitle: "Entrez votre numéro WhatsApp pour commencer",
                whatsappNumber: "Numéro WhatsApp",
                startWorking: "Commencer Avec Mon Agent de Vente IA",
                termsAgreement: "En cliquant, vous acceptez nos",
                termsConditions: "Termes et Conditions",
                freeTrial: "Essai Gratuit de 3 Jours"
            },
            sw: {
                heroTitle: "Hujambo, Mimi ni Wakala wako Mpya wa Mauzo wa AI",
                heroSubtitle: "Ninafunga mikataba 24/7 wakati wewe unalenga kukuza biashara yako. Ninashughulikia mazungumzo kamili ya mauzo, kuwahakikisha wateja wako, na kupatanisha bei bora.",
                meetSalesRep: "Kutana na Mwakilishi wako Mpya wa Mauzo",
                seeEarnings: "Ona Kiasi Kitakachokuleta Faida",
                trustIndicators: "Popote walipo wateja wako na lugha yoyote wanayoongea — Kiswahili, Kiingereza, Kiarabu, Kihispania, Kihausa, au lahaja za kienyeji — niko tayari kuwahudumia mara moja. Ninapatikana 24/7/365. Nipe tu bidhaa yako na nitahakikisha hakuna mauzo yatakayopotea tena.",
                trackRecordTitle: "Wakala wa Mauzo wa AI dhidi ya Wakala wa Mauzo wa Kibinadamu",
                trackRecordDesc: "Ona kwa nini biashara zinachagua AI badala ya timu za mauzo za jadi",
                challengesToGrowth: "Kutoka Changamoto hadi Ukuzi",
                coreSkills: "Ujuzi wangu wa Kimsingi wa Mauzo",
                industriesTitle: "Sekta Ambazo Ninafanya Vizuri",
                pricingTitle: "Bei Rahisi na ya Uwazi",
                testimonialsTitle: "Kile Wateja wangu Wanasema kuhusu Kufanya Kazi Nami",
                loginTitle: "Kutana na Mwakilishi wako Mpya wa AI",
                loginSubtitle: "Ingiza nambari yako ya WhatsApp ili kuanza kufanya kazi nami",
                whatsappNumber: "Nambari ya WhatsApp",
                startWorking: "Anza Kufanya Kazi na Wakala wangu wa Mauzo wa AI",
                termsAgreement: "Kwa kubofya Anza, unakubali",
                termsConditions: "Masharti na Hali",
                freeTrial: "Jaribio la Bure la Siku 3"
            }
        };

        // Language switcher functionality
        const languageDropdown = document.getElementById('languageDropdown');
        const languageBtn = document.getElementById('languageBtn');
        const languageOptions = document.getElementById('languageOptions');
        const currentLang = document.getElementById('currentLang');
        const currentFlag = document.getElementById('currentFlag');

        // Get current language from localStorage or default to 'en'
        let selectedLanguage = localStorage.getItem('selectedLanguage') || 'en';
        
        // Update current language display
        function updateCurrentLanguage(lang) {
            const langNames = {
                'en': 'English',
                'es': 'Español', 
                'pt': 'Português',
                'hi': 'हिंदी',
                'ar': 'العربية',
                'fr': 'Français',
                'sw': 'Kiswahili'
            };
            
            currentLang.textContent = langNames[lang];
            currentFlag.className = `flag-icon flag-${lang}`;
            
            // Update active state in dropdown
            document.querySelectorAll('.language-option').forEach(option => {
                option.classList.remove('active');
                if (option.dataset.lang === lang) {
                    option.classList.add('active');
                }
            });
        }

        // Apply translations to the page
        function applyTranslations(lang) {
            const t = translations[lang];
            if (!t) return;

            // Update hero section
            const heroTitle = document.querySelector('.hero-title');
            const heroSubtitle = document.querySelector('.hero-subtitle');
            const trustIndicators = document.querySelector('.trust-indicators');
            
            if (heroTitle) heroTitle.textContent = t.heroTitle;
            if (heroSubtitle) heroSubtitle.textContent = t.heroSubtitle;
            if (trustIndicators) trustIndicators.textContent = t.trustIndicators;

            // Update buttons
            const meetBtn = document.querySelector('.btn-hero-primary');
            const earningsBtn = document.querySelector('.btn-hero-secondary');
            if (meetBtn) {
                const icon = meetBtn.querySelector('svg');
                meetBtn.innerHTML = '';
                meetBtn.appendChild(icon);
                meetBtn.appendChild(document.createTextNode(' ' + t.meetSalesRep));
            }
            if (earningsBtn) {
                const icon = earningsBtn.querySelector('svg');
                earningsBtn.innerHTML = '';
                earningsBtn.appendChild(icon);
                earningsBtn.appendChild(document.createTextNode(' ' + t.seeEarnings));
            }

            // Update section titles
            const sectionTitles = document.querySelectorAll('.section-title');
            const titleTranslations = [
                t.trackRecordTitle,
                t.challengesToGrowth,
                t.coreSkills,
                t.industriesTitle,
                t.pricingTitle,
                t.testimonialsTitle
            ];
            
            sectionTitles.forEach((title, index) => {
                if (titleTranslations[index]) {
                    title.textContent = titleTranslations[index];
                }
            });

            // Update login section
            const loginTitle = document.querySelector('.login-title');
            const loginSubtitle = document.querySelector('.login-subtitle');
            const whatsappLabel = document.querySelector('label[for="phone2"]');
            const loginButton = document.getElementById('loginButton');
            
            if (loginTitle) loginTitle.textContent = t.loginTitle;
            if (loginSubtitle) loginSubtitle.textContent = t.loginSubtitle;
            if (whatsappLabel) whatsappLabel.innerHTML = `<i class="fab fa-whatsapp" style="color: #25d366; margin-right: 8px;"></i>${t.whatsappNumber}`;
            if (loginButton) {
                const icon = loginButton.querySelector('i');
                const spinner = loginButton.querySelector('span');
                loginButton.innerHTML = '';
                loginButton.appendChild(document.createTextNode(t.startWorking + ' '));
                if (icon) loginButton.appendChild(icon);
                if (spinner) loginButton.appendChild(spinner);
            }

            // Update legal text
            const legalText = document.querySelector('.legal-text');
            if (legalText) {
                legalText.innerHTML = `${t.termsAgreement} <a href="{{ url('/terms-and-conditions') }}" target="_blank">${t.termsConditions}</a>.<br><strong>${t.freeTrial}</strong> • No setup fees • Cancel anytime`;
            }
        }

        // Toggle dropdown
        if (languageBtn) {
            languageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                languageDropdown.classList.toggle('active');
            });
        }

        // Handle language selection
        if (languageOptions) {
            languageOptions.addEventListener('click', function(e) {
                e.preventDefault();
                if (e.target.closest('.language-option')) {
                    const option = e.target.closest('.language-option');
                    const selectedLang = option.dataset.lang;
                    
                    selectedLanguage = selectedLang;
                    localStorage.setItem('selectedLanguage', selectedLang);
                    
                    updateCurrentLanguage(selectedLang);
                    applyTranslations(selectedLang);
                    languageDropdown.classList.remove('active');
                }
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (languageDropdown && !languageDropdown.contains(e.target)) {
                languageDropdown.classList.remove('active');
            }
        });

        // Initialize language on page load
        if (languageDropdown) {
            updateCurrentLanguage(selectedLanguage);
            applyTranslations(selectedLanguage);
        }
    });
</script>
@endsection