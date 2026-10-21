# 31 — [MOD-05] Customer Loyalty, Points Ledger & CRM Module

**Tanggal:** 21 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/loyalty/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-05] Customer Loyalty, Points Ledger & CRM** mereplikasi seluruh logika perolehan (*earning*), penukaran (*redemption*), buku besar mutasi ganda (*double-entry points ledger*), tingkatan keanggotaan (*membership tiering*), serta manajemen kupon/voucher e-commerce dan toko offline ZedPos.

Modul ini bertanggung jawab penuh atas:

1. **Buku Besar Poin Berprinsip Mutasi Ganda (Double-Entry Points Ledger):**
   - Setiap mutasi saldo poin dicatat secara atomik ke tabel `loyalty_transactions` lengkap dengan saldo sebelum (`balance_before`), mutasi poin (`points`), dan saldo sesudah (`balance_after`).
   - Saldo `points_balance` pada `loyalty_accounts` dijamin selalu konsisten dengan agregasi mutasi transaksi.
2. **Perolehan Poin Transaksi (Point Earning Lifecycle):**
   - Dihitung dari nilai belanja bersih pelanggan (*net subtotal* setelah diskon produk, promo, dan kupon).
   - Rasio standar: Setiap kelipatan **Rp 10.000 = 1 Poin**.
   - Akumulasi belanja (*total_spend*) dan *lifetime_points* bertambah secara atomik.
3. **Penukaran Poin Diskon Kasir (Redeem Points with Concurrency Lock):**
   - Penukaran poin menjadi potongan diskon langsung di kasir POS atau Mobile App.
   - Nilai konversi: **1 Poin = Rp 100**.
   - **Invarian Batas Minimal:** Penukaran minimal adalah **50 Poin** (setara diskon Rp 5.000).
   - Penguncian baris pesimistik `SELECT ... FROM loyalty_accounts WHERE phone_number = ? FOR UPDATE` untuk mencegah race condition / saldo minus saat kasir memproses transaksi.
4. **Pemulihan & Pembatalan Otomatis (Reversal on Cancel/Void):**
   - Jika transaksi dibatalkan:
     - Poin yang pernah ditukarkan (*redeemed*) otomatis dikembalikan ke saldo pelanggan dengan tipe mutasi `REFUND`.
     - Poin yang pernah didapatkan (*earned*) otomatis ditarik kembali dengan tipe mutasi `REFUND_CANCEL`.
     - Akumulasi total belanja dan tier disinkronkan kembali.
5. **Tingkatan Keanggotaan Otomatis (Dynamic Membership Tiering):**
   - Evaluasi otomatis berdasarkan akumulasi belanja riil (`total_spend`):
     - **BRONZE:** Total belanja < Rp 5.000.000
     - **SILVER:** Total belanja Rp 5.000.000 s/d Rp 14.999.999
     - **GOLD:** Total belanja Rp 15.000.000 s/d Rp 49.999.999
     - **PLATINUM:** Total belanja $\ge$ Rp 50.000.000
   - Menyajikan informasi target belanja tersisa untuk naik ke tier berikutnya (*spend to next tier*).
6. **Validasi & Manajemen Kupon Voucher (Ecommerce & Store Vouchers):**
   - Pengecekan status aktif, rentang tanggal `start_date` dan `end_date`, batas minimum belanja (*min purchase amount*), kuota penggunaan total (*usage limit total*), dan kuota per pengguna (*usage limit per user*).
   - Dukungan tipe potongan harga produk (*fixed* & *percentage*) serta gratis ongkir (*shipping fixed* & *shipping percentage*) dengan pembatasan diskon maksimal (*max discount cap*).
   - Pencatatan penggunaan ke `ecommerce_coupon_redemptions` dan *rollback* otomatis jika pesanan dibatalkan.
7. **Kesiapan Migrasi Database Mandiri:**
   - DDL skrip migrasi native Go: `migrations/000005_create_loyalty_and_coupon_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/loyalty`)

