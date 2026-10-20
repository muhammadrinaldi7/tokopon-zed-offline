# 28 — [MOD-02] Retail POS & Cashier Desk Module

**Tanggal:** 20 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/pos/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-02] Retail POS & Cashier Desk** merupakan implementasi modular monolit inti untuk sistem transaksi ritel kasir toko fisik ZedPos. Modul ini bertanggung jawab penuh atas:

1. **Siklus Hidup Shift Kasir (Shift Lifecycle & Cash Reconciliation):**
   - Buka kasir (*open shift*) dengan pencatatan modal awal dan rincian pecahan uang (*denominations*).
   - Penegakan invarian ketat: Satu kasir hanya boleh memiliki satu shift aktif per unit bisnis.
   - Tutup kasir (*close shift*) dengan rekonsiliasi kas deterministik: menghitung selisih kas fisik vs sistem (`balanced`, `over`, atau `short`).
2. **Katalog Produk & Pemindaian Serial Number (Barcode / QR / SN):**
   - Pencarian instan barang di gudang cabang aktif kasir.
   - Filter barang baru vs *second-hand* (bekas).
   - **Invarian QC Ketat:** Barang bekas/titip jual wajib memiliki status `qc_status = 'PASSED'` sebelum diizinkan masuk keranjang kasir.
3. **Transaksi Pembayaran Atomik Kasir (High-Performance POS Checkout):**
   - Transaksi database atomik ACID (`sqlx.Tx`) dengan waktu respons < 50ms.
   - Generator nomor order anti-tabrakan (*collision-free sequence*) format `POS-YYYYMMDD-XXXX`.
   - Penguncian baris pesimistik (*pessimistic row locking*) `SELECT ... FOR UPDATE` pada tabel `warehouse_stocks` dan `product_serial_numbers` untuk mencegah *overselling*.
   - Validasi total pembayaran terhadap tagihan (`ErrUnderpaid`).
   - Dukungan multi-metode pembayaran (Tunai, QRIS, Kartu Debit/Kredit EDC, Transfer Bank, Split Payment) beserta biaya MDR (*Merchant Discount Rate*).
4. **Dispatcer Antrean Asinkron Non-Blocking (Background Queue Dispatcher):**
   - Enqueue tugas sinkronisasi Faktur & Penerimaan Penjualan ke Accurate Online ERP (`job:order:sync_accurate`).
   - Enqueue tugas pengiriman struk PDF digital via WhatsApp CRM (`job:notification:whatsapp`).
   - Eksekusi antrean sepenuhnya *non-blocking*, menjamin kasir tidak pernah terhambat oleh koneksi internet atau latensi API pihak ketiga.
5. **Pencetakan Struk Thermal (ESC/POS & QZ Tray):**
   - Generator struk teks monospaced 40-kolom format ESC/POS standar printer kasir thermal 58mm/80mm.
   - Endpoint tanda tangan digital QZ Tray (`POST /api/v1/pos/sign-qz`) dengan enkripsi RSA-SHA512 untuk *silent thermal printing* dari browser tanpa dialog dialog cetak OS.
6. **Kesiapan Migrasi Database Mandiri:**
   - Telah disiapkan file DDL migrasi native Go: `migrations/000002_create_pos_and_orders_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/pos`)

