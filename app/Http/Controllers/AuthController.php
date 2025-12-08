<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Http\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $service): JsonResponse
    {
        $dto  = $request->toDTO();
        $data = $service->register($dto);

        return response()->json($data, 201);
    }

    public function login(Request $request): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! $token = $guard->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ]);
    }

    public function refresh(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        $token = $guard->refresh();

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
        ]);
    }

    public function logout(): Response
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        if ($token = request()->bearerToken()) {
            $guard->setToken($token)->invalidate(true);
        }

        return response()->noContent();
    }

    public function me(): UserResource
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return UserResource::make($guard->user());
    }
}
