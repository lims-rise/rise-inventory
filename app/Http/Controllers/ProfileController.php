<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Config\UserController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Halaman Profile (tombol di header, URL CI3 /tbl_user_profile): ubah nama, foto, password sendiri. */
class ProfileController extends Controller
{
    public function show(): View
    {
        return view('config.profile', ['user' => Auth::user()->load('level', 'staff', 'location')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:50'],
            'images' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ], [], ['full_name' => 'full name', 'images' => 'profile picture', 'current_password' => 'current password',
            'password' => 'new password']);

        if (! empty($data['password']) && ! $user->passwordMatches((string) $data['current_password'])) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $values = ['full_name' => $data['full_name']];
        if ($request->hasFile('images')) {
            $values['images'] = UserController::storePhoto($request->file('images'));
        }
        if (! empty($data['password'])) {
            $values['password'] = Hash::make($data['password']);
        }

        $user->forceFill($values)->save();

        return redirect()->route('profile')->with('success', ! empty($data['password'])
            ? 'Your profile and password have been updated.'
            : 'Your profile has been updated.');
    }
}
