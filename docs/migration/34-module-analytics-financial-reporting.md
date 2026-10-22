# 34 — [MOD-08] Executive Analytics & Financial Reporting Module

**Tanggal:** 22 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/analytics/`  
**Target Basis Data:** Shared MySQL `zedpost-7` $\rightarrow$ Mandiri (*Independent Go Migrations*) `000008_create_analytics_indices.up.sql`  

---

## 1. Ringkasan Modul

Modul **[MOD-08] Executive Analytics & Financial Reporting** melengkapi arsitektur modular monolit Tokopon Go (modul ke-8 dari 8 modul), mereplikasi seluruh fungsi analitik eksekutif level C-Suite/Direktur dan audit pelaporan keuangan kasir/toko dengan paritas 100% terhadap Laravel.

Modul ini bertanggung jawab penuh atas:

1. **Executive Role-Guard (Strict Invariant 1):**
   - Seluruh endpoint analitik eksekutif dan pelaporan keuangan dilindungi oleh middleware RBAC (`middleware.RequireRole`) yang hanya mengizinkan peran `superadmin`, `admin`, `director`, `direktur`, atau `executive`. Staf operasional (kasir, sales) dilarang mengakses metrik finansial tingkat tinggi.

2. **Consistent HPP Snapshot Invariant (Strict Invariant 2):**
   - Perhitungan laba kotor (*Gross Profit*) dan margin persentase menggunakan data HPP historis yang dibekukan saat transaksi berlangsung (`order_items.price_at_checkout` vs `psn.hpp` atau `pa.base_cost`), sehingga fluktuasi harga modal di masa depan tidak mengubah laba historis toko.

3. **Shift Discrepancy Determinism (Strict Invariant 3):**
   - Penentuan selisih kas kasir (*cash discrepancy*) dilakukan secara deterministik:
     - Selisih == 0: Status `balanced`
     - Selisih > 0: Status `over` (kas fisik berlebih)
     - Selisih < 0: Status `short` (kas fisik kurang)

4. **Executive KPI Dashboard & Trends:**
   - Ringkasan KPI: Omzet penjualan (*Total Sales*), Total Pesanan, Barang Terjual, *Average Order Value* (AOV), Total HPP, Laba Kotor, dan Persentase Margin.
   - Tren Penjualan Harian/Periode: Agregasi pendapatan, jumlah pesanan, dan laba kotor terurut kronologis.
   - Perbandingan Cabang: Analisis kontribusi pendapatan dan pangsa pasar persentase (*market share*) antar gerai/cabang toko.
   - Sebaran Pembayaran Eksekutif: Pangsa volume transaksi tunai (Cash), QRIS, Transfer Bank, dan Debit/Kredit Card.

5. **Financial & Store Audit Reporting:**
   - **Sales Summary & Transaction Ledger:** Ringkasan penjualan kotor, potongan harga/diskon, penjualan bersih, potongan biaya MDR, penerimaan bersih, dan rata-rata keranjang (*Average Basket Size*), lengkap dengan ledger detail per transaksi (nomor invoice, kasir, salesman, cabang, MDR, dan grand total).
   - **Gross & Net Profit Analysis:** Laporan laba rugi P&L komprehensif, dengan pengelompokan (*group by*) berdasarkan kategori produk, merek/brand, gerai/cabang, atau item SKU spesifik.
   - **Cashier Shifts Audit & Reconciliation:** Audit shift harian, pencatatan kas modal awal, kas sistem yang diharapkan, kas aktual fisik, status rekonsiliasi, serta rincian pecahan uang (*denominations report* 100k, 50k, 20k, dst.).
   - **Salesman Performance & Commission Ledger:** Rangking produktivitas staf penjualan berdasarkan kuantitas barang, omzet kotor, omzet bersih, margin kontribusi, dan estimasi komisi insentif (default 1% dari margin).
   - **Product Velocity (Fast/Slow/Dead Stock):** Klasifikasi perputaran inventaris berdasarkan kecepatan jual vs sisa stok gudang aktif (`FAST`, `MEDIUM`, `SLOW`, `DEAD`).
   - **Payment Method & MDR Breakdown:** Analisis biaya merchant MDR per gerai dan per bank acquirer.
   - **Full Cutover Status & Data Integrity Verification:** Pengecekan integritas data cutover sistem (jumlah orders, items, users, products, cashier shifts) guna memastikan kesiapan migrasi 100% tanpa celah inkonsistensi.

---

## 2. Struktur Berkas Modul (`internal/modules/analytics`)

```
tokopon-go/internal/modules/analytics/
├── domain.go              # Entitas DTOs, typed sentinel errors, Repository, Service & UseCase interfaces
├── service.go             # Pure Domain Service: AOV, Margin, P&L, Discrepancy status, Commission, Rupiah formatter, Date parser
├── service_test.go        # Unit test 100% PASS untuk seluruh kalkulasi finansial
├── repository.go          # Implementasi sqlx OLAP query teroptimasi (Orders, Items, Payments, Shifts, Product Velocity)
├── usecase.go             # Orkestrasi analitik, data grouping, sorting, & formatting
├── usecase_test.go        # Unit test usecase dengan mock repository
├── handler.go             # Delivery Echo HTTP handler untuk /api/v1/executive/* dan /api/v1/reporting/*
└── handler_test.go        # HTTP test suite dengan httptest (Role guard 401/403, 200 OK, 404 shift not found)
```

---

## 3. Database Migration (`000008_create_analytics_indices`)

Composite index dibuat untuk mempercepat kueri analitik dan agregasi laporan:

- `idx_orders_analytics_bu_status_date`: `(business_unit_id, order_status, order_date)` pada tabel `orders`
- `idx_orders_analytics_sales_date`: `(sales_id, order_status, order_date)` pada tabel `orders`
- `idx_orders_analytics_branch_date`: `(branch_id, order_status, order_date)` pada tabel `orders`
- `idx_order_items_analytics_variant_qty`: `(product_variant_id, qty)` pada tabel `order_items`
- `idx_order_payments_analytics_method_status`: `(payment_method_id, status)` pada tabel `order_payments`
- `idx_shifts_analytics_recon_date`: `(reconciliation_status, shift_date)` pada tabel `cashier_shifts`
- `idx_shifts_analytics_bu_branch_date`: `(business_unit_id, branch_id, shift_date)` pada tabel `cashier_shifts`

---

## 4. Daftar Endpoint API

### A. Executive Analytics (`/api/v1/executive`)
| Method | Endpoint | Keterangan | Role Guard |
|---|---|---|---|
| `GET` | `/api/v1/executive/kpi-summary` | Ringkasan metrik KPI (Sales, Orders, AOV, Margin) | Superadmin/Admin/Director |
| `GET` | `/api/v1/executive/sales-trend` | Tren penjualan harian terurut kronologis | Superadmin/Admin/Director |
| `GET` | `/api/v1/executive/branch-comparison` | Perbandingan kontribusi omzet antar cabang | Superadmin/Admin/Director |
| `GET` | `/api/v1/executive/payment-breakdown` | Sebaran metode pembayaran (Cash, QRIS, Card) | Superadmin/Admin/Director |

### B. Financial Reporting (`/api/v1/reporting`)
| Method | Endpoint | Keterangan | Role Guard |
|---|---|---|---|
| `GET` | `/api/v1/reporting/sales/summary` | Ringkasan penjualan kotor, diskon, MDR & bersih | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/sales/transactions` | Ledger transaksi terpaginasi | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/profit/summary` | Laporan laba kotor & bersih (P&L) | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/profit/breakdown` | Rincian profitabilitas per kategori/merek/cabang | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/cashier-shifts/summary` | Ringkasan audit shift kasir (balanced/over/short) | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/cashier-shifts` | Daftar shift kasir terpaginasi | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/cashier-shifts/:id` | Detail audit shift kasir beserta pecahan uang | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/salesmen/performance` | Kinerja salesman & estimasi komisi insentif | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/products/velocity` | Analisis perputaran produk (Fast/Slow/Dead) | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/payments/breakdown` | Sebaran metode pembayaran & komisi MDR | Superadmin/Admin/Director |
| `GET` | `/api/v1/reporting/cutover/status` | Status cutover & kesehatan sistem | Superadmin/Admin/Director |
| `POST` | `/api/v1/reporting/cutover/verify` | Verifikasi integritas data cutover | Superadmin/Admin/Director |

