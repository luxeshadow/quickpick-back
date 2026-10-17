<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('update', $this->user()); }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users')->ignore($this->user()->id)],
            'roles' => ['prohibited'], 'status' => ['prohibited'], 'email_verified_at' => ['prohibited'],
        ];
    }
}
