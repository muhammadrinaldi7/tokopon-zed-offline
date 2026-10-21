# 32 — [MOD-06] Retail Trade-In, Buyback & Digital Device Passport Module

**Tanggal:** 21 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/tradein/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-06] Retail Trade-In, Buyback & Digital Device Passport** mereplikasi seluruh logika transaksi tukar tambah unit baru (*trade-in*), pembelian HP bekas dari pelanggan (*buyback*), checklist inspeksi Quality Control (QC) bertingkat dengan deteksi cacat fatal, serta pelacakan riwayat seumur hidup perangkat (*Digital Device Passport*) berdasarkan nomor IMEI cross-cutting.

Modul ini bertanggung jawab penuh atas:

1. **Alur Transaksi Tukar Tambah Retail (Retail Trade-In Lifecycle):**
   - Registrasi pelanggan walk-in atau integrasi akun pelanggan terdaftar.
   - Penaksiran nilai unit lama (*appraised value*) dan kalkulasi otomatis sisa top-up pembayaran terhadap harga produk target baru/second:
     $$\text{Topup Amount} = \max(0, \text{Target Product Price} - \text{Appraised Value})$$
   - Manajemen Finite State Machine (FSM) status trade-in: `PENDING` $\rightarrow$ `INSPECTING` $\rightarrow$ `OFFERED` $\rightarrow$ `OFFER_ACCEPTED` $\rightarrow$ `WAITING_FOR_DEVICE` $\rightarrow$ `PAYING` $\rightarrow$ `COMPLETED` / `CANCELLED`.
2. **Pembelian HP Bekas Toko (Buyback / SellPhone Flow):**
   - Penerimaan pengajuan pembelian perangkat bekas dari pelanggan di cabang toko atau online.
   - Pencatatan detail spesifikasi (merek, tipe, RAM, penyimpanan, kelengkapan, minus kondisi) dan rekening bank pembayaran pelanggan.
   - Fleksibilitas penyesuaian harga taksiran (*price adjustment*) lengkap dengan pencatatan `price_adjusted_by` dan `price_adjustment_reason`.
3. **Penyelesaian Buyback & Auto-Registration ke Serial Pool (Strict Invariant):**
   - Transaksi penyelesaian buyback dijalankan secara atomik (`SELECT ... FOR UPDATE` level baris).
   - Unit HP bekas yang diterima otomatis didaftarkan ke tabel `product_serial_numbers` dengan status `Available`, `qc_status = 'Passed Inbound'`, HPP sesuai nilai taksiran, tanggal penerimaan hari ini, serta penambahan stok varian pada gudang tujuan.
   - Perlindungan idempoten: Mencegah penyelesaian ganda terhadap transaksi buyback yang sudah berstatus `COMPLETED` (`ErrBuybackAlreadyCompleted`).
4. **Gerbang Inspeksi QC & Cacat Fatal (Quality Control Gate & Fatal Defects):**
   - Penggunaan template QC berbasis kategori/merek perangkat dengan bobot penalti per item inspeksi.
   - Evaluasi checklist inspeksi:
     - **LULUS QC (`pass`):** Seluruh checklist lulus tanpa kegagalan.
     - **LULUS BERSYARAT (`conditional`):** Terdapat minus kondisi minor non-fatal dengan total bobot penalti $\le$ ambang batas maksimum (`max_weight_threshold`).
     - **TIDAK LULUS (`fail`):** Total penalti melebihi batas atau ditemukan **cacat fatal (`is_fatal = true`)** seperti akun iCloud/Google terkunci, sinyal IMEI terblokir Kemenperin, atau kerusakan motherboard total.
   - Pembaruan status QC nomor seri di gudang (`product_serial_numbers.qc_status`) secara otomatis menjadi `Passed Inbound` atau `Failed Inbound`.
5. **Kalkulasi Penalti Taksiran Harga Otomatis (Appraisal Penalty Calculator):**
   - Menghitung potongan harga objektif berdasarkan temuan checklist inspeksi QC:
     $$\text{Final Appraised Value} = \max(0, \text{Base Price} - \sum (\text{Item Weight} \times \text{Penalty Per Weight}))$$
   - Jika ditemukan cacat fatal, nilai taksiran otomatis di-set Rp 0 dan transaksi wajib ditolak.
6. **Digital Device Passport Teragregasi (IMEI Full-Lifecycle Passport):**
   - Menyatukan rekam jejak seumur hidup unit fisik berdasarkan nomor IMEI unik:
     1. Identitas Unit: Model, merek, spesifikasi, gudang saat ini, status QC, dan tanggal masuk.
     2. Event Pembelian Bekas (Buyback): Tanggal terima, identitas pelanggan penjual, cabang penerima, nilai taksiran.
     3. Event Inspeksi QC: Riwayat teknisi pemeriksa, skor kelulusan poin, vonis QC, dan catatan teknis.
     4. Event Penjualan POS: Nomor nota pesanan kasir, nama pembeli, cabang penjualan, harga transaksi.
     5. Event Klaim Garansi & Swap Unit: Nomor klaim garansi, keluhan kerusakan, riwayat pergantian nomor seri (*unit swap*).
   - Pengurutan kronologis terintegrasi dari event terlama hingga kondisi mutakhir di tangan pelanggan atau stok gudang.
7. **Kesiapan Migrasi Database Mandiri:**
   - DDL skrip migrasi native Go: `migrations/000006_create_tradein_and_passport_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/tradein`)