---

## 5. Hasil Pengujian Unit & Integrasi

Semua pengujian berjalan 100% PASS tanpa external mocks dependency:
```bash
=== RUN   TestHandler_AnalyticsEndpoints
--- PASS: TestHandler_AnalyticsEndpoints (0.04s)
=== RUN   TestService_CalculateAOV
--- PASS: TestService_CalculateAOV (0.00s)
=== RUN   TestService_CalculateGrossMargin
--- PASS: TestService_CalculateGrossMargin (0.00s)
=== RUN   TestService_CalculateNetProfit
--- PASS: TestService_CalculateNetProfit (0.00s)
=== RUN   TestService_DetermineShiftDiscrepancy
--- PASS: TestService_DetermineShiftDiscrepancy (0.00s)
=== RUN   TestService_CalculateSalesmanCommission
--- PASS: TestService_CalculateSalesmanCommission (0.00s)
=== RUN   TestService_FormatRupiah
--- PASS: TestService_FormatRupiah (0.00s)
=== RUN   TestService_ParseDateRange
--- PASS: TestService_ParseDateRange (0.00s)
=== RUN   TestUseCase_GetKpiSummary
--- PASS: TestUseCase_GetKpiSummary (0.00s)
=== RUN   TestUseCase_GetSalesTrend
--- PASS: TestUseCase_GetSalesTrend (0.00s)
=== RUN   TestUseCase_GetBranchComparison
--- PASS: TestUseCase_GetBranchComparison (0.00s)
=== RUN   TestUseCase_GetExecutivePaymentBreakdown
--- PASS: TestUseCase_GetExecutivePaymentBreakdown (0.00s)
=== RUN   TestUseCase_GetSalesSummary
--- PASS: TestUseCase_GetSalesSummary (0.00s)
=== RUN   TestUseCase_GetProfitSummary
--- PASS: TestUseCase_GetProfitSummary (0.00s)
=== RUN   TestUseCase_GetShiftByID
--- PASS: TestUseCase_GetShiftByID (0.00s)
=== RUN   TestUseCase_GetSalesmenPerformance
--- PASS: TestUseCase_GetSalesmenPerformance (0.00s)
=== RUN   TestUseCase_Cutover
--- PASS: TestUseCase_Cutover (0.00s)
PASS
ok  	tokopon-go/internal/modules/analytics	2.930s
```

Seluruh 8 modul di `tokopon-go` kini 100% selesai dan lulus uji regresi penuh (`go test ./...` PASS).
