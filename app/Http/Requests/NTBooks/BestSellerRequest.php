<?php

namespace App\Http\Requests\NTBooks;

use Illuminate\Foundation\Http\FormRequest;

class BestSellerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author' => 'string|nullable|max:255',
            'isbn' => ['string', 'regex:/^(?:\d{10}|\d{13})$/'],
            'title' => 'string|nullable|max:255',
            'offset' => 'integer|nullable|min:0',
            'age-group' => ['string', 'nullable', 'max:255', 'regex:/^[a-zA-Z\s\-]+$/'],
            'price' => ['string', 'nullable', 'max:255', 'regex:/^\d+(\.\d{1,2})?$/'],
            'publisher' => 'string|nullable|max:255',
            'contributor' => 'string|nullable|max:255',
        ];
    }
}
