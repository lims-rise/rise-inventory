<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Menu sidebar + hak akses dari tbl_menu dan tbl_hak_akses, mengikuti perilaku CI3.
 *
 * Tampilan (treeForLevel):
 *  - Menu utama : is_main_menu = 0, is_aktif = 'y', terdaftar di tbl_hak_akses untuk level user.
 *  - Sub menu   : is_main_menu = id_menu induk, is_aktif = 'y', dan juga terdaftar di tbl_hak_akses.
 *
 * Hak akses (allowedModules):
 *  - SEMUA menu di tbl_hak_akses untuk level itu, termasuk yang is_aktif = 'n'.
 *    Di CI3 baris non-aktif dipakai sebagai izin halaman tersembunyi
 *    (mis. 'staff/read' untuk Staff, 'item_transaction', 'search', 'list_inventory').
 *  - Ditambah ALWAYS_ALLOWED (halaman yang di CI3 hanya butuh login).
 */
class MenuService
{
    /** Halaman yang cukup login saja, tidak dicek ke tbl_hak_akses. */
    public const ALWAYS_ALLOWED = ['welcome', 'dashboard', 'tbl_user_profile'];

    private array $treeCache = [];
    private array $moduleCache = [];

    public function treeForLevel(int $levelId): Collection
    {
        if (isset($this->treeCache[$levelId])) {
            return $this->treeCache[$levelId];
        }

        $menus = DB::table('tbl_menu as m')
            ->join('tbl_hak_akses as h', 'h.id_menu', '=', 'm.id_menu')
            ->where('h.id_user_level', $levelId)
            ->where('m.is_aktif', 'y')
            ->orderByRaw('m._index IS NULL, m._index')
            ->orderBy('m.id_menu')
            ->select('m.*')
            ->distinct()
            ->get();

        $children = $menus->where('is_main_menu', '!=', 0)->groupBy('is_main_menu');

        return $this->treeCache[$levelId] = $menus
            ->where('is_main_menu', 0)
            ->values()
            ->map(function ($menu) use ($children) {
                $menu->children = $children->get($menu->id_menu, collect())->values();

                return $menu;
            });
    }

    /** Segmen pertama URL yang boleh diakses, mis. ['item_master', 'staff', 'welcome', ...]. */
    public function allowedModules(int $levelId): array
    {
        if (isset($this->moduleCache[$levelId])) {
            return $this->moduleCache[$levelId];
        }

        $urls = DB::table('tbl_menu as m')
            ->join('tbl_hak_akses as h', 'h.id_menu', '=', 'm.id_menu')
            ->where('h.id_user_level', $levelId)
            ->pluck('m.url');

        return $this->moduleCache[$levelId] = $urls
            ->map(fn ($url) => self::moduleOf($url))
            ->filter()
            ->merge(self::ALWAYS_ALLOWED)
            ->unique()
            ->values()
            ->all();
    }

    /** 'item_master/create' -> 'item_master'; '#' atau kosong -> null. */
    public static function moduleOf(?string $url): ?string
    {
        $url = trim((string) $url, ' /');
        if ($url === '' || $url === '#') {
            return null;
        }

        return strtolower(explode('/', $url)[0]);
    }
}
