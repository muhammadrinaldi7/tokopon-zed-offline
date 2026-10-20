# 29 — [MOD-03] Store Inventory, Multi-Warehouse & Stock Opname Module

**Tanggal:** 20 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/inventory/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-03] Store Inventory, Multi-Warehouse & Stock Opname** merupakan pilar utama manajemen persediaan barang pada backend ZedPos Go. Modul ini bertanggung jawab penuh atas:

1. **Visibilitas Stok Multi-Gudang (Multi-Warehouse Stock Visibility):**
   - Agregasi stok realtime per produk di seluruh gudang unit bisnis.
   - Rincian stok per gudang (*stock breakdown*) dilengkapi pembedaan gudang cabang aktif kasir vs gudang lainnya.
   - Penelusuran status ketersediaan nomor seri / IMEI (`Available`, `Reserved`, `Sold`, `Unavailable`, `Damaged`).
2. **Penerimaan Barang Masuk Pemasok (Inbound Purchase Order & Barcode Scan):**
   - Penerimaan barang bertahap per baris item PO dengan verifikasi kuantitas (`quantity_received <= quantity_ordered`).
   - Pendaftaran otomatis nomor seri baru ke dalam pool `product_serial_numbers` dengan status default `Available` dan `qc_status = 'PASSED'`.
   - Pemutakhiran otomatis stok gudang di `warehouse_stocks` dan status PO (`PARTIAL` atau `COMPLETED`).
   - Pencegahan scan nomor seri ganda (*duplicate serial prevention*).
3. **Siklus Hidup Stock Opname Fisik Toko (Physical Stock Opname Lifecycle):**
   - Pembukaan sesi opname (*Start Opname*) dengan snapshot otomatis kuantitas stok sistem dan daftar nomor seri aktif di gudang terkait.
   - **Invarian Opname Ketat:** Hanya boleh ada 1 sesi opname aktif berstatus `COUNTING` pada satu gudang dalam satu waktu (`ErrActiveOpnameExists`).
   - Pencatatan fisik barang bernomor seri:
     - Nomor seri terdaftar di snapshot $\rightarrow$ status `MATCHED`.
     - Nomor seri ganda di-scan $\rightarrow$ ditolak (`ErrSerialAlreadyScanned`).
     - Nomor seri tidak tercatat di snapshot awal (nyasar dari gudang lain) $\rightarrow$ status `UNEXPECTED`.
   - Pencatatan fisik aksesoris (non-serial) dengan penghitungan kuantitas fisik langsung.
   - Finalisasi sesi opname: mengunci sesi ke `COMPLETED`, menghitung selisih kuantitas (`difference_qty`), serta nilai rugi (*loss value*) dan nilai lebih (*surplus value*).
4. **Penyesuaian Stok Terotorisasi (Authorized Stock Adjustment):**
   - Penyesuaian masuk (`IN`) dan keluar (`OUT`) dengan pencatatan kategori alasan audit (`DAMAGED`, `LOST`, `FOUND`, `CORRECTION`).
   - **Invarian Anti-Stok Negatif:** Penyesuaian `OUT` memeriksa stok tersedia dengan penguncian pesimistik `SELECT ... FOR UPDATE` pada `warehouse_stocks` (`ErrInsufficientStock`).
   - Penyesuaian nomor seri: mengubah status nomor seri menjadi `Unavailable` saat terjadi penyesuaian berkurang.
   - Generator nomor penyesuaian anti-tabrakan: format `ADJ-YYYYMMDD-XXXX`.
5. **Kesiapan Migrasi Database Mandiri:**
   - DDL skrip migrasi native Go: `migrations/000003_create_inventory_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/inventory`)

