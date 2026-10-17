<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'provider' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', 'string', 'max:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'external_reference' => ['nullable', 'string', 'max:255', 'unique:payments,external_reference'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'L’abonnement est obligatoire.',
            'subscription_id.exists' => 'L’abonnement sélectionné est introuvable.',
            'provider.required' => 'Le fournisseur de paiement est obligatoire.',
            'provider.max' => 'Le fournisseur ne doit pas dépasser 100 caractères.',
            'payment_method.required' => 'Le moyen de paiement est obligatoire.',
            'payment_method.max' => 'Le moyen de paiement ne doit pas dépasser 100 caractères.',
            'currency.size' => 'La devise doit contenir exactement trois lettres.',
            'external_reference.unique' => 'Cette référence de paiement est déjà utilisée.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('currency')) {
            $this->merge(['currency' => strtoupper((string) $this->input('currency'))]);
        }
    }
}
