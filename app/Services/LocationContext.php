<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Lokasi (negara) aktif yang dipilih lewat dropdown di sidebar.
 * Disimpan di session 'location_id'. Nilai awal = tbl_user.location_id.
 *
 * Aturan sementara (sesuaikan bila CI3 berbeda):
 *  - Super Admin & Admin boleh memilih semua lokasi aktif.
 *  - Staff terkunci di lokasinya sendiri (tbl_user.location_id).
 */
class LocationContext
{
    public const SESSION_KEY = 'location_id';

    /** Level yang boleh berpindah lokasi (id dari tbl_user_level). */
    public const CAN_SWITCH_LEVELS = [1, 2];

    public function canSwitch(?User $user): bool
    {
        return $user !== null
            && (in_array((int) $user->id_user_level, self::CAN_SWITCH_LEVELS, true) || empty($user->location_id));
    }

    /** Lokasi yang boleh dipilih user ini. */
    public function optionsFor(?User $user): Collection
    {
        $query = Location::where('status', 1)->orderBy('id');

        if (! $this->canSwitch($user) && $user?->location_id) {
            $query->where('id', $user->location_id);
        }

        return $query->get(['id', 'name']);
    }

    /** id lokasi aktif; bila session kosong/tidak valid, kembali ke lokasi default user. */
    public function currentId(?User $user): ?int
    {
        $options = $this->optionsFor($user);
        $selected = (int) session(self::SESSION_KEY);

        if ($selected && $options->contains('id', $selected)) {
            return $selected;
        }

        $default = (int) ($user?->location_id ?: $options->first()?->id);
        session([self::SESSION_KEY => $default ?: null]);

        return $default ?: null;
    }

    public function switchTo(?User $user, int $locationId): bool
    {
        if (! $this->optionsFor($user)->contains('id', $locationId)) {
            return false;
        }

        session([self::SESSION_KEY => $locationId]);

        return true;
    }

    /** 'Makassar - Indonesia' -> 'Indonesia' (seperti tampilan dropdown di CI3). */
    public static function label(string $name): string
    {
        $parts = array_map('trim', explode(' - ', $name));

        return end($parts) ?: $name;
    }
}
