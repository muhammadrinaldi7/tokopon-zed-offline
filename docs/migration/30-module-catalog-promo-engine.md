# 30 — [MOD-04] Product Catalog & Promo Engine Module

**Tanggal:** 21 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/catalog/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-04] Product Catalog & Promo Engine** mereplikasi secara presisi mesin diskon dan promosi enterprise Tokopon (ekuivalen penuh dengan `PromoCalculatorService` pada Laravel) serta manajemen katalog master produk terpadu.

Modul ini bertanggung jawab penuh atas:

1. **Mesin Kalkulasi Promo & Diskon In-Memory (High-Performance Promo Engine):**
   - Evaluasi kelayakan promo (*eligibility check*) berdasarkan keranjang kasir (`cart`), unit bisnis (`business_unit_id`), cabang toko (`branch_id`), batas waktu aktif (`start_date` / `end_date`), dan sisa kuota (`quota > used_quota`).
   - Kalkulasi diskon utama (*main discount*): potongan nominal tetap (*fixed*) atau persentase (*percentage*) dengan batas maksimal (*max discount cap*).
   - Multiplier kuantitas (*is_multiply*): penggandaan diskon otomatis berdasarkan kelipatan kuantitas pembelian (`floor(qty / min_qty)`).
   - Alokasi diskon proporsional (*itemized proportion allocation*): diskon total didistribusikan secara bulat (*round*) ke masing-masing item keranjang belanja dengan pelacakan sisa pembulatan tanpa selisih sen/rupiah.
2. **Logika Bundling & SKU Khusus (Bundle & Tiered SKU Discounts):**
   - Diskon bundling otomatis untuk produk pendamping / hadiah saat produk pemicu utama dibeli.
   - Dukungan batas kuantitas bundling maksimal (`bundle_max_qty * multiplier`).
   - Dukungan override diskon tingkat SKU pada tabel `promo_bundle_skus` (tipe diskon, nilai diskon, dan batas maksimal per SKU bundling).
3. **Aturan Kombinasi Ketat (Strict Combinability Invariant):**
   - Jika terdapat promo dengan penanda `is_combinable = false` dan kasir memilih lebih dari 1 promo, sistem melempar `ErrPromoNotCombinable` dan menolak kalkulasi ganda.
4. **Penguncian Kuota Atomik Pra-Checkout (Atomic Quota Decrement):**
   - Penguncian baris pesimistik `SELECT ... FROM promos WHERE id IN (...) FOR UPDATE`.
   - Validasi batas kuota di detik terakhir transaksi. Jika kuota habis, transaksi ditolak seketika (`ErrPromoQuotaExceeded`).
   - Pencatatan nilai diskon riil per promo ke tabel pivot `order_promos` dan penambahan nilai `used_quota = used_quota + 1`.
5. **Preset Diskon Manual Kasir (Manual Discount Presets):**
   - Mendukung preset potongan cepat kasir (misal: 10.000, 25.000, 50.000) yang terikat pada brand tertentu atau global.
6. **Katalog Master & Taksonomi:**
   - Akses data kategori produk (`categories`) dan merek/brand (`brands`) terisolasi per unit bisnis.
7. **Kesiapan Migrasi Database Mandiri:**
   - DDL skrip migrasi native Go: `migrations/000004_create_catalog_and_promos_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/catalog`)

