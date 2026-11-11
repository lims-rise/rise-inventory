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

        if (! $user || ! $user->passwordMatches($data['password'])) {
            return back()
                ->withErrors(['email' => 'Incorrect email or password.'])
                ->onlyInput('email');
        }

        if (! $user->isActive()) {
            return back()
                ->withErrors(['email' => 'Your account is inactive. Please contact your administrator.'])
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
}
