# PROGRESS — Laravel to Go Migration

**Dibuat:** 2026-10-19  
**Last Updated:** 2026-10-19  
**Fase Saat Ini:** Phase 1 (Comprehensive Laravel Audit) — **COMPLETED ✅**

---

## Ringkasan Eksekutif Phase 1

Seluruh audit sistematis terhadap monolit Laravel `tokopon-zed-offline` telah selesai dilaksanakan dan didokumentasikan secara menyeluruh di direktori `docs/migration/`. Seluruh 6 keputusan strategis arsitektur telah dipetakan dan diintegrasikan ke dalam proposal teknis dan roadmap migrasi.

---

## Status Dokumen Audit (Phase 1)

| File | Judul Dokumen | Status | Rangkuman Isi |
|---|---|---|---|
| [`00-project-overview.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/00-project-overview.md) | Project Overview | **COMPLETED** | Stack teknologi, 25 domain bisnis, struktur folder, profil monolit |
| [`01-laravel-architecture.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/01-laravel-architecture.md) | Laravel Architecture | **COMPLETED** | Middleware, booted hooks, observers, routing map API vs Web |
| [`02-package-inventory.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/02-package-inventory.md) | Package Inventory | **COMPLETED** | 14 package utama, analisis risiko, ekuivalen pustaka Go |
| [`03-business-logic-catalog.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/03-business-logic-catalog.md) | Business Logic Catalog | **COMPLETED** | 13 alur bisnis inti (checkout, loyalty, promo, approval, dll) |
| [`04-database-schema.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/04-database-schema.md) | Database Schema | **COMPLETED** | 238 migrasi, skema 18 tabel utama, relasi polimorfik, indeks |
| [`05-authentication-authorization.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/05-authentication-authorization.md) | Auth & Permissions | **COMPLETED** | Sanctum vs Fortify, 30+ permissions Spatie, strategi JWT Go |
| [`06-api-contracts.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/06-api-contracts.md) | API Contracts | **COMPLETED** | 60+ endpoint REST, parameter request/response, autentikasi |
| [`07-external-integrations.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/07-external-integrations.md) | External Integrations | **COMPLETED** | Accurate ERP, Xendit, WA CRM, Telegram, Expo, Reverb |
| [`08-background-jobs-and-events.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/08-background-jobs-and-events.md) | Background Jobs & Events | **COMPLETED** | 6 jobs antrian, 2 scheduler cron, event listeners |
| [`09-security-audit.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/09-security-audit.md) | Security Audit | **COMPLETED** | 3 endpoint rentan tanpa auth, penanganan secret key di DB |
| [`10-go-architecture-proposal.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/10-go-architecture-proposal.md) | Go Architecture Proposal | **COMPLETED** | Clean Architecture, Echo, sqlx, WebSocket hub, Response wrapper |
| [`11-migration-roadmap.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/11-migration-roadmap.md) | Migration Roadmap | **COMPLETED** | Strangler Fig Waves 0-6, urutan migrasi endpoint, rollback plan |
| [`12-migration-test-matrix.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/12-migration-test-matrix.md) | Migration Test Matrix | **COMPLETED** | Matriks uji fungsional, concurrency anti-overselling, WebSocket test |
| [`13-open-questions-and-risks.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/13-open-questions-and-risks.md) | Resolved Decisions & Risks | **COMPLETED** | Dokumentasi 6 keputusan final user, status verifikasi kode |

---

## 6 Keputusan Strategis yang Diadopsi Penuh

1. **Autentikasi (`harus login`):**
   - User melakukan re-login melalui endpoint Go (`/api/v1/mobile/auth/login`).
   - Go menerbitkan token standar JWT (Access Token + Refresh Token).
   - Menghindari utang teknis parsing tabel `personal_access_tokens` Sanctum.
2. **WebSocket Native Go (`go websocket ready`):**
   - Go mengimplementasikan WebSocket Hub & Room native (`gorilla/websocket`).
   - Siap menampung kanal: `conversation.{id}`, `user.{id}`, `cs-chats`.
   - Menghilangkan dependensi runtime pada Laravel Reverb / Pusher cloud.
3. **Pola Strangler Fig (`stargler fig`):**
   - Menggunakan Reverse Proxy (Nginx) di depan untuk routing inkremental.
   - Endpoint Go dipasang satu per satu; traffic Laravel dialihkan bertahap tanpa downtime.
