# 🤖 Tokopon Ecosystem — AI Coding Agent Context & Rules

> **BACA DOKUMEN INI TERLEBIH DAHULU SEBELUM MELAKUKAN PERUBAHAN APAPUN**  
> Baseline Audit: **22 Oktober 2026** | Versi Konteks: **1.0.0**

---

## 📌 1. Identitas & Peran Ekosistem

Kamu bekerja di dalam **Ekosistem Tokopon**, sistem ritel omnichannel untuk PT Syihab Store & GSK Group yang mengelola penjualan smartphone baru, aksesoris, unit second-hand, tukar tambah (trade-in), QC kelayakan, dan sinkronisasi Accurate Online ERP.

Ekosistem terdiri dari 3 repository di root yang sama:
1. `.` (`tokopon-zed-offline`): Monolit Laravel legacy (sumber kebenaran aturan bisnis eksisting & 238 migrasi MySQL).
2. `./tokopon-go`: Backend Go modern (Modular Monolith: 8 modul inti, Echo, sqlx, zero-latency WebSocket, background worker).
3. `./tokopon-web`: Frontend SPA modern (Vite, React 19, TypeScript, Tailwind v4, Zustand, TanStack Query).

---

## 🚨 2. Aturan Emas yang DILARANG KERAS Dilanggar (Golden Rules)

1. **Evidence-Based Only:** Dilarang mengarang fitur, test result, atau persentase progres. Status `VERIFIED` atau `COMPLETED` hanya boleh diberikan jika terbukti melalui kompilasi kode dan pengujian nyata.
2. **Strict Multi-Tenancy Invariant:** Database MySQL `zedpost-7` menggunakan **Column-Based Multi-Tenancy** dengan kolom `business_unit_id`.
   - `1`: Syihab Store (Smartphone Baru & Aksesoris)
   - `2`: GSK Second (HP Bekas, Buyback, Trade-In, QC)
   - `3`: GSK Distri (Grosir B2B)
   - **Setiap query mutasi data WAJIB memfilter `business_unit_id`!** Dilarang menjalankan query global tanpa filter tenant.
3. **Pessimistic Concurrency Invariant:** Kueri pengurangan stok di `warehouse_stocks` dan reservasi IMEI di `product_serial_numbers` **WAJIB** menggunakan `SELECT ... FOR UPDATE` dalam transaksi SQL (`sqlx.Tx`). Dilarang melakukan update stok tanpa row lock!
4. **Haram Fallback Mock Diam-Diam:** Dilarang membuat fallback data tiruan (*mock*) saat request API gagal di `tokopon-web`. Jika jaringan bermasalah, tampilkan state error yang jelas kepada pengguna.
5. **Kepatuhan React 19 Purity:** Jangan memanggil `Math.random()` atau `Date.now()` di dalam fungsi render komponen. Jangan memanggil `setState` sinkron di dalam `useEffect`.
6. **Zero External Test Libraries di Go:** Buat unit test Go menggunakan pustaka bawaan `testing` dengan struct mock in-memory. Dilarang menambah dependensi testing pihak ketiga tanpa persetujuan.
7. **Pembaruan Berkelanjutan Master Guide:** Setiap kali menyelesaikan sebuah task, kamu **WAJIB** memperbarui file terkait di `docs/master-guide/` (misal: `FEATURE_MATRIX.md`, `BACKLOG.md`, `CHANGELOG.md`).

---

## 🗺️ 3. Peta Navigasi Cepat untuk Agen AI

Jika kamu ditugaskan untuk:
- Memahami alur bisnis lengkap $\rightarrow$ Baca [`BUSINESS_LOGIC.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/BUSINESS_LOGIC.md)
- Melihat status fitur dan gap $\rightarrow$ Baca [`FEATURE_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/FEATURE_MATRIX.md)
- Mengetahui endpoint API & format JSON $\rightarrow$ Baca [`API_CONTRACTS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/API_CONTRACTS.md)
- Mengetahui struktur database & kolom $\rightarrow$ Baca [`DATABASE_MAP.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DATABASE_MAP.md)
- Mengambil task berikutnya yang siap dikerjakan $\rightarrow$ Buka [`BACKLOG.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/BACKLOG.md)
- Menyerahkan pekerjaan di akhir sesi $\rightarrow$ Gunakan format [`templates/SESSION_HANDOFF_TEMPLATE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/SESSION_HANDOFF_TEMPLATE.md)

---

## 🎯 4. Task Aktif yang Sedang Terbuka
Lihat daftar prioritas saat ini di [`BACKLOG.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/BACKLOG.md):
- **TASK-001 (P0):** Bersihkan Fallback Mocks & Terapkan Real Network Error Handling di POS Web
- **TASK-002 (P0):** Perbaiki 5 Purity & Effect Warnings di `tokopon-web`
- **TASK-003 (P1):** Bangun Antarmuka Frontend [MOD-03] Store Inventory & Stock Opname
- **TASK-004 (P1):** Bangun Antarmuka Frontend [MOD-08] Executive Analytics Dashboard
