<?php

namespace App\Jobs;

use App\Models\Topic;
use App\Models\User;
use App\Services\AI\QuestionGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateQuestionBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected User $user,
        protected Topic $topic,
        protected int $difficulty,
        protected int $count = 5
    ) {}

    /**
     * Execute the job.
     */
    public function handle(QuestionGeneratorService $service): void
    {
        $service->generateBatch($this->topic, $this->difficulty, $this->user, $this->count);
    }
}
