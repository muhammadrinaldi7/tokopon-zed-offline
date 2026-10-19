# Wave 9 — Retail Trade-In, Buyback & Digital Device Passport API Specification

## 1. Ikhtisar Wave 9

Wave 9 memigrasikan dan memodernisasi modul transaksi tukar tambah fisik, penerimaan HP bekas pelanggan (*buyback*), mesin inspeksi *Quality Control* (QC) bertingkat, serta *Digital Device Passport* dari monolit Laravel (`App\Livewire\Zoffline\TradeIn`, `App\Livewire\Zoffline\SellPhone`, `App\Livewire\Admin\Qc\InspectionForm`, dan `App\Livewire\Zoffline\DevicePassport`) ke backend Go berkecepatan tinggi `tokopon-go`.

Modul ini memiliki 4 pilar utama:
1. **Retail Trade-In & Penilaian Tukar Tambah:** Penaksiran nilai tukar tambah HP lama konsumen ke unit upgrade (baru maupun *second*), kalkulasi potongan nilai minus fisik/fungsi berdasarkan formula rules (persentase & nominal tetap), serta pencatatan pengajuan di kasir/frontliner toko.
2. **Buyback / Sell-Phone Store Intake:** Transaksi pembelian HP bekas langsung dari pelanggan di toko fisik, registrasi otomatis data pelanggan (*walk-in customer*), otorisasi penyesuaian harga kasir, dan penuntasan pembayaran yang secara otomatis meregistrasikan IMEI ke inventaris gudang toko (`product_serial_numbers` dengan HPP = nilai taksiran dan status `Available`).
3. **QC Inspection Checklist Engine:** Mesin checklist uji kelayakan fisik & fungsional perangkat bertingkat (*Inbound*, *Etalase*, *Pre-Sale*, *Serah Terima*), evaluasi penalti bobot kegagalan, deteksi otomatis cacat fatal (*fatal failure*), perhitungan skor lulus/gagal (*verdict: pass/conditional/fail*), dan sinkronisasi otomatis status QC serial number.
4. **Digital Device Passport:** Rekam jejak digital seumur hidup (*lifetime digital history*) sebuah nomor seri/IMEI unik. Mengagregasikan seluruh siklus hidup perangkat: asal penerimaan (*buyback/supplier*), riwayat seluruh uji QC beserta detail checklist, riwayat penjualan & pembeli, hingga riwayat klaim garansi dan pergantian unit tukar.

---

## 2. Pemetaan Skema Database `zedpost-7`

Modul ini beroperasi langsung di atas skema relasional tabel MySQL `zedpost-7`:

| Tabel | Peran & Kolom Utama |
|---|---|
| `trade_ins` | Transaksi tukar tambah fisik (`user_id`, `target_product_id`, `target_product_type`, `product_variant_id`, `product_variant_type`, `old_phone_brand`, `old_phone_model`, `old_phone_ram`, `old_phone_storage`, `old_phone_minus_desc`, `appraised_value`, `status`, `imei`, `order_id`, `handled_by`). |
| `sell_phones` | Transaksi penerimaan/pembelian HP bekas (`user_id`, `product_accurate_id`, `phone_brand`, `phone_model`, `phone_ram`, `phone_storage`, `minus_desc`, `appraised_value`, `original_appraised_value`, `is_price_adjusted`, `price_adjusted_by`, `price_adjustment_reason`, `status`, `imei`, `bank_name`, `bank_account_number`, `bank_account_name`, `handled_by`, `sales_id`, `business_unit_id`, `branch_id`). |
| `buyback_devices` | Master perangkat buyback (`product_accurate_id`, `os_name`, `category_name`, `brand_name`, `buyback_tier_id`, `is_active`). |
| `buyback_tiers` | Master tingkat penaksiran & aturan minus (`name`, `rules: JSON`). |
| `qc_templates` | Template checklist QC (`business_unit_id`, `name`, `brand_id`, `device_category`, `is_default`, `items: JSON`, `max_weight_threshold`, `is_active`). |
| `device_inspections` | Catatan inspeksi fisik IMEI (`imei`, `second_product_variant_id`, `qc_template_id`, `inspectable_type`, `inspectable_id`, `label`, `checklist_results: JSON`, `passed_count`, `failed_count`, `total_items`, `verdict: pass\|conditional\|fail`, `inspector_notes`, `inspected_by`, `inspected_at`). |
| `product_serial_numbers` | Nomor seri fisik (`serial_number`, `item_no`, `status`, `warehouse_id`, `hpp`, `qc_status`, `receipt_date`). |
| `orders` & `order_items` | Riwayat penjualan perangkat ke konsumen. |
| `warranties` & `warranty_claims` | Riwayat garansi, reparasi, dan unit replacement swap. |

---

## 3. Alur Bisnis & Integritas Data

### 3.1 Transaksi Trade-In & Walk-In Customer
1. Pelanggan datang ke toko fisik ingin menukar HP lamanya dengan unit baru/second di etalase.
2. Frontliner memilih unit target dan memasukkan spesifikasi HP lama konsumen (Brand, Model, RAM/Storage, dan kondisi fisik minus).
3. Sistem menghitung taksiran nilai HP lama (`appraised_value`) dan menghitung selisih pembayaran yang harus dibayar konsumen.
4. Jika konsumen belum memiliki akun, sistem secara otomatis meregistrasikan akun walk-in customer di tabel `users` & `user_profiles`.
5. Transaksi trade-in dicatat dengan status `INSPECTING` atau `OFFER_ACCEPTED`.

