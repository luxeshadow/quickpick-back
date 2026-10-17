<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'full_name' => $this->full_name, 'email' => $this->email,
            'phone' => $this->phone, 'status' => $this->status->value,
            'registered_at' => $this->created_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
        ];
    }
}