4. **Eliminasi Erzap (`skip aja itu erzap`):**
   - Integrasi dan controller Erzap legacy diabaikan sepenuhnya dari cakupan Go.
5. **Queue Driver Pluggable (`database` sekarang, `redis` ready via ENV):**
   - Abstraksi antarmuka `QueueBroker`.
   - Driver aktif saat ini: Database (`jobs` table dengan `FOR UPDATE SKIP LOCKED`).
   - Driver masa depan: Redis (`asynq` stub), aktif cukup dengan mengganti `QUEUE_DRIVER=redis` di file `.env`.
6. **API Response Wrapper Scalable & Standar:**
   - Amplop seragam: `success`, `code`, `message`, `data`, `meta` (pagination), dan `errors` terstruktur.
   - Kompatibel dengan aplikasi mobile eksisting.

---

## Status Fase Migrasi

- **Phase 1 (Comprehensive Laravel Audit):** SELESAI (100%) ✅
- **Phase 2 (Project Setup & Foundation Architecture):** SELESAI (100%) ✅
  - Inisialisasi Go Module `tokopon-go` di `tokopon-zed-offline/tokopon-go/` (terisolasi di `.gitignore`)
  - Koneksi database MySQL (`sqlx`) terverifikasi berhasil terhubung ke database `zedpost-7`
  - Implementasi `pkg/response` (Unified Scalable JSON Envelope)
  - Implementasi `pkg/queue` (Interface Broker: driver `database` aktif, driver `redis` ready)
  - Implementasi `pkg/auth` (JWT generation/validation & bcrypt password check)
  - Implementasi `internal/delivery/http` (Echo router, middleware, healthcheck)
  - Server teruji kompilasi dan endpoint `GET /api/v1/health` mengembalikan status `UP` & `database: healthy`
- **Wave 1 (Authentication & User Profile):** SELESAI (100%) ✅
  - Domain, DTO, dan Repository untuk `users`, `user_profiles`, `roles`, dan `loyalty_accounts`
  - Implementasi UseCase: Login (Bcrypt + JWT), Register (auto-create profile, role customer, loyalty account), GetMe, UpdateProfile, UpdatePushToken, RefreshToken
  - Implementasi HTTP Delivery di `/api/v1/mobile/auth/*`
  - Proteksi JWT Middleware untuk route sensitif
  - Pengujian end-to-end teruji sukses: Register (201), Login (200), Wrong Password (401), Me (200), Update Profile (200), Push Token (200)
- **Wave 2 (Product Catalog & Stock Read APIs):** SELESAI (100%) ✅
  - Domain, DTO, dan Repository untuk `business_units`, `warehouses`, `warehouse_stocks`, `banners`, `product_serial_numbers`
  - Implementasi 8 endpoint katalog publik di `/api/v1/mobile/*`
  - Agregasi stok realtime dari `warehouse_stocks` untuk gudang berstatus `is_online_store = 1`
  - Filter kurasi e-commerce (eliminasi item LDU/DEMO) & formatting Rupiah
  - Dokumentasi API lengkap di `tokopon-go/docs/api_wave2.md` dan `docs/migration/14-wave-2-catalog-api.md`
  - Test suite otomatis di `tokopon-go/tests/catalog_test.ps1` (10/10 Test Case PASS)
- **Wave 3 (Mobile Cart, Checkout & Transaksi Order):** SELESAI (100%) ✅
  - Domain, DTO, dan Repository untuk `orders`, `order_items`, `order_payments`, `ecommerce_coupons`, `warehouse_stocks`, `product_serial_numbers`
  - Transaksi checkout atomik dengan locking stok `SELECT ... FOR UPDATE` pada `warehouse_stocks`
  - Reservasi nomor seri/IMEI otomatis (`status = 'Reserved'`)
  - Validasi kupon diskon & potongan poin loyalitas pelanggan
  - Endpoint upload bukti transfer manual & pembatalan pesanan dengan auto-restock
  - Dokumentasi API di `tokopon-go/docs/api_wave3.md` dan `docs/migration/15-wave-3-order-checkout-api.md`
  - Go Native Tests lulus 100%
- **Wave 4 (Native Go WebSocket & Live Chat CS):** SELESAI (100%) ✅
  - In-memory WebSocket Hub (`gorilla/websocket`) tanpa dependensi Laravel Reverb / Pusher
  - Manajemen channel/room: `conversation.{id}`, `cs-broadcast`, `user.{id}`
  - REST endpoint: init chat (customer/guest), message history, send message (tag produk + media attachment Spatie compatible), mark as read
  - Dokumentasi API di `tokopon-go/docs/api_wave4.md` dan `docs/migration/16-wave-4-websocket-live-chat-api.md`
  - Go Native Tests (26/26 tests) lulus 100%
