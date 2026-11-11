<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Support\FormOptions;
use App\Support\SimpleXlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 User (URL tetap /user).
 *
 * Perbedaan dari CI3:
 *  - Password wajib saat membuat user (CI3: aturan dikomentari, user bisa dibuat tanpa password)
 *    dan bisa diganti saat edit (CI3: tidak bisa sama sekali).
 *  - Email harus unik (dipakai untuk login).
 *  - Lokasi default user bisa diisi (CI3 tidak punya field ini, user baru tidak melihat data apa pun).
 *  - Hanya Super Admin yang boleh membuat/mengubah akun Super Admin (mencegah eskalasi hak akses).
 *  - Tidak bisa menonaktifkan/menghapus akun sendiri atau Super Admin aktif terakhir.
 *  - Excel tidak lagi berisi hash password.
 */
class UserController extends Controller
{
    public const PHOTO_DIR = 'assets/foto_profil';

    private function me(): User
    {
        return Auth::user();
    }

    public function index(): View
    {
        $users = DB::table('tbl_user as u')
            ->leftJoin('tbl_user_level as l', 'l.id_user_level', '=', 'u.id_user_level')
            ->leftJoin('staff as s', 's.id', '=', 'u.staff_id')
            ->leftJoin('location as loc', 'loc.id', '=', 'u.location_id')
            ->orderByRaw("u.is_aktif = 'y' DESC")
            ->orderBy('u.full_name')
            ->get(['u.id_users', 'u.full_name', 'u.email', 'u.images', 'u.is_aktif', 'u.id_user_level',
                'l.nama_level', 's.name as staff_name', 'u.staff_id', 'loc.name as location_name']);

        return view('config.users', [
            'users' => $users,
            'levels' => $users->countBy('nama_level'),
            'me' => $this->me(),
        ]);
    }

    public function create(): View
    {
        return view('config.user-form', $this->formData(null));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $user = User::find($id);
        if (! $user) {
            return redirect()->route('user.index')->with('warning', 'User not found.');
        }

        return view('config.user-form', $this->formData($user));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);

        $user = new User();
        $user->forceFill([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'images' => $request->hasFile('images') ? $this->storePhoto($request->file('images')) : '',
            'id_user_level' => $data['id_user_level'],
            'is_aktif' => $data['is_aktif'],
            'staff_id' => $data['staff_id'] ?? null,
            'location_id' => $data['location_id'],
        ])->save();

        return redirect()->route('user.index')->with('success', 'User "'.$data['full_name'].'" has been created.');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = User::find($request->input('id_users'));
        if (! $user) {
            return redirect()->route('user.index')->with('warning', 'User not found.');
        }

        $data = $this->validated($request, $user);

        $values = [
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'id_user_level' => $data['id_user_level'],
            'is_aktif' => $data['is_aktif'],
            'staff_id' => $data['staff_id'] ?? null,
            'location_id' => $data['location_id'],
        ];
        if (! empty($data['password'])) {
            $values['password'] = Hash::make($data['password']);
        }
        if ($request->hasFile('images')) {
            $values['images'] = $this->storePhoto($request->file('images'));
        }

        $user->forceFill($values)->save();

