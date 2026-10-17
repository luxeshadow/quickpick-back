<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class SearchMyEventsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['required', 'string', 'min:1', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Le texte à rechercher est obligatoire.',
            'search.string' => 'La recherche doit être une chaîne de caractères.',
            'search.max' => 'La recherche ne doit pas dépasser 255 caractères.',
            'per_page.integer' => 'Le nombre d’événements par page doit être un entier.',
            'per_page.min' => 'Le nombre d’événements par page doit être au moins 1.',
            'per_page.max' => 'Le nombre d’événements par page ne doit pas dépasser 100.',
        ];
    }
}
