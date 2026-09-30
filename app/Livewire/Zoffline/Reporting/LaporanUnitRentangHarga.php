<?php

namespace App\Livewire\Zoffline\Reporting;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use App\Models\ProductSerialNumber;
use App\Models\ProductAccurate;
use App\Models\Warehouse;
use App\Models\Vendor;
use App\Models\BusinessUnit;
use App\Models\BusinessUnitProject;
use App\Exports\LaporanUnitRentangHargaExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanUnitRentangHarga extends Component
{
    use WithPagination;

    #[Url(as: 'view_mode', except: 'sku')]
    public $viewMode = 'sku'; // 'sku' (Rekap per Produk/SKU) or 'sn' (Detail per Serial Number)

    #[Url(except: '')]
    public $search = '';

    #[Url(as: 'bu_id', except: '')]
    public $businessUnitId = '';

    #[Url(except: '')]
    public $warehouseId = '';

    #[Url(as: 'vendor_id', except: '')]
    public $vendor_id = '';

    #[Url(as: 'subkategori', except: '')]
    public $subkategori = '';

    #[Url(as: 'brand', except: '')]
    public $brand = '';

    #[Url(as: 'kategori', except: '')]
    public $kategori = '';

    #[Url(as: 'min_price', except: '')]
    public $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public $maxPrice = '';

    #[Url(as: 'price_type', except: 'harga_jual')]
    public $priceType = 'harga_jual'; // 'harga_jual' (SKU base_price) or 'hpp' (SN modal)

    #[Url(as: 'preset', except: '')]
    public $selectedPreset = '';

    public $sortField = 'harga_jual';
    public $sortDirection = 'asc';
    public $csvSeparator = ';';

    public function mount()
    {
        $defaultBuId = \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId();
        if (request()->has('bu_id')) {
            $this->businessUnitId = request()->query('bu_id');
        } elseif ($defaultBuId) {
            $this->businessUnitId = (string) $defaultBuId;
        }

        if (request()->has('view_mode')) {
            $this->viewMode = request()->query('view_mode');
        }

        if (request()->has('warehouseId') && !empty(request()->query('warehouseId'))) {
            $this->warehouseId = request()->query('warehouseId');
        }

        if (request()->has('vendor_id') && !empty(request()->query('vendor_id'))) {
            $this->vendor_id = request()->query('vendor_id');
        }

        if (request()->has('subkategori') && !empty(request()->query('subkategori'))) {
            $this->subkategori = request()->query('subkategori');
        } elseif (request()->has('proyek') && !empty(request()->query('proyek'))) {
            $this->subkategori = request()->query('proyek');
        }

        if (request()->has('brand') && !empty(request()->query('brand'))) {
            $this->brand = request()->query('brand');
        }

        if (request()->has('kategori') && !empty(request()->query('kategori'))) {
            $this->kategori = request()->query('kategori');
        }

        if (request()->has('min_price')) {
            $this->minPrice = request()->query('min_price');
        }
        if (request()->has('max_price')) {
            $this->maxPrice = request()->query('max_price');
        }
        if (request()->has('price_type')) {
            $this->priceType = request()->query('price_type');
        }
        if (request()->has('preset')) {
            $this->selectedPreset = request()->query('preset');
        }
    }

    public function updatingViewMode()
    {
        $this->resetPage();
    }

    public function updatedViewMode()
    {
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingBusinessUnitId()
    {
        $this->warehouseId = '';
        $this->resetPage();
    }

    public function updatedBusinessUnitId()
    {
        $this->warehouseId = '';
        $this->resetPage();
    }

    public function updatingWarehouseId()
    {
        $this->resetPage();
    }

    public function updatingVendorId()
    {
        $this->resetPage();
    }

    public function updatingSubkategori()
    {
        $this->resetPage();
    }

    public function updatingBrand()
    {
        $this->resetPage();
    }

    public function updatedBrand()
    {
        $this->resetPage();
    }

    public function updatingKategori()
    {
        $this->resetPage();
    }

    public function updatedKategori()
    {
        $this->resetPage();
    }

    public function updatingMinPrice()
    {
        $this->selectedPreset = 'custom';
        $this->resetPage();
    }

    public function updatingMaxPrice()
    {
        $this->selectedPreset = 'custom';
        $this->resetPage();
    }

    public function updatingPriceType()
    {
        $this->resetPage();
    }

    public function setPreset($preset)
    {
        $this->selectedPreset = $preset;
        $this->resetPage();

        switch ($preset) {
            case 'under_1m':
                $this->minPrice = '0';
                $this->maxPrice = '1000000';
                break;
            case '1m_3m':
                $this->minPrice = '1000000';
                $this->maxPrice = '3000000';
                break;
            case '3m_5m':
                $this->minPrice = '3000000';
                $this->maxPrice = '5000000';
                break;
            case '5m_10m':
                $this->minPrice = '5000000';
                $this->maxPrice = '10000000';
                break;
            case 'above_10m':
                $this->minPrice = '10000000';
                $this->maxPrice = '';
                break;
            case 'all':
            case 'reset':
            default:
                $this->minPrice = '';
                $this->maxPrice = '';
                $this->selectedPreset = '';
                break;
        }
    }

    public function resetFilters()
    {
        $defaultBuId = \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId();
        $this->reset([
            'search',
            'warehouseId',
            'vendor_id',
            'subkategori',
            'brand',
            'kategori',
            'minPrice',
            'maxPrice',
            'priceType',
            'selectedPreset'
        ]);
        $this->businessUnitId = $defaultBuId ? (string) $defaultBuId : '';
        $this->priceType = 'harga_jual';
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
            $this->sortField = $field;
        }
    }

    protected function cleanNumeric($value)
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Remove dots, commas, currency symbols, and whitespace
        $cleaned = preg_replace('/[^\d.]/', '', str_replace('.', '', (string)$value));
        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    protected function getEffectiveBusinessUnitId()
    {
        if (!empty($this->businessUnitId)) {
            return $this->businessUnitId;
        }
        return \Illuminate\Support\Facades\Auth::user()?->getActiveBusinessUnitId();
    }

    /**
     * Query for SKU / Product grouped view
     */
    protected function getSkuQuery()
    {
        $effectiveBuId = $this->getEffectiveBusinessUnitId();

        $whQuery = Warehouse::query();
        if ($effectiveBuId) {
            $whQuery->where('business_unit_id', $effectiveBuId);
        }
        $validWarehouseIds = $whQuery->pluck('id')->toArray();

        $min = $this->cleanNumeric($this->minPrice);
        $max = $this->cleanNumeric($this->maxPrice);

        $warehouseId = $this->warehouseId;
        $vendorId = $this->vendor_id;

        $query = ProductAccurate::query()
            ->with([
                'productSerialNumbers' => function ($q) use ($validWarehouseIds, $warehouseId, $vendorId) {
                    $q->where('status', 'Available')
                      ->when(!empty($validWarehouseIds), fn($sq) => $sq->whereIn('warehouse_id', $validWarehouseIds))
                      ->when($warehouseId, fn($sq) => $sq->where('warehouse_id', $warehouseId))
                      ->when($vendorId, fn($sq) => $sq->where('vendor_id', $vendorId))
                      ->with(['warehouse', 'vendor'])
                      ->orderBy('id', 'asc');
                },
                'businessUnit'
            ])
            ->whereHas('productSerialNumbers', function ($q) use ($validWarehouseIds, $warehouseId, $vendorId) {
                $q->where('status', 'Available')
                  ->when(!empty($validWarehouseIds), fn($sq) => $sq->whereIn('warehouse_id', $validWarehouseIds))
                  ->when($warehouseId, fn($sq) => $sq->where('warehouse_id', $warehouseId))
                  ->when($vendorId, fn($sq) => $sq->where('vendor_id', $vendorId));
            });

        if ($effectiveBuId) {
            $query->where('business_unit_id', $effectiveBuId);
        }

        // Filter Rentang Harga
        if ($this->priceType === 'hpp') {
            if ($min !== null || $max !== null) {
                $query->whereHas('productSerialNumbers', function ($snQuery) use ($min, $max, $validWarehouseIds, $warehouseId, $vendorId) {
                    $snQuery->where('status', 'Available')
                        ->when(!empty($validWarehouseIds), fn($sq) => $sq->whereIn('warehouse_id', $validWarehouseIds))
                        ->when($warehouseId, fn($sq) => $sq->where('warehouse_id', $warehouseId))
                        ->when($vendorId, fn($sq) => $sq->where('vendor_id', $vendorId))
                        ->when($min !== null, fn($sq) => $sq->where('hpp', '>=', $min))
                        ->when($max !== null, fn($sq) => $sq->where('hpp', '<=', $max));
                });
            }
        } else {
            // Default: Harga Jual (SKU base_price)
            if ($min !== null) {
                $query->where('base_price', '>=', $min);
            }
            if ($max !== null) {
                $query->where('base_price', '<=', $max);
            }
        }

        // Filter Brand
        if ($this->brand) {
            $query->where('brandName', $this->brand);
        }

        // Filter Kategori
        if ($this->kategori) {
            $query->where('categoryName', $this->kategori);
        }

        // Filter Subkategori / Proyek
        if ($this->subkategori) {
            $query->where('proyek', $this->subkategori);
        }

        // Filter Search
        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_no', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('brandName', 'like', '%' . $search . '%')
                  ->orWhere('categoryName', 'like', '%' . $search . '%')
                  ->orWhere('proyek', 'like', '%' . $search . '%')
                  ->orWhereHas('productSerialNumbers', function ($snQ) use ($search) {
                      $snQ->where('serial_number', 'like', '%' . $search . '%');
                  });
            });
        }

        // Sorting
        if (in_array($this->sortField, ['harga_jual', 'base_price'])) {
            $query->orderBy('base_price', $this->sortDirection);
        } elseif (in_array($this->sortField, ['item_no', 'sku'])) {
            $query->orderBy('item_no', $this->sortDirection);
        } elseif ($this->sortField === 'subkategori') {
            $query->orderBy('proyek', $this->sortDirection);
        } elseif ($this->sortField === 'name') {
            $query->orderBy('name', $this->sortDirection);
        } else {
            $query->orderBy('base_price', 'asc');
        }

        return $query;
    }

    /**
     * Query for Serial Number individual view
     */
    protected function getStockQuery()
    {
        $effectiveBuId = $this->getEffectiveBusinessUnitId();

        $whQuery = Warehouse::query();
        if ($effectiveBuId) {
            $whQuery->where('business_unit_id', $effectiveBuId);
        }
        $validWarehouseIds = $whQuery->pluck('id')->toArray();

        $min = $this->cleanNumeric($this->minPrice);
        $max = $this->cleanNumeric($this->maxPrice);

        $query = ProductSerialNumber::query()
            ->select('product_serial_numbers.*')
            ->with(['productAccurate', 'warehouse', 'vendor'])
            ->where('product_serial_numbers.status', 'Available');

        if (!empty($validWarehouseIds)) {
            $query->whereIn('product_serial_numbers.warehouse_id', $validWarehouseIds);
        } elseif ($effectiveBuId) {
            $query->whereRaw('1 = 0');
        }

        // Filter Rentang Harga
        if ($this->priceType === 'hpp') {
            if ($min !== null) {
                $query->where('product_serial_numbers.hpp', '>=', $min);
            }
            if ($max !== null) {
                $query->where('product_serial_numbers.hpp', '<=', $max);
            }
        } else {
            // Default: Harga Jual (product_accurates.base_price)
            if ($min !== null || $max !== null) {
                $query->where(function ($q) use ($min, $max) {
                    $q->whereHas('productAccurate', function ($paQuery) use ($min, $max) {
                        if ($min !== null) {
                            $paQuery->where('base_price', '>=', $min);
                        }
                        if ($max !== null) {
                            $paQuery->where('base_price', '<=', $max);
                        }
                    })->orWhere(function ($fallback) use ($min, $max) {
                        $fallback->whereNull('product_serial_numbers.product_accurate_id')
                            ->whereIn('product_serial_numbers.item_no', function ($inner) use ($min, $max) {
                                $inner->select('item_no')
                                    ->from('product_accurates');
                                if ($min !== null) {
                                    $inner->where('base_price', '>=', $min);
                                }
                                if ($max !== null) {
                                    $inner->where('base_price', '<=', $max);
                                }
                            });
                    });
                });
            }
        }

        // Filter Brand / Merek
        if ($this->brand) {
            $brand = $this->brand;
            $query->where(function ($q) use ($brand) {
                $q->whereHas('productAccurate', function ($paQuery) use ($brand) {
                    $paQuery->where('brandName', $brand);
                })->orWhere(function ($fallbackQuery) use ($brand) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($brand) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('brandName', $brand);
                        });
                });
            });
        }

        // Filter Kategori
        if ($this->kategori) {
            $kategori = $this->kategori;
            $query->where(function ($q) use ($kategori) {
                $q->whereHas('productAccurate', function ($paQuery) use ($kategori) {
                    $paQuery->where('categoryName', $kategori);
                })->orWhere(function ($fallbackQuery) use ($kategori) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($kategori) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('categoryName', $kategori);
                        });
                });
            });
        }

        // Filter Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('product_serial_numbers.serial_number', 'like', '%' . $this->search . '%')
                    ->orWhere('product_serial_numbers.item_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('productAccurate', function ($sub) {
                        $sub->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('proyek', 'like', '%' . $this->search . '%')
                            ->orWhere('categoryName', 'like', '%' . $this->search . '%')
                            ->orWhere('brandName', 'like', '%' . $this->search . '%');
                    });
            });
        }

        // Filter Gudang
        if ($this->warehouseId) {
            $query->where('product_serial_numbers.warehouse_id', $this->warehouseId);
        }

        // Filter Vendor
        if ($this->vendor_id) {
            $query->where('product_serial_numbers.vendor_id', $this->vendor_id);
        }

        // Filter Subkategori / Proyek
        if ($this->subkategori) {
            $sub = $this->subkategori;
            $query->where(function ($q) use ($sub) {
                $q->whereHas('productAccurate', function ($paQuery) use ($sub) {
                    $paQuery->where('proyek', $sub);
                })->orWhere(function ($fallbackQuery) use ($sub) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($sub) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('proyek', $sub);
                        });
                });
            });
        }

        // Sorting
        if ($this->sortField === 'subkategori') {
            $query->leftJoin('product_accurates', 'product_serial_numbers.product_accurate_id', '=', 'product_accurates.id')
                ->orderBy('product_accurates.proyek', $this->sortDirection);
        } elseif (in_array($this->sortField, ['base_price', 'harga_jual'])) {
            $query->leftJoin('product_accurates', 'product_serial_numbers.product_accurate_id', '=', 'product_accurates.id')
                ->orderBy('product_accurates.base_price', $this->sortDirection);
        } else {
            $query->orderBy('product_serial_numbers.' . $this->sortField, $this->sortDirection);
        }

        return $query;
    }

    protected function getSummaryStats()
    {
        $effectiveBuId = $this->getEffectiveBusinessUnitId();

        $whQuery = Warehouse::query();
        if ($effectiveBuId) {
            $whQuery->where('business_unit_id', $effectiveBuId);
        }
        $validWarehouseIds = $whQuery->pluck('id')->toArray();

        $min = $this->cleanNumeric($this->minPrice);
        $max = $this->cleanNumeric($this->maxPrice);

        $baseQuery = ProductSerialNumber::query()
            ->where('product_serial_numbers.status', 'Available');

        if (!empty($validWarehouseIds)) {
            $baseQuery->whereIn('product_serial_numbers.warehouse_id', $validWarehouseIds);
        } elseif ($effectiveBuId) {
            $baseQuery->whereRaw('1 = 0');
        }

        if ($this->priceType === 'hpp') {
            if ($min !== null) {
                $baseQuery->where('product_serial_numbers.hpp', '>=', $min);
            }
            if ($max !== null) {
                $baseQuery->where('product_serial_numbers.hpp', '<=', $max);
            }
        } else {
            if ($min !== null || $max !== null) {
                $baseQuery->where(function ($q) use ($min, $max) {
                    $q->whereHas('productAccurate', function ($paQuery) use ($min, $max) {
                        if ($min !== null) {
                            $paQuery->where('base_price', '>=', $min);
                        }
                        if ($max !== null) {
                            $paQuery->where('base_price', '<=', $max);
                        }
                    })->orWhere(function ($fallback) use ($min, $max) {
                        $fallback->whereNull('product_serial_numbers.product_accurate_id')
                            ->whereIn('product_serial_numbers.item_no', function ($inner) use ($min, $max) {
                                $inner->select('item_no')
                                    ->from('product_accurates');
                                if ($min !== null) {
                                    $inner->where('base_price', '>=', $min);
                                }
                                if ($max !== null) {
                                    $inner->where('base_price', '<=', $max);
                                }
                            });
                    });
                });
            }
        }

        if ($this->brand) {
            $brand = $this->brand;
            $baseQuery->where(function ($q) use ($brand) {
                $q->whereHas('productAccurate', function ($paQuery) use ($brand) {
                    $paQuery->where('brandName', $brand);
                })->orWhere(function ($fallbackQuery) use ($brand) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($brand) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('brandName', $brand);
                        });
                });
            });
        }

        if ($this->kategori) {
            $kategori = $this->kategori;
            $baseQuery->where(function ($q) use ($kategori) {
                $q->whereHas('productAccurate', function ($paQuery) use ($kategori) {
                    $paQuery->where('categoryName', $kategori);
                })->orWhere(function ($fallbackQuery) use ($kategori) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($kategori) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('categoryName', $kategori);
                        });
                });
            });
        }

        if ($this->search) {
            $baseQuery->where(function ($q) {
                $q->where('product_serial_numbers.serial_number', 'like', '%' . $this->search . '%')
                    ->orWhere('product_serial_numbers.item_no', 'like', '%' . $this->search . '%')
                    ->orWhereHas('productAccurate', function ($sub) {
                        $sub->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('proyek', 'like', '%' . $this->search . '%')
                            ->orWhere('categoryName', 'like', '%' . $this->search . '%')
                            ->orWhere('brandName', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->warehouseId) {
            $baseQuery->where('product_serial_numbers.warehouse_id', $this->warehouseId);
        }

        if ($this->vendor_id) {
            $baseQuery->where('product_serial_numbers.vendor_id', $this->vendor_id);
        }

        if ($this->subkategori) {
            $sub = $this->subkategori;
            $baseQuery->where(function ($q) use ($sub) {
                $q->whereHas('productAccurate', function ($paQuery) use ($sub) {
                    $paQuery->where('proyek', $sub);
                })->orWhere(function ($fallbackQuery) use ($sub) {
                    $fallbackQuery->whereNull('product_serial_numbers.product_accurate_id')
                        ->whereIn('product_serial_numbers.item_no', function ($inner) use ($sub) {
                            $inner->select('item_no')
                                ->from('product_accurates')
                                ->where('proyek', $sub);
                        });
                });
            });
        }

        $stats = (clone $baseQuery)
            ->leftJoin('product_accurates', 'product_serial_numbers.product_accurate_id', '=', 'product_accurates.id')
            ->selectRaw('
                COUNT(DISTINCT product_serial_numbers.item_no) as total_skus,
                COUNT(product_serial_numbers.id) as total_units,
                SUM(COALESCE(product_accurates.base_price, 0)) as total_nilai_jual,
                SUM(COALESCE(product_serial_numbers.hpp, 0)) as total_nilai_hpp,
                AVG(COALESCE(product_accurates.base_price, 0)) as avg_harga_jual
            ')
            ->first();

        return [
            'total_skus' => $stats->total_skus ?? 0,
            'total_units' => $stats->total_units ?? 0,
            'total_nilai_jual' => (float)($stats->total_nilai_jual ?? 0),
            'total_nilai_hpp' => (float)($stats->total_nilai_hpp ?? 0),
            'avg_harga_jual' => (float)($stats->avg_harga_jual ?? 0),
        ];
    }

    public function exportExcel()
    {
        if ($this->viewMode === 'sku') {
            $data = $this->getSkuQuery()->get();
        } else {
            $data = $this->getStockQuery()->get();
        }

        if ($data->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data yang sesuai filter untuk diexport.', type: 'warning');
            return;
        }

        $filename = "laporan_unit_harga_" . $this->viewMode . "_" . date('Ymd_His') . ".xlsx";
        return Excel::download(new LaporanUnitRentangHargaExport($data, $this->viewMode), $filename);
    }

    public function exportCsv()
    {
        if ($this->viewMode === 'sku') {
            $data = $this->getSkuQuery()->get();
        } else {
            $data = $this->getStockQuery()->get();
        }

        if ($data->isEmpty()) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data yang sesuai filter untuk diexport.', type: 'warning');
            return;
        }

        $filename = "laporan_unit_harga_" . $this->viewMode . "_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $separator = $this->csvSeparator;
        $mode = $this->viewMode;

        if ($mode === 'sku') {
            $columns = [
                'SKU / ITEM NO',
                'NAMA PRODUK',
                'BRAND',
                'KATEGORI',
                'SUBKATEGORI / PROYEK',
                'HARGA JUAL (SKU)',
                'TOTAL UNIT READY (QTY)',
                'DAFTAR SERIAL NUMBER & MODAL (HPP)',
                'LOKASI GUDANG'
            ];
        } else {
            $columns = [
                'SERIAL NUMBER',
                'SKU',
                'NAMA PRODUK',
                'BRAND',
                'KATEGORI',
                'SUBKATEGORI',
                'GUDANG',
                'HPP',
                'HARGA JUAL',
                'VENDOR',
                'STATUS',
                'TANGGAL TERIMA',
                'UMUR (HARI)'
            ];
        }

        $callback = function () use ($data, $columns, $separator, $mode) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns, $separator);

            if ($mode === 'sku') {
                foreach ($data as $item) {
                    $sns = $item->productSerialNumbers ?? collect();
                    $snList = $sns->map(function ($sn) {
                        return $sn->serial_number . ' [HPP: Rp ' . number_format($sn->hpp ?? 0, 0, ',', '.') . ']';
                    })->implode(', ');
                    $warehouses = $sns->map(fn($sn) => $sn->warehouse->name ?? 'Belum Dialokasikan')->unique()->implode(', ');

                    fputcsv($file, [
                        $item->item_no,
                        $item->name ?? '-',
                        $item->brandName ?? '-',
                        $item->categoryName ?? '-',
                        $item->proyek ?? '-',
                        round($item->base_price ?? 0),
                        $sns->count(),
                        $snList ?: '-',
                        $warehouses ?: '-'
                    ], $separator);
                }
            } else {
                foreach ($data as $item) {
                    $umur = $item->receipt_date ? intval(\Carbon\Carbon::parse($item->receipt_date)->startOfDay()->diffInDays(now()->startOfDay())) . ' Hari' : '-';
                    fputcsv($file, [
                        $item->serial_number,
                        $item->item_no,
                        $item->productAccurate->name ?? '-',
                        $item->productAccurate->brandName ?? '-',
                        $item->productAccurate->categoryName ?? '-',
                        $item->productAccurate->proyek ?? ($item->proyek ?? '-'),
                        $item->warehouse->name ?? 'Belum Dialokasikan',
                        round($item->hpp ?? 0),
                        round($item->productAccurate->base_price ?? ($item->base_price ?? 0)),
                        $item->vendor->vendor_name ?? '-',
                        $item->status,
                        $item->receipt_date ? \Carbon\Carbon::parse($item->receipt_date)->format('Y-m-d') : '-',
                        $umur
                    ], $separator);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $effectiveBuId = $this->getEffectiveBusinessUnitId();

        if ($this->viewMode === 'sku') {
            $items = $this->getSkuQuery()->paginate(15);
        } else {
            $items = $this->getStockQuery()->paginate(20);
        }

        $whQuery = Warehouse::query();
        if ($effectiveBuId) {
            $whQuery->where('business_unit_id', $effectiveBuId);
        }
        $warehouses = $whQuery->orderBy('name')->get();

        $businessUnits = BusinessUnit::where('is_active', true)->orderBy('name')->get();
        if ($businessUnits->isEmpty()) {
            $businessUnits = BusinessUnit::orderBy('name')->get();
        }

        $vendors = Vendor::orderBy('vendor_name')->get();
        $summary = $this->getSummaryStats();

        // Brand options
        $brandQuery = ProductAccurate::whereNotNull('brandName')
            ->where('brandName', '!=', '');
        if ($effectiveBuId) {
            $brandQuery->where('business_unit_id', $effectiveBuId);
        }
        $brands = $brandQuery->distinct()->pluck('brandName')->filter()->sort()->values();
        if ($brands->isEmpty()) {
            $brands = ProductAccurate::whereNotNull('brandName')
                ->where('brandName', '!=', '')
                ->distinct()
                ->pluck('brandName')
                ->filter()
                ->sort()
                ->values();
        }

        // Kategori options
        $catQuery = ProductAccurate::whereNotNull('categoryName')
            ->where('categoryName', '!=', '');
        if ($effectiveBuId) {
            $catQuery->where('business_unit_id', $effectiveBuId);
        }
        $kategoris = $catQuery->distinct()->pluck('categoryName')->filter()->sort()->values();
        if ($kategoris->isEmpty()) {
            $kategoris = ProductAccurate::whereNotNull('categoryName')
                ->where('categoryName', '!=', '')
                ->distinct()
                ->pluck('categoryName')
                ->filter()
                ->sort()
                ->values();
        }

        // Subkategori / Proyek options
        $proyekQuery = ProductAccurate::whereNotNull('proyek')
            ->where('proyek', '!=', '');

        if ($effectiveBuId) {
            $proyekQuery->where('business_unit_id', $effectiveBuId);
        }

        $subkategoris = $proyekQuery->distinct()
            ->pluck('proyek')
            ->map(fn($p) => trim((string)$p))
            ->filter();

        if ($subkategoris->isEmpty()) {
            $subkategoris = ProductAccurate::whereNotNull('proyek')
                ->where('proyek', '!=', '')
                ->distinct()
                ->pluck('proyek')
                ->map(fn($p) => trim((string)$p))
                ->filter();
        }

        $bupQuery = BusinessUnitProject::query();
        if ($effectiveBuId) {
            $bupQuery->where('business_unit_id', $effectiveBuId);
        }
        $bupNames = $bupQuery->pluck('name')->map(fn($p) => trim((string)$p))->filter();

        $subkategoris = $subkategoris->concat($bupNames)
            ->unique()
            ->sort()
            ->values();

        return view('livewire.zoffline.reporting.laporan-unit-rentang-harga', [
            'items' => $items,
            'warehouses' => $warehouses,
            'businessUnits' => $businessUnits,
            'brands' => $brands,
            'kategoris' => $kategoris,
            'vendors' => $vendors,
            'subkategoris' => $subkategoris,
            'summary' => $summary,
        ])->layout('layouts.z');
    }
}
