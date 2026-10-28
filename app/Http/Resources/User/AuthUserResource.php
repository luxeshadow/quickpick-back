<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->attributesToArray(),
            'roles' => $this->resource->getRoleNames()->values()->all(),
        ];
    }
}