```
tokopon-go/internal/modules/pos/
├── domain.go              # Entities (CashierShift, Order, OrderItem, PaymentMethod), DTOs & Interfaces
├── repository.go          # Implementasi SQL Repository: shifts, pessimistic stock lock, atomic checkout
├── usecase.go             # Logika bisnis transaksi kasir, validasi QC, rekonsiliasi kas, QZ Tray sign
├── handler.go             # Echo HTTP delivery handler (rute /api/v1/pos/*)
├── escpos.go              # Formatter Rupiah & Generator struk thermal ESC/POS 40 kolom
├── usecase_test.go        # Unit test UseCase (Shift, QC SN scan, Atomic checkout, QZ Tray sign)
└── handler_test.go        # Unit test Handler HTTP (Shift open/close & checkout API lifecycle)
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul POS | Deskripsi |
|---|---|---|
| `cashier_shifts` | INSERT, SELECT, UPDATE | Riwayat buka/tutup shift kasir dan rekap penjualan tunai/non-tunai |
| `cashier_shift_denominations` | INSERT, SELECT | Rincian pecahan uang tunai saat pembukaan dan penutupan shift |
| `daily_order_counters` | INSERT, UPDATE | Monotonic atomic sequence untuk nomor transaksi kasir bebas tabrakan |
| `orders` | INSERT, SELECT | Header transaksi penjualan kasir (`order_channel = 'POS'`) |
| `order_items` | INSERT, SELECT | Rincian item barang yang dibeli (nama produk, SN, varian, harga, subtotal) |
| `order_payments` | INSERT, SELECT | Rincian pembayaran transaksi kasir (metode bayar, amount, MDR fee) |
| `warehouse_stocks` | SELECT (FOR UPDATE), UPDATE | Pengurangan kuantitas stok gudang cabang kasir secara atomik |
| `product_serial_numbers` | SELECT (FOR UPDATE), UPDATE | Validasi ketersediaan SN, validasi status QC, dan update status menjadi 'SOLD' |
| `payment_methods` | SELECT | Master metode pembayaran kasir yang aktif |
| `customers` | SELECT, INSERT | Master pelanggan toko kasir ritel |

---

## 4. Aturan Bisnis & Penegakan Invarian Ketat (*Strict Invariants*)

### 4.1 Invarian Shift Kasir (Shift Guard)
- Kasir **wajib** memiliki shift aktif (`status = 'open'`) sebelum dapat memproses transaksi checkout apapun. Jika tidak ada shift aktif, sistem melempar error:
  ```go
  ErrNoActiveShift = errors.New("tidak ada shift kasir yang aktif, buka shift terlebih dahulu")
  ```
- Rekonsiliasi kas saat tutup shift:
  ```go
  expectedCash := shift.StartingCash + shift.TotalCashSales
  cashDiff := req.ActualCash - expectedCash
  reconciliationStatus := "balanced"
  if cashDiff > 0 {
      reconciliationStatus = "over"
  } else if cashDiff < 0 {
      reconciliationStatus = "short"
  }
  ```

### 4.2 Invarian Kualitas Perangkat Bekas (*Second-Hand QC Check*)
- Untuk produk bekas yang memiliki Serial Number, sistem memeriksa integritas status QC pada tabel `product_serial_numbers`:
  ```go
  if sn.IsSecondHand && !strings.EqualFold(sn.QCStatus, "PASSED") {
      return nil, fmt.Errorf("%w: perangkat SN %s berstatus QC '%s' (harus PASSED)",
          ErrDeviceNotPassedQC, serialNumber, sn.QCStatus)
  }
  ```

### 4.3 Invarian Transaksi Atomik & Penguncian Pesimistik
- Mencegah *race condition* dan *double-selling* kasir paralel:
  ```sql
  SELECT id, physical_stock, available_stock 
  FROM warehouse_stocks 
  WHERE warehouse_id = ? AND product_variant_id = ? 
  FOR UPDATE;
  ```
- Jika stok tidak mencukupi:
  ```go
  if curStock < item.Quantity {
      return fmt.Errorf("%w: stok barang '%s' tidak cukup (sisa %d, diminta %d)",
          ErrInsufficientStock, item.ProductName, curStock, item.Quantity)
  }
  ```

### 4.4 Generator Nomor Order Anti-Tabrakan (*Collision-Free Sequence*)
- Format nomor transaksi: `POS-YYYYMMDD-XXXX` (contoh: `POS-20261020-0001`).
- Menggunakan `INSERT INTO daily_order_counters ... ON DUPLICATE KEY UPDATE last_number = last_number + 1` yang dipadu dengan in-memory atomic counter fallback.

### 4.5 Dispatcer Antrean Non-Blocking
- Transaksi kasir langsung selesai tanpa menunggu respon pihak ketiga.
- Job `job:order:sync_accurate` dan `job:notification:whatsapp` dimasukkan ke antrean broker dengan payload JSON terkompresi.

---

## 5. Matriks Rute API Kasir

Semua endpoint kasir terproteksi oleh JWT Middleware dan dapat diakses melalui rute `/api/v1/pos/*`:

| Metode | Path | Deskripsi | Response |
|---|---|---|---|
| `GET` | `/api/v1/pos/shift/active` | Mendapatkan shift kasir yang sedang aktif | `200 OK` |
| `POST` | `/api/v1/pos/shift/open` | Membuka shift kasir baru dengan modal awal | `201 Created` |
| `POST` | `/api/v1/pos/shift/close` | Menutup shift kasir dan rekonsiliasi kas | `200 OK` |
| `GET` | `/api/v1/pos/products` | Katalog produk gudang aktif kasir (search & paginate) | `200 OK` |
| `GET` | `/api/v1/pos/scan-sn` | Pemindaian barcode/SN perangkat dengan validasi QC | `200 OK` |
| `GET` | `/api/v1/pos/customers` | Pencarian master pelanggan ritel | `200 OK` |
| `POST` | `/api/v1/pos/customers` | Pendaftaran pelanggan baru kasir | `201 Created` |
| `GET` | `/api/v1/pos/salesmen` | Daftar staf sales untuk perhitungan komisi | `200 OK` |
| `GET` | `/api/v1/pos/payment-methods` | Daftar metode pembayaran kasir yang aktif | `200 OK` |
| `POST` | `/api/v1/pos/checkout` | Eksekusi checkout transaksi atomik kasir | `201 Created` |
| `GET` | `/api/v1/pos/orders` | Riwayat transaksi kasir hari ini | `200 OK` |
| `GET` | `/api/v1/pos/orders/:order_number/receipt` | Format struk thermal ESC/POS transaksi | `200 OK` |
| `POST` | `/api/v1/pos/sign-qz` | Tanda tangan digital QZ Tray RSA untuk cetak otomatis | `200 OK` |

---

## 6. Kesiapan Migrasi Database Mandiri

Migrasi mandiri telah diisolasi pada:
- `migrations/000002_create_pos_and_orders_tables.up.sql`
- `migrations/000002_create_pos_and_orders_tables.down.sql`

Skrip mencakup pembuatan tabel `cashier_shifts`, `cashier_shift_denominations`, `daily_order_counters`, `orders`, `order_items`, `order_payments`, dan `payment_methods` dengan indeks komposit optimal (`idx_shifts_user_status`, `idx_orders_channel_branch`, `idx_orders_date`).

---

## 7. Hasil Pengujian Otomatis (*Automated Tests*)

Pengujian unit dan integrasi modul POS dijalankan via `go test ./internal/modules/pos/... -v`:

```
=== RUN   TestHandler_ShiftLifecycle_Endpoints
{"level":"info","order_id":555,"order_number":"POS-20261020-0001","message":"[POS Module] Checkout succeeded and background jobs enqueued"}
--- PASS: TestHandler_ShiftLifecycle_Endpoints (0.04s)
=== RUN   TestPosUseCase_ShiftManagement
--- PASS: TestPosUseCase_ShiftManagement (0.00s)
=== RUN   TestPosUseCase_ScanSN_And_QC
--- PASS: TestPosUseCase_ScanSN_And_QC (0.00s)
=== RUN   TestPosUseCase_Checkout_FullFlow
{"level":"info","order_id":555,"order_number":"POS-20261020-0001","message":"[POS Module] Checkout succeeded and background jobs enqueued"}
--- PASS: TestPosUseCase_Checkout_FullFlow (0.00s)
=== RUN   TestPosUseCase_SignQZ
--- PASS: TestPosUseCase_SignQZ (0.13s)
PASS
ok      tokopon-go/internal/modules/pos 2.751s
```

Pengujian seluruh suite aplikasi Go (`go test ./...`):
- Seluruh 10 paket teruji dan berstatus **PASS (100%)** tanpa regresi.
- Eksekusi binary server (`go build -v ./cmd/server`) berhasil tanpa error kompilasi.
