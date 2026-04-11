<?php

namespace App\Services\Learning;

use App\Models\User;

class GamificationService
{
    public function addXP(User $user, int $amount): void
    {
        $currentXP = $user->preferences['xp'] ?? 0;
        $newXP = $currentXP + $amount;
        
        $preferences = $user->preferences;
        $preferences['xp'] = $newXP;
        
        $user->update(['preferences' => $preferences]);
    }

    public function getLevel(User $user): array
    {
        $xp = $user->preferences['xp'] ?? 0;
        $level = floor(sqrt($xp / 100)) + 1;
        
        $nextLevelXP = pow($level, 2) * 100;
        $currentLevelXP = pow($level - 1, 2) * 100;
        
        $progress = $xp > 0 ? (($xp - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100 : 0;

        return [
            'current' => $level,
            'xp' => $xp,
            'next_level_xp' => $nextLevelXP,
            'progress' => round($progress),
            'rank' => $this->getRankName($level)
        ];
    }

    private function getRankName(int $level): string
    {
        if ($level >= 50) return 'Aspirante Legendario';
        if ($level >= 30) return 'Maestro del Examen';
        if ($level >= 15) return 'Estudiante Avanzado';
        if ($level >= 5) return 'Aspirante Activo';
        return 'Novato';
    }
}
