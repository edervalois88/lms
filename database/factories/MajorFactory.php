<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MajorFactory extends Factory
{
    protected $model = Major::class;

    public function definition(): array
    {
        $name = fake()->word() . ' Engineering';
        return [
            'campus_id'      => Campus::factory(),
            'name'           => $name,
            'slug'           => Str::slug($name) . '-' . Str::random(4),
            'division_name'  => 'Área ' . fake()->numberBetween(1, 4),
            'min_score'      => fake()->numberBetween(40, 80),
            'applicants'     => fake()->numberBetween(500, 5000),
            'places'         => fake()->numberBetween(50, 500),
            'holland_code'   => null,
            'description'    => fake()->sentence(),
        ];
    }
}
