<?php

use App\Models\Major;
use App\Services\Learning\ExamAreaResolver;

it('resolves area from explicit division number', function () {
    $resolver = app(ExamAreaResolver::class);
    $major = new Major(['division_name' => 'Área 3 - Ciencias Sociales']);

    expect($resolver->fromMajor($major))->toBe(3);
});

it('resolves area using semantic keywords', function () {
    $resolver = app(ExamAreaResolver::class);

    $engineering = new Major(['division_name' => 'Ingenierias']);
    $health = new Major(['division_name' => 'Biológicas y Salud']);

    expect($resolver->fromMajor($engineering))->toBe(1)
        ->and($resolver->fromMajor($health))->toBe(2);
});
