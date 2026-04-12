<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Enums\ExamType;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'type'               => ExamType::Practice,
            'exam_area'          => 1,
            'total_questions'    => 5,
            'time_limit_minutes' => 60,
            'status'             => ExamStatus::InProgress,
            'score'              => null,
            'started_at'         => now(),
            'completed_at'       => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status'     => ExamStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status'       => ExamStatus::Completed,
            'completed_at' => now(),
            'score'        => fake()->numberBetween(0, 5),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status'             => ExamStatus::InProgress,
            'started_at'         => now()->subHours(3),
            'time_limit_minutes' => 1,
        ]);
    }
}
