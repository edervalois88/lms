<?php

namespace Database\Factories;

use App\Models\University;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UniversityFactory extends Factory
{
    protected $model = University::class;

    public function definition(): array
    {
        $name = fake()->company() . ' University';
        return [
            'name'        => $name,
            'acronym'     => strtoupper(Str::random(4)),
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->sentence(),
        ];
    }
}
