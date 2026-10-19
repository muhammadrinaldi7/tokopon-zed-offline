# 03 — Business Logic Catalog

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS

---

## 1. Authentication Flow

### 1.1 Web Login (Fortify — DIABAIKAN untuk Go)
```
Route POST /login
  → Fortify LoginAction
  → Fortify rate limit 5x/menit (email+IP)
  → bcrypt password verify
  → Session create
  → Redirect to intended URL
```

### 1.2 API Login (Executive)
```
POST /api/v1/executive/login
  → throttle:10,1
  → ExecutiveAuthController@login
  → Validate email + password
  → Auth::attempt()
  → Role check: must be superadmin|admin|director
  → Sanctum: createToken('executive-access')
  → Return: token, user data, permissions
```

### 1.3 API Login (Mobile)
```
POST /api/v1/mobile/auth/login
  → throttle:120,1 (grup mobile)
  → Mobile\AuthController@login
  → Validate email + password
  → Auth::attempt()
  → Sanctum: createToken('mobile-access')
  → Return: token, user data
```

### 1.4 API Register (Mobile)
```
POST /api/v1/mobile/auth/register
  → Mobile\AuthController@register
  → Validate name, email, password
  → User::create() [role: customer]
  → Sanctum: createToken
  → Return: token, user data
```

### 1.5 Google OAuth (Web only)
```
GET /auth/google → redirect ke Google
GET /auth/google/callback
  → GoogleCallbackController@handleGoogleCallback
  → Socialite::driver('google')->user()
  → User::updateOrCreate(provider_id)
  → Auth::login()
```

---

## 2. Multi-Tenant Business Unit Resolution

Fitur kritis: setiap user (khusus superadmin/admin/director) dapat berpindah antara Business Unit melalui session `active_business_unit_id`.

```php
// User.php
public function getActiveBusinessUnitId() {
    if (hasAnyRole(['superadmin', 'admin', 'director'])) {
        return session('active_business_unit_id', $this->business_unit_id);
    }
    return $this->business_unit_id;
}
```

**Implikasi Go:** Untuk API Executive, aktif BU bisa dikirim via header `X-Business-Unit-Id` atau query param. Untuk API Mobile, BU ditentukan dari konteks order/produk.

---

## 3. Mobile Order Checkout

### Flow Lengkap:
```
POST /api/v1/mobile/orders/checkout
  → auth:sanctum
  → Mobile\OrderController@checkout
  → MobileOrderService@createCheckoutOrder()
    → Validasi user_id, items tidak kosong
    → Auto-detect business_unit_id dari product_accurate_id
    → Tentukan warehouse online store (is_online_store=true)
    → DB::transaction():
      → foreach item:
        → ProductAccurate::findOrFail()
        → WarehouseStock::lockForUpdate() → cek stok cukup
        → Kurangi stok (warehouse_stocks.quantity)
        → Jika has_sn: lock ProductSerialNumber
        → Build orderItemsToCreate[]
      → Hitung subtotal, total_discount
      → PromoCalculatorService@getEligiblePromos() & calculateDiscount()
      → LoyaltyService: hitung earnable points (preview)
      → CouponService: apply coupon jika ada (kurangi grand_total)
      → Order::create() [status: WAITING_PAYMENT, channel: MOBILE_APP]
      → OrderItem::create() per item
      → Jika payment_method == 'xendit': XenditService@createInvoice()
      → LoyaltyService@getOrCreateAccount() + earnPoints() (deferred)
    → Return order data + payment URL
```

**Business Rules Kritis:**
- `lockForUpdate()` pada WarehouseStock untuk mencegah race condition
- Stok dikurangi saat checkout, BUKAN saat payment confirmed
- Jika order expired (WAITING_PAYMENT > N menit): `orders:expire-mobile` command restore stok
- Order CANCELLED: restore stok + refund loyalty points + rollback coupon (via `Order::booted()`)
- Order status: `WAITING_PAYMENT` → `WAITING_VERIFICATION` → `PROCESSING` → `SHIPPED` → `COMPLETED`

---

## 4. Loyalty System

### Business Rules (LoyaltyService):
- Global toggle via `settings.loyalty_is_active`
- Rate: setiap Rp 10.000 belanja = 1 poin (configurable via settings)
- Identifikasi customer via nomor HP (normalized ke format `628xxx`)
- Auto-create account jika belum ada
- Earn: dipicu saat order status berubah ke PAID/COMPLETED/PROCESSING/SHIPPED
- Refund: dipicu saat order status berubah ke CANCELLED
- Tiering: BRONZE, SILVER, GOLD, PLATINUM (treshold NEEDS VERIFICATION)
- Redeem: customer bisa gunakan poin saat checkout (NEEDS VERIFICATION via API)

