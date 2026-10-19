# 08 — Background Jobs & Events

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS

---

## Queue Configuration

**Driver:** NEEDS VERIFICATION (kemungkinan `database` berdasarkan `jobs` table yang ada di migrasi)  
**Command dev:** `php artisan queue:listen --tries=1 --timeout=0` (dari `composer.json scripts.dev`)  
**Tries:** 1 (tidak ada retry otomatis di dev)  
**Timeout:** 0 (tidak ada batas waktu)

---

## Background Jobs

### 1. ProcessAccurateWebhookJob

**File:** `app/Jobs/ProcessAccurateWebhookJob.php` (3.6KB)  
**Trigger:** `AccurateWebhookController@handle()` dispatch job ini  
**Queue:** NEEDS VERIFICATION  
**Payload:** Accurate webhook payload + business_unit_id  
**Side Effects:**
- Update `product_accurates` (stok, harga, data produk)
- Update `warehouse_stocks`
- Log ke `accurate_webhook_logs`

**Risiko Migrasi:** Medium — perlu memahami format payload Accurate

---

### 2. ProcessStockAdjustment

**File:** `app/Jobs/ProcessStockAdjustment.php` (4.8KB)  
**Trigger:** Setelah approval STOCK_ADJUSTMENT disetujui  
**Payload:** stock_adjustment_id  
**Side Effects:**
- Eksekusi item-item penyesuaian stok
- Update `warehouse_stocks.quantity`
- Update `product_serial_numbers` status (jika SN-tracked)
- Update status `stock_adjustments`

**Risiko Migrasi:** Medium

---

### 3. PushInvoiceToAccurateJob

**File:** `app/Jobs/PushInvoiceToAccurateJob.php` (5.3KB)  
**Trigger:** Order dibuat/dikonfirmasi (NEEDS VERIFICATION kapan tepatnya)  
**Payload:** order_id  
**Side Effects:**
- Call `AccurateService@createInvoice(order)`
- Simpan `accurate_invoice_number` ke order
- Log error jika gagal

**Risiko Migrasi:** Tinggi — bergantung pada AccurateService yang kompleks

---

### 4. SendExpoPushNotificationJob

**File:** `app/Jobs/SendExpoPushNotificationJob.php` (1.5KB)  
**Trigger:** Berbagai event (order update, chat message baru, dll)  
**Payload:** `{ push_token, title, body, data }`  
**Side Effects:**
- HTTP POST ke Expo push notification API
- Tidak ada retry logic yang terlihat (tries=1)

**Risiko Migrasi:** Rendah — Go HTTP client ke Expo API

---

### 5. SyncAccurateCustomerJob

**File:** `app/Jobs/SyncAccurateCustomerJob.php` (1.5KB)  
**Trigger:** User baru register atau data customer diupdate  
**Payload:** user_id, business_unit_id  
**Side Effects:**
- `AccurateService@syncCustomer(user, businessUnit)`
- Simpan `accurate_customer_no` ke `user_accurate_customers`

**Risiko Migrasi:** Medium

---

### 6. SyncOrderToAccurateJob

**File:** `app/Jobs/SyncOrderToAccurateJob.php` (9.2KB)  
**Trigger:** Order status berubah ke PAID/COMPLETED (NEEDS VERIFICATION)  
**Payload:** order_id  
**Side Effects:**
- `AccurateService@createSalesOrder(order)`
- Map order items → SO line items
- Handle MDR expenses (biaya kartu kredit)
- Simpan `OrderAccurateDoc` (accurate_so_number)
- Kirim notifikasi ke Telegram log webhook

**Risiko Migrasi:** Tinggi — logic mapping yang kompleks

---

## Scheduled Tasks

### 1. `accurate:renew-webhooks`
**File:** `routes/console.php`  
**Schedule:** `->everyMinute()` dengan kondisi `->when(fn() => ...)`  
**Logic kondisi:**
```
Cek settings 'accurate_webhook_renew_frequency':
  - 'disabled' → tidak jalan
  - 'every_minute' → jalan setiap ≥50 detik
  - 'every_five_minutes' → jalan setiap ≥5 menit
  - 'daily' → jalan setiap ≥1440 menit
  - 'weekly' → jalan setiap ≥7 hari
  - 'biweekly' → jalan setiap ≥14 hari
  - 'monthly' (default) → jalan setiap ≥28 hari
Simpan timestamp terakhir di settings 'accurate_webhook_last_renewed_at'
```
**Side Effects:** Daftar ulang webhook URL ke Accurate API

**Risiko Migrasi:** Medium — logic scheduling yang custom perlu diimplementasikan di Go

---

### 2. `orders:expire-mobile`
**File:** `routes/console.php`  
**Schedule:** `->everyTenMinutes()->withoutOverlapping()`  
**Fungsi:** Batalkan otomatis pesanan mobile yang melewati batas waktu pembayaran (`payment_expired_at`)  
**Side Effects:**
- Update `orders.order_status` ke CANCELLED
- Restore `warehouse_stocks.quantity`
- Restore `product_serial_numbers.status` ke available
- Trigger `Order::booted()` logic (refundPoints, rollbackCoupon)

**Risiko Migrasi:** TINGGI — logic rollback stok yang atomik sangat kritis

---

## Events

### MessageSent
**File:** `app/Events/MessageSent.php`  
**Broadcast ke:** Channel `conversation.{conversation_id}` (private Pusher/Reverb channel)  
**Payload:** message object  
**Listener:** None (pure broadcast via WebSocket)  
**Trigger:** `ChatController@sendMessage()`

---

## Listeners

### MergeGuestCartOnLogin
**File:** `app/Listeners/MergeGuestCartOnLogin.php`  
**Trigger:** `Illuminate\Auth\Events\Login`  
**Logic:** Saat user login, merge cart sebagai guest (berdasarkan session/cookie) ke cart user

**Risiko Migrasi:** Medium — perlu memahami bagaimana guest cart disimpan (session ID? cookie?)

---

## Ringkasan untuk Go

| Job / Event | Go Equivalent | Prioritas |
|---|---|---|
| `SyncOrderToAccurateJob` | goroutine / worker queue | TINGGI |
| `PushInvoiceToAccurateJob` | goroutine / worker queue | TINGGI |
| `orders:expire-mobile` (cron) | Go cron job (robfig/cron) | TINGGI |
| `ProcessAccurateWebhookJob` | goroutine queue | MEDIUM |
| `SyncAccurateCustomerJob` | goroutine queue | MEDIUM |
| `SendExpoPushNotificationJob` | goroutine / HTTP call | MEDIUM |
| `ProcessStockAdjustment` | goroutine queue | MEDIUM |
| `accurate:renew-webhooks` (cron) | Go cron job | MEDIUM |
| `MessageSent` (broadcast) | WebSocket hub / Redis pub-sub | TINGGI |
| `MergeGuestCartOnLogin` | Post-login hook di Go | MEDIUM |

**Queue driver rekomendasi Go:** Redis (beanstalkd, atau native DB jika tetap pakai MySQL queue)
