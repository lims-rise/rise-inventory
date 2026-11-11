<?php

namespace App\Http\Controllers;

use App\Models\ItemTransaction;
use App\Models\Location;
use App\Models\Staff;
use App\Models\User;
use App\Services\LocationContext;
use App\Support\ConditionStyle;
use App\Support\DataTables;
use App\Support\FormOptions;
use App\Support\InventoryQuery;
use App\Support\SimpleXlsx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 Staff (URL sama).
 *
 * Hak akses (sama dengan tombol CI3, sekarang juga dicek di server):
 *   Super Admin: lihat, tambah, ubah, hapus, transfer | Admin: lihat, tambah, ubah, transfer | Staff: lihat saja.
 * Hanya staff di negara yang dipilih di sidebar yang tampil (sama dengan CI3).
 */
class StaffController extends Controller
{
    /** Folder foto staff (sama dengan CI3). */
    public const IMAGE_DIR = 'assets/images';

    private const FILE_PATTERN = '/^[A-Za-z0-9]{20,64}\.(jpe?g|png|gif|webp)$/i';

    public function __construct(private LocationContext $locations) {}

    private function locationId(): ?int
    {
        return $this->locations->currentId(Auth::user());
    }

    private function user(): User
    {
        return Auth::user();
    }

    private function authorizeEdit(): void
    {
        abort_unless($this->user()->canEditInventory(), Response::HTTP_FORBIDDEN,
            'Only Super Admin and Admin can change staff data.');
    }

    /** Staff aktif di negara aktif (pengganti Staff_model::get_by_id). */
    private function findStaff(int|string|null $id): ?object
    {
        if (blank($id)) {
            return null;
        }

        return DB::table('staff as s')
            ->leftJoin('department as d', 'd.id', '=', 's.department_id')
            ->leftJoin('location as l', 'l.id', '=', 's.location_id')
            ->where('s.status', 1)
            ->where('s.location_id', $this->locationId())
            ->where('s.id', (int) $id)
            ->first(['s.*', 'd.name as department_name', 'l.name as location_name']);
    }

    /** Jumlah aset yang saat ini dipegang tiap staff (staff_id => jumlah), satu query. */
    private function heldCounts(): array
    {
        return InventoryQuery::base($this->locationId())
            ->whereNotNull('it2.staff_id')
            ->groupBy('it2.staff_id')
            ->selectRaw('it2.staff_id as sid, COUNT(*) as n')
            ->pluck('n', 'sid')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    public static function photoUrl(?string $image): string
    {
        $file = trim((string) $image);

        return $file !== '' && is_file(public_path(self::IMAGE_DIR.'/'.$file))
            ? asset(self::IMAGE_DIR.'/'.$file)
            : asset('images/avatar-default.svg');
    }

    // ------------------------------------------------------------------ List + form

    /** GET staff  (?id= membuka form edit di panel kiri, seperti CI3). */
    public function index(Request $request): View|RedirectResponse
    {
        $editing = null;
        if ($request->filled('id')) {
            $this->authorizeEdit();
            $editing = $this->findStaff($request->query('id'));
            if (! $editing) {
                return redirect()->route('staff.index')->with('warning', 'Staff not found in the selected location.');
            }
        }

        $locationId = $this->locationId();

        return view('staff.index', [
            'editing' => $editing,
            'departments' => FormOptions::departments(),
            'locations' => FormOptions::locations(),
            'defaultLocation' => $locationId,
            'total' => DB::table('staff')->where('status', 1)->where('location_id', $locationId)->count(),
            'locationLabel' => LocationContext::label((string) Location::whereKey($locationId)->value('name')),
            'canEdit' => $this->user()->canEditInventory(),
            'canDelete' => $this->user()->canDeleteInventory(),
        ]);
    }

    /** Data tabel staff (pengganti Staff_model::json). */
    public function json(Request $request): JsonResponse
    {
        $canEdit = $this->user()->canEditInventory();
        $canDelete = $this->user()->canDeleteInventory();
        $held = $this->heldCounts();

        $query = DB::table('staff as s')
            ->leftJoin('department as d', 'd.id', '=', 's.department_id')
            ->leftJoin('location as l', 'l.id', '=', 's.location_id')
            ->where('s.status', 1)
            ->where('s.location_id', $this->locationId())
            ->select(['s.id', 's.name', 's.image', 'd.name as department_name', 'l.name as location_name']);

        return DataTables::make($request, $query)
            ->searchable(['s.name', 'd.name', 'l.name'])
            ->orderable(['name' => 's.name', 'department_name' => 'd.name', 'location_name' => 'l.name'])
            ->defaultOrder('s.id', 'desc')
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => e($row->name),
                'department_name' => e((string) $row->department_name),
                'location_name' => e((string) $row->location_name),
                'photo' => self::photoUrl($row->image),
                'items' => $held[$row->id] ?? 0,
                'urls' => [
                    'read' => route('staff.read', ['id' => $row->id]),
                    'edit' => $canEdit ? route('staff.index', ['id' => $row->id]) : null,
                    'delete' => $canDelete ? route('staff.destroy', $row->id) : null,
                ],
            ])
            ->response();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEdit();
        $data = $this->validateStaff($request);

