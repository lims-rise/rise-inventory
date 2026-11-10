<?php

namespace App\Http\Controllers\Master;

use App\Models\ItemSupplier;
use App\Support\MasterResource;

/** Pengganti controller CI3 Item_supplier (URL tetap /item_supplier). */
class ItemSupplierController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: ItemSupplier::class,
            prefix: 'item_supplier',
            title: 'Supplier / Agent',
            icon: 'bi-truck',
            touchOnUpdate: true, // CI3: update_action mengisi created_at & created_by
            fields: [
                'name' => [
                    'label' => 'Supplier Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. PT Sumber Teknik',
                ],
            ],
        );
    }
}
