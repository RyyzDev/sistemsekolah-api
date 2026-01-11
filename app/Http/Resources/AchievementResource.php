<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AchievementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'achievement_name' => $this->achievement_name,
            'achievement_type' => $this->achievement_type,
            'level' => $this->level,
            'rank' => $this->rank,
            'organizer' => $this->organizer,
            'achievement_date' => $this->achievement_date,
            'year' => $this->year,
            'description' => $this->description,
            'certificate_file' => $this->certificate_file,
            'points' => $this->points,
        ];
    }
}