```
tokopon-go/internal/modules/catalog/
├── domain.go              # Entities, DTOs, typed errors, Promo, PromoSKU, PromoBundleSKU, Interfaces
├── promo_engine.go        # Pure in-memory Promo Calculator Engine (paritas 100% PromoCalculatorService Laravel)
├── repository.go          # Implementasi SQL: active promos query, eager relation load, FOR UPDATE lock, pivot insert
├── usecase.go             # Logika bisnis: evaluation, calculation, checkout quota decrement, Rupiah formatter
├── handler.go             # Echo HTTP delivery handler (rute /api/v1/pos/promos/* dan /api/v1/catalog/*)
├── promo_engine_test.go   # Unit test algoritma: Fixed, Percentage, Multiplier, Bundling, Combinability, Validation
├── usecase_test.go        # Unit test UseCase: Mock repository, Eligible Promos, Cart Calculation, Manual Presets
└── handler_test.go        # Unit test Handler HTTP: 4 endpoint (Eligible, Calculate, Manual Discounts, Catalog Promos)
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul Catalog | Deskripsi |
|---|---|---|
| `promos` | SELECT (FOR UPDATE), UPDATE | Header aturan promo, jenis diskon, kuota, periode aktif, dan aturan bundling |
| `promo_skus` | SELECT | Relasi SKU utama yang berhak mendapatkan diskon promo |
| `promo_bundle_skus` | SELECT | Relasi SKU bundling / reward pendamping beserta override diskonnya |
| `promo_branches` | SELECT | Restriksi cabang toko fisik yang berhak menjalankan promo |
| `order_promos` | INSERT | Pivot pencatatan nominal diskon yang terealisasi per promo pada sebuah pesanan |
| `manual_discount_presets` | SELECT | Preset potongan harga instan meja kasir toko |
| `categories` | SELECT | Taksonomi kategori produk toko |
| `brands` | SELECT | Master merek/brand produk terhubung per unit bisnis |
| `product_accurates` | SELECT | Master katalog produk terpadu |

---

## 4. Spesifikasi Kontrak API

### A. Operasional Kasir POS (`/api/v1/pos`)

| Method | Endpoint | Auth | Peran / Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/pos/promos/eligible` | JWT | Mengevaluasi dan menampilkan seluruh promo aktif yang eligible untuk cart kasir |
| `POST` | `/api/v1/pos/promos/calculate` | JWT | Menghitung simulasi diskon, alokasi per item keranjang, dan deteksi konflik kombinasi |
| `GET` | `/api/v1/pos/manual-discounts` | JWT | Mengambil daftar preset diskon manual meja kasir (`?brand_id=...`) |

### B. Katalog Publik & Backoffice (`/api/v1/catalog`)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/catalog/promos` | Public | Daftar promo aktif toko dengan filter kategori, pencarian, dan pagination |
| `GET` | `/api/v1/catalog/promos/:id` | Public | Detail lengkap sebuah promo beserta daftar SKU dan syarat bundling |
| `GET` | `/api/v1/catalog/categories` | Public | Daftar kategori produk aktif per unit bisnis |
| `GET` | `/api/v1/catalog/brands` | Public | Daftar merek/brand produk aktif per unit bisnis |

---

## 5. Hasil Verifikasi & Pengujian Unit (Unit Test Parity)

Modul diuji secara mandiri menggunakan testing bawaan Go (`go test -v ./internal/modules/catalog/...`):

- `TestPromoEngine_FixedDiscount` $\rightarrow$ **PASS** (Potongan fixed teralokasi presisi antar item cart)
- `TestPromoEngine_PercentageDiscountWithMaxCapping` $\rightarrow$ **PASS** (Diskon persentase ter-cap batas maksimal dan teralokasi proporsional)
- `TestPromoEngine_MultiplierLogic` $\rightarrow$ **PASS** (Kelipatan kuantitas `is_multiply` terhitung benar)
- `TestPromoEngine_SKUSpecificAndMaxQtyCapping` $\rightarrow$ **PASS** (Pembatasan SKU tertentu dan capping `max_qty`)
- `TestPromoEngine_BundlingLogic` $\rightarrow$ **PASS** (Diskon bundling pada produk pendamping dengan `bundle_max_qty`)
- `TestPromoEngine_StrictCombinabilityGuard` $\rightarrow$ **PASS** (Penolakan multi-promo jika salah satu bertanda non-combinable)
- `TestPromoEngine_ValidationBeforeCheckout` $\rightarrow$ **PASS** (Pencegahan tanggal kedaluwarsa, kuota habis, dan isolasi cabang/BU)
- `TestUseCase_GetEligiblePromos` $\rightarrow$ **PASS**
- `TestUseCase_CalculatePromos` $\rightarrow$ **PASS**
- `TestUseCase_ManualDiscountPresets` $\rightarrow$ **PASS**
- `TestHandler_GetEligiblePromos` $\rightarrow$ **PASS**
- `TestHandler_CalculatePromos` $\rightarrow$ **PASS**
- `TestHandler_GetManualDiscounts` $\rightarrow$ **PASS**
- `TestHandler_ListPromos` $\rightarrow$ **PASS**

Seluruh suite proyek `go test ./...` lulus 100% tanpa regresi.
