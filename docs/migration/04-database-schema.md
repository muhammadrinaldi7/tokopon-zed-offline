# 04 — Database Schema Audit

**Dibuat:** 2026-10-19  
**Status:** IN PROGRESS  
**Source:** 238 file migrasi di `database/migrations/`

---

## Catatan Penting

- Database production: **MySQL**
- Dev environment: SQLite (`database/database.sqlite`, 19MB — berisi data nyata)
- Skema TIDAK boleh diubah selama fase awal migrasi
- Terdapat 238 migrasi, beberapa file `.bak` (tidak dijalankan)

---

## Tabel Inti

### users
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | |
| email | varchar unique | |
| email_verified_at | timestamp nullable | |
| password | varchar | bcrypt hashed |
| provider | varchar nullable | 'google' |
| provider_id | varchar nullable | Google UID |
| avatar | varchar nullable | URL avatar |
| identity | varchar nullable | KTP/identitas |
| npwp | varchar nullable | NPWP |
| business_unit_id | bigint FK nullable | |
| warehouse_id | bigint FK nullable | |
| branch_id | bigint FK nullable | |
| telegram_chat_id | varchar nullable | |
| push_token | varchar nullable | Expo push token |
| two_factor_secret | text nullable | 2FA (Fortify) |
| two_factor_recovery_codes | text nullable | 2FA recovery |
| remember_token | varchar nullable | |
| timestamps | | created_at, updated_at |

### business_units
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| code | varchar | kode singkat (e.g., 'syihab') |
| name | varchar | nama toko |
| store_title | varchar nullable | judul toko untuk receipt |
| prefix | varchar nullable | prefix SO (Sales Order) |
| order_prefix | varchar nullable | prefix order number |
| draft_prefix | varchar nullable | prefix draft order |
| customer_prefix | varchar nullable | prefix customer Accurate |
| accurate_host | varchar nullable | URL host Accurate |
| accurate_token | text nullable | token Accurate (SENSITIF) |
| accurate_secret_key | text nullable | secret key Accurate (SENSITIF) |
| accurate_database_id | varchar nullable | |
| accurate_return_warehouse_id | varchar nullable | |
| accurate_return_warehouse_name | varchar nullable | |
| accurate_webhook_token | varchar nullable | untuk verifikasi webhook |
| is_active | boolean default true | |
| is_taxable | boolean | |
| is_visible_mobile | boolean | tampil di mobile app |
| receipt_show_discount | boolean | |
| is_prefix_neutral | boolean | |
| mobile_display_name | varchar nullable | nama di mobile |
| mobile_category | varchar nullable | kategori toko mobile |
| mobile_description | text nullable | |
| brand_color | varchar nullable | warna brand hex |
| warranty_highlight | varchar nullable | |
| telegram_approval_webhook | varchar nullable | webhook approval |
| telegram_log_webhook | varchar nullable | webhook log |
| timestamps | | |

### orders
Berdasarkan migrasi-migrasi yang ada:
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_number | varchar unique | format: PREFIX-YYYYMMDD-XXXX |
| user_id | bigint FK nullable | customer |
| business_unit_id | bigint FK nullable | |
| branch_id | bigint FK nullable | |
| warehouse_id | bigint FK nullable | |
| order_status | varchar | WAITING_PAYMENT, WAITING_VERIFICATION, PROCESSING, SHIPPED, COMPLETED, CANCELLED |
| order_channel | varchar | POS, ONLINE, MOBILE_APP |
| order_date | date nullable | |
| subtotal | decimal | |
| total_discount | decimal | |
| grand_total | decimal | |
| shipping_cost | decimal nullable | |
| shipping_address_snapshot | json nullable | snapshot alamat pengiriman |
| notes | text nullable | |
| handled_by | bigint FK nullable | user yang handle (kasir) |
| sales_id | bigint FK nullable | ID employe sebagai sales |
| payment_method_id | bigint FK nullable | |
| payment_method_rate_id | bigint FK nullable | |
| payment_expired_at | timestamp nullable | |
| accurate_so_number | varchar nullable | nomor SO di Accurate |
| accurate_invoice_number | varchar nullable | |
| receipt_email_sent | boolean | |
| receipt_wa_sent | boolean | |
| coupon_id | bigint FK nullable | |
| coupon_code | varchar nullable | |
| coupon_discount_amount | decimal nullable | |
| loyalty_points_used | int nullable | |
| loyalty_points_earned | int nullable | |
| timestamps | | |

