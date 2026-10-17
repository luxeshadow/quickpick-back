<?php

namespace App\Http\Requests\Event;

use App\Enums\EventStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('events', 'name')->where('user_id', $this->user()?->id),
            ],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'status' => ['sometimes', Rule::enum(EventStatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l’événement est obligatoire.',
            'name.string' => 'Le nom de l’événement doit être une chaîne de caractères.',
            'name.max' => 'Le nom de l’événement ne doit pas dépasser 255 caractères.',
            'name.unique' => 'Vous avez déjà un événement portant ce nom.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'location.string' => 'Le lieu doit être une chaîne de caractères.',
            'location.max' => 'Le lieu ne doit pas dépasser 255 caractères.',
            'event_date.date' => 'La date de l’événement doit être une date valide.',
            'status.enum' => 'Le statut doit être active ou inactive.',
        ];
    }
}
