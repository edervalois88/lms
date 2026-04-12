<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        $name = fake()->word() . ' ' . fake()->word();
        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->sentence(),
            'icon'        => null,
            'color'       => '#F97316',
            'exam_areas'  => [1],
            'sort_order'  => 0,
        ];
    }
}
