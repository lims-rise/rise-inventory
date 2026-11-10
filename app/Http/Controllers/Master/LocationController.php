<?php

namespace App\Http\Controllers\Master;

use App\Models\Location;
use App\Models\LocationDetail;
use App\Models\Staff;
use App\Models\User;
use App\Support\MasterResource;

/**
 * Pengganti controller CI3 Location (URL tetap /location).
 * Location = negara/kota (dipakai dropdown lokasi di sidebar).
 * Tabel location tidak punya kolom created_at/created_by (audit: false).
 */
class LocationController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: Location::class,
            prefix: 'location',
            title: 'Location',
            icon: 'bi-globe2',
            audit: false,
            touchOnUpdate: false,
            fields: [
                'name' => [
                    'label' => 'Location Name',
                    'list_label' => 'Name',
                    'type' => 'textarea',
                    'rules' => ['required', 'string', 'max:255'],
                    'placeholder' => 'e.g. Makassar - Indonesia',
                    'help' => 'Use the format "City - Country". The sidebar location menu shows the part after " - ".',
                ],
            ],
            // Di CI3 hapus Location selalu gagal (bug di model). Sekarang bisa,
            // tapi hanya bila lokasi itu sudah tidak dipakai, supaya data aset/staff tidak "yatim".
            guard: function (Location $row): ?string {
                $details = LocationDetail::where('id_location', $row->id)->where('status', 1)->count();
                if ($details) {
                    return "it still has {$details} active location detail(s). Move or delete them first.";
                }

                $staff = Staff::where('location_id', $row->id)->where('status', 1)->count();
                if ($staff) {
                    return "{$staff} active staff member(s) are assigned to it.";
                }

                $users = User::where('location_id', $row->id)->where('is_aktif', 'y')->count();
                if ($users) {
                    return "{$users} active user account(s) use it as their default location.";
                }

                return null;
            },
        );
    }
}
