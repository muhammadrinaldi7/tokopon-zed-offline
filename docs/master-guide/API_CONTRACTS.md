# 📜 Tokopon Ecosystem — Master API Contracts & Specifications

> **Spesifikasi Kontrak Antarmuka Pemrograman Aplikasi (REST API) Backend Go**  
> Sumber Implementasi: `tokopon-go` | Koleksi Postman: [`tokopon-go/docs/tokopon_go_api_collection.json`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/docs/tokopon_go_api_collection.json)  
> Status Audit: **VERIFIED**

---

## 📌 1. Standar Amplop Respon (Unified JSON Envelope)

Semua endpoint di `tokopon-go` menggunakan format amplop JSON seragam (`pkg/response/response.go`):

### A. Respon Sukses (Single Entity)
```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "Operasi berhasil dilakukan",
  "data": {
    "id": 1,
    "name": "Contoh Data"
  }
}
```

### B. Respon Sukses (Paginated List)
```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "Data berhasil dimuat",
  "data": [
    { "id": 1, "name": "Item 1" },
    { "id": 2, "name": "Item 2" }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total_records": 150,
    "total_pages": 8
  }
}
```

### C. Respon Error Terstruktur
```json
{
  "success": false,
  "code": "ERR_INSUFFICIENT_STOCK",
  "message": "Stok produk tidak mencukupi untuk checkout",
  "data": null,
  "errors": [
    {
      "field": "items[0].qty",
      "message": "Stok tersedia hanya 2 unit, diminta 5 unit"
    }
  ]
}
```

---

## 🔑 2. Headers & Autentikasi

| Header | Wajib? | Keterangan |
|---|---|---|
| `Content-Type` | Ya | `application/json` |
| `Authorization` | Untuk rute terproteksi | `Bearer <JWT_ACCESS_TOKEN>` |
| `X-Business-Unit-Id` | Opsional (Eksekutif) | ID Unit Bisnis aktif (1: Syihab, 2: GSK Second, 3: GSK Distri) |
| `X-Branch-Id` | Opsional | ID Cabang Toko pengguna |

---

## 📋 3. Katalog Endpoint per Modul

### [MOD-01] Identity, Multi-Tenant & RBAC
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/auth/login` | Publik | Login staf dengan email & password |
| `POST` | `/api/v1/auth/refresh` | Publik | Memperbarui JWT access token dengan refresh token |
| `GET` | `/api/v1/auth/me` | JWT | Mengambil profil user, roles, permissions, dan BU aktif |
| `POST` | `/api/v1/auth/switch-bu` | JWT (C-Level/Admin) | Berpindah konteks tenant unit bisnis secara aman |
| `POST` | `/api/v1/auth/logout` | JWT | Menghapus refresh token sesi aktif |

---

### [MOD-02] Retail POS & Cashier Desk
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/pos/shifts/current` | JWT Kasir | Mendapatkan shift kasir yang sedang aktif |
| `POST` | `/api/v1/pos/shifts/open` | JWT Kasir | Membuka shift kasir dengan uang modal & rincian pecahan fisik |
| `POST` | `/api/v1/pos/shifts/close` | JWT Kasir | Menutup shift kasir & menghitung rekonsiliasi selisih kas |
| `POST` | `/api/v1/pos/checkout` | JWT Kasir | Transaksi checkout atomik (pessimistic lock) & cetak struk |
| `GET` | `/api/v1/pos/receipt/:id/thermal` | JWT Kasir | Mengambil layout struk ESC/POS format 40-kolom |
| `POST` | `/api/v1/pos/sign-qz` | JWT Kasir | Generate tanda tangan RSA SHA-512 untuk QZ Tray silent print |

---

