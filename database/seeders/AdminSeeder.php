<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@siakad.sch.id'],
                [
                    'name' => 'Administrator',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
             );

        $admin->assignRole('Admin');
    }
}