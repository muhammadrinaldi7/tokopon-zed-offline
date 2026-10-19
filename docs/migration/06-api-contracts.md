# 06 — API Contracts

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS  
**Source:** `routes/api.php`, controllers di `app/Http/Controllers/Api/`

---

## Ringkasan API Groups

| Grup | Prefix | Auth | Target Rebuild Go |
|---|---|---|---|
| Web POS Kasir Toko Offline | `/api/v1/pos` | JWT Staff / Kasir | Wave 7 (High Priority) |
| Store Inventory & Warehouse | `/api/v1/inventory` | JWT Staff Gudang | Wave 8 |
| Trade-In, Buyback & QC | `/api/v1/trade-in`, `/api/v1/qc` | JWT Staff / Public | Wave 9 |
| Backoffice Master Data | `/api/v1/admin` | JWT Admin / Superadmin | Wave 10 |
| Store Reporting & Analytics | `/api/v1/reports` | JWT Manager / Director | Wave 11 |
| Mobile App E-Commerce | `/api/v1/mobile` | JWT Customer / Guest | Wave 1 - 3 (Selesai) |
| Realtime Chat WebSocket | `/ws/connect`, `/api/v1/mobile/chat` | JWT / Guest Token | Wave 4 (Selesai) |
| Webhook & Worker Jobs | `/api/v1/webhooks` | Signature / Token | Wave 5 (Selesai) |
| Approval, Warranty, Executive | `/api/v1/executive`, `/webhooks/approval` | JWT Role Superadmin | Wave 6 (Selesai) |
| Public Trade-In | `/api/v1/public/trade-in` | Public | Wave 6 (Selesai) |
| QZ Silent Print Sign | `/api/v1/pos/sign-qz` | JWT Staff | Wave 7 |

---

## A. Public Trade-In API

### GET /api/v1/public/trade-in/brands
**Response:** daftar brand yang tersedia untuk trade-in

### GET /api/v1/public/trade-in/old-devices
**Query params:** `brand_id` (optional)  
**Response:** daftar device lama yang bisa di-trade-in

### GET /api/v1/public/trade-in/target-devices
**Query params:** `old_device_id` (optional)  
**Response:** daftar device target yang bisa diterima

### POST /api/v1/public/trade-in/calculate
**Body:** `{ old_device_id, target_device_id, condition_answers[] }`  
**Response:** nilai trade-in yang dihitung

### GET /api/v1/public/trade-in/device/{id}
**Response:** detail harga satu device untuk trade-in

---

## B. Executive API

### Auth
#### POST /api/v1/executive/login (throttle:10,1)
**Body:** `{ email, password }`  
**Response:** `{ token, user: { id, name, email, roles, permissions } }`

#### GET /api/v1/executive/me (auth)
**Response:** data user + roles + permissions

#### POST /api/v1/executive/logout (auth)
**Response:** `{ message: "Logged out" }`

### Analytics & KPI (semua require auth + role:superadmin|admin|director)
#### GET /api/v1/executive/filters
**Query:** -  
**Response:** opsi filter (BU, branch, date range)

#### GET /api/v1/executive/kpi-summary
**Query:** `business_unit_id`, `branch_id`, `date_from`, `date_to`, `period`  
**Response:** total revenue, orders, customers, avg order value, dll

#### GET /api/v1/executive/sales-trend
**Query:** sama dengan kpi-summary  
**Response:** array data poin per hari/bulan

#### GET /api/v1/executive/top-products
**Response:** produk terlaris dengan qty dan revenue

#### GET /api/v1/executive/payment-breakdown
**Response:** breakdown pendapatan per metode pembayaran

#### GET /api/v1/executive/branch-comparison
**Response:** perbandingan metrik per cabang

#### GET /api/v1/executive/overview
**Response:** dashboard overview ringkasan

#### GET /api/v1/executive/staff-kpi
**Response:** KPI per staff (sales)

#### GET /api/v1/executive/brand-analytics
**Response:** analitik penjualan per brand

#### GET /api/v1/executive/cashier-audit
**Response:** audit shift kasir (opening/closing balance, discrepancy)

#### GET /api/v1/executive/promo-claims
**Response:** laporan promo terpakai

#### GET /api/v1/executive/project-sales
**Response:** penjualan per proyek Accurate

