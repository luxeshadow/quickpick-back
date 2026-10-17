<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetMyEventsByStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(['status' => $this->route('status')]);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(EventStatusEnum::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.enum' => 'Le statut doit être active ou inactive.',
            'per_page.integer' => 'Le nombre d’événements par page doit être un entier.',
            'per_page.min' => 'Le nombre d’événements par page doit être au moins 1.',
            'per_page.max' => 'Le nombre d’événements par page ne doit pas dépasser 100.',
        ];
    }
}
