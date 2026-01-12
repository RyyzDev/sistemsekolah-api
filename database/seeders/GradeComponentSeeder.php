<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeComponent;

class GradeComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'name' => 'Tugas',
                'code' => 'tugas',
                'default_weight' => 20,
                'sort_order' => 1,
            ],
            [
                'name' => 'Ulangan Harian (UH)',
                'code' => 'uh',
                'default_weight' => 30,
                'sort_order' => 2,
            ],
            [
                'name' => 'Ujian Tengah Semester (UTS)',
                'code' => 'uts',
                'default_weight' => 20,
                'sort_order' => 3,
            ],
            [
                'name' => 'Ujian Akhir Semester (UAS)',
                'code' => 'uas',
                'default_weight' => 30,
                'sort_order' => 4,
            ],
        ];

        foreach ($components as $component) {
            GradeComponent::firstOrCreate(
                ['code' => $component['code']],
                $component
            );
        }
    }
}