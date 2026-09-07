<?php

use App\Services\AuthService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;

new #[Layout('layouts.app')] #[Title('Login - Stockify')] class extends Component
{
    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|min:6')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle login form submission.
     * Delegates authentication to AuthService (Service Layer).
     */
    public function login(): void
    {
        $this->validate();

        /** @var AuthService $authService */
        $authService = app(AuthService::class);

        // AuthService handles: find user via Repository → Auth::attempt → session regeneration
        $user = $authService->login(
            credentials: [
                'email' => $this->email,
                'password' => $this->password,
            ],
            remember: $this->remember
        );

        // Redirect to dashboard based on role
        $route = $authService->getDashboardRoute($user->role);

        $this->redirect(route($route), navigate: true);
    }
}; ?>

<div>
    <div class="flex min-h-screen bg-gray-50 dark:bg-gray-900">
        {{-- Left Panel: Hero Image --}}
        <div class="hidden relative lg:block lg:w-2/3">
            <img src="{{ asset('storage/images/hero.webp') }}" alt="Stockify Hero"
                 class="w-full h-screen object-cover">
            <div class="absolute inset-0 bg-gray-900/50 flex items-center px-20">
                <div class="text-left bg-black/70 p-8 rounded-sm">
                    <h2 class="text-4xl font-bold text-white">Stockify</h2>
                    <p class="max-w-xl mt-3 text-gray-300">
                        Sistem manajemen stok barang yang membantu bisnis Anda mengelola
                        gudang secara efisien dan akurat.
                    </p>
                </div>
            </div>
        </div>

        {{-- Right Panel: Login Form --}}
        <div class="flex flex-col items-center justify-center w-full px-6 py-8 mx-auto lg:w-1/3">
            {{-- Mobile Logo --}}
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 lg:hidden">Stockify</h1>

            <div class="w-full bg-white rounded-lg shadow dark:border sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-6 space-y-4 sm:p-8">
                    <h2 class="text-xl font-bold leading-tight tracking-tight text-center text-gray-900 md:text-2xl dark:text-white">
                        Login
                    </h2>

                    <x-molecules.form wire:submit="login" class="space-y-4 md:space-y-6">
                        {{-- Email --}}
                        <div>
                            <x-atoms.label for="email" label="Email" />

                            <x-atoms.input type="email" placeholder="name@company.com" wire:model="email" />
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <x-atoms.label for="password" label="Password" />
                            <x-atoms.input type="password" placeholder="••••••••" wire:model="password" />
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                         {{-- Remember Me --}}
                        <div class="flex items-center">
                            <input wire:model="remember" type="checkbox" id="remember"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 dark:bg-gray-700 dark:border-gray-600">
                            <label for="remember" class="ml-2 text-sm text-gray-500 dark:text-gray-300">
                                Ingat saya
                            </label>
                        </div>

                        <x-atoms.button-loading type="submit" wire:loading.attr="disabled" target="login" title="Sign in" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:opacity-50 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800" />
                    </x-molecules.form>

                </div>
            </div>
        </div>
    </div>
</div>
