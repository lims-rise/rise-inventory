<?php

namespace App\Models;

use App\Services\MenuService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Login user, dibaca langsung dari tabel lama `tbl_user` (tanpa perubahan struktur).
 */
class User extends Authenticatable
{
    protected $table = 'tbl_user';
    protected $primaryKey = 'id_users';
    public $timestamps = false;

    protected $fillable = [
        'full_name', 'email', 'password', 'images',
        'id_user_level', 'is_aktif', 'staff_id', 'location_id',
    ];

    protected $hidden = ['password'];

    /**
     * tbl_user tidak punya kolom remember_token.
     * Nama kosong membuat Laravel melewati penyimpanan token, jadi tabel tidak disentuh.
     */
    public function getRememberTokenName()
    {
        return '';
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'id_user_level', 'id_user_level');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function isActive(): bool
    {
        return $this->is_aktif === 'y';
    }

    /**
     * Halaman tujuan setelah login, diambil dari kolom tbl_user_level.url seperti di CI3.
     * Bila URL itu bukan menu yang boleh diakses level ini, jatuh ke /welcome (hindari 403 setelah login).
     */
    public function homeUrl(): string
    {
        $path = trim((string) ($this->level?->url ?? ''), '/');
        $module = MenuService::moduleOf($path);
        $allowed = app(MenuService::class)->allowedModules((int) $this->id_user_level);

        return url($module !== null && in_array($module, $allowed, true) ? $path : 'welcome');
    }

    /** Foto profil: pakai file lama di public/assets/foto_profil bila ada. */
    public function avatarUrl(): string
    {
        $file = trim((string) $this->images);

        return $file !== '' && is_file(public_path('assets/foto_profil/'.$file))
            ? asset('assets/foto_profil/'.$file)
            : asset('images/avatar-default.svg');
    }
}
