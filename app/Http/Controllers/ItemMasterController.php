<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ItemCondition;
use App\Models\ItemFund;
use App\Models\ItemImage;
use App\Models\ItemManufacture;
use App\Models\ItemMaster;
use App\Models\ItemQty;
use App\Models\ItemReceipt;
use App\Models\ItemSubCategory;
use App\Models\ItemSupplier;
use App\Models\Location;
use App\Models\LocationDetail;
use App\Models\User;
use App\Services\LocationContext;
use App\Support\ConditionStyle;
use App\Support\DataTables;
use App\Support\FormOptions;
use App\Support\InventoryExcel;
use App\Support\InventoryNumber;
use App\Support\InventoryQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengganti controller CI3 Item_master (URL tetap /item_master, nomor aset lewat ?id=).
 *
 * Hak akses (sama dengan tombol di CI3, sekarang juga dicek di server):
 *   Super Admin: lihat, tambah, ubah, hapus | Admin: lihat, tambah, ubah | Staff: lihat & Excel.
 * Data yang tampil hanya aset di negara yang dipilih di sidebar (sama dengan CI3).
 */
class ItemMasterController extends Controller
{
    /** Folder upload (sama dengan CI3: assets/item-images). */
    public const IMAGE_DIR = 'assets/item-images';
    public const RECEIPT_DIR = 'assets/item-receipts';

    private const FILE_PATTERN_IMAGE = '/^[A-Za-z0-9]{20,64}\.(jpe?g|png|gif|webp)$/i';
    private const FILE_PATTERN_RECEIPT = '/^[A-Za-z0-9]{20,64}\.(pdf|jpe?g|png|webp)$/i';

