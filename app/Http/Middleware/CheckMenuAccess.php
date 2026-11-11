<?php

namespace App\Http\Middleware;

use App\Services\MenuService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akses ke modul yang tidak ada di menu level user (tbl_hak_akses),
 * sama seperti pengecekan hak akses di CI3. Contoh: Staff membuka /tbl_user langsung lewat URL.
 */
class CheckMenuAccess
{
    public function __construct(private MenuService $menus) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $module = strtolower((string) $request->segment(1));

        if (! $user || ! in_array($module, $this->menus->allowedModules((int) $user->id_user_level), true)) {
            abort(403, 'Your level does not have access to this page. Ask an administrator if you need it.');
        }

        return $next($request);
    }
}
