# 01 — Laravel Architecture Audit

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS

---

## Framework & Versi

- **Framework:** Laravel 12.x (`laravel/framework: ^12.0`)
- **PHP:** ^8.2
- **Livewire:** 4.x (UI layer, DIABAIKAN untuk migrasi Go)

---

## Struktur Aplikasi

```
app/
  Actions/Fortify/      # CreateNewUser, ResetUserPassword (Fortify user lifecycle)
  Ai/                   # DatabaseAnalyzerAgent (Laravel AI agent)
  Approvals/            # Contracts + Handlers (9 approval types)
  Console/              # Artisan commands
  Events/               # MessageSent (realtime broadcast event)
  Exports/              # Excel exports (Maatwebsite)
  Http/
    Controllers/
      Api/Executive/    # ExecutiveAuthController, ExecutiveDashboardController, ExecutiveAiController
      Api/Mobile/       # AuthController, BannerController, BusinessUnitController, ChatController,
                        # ConfigController, CouponController, FlashSaleController, LoyaltyController,
                        # OrderController, PaymentMethodController, ProductController
      Api/              # AccurateImportController, AccurateWebhookController, ErzapProductController,
                        # PublicTradeInController, XenditWebhookController
      Admin/            # Livewire controllers (diabaikan)
      ApprovalController.php    # Approval web + API webhook (/api/webhooks/approval)
      AiReportController.php
      GoogleCallbackController.php
      MessageLogAttachmentController.php
      QzTrayController.php
      R2MediaProxyController.php
      StockOpnamePdfController.php
      SystemResetExportController.php
      WarrantyReportPdfController.php
    Middleware/
      EnsureIsAdmin.php     # Cek permission Spatie, redirect ke / jika tidak ada
      EnsureIsCustomer.php  # Cek role 'customer'
  Imports/              # Excel imports
  Jobs/                 # 6 background jobs
  Listeners/            # MergeGuestCartOnLogin (merge cart guest saat login)
  Livewire/             # DIABAIKAN untuk migrasi Go
  Mail/                 # Mail classes
  Models/               # 89 Eloquent model
  Observers/
    ProductErzapObserver.php       # Update stock saat ProductErzap dibuat/diupdate
    WarehouseStockObserver.php     # Logic observer warehouse stock
  Paths/                # NEEDS VERIFICATION (folder tidak biasa)
  Providers/
    AppServiceProvider.php         # Bootstrap observer, event listener, HTTP timeout 600s
    FortifyServiceProvider.php     # Fortify actions, views, rate limiter login
  Services/             # 21 service class (lihat 01b)
  Traits/               # NEEDS VERIFICATION
  Utils/                # NEEDS VERIFICATION
  Webhooks/             # NEEDS VERIFICATION
```

---

## Bootstrap & Konfigurasi

### `bootstrap/app.php` (perlu diverifikasi)
- Standard Laravel 12 application bootstrap
- Middleware `admin` → `EnsureIsAdmin`
- Middleware `customer` → `EnsureIsCustomer`

### `AppServiceProvider.php` — Hal Penting
```php
// Observers
ProductErzap::observe(ProductErzapObserver::class);
WarehouseStock::observe(WarehouseStockObserver::class);

// HTTP Client global timeout (600 detik — untuk AI lokal VPS)
Http::globalTimeout(600);

// Event: merge guest cart on login
Event::listen(Auth\Events\Login::class, MergeGuestCartOnLogin::class);
```

> **Implikasi Migrasi:** Logic merge cart harus diimplementasikan di Go sebagai hook post-login.

---

## Middleware

### `EnsureIsAdmin`
- Redirect ke `/login` jika tidak autentikasi
- Redirect ke `/` jika user tidak memiliki permission apapun DAN bukan superadmin
- Source: `app/Http/Middleware/EnsureIsAdmin.php`

### `EnsureIsCustomer`
- Cek role 'customer'
- Source: `app/Http/Middleware/EnsureIsCustomer.php`

