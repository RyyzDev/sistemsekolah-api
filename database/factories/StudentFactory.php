<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'registration_number' => 'REG' . date('Y') . $this->faker->unique()->numerify('####'),
            'registration_type' => 'baru',
            'registration_path' => $this->faker->randomElement(['domisili', 'prestasi', 'afirmasi', 'mutasi']),
            'registration_date' => now(),
            'status' => 'draft',
            'full_name' => $this->faker->name(),
            'nickname' => $this->faker->optional()->firstName(),
            'nisn' => $this->faker->unique()->numerify('##########'),
            'nik' => $this->faker->unique()->numerify('################'),
            'no_kk' => $this->faker->numerify('################'),
            'no_akta_lahir' => $this->faker->optional()->numerify('##########'),
            'gender' => $this->faker->randomElement(['L', 'P']),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->date('Y-m-d', '2010-12-31'),
            'religion' => 'islam',
            'citizenship' => 'wni',
            'nationality' => 'Indonesia',
            'address' => $this->faker->address(),
            'rt' => $this->faker->numerify('###'),
            'rw' => $this->faker->numerify('###'),
            'dusun' => $this->faker->optional()->word(),
            'kelurahan' => $this->faker->city(),
            'kecamatan' => $this->faker->city(),
            'kabupaten_kota' => $this->faker->city(),
            'province' => $this->faker->state(),
            'postal_code' => $this->faker->numerify('#####'),
            'residence_type' => 'bersama_orangtua',
            'transportation' => 'jalan_kaki',
            'phone_number' => $this->faker->optional()->phoneNumber(),
            'mobile_number' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->email(),
        ];
    }

    public function submitted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'submitted',
            ];
        });
    }

    public function verified()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'verified',
            ];
        });
    }

    public function accepted()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'accepted',
            ];
        });
    }
}