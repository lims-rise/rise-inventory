<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Respons server-side untuk DataTables 1.10 (pengganti library Datatables CI3 / Ignited Datatables).
 *
 * DataTables::make($request, $query)
 *     ->searchable(['name'])                      // kolom yang dicari lewat kotak Search
 *     ->orderable(['name' => 'name', 'id' => 'id']) // data kolom di JS => kolom database
 *     ->defaultOrder('id', 'desc')
 *     ->map(fn ($row) => [...])                   // bentuk tiap baris (nilai teks WAJIB di-escape)
 *     ->response();
 */
class DataTables
{
    private array $searchable = [];
    private array $orderable = [];
    private array $defaultOrder = ['id', 'desc'];
    private $mapper;

    public function __construct(private Request $request, private EloquentBuilder|QueryBuilder $query)
    {
        $this->mapper = fn ($row) => (array) $row;
    }

    public static function make(Request $request, EloquentBuilder|QueryBuilder $query): self
    {
        return new self($request, $query);
    }

    public function searchable(array $columns): self
    {
        $this->searchable = $columns;

        return $this;
    }

    public function orderable(array $map): self
    {
        $this->orderable = $map;

        return $this;
    }

    public function defaultOrder(string $column, string $direction = 'desc'): self
    {
        $this->defaultOrder = [$column, $direction];

        return $this;
    }

    public function map(callable $mapper): self
    {
        $this->mapper = $mapper;

        return $this;
    }

    public function response(): JsonResponse
    {
        $query = $this->query;
        $total = (clone $query)->count();

        // Pencarian
        $term = trim((string) $this->request->input('search.value', ''));
        if ($term !== '' && $this->searchable) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function ($q) use ($like) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', $like);
                }
            });
        }
        $filtered = $term !== '' ? (clone $query)->count() : $total;

        // Urutan: hanya kolom yang diizinkan (mencegah SQL injection lewat nama kolom)
        $index = $this->request->input('order.0.column');
        $dataKey = $index !== null ? $this->request->input("columns.$index.data") : null;
        $direction = strtolower((string) $this->request->input('order.0.dir')) === 'asc' ? 'asc' : 'desc';

        if ($dataKey !== null && isset($this->orderable[$dataKey])) {
            $query->orderBy($this->orderable[$dataKey], $direction);
        } else {
            $query->orderBy($this->defaultOrder[0], $this->defaultOrder[1]);
        }

        // Halaman (length -1 = semua, dibatasi 1000 baris)
        $start = max(0, (int) $this->request->input('start', 0));
        $length = (int) $this->request->input('length', 10);
        $length = $length < 0 ? 1000 : min($length, 1000);

        $rows = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => (int) $this->request->input('draw', 0),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map($this->mapper)->values(),
        ]);
    }
}
