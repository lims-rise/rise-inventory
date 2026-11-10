<?php

namespace App\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Definisi satu master data (Category, Department, Supplier, ...).
 * Dipakai MasterController dan view resources/views/master/*.
 *
 * Contoh field:
 *   'name' => ['label' => 'Name', 'rules' => ['required', 'string', 'max:100']]
 *   'category_id' => [
 *       'label' => 'Category', 'type' => 'select', 'rules' => ['required', 'integer'],
 *       'options' => fn () => ItemCategory::where('status', 1)->orderBy('name')->pluck('name', 'id'),
 *   ]
 */
class MasterResource
{
    /**
     * @param  class-string<Model>  $model
     * @param  array<string, array>  $fields
     */
    public function __construct(
        public string $model,
        public string $prefix,        // URL & nama route, sama dengan CI3: 'item_category'
        public string $title,         // 'Category'
        public array $fields,
        public string $icon = 'bi-collection',
        public string $group = 'Master',
        public ?Closure $scope = null, // filter tambahan untuk list (opsional)
    ) {
        foreach ($this->fields as $key => $field) {
            $this->fields[$key] = $field + [
                'label' => ucfirst(str_replace('_', ' ', $key)),
                'type' => 'text',
                'rules' => ['nullable'],
                'list' => true,
                'placeholder' => $field['label'] ?? ucfirst($key),
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

    /** Nilai tampilan sebuah field (label untuk select). */
    public function display(Model $row, string $key): string
    {
        $value = $row->getAttribute($key);

        if (($this->fields[$key]['type'] ?? 'text') === 'select') {
            static $cache = [];
            $cache[$this->prefix.$key] ??= $this->options($key);

            return (string) ($cache[$this->prefix.$key][$value] ?? '');
        }

        return (string) $value;
    }
}
