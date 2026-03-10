# SafariChat AI Sales Agent - Landing Page Requirements (UPDATED)

**Project goal (one sentence)**
Create a personal, compelling landing page where I (the AI Sales Agent) introduce myself as your dedicated sales professional, explaining how I will personally drive your revenue growth, handle your leads with expertise, and deliver results that transform your business — speaking directly as the salesperson you're hiring, not a platform you're buying.

---

## High-level requirements

* **Primary audience**: Global enterprises, SMEs, financial institutions, educational organizations, and businesses seeking sales automation (Tanzania, Nigeria, Indonesia, Brazil, India, and beyond)
* **Secondary audience**: Sales teams, customer service managers, business owners looking to scale operations worldwide
* **Tone & voice**: Personal, confident, and achievement-oriented. Written in first person as an experienced sales professional introducing themselves. Focus on results I will deliver, problems I will solve, and success I will bring to your business.
* **Maintain existing login flow** - Keep current authentication system exactly as-is
* **Performance targets**: Lighthouse score >= 95 (desktop), <= 3s load time
* **Mobile-first responsive**: Optimized for African mobile networks with progressive loading
* **Advanced tracking**: Conversion funnels, feature interactions, pricing calculator usage, demo requests, ROI estimations
* **Multi-language support**: English (default), Spanish (LATAM + Spain), Portuguese (Brazil), Hindi (India), Arabic, French with language switcher in header

---

## Visual & brand guidance

