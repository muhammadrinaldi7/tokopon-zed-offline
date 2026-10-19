# Wave 10 — Backoffice Master Data, Promos & Store Settings API Specification

## 1. Ikhtisar Wave 10

Wave 10 memigrasikan seluruh modul pengelolaan master data backoffice, master rekanan vendor, manajemen salesman/karyawan toko, mesin promosi & preset diskon kasir, multi-unit bisnis, cabang toko, konfigurasi metode pembayaran & rate MDR, serta store settings key-value dari monolit Laravel ke backend Go `tokopon-go`.

Modul ini memiliki 4 pilar arsitektur inti:
1. **Master Katalog & Rekanan (Brands, Categories, Employees, Vendors):** Pengelolaan master merek perangkat, kategori produk, database salesman toko (`employes`) dengan integrasi ID Accurate & cabang, serta database rekanan supplier/vendor pengirim barang (`vendors`).
2. **Promosi & Diskon Kasir (Promos & Manual Discount Presets):** Setup program promosi retail (`promos`), diskon persentase dan nominal tetap, batasan kuota & periode berlaku, serta preset diskon kasir cepat (`manual_discount_presets`) untuk otorisasi diskon manual di kasir POS.
3. **Unit Bisnis Multi-Tenant & Cabang Toko (Business Units & Branches):** Pengelolaan unit bisnis multi-tenant (`business_units`), status wajib pajak (`is_taxable`), prefix nota transaksi, judul toko struk, warna brand, serta daftar cabang toko fisik (`branches`).
4. **Metode Pembayaran & Pengaturan Toko (Payment Methods, Rates & Settings):** Manajemen kanal pembayaran (`payment_methods`: TUNAI, EDC/DEBIT, QRIS, TRANSFER, KREDIT), struktur fee/MDR bertingkat (`payment_method_rates`), serta manajemen konfigurasi key-value aplikasi toko (`settings`).

---

## 2. Pemetaan Skema Database `zedpost-7`

Modul ini beroperasi langsung di atas skema relasional tabel MySQL `zedpost-7`:

| Tabel | Peran & Kolom Utama |
|---|---|
| `brands` | Master merek perangkat (`id`, `name`, `business_unit_id`). |
| `categories` | Master kategori produk (`id`, `name`, `slug`, `icon`, `business_unit_id`). |
| `employes` | Master karyawan & salesman toko (`id`, `business_unit_id`, `accurate_employee_id`, `employee_no`, `name`, `email`, `phone_number`, `position`, `is_active`, `user_id`, `branch_id`). |
| `vendors` | Master supplier rekanan (`id`, `accurate_vendor_id`, `database_source`, `vendor_no`, `vendor_name`, `email`, `phone`). |
| `promos` | Master promosi & voucher toko (`id`, `business_unit_id`, `name`, `description`, `code`, `category`, `brand_id`, `accurate_account_no`, `discount_type: fixed\|percentage`, `discount_value`, `max_discount`, `min_transaction_amount`, `min_qty`, `max_qty`, `apply_to_all_items`, `is_bundle`, `start_date`, `end_date`, `is_active`, `quota`, `used_quota`). |
| `manual_discount_presets` | Preset potongan harga kasir (`id`, `business_unit_id`, `amount`, `brand_id`, `is_active`). |
| `business_units` | Pengaturan unit bisnis multi-tenant (`id`, `code`, `customer_prefix`, `order_prefix`, `draft_prefix`, `store_title`, `receipt_show_discount`, `name`, `is_taxable`, `is_active`, `brand_color`). |
| `branches` | Cabang toko fisik (`id`, `name`, `status`, `business_unit_id`). |
| `payment_methods` | Kanal pembayaran kasir (`id`, `name`, `category`, `bank_name`, `account_number`, `account_owner`, `accurate_bank_no`, `mdr_percentage`, `is_active`, `business_unit_id`). |
| `payment_method_rates` | Rate MDR bertingkat per metode pembayaran (`id`, `payment_method_id`, `name`, `mdr_percentage`, `accurate_account_no`, `is_active`). |
| `settings` | Konfigurasi umum key-value toko (`id`, `key`, `value`, `type`). |

---

## 3. Alur Bisnis & Validasi Integritas

