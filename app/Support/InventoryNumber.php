<?php

namespace App\Support;

use App\Models\ItemCategory;
use App\Models\Location;
use App\Services\LocationContext;
use Illuminate\Support\Facades\DB;

/**
 * Nomor inventaris otomatis, pengganti Item_master_model::get_id2() di CI3.
 * Format sama: RISE-{NEGARA}/{TAHUN}/{KATEGORI}-{NNN}, contoh RISE-ID/2026/OFF-010.
 *
 * Perbedaan dari CI3: urutan diambil dari nomor TERBESAR yang sudah ada (CI3 menghitung
 * jumlah baris + 1, yang bisa menghasilkan nomor ganda bila ada nomor yang dilompati).
 */
class InventoryNumber
{
    /** id location => kode negara (sama dengan CI3). */
    public const COUNTRY = [3 => 'ID', 2 => 'AU', 1 => 'FJ'];

    /** id item_category => kode kategori (sama dengan CI3). */
    public const CATEGORY = [1 => 'IT', 2 => 'LAB', 3 => 'BLD', 4 => 'OBJ', 5 => 'OFF'];

    /** Mata uang awal di form per negara. */
    public const CURRENCY = [3 => 'IDR', 2 => 'AUD', 1 => 'FJD'];

    public static function next(int $categoryId, ?int $locationId): ?string
    {
        $country = self::countryCode($locationId);
        $category = self::categoryCode($categoryId);
        if ($country === null || $category === null) {
            return null;
        }

        $prefix = "RISE-{$country}/".date('Y')."/{$category}-";

        $max = DB::table('item_master')
            ->where('inventory_number', 'like', addcslashes($prefix, '%_\\').'%')
            ->pluck('inventory_number')
            ->map(fn ($n) => substr($n, strlen($prefix)))
            ->filter(fn ($suffix) => ctype_digit($suffix))
            ->map(fn ($suffix) => (int) $suffix)
            ->max() ?? 0;

        return $prefix.sprintf('%03d', $max + 1);
    }

    private static function countryCode(?int $locationId): ?string
    {
        if ($locationId && isset(self::COUNTRY[$locationId])) {
            return self::COUNTRY[$locationId];
        }

        // Negara baru yang belum ada di daftar: 2 huruf pertama nama negara
        $name = $locationId ? Location::whereKey($locationId)->value('name') : null;
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', LocationContext::label((string) $name)), 0, 2));

        return $code !== '' ? $code : null;
    }

    private static function categoryCode(int $categoryId): ?string
    {
        if (isset(self::CATEGORY[$categoryId])) {
            return self::CATEGORY[$categoryId];
        }

        // Kategori baru: 3 huruf pertama namanya (CI3 menghasilkan nomor tanpa kode)
        $name = (string) ItemCategory::whereKey($categoryId)->value('name');
        $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));

        return $code !== '' ? $code : null;
    }
}
