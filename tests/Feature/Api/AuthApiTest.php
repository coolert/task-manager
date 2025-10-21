<?php

use App\Models\User;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeaders;

use PHPOpenSourceSaver\JWTAuth\JWTGuard;

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

it('login fails with wrong credentials (401)', function () {
    $user = User::factory()->create(['password' => bcrypt('secret1234')]);

    postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid credentials']);
});

it('login validation fails (422)', function () {
    postJson('/api/auth/login', ['email' => ''])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['email']]);
});

it('me / refresh / logout', function () {
    $user = User::factory()->create();

    requestAs($user, 'GET', '/api/auth/me')
        ->assertOk()
        ->assertJson(['id' => $user->id]);

    requestAs($user, 'POST', '/api/auth/refresh')
        ->assertOk()
        ->assertJsonStructure(['token', 'token_type', 'expires_in']);

    /** @var JWTGuard $guard */
    $guard   = auth('api');
    $token   = $guard->fromUser($user);
    $headers = ['Authorization' => "Bearer {$token}"];
    withHeaders($headers)->json('POST', '/api/auth/logout')
        ->assertNoContent();

    withHeaders($headers)->json('POST', '/api/auth/refresh')
        ->assertUnauthorized();
});

it('rejects unauthenticated and invalid token', function () {
    // unauthenticated
    getJson('/api/auth/me')
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Unauthenticated',
        ]);

    // invalid token
    getJson('/api/auth/me', ['Authorization' => 'Bearer invalid.token.value'])
        ->assertUnauthorized()
        ->assertJson([
            'message' => 'Unauthenticated',
        ]);
});