```
tokopon-go/internal/modules/inventory/
├── domain.go              # Entities, DTOs, typed errors, dan Repository/UseCase interfaces
├── repository.go          # Implementasi SQL Repository: pessimistic locking, snapshot, PO receive, opname
├── usecase.go             # Logika bisnis: opname concurrency guard, anti-negative stock, rupiah format
├── handler.go             # Echo HTTP delivery handler (rute /api/v1/inventory/* terproteksi JWT)
├── usecase_test.go        # Unit test UseCase (Stocks, PO inbound scan, Opname lifecycle, Adjustments)
└── handler_test.go        # Unit test Handler HTTP (8 endpoint inventaris teruji sukses)
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul Inventory | Deskripsi |
|---|---|---|
| `warehouses` | SELECT | Master gudang toko, cabang fisik, dan penanda toko online (`is_online_store`) |
| `warehouse_stocks` | SELECT (FOR UPDATE), INSERT, UPDATE | Kuantitas stok fisik dan available stok per gudang |
| `product_serial_numbers` | SELECT, INSERT, UPDATE | Pool nomor seri/IMEI barang dagang dan riwayat status siklus hidupnya |
| `purchase_orders` | SELECT, UPDATE | Header transaksi pengadaan barang dari supplier / Accurate Online |
| `purchase_order_items` | SELECT (FOR UPDATE), UPDATE | Detail kuantitas dipesan vs diterima per item pengadaan |
| `stock_opnames` | INSERT, SELECT, UPDATE | Header sesi perhitungan fisik barang toko dan total nilai selisih |
| `stock_opname_items` | INSERT, SELECT, UPDATE | Snapshot dan pencatatan fisik stok per SKU |
| `stock_opname_serials` | INSERT, SELECT, UPDATE | Snapshot status nomor seri (`MATCHED`, `MISSING`, `UNEXPECTED`) |
| `stock_adjustments` | INSERT, SELECT | Catatan audit penyesuaian stok masuk/keluar resmi toko |

---

## 4. Aturan Bisnis & Penegakan Invarian Ketat (*Strict Invariants*)

### 4.1 Invarian Concurrency Stock Opname (Active Opname Guard)
- Sistem mencegah terbukanya dua sesi opname aktif yang bersamaan di gudang yang sama:
  ```go
  active, err := u.repo.GetActiveCountingOpname(ctx, warehouseID)
  if active != nil {
      return nil, fmt.Errorf("%w: nomor sesi '%s'", ErrActiveOpnameExists, active.OpnameNumber)
  }
  ```

### 4.2 Invarian FSM Nomor Seri Stock Opname
- Nomor seri di gudang difoto (*snapshot*) saat pembukaan sesi opname dengan status default `MISSING`.
- Saat operator kasir/gudang memindai barcode nomor seri:
  - Jika nomor seri ada di snapshot dan belum pernah di-scan $\rightarrow$ Berubah menjadi `MATCHED`.
  - Jika nomor seri sudah pernah di-scan pada sesi ini $\rightarrow$ Ditolak dengan `ErrSerialAlreadyScanned`.
  - Jika nomor seri ditemukan fisik di gudang tetapi tidak ada pada database stok gudang ini $\rightarrow$ Dicatat sebagai `UNEXPECTED`.

### 4.3 Invarian Zero-Overselling & Anti-Stok Negatif pada Penyesuaian
- Setiap penyesuaian tipe `OUT` melakukan penguncian pesimistik pada baris stok:
  ```sql
  SELECT id, stock FROM warehouse_stocks 
  WHERE warehouse_id = ? AND variant_id = ? 
  FOR UPDATE;
  ```
- Jika kuantitas fisik di database kurang dari kuantitas penyesuaian yang diminta, transaksi ditolak dan dibatalkan:
  ```go
  if currentStock < adj.Quantity {
      return fmt.Errorf("%w (sisa: %d, diminta: %d)", ErrInsufficientStock, currentStock, adj.Quantity)
  }
  ```

### 4.4 Invarian Batas Penerimaan Inbound Purchase Order
- Inbound scan tidak boleh melebihi kuantitas yang dipesan:
  ```go
  if item.QuantityReceived >= item.QuantityOrdered {
      return nil, fmt.Errorf("%w: kuantitas diterima (%d) sudah mencapai pesanan (%d)",
          ErrPOItemAlreadyFulfilled, item.QuantityReceived, item.QuantityOrdered)
  }
  ```

---

## 5. Matriks Rute API Inventaris

Seluruh endpoint inventaris terproteksi oleh JWT Middleware dan dapat diakses melalui rute `/api/v1/inventory/*`:

| Metode | Path | Deskripsi | Response |
|---|---|---|---|
| `GET` | `/api/v1/inventory/stocks` | Daftar stok inventaris (filter `warehouse_id`, `search`, paginasi) | `200 OK` |
| `GET` | `/api/v1/inventory/stocks/:id/breakdown` | Rincian stok per gudang dan daftar serial number aktif | `200 OK` |
| `GET` | `/api/v1/inventory/serial-numbers/:sn` | Informasi detail status dan HPP nomor seri/IMEI | `200 OK` |
| `GET` | `/api/v1/inventory/inbound/po` | Daftar purchase order dari supplier/Accurate | `200 OK` |
| `GET` | `/api/v1/inventory/inbound/po/:id` | Detail purchase order beserta sisa barang yang belum diterima | `200 OK` |
| `POST` | `/api/v1/inventory/inbound/po/:po_id/scan` | Pemindaian barcode inbound untuk penerimaan barang | `201 Created` |
| `GET` | `/api/v1/inventory/stock-opname` | Riwayat sesi perhitungan stock opname | `200 OK` |
| `POST` | `/api/v1/inventory/stock-opname` | Membuka sesi stock opname baru dan snapshot otomatis | `201 Created` |
| `GET` | `/api/v1/inventory/stock-opname/:id` | Detail sesi opname, item breakdown, dan status serial number | `200 OK` |
| `POST` | `/api/v1/inventory/stock-opname/:id/count` | Input penghitungan fisik (scan SN atau kuantitas aksesoris) | `200 OK` |
| `POST` | `/api/v1/inventory/stock-opname/:id/finalize` | Finalisasi sesi opname dan perhitungan nilai selisih kerugian/kelebihan | `200 OK` |
| `POST` | `/api/v1/inventory/stock-adjustments` | Eksekusi penyesuaian stok masuk (`IN`) atau keluar (`OUT`) | `201 Created` |

---

## 6. Kesiapan Migrasi Database Mandiri

Migrasi mandiri telah diisolasi pada:
- `migrations/000003_create_inventory_tables.up.sql`
- `migrations/000003_create_inventory_tables.down.sql`

Skrip mencakup pembuatan tabel `warehouses`, `warehouse_stocks`, `product_serial_numbers`, `purchase_orders`, `purchase_order_items`, `stock_opnames`, `stock_opname_items`, `stock_opname_serials`, dan `stock_adjustments` dengan foreign key dan indeks komposit yang optimal.

---

## 7. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian unit dan integrasi modul Inventory dijalankan via `go test ./internal/modules/inventory/... -v`:

```
=== RUN   TestHandler_InventoryEndpoints
--- PASS: TestHandler_InventoryEndpoints (0.04s)
=== RUN   TestInventoryUseCase_StocksAndBreakdown
--- PASS: TestInventoryUseCase_StocksAndBreakdown (0.00s)
=== RUN   TestInventoryUseCase_SerialNumberDetail
--- PASS: TestInventoryUseCase_SerialNumberDetail (0.00s)
=== RUN   TestInventoryUseCase_PurchaseOrdersAndInboundScan
--- PASS: TestInventoryUseCase_PurchaseOrdersAndInboundScan (0.00s)
=== RUN   TestInventoryUseCase_StockOpname_FullLifecycle
--- PASS: TestInventoryUseCase_StockOpname_FullLifecycle (0.00s)
=== RUN   TestInventoryUseCase_StockAdjustment
--- PASS: TestInventoryUseCase_StockAdjustment (0.00s)
PASS
ok      tokopon-go/internal/modules/inventory 2.263s
```

Pengujian seluruh suite aplikasi Go (`go test ./...`):
- Seluruh 11 paket teruji dan berstatus **PASS (100%)** tanpa regresi.
- Eksekusi binary server (`go build -v ./cmd/server`) berhasil tanpa error kompilasi.
