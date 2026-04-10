<?php

namespace App\Http\Controllers\Progress;

use App\Http\Controllers\Controller;
use App\Services\Learning\SpacedRepetitionService;
use Inertia\Inertia;
use Inertia\Response;

class SpacedRepetitionController extends Controller
{
    public function __construct(
        protected SpacedRepetitionService $srs
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $dueCards = $this->srs->getDueCards($user);

        return Inertia::render('Progress/Review', [
            'due_cards' => $dueCards,
            'total_due' => $dueCards->count(),
        ]);
    }
}
