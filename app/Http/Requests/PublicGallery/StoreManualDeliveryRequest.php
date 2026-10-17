<?php

namespace App\Http\Requests\PublicGallery;

class StoreManualDeliveryRequest extends DownloadSelectedMediaRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'channel' => ['required', 'in:email'],
            'recipient' => ['required', 'email:rfc', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'channel.required' => 'Le moyen d’envoi est obligatoire.',
            'channel.in' => 'Pour le moment, l’envoi est disponible uniquement par e-mail.',
            'recipient.required' => 'L’adresse e-mail du destinataire est obligatoire.',
            'recipient.email' => 'Saisissez une adresse e-mail valide.',
            'recipient.max' => 'L’adresse e-mail ne peut pas dépasser 255 caractères.',
        ]);
    }
}

