<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GetValueAtTimestampRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'key.required' => 'The key field is required.',
            'key.string' => 'The key must be a string.',
            'key.max' => 'The key may not be greater than 255 characters.',
            'timestamp.required' => 'The timestamp field is required.',
            'timestamp.integer' => 'The timestamp must be an integer.',
            'timestamp.min' => 'The timestamp must be a valid Unix timestamp.',
        ];
    }

    /**
     * route parameter to validation input
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'key' => $this->route('key'),
        ]);
    }
}
