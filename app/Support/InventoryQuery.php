<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Query dasar inventaris, pengganti Item_master_model::master() di CI3.
 * Dipakai Inventory List, Excel, halaman detail, dan dashboard supaya angkanya selalu sama.
 *
 * Alias tabel sama dengan CI3:
 *   im   = item_master            it2  = transaksi TERAKHIR aset (lihat latestTransactions)
 *   staff, d (department), l (location transaksi), ic (item_condition)
 *   ic2  = item_category          isc  = item_sub_category
 *   fund, manu, sup, qty          ldet = location_detail aset, loc = location (negara) aset
 *
 * Filter: im.status = 1 dan negara = loc.id (dari location_detail aset), seperti CI3.
 */
class InventoryQuery
{
    /**
     * Transaksi terakhir per aset: transaction_at paling baru (hanya status = 1);
     * bila ada beberapa di waktu yang sama, ambil id terbesar. Sama dengan subquery "hist" CI3.
     */
    public static function latestTransactions(): Builder
    {
        $latestDate = DB::table('item_transaction')
            ->selectRaw('inventory_number, MAX(transaction_at) as transaction_at')
            ->where('status', 1)
            ->groupBy('inventory_number');

        return DB::table('item_transaction as t')
            ->joinSub($latestDate, 'm', function ($join) {
                $join->on('m.inventory_number', '=', 't.inventory_number')
                    ->on('m.transaction_at', '=', 't.transaction_at');
            })
            ->where('t.status', 1)
            ->selectRaw('MAX(t.id) as id, t.inventory_number')
            ->groupBy('t.inventory_number');
    }

    /** Baris item_qty terbaru per aset (mencegah baris ganda bila ada lebih dari satu). */
    public static function latestQty(): Builder
    {
        $ids = DB::table('item_qty')->selectRaw('MAX(id)')->groupBy('inventory_number');

        return DB::table('item_qty')->whereIn('id', $ids)->select('inventory_number', 'qty');
    }

    public static function base(?int $locationId): Builder
    {
        return DB::table('item_master as im')
            ->leftJoinSub(self::latestTransactions(), 'hist', 'hist.inventory_number', '=', 'im.inventory_number')
            ->leftJoin('item_transaction as it2', 'it2.id', '=', 'hist.id')
            ->leftJoin('staff', 'staff.id', '=', 'it2.staff_id')
            ->leftJoin('location as l', 'l.id', '=', 'it2.location_id')
            ->leftJoin('department as d', 'd.id', '=', 'it2.department_id')
            ->leftJoin('item_condition as ic', 'ic.id', '=', 'it2.condition_id')
            ->leftJoin('item_category as ic2', 'ic2.id', '=', 'im.category_id')
            ->leftJoin('item_sub_category as isc', 'isc.id', '=', 'im.sub_category_id')
            ->leftJoin('item_fund as fund', 'fund.id', '=', 'im.fund_id')
            ->leftJoin('item_manufacture as manu', 'manu.id', '=', 'im.manufacture_id')
            ->leftJoin('item_supplier as sup', 'sup.id', '=', 'im.supplier_id')
            ->leftJoinSub(self::latestQty(), 'qty', 'qty.inventory_number', '=', 'im.inventory_number')
            ->leftJoin('location_detail as ldet', 'ldet.id', '=', 'im.location_det_id')
            ->leftJoin('location as loc', 'loc.id', '=', 'ldet.id_location')
            ->where('im.status', 1)
            ->when($locationId, fn ($q) => $q->where('loc.id', $locationId));
    }
}