---

## 5. Promo & Diskon

### Tipe Promo:
- **Promo biasa:** discount per-item atau semua item
- **Bundle promo:** beli X produk A dapat diskon/gratis produk B
- **Manual discount preset:** kasir memilih preset diskon manual (POS)

### PromoCalculatorService Logic:
```
getEligiblePromos(cart, branchId, businessUnitId):
  → Promo::where(is_active, valid date, quota belum habis)
  → Filter by branch (branches pivot atau global)
  → Filter by business_unit
  → forEach promo: isPromoEligible(promo, cart)
    → cek min_qty, min_transaction_amount
    → cek apply_to_all_items atau SKU-specific
  → Return eligible promos
```

**Business Rules:**
- Satu order bisa mendapat beberapa promo sekaligus
- Quota global promo: `quota > used_quota`
- Max qty per order: `max_qty` (NEEDS VERIFICATION)
- Bundle: trigger item A harus dibeli, item B mendapat diskon

---

## 6. Coupon / Voucher E-Commerce

### EcommerceCouponService Logic:
- Tipe kupon: FIXED_AMOUNT, PERCENTAGE, SHIPPING_FIXED, SHIPPING_PERCENTAGE
- Validasi: kode kupon, tanggal valid, kuota global, kuota per user, minimum belanja
- Rollback: jika order CANCELLED, `rollbackCoupon()` restore `used_count`
- Idempotency: cek `EcommerceCouponRedemption` sebelum apply

---

## 7. Sell Phone (Buyback)

### Flow:
```
User submit sell phone request
  → SellPhone::create() [status: PENDING]
  → Upload foto via Spatie Media (photos collection)
  → Staff evaluasi: update appraised_value
  → ApprovalRequest::create() [type: SELL_PHONE_APPROVAL]
  → Telegram notification ke approval webhook
  → Director approve/reject via Telegram (n8n callback)
  → POST /api/webhooks/approval
    → ApprovalController@apiProcess
    → ApprovalService@getHandler('SELL_PHONE_APPROVAL')
    → SellPhoneApprovalHandler@handleApproved()
      → Update SellPhone.status = 'APPROVED'
      → Kirim WA/email ke customer
```

**Business Rules:**
- Price adjustment bisa dilakukan staff (is_price_adjusted flag)
- IMEI wajib untuk perangkat (validasi via device inspection QC)
- Jika pernah ada QC inspection: cek hasPassedQc()

---

## 8. Approval Workflow

### Request Types & Handlers:
| Type | Handler | Trigger |
|---|---|---|
| ORDER_CANCELLATION | OrderCancellationHandler | Kasir minta cancel order yang sudah diproses |
| SELL_PHONE_APPROVAL | SellPhoneApprovalHandler | Staff evaluasi sell phone |
| WARRANTY_EXTENSION | WarrantyExtensionHandler | Perpanjangan masa garansi |
| WARRANTY_EXTRA_CLAIM | WarrantyExtraClaimHandler | Tambah jatah klaim garansi |
| WARRANTY_REPLACEMENT | WarrantyReplacementHandler | Penggantian unit garansi |
| CUSTOM_CASHBACK | CustomCashbackHandler | Cashback manual di POS |
| STOCK_OPNAME_REPORT | StockOpnameApprovalHandler | Finalisasi laporan stock opname |
| STOCK_ADJUSTMENT | StockAdjustmentApprovalHandler | Penyesuaian stok |
| SWITCH_WARRANTY | SwitchWarrantyApprovalHandler | Pindah garansi ke unit lain |

### Approval Flow:
```
Request dibuat → ApprovalService@resolveRules(module, businessUnitId, amount, branchId)
  → Cari ApprovalRule by BU (spesifik), fallback ke global
  → Rule menentukan: role approver, level, min/max amount, branch
  → Kirim notifikasi Telegram ke webhook BU
  → Director/admin approve via Telegram (atau web UI)
  → POST /api/webhooks/approval (dari n8n)
  → ApprovalRequest@executeAction()
  → Handler@handleApproved() / handleRejected()
```

---

## 9. Warranty System

