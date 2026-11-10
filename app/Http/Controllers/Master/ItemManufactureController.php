<?php

namespace App\Http\Controllers\Master;

use App\Models\ItemManufacture;
use App\Support\MasterResource;

/** Pengganti controller CI3 Item_manufacture (URL tetap /item_manufacture). */
class ItemManufactureController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: ItemManufacture::class,
            prefix: 'item_manufacture',
            title: 'Manufacture',
            icon: 'bi-building-gear',
            touchOnUpdate: true, // CI3: update_action mengisi created_at & created_by
            fields: [
                'name' => [
                    'label' => 'Manufacture Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. Dell',
                ],
            ],
        );
    }
}
