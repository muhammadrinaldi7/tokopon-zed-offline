# 📋 Tokopon Ecosystem — Prioritized Development Backlog

> **Daftar Pekerjaan Siap Eksekusi (Actionable Backlog) dengan Acceptance Criteria & DoD**  
> Baseline Audit: **22 Oktober 2026** | Standar Prioritas: **Strict Dependencies**

---

## 📌 Prioritas Legend
- `P0 — Critical`: Isu keamanan, potensi kehilangan data, integritas transaksi keuangan, atau blocker operasional.
- `P1 — High`: Kebutuhan utama untuk mencapai integrasi frontend penuh dan paritas operasional toko fisik.
- `P2 — Medium`: Fitur pendukung, otomasi lanjutan, dan penyempurnaan pengalaman pengguna.
- `P3 — Low`: Technical debt minor, pembersihan file usang, dan optimasi non-kritis.

---

## 🚀 Daftar Task Terprioritas

### 🔴 [P0] Critical Tasks

#### TASK-001: Hapus Fallback Mocks & Terapkan Real Network Error Handling di POS Web
- **Repository:** `tokopon-web`
- **Modul:** `pos`
- **Justifikasi Bisnis:** Fallback mock in-memory di `posApi.ts` memberikan ilusi transaksi berhasil meskipun backend mati, berisiko tinggi menyebabkan transaksi kasir tidak tercatat di database toko.
- **Status:** `COMPLETED ✅` (Selesai pada 2026-10-22)
- **Dependencies:** Tidak ada
- **Acceptance Criteria:**
  1. [x] Hapus seluruh fallback mock dari `tokopon-web/src/modules/pos/api.ts` (termasuk mock shift, mock customer, mock checkout).
  2. [x] Tampilkan banner error eksplisit saat open/close shift gagal di modal (`OpenShiftModal.tsx` & `CloseShiftModal.tsx`).
  3. [x] Barcode scanner (`Step2Cart.tsx`) mencari serial number/produk nyata via API `posApi.checkSerialNumber` & `posApi.searchProducts`, menampilkan pesan error jika barang tidak ditemukan di gudang.
- **Test Evidence:** `npm run build` lulus (536 kB JS), verifikasi endpoint langsung tanpa data tiruan fiktif.

---

#### TASK-002: Perbaiki 5 Purity & Effect Warnings di `tokopon-web`
- **Repository:** `tokopon-web`
- **Modul:** `pos`, `components/ui`
- **Justifikasi Bisnis:** Memastikan kepatuhan terhadap React 19 Compiler dan mencegah bug render tak terduga pada layar kasir.
- **Status:** `COMPLETED ✅` (Selesai pada 2026-10-22)
- **Dependencies:** Tidak ada
- **Acceptance Criteria:**
  1. [x] Ganti pemanggilan `Math.random()` di `Input.tsx` dengan hook bawaan React `useId()`.
  2. [x] Pindahkan `generateRefCode` dengan `Date.now()` ke level modul di `Step4Payment.tsx`.
  3. [x] Pindahkan update state `searchQuery` di `Step2Cart.tsx` ke handler input `handleSearchChange`.
  4. [x] Inisialisasi `cashTendered` langsung dari `grandTotal` di `Step4Payment.tsx` dan hapus `useEffect` sinkron.
  5. [x] `cmd.exe /c "npm run lint"` menghasilkan **0 errors dan 0 warnings**.
- **Test Evidence:** `oxlint` selesai dalam 39ms pada 28 file dengan **0 warning dan 0 error**; `tsc -b && vite build` lulus 100%.

---

### 🟠 [P1] High Priority Tasks

#### TASK-003: Bangun Antarmuka Frontend [MOD-03] Store Inventory & Stock Opname
- **Repository:** `tokopon-web`
- **Modul:** `inventory`
- **Justifikasi Bisnis:** Menggantikan Livewire `Zoffline\StockOpname` dan `Zoffline\Warehouse\Stock` agar petugas toko dapat memeriksa stok dan melakukan opname fisik via web modern.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** TASK-001
- **Acceptance Criteria:**
  1. Halaman `/inventory/stocks`: Tabel daftar stok produk multi-gudang terpaginasi dengan pencarian nama/SKU dan filter cabang.
  2. Halaman `/inventory/opname`: Form mulai sesi opname fisik, scanner barcode IMEI, tampilan perbandingan sistem vs fisik (MATCHED, MISSING, UNEXPECTED), dan tombol finalisasi opname.
  3. Terhubung langsung ke endpoint Go `/api/v1/inventory/*`.
- **Definition of Done:** Petugas dapat membuka sesi opname, memindai IMEI, dan memfinalisasi opname menghasilkan selisih stok di database live.

