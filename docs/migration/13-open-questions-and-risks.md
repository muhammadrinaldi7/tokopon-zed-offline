# 13 — Open Questions, Resolved Decisions & Risks

**Dibuat:** 2026-10-19  
**Last Updated:** 2026-10-19  
**Status:** ALIGNED & RESOLVED FOR PHASE 1

---

## 1. Keputusan Kunci yang Telah Disetujui (RESOLVED)

Berdasarkan arahan langsung user, 6 pertanyaan arsitektural utama telah diputuskan secara resmi:

| Pertanyaan / Isu | Keputusan Final | Implikasi Teknis di Go |
|---|---|---|
| **Token Strategy (Q1)** | **Harus Login Ulang** | Go tidak memvalidasi tabel `personal_access_tokens` Sanctum. Go menerbitkan token JWT standar (`access_token` + `refresh_token`). Client mobile login ulang ke endpoint Go. |
| **WebSocket Strategy (Q2)** | **Go WebSocket Native Ready** | Menghilangkan Reverb dari backend Go. Go membangun WebSocket Hub & Room native (`gorilla/websocket`), siap digunakan untuk chat & notifikasi realtime. |
| **Migration Strategy (Q3)** | **Strangler Fig Pattern** | Migrasi inkremental menggunakan API Gateway / Nginx reverse proxy. Endpoint Go dipasang satu per satu di depan Laravel tanpa downtime. |
| **Erzap Integration (Q11)** | **SKIP / Dieliminasi** | Seluruh modul sinkronisasi Erzap diabaikan sepenuhnya dari audit dan migrasi Go. |
| **Queue Driver (Q9)** | **Database Sekarang, Redis Ready via ENV** | Lapisan abstraksi antarmuka `QueueBroker`. Default aktif: Database (`jobs` table). Beralih ke Redis cukup ubah `QUEUE_DRIVER=redis` di file `.env`. |
| **Response Wrapper (Q13)** | **Perbaiki & Scalable** | Tidak mengikuti format ad-hoc Laravel. Go menggunakan format amplop JSON seragam: `success`, `code`, `message`, `data`, `meta`, dan `errors` terstruktur. |

---

## 2. Temuan Hasil Verifikasi Kode (VERIFIED)

| Isu | Status Verifikasi | Temuan Kode |
|---|---|---|
| **Sanctum Expiry (Q6)** | **VERIFIED** | `config/sanctum.php` baris 50: `'expiration' => null`. Token lama di Laravel tidak pernah kadaluarsa, namun dengan kebijakan *harus login*, Go memberlakukan masa aktif token JWT terstandarisasi. |
| **Two-Factor Auth (Q7)** | **VERIFIED** | `config/fortify.php` baris 146: Hanya `Features::registration()` dan `Features::resetPasswords()` yang aktif. Fitur 2FA tidak diaktifkan di backend. |
| **Queue Configuration (Q9)** | **VERIFIED** | `config/queue.php` baris 16: Menggunakan driver default `'database'` dengan tabel `'jobs'`. |
| **WebSocket Channels (Q2)** | **VERIFIED** | `routes/channels.php`: Memiliki channel privat `App.Models.User.{id}`, `conversation.{conversationId}`, dan `cs-chats` dengan otorisasi berbasis user id dan permission `access-cs-chat`. |
| **Order Hooks & Stock Lock** | **VERIFIED** | `MobileOrderService.php` baris 104 & 126 menggunakan `lockForUpdate()` pada tabel `warehouse_stocks` dan `product_serial_numbers`. Go wajib mereplikasi transaksi eksplisit ini. |

---

## 3. Pertanyaan Tersisa (Low Priority / Non-Blocking)

#### Q5: Apakah `products.total_stock` computed atau stored integer?
- Sebagian query menggunakan `products.total_stock`, sementara query checkout mengacu langsung ke `warehouse_stocks.stock`.
- **Rencana Go:** Query katalog membaca stok langsung dari agregat `warehouse_stocks` untuk menjamin konsistensi mutlak.

#### Q10: Endpoint CrmWhatsAppService
- Digunakan untuk notifikasi receipt transaksi.
- Tidak menghambat Wave 1-4, akan diaudit saat pengerjaan Wave 5.

#### Q12: Nilai default SettingService di Database
- Format key-value pada tabel `settings`.
- Go akan membuat repository `SettingRepository` yang membaca konfigurasi dinamis (misal: threshold poin loyalty, batas waktu expiry pesanan).

---

## 4. Matriks Risiko Utama & Rencana Mitigasi

### RISIKO SANGAT TINGGI

#### R1: Kompleksitas Akuntansi Accurate ERP (`AccurateService.php` 112KB)
- **Tingkat Risiko:** Sangat Tinggi (Berdampak pada keuangan)
- **Mitigasi:**
  1. Sinkronisasi Accurate tidak dilakukan secara sinkron pada proses checkout, melainkan melalui Queue Worker (`Wave 5`).
  2. Dibuat test harness / staging environment dengan database dummy Accurate untuk menguji payload transaksi sebelum rilis.
  3. Menyediakan logging transaksi lengkap pada tabel audit sync Accurate.

#### R2: Race Condition Stok (Overselling)
- **Tingkat Risiko:** Sangat Tinggi
- **Mitigasi:**
  1. Go menggunakan transaksi database atomik dengan isolasi `LevelReadCommitted` dan klausa `SELECT ... FOR UPDATE` pada tabel `warehouse_stocks`.
  2. Unit serial number (`product_serial_numbers`) langsung dikunci dengan status `'Reserved'` saat pesanan dibuat.
  3. Uji beban konkuren (concurrency test) dengan k6 wajib lolos sebelum rilis `Wave 3`.

#### R3: Side-Effects Status Order (`Order::booted`)
- **Tingkat Risiko:** Tinggi
- **Mitigasi:**
  1. Go meniadakan event listener implisit (*hidden magic*).
  2. Transisi status order dibungkus dalam *State Machine* eksplisit di `OrderUseCase`:
     - `PENDING -> COMPLETED`: set status 'Sold' pada serial number, catat perolehan poin loyalty, dispatch sync Accurate.
     - `PENDING -> CANCELLED`: kembalikan kuota stok agregat, pulihkan status serial number ke 'Available', batalkan pemakaian kupon/poin.

---

### RISIKO TINGGI

#### R4: Multi-Tenant Business Unit Scoping
- **Tingkat Risiko:** Tinggi
- **Mitigasi:**
  - Middleware Go mengidentifikasi `business_unit_id` dari header request atau payload produk.
  - Repository Go menerapkan parameter `business_unit_id` pada seluruh kueri manipulasi data.

#### R5: Integrasi Storage Media (S3 / Spatie MediaLibrary)
- **Tingkat Risiko:** Sedang
- **Mitigasi:**
  - Go menggunakan AWS SDK for Go v2 untuk berinteraksi langsung dengan bucket S3 / Cloudflare R2 yang sama.
  - Pola penamaan file dan direktori disesuaikan dengan struktur penyimpanan Spatie eksisting agar URL gambar lama tetap dapat diakses.
