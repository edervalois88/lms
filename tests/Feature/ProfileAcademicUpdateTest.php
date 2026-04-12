<?php

use App\Models\Major;
use App\Models\User;

it('updates user profile including selected major', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $major = Major::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nuevo Nombre',
            'email' => 'nuevo@email.com',
            'major_id' => $major->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $user->refresh();

    expect($user->name)->toBe('Nuevo Nombre')
        ->and($user->email)->toBe('nuevo@email.com')
        ->and($user->major_id)->toBe($major->id);
});

it('rejects profile update with non-existing major', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Usuario',
            'email' => 'usuario@email.com',
            'major_id' => 999999,
        ])
        ->assertSessionHasErrors('major_id');
});
