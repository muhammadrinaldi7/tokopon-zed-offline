# 📋 Tokopon Ecosystem — Master Feature Matrix

> **Matriks Seluruh Fitur Bisnis, Kesiapan Implementasi, dan Bukti Verifikasi**  
> Baseline Audit: **22 Oktober 2026** | Standar Paritas: **Strict Evidence-Based**

---

## 📌 Status Legend
- `VERIFIED`: Terbukti bekerja dan terverifikasi melalui unit test atau live execution.
- `PARTIALLY VERIFIED`: Kode sudah diimplementasikan dan unit test lulus, tetapi integrasi frontend masih berupa mock atau stub.
- `IMPLEMENTED_UNTESTED`: Kode telah ditulis tetapi belum memiliki cakupan test yang memadai.
- `NOT IMPLEMENTED`: Belum ditemukan implementasi di codebase.
- `NOT_REQUIRED`: Sengaja tidak diimplementasikan (misal integrasi Erzap yang sudah dipensiunkan).

---

## 📊 Matriks Fitur Lengkap

| Feature ID | Modul | Nama Fitur Bisnis | Implementasi Laravel | Implementasi Go Backend | Implementasi Frontend Web | Test Evidence | Status Parity | Priority |
|---|---|---|---|---|---|---|---|---|
| **FEAT-001** | Identity | Login Staf, JWT & Refresh Token | `LoginController.php`, Sanctum | `identity/handler.go` (`Login`) | `LoginPage.tsx`, `authStore.ts` | `identity/usecase_test.go` | `VERIFIED` | P0 |
| **FEAT-002** | Identity | Resolusi Multi-Tenant & Switch BU | `User.php::getActiveBusinessUnitId` | `identity/usecase.go` (`SwitchBusinessUnit`) | Header `X-Business-Unit-Id` di Axios | `identity/usecase_test.go` | `VERIFIED` | P0 |
| **FEAT-003** | Identity | Spatie RBAC & Dynamic Permission Union | Spatie `HasRoles`, `Gate::before` | `identity/middleware.go` (`RequireRole`) | `ProtectedRoute.tsx` | `identity/middleware_test.go` | `VERIFIED` | P0 |
| **FEAT-004** | POS | Buka & Tutup Shift Kasir (Denominasi) | `Zoffline\Pos\Index.php` | `pos/handler.go` (`OpenShift`, `CloseShift`) | `OpenShiftModal.tsx`, `CloseShiftModal.tsx` | `pos/usecase_test.go`, Build Pass | `VERIFIED` | P0 |
| **FEAT-005** | POS | Scan Barcode & Validasi Ketersediaan IMEI | `Zoffline\Pos\ScanItem.php` | `inventory/handler.go` (`ScanSerialNumber`) | `Step2Cart.tsx` (Real barcode/SN lookup) | `pos/usecase_test.go`, Build Pass | `VERIFIED` | P0 |
| **FEAT-006** | POS | Transaksi Checkout Atomik & Anti-Overselling | `Zoffline\Pos\Checkout.php` | `pos/usecase.go` (`Checkout` Tx Lock) | `Step4Payment.tsx` (Direct checkout API) | `pos/usecase_test.go`, `cmd/e2e_runner` | `VERIFIED` | P0 |
| **FEAT-007** | POS | Cetak Struk Kasir Thermal ESC/POS | `Zoffline\Pos\Print.php`, `QzTrayController` | `pos/escpos.go`, `pos/handler.go` (`SignQz`) | `ReceiptModal.tsx` | `pos/usecase_test.go`, Build Pass | `VERIFIED` | P1 |
| **FEAT-008** | Catalog | In-Memory Promo & Discount Engine | `PromoCalculatorService.php` | `catalog/promo_engine.go` | *Belum ada UI khusus katalog* | `catalog/promo_engine_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-009** | Catalog | Master Data Brands & Categories | `Admin\Products\Category.php` | `catalog/handler.go` (`GetCategories`) | *Belum ada UI master data* | `catalog/handler_test.go` | `PARTIALLY VERIFIED` | P2 |
| **FEAT-010** | Loyalty | Buku Besar Poin Loyalitas (1 pt = Rp 100) | `LoyaltyService.php` | `loyalty/service.go` (`ExecuteRedeem`) | `CustomerModal.tsx` | `loyalty/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-011** | Loyalty | Kupon Diskon E-Commerce & Batas Kuota | `EcommerceCoupon.php` | `loyalty/service.go` (`ValidateCoupon`) | `CouponModal.tsx` | `loyalty/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-012** | Inventory | Cek Stok Multi-Gudang & Breakdown IMEI | `Zoffline\Warehouse\Stock.php` | `inventory/handler.go` (`GetStocks`) | Placeholder `<div>` di `/inventory/*` | `inventory/usecase_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-013** | Inventory | Inbound PO Receiving & Scanning Barang | `Zoffline\Inbound\PoReceive.php` | `inventory/handler.go` (`ReceiveInboundPO`) | Placeholder `<div>` di `/inventory/*` | `inventory/usecase_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-014** | Inventory | Sesi Stock Opname Fisik & Rekonsiliasi | `Zoffline\StockOpname\Index.php` | `inventory/handler.go` (`FinalizeOpname`) | Placeholder `<div>` di `/inventory/*` | `inventory/usecase_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-015** | Inventory | Stock Adjustments (Penyesuaian Selisih) | `Admin\Inventory\Adjustment.php` | `inventory/handler.go` (`CreateAdjustment`) | Placeholder `<div>` di `/inventory/*` | `inventory/usecase_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-016** | Trade-In | Retail Trade-In & Taksiran Harga Buyback | `Zoffline\SellPhone\Buyback.php` | `tradein/service.go` (`CalculateAppraisal`) | Placeholder `<div>` di `/tradein/*` | `tradein/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-017** | Trade-In | Checklist QC HP Bekas & Evaluasi Fatal Cacat | `Zoffline\Qc\Inspection.php` | `tradein/service.go` (`EvaluateQCChecklist`)| Placeholder `<div>` di `/tradein/*` | `tradein/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-018** | Trade-In | Digital Device Passport (Riwayat Seumur Hidup)| `Zoffline\DevicePassport.php` | `tradein/handler.go` (`GetPassportByIMEI`) | Placeholder `<div>` di `/tradein/*` | `tradein/usecase_test.go` | `PARTIALLY VERIFIED` | P2 |
| **FEAT-019** | Integration | Background Worker Sync Accurate Online ERP | `ProcessAccurateWebhookJob.php` | `integration/service.go`, `cmd/worker` | *Belum ada UI monitoring sync* | `integration/service_test.go` | `PARTIALLY VERIFIED` | P0 |
| **FEAT-020** | Integration | Background Worker Dispatch WhatsApp CRM | `SendWhatsappNotificationJob.php` | `whatsapp/service.go`, `cmd/worker` | *Belum ada UI monitoring WA* | `whatsapp/formatter_test.go` | `PARTIALLY VERIFIED` | P0 |
| **FEAT-021** | Integration | Ingest Webhook Xendit & Telegram Approval | `XenditWebhookController.php` | `integration/handler.go` (`WebhookXendit`) | *Server-to-server webhook* | `integration/handler_test.go` | `VERIFIED` | P1 |
| **FEAT-022** | Integration | Manual Retry & Audit Log Sinkronisasi | *Di Laravel via Livewire Manual Retry* | `integration/handler.go` (`RetryAccurate`) | *Belum ada halaman monitoring* | `integration/handler_test.go` | `PARTIALLY VERIFIED` | P2 |
| **FEAT-023** | Analytics | Executive KPI Summary, Sales Trend & Cabang | `AiReportController.php` | `analytics/handler.go` (`GetExecutiveKPI`) | Placeholder `<div>` di `/analytics/*` | `analytics/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-024** | Analytics | Laporan Laba Rugi P&L (Consistent HPP) | `Admin\Reporting\ProfitLoss.php` | `analytics/handler.go` (`GetProfitLoss`) | Placeholder `<div>` di `/analytics/*` | `analytics/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-025** | Analytics | Audit Rekonsiliasi Kas Kasir Shift | `Admin\Pos\ShiftAudit.php` | `analytics/handler.go` (`GetShiftAudit`) | Placeholder `<div>` di `/analytics/*` | `analytics/service_test.go` | `PARTIALLY VERIFIED` | P1 |
| **FEAT-026** | Legacy | Integrasi POS Erzap Legacy | `ErzapProductController.php` | *Dieliminasi (Skip)* | *Tidak Diperlukan* | *N/A* | `NOT_REQUIRED` | P3 |
