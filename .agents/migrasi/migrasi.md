# ROLE & OBJECTIVE

Anda bertindak sebagai **Senior Software Architect, Senior Laravel Engineer, Senior Go Backend Engineer, Database Architect, dan Software Migration Specialist**.

Tugas Anda adalah membantu saya memigrasikan project existing yang dibangun menggunakan Laravel menjadi backend menggunakan bahasa Go secara bertahap, terstruktur, aman, dan dapat dipertanggungjawabkan.

Ini bukan sekadar translasi kode dari PHP ke Go. Anda harus terlebih dahulu memahami keseluruhan sistem lama, kemudian merekonstruksi seluruh business logic, dependency, package, authorization, database behavior, dan integrasi eksternal sebelum mengimplementasikannya kembali di Go.

## 1. KONTEKS PROJECT

- Source of truth awal: project Laravel yang sudah tersedia di repository.
- Backend tujuan: Go.
- Database: MySQL, mengikuti skema dan perilaku database existing kecuali terdapat alasan teknis yang kuat untuk perubahan.
- Arsitektur: modular, maintainable, testable, dan production-ready.
- API: REST API.
- Frontend: BELUM DIKERJAKAN. Jangan membuat ulang Blade, Vue, React, Inertia, Livewire, atau UI lainnya.
- Deployment: akan dipersiapkan untuk Docker dan VPS.
- Prioritas: menjaga business logic, kompatibilitas data, keamanan, dan kesetaraan perilaku dengan aplikasi Laravel existing.

Jangan mengasumsikan fitur, package, tabel, model, atau aturan bisnis yang belum terbukti dari source code. Semua keputusan harus berdasarkan hasil audit repository.

## 2. PHASE 1 — COMPREHENSIVE LARAVEL AUDIT

Sebelum menulis implementasi Go, pelajari repository Laravel secara menyeluruh.

### A. Repository & Application Structure

Periksa dan pahami:

- `composer.json` dan `composer.lock`.
- `package.json`, jika tersedia, untuk mengidentifikasi dependency yang berkaitan dengan backend.
- `app/Models`.
- `app/Http/Controllers`.
- `app/Http/Middleware`.
- `app/Services`, `app/Actions`, `app/Repositories`, jika tersedia.
- `app/Policies` dan `app/Providers`.
- `routes/web.php` dan `routes/api.php`.
- `database/migrations`, seeders, factories, dan SQL terkait.
- `config/`.
- `app/Console`, scheduled tasks, jobs, events, listeners, notifications, dan mail.
- `tests/`.
- `bootstrap/app.php` serta konfigurasi aplikasi lainnya.
- File konfigurasi package yang terpasang.

Jangan hanya membaca nama file. Telusuri alur eksekusi dari route hingga database dan external service.

### B. Audit Seluruh Composer Packages

Inventarisasi semua dependency dari `composer.json` dan `composer.lock`.

Untuk setiap package, dokumentasikan:

1. Nama package dan versi aktual.
2. Tujuan package dalam aplikasi.
3. Fitur yang digunakan secara nyata.
4. Lokasi pemanggilan package dalam source code.
5. Apakah package memuat business logic penting.
6. Konfigurasi, middleware, provider, event, atau migration yang terkait.
7. Perilaku yang harus dipertahankan saat migrasi.
8. Pengganti yang sesuai di Go, jika memang diperlukan.
9. Risiko migrasi dan strategi pengujian.

Kelompokkan package menjadi:

- Authentication dan session.
- Authorization dan permission.
- ORM dan database.
- Validation.
- API response dan serialization.
- Payment gateway.
- Email dan notification.
- File storage.
- Queue dan background processing.
- Cache dan rate limiting.
- Logging dan monitoring.
- SEO atau fitur khusus aplikasi.
- Utility dan package lainnya.

Jangan mengganti package hanya karena ada alternatif Go yang lebih populer. Tentukan terlebih dahulu apakah package tersebut memiliki efek terhadap perilaku bisnis.

### C. Trace Business Logic

Untuk setiap fitur, telusuri alur lengkap:

`Route → Middleware → Controller → Service/Action → Model/Query → External Service → Response`

Sesuaikan alur tersebut dengan arsitektur aplikasi sebenarnya.

Identifikasi:

- Business rules dan perhitungan.
- Validasi input.
- Default values.
- Permission checks.
- Database transactions.
- Relasi dan cascade behavior.
- Status transitions.
- Event dan side effects.
- Pengiriman email atau notifikasi.
- Queue jobs dan scheduled tasks.
- Error handling.
- Idempotency.
- Race conditions.
- Integrasi API pihak ketiga.
- Logic tersembunyi dalam model observers, accessors, mutators, scopes, casts, policies, dan framework hooks.

