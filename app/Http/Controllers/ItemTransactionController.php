<?php

namespace App\Http\Controllers;

use App\Models\ItemMaintenance;
use App\Models\ItemTransaction;
use App\Services\LocationContext;
use App\Support\InventoryQuery;
use App\Support\SimpleXlsx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 Item_transaction (URL sama):
 *   POST item_transaction/action   simpan History  (id kosong = baru)
 *   POST item_transaction/delete   hapus History   (status = 0)
 *   POST item_transaction/action2  simpan Maintenance (id_maintenance kosong = baru)
 *   POST item_transaction/delete2  hapus Maintenance (status = 0)
 *
 * Perbedaan dari CI3:
 *  - Hanya Super Admin & Admin yang boleh mengubah (CI3 hanya menyembunyikan tombol).
 *  - Validasi gagal dikembalikan sebagai JSON 422 dan tampil di form (CI3 diam saja).
 *  - Aset harus ada di negara yang sedang dipilih.
 */
class ItemTransactionController extends Controller
{
    public function __construct(private LocationContext $locations) {}

    private function authorizeEdit(): void
    {
        abort_unless(Auth::user()->canEditInventory(), Response::HTTP_FORBIDDEN,
            'Only Super Admin and Admin can change item history.');
    }

    private function itemInLocation(string $number): bool
    {
        return InventoryQuery::base($this->locations->currentId(Auth::user()))
            ->where('im.inventory_number', $number)
            ->exists();
    }

    private function notFound(string $message = 'Record not found.'): JsonResponse
    {
        return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
    }

    // ------------------------------------------------------------------ History