#### GET /api/v1/executive/project-sales/detail
**Response:** detail per proyek

#### GET /api/v1/executive/branch-transactions
**Response:** transaksi per cabang

#### GET /api/v1/executive/piutang
**Response:** laporan piutang customer

### AI Assistant
#### POST /api/v1/executive/ai/chat (auth)
**Body:** `{ message, history[] }`  
**Response:** `{ reply, history[] }`

#### GET /api/v1/executive/ai/history (auth)
**Response:** riwayat chat AI per user

#### POST /api/v1/executive/ai/summarize (auth)
**Response:** ringkasan AI dari data tertentu

#### DELETE /api/v1/executive/ai/history (auth)
**Response:** konfirmasi clear history

---

## C. Mobile App API

### Remote Config
#### GET /api/v1/mobile/config
**Auth:** Tidak  
**Response:** feature flags, app version, minimum version, settings publik

### Auth Mobile
#### POST /api/v1/mobile/auth/login
**Body:** `{ email, password }`  
**Response:** `{ token, user }`

#### POST /api/v1/mobile/auth/register
**Body:** `{ name, email, password, password_confirmation }`  
**Response:** `{ token, user }`

#### GET /api/v1/mobile/auth/me (auth)
**Response:** user profile lengkap

#### PUT /api/v1/mobile/auth/profile (auth)
**Body:** `{ name, phone_number, address, ... }`  
**Response:** user profile updated

#### POST /api/v1/mobile/auth/logout (auth)
**Response:** `{ message }`

#### POST /api/v1/mobile/user/push-token (auth)
**Body:** `{ push_token }`  
**Response:** konfirmasi update

### Business Units (Toko)
#### GET /api/v1/mobile/business-units
**Auth:** Tidak  
**Filter:** `is_visible_mobile = true && is_active = true`  
**Response:** list toko yang tersedia di mobile

### E-Commerce Catalog
#### GET /api/v1/mobile/banners
**Response:** daftar banner promo aktif

#### GET /api/v1/mobile/flash-sales
**Response:** daftar flash sale aktif + items

#### GET /api/v1/mobile/products
**Query:** `business_unit_id`, `category_id`, `brand_id`, `search`, `page`, `per_page`  
**Filter:** `is_active = true && has_active_accurate = true && total_stock >= min_threshold`  
**Response:** paginated products dengan media URLs

#### GET /api/v1/mobile/products/categories
**Response:** daftar kategori

#### GET /api/v1/mobile/products/brands
**Response:** daftar brand

#### GET /api/v1/mobile/products/{id}
**Response:** detail produk + variants + media + reviews

#### GET /api/v1/mobile/products/{id}/serial-numbers
**Auth:** NEEDS VERIFICATION  
**Response:** daftar SN tersedia untuk produk

### Payment Methods
#### GET /api/v1/mobile/payment-methods
**Response:** daftar metode pembayaran aktif

### Orders (semua require auth)
#### GET /api/v1/mobile/orders
**Response:** paginated orders milik user

#### POST /api/v1/mobile/orders/checkout
**Body:**
```json
{
  "business_unit_id": 1,
  "items": [
    { "product_accurate_id": 123, "qty": 1, "price": 5000000 }
  ],
  "shipping_address": { "full_address": "...", "phone_number": "08xxx" },
  "payment_method": "xendit | transfer | cash",
  "coupon_code": "PROMO10",
  "loyalty_points_used": 100
}
```
**Response:** `{ order, payment_url (jika xendit) }`

#### GET /api/v1/mobile/orders/{orderNumber}
**Response:** detail order lengkap

#### POST /api/v1/mobile/orders/{orderNumber}/upload-proof
**Body:** multipart/form-data, field `proof`  
**Response:** konfirmasi upload

#### POST /api/v1/mobile/orders/{orderNumber}/cancel
**Response:** konfirmasi cancel (mungkin perlu approval)

### Chat (Realtime)
#### POST /api/v1/mobile/chat/init
**Auth:** Tidak (guest support)  
**Body:** `{ business_unit_id, guest_name (optional) }`  
**Response:** `{ conversation_id, channel_name, pusher_key, pusher_cluster }`

#### GET /api/v1/mobile/chat/conversations/{conversationId}/messages
**Response:** paginated messages

#### POST /api/v1/mobile/chat/conversations/{conversationId}/messages
**Body:** `{ content, type }` (text, image)  
**Response:** message object + broadcast

