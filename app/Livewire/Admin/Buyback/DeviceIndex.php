<?php

namespace App\Livewire\Admin\Buyback;

use App\Exports\TradeInPriceExport;
use App\Models\ProductAccurate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Layout('layouts.admin', ['title' => 'Daftar Perangkat Buyback & Harga Trade-In'])]
class DeviceIndex extends Component
{
    use WithFileUploads, WithPagination;

    public $search = '';
    public $filterBrandName = '';
    public $filterCategoryName = '';
    public $filterProyek = '';
    public $filterOs = '';

    public $showEditModal = false;
    public $editingDeviceId = null;
    public $editItemNo = '';
    public $editName = '';
    public $editAccuratePrice = 0;
    public $editBuyPrice = 0;
    public $editCustomSellPrice = 0;
    public $applyToColorGroup = false;

    // Excel / CSV Import & Export
    public $showImportModal = false;
    public $csvFile;
    public $syncColorGroupOnImport = false;
    public $importSummary = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterBrandName()
    {
        $this->resetPage();
    }

    public function updatingFilterCategoryName()
    {
        $this->resetPage();
    }

    public function updatingFilterProyek()
    {
        $this->resetPage();
    }

    public function updatingFilterOs()
    {
        $this->resetPage();
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->csvFile = null;
        $this->importSummary = null;
    }

    protected function getBusinessUnitId(): int
    {
        return (int) (Auth::user()?->getActiveBusinessUnitId()
            ?? Auth::user()?->business_unit_id
            ?? 2);
    }

    /**
     * Parse input harga (integer, float Excel, atau string berformat Rp / titik ribuan) secara presisi.
     */
    public static function parsePriceCell(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return max(0, (int) round((float) $value));
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-') {
            return null;
        }

        // Bersihkan simbol mata uang dan spasi
        $str = trim((string) preg_replace('/^(Rp\.?|IDR)\s*/i', '', $str));
        if ($str === '') {
            return null;
        }

        // Jika desimal standar dengan 1-2 angka di belakang titik (contoh: "3500000.00" dari DB/Excel)
        if (preg_match('/^\d+\.\d{1,2}$/', $str)) {
            return max(0, (int) round((float) $str));
        }

        // Jika menggunakan koma desimal di akhir (contoh: "3.500.000,00")
        $str = (string) preg_replace('/,\d{1,2}$/', '', $str);

        $digits = preg_replace('/[^0-9]/', '', $str);
        if ($digits === '' || $digits === null) {
            return 0;
        }

