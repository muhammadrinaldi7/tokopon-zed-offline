# 07 — External Integrations Audit

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS

> **PENTING:** Dokumen ini TIDAK memuat secret key, API token, password, atau kredensial apapun.

---

## 1. Accurate Online ERP

**Purpose:** Sistem ERP untuk pencatatan akuntansi, invoice penjualan, data produk/vendor.  
**Service:** `AccurateService.php` (112KB — terbesar di aplikasi)  
**Webhook Receiver:** `AccurateWebhookController.php`

### Konfigurasi
- Host URL per Business Unit (`business_units.accurate_host`)
- Token per Business Unit (`business_units.accurate_token`)
- Database ID per Business Unit (`business_units.accurate_database_id`)
- Secret Key untuk webhook verification

### Operasi yang Diidentifikasi
| Operasi | Method | Deskripsi |
|---|---|---|
| Import Products | GET | Fetch semua item dari Accurate ke `product_accurates` |
| Create Sales Order | POST | Kirim order terkonfirmasi ke Accurate sebagai SO |
| Create Invoice | POST | Buat invoice di Accurate |
| Sync Customer | POST/PUT | Upsert customer di Accurate |
| Sync Vendor | POST/PUT | Upsert vendor di Accurate |
| Webhook Receive | POST | Terima notifikasi perubahan dari Accurate (item, stok) |
| Renew Webhooks | POST | Daftarkan ulang webhook URL ke Accurate (scheduled) |
| Serial Number Sync | - | `SerialNumberSyncService.php` (35KB) — sync SN dari Accurate |
| Return Sync | - | `AccurateReturnSyncService.php` (26KB) — sync return ke Accurate |

### Webhook dari Accurate
- **Endpoint:** `POST /api/webhooks/accurate`
- **Verifikasi:** Token dari `business_unit.accurate_webhook_token`
- **Payload:** NEEDS VERIFICATION (struktur payload Accurate)
- **Side effect:** Dispatch `ProcessAccurateWebhookJob`

### Scheduled Task
- `accurate:renew-webhooks` — berjalan setiap menit, tapi hanya execute berdasarkan frekuensi setting (`every_minute`, `every_five_minutes`, `daily`, `weekly`, `biweekly`, `monthly`)

### Risiko Migrasi
- `AccurateService.php` sangat besar (112KB) — perlu audit mendalam
- Multi-tenant: setiap BU punya konfigurasi Accurate berbeda
- Token Accurate disimpan di DB (bukan .env) → Go perlu load dari DB
- Webhook renewal logic kompleks

---

## 2. Xendit (Payment Gateway)

**Purpose:** Payment gateway untuk pembuatan invoice dan menerima pembayaran online.  
**Service:** `XenditService.php`  
**Package:** `xendit/xendit-php: ^7.0`

### Konfigurasi
- API Key (`xendit_secret_key`) — disimpan di tabel `settings`, bukan .env
- Payment channels — disimpan di `settings.xendit_payment_channels` (array)

### Operasi
| Operasi | Deskripsi |
|---|---|
| Create Invoice | Buat invoice Xendit dari Order, return `invoice_url` |
| Receive Callback | Terima notifikasi pembayaran dari Xendit |

### Invoice Creation Detail
```
XenditService@createInvoice(Order):
  - external_id: order.order_number
  - amount: order.grand_total
  - invoice_duration: 86400 (24 jam)
  - customer: { name, email, mobile_number dari shipping_address_snapshot }
  - items: order.items (nama + qty + price)
  - payment_methods: dari settings (bisa VA, EWALLET, dll)
  - success_redirect_url: route('orders.confirmation', order.id)
  - failure_redirect_url: route('orders.show', order.id)
```

### Webhook Callback
- **Endpoint:** `POST /api/webhooks/xendit/invoice`
- **Auth:** Header `x-callback-token` (NEEDS VERIFICATION — apakah diverifikasi signature?)
- **Payload:** Xendit Invoice object
- **Side effects:** Update `order.order_status`, trigger loyalty earn, kirim notifikasi WA/email

### Risiko Migrasi
- API key dari DB (bukan .env) — Go harus load dari tabel `settings`
- Webhook token verification harus diimplementasikan
- Redirect URLs harus disesuaikan dengan frontend baru

---

## 3. WhatsApp CRM (CrmWhatsAppService)

**Purpose:** Kirim pesan WhatsApp ke customer (receipt, notifikasi, approval).  
**Service:** `CrmWhatsAppService.php` (24KB)

### Konfigurasi
- NEEDS VERIFICATION: endpoint CRM WA (kemungkinan internal atau WABLAS/Fonnte/WAToken)
- Token autentikasi via `settings` table

### Operasi yang Diperkirakan
- Kirim receipt order via WA
- Kirim notifikasi sell phone approval ke customer
- Kirim notifikasi warranty claim
- Kirim OTP atau konfirmasi lainnya

