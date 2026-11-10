<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data dashboard (pengganti Item_master_model::get_condition/get_summary/get_staff_activity di CI3).
 *
 * Aturan yang dipakai (cocokkan dengan aplikasi lama):
 *  - Satu baris item_master = satu unit aset.
 *  - Negara aktif = location_detail.id_location dari item_master.location_det_id.
 *  - Kondisi aset = condition_id pada transaksi TERAKHIR (id terbesar) aset tersebut.
 */
class DashboardService
{
    public const CONDITION_GOOD = 2;
    public const CONDITION_BROKEN = 4;
    public const CONDITION_DECOMMISSIONED = 5;
    public const CONDITION_REPAIR = 6;
    public const CONDITION_LOST = 7;

    private ?Collection $breakdown = null;

    public function __construct(private ?int $locationId) {}

    /** Aset di negara yang sedang dipilih. */
    private function items(): Builder
    {
        return DB::table('item_master as im')
            ->when($this->locationId, fn ($q) => $q
                ->join('location_detail as ld', 'ld.id', '=', 'im.location_det_id')
                ->where('ld.id_location', $this->locationId));
    }

    /** Transaksi terakhir (id terbesar) per aset. */
    private function latestTransactions(): Builder
    {
        return DB::table('item_transaction')
            ->selectRaw('inventory_number, MAX(id) as last_id')
            ->groupBy('inventory_number');
    }

    /** Jumlah aset per kondisi, lengkap dengan nama: [{id, name, val}], terbanyak dulu. */
    public function conditionBreakdown(): Collection
    {
        return $this->breakdown ??= $this->items()
            ->joinSub($this->latestTransactions(), 'lt', 'lt.inventory_number', '=', 'im.inventory_number')
            ->join('item_transaction as t', 't.id', '=', 'lt.last_id')
            ->leftJoin('item_condition as c', 'c.id', '=', 't.condition_id')
            ->groupBy('t.condition_id', 'c.name')
            ->selectRaw("t.condition_id as id, COALESCE(c.name, 'Unknown') as name, COUNT(*) as val")
            ->orderByDesc('val')
            ->get()
            ->map(function ($row) {
                $row->id = (int) $row->id;
                $row->val = (int) $row->val;

                return $row;
            });
    }

    /** Ringkasan untuk kartu KPI. total = semua kondisi, sama seperti Welcome.php. */
    public function conditionSummary(): array
    {
        $count = fn (int ...$ids) => (int) $this->conditionBreakdown()->whereIn('id', $ids)->sum('val');

        return [
            'good' => $count(self::CONDITION_GOOD),
            'repair' => $count(self::CONDITION_REPAIR),
            'decommissioned' => $count(self::CONDITION_DECOMMISSIONED),
            'attention' => $count(self::CONDITION_BROKEN, self::CONDITION_LOST),
            'total' => (int) $this->conditionBreakdown()->sum('val'),
        ];
    }

    /** Jumlah aset per sub kategori, terbanyak dulu (data chart). */
    public function inventoryBySubCategory(): Collection
    {
        return $this->items()
            ->join('item_sub_category as sc', 'sc.id', '=', 'im.sub_category_id')
            ->groupBy('sc.id', 'sc.name')
            ->selectRaw('sc.name, COUNT(*) as val')
            ->orderByDesc('val')
            ->orderBy('sc.name')
            ->get();
    }

    public function totalItems(): int
    {
        return $this->items()->count();
    }

    public function categoryCount(): int
    {
        return (int) $this->items()->whereNotNull('im.sub_category_id')->distinct()->count('im.sub_category_id');
    }

    /** Staff di negara ini dengan transaksi terakhir paling baru (maks. $limit). */
    public function staffActivity(int $limit = 10): Collection
    {
        $activity = DB::table('item_transaction')
            ->selectRaw('staff_id, COUNT(*) as total, MAX(COALESCE(transaction_at, created_at)) as last_at')
            ->groupBy('staff_id');

        return DB::table('staff as s')
            ->joinSub($activity, 'a', 'a.staff_id', '=', 's.id')
            ->leftJoin('department as d', 'd.id', '=', 's.department_id')
            ->where('s.status', 1)
            ->when($this->locationId, fn ($q) => $q->where('s.location_id', $this->locationId))
            ->orderByDesc('a.last_at')
            ->limit($limit)
            ->get(['s.id', 's.name', 's.image', 'd.name as department_name', 'a.total', 'a.last_at']);
    }

    public function activeStaffCount(): int
    {
        return DB::table('staff')
            ->where('status', 1)
            ->when($this->locationId, fn ($q) => $q->where('location_id', $this->locationId))
            ->count();
    }

    /** Transaksi aset terbaru di negara ini. */
    public function recentTransactions(int $limit = 6): Collection
    {
        return DB::table('item_transaction as t')
            ->join('item_master as im', 'im.inventory_number', '=', 't.inventory_number')
            ->when($this->locationId, fn ($q) => $q
                ->join('location_detail as ld', 'ld.id', '=', 'im.location_det_id')
                ->where('ld.id_location', $this->locationId))
            ->leftJoin('staff as s', 's.id', '=', 't.staff_id')
            ->leftJoin('item_condition as c', 'c.id', '=', 't.condition_id')
            ->orderByRaw('COALESCE(t.transaction_at, t.created_at) DESC')
            ->orderByDesc('t.id')
            ->limit($limit)
            ->get([
                't.id', 't.inventory_number', 'im.name as item_name', 's.name as staff_name',
                't.condition_id', 'c.name as condition_name',
                DB::raw('COALESCE(t.transaction_at, t.created_at) as happened_at'),
            ]);
    }
}