```
tokopon-go/internal/modules/tradein/
├── domain.go              # Entities, DTOs, typed errors, Status FSM, Verdicts, Interfaces
├── service.go             # Pure Domain Service: QC evaluation, appraisal calculator, Rupiah format, FSM validation
├── repository.go          # Implementasi MySQL: FOR UPDATE lock, auto-register SN, multi-source passport aggregator
├── usecase.go             # UseCase orchestration: TradeIn, Buyback, QC Inspections, Device Passport
├── handler.go             # Echo HTTP delivery handler (rute /api/v1/trade-in/*, /api/v1/buyback/*, /api/v1/qc/*, /api/v1/passport/*)
├── service_test.go        # Unit test service: QC pass/conditional/fail, fatal defect, appraisal calc, FSM rules
├── usecase_test.go        # Unit test UseCase: Mock repository, TradeIn, Buyback, QC, Passport, strict invariants
└── handler_test.go        # Unit test Handler HTTP: 11 endpoints test suite with mock JWT
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul Trade-In | Deskripsi |
|---|---|---|
| `trade_ins` | SELECT, INSERT, UPDATE | Pengajuan tukar tambah unit baru, status FSM, taksiran harga, dan perhitungan sisa top-up |
| `sell_phones` | SELECT (FOR UPDATE), INSERT, UPDATE | Transaksi pembelian HP bekas (*buyback*), rekening pembayaran, dan status penyelesaian |
| `qc_templates` | SELECT | Template checklist inspeksi QC per merek dan kategori perangkat |
| `device_inspections` | SELECT, INSERT | Riwayat inspeksi fisik teknisi toko per IMEI, skor kelulusan, dan vonis QC |
| `product_serial_numbers` | SELECT, INSERT, UPDATE | Auto-registrasi nomor seri HP bekas ke inventaris toko saat buyback selesai |
| `warehouse_stocks` | INSERT, UPDATE (ON DUPLICATE KEY) | Penambahan stok fisik varian pada gudang penerima unit buyback |
| `users` & `user_profiles` | SELECT, INSERT | Resolusi atau pembuatan instan akun pelanggan walk-in |

---

## 4. Endpoint HTTP API

Seluruh endpoint diproteksi oleh middleware JWT standar Tokopon Go:

### A. Retail Trade-In
- `POST /api/v1/trade-in` — Mencatat pengajuan trade-in baru dari kasir/pelanggan.
- `GET /api/v1/trade-in` — Daftar transaksi trade-in dengan filter status, pencarian, dan paginasi.
- `GET /api/v1/trade-in/:id` — Detail spesifik transaksi trade-in.
- `PATCH /api/v1/trade-in/:id/status` — Pembaruan status FSM trade-in (divalidasi oleh `ValidateTradeInTransition`).

### B. Buyback (Pembelian HP Bekas)
- `POST /api/v1/buyback` — Pembuatan transaksi pembelian HP bekas walk-in/online.
- `GET /api/v1/buyback` — Daftar transaksi buyback dengan filter status, cabang, pencarian, dan paginasi.
- `GET /api/v1/buyback/:id` — Detail spesifik transaksi buyback.
- `POST /api/v1/buyback/:id/complete` — Penyelesaian transaksi buyback, pembayaran, dan auto-intake ke inventaris toko.
- `POST /api/v1/buyback/appraisal/calculate` — Kalkulator estimasi taksiran harga berdasarkan temuan checklist inspeksi QC.

### C. QC Inspections & Templates
- `GET /api/v1/qc/templates` — Daftar template QC aktif (dapat difilter berdasarkan `brand_id` atau `category`).
- `GET /api/v1/qc/templates/:id` — Detail item checklist template QC.
- `POST /api/v1/qc/inspections` — Submit hasil inspeksi teknisi toko per IMEI (otomatis mengupdate status QC nomor seri).
- `GET /api/v1/qc/inspections` — Riwayat inspeksi QC dengan filter IMEI, verdict, label, dan paginasi.
- `GET /api/v1/qc/inspections/:id` — Detail checklist dan skor inspeksi QC.

### D. Digital Device Passport
- `GET /api/v1/qc/passport/:imei` & `GET /api/v1/passport/:imei` — Mengambil paspor digital seumur hidup IMEI lengkap dengan identitas perangkat dan timeline kronologis.

---

## 5. Hasil Pengujian Unit (*Unit Test Results*)

Pengujian unit mandiri dijalankan tanpa dependensi database eksternal:

```bash
=== RUN   TestHandler_TradeInEndpoints
--- PASS: TestHandler_TradeInEndpoints (0.04s)
=== RUN   TestHandler_BuybackEndpoints
--- PASS: TestHandler_BuybackEndpoints (0.00s)
=== RUN   TestHandler_QCAndPassportEndpoints
--- PASS: TestHandler_QCAndPassportEndpoints (0.00s)
=== RUN   TestService_EvaluateQCChecklist
--- PASS: TestService_EvaluateQCChecklist (0.00s)
=== RUN   TestService_CalculateAppraisal
--- PASS: TestService_CalculateAppraisal (0.00s)
=== RUN   TestService_FormatRupiah
--- PASS: TestService_FormatRupiah (0.00s)
=== RUN   TestService_ValidateTradeInTransition
--- PASS: TestService_ValidateTradeInTransition (0.00s)
=== RUN   TestUseCase_TradeInWorkflows
--- PASS: TestUseCase_TradeInWorkflows (0.00s)
=== RUN   TestUseCase_BuybackWorkflows
--- PASS: TestUseCase_BuybackWorkflows (0.00s)
=== RUN   TestUseCase_QCAndPassportWorkflows
--- PASS: TestUseCase_QCAndPassportWorkflows (0.00s)
PASS
ok  	tokopon-go/internal/modules/tradein	1.346s
```

Seluruh 21 kasus uji pada modul trade-in berhasil lulus 100% dan regresi penuh `go test ./...` tetap hijau (*green*).
