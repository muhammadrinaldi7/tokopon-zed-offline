# ⚖️ Tokopon Ecosystem — Architectural Decision Records (ADR)

> **Catatan Keputusan Arsitektur dan Bisnis Ekosistem Tokopon**  
> Status: **ACCEPTED & ACTIVE** | Baseline: **22 Oktober 2026**

---

## 📌 Daftar Rekaman Keputusan (ADR)

### ADR-001: Autentikasi JWT Go Native vs Parsing Sanctum Database Token
- **Status:** `ACCEPTED`
- **Konteks:** Laravel monolit menggunakan Laravel Sanctum (`personal_access_tokens`) dengan hashing SHA-256 dan token stateful di database.
- **Keputusan:** Backend Go mengimplementasikan **JWT Standar (Access Token + Refresh Token)** secara mandiri (`pkg/auth/jwt.go`). User melakukan login ulang via endpoint Go `/api/v1/auth/login`.
- **Justifikasi:** Menghindari beban I/O query database pada setiap request API dan menghilangkan dependensi rapuh terhadap internal implementasi tabel Sanctum Laravel.

---

### ADR-002: Native Go WebSocket Hub vs Laravel Reverb / Pusher Cloud
- **Status:** `ACCEPTED`
- **Konteks:** Fitur live chat CS dan notifikasi kasir memerlukan koneksi duplex realtime. Laravel menggunakan Laravel Reverb / Pusher.
- **Keputusan:** Backend Go mengimplementasikan **WebSocket Hub native** menggunakan pustaka `gorilla/websocket` (`pkg/websocket/hub.go`).
- **Justifikasi:** Mengurangi biaya langganan cloud pihak ketiga, latensi nol milidetik, konsumsi memori rendah, dan menghilangkan dependensi runtime PHP pada background service Reverb.

---

### ADR-003: Migrasi Inkremental via Strangler Fig Pattern (Nginx Ingress)
- **Status:** `ACCEPTED`
- **Konteks:** Memindahkan seluruh fitur monolit Laravel sekaligus (*Big Bang Cutover*) memiliki risiko downtime dan kekacauan operasional toko fisik yang sangat tinggi.
- **Keputusan:** Menggunakan **Strangler Fig Pattern** dengan Nginx Reverse Proxy di depan. Endpoint API dan halaman web dialihkan secara bertahap satu per satu dari Laravel ke Go/Web.
- **Justifikasi:** Operasional kasir fisik toko tetap berjalan normal. Tim pengembang dapat memvalidasi setiap modul secara bertahap di lingkungan produksi nyata.

---

### ADR-004: Penghentian & Eliminasi Modul Erzap Legacy
- **Status:** `ACCEPTED`
- **Konteks:** Terdapat sisa implementasi integrasi API POS Erzap (`ErzapProductController.php`) di repositori Laravel.
- **Keputusan:** Modul Erzap **dieliminasi sepenuhnya** dan tidak dipindahkan ke backend Go maupun frontend web (`NOT_REQUIRED`).
- **Justifikasi:** Manajemen telah memutuskan untuk memusatkan seluruh sistem akuntansi dan inventaris ke Accurate Online ERP.

---

### ADR-005: Pluggable Queue Broker (Database Driver Aktif, Redis Ready)
- **Status:** `ACCEPTED`
- **Konteks:** Sistem membutuhkan antrean latar belakang (*background queue*) untuk sinkronisasi pesanan ke Accurate Online dan pengiriman struk digital WhatsApp tanpa memblokir kasir.
- **Keputusan:** Membuat abstraksi antarmuka `queue.Broker` (`pkg/queue/queue.go`). Driver aktif saat ini adalah **Database Queue** (tabel `jobs` MySQL dengan `FOR UPDATE SKIP LOCKED`), namun driver **Redis** (`asynq`) sudah disiapkan dan dapat diaktifkan cukup dengan mengubah variabel lingkungan `QUEUE_DRIVER=redis` di file `.env`.
- **Justifikasi:** Meminimalkan kebutuhan infrastruktur awal di toko (tidak wajib menyalakan Redis daemon), namun tetap menjamin skalabilitas horizontal di masa depan.

---

### ADR-006: Standar Amplop JSON Terpadu (Unified API Envelope)
- **Status:** `ACCEPTED`
- **Konteks:** Monolit Laravel memiliki format respon JSON yang bervariasi antara endpoint API mobile dan response controller web.
- **Keputusan:** Menstandarkan seluruh respon Go ke dalam format seragam (`pkg/response/response.go`): `success`, `code`, `message`, `data`, `meta` (paginasi), dan `errors` (validasi rinci).
- **Justifikasi:** Mempermudah penanganan state di frontend web (`tokopon-web`) dan aplikasi mobile Android/iOS dengan kontrak error yang deterministik.

---

### ADR-007: Arsitektur Modular Monolith vs Microservices
- **Status:** `ACCEPTED`
- **Konteks:** Pemilihan arsitektur untuk backend pengganti Go.
- **Keputusan:** Memilih **Modular Monolith** dengan 8 modul domain terisolasi (`identity`, `pos`, `inventory`, `catalog`, `loyalty`, `tradein`, `integration`, `analytics`) dalam satu repositori `tokopon-go`.
- **Justifikasi:** Menghindari kompleksitas operasional microservices (distributed tracing, network latency, multi-repo CI/CD) sambil tetap menjaga batas domain yang bersih dan modular.

---

### ADR-008: Shared Database MySQL (`zedpost-7`) Selama Masa Transisi
- **Status:** `ACCEPTED`
- **Konteks:** Apakah Go harus membuat database terpisah atau berbagi database dengan Laravel monolit.
- **Keputusan:** Go dan Laravel **berbagi database MySQL `zedpost-7` yang sama** selama masa transisi.
- **Justifikasi:** Memungkinkan data kasir dan stok sinkron secara real-time tanpa perlu membangun data pipeline CDC (*Change Data Capture*) atau replikasi yang kompleks dan rawan selisih.

---

### ADR-009: Frontend SPA Modern (Vite + React 19) Menggantikan Livewire
- **Status:** `ACCEPTED`
- **Konteks:** Antarmuka toko fisik eksisting dibuat menggunakan server-rendered Livewire 3 yang membebani server PHP dan memiliki responsivitas lambat saat offline/jaringan lambat.
- **Keputusan:** Membangun `tokopon-web` sebagai Single Page Application (SPA) client-side berbasis Vite, React 19, TypeScript, Tailwind CSS v4, dan Zustand.
- **Justifikasi:** Memberikan pengalaman antarmuka kasir yang super cepat, interaksi offline-ready, integrasi hardware printer kasir tanpa reload halaman, dan UI modern standar industri ritel modern.