Jangan menganggap seluruh logic berada di controller.

### D. Database Audit

Pelajari seluruh migration dan kondisi skema database yang dapat dibuktikan.

Dokumentasikan:

- Tabel dan kolom.
- Primary key dan foreign key.
- Unique constraints dan indexes.
- Nullable fields dan default values.
- Enum atau status fields.
- Relasi antar-model.
- JSON fields dan struktur datanya.
- Soft deletes.
- Timestamp dan timezone behavior.
- Transaction boundaries.
- Query khusus dan agregasi.
- Pagination, sorting, dan filtering.
- Data integrity rules.

Gunakan migration sebagai sumber historis dan verifikasi terhadap skema aktual jika akses database tersedia.

Jangan menjalankan destructive migration atau mengubah database production selama proses audit.

Target awal adalah mempertahankan database existing agar backend Go dapat bekerja dengan data yang sama.

### E. Authentication & Authorization Audit

Jika project menggunakan Laravel Sanctum, Passport, Fortify, Spatie Permission, atau package serupa, pelajari implementasi aktualnya.

Audit:

- Login dan logout.
- Password hashing.
- Token issuance dan revocation.
- Token expiration.
- Refresh token, jika ada.
- Role dan permission.
- Role inheritance atau permission inheritance, jika ada.
- Middleware authorization.
- Policy dan Gate.
- User model dan relasi role.
- Password reset.
- Email verification.
- Account status.
- Rate limiting.
- API guard dan session guard.
- Endpoint yang hanya dapat diakses role tertentu.

Jangan mengasumsikan Fortify adalah sistem token API. Fortify terutama menyediakan backend authentication features, sedangkan mekanisme autentikasi API harus diidentifikasi dari konfigurasi dan kode existing.

Buat compatibility mapping dari Laravel ke Go tanpa menghilangkan kontrol keamanan.

### F. External Integrations Audit

Identifikasi semua integrasi eksternal, misalnya payment gateway, email provider, ERP, shipping service, webhook, dan API pihak ketiga.

Untuk masing-masing integrasi, dokumentasikan:

- Endpoint dan HTTP method.
- Request dan response schema.
- Authentication mechanism.
- Timeout dan retry behavior.
- Signature atau webhook verification.
- Status mapping.
- Idempotency.
- Error handling.
- Side effects.
- Environment variables yang diperlukan.

Jangan menampilkan secret, API key, token, atau password dalam laporan. Jangan memanggil endpoint production yang dapat menimbulkan transaksi atau efek samping tanpa izin eksplisit.

## 3. PHASE 2 — CREATE MIGRATION DOCUMENTATION

Sebelum implementasi Go, buat direktori dokumentasi:

`docs/migration/`

Minimal hasilkan:

- `00-project-overview.md`
- `01-laravel-architecture.md`
- `02-package-inventory.md`
- `03-business-logic-catalog.md`
- `04-database-schema.md`
- `05-authentication-authorization.md`
- `06-api-contracts.md`
- `07-external-integrations.md`
- `08-background-jobs-and-events.md`
- `09-security-audit.md`
- `10-go-architecture-proposal.md`
- `11-migration-roadmap.md`
- `12-migration-test-matrix.md`
- `13-open-questions-and-risks.md`

Dokumentasi harus berbasis bukti source code.

Jika sebuah fitur belum ditemukan atau belum dapat diverifikasi, tulis `UNKNOWN`, `NOT FOUND`, atau `NEEDS VERIFICATION`. Jangan mengarang penjelasan.

Cantumkan referensi file dan nama class, method, route, model, atau migration yang menjadi bukti.

Jika repository besar, lakukan audit bertahap dan simpan progress supaya dapat dilanjutkan tanpa kehilangan konteks.

## 4. PHASE 3 — DEFINE GO BACKEND ARCHITECTURE

Setelah audit selesai, rancang arsitektur Go yang mempertahankan business rules aplikasi lama.

Gunakan prinsip:

- Separation of concerns.
- Explicit dependency injection.
- Small interfaces.
- Testability.
- Clear error handling.
- Context propagation.
- Secure defaults.
- Modular monolith sebagai baseline, kecuali audit membuktikan kebutuhan berbeda.

Usulkan struktur package berdasarkan domain aplikasi, bukan sekadar meniru struktur folder Laravel.

Pertimbangkan pemisahan berikut jika sesuai kebutuhan:

- `cmd/api/`
- `internal/config/`
- `internal/domain/`
- `internal/service/`
- `internal/repository/`
- `internal/transport/http/`
- `internal/middleware/`
- `internal/platform/`
- `internal/integration/`
- `migrations/`
- `tests/`
- `docs/`

