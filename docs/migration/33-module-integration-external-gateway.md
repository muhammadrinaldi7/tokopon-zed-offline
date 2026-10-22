# 33 — [MOD-07] External Gateway & Integration Engine Module

**Tanggal:** 22 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/integration/`  
**Target Basis Data:** Shared MySQL `zedpost-7` $\rightarrow$ Mandiri (*Independent Go Migrations*) `000007_create_integration_and_jobs_tables.up.sql`  

---

## 1. Ringkasan Modul

Modul **[MOD-07] External Gateway & Integration Engine** mereplikasi seluruh integrasi gateway eksternal, webhook ingestion, sinkronisasi cloud ERP, bot approval, serta pengiriman pesan notifikasi WhatsApp resmi dengan kepatuhan penuh terhadap arsitektur POS offline-first yang andal dan non-blocking.

Modul ini bertanggung jawab penuh atas:

1. **Non-Blocking POS Guarantee (Strict Invariant 1):**
   - Kasir POS dapat menyelesaikan penjualan secara instan (< 100ms) tanpa menunggu respon jaringan dari pihak ketiga (Accurate Online atau WhatsApp Gateway).
   - Pekerjaan integrasi dimasukkan ke dalam antrian asinkron (`queue.Broker`) dengan jaminan *at-least-once delivery*. Kegagalan jaringan atau downtime pihak ketiga tidak akan pernah membatalkan (*rollback*) status transaksi penjualan yang telah sukses dicatat di toko fisik.

2. **Idempotency Protection (Strict Invariant 2):**
   - Sebelum memproses sinkronisasi faktur penjualan ke Accurate Online, worker memeriksa `HasInvoiceDoc` dan keberadaan `accurate_invoice_no`.
   - Jika dokumen telah berhasil dibuat atau sedang diproses, antrian akan melewati (*skip*) eksekusi ulang untuk mencegah terjadinya tagihan ganda (*duplicate sales invoices*) di Accurate Cloud.

3. **Exponential Backoff Retry Strategy (Strict Invariant 3):**
   - Permintaan jaringan eksternal yang gagal dieksekusi dengan jeda bertingkat eksponensial:
     - Percobaan 1: 500ms
     - Percobaan 2: 2.000ms (2 detik)
     - Percobaan 3: 5.000ms (5 detik)
     - Percobaan $\ge$ 4: 10.000ms (10 detik)

4. **Accurate Online ERP Integration & Audit Trail:**
   - Pemetaan dokumen Accurate: `SALES_INVOICE`, `SALES_RECEIPT`, `DP_INVOICE`, `DP_RECEIPT`, `SALES_ORDER`.
   - Pencatatan seluruh jejak dokumen di tabel `order_accurate_docs`.
   - Endpoint manual retry dan status audit untuk backoffice (`/api/v1/backoffice/accurate/...`).

5. **WhatsApp CRM & Notification Engine:**
   - Normalisasi nomor ponsel otomatis (`08...`, `8...`, `+628...` $\rightarrow$ `628...`).
   - Templating pesan struk digital resmi lengkap dengan rincian nomor order, nama toko, tautan unduh faktur/struk PDF.
   - Pencatatan log terperinci di tabel `message_logs` (penerima, kanal, status `SENT`/`FAILED`, response payload).
   - Endpoint manual resend dan status pengiriman untuk staf kasir/backoffice (`/api/v1/backoffice/whatsapp/...`).

6. **Unified Webhook Ingestion Engine:**
   - **Xendit Payment Gateway:** Validasi `x-callback-token`, parsing status faktur (`PAID`, `SETTLED`), dan pembaruan status pembayaran order secara otomatis.
   - **Accurate Online Webhooks:** Ingesti event webhook ERP dan pencatatan audit di `accurate_webhook_logs`.
   - **Telegram Manager Approval:** Verifikasi tanda tangan HMAC-SHA256 (`x-signature-hash`), parsing payload callback bot, dan pembaruan status persetujuan diskon/void.

7. **Dynamic Key-Value Settings Engine:**
   - Akses konfigurasi toko, parameter integrasi, dan feature flag dinamis melalui tabel `settings`.

8. **Dedicated Background Queue Worker:**
   - Binary mandiri `cmd/worker/main.go` yang dapat di-deploy secara terpisah (*decoupled micro-worker*) maupun dijalankan bersamaan dengan API server monolitik.

---

## 2. Struktur Berkas Modul (`internal/modules/integration`)

```
tokopon-go/internal/modules/integration/
├── domain.go              # Entities, DTOs, typed errors, repository & service & usecase interfaces
├── service.go             # Pure Domain Service: Backoff delay, HMAC-SHA256, Idempotency, Phone normalizer, Receipt formatter
├── service_test.go        # Unit test 100% PASS untuk seluruh logika kalkulasi murni
├── repository.go          # Implementasi MySQL: order_accurate_docs, accurate_webhook_logs, message_logs, settings, orders
├── usecase.go             # UseCase orchestration: Retry Accurate, Resend WA, Webhooks, Settings, Worker processors
├── usecase_test.go        # Unit test 100% PASS untuk alur bisnis dan kepatuhan invariant
├── handler.go             # Echo HTTP handlers: Backoffice Accurate, WhatsApp, Settings, dan Webhooks
└── handler_test.go        # Unit test 100% PASS untuk 10 endpoint HTTP REST

