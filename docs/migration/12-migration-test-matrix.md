# 12 — Migration Test Matrix & Verification Suite

**Dibuat:** 2026-10-19  
**Status:** DRAFTED & ALIGNED  
**Tujuan:** Panduan pengujian komprehensif untuk memastikan backend Go memiliki kesetaraan fungsional (functional parity), keandalan concurrency, dan integritas data terhadap database MySQL yang dibagikan bersama Laravel.

---

## 1. Lingkup Pengujian (Test Scope)

Pengujian mencakup 6 domain utama:
1. **Autentikasi & Otorisasi:** JWT, Bcrypt hash compatibility, proteksi route.
2. **Katalog & Stok:** Konsistensi stok agregat dan query performa.
3. **Checkout & Penguncian Transaksi (Anti-Overselling):** Concurrency lock, SN reservation, coupon & loyalty deduction.
4. **Lifecycle Pesanan & Background Jobs:** Order expiry restoration, queue driver database & redis.
5. **Realtime WebSocket:** Handshake auth, room routing, pub-sub broadcast.
6. **API Response Envelope Compatibility:** Verifikasi backward-compatibility dengan aplikasi mobile.

---

## 2. Matriks Uji Rinci (Detailed Test Matrix)

### Kategori 1: Autentikasi & Akun (`harus login`)

| Test ID | Skenario Uji | Data Uji / Aksi | Expected Result di Go Backend | Verifikasi DB / Log |
|---|---|---|---|---|
| **AUTH-01** | Login dengan user terdaftar Laravel | Email valid + password plaintext (dicocokkan dengan hash `$2y$` di DB) | HTTP 200, mengembalikan JWT `token`, `token_type: Bearer`, dan profil `user` | Record `users.password` tidak berubah |
| **AUTH-02** | Login dengan password salah | Email terdaftar + password salah | HTTP 401, `code: "INVALID_CREDENTIALS"` | Tidak ada token yang diterbitkan |
| **AUTH-03** | Registrasi akun mobile baru | Email unik, nama, password, nomor handphone | HTTP 201, `user` dibuat, auto-create `user_profiles`, auto-create `loyalty_accounts` jika nomor HP valid | Tabel `users`, `user_profiles`, `loyalty_accounts` bertambah |
| **AUTH-04** | Registrasi dengan email duplikat | Email yang sudah terdaftar di MySQL | HTTP 422, `code: "VALIDATION_ERROR"`, field `errors: [{field: "email", message: "..."}]` | Tidak ada penambahan baris di tabel `users` |
| **AUTH-05** | Akses endpoint terproteksi tanpa Bearer | Request ke `/api/v1/mobile/orders` tanpa header | HTTP 401, `code: "UNAUTHORIZED"` | Ditolak sebelum masuk ke UseCase |
| **AUTH-06** | Akses endpoint terproteksi dengan token kadaluarsa | Token JWT dengan `exp` masa lampau | HTTP 401, `code: "TOKEN_EXPIRED"` | Ditolak oleh middleware JWT |
| **AUTH-07** | Update Push Token Expo | `POST /api/v1/mobile/auth/push-token` dengan token `ExponentPushToken[...]` | HTTP 200, profil user diperbarui | Kolom `users.push_token` terisi |

---

### Kategori 2: Katalog & Pembacaan Stok

| Test ID | Skenario Uji | Data Uji / Aksi | Expected Result di Go Backend | Verifikasi DB / Log |
|---|---|---|---|---|
| **CAT-01** | Ambil daftar produk katalog mobile | `GET /api/v1/mobile/products?page=1&limit=20` | HTTP 200, amplop respon berisi array `data` dan objek `meta` (page, per_page, total_records) | Query efisien tanpa N+1 query problem |
| **CAT-02** | Filter produk berdasarkan kategori & brand | `GET /api/v1/mobile/products?category_id=2&brand_id=5` | HTTP 200, hanya mengembalikan produk yang sesuai filter | Data terfilter akurat |
| **CAT-03** | Verifikasi stok per gudang online | `GET /api/v1/mobile/products/:id` | Mengembalikan field `total_stock` yang sesuai dengan `SUM(stock)` pada `warehouse_stocks` untuk gudang online | Nilai identik dengan hitungan manual di MySQL |
| **CAT-04** | Format media foto produk | `GET /api/v1/mobile/products/:id` | `thumbnail` dan `image_url` menghasilkan URL S3/Cloudflare R2 publik yang valid | URL dapat dibuka tanpa signature error |

