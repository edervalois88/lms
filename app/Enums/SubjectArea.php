<?php

namespace App\Enums;

enum SubjectArea: int
{
    case Area1 = 1;
    case Area2 = 2;
    case Area3 = 3;
    case Area4 = 4;

    public function label(): string
    {
        return match($this) {
            self::Area1 => 'Ciencias Físico-Matemáticas y de las Ingenierías',
            self::Area2 => 'Ciencias Biológicas, Químicas y de la Salud',
            self::Area3 => 'Ciencias Sociales',
            self::Area4 => 'Humanidades y de las Artes',
        };
    }
}
