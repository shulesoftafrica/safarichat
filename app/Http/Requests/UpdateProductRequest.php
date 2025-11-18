<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product') ? $this->route('product')->id : $this->route('id');
        
        return [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId)
            ],
            'category' => 'required|string|max:100',
            'description' => 'required_without:minimal_description|string|max:2000',
           // 'minimal_description' => 'required_without:description|string|max:500',
            'retail_price' => 'required|numeric|min:0|max:999999.99',
            'wholesale_price' => 'required|numeric|min:0|max:999999.99|lte:retail_price',
            'max_discount' => 'required|integer|min:0|max:100',
            'quantity' => 'nullable|integer|min:0',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'required|in:active,inactive,draft',
            'ai_generated_description' => 'boolean',
            'faq_questions' => 'nullable|array',
            'faq_questions.*' => 'string|max:500',
            'faq_answers' => 'nullable|array',
            'faq_answers.*' => 'string|max:1000',
            'product_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'product_attachment' => 'nullable|mimes:pdf|max:10240' // 10MB max
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required' => 'Product name is required.',
            'sku.required' => 'SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'category.required' => 'Category is required.',
            'description.required_without' => 'Description is required when not using AI generation.',
            'minimal_description.required_without' => 'Minimal description is required for AI generation.',
            'retail_price.required' => 'Retail price is required.',
            'retail_price.numeric' => 'Retail price must be a valid number.',
            'wholesale_price.required' => 'Wholesale price is required.',
            'wholesale_price.lte' => 'Wholesale price must not exceed retail price.',
            'max_discount.required' => 'Maximum discount is required.',
            'max_discount.max' => 'Maximum discount cannot exceed 100%.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be active, inactive, or draft.',
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
