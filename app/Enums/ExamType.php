<?php

namespace App\Enums;

enum ExamType: string
{
    case Practice = 'practice';
    case Simulation = 'simulation';
    case Diagnostic = 'diagnostic';
}
