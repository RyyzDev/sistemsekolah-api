<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'document_type' => $this->document_type,
            'document_name' => $this->document_name,
            'file_path' => $this->file_path,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'description' => $this->description,
            'is_required' => (bool) $this->is_required,
            'is_verified' => (bool) $this->is_verified,
            'verified_at' => $this->verified_at,
            'verified_by' => $this->verified_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}