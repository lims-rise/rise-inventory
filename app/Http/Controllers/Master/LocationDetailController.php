<?php

namespace App\Http\Controllers\Master;

use App\Models\Location;
use App\Models\LocationDetail;
use App\Services\LocationContext;
use App\Support\MasterResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pengganti controller CI3 Location_detail (URL tetap /location_detail).
 * Location Detail = tempat/bangunan di dalam sebuah Location (negara).
 */
class LocationDetailController extends MasterController
{
    protected function resource(): MasterResource
    {
        return new MasterResource(
            model: LocationDetail::class,
            prefix: 'location_detail',
            title: 'Location Detail',
            icon: 'bi-building',
            touchOnUpdate: true, // CI3: update_action mengisi created_at & created_by
            scope: fn (Builder $q) => $q
                ->leftJoin('location', 'location.id', '=', 'location_detail.id_location')
                ->addSelect('location.name as location_name'),
            // Tabel: Location Detail | Location (seperti CI3). Form: Location dulu.
            formOrder: ['id_location', 'name'],
            fields: [
                'name' => [
                    'label' => 'Location Detail',
                    'rules' => ['required', 'string', 'max:255'],
                    'placeholder' => 'e.g. UNHAS Laboratory',
                ],
                'id_location' => [
                    'label' => 'Location',
                    'type' => 'select',
                    'rules' => ['required', 'integer', Rule::exists('location', 'id')],
                    'options' => fn () => Location::where('status', 1)->orderBy('id')->pluck('name', 'id'),
                    'display' => fn ($row) => $row->location_name ?? '',
                    'search' => 'location.name',
                    'sort' => 'location.name',
                    // Form baru langsung memilih negara yang aktif di sidebar
                    'default' => fn () => app(LocationContext::class)->currentId(auth()->user()),
                ],
            ],
        );
    }

    /**
     * POST /location_detail/list  (id = id_location, select = location detail terpilih)
     * Dropdown bertingkat Location -> Location Detail di form aset. Format respons sama dengan CI3.
     * Perbedaan: hanya yang aktif, kecuali yang sedang terpilih tetap ditampilkan.
     */
    public function options(Request $request): JsonResponse
    {
        $locationId = (int) $request->input('id');
        $selected = (string) $request->input('select', '');

        $rows = LocationDetail::where('id_location', $locationId)
            ->where(fn ($q) => $q->where('status', 1)
                ->when($selected !== '', fn ($q) => $q->orWhere('id', $selected)))
            ->orderBy('id')
            ->get(['id', 'name']);

        $html = '<option value=""></option>';
        foreach ($rows as $row) {
            $isSelected = (string) $row->id === $selected ? ' selected="selected"' : '';
            $html .= '<option value="'.e($row->id).'"'.$isSelected.'>'.e($row->name).'</option>';
        }

        return response()->json(['data' => $html]);
    }
}
