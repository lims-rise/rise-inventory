<?php

namespace App\Http\Controllers;

use App\Services\LocationContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /** Dipanggil dari dropdown lokasi di sidebar. */
    public function switch(Request $request, LocationContext $locations): RedirectResponse
    {
        $data = $request->validate(['location_id' => ['required', 'integer']]);

        if (! $locations->switchTo($request->user(), (int) $data['location_id'])) {
            return back()->with('error', 'Anda tidak memiliki akses ke lokasi tersebut.');
        }

        return back();
    }
}
