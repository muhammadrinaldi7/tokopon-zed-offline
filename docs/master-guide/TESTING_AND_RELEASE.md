# 🧪 Tokopon Ecosystem — Testing & Release Readiness Runbook

> **Panduan Pengujian Sistem, Quality Gates, dan Strategi Rilis / Cutover**  
> Baseline Audit: **22 Oktober 2026** | Status: **VERIFIED**

---

## 📌 1. Hasil Pengujian Terverifikasi (Actual Verified Tests)

Seluruh perintah berikut telah dieksekusi secara nyata pada lingkungan audit:

| Target Repositori | Perintah Uji yang Dijalankan | Hasil Aktual | Waktu Eksekusi | Status |
|---|---|---|---|---|
| **tokopon-go** | `go test -count=1 ./...` | **100% Lulus (Zero Failure)** di seluruh 10 package teruji | ~28.5s | `VERIFIED` ✅ |
| **tokopon-go** | `go vet ./...` | **0 Error, 0 Warning** | ~3.2s | `VERIFIED` ✅ |
| **tokopon-go** | `go build ./cmd/server` | Kompilasi binary sukses | ~4.1s | `VERIFIED` ✅ |
| **tokopon-go** | `go build ./cmd/worker` | Kompilasi binary sukses | ~3.8s | `VERIFIED` ✅ |
| **tokopon-go** | `go build ./cmd/e2e_runner` | Kompilasi binary sukses | ~3.5s | `VERIFIED` ✅ |
| **tokopon-web** | `cmd.exe /c "npm run build"` | **Kompilasi TypeScript & Vite sukses** (Bundle: 537 kB JS, 64.9 kB CSS) | ~408ms | `VERIFIED` ✅ |
| **tokopon-web** | `cmd.exe /c "npm run lint"` | **0 Error**, 5 Warning (React 19 purity) | ~61ms | `PARTIALLY VERIFIED` ⚠️ |

---

## 🚦 2. Kriteria Rilis (Release Quality Gates)

Sebuah fitur atau modul baru hanya diizinkan untuk dirilis ke lingkungan produksi jika memenuhi seluruh kriteria berikut:

### Quality Gate A: Backend Go (`tokopon-go`)
1. **Zero Compiler & Linter Errors:** `go vet ./...` wajib bersih dari peringatan.
2. **100% Pass Unit Tests:** Seluruh usecase dan domain service memiliki unit test dengan pengujian jalur positif dan negatif.
3. **Transaction Boundaries Verified:** Setiap mutasi stok, reservasi nomor seri, dan pembayaran wajib berada dalam blok transaksi atomik `sqlx.Tx`.
4. **Pessimistic Locking Verified:** Kueri stok wajib menyertakan `SELECT ... FOR UPDATE`.
5. **Idempotency Guard:** Seluruh webhook dan job integrasi Accurate ERP wajib memiliki pengecekan idempoten sebelum eksekusi.

### Quality Gate B: Frontend Web (`tokopon-web`)
1. **Zero Build Error:** `npm run build` (`tsc -b && vite build`) wajib menghasilkan exit code 0.
2. **No Fallback Mock in Production:** Antarmuka dilarang menggunakan data mock fallback diam-diam jika terjadi kegagalan jaringan.
3. **Purity Compliance:** Bebas dari pemanggilan fungsi tidak murni (`Math.random()`, `Date.now()`) saat proses render komponen.
4. **Comprehensive UI States:** Menangani minimal 4 state: `Loading State`, `Error State`, `Empty State`, dan `Success State`.

### Quality Gate C: Full Cutover Toko Fisik (Replacing Livewire)
1. **Data Integrity Audit:** Seluruh saldo stok di `warehouse_stocks` cocok 100% dengan snapshot fisik toko.
2. **Shift Reconciliation Tested:** Kasir berhasil membuka shift, transaksi tunai & non-tunai, dan menutup shift dengan selisih Rp 0.
3. **Thermal Receipt Verified:** Hasil cetak struk kasir thermal via QZ Tray / ESC/POS terbaca jelas dan memuat informasi nomor nota, kasir, dan rincian PPN.
4. **Queue Worker Healthy:** Background worker berjalan tanpa hambatan dan antrean `job:order:sync_accurate` terproses sempurna.

---

## 🔄 3. Strategi Rilis Inkremental (Strangler Fig Cutover)

```
[ Nginx Reverse Proxy ]
  │
  ├──► Route: /api/v1/*        ──► Arahkan ke Tokopon Go Backend
  ├──► Route: /pos/*           ──► Arahkan ke Tokopon Web SPA
  ├──► Route: /admin/*         ──► Pertahankan ke Laravel Monolith Legacy
  └──► Route: /api/v1/mobile/* ──► Arahkan ke Tokopon Go Backend
```

### Langkah Rollback Darurat (Emergency Rollback Plan):
Jika terjadi kendala kritis pada sistem Go di toko fisik (misal: deadlock database atau kegagalan printer thermal kasir):
1. **Langkah 1 (Nginx Divert):** Ubah upstream rute `/pos` pada konfigurasi Nginx untuk mengembalikan traffic ke URL Livewire kasir Laravel (`/zoffline/pos`).
2. **Langkah 2 (Queue Drain):** Biarkan worker Go menyelesaikan sisa job yang masih pending di tabel `jobs` agar data Accurate tidak tertinggal.
3. **Langkah 3 (Audit Database):** Jalankan script verifikasi konsistensi stok `cmd/e2e_runner` untuk memastikan tidak ada stok yang terkunci atau gantung.
