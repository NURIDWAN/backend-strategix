<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

use App\Services\WhatsAppService;

class UserController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'banned'])],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];

        // Cek jika nomor telepon berubah
        $phoneChanged = $user->phone !== $validated['phone'];
        $needsVerification = false;

        if ($phoneChanged) {
            $user->phone = $validated['phone'];
            $user->phone_verified_at = null; // Reset verifikasi

            // Generate dan kirim OTP
            $otp = $user->generateOtp();
            $this->whatsappService->sendOtp($user->phone, $otp);

            $needsVerification = true;
        }

        if (isset($validated['status'])) {
            $user->account_status = $validated['status'];
        }

        $user->save();

        $message = 'Profil berhasil diperbarui';
        if ($needsVerification) {
            $message .= '. Kode OTP telah dikirim ke nomor baru Anda.';
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'message' => $message,
            'needs_verification' => $needsVerification
        ]);
    }

    public function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['status' => 'error', 'message' => 'Password lama tidak cocok'], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'Password berhasil diubah']);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'banned'])],
        ]);

        $user = User::findOrFail($id);
        $oldStatus = $user->account_status;
        $user->account_status = $validated['status'];
        $user->save();

        \App\Models\ActivityLog::logAction(
            'user.status_changed',
            "Status akun {$user->name} diubah dari {$oldStatus} ke {$validated['status']}",
            $user,
            ['old_status' => $oldStatus, 'new_status' => $validated['status']],
            $request
        );

        return response()->json(['status' => 'success', 'data' => $user, 'message' => 'Status akun diperbarui']);
    }
}