---

### Kategori 3: Checkout, Stok Atomik & Anti-Overselling (KRITIS)

| Test ID | Skenario Uji | Data Uji / Aksi | Expected Result di Go Backend | Verifikasi DB / Log |
|---|---|---|---|---|
| **CHK-01** | Checkout normal dengan stok cukup | Request checkout 1 unit produk non-SN | HTTP 201, `order` dibuat berstatus `WAITING_PAYMENT`, `grand_total` akurat | `warehouse_stocks.stock` berkurang 1 unit secara persisten |
| **CHK-02** | **Race Condition Concurrency Test (Anti-Overselling)** | 10 request paralel secara simultan memesan 1 unit terakhir dari produk yang sama (Stok = 1) | **Tepat 1 request berhasil (HTTP 201)**, dan **9 request lainnya gagal (HTTP 422)** dengan pesan `code: "INSUFFICIENT_STOCK"` | `warehouse_stocks.stock` bernilai tepat `0` (TIDAK BOLEH bernilai negatif `-9`) |
| **CHK-03** | Reservasi Nomor Seri (IMEI) Spesifik | Checkout produk ber-SN dengan menyertakan `serial_numbers: ["IMEI12345"]` | HTTP 201, IMEI berhasil dikunci | Status record di `product_serial_numbers` berubah dari `'Available'` menjadi `'Reserved'` |
| **CHK-04** | Reservasi Nomor Seri Duplikat | 2 request simultan memilih IMEI spesifik yang sama | 1 request berhasil (HTTP 201), 1 request gagal (HTTP 422) | Tidak terjadi alokasi ganda untuk nomor seri yang sama |
| **CHK-05** | Auto-Assign Nomor Seri Tersedia | Checkout 2 unit produk ber-SN tanpa input IMEI | HTTP 201, sistem otomatis mengalokasikan 2 unit nomor seri acak berstatus 'Available' | 2 unit di `product_serial_numbers` berstatus `'Reserved'`, dicatat comma-separated di `order_items` |
| **CHK-06** | Checkout dengan Kupon Diskon E-Commerce | Input `coupon_code: "DISKON100K"` yang valid dan memenuhi syarat min belanja | HTTP 201, `coupon_discount_amount` terpotong, `grand_total` berkurang tepat | Record kupon dicatat di tabel `orders` dan pemakaian di `ecommerce_coupon_usages` |
| **CHK-07** | Checkout dengan Penukaran Poin Loyalty | Input `redeem_points: 500` dengan saldo poin cukup | HTTP 201, nilai diskon poin dipotong dari `grand_total` | Saldo `loyalty_accounts.points_balance` berkurang, transaksi debit dicatat di `loyalty_transactions` |

---

### Kategori 4: Lifecycle Pesanan, Pembatalan & Queue Driver

| Test ID | Skenario Uji | Data Uji / Aksi | Expected Result di Go Backend | Verifikasi DB / Log |
|---|---|---|---|---|
| **LFC-01** | Expiry Pesanan Otomatis (60 Menit) | Background worker memproses order `WAITING_PAYMENT` yang telah melewati `payment_expired_at` | Status order berubah menjadi `CANCELLED` | 1. `warehouse_stocks.stock` bertambah kembali (+qty)<br>2. `product_serial_numbers.status` pulih dari `'Reserved'` ke `'Available'`<br>3. Poin loyalty dan kupon dikembalikan |
| **LFC-02** | Pembayaran Berhasil (Payment Approval) | Admin/Webhook menandai order selesai (`COMPLETED`) | Status order menjadi `COMPLETED`, pembayaran menjadi `PAID` | 1. `product_serial_numbers.status` berubah dari `'Reserved'` menjadi `'Sold'`<br>2. Poin loyalty baru dihitung dan dikreditkan ke pelanggan<br>3. Job sinkronisasi Accurate masuk antrian queue |
| **LFC-03** | **Pluggable Queue: Database Driver Test** | `QUEUE_DRIVER=database` di `.env`, trigger job kirim notifikasi push | Job tersimpan di tabel `jobs`, diambil oleh worker Go dengan `FOR UPDATE SKIP LOCKED`, dan tereksekusi | Status baris di tabel `jobs` terhapus/selesai tanpa deadlocks |
| **LFC-04** | **Pluggable Queue: Redis Driver Switch Test** | Ubah `QUEUE_DRIVER=redis` di `.env`, restart worker | Worker terhubung ke Redis, job diproses via antrian Redis Asynq tanpa eror kode | Job selesai diproses secara transparan |

