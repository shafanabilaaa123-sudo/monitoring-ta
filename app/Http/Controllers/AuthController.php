<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($kredensial, $request->boolean('ingat'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Email atau kata sandi salah.'])
            ->onlyInput('email');
    }

    public function formDaftar()
    {
        abort_unless(config('pendaftaran.aktif'), 404);

        return view('auth.daftar', [
            'dosenBisaDaftar' => config('pendaftaran.kode_dosen') !== '',
        ]);
    }

    /** Pendaftaran mandiri: mahasiswa bebas, dosen perlu kode pendaftaran, admin tidak bisa mendaftar sendiri. */
    public function daftar(Request $request)
    {
        abort_unless(config('pendaftaran.aktif'), 404);

        $kodeDosen = (string) config('pendaftaran.kode_dosen');
        $peranBoleh = $kodeDosen !== '' ? ['mahasiswa', 'dosen'] : ['mahasiswa'];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in($peranBoleh)],
            'nomor_induk' => ['nullable', 'string', 'max:30', 'required_if:role,mahasiswa'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'kode_dosen' => ['nullable', 'string', 'required_if:role,dosen'],
        ]);

        if ($data['role'] === 'dosen' && ! hash_equals($kodeDosen, (string) $data['kode_dosen'])) {
            return back()
                ->withErrors(['kode_dosen' => 'Kode pendaftaran dosen salah.'])
                ->onlyInput('name', 'email', 'role', 'nomor_induk');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'nomor_induk' => $data['nomor_induk'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        // Beri tahu admin ada pengguna baru
        foreach (User::where('role', 'admin')->pluck('id') as $adminId) {
            Notifier::kirim(
                $adminId,
                'pengguna',
                $user->name . ' mendaftar sebagai ' . ($user->role === 'dosen' ? 'dosen' : 'mahasiswa') . '.',
                route('admin.pengguna', [], false)
            );
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('sukses', 'Pendaftaran berhasil. Selamat datang, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
