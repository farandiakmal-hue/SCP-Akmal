<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'fullname'         => 'required|string|max:100',
            'username'         => 'required|string|max:50|unique:users,username,' . $user->id,
            'current_password' => 'nullable|required_with:password|current_password',
            'password'         => 'nullable|string|min:6|confirmed',
            'email' => 'nullable|email|max:150',
        ]);

        $user->fullname = $request->fullname;
        $user->username = $request->username;
        $user->email = $request->email ?: null;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
