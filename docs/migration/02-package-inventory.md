# 02 — Package Inventory (Composer)

**Dibuat:** 2026-10-19  
**Source:** `composer.json`

---

## Production Dependencies

### Authentication & Session

#### `laravel/fortify: ^1.36`
- **Tujuan:** Backend authentication (login, register, reset password, 2FA)
- **Fitur yang digunakan:** Login view, register view, forgot password, CreateNewUser action, ResetUserPassword action
- **Middleware:** `auth` (session-based)
- **Rate limiting:** Login throttle 5x/menit per email+IP
- **Lokasi pemanggilan:** `FortifyServiceProvider`, `app/Actions/Fortify/`
- **Perilaku penting:** Login web menggunakan session (bukan token)
- **Go equivalent:** Implementasi login/register endpoint dengan bcrypt + JWT/session
- **Risiko:** Fortify hanya untuk web. API sudah pakai Sanctum token terpisah.

#### `laravel/sanctum: ^4.0`
- **Tujuan:** API token authentication
- **Fitur yang digunakan:** `HasApiTokens` di User model, `auth:sanctum` middleware, token issuance/revocation
- **Konfigurasi:** Stateful domains (untuk SPA), token expiry (NEEDS VERIFICATION di config/sanctum.php)
- **Lokasi:** `Api/Mobile/AuthController`, `Api/Executive/ExecutiveAuthController`
- **Go equivalent:** JWT atau opaque token (simpan di DB) 
- **Risiko:** Token format berbeda; perlu strategi cutover

#### `laravel/socialite: ^5.26`
- **Tujuan:** Google OAuth login
- **Fitur yang digunakan:** Google provider
- **Lokasi:** `GoogleCallbackController.php`
- **Go equivalent:** golang.org/x/oauth2
- **Risiko:** Rendah — satu controller saja

---

### Authorization & Permission

