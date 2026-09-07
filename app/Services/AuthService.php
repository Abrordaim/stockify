<?php

namespace App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * Attempt to authenticate a user with the given credentials.
     *
     * @param  array{email: string, password: string}  $credentials
     * @param  bool  $remember
     * @return \App\Models\User
     *
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false)
    {
        // Check if user exists
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => __('Email tidak ditemukan dalam sistem.'),
            ]);
        }

        // Attempt authentication via Laravel Auth
        if (!Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('Email atau password salah.'),
            ]);
        }

        // Regenerate session to prevent fixation
        session()->regenerate();

        return Auth::user();
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Get the dashboard route based on user role.
     */
    public function getDashboardRoute(string $role): string
    {
        return match ($role) {
            'admin', 'manager', 'staff' => 'dashboard',
            default => 'login',
        };
    }
}
