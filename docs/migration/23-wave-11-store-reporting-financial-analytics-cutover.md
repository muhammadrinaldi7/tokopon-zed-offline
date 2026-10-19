# Wave 11: Store Reporting, Financial Analytics & Full Cutover (Final Wave)

**Dokumen Versi:** 1.0  
**Tanggal Rilis:** 2026-10-19  
**Status:** IMPLEMENTED ✅  
**Target:** Rebuild Laporan Keuangan, Rekonsiliasi Kasir EOD, Analisis Laba Rugi, Performa Salesman, dan Eksekusi Penuh Cutover dari Monolit Laravel ke Go Backend.

---

## 1. Pendahuluan & Latar Belakang

Pada arsitektur monolitik Laravel sebelumnya (`tokopon-zed-offline`), pelaporan penjualan dan analitik dijalankan melalui Livewire components (`SalesReport.php`, `ProfitReport.php`, `StaffReport.php`, `ClosingKasirReport.php`) yang memuat data ratusan ribu transaksi langsung ke memori PHP. Akibatnya:
1. **OOM (Out Of Memory) Crash:** Export CSV dan kalkulasi laba rugi bulanan sering memicu `PHP Fatal error: Allowed memory size exhausted`.
2. **N+1 Query Explosion:** Relasi rekursif antara order, order item, serial number, vendor, dan MDR payment method rate dieksekusi secara berulang di dalam loop PHP.
3. **Penyatuan Logika Kasir & Laporan Terputus:** Rekonsiliasi fisik kasir (*actual cash* vs *system expected cash*) membutuhkan waktu lama karena penghitungan manual di level aplikasi tanpa indexing SQL yang optimal.

**Wave 11** mentransformasikan seluruh kapabilitas pelaporan toko offline dan online menjadi **High-Performance Reporting Engine** native Go dengan:
- Kueri SQL agregasi level database (`SUM`, `AVG`, `COUNT`, `GROUP BY`) dengan indeks komposit.
- Kalkulasi HPP (*Cost of Goods Sold*) otomatis berbasis IMEI riil (`product_serial_numbers.hpp`) atau baseline cost varian.
- Analisis profitabilitas bersih (*Net Profit*) setelah potongan diskon toko, kupon, poin loyalitas, HPP, dan biaya MDR payment gateway/EDC.
- Rekapitulasi shift kasir dan rekonsiliasi selisih kas fisik (*cash discrepancy*).
- Analisis performa & komisi salesman (*basket size*, *gross revenue*, *net revenue*).
- Panduan *Cutover* komprehensif untuk pengalihan 100% traffic operasional dari Laravel ke Go backend (*Monolith Strangled*).

---

## 2. Pemetaan Modul & Endpoint Wave 11

### 2.1 Ringkasan 14 REST Endpoints Reporting Engine (`/api/v1/reporting/*`)

| No | Method | Endpoint | Deskripsi Bisnis |
|:---|:---|:---|:---|
| 1 | `GET` | `/api/v1/reporting/sales/summary` | Ringkasan metrik penjualan (Gross, Net, Diskon, Pajak, Ongkir, MDR) |
| 2 | `GET` | `/api/v1/reporting/sales/transactions` | Daftar transaksi penjualan terperinci dengan filter multi-cabang & paginasi |
| 3 | `GET` | `/api/v1/reporting/sales/export` | Ekspor data laporan penjualan (format terstruktur siap CSV/Excel) |
| 4 | `GET` | `/api/v1/reporting/profit/summary` | Ringkasan laba rugi toko (Net Sales, Total HPP, Total MDR, Net Profit, Margin %) |
| 5 | `GET` | `/api/v1/reporting/profit/breakdown` | Rincian laba rugi per transaksi atau per kategori/brand |
| 6 | `GET` | `/api/v1/reporting/shifts/summary` | Ringkasan rekonsiliasi shift kasir (Modal awal, Expected, Actual, Selisih) |
| 7 | `GET` | `/api/v1/reporting/shifts` | Riwayat buka/tutup shift kasir, selisih kas fisik, dan status rekonsiliasi |
| 8 | `GET` | `/api/v1/reporting/shifts/:id` | Detail audit shift kasir beserta rincian pecahan uang (*denominations*) |
| 9 | `GET` | `/api/v1/reporting/salesmen/performance` | Laporan performa salesman/karyawan (Transaksi, Gross/Net Revenue, Avg Basket Size) |
| 10 | `GET` | `/api/v1/reporting/salesmen/commission` | Estimasi komisi penjualan per staf berdasarkan tier atau persentase |
| 11 | `GET` | `/api/v1/reporting/products/velocity` | Analisis kecepatan penjualan produk (*fast-moving vs slow-moving inventory*) |
| 12 | `GET` | `/api/v1/reporting/payments/breakdown` | Rekapitulasi penerimaan per metode pembayaran (Cash, EDC, QRIS, Transfer) & MDR |
| 13 | `GET` | `/api/v1/reporting/cutover/status` | Status kesiapan cutover sistem & healthcheck performa backend Go |
| 14 | `POST` | `/api/v1/reporting/cutover/verify` | Verifikasi integritas data cross-backend (order count, stock tally, shift reconciliation) |

