<?php

namespace App\Support;

use App\Models\Department;
use App\Models\ItemCondition;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Pilihan dropdown yang dipakai beberapa form (History, Transfer, Staff). */
class FormOptions
{
    /**
     * Staff aktif dikelompokkan per negara (negara $preferLocation di urutan teratas).
     * department_id & location_id ikut supaya form bisa terisi otomatis.
     */
    public static function staffGrouped(?int $preferLocation): Collection
    {
        return DB::table('staff as s')
            ->leftJoin('location as l', 'l.id', '=', 's.location_id')
            ->where('s.status', 1)
            ->orderByRaw('s.location_id = ? DESC', [(int) $preferLocation])
            ->orderBy('l.id')
            ->orderBy('s.name')
            ->get(['s.id', 's.name', 's.department_id', 's.location_id', 'l.name as location_name'])
            ->groupBy(fn ($r) => $r->location_name ?: 'No location');
    }

    public static function departments(): Collection
    {
        return Department::where('status', 1)->orderBy('name')->pluck('name', 'id');
    }

    public static function locations(): Collection
    {
        return Location::where('status', 1)->orderBy('id')->pluck('name', 'id');
    }

    public static function conditions(): Collection
    {
        return ItemCondition::where('status', 1)->orderBy('id')->pluck('name', 'id');
    }
}
