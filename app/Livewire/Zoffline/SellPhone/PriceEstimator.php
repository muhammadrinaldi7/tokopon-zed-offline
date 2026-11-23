<?php

namespace App\Livewire\Zoffline\SellPhone;

use App\Models\BuybackDevice;
use App\Models\BuybackTier;
use App\Models\ProductAccurate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.z')]
class PriceEstimator extends Component
{
    // Filter berjenjang (SellPhone style)
    #[Url]
    public ?string $selected_brand_id = null;

    #[Url]
    public ?string $selected_categoryName = null;

    #[Url]
    public ?string $selected_proyek = null;

    #[Url]
    public ?string $selected_model_name = null;

    // Filter tambahan & Quick Search
    public string $quick_search = '';
    public string $model_search = '';

    // Cache list opsi filter
    public array $brands = [];
    public array $available_categories = [];
    public array $available_proyek = [];
    public array $available_models = [];

    // Data Produk & Aturan Buyback
    public ?array $selected_product = null;
    public float $base_price = 0;
    public ?string $selected_tier_name = null;
    public array $device_rules = []; // Flat array
    public array $grouped_rules = []; // Category => rules

    // Pilihan kondisi yang dicentang oleh user
    public array $selected_rules = [];

    // Hasil Perhitungan Real-Time
    public float $calculated_price = 0;
    public float $total_deduction = 0;

    // Pola brand aksesoris / non-HP yang dikecualikan
    protected array $excludedBrandPatterns = [
        'JETE',
        'KIIP',
        'OLIKE',
        'OTHERS ACC',
        'ROBOT',
        'ROKER',
        'VIVAN',
        'UGREEN',
        'ANKER',
        'BASEUS',
        'BMW',
        'COSTUM',
        'HIKARU',
        'M2K',
        'OASE',
        'SULADA',
        'TITON',
        'X GUARD',
        'MAGIC GLASS',
        'NILLKIN',
        'OEM',
        'ICOLOR',
        'OTHERS',
        'INDOSCREEN',
        'HARDWEAR'
    ];

    public function mount()
    {
        $this->loadBrands();

        // Jika URL membawa parameter awal
        if ($this->selected_brand_id) {
            $this->loadCategories();
            $this->loadProyeks();
            $this->loadModels();

            if ($this->selected_model_name) {
                $this->loadSelectedProduct();
            }
        }
    }