### Framework Middleware yang Relevan
- `auth:sanctum` — API token authentication
- `throttle:60,1` — rate limiting 60 req/menit (public API)
- `throttle:120,1` — rate limiting 120 req/menit (mobile API)
- `throttle:10,1` — rate limiting 10 req/menit (login endpoint)
- `role:superadmin|admin|director` — Spatie role check
- `can:monitoring-kasir` — Spatie permission check

---

## Routing Overview

### `routes/api.php`
- `/api/user` — GET, auth:sanctum
- `/api/user/push-token` — POST, auth:sanctum
- `/api/chat/init` — POST, publik
- `/api/webhooks/xendit/invoice` — POST, publik (signed)
- `/api/accurate/import-items` — GET, publik (semestinya dilindungi)
- `/api/webhooks/accurate` — POST, publik
- `/api/webhooks/approval` — POST, publik (n8n callback)
- `/api/sign-qz` — POST, publik
- `/api/v1/public/trade-in/*` — GET/POST, throttle:60
- `/api/v1/executive/*` — Campuran auth + role
- `/api/v1/mobile/*` — Campuran publik + auth:sanctum, throttle:120

### `routes/web.php`
- Route Livewire/Blade (DIABAIKAN untuk migrasi Go)
- Google OAuth callback
- Erzap webhook (POST, publik)
- Cloudflare R2 media proxy
- QZ Tray sign (GET, publik)

### `routes/console.php`
- Scheduled: `accurate:renew-webhooks` — everyMinute dengan kondisi dinamis
- Scheduled: `orders:expire-mobile` — everyTenMinutes

---

## Queue & Background Jobs

Driver queue: NEEDS VERIFICATION (kemungkinan `database` berdasarkan Dockerfile)

| Job | Trigger | Deskripsi |
|---|---|---|
| `ProcessAccurateWebhookJob` | Webhook Accurate | Proses webhook dari Accurate ERP |
| `ProcessStockAdjustment` | Manual trigger | Eksekusi penyesuaian stok |
| `PushInvoiceToAccurateJob` | Order completion | Push invoice ke Accurate |
| `SendExpoPushNotificationJob` | Various | Kirim push notification via Expo |
| `SyncAccurateCustomerJob` | User create/update | Sync customer ke Accurate |
| `SyncOrderToAccurateJob` | Order events | Sync order ke Accurate (Sales Order) |

---

## Events & Listeners

| Event | Listener |
|---|---|
| `Illuminate\Auth\Events\Login` | `MergeGuestCartOnLogin` |
| `App\Events\MessageSent` | (broadcast via Reverb/Pusher ke channel) |

---

## Observers

| Model | Observer | Side Effects |
|---|---|---|
| `ProductErzap` | `ProductErzapObserver` | NEEDS VERIFICATION (kemungkinan update stock) |
| `WarehouseStock` | `WarehouseStockObserver` | NEEDS VERIFICATION (kemungkinan broadcast/notify) |

---

## Model Observers (via `booted()`)

| Model | Event | Side Effect |
|---|---|---|
| `Order` | `updated` (order_status berubah) | Jika CANCELLED: refundPoints + rollbackCoupon; jika PAID/COMPLETED/PROCESSING/SHIPPED: earnPoints |
| `OrderItem` | `created`, `updated` | Sync `OrderItemSerialNumber` dari field `serial_number` (comma-separated) |
| `ApprovalRequest` | `creating` | Auto-set `business_unit_id`, `branch_id` dari context |

> **CRITICAL:** Logic di `Order::booted()` sangat penting — loyalty earn/refund dan coupon rollback dipicu oleh perubahan status order. Backend Go harus mengimplementasikan logic ini.

---

## Config yang Relevan

- `config/fortify.php` — fitur aktif Fortify (login, register, reset password, two factor?)
- `config/sanctum.php` — token expiry, stateful domains
- `config/permission.php` — Spatie table names, cache
- `config/queue.php` — driver queue
- Semua config perlu diverifikasi lebih lanjut