* **Modern enterprise aesthetic**: Clean, professional, data-driven design language
* **Color palette**: SafariChat teal primary (#1F7A8C), warm gold accent (#FFBB33), smart greys
* **Global design elements**: Currency symbols, flag icons for regions, international business imagery
* **Typography**: Inter or Poppins - clear information hierarchy
* **Visual elements**: Real conversation screenshots, live chat demos, data dashboards, ROI graphics
* **Performance-optimized**: Compressed images, lazy loading, critical CSS inlining
* **Accessibility**: WCAG AA compliance, keyboard navigation, screen reader optimization

---

## Multi-Language Support Requirements

### **Supported Languages (Launch)**
1. **English (Default)** - Primary language for global markets
2. **Spanish** - LATAM + Spain markets  
3. **Portuguese** - Brazil market
4. **Hindi** - India market
5. **Arabic** - MENA markets
6. **French** - Francophone markets

### **Language Implementation**
* **Language files structure**: `/resources/lang/{locale}/landing.php`
* **Dynamic language switcher** in header with flag icons
* **URL structure**: `domain.com/{locale}/` (e.g., `safarichat.ai/es/`, `safarichat.ai/pt-br/`)
* **Auto-detection** based on browser language with manual override
* **Persistent selection** via localStorage/cookies

### **Language Files Content**
Each language file must include:
```php
// resources/lang/en/landing.php
return [
    'hero' => [
        'title' => 'Hi, I\'m your new AI Sales Agent. I close deals 24/7 while you focus on growing your business.',
        'subtitle' => 'I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals. I never take breaks, never miss follow-ups, and I turn every WhatsApp message into a potential sale.',
        'cta_primary' => 'Meet Your New Sales Rep',
        'cta_secondary' => 'See How Much I\'ll Earn You',
        'trust_indicators' => 'I\'ve successfully closed deals for 500+ businesses across Tanzania, Nigeria, Indonesia, Brazil, and 40+ countries • Available 24/7/365 • Proven results guaranteed'
    ],
    'pricing' => [
        'header' => 'Simple, transparent pricing — only pay for the AI messages you use.',
        'subheader' => 'Choose a plan based on your monthly message volume. Higher plans include more AI sales messages at a lower cost per message.',
        'footer_note' => 'SafariChat helps you close deals — every AI message is a real sales interaction that moves your customers toward buying.'
    ]
    // ... complete translation structure
];
```

### **Regional Customization**
* **Currency display** based on detected/selected region
* **Local business examples** in testimonials and case studies  
* **Cultural adaptation** of communication style and examples
* **Time zone aware** business hours and contact information

---

## What I Will Achieve For Your Business

### 💼 **HOW I WILL DRIVE YOUR SALES SUCCESS**
1. **I'll Manage Multiple Product Lines Like a Senior Sales Rep**
   - I handle complex conversations about all your products simultaneously
   - I remember every customer's history and make smart recommendations
   - I know exactly when to upsell and cross-sell for maximum revenue

2. **I'll Qualify Every Lead Like an Expert**
   - I instantly identify your hottest prospects using proven sales criteria
   - I prioritize my time on the leads most likely to close
   - I predict which customers will buy so you can focus your team's efforts

3. **I'll Close Deals From Start to Finish**
   - I take prospects from first contact all the way to signed contracts
   - I handle objections professionally using industry best practices
   - I negotiate prices within your guidelines to maximize profit margins

4. **I'll Know When to Bring in Your Human Team**
   - I recognize when deals need that personal touch from your senior staff
   - I hand over warm, qualified leads with complete conversation history
   - I track response times to ensure nothing falls through the cracks

### 💬 **HOW I COMMUNICATE WITH YOUR CUSTOMERS**
1. **I Work Across All Your Business Channels Globally**
   - I manage conversations for organizations across Tanzania, Nigeria, Indonesia, Brazil, India, and Spanish-speaking markets
   - I route urgent matters to the right departments instantly in any supported language
   - I never miss a message, even during peak business periods across multiple time zones

2. **I Share Information Professionally in Multiple Languages**
   - I send contracts, brochures, and proposals instantly in English, Spanish, Portuguese, Hindi, Arabic, or French
   - I present your product catalogs in an engaging way, adapted for local markets
   - I generate QR codes so customers can reach you easily

3. **I Respect Global Business Hours and Cultural Preferences**
   - I know when to respond immediately vs. schedule for business hours across different countries
   - I adapt my communication style for Brazilian, Nigerian, Indonesian, Indian, and Arabic business cultures
   - I work around the clock but respect your customers' local time zones and cultural norms

### 🎯 **HOW I MANAGE YOUR SALES PIPELINE**
1. **I Never Let Opportunities Slip Away**
   - I reach out to new leads every single day with personalized messages
   - I re-engage past customers who haven't bought in a while
   - I follow up persistently but professionally when prospects go quiet
   - I nurture long-term relationships based on each customer's behavior

2. **I Handle High-Volume Sales Like a Pro**
   - I respond to every inquiry immediately, even during busy periods
   - I prioritize urgent deals while keeping all conversations moving
   - I never drop the ball on any conversation, ever

### 📊 **HOW I REPORT MY PERFORMANCE TO YOU**
1. **I Show You My Results in Real-Time**
   - I track every conversation so you see exactly what I'm doing
   - I measure my sales performance just like any sales rep
   - I show you which approaches work best for closing deals
   - I prove my value by tracking the revenue I generate

2. **I Help You Understand Your Sales Process Better**
   - I analyze your entire sales funnel to find improvement opportunities
   - I map out the customer journey so you can optimize it
   - I calculate my ROI and show you exactly how much money I'm making you
   - I benchmark my performance against industry standards

---

## Updated Technology Stack (Actual Implementation)

### **Backend Architecture**
* **Framework**: Laravel 10.x (Latest LTS)
* **Language**: PHP 8.1+
* **Database**: PostgreSQL with doctrine/dbal support
* **Queue System**: Redis with Laravel Horizon
* **AI Integration**: OpenAI GPT-4o with openai-php/client v0.18.0

### **Advanced Features**
* **Real-time Processing**: Instant webhook processing for immediate responses
* **Enterprise Security**: Laravel Sanctum API authentication
* **Monitoring**: Sentry error tracking, Laravel Telescope debugging
* **Document Processing**: PDF generation, Excel exports, QR code creation
* **Multi-tenant Support**: Instance-based architecture for enterprise clients

### **Integrations & APIs**
* **WhatsApp**: WaSender API with dynamic instance management
* **AI/ML**: OpenAI GPT-4o with context-aware prompt engineering
* **Analytics**: Laravel Telescope, custom analytics dashboard
* **File Processing**: PDF manipulation, media handling, document generation

### **Infrastructure Ready**
* **Queue Management**: Laravel Horizon with Redis clustering
* **Error Handling**: Comprehensive logging and monitoring
* **Scalability**: Multi-instance WhatsApp support, database optimization
* **Performance**: Caching layers, optimized queries, background processing

---

## Structure & content (updated for AI focus)

### 1. Header
   * Logo, navigation (Features, Pricing, Case Studies, Enterprise, Login)
   * CTA: "Start AI Demo"

### 2. Hero Section (Personal Introduction)
   * **H1**: "Hi, I'm your new AI Sales Agent. I close deals 24/7 while you focus on growing your business."
   * **Subheadline**: "I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals. I never take breaks, never miss follow-ups, and I turn every WhatsApp message into a potential sale."
   * **Primary CTA**: "Meet Your New Sales Rep" 
   * **Secondary CTA**: "See How Much I'll Earn You"
   * **Trust indicators**: "I've successfully closed deals for 500+ businesses globally • Available 24/7/365 • Proven results across 6 countries"

### 3. My Track Record
   * Client logos from financial institutions, schools, enterprises across Tanzania, Nigeria, Brazil, Indonesia, India, and Spanish-speaking markets
   * **My Results**: "I've helped 500+ organizations across 6 countries increase sales • I've handled 2M+ successful conversations in multiple languages • I've tracked over $50M in closed deals globally"

### 4. Problems I Solve → Value I Deliver
   * **Left**: Your current sales challenges (missed leads, inconsistent follow-ups, limited working hours, overwhelmed sales team)
   * **Right**: How I solve them personally (I never miss a lead, I follow up consistently, I work 24/7, I handle the heavy lifting so your team can focus on closing big deals)

### 5. My Core Sales Skills
   
   **What I Do: Intelligent Sales Conversations**
   * I speak multiple languages and understand exactly what customers are thinking
   * I handle tough objections using proven sales techniques from your industry
   * I negotiate prices skillfully within the limits you set for me

   **What I Do: Expert Lead Management** 
   * I capture every lead from your WhatsApp and instantly assess their potential
   * I score prospects using 15+ proven sales indicators
   * I predict which customers are ready to buy so you can close them faster

   **What I Do: Systematic Sales Campaigns**
   * I run daily outreach campaigns with personalized messages for each prospect
   * I win back customers who haven't purchased in a while
   * I follow up persistently but professionally when prospects don't respond

   **What I Do: Smart Team Collaboration**
   * I know exactly when a deal needs your personal attention
   * I provide complete conversation history when handing off to your team
   * I monitor response times to make sure nothing gets forgotten

   **What I Do: Professional WhatsApp Management**
   * I manage multiple WhatsApp accounts for large organizations
   * I handle documents, images, and rich media professionally
   * I process messages instantly with backup systems that never fail

### 6. See Me In Action
   * Interactive chat where you can talk to me directly
   * Real conversations I've had with customers (names removed for privacy)
   * "Test drive" where you can see how I handle your specific industry

### 7. Calculate How Much Money I'll Make You
   * Interactive tool showing exactly how much revenue I'll generate
   * Input fields: your team size, average deal size, current conversion rates
   * Output: additional deals I'll close, time I'll save your team, total profit increase

### 8. Industries Where I Excel
   * **Financial Services**: I've helped banks automate loan applications and customer onboarding
   * **Education**: I handle student inquiries, course enrollment, and parent communications expertly
   * **E-commerce**: I recommend products, support orders, and recover abandoned purchases
   * **Professional Services**: I book appointments, schedule consultations, and manage follow-ups flawlessly

### 9. How to Hire Me (My Service Packages)

## **Pricing Overview**

I use a **simple, transparent, message-based billing model** that allows every business — from startups to large enterprises — to pay only for what they use.

Each plan includes:

1. **A fixed monthly subscription price**
2. **A fixed number of AI-powered messages I'll handle per month**
3. **A clear cost-per-message model**
4. **Lower per-message cost as you upgrade to higher plans**
5. **A simple overage pricing rate for advanced users**

### **SME Plans**

#### **Starter Plan (Winga)**
* **Price:** TSh 49,700 per month
* **Includes:** 497 AI messages I'll handle for you
* **Effective Rate:** **TSh 100 per message**
* **Perfect for:** Startups, small shops, solo entrepreneurs, and low-volume businesses
* **What I'll do:** Handle all your basic sales conversations affordably while you focus on growing

#### **Pro Plan** ⭐ Most Popular
* **Price:** TSh 93,700 per month  
* **Includes:** 1,041 AI messages I'll handle for you
* **Effective Rate:** **TSh 90 per message**
* **Perfect for:** Growing businesses and schools with regular WhatsApp traffic
* **What I'll do:** Manage steady sales activity throughout the month with better value per conversation

#### **Enterprise Plan** 🏆 Best Value
* **Price:** TSh 123,600 per month
* **Includes:** 1,545 AI messages I'll handle for you
* **Effective Rate:** **TSh 80 per message**
* **Perfect for:** High-volume organizations like banks, real estate companies, large schools
* **What I'll do:** Handle consistent, high-volume engagement with the best cost-per-message

### **Corporate Toggle & Custom Solutions**

**For Enterprise Organizations:**
* **Custom message volumes** based on your needs
* **Volume discounts** for high-volume operations
* **Per-conversation billing:** Starting at $0.005 per message I handle
* **Setup investment:** $5,000 - $25,000 (includes my training on your business)
* **Dedicated account management** and guaranteed response times

### **Usage & Overage**

**For users exceeding 1,545 messages:**
* **TSh 75 per additional AI message** I handle
* **No service interruption** - I keep working while costs stay predictable
* **Enterprise scaling** available for unlimited volumes

### **Multi-Currency Pricing**

**Regional Currency Support:**
* **Tanzania:** TSh (Tanzanian Shilling) - Base pricing shown above
* **Nigeria:** NGN (Nigerian Naira) - Real-time conversion
* **Brazil:** BRL (Brazilian Real) - Real-time conversion  
* **Indonesia:** IDR (Indonesian Rupiah) - Real-time conversion
* **India:** INR (Indian Rupee) - Real-time conversion
* **Global:** USD (US Dollar) - Universal fallback

### **Why I Charge Per Message**

**What You Pay For:**
* **Incoming messages are FREE** - Customers can message you unlimited times
* **I only charge for AI responses** - When I actively work to close your deals
* **Every charged message is valuable work:** Answering inquiries, qualifying leads, sending documents, recommending products, negotiating prices, and closing deals
* **You pay for results, not just technology**

**Billing Transparency:**
* **Real-time usage tracking** in your dashboard
* **Monthly statements** showing exactly what conversations I handled
* **ROI reporting** showing revenue generated per message cost
* **No hidden fees** - Pay only for the sales work I perform

### **Plan Comparison Table**

| Feature | Starter | Pro | Enterprise | Corporate |
|---------|---------|-----|------------|-----------|
| Monthly Price | TSh 49,700 | TSh 93,700 | TSh 123,600 | Custom |
| AI Messages Included | 497 | 1,041 | 1,545 | Unlimited |
| Cost Per Message | TSh 100 | TSh 90 | TSh 80 | From $0.005 |
| WhatsApp Instances | 1 | 2 | 5 | Unlimited |
| Business Hours Support | Email | Phone + Email | Priority Phone | Dedicated Manager |
| CRM Integration | Basic | Advanced | Full Integration | Custom Integration |
| Response Time SLA | - | - | 2 hours | 30 minutes |
| Custom Training | - | - | ✓ | ✓ |
| Multi-language | ✓ | ✓ | ✓ | ✓ |

**Choose Your Plan:**
* **Starter:** "Start Working With Me" - TSh 49,700/month
* **Pro:** "Hire Me as Your Sales Rep" - TSh 93,700/month  
* **Enterprise:** "Bring Me Into Your Sales Team" - TSh 123,600/month
* **Corporate:** "Get Custom Partnership Quote"

### 10. What My Clients Say About Working With Me
   * **Success Story**: "How I helped XYZ Bank automate 80% of their loan inquiries and increase approvals by 35%"
   * **Client testimonials**: Business owners sharing specific results I delivered for them
   * **Video testimonials**: Customers explaining how I transformed their sales process

### 11. Common Questions About Working With Me
   * How good are you at sales? (I achieve 99.2% conversation success rates)
   * Can you handle complex deals? (Yes, and I know exactly when to bring in your senior team)
   * What languages do you speak? (Multiple languages with automatic detection)
   * How do you negotiate prices? (I negotiate skillfully within the parameters you set for me)
   * How quickly can you start? (I can be working for you within 24-48 hours for basic setup, 1-2 weeks for complex integrations)

### 12. Footer
   * Contact information, enterprise sales, technical documentation

---

## Advanced Technical Integrations

### **Example of How I Handle Sales Conversations**
```javascript
// Real example of how I work with customers
const myConversation = [
  "Customer: I need information about your loan products",
  "Me: I'd be happy to help! I've helped hundreds of clients with loans. Our personal loans range from $1,000-$50,000 with rates starting at 8.5%. What amount are you considering?",
  "Customer: Around $15,000 for home renovation", 
  "Me: Perfect choice! For $15,000, you'd qualify for our home improvement loan at 8.5% APR. Monthly payments would be around $287 over 5 years. I can start your pre-approval right now - shall we proceed?"
];
```

### **ROI Calculator Logic**
```javascript
function calculateROI(teamSize, avgDealSize, currentConversionRate) {
  const aiConversionBoost = 0.35; // 35% improvement
  const costSavings = teamSize * 2080 * 25; // Hours saved annually
  const additionalRevenue = avgDealSize * currentConversionRate * aiConversionBoost;
  return { costSavings, additionalRevenue, totalROI: costSavings + additionalRevenue };
}
```

### **Multi-Currency & Global Localization**
* Tanzania Shilling (TSh), Nigerian Naira (NGN), Brazilian Real (BRL), Indonesian Rupiah (IDR), Indian Rupee (INR)
* Multi-currency support with real-time exchange rates
* Localized content for different global markets
* Regional pricing optimization

---

## Why I'm Different From Other Sales Solutions

1. **I'm a real sales professional, not just a chatbot** - I understand sales processes and psychology
2. **I handle enterprise-level WhatsApp operations** - Multiple accounts, failover systems, professional security
3. **I was built for global businesses** - I understand diverse markets from Lagos to São Paulo, Mumbai to Jakarta  
4. **I manage complete sales cycles** - From first contact to signed contracts
5. **I work with your team, not against them** - Seamless collaboration, not replacement
6. **I prove my value with real numbers** - Measurable results with specific revenue metrics
7. **I never sleep or take time off** - Available across all time zones, never miss opportunities
8. **I specialize in your industry** - I learn your business terminology and sector-specific approaches

---

## Implementation Notes

* **Progressive Web App features** for offline capability
* **Advanced analytics integration** with custom event tracking
* **A/B testing framework** for continuous optimization  
* **Multi-language support** with automatic detection
* **Enterprise security features** highlighting compliance
* **API documentation links** for technical decision makers
* **White-label options** for partners and resellers

---

## Copy — ready-to-use snippets (Personal Perspective)

**Hero H1**: Hi, I'm your new AI Sales Agent. I close deals 24/7 while you focus on growing your business.
**Hero Sub**: I handle complete sales conversations, qualify your prospects, negotiate the best prices, and hand you ready-to-close deals. I never take breaks, never miss follow-ups, and I turn every WhatsApp message into a potential sale.
**Primary CTA**: Meet Your New Sales Rep
**Secondary CTA**: See How Much I'll Earn You

**Trust microcopy**: I've successfully closed deals for 500+ businesses across Tanzania, Nigeria, Indonesia, Brazil, and 40+ countries • Available 24/7/365 • Proven results guaranteed

**My Skills Headlines**:
* "I don't just chat — I actually close deals"
* "I handle your entire sales process from lead to close"
* "I work 24/7 with the expertise of a senior sales professional"
* "I manage your WhatsApp like an enterprise sales operation"

**Service Package CTAs**:
* Starter: "Start Working With Me - TSh 49,700/month"
* Pro: "Hire Me as Your Sales Rep - TSh 93,700/month"
* Enterprise: "Bring Me Into Your Sales Team - TSh 123,600/month"
* Corporate: "Get Custom Partnership Quote"

**Pricing Headlines**:
* "Simple, transparent pricing — only pay for the AI messages you use."
* "Choose a plan based on your monthly message volume. Higher plans include more AI sales messages at a lower cost per message."
* "SafariChat helps you close deals — every AI message is a real sales interaction that moves your customers toward buying."

**FAQ snippets (Personal perspective):**
* Q: How do you handle complex sales conversations?
  A: I use advanced conversation skills with full context awareness, handling objections professionally and negotiating within your guidelines. When deals get complex, I seamlessly hand them to your senior team with complete background.
  
* Q: Can you really close deals without human help?
  A: Absolutely! I complete entire sales cycles including professional price negotiations within your parameters. For complex enterprise deals, I involve your team at exactly the right moment with full context.
  
* Q: What makes you different from chatbots?
  A: I'm a complete sales professional, not a chatbot. I understand sales processes, qualify leads expertly, negotiate prices skillfully, and maintain relationship context across every interaction.

* Q: How quickly will I see results with you?
  A: Most of my clients see 25-40% improvement in lead response times within 48 hours and 30%+ increase in qualified leads within 2 weeks.

* Q: Why do you charge per message instead of a flat fee?
  A: You only pay when I actually do sales work for you. Incoming customer messages are free - I only charge when I respond with value like qualifying leads, answering questions, negotiating prices, or closing deals. This way you pay for results, not just technology.

* Q: What happens if I exceed my message limit?
  A: I keep working for you at TSh 75 per additional message. No interruptions, no surprises - just predictable scaling as your business grows.

* Q: Can I change my plan later?
  A: Absolutely! You can upgrade or downgrade anytime. If you upgrade mid-month, you get prorated additional messages immediately. If you downgrade, changes take effect next billing cycle.

* Q: Do you work in my local currency?
  A: Yes! I support TSh (Tanzania), NGN (Nigeria), BRL (Brazil), IDR (Indonesia), INR (India), and USD globally. Pricing updates in real-time with current exchange rates.

---

## How to Get Started Working With Me

**Meet Me Form**:
* Your company name, industry, team size
* Current sales challenges (what's frustrating you most?)
* WhatsApp volume (how many conversations monthly?)
* What you want me to focus on first (lead generation, closing deals, customer service)

**Calculate My Value Form**:
* Your current team size
* Average deal size you're working with
* Monthly lead volume
* Current conversion rate

**Enterprise Partnership Form**:
* Organization type and size
* Do you sell multiple products/services?
* What CRM system do you use?
* Estimated conversation volume
* Special integration needs
* Compliance requirements (GDPR, local regulations)

---

## Technical Stack Integration Notes

**Backend API Endpoints** (Laravel 10.x):
```php
// ROI Calculator API
Route::post('/api/calculate-roi', [RoiCalculatorController::class, 'calculate']);

// Demo Request API  
Route::post('/api/demo-request', [DemoController::class, 'request']);

// Live Chat Demo API
Route::post('/api/ai-demo-chat', [AiDemoController::class, 'processMessage']);

// Multi-language Support
Route::get('/api/language/{locale}', [LanguageController::class, 'getTranslations']);
Route::post('/api/language/detect', [LanguageController::class, 'detectLanguage']);

// Multi-currency Pricing
Route::get('/api/pricing/{currency}', [PricingController::class, 'getCurrencyPricing']);
Route::post('/api/pricing/calculate', [PricingController::class, 'calculatePlan']);

// Usage Tracking
Route::get('/api/usage/{userId}', [UsageController::class, 'getCurrentUsage']);
Route::post('/api/usage/estimate', [UsageController::class, 'estimateMonthly']);
```

**Frontend Technology**:
* **Framework**: Next.js 14 with TypeScript
* **Styling**: Tailwind CSS with custom SafariChat theme
* **State Management**: Zustand for pricing calculator state
* **Internationalization**: next-i18next for multi-language support
* **Analytics**: GA4 + custom event tracking with language/currency segmentation
* **Performance**: Image optimization, lazy loading, CDN with global distribution

**Multi-language Integration**:
```javascript
// Dynamic language switching
import { useTranslation } from 'next-i18next';
const { t, i18n } = useTranslation('landing');

// Pricing with currency conversion
const pricingData = await fetch(`/api/pricing/${userCurrency}`);
const localizedPricing = await pricingData.json();
```

**Currency & Pricing Integration**:
```javascript
// Real-time currency conversion
const convertPrice = (basePrice, fromCurrency, toCurrency) => {
  return fetch('/api/pricing/calculate', {
    method: 'POST',
    body: JSON.stringify({ basePrice, fromCurrency, toCurrency })
  });
};

// Usage tracking and overage calculation
const usageTracker = {
  currentUsage: 0,
  planLimit: 497, // or 1041, 1545
  overageRate: 75, // TSh per message
  calculateOverage: () => Math.max(0, currentUsage - planLimit) * overageRate
};
```

**SME vs Corporate Toggle**:
```javascript
// Pricing mode toggle
const [pricingMode, setPricingMode] = useState('SME'); // or 'CORPORATE'
const togglePricing = () => {
  setPricingMode(prev => prev === 'SME' ? 'CORPORATE' : 'SME');
  trackEvent('pricing_toggle', { mode: pricingMode });
};
```

This landing page should position me not as a software platform, but as your personal AI Sales Professional — someone you're hiring to join your team, drive revenue, and deliver measurable results for your business.