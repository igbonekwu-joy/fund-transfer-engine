<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FundingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'narration' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'The amount is required.',
            'amount.integer' => 'The amount must be an integer in minor units.',
            'amount.min' => 'The amount must be a positive integer in minor units.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $narration = $this->validated('narration');

        return is_string($narration) && $narration !== ''
            ? ['narration' => $narration]
            : [];
    }
}
