<?php

namespace App\Http\Requests\Otp;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOtpRequest extends FormRequest
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
            'email' => [
                'required',
                'email',
                'exists:users,email',
            ],

            'otp' => [
                'required',
                'digits:6',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'L’adresse e-mail n’est pas valide.',
            'email.exists' => 'Aucun compte n’est associé à cette adresse e-mail.',

            'otp.required' => 'Le code de vérification est obligatoire.',
            'otp.digits' => 'Le code de vérification doit contenir exactement 6 chiffres.',
        ];
    }
}