        return max(0, (int) $digits);
    }

    public function editDevice($id)
    {
        $device = ProductAccurate::where('business_unit_id', $this->getBusinessUnitId())->find($id);
        if ($device) {
            $this->editingDeviceId = $device->id;
            $this->editItemNo = $device->item_no;
            $this->editName = $device->name;
            $this->editAccuratePrice = (int) round((float) ($device->base_price ?? 0));
            $this->editBuyPrice = $device->buy_price ? (int) round((float) $device->buy_price) : 0;
            $this->editCustomSellPrice = $device->custom_sell_price ? (int) round((float) $device->custom_sell_price) : 0;
            $this->applyToColorGroup = false;
            $this->showEditModal = true;
        }
    }

    public function updatedEditBuyPrice($value)
    {
        $this->editBuyPrice = self::parsePriceCell($value) ?? 0;
    }

    public function updatedEditCustomSellPrice($value)
    {
        $this->editCustomSellPrice = self::parsePriceCell($value) ?? 0;
    }

    public function updateDevice()
    {
        $this->editBuyPrice = self::parsePriceCell($this->editBuyPrice) ?? 0;
        $this->editCustomSellPrice = self::parsePriceCell($this->editCustomSellPrice) ?? 0;

        $this->validate([
            'editBuyPrice' => 'required|numeric|min:0',
            'editCustomSellPrice' => 'required|numeric|min:0',
        ]);

        $buId = $this->getBusinessUnitId();
        $device = ProductAccurate::where('business_unit_id', $buId)->find($this->editingDeviceId);

        if (!$device) {
            $this->closeEditModal();
            return;
        }

        $payload = [
            'buy_price' => $this->editBuyPrice,
            'custom_sell_price' => $this->editCustomSellPrice > 0 ? $this->editCustomSellPrice : null,
        ];

        $syncedSiblings = 0;

        if ($this->applyToColorGroup) {
            $targetGroupKey = $device->getTradeInGroupKey();
            $attrs = ProductAccurate::parseTradeInAttributes($device->name, $device->proyek, $buId);

            $candidatesQuery = ProductAccurate::where('business_unit_id', $buId);
            if (!empty($device->brandName)) {
                $candidatesQuery->where('brandName', $device->brandName);
            }
            if (!empty($attrs['model'])) {
                $candidatesQuery->where('name', 'like', '%' . $attrs['model'] . '%');
            }

            $matchingIds = $candidatesQuery->get(['id', 'name', 'proyek', 'business_unit_id'])
                ->filter(fn($item) => $item->getTradeInGroupKey() === $targetGroupKey)
                ->pluck('id')
                ->all();

            if (!empty($matchingIds)) {
                ProductAccurate::where('business_unit_id', $buId)
                    ->whereIn('id', $matchingIds)
                    ->update($payload);
                $syncedSiblings = max(0, count($matchingIds) - 1);
            } else {
                $device->update($payload);
            }
        } else {
            $device->update($payload);
        }

        $this->closeEditModal();

        $msg = 'Harga perangkat berhasil diperbarui.';
        if ($syncedSiblings > 0) {
            $msg .= " Disamakan juga ke {$syncedSiblings} varian warna lainnya.";
        }

        $this->dispatch('toast', title: 'Berhasil', message: $msg, type: 'success');
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingDeviceId = null;
        $this->applyToColorGroup = false;
    }

    protected function buildFilteredQuery(int $buId)
    {
        $query = ProductAccurate::where('business_unit_id', $buId);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('item_no', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->filterBrandName)) {
            $query->where('brandName', $this->filterBrandName);
        }

        if (!empty($this->filterCategoryName)) {
            $query->where('categoryName', $this->filterCategoryName);
        }

        if (!empty($this->filterProyek)) {
            $query->where('proyek', $this->filterProyek);
        }

        if (!empty($this->filterOs)) {
            $query->where('os', $this->filterOs);
        }

        return $query;
    }

    protected function hasActiveFilter(): bool
    {
        return !empty($this->search)
            || !empty($this->filterBrandName)
            || !empty($this->filterCategoryName)
            || !empty($this->filterProyek)
            || !empty($this->filterOs);
    }

    protected function passesExportSizeGuard($query): bool
    {
        if (!$this->hasActiveFilter()) {
            $totalCount = (clone $query)->count();
            if ($totalCount > 1500) {
                $this->dispatch(
                    'toast',
                    title: 'Filter Diperlukan',
                    message: "Data terlalu besar ({$totalCount} produk). Silakan pilih filter Merek, OS, Proyek, atau Kategori terlebih dahulu sebelum mengekspor agar server tetap stabil.",
                    type: 'warning'
                );
                return false;
            }
        }

        return true;
    }

    public function downloadTemplate(string $format = 'xlsx')
    {
        $headings = ['ID', 'SKU', 'Nama Barang', 'Buy Price', 'Custom Sell Price'];
        $sampleRows = collect([
            [101, '1782741761464', 'HP 2ND IPHONE 11 - 128GB - BLACK - INTER', 2600000, 3550000],
            [102, '1782741761465', 'HP 2ND IPHONE 13 - 128GB - MIDNIGHT - RESMI', 5800000, 7250000],
        ]);

        if (strtolower($format) === 'csv') {
            $csvFileName = 'template_import_harga_tradein.csv';
            return response()->streamDownload(function () use ($headings, $sampleRows) {
                $file = fopen('php://output', 'w');
                fputs($file, "\xEF\xBB\xBF");
                fputcsv($file, $headings, ';');
                foreach ($sampleRows as $row) {
                    fputcsv($file, $row, ';');
                }
                fclose($file);
            }, $csvFileName, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
            ]);
        }

        return Excel::download(
            new TradeInPriceExport($sampleRows, $headings),
            'template_import_harga_tradein.xlsx'
        );
    }

    public function exportExcel(bool $groupedWithoutColor = false)
    {
        $buId = $this->getBusinessUnitId();
        $query = $this->buildFilteredQuery($buId)
            ->select('id', 'item_no', 'name', 'brandName', 'categoryName', 'proyek', 'os', 'base_price', 'buy_price', 'custom_sell_price', 'business_unit_id');

        if (!$this->passesExportSizeGuard($query)) {
            return;
        }

        $items = $query->orderBy('name')->get();

        if ($groupedWithoutColor) {
            $headings = [
                'ID',
                'SKU',
                'Nama Barang (Tanpa Warna)',
                'Merek',
                'Kategori',
                'Proyek',
                'OS',
                'Jumlah Warna',
                'Harga Accurate',
                'Buy Price',
                'Custom Sell Price',
            ];

            $rows = $items->groupBy(fn($device) => $device->getTradeInGroupKey())
                ->map(function ($group) use ($buId) {
                    $first = $group->first();
                    $attrs = ProductAccurate::parseTradeInAttributes($first->name, $first->proyek, $buId);
                    $displayName = ($attrs['color'] !== null || $group->count() > 1)
                        ? $attrs['grouped_name']
                        : $first->name;

                    $accuratePrice = (int) round((float) ($group->max('base_price') ?? 0));
                    $buyPrice = (int) round((float) ($group->first(fn($i) => ($i->buy_price ?? 0) > 0)?->buy_price ?? 0));
                    $customSellPrice = (int) round((float) ($group->first(fn($i) => ($i->custom_sell_price ?? 0) > 0)?->custom_sell_price ?? 0));

                    return [
                        $first->id,
                        $first->item_no,
                        $displayName,
                        $first->brandName ?: '-',
                        $first->categoryName ?: '-',
                        $first->proyek ?: '-',
                        $first->os ?: '-',
                        $group->count(),
                        $accuratePrice,
                        $buyPrice,
                        $customSellPrice,
                    ];
                })
                ->values();

            $fileName = 'tradein_devices_tanpa_warna_' . date('Ymd_His') . '.xlsx';
            return Excel::download(new TradeInPriceExport($rows, $headings), $fileName);
        }

        $headings = [
            'ID',
            'SKU',
            'Nama Barang',
            'Merek',
            'Kategori',
            'Proyek',
            'OS',
            'Harga Accurate',
            'Buy Price',
            'Custom Sell Price',
        ];

        $rows = $items->map(function ($device) {
            return [
                $device->id,
                $device->item_no,
                $device->name,
                $device->brandName ?: '-',
                $device->categoryName ?: '-',
                $device->proyek ?: '-',
                $device->os ?: '-',
                (int) round((float) ($device->base_price ?? 0)),
                (int) round((float) ($device->buy_price ?? 0)),
                (int) round((float) ($device->custom_sell_price ?? 0)),
            ];
        });

        $fileName = 'tradein_devices_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new TradeInPriceExport($rows, $headings), $fileName);
    }

    public function exportCsv()
    {
        $buId = $this->getBusinessUnitId();
        $query = $this->buildFilteredQuery($buId)
            ->select('id', 'item_no', 'name', 'brandName', 'categoryName', 'proyek', 'os', 'base_price', 'buy_price', 'custom_sell_price');

        if (!$this->passesExportSizeGuard($query)) {
            return;
        }

        $csvFileName = 'buyback_devices_' . date('Ymd_His') . '.csv';
        $columns = ['ID', 'SKU', 'Nama Barang', 'Merek', 'Kategori', 'Proyek', 'OS', 'Harga Accurate', 'Buy Price', 'Custom Sell Price'];

        return response()->streamDownload(function () use ($query, $columns) {
            $file = fopen('php://output', 'w');

            // Tambahkan BOM untuk Excel agar mengenali UTF-8
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $columns, ';'); // Menggunakan ; agar ramah Excel Indonesia

            foreach ($query->orderBy('name')->cursor() as $device) {
                fputcsv($file, [
                    $device->id,
                    $device->item_no,
                    $device->name,
                    $device->brandName ?: '-',
                    $device->categoryName ?: '-',
                    $device->proyek ?: '-',
                    $device->os ?: '-',
                    (int) round((float) ($device->base_price ?? 0)),
                    (int) round((float) ($device->buy_price ?? 0)),
                    (int) round((float) ($device->custom_sell_price ?? 0)),
                ], ';');
            }

            fclose($file);
        }, $csvFileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $csvFileName . '"',
        ]);
    }

    protected function extractSheetRows(string $filePath, string $extension): array
    {
        $filePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $filePath);

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            try {
                $reader = IOFactory::createReaderForFile($filePath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($filePath);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, false, false, false);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            } catch (\Throwable $e) {
                if ($extension === 'xlsx' && class_exists(\ZipArchive::class)) {
                    $sheetData = $this->readXlsxViaZipFallback($filePath);
                } else {
                    throw $e;
                }
            }

            if (empty($sheetData)) {
                return [null, []];
            }

            $header = array_shift($sheetData);
            return [$header, $sheetData];
        }

        $file = fopen($filePath, 'r');
        if (!$file) {
            return [null, []];
        }

        $firstLine = fgets($file);
        if ($firstLine === false) {
            fclose($file);
            return [null, []];
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        rewind($file);

        $headerLine = fgets($file);
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headerLine);
        $header = str_getcsv($headerLine, $delimiter);

        $rows = [];
        while (($row = fgetcsv($file, 4096, $delimiter)) !== false) {
            $rows[] = $row;
        }
        fclose($file);

        return [$header, $rows];
    }

    protected function readXlsxViaZipFallback(string $filePath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \RuntimeException('Tidak dapat membuka arsip file XLSX.');
        }

        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $ssDoc = @simplexml_load_string($ssXml);
            if ($ssDoc !== false) {
                foreach ($ssDoc->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $txt = '';
                        foreach ($si->r as $run) {
                            $txt .= (string) ($run->t ?? '');
                        }
                        $sharedStrings[] = $txt;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheetDoc = @simplexml_load_string($sheetXml);
        if ($sheetDoc === false || !isset($sheetDoc->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($sheetDoc->sheetData->row as $rowNode) {
            $rowArr = [];
            $maxColIdx = -1;
            foreach ($rowNode->c as $c) {
                $ref = (string) ($c['r'] ?? '');
                $colLetters = preg_replace('/[^A-Z]/', '', strtoupper($ref));
                $colIdx = 0;
                for ($i = 0, $len = strlen($colLetters); $i < $len; $i++) {
                    $colIdx = $colIdx * 26 + (ord($colLetters[$i]) - 64);
                }
                $colIdx = max(0, $colIdx - 1);
                $maxColIdx = max($maxColIdx, $colIdx);

                $type = (string) ($c['t'] ?? '');
                $val = null;
                if ($type === 's') {
                    $sIdx = (int) ($c->v ?? 0);
                    $val = $sharedStrings[$sIdx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($c->is->t ?? '');
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }
                $rowArr[$colIdx] = $val;
            }

            if ($maxColIdx >= 0) {
                $normalized = [];
                for ($i = 0; $i <= $maxColIdx; $i++) {
                    $normalized[$i] = $rowArr[$i] ?? null;
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    protected function resolveColumnIndex(array $headerMap, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $headerMap)) {
                return $headerMap[$candidate];
            }
        }
        return null;
    }

    public function importCsv()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240', // max 10MB
        ]);

        $filePath = $this->csvFile->getRealPath();
        $extension = strtolower((string) $this->csvFile->getClientOriginalExtension());

        try {
            [$header, $rawRows] = $this->extractSheetRows($filePath, $extension);
        } catch (\Throwable $e) {
            $this->addError('csvFile', 'Gagal membaca file Excel/CSV: ' . $e->getMessage());
            return;
        }

        if (empty($header) || !is_array($header)) {
            $this->addError('csvFile', 'Format header file Excel/CSV tidak valid.');
            return;
        }

        $normalizedHeaders = array_map(function ($col) {
            $clean = preg_replace('/^\xEF\xBB\xBF/', '', (string) $col);
            return strtolower(trim((string) preg_replace('/\s+/', ' ', $clean)));
        }, $header);

        $headerMap = array_flip($normalizedHeaders);

        $idCol = $this->resolveColumnIndex($headerMap, ['id']);
        $skuCol = $this->resolveColumnIndex($headerMap, ['sku', 'item_no', 'no item', 'no. item']);
        $buyPriceCol = $this->resolveColumnIndex($headerMap, ['buy price', 'buy_price', 'harga beli', 'buy price (beli)']);
        $sellPriceCol = $this->resolveColumnIndex($headerMap, [
            'custom sell price',
            'custom_sell_price',
            'sell price',
            'harga jual',
            'harga jual custom',
            'custom sell price (jual)',
        ]);

        if ($idCol === null && $skuCol === null) {
            $this->addError('csvFile', 'File wajib memiliki kolom "ID" atau "SKU".');
            return;
        }

        if ($buyPriceCol === null && $sellPriceCol === null) {
            $this->addError('csvFile', 'File wajib memiliki minimal salah satu kolom harga: "Buy Price" atau "Custom Sell Price".');
            return;
        }

        $buId = $this->getBusinessUnitId();

        // Kumpulkan baris valid berdasarkan ID (atau resolusi SKU jika ID kosong)
        $parsedById = [];
        $pendingBySku = [];

        foreach ($rawRows as $row) {
            if (!is_array($row) || count(array_filter($row, fn($v) => $v !== null && trim((string) $v) !== '')) === 0) {
                continue;
            }

            $rawId = $idCol !== null ? trim((string) ($row[$idCol] ?? '')) : '';
            $rawSku = $skuCol !== null ? trim((string) ($row[$skuCol] ?? '')) : '';

            $buyVal = $buyPriceCol !== null ? self::parsePriceCell($row[$buyPriceCol] ?? null) : null;
            $sellVal = $sellPriceCol !== null ? self::parsePriceCell($row[$sellPriceCol] ?? null) : null;

            // Jika kedua sel harga kosong pada baris ini, lewati
            if ($buyVal === null && $sellVal === null) {
                continue;
            }

            $entry = [
                'has_buy' => $buyVal !== null,
                'buy_price' => $buyVal,
                'has_sell' => $sellVal !== null,
                'custom_sell_price' => $sellVal,
            ];

            if ($rawId !== '' && is_numeric($rawId)) {
                $parsedById[(int) $rawId] = $entry;
            } elseif ($rawSku !== '') {
                $pendingBySku[$rawSku] = $entry;
            }
        }

        if (!empty($pendingBySku)) {
            $skuToId = ProductAccurate::where('business_unit_id', $buId)
                ->whereIn('item_no', array_keys($pendingBySku))
                ->pluck('id', 'item_no')
                ->toArray();

            foreach ($pendingBySku as $sku => $entry) {
                if (isset($skuToId[$sku])) {
                    $parsedById[(int) $skuToId[$sku]] = $entry;
                } else {
                    // Tandai ID negatif unik agar tetap dihitung sebagai skipped (tidak ditemukan)
                    $parsedById[-1 * (count($parsedById) + 1)] = $entry;
                }
            }
        }

        $totalRows = count($parsedById);
        if ($totalRows === 0) {
            $this->addError('csvFile', 'Tidak ada data baris harga yang valid di dalam file.');
            return;
        }

        if ($totalRows > 20000) {
            $this->addError('csvFile', "File terlalu besar ({$totalRows} baris). Maksimal 20.000 baris per file import untuk menjaga performa server.");
            return;
        }

        $chunks = array_chunk($parsedById, 250, true);

        $updatedCount = 0;
        $unchangedCount = 0;
        $skippedCount = 0;
        $groupSyncedCount = 0;
        $groupPropagationMap = [];

        try {
            foreach ($chunks as $chunk) {
                $positiveIds = array_values(array_filter(array_keys($chunk), fn($id) => $id > 0));

                $existingProducts = !empty($positiveIds)
                    ? ProductAccurate::where('business_unit_id', $buId)
                        ->whereIn('id', $positiveIds)
                        ->get(['id', 'name', 'proyek', 'brandName', 'buy_price', 'custom_sell_price', 'business_unit_id'])
                        ->keyBy('id')
                    : collect();

                $toUpdate = [];

                foreach ($chunk as $id => $entry) {
                    if ($id <= 0 || !$existingProducts->has($id)) {
                        $skippedCount++;
                        continue;
                    }

                    /** @var ProductAccurate $prod */
                    $prod = $existingProducts->get($id);
                    $changes = [];

                    if ($entry['has_buy']) {
                        $currentBuy = (int) round((float) ($prod->buy_price ?? 0));
                        $newBuy = (int) $entry['buy_price'];
                        if ($currentBuy !== $newBuy) {
                            $changes['buy_price'] = $newBuy;
                        }
                    }

                    if ($entry['has_sell']) {
                        $currentSell = (int) round((float) ($prod->custom_sell_price ?? 0));
                        $newSell = (int) $entry['custom_sell_price'];
                        if ($currentSell !== $newSell) {
                            $changes['custom_sell_price'] = $newSell > 0 ? $newSell : null;
                        }
                    }

                    if (!empty($changes)) {
                        $toUpdate[$id] = $changes;

                        if ($this->syncColorGroupOnImport) {
                            $groupKey = $prod->getTradeInGroupKey();
                            if (!isset($groupPropagationMap[$groupKey])) {
                                $groupPropagationMap[$groupKey] = [
                                    'brandName' => $prod->brandName,
                                    'model' => ProductAccurate::parseTradeInAttributes($prod->name, $prod->proyek, $buId)['model'],
                                    'changes' => [],
                                    'explicit_ids' => [],
                                ];
                            }
                            $groupPropagationMap[$groupKey]['changes'] = array_merge(
                                $groupPropagationMap[$groupKey]['changes'],
                                $changes
                            );
                            $groupPropagationMap[$groupKey]['explicit_ids'][] = $id;
                        }
                    } else {
                        $unchangedCount++;
                    }
                }

                if (!empty($toUpdate)) {
                    DB::transaction(function () use ($toUpdate, $buId) {
                        foreach ($toUpdate as $id => $changes) {
                            ProductAccurate::where('business_unit_id', $buId)
                                ->where('id', $id)
                                ->update($changes);
                        }
                    });
                    $updatedCount += count($toUpdate);
                }
            }

            // Propagasi otomatis ke varian warna lain dalam grup Model + Storage + Proyek yang sama (jika dicentang)
            if ($this->syncColorGroupOnImport && !empty($groupPropagationMap)) {
                foreach ($groupPropagationMap as $groupKey => $meta) {
                    if (empty($meta['changes'])) {
                        continue;
                    }

                    $siblingQuery = ProductAccurate::where('business_unit_id', $buId)
                        ->whereNotIn('id', $meta['explicit_ids']);

                    if (!empty($meta['brandName'])) {
                        $siblingQuery->where('brandName', $meta['brandName']);
                    }
                    if (!empty($meta['model'])) {
                        $siblingQuery->where('name', 'like', '%' . $meta['model'] . '%');
                    }

                    $siblingIds = $siblingQuery->get(['id', 'name', 'proyek', 'business_unit_id'])
                        ->filter(fn($item) => $item->getTradeInGroupKey() === $groupKey)
                        ->pluck('id')
                        ->all();

                    if (!empty($siblingIds)) {
                        ProductAccurate::where('business_unit_id', $buId)
                            ->whereIn('id', $siblingIds)
                            ->update($meta['changes']);
                        $groupSyncedCount += count($siblingIds);
                    }
                }
            }

            $this->csvFile = null;
            $this->importSummary = [
                'total' => $totalRows,
                'updated' => $updatedCount,
                'group_synced' => $groupSyncedCount,
                'unchanged' => $unchangedCount,
                'skipped' => $skippedCount,
            ];

            $msg = "Berhasil mengupdate {$updatedCount} perangkat.";
            if ($groupSyncedCount > 0) {
                $msg .= " (+{$groupSyncedCount} varian warna grup ikut disamakan).";
            }

            $this->dispatch('toast', title: 'Import Selesai', message: $msg, type: 'success');
        } catch (\Exception $e) {
            $this->addError('csvFile', 'Gagal memproses file: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $buId = $this->getBusinessUnitId();
        $baseQuery = ProductAccurate::where('business_unit_id', $buId);

        // Ambil data untuk dropdown filters
        $availableBrands = (clone $baseQuery)->whereNotNull('brandName')->where('brandName', '!=', '')->distinct()->pluck('brandName')->sort()->values();
        $availableCategories = (clone $baseQuery)->whereNotNull('categoryName')->where('categoryName', '!=', '')->distinct()->pluck('categoryName')->sort()->values();
        $availableProyeks = (clone $baseQuery)->whereNotNull('proyek')->where('proyek', '!=', '')->distinct()->pluck('proyek')->sort()->values();
        $availableOs = (clone $baseQuery)->whereNotNull('os')->where('os', '!=', '')->distinct()->pluck('os')->sort()->values();

        $devices = $this->buildFilteredQuery($buId)
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.buyback.device-index', compact(
            'devices',
            'availableBrands',
            'availableCategories',
            'availableProyeks',
            'availableOs'
        ));
    }
}
