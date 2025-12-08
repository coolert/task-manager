<?php

namespace App\Http\Services;

use App\DTOs\RegisterDTO;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthService
{
    /**
     * @return array<string, mixed>
     */
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => Hash::make($dto->password),
        ]);

        /** @var JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 60) * 60,
            'user'       => UserResource::make($guard->user()),
        ];
    }
}
