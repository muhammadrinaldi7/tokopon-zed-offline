<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait WithDynamicReportingFilters
{
    /**
     * Active dynamic filters list.
     * Format: [
     *   [
     *     'id' => 'flt_12345',
     *     'field' => 'proyek',
     *     'operator' => '!=',
     *     'value' => 'BEACUKAI',
     *     'value_end' => null,
     *     'label' => 'Kategori Proyek',
     *     'display_value' => 'BEACUKAI',
     *     'operator_label' => 'Bukan',
     *   ]
     * ]
     */
    public array $dynamicFilters = [];

    /**
     * Modal form state for adding / editing a filter.
     */
    public bool $showFilterModal = false;
    public string $newFilterField = '';
    public string $newFilterOperator = '=';
    public mixed $newFilterValue = '';
    public mixed $newFilterValueEnd = '';

    /**
     * Operator labels dictionary.
     */
    public function getOperatorDefinitions(): array
    {
        return [
            'in' => ['label' => 'Salah satu dari (Pilih opsi / In)', 'types' => ['select']],
            'not_in' => ['label' => 'Kecualikan / Bukan salah satu dari (Not In)', 'types' => ['select']],
            '=' => ['label' => 'Adalah (Sama dengan)', 'types' => ['text', 'select', 'number', 'date']],
            '!=' => ['label' => 'Bukan (Tidak sama)', 'types' => ['text', 'select', 'number', 'date']],
            'like' => ['label' => 'Mengandung kata', 'types' => ['text']],
            'not_like' => ['label' => 'Tidak mengandung', 'types' => ['text']],
            '>=' => ['label' => 'Lebih besar / Minimal (>=)', 'types' => ['number', 'date']],
            '<=' => ['label' => 'Lebih kecil / Maksimal (<=)', 'types' => ['number', 'date']],
            'between' => ['label' => 'Di antara (Rentang)', 'types' => ['number', 'date']],
            'empty' => ['label' => 'Kosong / Belum diisi', 'types' => ['text', 'select']],
            'not_empty' => ['label' => 'Terisi / Memiliki data', 'types' => ['text', 'select']],
        ];
    }

    /**
     * Open filter modal.
     */
    public function openFilterModal(?string $defaultField = null): void
    {
        $fields = $this->getFilterableFields();
        if (empty($fields)) return;

        $firstKey = $defaultField && isset($fields[$defaultField]) ? $defaultField : array_key_first($fields);
        $this->newFilterField = $firstKey;
        
        $def = $fields[$firstKey] ?? null;
        $type = $def['type'] ?? 'text';
        
        if ($type === 'select') {
            $this->newFilterOperator = 'in';
            $this->newFilterValue = [];
        } else {
            $this->newFilterOperator = '=';
            $this->newFilterValue = '';
        }

        $this->newFilterValueEnd = '';
        $this->showFilterModal = true;
    }

    /**
     * Close filter modal.
     */
    public function closeFilterModal(): void
    {
        $this->showFilterModal = false;
        $this->newFilterField = '';
        $this->newFilterOperator = '=';
        $this->newFilterValue = '';
        $this->newFilterValueEnd = '';
    }

    /**
     * When new filter field is changed in modal.
     */
    public function updatedNewFilterField($field): void
    {
        $fields = $this->getFilterableFields();
        $def = $fields[$field] ?? null;
        if (!$def) return;

        $type = $def['type'] ?? 'text';
        $allowedOps = array_keys($this->getAvailableOperatorsForType($type));
        
        if ($type === 'select') {
            $this->newFilterOperator = in_array('in', $allowedOps) ? 'in' : ($allowedOps[0] ?? '=');
            $this->newFilterValue = [];
        } else {
            if (!in_array($this->newFilterOperator, $allowedOps)) {
                $this->newFilterOperator = $allowedOps[0] ?? '=';
            }
            $this->newFilterValue = '';
        }

        $this->newFilterValueEnd = '';
    }

    /**
     * Get available operators for field type.
     */
    public function getAvailableOperatorsForType(string $type): array
    {
        $operators = $this->getOperatorDefinitions();
        $filtered = [];

        foreach ($operators as $op => $info) {
            if (in_array($type, $info['types'])) {
                $filtered[$op] = $info['label'];
            }
        }

        return $filtered;
    }

    /**
     * Add a dynamic filter from modal.
     */
    public function addDynamicFilter($field = null, $operator = null, $value = null, $valueEnd = null): void
    {
        $fields = $this->getFilterableFields();
        $fieldKey = $field ?: $this->newFilterField;
        $def = $fields[$fieldKey] ?? null;

        if (!$def) {
            $this->closeFilterModal();
            return;
        }

        $op = $operator ?: $this->newFilterOperator;
        $val = $value !== null ? $value : $this->newFilterValue;
        $valEnd = $valueEnd !== null ? $valueEnd : $this->newFilterValueEnd;

        // Skip value validation if operator checks empty / not empty
        if (!in_array($op, ['empty', 'not_empty'])) {
            if (is_array($val)) {
                // Filter out empty strings/nulls
                $val = array_values(array_filter($val, fn($item) => $item !== '' && $item !== null));
            }

            if ($val === '' || $val === null || (is_array($val) && empty($val))) {
                if (method_exists($this, 'dispatch')) {
                    $this->dispatch('toast', type: 'warning', title: 'Perhatian', message: 'Silakan pilih atau isi nilai filter.');
                }
                return;
            }
        }

        $opDefs = $this->getOperatorDefinitions();
        $opLabel = $opDefs[$op]['label'] ?? $op;

        // Map values to human labels if options mapping exists
        $optionsMap = [];
        if (!empty($def['options'])) {
            foreach ($def['options'] as $k => $v) {
                $optVal = is_numeric($k) ? (string)$v : (string)$k;
                $optionsMap[$optVal] = (string)$v;
            }
        }

        // Compute human-friendly display value
        $displayVal = '';
        if (in_array($op, ['empty', 'not_empty'])) {
            $displayVal = '-';
        } elseif ($op === 'between') {
            $displayVal = $val . ' s/d ' . ($valEnd ?: $val);
        } elseif (is_array($val)) {
            $labels = array_map(fn($v) => $optionsMap[(string)$v] ?? (string)$v, $val);
            if (count($labels) === 1) {
                $displayVal = $labels[0];
            } elseif (count($labels) <= 3) {
                $displayVal = implode(', ', $labels);
            } else {
                $displayVal = count($labels) . ' dipilih (' . implode(', ', array_slice($labels, 0, 2)) . '...)';
            }
        } else {
            $displayVal = $optionsMap[(string)$val] ?? (string)$val;
        }

        $this->dynamicFilters[] = [
            'id' => 'flt_' . Str::random(8),
            'field' => $fieldKey,
            'operator' => $op,
            'value' => $val,
            'value_end' => $valEnd,
            'label' => $def['label'] ?? ucfirst($fieldKey),
            'display_value' => $displayVal,
            'operator_label' => $opLabel,
        ];

        $this->closeFilterModal();

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Remove a dynamic filter by index or id.
     */
    public function removeDynamicFilter($indexOrId): void
    {
        if (is_numeric($indexOrId) && isset($this->dynamicFilters[$indexOrId])) {
            unset($this->dynamicFilters[$indexOrId]);
        } else {
            $this->dynamicFilters = array_filter(
                $this->dynamicFilters,
                fn($f) => ($f['id'] ?? null) !== $indexOrId
            );
        }

        $this->dynamicFilters = array_values($this->dynamicFilters);

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Clear all dynamic filters.
     */
    public function clearAllDynamicFilters(): void
    {
        $this->dynamicFilters = [];

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Apply active dynamic filters to an Eloquent or DB query builder.
     */
    public function applyDynamicFilters($query, ?array $customFields = null)
    {
        $fields = $customFields ?: $this->getFilterableFields();

        foreach ($this->dynamicFilters as $filter) {
            $fieldKey = $filter['field'] ?? null;
            if (!$fieldKey || !isset($fields[$fieldKey])) continue;

            $def = $fields[$fieldKey];
            $op = $filter['operator'] ?? '=';
            $val = $filter['value'] ?? null;
            $valEnd = $filter['value_end'] ?? null;

            // 1. Custom closure handler takes precedence
            if (isset($def['handler']) && is_callable($def['handler'])) {
                call_user_func($def['handler'], $query, $op, $val, $filter);
                continue;
            }

            // 2. Default column handler
            $column = $def['column'] ?? $fieldKey;

            switch ($op) {
                case '=':
                    if (is_array($val)) {
                        $query->whereIn($column, $val);
                    } else {
                        $query->where($column, '=', $val);
                    }
                    break;

                case '!=':
                    // Safe-null check for SQL !=
                    $query->where(function ($sub) use ($column, $val) {
                        if (is_array($val)) {
                            $sub->whereNull($column)->orWhereNotIn($column, $val);
                        } else {
                            $sub->whereNull($column)->orWhere($column, '!=', $val);
                        }
                    });
                    break;

                case 'like':
                    $query->where($column, 'like', '%' . $val . '%');
                    break;

                case 'not_like':
                    $query->where(function ($sub) use ($column, $val) {
                        $sub->whereNull($column)->orWhere($column, 'not like', '%' . $val . '%');
                    });
                    break;

                case '>=':
                    $query->where($column, '>=', $val);
                    break;

                case '<=':
                    $query->where($column, '<=', $val);
                    break;

                case 'between':
                    $start = $val;
                    $end = $valEnd ?: $val;
                    $query->whereBetween($column, [$start, $end]);
                    break;

                case 'in':
                    $arr = is_array($val) ? $val : array_map('trim', explode(',', (string)$val));
                    $query->whereIn($column, $arr);
                    break;

                case 'not_in':
                    $arr = is_array($val) ? $val : array_map('trim', explode(',', (string)$val));
                    $query->where(function ($sub) use ($column, $arr) {
                        $sub->whereNull($column)->orWhereNotIn($column, $arr);
                    });
                    break;

                case 'empty':
                    $query->where(function ($sub) use ($column) {
                        $sub->whereNull($column)->orWhere($column, '');
                    });
                    break;

                case 'not_empty':
                    $query->whereNotNull($column)->where($column, '!=', '');
                    break;
            }
        }

        return $query;
    }
}