### Policy Engine:
- `WarrantyPolicy`: aturan garansi per kategori produk/brand
- `Warranty`: instance garansi per serial number
- `WarrantyClaim`: klaim garansi yang diajukan customer
- Status claim: `pending` → `approved` → `in_repair` → `waiting_payment` → `waiting_refund` → `completed` / `rejected`

### Business Rules:
- Max claims: `warranty.max_claims = policy.max_claims + warranty.extra_claims`
- Setiap klaim dapat memiliki DeviceInspection (QC saat penerimaan)
- Klaim bisa berlanjut ke ServiceCenterTicket
- Penggantian unit via WarrantyReplacement
- Serial number history via WarrantySerialLog

---

## 10. Stock Management

### WarehouseStock:
- Satu baris per (product_accurate_id, warehouse_id)
- `quantity` adalah stok aggregate
- Dikurangi saat checkout (lockForUpdate), dikembalikan saat cancel/expire

### ProductSerialNumber:
- Tracking serial number per unit
- Status: available, sold, returned, damaged
- Linked ke order_items via `OrderItemSerialNumber`
- QC status field

### Stock Opname:
- Dibuat oleh staff per warehouse
- Items dibandingkan: system qty vs physical qty
- Perbedaan (`difference_qty`, `loss_value`, `surplus_value`) dihitung
- Perlu approval sebelum finalisasi

### Stock Adjustment:
- Penambahan/pengurangan stok dengan alasan
- Juga perlu approval (STOCK_ADJUSTMENT handler)

---

## 11. Accurate ERP Integration

### SyncOrderToAccurateJob:
```
Triggered: saat order dibuat/dikonfirmasi
  → AccurateService@createSalesOrder(order)
  → POST ke Accurate API (per BU host + token)
  → Map: order items → SO detail lines
  → Handle MDR expenses
  → Store: OrderAccurateDoc (so_number, invoice_number)
```

### AccurateWebhookController:
```
POST /api/webhooks/accurate
  → Verifikasi token (accurate_webhook_token di BU)
  → Dispatch ProcessAccurateWebhookJob
  → Job update ProductAccurate stock/price dari payload
```

### AccurateImportController:
```
GET /api/accurate/import-items
  → TIDAK ADA auth! (risiko keamanan)
  → AccurateService@importItems()
  → Fetch semua item dari Accurate API
  → Upsert ProductAccurate
```

> **Security Risk:** `/api/accurate/import-items` tidak dilindungi auth. Harus diperbaiki di Go.

---

## 12. Chat / CS System

### Flow (Mobile App):
```
POST /api/v1/mobile/chat/init
  → ChatController@initConversation
  → Guest atau authenticated user
  → Conversation::firstOrCreate(user_id, business_unit_id)
  → Return: conversation_id, channel name

GET /api/v1/mobile/chat/conversations/{id}/messages
  → Paginated messages

POST /api/v1/mobile/chat/conversations/{id}/messages
  → Message::create()
  → Broadcast MessageSent event ke Reverb/Pusher
  → Channel: conversation.{id}
```

### CS Staff (Web Livewire — DIABAIKAN, tapi realtime tetap perlu):
- Staff menjawab di halaman `/zoffline/cs-chat`
- Real-time via Livewire polling atau WebSocket

---

## 13. Executive Dashboard API

### Endpoints (semua require role: superadmin|admin|director):
- `GET /api/v1/executive/kpi-summary` — total revenue, orders, customers (per BU/date range)
- `GET /api/v1/executive/sales-trend` — trend penjualan harian/bulanan
- `GET /api/v1/executive/top-products` — produk terlaris
- `GET /api/v1/executive/payment-breakdown` — breakdown per metode pembayaran
- `GET /api/v1/executive/branch-comparison` — perbandingan antar cabang
- `GET /api/v1/executive/staff-kpi` — KPI per staff
- `GET /api/v1/executive/brand-analytics` — analitik per brand
- `GET /api/v1/executive/cashier-audit` — audit shift kasir
- `GET /api/v1/executive/promo-claims` — laporan promo terpakai
- `GET /api/v1/executive/piutang` — laporan piutang
- `GET /api/v1/executive/project-sales` — penjualan per proyek
- `POST /api/v1/executive/ai/chat` — AI assistant chat (Gemini/OpenAI)

### ExecutiveMetricsService:
- File terbesar: 96KB — mengandung banyak raw SQL query untuk analytics
- Telah dimigrasikan sebagian di Wave 6 untuk metrik eksekutif harian/bulanan.

---

## 14. Web POS Kasir Toko Offline (Zoffline POS)

