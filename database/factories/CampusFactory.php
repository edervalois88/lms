<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CampusFactory extends Factory
{
    protected $model = Campus::class;

    public function definition(): array
    {
        $name = fake()->city() . ' Campus';
        return [
            'university_id' => University::factory(),
            'name'          => $name,
            'slug'          => Str::slug($name) . '-' . Str::random(4),
            'location'      => fake()->city(),
        ];
    }
}
