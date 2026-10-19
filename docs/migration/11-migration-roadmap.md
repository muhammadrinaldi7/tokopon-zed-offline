# 11 — Migration Roadmap (Strangler Fig Strategy)

**Dibuat:** 2026-10-19  
**Status:** PROPOSED & READY  
**Pola Migrasi:** Strangler Fig (Inkremental, Zero-Downtime, Shared Database)

---

## 1. Prinsip & Strategi Pelaksanaan

1. **Zero-Downtime:** Sistem Laravel eksisting tetap melayani operasional toko fisik (POS) dan backoffice Livewire selama proses migrasi berlangsung.
2. **Reverse Proxy Routing:** Traffic HTTP diarahkan ke API Gateway (Nginx). Endpoint yang siap di Go akan di-route ke backend Go; sisanya dialihkan ke Laravel.
3. **Database Sharing:** Selama masa transisi, Go dan Laravel mengakses database MySQL yang sama tanpa mengubah nama tabel, kolom, atau tipe data eksisting.
4. **Re-Login Policy:** Tidak ada konversi sesi Sanctum. Klien mobile/web diarahkan untuk login ulang melalui endpoint auth Go saat beralih.
5. **Erzap Dieliminasi:** Endpoint dan fitur sinkronisasi Erzap diabaikan sepenuhnya dari roadmap migrasi.
6. **Queue Pluggable:** Worker dijalankan dengan Database driver terlebih dahulu; siap beralih ke Redis via ENV saat infrastruktur siap.
7. **WebSocket Go:** WebSocket Go dibangun paralel dan diaktifkan untuk fitur chat serta notifikasi realtime.

---

## 2. Peta Gelombang Migrasi (Full Rebuild Migration Waves)

```
[ Phase 0: Foundation Architecture ]
        │
[ Wave 1: Auth & User Profile (Staff & Customer) ] ───> ✅ COMPLETED (5dffb7b)
        │
[ Wave 2: Mobile Product Catalog & Stock Read APIs ] ──> ✅ COMPLETED (a9d6194)
        │
[ Wave 3: Mobile Cart, Checkout & Orders ] ────────────> ✅ COMPLETED (043b202)
        │
[ Wave 4: Native Go WebSocket & Live Chat CS Engine ] ─> ✅ COMPLETED (622479d)
        │
[ Wave 5: Background Workers, Auto-Expiry & Webhooks ] > ✅ COMPLETED (124b0f4)
        │
[ Wave 6: Approval, Warranty, Trade-In & Executive ] ──> ✅ COMPLETED (44 Tests PASS)
        │
[ Wave 7: Web POS Kasir Toko Offline API ] ────────────> ⏳ IN PROGRESS (Shift, Scan SN, Split Payment, ESC/POS)
        │
[ Wave 8: Store Inventory, Stock Opname & Inbound PO ] > 📋 UPCOMING (Gudang Fisik & Audit IMEI)
        │
[ Wave 9: Retail Trade-In, Buyback & Device Passport ] > 📋 UPCOMING (Toko Fisik & QC History)
        │
[ Wave 10: Backoffice Master Data & Store Settings ] ──> 📋 UPCOMING (Katalog, Salesman, Promo)
        │
[ Wave 11: Store Reporting, Financials & Full Cutover] > 📋 FINAL (Full Retirement of Laravel)
```

---

## 3. Rincian Tiap Gelombang (Waves Detail)

### Phase 0: Project Setup & Fondasi Arsitektur
**Estimasi Durasi:** 3 - 5 Hari  
**Target Luaran:** Repositori Go siap pakai dengan arsitektur standar produksi.

