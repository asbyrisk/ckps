<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Services\AuditLogger;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function createRegister()
    {
        return view('auth.register');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $audit->log('login', 'Pengguna berhasil masuk ke sistem.');

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Selamat datang kembali.');
    }

    public function register(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
            ],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email tersebut sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.mixed' => 'Kata sandi harus mengandung huruf besar dan huruf kecil.',
            'password.numbers' => 'Kata sandi harus mengandung angka.',
            'password.symbols' => 'Kata sandi harus mengandung simbol.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        $audit->log('register', 'Akun pengguna baru dibuat: ' . $user->name, $user, null, ['email' => $user->email]);

        return redirect()
            ->route('login')
            ->with('success', 'Akun berhasil dibuat. Silakan masuk menggunakan akun baru Anda.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah keluar dari sistem.');
    }
}