### 3.1 Manajemen Salesman & Karyawan Toko
- Karyawan toko (`employes`) memiliki peran penting dalam pencatatan komisi transaksi POS dan Buyback (`sales_id`).
- Backoffice dapat mengaktifkan/menonaktifkan karyawan, memetakan karyawan ke cabang toko tertentu (`branch_id`), serta menghubungkan akun login user jika ada (`user_id`).

### 3.2 Mesin Promosi & Preset Diskon Kasir
- Promosi memiliki tanggal mulai dan berakhir (`start_date` dan `end_date`), batasan kuota pemakaian (`quota` dan `used_quota`), serta syarat minimal transaksi (`min_transaction_amount`).
- Kasir POS dapat membaca daftar promosi aktif secara real-time dan memvalidasi apakah keranjang belanja memenuhi syarat promo.
- Preset diskon manual (`manual_discount_presets`) membatasi nilai potongan yang boleh diberikan kasir di kasir POS (misal potongan Rp 25.000, Rp 50.000, Rp 100.000) tanpa memerlukan supervisor override.

### 3.3 Multi-Tenant Unit Bisnis & Rate Pembayaran
- Multi-unit bisnis (`business_units`) memisahkan operasional toko retail fisik, e-commerce, dan second-hand.
- Konfigurasi kanal pembayaran (`payment_methods`) dan rate MDR (`payment_method_rates`) digunakan oleh modul POS kasir untuk menghitung biaya administrasi pembayaran non-tunai (kartu debit/kredit/QRIS).

---

## 4. Daftar Endpoint API Wave 10 (`/api/v1/*`)

| No | Method | Endpoint | Deskripsi |
|---|---|---|---|
| 1 | `GET` | `/api/v1/master/brands` | Daftar merek produk (filter unit bisnis & pencarian) |
| 2 | `POST` | `/api/v1/master/brands` | Tambah merek produk baru |
| 3 | `GET` | `/api/v1/master/categories` | Daftar kategori produk |
| 4 | `POST` | `/api/v1/master/categories` | Tambah kategori produk baru |
| 5 | `GET` | `/api/v1/master/employees` | Daftar karyawan/salesman (filter cabang & status aktif) |
| 6 | `POST` | `/api/v1/master/employees` | Tambah karyawan toko baru |
| 7 | `PUT` | `/api/v1/master/employees/:id` | Update data karyawan & cabang penugasan |
| 8 | `GET` | `/api/v1/master/vendors` | Daftar supplier/vendor rekanan pengirim barang |
| 9 | `POST` | `/api/v1/master/vendors` | Tambah data vendor baru |
| 10 | `GET` | `/api/v1/master/business-units` | Daftar unit bisnis multi-tenant |
| 11 | `GET` | `/api/v1/master/business-units/:id` | Detail konfigurasi unit bisnis |
| 12 | `GET` | `/api/v1/master/branches` | Daftar cabang toko fisik |
| 13 | `POST` | `/api/v1/master/branches` | Tambah cabang toko fisik baru |
| 14 | `GET` | `/api/v1/promos` | Daftar program promo & voucher toko aktif |
| 15 | `POST` | `/api/v1/promos` | Buat program promo / diskon baru |
| 16 | `GET` | `/api/v1/promos/:id` | Detail program promo beserta syarat transaksi |
| 17 | `PATCH` | `/api/v1/promos/:id/toggle` | Aktifkan / non-aktifkan program promo |
| 18 | `GET` | `/api/v1/promos/discount-presets` | Daftar preset potongan diskon manual kasir |
| 19 | `POST` | `/api/v1/promos/discount-presets` | Tambah preset potongan diskon manual baru |
| 20 | `GET` | `/api/v1/payment-methods` | Daftar metode pembayaran kasir & rate MDR |
| 21 | `POST` | `/api/v1/payment-methods` | Tambah kanal pembayaran baru |
| 22 | `POST` | `/api/v1/payment-methods/:id/rates` | Tambah rate MDR pada metode pembayaran |
| 23 | `GET` | `/api/v1/settings` | Ambil seluruh konfigurasi toko key-value |
| 24 | `PUT` | `/api/v1/settings` | Simpan / perbarui setting toko key-value |

---

## 5. Strategi Pengujian (Go Native Testing)
- Pengujian murni menggunakan paket standar `testing` dan `net/http/httptest`.
- Verifikasi CRUD master data, validasi duplikasi kode/nama, filtering cabang & unit bisnis, perhitungan kuota promo, dan formatting setting key-value.
