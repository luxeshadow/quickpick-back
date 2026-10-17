<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPaymentsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                Rule::when($this->filled('date_from'), 'after_or_equal:date_from'),
            ],
            'status' => ['nullable', Rule::enum(PaymentStatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.date' => 'La date de début doit être une date valide.',
            'date_to.date' => 'La date de fin doit être une date valide.',
            'date_to.after_or_equal' => 'La date de fin doit être postérieure ou égale à la date de début.',
            'status.enum' => 'Le statut de paiement est invalide.',
        ];
    }
}
