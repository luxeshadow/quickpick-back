<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')
                    ->where(fn (Builder $query) => $query->where('is_active', true)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_plan_id.required' => 'Le plan d’abonnement est obligatoire.',
            'subscription_plan_id.integer' => 'L’identifiant du plan d’abonnement doit être un entier.',
            'subscription_plan_id.exists' => 'Le plan d’abonnement sélectionné est introuvable ou inactif.',
        ];
    }
}
