# 💻 Tokopon Ecosystem — Development Guide & Engineering Standards

> **Panduan Praktis Pengembangan, Standar Penulisan Kode, dan Workflow Harian**  
> Baseline Audit: **22 Oktober 2026** | Target Pengembang: **Software Engineers & AI Agents**

---

## 📌 1. Cara Menjalankan Ekosistem Secara Lokal

### A. Menjalankan Backend Go (`tokopon-go`)
1. Masuk ke direktori `tokopon-go`:
   ```bash
   cd tokopon-go
   ```
2. Pastikan file `.env` sudah dikonfigurasi (koneksi MySQL `zedpost-7`).
3. Jalankan HTTP API Server:
   ```bash
   go run ./cmd/server
   ```
   *Server berjalan di port `8080` (`http://localhost:8080`).*
4. Jalankan Background Queue Worker (di terminal terpisah):
   ```bash
   go run ./cmd/worker
   ```
5. Menjalankan Unit Test (Wajib Uncached):
   ```bash
   go test -count=1 ./...
   ```

### B. Menjalankan Frontend Web (`tokopon-web`)
1. Masuk ke direktori `tokopon-web`:
   ```bash
   cd tokopon-web
   ```
2. Jalankan development server:
   ```bash
   cmd.exe /c "npm run dev"
   ```
   *Frontend berjalan di `http://localhost:5173`. Request `/api/*` otomatis di-proxy ke `http://localhost:8080`.*
3. Lakukan pengecekan Linter & Build sebelum commit:
   ```bash
   cmd.exe /c "npm run lint"
   cmd.exe /c "npm run build"
   ```

### C. Menjalankan Monolit Laravel Legacy (`tokopon-zed-offline`)
1. Di root direktori `tokopon-zed-offline`:
   ```bash
   php artisan serve --port=8000
   ```
2. Menjalankan queue worker Laravel:
   ```bash
   php artisan queue:work
   ```

---

## 🏗️ 2. Standar Rekayasa Backend Go

1. **Prinsip Clean Architecture Modular Monolith:**
   - Setiap modul (`internal/modules/<nama_modul>`) harus memiliki:
     - `domain.go`: Model entitas murni, typed errors, DTO request/response, dan definisi antarmuka (interfaces).
     - `repository.go`: Implementasi akses database SQL (`sqlx.DB` & `sqlx.Tx`).
     - `usecase.go`: Orkestrasi alur bisnis, validasi, dan penegakan batas transaksi.
     - `handler.go`: HTTP delivery handler menggunakan Echo (`echo.Context`), binding payload, dan formatting respon (`pkg/response`).
     - `*_test.go`: Go native unit test (bebas dependensi eksternal, gunakan struct mock in-memory).
2. **Kepatuhan Penanganan Error:**
   - Gunakan typed errors dari `domain.go` (contoh: `ErrInsufficientStock`, `ErrNoActiveShift`).
   - Handler memetakan typed error ke HTTP Status Code yang sesuai (400, 401, 403, 404, 409).
3. **Pessimistic Concurrency Invariant:**
   - Kueri pengurangan saldo atau kuantitas barang fisik **WAJIB** berada di dalam transaksi (`sqlx.Tx`) dengan klausa `SELECT ... FOR UPDATE`.
4. **Zero External Test Libraries:**
   - Pengujian unit test Go menggunakan pustaka bawaan `testing`. Hindari penggunaan framework testing pihak ketiga (Testify/Ginkgo) agar test suite tetap ringan dan cepat.

---

## 🎨 3. Standar Rekayasa Frontend Web

1. **Stack & Arsitektur:**
   - React 19 + TypeScript + Tailwind v4 + Zustand + TanStack Query.
   - Gunakan path alias `@/*` yang merujuk ke `./src/*`.
2. **Aturan React 19 Purity:**
   - Dilarang memanggil fungsi tidak murni (`Math.random()`, `Date.now()`, `crypto.randomUUID()`) langsung di dalam tubuh fungsi render komponen. Gunakan hook `useId()` atau inisialisasi di event handler.
   - Hindari pemanggilan `setState` sinkron di dalam `useEffect`.
3. **Standar Penanganan API:**
   - Gunakan wrapper `http` dari `@/api/client` yang otomatis menangani JWT Authorization header dan auto-refresh token.
   - Daftarkan seluruh URL endpoint di `@/api/endpoints.ts`.
4. **Aturan Bebas Mock di Lingkungan Produksi:**
   - Dilarang membungkus kegagalan request API dengan fallback data mock fiktif. Kegagalan jaringan harus memunculkan state error yang jelas kepada pengguna.

---

## 📝 4. Prosedur Memperbarui Master Guide

Setiap kali melakukan perubahan kode yang memengaruhi:
1. **Fitur Baru:** Tambahkan ke [`FEATURE_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/FEATURE_MATRIX.md) dan [`MIGRATION_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/MIGRATION_MATRIX.md).
2. **Endpoint API Baru:** Daftarkan di [`API_CONTRACTS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/API_CONTRACTS.md) beserta contoh payload request/response.
3. **Perubahan Tabel / Indeks:** Dokumentasikan di [`DATABASE_MAP.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DATABASE_MAP.md).
4. **Keputusan Arsitektur Baru:** Catat di [`DECISIONS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DECISIONS.md) menggunakan format ADR standar.
5. **Catat Perubahan:** Tambahkan catatan rilis di [`CHANGELOG.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/CHANGELOG.md).
