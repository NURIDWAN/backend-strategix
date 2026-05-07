<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret')) || empty(config('services.google.redirect'))) {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');
            return redirect()->away($frontendUrl . '/login?google_error=' . urlencode('Google OAuth belum dikonfigurasi di server.'));
        }

        return app('Laravel\\Socialite\\Contracts\\Factory')
            ->driver('google')
            ->stateless()
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        try {
            $googleUser = app('Laravel\\Socialite\\Contracts\\Factory')
                ->driver('google')
                ->stateless()
                ->user();
        } catch (\Throwable $e) {
            return redirect()->away($frontendUrl . '/login?google_error=' . urlencode('Google login gagal. Silakan coba lagi.'));
        }

        $email = $googleUser->getEmail();

        if (!$email) {
            return redirect()->away($frontendUrl . '/login?google_error=' . urlencode('Akun Google tidak memiliki email yang valid.'));
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $name = $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Google User');

            $user = User::create([
                'name' => $name,
                'username' => $this->generateUniqueUsername($name, $email),
                'email' => $email,
                'phone' => 'google_' . Str::lower(Str::random(16)),
                'password' => Str::random(40),
                'role' => 'user',
                'email_verified_at' => now(),
                'profile_photo' => $googleUser->getAvatar(),
            ]);
        }

        if ($user->account_status === 'banned') {
            return redirect()->away($frontendUrl . '/login?google_error=' . urlencode('Akun Anda telah diblokir. Silakan hubungi admin.'));
        }

        if ($user->account_status === 'inactive') {
            return redirect()->away($frontendUrl . '/login?google_error=' . urlencode('Akun Anda tidak aktif. Silakan hubungi admin.'));
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect()->away($frontendUrl . '/login?google_token=' . urlencode($token));
    }

    private function generateUniqueUsername(string $name, string $email): string
    {
        $base = Str::slug(Str::before($email, '@'), '');

        if (empty($base)) {
            $base = Str::slug($name, '');
        }

        if (empty($base)) {
            $base = 'user';
        }

        $base = Str::lower(substr($base, 0, 20));
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $suffix = (string) $counter;
            $username = substr($base, 0, max(1, 20 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $username;
    }
}
