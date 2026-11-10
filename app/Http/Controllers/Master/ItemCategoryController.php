<?php

namespace App\Http\Controllers\Master;

use App\Models\ItemCategory;
use App\Support\MasterResource;

/** Pengganti controller CI3 Item_category (URL tetap /item_category). */
class ItemCategoryController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: ItemCategory::class,
            prefix: 'item_category',
            title: 'Category',
            icon: 'bi-grid-3x3-gap',
            touchOnUpdate: true, // CI3: update_action mengisi created_at & created_by
            fields: [
                'name' => [
                    'label' => 'Category Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. IT Equipment',
                ],
            ],
        );
    }
}