#### POST /api/v1/mobile/chat/conversations/{conversationId}/read
**Response:** mark as read

#### POST /api/v1/mobile/broadcasting/auth
**Body:** Pusher auth challenge  
**Response:** Pusher auth response (signed)

### Loyalty
#### GET /api/v1/mobile/loyalty/summary (auth)
**Response:** `{ points_balance, lifetime_points, tier, phone_number }`

#### GET /api/v1/mobile/loyalty/history (auth)
**Response:** paginated loyalty transactions

### Coupons
#### GET /api/v1/mobile/coupons/available
**Query:** `business_unit_id`, `subtotal`, `shipping_cost`  
**Response:** daftar kupon + eligibility info + potential discount

#### POST /api/v1/mobile/coupons/validate
**Body:** `{ code, business_unit_id, subtotal, shipping_cost }`  
**Response:** `{ valid, coupon, discount_amount, message }`

---

## D. Webhook Endpoints

### POST /api/webhooks/xendit/invoice
**Trigger:** Xendit payment notification  
**Auth:** Header `x-callback-token` (verified against settings)  
**Payload:** Xendit invoice object  
**Side effects:** Update order status, trigger loyalty earn, kirim notifikasi

### POST /api/webhooks/accurate
**Auth:** Query param `token` (dari `business_unit.accurate_webhook_token`)  
**Payload:** Accurate webhook payload (item update)  
**Side effects:** Dispatch `ProcessAccurateWebhookJob`

### POST /api/webhooks/approval
**Auth:** Tidak (n8n callback) — perlu diamankan  
**Body:** `{ approval_request_id, action: approved|rejected, params }`  
**Side effects:** Execute approval action (handler-specific)

---

## E. Erzap Webhook (Legacy)
```
POST /web_service/import_produk_json/new.json
POST /web_service/import_produk_json/new
POST /web_service/sinkronisasi_stok/new
```
**Auth:** TIDAK ADA — perlu diamankan  
**Source:** Sistem Erzap (POS lama)  
**Controller:** `ErzapProductController`

---

## F. Web POS Kasir Toko Offline API (`/api/v1/pos`)

Endpoint khusus untuk operasional kasir toko fisik (menggantikan Livewire `Zoffline\Pos`).
**Semua endpoint memerlukan Header:** `Authorization: Bearer <jwt_staff_token>`

### F.1 Shift Kasir
#### GET /api/v1/pos/shift/active
**Response:** Status shift kasir yang sedang `open`, detail `starting_cash`, waktu buka, total penjualan terkini.
```json
{
  "status": "success",
  "data": {
    "has_active_shift": true,
    "shift": {
      "id": 14,
      "shift_date": "2026-10-19",
      "opened_at": "2026-10-19T08:00:00Z",
      "starting_cash": 500000.0,
      "total_cash_sales": 1500000.0,
      "total_non_cash_sales": 4200000.0,
      "total_transactions": 3,
      "status": "open"
    }
  }
}
```

#### POST /api/v1/pos/shift/open
**Body:**
```json
{
  "starting_cash": 500000.0,
  "opening_notes": "Modal awal kasir pagi",
  "denominations": {
    "100000": 3,
    "50000": 4
  }
}
```
**Validasi:** Kasir tidak memiliki shift `open` lainnya.

#### POST /api/v1/pos/shift/close
**Body:**
```json
{
  "actual_cash": 2000000.0,
  "closing_notes": "Tutup shift lancar",
  "denominations": {
    "100000": 15,
    "50000": 10
  }
}
```
**Response:** Ringkasan shift, `expected_cash`, `cash_difference`, dan status rekonsiliasi (`balanced`, `over`, `short`).

### F.2 Katalog & Scan POS
#### GET /api/v1/pos/products
**Query params:** `search`, `category_id`, `is_second` (0|1), `page`, `per_page`  
**Filter:** Stok gudang kasir aktif (`users.warehouse_id`).  
**Response:** Daftar item yang tersedia untuk dijual di toko fisik.

#### GET /api/v1/pos/scan-sn
**Query params:** `serial_number`  
**Response:** Validasi nomor seri/IMEI. Status harus `Available` dan berada di gudang kasir. Untuk produk second, memverifikasi `verdict = pass` pada QC Serah Terima.

