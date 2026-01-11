<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'google_id' => $this->google_id,
            'name' => $this->name,
            'role' => $this->role,
            'email' => $this->email,
            'contact' => $this->contact,
        ];
    }
}