- [ ] Inisialisasi Go Module (`go mod init tokopon-backend`)
- [ ] Setup struktur folder Clean Architecture (`cmd/`, `internal/`, `pkg/`, `config/`)
- [ ] Konfigurasi database connection pool MySQL menggunakan `sqlx` (tuning: `SetMaxOpenConns`, `SetMaxIdleConns`, `SetConnMaxLifetime`)
- [ ] Implementasi paket respon seragam `pkg/response` (Unified Response Envelope)
- [ ] Implementasi abstraksi queue `pkg/queue` dengan driver default database (`jobs` table) dan driver Redis (`asynq` stub)
- [ ] Setup structured logging (`zerolog`) dan custom HTTP error handler di Echo
- [ ] Setup Dockerfile dan konfigurasi Nginx reverse proxy lokal

---

### Wave 1: Authentication & User Profile (`harus login`)
**Estimasi Durasi:** 4 - 6 Hari  
**Target Luaran:** Seluruh alur login, pendaftaran akun mobile, dan profil beralih ke Go JWT.

- [ ] Implementasi domain `User` & `UserProfile`
- [ ] Repository: query user berdasarkan email/phone, create user, verifikasi hash bcrypt Laravel
- [ ] UseCase Auth:
  - `LoginUseCase`: verifikasi password, generate JWT Access Token & Refresh Token
  - `RegisterUseCase`: registrasi user baru, auto-create profile, create default loyalty account
  - `RefreshTokenUseCase`: rotasi token
- [ ] Delivery HTTP:
  - `POST /api/v1/mobile/auth/login`
  - `POST /api/v1/mobile/auth/register`
  - `POST /api/v1/mobile/auth/refresh`
  - `GET /api/v1/mobile/auth/me`
  - `PUT /api/v1/mobile/auth/profile`
  - `POST /api/v1/mobile/auth/push-token`
- [ ] Middleware `JWTMiddleware`: validasi token JWT & injeksi `claims` ke Echo Context
- [ ] Route Nginx: alihkan `/api/mobile/auth/*` ke Go

---

### Wave 2: Catalog, Product & Inventory Read APIs
**Estimasi Durasi:** 5 - 7 Hari  
**Target Luaran:** Endpoint baca produk berkecepatan tinggi diambil alih oleh Go, mengurangi beban CPU Laravel secara drastis.

- [ ] Implementasi domain `ProductAccurate`, `Category`, `Brand`, `WarehouseStock`
- [ ] Repository:
  - List produk dengan pagination, filter kategori, brand, status second-hand
  - Detail produk beserta varian harga dan foto media (S3 URL generation)
  - Agregasi stok realtime per gudang online (`warehouse_stocks`)
- [ ] Delivery HTTP:
  - `GET /api/v1/mobile/products`
  - `GET /api/v1/mobile/products/:id`
  - `GET /api/v1/mobile/categories`
  - `GET /api/v1/mobile/brands`
  - `GET /api/v1/mobile/banners`
  - `GET /api/v1/mobile/warehouses`
- [ ] Route Nginx: alihkan route katalog mobile ke Go

---

### Wave 3: Mobile Cart, Checkout & Transaksi Order
**Estimasi Durasi:** 7 - 10 Hari  
**Target Luaran:** Transaksi checkout inti berjalan di Go dengan jaminan anti-overselling via `lockForUpdate`.

- [ ] Implementasi domain `Order`, `OrderItem`, `OrderPayment`, `WarehouseStock`, `ProductSerialNumber`
- [ ] Service/UseCase:
  - `OrderCheckoutUseCase`:
    - Transaksi database atomik
    - Penguncian stok (`SELECT ... FOR UPDATE` pada `warehouse_stocks`)
    - Reservasi nomor seri/IMEI (`status = 'Reserved'`)
    - Validasi kupon diskon e-commerce & kalkulasi potongan
    - Validasi poin loyalty pelanggan & kalkulasi diskon poin
    - Pembuatan record `orders`, `order_items`, dan `order_payments`
  - `OrderQueryUseCase`:
    - Riwayat pesanan pelanggan (`GET /api/v1/mobile/orders`)
    - Detail status pesanan (`GET /api/v1/mobile/orders/:order_number`)
  - `PaymentProofUseCase`:
    - Upload bukti bayar transfer manual ke S3 / Cloudflare R2
    - Update status ke `WAITING_VERIFICATION`
