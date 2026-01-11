<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'parent_type' => $this->parent_type,
            'full_name' => $this->full_name,
            'nik' => $this->nik,
            'birth_place' => $this->birth_place,
            'birth_date' => $this->birth_date,
            'religion' => $this->religion,
            'citizenship' => $this->citizenship,
            'education' => $this->education,
            'occupation' => $this->occupation,
            'occupation_category' => $this->occupation_category,
            'monthly_income' => $this->monthly_income,
            'phone_number' => $this->phone_number,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'living_status' => $this->living_status,
            'is_guardian' => (bool) $this->is_guardian,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