        return redirect()->route('user.index')->with('success', 'User "'.$user->full_name.'" has been updated.');
    }

    /** Hapus permanen (seperti CI3) dengan pengaman. Nonaktifkan lebih disarankan supaya riwayat tetap punya nama. */
    public function destroy(int $id): RedirectResponse
    {
        $user = User::find($id);
        if (! $user) {
            return redirect()->route('user.index')->with('warning', 'User not found.');
        }
        if ((int) $user->id_users === (int) $this->me()->id_users) {
            return redirect()->route('user.index')->with('error', 'You cannot delete your own account.');
        }
        if ($user->isSuperAdmin() && ! $this->me()->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Only Super Admin can delete a Super Admin account.');
        }
        if ($user->isSuperAdmin() && $this->activeSuperAdmins($user->id_users) === 0) {
            return redirect()->route('user.index')->with('error', 'This is the last active Super Admin and cannot be deleted.');
        }

        $user->delete();

        return redirect()->route('user.index')->with('success', 'User "'.$user->full_name.'" has been deleted.');
    }

    public function excel(): BinaryFileResponse
    {
        $rows = DB::table('tbl_user as u')
            ->leftJoin('tbl_user_level as l', 'l.id_user_level', '=', 'u.id_user_level')
            ->leftJoin('staff as s', 's.id', '=', 'u.staff_id')
            ->leftJoin('location as loc', 'loc.id', '=', 'u.location_id')
            ->orderBy('u.full_name')
            ->get(['u.full_name', 'u.email', 'l.nama_level', 'u.is_aktif', 's.name as staff_name', 'loc.name as location_name'])
            ->values()
            ->map(fn ($r, $i) => [$i + 1, $r->full_name, $r->email, $r->nama_level, $r->is_aktif === 'y' ? 'Active' : 'Inactive',
                $r->staff_name, $r->location_name]);

        return SimpleXlsx::make('Users', ['No', 'Full Name', 'Email', 'Level', 'Status', 'Linked Staff', 'Default Location'], $rows)
            ->download('users_'.date('Ymd').'.xlsx');
    }

    // ------------------------------------------------------------------ Helpers

    private function activeSuperAdmins(int $exceptId): int
    {
        return User::where('id_user_level', User::LEVEL_SUPER_ADMIN)->where('is_aktif', 'y')
            ->where('id_users', '!=', $exceptId)->count();
    }

    private function formData(?User $user): array
    {
        $levels = UserLevel::orderBy('id_user_level')->pluck('nama_level', 'id_user_level');
        if (! $this->me()->isSuperAdmin()) {
            $levels = $levels->except([User::LEVEL_SUPER_ADMIN]);
        }

        return [
            'user' => $user,
            'levels' => $levels,
            'locations' => FormOptions::locations(),
            'staff' => FormOptions::staffGrouped($user->location_id ?? $this->me()->location_id),
            'isSelf' => $user && (int) $user->id_users === (int) $this->me()->id_users,
            'lockedSuper' => $user && $user->isSuperAdmin() && ! $this->me()->isSuperAdmin(),
        ];
    }

    private function validated(Request $request, ?User $user): array
    {
        $me = $this->me();
        $isSelf = $user && (int) $user->id_users === (int) $me->id_users;

        if ($user && $user->isSuperAdmin() && ! $me->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Only Super Admin can change a Super Admin account.');
        }

        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'max:50',
                Rule::unique('tbl_user', 'email')->ignore($user?->id_users, 'id_users')],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
            'id_user_level' => ['required', 'integer', Rule::exists('tbl_user_level', 'id_user_level'),
                function ($attr, $value, $fail) use ($me) {
                    if ((int) $value === User::LEVEL_SUPER_ADMIN && ! $me->isSuperAdmin()) {
                        $fail('Only Super Admin can give the Super Admin level.');
                    }
                }],
            'is_aktif' => ['required', Rule::in(['y', 'n'])],
            'staff_id' => ['nullable', 'integer', Rule::exists('staff', 'id')],
            'location_id' => ['required', 'integer', Rule::exists('location', 'id')],
            'images' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [], [
            'full_name' => 'full name', 'id_user_level' => 'level', 'is_aktif' => 'status',
            'staff_id' => 'linked staff', 'location_id' => 'default location', 'images' => 'profile picture',
        ]);

        // Jangan sampai mengunci diri sendiri
        if ($isSelf) {
            $data['is_aktif'] = 'y';
            $data['id_user_level'] = $me->id_user_level;
        }

        // Super Admin aktif terakhir tidak boleh diturunkan/dinonaktifkan
        if ($user && $user->isSuperAdmin()
            && ((int) $data['id_user_level'] !== User::LEVEL_SUPER_ADMIN || $data['is_aktif'] !== 'y')
            && $this->activeSuperAdmins($user->id_users) === 0) {
            back()->withInput()->withErrors(['id_user_level' => 'This is the last active Super Admin.'])->throwResponse();
        }

        return $data;
    }

    public static function storePhoto(UploadedFile $file): string
    {
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $name = Str::random(32).'.'.($extension === 'jpeg' ? 'jpg' : $extension);
        File::ensureDirectoryExists(public_path(self::PHOTO_DIR));
        $file->move(public_path(self::PHOTO_DIR), $name);

        return $name;
    }
}
