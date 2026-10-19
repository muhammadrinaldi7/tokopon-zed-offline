# Wave 8 — Store Inventory, Stock Opname & Inbound PO API Specification

## 1. Ikhtisar Wave 8

Wave 8 mereplikasi dan mengoptimalkan seluruh alur operasional gudang dan inventaris toko fisik dari monolit Laravel (`App\Livewire\Zoffline\Warehouse\CekStock`, `App\Livewire\Zoffline\StockOpname\*`, `App\Livewire\Zoffline\Inbound\*`, dan `App\Models\StockAdjustment`) menjadi high-performance Go REST API pada backend `tokopon-go`.

Modul ini mencakup 4 pilar utama manajemen logistik retail handphone & aksesoris:
1. **Cek Stok Multi-Gudang & Pelacakan IMEI:** Pencarian stok real-time antar toko/cabang fisik dan rincian nomor seri unit aktif beserta harga pokok penjualan (HPP) dan asal vendor.
2. **Inbound PO Supplier (Goods Receiving):** Penerimaan fisik pesanan barang dari supplier (`purchase_orders`), pemindaian barcode/IMEI baru secara cepat, dan penambahan kuantitas stok gudang secara otomatis.
3. **Stock Opname Toko Fisik (Multi-User Scan Counting):** Pembukaan sesi audit inventaris toko cabang dengan pembekuan (snapshot) stok sistem, pemindaian IMEI massal untuk mendeteksi unit *Matched*, *Missing*, atau *Unexpected*, serta valuasi nominal selisih rugi/lebih.
4. **Penyesuaian Stok (Stock Adjustment):** Pengajuan dan eksekusi koreksi inventaris bertingkat (barang display rusak, hilang, atau selisih administrasi) dengan validasi stok fisik dan pencatatan audit trail lengkap.

---

## 2. Pemetaan Skema Database `zedpost-7`

Modul ini beroperasi langsung di atas skema relasional tabel MySQL `zedpost-7`:

| Tabel | Peran & Kolom Utama |
|---|---|
| `warehouse_stocks` | Stok agregat per gudang (`warehouse_id`, `variant_id`, `variant_type`, `stock`). |
| `product_serial_numbers` | Nomor seri/IMEI unik (`serial_number`, `item_no`, `status`, `warehouse_id`, `hpp`, `qc_status`, `vendor_id`, `receipt_date`). |
| `product_accurates` | Master produk sinkronisasi Accurate (`item_no`, `name`, `base_price`, `has_sn`, `brandName`, `categoryName`, `proyek`). |
| `purchase_orders` | Header PO supplier (`po_number`, `vendor_id`, `po_date`, `status: PENDING\|PARTIAL\|COMPLETED`, `database_source`). |
| `purchase_order_items` | Item PO (`purchase_order_id`, `item_no`, `item_name`, `unit_price`, `quantity_ordered`, `quantity_received`). |
| `stock_opnames` | Header sesi opname cabang (`opname_number`, `business_unit_id`, `branch_id`, `warehouse_id`, `user_id`, `status: COUNTING\|COMPLETED`, `type`, `total_system_qty`, `total_physical_qty`, `total_difference_qty`, `total_loss_value`, `total_surplus_value`). |
| `stock_opname_items` | Snapshot SKU opname (`stock_opname_id`, `item_no`, `product_name`, `is_serialized`, `system_qty`, `physical_qty`, `difference_qty`, `unit_cost`, `difference_value`). |
| `stock_opname_serials` | Snapshot IMEI opname (`stock_opname_id`, `stock_opname_item_id`, `serial_number`, `status: MATCHED\|MISSING\|UNEXPECTED`, `hpp`, `scanned_at`, `scanned_by`). |
| `stock_adjustments` | Header koreksi stok fisik (`adjustment_number`, `warehouse_id`, `adjustment_type: IN\|OUT`, `item_no`, `quantity`, `unit_cost`, `reason_category`, `status: PENDING\|APPROVED`). |
| `vendors` | Rekanan supplier pengirim barang (`vendor_no`, `vendor_name`, `email`, `phone`). |

---

## 3. Alur Bisnis & Jaminan Integritas

### 3.1 Cek Stok & Breakdown Multi-Gudang
Kasir dan kepala toko dapat memeriksa stok suatu produk di seluruh jaringan toko fisik:
- Menghitung agregat stok fisik dari `warehouse_stocks` untuk setiap gudang.
- Mengelompokkan nomor seri/IMEI aktif (`status = 'Available'`) per gudang beserta informasi HPP dan tanggal terima barang.

