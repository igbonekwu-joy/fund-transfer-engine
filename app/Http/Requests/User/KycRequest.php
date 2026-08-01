<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class KycRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'tier' => $this->input('tier') ? strtolower($this->input('tier')) : null,
            'provider' => $this->input('provider') ? strtolower($this->input('provider')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tier' => ['required', 'string', 'in:tier1,tier2,tier3'],
            'provider' => ['nullable', 'required_if:tier,tier1', 'string', 'in:nin,bvn'],
            'id_number' => ['required_if:tier,tier1', 'string', 'regex:/^\d{11}$/'],
            'supporting_document_type' => ['required_if:tier,tier2', 'string', 'in:passport,utility_bill,bank_statement'],
            'supporting_document_value' => ['required_if:tier,tier2', 'string', 'max:255'],
            'tier3_consent' => ['required_if:tier,tier3', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tier.in' => 'The selected tier must be tier1, tier2, or tier3.',
            'provider.in' => 'The selected provider must be nin or bvn.',
            'id_number.regex' => 'The id number must be 11 digits.',
            'supporting_document_type.in' => 'The document type must be passport, utility_bill, or bank_statement.',
            'tier3_consent.boolean' => 'The tier3 consent must be true or false.',
        ];
    }
}
