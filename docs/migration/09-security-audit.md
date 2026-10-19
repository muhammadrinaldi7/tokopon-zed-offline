# 09 — Security Audit

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS

---

## Temuan Keamanan

### KRITIS

#### 1. Endpoint Import Accurate Tidak Terautentikasi
- **Endpoint:** `GET /api/accurate/import-items`
- **Risiko:** Siapa saja bisa trigger import ulang produk dari Accurate tanpa autentikasi
- **Rekomendasi Go:** Wajib tambahkan `auth:sanctum` + role check, atau gunakan shared secret header

#### 2. Webhook Approval Tidak Terautentikasi
- **Endpoint:** `POST /api/webhooks/approval`
- **Risiko:** Siapa saja bisa approve/reject approval request tanpa autentikasi
- **Konteks:** Dipanggil oleh n8n setelah callback dari Telegram
- **Rekomendasi Go:** Tambahkan shared secret header dari n8n, atau verifikasi signature

#### 3. Erzap Webhook Tidak Terautentikasi
- **Endpoint:** `POST /web_service/import_produk_json/new`, dst
- **Risiko:** Siapa saja bisa inject data produk palsu
- **Rekomendasi Go:** Tambahkan IP whitelist atau shared secret header

---

### MEDIUM

#### 4. API Key Accurate Tersimpan di Database
- **Kolom:** `business_units.accurate_token`, `accurate_secret_key`
- **Risiko:** Jika database bocor, semua token Accurate bocor
- **Rekomendasi:** Enkripsi at rest (Laravel `encrypted` cast) — NEEDS VERIFICATION apakah sudah diimplementasikan

#### 5. Xendit API Key Tersimpan di Tabel Settings
- **Kolom:** `settings.xendit_secret_key`
- **Risiko:** Sama seperti di atas
- **Rekomendasi:** Enkripsi at rest atau gunakan environment variable

#### 6. Tidak Ada Rate Limiting di Beberapa Endpoint
- `POST /api/webhooks/xendit/invoice` — tidak ada throttle
- `POST /api/webhooks/accurate` — tidak ada throttle
- Rekomendasi: Tambahkan IP whitelist atau rate limiting untuk webhook endpoints

#### 7. QZ Tray Sign Endpoint Tidak Terautentikasi
- **Endpoint:** `GET /api/sign-qz`, `GET /qz/sign`
- **Fungsi:** Sign request untuk printer kasir dengan private key RSA SHA512
- **Risiko:** Jika private key bocor atau endpoint disalahgunakan
- **Catatan:** Ini by design untuk QZ Tray, tapi tetap perlu diperhatikan

---

### RENDAH

#### 8. `DatabaseAnalyzerAgent` — Akses Database Langsung via AI
- **Risk:** AI agent yang bisa query database secara langsung berpotensi SQL injection jika tidak dikontrol
- **Rekomendasi:** Batasi read-only, whitelist tabel yang bisa diquery

#### 9. `R2MediaProxyController` — Proxy Tanpa Autentikasi
- **Endpoint:** `GET /media-r2/{path}`
- **Fungsi:** Proxy ke Cloudflare R2 (bypass ISP block)
- **Risiko:** Bisa dipakai untuk mengakses file sensitif jika URL diketahui

---

## Password Security

- Hashing: `bcrypt` (via Laravel `password` cast)
- **Baik** — bcrypt adalah standar yang aman
- Go equivalent: `golang.org/x/crypto/bcrypt` — kompatibel langsung

---

## Token Security

- Sanctum token: opaque, disimpan sebagai SHA256 hash di `personal_access_tokens`
- Token expiry: NEEDS VERIFICATION
- Token revocation: `currentAccessToken()->delete()` saat logout
- Multiple tokens per user diperbolehkan (setiap login = token baru)

---

## CORS

- NEEDS VERIFICATION: konfigurasi CORS di `config/cors.php`
- Kemungkinan `allowed_origins: ['*']` untuk dev — harus diperketat di production

---

## Secret Management

**Environment Variables yang Digunakan:**
- `ACCURATE_*` — NEEDS VERIFICATION (atau semua dari DB?)
- `XENDIT_*` — NEEDS VERIFICATION (atau semua dari settings table?)
- `PUSHER_*` / `REVERB_*` — konfigurasi Pusher/Reverb
- `MAIL_*` — konfigurasi email

**Pattern yang Berbahaya:**
- Banyak rahasia tersimpan di tabel `settings` (DB) alih-alih `.env`
- Pro: bisa diubah via admin UI tanpa deploy
- Con: butuh enkripsi at-rest dan audit log

---

## Security Checklist untuk Migrasi Go

- [ ] Autentikasi semua endpoint yang sekarang tidak terautentikasi (khususnya webhooks internal)
- [ ] Verifikasi signature Xendit webhook (`x-callback-token`)
- [ ] Verifikasi token Accurate webhook (`accurate_webhook_token` dari DB)
- [ ] Enkripsi secrets di DB (atau pindah ke environment/vault)
- [ ] CORS yang tepat (hanya allow origin yang diketahui)
- [ ] Rate limiting yang konsisten (terutama login + checkout)
- [ ] SQL injection prevention (gunakan parameterized query)
- [ ] Input validation yang ketat di semua endpoint
- [ ] Audit log untuk operasi sensitif (approval, stock adjustment)
- [ ] Tidak ada secrets di log output
