# 15 — Wave 3: Mobile Cart, Checkout & Orders API

**Dibuat:** 2026-10-19  
**Status:** COMPLETED & VERIFIED ✅  
**Base URL:** `http://localhost:8080/api/v1/mobile`  
**Authentication:** Optional Bearer Token / Guest Checkout Supported  
**Testing:** 15/15 Go Native Tests Passed (`go test -v ./...`)

---

## 1. Daftar Endpoint yang Selesai Dimigrasikan

| Endpoint | Method | Fungsi | Parameter Kunci |
|---|---|---|---|
| `/orders/checkout` | POST | Checkout Keranjang & Atomic Stock Locking | `items`, `shipping_address`, `warehouse_id`, `payment_method_id`, `coupon_code`, `redeem_points` |
| `/orders` | GET | Riwayat Pesanan Pelanggan (Paginasi) | `phone`, `status`, `page`, `limit` |
| `/orders/:order_number` | GET | Detail Rincian Pesanan | `:order_number` (path) |
| `/orders/:order_number/cancel` | POST | Pembatalan Pesanan Mandiri & Rollback | `:order_number` (path), `reason` |
| `/orders/:order_number/upload-proof` | POST | Upload Foto Bukti Transfer Bank | `:order_number` (path), `proof` (multipart/form-data) |
| `/payment-methods` | GET | Daftar Rekening Bank Tujuan Transfer | `business_unit_id` |

---

## 2. Fitur & Keunggulan Khusus di Backend Go

1. **Anti-Overselling dengan Atomic Stock Lock:**
   - Menggunakan `SELECT stock FROM warehouse_stocks ... FOR UPDATE` dalam transaksi ACID.
   - Mengeliminasi *race condition* saat flash sale atau checkout serentak.
2. **Reservasi Nomor Seri / IMEI Realtime:**
   - Mengunci dan mengubah nomor seri dari status `Available` menjadi `Reserved` seketika saat order dibuat.
3. **Kupon Diskon & Poin Loyalitas Terintegrasi:**
   - Validasi syarat minimum belanja dan batas pemakaian kupon diskon.
   - Pemotongan saldo poin loyalitas pelanggan dan pencatatan riwayat transaksi `REDEEM`.
4. **Pembatalan Mandiri & Rollback Bersih:**
   - Pemulihan kuota stok otomatis (`stock + qty`), pemulihan status IMEI/SN kembali ke `Available`, rollback kupon, dan *refund* poin loyalitas.
5. **Kompatibilitas Penuh dengan Spatie MediaLibrary:**
   - Unggahan bukti transfer langsung tercatat ke tabel `media` Laravel sehingga langsung dapat diverifikasi oleh admin di dashboard web.
6. **Go Native Testing:**
   - 15 unit & integration tests ditulis menggunakan Go standard testing (`testing` dan `httptest`), menjamin keandalan tanpa dependensi pihak ketiga.
