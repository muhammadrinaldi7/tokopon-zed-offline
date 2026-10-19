# 17 — Wave 5: Background Workers, Queue Processors, Order Auto-Expiry & Webhook Receivers

**Dibuat:** 2026-10-19  
**Status:** COMPLETED & VERIFIED ✅  
**Base URL Webhook:** `http://localhost:8080/api/v1/webhooks`  
**Testing:** 34/34 Go Native Tests Passed (`go test -v ./...`)

---

## 1. Daftar Endpoint Webhook yang Selesai Dimigrasikan

| Endpoint | Method | Fungsi | Parameter / Header Kunci |
|---|---|---|---|
| `/webhooks/xendit/invoice` | POST | Penerima callback otomatis pelunasan/kadaluarsa invoice Xendit | Header `x-callback-token`, Payload Xendit Invoice (`status`, `external_id`) |
| `/webhooks/accurate` | POST | Penerima pembaruan event master item/stok dari Accurate Online | Header `X-Accurate-Signature` atau query `token`, Payload array event (`databaseId`, `uuid`, `type`) |

---

## 2. Fitur & Keunggulan Khusus di Backend Go

1. **Order Auto-Expiry Worker (Goroutine Background Ticker):**
   - Menjalankan background worker setiap interval 1 menit untuk memindai pesanan berstatus `WAITING_PAYMENT` dengan `order_channel = 'MOBILE_APP'` yang telah melewati batas `payment_expired_at < NOW()`.
   - Menggunakan mekanisme pembatalan atomik (`CancelOrderWithRollback`) yang mengembalikan stok gudang (`stock + qty`), mengembalikan nomor seri/IMEI ke status `Available`, mengembalikan kuota kupon, dan mengembalikan poin loyalitas pelanggan.
2. **Xendit Payment Callback Processing:**
   - Verifikasi token `x-callback-token` terhadap database setting `xendit_webhook_token`.
   - Saat `PAID` / `SETTLED`: ubah status pembayaran ke `PAID`, update order ke `PROCESSING`, tandai status IMEI/SN menjadi `Sold`, berikan poin loyalty, dan masukkan job `job:order:sync_accurate` ke queue.
   - Saat `EXPIRED`: batalkan pesanan dan pulihkan stok serta nomor seri/IMEI secara otomatis.
3. **Accurate Webhook Receiver:**
   - Verifikasi HMAC SHA-256 signature / fallback URL token.
   - Idempotency guard berbasis tabel `accurate_webhook_logs` menggunakan `uuid` event.
   - Menangani unknown `databaseId` dengan HTTP 200 OK informatif agar Accurate tidak melakukan loop retry tanpa henti.
4. **Queue Worker Consumers:**
   - Registrasi consumer antrian untuk `job:order:sync_accurate` (sinkronisasi ke Accurate Online) dan `job:notification:push` (Expo Push Notification).
5. **Go Native Testing:**
   - Pengujian komprehensif menggunakan native package Go (`testing` & `httptest`) tanpa mock framework eksternal, mencakup 34 test cases yang lulus 100%.
