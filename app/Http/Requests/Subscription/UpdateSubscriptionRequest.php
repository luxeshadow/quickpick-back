<?php

namespace App\Http\Requests\Subscription;

use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subscription_plans', 'id')
                    ->where(fn (Builder $query) => $query->where('is_active', true)),
            ],
            'status' => ['sometimes', Rule::enum(SubscriptionStatusEnum::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_plan_id.required' => 'Le plan d’abonnement est obligatoire lorsqu’il est envoyé.',
            'subscription_plan_id.integer' => 'L’identifiant du plan d’abonnement doit être un entier.',
            'subscription_plan_id.exists' => 'Le plan d’abonnement sélectionné est introuvable ou inactif.',
            'status.enum' => 'Le statut doit être active, cancelled ou expired.',
        ];
    }
}
