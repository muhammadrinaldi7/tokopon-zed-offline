# 27 — [MOD-01] Identity, Multi-Tenant & RBAC Module

**Tanggal:** 20 Oktober 2026  
**Status:** COMPLETED ✅  
**Paket Sumber:** `tokopon-go/internal/modules/identity/`  
**Target Basis Data:** Shared MySQL `zedpost-7` (Tahap 1) $\rightarrow$ Mandiri (*Independent Go Migrations*) (Tahap 2)  

---

## 1. Ringkasan Modul

Modul **[MOD-01] Identity, Multi-Tenant & RBAC** merupakan modul pertama yang diimplementasikan dalam format **Modular Monolith** baru untuk ZedPos Go Backend. Modul ini bertanggung jawab penuh terhadap:

1. **Autentikasi Terpusat:** Login, registrasi akun mobile, rotasi refresh token, profil pengguna, dan push notification token.
2. **Multi-Tenant Business Unit (BU) & Branch Scoping:**
   - Menegakkan isolasi data antar Unit Bisnis toko.
   - Paritas penuh terhadap logika Laravel `User.php::getActiveBusinessUnitId()`.
   - Mengizinkan peran eksekutif (`superadmin`, `admin`, `director`) berpindah konteks BU secara dinamis via header `X-Business-Unit-Id` atau endpoint `POST /api/v1/auth/switch-bu`.
   - Memblokir akses silang (*cross-tenant spoofing*) bagi pengguna non-eksekutif (`403 Forbidden`).
3. **Paritas Penuh Spatie Permission & Role-Based Access Control (RBAC):**
   - Mengambil data roles dan permissions langsung dari tabel Spatie Laravel via SQL join optimal.
   - Menyuntikkan roles dan permissions ke dalam klaim JWT, menghilangkan kueri DB per-request pada endpoint terproteksi.
   - Fitur *SuperAdmin Wildcard Bypass* yang identik dengan `Gate::before` Laravel.
4. **Kompatibilitas Ganda Rute API (Path Aliasing):**
   - Mendukung format path modern: `/api/v1/auth/*`
   - Mendukung format legacy aplikasi mobile: `/api/v1/mobile/auth/*`
5. **Kesiapan Migrasi Database Mandiri:**
   - Telah disiapkan file DDL migrasi native Go: `migrations/000001_create_identity_and_rbac_tables.up.sql` dan `down.sql`.

---

## 2. Struktur Berkas Modul (`internal/modules/identity`)

```
tokopon-go/internal/modules/identity/
├── domain.go              # Entities (User, Role, Permission, BU, Branch), TenantContext, DTOs & Interfaces
├── repository.go          # Implementasi SQL Repository untuk tabel pengguna, Spatie RBAC & BU
├── usecase.go             # Logika bisnis autentikasi, tenant resolution, dan switch BU
├── middleware.go          # JWTMiddleware, RequireRole, RequirePermission, TenantContextMiddleware
├── handler.go             # Echo HTTP delivery handler (registrasi route /api/v1/auth & /api/v1/mobile/auth)
├── usecase_test.go        # Unit test UseCase (login, register, switch BU, tenant isolation)
├── handler_test.go        # Unit test Handler (HTTP route parity, validation, switch BU)
└── middleware_test.go     # Unit test Middleware (superadmin bypass, RBAC guard)
```

---

## 3. Matriks Kepemilikan Data (*Table Ownership*)

| Tabel | Operasi di Modul Identity | Deskripsi |
|---|---|---|
| `users` | READ, INSERT, UPDATE | Master akun pengguna kasir, staf, eksekutif, dan pelanggan |
| `user_profiles` | READ, INSERT, UPDATE | Profil detail (nama lengkap, nomor HP, domisili, jenis kelamin) |
| `roles` | READ, INSERT | Master peran Spatie (superadmin, admin, director, cashier, customer) |
| `permissions` | READ | Master izin akses Spatie (pos-checkout, view-reports, dll) |
| `model_has_roles` | READ, INSERT | Relasi banyak-ke-banyak peran pengguna |
| `model_has_permissions` | READ | Relasi langsung izin khusus ke pengguna |
| `role_has_permissions` | READ | Relasi izin ke peran |
| `business_units` | READ | Master Unit Bisnis toko fisik & e-commerce |
| `branches` | READ | Master cabang toko fisik |

---

## 4. Aturan Bisnis & Penegakan Invarian Ketat (*Strict Invariants*)