    /**
     * Muat daftar Brand HP & Gadget dari ProductAccurate
     */
    public function loadBrands(): void
    {
        $brandsRaw = ProductAccurate::query()
            ->where(function ($query) {
                $query->whereNull('proyek')
                    ->orWhereRaw("UPPER(proyek) NOT LIKE '%ACCESSORIES%'");
            })
            ->where(function ($catQuery) {
                $catQuery->whereNull('categoryName')
                    ->orWhere(function ($c) {
                        $c->where('categoryName', 'not like', '%ANTI GORES%')
                            ->where('categoryName', 'not like', '%AKSESORIS%')
                            ->where('categoryName', 'not like', '%ACCESSORIES%')
                            ->where('categoryName', 'not like', '%SPAREPART%')
                            ->where('categoryName', 'not like', '%TEMPERED%')
                            ->where('categoryName', 'not like', '%CASE%');
                    });
            })
            ->whereNotNull('brandName')
            ->where('brandName', '!=', '')
            ->selectRaw('UPPER(TRIM(brandName)) as brand_upper, MIN(brandName) as brand_display, count(*) as count')
            ->groupBy('brand_upper')
            ->orderBy('brand_upper')
            ->get();

        $this->brands = $brandsRaw
            ->filter(fn($b) => !in_array($b->brand_upper, $this->excludedBrandPatterns))
            ->map(function ($b) {
                return [
                    'id' => $b->brand_upper,
                    'name' => $b->brand_display ?: $b->brand_upper,
                    'item_count' => (int) $b->count,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Pilihan brand berubah
     */
    public function updatedSelectedBrandId(): void
    {
        $this->selected_categoryName = null;
        $this->selected_proyek = null;
        $this->selected_model_name = null;

        $this->available_categories = [];
        $this->available_proyek = [];
        $this->available_models = [];
        $this->model_search = '';

        $this->resetProductData();

        if ($this->selected_brand_id) {
            $this->loadCategories();
            // Catatan performa: Jangan loadModels() di sini agar transisi klik brand secepat kilat (<20ms)
        }
    }

    public function loadCategories(): void
    {
        if (!$this->selected_brand_id) {
            $this->available_categories = [];
            return;
        }

        $this->available_categories = ProductAccurate::query()
            ->whereRaw('UPPER(TRIM(brandName)) = ?', [strtoupper($this->selected_brand_id)])
            ->whereNotNull('categoryName')
            ->where('categoryName', '!=', '')
            ->where(function ($c) {
                $c->where('categoryName', 'not like', '%ANTI GORES%')
                    ->where('categoryName', 'not like', '%AKSESORIS%')
                    ->where('categoryName', 'not like', '%ACCESSORIES%')
                    ->where('categoryName', 'not like', '%SPAREPART%')
                    ->where('categoryName', 'not like', '%TEMPERED%')
                    ->where('categoryName', 'not like', '%CASE%');
            })
            ->select('categoryName')
            ->distinct()
            ->orderBy('categoryName')
            ->pluck('categoryName')
            ->toArray();
    }

    /**
     * Pilihan kategori berubah
     */
    public function updatedSelectedCategoryName(): void
    {
        $this->selected_proyek = null;
        $this->selected_model_name = null;

        $this->available_proyek = [];
        $this->available_models = [];
        $this->model_search = '';

        $this->resetProductData();

        if ($this->selected_brand_id && $this->selected_categoryName) {
            $this->loadProyeks();
            // Jika kategori ini tidak memiliki pemisahan proyek khusus, langsung muat daftar modelnya
            if (empty($this->available_proyek)) {
                $this->loadModels();
            }
        }
    }

    public function loadProyeks(): void
    {
        if (!$this->selected_brand_id || !$this->selected_categoryName) {
            $this->available_proyek = [];
            return;
        }

        $this->available_proyek = ProductAccurate::query()
            ->whereRaw('UPPER(TRIM(brandName)) = ?', [strtoupper($this->selected_brand_id)])
            ->where('categoryName', $this->selected_categoryName)
            ->whereNotNull('proyek')
            ->where('proyek', '!=', '')
            ->whereRaw("UPPER(proyek) NOT LIKE '%ACCESSORIES%'")
            ->select('proyek')
            ->distinct()
            ->orderBy('proyek')
            ->pluck('proyek')
            ->toArray();
    }

    /**
     * Pilihan proyek berubah
     */
    public function updatedSelectedProyek(): void
    {
        $this->selected_model_name = null;
        $this->available_models = [];
        $this->model_search = '';

        $this->resetProductData();

        if ($this->selected_brand_id) {
            $this->loadModels();
        }
    }

    public function loadModels(): void
    {
        if (!$this->selected_brand_id) {
            $this->available_models = [];
            return;
        }

        $query = ProductAccurate::query()
            ->whereRaw('UPPER(TRIM(brandName)) = ?', [strtoupper($this->selected_brand_id)]);

        if ($this->selected_categoryName) {
            $query->where('categoryName', $this->selected_categoryName);
        }

        if ($this->selected_proyek) {
            $query->where('proyek', $this->selected_proyek);
        }

        $query->whereNotNull('name')
            ->where('name', '!=', '')
            ->where(function ($cat) {
                $cat->where('categoryName', 'not like', '%ANTI GORES%')
                    ->where('categoryName', 'not like', '%AKSESORIS%')
                    ->where('categoryName', 'not like', '%ACCESSORIES%');
            })
            ->where('name', 'not like', '%ANTI GORES%')
            ->where('name', 'not like', '%CASE %')
            ->where('name', 'not like', '%TEMPERED%');

        $this->available_models = $query
            ->orderByRaw('CASE WHEN buy_price > 0 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->get(['id', 'name', 'item_no', 'buy_price', 'base_price'])
            ->map(function ($device) {
                $buyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : $device->base_price);
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'item_no' => $device->item_no,
                    'buy_price' => $buyPrice,
                    'formatted_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
                    'is_official_buy_price' => (float) ($device->buy_price ?? 0) > 0,
                ];
            })
            ->toArray();
    }

    /**
     * Pilihan model berubah
     */
    public function updatedSelectedModelName(): void
    {
        $this->resetProductData();

        if ($this->selected_model_name) {
            $this->loadSelectedProduct();
        }
    }

    /**
     * Pilih produk langsung via ID (misal dari quick search atau daftar kartu)
     */
    public function selectProduct(int $productId): void
    {
        $product = ProductAccurate::find($productId);
        if (!$product) {
            return;
        }

        $this->selected_brand_id = strtoupper(trim((string) $product->brandName));
        $this->loadCategories();

        $this->selected_categoryName = $product->categoryName;
        $this->loadProyeks();

        $this->selected_proyek = $product->proyek;
        $this->loadModels();

        $this->selected_model_name = $product->name;
        $this->quick_search = '';

        $this->loadSelectedProduct();
    }

    /**
     * Muat detail produk, base price, serta tier buyback rules
     */
    public function loadSelectedProduct(): void
    {
        $query = ProductAccurate::query()
            ->where('name', $this->selected_model_name);

        if ($this->selected_brand_id) {
            $query->whereRaw('UPPER(TRIM(brandName)) = ?', [strtoupper($this->selected_brand_id)]);
        }

        if ($this->selected_categoryName) {
            $query->where('categoryName', $this->selected_categoryName);
        }

        if ($this->selected_proyek) {
            $query->where('proyek', $this->selected_proyek);
        }

        $product = $query->first();

        if (!$product) {
            return;
        }

        $buyPrice = (float) ($product->buy_price > 0 ? $product->buy_price : $product->base_price);
        $this->base_price = $buyPrice;

        $this->selected_product = [
            'id' => $product->id,
            'name' => $product->name,
            'item_no' => $product->item_no,
            'brand' => $product->brandName ?: '-',
            'category' => $product->categoryName ?: '-',
            'proyek' => $product->proyek ?: '-',
            'buy_price' => $buyPrice,
            'is_official' => (float) ($product->buy_price ?? 0) > 0,
        ];

        // Cari Tier Rules dari BuybackDevice
        $buybackDevice = BuybackDevice::findByProductAccurate($product);
        $tier = $buybackDevice?->tier;

        if (!$tier) {
            $tier = BuybackTier::first();
        }

        $this->selected_tier_name = $tier?->name ?? 'Standar Buyback';

        $rawRules = $tier ? $tier->getRulesByCategory() : [];

        // Aturan standar jika belum terkonfigurasi di database
        if (empty($rawRules)) {
            $rawRules = [
                'Kondisi Fisik / Body' => [
                    'is_multiple' => false,
                    'items' => [
                        ['name' => 'Mulus 98-99% (Grade A)', 'type' => 'fixed', 'value' => 0, 'description' => 'Mulus prima tanpa cacat / lecet'],
                        ['name' => 'Lecet Pemakaian Halus (Grade B)', 'type' => 'fixed', 'value' => 150000, 'description' => 'Baret halus pemakaian wajar'],
                        ['name' => 'Dent / Penyok / Cat Mengelupas (Grade C)', 'type' => 'percentage', 'value' => 10, 'description' => 'Ada bekas jatuh atau dent di sudut'],
                    ]
                ],
                'Layar / LCD' => [
                    'is_multiple' => false,
                    'items' => [
                        ['name' => 'Layar Normal & Bersih', 'type' => 'fixed', 'value' => 0, 'description' => 'Layar responsif, no shadow/garis'],
                        ['name' => 'Shadow / Burn-in Ringan', 'type' => 'percentage', 'value' => 15, 'description' => 'Ada bayangan tipis di layar'],
                        ['name' => 'Kaca Retak (Touch Normal)', 'type' => 'percentage', 'value' => 25, 'description' => 'Kaca retak tapi layar sentuh masih responsif'],
                        ['name' => 'Layar Bergaris / Mati Sebagian', 'type' => 'percentage', 'value' => 45, 'description' => 'Perlu penggantian LCD total'],
                    ]
                ],
                'Fungsi & Baterai' => [
                    'is_multiple' => true,
                    'items' => [
                        ['name' => 'Battery Health < 80% (Perlu Service)', 'type' => 'fixed', 'value' => 200000, 'description' => 'Kapasitas baterai menurun drastis'],
                        ['name' => 'Face ID / Fingerprint Rusak', 'type' => 'percentage', 'value' => 20, 'description' => 'Fitur biometrik tidak dapat membaca'],
                        ['name' => 'Kamera Bintik / Getar', 'type' => 'fixed', 'value' => 300000, 'description' => 'Kamera tidak fokus atau terdapat bercak'],
                    ]
                ],
                'Kelengkapan Unit' => [
                    'is_multiple' => true,
                    'items' => [
                        ['name' => 'Batangan (Tanpa Box Original)', 'type' => 'fixed', 'value' => 150000, 'description' => 'Tidak menyertakan dusbook asli'],
                        ['name' => 'Tanpa Kabel & Kepala Charger', 'type' => 'fixed', 'value' => 100000, 'description' => 'Hanya membawa unit HP saja'],
                    ]
                ]
            ];
        }

        $flat = [];
        $grouped = [];

        foreach ($rawRules as $catName => $data) {
            $isMultiple = (bool) ($data['is_multiple'] ?? false);
            $items = [];

            foreach ($data['items'] ?? [] as $idx => $item) {
                $ruleObj = [
                    'key' => Str::slug($catName) . '_' . $idx,
                    'category' => $catName,
                    'is_multiple' => $isMultiple,
                    'name' => $item['name'] ?? '',
                    'type' => $item['type'] ?? 'fixed',
                    'value' => (float) ($item['value'] ?? 0),
                    'description' => $item['description'] ?? '',
                ];

                $flat[] = $ruleObj;
                $items[] = $ruleObj;
            }

            $grouped[$catName] = [
                'is_multiple' => $isMultiple,
                'items' => $items,
            ];
        }

        $this->device_rules = $flat;
        $this->grouped_rules = $grouped;
        $this->selected_rules = [];

        // Hitung estimasi awal
        $this->calculatePrice();
    }

    /**
     * Toggle checklist kondisi unit
     */
    public function toggleRule(string $ruleKey, string $category, bool $isMultiple): void
    {
        if ($isMultiple) {
            // Checkbox multi-select
            if (!empty($this->selected_rules[$ruleKey])) {
                unset($this->selected_rules[$ruleKey]);
            } else {
                $this->selected_rules[$ruleKey] = true;
            }
        } else {
            // Radio button single-select
            if (($this->selected_rules[$category] ?? null) === $ruleKey) {
                unset($this->selected_rules[$category]);
            } else {
                $this->selected_rules[$category] = $ruleKey;
            }
        }

        $this->calculatePrice();
    }

    /**
     * Hitung harga taksiran riil
     */
    public function calculatePrice(): void
    {
        $price = $this->base_price;
        $totalDeduction = 0;
        $rulesByKey = collect($this->device_rules)->keyBy('key');

        foreach ($this->selected_rules as $key => $value) {
            $ruleId = null;
            if (is_bool($value) && $value) {
                $ruleId = $key;
            } elseif (is_string($value) && !empty($value)) {
                $ruleId = $value;
            }

            if ($ruleId && ($rule = $rulesByKey->get($ruleId))) {
                $type = $rule['type'];
                $val = (float) $rule['value'];

                $deduction = ($type === 'fixed')
                    ? $val
                    : ($this->base_price * ($val / 100));

                $totalDeduction += $deduction;
                $price -= $deduction;
            }
        }

        if ($price < 0) {
            $price = 0;
        }

        $this->total_deduction = $totalDeduction;
        $this->calculated_price = $price;
    }

    /**
     * Reset semua kondisi yang diceklis (kembali ke harga dasar mulus)
     */
    public function resetConditionRules(): void
    {
        $this->selected_rules = [];
        $this->calculatePrice();

        $this->dispatch('toast', [
            'type' => 'info',
            'title' => 'Kondisi Direset',
            'message' => 'Semua checklist kondisi telah dikembalikan ke kondisi awal (Grade A Mulus).'
        ]);
    }

    /**
     * Reset seluruh formulir pemilihan perangkat
     */
    public function resetAll(): void
    {
        $this->selected_brand_id = null;
        $this->selected_categoryName = null;
        $this->selected_proyek = null;
        $this->selected_model_name = null;
        $this->quick_search = '';
        $this->model_search = '';

        $this->available_categories = [];
        $this->available_proyek = [];
        $this->available_models = [];

        $this->resetProductData();
    }

    private function resetProductData(): void
    {
        $this->selected_product = null;
        $this->base_price = 0;
        $this->selected_tier_name = null;
        $this->device_rules = [];
        $this->grouped_rules = [];
        $this->selected_rules = [];
        $this->calculated_price = 0;
        $this->total_deduction = 0;
    }

    /**
     * Dapatkan label kondisi-kondisi yang sedang dipilih (tanpa menampilkan harga pengurang)
     */
    public function getSelectedConditionLabels(): array
    {
        $labels = [];
        $rulesByKey = collect($this->device_rules)->keyBy('key');

        foreach ($this->selected_rules as $key => $value) {
            $ruleId = null;
            if (is_bool($value) && $value) {
                $ruleId = $key;
            } elseif (is_string($value) && !empty($value)) {
                $ruleId = $value;
            }

            if ($ruleId && ($rule = $rulesByKey->get($ruleId))) {
                // Jangan sertakan jika opsi merupakan "Mulus" atau "Layar Normal" bernilai 0
                if ((float) $rule['value'] > 0 || !str_contains(strtolower($rule['name']), 'mulus')) {
                    $labels[] = $rule['name'];
                }
            }
        }

        return $labels;
    }

    /**
     * Generate teks format WhatsApp / Customer Note
     */
    public function getEstimateTextProperty(): string
    {
        if (!$this->selected_product) {
            return '';
        }

        $conditions = $this->getSelectedConditionLabels();
        $conditionText = count($conditions) > 0
            ? implode("\n- ", $conditions)
            : 'Mulus Prima 99% (Tanpa Minus)';

        $modelName = $this->selected_product['name'] ?? '-';
        $brandName = $this->selected_product['brand'] ?? '-';
        $categoryName = $this->selected_product['category'] ?? '';
        $proyek = $this->selected_product['proyek'] ?? '';

        $brandInfo = $brandName;
        if (!empty($proyek) && $proyek !== '-') {
            $brandInfo .= " ({$proyek})";
        } elseif (!empty($categoryName) && $categoryName !== '-') {
            $brandInfo .= " ({$categoryName})";
        }

        $formattedPrice = 'Rp ' . number_format($this->calculated_price, 0, ',', '.');

        return "*ESTIMASI HARGA BELI HP*\n" .
            " *Model:* {$modelName}\n" .
            " *Merek:* {$brandInfo}\n\n" .
            " *Kondisi Unit:*\n- {$conditionText}\n\n" .
            " *Estimasi Harga Beli:* {$formattedPrice}\n\n" .
            "_Catatan: Harga di atas adalah taksiran estimasi awal. Harga final ditentukan setelah pengecekan fisik langsung di toko TokoPon._";
    }

    /**
     * Dapatkan hasil pencarian cepat (Quick Search)
     */
    public function getQuickSearchResultsProperty(): array
    {
        $q = trim($this->quick_search);
        if (strlen($q) < 2) {
            return [];
        }

        $query = ProductAccurate::query()
            ->where(function ($query) {
                $query->whereNull('proyek')
                    ->orWhereRaw("UPPER(proyek) NOT LIKE '%ACCESSORIES%'");
            })
            ->where(function ($catQuery) {
                $catQuery->whereNull('categoryName')
                    ->orWhere(function ($c) {
                        $c->where('categoryName', 'not like', '%ANTI GORES%')
                            ->where('categoryName', 'not like', '%AKSESORIS%')
                            ->where('categoryName', 'not like', '%ACCESSORIES%')
                            ->where('categoryName', 'not like', '%SPAREPART%')
                            ->where('categoryName', 'not like', '%TEMPERED%')
                            ->where('categoryName', 'not like', '%CASE%');
                    });
            })
            ->where('name', 'not like', '%ANTI GORES%')
            ->where('name', 'not like', '%CASE %')
            ->where('name', 'not like', '%TEMPERED%');

        $words = explode(' ', $q);
        $query->where(function ($sub) use ($words) {
            foreach ($words as $w) {
                if (trim($w) !== '') {
                    $sub->where('name', 'like', '%' . trim($w) . '%');
                }
            }
        });

        return $query
            ->orderByRaw('CASE WHEN buy_price > 0 THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'name', 'brandName', 'categoryName', 'proyek', 'buy_price', 'base_price'])
            ->map(function ($device) {
                $buyPrice = (float) ($device->buy_price > 0 ? $device->buy_price : $device->base_price);
                return [
                    'id' => $device->id,
                    'name' => $device->name,
                    'brand' => $device->brandName ?: '-',
                    'category' => $device->categoryName ?: '-',
                    'proyek' => $device->proyek ?: '-',
                    'buy_price' => $buyPrice,
                    'formatted_price' => 'Rp ' . number_format($buyPrice, 0, ',', '.'),
                    'is_official' => (float) ($device->buy_price ?? 0) > 0,
                ];
            })
            ->toArray();
    }

    /**
     * Dapatkan model yang difilter dengan kata kunci di input model_search
     */
    public function getFilteredModelsProperty(): array
    {
        if (empty($this->model_search)) {
            return $this->available_models;
        }

        $search = strtolower(trim($this->model_search));
        return array_values(array_filter($this->available_models, function ($m) use ($search) {
            return str_contains(strtolower($m['name']), $search);
        }));
    }

    public function render()
    {
        return view('livewire.zoffline.sell-phone.price-estimator', [
            'selected_brand_id' => $this->selected_brand_id,
            'selected_categoryName' => $this->selected_categoryName,
            'selected_proyek' => $this->selected_proyek,
            'selected_model_name' => $this->selected_model_name,
            'quick_search' => $this->quick_search,
            'model_search' => $this->model_search,
            'brands' => $this->brands,
            'available_categories' => $this->available_categories,
            'available_proyek' => $this->available_proyek,
            'available_models' => $this->available_models,
            'selected_product' => $this->selected_product,
            'base_price' => $this->base_price,
            'selected_tier_name' => $this->selected_tier_name,
            'grouped_rules' => $this->grouped_rules,
            'selected_rules' => $this->selected_rules,
            'calculated_price' => $this->calculated_price,
            'total_deduction' => $this->total_deduction,
            'quickSearchResults' => $this->quickSearchResults,
            'filteredModels' => $this->filteredModels,
            'estimateText' => $this->estimateText,
            'selectedConditionLabels' => $this->getSelectedConditionLabels(),
        ]);
    }
}