### F.3 Pelanggan & Salesman
#### GET /api/v1/pos/customers
**Query params:** `query` (nama atau nomor HP)  
**Response:** Daftar pelanggan terdaftar.

#### POST /api/v1/pos/customers
**Body:** `{ "name": "Budi Santoso", "phone_number": "081234567890", "email": "budi@gmail.com" }`  
**Response:** Auto-create user pelanggan walk-in.

#### GET /api/v1/pos/salesmen
**Response:** Daftar staf/salesman aktif (`employes`) dari cabang kasir untuk penugasan komisi penjualan.

### F.4 Metode Pembayaran & Rate MDR
#### GET /api/v1/pos/payment-methods
**Response:** Daftar metode pembayaran aktif (Tunai, EDC/Debit, QRIS, Transfer, Leasing/Finance) beserta daftar `payment_method_rates` (MDR %).

### F.5 Transaksi Checkout & Struk POS
#### POST /api/v1/pos/checkout
**Body:**
```json
{
  "customer_id": 45,
  "sales_ids": [2],
  "items": [
    {
      "variant_id": 12,
      "variant_type": "product_variant",
      "qty": 1,
      "price": 12500000,
      "discount_amount": 0,
      "serial_numbers": ["356789123456789"]
    }
  ],
  "payments": [
    {
      "payment_method_id": 1,
      "amount": 2500000
    },
    {
      "payment_method_id": 3,
      "payment_method_rate_id": 2,
      "amount": 10000000,
      "no_kontrak": null
    }
  ],
  "notes": "Pembeli minta bonus antigores",
  "use_loyalty_points": false,
  "loyalty_points_to_redeem": 0
}
```
**Side Effects Atomik:**
1. Penguncian stok dengan `SELECT ... FOR UPDATE` pada `warehouse_stocks`.
2. Pengurangan stok fisik toko.
3. Penandaan serial number IMEI menjadi `Sold`.
4. Pembuatan `orders`, `order_items`, `order_payments`.
5. Akumulasi kas & non-kas ke `cashier_shifts`.
6. Generasi struk thermal ESC/POS.

#### GET /api/v1/pos/orders
**Query params:** `query`, `date_from`, `date_to`, `page`, `per_page`  
**Response:** Riwayat transaksi kasir di toko fisik.

#### GET /api/v1/pos/orders/:order_number/receipt
**Response:** Data lengkap nota/struk pesanan untuk preview atau cetak ulang, beserta teks format ESC/POS 40/58 kolom.

#### POST /api/v1/pos/sign-qz
**Body:** `{ "request": "<qz_tray_payload>" }`  
**Response:** Digital signature SHA-512 base64 untuk pencetakan silent thermal melalui QZ Tray client.

---

## G. Store Inventory & Warehouse Operations API (`/api/v1/inventory`)

### G.1 Cek Stok & Serial Number Lifecycle
#### GET /api/v1/inventory/stocks
**Query params:** `warehouse_id`, `category_id`, `search`, `page`, `per_page`  
**Response:** Kuantitas stok produk per gudang dan total agregat.

#### GET /api/v1/inventory/serial-numbers/:sn
**Response:** Detail satu IMEI/SN (status, gudang, tanggal penerimaan, faktur PO, riwayat mutasi).

#### GET /api/v1/inventory/serial-numbers/:sn/history
**Response:** Riwayat pergerakan logistik unit sejak inbound hingga penjualan POS.

### G.2 Inbound PO (Penerimaan Barang Supplier)
#### GET /api/v1/inventory/inbound/po
**Query params:** `status` (PENDING|PARTIAL|COMPLETED), `search`  
**Response:** Daftar Purchase Order supplier.

#### POST /api/v1/inventory/inbound/po/:po_id/scan
**Body:** `{ "item_no": "IPHONE15-128", "serial_number": "358912345678901", "warehouse_id": 2 }`  
**Response:** Verifikasi nomor seri, penambahan `quantity_received`, dan penambahan stok fisik di `warehouse_stocks`.

### G.3 Stock Opname Toko Cabang
#### GET /api/v1/inventory/stock-opname
**Response:** Daftar sesi stock opname toko cabang.

#### POST /api/v1/inventory/stock-opname
**Body:** `{ "warehouse_id": 2, "type": "ALL", "notes": "Opname bulanan" }`  
**Response:** Sesi opname baru (`COUNTING`) dengan snapshot kuantitas sistem.

