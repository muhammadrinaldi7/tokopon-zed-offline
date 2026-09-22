# Tokopon Zed &bull; Executive Intelligence Suite (Frontend Standalone)

Dashboard analitik C-Level & intelijen bisnis eksekutif independen untuk **PT Syihab Store / GSK Group (Tokopon Zed)**.

Aplikasi ini dibangun menggunakan arsitektur **Headless / Decoupled** (Vite + React 19 + TypeScript + Tailwind CSS v4 + Recharts + Lucide Icons). Aplikasi ini 100% mandiri dan hanya terhubung ke backend Laravel melalui REST API via Bearer Token.

---

## 🚀 Fitur Utama
1. **Executive Financial Cards**: Net Sales, Gross Profit, HPP, Margin %, AOV, Transaksi, Piutang Berjalan, dan Realisasi Kas.
2. **Kesehatan Arus Kas (Cash Flow Health Card)**: Rasio penagihan kas masuk (*Collection Rate %*) vs piutang berjalan dengan status peringatan risiko.
3. **Benchmarking Cabang Toko**: Leaderboard performa toko lengkap dengan medali juara (#1 Gold, #2 Silver, #3 Bronze), margin quality badges, dan kontribusi omset.
4. **Sales & Profit Trend Analysis**: Grafik area interaktif tren penjualan dan laba kotor.
5. **Top 10 Produk Terlaris**: Peringkat unit dan omset produk.
6. **Beban MDR & Metode Pembayaran**: Komposisi transaksi per channel pembayaran beserta potongan MDR.
7. **AI Executive Strategic Insight (9router)**: Analisis 1-klik dengan tombol **Salin untuk WhatsApp/Telegram** dan asisten chat interaktif dengan *context-injection* data realtime.
8. **Fitur Ekspor Lengkap**:
   - **Cetak / Ekspor PDF Resmi**: Layout ramah cetak A4 dengan kop resmi dan kolom tanda tangan Direksi.
   - **Unduh Excel / CSV**: File CSV ber-BOM UTF-8 siap buka di Microsoft Excel.
9. **Live Boardroom Display**: Auto-refresh 60 detik untuk layar monitor kantor direksi.

---

## 💻 Cara Menjalankan Secara Mandiri (Standalone)

### 1. Konfigurasi Lingkungan (.env)
Pastikan file `.env` di folder ini sudah mengarah ke backend Laravel Anda:
```env
# Mode Lokal:
VITE_API_URL=http://127.0.0.1:8000

# Mode Production (jika sudah di VPS/Hosting):
# VITE_API_URL=https://api.tokopon.com
```

### 2. Instalasi & Menjalankan Dev Server
```bash
npm install
npm run dev
```
Buka browser di: **`http://localhost:5174`**

### 3. Build untuk Production (Deploy ke Vercel / Cloudflare / VPS)
```bash
npm run build
```
Folder `dist/` siap di-upload ke static hosting mana pun (Vercel, Cloudflare Pages, Netlify, atau web server Nginx).

---

## 📂 Cara Memindahkan Folder ke Lokasi Baru
Jika Anda ingin memindahkan folder ini ke luar dari repository utama (misal ke `D:\APP\tokopon-executive`):
1. Salin atau pindahkan seluruh isi folder `executive-app` ke lokasi baru.
2. Di folder baru, buka terminal PowerShell / CMD:
   ```bash
   cd D:\APP\tokopon-executive
   npm install
   npm run dev
   ```
3. Selesai! Aplikasi langsung berfungsi mandiri.
