# 📜 Tokopon Ecosystem — Master Guide Changelog

> **Catatan Riwayat Pembaruan Master Guide, Transisi Status, dan Rilis Fitur**

---

## [1.0.1] — 2026-10-22

### ⚡ Penyelesaian Task P0 Kritis di Frontend (tokopon-web)
- **TASK-001 (Pembersihan Fallback Mock & Real Network Error Handling):**
  - Menghapus seluruh fallback mock tiruan di `tokopon-web/src/modules/pos/api.ts` (mock shift, mock customer, mock order checkout).
  - Menambahkan penanganan pesan error nyata pada `OpenShiftModal.tsx` dan `CloseShiftModal.tsx` dengan banner alert `AlertTriangle`.
  - Mengintegrasikan pemindaian barcode nyata dengan endpoint serial number (`checkSerialNumber`) dan katalog stok (`searchProducts`) di `Step2Cart.tsx`.
- **TASK-002 (Pembersihan Purity & Effect Warnings React 19):**
  - Mengganti `Math.random()` di `src/components/ui/Input.tsx` dengan hook React bawaan `useId()`.
  - Menghilangkan peringatan render impure `Date.now()` di `Step4Payment.tsx` dengan mendefinisikan `generateRefCode()` di level modul.
  - Menghilangkan peringatan `set-state-in-effect` di `Step2Cart.tsx` dan `Step4Payment.tsx`.
  - **Hasil Verifikasi:** Linter `oxlint` lulus **0 error dan 0 warning**; `tsc -b && vite build` lulus 100%.

---

## [1.0.0] — 2026-10-22

### 🎉 Inisialisasi Tokopon Master Guide
- **Audit Komprehensif Ekosistem Tokopon:**
  - Audit berbasis bukti terhadap 3 repository: `tokopon-zed-offline` (Laravel legacy), `tokopon-go` (Go 1.26), dan `tokopon-web` (React 19).
  - Penetapan baseline git commit:
    - `tokopon-zed-offline`: branch `tokopon-go`, commit `2e8e488`
    - `tokopon-go`: branch `main`, commit `8b226d8`
    - `tokopon-web`: branch `main`, commit `80c0f64`
- **Verifikasi Pengujian Nyata:**
  - `tokopon-go`: 100% lulus uncached unit tests (`go test -count=1 ./...`), `go vet ./...` 0 error, build server/worker/e2e sukses.
  - `tokopon-web`: Kompilasi TypeScript & Vite build sukses (`tsc -b && vite build`), linter `oxlint` mendeteksi 0 error dan 5 peringatan kelemahan React purity.
- **Penerbitan Dokumen Inti Master Guide:**
  - `README.md`: Entry point dan peta navigasi seluruh dokumen.
  - `PROJECT_STATUS.md`: Snapshot progres terukur 4 dimensi, status aktual, gap, dan prioritas.
  - `SYSTEM_ARCHITECTURE.md`: Diagram arsitektur, multi-tenancy BU 1/2/3, topologi Strangler Fig.
  - `BUSINESS_LOGIC.md`: Rekonstruksi alur bisnis Laravel (POS, Promo Engine, Opname, Buyback, QC, HPP).
  - `FEATURE_MATRIX.md`: Matriks 26 fitur bisnis lengkap dengan acceptance criteria dan bukti pengujian.
  - `MIGRATION_MATRIX.md`: Pemetaan paritas Laravel ke Go per komponen.
  - `API_CONTRACTS.md`: Spesifikasi amplop JSON standar dan katalog endpoint seluruh modul.
  - `DATABASE_MAP.md`: ERD dan kamus data 18 entitas utama database `zedpost-7`.
  - `INTEGRATION_MATRIX.md`: Kesiapan integrasi frontend-backend dan third-party (Accurate, WA, Xendit).
  - `TESTING_AND_RELEASE.md`: Runbook pengujian dan quality gates rilis produksi.
  - `BACKLOG.md`: Daftar pekerjaan terprioritas (P0 - P3) siap eksekusi.
  - `DECISIONS.md`: 9 Architectural Decision Records (ADR) aktif.
  - `RISKS_AND_BLOCKERS.md`: Matriks risiko dan langkah pembuka blocker.
  - `DEVELOPMENT_GUIDE.md`: Standar rekayasa perangkat lunak dan panduan lokal.
  - `AI_CONTEXT.md`: Instruksi ringkas dan aturan emas untuk AI coding agents.
  - `CHANGELOG.md`: Dokumen pelacak riwayat ini.
  - 4 template standar di `templates/`.
