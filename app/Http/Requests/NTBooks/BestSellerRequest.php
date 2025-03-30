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
            'author' => 'string|nullable',
            'isbn' => ['string', 'regex:/^(?:\d{10}|\d{13})$/'],
            'title' => 'string|nullable',
            'offset' => 'integer|nullable',
            'age-group' => 'string|nullable',
            'price' => 'string|nullable',
            'publisher' => 'string|nullable',
            'contributor' => 'string|nullable',
        ];

    }
}
