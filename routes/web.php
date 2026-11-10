<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Master;
use App\Http\Controllers\ModulePlaceholderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Login
|--------------------------------------------------------------------------
| URL lama CI3 /auth tetap diarahkan ke halaman login baru.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')   // maks. 5 percobaan per menit
        ->name('login.attempt');
});

Route::redirect('/auth', '/login');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Area setelah login
|--------------------------------------------------------------------------
| URL sengaja sama dengan CI3 (welcome, item_master, staff, ...) supaya
| bookmark lama dan isi kolom url di tbl_menu / tbl_user_level tetap berlaku.
*/
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect(auth()->user()->homeUrl()));
    Route::get('/welcome', [DashboardController::class, 'index'])->name('dashboard');
    Route::redirect('/dashboard', '/welcome');

    // Dropdown lokasi (negara) di sidebar
    Route::post('/switch-location', [LocationController::class, 'switch'])->name('location.switch');

    // ---- Master data (URL sama dengan CI3, hak akses dari tbl_hak_akses) ----
    Route::legacyMaster('item_category', Master\ItemCategoryController::class);
    Route::legacyMaster('item_sub_category', Master\ItemSubCategoryController::class);
    Route::legacyMaster('department', Master\DepartmentController::class);
    Route::legacyMaster('item_condition', Master\ItemConditionController::class);
    Route::legacyMaster('item_manufacture', Master\ItemManufactureController::class);
    Route::legacyMaster('item_supplier', Master\ItemSupplierController::class);
    Route::legacyMaster('location', Master\LocationController::class);
    Route::legacyMaster('location_detail', Master\LocationDetailController::class);

    // Dropdown bertingkat (dipakai form aset): Category -> Sub Category, Location -> Location Detail.
    // Cukup login (tanpa cek menu) karena hanya membaca daftar nama.
    Route::post('item_sub_category/list', [Master\ItemSubCategoryController::class, 'options'])
        ->name('item_sub_category.options');
    Route::post('location_detail/list', [Master\LocationDetailController::class, 'options'])
        ->name('location_detail.options');

    // Sementara: semua URL dari tbl_menu yang belum dimigrasi tetap bisa diklik
    // (dengan cek hak akses), menampilkan halaman "sedang dimigrasi".
    // Route ini HARUS paling bawah.
    Route::get('/{module}/{any?}', ModulePlaceholderController::class)
        ->where('module', '[A-Za-z0-9_\-]+')
        ->where('any', '.*')
        ->middleware('menu.access')
        ->name('module.placeholder');
});