- [ ] Delivery HTTP:
  - `POST /api/v1/mobile/orders/checkout`
  - `GET /api/v1/mobile/orders`
  - `GET /api/v1/mobile/orders/:order_number`
  - `POST /api/v1/mobile/orders/:id/payment-proof`
  - `POST /api/v1/mobile/orders/:id/cancel`
- [ ] Route Nginx: alihkan seluruh order API ke Go

---

### Wave 4: Native Go WebSocket & Live Chat (`go websocket ready`)
**Estimasi Durasi:** 5 - 7 Hari  
**Target Luaran:** Chat realtime pelanggan & CS beroperasi tanpa dependensi Laravel Reverb.

- [ ] Implementasi `Hub` & `Client` manager (Gorilla WebSocket)
  - Room management: `conversation.{id}`, `user.{id}`, `cs-chats`
  - Connection lifecycle: Handshake auth (JWT Bearer / Guest Token via URL query)
  - Heartbeat / Ping-Pong liveness check
- [ ] Domain & UseCase:
  - `InitConversationUseCase`: inisialisasi sesi pelanggan / tamu, auto-greeting
  - `SendMessageUseCase`: simpan pesan ke DB, upload attachment gambar, broadcast frame WS ke room
  - `MarkAsReadUseCase`: update status dibaca
- [ ] Delivery WebSocket & HTTP:
  - `GET /ws/connect` (Upgrade HTTP ke WebSocket)
  - `POST /api/v1/mobile/chat/init`
  - `GET /api/v1/mobile/chat/:conversation_id/messages`
  - `POST /api/v1/mobile/chat/:conversation_id/messages`
  - `POST /api/v1/mobile/chat/:conversation_id/read`
- [ ] Route Nginx: konfigurasi `proxy_pass` dengan `Upgrade` header untuk `/ws/`

---

### Wave 5: Background Jobs & Integrasi Eksternal
**Estimasi Durasi:** 6 - 8 Hari  
**Target Luaran:** Worker Go menangani sinkronisasi Accurate, Xendit Webhook, dan auto-cancellation order kadaluarsa.

- [ ] Queue Worker Service (`cmd/worker/main.go`):
  - Menjalankan processor job berbasis Database Queue (atau Redis jika ENV aktif)
- [ ] Implementasi Processors:
  - `OrderExpiryProcessor`: membatalkan pesanan yang belum dibayar dalam 60 menit, memulihkan kuota stok (`stock + qty`), dan mengembalikan status SN ke `Available`
  - `AccurateOrderSyncProcessor`: mengirim pesanan selesai ke Accurate Online API sebagai Sales Order / Sales Invoice
  - `ExpoPushNotificationProcessor`: mengirim push notification via Expo HTTP API
- [ ] Webhook Receiver:
  - `POST /api/v1/webhooks/xendit/invoice`: verifikasi signature callback token Xendit, set order `COMPLETED`, tandai SN `Sold`, picu Accurate sync job
  - `POST /api/v1/webhooks/accurate`: terima pembaruan stok/harga dari Accurate
- [ ] Route Nginx: alihkan webhook endpoint ke Go

---

### Wave 6: Approval Workflow, Warranty, & Executive Metrics
**Estimasi Durasi:** 6 - 8 Hari  
**Target Luaran:** Migrasi alur approval via Telegram n8n, klaim garansi, dan API metrik eksekutif.

- [x] Approval Engine di Go:
  - Handler dispatcher: `SellPhone`, `StockOpname`, `StockAdjustment`, `Discount`, dll.
  - Endpoint callback Telegram: `POST /api/v1/webhooks/approval`
- [x] Warranty & SellPhone Service:
  - Cek masa berlaku garansi IMEI
  - Form pengajuan sell phone / buyback
- [x] Executive Analytics API:
  - Query ringkasan omset penjualan harian/bulanan
  - Laporan laba kotor & HPP

---

