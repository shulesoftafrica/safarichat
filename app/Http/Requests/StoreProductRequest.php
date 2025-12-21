<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'description' => 'required_without:minimal_description|string|max:2000',
            'product_type' => 'required|in:tangible,service',
            'max_discount' => 'nullable|integer|min:0|max:100',
            'quantity' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'ai_generated_description' => 'boolean',
            'faq_questions' => 'nullable|array',
            'faq_questions.*' => 'string|max:500',
            'faq_answers' => 'nullable|array',
            'faq_answers.*' => 'string|max:1000',
            'faqs' => 'nullable|string', // JSON string from frontend
            'selling_points' => 'nullable|string', // JSON string from frontend
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'product_attachment' => 'nullable|mimes:pdf|max:10240' // 10MB max
        ];

        // Check if this is a tangible product or service
        $productType = $this->input('product_type');
        
        if ($productType === 'tangible') {
            // For tangible products, these fields are required
            $rules['sku'] = 'required|string|max:100|unique:products,sku';
            $rules['retail_price'] = 'required|numeric|min:0|max:999999.99';
            $rules['wholesale_price'] = 'required|numeric|min:0|max:999999.99|lte:retail_price';
            $rules['status'] = 'required|in:active,inactive,draft';
        } else {
            // For services, these fields are optional
            $rules['sku'] = 'nullable|string|max:100|unique:products,sku';
            $rules['retail_price'] = 'nullable|numeric|min:0|max:999999.99';
            $rules['wholesale_price'] = 'nullable|numeric|min:0|max:999999.99|lte:retail_price';
            $rules['status'] = 'nullable|in:active,inactive,draft';
            
            // Service-specific validations
            $rules['service_delivery_type'] = 'required|in:digital,physical,hybrid,consultation';
            $rules['pricing_type'] = 'required|in:one_time,monthly,yearly,per_hour,per_project,tiered';
            
            // Tiered pricing validation
            if ($this->input('pricing_type') === 'tiered') {
                $rules['tier_names'] = 'required|array|min:1';
                $rules['tier_names.*'] = 'required|string|max:100';
                $rules['tier_prices'] = 'required|array|min:1';
                $rules['tier_prices.*'] = 'required|numeric|min:0';
                $rules['tier_descriptions'] = 'nullable|array';
                $rules['tier_descriptions.*'] = 'nullable|string|max:255';
            }
        }
        
        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Product/Service name is required.',
            'product_type.required' => 'Product type is required.',
            'product_type.in' => 'Product type must be tangible or service.',
            'sku.required' => 'SKU is required for tangible products.',
            'sku.unique' => 'This SKU is already in use.',
            'category.required' => 'Category is required.',
            'description.required_without' => 'Description is required when not using AI generation.',
            'minimal_description.required_without' => 'Minimal description is required for AI generation.',
            'retail_price.required' => 'Retail price is required for tangible products.',
            'retail_price.numeric' => 'Retail price must be a valid number.',
            'wholesale_price.required' => 'Wholesale price is required for tangible products.',
            'wholesale_price.lte' => 'Wholesale price must not exceed retail price.',
            'max_discount.max' => 'Maximum discount cannot exceed 100%.',
            'status.required' => 'Status is required for tangible products.',
            'status.in' => 'Status must be active, inactive, or draft.',
            'service_delivery_type.required' => 'Service delivery type is required for services.',
            'service_delivery_type.in' => 'Service delivery type must be digital, physical, hybrid, or consultation.',
            'pricing_type.required' => 'Pricing type is required for services.',
            'pricing_type.in' => 'Pricing type must be one_time, monthly, yearly, per_hour, per_project, or tiered.',
            'tier_names.required' => 'At least one tier name is required for tiered pricing.',
            'tier_names.*.required' => 'Tier name is required.',
            'tier_prices.required' => 'At least one tier price is required for tiered pricing.',
            'tier_prices.*.required' => 'Tier price is required.',
            'tier_prices.*.numeric' => 'Tier price must be a valid number.',
            'product_image.image' => 'Product image must be a valid image file.',
            'product_image.mimes' => 'Product image must be jpeg, png, jpg, or gif.',
            'product_image.max' => 'Product image size cannot exceed 5MB.',
            'product_attachment.mimes' => 'Product attachment must be a PDF file.',
            'product_attachment.max' => 'Product attachment size cannot exceed 10MB.'
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Convert checkbox values
        $this->merge([
            'ai_generated_description' => $this->boolean('ai_generated_description'),
        ]);

        // Handle tags
        if ($this->has('tags') && is_array($this->tags)) {
            $this->merge([
                'tags' => array_filter($this->tags)
            ]);
        }

        // Filter empty FAQ entries
        if ($this->has('faq_questions') && $this->has('faq_answers')) {
            $questions = array_filter($this->faq_questions);
            $answers = array_filter($this->faq_answers);
            
            $this->merge([
                'faq_questions' => $questions,
                'faq_answers' => $answers
            ]);
        }
    }
}
