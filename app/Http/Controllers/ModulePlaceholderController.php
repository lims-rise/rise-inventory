<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Halaman sementara untuk menu CI3 yang belum dimigrasi. Hapus setelah semua modul selesai. */
class ModulePlaceholderController extends Controller
{
    public function __invoke(string $module): View
    {
        $title = DB::table('tbl_menu')->where('url', 'like', $module.'%')->value('title')
            ?? ucwords(str_replace(['_', '-'], ' ', $module));

        return view('module-placeholder', compact('title', 'module'));
    }
}
