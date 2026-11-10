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
 *  - Hanya baris status = 1 yang tampil; "hapus" = status menjadi 0 (soft delete).
 *  - Insert mengisi status, created_at & created_by (bila tabelnya punya kolom itu: MasterResource::$audit).
 *  - Update mengisi ulang created_at & created_by HANYA bila modul CI3-nya begitu
 *    (MasterResource::$touchOnUpdate; Category/Sub Category/Manufacture ya, Department/Condition tidak).
 *  - URL sama: /prefix, /prefix/read/{id}, /prefix/create, /prefix/update/{id}, /prefix/excel.
 */
abstract class MasterController extends Controller
{
    abstract protected function resource(): MasterResource;

    protected function model(): Model
    {
        return new ($this->resource()->model);
    }

    /** Baris aktif (status = 1). Kolom ditulis lengkap (tabel.kolom) supaya aman saat ada join. */
    protected function query(): Builder
    {
        $resource = $this->resource();
        $model = $this->model();
        $query = $model->newQuery()
            ->select($model->qualifyColumn('*'))
            ->where($model->qualifyColumn('status'), 1);

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
        $model = $this->model();
        $listFields = $resource->listFields();

        $searchable = [];
        $orderable = ['id' => $model->getQualifiedKeyName()];
        foreach ($listFields as $key => $field) {
            $column = $field['type'] === 'select' ? null : $model->qualifyColumn($key);
            if ($search = $field['search'] ?? $column) {
                $searchable[] = $search;
            }
            $orderable[$key] = $field['sort'] ?? $model->qualifyColumn($key);
        }

        return DataTables::make($request, $this->query())
            ->searchable($searchable)
            ->orderable($orderable)
            ->defaultOrder($model->getQualifiedKeyName(), 'desc')
            ->map(function (Model $row) use ($resource, $listFields) {
                $data = ['id' => $row->getKey()];
                foreach (array_keys($listFields) as $key) {
                    $data[$key] = e($resource->display($row, $key)); // escape: cegah XSS di tabel
                }
                $data['urls'] = [
                    'read' => $resource->route('read', $row->getKey()),
                    'edit' => $resource->route('edit', $row->getKey()),
                    'delete' => $resource->lockReason($row->getKey()) ? null : $resource->route('destroy', $row->getKey()),
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
            'createdBy' => $this->resource()->audit && $row->created_by
                ? User::whereKey($row->created_by)->value('full_name')
                : null,
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

        $data['status'] = 1;
        if ($resource->audit) {
            $data += ['created_at' => now(), 'created_by' => Auth::id()];
        }

        $row = $this->model();
        $row->forceFill($data)->save();

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

        if ($resource->audit && $resource->touchOnUpdate) {
            $data += ['created_at' => now(), 'created_by' => Auth::id()];
        }

        $row->forceFill($data)->save();

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

        if ($reason = $resource->deleteBlockedReason($row)) {
            return redirect($resource->route('index'))
                ->with('error', $resource->title.' "'.$row->name.'" cannot be deleted: '.$reason);
        }

        $row->forceFill(['status' => 0])->save();

        return redirect($resource->route('index'))
            ->with('success', $resource->title.' "'.($row->name ?? $row->getKey()).'" has been deleted.');
    }

    public function excel(): BinaryFileResponse
    {
        $resource = $this->resource();
        $listFields = $resource->listFields();

        $rows = [];
        $no = 1;
        foreach ($this->query()->orderByDesc($this->model()->getQualifiedKeyName())->cursor() as $row) {
            $line = [$no++];
            foreach (array_keys($listFields) as $key) {
                $line[] = $resource->display($row, $key);
            }
            $rows[] = $line;
        }

        return SimpleXlsx::make(
            $resource->title,
            array_merge(['No'], array_column($listFields, 'list_label')),
            $rows,
        )->download($resource->prefix.'_'.now()->format('Ymd_His').'.xlsx');
    }
}
