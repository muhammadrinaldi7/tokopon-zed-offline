# 19 — Wave 7: Web POS Kasir Toko Offline API

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS (Wave 7)  
**Referensi Monolit Laravel:** `app/Livewire/Zoffline/Pos/` (`Pos.php`, `OpenShift.php`, `ClosingKasir.php`, `WithCheckoutAndReceipt.php`, `WithCart.php`)

---

## 1. Latar Belakang & Tujuan Wave 7

Pada monolit Laravel eksisting, kasir toko fisik menggunakan antarmuka Livewire (`/zoffline/pos`, `/zoffline/pos/open-shift`, `/zoffline/pos/closing-kasir`). Beban rendering server-side Livewire pada setiap ketukan keyboard, pencarian produk, dan pemindaian barcode menyebabkan latensi tinggi, konsumsi memori server yang boros, serta kerentanan race condition saat kasir di beberapa cabang melakukan checkout bersamaan.

**Tujuan Wave 7:**
Membangun backend REST API berkecepatan tinggi di Go (`tokopon-go`) untuk mengambil alih 100% logika bisnis Web POS kasir toko fisik. Dengan Go, latensi transaksi checkout dan verifikasi barcode dipangkas menjadi hitungan milidetik, stok dikunci secara pesimistik (`SELECT ... FOR UPDATE`), dan sistem siap diintegrasikan dengan frontend Web POS SPA modern.

---

## 2. Cakupan Fitur Web POS di Go

### A. Manajemen Shift Kasir (`/api/v1/pos/shift/*`)
1. **Pengecekan Shift Aktif (`GET /active`):** Kasir hanya boleh bertransaksi jika memiliki shift berstatus `open`.
2. **Buka Shift (`POST /open`):**
   - Menghitung modal awal fisik kasir (`starting_cash`) berdasarkan pecahan uang (100k, 50k, 20k, 10k, 5k, 2k, 1k, 500, 200, 100).
   - Validasi: kasir maksimal hanya memiliki 1 shift open dalam satu waktu dan 1 shift per hari kerja.
   - Menyimpan rincian pecahan awal ke `cashier_shift_denominations` bertipe `opening`.
3. **Tutup Shift & Rekonsiliasi Kas (`POST /close`):**
   - Kasir menghitung fisik uang tunai di laci saat closing (`actual_cash`).
   - Sistem mengakumulasi total transaksi tunai (`total_cash_sales`) dan non-tunai (`total_non_cash_sales`) selama shift.
   - `expected_cash = starting_cash + total_cash_sales`.
   - `cash_difference = actual_cash - expected_cash`.
   - Penentuan status rekonsiliasi kas:
     - `balanced`: fisik kas cocok dengan pembukuan sistem (`abs(cash_difference) < 0.01`).
     - `over`: kelebihan uang kas fisik (`cash_difference > 0`).
     - `short`: selisih kurang fisik kas / tekor (`cash_difference < 0`).
   - Menyimpan rincian pecahan penutupan ke `cashier_shift_denominations` bertipe `closing`.

### B. Katalog & Scan Stok Toko Fisik (`/api/v1/pos/products`, `/api/v1/pos/scan-sn`)
1. **Katalog Produk Toko Fisik:**
   - Filter ketat berdasarkan gudang toko tempat kasir bertugas (`users.warehouse_id`).
   - Menampilkan produk baru (`ProductVariant`) dan produk bekas (`SecondProductVariant`).
   - Pencarian cepat via nama produk, SKU, atau kategori.
2. **Pemindaian Barcode / Serial Number (IMEI):**
   - Input scanner mencari ke tabel `product_serial_numbers`.
   - Validasi: unit harus berstatus `Available` dan berlokasi di gudang kasir.
   - Validasi QC Khusus Produk Bekas (Second): memeriksa tabel `device_inspections` dengan label `QC Serah Terima` dan `verdict = pass`. Jika belum lolos QC, sistem menolak unit dimasukkan ke checkout.

### C. Pelanggan & Salesman POS (`/api/v1/pos/customers`, `/api/v1/pos/salesmen`)
1. **Lookup Pelanggan & Pendaftaran Cepat Walk-in:**
   - Kasir mencari pelanggan via nomor telepon atau nama.
   - Jika pelanggan baru datang ke toko (walk-in), sistem otomatis mendaftarkan akun `User` & `UserProfile` dengan normalisasi nomor telepon (deteksi awalan `0` / `62`).
2. **Penugasan Tenaga Penjual (`employes`):**
   - Kasir memilih satu atau beberapa salesman aktif untuk dicatat pada item pesanan (`sales_ids`) guna tracking komisi penjualan dan KPI staff.

