<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'default_weight',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }
}