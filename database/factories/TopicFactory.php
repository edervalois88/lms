<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TopicFactory extends Factory
{
    protected $model = Topic::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        return [
            'subject_id'      => Subject::factory(),
            'parent_id'       => null,
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . Str::random(4),
            'description'     => fake()->sentence(),
            'difficulty_base' => 3,
            'sort_order'      => 0,
        ];
    }
}