---

## 3. Desain Arsitektur & Formula Kalkulasi Finansial

### 3.1 Formula Penjualan (Sales Equations)
- **Gross Sales** = `SUM(orders.total_amount)`
- **Total Diskon** = `SUM(orders.discount_amount + orders.coupon_discount_amount + orders.loyalty_discount_amount)`
- **Net Sales** = `SUM(orders.grand_total)`
- **Total MDR** = `SUM(orders.mdr_amount)`
- **Net Collected Cash/Bank** = `Net Sales - Total MDR`

### 3.2 Formula Laba Rugi (Profit & Loss Equations)
- **HPP Barang Berseri (IMEI/SN)** = `SUM(product_serial_numbers.hpp)` untuk seluruh SN terdaftar pada order item.
- **HPP Non-Seri** = `SUM(product_accurates.base_cost * order_items.qty)`.
- **Total HPP (COGS)** = `HPP Barang Berseri + HPP Non-Seri`.
- **Gross Profit** = `Net Sales - Total HPP`.
- **Net Profit** = `Net Sales - Total HPP - Total MDR`.
- **Profit Margin (%)** = `(Net Profit / Net Sales) * 100` (jika Net Sales > 0).

### 3.3 Formula Rekonsiliasi Kasir (Cashier Shift Reconciliation)
- **Expected Cash** = `starting_cash + total_cash_sales`.
- **Cash Discrepancy (Selisih)** = `actual_cash - expected_cash`.
- **Reconciliation Verdict**:
  - `balanced` jika `selisih == 0`.
  - `short` jika `selisih < 0` (kas fisik kurang / tekor).
  - `over` jika `selisih > 0` (kas fisik lebih).

---

## 4. Strategi Full Cutover (Pola Strangler Fig - Final Switchover)

### 4.1 Arsitektur Reverse Proxy (Nginx)
Dengan selesainya Wave 11, seluruh domain monolit Laravel kini telah sepenuhnya memiliki ekuivalen di backend Go (`tokopon-go`).

```nginx
# Konfigurasi Cutover Nginx (Produksi)
upstream go_backend {
    server 127.0.0.1:8080 max_fails=3 fail_timeout=10s;
    keepalive 64;
}

upstream laravel_legacy {
    server 127.0.0.1:8000 backup; # Fallback cadangan jika diperlukan
}

server {
    listen 80;
    server_name pos.tokopon.id api.tokopon.id;

    # 1. 100% Traffic REST API dialihkan langsung ke Go Backend
    location /api/v1/ {
        proxy_pass http://go_backend;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 2. WebSocket Hub Native Go
    location /ws {
        proxy_pass http://go_backend/ws;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }
}
```

---

## 5. Matriks Pengujian Native Go (Wave 11)

Pengujian diimplementasikan menggunakan paket standar `testing` dan `net/http/httptest`:
1. **Sales Summary Test:** Verifikasi kalkulasi omset kotor, diskon, dan omset bersih.
2. **Profit & Loss Test:** Validasi pengurangan HPP dan MDR terhadap margin laba.
3. **Cashier Shift Reconciliation Test:** Verifikasi status `balanced`, `short`, dan `over`.
4. **Salesman Performance Test:** Verifikasi pengelompokan transaksi per staf dan rata-rata *basket size*.
5. **Product Velocity Test:** Verifikasi pemilahan produk terlaris (*fast-moving*).
6. **Payment Breakdown Test:** Validasi rekonsiliasi total penerimaan per metode pembayaran.
7. **Cutover Readiness Test:** Validasi integritas respons endpoint status cutover.
