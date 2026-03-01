<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NurtureLibrarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = 1; // Default user ID - adjust as needed
        $businessId = 1; // Default business ID - adjust as needed

        $valueNuggets = [
            // CASE STUDIES (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => '75% Faster Student Registration',
                'content_type' => 'case_study',
                'content_body' => 'Habari! Nilikuwa nikitengeneza mfumo wa ujumbe kwa shule ya St. Mary\'s wiki hii—walipunguza muda wa kusajili wanafunzi kwa 75% kwa kutumia kipengele cha SMS auto-confirmation. Wazazi wanapata thibitisho papo hapo baada ya malipo, hakuna simu za ziada! Nadhani inaweza kukusaidia msimu huu wa kuandikisha wanafunzi. Hakuna haraka, nilikuwa tu nataka kushare! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'School Director',
                'target_pain_point' => 'Student registration',
                'target_lead_status' => 'warm',
                'seasonal_relevance' => 'January-February',
                'language' => 'sw',
                'tone' => 'friendly',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'School Cut Registration Time by 75%',
                'content_type' => 'case_study',
                'content_body' => 'Hi there! I was working with St. Mary\'s Primary in Arusha this week—their admin team cut student registration time by 75% using our SMS auto-confirmation feature. Parents get instant confirmation after payment, no more phone calls! Thought you might find this helpful for your intake season. No pressure, just sharing! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'Principal',
                'target_pain_point' => 'Student registration',
                'target_lead_status' => 'cold',
                'seasonal_relevance' => 'January-February',
                'language' => 'en',
                'tone' => 'friendly',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => '92% Collection Rate Boost',
                'content_type' => 'case_study',
                'content_body' => 'Quick insight: A school in Mwanza was struggling with late fee collection (60% on-time rate). They started sending automated SMS reminders 3 days before due date—collection jumped to 92% within one term! Saves them 15+ hours per week chasing parents. Want me to share their exact reminder template? No strings attached! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'School Director',
                'target_pain_point' => 'Fee collection',
                'target_lead_status' => 'cold',
                'seasonal_relevance' => null,
                'language' => 'en',
                'tone' => 'casual',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],

            // TIPS (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'Fee Reminder Template That Works',
                'content_type' => 'tip',
                'content_body' => 'Habari! Nilikuwa nikizungumza na directors kadhaa wiki hii. Uchungu wao kubwa ni kufuatilia malipo ya ada kila mwezi (inachukua masaa 10-20 kwa wiki!). Quick tip: Automated SMS reminder iliyotumwa siku 3 kabla ya deadline inarudisha wastani wa 92% vs 60% bila reminders. Je ungependa nishare template ya reminder wanayotumia? Hakuna haraka, tu thought inaweza kukusaidia! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'School Director',
                'target_pain_point' => 'Fee collection',
                'target_lead_status' => 'warm',
                'seasonal_relevance' => null,
                'language' => 'sw',
                'tone' => 'friendly',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'Parent Communication in 5 Minutes',
                'content_type' => 'tip',
                'content_body' => 'Quick tip for busy administrators: Instead of calling 200+ parents individually about exam schedules (takes hours!), you can send a bulk WhatsApp update in 5 minutes. Schools using this save 10+ hours per week on parent communication. Want me to show you how it works? No pressure, just thought it might help! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'Administrator',
                'target_pain_point' => 'Parent communication',
                'target_lead_status' => 'cold',
                'seasonal_relevance' => 'April-May',
                'language' => 'en',
                'tone' => 'casual',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],

            // INSIGHTS (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'Digital Transformation in Schools 2026',
                'content_type' => 'insight',
                'content_body' => 'Interesting trend I noticed: 80% of schools in Dar es Salaam are switching to digital systems this year. The top reason? Parents now expect instant SMS updates for everything (fees, exams, events). Schools still using manual processes are losing students to competitors. Just thought you\'d find this useful! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'Principal',
                'target_pain_point' => null,
                'target_lead_status' => 'warm',
                'seasonal_relevance' => null,
                'language' => 'en',
                'tone' => 'formal',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],

            // VIDEOS (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => '2-Minute Demo: Bulk Parent Updates',
                'content_type' => 'video',
                'content_body' => 'Saw this quick 2-minute demo showing how schools send exam schedules to all parents via WhatsApp in one click. Saves hours vs calling individually. Link: [Demo URL]. Thought it might be useful—no pressure! 😊',
                'content_url' => 'https://example.com/demo',
                'target_industry' => 'Education',
                'target_job_title' => 'Administrator',
                'target_pain_point' => 'Parent communication',
                'target_lead_status' => 'cold',
                'seasonal_relevance' => null,
                'language' => 'en',
                'tone' => 'friendly',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],

            // TESTIMONIALS (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'What School Directors Are Saying',
                'content_type' => 'testimonial',
                'content_body' => 'Habari! Just heard from Madam Grace (Upendo Primary School) leo—she said automated fee reminders saved her team 20 hours per week. Quote: "Sasa tunaanza kufanya kazi muhimu zaidi, si kufuatilia malipo tu!" Thought you might appreciate hearing from a fellow director. No pressure, just sharing! 😊',
                'content_url' => null,
                'target_industry' => 'Education',
                'target_job_title' => 'School Director',
                'target_pain_point' => 'Fee collection',
                'target_lead_status' => 'warm',
                'seasonal_relevance' => null,
                'language' => 'sw',
                'tone' => 'friendly',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],

            // ARTICLES (Education)
            [
                'user_id' => $userId,
                'business_id' => $businessId,
                'title' => 'How Top Schools Reduce Admin Workload',
                'content_type' => 'article',
                'content_body' => 'Came across this helpful article: "5 Ways Top Schools in Tanzania Reduced Admin Work by 50%". Main insight: Automation of repetitive tasks (fee reminders, attendance SMS, parent updates) frees up staff for teaching & student care. Link: [Article URL]. Thought you\'d find it interesting! 😊',
                'content_url' => 'https://example.com/article',
                'target_industry' => 'Education',
                'target_job_title' => 'Principal',
                'target_pain_point' => null,
                'target_lead_status' => 'cold',
                'seasonal_relevance' => null,
                'language' => 'en',
                'tone' => 'formal',
                'usage_count' => 0,
                'success_rate' => 0.00,
            ],
        ];

        // Insert all value nuggets
        foreach ($valueNuggets as $nugget) {
            \App\Models\NurtureLibrary::create($nugget);
        }

        $this->command->info('Nurture library seeded with ' . count($valueNuggets) . ' value nuggets!');
    }
}