#### POST /api/v1/inventory/stock-opname/:id/count
**Body:** `{ "serial_number": "358912345678901" }` atau `{ "item_no": "CASE-IP15", "counted_qty": 10 }`  
**Response:** Pencatatan fisik barang (`MATCHED`, `MISSING`, `UNEXPECTED`).

#### POST /api/v1/inventory/stock-opname/:id/finalize
**Response:** Perhitungan selisih kuantitas, nilai finansial selisih rugi/lebih, dan pengajuan approval.

### G.4 Penyesuaian Stok (Stock Adjustment)
#### POST /api/v1/inventory/stock-adjustments
**Body:** `{ "warehouse_id": 2, "variant_id": 15, "variant_type": "product_variant", "adjustment_qty": -1, "reason": "Barang display toko rusak", "notes": "Penyesuaian internal" }`

---

## H. Trade-In, Buyback & QC API (`/api/v1/trade-in`, `/api/v1/qc`, `/api/v1/warranty`)

### H.1 Retail Trade-In Kasir
#### POST /api/v1/trade-in/evaluate
**Body:** `{ "brand_id": 1, "model": "iPhone 11 64GB", "grade": "B", "accessories": ["box", "charger"], "functional_tests": { "lcd": true, "battery_health": 82 } }`  
**Response:** Taksiran nilai trade-in yang disetujui sistem.

### H.2 Buyback / Sell-Phone Kasir
#### POST /api/v1/trade-in/buyback
**Body:** `{ "customer_id": 10, "imei": "352345678912345", "device_name": "Samsung S21", "appraised_value": 4500000, "photos": [] }`  
**Response:** Pengajuan pembelian kembali dan status approval.

### H.3 QC & Device Passport
#### GET /api/v1/qc/device/:imei/passport
**Response:** Paspor digital lengkap perangkat: asal PO, riwayat inspeksi QC, tanggal terjual di POS, status garansi aktif, dan riwayat service.

#### POST /api/v1/qc/inspections
**Body:** `{ "imei": "352345678912345", "template_id": 1, "label": "QC Serah Terima", "answers": {}, "verdict": "pass" }`

### H.4 Garansi Toko
#### POST /api/v1/warranty/activate
**Body:** `{ "order_item_id": 102, "serial_number": "352345678912345", "customer_user_id": 45 }`

#### POST /api/v1/warranty/claims
**Body:** `{ "serial_number": "352345678912345", "issue_description": "Layar bergaris hijau", "customer_phone": "081234567890" }`

---

## I. Backoffice Master Data & Store Settings API (`/api/v1/admin`)

### I.1 Katalog Master
- `GET/POST /api/v1/admin/products` — Manajemen Produk Baru
- `GET/POST /api/v1/admin/second-products` — Manajemen Produk Bekas
- `GET/POST /api/v1/admin/categories` — Manajemen Kategori
- `GET/POST /api/v1/admin/brands` — Manajemen Brand / Merk

### I.2 Karyawan, Salesman & Vendor
- `GET/POST/PUT /api/v1/admin/employees` — Data Salesman/Karyawan toko
- `GET/POST/PUT /api/v1/admin/vendors` — Data Vendor / Supplier unit

### I.3 Promo & Pengaturan
- `GET/POST /api/v1/admin/promos` — Setup Promo bundle & diskon
- `GET/POST /api/v1/admin/manual-discounts` — Preset diskon kasir
- `GET/PUT /api/v1/admin/settings/business-units/:id` — Konfigurasi Unit Bisnis & Toko
- `GET/POST /api/v1/admin/settings/payment-methods` — Konfigurasi Rekening & MDR Rate

---

## Error Response Convention

Format belum terdokumentasi secara eksplisit. Dari kode yang terlihat, kemungkinan:
```json
// Success
{ "status": "success", "data": {} }

// Error
{ "message": "Error message" }  (Laravel default)
// atau
{ "status": "error", "message": "..." }
```

NEEDS VERIFICATION: apakah ada standard response wrapper yang konsisten.

---

## Pagination Convention

NEEDS VERIFICATION: format pagination response (Laravel default vs custom).  
Kemungkinan: `{ data: [], meta: { current_page, last_page, per_page, total } }`
