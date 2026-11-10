<?php

namespace App\Http\Controllers;

use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class PasswordResetController extends Controller
{
    private const OTP_MINUTES    = 10;
    private const MAX_ATTEMPTS   = 5;
    private const RESEND_SECONDS = 60;

    // Langkah 1: masukkan username
    public function showForgot()
    {
        return view('auth.forgot');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['username' => 'required|string|max:50']);
        $username = trim($request->username);

        $key = 'otp-send:' . strtolower($username) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            return back()->withInput()->with('error', "Terlalu banyak permintaan. Coba lagi dalam $minutes menit.");
        }

        $user = User::where('username', $username)->first();
        if (!$user) {
            RateLimiter::hit($key, 600);
            return back()->withInput()->with('error', 'Username tidak ditemukan.');
        }
        if (!$user->email) {
            return back()->withInput()->with('error', 'Akun ini belum memiliki email. Hubungi Waka Kurikulum.');
        }

        $last = PasswordOtp::where('user_id', $user->id)->latest()->first();
        if ($last && (now()->timestamp - $last->created_at->timestamp) < self::RESEND_SECONDS) {
            $wait = max(1, self::RESEND_SECONDS - (now()->timestamp - $last->created_at->timestamp));
            return back()->withInput()->with('error', "Tunggu $wait detik sebelum meminta kode baru.");
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordOtp::where('user_id', $user->id)->delete();
        PasswordOtp::create([
            'user_id'    => $user->id,
            'otp_hash'   => $this->hashOtp($otp),
            'expires_at' => now()->addMinutes(self::OTP_MINUTES),
        ]);

        try {
            Mail::raw(
                "Halo {$user->fullname},\n\n"
                . "Kode OTP reset password School Class Progress Anda: {$otp}\n\n"
                . 'Kode berlaku ' . self::OTP_MINUTES . " menit. Jangan bagikan kode ini kepada siapa pun.\n"
                . 'Abaikan email ini jika Anda tidak meminta reset password.',
                fn ($m) => $m->to($user->email)->subject('Kode OTP Reset Password - SCP')
            );
        } catch (\Throwable $e) {
            PasswordOtp::where('user_id', $user->id)->delete();
            report($e);
            return back()->withInput()->with('error', 'Gagal mengirim email. Periksa pengaturan email server atau hubungi Waka Kurikulum.');
        }

        RateLimiter::hit($key, 600);
        session(['reset_user_id' => $user->id]);
        session()->forget('reset_verified_id');

        return redirect()->route('password.verify')
            ->with('success', 'Kode OTP telah dikirim ke ' . $this->maskEmail($user->email) . '.');
    }

    // Langkah 2: masukkan kode OTP
    public function showVerify()
    {
        $user = $this->pendingUser();
        if (!$user) {
            return redirect()->route('password.forgot')->with('error', 'Silakan mulai dari awal.');
        }

        return view('auth.verify-otp', ['maskedEmail' => $this->maskEmail($user->email)]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:6']);

        $user = $this->pendingUser();
        if (!$user) {
            return redirect()->route('password.forgot')->with('error', 'Sesi habis. Silakan minta kode OTP lagi.');
        }

        $record = PasswordOtp::where('user_id', $user->id)->latest()->first();

        if (!$record || $record->expires_at->isPast()) {
            return redirect()->route('password.forgot')->with('error', 'Kode OTP sudah kedaluwarsa. Silakan minta kode baru.');
        }
        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->delete();
            return redirect()->route('password.forgot')->with('error', 'Terlalu banyak percobaan salah. Silakan minta kode baru.');
        }

        if (!hash_equals($record->otp_hash, $this->hashOtp($request->otp))) {
            $record->increment('attempts');
            $left = self::MAX_ATTEMPTS - $record->attempts;

            if ($left <= 0) {
                $record->delete();
                return redirect()->route('password.forgot')->with('error', 'Terlalu banyak percobaan salah. Silakan minta kode baru.');
            }

            return back()->with('error', "Kode OTP salah. Sisa percobaan: $left.");
        }

        $record->update(['verified_at' => now()]);
        session(['reset_verified_id' => $user->id]);

        return redirect()->route('password.reset.form');
    }

    // Langkah 3: buat password baru
    public function showReset()
    {
        if (!$this->verifiedUser()) {
            return redirect()->route('password.forgot')->with('error', 'Verifikasi OTP diperlukan lebih dulu.');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate(['password' => 'required|string|min:6|confirmed']);

        $user = $this->verifiedUser();
        if (!$user) {
            return redirect()->route('password.forgot')->with('error', 'Sesi habis. Silakan ulangi dari awal.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        PasswordOtp::where('user_id', $user->id)->delete();
        session()->forget(['reset_user_id', 'reset_verified_id']);

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
    }

    // --- helper ---
    private function pendingUser(): ?User
    {
        $id = session('reset_user_id');
        return $id ? User::find($id) : null;
    }

    private function verifiedUser(): ?User
    {
        $id = session('reset_verified_id');
        if (!$id || $id != session('reset_user_id')) {
            return null;
        }

        $valid = PasswordOtp::where('user_id', $id)
            ->whereNotNull('verified_at')
            ->where('expires_at', '>', now())
            ->exists();

        return $valid ? User::find($id) : null;
    }

    private function hashOtp(string $otp): string
    {
        return hash_hmac('sha256', $otp, (string) config('app.key'));
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2) + [1 => ''];
        return substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 1)) . '@' . $domain;
    }
}