```
tokopon-go/internal/modules/loyalty/
├── domain.go              # Entities, DTOs, typed errors, Tiers, TxTypes, CouponTypes, Interfaces
├── service.go             # Pure Domain Service: phone normalization, points calc, tiering, coupon validator
├── repository.go          # Implementasi SQL: FOR UPDATE lock, double-entry ledger, coupon redemptions
├── usecase.go             # UseCase orchestration: GetSummary, CalculateRedeem, Tx Earn/Redeem/Refund
├── handler.go             # Echo HTTP delivery handler (rute /api/v1/pos/loyalty/*, /api/v1/loyalty/*, /api/v1/coupons/*)
├── service_test.go        # Unit test service: normalisasi nomor HP, rasio poin, tier spend, kupon max cap
├── usecase_test.go        # Unit test UseCase: Mock repository, Earn, Redeem, Refund reversal, Summary
└── handler_test.go        # Unit test Handler HTTP: 4 endpoint (Account summary, Redeem calc, Coupon validate, List)
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul Loyalty | Deskripsi |
|---|---|---|
| `loyalty_accounts` | SELECT (FOR UPDATE), INSERT, UPDATE | Profil loyalty pelanggan, saldo poin aktif, lifetime points, akumulasi belanja, dan membership tier |
| `loyalty_transactions` | SELECT, INSERT | Buku besar (*ledger*) mutasi poin (`EARN`, `REDEEM`, `REFUND`, `REFUND_CANCEL`, `ADJUSTMENT`) |
| `ecommerce_coupons` | SELECT (FOR UPDATE), UPDATE | Master kupon/voucher diskon toko & e-commerce |
| `ecommerce_coupon_redemptions` | SELECT, INSERT, DELETE | Riwayat penggunaan voucher per pesanan dan per pengguna |

---

## 4. Spesifikasi Kontrak API

### A. Operasional Kasir POS (`/api/v1/pos`)

| Method | Endpoint | Auth | Peran / Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/pos/loyalty/customer` | JWT | Lookup data member berdasarkan nomor HP (menampilkan saldo poin, nilai rupiah, tier saat ini) |
| `POST` | `/api/v1/pos/loyalty/calculate-redeem` | JWT | Simulasi nilai diskon saat kasir menukarkan sejumlah poin tertentu |
| `POST` | `/api/v1/pos/coupons/validate` | JWT | Validasi kode voucher/kupon di kasir toko offline |

### B. Member & Kupon Publik / Mobile (`/api/v1/loyalty` & `/api/v1/coupons`)

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/loyalty/account` | Public/JWT | Informasi saldo poin, tier, progress belanja, dan nilai rupiah member |
| `GET` | `/api/v1/loyalty/history` | Public/JWT | Riwayat buku besar mutasi poin masuk/keluar dengan filter pagination |
| `POST` | `/api/v1/loyalty/calculate-redeem` | Public/JWT | Kalkulator penukaran poin |
| `GET` | `/api/v1/coupons` | Public | Daftar voucher belanja aktif toko |
| `POST` | `/api/v1/coupons/validate` | Public/JWT | Validasi kupon untuk cart e-commerce / mobile |

---

## 5. Hasil Verifikasi & Pengujian Unit (Unit Test Parity)

Modul diuji secara mandiri menggunakan testing bawaan Go (`go test -v ./internal/modules/loyalty/...`):

- `TestService_NormalizePhone` $\rightarrow$ **PASS** (Normalisasi `08...`, `8...`, `+62...` ke standar `628...`)
- `TestService_PointCalculations` $\rightarrow$ **PASS** (Perolehan kelipatan 10rb = 1 poin, nilai 1 poin = Rp 100)
- `TestService_TierProgression` $\rightarrow$ **PASS** (Evaluasi tier BRONZE/SILVER/GOLD/PLATINUM & kalkulasi selisih target belanja)
- `TestService_ValidateCoupon` $\rightarrow$ **PASS** (Validasi kupon kedaluwarsa, minimum belanja, kuota per-user, dan max discount cap)
- `TestUseCase_GetCustomerSummary` $\rightarrow$ **PASS** (Penyajian summary lengkap profil loyalty)
- `TestUseCase_CalculateRedeem` $\rightarrow$ **PASS** (Validasi batas minimal 50 poin dan batas saldo)
- `TestUseCase_ExecuteEarnAndRefund` $\rightarrow$ **PASS** (Perolehan atomik poin saat transaksi lunas dan penarikan kembali saat transaksi dibatalkan)
- `TestHandler_GetCustomerSummary` $\rightarrow$ **PASS**
- `TestHandler_CalculateRedeem` $\rightarrow$ **PASS**
- `TestHandler_ValidateCoupon` $\rightarrow$ **PASS**
- `TestHandler_ListCoupons` $\rightarrow$ **PASS**

Seluruh suite proyek `go test ./...` lulus 100% tanpa regresi.