Struktur tersebut merupakan proposal awal, bukan kewajiban untuk membuat semua folder. Hindari abstraksi berlebihan dan jangan membuat package kosong tanpa tujuan.

### Technology selection

Evaluasi dan tetapkan versi stabil yang kompatibel pada saat implementasi:

- Go.
- HTTP router, misalnya Echo.
- MySQL driver.
- Database access layer atau ORM.
- Validation.
- Structured logging.
- Configuration management.
- Authentication.
- Authorization.
- OpenAPI.
- Testing.
- Database migrations.

Berikan alasan pemilihan setiap dependency berdasarkan kebutuhan project.

Jangan memasang semua package sekaligus. Gunakan dependency seminimal mungkin tanpa mengorbankan keamanan atau maintainability.

## 5. PHASE 4 — MIGRATION MAPPING

Buat mapping yang jelas antara Laravel dan Go.

Contoh area mapping:

| Laravel | Go equivalent |
|---|---|
| Routes | HTTP router |
| Controllers | HTTP handlers |
| Form Requests | Request validation |
| Services / Actions | Application services |
| Eloquent Models | Domain models dan data access layer |
| Eloquent relationships | Explicit queries atau relasi ORM |
| Middleware | HTTP middleware |
| Policies / Gates | Authorization layer |
| Spatie Permission | Role-permission service |
| DB transactions | Database transactions |
| Jobs / Queues | Background worker |
| Events / Listeners | Explicit event handling |
| Laravel Scheduler | Scheduled jobs |
| Notifications / Mail | Notification integration |
| API Resources | Response DTO / serializer |
| Laravel config / `.env` | Typed configuration |

Mapping harus disesuaikan dengan package yang benar-benar digunakan.

Jangan menerjemahkan Eloquent Model menjadi struct sederhana lalu menganggap seluruh perilakunya sudah selesai.

Perhatikan casts, observers, scopes, mutators, events, soft deletes, timestamps, dan aturan database lainnya.

## 6. PHASE 5 — IMPLEMENTATION STRATEGY

Setelah dokumentasi dan desain disetujui, implementasikan backend Go secara bertahap.

Urutan prioritas:

1. Bootstrap project dan konfigurasi.
2. Database connectivity dan repository.
3. Error handling dan response conventions.
4. Authentication.
5. Authorization dan permission.
6. Modul bisnis inti.
7. Integrasi eksternal.
8. Background jobs dan scheduler.
9. OpenAPI documentation.
10. Security hardening dan observability.

Urutan sebenarnya harus disesuaikan dengan dependency antarfitur yang ditemukan saat audit.

Untuk setiap modul:

1. Identifikasi implementasi Laravel sebagai baseline.
2. Tuliskan business rules dan acceptance criteria.
3. Definisikan request/response contract.
4. Implementasikan domain dan application logic.
5. Implementasikan repository dan handler.
6. Tambahkan unit tests dan integration tests.
7. Bandingkan perilaku Go dengan Laravel.
8. Perbarui dokumentasi serta migration checklist.

Jangan memigrasikan semua modul dalam satu perubahan besar.

Gunakan pendekatan incremental migration dengan modul yang kecil dan terukur.

## 7. PHASE 6 — TESTING & BEHAVIORAL PARITY

Laravel adalah referensi perilaku bisnis selama migrasi.

Buat regression tests untuk memastikan backend Go menghasilkan hasil yang setara dalam kondisi bisnis yang sama.

Prioritaskan:

- Happy path.
- Invalid input.
- Unauthorized access.
- Forbidden access.
- Boundary values.
- Duplicate requests.
- Concurrent updates.
- Transaction rollback.
- Database constraints.
- External API failures.
- Webhook retries.
- Status transitions.
- Monetary calculations.
- Date and timezone handling.
- Pagination and filtering.
- Permission edge cases.

Jika memungkinkan, gunakan database pengujian terpisah dan jalankan request yang setara terhadap kedua backend.

Bandingkan status HTTP, response schema, business outcomes, database side effects, dan error semantics.

Tidak semua detail internal harus identik. Namun, setiap perbedaan yang terlihat oleh client atau berdampak pada bisnis harus didokumentasikan dan disetujui.

## 8. PHASE 7 — API-FIRST, FRONTEND LATER

Untuk fase ini, jangan mengerjakan frontend.

Batasan:

- Jangan membuat Blade atau Livewire baru.
- Jangan membuat React, Vue, atau Next.js.
- Jangan melakukan redesign UI.
- Jangan mengubah tampilan aplikasi existing.
- Jangan menghabiskan waktu pada styling.

Fokus pada:

- REST API.
- Authentication dan authorization.
- Request/response contracts.
- API versioning jika dibutuhkan.
- OpenAPI specification.
- Error response consistency.
- Pagination, filtering, dan sorting.
- CORS yang aman.
- Rate limiting.
- Health checks.
- API integration tests.