- **Wave 5 (Background Workers, Order Auto-Expiry & Webhook Receivers):** SELESAI (100%) ✅
  - Webhook Xendit Invoice Callback: verifikasi `x-callback-token`, status `PAID`/`SETTLED` (update order `PROCESSING`, IMEI `Sold`, loyalty points, trigger sync job), status `EXPIRED` (auto-cancel & restock)
  - Webhook Accurate Online: verifikasi HMAC signature / URL token, idempotency guard via `accurate_webhook_logs`
  - Background Worker `OrderExpiryWorker`: auto-cancellation pesanan expired setiap 1 menit dengan rollback stok & IMEI
  - Queue worker consumers: `job:order:sync_accurate` dan `job:notification:push`
  - Dokumentasi API di `tokopon-go/docs/api_wave5.md` dan `docs/migration/17-wave-5-background-workers-and-webhooks.md`
  - Go Native Tests (34/34 tests) lulus 100%
- **Wave 6 (Approval Workflow, Warranty, Trade-In & Executive Metrics):** SELESAI (100%) ✅
  - Approval Engine: Callback Telegram inline keyboard via n8n (`POST /api/v1/webhooks/approval`), otorisasi role, level lock, order rollback & notifikasi bot
  - Warranty Service: Validasi dan pengecekan garansi IMEI/SN (`GET /api/v1/mobile/warranty/:serial_number`)
  - Public Trade-In: Daftar brand, old devices, target upgrade devices, kalkulasi tukar tambah & WhatsApp URL generation
  - Executive Metrics: Login eksekutif (role guard: `superadmin`, `admin`, `director`), KPI summary (omset, profit, margin, basket size), sales trend, komparasi cabang, dan breakdown metode pembayaran
  - Dokumentasi API di `tokopon-go/docs/api_wave6.md` dan `docs/migration/18-wave-6-approval-warranty-executive-metrics.md`
  - Go Native Tests (44/44 tests) lulus 100%
- **Wave 7 (Web POS Kasir Toko Offline API):** SELESAI (100%) ✅
  - Menggantikan alur Livewire POS kasir fisik monolitik (`Zoffline\Pos`) menjadi high-performance Go REST API
  - Domain Entity (`internal/domain/pos.go`): ✅ SELESAI
  - Repository & DB (`internal/repository/pos_repository.go`): ✅ SELESAI (Pessimistic locking `FOR UPDATE`, atomic SN status `Available` -> `Sold`, sinkronisasi kas shift)
  - UseCase & ESC/POS (`internal/usecase/pos_usecase.go`): ✅ SELESAI (Perhitungan shift & rekonsiliasi kas, validasi checkout, generator struk thermal 58mm/80mm, QZ Tray SHA512 signing)
  - Delivery HTTP (`internal/delivery/http/pos_handler.go`): ✅ SELESAI (13 Endpoint POS kasir toko fisik)
  - Router wiring (`internal/delivery/http/router.go`) & Server (`cmd/server/main.go`): ✅ SELESAI
  - Dokumentasi API di `tokopon-go/docs/api_wave7.md` dan `docs/migration/19-wave-7-web-pos-kasir-api.md`: ✅ SELESAI
  - Go Native Tests (`pos_usecase_test.go` & `pos_handler_test.go`): ✅ Lulus 100% (Zero external testing libraries)
- **Wave 8 (Store Inventory, Stock Opname & Inbound PO API):** SELESAI (100%) ✅
  - Domain Entity (`internal/domain/inventory.go`): ✅ SELESAI
  - Repository & DB (`internal/repository/inventory_repository.go`): ✅ SELESAI (Cek stok multi-gudang, breakdown IMEI aktif, inbound PO receiving, snapshot opname toko cabang, recording count serial/non-serial, rekonsiliasi selisih rugi/lebih, dan stock adjustment)
  - UseCase (`internal/usecase/inventory_usecase.go`): ✅ SELESAI (Formatting Rupiah, validasi sesi opname aktif, perhitungan selisih finansial, progres PO receiving)
  - Delivery HTTP (`internal/delivery/http/inventory_handler.go`): ✅ SELESAI (12 Endpoint inventaris & opname)
  - Router wiring (`internal/delivery/http/router.go`) & Server (`cmd/server/main.go`): ✅ SELESAI
  - Dokumentasi API di `tokopon-go/docs/api_wave8.md` dan `docs/migration/20-wave-8-store-inventory-stock-opname-api.md`: ✅ SELESAI
  - Go Native Tests (`inventory_usecase_test.go` & `inventory_handler_test.go`): ✅ Lulus 100% (Zero external testing libraries)
