<?php

namespace App\Http\Controllers\Master;

use App\Models\ItemCondition;
use App\Services\DashboardService;
use App\Support\MasterResource;

/** Pengganti controller CI3 Item_condition (URL tetap /item_condition). */
class ItemConditionController extends MasterController
{
    protected function resource(): MasterResource
    {
        $reason = 'it is used by the dashboard and asset history.';

        return new MasterResource(
            model: ItemCondition::class,
            prefix: 'item_condition',
            title: 'Condition',
            icon: 'bi-clipboard2-pulse',
            touchOnUpdate: false, // CI3: update_action hanya mengubah name
            fields: [
                'name' => [
                    'label' => 'Condition Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. Good',
                ],
            ],
            // Kondisi bawaan dipakai dashboard (Good, Broken, Decommissioned, Repair, Lost).
            // Boleh diganti nama, tapi tidak boleh dihapus.
            locked: [
                DashboardService::CONDITION_GOOD => $reason,
                DashboardService::CONDITION_BROKEN => $reason,
                DashboardService::CONDITION_DECOMMISSIONED => $reason,
                DashboardService::CONDITION_REPAIR => $reason,
                DashboardService::CONDITION_LOST => $reason,
            ],
        );
    }
}