### Risiko Migrasi
- Perlu audit `CrmWhatsAppService.php` secara menyeluruh
- Endpoint dan format request belum terverifikasi

---

## 4. Telegram Webhook

**Purpose:** Approval workflow — notifikasi dan callback approval via Telegram bot.  
**Konfigurasi:** Per Business Unit di `business_units.telegram_approval_webhook` dan `telegram_log_webhook`

### Flow
```
1. ApprovalService membuat ApprovalRequest
2. Notifikasi dikirim ke Telegram webhook BU (format pesan dengan tombol Setuju/Tolak)
3. Director menekan tombol di Telegram
4. Telegram webhook (via n8n) memanggil POST /api/webhooks/approval
5. ApprovalController@apiProcess() memproses aksi
```

### Risiko Migrasi
- Telegram bot setup per BU (konfigurasi di DB)
- n8n sebagai middleware — Go harus tetap kompatibel dengan format callback n8n
- Endpoint `/api/webhooks/approval` tidak terautentikasi — perlu diamankan

---

## 5. Expo Push Notifications

**Purpose:** Push notification ke mobile app pelanggan.  
**Service:** `ExpoPushService.php`  
**Job:** `SendExpoPushNotificationJob`

### Konfigurasi
- Expo push notification API (tidak perlu API key khusus)
- `users.push_token` disimpan di DB, diupdate via `POST /api/user/push-token`

### Flow
```
Event terjadi (order update, chat message)
  → SendExpoPushNotificationJob::dispatch(push_token, title, body, data)
  → HTTP POST ke https://exp.host/--/api/v2/push/send
```

### Risiko Migrasi
- Rendah — HTTP API yang sederhana
- Go tinggal implement HTTP client ke Expo API

---

## 6. Erzap (Legacy POS Sync)

**Purpose:** Sinkronisasi produk dan stok dari sistem POS Erzap lama.  
**Controller:** `ErzapProductController`  
**Routes:**
- `POST /web_service/import_produk_json/new.json`
- `POST /web_service/import_produk_json/new`
- `POST /web_service/sinkronisasi_stok/new`

### Risiko Migrasi
- Endpoint TIDAK terautentikasi
- Sistem legacy — mungkin sudah tidak aktif atau frekuensi rendah
- NEEDS VERIFICATION: apakah Erzap masih aktif digunakan

---

## 7. Biteship (Shipping)

**Purpose:** Perhitungan ongkos kirim.  
**Service:** `BiteshipService.php` (2.9KB — kecil)

### Operasi
- NEEDS VERIFICATION: apakah dipakai di checkout mobile atau hanya kalkulasi

---

## 8. Laravel Reverb / Pusher (Realtime)

**Purpose:** WebSocket untuk chat realtime.  
**Package:** `laravel/reverb: ^1.0` + `pusher/pusher-php-server: ^7.2`

### Konfigurasi
- NEEDS VERIFICATION: apakah pakai Reverb (self-hosted) atau Pusher cloud
- Channel: `conversation.{id}` (private channel)
- Event: `MessageSent`

### Risiko Migrasi
- Tinggi — fitur chat bergantung pada WebSocket
- Go perlu implement WebSocket server atau hub (e.g., Gorilla WebSocket)
- Client mobile harus tetap bisa connect (kompatibel)

---

## 9. Google OAuth

**Purpose:** Login dengan akun Google untuk web app.  
**Package:** `laravel/socialite: ^5.26`  
**Hanya web — tidak di API mobile**

---

## 10. Laravel AI (Gemini/OpenAI)

**Purpose:** AI Executive Assistant untuk analitik dan chat.  
**Package:** `laravel/ai: ^0.7.2`  
**Service:** `ExecutiveAiService.php` (40KB)

### Operasi
- Chat dengan AI tentang data bisnis
- Summarize laporan
- `DatabaseAnalyzerAgent` — AI yang bisa query database langsung

### Risiko Migrasi
- Medium — Go bisa HTTP proxy ke Gemini/OpenAI API
- DatabaseAnalyzerAgent perlu re-implementasi yang hati-hati (security concern)

---

## Ringkasan Risiko Integrasi

| Integrasi | Risiko | Prioritas Migrasi |
|---|---|---|
| Accurate ERP | SANGAT TINGGI | Tinggi (core bisnis) |
| Xendit Payment | TINGGI | Tinggi (revenue path) |
| Telegram Approval | TINGGI | Medium |
| Realtime Chat (Reverb) | TINGGI | Medium |
| WA CRM | MEDIUM | Medium |
| Expo Push | RENDAH | Medium |
| Biteship | RENDAH | Rendah |
| Google OAuth | RENDAH | Rendah (hanya web) |
| Laravel AI | MEDIUM | Rendah |
| Erzap | RENDAH | Rendah (legacy) |
