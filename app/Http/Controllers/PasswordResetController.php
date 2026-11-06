<?php

namespace App\Http\Controllers;

use App\Mail\ResetCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PasswordResetController extends Controller
{
    private const CODE_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    // Langkah 1: minta kode (username atau email)
    public function showRequest()
    {
        return view('auth.forgot');
    }

    public function sendCode(Request $request)
    {
        $request->validate(['identifier' => 'required|string|max:191']);
        $identifier = trim($request->identifier);
        $user = $this->findUser($identifier);

        if ($user && $user->email) {
            $code = (string) random_int(100000, 999999);

            DB::table('password_reset_codes')->where('user_id', $user->id)->delete();
            DB::table('password_reset_codes')->insert([
                'user_id'    => $user->id,
                'code_hash'  => Hash::make($code),
                'attempts'   => 0,
                'expires_at' => now()->addMinutes(self::CODE_MINUTES),
                'created_at' => now(),
            ]);

            try {
                Mail::to($user->email)->send(new ResetCodeMail($user->fullname, $code, self::CODE_MINUTES));
            } catch (Throwable $e) {
                Log::error('Gagal mengirim kode reset password: ' . $e->getMessage());
            }
        }

        // Pesan selalu sama agar tidak membocorkan akun mana yang terdaftar
        return redirect()->route('reset.show')
            ->with('identifier', $identifier)
            ->with('info', 'Jika akun terdaftar dan memiliki email, kode 6 digit telah dikirim (berlaku ' . self::CODE_MINUTES . ' menit). Belum mendaftarkan email? Hubungi Waka Kurikulum.');
    }

    // Langkah 2: masukkan kode + password baru
    public function showReset()
    {
        return view('auth.reset-code');
    }

    public function reset(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string|max:191',
            'code'       => 'required|digits:6',
            'password'   => 'required|string|min:6|confirmed',
        ], [
            'code.digits' => 'Kode harus 6 digit angka.',
        ]);

        $user = $this->findUser(trim($request->identifier));
        $row = $user ? DB::table('password_reset_codes')->where('user_id', $user->id)->first() : null;

        $invalid = fn (string $msg) => back()->withInput($request->only('identifier'))->with('error', $msg);

        if (!$row || now()->greaterThan($row->expires_at)) {
            if ($row) {
                DB::table('password_reset_codes')->where('id', $row->id)->delete();
            }
            return $invalid('Kode tidak valid atau sudah kedaluwarsa. Minta kode baru bila perlu.');
        }

        if ($row->attempts >= self::MAX_ATTEMPTS) {
            DB::table('password_reset_codes')->where('id', $row->id)->delete();
            return $invalid('Terlalu banyak percobaan. Silakan minta kode baru.');
        }

        if (!Hash::check($request->code, $row->code_hash)) {
            DB::table('password_reset_codes')->where('id', $row->id)->increment('attempts');
            return $invalid('Kode tidak valid atau sudah kedaluwarsa. Minta kode baru bila perlu.');
        }

        // Kode benar: simpan password baru, hapus kode, akhiri sesi lama akun ini
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_codes')->where('user_id', $user->id)->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }

    private function findUser(string $identifier): ?User
    {
        return User::where('username', $identifier)->first()
            ?? User::where('email', $identifier)->first();
    }
}
