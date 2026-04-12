<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        $options = [
            fake()->sentence(4),
            fake()->sentence(4),
            fake()->sentence(4),
            fake()->sentence(4),
        ];

        return [
            'topic_id'       => Topic::factory(),
            'created_by'     => null,
            'type'           => 'multiple_choice',
            'stem'           => fake()->sentence(10) . '?',
            'options'        => $options,
            'correct_answer' => $options[0],
            'explanation'    => fake()->paragraph(),
            'difficulty'     => fake()->numberBetween(1, 5),
            'is_ai_generated'=> false,
            'is_active'      => true,
        ];
    }
}
