<?php

namespace App\Support;

/** Warna tiap kondisi aset (id item_condition), dipakai dashboard, list, dan detail. */
class ConditionStyle
{
    public const COLORS = [
        0 => '#cbd5e1', // belum ada riwayat transaksi
        2 => '#14b8a6', // Good
        4 => '#fb7185', // Broken
        5 => '#ef4444', // Decommissioned
        6 => '#f59e0b', // Repair
        7 => '#94a3b8', // Lost
    ];

    public static function color(int|string|null $id): string
    {
        return self::COLORS[(int) $id] ?? '#6366f1';
    }
}
