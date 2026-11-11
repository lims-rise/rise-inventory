<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\UserLevel;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 Userlevel (URL tetap /userlevel).
 * Perbedaan penting: di CI3 controller ini TANPA cek login (is_login dikomentari),
 * sekarang hanya level yang punya akses menu ini yang bisa membuka.
 */
class UserLevelController extends Controller
{
    /** Level bawaan yang dipakai kode aplikasi (User::LEVEL_*): tidak boleh dihapus. */
    public const LOCKED = [1, 2, 3];

    public function index(Request $request): View|RedirectResponse
    {
        $editing = null;
        if ($request->filled('id')) {
            $editing = UserLevel::find($request->query('id'));
            if (! $editing) {
                return redirect()->route('userlevel.index')->with('warning', 'Level not found.');
            }
        }

        $levels = UserLevel::query()
            ->withCount('users')
            ->addSelect(['menus_count' => DB::table('tbl_hak_akses')->selectRaw('COUNT(*)')
                ->whereColumn('tbl_hak_akses.id_user_level', 'tbl_user_level.id_user_level')])
            ->orderBy('id_user_level')
            ->get();

        return view('config.levels', [
            'levels' => $levels,
            'editing' => $editing,
            'locked' => self::LOCKED,
        ]);
    }

    /** URL lama CI3 /userlevel/update/{id} */
    public function editLegacy(int $id): RedirectResponse
    {
        return redirect()->route('userlevel.index', ['id' => $id]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, null);
        $level = UserLevel::create($data + ['status' => 1]);

        return redirect()->route('userlevel.akses', $level->id_user_level)
            ->with('success', 'Level "'.$data['nama_level'].'" has been created. Now choose which menus it can open.');
    }

    public function update(Request $request): RedirectResponse
    {
        $level = UserLevel::find($request->input('id_user_level'));
        if (! $level) {
            return redirect()->route('userlevel.index')->with('warning', 'Level not found.');
        }

        $level->forceFill($this->validated($request, $level))->save();

        return redirect()->route('userlevel.index')->with('success', 'Level "'.$level->nama_level.'" has been updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $level = UserLevel::withCount('users')->find($id);
        if (! $level) {
            return redirect()->route('userlevel.index')->with('warning', 'Level not found.');
        }
        if (in_array((int) $level->id_user_level, self::LOCKED, true)) {
            return redirect()->route('userlevel.index')->with('error', '"'.$level->nama_level.'" is a built-in level and cannot be deleted.');
        }
        if ($level->users_count) {
            return redirect()->route('userlevel.index')
                ->with('error', $level->users_count.' user(s) still use "'.$level->nama_level.'". Move them to another level first.');
        }

        DB::transaction(function () use ($level) {
            DB::table('tbl_hak_akses')->where('id_user_level', $level->id_user_level)->delete();
            $level->delete();
        });

        return redirect()->route('userlevel.index')->with('success', 'Level "'.$level->nama_level.'" has been deleted.');
    }

    // ------------------------------------------------------------------ Hak akses

    /** GET userlevel/akses/{id} */
    public function akses(int $id): View|RedirectResponse
    {
        $level = UserLevel::find($id);
        if (! $level) {
            return redirect()->route('userlevel.index')->with('warning', 'Level not found.');
        }

        $menus = Menu::query()->orderByRaw('_index IS NULL, _index')->orderBy('id_menu')->get();
        $granted = DB::table('tbl_hak_akses')->where('id_user_level', $id)->pluck('id_menu')->map(fn ($v) => (int) $v)->all();
        $mains = $menus->where('is_main_menu', 0)->values();
        $mainIds = $mains->pluck('id_menu')->all();

        return view('config.akses', [
            'level' => $level,
            'mains' => $mains,
            'children' => $menus->where('is_main_menu', '!=', 0)->groupBy('is_main_menu'),
            'orphans' => $menus->filter(fn ($m) => $m->is_main_menu != 0 && ! in_array($m->is_main_menu, $mainIds))->values(),
            'granted' => $granted,
            'protected' => $this->protectedMenus($id),
        ]);
    }

    /**
     * POST userlevel/akses/{id}  (id_menu, granted=1|0)
     * Pengganti kasi_akses_ajax CI3 (GET tanpa login & tanpa CSRF).
     */
    public function toggle(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'id_menu' => ['required', 'integer', Rule::exists('tbl_menu', 'id_menu')],
            'granted' => ['required', 'boolean'],
        ]);
        abort_unless(UserLevel::whereKey($id)->exists(), Response::HTTP_NOT_FOUND);

        $menuId = (int) $data['id_menu'];
        if (! $data['granted'] && in_array($menuId, $this->protectedMenus($id), true)) {
            return response()->json(['message' => 'You cannot remove this from your own level, or you would lock yourself out of Level Management.'], 422);
        }

        $params = ['id_user_level' => $id, 'id_menu' => $menuId];
        $exists = DB::table('tbl_hak_akses')->where($params)->exists();

        if ($data['granted'] && ! $exists) {
            DB::table('tbl_hak_akses')->insert($params);
        } elseif (! $data['granted'] && $exists) {
            DB::table('tbl_hak_akses')->where($params)->delete();
        }

        return response()->json(['message' => 'saved', 'granted' => (bool) $data['granted']]);
    }

    /** Menu yang tidak boleh dicabut dari level user yang sedang login (Level Management + induknya). */
    private function protectedMenus(int $levelId): array
    {
        if ((int) Auth::user()->id_user_level !== $levelId) {
            return [];
        }

        $menu = Menu::all()->first(fn ($m) => MenuService::moduleOf($m->url) === 'userlevel');

        return $menu ? array_values(array_filter([(int) $menu->id_menu, (int) $menu->is_main_menu])) : [];
    }

    private function validated(Request $request, ?UserLevel $level): array
    {
        return $request->validate([
            'nama_level' => ['required', 'string', 'max:30',
                Rule::unique('tbl_user_level', 'nama_level')->ignore($level?->id_user_level, 'id_user_level')],
            'url' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_\-\/]+$/'],
        ], [
            'url.regex' => 'Use a path such as welcome or staff/read.',
        ], ['nama_level' => 'level name', 'url' => 'home page']);
    }
}
