<?php

namespace App\Services;

use App\Support\InventoryQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data Reports. Pengganti Reports_model CI3 (yang membaca view v_item_report).
 * Semua laporan memakai InventoryQuery::base() yang sama dengan Inventory List & dashboard,
 * ditambah filter di bawah. Nama parameter sama dengan CI3: date1, date2, cat, subcat, fund, loc, con.
 */
class ReportService
{
    public const DUE_SOON_WEEKS_DEFAULT = 4;

    /** @var array{date1:?string,date2:?string,cat:?int,subcat:?int,fund:?int,loc:?int,con:int|string|null} */
    public array $filters;

    public function __construct(private ?int $locationId, array $filters)
    {
        $this->filters = $filters;
    }

    /** Baca & bersihkan filter dari request (nilai tidak valid diabaikan). */
    public static function filtersFrom(Request $request): array
    {
        $int = fn (string $key) => ctype_digit((string) $request->input($key)) ? (int) $request->input($key) : null;
        $date = function (string $key) use ($request) {
            $v = (string) $request->input($key);

            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
        };

        $date1 = $date('date1');
        $date2 = $date('date2');
        if ($date1 && $date2 && $date1 > $date2) {
            [$date1, $date2] = [$date2, $date1];
        }

        $con = $request->input('con');

        return [
            'date1' => $date1,
            'date2' => $date2,
            'cat' => $int('cat'),
            'subcat' => $int('subcat'),
            'fund' => $int('fund'),
            'loc' => $int('loc'),
            'con' => $con === 'none' ? 'none' : (ctype_digit((string) $con) ? (int) $con : null),
        ];
    }

    public function hasFilters(): bool
    {
        return collect($this->filters)->filter(fn ($v) => $v !== null)->isNotEmpty();
    }

    /** Aset di negara aktif + filter laporan. */
    public function query(): Builder
    {
        $f = $this->filters;

        return InventoryQuery::base($this->locationId)
            ->when($f['cat'], fn ($q, $v) => $q->where('im.category_id', $v))
            ->when($f['subcat'], fn ($q, $v) => $q->where('im.sub_category_id', $v))
            ->when($f['fund'], fn ($q, $v) => $q->where('im.fund_id', $v))
            ->when($f['loc'], fn ($q, $v) => $q->where('im.location_det_id', $v))
            ->when($f['con'] === 'none', fn ($q) => $q->whereNull('it2.id'))
            ->when(is_int($f['con']), fn ($q) => $q->where('it2.condition_id', $f['con']))
            ->when($f['date1'], fn ($q, $v) => $q->where('im.purchase_date', '>=', $v))
            ->when($f['date2'], fn ($q, $v) => $q->where('im.purchase_date', '<=', $v));
    }

    /** Catatan maintenance terakhir per aset (last_service_date terbaru, status = 1). */
    private static function latestMaintenance(): Builder
    {
        $latestDate = DB::table('item_maintenance')
            ->selectRaw('inventory_number, MAX(last_service_date) as d')
            ->where('status', 1)
            ->groupBy('inventory_number');

        return DB::table('item_maintenance as m')
            ->joinSub($latestDate, 'md', function ($join) {
                $join->on('md.inventory_number', '=', 'm.inventory_number')
                    ->on('md.d', '=', 'm.last_service_date');
            })
            ->where('m.status', 1)
            ->selectRaw('MAX(m.id_maintenance) as id, m.inventory_number')
            ->groupBy('m.inventory_number');
    }

    /** query() + kolom maintenance terakhir (alias mt, rs, rf, mpic) + next_service. */
    public function queryWithMaintenance(): Builder
    {
        return $this->query()
            ->leftJoinSub(self::latestMaintenance(), 'mh', 'mh.inventory_number', '=', 'im.inventory_number')
            ->leftJoin('item_maintenance as mt', 'mt.id_maintenance', '=', 'mh.id')
            ->leftJoin('ref_services as rs', 'rs.id_services', '=', 'mt.id_services')
            ->leftJoin('ref_frequency as rf', 'rf.id_frequency', '=', 'mt.id_frequency')
            ->leftJoin('staff as mpic', 'mpic.id', '=', 'mt.id_staff');
    }

    private const NEXT_SERVICE_SQL = 'CASE WHEN rf.months > 0 THEN DATE_ADD(mt.last_service_date, INTERVAL rf.months MONTH) END';

    // ================================================================== Inventory (CI3)

