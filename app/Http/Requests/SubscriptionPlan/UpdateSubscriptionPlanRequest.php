<?php

namespace App\Http\Requests\SubscriptionPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'name')->ignore($this->route('subscription_plan')),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'decimal:0,2'],
            'duration_days' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_events' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'max_photos' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'accept_video' => ['sometimes', 'boolean'],
            'max_videos' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du plan d’abonnement ne peut pas être vide.',
            'name.unique' => 'Un plan d’abonnement portant ce nom existe déjà.',
            'name.max' => 'Le nom du plan ne doit pas dépasser 255 caractères.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'price.required' => 'Le prix du plan ne peut pas être vide.',
            'price.numeric' => 'Le prix du plan doit être un nombre.',
            'price.min' => 'Le prix du plan ne peut pas être négatif.',
            'price.decimal' => 'Le prix doit comporter au maximum deux décimales.',
            'duration_days.integer' => 'La durée du plan doit être exprimée en jours entiers.',
            'duration_days.min' => 'La durée du plan doit être d’au moins un jour.',
            'max_events.integer' => 'La limite d’événements doit être un entier.',
            'max_events.min' => 'La limite d’événements doit être d’au moins un.',
            'max_photos.integer' => 'La limite de photos doit être un entier.',
            'max_photos.min' => 'La limite de photos doit être d’au moins une.',
            'is_active.boolean' => 'Le champ actif doit être vrai ou faux.',
            'accept_video.boolean' => 'Le champ acceptation des vidéos doit être vrai ou faux.',
            'max_videos.integer' => 'La limite de vidéos doit être un entier.',
            'max_videos.min' => 'La limite de vidéos doit être d’au moins une.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $plan = $this->route('subscription_plan');
                $acceptVideo = $this->has('accept_video')
                    ? $this->boolean('accept_video')
                    : (bool) $plan?->accept_video;
                $maxVideos = $this->has('max_videos')
                    ? $this->input('max_videos')
                    : $plan?->max_videos;

                if ($acceptVideo && $maxVideos === null) {
                    $validator->errors()->add(
                        'max_videos',
                        'Une limite vidéo est obligatoire lorsque les vidéos sont autorisées.'
                    );
                }
            },
        ];
    }
}
