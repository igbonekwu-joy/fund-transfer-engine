<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
            'account_number' => ['required', 'string', 'regex:/^\d{10}$/'],
            'amount' => ['required', 'integer', 'min:1'],
            'narration' => ['nullable', 'string', 'max:255'],
            // Sender is always the auth user's primary wallet; recipient is account_number only.
            'account_id' => ['prohibited'],
            'from_account_id' => ['prohibited'],
            'to_account_id' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_number.required' => 'The recipient account number is required.',
            'account_number.regex' => 'The recipient account number must be 10 digits.',
            'amount.required' => 'The amount is required.',
            'amount.integer' => 'The amount must be an integer in minor units.',
            'amount.min' => 'The amount must be a positive integer in minor units.',
            'account_id.prohibited' => 'Do not send account_id; transfers always debit your primary wallet.',
            'from_account_id.prohibited' => 'Do not send from_account_id; transfers always debit your primary wallet.',
            'to_account_id.prohibited' => 'Identify the recipient with account_number, not to_account_id.',
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