        $staff = new Staff();
        $staff->forceFill([
            'name' => $data['name'],
            'department_id' => $data['department_id'],
            'location_id' => $data['location_id'] ?? null,
            'status' => 1,
        ] + (! empty($data['file']) ? ['image' => $data['file']] : []))->save();

        return redirect()->route('staff.index')->with('success', 'Staff "'.$data['name'].'" has been created.');
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEdit();
        $current = $this->findStaff($request->input('id'));
        if (! $current) {
            return redirect()->route('staff.index')->with('warning', 'Staff not found in the selected location.');
        }

        $data = $this->validateStaff($request);

        Staff::whereKey($current->id)->update([
            'name' => $data['name'],
            'department_id' => $data['department_id'],
            'location_id' => $data['location_id'] ?? null,
        ] + (! empty($data['file']) ? ['image' => $data['file']] : []));

        return redirect()->route('staff.index')->with('success', 'Staff "'.$data['name'].'" has been updated.');
    }

    /** Soft delete (status = 0), hanya Super Admin; ditolak bila staff masih memegang aset. */
    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->user()->canDeleteInventory(), Response::HTTP_FORBIDDEN, 'Only Super Admin can delete staff.');

        $staff = $this->findStaff($id);
        if (! $staff) {
            return redirect()->route('staff.index')->with('warning', 'Staff not found in the selected location.');
        }

        $held = $this->heldCounts()[$staff->id] ?? 0;
        if ($held > 0) {
            return redirect()->route('staff.index')->with('error',
                $staff->name.' still holds '.$held.' '.Str::plural('item', $held).'. Use "Transfer All" on their profile first.');
        }

        Staff::whereKey($staff->id)->update(['status' => 0]);

        return redirect()->route('staff.index')->with('success', 'Staff "'.$staff->name.'" has been deleted.');
    }

    /** Upload foto (AJAX), pengganti staff/upload CI3. Respons sama: {error, data}. */
    public function upload(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $validator = Validator::make($request->all(), [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ], [], ['image' => 'photo']);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first('image')], 422);
        }

        $file = $request->file('image');
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $name = Str::random(32).'.'.($extension === 'jpeg' ? 'jpg' : $extension);

        File::ensureDirectoryExists(public_path(self::IMAGE_DIR));
        $file->move(public_path(self::IMAGE_DIR), $name);

        return response()->json(['error' => false, 'data' => $name, 'url' => asset(self::IMAGE_DIR.'/'.$name)]);
    }

    public function excel(): BinaryFileResponse
    {
        $held = $this->heldCounts();

        $rows = DB::table('staff as s')
            ->leftJoin('department as d', 'd.id', '=', 's.department_id')
            ->leftJoin('location as l', 'l.id', '=', 's.location_id')
            ->where('s.status', 1)
            ->where('s.location_id', $this->locationId())
            ->orderBy('s.name')
            ->get(['s.id', 's.name', 'd.name as department_name', 'l.name as location_name'])
            ->values()
            ->map(fn ($r, $i) => [$i + 1, $r->name, $r->department_name, $r->location_name, $held[$r->id] ?? 0]);

        return SimpleXlsx::make('Staff', ['No', 'Name', 'Department', 'Location', 'Items held'], $rows)
            ->download('staff_'.date('Ymd').'.xlsx');
    }

    // ------------------------------------------------------------------ Profil

    /** GET staff/read?id=…  Tanpa id: profil staff milik user yang login (halaman awal level Staff). */
    public function read(Request $request): View|RedirectResponse
    {
        $id = $request->query('id') ?: $this->user()->staff_id;
        $staff = $this->findStaff($id);

        if (! $staff) {
            if (blank($request->query('id')) && ! $this->user()->canEditInventory()) {
                return redirect()->route('item_master.index')
                    ->with('warning', 'Your account is not linked to a staff profile in this location. Ask an administrator.');
            }

            return redirect()->route('staff.index')->with('warning', 'Staff not found in the selected location.');
        }

        $base = fn () => InventoryQuery::base($this->locationId())->where('it2.staff_id', $staff->id);

        $conditions = $base()
            ->groupBy('it2.condition_id', 'ic.name')
            ->selectRaw("it2.condition_id as id, COALESCE(ic.name, 'Unknown') as name, COUNT(*) as val")
            ->orderByDesc('val')
            ->get()
            ->map(function ($r) {
                $r->val = (int) $r->val;
                $r->color = ConditionStyle::color($r->id);

                return $r;
            });

        $subCategories = $base()
            ->groupBy('im.sub_category_id', 'isc.name')
            ->selectRaw("COALESCE(isc.name, 'Uncategorized') as name, COUNT(*) as val")
            ->orderByDesc('val')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'y' => (int) $r->val])
            ->values();

        $canEdit = $this->user()->canEditInventory();

        return view('staff.show', [
            'staff' => $staff,
            'photo' => self::photoUrl($staff->image),
            'conditions' => $conditions,
            'total' => (int) $conditions->sum('val'),
            'chartData' => $subCategories,
            'canEdit' => $canEdit,
            'options' => $canEdit ? [
                'staff' => FormOptions::staffGrouped($this->locationId()),
                'departments' => FormOptions::departments(),
                'locations' => FormOptions::locations(),
                'conditions' => FormOptions::conditions(),
            ] : null,
            'backUrl' => $this->backUrl(),
        ]);
    }

    /** Back di profil: halaman sebelumnya, kecuali profil staff lain / dirinya sendiri. */
    private function backUrl(): string
    {
        $previous = url()->previous();
        $path = trim((string) parse_url($previous, PHP_URL_PATH), '/');

        return str_starts_with($previous, url('/')) && ! str_ends_with($path, 'staff/read') && $previous !== url()->current()
            ? $previous
            : route('staff.index');
    }

    /** Aset yang sedang dipegang staff (pengganti staff/data CI3). */
    public function data(Request $request): JsonResponse
    {
        $staff = $this->findStaff($request->input('staff_id'));
        $canEdit = $this->user()->canEditInventory();

        $query = InventoryQuery::base($this->locationId())
            ->where('it2.staff_id', $staff->id ?? 0)
            ->select([
                'im.inventory_number', 'im.name', 'im.description', 'im.serial_number', 'im.type',
                'it2.transaction_at', 'ic2.name as category_name', 'isc.name as sub_category_name',
                'it2.purpose', 'it2.remark', 'it2.condition_id', 'ic.name as condition_name',
            ]);

        if ($request->filled('condition_id')) {
            $query->where('it2.condition_id', (int) $request->input('condition_id'));
        }

        $columns = [
            'inventory_number' => 'im.inventory_number', 'transaction_at' => 'it2.transaction_at',
            'description' => 'im.description', 'serial_number' => 'im.serial_number', 'type' => 'im.type',
            'category_name' => 'ic2.name', 'sub_category_name' => 'isc.name', 'remark' => 'it2.remark',
            'condition_name' => 'ic.name',
        ];

        return DataTables::make($request, $query)
            ->searchable(array_merge(array_values($columns), ['im.name']))
            ->orderable($columns)
            ->defaultOrder('it2.transaction_at', 'desc')
            ->map(fn ($r) => [
                'inventory_number' => e($r->inventory_number),
                'name' => e((string) $r->name),
                'transaction_at' => $r->transaction_at ? Carbon::parse($r->transaction_at)->format('d M Y') : '',
                'description' => e((string) $r->description),
                'description_short' => e(Str::limit((string) $r->description, 34, ' ..')),
                'serial_number' => e((string) $r->serial_number),
                'type' => e((string) $r->type),
                'category_name' => e((string) $r->category_name),
                'sub_category_name' => e((string) $r->sub_category_name),
                'remark' => e((string) $r->remark),
                'condition_name' => e((string) $r->condition_name),
                'condition_color' => ConditionStyle::color($r->condition_id),
                'url' => route('item_master.read', ['id' => $r->inventory_number]),
                // data untuk modal Transfer (nilai mentah, dipakai lewat .val()/.text())
                'transfer' => $canEdit ? [
                    'inventory_number' => $r->inventory_number,
                    'name' => (string) $r->name,
                    'description' => (string) $r->description,
                    'condition_id' => $r->condition_id,
                    'purpose' => (string) $r->purpose,
                    'remark' => (string) $r->remark,
                ] : null,
            ])
            ->response();
    }

    // ------------------------------------------------------------------ Transfer

    /** Transfer satu aset ke staff lain (pengganti staff/item_mutation): menambah baris riwayat baru. */
    public function itemMutation(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $data = $request->validate([
            'inventory_number' => ['required', 'string', 'max:25'],
            'transaction_at' => ['required', 'date'],
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

        $exists = InventoryQuery::base($this->locationId())->where('im.inventory_number', $data['inventory_number'])->exists();
        if (! $exists) {
            return response()->json(['message' => 'Item not found in the selected location.'], Response::HTTP_NOT_FOUND);
        }

        (new ItemTransaction())->forceFill([
            'inventory_number' => $data['inventory_number'],
            'location_id' => $data['location_id'],
            'department_id' => $data['department_id'],
            'staff_id' => $data['staff_id'],
            'purpose' => $data['purpose'] ?? null,
            'condition_id' => $data['condition_id'],
            'remark' => $data['remark'] ?? null,
            'status' => 1,
            'transaction_at' => Carbon::parse($data['transaction_at'])->format('Y-m-d H:i:s'),
            'transaction_by' => Auth::id(),
            'created_at' => now(),
            'created_by' => Auth::id(),
        ])->save();

        session()->flash('success', $data['inventory_number'].' has been transferred.');

        return response()->json(['message' => 'success']);
    }

    /** Transfer semua aset yang dipegang staff ini ke staff lain (pengganti staff/transfer_all). */
    public function transferAll(Request $request): JsonResponse
    {
        $this->authorizeEdit();

        $source = $this->findStaff($request->input('id'));
        if (! $source) {
            return response()->json(['message' => 'Staff not found in the selected location.'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->validate([
            'staff_id_transfer' => ['required', 'integer', Rule::exists('staff', 'id'), Rule::notIn([$source->id])],
            'department_id_transfer' => ['required', 'integer', Rule::exists('department', 'id')],
            'location_id_transfer' => ['required', 'integer', Rule::exists('location', 'id')],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'remark' => ['nullable', 'string', 'max:20000'],
        ], [
            'staff_id_transfer.not_in' => 'Choose a different person than the current holder.',
        ], [
            'staff_id_transfer' => 'responsible person', 'department_id_transfer' => 'department',
            'location_id_transfer' => 'location',
        ]);

        $items = InventoryQuery::base($this->locationId())
            ->where('it2.staff_id', $source->id)
            ->get(['im.inventory_number', 'it2.condition_id']);

        if ($items->isEmpty()) {
            return response()->json(['message' => $source->name.' does not hold any items.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $now = now()->format('Y-m-d H:i:s');
        $rows = $items->map(fn ($item) => [
            'inventory_number' => $item->inventory_number,
            'location_id' => $data['location_id_transfer'],
            'department_id' => $data['department_id_transfer'],
            'staff_id' => $data['staff_id_transfer'],
            'purpose' => $data['purpose'] ?? null,
            'condition_id' => $item->condition_id, // kondisi tetap sama, seperti CI3
            'remark' => $data['remark'] ?? null,
            'status' => 1,
            'transaction_at' => $now,
            'transaction_by' => Auth::id(),
            'created_at' => $now,
            'created_by' => Auth::id(),
        ])->all();

        DB::transaction(fn () => DB::table('item_transaction')->insert($rows));

        session()->flash('success', count($rows).' '.Str::plural('item', count($rows)).' transferred from '.$source->name.'.');

        return response()->json(['message' => 'success', 'count' => count($rows)]);
    }

    // ------------------------------------------------------------------ Helpers

    private function validateStaff(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'department_id' => ['required', 'integer', Rule::exists('department', 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists('location', 'id')],
            'file' => ['nullable', 'string', 'regex:'.self::FILE_PATTERN],
        ], [], ['department_id' => 'department', 'location_id' => 'location', 'file' => 'photo']);
    }
}
