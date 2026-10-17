<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'provider' => ['sometimes', 'required', 'string', 'max:100'],
            'payment_method' => ['sometimes', 'required', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(PaymentStatusEnum::class)],
            'external_reference' => [
                'sometimes', 'nullable', 'string', 'max:255',
                Rule::unique('payments', 'external_reference')->ignore($this->route('payment')),
            ],
            'paid_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'provider.required' => 'Le fournisseur de paiement ne peut pas être vide.',
            'payment_method.required' => 'Le moyen de paiement ne peut pas être vide.',
            'status.enum' => 'Le statut doit être pending, paid, failed, cancelled ou refunded.',
            'external_reference.unique' => 'Cette référence de paiement est déjà utilisée.',
            'paid_at.date' => 'La date de paiement doit être une date valide.',
        ];
    }
}
