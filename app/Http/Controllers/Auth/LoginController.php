<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('level')->where('email', $data['email'])->first();

        if (! $user || ! $this->passwordMatches($data['password'], (string) $user->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah.'])
                ->onlyInput('email');
        }

        if (! $user->isActive()) {
            return back()
                ->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.'])
                ->onlyInput('email');
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Lokasi awal di dropdown sidebar = lokasi user (tbl_user.location_id)
        app(LocationContext::class)->currentId($user);

        return redirect()->intended($user->homeUrl());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Cek password terhadap hash lama dari CI3 TANPA mengubah isi tbl_user.
     * - bcrypt / argon (password_hash CI3)  -> password_verify()
     * - md5 (32 hex) atau sha1 (40 hex)     -> dibandingkan langsung
     * Hapus cabang md5/sha1 bila CI3 Anda memang memakai password_hash().
     */
    private function passwordMatches(string $plain, string $hash): bool
    {
        if ($hash === '') {
            return false;
        }

        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$argon2')) {
            return password_verify($plain, $hash);
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
            return hash_equals(strtolower($hash), md5($plain));
        }

        if (preg_match('/^[a-f0-9]{40}$/i', $hash)) {
            return hash_equals(strtolower($hash), sha1($plain));
        }

        return false;
    }
}
