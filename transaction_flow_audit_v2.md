# 🔍 Audit Mendalam: Seluruh Flow Transaksi StayKoe

**Auditor:** Senior Fullstack Developer — Spesialisasi Booking/Hospitality Systems  
**Tanggal:** 27 Juli 2026  
**Cakupan:** Online Booking, Walk-in (On-site), Payment, Check-in/out, Cancellation, Refund  

---

## Ringkasan Eksekutif

Setelah mengaudit **17+ file inti** yang membentuk seluruh flow transaksi, saya menemukan **5 celah kritis**, **7 bug medium**, dan **6 ketidaksesuaian** dengan standar industri booking/hospitality. Sistem memiliki fondasi arsitektur yang baik (pessimistic locking, header-detail pattern, service layer), namun terdapat celah-celah yang **bisa menyebabkan kerugian finansial nyata** jika tidak diperbaiki.

---

## I. CELAH KRITIS (Harus Diperbaiki Segera)

### 🔴 K1. Double Affiliate Commission — Komisi Affiliator Dihitung 2x Lipat

| Item | Detail |
|------|--------|
| **File** | [BookingOnSite.php#L126-131](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L126-L131) + [BookingOnSite.php#L422-425](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L422-L425) |
| **Dampak** | **Kerugian finansial langsung** — komisi afiliator dibayar 2× setiap transaksi walk-in cash |
| **Severity** | 🔴 **CRITICAL** |

**Penjelasan:**  
Saat walk-in dengan pembayaran cash (`initialStatus = 'confirmed'`), komisi affiliator di-increment di **dua tempat berbeda**:

1. **Di dalam** `BookingService::createOrderAndBookings()` baris 126-131:
   ```php
   if (!empty($data['affiliator_id']) && $data['initial_status'] === 'confirmed') {
       $affiliator->increment('total_earnings', $commission);
   }
   ```
2. **Setelahnya** di `BookingOnSite::submitBooking()` baris 422-425:
   ```php
   if ($this->appliedAffiliator && $initialStatus === 'confirmed') {
       $this->appliedAffiliator->increment('total_earnings', $commission);
   }
   ```

Hasilnya: affiliator mendapat **komisi ganda** untuk setiap booking walk-in.

---

### 🔴 K2. Double Partner Rewards — Voucher Reward Tercetak 2× Lipat

| Item | Detail |
|------|--------|
| **File** | [BookingService.php#L134-137](file:///d:/APP/staykoe/app/Services/Booking/BookingService.php#L134-L137) + [BookingOnSite.php#L428-454](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L428-L454) |
| **Dampak** | Tamu menerima **2× reward voucher** untuk 1 booking |
| **Severity** | 🔴 **CRITICAL** |

**Penjelasan:**  
Identik dengan masalah K1. Partner rewards di-generate di:
1. `BookingService::createOrderAndBookings()` baris 135 memanggil `generatePartnerRewardsForOrder()`
2. `BookingOnSite::submitBooking()` baris 428-454 melakukan **logika yang sama persis** secara manual

Kedua blok ini berjalan ketika `initialStatus === 'confirmed'` (cash walk-in).

---

### 🔴 K3. Xendit Webhook Route Potensial Gagal Terima — CSRF Blocking

| Item | Detail |
|------|--------|
| **File** | [web.php#L128](file:///d:/APP/staykoe/routes/web.php#L128) |
| **Dampak** | **Pembayaran online bisa tidak ter-confirm** — uang masuk tapi status tetap "pending" |
| **Severity** | 🔴 **CRITICAL** |

**Penjelasan:**  
Webhook Xendit didefinisikan di `web.php` dengan `->withoutMiddleware(['web'])`, tapi ini adalah **cara yang salah** untuk exclude CSRF di Laravel 11+. Route `web.php` secara default memuat middleware group `web` yang includes `VerifyCsrfToken`. Metode `withoutMiddleware(['web'])` bisa menghapus **seluruh web middleware** (sessions, cookies, dll) atau malah **tidak bekerja sama sekali** tergantung versi Laravel.

> [!CAUTION]
> **Solusi yang benar**: Pindahkan route webhook ke `routes/api.php` yang secara default TIDAK memiliki CSRF protection, atau gunakan `$except` array di VerifyCsrfToken middleware.

---

### 🔴 K4. Race Condition pada Auto-Create User di Walk-in

| Item | Detail |
|------|--------|
| **File** | [BookingOnSite.php#L356-372](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L356-L372) |
| **Dampak** | **Duplicate user** bisa dibuat, atau error `Duplicate entry` jika 2 resepsionis booking guest yang sama secara bersamaan |
| **Severity** | 🔴 **CRITICAL** |

**Penjelasan:**  
Auto-create user dilakukan **di luar** `DB::transaction()`. Alurnya:
```
1. Check: User::where('email', email)->first()  ← BUKAN di dalam transaksi
2. Create: User::create(...)                     ← BUKAN di dalam transaksi
3. DB::transaction(function() {                   ← Transaksi baru mulai di sini
       $bookingService->createOrderAndBookings(...)
   })
```

Jika 2 resepsionis mengetik email yang sama bersamaan, kedua proses bisa melewati langkah 1 (karena belum ada user) dan keduanya membuat user baru → **duplicate user + duplicate email** → bisa crash.

---

### 🔴 K5. Payment Success Page Tanpa Auth — Siapapun Bisa Melihat Data Booking

| Item | Detail |
|------|--------|
| **File** | [web.php#L120-124](file:///d:/APP/staykoe/routes/web.php#L120-L124), [PaymentController.php](file:///d:/APP/staykoe/app/Http/Controllers/Payment/PaymentController.php) |
| **Dampak** | **Kebocoran data pribadi** — Nama tamu, unit, harga, kode booking bisa dilihat siapapun yang tahu order code |
| **Severity** | 🔴 **CRITICAL** |

**Penjelasan:**  
Route `/order/{orderCode}/payment/success|failed|status` **tidak dilindungi middleware `auth`**. PaymentController hanya melakukan `where('order_code', $orderCode)->firstOrFail()` tanpa cek siapa yang mengakses. Siapapun yang menebak/mengetahui format order code (`ORD-DDMMYY-XXXXXXXX`) bisa melihat data booking.

---

## II. BUG MEDIUM (Perlu Diperbaiki)

### 🟡 M1. Add-On Tidak Dihitung dalam `total_price` yang Disimpan ke Order

| Item | Detail |
|------|--------|
| **File** | [BookingOnSite.php#L387-389](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L387-L389) |

**Penjelasan:**  
`$this->total_price` sudah termasuk add-ons (baris 266), tapi di `BookingService::createOrderAndBookings()`, subtotal disimpan dari data yang sudah include add-on. Kemudian add-on juga disimpan terpisah di tabel `booking_add_ons`. Ini menyebabkan **inkonsistensi perhitungan** — jika nanti ada fitur "lihat detail harga", add-on bisa terhitung ganda di UI.

---

### 🟡 M2. Coupon Usage Tidak Pernah Di-Decrement untuk Walk-in

| Item | Detail |
|------|--------|
| **File** | [BookingOnSite.php#L417-419](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/BookingOnSite.php#L417-L419) |

**Penjelasan:**  
```php
if ($this->appliedCoupon) {
    // Implement logic decrement usage count if coupon has limits
}
```
Komentar `// Implement logic...` menunjukkan ini **belum diimplementasi**. Coupon bisa dipakai tanpa batas di walk-in. Model `Coupon` juga tidak memiliki field `max_usage` atau `usage_count`, artinya **tidak ada pembatasan pemakaian kupon sama sekali** di seluruh sistem.

---

### 🟡 M3. `updateStatus()` Tidak Validate State Machine — Status Bisa Lompat Acak

| Item | Detail |
|------|--------|
| **File** | [ListBookings.php#L108-146](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/ListBookings.php#L108-L146) |

**Penjelasan:**  
Method `updateStatus()` langsung meng-assign status baru tanpa validasi transisi yang valid:
```php
$booking->status = $newStatus;
$booking->save();
```
Ini memungkinkan transisi yang **tidak masuk akal**:
- `checked_out` → `pending` (sudah checkout tapi kembali pending?)
- `cancelled` → `checked_in` (yang dibatalkan bisa check-in?)
- `pending` → `checked_out` (belum bayar langsung checkout?)

**Standar industri**: Booking status harus mengikuti state machine yang strict:  
`pending → confirmed → checked_in → checked_out`  
`pending → cancelled`  
`confirmed → cancelled`

---

### 🟡 M4. `cancelBooking()` Tidak Cancel Transaksi Xendit yang Sudah Dibuat

| Item | Detail |
|------|--------|
| **File** | [BookingConfirmation.php#L190-208](file:///d:/APP/staykoe/app/Livewire/Pages/BookingConfirmation.php#L190-L208) |

**Penjelasan:**  
Ketika pelanggan online menekan "Batalkan", kode hanya mengubah status order + booking di database lokal, tapi **tidak** memanggil Xendit API untuk expire/cancel invoice yang sudah dibuat. Invoice Xendit bisa tetap aktif selama 24 jam dan tamu masih bisa membayar invoice yang seharusnya sudah dibatalkan. Jika tamu membayar setelah cancel, webhook akan mengubah status kembali ke `confirmed` — menyebabkan **booking zombie**.

---

### 🟡 M5. `selectUser()` Tidak Mengisi Phone dari Profile

| Item | Detail |
|------|--------|
| **File** | [WithCustomerSearch.php#L39](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/Traits/WithCustomerSearch.php#L39) |

**Penjelasan:**  
```php
$this->guest_phone = $user->phone ?? $this->guest_phone;
```
Field `phone` **tidak ada** di model User. Data phone tersimpan di `UserProfile::no_telp`. Akibatnya, ketika resepsionis memilih user dari autocomplete, field phone **tidak pernah terisi otomatis**.

---

### 🟡 M6. Booking Online Tidak Ada Mekanisme Expiry Otomatis

| Item | Detail |
|------|--------|
| **File** | Seluruh sistem |

**Penjelasan:**  
Booking online yang berstatus `pending` (belum bayar) **tidak memiliki scheduler** untuk auto-cancel. Jika pelanggan membuat booking lalu meninggalkan halaman tanpa bayar dan invoice Xendit expired, webhook memang akan cancel order. Tapi jika webhook gagal (network issue, server down), booking tetap `pending` selamanya → **kamar terblokir permanen**.

**Standar industri**: Diperlukan scheduler (cron job) yang mengecek booking pending yang sudah melewati batas waktu (biasanya 1-2 jam) dan otomatis cancel.

---

### 🟡 M7. `updateStatus()` di ListBookings Langsung Sync Order Status — Salah untuk Multi-Room

| Item | Detail |
|------|--------|
| **File** | [ListBookings.php#L122-124](file:///d:/APP/staykoe/app/Livewire/Admin/Booking/ListBookings.php#L122-L124) |

**Penjelasan:**  
```php
if ($booking->booking_order_id) {
    BookingOrder::where('id', $booking->booking_order_id)->update(['status' => $newStatus]);
}
```
Jika order memiliki 2 kamar (Room A dan Room B), dan resepsionis hanya check-in Room A, maka **Order header** langsung berubah ke `checked_in`. Padahal Room B masih `confirmed`. Ini membuat status order **tidak akurat**.

**Standar industri**: Status order header harus diderivasi dari status semua booking children:
- Semua `confirmed` → Order `confirmed`
- Salah satu `checked_in` → Order `partially_checked_in` atau tetap `confirmed`
- Semua `checked_in` → Order `checked_in`
- Semua `checked_out` → Order `checked_out`

---

## III. KETIDAKSESUAIAN DENGAN STANDAR INDUSTRI

### 📋 S1. Tidak Ada Cancellation Policy / Refund Window

Sistem booking properti/hotel standar selalu memiliki:
- **Free cancellation window** (misal: gratis cancel sampai 24 jam sebelum check-in)
- **Cancellation fee** (misal: 50% jika cancel dalam 24 jam)
- **No-refund period** (tidak bisa cancel setelah check-in)

Saat ini `canBeCancelled()` hanya cek apakah status `pending` atau `confirmed`, tanpa mempertimbangkan **waktu**.

---

### 📋 S2. Tidak Ada Guest Count / Jumlah Tamu

Properti real selalu menanyakan **jumlah tamu** (dewasa + anak) untuk:
- Menentukan apakah unit cukup (`max_guests`)
- Menghitung harga extra bed/extra person
- Pelaporan pajak/occupancy

Field `max_guests` ada di Unit tapi **tidak pernah divalidasi** terhadap jumlah tamu aktual.

---

### 📋 S3. Tidak Ada Check-in/Check-out Time

Sistem hanya menyimpan **tanggal** (`check_in_date`, `check_out_date`) tanpa waktu. Pada industri:
- Check-in biasanya mulai pukul 14:00
- Check-out biasanya sampai pukul 12:00
- Early check-in / late checkout bisa dikenakan biaya tambahan

---

### 📋 S4. Tidak Ada Booking Confirmation Email / Notification

Setelah booking berhasil (online maupun walk-in), **tidak ada email konfirmasi** yang dikirim ke tamu. Ini standar wajib di industri hospitality.

---

### 📋 S5. Tidak Ada No-Show Handling

Jika tamu tidak datang pada tanggal check-in, **tidak ada mekanisme otomatis** untuk:
- Menandai booking sebagai "no-show"
- Membebaskan kamar kembali
- Menerapkan no-show fee

---

### 📋 S6. Tidak Ada Audit Trail Perubahan Status

Method `updateStatus()` mengubah status tanpa mencatat **siapa** yang mengubah, **kapan**, dan **dari status apa**. Ini vital untuk:
- Investigasi dispute
- Audit keuangan
- Compliance

---

## IV. RINGKASAN PRIORITAS

| # | Issue | Severity | Effort | Prioritas |
|---|-------|----------|--------|-----------|
| K1 | Double Affiliate Commission | 🔴 Critical | ⚡ Low (hapus duplikasi) | **P0** |
| K2 | Double Partner Rewards | 🔴 Critical | ⚡ Low (hapus duplikasi) | **P0** |
| K3 | Webhook CSRF Blocking | 🔴 Critical | ⚡ Low (pindah ke api.php) | **P0** |
| K4 | Race Condition Auto-Create User | 🔴 Critical | 🔧 Medium | **P0** |
| K5 | Payment Page Tanpa Auth | 🔴 Critical | ⚡ Low (tambah middleware/check) | **P0** |
| M3 | Status Tanpa State Machine | 🟡 Medium | 🔧 Medium | **P1** |
| M4 | Cancel Tidak Expire Invoice Xendit | 🟡 Medium | 🔧 Medium | **P1** |
| M2 | Coupon Usage Tidak Dibatasi | 🟡 Medium | 🔧 Medium | **P1** |
| M1 | Add-On Double Count Potensial | 🟡 Medium | 🔧 Medium | **P1** |
| M5 | Phone Tidak Terisi di Autocomplete | 🟡 Medium | ⚡ Low | **P1** |
| M6 | Tidak Ada Booking Expiry Scheduler | 🟡 Medium | 🏗️ High | **P2** |
| M7 | Multi-Room Status Sync Salah | 🟡 Medium | 🔧 Medium | **P2** |
| S1-S6 | Standar Industri | 📋 Enhancement | 🏗️ High | **P3** |

---

## V. REKOMENDASI AKSI

### Fase 1 — Hotfix (Bisa dikerjakan hari ini)
1. **K1+K2**: Hapus duplikasi affiliate commission & partner rewards di `BookingOnSite.php` (baris 422-454). Biarkan hanya `BookingService` yang menangani ini.
2. **K3**: Pindahkan webhook routes ke `routes/api.php`.
3. **K5**: Tambahkan auth check di `PaymentController` atau tambahkan middleware `auth` + ownership verification.
4. **M5**: Ubah `$user->phone` menjadi `$user->profile?->no_telp` di `WithCustomerSearch`.

### Fase 2 — Perbaikan Penting (1-2 hari)
5. **K4**: Pindahkan auto-create user ke dalam `DB::transaction()`.
6. **M3**: Implementasi state machine validation di `updateStatus()`.
7. **M4**: Panggil `XenditPaymentService::expireInvoice()` saat cancel.
8. **M7**: Derivasi status order dari status children, bukan blind-copy.

### Fase 3 — Peningkatan Kualitas (1 minggu)
9. **M2**: Tambahkan field `max_usage`, `usage_count` di Coupon dan enforce limit.
10. **M6**: Buat scheduled command `php artisan bookings:cancel-expired`.
11. **S6**: Tambahkan tabel `booking_status_logs` untuk audit trail.

> [!IMPORTANT]
> **K1 dan K2 (Double Commission & Double Rewards)** adalah bug finansial yang paling mendesak. Setiap transaksi walk-in cash dengan affiliator saat ini **mengeluarkan komisi 2× lipat**. Ini langsung berdampak pada bottom line bisnis.