- **Wave 9 (Retail Trade-In, Buyback & Digital Device Passport API):** SELESAI (100%) ✅
  - Domain Entity (`internal/domain/tradein_buyback.go`): ✅ SELESAI
  - Repository & DB (`internal/repository/tradein_buyback_repository.go` & `qc_passport_repository.go`): ✅ SELESAI (Transaksi retail trade-in, intake buyback HP bekas, auto-register walk-in customer, registrasi stok otomatis ke `product_serial_numbers`, checklist QC & evaluasi fatal failure, dan agregasi timeline paspor seumur hidup IMEI)
  - UseCase (`internal/usecase/tradein_buyback_usecase.go` & `qc_passport_usecase.go`): ✅ SELESAI (Perhitungan top-up tukar tambah, formatting Rupiah, kalkulasi verdict QC pass/fail/conditional, dan sorting kronologis paspor perangkat)
  - Delivery HTTP (`internal/delivery/http/tradein_buyback_handler.go` & `qc_passport_handler.go`): ✅ SELESAI (14 Endpoint operasional trade-in, buyback, QC, dan paspor digital)
  - Router wiring (`internal/delivery/http/router.go`) & Server (`cmd/server/main.go`): ✅ SELESAI
  - Dokumentasi API di `tokopon-go/docs/api_wave9.md` dan `docs/migration/21-wave-9-trade-in-buyback-device-passport-api.md`: ✅ SELESAI
  - Go Native Tests (`tradein_buyback_usecase_test.go` & `tradein_buyback_handler_test.go`): ✅ Lulus 100% (Zero external testing libraries)
- **Wave 10 (Backoffice Master Data, Promos & Store Settings API):** SELESAI (100%) ✅
  - Domain Entity (`internal/domain/backoffice.go`): ✅ SELESAI (Brands, Categories, Employees, Vendors, Business Units, Branches, Promos, Manual Discount Presets, Payment Methods & Rates, Settings)
  - Repository & DB (`internal/repository/backoffice_repository.go`): ✅ SELESAI (Akses skema native `zedpost-7` untuk tabel `brands`, `categories`, `employes`, `vendors`, `business_units`, `branches`, `promos`, `manual_discount_presets`, `payment_methods`, `payment_method_rates`, dan `settings`)
  - UseCase (`internal/usecase/backoffice_usecase.go`): ✅ SELESAI (Business validation, formatting Rupiah, auto-slug generator, status toggle, filter multi-cabang & unit bisnis)
  - Delivery HTTP (`internal/delivery/http/backoffice_handler.go`): ✅ SELESAI (24 Endpoint backoffice komprehensif di `/api/v1/backoffice/*`)
  - Router wiring (`internal/delivery/http/router.go`) & Server (`cmd/server/main.go`): ✅ SELESAI (Route group `/api/v1/backoffice` dengan proteksi JWT middleware)
  - Dokumentasi API di `tokopon-go/docs/api_wave10.md` dan `docs/migration/22-wave-10-backoffice-master-data-settings-api.md`: ✅ SELESAI
  - Go Native Tests (`backoffice_usecase_test.go` & `backoffice_handler_test.go`): ✅ Lulus 100% (Zero external testing libraries)
