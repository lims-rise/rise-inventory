<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengganti controller CI3 Kelolamenu (URL tetap /kelolamenu).
 * Isi tbl_menu menentukan sidebar & hak akses, jadi perubahan langsung berlaku.
 *
 * Tidak dimigrasi dengan sengaja:
 *  - kelolamenu/clab: di CI3 tanpa login & bisa membuat siapa pun masuk sebagai user lain
 *    (diganti dropdown lokasi di sidebar).
 *  - Setting "Show Menu Base on Level" = tidak: di Laravel menu SELALU mengikuti hak akses level.
 */
class MenuController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $editing = null;
        if ($request->filled('id')) {
            $editing = Menu::find($request->query('id'));
            if (! $editing) {
                return redirect()->route('kelolamenu.index')->with('warning', 'Menu not found.');
            }
        }

        $menus = Menu::query()->orderByRaw('_index IS NULL, _index')->orderBy('id_menu')->get();

        // Level yang punya akses per menu
        $access = DB::table('tbl_hak_akses as h')
            ->join('tbl_user_level as l', 'l.id_user_level', '=', 'h.id_user_level')
            ->get(['h.id_menu', 'l.nama_level'])
            ->groupBy('id_menu')
            ->map(fn ($rows) => $rows->pluck('nama_level')->unique()->values());

        $mains = $menus->where('is_main_menu', 0)->values();
        $mainIds = $mains->pluck('id_menu')->all();
        $children = $menus->where('is_main_menu', '!=', 0)->groupBy('is_main_menu');
        $orphans = $menus->filter(fn ($m) => $m->is_main_menu != 0 && ! in_array($m->is_main_menu, $mainIds))->values();

        return view('config.menu', [
            'mains' => $mains,
            'children' => $children,
            'orphans' => $orphans,
            'access' => $access,
            'editing' => $editing,
            'parents' => $mains->filter(fn ($m) => ! $editing || $m->id_menu !== $editing->id_menu)->pluck('title', 'id_menu'),
            'total' => $menus->count(),
        ]);
    }

    /** URL lama CI3 /kelolamenu/update/{id} */
    public function editLegacy(int $id): RedirectResponse
    {
        return redirect()->route('kelolamenu.index', ['id' => $id]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        Menu::create($data);

        return redirect()->route('kelolamenu.index')->with('success', 'Menu "'.$data['title'].'" has been created.');
    }

    public function update(Request $request): RedirectResponse
    {
        $menu = Menu::find($request->input('id_menu'));
        if (! $menu) {
            return redirect()->route('kelolamenu.index')->with('warning', 'Menu not found.');
        }

        $data = $this->validated($request, $menu);
        $menu->forceFill($data)->save();

        return redirect()->route('kelolamenu.index')->with('success', 'Menu "'.$data['title'].'" has been updated.');
    }

    /** Hapus permanen (seperti CI3), tapi tolak bila masih punya sub menu & bersihkan hak aksesnya. */
    public function destroy(int $id): RedirectResponse
    {
        $menu = Menu::find($id);
        if (! $menu) {
            return redirect()->route('kelolamenu.index')->with('warning', 'Menu not found.');
        }

        $childCount = Menu::where('is_main_menu', $menu->id_menu)->count();
        if ($childCount) {
            return redirect()->route('kelolamenu.index')
                ->with('error', '"'.$menu->title.'" still has '.$childCount.' sub menu(s). Move or delete them first.');
        }

        if (MenuService::moduleOf($menu->url) === 'kelolamenu') {
            return redirect()->route('kelolamenu.index')->with('error', 'Menu Management itself cannot be deleted.');
        }

        DB::transaction(function () use ($menu) {
            DB::table('tbl_hak_akses')->where('id_menu', $menu->id_menu)->delete();
            $menu->delete();
        });

        return redirect()->route('kelolamenu.index')->with('success', 'Menu "'.$menu->title.'" has been deleted.');
    }

    private function validated(Request $request, ?Menu $menu): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'url' => ['required', 'string', 'max:30', 'regex:/^(#|[A-Za-z0-9_\-\/]+)$/'],
            'icon' => ['required', 'string', 'max:30', 'regex:/^[a-z0-9 \-]+$/i'],
            'is_main_menu' => ['required', 'integer', function ($attr, $value, $fail) use ($menu) {
                if ((int) $value === 0) {
                    return;
                }
                if ($menu && (int) $value === (int) $menu->id_menu) {
                    return $fail('A menu cannot be its own parent.');
                }
                if (! Menu::where('id_menu', (int) $value)->where('is_main_menu', 0)->exists()) {
                    return $fail('The parent must be a main menu.');
                }
                if ($menu && Menu::where('is_main_menu', $menu->id_menu)->exists()) {
                    $fail('This menu has sub menus, so it must stay a main menu.');
                }
            }],
            'is_aktif' => ['required', Rule::in(['y', 'n'])],
            '_index' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'url.regex' => 'Use "#" for a group, or a path such as item_master or staff/read.',
            'icon.regex' => 'Use a Font Awesome class such as "fa fa-server".',
        ], ['is_main_menu' => 'parent', 'is_aktif' => 'status', '_index' => 'order']);

        $data['is_main_menu'] = (int) $data['is_main_menu'];
        $data['_index'] = $data['_index'] ?? null;

        return $data;
    }
}