### order_items
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_id | bigint FK | |
| product_variant_id | bigint | polymorphic FK |
| product_variant_type | varchar | App\Models\ProductVariant atau App\Models\SecondProductVariant |
| product_name | varchar nullable | snapshot nama produk |
| qty | int | |
| price_at_checkout | decimal | harga saat checkout |
| discount_amount | decimal | diskon manual |
| promo_discount_amount | decimal | diskon promo |
| subtotal | decimal | qty * price - discount |
| serial_number | varchar nullable | comma-separated (legacy) |
| applied_promo_id | bigint FK nullable | |
| sales_id | bigint FK nullable | ID employe |
| unit_hpp | decimal nullable | HPP per unit |
| total_hpp | decimal nullable | total HPP |
| timestamps | | |

### products
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| slug | varchar unique | digunakan sebagai route key |
| name | varchar | |
| category_id | bigint FK | |
| brand_id | bigint FK | |
| business_unit_id | bigint FK nullable | |
| is_active | boolean | |
| has_active_accurate | boolean | apakah ada ProductAccurate aktif |
| total_stock | int | aggregate (NEEDS VERIFICATION: apakah computed atau stored) |
| specifications | json nullable | |
| timestamps | | |

### product_variants
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | bigint FK | |
| sell_phone_id | bigint FK nullable | relasi ke SellPhone (second hand) |
| sku | varchar | |
| name | varchar | label varian (e.g., '128GB Black') |
| price | decimal | |
| stock | int | |
| is_active | boolean | |
| has_sn | boolean | apakah tracking serial number |
| timestamps | | |

### product_accurates
Tabel mapping antara produk internal dan item di Accurate ERP:
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_unit_id | bigint FK | |
| accurate_item_no | varchar | kode item di Accurate |
| name | varchar | |
| sku | varchar nullable | |
| base_price | decimal | harga jual |
| base_cost | decimal nullable | HPP |
| buy_price | decimal nullable | harga beli |
| vendor_name | varchar nullable | nama vendor |
| vendor_no | varchar nullable | kode vendor di Accurate |
| stock | int | stok di Accurate |
| has_sn | boolean | |
| is_addon | boolean | |
| item_type | varchar nullable | INVENTORY, NON_INVENTORY |
| os | varchar nullable | |
| proyek | varchar nullable | kode project Accurate |
| timestamps | | |

### warehouse_stocks
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_accurate_id | bigint FK | |
| warehouse_id | bigint FK | |
| quantity | int | stok aggregate |
| timestamps | | |

### product_serial_numbers
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_accurate_id | bigint FK | |
| business_unit_id | bigint FK nullable | |
| serial_number | varchar | IMEI/SN |
| vendor_id | bigint FK nullable | |
| vendor_no | varchar nullable | |
| hpp | decimal nullable | |
| receipt_date | date nullable | |
| qc_status | varchar nullable | passed, failed |
| status | varchar | available, sold, returned, damaged |
| timestamps | | |

### warranties
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_item_id | bigint FK nullable | |
| warranty_policy_id | bigint FK nullable | |
| customer_user_id | bigint FK nullable | |
| device_inspection_id | bigint FK nullable | |
| serial_number | varchar | |
| activated_at | timestamp nullable | |
| expires_at | timestamp nullable | |
| claims_used | int default 0 | |
| extra_claims | int default 0 | |
| parent_warranty_id | bigint FK nullable | untuk lineage switch |
| previous_serial | varchar nullable | |
| timestamps | | |

### warranty_claims
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| warranty_id | bigint FK | |
| customer_user_id | bigint FK nullable | |
| claimed_by | bigint FK nullable | staff |
| approved_by | bigint FK nullable | |
| receiving_inspection_id | bigint FK nullable | |
| status | varchar | pending, approved, in_repair, waiting_payment, waiting_refund, completed, rejected |
| claim_reason | text | |
| complaint_description | text nullable | |
| claimed_at | timestamp | |
| resolved_at | timestamp nullable | |
| refund_amount | decimal nullable | |
| payment_method | varchar nullable | |
| payment_proof | varchar nullable | |
| replacement_serial | varchar nullable | |
| replacement_notes | text nullable | |
| timestamps | | |

