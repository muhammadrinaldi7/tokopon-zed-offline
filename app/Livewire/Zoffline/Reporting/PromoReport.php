<?php

namespace App\Livewire\Zoffline\Reporting;

use App\Exports\PromoReportExport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductAccurate;
use App\Models\Promo;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PromoReport extends Component
{
    use WithPagination;

    public $dateRange = 'this_month';
    public $startDate;
    public $endDate;
    public $search = '';
    public $brandFilter = '';
    public $businessUnitFilter = '';

    public function mount()
    {
        $this->setDateRange();
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->setDateRange();
        }
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }
    public function updatedEndDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }
    public function updatedSearch()
    {
        $this->resetPage();
    }
    public function updatedBrandFilter()
    {
        $this->resetPage();
    }
    public function updatedBusinessUnitFilter()
    {
        $this->brandFilter = '';
        $this->resetPage();
    }

    private function setDateRange()
    {
        $now = now();
        switch ($this->dateRange) {
            case 'today':
                $this->startDate = $now->copy()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfDay()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = $now->copy()->subDay()->startOfDay()->format('Y-m-d');
                $this->endDate = $now->copy()->subDay()->endOfDay()->format('Y-m-d');
                break;
            case 'this_week':
                $this->startDate = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'last_week':
                $this->startDate = $now->copy()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->endDate = $now->copy()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            case 'this_month':
                $this->startDate = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'this_year':
                $this->startDate = $now->copy()->startOfYear()->format('Y-m-d');
                $this->endDate = $now->copy()->endOfYear()->format('Y-m-d');
                break;
        }
    }

    public function getOrdersQueryProperty()
    {
        return Order::with([
            'items.variant',
            'items.serialNumbers',
            'items.promos.bundleSkus',
            'items.promos.skus',
            'promos.bundleSkus',
            'promos.skus',
            'salesBy',
        ])
            ->where(function ($q) {
                $q->whereBetween('order_date', [$this->startDate, $this->endDate])
                    ->orWhere(function ($sub) {
                        $sub->whereNull('order_date')
                            ->whereBetween('created_at', [
                                Carbon::parse($this->startDate)->startOfDay(),
                                Carbon::parse($this->endDate)->endOfDay(),
                            ]);
                    });
            })
            ->whereHas('promos') // Hanya ambil order yang pakai promo
            ->whereNotIn('order_status', [
                'CANCELLED', 'cancelled',
                'DELETED', 'deleted',
                'down_payment', 'DOWN_PAYMENT',
                'paid', 'PAID',
                'pending', 'PENDING',
                'DRAFT', 'draft',
            ])
            ->whereIn('order_status', ['COMPLETED', 'completed', 'PIUTANG', 'piutang'])
            ->whereDoesntHave('approvalRequests', function ($q) {
                $q->whereIn('request_type', ['ORDER_CANCELLATION', 'cancellation'])
                    ->where('status', 'PENDING');
            })
            ->when($this->search, function ($query) {
                $search = trim($this->search);
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', '%' . $search . '%')
                        ->orWhere('accurate_invoice_no', 'like', '%' . $search . '%')
                        ->orWhereHas('salesBy', function ($salesQuery) use ($search) {
                            $salesQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('items', function ($itemQuery) use ($search) {
                            $itemQuery->where('serial_number', 'like', '%' . $search . '%')
                                ->orWhereHas('serialNumbers', function ($snQuery) use ($search) {
                                    $snQuery->where('serial_number', 'like', '%' . $search . '%');
                                })
                                ->orWhereHas('promos', function ($promoQuery) use ($search) {
                                    $promoQuery->where('order_item_promos.serial_number', 'like', '%' . $search . '%');
                                });
                        });
                });
            })
            ->when($this->brandFilter, function ($query) {
                // Filter order yang punya item dengan brand ini
                $query->whereHas('items', function ($q) {
                    $q->whereHasMorph('variant', [ProductAccurate::class], function ($q2) {
                        $q2->where('brandName', $this->brandFilter);
                    });
                });
            })
            ->when($this->businessUnitFilter, function ($query) {
                $query->where('business_unit_id', $this->businessUnitFilter);
            })
            ->latest('order_date');
    }

    /**
     * Resolve SKU item dari ProductAccurate.
     */
    private function resolveItemSku(OrderItem $item): string
    {
        return trim((string) ($item->variant?->item_no ?? ''));
    }

    /**
     * Resolve nama produk item.
     */
    private function resolveItemName(OrderItem $item): string
    {
        return $item->variant?->name ?? $item->product_name ?? 'Unknown Product';
    }

    /**
     * Resolve merk/brand item dari ProductAccurate.
     */
    private function resolveItemBrand(OrderItem $item): string
    {
        return $item->variant?->brandName ?? 'Unknown';
    }

    /**
     * Resolve daftar Serial Number item dari pivot promo (opsional), tabel order_item_serial_numbers, atau kolom serial_number.
     */
    private function resolveItemSerialNumbers(OrderItem $item, ?int $promoId = null): array
    {
        $sns = [];

        if ($promoId !== null) {
            foreach ($item->promos->where('id', $promoId) as $promoPivot) {
                $pivotSn = trim((string) ($promoPivot->pivot->serial_number ?? ''));
                if ($pivotSn !== '') {
                    $sns[] = $pivotSn;
                }
            }
        }

        if (empty($sns)) {
            $sns = $item->serialNumbers
                ->pluck('serial_number')
                ->map(fn($sn) => trim((string) $sn))
                ->filter(fn($sn) => $sn !== '')
                ->values()
                ->all();
        }

        if (empty($sns) && !empty($item->serial_number)) {
            $sns = array_values(array_filter(
                array_map('trim', explode(',', (string) $item->serial_number)),
                fn($sn) => $sn !== ''
            ));
        }

        return array_values(array_unique($sns));
    }

    /**
     * Kumpulkan ringkasan produk utama dan item bundling (beserta SN masing-masing) untuk satu kombinasi Order + Promo.
     */
    private function getOrderPromoBundleContext(Order $order, Promo $promo): array
    {
        if (!$promo->is_bundle) {
            return [
                'bundle_skus' => [],
                'bundle_items_text' => '-',
                'bundle_sns' => [],
                'main_skus' => [],
                'main_items_text' => '-',
                'main_sns' => [],
                'main_brands' => [],
            ];
        }

        $bundleSkus = $promo->bundleSkus
            ->pluck('sku')
            ->map(fn($sku) => trim((string) $sku))
            ->filter(fn($sku) => $sku !== '')
            ->unique()
            ->values()
            ->all();

        $mainSkus = $promo->skus
            ->pluck('sku')
            ->map(fn($sku) => trim((string) $sku))
            ->filter(fn($sku) => $sku !== '')
            ->unique()
            ->values()
            ->all();

        $bundleLabels = [];
        $bundleSns = [];
        $mainLabels = [];
        $mainSns = [];
        $mainBrands = [];

        foreach ($order->items as $candidateItem) {
            $candidateSku = $this->resolveItemSku($candidateItem);
            $isBundleCandidate = $candidateSku !== '' && in_array($candidateSku, $bundleSkus, true);

            if ($isBundleCandidate) {
                $bundleLabels[] = $this->resolveItemName($candidateItem) . ' (x' . $candidateItem->qty . ')';

                foreach ($this->resolveItemSerialNumbers($candidateItem, $promo->id) as $sn) {
                    $bundleSns[] = $sn;
                }
                continue;
            }

            $isMainCandidate = $promo->apply_to_all_items
                || ($candidateSku !== '' && in_array($candidateSku, $mainSkus, true));

            if ($isMainCandidate) {
                $mainLabels[] = $this->resolveItemName($candidateItem) . ' (x' . $candidateItem->qty . ')';
                $mainBrands[] = $this->resolveItemBrand($candidateItem);

                foreach ($this->resolveItemSerialNumbers($candidateItem, $promo->id) as $sn) {
                    $mainSns[] = $sn;
                }
            }
        }

        return [
            'bundle_skus' => $bundleSkus,
            'bundle_items_text' => !empty($bundleLabels) ? implode('; ', $bundleLabels) : '-',
            'bundle_sns' => array_values(array_unique($bundleSns)),
            'main_skus' => $mainSkus,
            'main_items_text' => !empty($mainLabels) ? implode('; ', $mainLabels) : '-',
            'main_sns' => array_values(array_unique($mainSns)),
            'main_brands' => array_values(array_unique($mainBrands)),
        ];
    }

    protected function getClaimRows(): array
    {
        $orders = $this->ordersQuery->get();
        $rows = [];

        foreach ($orders as $order) {
            $branch = $order->shipping_address_snapshot['store'] ?? 'Unknown';
            $salesName = $order->salesBy?->name ?? '-';
            $orderDate = $order->order_date
                ? $order->order_date->format('Y-m-d')
                : $order->created_at->format('Y-m-d');
            $updatedAt = $order->updated_at
                ? $order->updated_at->format('Y-m-d')
                : '-';
            $orderNo = $order->order_number;
            $invNo = $order->accurate_invoice_no ?? '-';

            $bundleContextCache = [];
            $mainRowTotalsByPromo = [];
            $bundleRowTotalsByPromo = [];

            foreach ($order->items as $item) {
                $itemSku = $this->resolveItemSku($item);
                foreach ($item->promos as $promo) {
                    if ((float) $promo->pivot->discount_amount <= 0) {
                        continue;
                    }
                    if (!isset($bundleContextCache[$promo->id])) {
                        $bundleContextCache[$promo->id] = $this->getOrderPromoBundleContext($order, $promo);
                    }
                    $isBundleItem = $promo->is_bundle
                        && $itemSku !== ''
                        && in_array($itemSku, $bundleContextCache[$promo->id]['bundle_skus'], true);

                    if ($isBundleItem) {
                        $bundleRowTotalsByPromo[$promo->id] = ($bundleRowTotalsByPromo[$promo->id] ?? 0) + 1;
                    } else {
                        $mainRowTotalsByPromo[$promo->id] = ($mainRowTotalsByPromo[$promo->id] ?? 0) + 1;
                    }
                }
            }

            $mainRowIndexByPromo = [];
            $bundleRowIndexByPromo = [];

            foreach ($order->items as $item) {
                $name = $this->resolveItemName($item);
                $merk = $this->resolveItemBrand($item);
                $itemSku = $this->resolveItemSku($item);
                $fallbackItemSns = $this->resolveItemSerialNumbers($item);

                foreach ($item->promos as $promo) {
                    $promoName = $promo->name;
                    $discountAmount = $promo->pivot->discount_amount;
                    $pivotSn = trim((string) ($promo->pivot->serial_number ?? ''));
                    $sn = $pivotSn !== ''
                        ? $pivotSn
                        : (!empty($fallbackItemSns) ? implode(', ', $fallbackItemSns) : '-');
                    $vendorName = $promo->pivot->vendor_name ?: $merk;

                    if ((float) $discountAmount <= 0) {
                        continue;
                    }

                    $bundleContext = $bundleContextCache[$promo->id] ?? $this->getOrderPromoBundleContext($order, $promo);
                    $isBundleItem = $promo->is_bundle
                        && $itemSku !== ''
                        && in_array($itemSku, $bundleContext['bundle_skus'], true);

                    $mainRowIdx = null;
                    $bundleRowIdx = null;
                    if ($isBundleItem) {
                        $bundleRowIdx = $bundleRowIndexByPromo[$promo->id] ?? 0;
                        $bundleRowIndexByPromo[$promo->id] = $bundleRowIdx + 1;
                    } else {
                        $mainRowIdx = $mainRowIndexByPromo[$promo->id] ?? 0;
                        $mainRowIndexByPromo[$promo->id] = $mainRowIdx + 1;
                    }

                    $matchesBrand = !$this->brandFilter
                        || $merk === $this->brandFilter
                        || ($isBundleItem
                            && ($mainRowTotalsByPromo[$promo->id] ?? 0) === 0
                            && in_array($this->brandFilter, $bundleContext['main_brands'], true));

                    if (!$matchesBrand) {
                        continue;
                    }

                    $tipeItem = $isBundleItem ? 'Item Bundling' : 'Produk Utama';
                    $produkUtama = '-';
                    $snProdukUtama = '-';
                    $itemBundling = '-';
                    $snBundling = '-';

                    if (!$isBundleItem && $promo->is_bundle) {
                        $itemBundling = $bundleContext['bundle_items_text'];
                        $bundleSns = $bundleContext['bundle_sns'];
                        $mainRowsCount = $mainRowTotalsByPromo[$promo->id] ?? 1;

                        if (!empty($bundleSns)) {
                            if ($mainRowsCount > 1 && count($bundleSns) === $mainRowsCount && isset($bundleSns[$mainRowIdx])) {
                                $snBundling = $bundleSns[$mainRowIdx];
                            } else {
                                $snBundling = implode(', ', $bundleSns);
                            }
                        }
                    } elseif ($isBundleItem && $promo->is_bundle) {
                        $produkUtama = $bundleContext['main_items_text'];
                        $mainSns = $bundleContext['main_sns'];
                        $bundleRowsCount = $bundleRowTotalsByPromo[$promo->id] ?? 1;

                        if (!empty($mainSns)) {
                            if ($bundleRowsCount > 1 && count($mainSns) === $bundleRowsCount && isset($mainSns[$bundleRowIdx])) {
                                $snProdukUtama = $mainSns[$bundleRowIdx];
                            } else {
                                $snProdukUtama = implode(', ', $mainSns);
                            }
                        }
                    }

                    $rows[] = [
                        'tanggal' => $orderDate,
                        'updated_at' => $updatedAt,
                        'order_no' => $orderNo,
                        'inv_no' => $invNo,
                        'cabang' => $branch,
                        'sales' => $salesName,
                        'merk' => $merk,
                        'vendor' => $vendorName,
                        'tipe_item' => $tipeItem,
                        'nama_produk' => $name,
                        'sn' => $sn,
                        'produk_utama' => $produkUtama,
                        'sn_produk_utama' => $snProdukUtama,
                        'item_bundling' => $itemBundling,
                        'sn_bundling' => $snBundling,
                        'nama_promo' => $promoName,
                        'nilai_klaim' => (float) $discountAmount,
                    ];
                }
            }
        }

        return $rows;
    }

    public function exportExcelClaim()
    {
        $rows = $this->getClaimRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data klaim promo untuk diexport pada rentang tanggal ini.', type: 'warning');
            return;
        }

        $excelFileName = 'laporan_klaim_promo_' . $this->startDate . '_sd_' . $this->endDate . '.xlsx';
        return Excel::download(new PromoReportExport($rows), $excelFileName);
    }

    public function exportCsvClaim()
    {
        $rows = $this->getClaimRows();

        if (empty($rows)) {
            $this->dispatch('toast', title: 'Perhatian', message: 'Tidak ada data klaim promo untuk diexport pada rentang tanggal ini.', type: 'warning');
            return;
        }

        $csvFileName = 'laporan_klaim_promo_' . $this->startDate . '_sd_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'TANGGAL',
                'UPDATED AT',
                'NO. ORDER',
                'NO. INVOICE',
                'CABANG',
                'SALES',
                'MERK PRODUK',
                'NAMA VENDOR',
                'TIPE ITEM',
                'NAMA PRODUK',
                'SN',
                'PRODUK UTAMA',
                'SN PRODUK UTAMA',
                'ITEM BUNDLING',
                'SN BUNDLING',
                'NAMA PROMO',
                'NILAI KLAIM PROMO (Rp)'
            ]);

            foreach ($rows as $row) {
                fputcsv($file, array_values($row));
            }

            fclose($file);
        }, $csvFileName);
    }

    public function render()
    {
        $orders = $this->ordersQuery->paginate(20);

        // Ambil list brand yang unik dari order-order yang ada (untuk filter)
        $availableBrands = \App\Models\ProductAccurate::whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->when($this->businessUnitFilter, function ($q) {
                $q->where('business_unit_id', $this->businessUnitFilter);
            })
            ->distinct()
            ->pluck('brandName')
            ->unique(fn($brand) => strtolower(trim($brand)))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('livewire.zoffline.reporting.promo-report', compact('orders', 'availableBrands'))->layout('layouts.z');
    }
}
