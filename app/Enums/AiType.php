<?php

namespace App\Enums;

enum AiType: string
{
    case Explanation = 'explanation';
    case Hint = 'hint';
    case StudyPlan = 'study_plan';
    case Feedback = 'feedback';
}