### 3.2 Inbound Receiving PO Supplier
1. Kasir / logistik memilih nomor PO berstatus `PENDING` atau `PARTIAL`.
2. Saat barang tiba di toko, operator memindai IMEI satu per satu:
   - Validasi bahwa IMEI belum pernah terdaftar sebelumnya di `product_serial_numbers`.
   - Menambahkan IMEI baru dengan status `'Available'` dan gudang tujuan penerimaan.
   - Menginkrementasi `quantity_received` pada `purchase_order_items`.
   - Menginkrementasi `stock` pada `warehouse_stocks` gudang tujuan.
   - Jika seluruh item PO telah terpenuhi (`quantity_received >= quantity_ordered`), status PO diperbarui otomatis menjadi `'COMPLETED'`.

### 3.3 Stock Opname Berbasis Snapshot & Scanning
1. **Mulai Opname:** Branch Manager (BM) membuka sesi opname di gudang tokonya.
   - Sistem mengambil snapshot seluruh stok aktif di gudang tersebut.
   - Setiap SKU dicatat ke `stock_opname_items` (`system_qty`).
   - Setiap nomor seri yang sedang aktif (`status = 'Available'`) dicatat ke `stock_opname_serials` dengan status awal `'MISSING'`.
2. **Scanning Fisik (Multi-User):**
   - Operator memindai barcode/IMEI fisik yang ada di rak toko.
   - Jika serial ada di database snapshot: status berubah menjadi `'MATCHED'`, waktu pemindaian dan operator pencatat disimpan.
   - Jika serial tidak ada di snapshot gudang (misal salah kirim dari toko lain): dicatat sebagai `'UNEXPECTED'`.
   - Kuantitas fisik pada `stock_opname_items` bertambah secara real-time.
3. **Finalisasi & Valuasi Selisih:**
   - Menghitung selisih kuantitas (`physical_qty - system_qty`).
   - Menghitung total kerugian finansial (`total_loss_value` untuk barang hilang) dan total kelebihan (`total_surplus_value`).
   - Status sesi opname diubah menjadi `'COMPLETED'`.

### 3.4 Penyesuaian Stok (Stock Adjustment)
- Penyesuaian tipe `OUT`: Memvalidasi stok fisik mencukupi, mengunci baris `warehouse_stocks` dengan `FOR UPDATE`, dan mengurangi stok. Jika barang serialized, status nomor seri diubah menjadi `'Unavailable'`.
- Penyesuaian tipe `IN`: Menambah kuantitas `warehouse_stocks`.
- Mencatat nomor referensi unik `ADJ-YYYYMMDD-XXXX` dan riwayat user pemohon.

---

## 4. Daftar Endpoint API Wave 8 (`/api/v1/inventory/*`)

| No | Method | Endpoint | Deskripsi |
|---|---|---|---|
| 1 | `GET` | `/api/v1/inventory/stocks` | Katalog stok multi-gudang (search, pagination, filter) |
| 2 | `GET` | `/api/v1/inventory/stocks/:id/breakdown` | Rincian stok per gudang & daftar IMEI aktif per toko |
| 3 | `GET` | `/api/v1/inventory/serial-numbers/:sn` | Informasi detail riwayat & status satu nomor seri |
| 4 | `GET` | `/api/v1/inventory/inbound/po` | Daftar Purchase Order supplier (filter status & pencarian) |
| 5 | `GET` | `/api/v1/inventory/inbound/po/:id` | Rincian PO beserta progres kuantitas diterima |
| 6 | `POST` | `/api/v1/inventory/inbound/po/:po_id/scan` | Pemindaian barang masuk (tambah SN & update stok gudang) |
| 7 | `GET` | `/api/v1/inventory/stock-opname` | Riwayat sesi stock opname toko cabang |
| 8 | `POST` | `/api/v1/inventory/stock-opname` | Buka sesi opname baru & snapshot otomatis stok gudang |
| 9 | `GET` | `/api/v1/inventory/stock-opname/:id` | Detail sesi opname, ringkasan SKU & daftar IMEI ter-scan |
| 10 | `POST` | `/api/v1/inventory/stock-opname/:id/count` | Pemindaian fisik IMEI / input kuantitas non-serial |
| 11 | `POST` | `/api/v1/inventory/stock-opname/:id/finalize` | Finalisasi sesi opname & perhitungan selisih rugi/lebih |
| 12 | `POST` | `/api/v1/inventory/stock-adjustments` | Eksekusi penyesuaian stok gudang toko berotorisasi |

---

## 5. Strategi Pengujian (Go Native Testing)
Sesuai standar `tokopon-go`:
- Zero external testing/mock libraries (`testing` dan `net/http/httptest`).
- Unit test UseCase mencakup seluruh alur snapshot opname, pencocokan scan IMEI, kalkulasi selisih finansial, inbound PO progress, dan stock adjustment.
- Unit test HTTP Handler memvalidasi respons JSON, status HTTP (200, 201, 400, 404), dan otorisasi JWT.