---

### Kategori 5: Realtime WebSocket (`go websocket ready`)

| Test ID | Skenario Uji | Data Uji / Aksi | Expected Result di Go Backend | Verifikasi DB / Log |
|---|---|---|---|---|
| **WS-01** | Handshake Koneksi WebSocket (Customer) | `GET /ws/connect?token=<valid_jwt>` | Upgrade HTTP 101 Switching Protocols sukses, client terdaftar di Hub, otomatis bergabung ke kanal privat `user.{id}` | In-memory Hub mencatat 1 connection aktif |
| **WS-02** | Handshake Koneksi WebSocket (Guest Chat) | `GET /ws/connect?guest_token=<guest_uuid>` | Upgrade sukses, client terdaftar sebagai sesi tamu | Hub mencatat guest connection |
| **WS-03** | Bergabung ke Room Chat Percakapan | Client mengirim frame `{action: "join_room", room: "conversation.10"}` dengan hak akses valid | Server mengonfirmasi join room sukses | Client ditambahkan ke subscriber map room |
| **WS-04** | Pengiriman & Broadcast Pesan Realtime | CS mengirim pesan teks via API / WS ke room `conversation.10` | Seluruh klien yang tergabung di room `conversation.10` menerima frame event `{event: "message.sent", ...}` dalam < 10ms | Pesan tersimpan di tabel `messages` MySQL |
| **WS-05** | Fallback Push Notification saat Offline | Pesan dikirim ke pelanggan yang tidak sedang aktif di WebSocket | Worker memicu push notification Expo ke `users.push_token` | HTTP POST terkirim ke `https://exp.host/--/api/v2/push/send` |

---

### Kategori 6: Keseragaman Response Wrapper (`wraperr scalabel`)

| Test ID | Skenario Uji | Struktur JSON Respon | Assertion Kritis |
|---|---|---|---|
| **WRAP-01** | Respon Sukses Single Object | `{"success": true, "code": "SUCCESS", "message": "...", "data": {...}}` | Field `success` boolean true, `data` bukan null |
| **WRAP-02** | Respon Sukses Paginated List | `{"success": true, "code": "SUCCESS", "data": [...], "meta": {"page": 1, ...}}` | Field `meta.page`, `meta.per_page`, `meta.total_records` selalu ada dan integer |
| **WRAP-03** | Respon Error Validasi (Form Error) | `{"success": false, "code": "VALIDATION_ERROR", "message": "...", "errors": [...]}` | Array `errors` berisi objek `{field: "...", message: "..."}`, bukan string acak |
| **WRAP-04** | Backward Compatibility Mobile Client | Parsing respon oleh aplikasi Flutter / React Native eksisting | Aplikasi tidak crash saat membaca `res.data.success` atau `res.data.data` |

---

## 3. Prosedur Uji Beban & Integritas Transaksi (Benchmarking)

Untuk memvalidasi kesiapan produksi sebelum live cutover:

1. **Tool Pengujian:** Gunakan `k6` atau `vegeta` untuk HTTP load testing, dan `autobahn-testsuite` untuk WebSocket compliance.
2. **Target Ambang Batas (SLA):**
   - **Throughput Katalog:** Minimal 2.500 requests/detik pada CPU 2-Core (dibandingkan Laravel yang rata-rata 120 req/s).
   - **Latensi Checkout:** p95 < 80ms (termasuk eksekusi transaksi DB dan penguncian baris).
   - **Koneksi Simultan WebSocket:** Mampu menampung 10.000 koneksi bersamaan dengan konsumsi RAM < 200MB.
   - **Zero Data Inconsistency:** Tidak boleh ada saldo minus, stok minus, atau pemakaian ganda kupon/loyalty point saat diuji dengan 100 concurrent checkout request.