### D. Metode Pembayaran & Perhitungan MDR (`/api/v1/pos/payment-methods`)
1. Mengambil daftar metode pembayaran kasir aktif (Tunai, EDC/Debit, QRIS, Transfer, Finance/Leasing).
2. Menghitung biaya Merchant Discount Rate (MDR %) dari `payment_method_rates` secara presisi.
3. Mendukung input `no_kontrak` untuk metode pembayaran Finance/Leasing (status dicatat `PENDING`).

### E. Checkout POS Atomik (`POST /api/v1/pos/checkout`)
Eksekusi transaksi database atomik:
1. Validasi keranjang, ketersediaan kasir, dan status shift aktif.
2. Buka transaksi database MySQL (`tx.Begin()`).
3. Penguncian stok pesimistik:
   ```sql
   SELECT stock FROM warehouse_stocks 
   WHERE warehouse_id = ? AND variant_id = ? AND variant_type = ? 
   FOR UPDATE;
   ```
4. Pengurangan kuantitas stok gudang (`stock = stock - qty`).
5. Pembaruan status Serial Number/IMEI dari `Available` langsung menjadi `Sold`.
6. Pembuatan record `orders` dengan `order_channel = 'POS'`, nomor transaksi unik (`POS-YYYYMMDD-XXXX`), status `COMPLETED`, kasir `handled_by`, dan snapshot toko.
7. Pembuatan baris `order_items` beserta nomor seri dan data HPP.
8. Pembuatan baris `order_payments` untuk setiap metode pembayaran split yang digunakan.
9. Akumulasi update penjualan pada shift kasir yang aktif (`total_cash_sales` & `total_non_cash_sales`).
10. Commit transaksi atomik database.

### F. Generator Struk Thermal ESC/POS & QZ Tray
1. **Thermal ESC/POS Formatter:** Menghasilkan payload string teks terformat untuk printer thermal lebar 40, 58, atau 80 kolom, memuat header toko, kasir, rincian produk, SN/IMEI, diskon, rincian pembayaran, dan footer nota.
2. **QZ Tray Silent Print (`POST /api/v1/pos/sign-qz`):** Endpoint penandatanganan payload digital signature SHA-512 base64 untuk pencetakan struk kasir otomatis tanpa dialog popup browser.

---

## 3. Daftar Endpoint API Wave 7

| HTTP Method | Path | Keterangan | Otorisasi |
|---|---|---|---|
| `GET` | `/api/v1/pos/shift/active` | Cek status shift kasir aktif | JWT Staff/Kasir |
| `POST` | `/api/v1/pos/shift/open` | Buka shift kasir & modal awal | JWT Staff/Kasir |
| `POST` | `/api/v1/pos/shift/close` | Tutup shift kasir & rekonsiliasi fisik kas | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/products` | Katalog produk siap jual di toko kasir | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/scan-sn` | Scan & verifikasi status SN / IMEI | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/customers` | Cari pelanggan terdaftar | JWT Staff/Kasir |
| `POST` | `/api/v1/pos/customers` | Auto-create pelanggan baru walk-in | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/salesmen` | Daftar tenaga penjual aktif cabang | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/payment-methods` | Daftar metode pembayaran kasir & MDR rates | JWT Staff/Kasir |
| `POST` | `/api/v1/pos/checkout` | Transaksi checkout kasir atomik & potong stok | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/orders` | Riwayat transaksi kasir toko fisik | JWT Staff/Kasir |
| `GET` | `/api/v1/pos/orders/:order_number/receipt` | Ambil data nota & teks ESC/POS cetak ulang | JWT Staff/Kasir |
| `POST` | `/api/v1/pos/sign-qz` | Penandatanganan request cetak digital QZ Tray | JWT Staff/Kasir |

---

## 4. Strategi Pengujian (Go Native Testing)

Sesuai aturan engineering, pengujian dilakukan menggunakan pustaka bawaan Go `testing` dan `httptest` tanpa library eksternal:
1. `pos_usecase_test.go`:
   - Pengujian pembukaan shift baru & pencegahan multiple shift open.
   - Pengujian penutupan shift dan verifikasi kalkulasi status rekonsiliasi (`balanced`, `over`, `short`).
   - Pengujian checkout POS: penguncian stok, pengurangan stok gudang, perubahan status IMEI ke `Sold`, split payment, dan kalkulasi total penjualan kasir.
   - Pengujian proteksi checkout jika stok tidak mencukupi (anti-overselling).
   - Pengujian generator teks struk thermal ESC/POS.
2. `pos_handler_test.go`:
   - Pengujian integrasi HTTP: autentikasi bearer token kasir, validasi JSON payload, HTTP status code (200, 201, 400, 401, 404, 500).
