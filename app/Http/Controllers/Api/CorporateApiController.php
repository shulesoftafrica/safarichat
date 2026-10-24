<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CorporateApiController extends Controller
{
    /**
     * Submit corporate proposal request
     */
    public function submitProposalRequest(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'companyName' => 'required|string|max:255',
                'country' => 'required|string|max:100',
                'officialEmail' => 'required|email|max:255',
                'adoptionTimeline' => 'required|in:very_soon,within_month,within_3months,within_6months',
                'customMessage' => 'nullable|string|max:2000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate official email (not public domains)
            $email = $request->officialEmail;
            $publicDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com', 'live.com'];
            $domain = strtolower(explode('@', $email)[1]);
            
            if (in_array($domain, $publicDomains)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Public emails are not accepted. Please use your official company email.'
                ], 422);
            }

            // Store in database
            $proposalId = DB::table('corporate_proposals')->insertGetId([
                'company_name' => $request->companyName,
                'country' => $request->country,
                'official_email' => $request->officialEmail,
                'adoption_timeline' => $request->adoptionTimeline,
                'custom_message' => $request->customMessage,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Send WhatsApp notification to admin
            $this->sendWhatsAppNotification('+255714825469', 
                "🏢 New Corporate Proposal Request\n\n" .
                "📋 Request ID: #{$proposalId}\n" .
                "🏢 Company: {$request->companyName}\n" .
                "🌍 Country: {$request->country}\n" .
                "📧 Email: {$request->officialEmail}\n" .
                "⏰ Timeline: " . $this->formatTimeline($request->adoptionTimeline) . "\n" .
                ($request->customMessage ? "💬 Message: {$request->customMessage}\n" : "") .
                "\n🔗 Review at: " . url('/admin/corporate-proposals')
            );

            // Email the lead to the company inbox
            $this->sendLeadEmail(
                "New Corporate Proposal Request #{$proposalId} — {$request->companyName}",
                "A new corporate proposal request was submitted on SafariChat.\n\n" .
                "Request ID: #{$proposalId}\n" .
                "Company: {$request->companyName}\n" .
                "Country: {$request->country}\n" .
                "Email: {$request->officialEmail}\n" .
                "Timeline: " . $this->formatTimeline($request->adoptionTimeline) . "\n" .
                ($request->customMessage ? "Message: {$request->customMessage}\n" : "") .
                "\nReview at: " . url('/admin/corporate-proposals'),
                $request->officialEmail
            );

            return response()->json([
                'success' => true,
                'message' => 'Corporate proposal request submitted successfully.',
                'proposal_id' => $proposalId
            ]);

        } catch (\Exception $e) {
            Log::error('Corporate proposal request error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your request.'
            ], 500);
        }
    }

    /**
     * Submit strategy session request
     */
    public function submitStrategySession(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'companyName' => 'required|string|max:255',
                'country' => 'required|string|max:100',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'meetingLength' => 'required|in:30,60,90',
                'proposedDateTime' => 'required|date|after:now',
                'meetingAgendas' => 'required|string|max:2000',
                'paymentMethod' => 'required|in:flutterwave,stripe,ucn'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate official email (not public domains)
            $email = $request->email;
            $publicDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com', 'icloud.com', 'live.com'];
            $domain = strtolower(explode('@', $email)[1]);
            
            if (in_array($domain, $publicDomains)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Public emails are not accepted. Please use your official company email.'
                ], 422);
            }

            // Calculate price
            $prices = ['30' => 150, '60' => 300, '90' => 450];
            $price = $prices[$request->meetingLength];

            // Store in database
            $sessionId = DB::table('corporate_strategy_sessions')->insertGetId([
                'company_name' => $request->companyName,
                'country' => $request->country,
                'email' => $request->email,
                'phone' => $request->phone,
                'meeting_length' => $request->meetingLength,
                'proposed_date_time' => $request->proposedDateTime,
                'meeting_agendas' => $request->meetingAgendas,
                'payment_method' => $request->paymentMethod,
                'price_usd' => $price,
                'payment_status' => 'pending',
                'session_status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Send WhatsApp notification to admin
            $this->sendWhatsAppNotification('+255714825469', 
                "💼 New Paid Strategy Session Request\n\n" .
                "📋 Session ID: #{$sessionId}\n" .
                "🏢 Company: {$request->companyName}\n" .
                "🌍 Country: {$request->country}\n" .
                "📧 Email: {$request->email}\n" .
                "📱 Phone: {$request->phone}\n" .
                "⏰ Duration: {$request->meetingLength} minutes\n" .
                "💰 Price: \${$price}\n" .
                "💳 Payment: " . ucfirst($request->paymentMethod) . "\n" .
                "📅 Proposed: " . date('Y-m-d H:i', strtotime($request->proposedDateTime)) . "\n" .
                "📝 Agendas: {$request->meetingAgendas}\n" .
                "\n🔗 Review at: " . url('/admin/strategy-sessions')
            );

            // Email the lead to the company inbox
            $this->sendLeadEmail(
                "New Paid Strategy Session #{$sessionId} — {$request->companyName}",
                "A new paid strategy session request was submitted on SafariChat.\n\n" .
                "Session ID: #{$sessionId}\n" .
                "Company: {$request->companyName}\n" .
                "Country: {$request->country}\n" .
                "Email: {$request->email}\n" .
                "Phone: {$request->phone}\n" .
                "Duration: {$request->meetingLength} minutes\n" .
                "Price: \${$price}\n" .
                "Payment: " . ucfirst($request->paymentMethod) . "\n" .
                "Proposed: " . date('Y-m-d H:i', strtotime($request->proposedDateTime)) . "\n" .
                "Agendas: {$request->meetingAgendas}\n" .
                "\nReview at: " . url('/admin/strategy-sessions'),
                $request->email
            );

            // Here you would integrate with actual payment providers
            // For now, return success with payment instructions
            return response()->json([
                'success' => true,
                'message' => 'Strategy session request submitted successfully.',
                'session_id' => $sessionId,
                'price' => $price,
                'payment_method' => $request->paymentMethod,
                // 'payment_url' => $this->generatePaymentUrl($sessionId, $price, $request->paymentMethod)
            ]);

        } catch (\Exception $e) {
            Log::error('Strategy session request error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting your request.'
            ], 500);
        }
    }

    /**
     * Email every corporate lead to the company inbox (reply-to = the submitter,
     * so the team can answer directly). Never breaks the submission.
     */
    private function sendLeadEmail(string $subject, string $body, ?string $replyTo = null): void
    {
        $to = config('corporate.lead_email', 'shulesoftcompany@gmail.com');
        try {
            Mail::raw($body, function ($m) use ($to, $subject, $replyTo) {
                $m->to($to)->subject($subject);
                if ($replyTo) {
                    $m->replyTo($replyTo);
                }
            });
        } catch (\Throwable $e) {
            Log::error('Corporate lead email failed: ' . $e->getMessage());
        }
    }

    /**
     * Send WhatsApp notification
     */
    private function sendWhatsAppNotification($phone, $message)
    {
        try {
            // Use the Setup controller's method for sending WhatsApp messages
            $setup = app(\App\Http\Controllers\Setup::class);
            $setup->sendTextMessage($phone, $message, 'whatsapp', 'corporate_notification');
            
        } catch (\Exception $e) {
            Log::error('WhatsApp notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Format timeline for display
     */
    private function formatTimeline($timeline)
    {
        $timelines = [
            'very_soon' => 'Very Soon (Within 2 weeks)',
            'within_month' => 'Within a Month',
            'within_3months' => 'Within 3 Months',
            'within_6months' => 'Within 6 Months'
        ];

        return $timelines[$timeline] ?? $timeline;
    }

    /**
     * Generate payment URL (placeholder for actual payment integration)
     */
    private function generatePaymentUrl($sessionId, $price, $paymentMethod)
    {
        // This would integrate with actual payment providers
        // Return appropriate payment URL based on the method
        return url("/payment/{$paymentMethod}/{$sessionId}");
    }
}