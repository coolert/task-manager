<?php

use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeaders;

use PHPOpenSourceSaver\JWTAuth\JWTGuard;

it('registers a new user and returns token + user resource', function () {
    $payload = [
        'name'                  => 'John Doe',
        'email'                 => 'john@example.com',
        'password'              => 'secret123',
        'password_confirmation' => 'secret123',
    ];

    $response = postJson('/api/auth/register', $payload);

    $response->assertCreated();

    $response->assertJson(fn (AssertableJson $json) => $json->has('token')
        ->where('token_type', 'Bearer')
        ->has('expires_in')
        ->has('user', fn ($json) => $json->where('name', 'John Doe')
            ->where('email', 'john@example.com')
            ->etc()
        )
    );

    assertDatabaseHas('users', [
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $user = User::firstWhere('email', 'john@example.com');
    expect(Hash::check('secret123', $user->password))->toBeTrue();
});

it('fails if email already exists', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $payload = [
        'name'                  => 'Test',
        'email'                 => 'dup@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = postJson('/api/auth/register', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('fails when validation rules are not met', function () {
    $payload = [
        'name'                  => '',
        'email'                 => 'not-an-email',
        'password'              => '123',
        'password_confirmation' => '456',
    ];

    $response = postJson('/api/auth/register', $payload);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

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
        ->assertJsonPath('data.id', $user->id);

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