Modul ini merupakan inti operasional toko fisik Zed-Offline yang sebelumnya di-handle oleh Livewire (`App\Livewire\Zoffline\Pos`). Dalam rebuild Go, seluruh fungsionalitas ini diekspos sebagai high-performance REST API.

### 14.1 Manajemen Shift Kasir (`CashierShift`)
- **Lifecycle Shift:** Kasir hanya boleh memiliki 1 shift `open` dalam satu waktu. Aturan bisnis: 1 kasir maksimal 1 shift per hari kerja per unit bisnis (`shift_date = CURRENT_DATE`).
- **Buka Shift (`OpenShift`):**
  - Kasir memasukkan rincian pecahan uang modal awal (denominasi: 100k, 50k, 20k, 10k, 5k, 2k, 1k, 500, 200, 100).
  - Sistem menghitung `starting_cash` dan mencatat rincian ke `cashier_shift_denominations` dengan tipe `opening`.
  - Record `cashier_shifts` dibuat dengan status `open`, mengikat `business_unit_id`, `branch_id`, dan `user_id` (kasir).
- **Tutup Shift & Rekonsiliasi Kas (`ClosingKasir`):**
  - Kasir menghitung fisik uang tunai di laci kasir per pecahan uang (`actual_cash`).
  - Sistem mengagregasi total transaksi kasir selama shift:
    - `total_cash_sales`: akumulasi pembayaran bertipe `TUNAI` dari order `COMPLETED` yang di-handle kasir.
    - `total_non_cash_sales`: akumulasi pembayaran bertipe `NON-TUNAI` (Transfer, EDC/Debit, QRIS, Leasing/Finance).
    - `expected_cash = starting_cash + total_cash_sales`.
    - `cash_difference = actual_cash - expected_cash`.
    - `reconciliation_status`:
      - `balanced` jika `abs(cash_difference) < 0.01`
      - `over` jika `cash_difference > 0` (kelebihan fisik kas)
      - `short` jika `cash_difference < 0` (selisih kurang kas / tekor)
  - Simpan rincian pecahan penutupan ke `cashier_shift_denominations` tipe `closing`.
  - Shift ditandai status `closed`.

### 14.2 Katalog Produk & Scan Stok Toko Fisik
- **Filter Gudang Kasir:** Katalog POS hanya menampilkan item yang memiliki stok aktif di gudang tempat kasir bertugas (`users.warehouse_id`).
- **Dukungan Dua Jenis Produk:**
  - Produk Baru: `Product` & `ProductVariant` (atau `ProductAccurate`)
  - Produk Bekas: `SecondProduct` & `SecondProductVariant`
- **Pencarian Cepat:** Pencarian berdasarkan SKU, Barcode, Nama Produk, atau Varian.
- **Scan Barcode / Serial Number (IMEI):**
  - Input scan langsung mencari ke tabel `product_serial_numbers`.
  - Validasi ketat: nomor seri harus berstatus `Available` dan berada di gudang toko kasir.
  - Untuk produk bekas (Second), wajib memvalidasi apakah unit telah lulus "QC Serah Terima" (`device_inspections.verdict = 'pass'`). Jika belum lolos QC, produk tidak boleh di-checkout.

### 14.3 Pelanggan & Salesman POS
- **Walk-in Customer Auto-Registration:**
  - Kasir dapat mencari pelanggan yang sudah terdaftar via nomor telepon atau nama.
  - Jika pelanggan baru walk-in, sistem otomatis mendaftarkan akun `User` & `UserProfile` dengan normalisasi nomor telepon (deteksi awalan `0` / `62`).
- **Penugasan Salesman (`employes`):**
  - Setiap item transaksi dapat diasosiasikan dengan satu atau lebih tenaga penjual (`sales_ids`) dari tabel `employes`.
  - Digunakan untuk perhitungan komisi penjualan karyawan dan KPI salesman.

### 14.4 Promo, Diskon & Program Loyalitas di POS
- **Diskon Manual:** Kasir berhak memasukkan diskon nominal per baris item belanja (dengan limitasi otorisasi).
- **Promo Otomatis:** Perhitungan promo bundle, diskon persentase, atau potongan harga yang memenuhi syarat (`PromoCalculatorService`).
- **Loyalitas Poin:**
  - Pelanggan dapat menukarkan poin yang dimiliki untuk memotong tagihan belanja (`LoyaltyService.redeemPoints`).
  - Setelah transaksi `COMPLETED`, sistem otomatis mencatat penambahan poin baru pelanggan (`LoyaltyService.earnPoints`).