### 3.2 Buyback & Auto-Intake ke Inventaris Gudang
1. Frontliner menginput data HP yang dijual pelanggan di menu Buyback.
2. Kasir/BM dapat melakukan penyesuaian harga (`is_price_adjusted = true`) disertai alasan yang tercatat di audit trail.
3. Saat inspeksi lulus dan pembayaran dicairkan (`POST /api/v1/buyback/:id/complete`):
   - Status buyback diubah menjadi `COMPLETED`.
   - Nomor seri / IMEI secara atomik diregistrasikan ke `product_serial_numbers` dengan:
     - `status = 'Available'`
     - `hpp = appraised_value`
     - `qc_status = 'Passed Inbound'`
     - `warehouse_id = warehouse tujuan toko`
   - Stok agregat pada `warehouse_stocks` otomatis bertambah 1.

### 3.3 Mesin Inspeksi QC Bertingkat
1. Frontliner/Teknisi memilih template QC yang sesuai (atau menggunakan template default perangkat).
2. Memeriksa komponen fisik & fitur (Layar LCD, Kamera Depan/Belakang, Face ID, Fingerprint, TrueTone, Mic/Speaker, Baterai, Wi-Fi/Bluetooth, dll).
3. Evaluasi otomatis:
   - Jika terdapat kegagalan pada komponen berlabel `is_fatal: true`, verdict otomatis bernilai `fail`.
   - Jika akumulasi bobot penalti melebihi `max_weight_threshold`, verdict bernilai `fail`.
   - Jika terdapat minus kecil dalam toleransi, verdict bernilai `conditional`.
   - Jika seluruh poin normal, verdict bernilai `pass`.
4. Menyimpan catatan inspeksi ke `device_inspections` dan menyinkronkan status `qc_status` di `product_serial_numbers`.

### 3.4 Digital Device Passport
1. Pengguna memasukkan nomor seri atau IMEI (`GET /api/v1/qc/passport/:imei`).
2. Endpoint mengagregasi seluruh riwayat hidup perangkat:
   - Identitas & Stok Aktif: status saat ini (`Available`, `Sold`), lokasi toko/gudang, HPP, SKU Accurate.
   - Event Intake: tanggal dan asal perangkat dibeli dari konsumen (Buyback/Trade-In).
   - Event QC Timeline: seluruh sesi inspeksi yang pernah dilakukan teknisi beserta skor dan rincian checklist.
   - Event Penjualan: tanggal perangkat terjual di POS/Order, nomor nota pesanan, nama pembeli, dan harga jual.
   - Event Purna Jual: klaim garansi, catatan perbaikan teknisi, atau pergantian unit.

---

## 4. Daftar Endpoint Wave 9 (`/api/v1/*`)

| No | Method | Endpoint | Deskripsi |
|---|---|---|---|
| 1 | `POST` | `/api/v1/trade-in` | Pengajuan transaksi trade-in toko fisik (walk-in customer) |
| 2 | `GET` | `/api/v1/trade-in` | Riwayat transaksi trade-in (filter status, search, pagination) |
| 3 | `GET` | `/api/v1/trade-in/:id` | Rincian lengkap transaksi trade-in & HP lama |
| 4 | `PATCH` | `/api/v1/trade-in/:id/status` | Update status trade-in (`INSPECTING`, `OFFER_ACCEPTED`, `COMPLETED`, `CANCELLED`) |
| 5 | `POST` | `/api/v1/buyback` | Input transaksi pembelian HP bekas pelanggan di toko |
| 6 | `GET` | `/api/v1/buyback` | Daftar transaksi buyback (filter status, IMEI, tanggal) |
| 7 | `GET` | `/api/v1/buyback/:id` | Rincian pengajuan buyback, bank transfer & hasil inspeksi |
| 8 | `POST` | `/api/v1/buyback/:id/complete` | Selesaikan pembayaran & auto-intake IMEI ke `product_serial_numbers` |
| 9 | `GET` | `/api/v1/qc/templates` | Daftar template checklist QC aktif per brand/kategori |
| 10 | `GET` | `/api/v1/qc/templates/:id` | Rincian template QC beserta bobot penalti & komponen fatal |
| 11 | `POST` | `/api/v1/qc/inspections` | Submit hasil inspeksi QC fisik & kalkulasi verdict otomatis |
| 12 | `GET` | `/api/v1/qc/inspections` | Riwayat inspeksi QC toko cabang |
| 13 | `GET` | `/api/v1/qc/inspections/:id` | Rincian hasil checklist inspeksi & catatan teknisi |
| 14 | `GET` | `/api/v1/qc/passport/:imei` | Digital Device Passport (timeline seumur hidup nomor seri/IMEI) |

---

## 5. Strategi Pengujian (Go Native Testing)
- Pengujian murni menggunakan paket bawaan Go (`testing` dan `net/http/httptest`).
- Menjamin isolasi unit test pada perhitungan nilai penaksiran, kalkulasi otomatis verdict QC, registrasi walk-in customer, dan penyusunan urutan timeline Digital Device Passport.
