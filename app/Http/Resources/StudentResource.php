<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'full_name' => $this->full_name,
            'nisn' => $this->nisn,
            'gender' => $this->gender,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'photo' => $this->photo,

            // // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'parents' => ParentResource::collection($this->whenLoaded('parents')),
            'achievements' => AchievementResource::collection($this->whenLoaded('achievements')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}