- **Penggunaan Customer Deposit:** Pelanggan yang memiliki saldo deposit aktif (`customer_deposits`) dapat menggunakan depositnya sebagai metode pembayaran pesanan.

### 14.5 Split Payment & Perhitungan MDR
- **Multi-Split Payment:** Mendukung kombinasi pembayaran dalam satu transaksi (contoh: Rp 500.000 Tunai + Rp 5.000.000 Transfer BCA + Rp 3.000.000 Leasing Home Credit).
- **Perhitungan MDR (Merchant Discount Rate):**
  - Metode non-tunai (kartu debit/kredit/QRIS) yang memiliki rate MDR (`payment_method_rates`) akan dihitung biaya MDR-nya.
- **Finance / Leasing:**
  - Pembayaran leasing memerlukan input `no_kontrak`. Status pembayaran dicatat `PENDING` hingga dana dicairkan oleh pihak leasing.
- **Transaksi Piutang POS:**
  - Jika pelanggan tidak langsung melunasi, transaksi dapat dicatat dengan status `PIUTANG`.
  - Kasir dapat melakukan settlement pelunasan piutang di kemudian hari (`processPiutangSettlement`).

### 14.6 Transaksi Checkout Atomik POS
- **Langkah Eksekusi Database Transaction:**
  1. Validasi keranjang, ketersediaan kasir, dan active shift.
  2. Buka transaksi database MySQL.
  3. Penguncian stok fisik secara pesimistik: `SELECT stock FROM warehouse_stocks WHERE warehouse_id = ? AND variant_id = ? FOR UPDATE`.
  4. Pengurangan kuantitas stok gudang (`stock = stock - qty`).
  5. Penguncian dan pembaruan nomor seri/IMEI dari `Available` menjadi `Sold` / `Unavailable`.
  6. Pembuatan record `orders` dengan `order_channel = 'POS'`, nomor transaksi unik (`POS-YYYYMMDD-XXXX`), relasi ke kasir (`handled_by`), dan snapshot toko.
  7. Pembuatan baris `order_items` beserta perhitungan HPP unit (`OrderHppResolverService`) dan nomor seri.
  8. Relink histori inspeksi QC dan aktivasi record garansi IMEI ke `order_item_id`.
  9. Pembuatan baris `order_payments` untuk setiap metode split payment yang digunakan.
  10. Akumulasi update penjualan pada shift kasir yang aktif (`total_cash_sales` & `total_non_cash_sales`).
  11. Commit transaksi atomik database.

### 14.7 Pencetakan Struk (Receipt) & Delivery
- **ESC/POS Thermal Text Generator:** Menghasilkan payload teks ESC/POS terformat (lebar 40, 58, atau 80 kolom) memuat logo/header toko, nomor transaksi, nama kasir, daftar item + serial number/IMEI, rincian split payment, uang bayar & kembalian, serta footer nota.
- **QZ Tray Silent Print Integration:** Endpoint `/sign-qz` untuk penandatanganan request cetak digital QZ Tray secara aman.
- **PDF Receipt & WhatsApp Dispatch:** Generator nota PDF dan pengiriman otomatis tanda terima belanja via WhatsApp Gateway (Qontak / CRM).

---

## 15. Manajemen Operasional Toko & Gudang (Warehouse Operations)

### 15.1 Cek Stok & Penelusuran Serial Number (IMEI Lifecycle)
- **Cek Stok Realtime:** Menampilkan stok fisik per gudang, total stok konsolidasi, dan alokasi stok online vs offline.
- **Pencarian Nomor Seri/IMEI:** Menampilkan data lengkap satu nomor seri: status (`Available`, `Sold`, `Reserved`, `Damaged`), lokasi gudang saat ini, tanggal masuk, dan faktur pembelian.
- **Histori Pergerakan Serial Number (`product_serial_number_histories`):** Mencatat setiap perpindahan status atau lokasi unit sejak penerimaan (Inbound), mutasi, penjualan POS, retur, hingga klaim garansi.

### 15.2 Inbound PO (Penerimaan Barang Supplier)
- **Integrasi Dokumen PO (`purchase_orders`):** Mengambil data purchase order yang disinkronisasikan dari Accurate Online.
- **Pemindaian Barcode & Serial Number:** Staf gudang melakukan scan barcode/IMEI fisik saat barang datang.
- **Penambahan Stok Otomatis:** Setiap scan yang berhasil meningkatkan `quantity_received` pada `purchase_order_items`, menambah kuantitas `warehouse_stocks`, dan membuat baris baru di `product_serial_numbers` dengan status `Available`.

