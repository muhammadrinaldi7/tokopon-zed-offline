# 14 — Wave 2: Mobile Catalog & Stores API

**Dibuat:** 2026-10-19  
**Status:** COMPLETED & VERIFIED ✅  
**Base URL:** `http://localhost:8080/api/v1/mobile`  
**Authentication:** Public Read-Only

---

## 1. Daftar Endpoint yang Selesai Dimigrasikan

| Endpoint | Method | Fungsi | Parameter Kunci |
|---|---|---|---|
| `/business-units` | GET | Daftar Toko Online (Store Switcher) | - |
| `/banners` | GET | Carousel Banner Promo | `business_unit_id` |
| `/warehouses` | GET | Daftar Gudang Toko Online | `business_unit_id`, `online_only` |
| `/products` | GET | Daftar Produk E-Commerce (Pagination) | `search`, `category`, `brand`, `min_price`, `max_price`, `sort`, `page`, `limit` |
| `/products/categories` | GET | Daftar Kategori Produk Tersedia | `business_unit_id` |
| `/products/brands` | GET | Daftar Merek Produk Tersedia | `business_unit_id` |
| `/products/:id` | GET | Detail Produk & Stok per Gudang | `id` (path) |
| `/products/:id/serial-numbers` | GET | Daftar Nomor Seri (SN/IMEI) Available | `id` (path), `warehouse_id` |

---

## 2. Fitur & Keunggulan Khusus di Backend Go

1. **Performa Agregasi Stok Realtime:**
   - Stok dihitung langsung dari `warehouse_stocks.stock` yang terhubung ke gudang berstatus `is_online_store = 1`.
   - Mengabaikan produk tanpa stok atau berharga 0 secara otomatis.
2. **Eliminasi Sampah Katalog (LDU & Demo):**
   - Kategori yang mengandung `%LDU%` atau `%DEMO%` otomatis difilter di level query database.
3. **Format Mata Uang Rupiah Eksplisit:**
   - Field `price` berupa numeric `float64` dan field `formatted_price` berupa string terformat (`Rp 9.000.000`).
4. **Respon Seragam (Scalable Wrapper):**
   - Mengembalikan `success: true`, `code: "SUCCESS"`, `data`, dan `meta` untuk pagination.