- **Wave 11 (Store Reporting, Financial Analytics & Full Cutover):** SELESAI (100%) ✅
  - Domain Entity (`internal/domain/reporting.go`): ✅ SELESAI (Sales Summary, itemized transactions, Profit & Loss HPP calculation, Cashier Shift Reconciliation, Salesman performance & commissions, Product velocity fast/slow moving, Payment methods & MDR breakdown, Cutover status & verification)
  - Repository & DB (`internal/repository/reporting_repository.go`): ✅ SELESAI (Optimized SQL aggregations dengan `orders`, `order_items`, `order_payments`, `cashier_shifts`, `employes`, `product_serial_numbers`, dan `payment_methods`)
  - UseCase (`internal/usecase/reporting_usecase.go`): ✅ SELESAI (Perhitungan HPP otomatis, kalkulasi komisi staf, formula margin laba rugi, pemformatan Rupiah seragam, dan runtime healthcheck monitor)
  - Delivery HTTP (`internal/delivery/http/reporting_handler.go`): ✅ SELESAI (14 Endpoint pelaporan & analitik komprehensif di `/api/v1/reporting/*`)
  - Router wiring (`internal/delivery/http/router.go`) & Server (`cmd/server/main.go`): ✅ SELESAI (Route group `/api/v1/reporting` dengan proteksi JWT middleware)
  - Dokumentasi API di `tokopon-go/docs/api_wave11.md` dan `docs/migration/23-wave-11-store-reporting-financial-analytics-cutover.md`: ✅ SELESAI
  - Go Native Tests (`reporting_usecase_test.go` & `reporting_handler_test.go`): ✅ Lulus 100% (Zero external testing libraries)
  - Strategi Full Cutover: ✅ SELESAI (Arsitektur Reverse Proxy Nginx untuk pengalihan 100% traffic API ke Go backend, Laravel Monolith Strangled)

---

---

## 🚀 ROADMAP STRANGLER FIG SELANJUTNYA: STEP 2

### Background Integration Engines (Accurate ERP & WhatsApp CRM) — Step 2
- **Status:** **SELESAI (100%) ✅**
- **Dokumen Referensi:** [`docs/migration/24-step-2-accurate-erp-and-whatsapp-crm-engine.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/24-step-2-accurate-erp-and-whatsapp-crm-engine.md)
- **Implementasi:**
  1. Accurate Online ERP Client & Multi-Tenant Credentials (`internal/integration/accurate/client.go` & `models.go`): ✅ SELESAI (Signature generator HMAC-SHA256, ISO-8601 timestamp, auto-retry 2x delay 500ms, multi-tenant DB reader)
  2. Order Sync Service (`internal/integration/accurate/service.go`): ✅ SELESAI (Customer Sync `/customer/save.do`, Sales Invoice `/sales-invoice/save.do`, Sales Receipt `/sales-receipt/save.do`, pencatatan audit `order_accurate_docs`)
  3. WhatsApp CRM Gateway Client (`internal/integration/whatsapp/client.go`, `models.go`, `formatter.go`): ✅ SELESAI (Normalisasi nomor E.164 Indonesia, dispatch template struk penjualan, pencegahan duplikasi)
  4. WhatsApp Notification Service & Audit Logger (`internal/integration/whatsapp/service.go`): ✅ SELESAI (Pencatatan komprehensif ke `message_logs`, update `orders.is_wa_sent = 1`, toggle gateway via `settings`)
  5. Asynchronous Queue Worker Consumer (`internal/worker/queue_handlers.go`): ✅ SELESAI (Handling `job:order:sync_accurate` dan `job:notification:whatsapp` secara non-blocking)
  6. Go Native Unit Tests: ✅ Lulus 100% (`client_test.go`, `formatter_test.go`, `queue_handlers_test.go`, zero external mock/testing libraries)

### Role & Permission Parity (Laravel Spatie Permission in Go) — Opsi A
- **Status:** **SELESAI (100%) ✅**
- **Dokumen Referensi:** [`docs/migration/25-role-and-permission-spatie-parity.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/migration/25-role-and-permission-spatie-parity.md)
- **Implementasi:**
  1. Ekstraksi permission terpadu via SQL union (`role_has_permissions` + `model_has_permissions`) di `internal/repository/user_repository.go`: ✅ SELESAI
  2. Domain Entity & JWT Claims (`internal/domain/user.go` & `pkg/auth/jwt.go`): ✅ SELESAI (Injeksi `Permissions []string`, helper `HasRole`, `HasAnyRole`, `HasPermission`, `HasAnyPermission`, `HasAllPermissions`)
  3. UseCase (`internal/usecase/auth_usecase.go`): ✅ SELESAI (Integrasi permission di `Login`, `Register`, `GetMe`, `UpdateProfile`, `RefreshToken`)
  4. Middleware Go (`internal/delivery/http/middleware/auth_middleware.go`): ✅ SELESAI (`RequireRole`, `RequirePermission`, `RequireAllPermissions` dengan SuperAdmin automatic wildcard bypass ekuivalen `Gate::before`)
  5. Go Native Unit Tests: ✅ Lulus 100% (`auth_middleware_test.go`, `jwt_test.go`, `auth_usecase_test.go`, zero external mock/testing libraries)


