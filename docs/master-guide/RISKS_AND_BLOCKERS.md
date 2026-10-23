# ⚠️ Tokopon Ecosystem — Risks, Blockers & Mitigations

> **Matriks Manajemen Risiko Teknis & Operasional Ekosistem Tokopon**  
> Status: **ACTIVE MONITORING** | Baseline Audit: **22 Oktober 2026**

---

## 📌 1. Matriks Risiko Utama (Risk Assessment Matrix)

| ID Risiko | Deskripsi Risiko | Severity | Likelihood | Dampak Bisnis / Teknis | Strategi Mitigasi Terverifikasi | Status |
|---|---|:---:|:---:|---|---|:---:|
| **RSK-01** | **Fallback Mock Data di Frontend Menyembunyikan Kegagalan** | 🔴 HIGH | 🔴 HIGH | Kasir mengira transaksi masuk ke database padahal backend offline atau error. Potensi kehilangan data pesanan riil. | Hapus seluruh fallback mock dari `posApi.ts`. Tampilkan error modal eksplisit yang memblokir penyelesaian pesanan fiktif. | `ACTION REQUIRED` |
| **RSK-02** | **Kebocoran Data Lintas Tenant (Cross-Tenant Leaks)** | 🔴 HIGH | 🟡 LOW | Data penjualan atau stok Syihab Store terlihat oleh cabang GSK Second atau GSK Distri jika query tidak memfilter `business_unit_id`. | `TenantContextMiddleware` di Go menyaring tenant secara otomatis dan unit test menguji isolasi BU secara ketat. | `MITIGATED` |
| **RSK-03** | **Lock Contention & Deadlock pada Jam Sibuk Kasir** | 🟡 MED | 🟡 MED | Transaksi kasir timeout atau gagal saat beberapa kasir menjual produk non-serial fast-moving bersamaan. | Menggunakan urutan penguncian konsisten (`SELECT ... FOR UPDATE`), transaction timeout 5 detik, dan indeks gabungan. | `CONTROLLED` |
| **RSK-04** | **Kegagalan / Latensi API Accurate Online ERP** | 🟡 MED | 🔴 HIGH | Proses transaksi kasir toko terhenti jika sinkronisasi faktur Accurate dilakukan secara sinkronus. | Seluruh sinkronisasi Accurate dioperasikan asinkron via antrean latar belakang (`jobs`) dengan exponential backoff retry. | `MITIGATED` |
| **RSK-05** | **Pemblokiran Nomor WhatsApp Gateway atau Kuota Habis** | 🟡 MED | 🟡 MED | Pelanggan tidak menerima struk digital WhatsApp setelah transaksi selesai di kasir toko. | Penyediaan tombol manual kirim ulang (*resend*) di web backoffice dan fallback cetak struk fisik thermal kasir. | `MITIGATED` |
| **RSK-06** | **Ketiadaan Automated CI/CD Regression Pipeline** | 🔴 HIGH | 🔴 HIGH | Perubahan kode baru berisiko merusak test suite yang ada tanpa disadari oleh pengembang. | Setup GitHub Actions workflow untuk menjalankan `go test` dan `npm run build` otomatis pada setiap PR. | `OPEN` |

---

## 🛑 2. Blocker Aktif Saat Ini & Solusi Membukanya

### Blocker 1: Antarmuka Web Inventaris & Analitik Belum Tersedia
- **Dampak:** Operasional gudang dan laporan C-Level belum dapat dialihkan dari Livewire Laravel.
- **Langkah Membuka Blocker:** Eksekusi task **TASK-003** (Antarmuka Inventaris & Opname) dan **TASK-004** (Dashboard Analitik Eksekutif) di `tokopon-web`.

### Blocker 2: Purity Warnings React 19 di Frontend
- **Dampak:** Penggunaan fungsi tidak murni di `Input.tsx` dan `Step4Payment.tsx` dapat menyebabkan render ganda tak stabil.
- **Langkah Membuka Blocker:** Eksekusi task **TASK-002** untuk menstandarkan input ID dan handling tanggal sesuai standar React 19.
