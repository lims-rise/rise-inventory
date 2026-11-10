<?php

namespace App\Providers;

use App\Services\LocationContext;
use App\Services\MenuService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Satu instance per request, supaya hasil query dipakai bersama sidebar, middleware & controller.
        $this->app->scoped(MenuService::class);
        $this->app->scoped(LocationContext::class);
    }

    public function boot(): void
    {
        /*
         * Route::legacyMaster('item_category', ItemCategoryController::class);
         * Mendaftarkan URL CRUD yang sama dengan CI3 (Harviacode), dengan cek hak akses menu.
         * Hapus memakai DELETE + token CSRF (CI3 memakai link GET yang rawan disalahgunakan).
         */
        Route::macro('legacyMaster', function (string $prefix, string $controller) {
            Route::prefix($prefix)
                ->middleware('menu.access')
                ->controller($controller)
                ->name($prefix.'.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('json', 'json')->name('json');
                    Route::get('read/{id}', 'read')->whereNumber('id')->name('read');
                    Route::get('create', 'create')->name('create');
                    Route::post('create_action', 'store')->name('store');
                    Route::get('update/{id}', 'edit')->whereNumber('id')->name('edit');
                    Route::post('update_action', 'update')->name('update');
                    Route::delete('delete/{id}', 'destroy')->whereNumber('id')->name('destroy');
                    Route::get('excel', 'excel')->name('excel');
                });
        });

        View::composer('layouts.partials.sidebar', function ($view) {
            $user = Auth::user();
            $locations = app(LocationContext::class);

            $view->with([
                'sidebarMenus' => $user
                    ? app(MenuService::class)->treeForLevel((int) $user->id_user_level)
                    : collect(),
                'locationOptions' => $locations->optionsFor($user),
                'currentLocationId' => $locations->currentId($user),
                'canSwitchLocation' => $locations->canSwitch($user),
            ]);
        });
    }
}
