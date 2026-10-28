<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Menampilkan halaman Register
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect($this->homePath(Auth::user()));
        }
        return view('auth.register');
    }

    // Memproses registrasi user baru
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar. Gunakan email lain.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        // Otomatis login setelah registrasi berhasil
        Auth::login($user);

        return redirect()->intended($this->homePath($user))->with('sukses', 'Selamat datang di Monevia, ' . $user->name . '!');
    }

    // Menampilkan halaman Login
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect($this->homePath(Auth::user()));
        }
        return view('auth.login');
    }

    // Memproses login user
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $credentials = [
            'email' => strtolower(trim($request->email)),
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            return redirect()->intended($this->homePath($user))->with('sukses', 'Berhasil masuk. Selamat datang kembali, ' . $user->name . '.');
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    // Memproses logout user
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with('sukses', 'Anda berhasil logout.');
    }

    private function homePath($user): string
    {
        return $user && $user->isAdmin() ? '/admin' : '/dashboard';
    }
}

