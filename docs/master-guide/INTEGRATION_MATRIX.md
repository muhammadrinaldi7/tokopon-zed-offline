# 🔌 Tokopon Ecosystem — Integration Matrix (Frontend & External)

> **Matriks Kesiapan Integrasi Frontend ke Backend Go dan Layanan Eksternal**  
> Status Audit: **VERIFIED** | Baseline: **22 Oktober 2026**

---

## 📌 1. Matriks Integrasi Frontend (`tokopon-web`) ke Backend Go

| Halaman / Fitur | Status UI | Endpoint Backend Terkait | Status Backend | Status Integrasi Frontend | Loading / Error State | Bukti Pengujian | Gap & Next Action |
|---|---|---|---|---|---|---|---|
| **Halaman Login Staf** | `SELESAI` | `POST /api/v1/auth/login` | `READY` | `TERHUBUNG` | Lengkap (Toast & Spinner) | Manual & Build Pass | Siap diuji end-to-end |
| **Switch Business Unit** | `SELESAI` | `POST /api/v1/auth/switch-bu` | `READY` | `TERHUBUNG` | Lengkap (Dropdown C-Level) | Unit test Go Pass | Siap diuji end-to-end |
| **Buka & Tutup Shift Kasir** | `SELESAI` | `POST /api/v1/pos/shifts/open`, `/close` | `READY` | `MOCK FALLBACK` | Ada fallback mock otomatis | `posApi.ts:206` | **Hapus mock fallback**; tampilkan error jaringan riil |
| **Katalog & Scan Item POS** | `SELESAI` | `GET /api/v1/inventory/stocks` | `READY` | `MOCK FALLBACK` | Ada fallback `MOCK_PRODUCTS` | `posApi.ts:241` | Hubungkan langsung ke endpoint stok riil |
| **Lookup Pelanggan & Loyalty** | `SELESAI` | `GET /api/v1/pos/loyalty/customer` | `READY` | `MOCK FALLBACK` | Ada fallback "Budi Santoso" | `posApi.ts:258` | Hubungkan langsung ke nomor telepon riil pelanggan |
| **Checkout & Cetak Struk** | `SELESAI` | `POST /api/v1/pos/checkout` | `READY` | `MOCK FALLBACK` | Ada fallback mock order | `posApi.ts:284` | **Hapus mock checkout**; gunakan respon Go riil |
| **Tabel Stok Multi-Gudang** | `STUB (<div>)` | `GET /api/v1/inventory/stocks` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/inventory/*` | Bangun komponen tabel stok di `src/modules/inventory` |
| **Inbound PO Receiving** | `STUB (<div>)` | `POST /api/v1/inventory/inbound/po/:id/scan` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/inventory/*` | Bangun scanner barcode PO di `tokopon-web` |
| **Sesi Stock Opname Fisik** | `STUB (<div>)` | `POST /api/v1/inventory/stock-opname/*` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/inventory/*` | Bangun wizard opname toko cabang |
| **Trade-In & Buyback Wizard** | `STUB (<div>)` | `POST /api/v1/buyback/appraisal/calculate` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/tradein/*` | Bangun formulir taksiran HP bekas |
| **Checklist QC Teknisi** | `STUB (<div>)` | `POST /api/v1/qc/inspections` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/tradein/*` | Bangun form inspeksi kelayakan fisik |
| **Digital Device Passport** | `STUB (<div>)` | `GET /api/v1/passport/:imei` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/tradein/*` | Bangun halaman timeline paspor IMEI |
| **Dashboard KPI Eksekutif** | `STUB (<div>)` | `GET /api/v1/executive/kpi-summary` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/analytics/*` | Bangun kartu KPI & grafik tren penjualan |
| **Laporan Laba Rugi P&L** | `STUB (<div>)` | `GET /api/v1/reporting/profit/summary` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/analytics/*` | Bangun visualisasi breakdown profit per cabang/brand |
| **Rekap Shift Kasir Toko** | `STUB (<div>)` | `GET /api/v1/reporting/cashier-shifts` | `READY` | `BELUM TERHUBUNG` | Belum ada UI | Route: `/analytics/*` | Bangun tabel audit selisih kas |

---

## 🌐 2. Matriks Integrasi Layanan Pihak Ketiga (Third-Party)

| Layanan Eksternal | Modul Pengendali | Protokol & Endpoint | Status Driver Go | Mekanisme Idempotensi | Status Pengujian |
|---|---|---|---|---|---|
| **Accurate Online ERP** | `internal/integration/accurate` | HTTPS REST (`/customer/save.do`, `/sales-invoice/save.do`, `/sales-receipt/save.do`) | `READY` | Tabel `order_accurate_docs` mencatat ID dokumen faktur & kwitansi | `VERIFIED` (Unit & E2E Runner tested) |
| **WhatsApp CRM Gateway** | `internal/integration/whatsapp` | HTTPS REST (`/api/zedpos/nota` & Mekari Qontak Direct) | `READY` | Cek flag `orders.is_wa_sent` & log di `message_logs` | `VERIFIED` (Formatter & Worker unit tested) |
| **Xendit Payment Gateway**| `internal/modules/integration` | Webhook Receiver (`POST /api/v1/webhooks/xendit`) | `READY` | Verifikasi token `x-callback-token` & locking status pesanan | `VERIFIED` (Handler unit tested) |
| **Telegram Bot Direksi** | `internal/modules/integration` | Webhook Receiver (`POST /api/v1/webhooks/approval`) | `READY` | Validasi HMAC signature callback n8n | `VERIFIED` (Handler unit tested) |
| **QZ Tray Silent Print** | `internal/modules/pos` | SHA-512 RSA Signature (`POST /api/v1/pos/sign-qz`) | `READY` | Dynamic signing request menggunakan private key kasir | `VERIFIED` (Handler unit tested) |

---

## ⚠️ 3. Analisis Kesenjangan Kontrak (Contract Mismatches)

1. **Mock Fallback Menyembunyikan Status Offline:**  
   Di `tokopon-web/src/modules/pos/api.ts`, kegagalan request dibungkus oleh `catch` yang secara otomatis mengembalikan objek dummy (contoh: baris 206 `mockShift`, baris 241 `MOCK_PRODUCTS`, baris 258 mock customer `Budi Santoso`, baris 284 mock order).  
   *Rekomendasi:* Hapus fallback mock dari file produksi. Gunakan React Error Boundary atau banner notifikasi "Mode Offline / Backend Tidak Terjangkau" agar operator kasir mengetahui status sebenarnya.
2. **Format Tanggal Transaksi:**  
   Backend Go dan SQL reporting mengharapkan `orders.order_date` berformat `YYYY-MM-DD HH:mm:ss`. Pastikan payload frontend tidak mengirim ISO-8601 string dengan timezone offset mentah tanpa normalisasi.