### sell_phones
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK | customer |
| product_accurate_id | bigint FK nullable | produk target buyback |
| phone_brand | varchar | |
| phone_model | varchar | |
| phone_ram | varchar nullable | |
| phone_storage | varchar nullable | |
| imei | varchar nullable | |
| appraised_value | int | nilai taksiran |
| original_appraised_value | int nullable | sebelum adjustment |
| is_price_adjusted | boolean | |
| price_adjusted_by | bigint FK nullable | |
| price_adjustment_reason | text nullable | |
| status | varchar | PENDING, APPROVED, REJECTED, PAID |
| bank_name | varchar nullable | rekening customer |
| bank_account_number | varchar nullable | |
| bank_account_name | varchar nullable | |
| invoice_number | varchar nullable | |
| handled_by | bigint FK nullable | |
| branch_id | bigint FK nullable | |
| business_unit_id | bigint FK nullable | |
| sales_id | bigint FK nullable | |
| is_wa_sent | boolean | |
| is_email_sent | boolean | |
| minus_desc | text nullable | catatan kerusakan |
| timestamps | | |

### approval_requests
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| approvable_type | varchar | morphable model type |
| approvable_id | bigint | morphable model id |
| request_type | varchar | ORDER_CANCELLATION, SELL_PHONE_APPROVAL, dll |
| requested_by | bigint FK | user |
| business_unit_id | bigint FK nullable | |
| branch_id | bigint FK nullable | |
| status | varchar | PENDING, APPROVED, REJECTED, COMPLETED |
| total_amount | decimal nullable | untuk threshold approval |
| payload | json nullable | data tambahan untuk handler |
| timestamps | | |

### loyalty_accounts
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| user_id | bigint FK nullable | |
| phone_number | varchar unique | format: 628xxx |
| customer_name | varchar | |
| points_balance | int default 0 | saldo poin aktif |
| lifetime_points | int default 0 | total poin sepanjang masa |
| total_spend | decimal default 0 | total belanja |
| tier | varchar | BRONZE, SILVER, GOLD, PLATINUM |
| timestamps | | |

### loyalty_transactions
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| loyalty_account_id | bigint FK | |
| order_id | bigint FK nullable | |
| type | varchar | earn, redeem, refund, cancel |
| points | int | positif atau negatif |
| description | varchar nullable | |
| timestamps | | |

### ecommerce_coupons
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_unit_id | bigint FK nullable | null = berlaku semua BU |
| code | varchar unique | kode kupon |
| type | varchar | FIXED_AMOUNT, PERCENTAGE, SHIPPING_FIXED, SHIPPING_PERCENTAGE |
| discount_value | decimal | |
| max_discount_amount | decimal nullable | cap untuk PERCENTAGE |
| min_purchase_amount | decimal nullable | |
| usage_limit_total | int nullable | kuota global |
| usage_limit_per_user | int nullable | |
| used_count | int default 0 | |
| valid_from | datetime nullable | |
| valid_until | datetime nullable | |
| is_active | boolean | |
| timestamps | | |

### cashier_shifts
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_unit_id | bigint FK | |
| user_id | bigint FK | kasir |
| branch_id | bigint FK | cabang toko |
| shift_date | date | tanggal shift (1 shift/hari/kasir) |
| opened_at | timestamp | waktu buka shift |
| closed_at | timestamp nullable | waktu tutup shift |
| starting_cash | decimal | modal awal fisik kasir |
| expected_cash | decimal nullable | starting_cash + total_cash_sales |
| actual_cash | decimal nullable | fisik uang tunai di laci saat closing |
| cash_difference | decimal nullable | actual_cash - expected_cash |
| total_cash_sales | decimal | total pembayaran tunai order COMPLETED |
| total_non_cash_sales | decimal | total pembayaran non-tunai order COMPLETED |
| total_transactions | int | jumlah order diproses kasir |
| status | varchar | 'open', 'closed' |
| opening_notes | text nullable | catatan saat buka shift |
| closing_notes | text nullable | catatan saat tutup shift |
| reconciliation_status | varchar nullable | 'balanced', 'over', 'short' |
| timestamps | | |

### cashier_shift_denominations
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| shift_id | bigint FK | foreign key ke cashier_shifts |
| type | varchar | 'opening', 'closing' |
| denomination | int | pecahan (100000, 50000, 20000, dst) |
| quantity | int | lembar / keping |
| subtotal | decimal | denomination * quantity |
| timestamps | | |

### payment_methods
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_unit_id | bigint FK nullable | |
| name | varchar | nama metode (CASH, BCA Transfer, QRIS, dll) |
| category | varchar | 'TUNAI', 'NON-TUNAI' |
| bank_name | varchar nullable | nama bank / provider |
| account_number | varchar nullable | no rekening / VA |
| account_owner | varchar nullable | atas nama pemilik |
| accurate_bank_no | varchar nullable | kode akun kas/bank Accurate (e.g. 1102.01) |
| accurate_customer_no | varchar nullable | kode customer leasing jika finance |
| mdr_percentage | decimal | default rate MDR % |
| is_active | boolean | status aktif |
| timestamps | | |

