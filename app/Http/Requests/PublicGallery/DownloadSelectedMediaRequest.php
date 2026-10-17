<?php

namespace App\Http\Requests\PublicGallery;

use Illuminate\Foundation\Http\FormRequest;

class DownloadSelectedMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'media_ids' => ['required', 'array', 'min:1', 'max:100'],
            'media_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'media_ids.required' => 'Sélectionnez au moins un média.',
            'media_ids.array' => 'La sélection des médias doit être une liste.',
            'media_ids.min' => 'Sélectionnez au moins un média.',
            'media_ids.max' => 'Vous pouvez préparer au maximum 100 médias à la fois.',
            'media_ids.*.integer' => 'Chaque identifiant de média doit être un nombre entier.',
            'media_ids.*.distinct' => 'Un même média ne peut pas être sélectionné plusieurs fois.',
        ];
    }
}