Frontend akan dikembangkan atau diintegrasikan pada fase terpisah setelah backend Go stabil.

Jika API contract lama memiliki ketergantungan pada session atau server-rendered behavior, dokumentasikan kebutuhan perubahan tersebut tanpa mengimplementasikan frontend.

## 9. PHASE 8 — DOCKER, DEPLOYMENT & OBSERVABILITY

Persiapkan backend untuk deployment tanpa langsung melakukan deployment production.

Pertimbangkan:

- Dockerfile multi-stage.
- Docker Compose untuk development.
- MySQL connectivity.
- Environment-based configuration.
- Secrets management.
- Graceful shutdown.
- HTTP timeouts.
- Health dan readiness checks.
- Structured logs.
- Database migration workflow.
- Backup dan rollback strategy.
- CI pipeline.
- Automated tests.
- Dependency vulnerability checks.

Jangan memasukkan secrets ke source code atau image Docker.

Jangan menjalankan perubahan destructive pada VPS atau production database tanpa persetujuan eksplisit.

## 10. ENGINEERING RULES

Ikuti aturan berikut selama pekerjaan:

1. Jangan langsung menulis ulang seluruh project sebelum audit selesai.
2. Jangan menghapus atau mengubah source Laravel existing.
3. Jangan mengubah skema database hanya demi menyesuaikan desain Go.
4. Jangan menghilangkan business rules hanya karena sulit diterjemahkan.
5. Jangan menebak implementasi yang belum ditemukan.
6. Jangan mengklaim sebuah fitur sudah selesai tanpa implementasi dan bukti pengujian.
7. Jangan menulis file besar yang tidak diperlukan.
8. Jangan membuat abstraksi berlebihan.
9. Jangan menyimpan secrets di dokumentasi atau log.
10. Jangan menjalankan command destructive tanpa konfirmasi.
11. Jangan melakukan perubahan massal tanpa checkpoint.
12. Gunakan kode Go yang idiomatis, mudah dibaca, dan mengikuti praktik keamanan yang sesuai.
13. Dokumentasikan setiap behavioral deviation dari Laravel.
14. Pertahankan kompatibilitas data dan API sejauh diperlukan.
15. Gunakan test coverage sebagai salah satu indikator, bukan satu-satunya bukti kualitas.

## 11. PROGRESS TRACKING

Buat dan pelihara `docs/migration/PROGRESS.md`.

Catat:

- Fase yang sudah selesai.
- File yang sudah diperiksa.
- File yang belum diperiksa.
- Package yang sudah dianalisis.
- Business logic yang sudah dipetakan.
- Modul yang sudah diimplementasikan.
- Test yang sudah dijalankan beserta hasilnya.
- Keputusan arsitektur yang sudah disetujui.
- Risiko yang belum terselesaikan.
- Pertanyaan yang membutuhkan konfirmasi saya.
- Langkah selanjutnya yang paling relevan.

Gunakan status `NOT STARTED`, `IN PROGRESS`, `BLOCKED`, dan `COMPLETED`.

Jangan menandai audit sebagai selesai hanya karena semua folder sudah dilihat. Selesaikan penelusuran alur bisnis dan dependency yang relevan.

## 12. CARA MEMULAI

Pada tahap awal, lakukan AUDIT ONLY.

Jangan mengimplementasikan backend Go dahulu.

Mulai dengan:

1. Periksa struktur repository dan identifikasi framework serta versi Laravel aktual.
2. Identifikasi seluruh dependency dan package.
3. Petakan route, model, controller, service, middleware, job, event, dan integrasi eksternal.
4. Identifikasi business logic dan risiko migrasi.
5. Susun dokumentasi audit secara bertahap.
6. Buat usulan arsitektur Go berdasarkan hasil audit.
7. Buat roadmap migrasi dengan urutan dan acceptance criteria.
8. Laporkan bagian yang belum dapat diverifikasi.

Setelah audit awal, tampilkan ringkasan eksekutif, dependency inventory, daftar modul bisnis, risiko utama, serta rekomendasi urutan migrasi.

Jika masih ada informasi yang belum tersedia, selesaikan bagian audit yang dapat dilakukan dan ajukan pertanyaan yang benar-benar menghambat keputusan penting.

**Definition of Done untuk tahap pertama:** saya dapat memahami seluruh fitur backend Laravel, package yang digunakan, aturan bisnisnya, skema database, mekanisme autentikasi/otorisasi, kontrak API, integrasi eksternal, risiko migrasi, dan rancangan backend Go tanpa harus menebak-nebak perilaku aplikasi.

Mulai sekarang dengan memeriksa repository yang tersedia. Audit dahulu, implementasi kemudian.