    public function save(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $data = $request->validate([
            'inventory_number' => ['required', 'string', 'max:25'],
            'id' => ['nullable', 'integer'],
            'transaction_at' => ['required', 'date_format:Y-m-d'],
            'staff_id' => ['required', 'integer', Rule::exists('staff', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('department', 'id')],
            'location_id' => ['required', 'integer', Rule::exists('location', 'id')],
            'condition_id' => ['required', 'integer', Rule::exists('item_condition', 'id')],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'remark' => ['nullable', 'string', 'max:20000'],
        ], [], [
            'transaction_at' => 'date', 'staff_id' => 'responsible person', 'department_id' => 'department',
            'location_id' => 'location', 'condition_id' => 'condition',
        ]);

        if (! $this->itemInLocation($data['inventory_number'])) {
            return $this->notFound('Item not found in the selected location.');
        }

        // Sama dengan CI3: transaction_by, created_at & created_by diisi ulang setiap simpan
        $values = [
            'inventory_number' => $data['inventory_number'],
            'transaction_at' => $data['transaction_at'],
            'staff_id' => $data['staff_id'],
            'department_id' => $data['department_id'],
            'location_id' => $data['location_id'],
            'condition_id' => $data['condition_id'],
            'purpose' => $data['purpose'] ?? null,
            'remark' => $data['remark'] ?? null,
            'status' => 1,
            'transaction_by' => Auth::id(),
            'created_at' => now(),
            'created_by' => Auth::id(),
        ];

        if (! empty($data['id'])) {
            $row = ItemTransaction::where('id', $data['id'])
                ->where('inventory_number', $data['inventory_number'])
                ->where('status', 1)
                ->first();
            if (! $row) {
                return $this->notFound();
            }
            $row->forceFill($values)->save();
            session()->flash('success', 'History has been updated.');
        } else {
            (new ItemTransaction())->forceFill($values)->save();
            session()->flash('success', 'History has been added.');
        }

        return response()->json(['message' => 'Data Saved']);
    }

    public function delete(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $row = ItemTransaction::where('id', (int) $request->input('id'))->where('status', 1)->first();
        if (! $row || ! $this->itemInLocation($row->inventory_number)) {
            return $this->notFound();
        }

        $row->forceFill(['status' => 0])->save();
        session()->flash('success', 'History has been deleted.');

        return response()->json(['message' => 'Delete Record Success']);
    }

    // ------------------------------------------------------------------ Maintenance

    public function saveMaintenance(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $data = $request->validate([
            'inventory_number' => ['required', 'string', 'max:25'],
            'id_maintenance' => ['nullable', 'integer'],
            'last_service_date' => ['required', 'date_format:Y-m-d'],
            'id_services' => ['nullable', 'integer', Rule::exists('ref_services', 'id_services')],
            'provider_contact' => ['nullable', 'string', 'max:100'],
            'staff_id' => ['nullable', 'integer', Rule::exists('staff', 'id')],
            'service_desc' => ['nullable', 'string', 'max:255'],
            'id_frequency' => ['nullable', 'integer', Rule::exists('ref_frequency', 'id_frequency')],
            'service_schedule' => ['nullable', 'integer', 'min:0', 'max:520'],
        ], [], [
            'last_service_date' => 'last service date', 'id_services' => 'service type', 'staff_id' => 'PIC',
            'service_desc' => 'service description', 'id_frequency' => 'frequency', 'service_schedule' => 'schedule',
        ]);

        if (! $this->itemInLocation($data['inventory_number'])) {
            return $this->notFound('Item not found in the selected location.');
        }

        $values = [
            'inventory_number' => $data['inventory_number'],
            'last_service_date' => $data['last_service_date'],
            'id_services' => $data['id_services'] ?? null,
            'provider_contact' => $data['provider_contact'] ?? null,
            'id_staff' => $data['staff_id'] ?? null,
            'service_desc' => $data['service_desc'] ?? null,
            'id_frequency' => $data['id_frequency'] ?? null,
            'service_schedule' => $data['service_schedule'] ?? null,
            'status' => 1,
            'created_at' => now(),
            'created_by' => Auth::id(),
        ];

        if (! empty($data['id_maintenance'])) {
            $row = ItemMaintenance::where('id_maintenance', $data['id_maintenance'])
                ->where('inventory_number', $data['inventory_number'])
                ->where('status', 1)
                ->first();
            if (! $row) {
                return $this->notFound();
            }
            $row->forceFill($values)->save();
            session()->flash('success', 'Maintenance record has been updated.');
        } else {
            (new ItemMaintenance())->forceFill($values)->save();
            session()->flash('success', 'Maintenance record has been added.');
        }

        return response()->json(['message' => 'Data Saved']);
    }

    public function deleteMaintenance(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $row = ItemMaintenance::where('id_maintenance', (int) $request->input('id'))->where('status', 1)->first();
        if (! $row || ! $this->itemInLocation((string) $row->inventory_number)) {
            return $this->notFound();
        }

        $row->forceFill(['status' => 0])->save();
        session()->flash('success', 'Maintenance record has been deleted.');

        return response()->json(['message' => 'Delete Record Success']);
    }

    // ------------------------------------------------------------------ Excel

    /**
     * GET item_transaction/excel?id=…  Riwayat satu aset (dari halaman detail).
     * Tanpa id: semua riwayat aset di negara yang dipilih (CI3 mengekspor semua negara).
     */
    public function excel(Request $request): BinaryFileResponse
    {
        $number = (string) $request->query('id', '');

        $rows = DB::table('item_transaction as t')
            ->join('item_master as im', 'im.inventory_number', '=', 't.inventory_number')
            ->leftJoin('location_detail as ldet', 'ldet.id', '=', 'im.location_det_id')
            ->leftJoin('location as l', 'l.id', '=', 't.location_id')
            ->leftJoin('department as d', 'd.id', '=', 't.department_id')
            ->leftJoin('staff as s', 's.id', '=', 't.staff_id')
            ->leftJoin('item_condition as c', 'c.id', '=', 't.condition_id')
            ->where('t.status', 1)
            ->where('im.status', 1)
            ->where('ldet.id_location', $this->locations->currentId(Auth::user()))
            ->when($number !== '', fn ($q) => $q->where('t.inventory_number', $number))
            ->orderByDesc('t.transaction_at')
            ->orderByDesc('t.id')
            ->get(['t.transaction_at', 't.inventory_number', 'l.name as location_name', 'd.name as department_name',
                's.name as staff_name', 't.purpose', 'c.name as condition_name', 't.remark'])
            ->values()
            ->map(fn ($r, $i) => [$i + 1, $r->transaction_at ? substr($r->transaction_at, 0, 10) : '', $r->inventory_number,
                $r->location_name, $r->department_name, $r->staff_name, $r->purpose, $r->condition_name, $r->remark]);

        return SimpleXlsx::make('History',
            ['No', 'Date', 'Inventory Number', 'Location', 'Department', 'Responsible Person', 'Purpose', 'Condition', 'Remark'],
            $rows,
        )->download('item_history_'.($number !== '' ? preg_replace('/[^A-Za-z0-9]+/', '-', $number).'_' : '').date('Ymd').'.xlsx');
    }

    /** GET item_transaction/excel2?id=…  Maintenance satu aset (CI3 keliru memakai Excel riwayat). */
    public function excelMaintenance(Request $request): BinaryFileResponse
    {
        $number = (string) $request->query('id', '');
        abort_if($number === '' || ! $this->itemInLocation($number), Response::HTTP_NOT_FOUND);

        $rows = DB::table('item_maintenance as m')
            ->leftJoin('ref_services as rs', 'rs.id_services', '=', 'm.id_services')
            ->leftJoin('ref_frequency as rf', 'rf.id_frequency', '=', 'm.id_frequency')
            ->leftJoin('staff as s', 's.id', '=', 'm.id_staff')
            ->where('m.inventory_number', $number)
            ->where('m.status', 1)
            ->orderByDesc('m.last_service_date')
            ->get(['m.last_service_date', 'rs.services', 'm.provider_contact', 's.name as staff_name',
                'm.service_desc', 'rf.frequency', 'm.service_schedule'])
            ->values()
            ->map(fn ($r, $i) => [$i + 1, $number, $r->last_service_date, $r->services, $r->provider_contact,
                $r->staff_name, $r->service_desc, $r->frequency, $r->service_schedule !== null ? (int) $r->service_schedule : null]);

        return SimpleXlsx::make('Maintenance',
            ['No', 'Inventory Number', 'Latest services date', 'Services type', 'Services provider contact', 'Lab PIC',
                'Services description', 'Frequency', 'Schedule prior (weeks)'],
            $rows,
        )->download('item_maintenance_'.preg_replace('/[^A-Za-z0-9]+/', '-', $number).'_'.date('Ymd').'.xlsx');
    }

    // ------------------------------------------------------------------ Staff lookup

    /** POST staff/get_detail (id) -> {department_id, location_id}, sama dengan CI3. */
    public function staffDetail(Request $request): JsonResponse
    {
        $row = DB::table('staff')
            ->where('id', (int) $request->input('id'))
            ->where('status', 1)
            ->first(['department_id', 'location_id']);

        return response()->json($row);
    }
}