tokopon-go/
├── cmd/worker/main.go     # Dedicated Background Worker Binary
└── migrations/
    ├── 000007_create_integration_and_jobs_tables.up.sql
    └── 000007_create_integration_and_jobs_tables.down.sql
```

---

## 3. Matriks Endpoint API

| Metode | URL Path | Akses | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/backoffice/accurate/sync-retry/:order_id` | JWT (Protected) | Pemicu manual sinkronisasi ulang order ke Accurate Online |
| `GET` | `/api/v1/backoffice/accurate/status/:order_id` | JWT (Protected) | Cek status sinkronisasi faktur Accurate dan daftar dokumen terkait |
| `POST` | `/api/v1/backoffice/whatsapp/resend/:order_id` | JWT (Protected) | Pemicu manual kirim ulang struk WhatsApp ke pelanggan |
| `GET` | `/api/v1/backoffice/whatsapp/status/:order_id` | JWT (Protected) | Riwayat audit pengiriman WhatsApp pesan struk order |
| `GET` | `/api/v1/settings` | JWT (Protected) | Ambil seluruh konfigurasi dinamis sistem |
| `GET` | `/api/v1/settings/:key` | JWT (Protected) | Ambil satu nilai pengaturan berdasarkan kunci |
| `POST` | `/api/v1/settings` | JWT (Protected) | Simpan atau perbarui nilai pengaturan (Upsert) |
| `POST` | `/api/v1/webhooks/xendit/invoice` | Token Callback | Ingesti webhook pembayaran faktur dari Xendit |
| `POST` | `/api/v1/webhooks/accurate` | Public / Key | Ingesti webhook notifikasi event dari Accurate Online |
| `POST` | `/api/v1/webhooks/approval` | HMAC-SHA256 | Callback webhook bot Telegram untuk persetujuan transaksi |

---

## 4. Skema Basis Data Mandiri (`000007`)

Tabel yang dibuat pada migrasi Go mandiri:
1. `order_accurate_docs`: Pelacakan nomor dokumen, tipe (`SALES_INVOICE`, `SALES_RECEIPT`), dan payload Accurate Online.
2. `accurate_webhook_logs`: Log event masuk dari Accurate Cloud.
3. `message_logs`: Rekam jejak audit seluruh pesan keluar WhatsApp, status pengiriman, nomor referensi, dan respon gateway.
4. `settings`: Pengaturan konfigurasi dinamis key-value.
5. `jobs`: Antrian database-backed worker (*interoperable* dengan Laravel Database Queue driver).
6. `failed_jobs`: Dead letter queue (DLQ) untuk pelacakan error pekerjaan yang gagal.

---

## 5. Hasil Verifikasi & Unit Testing

```
=== RUN   TestHandler_BackofficeAccurateAndWhatsApp
--- PASS: TestHandler_BackofficeAccurateAndWhatsApp (0.00s)
=== RUN   TestHandler_SettingsEndpoints
--- PASS: TestHandler_SettingsEndpoints (0.00s)
=== RUN   TestHandler_WebhookEndpoints
--- PASS: TestHandler_WebhookEndpoints (0.00s)
=== RUN   TestService_GetBackoffDelay
--- PASS: TestService_GetBackoffDelay (0.00s)
=== RUN   TestService_VerifyHmacSHA256
--- PASS: TestService_VerifyHmacSHA256 (0.00s)
=== RUN   TestService_IsIdempotentInvoice
--- PASS: TestService_IsIdempotentInvoice (0.00s)
=== RUN   TestService_NormalizePhoneNumber
--- PASS: TestService_NormalizePhoneNumber (0.00s)
=== RUN   TestService_FormatReceiptMessage
--- PASS: TestService_FormatReceiptMessage (0.00s)
=== RUN   TestUseCase_AccurateAndWhatsAppWorkflows
--- PASS: TestUseCase_AccurateAndWhatsAppWorkflows (0.00s)
=== RUN   TestUseCase_WebhookWorkflows
--- PASS: TestUseCase_WebhookWorkflows (0.00s)
=== RUN   TestUseCase_SettingsAndWorkerJobs
--- PASS: TestUseCase_SettingsAndWorkerJobs (0.00s)
PASS
ok      tokopon-go/internal/modules/integration 2.477s
```

Full regression test across all modules in `tokopon-go`: **100% PASS**.