    /** Baris lengkap untuk Excel (27 kolom, sama dengan Excel CI3). */
    public function inventoryRows(): Collection
    {
        return $this->queryWithMaintenance()
            ->orderBy('im.inventory_number')
            ->get([
                'im.inventory_number', 'im.name', 'im.description', 'im.type', 'im.serial_number',
                'im.purchase_date', 'im.purchase_price', 'im.currency',
                'ic2.name as category', 'isc.name as sub_category', 'fund.name as fund',
                'manu.name as manufacture', 'sup.name as supplier', 'qty.qty',
                'loc.name as location', 'ldet.name as location_detail', 'staff.name as staffname',
                'ic.name as condition', 'it2.purpose', 'it2.remark',
                'rs.services', 'rf.frequency', 'mt.last_service_date',
                DB::raw(self::NEXT_SERVICE_SQL.' as next_service'),
                'mt.provider_contact', 'mpic.name as staff_pic', 'mt.service_schedule',
            ]);
    }

    // ================================================================== Summary

    public function summary(): array
    {
        $q = fn () => $this->query();
        $today = now()->toDateString();

        $total = $q()->count();

        $values = $q()->whereNotNull('im.purchase_price')->where('im.purchase_price', '>', 0)
            ->groupBy('im.currency')
            ->selectRaw("COALESCE(im.currency, '—') as currency, SUM(im.purchase_price) as total, COUNT(*) as n")
            ->orderByDesc('n')
            ->get()
            ->map(fn ($r) => ['currency' => $r->currency, 'total' => (float) $r->total, 'n' => (int) $r->n]);

        $condition = $q()->groupBy('ic.id', 'ic.name')
            ->selectRaw("COALESCE(ic.id, 0) as id, COALESCE(ic.name, 'No history') as name, COUNT(*) as n")
            ->orderByDesc('n')->get();

        $good = (int) $condition->firstWhere('id', DashboardService::CONDITION_GOOD)?->n;

        $age = $q()->whereNotNull('im.purchase_date')
            ->selectRaw('AVG(DATEDIFF(?, im.purchase_date)) / 365.25 as years', [$today])
            ->first()?->years;

        // Per kategori > sub kategori, dengan nilai per mata uang
        $categoryRows = $q()
            ->groupBy('ic2.id', 'ic2.name', 'isc.id', 'isc.name', 'im.currency')
            ->selectRaw("COALESCE(ic2.name, 'Uncategorized') as category, COALESCE(isc.name, '—') as sub_category,
                im.currency, COUNT(*) as n, SUM(CASE WHEN im.purchase_price > 0 THEN im.purchase_price ELSE 0 END) as value")
            ->get();

        $currencies = $values->pluck('currency')->reject(fn ($c) => $c === '—')->values()->all();

        $byCategory = $categoryRows
            ->groupBy('category')
            ->map(function ($rows, $category) use ($currencies) {
                $subs = $rows->groupBy('sub_category')->map(function ($subRows, $sub) use ($currencies) {
                    $row = ['name' => $sub, 'n' => (int) $subRows->sum('n'), 'value' => []];
                    foreach ($currencies as $c) {
                        $row['value'][$c] = (float) $subRows->where('currency', $c)->sum('value');
                    }

                    return $row;
                })->sortByDesc('n')->values();

                $value = [];
                foreach ($currencies as $c) {
                    $value[$c] = (float) $rows->where('currency', $c)->sum('value');
                }

                return ['name' => $category, 'n' => (int) $rows->sum('n'), 'value' => $value, 'subs' => $subs];
            })
            ->sortByDesc('n')
            ->values();

        $simple = fn (string $idCol, string $nameCol, string $empty) => $q()
            ->groupBy($idCol, $nameCol)
            ->selectRaw("COALESCE({$nameCol}, ?) as name, COUNT(*) as n", [$empty])
            ->orderByDesc('n')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'n' => (int) $r->n]);

        $byYear = $q()->whereNotNull('im.purchase_date')
            ->groupByRaw('YEAR(im.purchase_date)')
            ->selectRaw('YEAR(im.purchase_date) as y, COUNT(*) as n')
            ->orderBy('y')
            ->get()
            ->map(fn ($r) => ['year' => (string) $r->y, 'n' => (int) $r->n]);