### 15.3 Stock Opname Cabang (`stock_opnames`)
- **Alur Kerja Audit Fisik:**
  1. Buat sesi stock opname baru (`DRAFT` → `COUNTING`).
  2. Snapshot stok sistem pada saat opname dimulai (`system_qty`).
  3. Pemindaian fisik barang di toko/gudang (`physical_qty`).
  4. Deteksi status serial number: `MATCHED` (ada di sistem & fisik), `MISSING` (ada di sistem tapi fisik hilang), `UNEXPECTED` (fisik ada tapi tidak tercatat di sistem).
  5. Perhitungan otomatis selisih kuantitas (`difference_qty`) dan nilai moneter (`total_loss_value`, `total_surplus_value`).
  6. Pengajuan approval hasil opname (`PENDING_APPROVAL`).
  7. Finalisasi & penyesuaian stok sistem setelah di-approve pimpinan (`COMPLETED`).

### 15.4 Penyesuaian Stok (Stock Adjustment) & Pemakaian Inventaris
- Pencatatan pemakaian barang dagangan untuk keperluan internal toko (display, perlengkapan toko, unit test).
- Pengurangan stok dengan alasan jelas dan pencatatan biaya pemakaian inventaris.

---

## 16. Retail Trade-In, Buyback (Sell-Phone) & Device Passport

### 16.1 Retail Trade-In Toko Fisik
- Pelanggan membawa ponsel lama untuk ditukar tambah dengan unit baru di kasir.
- Sistem menghitung estimasi nilai tukar berdasarkan grade fisik (Grade A, B, C), kelengkapan aksesoris, dan kondisi fungsi.
- Nilai trade-in langsung memotong total tagihan belanja transaksi POS.

### 16.2 Buyback / Sell-Phone Kasir
- Pembelian kembali ponsel bekas dari pelanggan umum.
- Evaluasi perangkat menggunakan checklist template QC.
- Jika nilai taksiran beli melebihi batas wewenang kasir, sistem memicu workflow approval ke direktur via Telegram callback sebelum dana dibayarkan ke pelanggan.

### 16.3 Device Passport (Paspor Perangkat)
- Setiap nomor seri IMEI memiliki riwayat hidup terpadu yang dapat diakses publik atau staf toko:
  - Asal usul unit (Inbound PO / Pembelian Vendor)
  - Hasil inspeksi QC & skor kesehatan unit
  - Riwayat penjualan & identitas pembeli
  - Masa berlaku dan sisa kuota garansi toko
  - Riwayat service & penggantian suku cadang

---

## 17. Warranty & QC Service Center

### 17.1 Aktivasi Garansi Toko
- Saat produk bergaransi terjual di POS atau Mobile, record `warranties` otomatis diaktifkan dengan mengaitkan nomor seri IMEI, customer ID, dan tanggal kedaluwarsa sesuai `warranty_policies`.

### 17.2 Klaim Garansi & Inspeksi Retur
- Pelanggan mengajukan klaim kerusakan unit di toko fisik.
- Staf memeriksa validitas garansi berdasarkan nomor IMEI.
- Pengujian kondisi kerusakan dicatat pada form QC inspeksi retur (`device_inspections`).
- Opsi penyelesaian: perbaikan unit di Service Center, penggantian unit baru (`WarrantyReplacement`), atau pengembalian dana (refund).

---

## 18. Backoffice Master Data & Operational Settings

Untuk menggantikan kebutuhan administrasi backend Laravel secara menyeluruh:
- **Katalog Produk & Varian:** Manajemen produk baru (`products`, `product_variants`) dan produk bekas (`second_products`, `second_product_variants`) beserta foto media dan spesifikasi.
- **Kategori & Brand:** Hirarki kategori produk dan katalog merk gadget.
- **Karyawan / Salesman (`employes`):** Manajemen data tenaga penjual, nomor induk karyawan, cabang tugas, dan status keaktifan.
- **Vendor / Supplier (`vendors`):** Master data rekanan supplier unit gadget dan aksesoris.
- **Pengaturan Bisnis & Pembayaran:** Konfigurasi unit bisnis (`business_units`), rekening bank, rate MDR pembayaran non-tunai (`payment_method_rates`), dan aturan approval (`approval_rules`).
