<?php

namespace App\Http\Controllers\Master;

use App\Models\ItemCategory;
use App\Models\ItemSubCategory;
use App\Support\MasterResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Pengganti controller CI3 Item_sub_category (URL tetap /item_sub_category). */
class ItemSubCategoryController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: ItemSubCategory::class,
            prefix: 'item_sub_category',
            title: 'Sub Category',
            icon: 'bi-diagram-2',
            touchOnUpdate: true, // CI3: update_action mengisi created_at & created_by
            // Nama kategori ikut diambil (join) supaya bisa dicari & diurutkan di tabel
            scope: fn (Builder $q) => $q
                ->leftJoin('item_category', 'item_category.id', '=', 'item_sub_category.category_id')
                ->addSelect('item_category.name as category_name'),
            // Tabel: Name | Category Name (seperti CI3). Form: Category dulu, lalu Name.
            formOrder: ['category_id', 'name'],
            fields: [
                'name' => [
                    'label' => 'Sub Category Name',
                    'list_label' => 'Name',
                    'rules' => ['required', 'string', 'max:100'],
                    'placeholder' => 'e.g. Laptop',
                ],
                'category_id' => [
                    'label' => 'Category',
                    'list_label' => 'Category Name',
                    'type' => 'select',
                    'rules' => ['required', 'integer', Rule::exists('item_category', 'id')],
                    'options' => fn () => ItemCategory::where('status', 1)->orderBy('name')->pluck('name', 'id'),
                    'display' => fn ($row) => $row->category_name ?? '',
                    'search' => 'item_category.name',
                    'sort' => 'item_category.name',
                ],
            ],
        );
    }

    /**
     * POST /item_sub_category/list  (id = category_id, select = sub category terpilih)
     * Dipakai dropdown bertingkat Category -> Sub Category di form aset (item_master).
     * Format respons sama dengan CI3: {"data": "<option ...>...</option>"}.
     * Perbedaan: hanya sub category aktif, kecuali yang sedang terpilih tetap ditampilkan.
     */
    public function options(Request $request): JsonResponse
    {
        $categoryId = (int) $request->input('id');
        $selected = (string) $request->input('select', '');

        $rows = ItemSubCategory::where('category_id', $categoryId)
            ->where(fn ($q) => $q->where('status', 1)
                ->when($selected !== '', fn ($q) => $q->orWhere('id', $selected)))
            ->orderBy('name')
            ->get(['id', 'name']);

        $html = '<option value=""></option>';
        foreach ($rows as $row) {
            $isSelected = (string) $row->id === $selected ? ' selected="selected"' : '';
            $html .= '<option value="'.e($row->id).'"'.$isSelected.'>'.e($row->name).'</option>';
        }

        return response()->json(['data' => $html]);
    }
}
