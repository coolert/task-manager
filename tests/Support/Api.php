<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Testing\TestResponse;

use function Pest\Laravel\withHeaders;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

/**
 * @param  array<string,mixed>  $json
 * @param  array<string,mixed>  $headers
 *
 * @return TestResponse<Response>
 */
function requestAs(User $user, string $method, string $uri, array $json = [], array $headers = []): TestResponse
{
    $token   = JWTAuth::fromUser($user);
    $headers = ['Authorization' => "Bearer {$token}"] + $headers;

    return withHeaders($headers)->json($method, $uri, $json);
}

/**
 * @param array{
 *     project: Project,
 *     owner: User,
 *     admin: User,
 *     member: User,
 *     viewer: User,
 *     outsider: User
 * } $ctx
 */
function userFor(array $ctx, string $role): User
{
    return $ctx[$role];
}

/**
 * @param array{
 *      project: Project,
 *      owner: User,
 *      admin: User,
 *      member: User,
 *      viewer: User,
 *      outsider: User } $ctx
 * @param array{
 *     owner?: int,
 *     admin?: int,
 *     member?: int,
 *     viewer?: int,
 *     outsider?: int
 * } $cases
 * @param  array<string,mixed>  $json
 */
function assertRoleMatrix(array $ctx, string $method, string $uri, array $cases, array $json = []): void
{
    foreach ($cases as $role => $expected) {
        requestAs(userFor($ctx, $role), $method, $uri, $json)->assertStatus($expected);
    }
}
