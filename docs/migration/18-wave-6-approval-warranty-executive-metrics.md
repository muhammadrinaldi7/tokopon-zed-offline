# 18 — Wave 6: Approval Workflow, Warranty, Trade-In & Executive Metrics

**Dibuat:** 2026-10-19  
**Status:** COMPLETED & VERIFIED ✅  
**Base URL HTTP:** `http://localhost:8080/api/v1`  
**Testing:** 44/44 Go Native Tests Passed (`go test -v ./...`)

---

## 1. Daftar Endpoint yang Selesai Dimigrasikan

| Endpoint | Method | Fungsi | Parameter Kunci |
|---|---|---|---|
| `/webhooks/approval` | POST | Webhook callback inline keyboard approval Telegram n8n | Header `X-API-KEY`, payload: `approval_id`, `action`, `req_level`, `telegram_chat_id` |
| `/mobile/warranty/:serial_number` | GET | Validasi dan pengecekan masa aktif garansi IMEI perangkat | `:serial_number` (IMEI) |
| `/public/trade-in/brands` | GET | Daftar brand ponsel untuk kalkulator tukar tambah | `type` (`new`, `second`, `old`, `all`) |
| `/public/trade-in/old-devices` | GET | Daftar ponsel lama eligible tukar tambah / buyback | `brand`, `proyek`, `search`, `limit` |
| `/public/trade-in/target-devices` | GET | Daftar ponsel tujuan upgrade (baru/bekas) | `target_type`, `brand`, `proyek`, `search` |
| `/public/trade-in/calculate` | POST | Estimasi nilai tambah bayar & WhatsApp consultation link | `old_device_id`, `target_device_id`, `customer_name` |
| `/public/trade-in/device/:id` | GET | Estimasi beli perangkat lama untuk direct sell | `:id` (device ID) |
| `/executive/login` | POST | Login staf eksekutif / direksi (role guard) | `email`, `password` |
| `/executive/me` | GET | Profil data staf eksekutif yang sedang login | Header Bearer JWT |
| `/executive/kpi-summary` | GET | Ringkasan KPI: Omset, Transaksi, Laba Kotor, Margin, Avg Basket | `date_range`, `start_date`, `end_date`, `branch` |
| `/executive/sales-trend` | GET | Tren chart penjualan & profit harian/bulanan | `date_range` |
| `/executive/branch-comparison` | GET | Komparasi omset & persentase kontribusi per cabang | `date_range` |
| `/executive/payment-breakdown` | GET | Distribusi transaksi per metode pembayaran | `date_range` |

---

## 2. Fitur & Keunggulan Khusus di Backend Go

1. **Multi-Level Approval Engine & Telegram Callback:**
   - Level lock validation mencegah penekanan tombol callback Telegram yang sudah kadaluarsa.
   - Otorisasi berbasis role Spatie (`manager`, `admin`, `director`, `superadmin`).
   - Integrasi otomatis dengan webhook n8n bot untuk notifikasi personal dan group logging Telegram.
2. **Warranty & Trade-In Services:**
   - Verifikasi garansi instan berdasarkan nomor seri/IMEI dengan kalkulasi sisa hari dan kuota klaim.
   - Smart calculation tukar tambah smartphone dengan URL WhatsApp terenkode otomatis untuk kemudahan konversi penjualan retail.
3. **Executive Analytics & KPI Metrics Engine:**
   - Perhitungan omset dan laba kotor (HPP) berkinerja tinggi langsung dari database tanpa overhead ORM berat.
   - Filter tanggal fleksibel (`today`, `yesterday`, `this_week`, `this_month`, `last_month`, `this_year`, `custom`).
4. **Go Native Testing:**
   - Seluruh pengujian use case dan HTTP handler menggunakan native package Go (`testing` & `httptest`), mencapai 100% kelulusan (44/44 test cases).
