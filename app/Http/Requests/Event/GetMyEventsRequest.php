<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class GetMyEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'Le nombre d’événements par page doit être un entier.',
            'per_page.min' => 'Le nombre d’événements par page doit être au moins 1.',
            'per_page.max' => 'Le nombre d’événements par page ne doit pas dépasser 100.',
            'page.integer' => 'Le numéro de page doit être un entier.',
            'page.min' => 'Le numéro de page doit être au moins 1.',
        ];
    }
}
