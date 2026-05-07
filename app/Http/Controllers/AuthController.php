<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Setting;
use App\Models\Affiliate\AffiliateLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use App\Services\AffiliateService;

class AuthController extends Controller
{
    protected $affiliateService;

    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
    }

    public function register(Request $request)
    {
        return $this->processRegistration($request, 'user');
    }

    public function registerConsultant(Request $request)
    {
        return $this->processRegistration($request, 'consultant');
    }

    private function processRegistration(Request $request, $role = 'user')
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => [
                'required',
                'string',
                'unique:users',
                function ($attribute, $value, $fail) {
                    $cleanPhone = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($cleanPhone) < 10 || strlen($cleanPhone) > 15) {
                        $fail('Format nomor telepon tidak valid.');
                    }
                },
            ],
            'password' => 'required|string|min:8|confirmed',
            'referral_code' => 'nullable|string',
        ], [
            'phone.unique' => 'Nomor HP sudah terdaftar.',
            'email.unique' => 'Email sudah terdaftar.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check referral code
        $referredByUserId = null;
        if ($request->filled('referral_code')) {
            $affiliateLink = AffiliateLink::where('slug', $request->referral_code)
                ->where('is_active', true)
                ->first();

            if ($affiliateLink) {
                $referredByUserId = $affiliateLink->user_id;
                Log::info('[Register] User registered via affiliate link', [
                    'referral_code' => $request->referral_code,
                    'referrer_user_id' => $referredByUserId,
                ]);
            }
        }

        $otpEnabled = Setting::getValue('otp_enabled', false);

        // Buat user baru
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'referred_by_user_id' => $referredByUserId,
            'role' => $role,
            'email_verified_at' => $otpEnabled ? null : now(),
            'phone_verified_at' => $otpEnabled ? null : now(), // bypass phone verified too
        ]);

        if ($otpEnabled) {
            // Generate dan kirim OTP via Email
            $otp = $user->generateOtp();

            try {
                Mail::to($user->email)->send(new OtpMail($otp));
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email: ' . $e->getMessage());
                $user->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim email verifikasi. Pastikan email Anda valid/aktif dan coba lagi.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil! Silakan cek Email Anda untuk kode verifikasi.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                        'email_verified' => false,
                        'role' => $user->role,
                    ]
                ]
            ], 201);
        }

        // Jika OTP dimatikan, langsung login
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil!',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                ],
                'token' => $token
            ]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails() || (!$request->has('email') && !$request->has('phone'))) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid'
            ], 422);
        }

        $identifier = $request->email ?? $request->phone;
        $user = User::where('email', $identifier)->orWhere('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Akun sudah terverifikasi sebelumnya'
            ], 400);
        }

        if (!$user->validateOtp($request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa'
            ], 400);
        }

        // Verifikasi berhasil
        $user->email_verified_at = now();
        $user->phone_verified_at = now(); // Mark both as verified to not break legacy checks
        $user->clearOtp();
        $user->save();

        // Create affiliate link automatically for new verified user
        $affiliateLink = AffiliateLink::where('user_id', $user->id)->first();
        if (!$affiliateLink) {
            $slug = $this->affiliateService->generateInitialSlug($user);
            AffiliateLink::create([
                'user_id' => $user->id,
                'slug' => $slug,
                'original_slug' => $slug,
                'is_custom' => false,
                'change_count' => 0,
                'max_changes' => 999,
                'is_active' => true,
            ]);
        }

        // Auto login setelah verifikasi
        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil! Akun Anda telah aktif.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified' => true,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        if (!$request->has('email') && !$request->has('phone')) {
            return response()->json([
                'success' => false,
                'message' => 'Identifier tidak valid'
            ], 422);
        }

        $identifier = $request->email ?? $request->phone;
        $user = User::where('email', $identifier)->orWhere('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Akun sudah terverifikasi'
            ], 400);
        }

        // Generate OTP baru
        $otp = $user->generateOtp();

        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send OTP resend email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Silakan coba lagi nanti.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP baru telah dikirim ke Email Anda.'
        ]);
    }

    public function login(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Cek apakah input adalah email, phone, atau username
        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : (preg_match('/^[0-9+]+$/', $request->login) ? 'phone' : 'username');

        // Attempt login
        $credentials = [
            $loginType => $request->login,
            'password' => $request->password
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Username/Email/No HP atau password salah'
            ], 401);
        }

        $user = User::where($loginType, $request->login)->firstOrFail();

        // Check if account is active
        if ($user->account_status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah diblokir. Silakan hubungi admin untuk informasi lebih lanjut.',
                'data' => ['account_status' => 'banned']
            ], 403);
        }

        if ($user->account_status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi admin untuk mengaktifkan kembali.',
                'data' => ['account_status' => 'inactive']
            ], 403);
        }

        // Check verification requirement
        $otpEnabled = Setting::getValue('otp_enabled', false);

        if ($otpEnabled && !$user->email_verified_at) {
            $otp = $user->generateOtp();
            try {
                Mail::to($user->email)->send(new OtpMail($otp));
            } catch (\Exception $e) {
                Log::error('Failed to send OTP email during login: ' . $user->email);
            }

            return response()->json([
                'success' => false,
                'message' => 'Akun belum terverifikasi. Kode OTP baru telah dikirim ke Email Anda.',
                'data' => [
                    'needs_verification' => true,
                    'email' => $user->email,
                    'phone' => $user->phone
                ]
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Log login activity
        \App\Models\ActivityLog::logAction(
            'login.success',
            "Login berhasil: {$user->name} ({$user->role})",
            $user,
            ['login_type' => $loginType],
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'phone' => $user->phone,
                    'phone_verified' => true,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        if (!$request->has('email') && !$request->has('phone')) {
            return response()->json([
                'success' => false,
                'message' => 'Identifier tidak valid'
            ], 422);
        }

        $identifier = $request->email ?? $request->phone;
        $user = User::where('email', $identifier)->orWhere('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email/No HP tidak terdaftar'
            ], 422);
        }

        // Generate reset OTP
        $otp = $user->generateResetOtp();

        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            Log::error('Failed to send reset OTP email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP. Silakan coba lagi nanti.'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kode OTP reset password telah dikirim ke Email Anda.'
        ]);
    }

    public function verifyResetOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails() || (!$request->has('email') && !$request->has('phone'))) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid'
            ], 422);
        }

        $identifier = $request->email ?? $request->phone;
        $user = User::where('email', $identifier)->orWhere('phone', $identifier)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        if (!$user->validateResetOtp($request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa'
            ], 400);
        }

        // OTP valid, return token untuk reset password
        $resetToken = bin2hex(random_bytes(32));

        // Simpan token sementara (bisa menggunakan cache)
        cache()->put('reset_token_' . $resetToken, $user->id, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'reset_token' => $resetToken
            ]
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid'
            ], 422);
        }

        // Validasi reset token
        $cacheKey = 'reset_token_' . $request->reset_token;
        $userId = cache()->get($cacheKey);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token reset tidak valid atau sudah kedaluwarsa'
            ], 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->clearResetOtp();
        $user->save();

        // Hapus token dari cache
        cache()->forget($cacheKey);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil direset. Silakan login dengan password baru.'
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Log logout activity
        \App\Models\ActivityLog::logAction(
            'logout',
            "Logout: {$user->name}",
            $user,
            null,
            $request
        );

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }
}