    /** Kolom tabel list: data DataTables => kolom database (urut & cari). */
    private const LIST_COLUMNS = [
        'inventory_number' => 'im.inventory_number',
        'name' => 'im.name',
        'description' => 'im.description',
        'type' => 'im.type',
        'fund_name' => 'fund.name',
        'category_name' => 'ic2.name',
        'sub_category_name' => 'isc.name',
        'location_name' => 'l.name',
        'department_name' => 'd.name',
        'staff_name' => 'staff.name',
        'purpose' => 'it2.purpose',
        'condition_name' => 'ic.name',
        'remark' => 'it2.remark',
    ];

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
            'Only Super Admin and Admin can change inventory data.');
    }

    /** Satu aset di negara aktif, lengkap dengan nama relasi (pengganti get_by_id CI3). */
    private function findRow(?string $id): ?object
    {
        if (blank($id)) {
            return null;
        }

        return InventoryQuery::base($this->locationId())
            ->where('im.inventory_number', $id)
            ->select([
                'im.*',
                'fund.name as fund_name', 'manu.name as manufacture_name', 'sup.name as supplier_name',
                'ic2.name as category_name', 'isc.name as sub_category_name', 'qty.qty',
                'loc.id as id_location', 'loc.name as location_name', 'ldet.name as location_detail_name',
                'it2.condition_id', 'ic.name as condition_name',
                'staff.id as staff_id', 'staff.name as staff_name', 'd.name as department_name',
            ])
            ->first();
    }

    private function notFound(): RedirectResponse
    {
        return redirect()->route('item_master.index')
            ->with('warning', 'Record not found in the selected location.');
    }

    // ------------------------------------------------------------------ List

    public function index(): View
    {
        $locationId = $this->locationId();

        return view('inventory.index', [
            'categories' => ItemCategory::where('status', 1)->orderBy('name')->pluck('name', 'id'),
            'conditions' => ItemCondition::where('status', 1)->orderBy('id')->pluck('name', 'id'),
            'total' => InventoryQuery::base($locationId)->count(),
            'locationLabel' => LocationContext::label((string) Location::whereKey($locationId)->value('name')),
            'canEdit' => $this->user()->canEditInventory(),
            'canDelete' => $this->user()->canDeleteInventory(),
        ]);
    }

    /** Data tabel (DataTables server-side), pengganti get_all_with_detail() CI3. */
    public function json(Request $request): JsonResponse
    {
        $canEdit = $this->user()->canEditInventory();
        $canDelete = $this->user()->canDeleteInventory();

        $query = InventoryQuery::base($this->locationId())->select([
            'im.inventory_number', 'im.name', 'im.description', 'im.type',
            'fund.name as fund_name', 'ic2.name as category_name', 'isc.name as sub_category_name',
            'l.name as location_name', 'd.name as department_name',
            'staff.id as staff_id', 'staff.name as staff_name',
            'it2.purpose', 'it2.condition_id', 'ic.name as condition_name', 'it2.remark',
        ]);

        // Filter tambahan di atas tabel
        if ($request->filled('category_id')) {
            $query->where('im.category_id', (int) $request->input('category_id'));
        }
        if ($request->filled('condition_id')) {
            $request->input('condition_id') === 'none'
                ? $query->whereNull('it2.id')
                : $query->where('it2.condition_id', (int) $request->input('condition_id'));
        }

        return DataTables::make($request, $query)
            ->searchable(array_merge(array_values(self::LIST_COLUMNS), ['im.serial_number']))
            ->orderable(self::LIST_COLUMNS)
            ->defaultOrder('im.inventory_number', 'desc')
            ->map(function ($row) use ($canEdit, $canDelete) {
                $data = [];
                foreach (array_keys(self::LIST_COLUMNS) as $key) {
                    $data[$key] = e((string) ($row->$key ?? '')); // escape: cegah XSS
                }
                $data['description_short'] = e(Str::limit((string) $row->description, 34, ' ..'));
                $data['staff_id'] = $row->staff_id;
                $data['condition_color'] = ConditionStyle::color($row->condition_id);
                $data['urls'] = [
                    'read' => route('item_master.read', ['id' => $row->inventory_number]),
                    'edit' => $canEdit ? route('item_master.edit', ['id' => $row->inventory_number]) : null,
                    'staff' => $row->staff_id ? url('staff/read?id='.$row->staff_id) : null,
                ];
                $data['can_delete'] = $canDelete;

                return $data;
            })
            ->response();
    }

    // ------------------------------------------------------------------ Detail

    public function read(Request $request): View|RedirectResponse
    {
        $id = (string) $request->query('id');
        $item = $this->findRow($id);
        if (! $item) {
            return $this->notFound();
        }

        $history = DB::table('item_transaction as t')
            ->leftJoin('staff as s', 's.id', '=', 't.staff_id')
            ->leftJoin('department as d', 'd.id', '=', 't.department_id')
            ->leftJoin('location as l', 'l.id', '=', 't.location_id')
            ->leftJoin('item_condition as c', 'c.id', '=', 't.condition_id')
            ->where('t.inventory_number', $id)
            ->where('t.status', 1)
            ->orderByDesc('t.transaction_at')
            ->orderByDesc('t.id')
            ->get([
                't.id', 't.transaction_at', 't.staff_id', 't.department_id', 't.location_id',
                's.name as staff_name', 'd.name as department_name',
                'l.name as location_name', 't.purpose', 't.condition_id', 'c.name as condition_name', 't.remark',
            ]);

        $maintenance = DB::table('item_maintenance as m')
            ->leftJoin('ref_services as rs', 'rs.id_services', '=', 'm.id_services')
            ->leftJoin('ref_frequency as rf', 'rf.id_frequency', '=', 'm.id_frequency')
            ->leftJoin('staff as s', 's.id', '=', 'm.id_staff')
            ->where('m.inventory_number', $id)
            ->where('m.status', 1) // sama dengan json2() CI3
            ->orderByDesc('m.last_service_date')
            ->orderByDesc('m.id_maintenance')
            ->get([
                'm.id_maintenance', 'm.last_service_date', 'm.id_services', 'm.id_frequency',
                'rs.services as services_type', 'm.provider_contact',
                'm.id_staff as staff_id', 's.name as staff_name', 'm.service_desc', 'rf.frequency', 'm.service_schedule',
            ]);

        $canEdit = $this->user()->canEditInventory();

        return view('inventory.show', [
            'item' => $item,
            'options' => $canEdit ? $this->historyOptions() : null,
            'images' => ItemImage::where('inventory_number', $id)->pluck('image'),
            'receipts' => ItemReceipt::where('inventory_number', $id)->pluck('image'),
            'history' => $history,
            'maintenance' => $maintenance,
            'createdBy' => $item->created_by ? User::whereKey($item->created_by)->value('full_name') : null,
            'canEdit' => $canEdit,
            'canDelete' => $this->user()->canDeleteInventory(),
            'backUrl' => $this->backUrl(),
        ]);
    }

    /** Pilihan dropdown untuk form History & Maintenance di halaman detail. */
    private function historyOptions(): array
    {
        return [
            'staff' => FormOptions::staffGrouped($this->locationId()),
            'departments' => FormOptions::departments(),
            'locations' => FormOptions::locations(),
            'conditions' => FormOptions::conditions(),
            'services' => DB::table('ref_services')->whereRaw('COALESCE(status, 1) = 1')->orderBy('id_services')->pluck('services', 'id_services'),
            'frequencies' => DB::table('ref_frequency')->whereRaw('COALESCE(status, 1) = 1')->orderBy('months')->pluck('frequency', 'id_frequency'),
        ];
    }

    /**
     * Tujuan tombol Back di halaman detail: halaman sebelumnya (mis. Dashboard atau profil Staff),
     * KECUALI bila sebelumnya halaman detail/form aset sendiri. Tanpa pengecualian ini
     * Back -> Edit -> Back -> Detail -> Back -> Edit ... berputar terus.
     */
    private function backUrl(): string
    {
        $previous = url()->previous();
        $path = trim((string) parse_url($previous, PHP_URL_PATH), '/');
        $isSameApp = str_starts_with($previous, url('/'));
        $isItemPage = (bool) preg_match('#(^|/)item_master/(read|update|create|create2)$#', $path);

        return $isSameApp && ! $isItemPage && $previous !== url()->current()
            ? $previous
            : route('item_master.index');
    }

    // ------------------------------------------------------------------ Create / Update

    public function create(Request $request): View
    {
        $this->authorizeEdit();

        return view('inventory.form', $this->formData(null, $request->query('category_id')) + [
            'button' => 'Create',
            'action' => route('item_master.store'),
        ]);
    }

    /** URL lama CI3 /item_master/create2?cat_id=… (dulu dipakai saat ganti kategori). */
    public function createLegacy(Request $request): RedirectResponse
    {
        return redirect()->route('item_master.create', array_filter(['category_id' => $request->query('cat_id')]));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeEdit();
        $data = $this->validateItem($request, null);
        $number = $data['inventory_number'];

        DB::transaction(function () use ($data, $number) {
            $item = new ItemMaster();
            $item->forceFill($this->itemAttributes($data) + [
                'inventory_number' => $number,
                'status' => 1,
                'created_at' => now(),
                'created_by' => Auth::id(),
            ])->save();

            $this->syncFiles($number, $data);

            ItemQty::create(['inventory_number' => $number, 'qty' => $data['qty'] ?? null]);
        });

        return redirect()->route('item_master.read', ['id' => $number])
            ->with('success', 'Item '.$number.' has been created.');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        $this->authorizeEdit();
        $item = $this->findRow((string) $request->query('id'));
        if (! $item) {
            return $this->notFound();
        }

        return view('inventory.form', $this->formData($item) + [
            'button' => 'Update',
            'action' => route('item_master.update'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeEdit();
        $number = (string) $request->input('inventory_number');
        if (! $this->findRow($number)) {
            return $this->notFound();
        }

        $data = $this->validateItem($request, $number);

        DB::transaction(function () use ($data, $number) {
            ItemMaster::whereKey($number)->where('status', 1)->firstOrFail()
                ->forceFill($this->itemAttributes($data))
                ->save();

            $this->syncFiles($number, $data);

            // Sama dengan updateqty() CI3: ubah bila ada, tambah bila belum
            $qty = ItemQty::where('inventory_number', $number);
            $qty->exists()
                ? $qty->update(['qty' => $data['qty'] ?? null])
                : ItemQty::create(['inventory_number' => $number, 'qty' => $data['qty'] ?? null]);
        });

        return redirect()->route('item_master.read', ['id' => $number])
            ->with('success', 'Item '.$number.' has been updated.');
    }

    /** Soft delete (status = 0), hanya Super Admin. */
    public function destroy(Request $request): RedirectResponse
    {
        abort_unless($this->user()->canDeleteInventory(), Response::HTTP_FORBIDDEN, 'Only Super Admin can delete items.');

        $number = (string) $request->input('id');
        if (! $this->findRow($number)) {
            return $this->notFound();
        }

        ItemMaster::whereKey($number)->update(['status' => 0]);

        return redirect()->route('item_master.index')->with('success', 'Item '.$number.' has been deleted.');
    }

    /** Nomor inventaris berikutnya untuk kategori + negara (AJAX dari form). */
    public function nextNumber(Request $request): JsonResponse
    {
        $this->authorizeEdit();
        $categoryId = (int) $request->query('category_id');
        $locationId = (int) $request->query('id_location') ?: $this->locationId();

        return response()->json([
            'number' => $categoryId ? InventoryNumber::next($categoryId, $locationId) : null,
        ]);
    }

    // ------------------------------------------------------------------ Upload

    /** Upload foto (AJAX), pengganti upload() CI3. File disimpan saat form disimpan. */
    public function upload(Request $request): JsonResponse
    {
        return $this->storeUpload($request, 'image', ['jpg', 'jpeg', 'png', 'gif', 'webp'], 5120, self::IMAGE_DIR);
    }

    /** Upload struk/kuitansi (AJAX). Di CI3 field Receipt ada tetapi tidak pernah tersimpan. */
    public function uploadReceipt(Request $request): JsonResponse
    {
        return $this->storeUpload($request, 'receipt', ['pdf', 'jpg', 'jpeg', 'png', 'webp'], 10240, self::RECEIPT_DIR);
    }

    private function storeUpload(Request $request, string $field, array $mimes, int $maxKb, string $dir): JsonResponse
    {
        $this->authorizeEdit();

        $validator = Validator::make($request->all(), [
            $field => ['required', 'file', 'mimes:'.implode(',', $mimes), 'max:'.$maxKb],
        ], [], [$field => $field === 'image' ? 'photo' : 'receipt']);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first($field)], 422);
        }

        $file = $request->file($field);
        $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension());
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $name = Str::random(32).'.'.$extension; // nama acak seperti encrypt_name di CI3

        File::ensureDirectoryExists(public_path($dir));
        $file->move(public_path($dir), $name);

        return response()->json([
            'error' => false,
            'file' => $name,
            'url' => asset($dir.'/'.$name),
            'original' => Str::limit($file->getClientOriginalName(), 60),
        ]);
    }

    // ------------------------------------------------------------------ Excel

    public function excel()
    {
        $locationId = $this->locationId();

        return InventoryExcel::download($locationId, (string) Location::whereKey($locationId)->value('name'));
    }

    // ------------------------------------------------------------------ Helpers

    private function validateItem(Request $request, ?string $existingNumber): array
    {
        $rules = [
            'category_id' => ['required', 'integer', Rule::exists('item_category', 'id')],
            'sub_category_id' => ['nullable', 'integer', Rule::exists('item_sub_category', 'id')],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:200'],
            'serial_number' => ['nullable', 'string', 'max:5000'],
            'purchase_date' => ['nullable', 'date_format:Y-m-d'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(ItemMaster::CURRENCIES)],
            'fund_id' => ['nullable', 'integer', Rule::exists('item_fund', 'id')],
            'manufacture_id' => ['nullable', 'integer', Rule::exists('item_manufacture', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('item_supplier', 'id')],
            'id_location' => ['required', 'integer', Rule::exists('location', 'id')],
            'location_det_id' => ['required', 'integer',
                Rule::exists('location_detail', 'id')->where('id_location', (int) $request->input('id_location'))],
            'scheduled' => ['nullable', Rule::in(['YES', 'NO'])],
            'qty' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'image_upload' => ['nullable', 'array', 'max:30'],
            'image_upload.*' => ['string', 'regex:'.self::FILE_PATTERN_IMAGE],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['string', 'max:100'],
            'receipt_upload' => ['nullable', 'array', 'max:30'],
            'receipt_upload.*' => ['string', 'regex:'.self::FILE_PATTERN_RECEIPT],
            'remove_receipts' => ['nullable', 'array'],
            'remove_receipts.*' => ['string', 'max:100'],
        ];

        if ($existingNumber === null) {
            // max 20: kolom inventory_number di item_image & item_receipt hanya 20 karakter
            $rules['inventory_number'] = ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\/\-_.]+$/',
                Rule::unique('item_master', 'inventory_number')];
        }

        return $request->validate($rules, [
            'inventory_number.unique' => 'This inventory number is already used. Click the refresh button to get the next free number.',
            'inventory_number.regex' => 'Use only letters, numbers, "/", "-", "_" or ".".',
            'location_det_id.exists' => 'Choose a location detail that belongs to the selected location.',
        ], [
            'category_id' => 'category', 'sub_category_id' => 'sub category', 'fund_id' => 'fund',
            'manufacture_id' => 'manufacture', 'supplier_id' => 'supplier', 'id_location' => 'location',
            'location_det_id' => 'location detail', 'qty' => 'quantity', 'type' => 'type/model',
        ]);
    }

    /** Kolom item_master yang diisi dari form (inventory_number tidak bisa diubah). */
    private function itemAttributes(array $data): array
    {
        $keys = [
            'description', 'name', 'type', 'serial_number', 'category_id', 'sub_category_id',
            'purchase_date', 'purchase_price', 'currency', 'fund_id', 'manufacture_id', 'supplier_id',
            'location_det_id', 'scheduled',
        ];

        return collect($keys)->mapWithKeys(fn ($k) => [$k => $data[$k] ?? null])->all();
    }

    /** Simpan foto & struk baru, hapus yang ditandai (baris database saja, file tetap ada seperti CI3). */
    private function syncFiles(string $number, array $data): void
    {
        $sets = [
            [ItemImage::class, self::IMAGE_DIR, $data['image_upload'] ?? [], $data['remove_images'] ?? []],
            [ItemReceipt::class, self::RECEIPT_DIR, $data['receipt_upload'] ?? [], $data['remove_receipts'] ?? []],
        ];

        foreach ($sets as [$model, $dir, $added, $removed]) {
            if ($removed) {
                $model::where('inventory_number', $number)->whereIn('image', $removed)->delete();
            }

            $existing = $model::where('inventory_number', $number)->pluck('image')->all();
            foreach (array_unique($added) as $file) {
                if (! in_array($file, $existing, true) && ! in_array($file, $removed, true)
                    && is_file(public_path($dir.'/'.$file))) {
                    $model::create(['inventory_number' => $number, 'image' => $file]);
                }
            }
        }
    }

    /** Data dropdown form. $item = baris dari findRow() saat edit. */
    private function formData(?object $item, mixed $categoryFromUrl = null): array
    {
        $categoryId = old('category_id', $item->category_id ?? $categoryFromUrl);
        $locationId = old('id_location', $item->id_location ?? $this->locationId());

        $active = fn (string $model) => $model::where('status', 1)->orderBy('name')->pluck('name', 'id');

        return [
            'item' => $item,
            'categories' => $active(ItemCategory::class),
            'subCategories' => $categoryId
                ? ItemSubCategory::where('category_id', $categoryId)->where('status', 1)->orderBy('name')->pluck('name', 'id')
                : collect(),
            'funds' => $active(ItemFund::class),
            'manufactures' => $active(ItemManufacture::class),
            'suppliers' => $active(ItemSupplier::class),
            'locations' => Location::where('status', 1)->orderBy('id')->pluck('name', 'id'),
            'locationDetails' => $locationId
                ? LocationDetail::where('id_location', $locationId)->where('status', 1)->orderBy('id')->pluck('name', 'id')
                : collect(),
            'selectedCategory' => $categoryId,
            'selectedLocation' => $locationId,
            'defaultCurrency' => InventoryNumber::CURRENCY[(int) $locationId] ?? 'AUD',
            'images' => $item ? ItemImage::where('inventory_number', $item->inventory_number)->pluck('image') : collect(),
            'receipts' => $item ? ItemReceipt::where('inventory_number', $item->inventory_number)->pluck('image') : collect(),
        ];
    }
}
