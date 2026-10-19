# 00 — Project Overview

**Dibuat:** 2026-10-19  
**Status Audit:** IN PROGRESS — Phase 1

---

## Ringkasan Eksekutif

**Tokopon / Zed-Offline** adalah sistem backend monolitik yang dibangun dengan **Laravel 12** (PHP 8.2+), yang melayani operasional ritel toko elektronik (gadget/ponsel baru dan bekas) secara menyeluruh:

1. **Web POS Kasir Toko Offline** — Alur transaksi kasir fisik (Buka/Tutup Shift, Rekonsiliasi Kas, Scan Barcode & IMEI SN, Split Payment, Perhitungan MDR, Cetak Struk ESC/POS & QZ Tray, Pelunasan Piutang, Draft Order). Sebelumnya di Laravel berjalan via Livewire 4.
2. **Manajemen Operasional Toko & Gudang** — Inbound PO Scan, Stock Opname Cabang, Penelusuran Siklus Hidup Serial Number/IMEI (Device Passport), Cek Stok Multi-Gudang, dan Penyesuaian Stok (Stock Adjustment).
3. **Retail Trade-In, Buyback (Sell-Phone) & QC Garansi** — Evaluasi perangkat bekas, inspeksi QC serah terima, kalkulator trade-in, aktivasi garansi IMEI, dan workflow klaim garansi toko.
4. **Backoffice Master Data & Pengaturan** — Katalog Produk Baru & Bekas (Varian, Kategori, Brand), Promo & Diskon Manual Kasir, Data Salesman/Karyawan, Vendor, Customer Deposit, dan Pengaturan Unit Bisnis.
5. **Mobile App E-Commerce & Realtime CS Chat** — REST API untuk belanja online pelanggan, voucher e-commerce, program loyalitas poin, dan chat interaktif via WebSocket Go.
6. **Executive Dashboard & AI Assistant** — Dashboard analitik metrik KPI, tren penjualan, dan asisten AI direktur.

---

## Target & Visi Rebuild: Full Backend Rewrite ke Go (`tokopon-go`)

Tujuan utama proyek ini adalah **rebuild seluruh backend Laravel menjadi backend Go (`tokopon-go`) yang jauh lebih ringan, cepat, hemat memori, dan tinggi konkurensi (high-throughput concurrency)**:

- **Full Backend API Replacement:** Seluruh logika bisnis yang sebelumnya dijalankan oleh server PHP Laravel (baik Livewire controller maupun API controller) direkonstruksi menjadi **RESTful API & WebSocket di Go** menggunakan Clean Architecture.
- **Web POS Decoupling:** Antarmuka Web POS kasir toko fisik yang sebelumnya lambat dan berat karena rendering Livewire digantikan dengan Go REST API berkecepatan milidetik (sub-millisecond latency), siap dihubungkan dengan Web POS SPA / Frontend modern.
- **Database Sharing & Data Integrity:** Beroperasi di atas database MySQL `zedpost-7` yang sama tanpa merusak skema eksisting selama transisi (Strangler Fig Pattern).
- **Concurrency & Anti-Overselling:** Menggunakan transaksi atomik native Go dengan penguncian baris `SELECT ... FOR UPDATE` pada tabel stok gudang dan penandaan status nomor seri/IMEI secara real-time.
- **Zero Heavy Dependencies:** Menghilangkan overhead Laravel (Reverb, Livewire, Blade, session storage di server) menjadi single binary Go yang sangat efisien dan mudah di-deploy via Docker/VPS.

---

## Domain Bisnis Utama

| Domain | Kelas Utama | Status Audit |
|---|---|---|
| User & Auth | User, FortifyServiceProvider | COMPLETED |
| Business Unit (Multi-Tenant) | BusinessUnit, Branch, Warehouse | COMPLETED |
| Produk (Baru) | Product, ProductVariant, ProductAccurate | COMPLETED |
| Produk (Bekas) | SecondProduct, SecondProductVariant | IN PROGRESS |
| Inventory / Serial Number | ProductSerialNumber, WarehouseStock | IN PROGRESS |
| Order (POS + Mobile) | Order, OrderItem, OrderPayment | COMPLETED |
| Promo & Diskon | Promo, PromoCalculatorService | IN PROGRESS |
| Loyalty | LoyaltyAccount, LoyaltyService | IN PROGRESS |
| Voucher/Kupon | EcommerceCoupon, EcommerceCouponService | IN PROGRESS |
| Sell Phone (Buyback) | SellPhone, SellPhoneIssue | COMPLETED |
| Trade-In | TradeIn, TradeInService | IN PROGRESS |
| Garansi | Warranty, WarrantyClaim, WarrantyPolicy | COMPLETED |
| QC & Inspeksi | DeviceInspection, QcTemplate | IN PROGRESS |
| Approval Workflow | ApprovalRequest, ApprovalService | COMPLETED |
| Stock Opname | StockOpname, StockOpnameItem | IN PROGRESS |
| Stock Adjustment | StockAdjustment | IN PROGRESS |
| Inbound / PO | PurchaseOrder, PurchaseOrderItem | IN PROGRESS |
| Customer Deposit | CustomerDeposit | IN PROGRESS |
| Accurate ERP | AccurateService, SyncOrderToAccurateJob | IN PROGRESS |
| Xendit Payment | XenditService, XenditWebhookController | COMPLETED |
| Chat / CS | Conversation, Message, ChatController | IN PROGRESS |
| AI Executive | ExecutiveAiService | IN PROGRESS |
| Reporting / KPI | ExecutiveMetricsService | IN PROGRESS |
| Notification (WA/Telegram) | MessageDispatchService | IN PROGRESS |
| Cashier Shift | CashierShift | IN PROGRESS |
