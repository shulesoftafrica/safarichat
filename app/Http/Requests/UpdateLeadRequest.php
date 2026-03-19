<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
                'min:7',
                'max:20'
            ],
            'email' => 'nullable|email|max:255',
            'company_name' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:NEW,OUTREACHED,REPLIED,ENGAGED,QUALIFIED,PITCHED,DEMO_SCHEDULED,PROPOSAL_SENT,NEGOTIATING,CLOSED,LOST,HANDED_OFF,DO_NOT_CONTACT,NEEDS_ATTENTION,CONVERTED,CHURNED',
            'notes' => 'nullable|string',
            'business_id' => 'sometimes|required|exists:businesses,id',
            'ai_sales_agent_id' => 'nullable|exists:ai_sales_agents,id',
            'user_id' => 'nullable|exists:users,id',
            'deal_value' => 'nullable|numeric|min:0',
            'conversion_probability' => 'nullable|integer|min:0|max:100',
            'lead_score' => 'nullable|integer|min:0|max:100',
            'is_churned' => 'nullable|boolean',
            'churn_reason' => 'nullable|string',
            'churn_notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number format is invalid. Please use a valid international format (e.g., +1234567890, (123) 456-7890).',
            'phone_number.min' => 'Phone number must be at least 7 digits.',
            'phone_number.max' => 'Phone number must not exceed 20 characters.',
            'name.required' => 'Lead name is required.',
            'email.email' => 'Please provide a valid email address.',
            'business_id.required' => 'Business ID is required.',
            'business_id.exists' => 'Selected business does not exist.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitize phone number before validation
        if ($this->has('phone_number') && !empty($this->phone_number)) {
            $this->merge([
                'phone_number' => sanitize_phone_number($this->phone_number),
            ]);
        }
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone_number' => 'phone number',
            'company_name' => 'company name',
            'deal_value' => 'deal value',
            'conversion_probability' => 'conversion probability',
            'lead_score' => 'lead score',
        ];
    }
}
