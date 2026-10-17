<?php

namespace App\Http\Requests\Media;

use App\Enums\MediaTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexMediaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['type' => ['nullable', Rule::enum(MediaTypeEnum::class)]];
    }

    public function messages(): array
    {
        return ['type.enum' => 'Le type de média doit être image ou video.'];
    }
}
