# Cara Kerja Integrasi Webhook Accurate saat ini

## 1. Menerima Request dari Accurate (Controller)
Webhook dimulai dari rute `POST /api/webhooks/accurate` yang ditangani oleh **`AccurateWebhookController`**. 
- **Keamanan (Validasi Token):** Controller akan mencocokkan parameter `?token=` di URL dengan nilai `ACCURATE_WEBHOOK_TOKEN` yang ada di `.env`. Jika cocok, proses dilanjut. Jika tidak, akan ditolak (Log: *Accurate Webhook Ditolak: Token tidak cocok*).
- **Menerima Data (Payload):** Controller menangkap payload berupa JSON array dari Accurate (karena Accurate bisa mengirim beberapa event sekaligus).

## 2. Penyimpanan ke Database Log
Setiap event yang ditangkap tidak langsung diproses saat itu juga, melainkan:
- Disimpan terlebih dahulu ke tabel `accurate_webhook_logs` dengan status **`pending`**.
- Hal ini sangat penting agar tidak ada data yang hilang jika sewaktu-waktu terjadi error pada sistem saat memproses, dan menjaga agar server merespons 200 OK ke Accurate dengan cepat.

## 3. Masuk ke Antrean Latar Belakang (Queue/Job)
Setelah data log tersimpan, Controller melempar tugas tersebut ke **`ProcessAccurateWebhookJob`**. Proses selanjutnya berjalan di latar belakang (*background*) sehingga tidak membebani request web.

## 4. Pemrosesan Data (Job & Handler)
Di dalam **`ProcessAccurateWebhookJob`**, status log diubah menjadi **`processing`**. Sistem akan membaca jenis event (`event_type`) dan mengarahkannya ke kelas **Handler** yang sesuai:
- Jika tipe **`ITEM`** → Diarahkan ke `ItemSaveHandler` (Misalnya untuk update nama/harga produk).
- Jika tipe **`INVENTORY_ADJUSTMENT`, `INVENTORY_TRANSFER`, `PURCHASE_INVOICE`, `RECEIVE_ITEM`** → Diarahkan ke `StockChangeHandler` (Digunakan untuk update stok gudang secara live).
- Jika tipe **`SALES_INVOICE`, `SALES_RECEIPT`** → Diarahkan ke `SalesInvoiceHandler`.

Setelah berhasil diproses oleh Handler, status di tabel log akan berubah menjadi **`success`**. Jika ada error, akan tercatat sebagai **`failed`** lengkap dengan pesan errornya.

---

### Apakah saat ini sudah bekerja?

**YAA, SUDAH BEKERJA SANGAT BAIK! 🎉**

Dari *history* log yang Anda tunjukkan sebelumnya:
```
[2026-05-29 13:57:16] local.INFO: Accurate Webhook queued: [Event: ITEM] [Log ID: 3]
```
Ini membuktikan 3 hal:
1. Accurate sudah **berhasil mengirim POST request** ke server Anda.
2. Token otentikasi di URL **sudah sesuai** dengan yang ada di sistem.
3. Payload berhasil **disimpan di Database (Log ID: 3)** dan antrean untuk Job telah berhasil dibuat (*queued*).

**Satu hal lagi yang perlu dipastikan jalan:**
Karena ini menggunakan Job/Queue, pastikan Anda selalu menyalakan perintah *worker* latar belakang di terminal server Anda (misalnya menggunakan perintah `php artisan queue:work` atau dikelola melalui *Supervisor* di production) agar data yang berstatus `pending` bisa segera dieksekusi menjadi `success`.
