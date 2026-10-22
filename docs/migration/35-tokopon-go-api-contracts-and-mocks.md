# 35 — Master API Contracts & Mocking Specifications (Tokopon Go)

**Tanggal:** 22 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** Seluruh Modul [MOD-01] s/d [MOD-08] + Waves 1 s/d 11 di `tokopon-go`  
**Berkas Postman Collection:** [`tokopon-go/docs/tokopon_go_api_collection.json`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/docs/tokopon_go_api_collection.json)  
**Dokumen Master Mocks:** [`tokopon-go/docs/API_CONTRACT_AND_MOCKS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/docs/API_CONTRACT_AND_MOCKS.md)  

---

## 1. Ringkasan & Tujuan

Sebelum melangkah ke tahap *cutover / deployment*, dokumen ini mendokumentasikan seluruh **Kontrak API & Mock Payloads** (Permintaan, Respon Sukses, Respon Gagal, Headers, dan Struktur Error) yang telah selesai dibangun di backend Go.

Format respons konsisten dengan envelope standar Go (`pkg/response/response.go`):
- **Success:** `{ "success": true, "code": "SUCCESS", "message": "...", "data": {...} }`
- **Paginated:** `{ "success": true, "code": "SUCCESS", "message": "...", "data": [...], "meta": { "page": 1, "per_page": 50, "total_records": 128, "total_pages": 3 } }`
- **Error:** `{ "success": false, "code": "ERR_CODE", "message": "...", "data": null, "errors": [...] }`

---

## 2. Matriks Grup Kontrak API

| Grup / Modul | Prefix Rute | Proteksi Auth | Contoh Mock Endpoint Utama |
|---|---|---|---|
| **System & Health** | `/`, `/ping`, `/health` | Publik | `GET /api/v1/health` |
| **[MOD-01] Identity & Multi-Tenant** | `/api/v1/auth`, `/api/v1/mobile/auth` | Publik & JWT | `POST /api/v1/auth/login`, `POST /api/v1/auth/switch-bu` |
| **[MOD-02] Retail POS & Kasir** | `/api/v1/pos` | JWT Kasir / Staff | `POST /api/v1/pos/checkout`, `POST /api/v1/pos/shifts/close` |
| **[MOD-03] Store Inventory & Opname** | `/api/v1/inventory` | JWT Gudang | `GET /api/v1/inventory/stocks`, `POST /api/v1/inventory/stock-opname/:id/finalize` |
| **[MOD-04] Catalog & Promo Engine** | `/api/v1/pos/promos`, `/api/v1/catalog` | JWT Kasir / Publik | `POST /api/v1/pos/promos/calculate`, `GET /api/v1/pos/promos/eligible` |
| **[MOD-05] Customer Loyalty & CRM** | `/api/v1/pos/loyalty`, `/api/v1/coupons` | JWT Kasir / Customer | `GET /api/v1/pos/loyalty/customer`, `POST /api/v1/pos/coupons/validate` |
| **[MOD-06] Trade-In & Device Passport** | `/api/v1/trade-in`, `/api/v1/buyback`, `/api/v1/qc`, `/api/v1/passport` | JWT Staff / Publik | `POST /api/v1/buyback/appraisal/calculate`, `GET /api/v1/passport/:imei` |
| **[MOD-07] External Gateway & Integration** | `/api/v1/backoffice`, `/api/v1/webhooks`, `/api/v1/settings` | Signature / Admin | `POST /api/v1/webhooks/xendit`, `POST /api/v1/backoffice/accurate/sync/:order_id` |
| **[MOD-08] Executive Analytics & Reporting** | `/api/v1/executive`, `/api/v1/reporting` | JWT Director / Superadmin | `GET /api/v1/executive/kpi-summary`, `POST /api/v1/reporting/cutover/verify` |
| **Mobile App & Realtime Chat** | `/api/v1/mobile`, `/ws/connect` | Customer JWT / Guest | `POST /api/v1/mobile/orders/checkout`, `ws://localhost:8080/ws/connect` |

---

## 3. Postman Collection Ready-to-Use

Berkas JSON Postman Collection v2.1 telah disediakan di [`tokopon-go/docs/tokopon_go_api_collection.json`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go/docs/tokopon_go_api_collection.json).
Fitur koleksi:
1. **Environment Variables Terintegrasi:** `{{base_url}}` (default: `http://localhost:8080`) dan `{{token}}`.
2. **Preset Headers:** Otomatis menyertakan `Content-Type: application/json` dan `Authorization: Bearer {{token}}`.
3. **Saved Mock Examples:** Setiap request memiliki saved response mock 200 OK dengan payload realistis yang siap digunakan untuk Mock Server Postman maupun integrasi Frontend/QA.
