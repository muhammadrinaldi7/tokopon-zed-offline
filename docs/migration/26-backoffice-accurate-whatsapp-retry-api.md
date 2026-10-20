# 26. Backoffice Accurate ERP & WhatsApp CRM Integration Retry & Status API

Dokumen ini mencakup arsitektur teknis dan kontrak API operasional backoffice untuk melakukan manual re-trigger / retry sinkronisasi Accurate Online ERP serta pengiriman ulang struk belanja WhatsApp CRM, lengkap dengan monitoring status dan riwayat dokumen.

---

## 🎯 Tujuan & Fitur Utama

1. **Manual Retry Sinkronisasi Accurate Online ERP**:
   - Memungkinkan admin/staff backoffice memicu sinkronisasi pesanan ke Accurate secara manual jika sinkronisasi otomatis sebelumnya gagal atau terlewat.
   - Mendukung dua mode eksekusi:
     - **Asinkron (Default):** Memasukkan payload ke antrian background worker (`job:order:sync_accurate`).
     - **Sinkron (`?sync=true`):** Mengeksekusi sinkronisasi secara langsung untuk verifikasi instan.
   - Memastikan hanya order berstatus `COMPLETED` yang dapat disinkronkan.

2. **Manual Resend Struk WhatsApp CRM**:
   - Memungkinkan admin/kasir mengirim ulang notifikasi struk belanja digital PDF ke WhatsApp pelanggan.
   - Mendukung mode asinkron (`job:notification:whatsapp`) dan mode langsung (`?sync=true`).
   - Mencatat ID staf pengirim (`sent_by_user_id`) dan menyimpan audit ke tabel `message_logs`.

3. **Inquiry Status & Audit Trail**:
   - Status sinkronisasi Accurate (`SYNCED`, `PARTIAL`, `NOT_SYNCED`) beserta daftar invoice & receipt di `order_accurate_docs`.
   - Status pengiriman WhatsApp (`is_wa_sent`) beserta seluruh riwayat pengiriman di `message_logs`.

---

## 🔐 Keamanan & Hak Akses

Semua endpoint dilindungi oleh `JWTMiddleware`:
- **Header:** `Authorization: Bearer <JWT_TOKEN>`
- **Role/Permission:** Direkomendasikan untuk role `SuperAdmin`, `Admin`, `BranchManager`, atau permission `manage-accurate-products`.

---

## 📡 Daftar Endpoint API

### 1. Manual Retry Accurate Sync
- **URL:** `POST /api/v1/backoffice/accurate/sync-retry/:order_id`
- **Query Param:**
  - `sync` (optional, default `false`): set `sync=true` untuk eksekusi langsung.
- **Respon Sukses (200 OK - Asinkron):**
```json
{
  "success": true,
  "message": "Sinkronisasi Accurate berhasil dimasukkan ke antrian background",
  "data": {
    "order_id": 123,
    "order_number": "POS-20261020-0001",
    "status": "QUEUED",
    "is_async": true
  }
}
```
- **Respon Sukses (200 OK - Sinkron):**
```json
{
  "success": true,
  "message": "Sinkronisasi Accurate berhasil dieksekusi secara langsung",
  "data": {
    "order_id": 123,
    "order_number": "POS-20261020-0001",
    "status": "SYNCED",
    "invoice_no": "INV.2026.10.0001",
    "receipt_no": "CR.2026.10.0001",
    "is_async": false
  }
}
```

---

### 2. Cek Status Sinkronisasi Accurate
- **URL:** `GET /api/v1/backoffice/accurate/status/:order_id`
- **Respon Sukses (200 OK):**
```json
{
  "success": true,
  "message": "Status sinkronisasi Accurate berhasil diambil",
  "data": {
    "order_id": 123,
    "order_number": "POS-20261020-0001",
    "order_status": "COMPLETED",
    "sync_status": "SYNCED",
    "accurate_invoice_no": "INV.2026.10.0001",
    "accurate_receipt_no": "CR.2026.10.0001",
    "docs": [
      {
        "id": 1,
        "order_id": 123,
        "doc_type": "SALES_INVOICE",
        "doc_number": "INV.2026.10.0001",
        "accurate_id": "100234",
        "amount": 13000000,
        "status": "success",
        "created_at": "2026-10-20T10:15:00Z"
      },
      {
        "id": 2,
        "order_id": 123,
        "doc_type": "SALES_RECEIPT",
        "doc_number": "CR.2026.10.0001",
        "accurate_id": "100235",
        "amount": 13000000,
        "status": "success",
        "created_at": "2026-10-20T10:15:02Z"
      }
    ]
  }
}
```

---

### 3. Manual Resend Struk WhatsApp
- **URL:** `POST /api/v1/backoffice/whatsapp/resend/:order_id`
- **Query Param:**
  - `sync` (optional, default `false`): set `sync=true` untuk eksekusi langsung.
- **Respon Sukses (200 OK - Asinkron):**
```json
{
  "success": true,
  "message": "Pengiriman ulang struk WhatsApp berhasil dimasukkan ke antrian background",
  "data": {
    "order_id": 123,
    "order_number": "POS-20261020-0001",
    "recipient": "081234567890",
    "status": "QUEUED",
    "is_async": true
  }
}
```

---

### 4. Cek Riwayat Notifikasi WhatsApp
- **URL:** `GET /api/v1/backoffice/whatsapp/status/:order_id`
- **Respon Sukses (200 OK):**
```json
{
  "success": true,
  "message": "Status pengiriman WhatsApp berhasil diambil",
  "data": {
    "order_id": 123,
    "order_number": "POS-20261020-0001",
    "is_wa_sent": true,
    "total_logs": 2,
    "logs": [
      {
        "id": 105,
        "recipient": "081234567890",
        "recipient_name": "Budi Santoso",
        "status": "sent",
        "is_sent": true,
        "error_message": null,
        "sent_at": "2026-10-20T10:16:00Z",
        "created_at": "2026-10-20T10:16:00Z"
      }
    ]
  }
}
```

---

## 🏗️ Implementasi Komponen Go

- **Domain Entity:** [`internal/domain/integration.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/domain/integration.go)
- **Repository SQL:** [`internal/repository/integration_repository.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/repository/integration_repository.go)
- **Business UseCase:** [`internal/usecase/integration_usecase.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/usecase/integration_usecase.go)
- **Delivery HTTP Handler:** [`internal/delivery/http/integration_handler.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/delivery/http/integration_handler.go)
- **Unit Tests Native:**
  - [`internal/usecase/integration_usecase_test.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/usecase/integration_usecase_test.go) (100% Pass)
  - [`internal/delivery/http/integration_handler_test.go`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/internal/delivery/http/integration_handler_test.go) (100% Pass)