### 4.1 Invarian Isolasi Multi-Tenant
```go
func (u *useCase) ResolveTenantContext(ctx context.Context, claims *auth.CustomClaims, headerBU string, headerBranch string) (*TenantContext, error) {
    // 1. Periksa apakah user memiliki peran eksekutif (superadmin, admin, director)
    isExecutive := claims.HasAnyRole("superadmin", "admin", "director", "direktur")
    
    // 2. Jika ada header X-Business-Unit-Id
    if headerBU != "" {
        if isExecutive {
            // Eksekutif diizinkan melakukan override BU
            activeBUID = parsedBU
        } else {
            // Non-eksekutif DITOLAK jika mencoba mengakses BU di luar akunnya
            if parsedBU != *claims.BusinessUnitID {
                return nil, ErrTenantAccessDenied
            }
        }
    }
}
```

### 4.2 SuperAdmin Wildcard Bypass (Laravel `Gate::before` Parity)
```go
func (t *TenantContext) HasPermission(permission string) bool {
    if t.HasRole("superadmin") {
        return true // Superadmin otomatis memiliki seluruh izin tanpa pengecualian
    }
    // Periksa daftar permissions aktual
    ...
}
```

---

## 5. Daftar Endpoint API

Seluruh endpoint di bawah ini terdaftar pada dua prefix path: `/api/v1/auth` dan `/api/v1/mobile/auth`.

| Method | Endpoint | Auth | Middleware Guard | Deskripsi |
|---|---|---|---|---|
| `POST` | `/api/v1/auth/login` | Publik | — | Autentikasi email & password Bcrypt, terbitkan JWT |
| `POST` | `/api/v1/auth/register` | Publik | — | Pendaftaran akun baru, auto-create profile & role customer |
| `POST` | `/api/v1/auth/refresh` | Publik | — | Rotasi token sesi menggunakan refresh token |
| `POST` | `/api/v1/auth/logout` | Publik | — | Logout stateless JWT |
| `GET` | `/api/v1/auth/me` | JWT | `TenantContext` | Mengambil data akun, profil, roles, dan permissions aktif |
| `PUT` | `/api/v1/auth/profile` | JWT | `TenantContext` | Memperbarui nama, nomor telepon, dan domisili |
| `POST` | `/api/v1/auth/push-token` | JWT | — | Memperbarui token push notification Expo |
| `POST` | `/api/v1/auth/switch-bu` | JWT | `RequireRole(executive)` | Berpindah konteks Unit Bisnis aktif (khusus eksekutif) |
| `GET` | `/api/v1/auth/business-units` | JWT | — | Daftar Unit Bisnis yang dapat diakses oleh user saat ini |

---

## 6. Verifikasi Pengujian

Seluruh pengujian unit pada paket `internal/modules/identity` lulus 100%:

```
=== RUN   TestHandler_LoginAndBackwardsCompatibleRoutes
--- PASS: TestHandler_LoginAndBackwardsCompatibleRoutes (0.22s)
=== RUN   TestHandler_SwitchBusinessUnit_Endpoint
--- PASS: TestHandler_SwitchBusinessUnit_Endpoint (0.15s)
=== RUN   TestMiddleware_RequireRole_SuperadminBypass
--- PASS: TestMiddleware_RequireRole_SuperadminBypass (0.00s)
=== RUN   TestMiddleware_RequirePermission_SuperadminBypass
--- PASS: TestMiddleware_RequirePermission_SuperadminBypass (0.00s)
=== RUN   TestLogin_Success
--- PASS: TestLogin_Success (0.11s)
=== RUN   TestLogin_InvalidPassword_Fails
--- PASS: TestLogin_InvalidPassword_Fails (0.12s)
=== RUN   TestLogin_UserNotFound_Fails
--- PASS: TestLogin_UserNotFound_Fails (0.06s)
=== RUN   TestRegister_Success
--- PASS: TestRegister_Success (0.11s)
=== RUN   TestRegister_DuplicateEmail_Fails
--- PASS: TestRegister_DuplicateEmail_Fails (0.06s)
=== RUN   TestSwitchBusinessUnit_Executive_Success
--- PASS: TestSwitchBusinessUnit_Executive_Success (0.05s)
=== RUN   TestSwitchBusinessUnit_NonExecutive_Forbidden
--- PASS: TestSwitchBusinessUnit_NonExecutive_Forbidden (0.06s)
=== RUN   TestSwitchBusinessUnit_InactiveBU_Fails
--- PASS: TestSwitchBusinessUnit_InactiveBU_Fails (0.06s)
=== RUN   TestResolveTenantContext_StrictIsolation
--- PASS: TestResolveTenantContext_StrictIsolation (0.06s)
PASS
ok  	tokopon-go/internal/modules/identity	3.498s
```

Seluruh test suite proyek (`go test ./...`) tetap lulus 100% tanpa regresi.
