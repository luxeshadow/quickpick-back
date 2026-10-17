<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DirectMediaUploadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:1000'],
            'files.*' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm',
                'max:512000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Sélectionnez au moins un fichier.',
            'files.array' => 'Les médias doivent être envoyés sous forme de liste.',
            'files.min' => 'Sélectionnez au moins un fichier.',
            'files.max' => 'Vous pouvez envoyer au maximum 1000 fichiers à la fois.',
            'files.*.required' => 'Chaque média est obligatoire.',
            'files.*.file' => 'Un des médias envoyés n’est pas un fichier valide.',
            'files.*.mimetypes' => 'Seuls les fichiers JPEG, PNG, WebP, MP4, MOV et WebM sont acceptés.',
            'files.*.max' => 'Chaque fichier doit peser au maximum 500 Mo.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->file('files', []) as $index => $file) {
                    if (str_starts_with((string) $file->getMimeType(), 'image/')
                        && $file->getSize() > 26214400) {
                        $validator->errors()->add("files.{$index}", 'Chaque image doit peser au maximum 25 Mo.');
                    }
                }
            },
        ];
    }
}