### payment_method_rates
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| payment_method_id | bigint FK | |
| name | varchar | nama sub-rate (e.g. EDC BCA 1.5%, QRIS 0.7%) |
| mdr_percentage | decimal | persentase potongan MDR |
| accurate_account_no | varchar nullable | akun beban MDR di Accurate |
| is_active | boolean | |
| timestamps | | |

### employes (Salesman & Karyawan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| business_unit_id | bigint FK nullable | |
| branch_id | bigint FK nullable | |
| accurate_employee_id | bigint nullable | ID di Accurate |
| employee_no | varchar unique nullable | NIK / Kode karyawan |
| name | varchar | nama lengkap |
| email | varchar nullable | |
| phone_number | varchar nullable | |
| position | varchar nullable | jabatan (Salesman, FL, dll) |
| is_active | boolean | status aktif |
| user_id | bigint FK nullable | relasi ke tabel users |
| timestamps | | |

### vendors (Supplier Barang)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| accurate_vendor_id | varchar nullable | ID vendor di Accurate |
| vendor_no | varchar unique | kode unik vendor (VND-xxx) |
| vendor_name | varchar | nama distributor / supplier |
| email | varchar nullable | |
| phone | varchar nullable | |
| timestamps | | |

### stock_opnames
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| opname_number | varchar unique | nomor sesi opname |
| business_unit_id | bigint FK | |
| branch_id | bigint FK | |
| warehouse_id | bigint FK | gudang toko yang diaudit |
| user_id | bigint FK | auditor yang membuka opname |
| status | varchar | DRAFT, COUNTING, PENDING_APPROVAL, APPROVED, REJECTED, COMPLETED |
| type | varchar | ALL, SERIALIZED_ONLY, NON_SERIALIZED_ONLY |
| total_system_qty | int | stok sistem saat opname dimulai |
| total_physical_qty | int | total fisik hasil scan |
| total_difference_qty | int | selisih fisik vs sistem |
| total_loss_value | decimal | estimasi nilai kerugian selisih kurang |
| total_surplus_value | decimal | estimasi nilai selisih lebih |
| timestamps | | |

### purchase_orders (Inbound PO Supplier)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| po_number | varchar unique | nomor PO |
| vendor_id | bigint FK nullable | supplier |
| po_date | date nullable | tanggal PO |
| status | varchar | PENDING, PARTIAL, COMPLETED |
| database_source | varchar nullable | unit bisnis source |
| description | text nullable | |
| timestamps | | |

---

## Tabel Permission (Spatie)

- `roles`
- `permissions`
- `model_has_roles` (polymorphic)
- `model_has_permissions` (polymorphic)
- `role_has_permissions`

---

## Relasi Polymorphic yang Ditemukan

| Model | Relasi | Tipe |
|---|---|---|
| `ApprovalRequest` | `approvable` | morphTo (Order, SellPhone, WarrantyClaim, StockOpname, StockAdjustment) |
| `DeviceInspection` | `inspectable` | morphTo (OrderItem, SellPhone, WarrantyClaim) |
| `OrderItem` | `variant` | morphTo (ProductVariant, SecondProductVariant) |
| `TradeIn` | target | morphTo (ProductVariant, SecondProductVariant) |
| `MessageLog` | `source` | morphTo (Order, SellPhone) |
| `Media` | `model` | morphTo (Spatie MediaLibrary) |

---

## Soft Deletes

NEEDS VERIFICATION: belum ditemukan bukti penggunaan `SoftDeletes` trait dalam audit awal.

---

## Catatan Skema Penting

1. **`order_items.product_variant_type`** — polymorphic string, bisa berisi `App\Models\ProductVariant` atau `App\Models\SecondProductVariant`. Harus di-handle di Go sebagai discriminator.
2. **`orders.shipping_address_snapshot`** — JSON field, berisi snapshot alamat saat checkout. Tidak ada FK ke tabel alamat.
3. **`business_units.accurate_token`** — Menyimpan token Accurate per BU. JANGAN disimpan di log atau dokumentasi.
4. **`products.total_stock`** — Perlu diverifikasi apakah ini computed kolom atau stored integer. Jika stored, harus dijaga konsistensinya di Go.
5. **`approval_requests.payload`** — JSON bebas, isinya tergantung `request_type`. Perlu dokumentasi per type.