---

#### TASK-004: Bangun Antarmuka Frontend [MOD-08] Executive Analytics Dashboard
- **Repository:** `tokopon-web`
- **Modul:** `analytics`
- **Justifikasi Bisnis:** Menggantikan laporan Livewire admin agar jajaran Direksi PT Syihab Store & GSK Group dapat memantau omzet, profit, dan rekonsiliasi kas cabang secara real-time.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** TASK-001
- **Acceptance Criteria:**
  1. Halaman `/analytics`: Menampilkan kartu ringkasan KPI (Omzet Kotor, Omzet Bersih, Margin Laba Kotor, Basket Size).
  2. Grafik tren penjualan harian dengan filter periode (Hari Ini, Kemarin, 7 Hari, Bulan Ini).
  3. Tabel perbandingan kinerja cabang dan breakdown metode pembayaran (Tunai vs EDC vs Transfer).
  4. Terhubung ke endpoint Go `/api/v1/executive/*` dan `/api/v1/reporting/*` dengan role guard `superadmin`, `admin`, `director`.
- **Definition of Done:** Direksi dapat login dan melihat grafik KPI penjualan yang datanya bersumber dari database MySQL `zedpost-7`.

---

#### TASK-005: Bangun Antarmuka Frontend [MOD-06] Trade-In, Buyback & Device Passport
- **Repository:** `tokopon-web`
- **Modul:** `tradein`
- **Justifikasi Bisnis:** Menggantikan Livewire `Zoffline\SellPhone` dan `Zoffline\DevicePassport` di cabang GSK Second.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** TASK-001
- **Acceptance Criteria:**
  1. Wizard taksiran harga buyback HP bekas (input spesifikasi, kondisi fisik, kalkulasi taksiran harga).
  2. Form checklist inspeksi teknisi QC dengan evaluasi fatal failure.
  3. Halaman pencarian paspor digital IMEI (`/passport/:imei`) yang menampilkan riwayat lengkap unit seumur hidup.
  4. Terhubung ke endpoint Go `/api/v1/buyback/*`, `/api/v1/qc/*`, dan `/api/v1/passport/:imei`.
- **Definition of Done:** Staf dapat menginput HP bekas, menyelesaikan checklist QC, dan menerbitkan nota buyback secara end-to-end.

---

### 🟡 [P2] Medium Priority Tasks

#### TASK-006: Setup Automated CI Pipeline (GitHub Actions) untuk Go & Web
- **Repository:** Seluruh repository
- **Modul:** DevOps / CI
- **Justifikasi Bisnis:** Mencegah regresi kode dengan memastikan pengujian unit test Go dan build frontend berjalan otomatis pada setiap pull request.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** TASK-002
- **Acceptance Criteria:**
  1. Workflow `.github/workflows/ci.yml` di `tokopon-go`: menjalankan `go vet ./...` dan `go test -race ./...`.
  2. Workflow di `tokopon-web`: menjalankan `npm run lint` dan `npm run build`.
- **Definition of Done:** Badge build hijau terpasang di README masing-masing repository.

---

#### TASK-007: Halaman Monitoring Backoffice Sync Accurate & WhatsApp
- **Repository:** `tokopon-web`
- **Modul:** `integration`
- **Justifikasi Bisnis:** Memudahkan tim finance/admin memantau antrean faktur Accurate yang gagal atau mengirim ulang struk WhatsApp digital.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** TASK-001
- **Acceptance Criteria:**
  1. Tabel log sinkronisasi dokumen Accurate (`order_accurate_docs`) dengan tombol manual retry (`POST /api/v1/backoffice/accurate/sync-retry/:id`).
  2. Tabel riwayat pengiriman pesan WhatsApp dengan tombol resend.
- **Definition of Done:** Admin finance dapat memicu retry faktur Accurate yang berstatus pending/failed langsung dari antarmuka web.

---

### 🟢 [P3] Low Priority & Cleanup Tasks

#### TASK-008: Pembersihan File Audit Asing (StayKoe) di Root Repository
- **Repository:** `tokopon-zed-offline`
- **Modul:** Cleanup
- **Justifikasi Bisnis:** File `transaction_flow_audit_v2.md` berasal dari proyek sistem perhotelan ("StayKoe") yang tidak relevan dengan bisnis Tokopon, berpotensi membingungkan pengembang dan agen AI baru.
- **Status:** `READY_FOR_DEV`
- **Dependencies:** Tidak ada
- **Acceptance Criteria:**
  1. Arsipkan atau tandai file `transaction_flow_audit_v2.md` sebagai file luar cakupan (Out of Scope).
- **Definition of Done:** Tidak ada kebingungan konteks proyek perhotelan dalam ekosistem retail Tokopon.