### Wave 7: Web POS Kasir Toko Offline API (Shift, Scan SN, Split Payment, ESC/POS)
**Estimasi Durasi:** 7 - 10 Hari  
**Target Luaran:** Seluruh alur kerja kasir fisik toko offline (yang sebelumnya di Livewire `Zoffline\Pos`) beralih 100% ke REST API Go berkecepatan tinggi.

- [x] Domain & Entity POS (`internal/domain/pos.go`):
  - `CashierShift`, `CashierShiftDenomination`, `Salesman`, `PosProductItem`, `PosScannedSN`, `PosPaymentMethod`, `PosPaymentRate`, `PosCheckoutRequest`, `PosCheckoutResponse`
- [ ] Repository POS (`internal/repository/pos_repository.go`):
  - Query shift kasir aktif, buka shift baru, tutup shift & hitung rekonsiliasi kas
  - Query produk katalog POS per gudang kasir aktif
  - Scan SN / IMEI real-time & verifikasi kelulusan QC Serah Terima produk second
  - Lookup walk-in customer & auto-create customer baru
  - Query salesman aktif (`employes`) & metode pembayaran + rates
  - Riwayat transaksi kasir & query data nota struk
- [ ] UseCase POS (`internal/usecase/pos_usecase.go`):
  - Logika buka/tutup shift kasir & validasi denominasi
  - Transaksi Checkout Atomik POS:
    - `SELECT ... FOR UPDATE` pada `warehouse_stocks`
    - Pengurangan stok fisik toko
    - Penandaan IMEI `Sold`
    - Relink QC inspection & garansi
    - Split payment & perhitungan MDR
    - Akumulasi kas/non-kas ke shift kasir
    - Generator struk kasir thermal ESC/POS (40/58/80 cols)
- [ ] Delivery HTTP POS (`internal/delivery/http/pos_handler.go`):
  - `GET /api/v1/pos/shift/active`
  - `POST /api/v1/pos/shift/open`
  - `POST /api/v1/pos/shift/close`
  - `GET /api/v1/pos/products`
  - `GET /api/v1/pos/scan-sn`
  - `GET /api/v1/pos/customers`
  - `POST /api/v1/pos/customers`
  - `GET /api/v1/pos/salesmen`
  - `GET /api/v1/pos/payment-methods`
  - `POST /api/v1/pos/checkout`
  - `GET /api/v1/pos/orders`
  - `GET /api/v1/pos/orders/:order_number/receipt`
  - `POST /api/v1/pos/sign-qz`
- [ ] Go Native Testing (`pos_usecase_test.go` & `pos_handler_test.go`)
- [ ] Dokumentasi API Wave 7 (`tokopon-go/docs/api_wave7.md` & `docs/migration/19-wave-7-web-pos-kasir-api.md`)

---

### Wave 8: Store Inventory, Stock Opname & Inbound PO API
**Estimasi Durasi:** 6 - 8 Hari  
**Target Luaran:** Operasional gudang fisik toko (inbound scan barang supplier, stock opname cabang, audit IMEI, dan penyesuaian stok) beralih ke Go API.

- [ ] Cek Stok Multi-Gudang & Penelusuran Lifecycle Serial Number (`/api/v1/inventory/stocks`, `/api/v1/inventory/serial-numbers/:sn/history`)
- [ ] Inbound PO Scan (`purchase_orders`, `purchase_order_items`): scan barcode/IMEI dari PO supplier, auto-increase stok gudang, registrasi SN `Available`.
- [ ] Stock Opname Cabang (`stock_opnames`, `stock_opname_items`, `stock_opname_serials`):
  - Inisialisasi sesi opname & snapshot stok sistem
  - Scan fisik IMEI (`MATCHED`, `MISSING`, `UNEXPECTED`)
  - Kalkulasi selisih qty & nilai moneter loss/surplus
  - Integrasi approval hasil opname
- [ ] Penyesuaian Stok (Stock Adjustment) & Pemakaian Inventaris Toko

---

