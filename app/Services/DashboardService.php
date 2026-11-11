<?php

namespace App\Services;

use App\Support\InventoryQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data dashboard, pengganti Item_master_model::get_condition(), get_summary(), get_staff_activity().
 * Semua angka memakai InventoryQuery::base() yang sama dengan Inventory List, jadi selalu cocok.
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

    private function items(): Builder
    {
        return InventoryQuery::base($this->locationId);
    }

    /**
     * Jumlah aset per kondisi transaksi terakhir: [{id, name, val}], terbanyak dulu.
     * Aset tanpa riwayat transaksi masuk sebagai id 0 "No history" (CI3 menghitungnya di total).
     */
    public function conditionBreakdown(): Collection
    {
        return $this->breakdown ??= $this->items()
            ->groupBy('ic.id', 'ic.name')
            ->selectRaw("COALESCE(ic.id, 0) as id, COALESCE(ic.name, 'No history') as name, COUNT(*) as val")
            ->orderByDesc('val')
            ->get()
            ->map(function ($row) {
                $row->id = (int) $row->id;
                $row->val = (int) $row->val;

                return $row;
            });
    }

    /** Ringkasan kartu KPI. total = semua aset di negara ini, sama seperti Welcome.php. */
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

    /** Jumlah aset per sub kategori (data chart), sama dengan get_summary() CI3. */
    public function inventoryBySubCategory(): Collection
    {
        return $this->items()
            ->groupBy('im.sub_category_id', 'isc.name')
            ->selectRaw("COALESCE(isc.name, 'Uncategorized') as name, COUNT(*) as val")
            ->orderByDesc('val')
            ->orderBy('name')
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

    /**
     * Staff yang SAAT INI memegang aset (penanggung jawab di transaksi terakhir), seperti CI3,
     * diurutkan dari aktivitas terbaru. total = jumlah aset yang dipegang.
     */
    public function staffActivity(int $limit = 10): Collection
    {
        return $this->items()
            ->whereNotNull('staff.id')
            ->when($this->locationId, fn ($q) => $q->where('staff.location_id', $this->locationId))
            ->groupBy('staff.id', 'staff.name', 'staff.image')
            ->selectRaw('staff.id, staff.name, staff.image, MAX(d.name) as department_name, COUNT(*) as total,
                MAX(COALESCE(it2.transaction_at, it2.created_at)) as last_at')
            ->orderByDesc('last_at')
            ->limit($limit)
            ->get();
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
            ->leftJoin('location_detail as ldet', 'ldet.id', '=', 'im.location_det_id')
            ->leftJoin('staff as s', 's.id', '=', 't.staff_id')
            ->leftJoin('item_condition as c', 'c.id', '=', 't.condition_id')
            ->where('t.status', 1)
            ->where('im.status', 1)
            ->when($this->locationId, fn ($q) => $q->where('ldet.id_location', $this->locationId))
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
