# Aturan Pengembangan AI (Coding Guidelines) untuk Proyek Tokopon-Zed

File ini berisi rangkuman arsitektur database, relasi tenant, serta instruksi ketat yang **WAJIB** dipatuhi oleh AI (maupun developer) saat men-generate kode, khususnya *query* Eloquent, guna mencegah kebocoran data lintas *tenant* (Cross-Tenant Data Leak).

---

## 1. Arsitektur Multi-Tenancy & Database
Sistem ini menggunakan arsitektur **Column-Based Multi-Tenancy** (Single Database, Shared Schema) dengan kolom `business_unit_id` sebagai *Tenant Key*.
Selain itu, sistem ini terhubung ke beberapa *database* **Accurate Accounting** secara dinamis (Multi-Database Accurate) berdasarkan unit bisnis (*Business Unit*).

### A. Tabel Tenant-Aware (Memiliki `business_unit_id`)
Berikut adalah entitas utama yang terikat dengan unit bisnis:
- `users`
- `orders`
- `employes`
- `products` (dan tabel turunannya yang merujuk ke master `product_accurates`)
- `product_accurates`
- `payment_methods`
- `branches`
- `warehouses`
- `categories`
- `brands`
- Pivot Integrasi: `user_accurate_customers`, `user_accurate_vendors`

### B. Relasi dengan Accurate Accounting
1. **Kredensial Dinamis**: Kredensial API Accurate (`accurate_host`, `accurate_token`, `accurate_secret_key`) disimpan di tabel `business_units`. Integrasi Accurate harus memanggil API menggunakan kredensial dari *business unit* yang sedang aktif.
2. **Pemetaan Pivot**: Sistem menggunakan tabel pivot untuk memetakan pelanggan dan *vendor* secara lokal ke *database* Accurate yang berbeda. 
   - Contoh: Seorang `User` dapat memiliki ID Pelanggan Accurate yang berbeda di unit bisnis "Syihab" dan unit bisnis "Second".

---

## 2. 🚨 INSTRUKSI KETAT: Mencegah Kebocoran Data (Data Leaks) 🚨

Berdasarkan analisis *source code*, sistem ini **TIDAK MENGGUNAKAN GLOBAL SCOPE** (`addGlobalScope`) untuk memfilter `business_unit_id` secara otomatis.
Oleh karena itu, penyaringan tenant **WAJIB DILAKUKAN SECARA MANUAL** pada setiap *query* Eloquent.

**JIKA ANDA (AI) MENULIS QUERY ELOQUENT UNTUK TABEL TENANT-AWARE, ANDA WAJIB MEMATUHI ATURAN BERIKUT:**

### 1. Dapatkan Business Unit ID yang Aktif
Gunakan fungsi bawaan pada *model* `User` untuk mendapatkan konteks tenant pengguna yang sedang login:
```php
$buId = \Illuminate\Support\Facades\Auth::user()->getActiveBusinessUnitId();
```
*(Catatan: `getActiveBusinessUnitId()` menangani fallback ke session jika user adalah Admin).*

### 2. Selalu Filter berdasarkan `business_unit_id`
Untuk *query* standar (User, Order, Employe, dll), filter secara eksplisit:
```php
// CONTOH BENAR
$orders = Order::where('business_unit_id', $buId)->get();

// CONTOH SALAH (Berpotensi membocorkan data tenant lain!)
$orders = Order::all();
```

### 3. Aturan Khusus Master Data (Shared Global)
Beberapa master data (seperti Produk, Kategori, atau Cabang) dirancang agar bisa berlaku khusus untuk satu unit bisnis, ATAU berlaku secara global jika `business_unit_id` bernilai `null`. 
Gunakan pola *query* `orWhereNull` berikut untuk master data:
```php
$products = Product::where(function ($q) use ($buId) {
    $q->where('business_unit_id', $buId)
      ->orWhereNull('business_unit_id');
})->get();
```

### 4. Jangan Asumsi Relasi Terfilter Otomatis
Ketika memanggil relasi, ingat bahwa relasi tidak memfilter tenant secara otomatis. Lakukan pembatasan saat me-*load* relasi (*Eager Loading Constraint*) jika diperlukan:
```php
$users = User::with(['orders' => function ($q) use ($buId) {
    $q->where('business_unit_id', $buId);
}])->where('business_unit_id', $buId)->get();
```

### 5. Hindari `Unique` Secara Global Saat Validasi
Saat membuat validasi unik (seperti pengecekan *email* atau *sku*), pastikan pengecekan *unique* dibatasi oleh `business_unit_id` menggunakan `Rule::unique()->where()` jika entitas tersebut dapat diduplikasi antar tenant.

### 6. Sinkronisasi Accurate
Pastikan Anda meneruskan objek `BusinessUnit` yang benar, atau *business unit code* (seperti `'syihab'` atau `'second'`) ke kelas layanan seperti `AccurateService` agar layanan tahu ke *database* Accurate mana permintaan harus dikirimkan. 

---
*Catatan AI: Selalu rujuk kembali ke dokumen ini sebelum membuat, mengubah, atau menganalisa controller, livewire component, dan query database di proyek ini.*