### Wave 9: Retail Trade-In, Buyback & Digital Device Passport API
**Estimasi Durasi:** 5 - 7 Hari  
**Target Luaran:** Fitur tukar tambah ritel di kasir, pembelian HP bekas (buyback) dengan inspeksi QC, dan histori digital paspor perangkat.

- [ ] Evaluasi Tukar Tambah (Trade-In) di Kasir
- [ ] Evaluasi Pembelian HP Bekas (Buyback / Sell-Phone) & Checklist QC
- [ ] Digital Device Passport API (`/api/v1/qc/device/:imei/passport`): timeline lengkap unit mulai dari PO -> QC Inbound -> POS Sold -> Warranty Claim -> Service Center

---

### Wave 10: Backoffice Master Data, Promos & Store Settings API
**Estimasi Durasi:** 6 - 8 Hari  
**Target Luaran:** Seluruh pengelolaan data master katalog, varian baru & bekas, diskon promo, data salesman, dan konfigurasi unit bisnis.

- [ ] Master Katalog Produk Baru (`products`, `product_variants`) & Produk Bekas (`second_products`, `second_product_variants`)
- [ ] Master Kategori & Brand
- [ ] Master Karyawan / Salesman (`employes`) & Vendor Rekanan (`vendors`)
- [ ] Konfigurasi Promo Bundle & Preset Diskon Kasir
- [ ] Pengaturan Multi-Tenant Unit Bisnis (`business_units`) & Rate Pembayaran

---

### Wave 11: Store Reporting, Financial Analytics & Full Cutover (Monolith Retirement)
**Estimasi Durasi:** 4 - 6 Hari  
**Target Luaran:** Laporan penjualan toko fisik, rekonsiliasi kasir, laba-rugi, dan penonaktifan total server Laravel.

- [ ] Laporan Penjualan Cabang, Laporan Kasir & Rekonsiliasi Shift
- [ ] Laporan Laba Rugi Toko & Omset Salesman
- [ ] Validasi integritas data 100% antara Go dan database MySQL
- [ ] Uji performa & benchmarking (target: response latency < 20ms di semua endpoint)
- [ ] Pengalihan penuh 100% traffic ke Go backend (Nginx proxy cutover) & Laravel monolith dipensiunkan

---

## 4. Matriks Ringkasan Eksekusi

| Gelombang | Fokus Utama | Dependensi | Risiko | Kesiapan |
|---|---|---|---|---|
| **Phase 0** | Fondasi Arsitektur | - | Rendah | ✅ COMPLETED |
| **Wave 1** | Auth JWT & Profil (`harus login`) | Phase 0 | Rendah | ✅ COMPLETED |
| **Wave 2** | Mobile Katalog & Stok Read-Only | Wave 1 | Rendah | ✅ COMPLETED |
| **Wave 3** | Mobile Checkout & Stok Atomik | Wave 2 | Tinggi (Finansial & Stok) | ✅ COMPLETED |
| **Wave 4** | Native Go WebSocket & Live Chat | Wave 1 | Sedang (Stateful WS) | ✅ COMPLETED |
| **Wave 5** | Queue Worker & Webhooks | Wave 3 | Tinggi (Third Party) | ✅ COMPLETED |
| **Wave 6** | Approval, Warranty & Executive | Wave 5 | Sedang | ✅ COMPLETED |
| **Wave 7** | **Web POS Kasir Toko Offline API** | Wave 1, 3 | Tinggi (Finansial & Kasir) | ⏳ IN PROGRESS |
| **Wave 8** | **Store Inventory & Stock Opname** | Wave 7 | Sedang (Stok Fisik) | 📋 Siap Dieksekusi |
| **Wave 9** | **Trade-In, Buyback & Passport** | Wave 7, 8 | Sedang (Valuasi HP) | 📋 Siap Dieksekusi |
| **Wave 10** | **Backoffice Master & Settings** | Wave 7 | Rendah | 📋 Siap Dieksekusi |
| **Wave 11** | **Reporting & Monolith Cutover** | All | Sedang | 📋 Menunggu Wave 7-10 |
