<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form Request validation for Billing Webhook payloads
 * 
 * This validates the structure and data types of incoming webhook payloads
 * before they reach the controller, ensuring data integrity.
 */
class BillingWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Authorization is handled by signature validation in the controller,
     * so we always return true here.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Core webhook fields
            'event' => ['required', 'string', 'in:payment.success,payment.failed,subscription.created,subscription.renewed,subscription.upgraded,subscription.cancelled,subscription.expired,credits.purchased'],
            'event_id' => ['nullable', 'string', 'max:100'],
            'timestamp' => ['required', 'date'],
            'api_version' => ['nullable', 'string'],
            
            // Customer/Business identification (at least one required)
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'business_id' => ['nullable', 'integer', 'min:1'],
            'customer' => ['nullable', 'array'],
            'customer.id' => ['nullable', 'integer'],
            'customer.email' => ['nullable', 'email'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            
            // Payment details (for payment events)
            'payment' => ['nullable', 'array'],
            'payment.transaction_id' => ['required_with:payment', 'string', 'max:255'],
            'payment.amount' => ['required_with:payment', 'numeric', 'min:0'],
            'payment.currency' => ['required_with:payment', 'string', 'size:3'],
            'payment.status' => ['nullable', 'string', 'in:success,paid,completed,failed,pending,cancelled,refunded'],
            'payment.method' => ['nullable', 'string', 'in:stripe,flutterwave,ucn,lipa_namba,mpesa,card,bank_transfer,unknown'],
            'payment.paid_at' => ['nullable', 'date'],
            'payment.gateway' => ['nullable', 'string', 'max:50'],
            
            // Subscription details (for subscription events)
            'subscription' => ['nullable', 'array'],
            'subscription.id' => ['nullable', 'integer'],
            'subscription.status' => ['nullable', 'string', 'max:50'],
            // Legacy field names kept for backwards compat
            'subscription.plan_id' => ['nullable', 'string', 'max:100'],
            'subscription.plan' => ['nullable', 'string', 'max:100'],
            // Actual field names from billing platform
            'subscription.price_plan_name' => ['nullable', 'string', 'max:100'],
            'subscription.billing_interval' => ['nullable', 'string', 'in:monthly,quarterly,yearly,annual,weekly,daily'],
            'subscription.amount' => ['nullable', 'numeric', 'min:0'],
            'subscription.currency' => ['nullable', 'string', 'max:10'],
            'subscription.current_period_start' => ['nullable', 'date'],
            'subscription.current_period_end' => ['nullable', 'date'],
            'subscription.trial_ends_at' => ['nullable', 'date'],
            'subscription.duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'subscription.ai_credits' => ['nullable', 'integer', 'min:0'],
            'subscription.features' => ['nullable', 'array'],
            'subscription.features.max_contacts' => ['nullable', 'integer', 'min:0'],
            'subscription.features.max_products' => ['nullable', 'integer', 'min:0'],
            'subscription.features.whatsapp_channels' => ['nullable', 'integer', 'min:0'],
            'subscription.features.customer_followups' => ['nullable', 'boolean'],
            'subscription.features.customer_categorization' => ['nullable', 'boolean'],
            'subscription.features.booking_calendars' => ['nullable', 'boolean'],
            'subscription.features.sales_reports' => ['nullable', 'boolean'],
            
            // Invoice details (sent with payment events)
            'invoice' => ['nullable', 'array'],
            'invoice.items' => ['nullable', 'array'],
            'invoice.items.*.price_plan_name' => ['nullable', 'string', 'max:100'],
            'invoice.items.*.amount' => ['nullable', 'numeric'],
            'invoice.items.*.quantity' => ['nullable', 'numeric'],
            
            // Credits purchase — platform sends credits as an object {id, amount, balance, ...}
            'credits' => ['nullable', 'array'],
            'credits.id' => ['nullable', 'integer'],
            'credits.amount' => ['nullable', 'integer', 'min:0'],
            'credits.balance' => ['nullable', 'integer', 'min:0'],
            'credits.description' => ['nullable', 'string', 'max:255'],
            'credits.purchased_at' => ['nullable', 'date'],

            // Upgrade block (subscription.upgraded event)
            'upgrade' => ['nullable', 'array'],
            'upgrade.previous_plan' => ['nullable', 'array'],
            'upgrade.new_plan' => ['nullable', 'array'],
            'upgrade.new_plan.name' => ['nullable', 'string', 'max:100'],
            'upgrade.upgraded_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event.required' => 'Webhook event type is required',
            'event.in' => 'Invalid webhook event type. Must be one of: payment.success, payment.failed, subscription.created, subscription.renewed, subscription.upgraded, subscription.cancelled, subscription.expired, credits.purchased',
            'timestamp.required' => 'Webhook timestamp is required',
            'timestamp.date' => 'Webhook timestamp must be a valid date',
            'payment.transaction_id.required_with' => 'Transaction ID is required when payment data is provided',
            'payment.amount.required_with' => 'Payment amount is required when payment data is provided',
            'payment.currency.size' => 'Currency code must be exactly 3 characters (e.g., USD, TZS)',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation: Ensure either customer_id or business_id is provided
            if (!$this->input('customer_id') && !$this->input('business_id')) {
                $validator->errors()->add(
                    'customer_id',
                    'Either customer_id or business_id must be provided'
                );
            }
            
            // Custom validation: Payment events must include payment data
            $paymentEvents = ['payment.success', 'payment.failed'];
            if (in_array($this->input('event'), $paymentEvents) && !$this->input('payment')) {
                $validator->errors()->add(
                    'payment',
                    'Payment data is required for ' . $this->input('event') . ' events'
                );
            }
            
            // Custom validation: Subscription events should include subscription data
            $subscriptionEvents = ['subscription.created', 'subscription.renewed'];
            if (in_array($this->input('event'), $subscriptionEvents) && !$this->input('subscription')) {
                $validator->errors()->add(
                    'subscription',
                    'Subscription data is required for ' . $this->input('event') . ' events'
                );
            }
            
            // Custom validation: Credits purchased event must include credits amount
            if ($this->input('event') === 'credits.purchased' && !$this->input('credits')) {
                $validator->errors()->add(
                    'credits',
                    'Credits amount is required for credits.purchased events'
                );
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        \Log::warning('Webhook validation failed', [
            'errors' => $validator->errors()->toArray(),
            'payload' => $this->all(),
            'ip' => $this->ip()
        ]);

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => 'The webhook payload does not match the expected format',
                'errors' => $validator->errors()
            ], 400)
        );
    }
}