#### `spatie/laravel-permission: ^7.2`
- **Tujuan:** Role-Based Access Control (RBAC)
- **Fitur yang digunakan:** `HasRoles` trait di User, `can:` middleware, `role:` middleware, `getAllPermissions()`, `hasAnyRole()`, `hasRole()`
- **Tabel:** `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **Roles yang ditemukan di kode:** `superadmin`, `admin`, `director`, `customer`, `cs`
- **Permissions yang ditemukan:** `monitoring-kasir`, `view-pos`, `trade-in`, `sell-phone`, `view-stock`, `manage-orders`, `manage-users`, `manage-settings`, `manage-promos`, `manage-trade-in`, `manage-buyback`, `warranty-activation`, `access-cs-chat`, `view-reporting`, `view-riwayat-kasir`, `sell-phone-history`, `manage-inbound`, `view-warehouse-stocks`, `manage-qc-templates`, `manage-qc-inspections`, `manage-new-catalog`, `manage-second-catalog`, `manage-categories`, `manage-brands`, `manage-accurate-products`, `manage-accurate-customers`, `view-message-logs`, `view-dashboard`, `can:manage-settings`, `can:cek-unit-rentang-harga`, `can:report-monitoring-kasir`
- **Go equivalent:** Custom RBAC middleware + tabel yang sama
- **Risiko:** Tinggi — banyak permission granular yang harus dipetakan satu per satu

---

### ORM & Database

#### `laravel/framework: ^12.0` (Eloquent ORM built-in)
- **Fitur yang digunakan:** Eloquent ORM, polymorphic relations, global scopes, model observers, booted hooks, casts, accessors, mutators, DB transactions, lockForUpdate, soft deletes (NEEDS VERIFICATION)
- **Go equivalent:** sqlx atau GORM (tergantung keputusan arsitektur)

---

### Media & File Storage

#### `spatie/laravel-medialibrary: ^11.21`
- **Tujuan:** Manajemen file/media (upload, transform, CDN)
- **Fitur yang digunakan:** `HasMedia` + `InteractsWithMedia` di `User`, `Product`, `BusinessUnit`, `SellPhone`; media collections (`cover`, `gallery`, `logo`, `photos`); media conversions (`thumb` 300x300); `getFirstMediaUrl()`
- **Storage:** AWS S3 / Cloudflare R2 (via `league/flysystem-aws-s3-v3`)
- **Go equivalent:** Direct S3/R2 SDK, media URL generation
- **Risiko:** Tinggi — banyak model yang bergantung pada media URL

#### `league/flysystem-aws-s3-v3: 3.0`
- **Tujuan:** S3/R2 driver untuk Laravel Storage
- **Go equivalent:** aws-sdk-go-v2/s3

---

### PDF & Export

#### `barryvdh/laravel-dompdf: ^3.1`
- **Tujuan:** Generate PDF dari Blade template
- **Fitur yang digunakan:** PDF receipt, warranty report, stock opname report
- **Lokasi:** `StockOpnamePdfController`, `WarrantyReportPdfController`
- **Go equivalent:** chromedp atau gopdf (hanya jika PDF dibutuhkan di Go)
- **Risiko:** Rendah untuk API, Medium jika PDF endpoint dimigrasi

#### `maatwebsite/excel: ^3.1`
- **Tujuan:** Export/import Excel
- **Fitur yang digunakan:** Export stock opname, purchase orders
- **Go equivalent:** `excelize` package
- **Risiko:** Rendah — bukan core API

---

### Payment Gateway

#### `xendit/xendit-php: ^7.0`
- **Tujuan:** Payment gateway (Invoice API)
- **Fitur yang digunakan:** `InvoiceApi`, `CreateInvoiceRequest`, webhook callback verifikasi
- **Lokasi:** `XenditService.php`, `XenditWebhookController.php`
- **Config:** API key dari `settings` table (dinamis, bukan .env)
- **Go equivalent:** HTTP client langsung ke Xendit API atau xendit-go SDK
- **Risiko:** Medium — webhook signature harus diimplementasikan di Go

---

### Realtime & Broadcast

#### `laravel/reverb: ^1.0`
- **Tujuan:** WebSocket server (self-hosted Pusher-compatible)
- **Fitur yang digunakan:** Broadcast event `MessageSent` ke channel `conversation.{id}`
- **Go equivalent:** Gorilla WebSocket atau NATS/Redis pub-sub
- **Risiko:** Tinggi — fitur chat realtime bergantung pada ini

#### `pusher/pusher-php-server: ^7.2`
- **Tujuan:** Pusher SDK (kompatibel dengan Reverb)
- **Go equivalent:** Pusher Go SDK atau direct WebSocket

---

### AI

#### `laravel/ai: ^0.7.2`
- **Tujuan:** Laravel AI agent (Gemini/OpenAI integration)
- **Fitur yang digunakan:** `agent()` helper, `DatabaseAnalyzerAgent`, `ExecutiveAiService`
- **Lokasi:** `app/Ai/`, `ExecutiveAiController`, `ExecutiveAiService`
- **Go equivalent:** Direct Gemini/OpenAI HTTP client
- **Risiko:** Medium — AI endpoint bisa dimigrasi sebagai HTTP proxy ke LLM API

---

### Messaging & Notification

Tidak ada paket khusus email — menggunakan Laravel Mail dengan SMTP kustom (`pos_sales` mailer).  
WhatsApp via `CrmWhatsAppService` (HTTP client ke CRM API — NEEDS VERIFICATION endpoint).  
Telegram via webhook URL di `BusinessUnit.telegram_log_webhook` dan `telegram_approval_webhook`.  
Expo Push Notification via `ExpoPushService` (HTTP ke Expo API).

---

### ESC/POS Print

#### `mike42/escpos-php: ^4.0`
- **Tujuan:** Print receipt ke printer kasir via QZ Tray (browser bridge)
- **Fitur yang digunakan:** Generate print payload, sign via private key RSA SHA512 (`QzTrayController`)
- **Go equivalent:** Tidak diperlukan di API backend — print dilakukan di browser
- **Risiko:** Rendah untuk migrasi API

---

## Dev Dependencies (Tidak Dimigrasi)

| Package | Tujuan |
|---|---|
| `barryvdh/laravel-ide-helper` | IDE helper generation |
| `fakerphp/faker` | Seeder & factory data |
| `laravel/pail` | Log viewer |
| `laravel/pint` | Code style (PHP CS Fixer) |
| `laravel/sail` | Docker dev environment |
| `mockery/mockery` | Mocking untuk test |
| `nunomaduro/collision` | Error reporting |
| `phpunit/phpunit: ^11.5.3` | Unit & feature testing |

---

## Ringkasan Risiko per Kategori

| Kategori | Risiko | Alasan |
|---|---|---|
| Auth & Token | TINGGI | Transisi dari Sanctum ke JWT perlu strategi |
| RBAC (Spatie) | TINGGI | Banyak permission granular |
| Media (MediaLibrary) | TINGGI | URL media langsung dipakai di API response |
| Realtime (Reverb) | TINGGI | Chat feature bergantung WebSocket |
| ERP (Accurate) | TINGGI | Logic bisnis kompleks |
| Payment (Xendit) | MEDIUM | Webhook signature harus diimplementasikan |
| AI Integration | MEDIUM | HTTP proxy ke LLM |
| PDF Export | RENDAH | Tidak dikerjakan di fase awal |
| Excel Export | RENDAH | Tidak dikerjakan di fase awal |
| ESC/POS | RENDAH | Client-side, tidak perlu dimigrasi |
