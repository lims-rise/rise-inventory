<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemMasterController;
use App\Http\Controllers\ItemTransactionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Config;
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

    // Lupa password: link reset dikirim lewat email (URL bertanda tangan, tanpa tabel baru).
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{user}', [PasswordResetController::class, 'edit'])
        ->whereNumber('user')->name('password.reset');
    Route::post('/reset-password/{user}', [PasswordResetController::class, 'update'])
        ->whereNumber('user')->middleware('throttle:6,1')->name('password.update');
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

    // ---- Inventory List (URL sama dengan CI3; nomor aset lewat ?id= karena berisi "/") ----
    Route::prefix('item_master')
        ->middleware('menu.access')
        ->controller(ItemMasterController::class)
        ->name('item_master.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('json', 'json')->name('json');
            Route::get('read', 'read')->name('read');
            Route::get('create', 'create')->name('create');
            Route::get('create2', 'createLegacy');
            Route::post('create_action', 'store')->name('store');
            Route::get('update', 'edit')->name('edit');
            Route::post('update_action', 'update')->name('update');
            Route::delete('delete', 'destroy')->name('destroy');
            Route::get('next_number', 'nextNumber')->name('next_number');
            Route::post('upload', 'upload')->name('upload');
            Route::post('upload_receipt', 'uploadReceipt')->name('upload_receipt');
            Route::get('excel', 'excel')->name('excel');
        });

    // ---- History & Maintenance aset (URL sama dengan CI3 Item_transaction) ----
    Route::prefix('item_transaction')
        ->middleware('menu.access')
        ->controller(ItemTransactionController::class)
        ->name('item_transaction.')
        ->group(function () {
            Route::post('action', 'save')->name('save');
            Route::post('delete', 'delete')->name('delete');
            Route::post('action2', 'saveMaintenance')->name('save_maintenance');
            Route::post('delete2', 'deleteMaintenance')->name('delete_maintenance');
            Route::get('excel', 'excel')->name('excel');
            Route::get('excel2', 'excelMaintenance')->name('excel_maintenance');
        });

    // ---- Staff (URL sama dengan CI3) ----
    Route::prefix('staff')
        ->middleware('menu.access')
        ->controller(StaffController::class)
        ->name('staff.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('json', 'json')->name('json');
            Route::get('read', 'read')->name('read');
            Route::post('data', 'data')->name('data');
            Route::post('create_action', 'store')->name('store');
            Route::post('update_action', 'update')->name('update');
            Route::delete('delete/{id}', 'destroy')->whereNumber('id')->name('destroy');
            Route::post('upload', 'upload')->name('upload');
            Route::post('item_mutation', 'itemMutation')->name('item_mutation');
            Route::post('transfer_all', 'transferAll')->name('transfer_all');
            Route::get('excel', 'excel')->name('excel');
        });

    // ---- Reports (URL & parameter sama dengan CI3) ----
    Route::prefix('reports')
        ->middleware('menu.access')
        ->controller(ReportController::class)
        ->name('reports.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::match(['get', 'post'], 'json', 'json')->name('json');
            Route::get('excel', 'excel')->name('excel');
            Route::get('excel_maintenance', 'excelMaintenance')->name('excel_maintenance');
            Route::get('excel_quality', 'excelQuality')->name('excel_quality');
        });

    // ---- Config (URL sama dengan CI3; hak akses dari tbl_hak_akses, default hanya Super Admin) ----
    Route::prefix('kelolamenu')->middleware('menu.access')->controller(Config\MenuController::class)
        ->name('kelolamenu.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('update/{id}', 'editLegacy')->whereNumber('id');
            Route::post('create_action', 'store')->name('store');
            Route::post('update_action', 'update')->name('update');
            Route::delete('delete/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });

    Route::prefix('userlevel')->middleware('menu.access')->controller(Config\UserLevelController::class)
        ->name('userlevel.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('update/{id}', 'editLegacy')->whereNumber('id');
            Route::post('create_action', 'store')->name('store');
            Route::post('update_action', 'update')->name('update');
            Route::delete('delete/{id}', 'destroy')->whereNumber('id')->name('destroy');
            Route::get('akses/{id}', 'akses')->whereNumber('id')->name('akses');
            Route::post('akses/{id}', 'toggle')->whereNumber('id')->name('toggle');
        });

    Route::prefix('user')->middleware('menu.access')->controller(Config\UserController::class)
        ->name('user.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('create_action', 'store')->name('store');
            Route::get('update/{id}', 'edit')->whereNumber('id')->name('edit');
            Route::post('update_action', 'update')->name('update');
            Route::delete('delete/{id}', 'destroy')->whereNumber('id')->name('destroy');
            Route::get('excel', 'excel')->name('excel');
        });

    // Profile sendiri (tombol di header). Cukup login.
    Route::get('tbl_user_profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('tbl_user_profile', [ProfileController::class, 'update'])->name('profile.update');

    // Department & Location seorang staff (isi otomatis form History). Cukup login.
    Route::post('staff/get_detail', [ItemTransactionController::class, 'staffDetail'])->name('staff.get_detail');

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
