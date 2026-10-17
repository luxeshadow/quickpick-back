<?php

namespace App\Http\Requests\GuestScan;

use Illuminate\Foundation\Http\FormRequest;

class StoreGuestScanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'selfie' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'consent' => ['required', 'accepted'],
        ];
    }
}