        return [
            'total' => $total,
            'units' => (int) $q()->sum(DB::raw('COALESCE(qty.qty, 0)')),
            'values' => $values,
            'currencies' => $currencies,
            'good' => $good,
            'goodPct' => $total ? round($good / $total * 100, 1) : 0,
            'noHolder' => (int) $condition->firstWhere('id', 0)?->n,
            'avgAge' => $age !== null ? round((float) $age, 1) : null,
            'byCategory' => $byCategory,
            'byLocation' => $simple('ldet.id', 'ldet.name', 'No location detail'),
            'byFund' => $simple('fund.id', 'fund.name', 'No fund'),
            'byCondition' => $condition->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'n' => (int) $r->n]),
            'byYear' => $byYear,
        ];
    }

    // ================================================================== Maintenance due

    /**
     * Aset yang punya catatan maintenance ATAU ditandai "Scheduled check = YES".
     * status: overdue | due | ok | no_frequency | never
     *  - due  = sudah masuk masa pengingat (Schedule prior weeks sebelum next service; default 4 minggu)
     */
    public function maintenance(): Collection
    {
        $today = Carbon::today();

        return $this->queryWithMaintenance()
            ->where(fn ($w) => $w->whereNotNull('mt.id_maintenance')->orWhere('im.scheduled', 'YES'))
            ->orderByRaw(self::NEXT_SERVICE_SQL.' IS NULL, '.self::NEXT_SERVICE_SQL)
            ->get([
                'im.inventory_number', 'im.name', 'im.scheduled', 'ldet.name as location_detail',
                'staff.name as holder', 'mt.id_maintenance', 'mt.last_service_date', 'rs.services',
                'rf.frequency', 'mt.provider_contact', 'mpic.name as staff_pic', 'mt.service_schedule',
                DB::raw(self::NEXT_SERVICE_SQL.' as next_service'),
            ])
            ->map(function ($r) use ($today) {
                if (! $r->id_maintenance) {
                    $r->state = 'never';
                    $r->days = null;
                } elseif (! $r->next_service) {
                    $r->state = 'no_frequency';
                    $r->days = null;
                } else {
                    $next = Carbon::parse($r->next_service);
                    $r->days = (int) round($today->diffInDays($next, false));
                    $lead = ((int) ($r->service_schedule ?: self::DUE_SOON_WEEKS_DEFAULT)) * 7;
                    $r->state = $r->days < 0 ? 'overdue' : ($r->days <= $lead ? 'due' : 'ok');
                }

                return $r;
            })
            ->sortBy(fn ($r) => [
                ['overdue' => 0, 'due' => 1, 'never' => 2, 'no_frequency' => 3, 'ok' => 4][$r->state],
                $r->days ?? PHP_INT_MAX,
            ])
            ->values();
    }

    // ================================================================== Data quality

    /** Pemeriksaan kelengkapan data: key => [label, hint, query]. */
    public function qualityChecks(): array
    {
        return [
            'no_holder' => ['No responsible person', 'Item has no history yet, so nobody is responsible for it.',
                fn ($q) => $q->whereNull('it2.id')],
            'no_photo' => ['No photo', 'Add at least one photo to make the item easy to recognise.',
                fn ($q) => $q->whereNotExists(fn ($s) => $s->selectRaw('1')->from('item_image as img')
                    ->whereColumn('img.inventory_number', 'im.inventory_number'))],
            'no_purchase_date' => ['No purchase date', 'Needed for asset age and the purchase date filter.',
                fn ($q) => $q->whereNull('im.purchase_date')],
            'no_price' => ['No purchase price', 'Needed for asset value totals.',
                fn ($q) => $q->where(fn ($w) => $w->whereNull('im.purchase_price')->orWhere('im.purchase_price', '<=', 0))],
            'no_serial' => ['No serial number', 'Helps when claiming warranty or checking stock.',
                fn ($q) => $q->where(fn ($w) => $w->whereNull('im.serial_number')->orWhere('im.serial_number', ''))],
            'no_fund' => ['No fund', 'Which grant or budget paid for it.',
                fn ($q) => $q->whereNull('im.fund_id')],
            'no_sub_category' => ['No sub category', 'Item is missing from the charts by sub category.',
                fn ($q) => $q->whereNull('im.sub_category_id')],
        ];
    }

    /** [key => ['label','hint','count','items'(maks $limit)]] */
    public function quality(int $limit = 50): array
    {
        $result = [];
        foreach ($this->qualityChecks() as $key => [$label, $hint, $apply]) {
            $base = $apply($this->query());
            $result[$key] = [
                'label' => $label,
                'hint' => $hint,
                'count' => (clone $base)->count(),
                'items' => $base->orderBy('im.inventory_number')->limit($limit)
                    ->get(['im.inventory_number', 'im.name', 'isc.name as sub_category']),
            ];
        }

        return $result;
    }

    /** Semua baris bermasalah untuk Excel: [check label, inventory number, name, sub category]. */
    public function qualityRows(): Collection
    {
        $rows = collect();
        foreach ($this->qualityChecks() as [$label, , $apply]) {
            $apply($this->query())->orderBy('im.inventory_number')
                ->get(['im.inventory_number', 'im.name', 'isc.name as sub_category', 'ldet.name as location_detail'])
                ->each(fn ($r) => $rows->push([$label, $r->inventory_number, $r->name, $r->sub_category, $r->location_detail]));
        }

        return $rows;
    }
}
