<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DataTables;
use App\Support\MasterResource;
use App\Support\SimpleXlsx;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CRUD master data, pengganti controller hasil Harviacode CRUD Generator di CI3.
 * Perilaku dipertahankan:
 *  - Hanya baris status = 1 yang tampil; "hapus" = mengubah status menjadi 0 (soft delete).
 *  - Insert mengisi created_at & created_by; update juga memperbarui created_at & created_by
 *    (sama seperti CI3, kolom ini dipakai sebagai "terakhir diubah").
 *  - URL sama: /prefix, /prefix/read/{id}, /prefix/create, /prefix/update/{id}, /prefix/excel.
 */
abstract class MasterController extends Controller
{
    abstract protected function resource(): MasterResource;

    /** Baris aktif (status = 1). */
    protected function query(): Builder
    {
        $resource = $this->resource();
        $query = ($resource->model)::query()->where('status', 1);

        if ($resource->scope) {
            ($resource->scope)($query);
        }

        return $query;
    }

    protected function findOrBack(int|string $id): Model|RedirectResponse
    {
        return $this->query()->whereKey($id)->first()
            ?? redirect($this->resource()->route('index'))->with('warning', 'Record not found.');
    }

    public function index(): View
    {
        return view('master.index', [
            'resource' => $this->resource(),
            'total' => $this->query()->count(),
        ]);
    }

    /** Data tabel (DataTables server-side, POST seperti CI3). */
    public function json(Request $request): JsonResponse
    {
        $resource = $this->resource();
        $listFields = $resource->listFields();
        $textFields = array_keys(array_filter($listFields, fn ($f) => $f['type'] !== 'select'));

        return DataTables::make($request, $this->query())
            ->searchable($textFields)
            ->orderable(array_combine(array_keys($listFields), array_keys($listFields)) + ['id' => 'id'])
            ->defaultOrder((new $resource->model)->getKeyName(), 'desc')
            ->map(function (Model $row) use ($resource, $listFields) {
                $data = ['id' => $row->getKey()];
                foreach (array_keys($listFields) as $key) {
                    $data[$key] = e($resource->display($row, $key)); // escape: cegah XSS di tabel
                }
                $data['urls'] = [
                    'read' => $resource->route('read', $row->getKey()),
                    'edit' => $resource->route('edit', $row->getKey()),
                    'delete' => $resource->route('destroy', $row->getKey()),
                ];

                return $data;
            })
            ->response();
    }

    public function read(int $id): View|RedirectResponse
    {
        $row = $this->findOrBack($id);
        if ($row instanceof RedirectResponse) {
            return $row;
        }

        return view('master.show', [
            'resource' => $this->resource(),
            'row' => $row,
            'createdBy' => $row->created_by ? User::whereKey($row->created_by)->value('full_name') : null,
        ]);
    }

    public function create(): View
    {
        return view('master.form', [
            'resource' => $this->resource(),
            'row' => null,
            'button' => 'Create',
            'action' => $this->resource()->route('store'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = $this->resource();
        $data = $request->validate($resource->rules(), [], $resource->labels());

        $row = new $resource->model;
        $row->forceFill($data + [
            'status' => 1,
            'created_at' => now(),
            'created_by' => Auth::id(),
        ])->save();

        return redirect($resource->route('index'))
            ->with('success', $resource->title.' "'.($data['name'] ?? $row->getKey()).'" has been created.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $row = $this->findOrBack($id);
        if ($row instanceof RedirectResponse) {
            return $row;
        }

        return view('master.form', [
            'resource' => $this->resource(),
            'row' => $row,
            'button' => 'Update',
            'action' => $this->resource()->route('update'),
        ]);
    }

    /** id dikirim lewat hidden input, sama seperti update_action di CI3. */
    public function update(Request $request): RedirectResponse
    {
        $resource = $this->resource();
        $row = $this->findOrBack((int) $request->input('id'));
        if ($row instanceof RedirectResponse) {
            return $row;
        }

        $data = $request->validate($resource->rules(), [], $resource->labels());

        $row->forceFill($data + [
            'created_at' => now(),       // perilaku CI3: dipakai sebagai "terakhir diubah"
            'created_by' => Auth::id(),
        ])->save();

        return redirect($resource->route('index'))
            ->with('success', $resource->title.' "'.($data['name'] ?? $row->getKey()).'" has been updated.');
    }

    /** Soft delete: status = 0 (data tetap ada di database, seperti CI3). */
    public function destroy(int $id): RedirectResponse
    {
        $resource = $this->resource();
        $row = $this->findOrBack($id);
        if ($row instanceof RedirectResponse) {
            return $row;
        }

        $row->forceFill(['status' => 0])->save();

        return redirect($resource->route('index'))
            ->with('success', $resource->title.' "'.($row->name ?? $row->getKey()).'" has been deleted.');
    }

    public function excel(): BinaryFileResponse
    {
        $resource = $this->resource();
        $listFields = $resource->listFields();
        $keyName = (new $resource->model)->getKeyName();

        $rows = [];
        $no = 1;
        foreach ($this->query()->orderByDesc($keyName)->cursor() as $row) {
            $line = [$no++];
            foreach (array_keys($listFields) as $key) {
                $line[] = $resource->display($row, $key);
            }
            $rows[] = $line;
        }

        return SimpleXlsx::make(
            $resource->title,
            array_merge(['No'], array_column($listFields, 'label')),
            $rows,
        )->download($resource->prefix.'_'.now()->format('Ymd_His').'.xlsx');
    }
}
