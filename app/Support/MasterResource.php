<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Definisi satu master data (Category, Department, Supplier, ...).
 * Dipakai MasterController dan view resources/views/master/*.
 *
 * Kunci field yang didukung:
 *   'label'       Label di form & halaman detail       ('Department Name')
 *   'list_label'  Judul kolom di tabel & Excel          ('Name'); default = label
 *   'type'        text | textarea | select
 *   'rules'       Aturan validasi Laravel
 *   'options'     select: Closure/array id => label untuk dropdown form
 *   'display'     Closure(Model $row): string, nilai tampilan (mis. nama dari tabel join)
 *   'search'      Kolom database untuk pencarian tabel  ('item_category.name')
 *   'sort'        Kolom database untuk pengurutan tabel
 *   'list'        false = tidak tampil di tabel/Excel
 *   'help'        Teks bantuan kecil di bawah input form
 *   'default'     Nilai awal saat create (nilai atau Closure)
 */
class MasterResource
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, array>  $fields      Urutan = urutan kolom tabel
     * @param  array<int, string>  $formOrder    Urutan field di form (kosong = sama dengan $fields)
     * @param  array<int|string, string>  $locked  id yang tidak boleh dihapus => alasan
     * @param  Closure|null  $guard  fn(Model $row): ?string, alasan bila baris tidak boleh dihapus
     */
    public function __construct(
        public string $model,
        public string $prefix,
        public string $title,
        public array $fields,
        public string $icon = 'bi-collection',
        public string $group = 'Master',
        public ?Closure $scope = null,
        public bool $touchOnUpdate = true,   // update juga mengisi ulang created_at & created_by (perilaku CI3 per modul)
        public array $formOrder = [],
        public array $locked = [],
        public bool $audit = true,           // tabel punya kolom created_at & created_by
        public ?Closure $guard = null,
    ) {
        foreach ($this->fields as $key => $field) {
            $label = $field['label'] ?? ucfirst(str_replace('_', ' ', $key));
            $this->fields[$key] = $field + [
                'label' => $label,
                'list_label' => $label,
                'type' => 'text',
                'rules' => ['nullable'],
                'list' => true,
                'placeholder' => $label,
            ];
        }
    }

    public function route(string $action, mixed $params = []): string
    {
        return route($this->prefix.'.'.$action, $params);
    }

    public function listFields(): array
    {
        return array_filter($this->fields, fn ($f) => $f['list']);
    }

    public function formFields(): array
    {
        if (! $this->formOrder) {
            return $this->fields;
        }

        return array_replace(array_flip($this->formOrder), $this->fields);
    }

    public function rules(): array
    {
        return array_map(fn ($f) => $f['rules'], $this->fields);
    }

    public function labels(): array
    {
        return array_map(fn ($f) => $f['label'], $this->fields);
    }

    /** Pilihan untuk field select (id => label). */
    public function options(string $key): array
    {
        $options = $this->fields[$key]['options'] ?? [];

        return collect($options instanceof Closure ? $options() : $options)->all();
    }

    /** Nilai tampilan sebuah field. */
    public function display(Model $row, string $key): string
    {
        $field = $this->fields[$key];

        if (($field['display'] ?? null) instanceof Closure) {
            return (string) $field['display']($row);
        }

        $value = $row->getAttribute($key);

        if ($field['type'] === 'select') {
            static $cache = [];
            $cache[$this->prefix.'.'.$key] ??= $this->options($key);

            return (string) ($cache[$this->prefix.'.'.$key][$value] ?? '');
        }

        return (string) $value;
    }

    public function lockReason(int|string $id): ?string
    {
        return $this->locked[$id] ?? null;
    }

    /** Alasan baris tidak boleh dihapus (id terkunci atau aturan guard), null = boleh. */
    public function deleteBlockedReason(Model $row): ?string
    {
        return $this->lockReason($row->getKey()) ?? ($this->guard ? ($this->guard)($row) : null);
    }

    /** Nilai awal field saat create. */
    public function defaultValue(string $key): mixed
    {
        $default = $this->fields[$key]['default'] ?? null;

        return $default instanceof Closure ? $default() : $default;
    }
}