### [MOD-03] Store Inventory & Stock Opname
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/inventory/stocks` | JWT Gudang/Kasir | Daftar stok produk multi-gudang (filter cabang, search, pagination) |
| `GET` | `/api/v1/inventory/stocks/:id/breakdown` | JWT Gudang | Rincian ketersediaan stok fisik per-gudang |
| `GET` | `/api/v1/inventory/serial-numbers/:sn` | JWT Kasir/Gudang | Inquiry nomor seri (IMEI), status ketersediaan & vonis QC |
| `POST` | `/api/v1/inventory/inbound/po/:id/scan` | JWT Gudang | Scan penerimaan IMEI dari Purchase Order supplier |
| `POST` | `/api/v1/inventory/stock-opname/start` | JWT Gudang | Membuka sesi fisik stock opname toko cabang |
| `POST` | `/api/v1/inventory/stock-opname/:id/count` | JWT Gudang | Memindai IMEI fisik (mencatat FSM MATCHED/MISSING/UNEXPECTED) |
| `POST` | `/api/v1/inventory/stock-opname/:id/finalize`| JWT Gudang | Menutup sesi opname & otomatis menerbitkan stock adjustment |

---

### [MOD-04] Product Catalog & Promo Engine
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/pos/promos/eligible` | JWT Kasir | Daftar promo aktif yang berlaku untuk cabang & keranjang saat ini |
| `POST` | `/api/v1/pos/promos/calculate` | JWT Kasir | Simulasi kalkulasi diskon in-memory & alokasi ke item keranjang |
| `GET` | `/api/v1/pos/manual-discounts` | JWT Kasir | Daftar preset diskon manual yang disetujui untuk kasir |
| `GET` | `/api/v1/catalog/categories` | Publik/JWT | Daftar kategori produk aktif |
| `GET` | `/api/v1/catalog/brands` | Publik/JWT | Daftar merek dagang aktif |

---

### [MOD-05] Customer Loyalty & CRM
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/pos/loyalty/customer` | JWT Kasir | Cari pelanggan berdasarkan nomor telepon & saldo poin |
| `POST` | `/api/v1/pos/loyalty/calculate-redeem`| JWT Kasir | Hitung potongan nominal rupiah dari poin yang ditukarkan |
| `POST` | `/api/v1/pos/coupons/validate` | JWT Kasir | Validasi kupon e-commerce / kasir terhadap keranjang belanja |

---

### [MOD-06] Retail Trade-In, Buyback & Digital Device Passport
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/buyback/appraisal/calculate` | JWT Staff | Kalkulasi taksiran harga beli HP bekas berdasarkan kondisi fisik |
| `GET` | `/api/v1/qc/templates` | JWT Staff/Teknisi | Template checklist inspeksi QC perangkat bekas |
| `POST` | `/api/v1/qc/inspections` | JWT Teknisi | Kirim hasil inspeksi QC teknisi & evaluasi kegagalan fatal |
| `POST` | `/api/v1/buyback/:id/complete` | JWT Kasir | Penyelesaian buyback & auto-intake registrasi IMEI ke gudang |
| `GET` | `/api/v1/passport/:imei` | Publik/JWT | Rekonstruksi timeline digital paspor seumur hidup IMEI |

---

### [MOD-07] External Gateway & Background Integration
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `POST` | `/api/v1/webhooks/xendit` | Token Callback | Ingest webhook invoice Xendit (PAID / EXPIRED) |
| `POST` | `/api/v1/webhooks/approval` | Telegram Signature | Callback approval inline keyboard Direksi via bot n8n |
| `POST` | `/api/v1/backoffice/accurate/sync-retry/:id`| JWT Admin | Manual trigger pengiriman ulang faktur ke Accurate ERP |
| `GET` | `/api/v1/backoffice/accurate/status/:id` | JWT Admin | Status riwayat sinkronisasi dokumen Accurate |
| `POST` | `/api/v1/backoffice/whatsapp/resend/:id` | JWT Admin | Manual kirim ulang pesan nota WhatsApp digital ke pelanggan |

---

### [MOD-08] Executive Analytics & Financial Reporting
| Method | Endpoint | Proteksi Auth | Deskripsi |
|---|---|---|---|
| `GET` | `/api/v1/executive/kpi-summary` | JWT Direksi | KPI omzet kotor, omzet bersih, margin laba, dan basket size |
| `GET` | `/api/v1/executive/sales-trend` | JWT Direksi | Grafik tren penjualan harian dengan filter rentang tanggal |
| `GET` | `/api/v1/executive/branch-comparison` | JWT Direksi | Perbandingan kontribusi omzet dan profit antar-cabang toko |
| `GET` | `/api/v1/executive/payment-breakdown` | JWT Direksi | Sebaran penerimaan uang per metode pembayaran & EDC |
| `GET` | `/api/v1/reporting/profit/summary` | JWT Direksi/Finance | Laporan laba rugi P&L komprehensif berbasis HPP unit |
| `GET` | `/api/v1/reporting/cashier-shifts` | JWT Direksi/Finance | Audit rekap shift kasir dan ringkasan selisih uang fisik |
| `POST` | `/api/v1/reporting/cutover/verify` | JWT Superadmin | Verifikasi integritas data transaksi sebelum full cutover |
