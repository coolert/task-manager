<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\withHeader;

it('login works and returns bearer token', function () {
    $pwd  = 'secret123';
    $user = User::factory()->create(['password' => bcrypt($pwd)]);

    $response = requestAs($user, 'POST', '/api/auth/login', [
        'email'    => $user->email,
        'password' => $pwd,
    ])->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'expires_in']);

    expect($response->json('token_type'))->toBe('Bearer');
});

it('me / refresh / logout', function () {
    $user = User::factory()->create();

    requestAs($user, 'GET', '/api/auth/me')
        ->assertOk()
        ->assertJson(['id' => $user->id]);

    requestAs($user, 'POST', '/api/auth/refresh')
        ->assertOk()
        ->assertJsonStructure(['token']);

    requestAs($user, 'POST', '/api/auth/logout')
        ->assertNoContent();

});

it('rejects unauthenticated and invalid token', function () {
    // unauthenticated
    getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Unauthenticated',
        ]);

    // invalid token
    withHeader('Authorization', 'Bearer invalid.token.value')
        ->getJson('/api/auth/me')
        ->assertJson([
            'message' => 'Unauthenticated',
        ]);
});
