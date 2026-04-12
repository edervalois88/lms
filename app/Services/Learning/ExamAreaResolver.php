<?php

namespace App\Services\Learning;

use App\Models\Major;
use App\Models\User;

class ExamAreaResolver
{
    public function fromUser(User $user): int
    {
        return $this->fromMajor($user->major);
    }

    public function fromMajor(?Major $major): int
    {
        $division = mb_strtolower((string) ($major?->division_name ?? ''));

        if (preg_match('/area\s*([1-4])/', $division, $matches)) {
            return (int) $matches[1];
        }

        if (str_contains($division, 'fis') || str_contains($division, 'mat') || str_contains($division, 'ing')) {
            return 1;
        }

        if (str_contains($division, 'bio') || str_contains($division, 'salud') || str_contains($division, 'quim')) {
            return 2;
        }

        if (str_contains($division, 'social') || str_contains($division, 'econ') || str_contains($division, 'admin')) {
            return 3;
        }

        if (str_contains($division, 'human') || str_contains($division, 'arte') || str_contains($division, 'filo')) {
            return 4;
        }

        return 1;
    }
}
