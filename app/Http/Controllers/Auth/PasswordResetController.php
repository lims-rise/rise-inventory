<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Lupa password tanpa tabel baru.
 *
 * Link reset = URL bertanda tangan (signed) berisi id user + sidik jari dari hash password saat ini.
 * - Kedaluwarsa setelah EXPIRE_MINUTES.
 * - Begitu password diganti, sidik jarinya berubah, jadi link otomatis tidak berlaku lagi (sekali pakai).
 * - Tidak ada perubahan struktur pada tbl_user maupun tabel lain.
 */
class PasswordResetController extends Controller
{
    public const EXPIRE_MINUTES = 60;

    /** Maks. email reset per alamat dalam DECAY_SECONDS. */
    private const MAX_PER_EMAIL = 3;
    private const DECAY_SECONDS = 900;

    private const SENT_MESSAGE = 'If that email belongs to an active account, a reset link is on its way. Please check your inbox (and spam folder).';

    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:50']], [
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please enter a valid email address.',
        ]);

        $key = 'pw-reset:'.sha1(strtolower(trim($data['email'])));
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_EMAIL)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);

            return back()->withErrors(['email' => "Too many reset requests. Please try again in {$minutes} minute(s)."])->onlyInput('email');
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $user = User::where('email', $data['email'])->first();

        // Pesan yang sama untuk email terdaftar maupun tidak, supaya daftar email user tidak bisa ditebak.
        if ($user && $user->isActive() && filled($user->password)) {
            $url = URL::temporarySignedRoute('password.reset', now()->addMinutes(self::EXPIRE_MINUTES), [
                'user' => $user->id_users,
                'h' => $this->fingerprint($user),
            ]);

            try {
                Mail::to($user->email, $user->full_name)->send(new ResetPasswordMail($user, $url, self::EXPIRE_MINUTES));
            } catch (\Throwable $e) {
                Log::error('Password reset email failed', ['user' => $user->id_users, 'error' => $e->getMessage()]);

                return back()->withErrors(['email' => "We couldn't send the email right now. Please try again later or contact your administrator."])->onlyInput('email');
            }
        }

        return redirect()->route('password.request')->with('status', self::SENT_MESSAGE);
    }

    public function edit(Request $request, int $user): View|RedirectResponse
    {
        $account = $this->resolve($request, $user);
        if ($account instanceof RedirectResponse) {
            return $account;
        }

        return view('auth.reset-password', [
            'user' => $account,
            'expires' => Carbon::createFromTimestamp((int) $request->query('expires')),
        ]);
    }

    public function update(Request $request, int $user): RedirectResponse
    {
        $account = $this->resolve($request, $user);
        if ($account instanceof RedirectResponse) {
            return $account;
        }

        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => 'Please enter a new password.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);

        $account->password = Hash::make($request->input('password'));
        $account->save();

        RateLimiter::clear('pw-reset:'.sha1(strtolower(trim($account->email))));
        Log::info('Password reset via email link', ['user' => $account->id_users]);

        return redirect()->route('login')
            ->withInput(['email' => $account->email])
            ->with('status', 'Your password has been updated. You can log in with the new password now.');
    }

    /** Cek tanda tangan, masa berlaku, user aktif, dan sidik jari password. */
    private function resolve(Request $request, int $id): User|RedirectResponse
    {
        $invalid = fn () => redirect()->route('password.request')
            ->withErrors(['email' => 'This reset link is invalid, already used, or expired. Please request a new one.']);

        if (! $request->hasValidSignature()) {
            return $invalid();
        }

        $user = User::find($id);
        if (! $user || ! $user->isActive() || ! hash_equals($this->fingerprint($user), (string) $request->query('h'))) {
            return $invalid();
        }

        return $user;
    }

    private function fingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', $user->id_users.'|'.$user->email.'|'.$user->password, (string) config('app.key')), 0, 32);
    }
}
