<?php

namespace App\Http\Requests\Stats;

use Illuminate\Foundation\Http\FormRequest;

class UserStatsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array
    {
        return ['year' => ['sometimes', 'integer', 'between:1900,2100']];
    }
}
