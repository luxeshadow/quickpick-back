<?php

namespace App\Http\Requests\AccessLink;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccessLinkRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'expires_at' => ['sometimes', 'date', 'after:now'],
            'max_scans' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'expires_at.date' => 'La date d’expiration doit être une date valide.',
            'expires_at.after' => 'La date d’expiration doit être située dans le futur.',
            'max_scans.integer' => 'Le nombre maximal de scans doit être un entier.',
            'max_scans.min' => 'Le nombre maximal de scans doit être d’au moins un.',
        ];
    }
}
