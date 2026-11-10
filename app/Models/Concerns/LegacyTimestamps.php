<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Untuk tabel lama yang hanya punya `created_at` + `created_by` (tanpa `updated_at`).
 * - created_at diisi otomatis saat insert, tidak pernah diubah saat update.
 * - created_by diisi id user yang login (sama seperti CI3) bila belum diisi.
 */
trait LegacyTimestamps
{
    public static function bootLegacyTimestamps(): void
    {
        static::creating(function ($model) {
            if (in_array('created_by', $model->getFillable(), true) && empty($model->created_by) && Auth::check()) {
                $model->created_by = Auth::id();
            }
        });
    }

    public function getUpdatedAtColumn()
    {
        return null;
    }
}
