# 05 — Authentication & Authorization Audit

**Dibuat:** 2026-10-19  
**Status:** COMPLETED (audit awal)

---

## Authentication

### Web Authentication (Laravel Fortify)
- **Mekanisme:** Session-based (cookie)
- **Package:** `laravel/fortify: ^1.36`
- **Features aktif:** Login, Register, Reset Password (Two-Factor Authentication: NEEDS VERIFICATION)
- **Password hashing:** bcrypt (`password` cast di User model)
- **Rate limiting login:** 5x/menit per kombinasi email+IP (`FortifyServiceProvider`)
- **Login identifier:** email
- **Actions:** `CreateNewUser`, `ResetUserPassword` (di `app/Actions/Fortify/`)
- **Views:** Blade views yang membungkus Livewire components

### API Authentication (Laravel Sanctum)
- **Mekanisme:** Opaque token (simpan di tabel `personal_access_tokens`)
- **Package:** `laravel/sanctum: ^4.0`
- **Trait di User:** `HasApiTokens`
- **Middleware:** `auth:sanctum`
- **Token issuance:** `$user->createToken('executive-access')` atau `'mobile-access'`
- **Token revocation:** `$user->currentAccessToken()->delete()` saat logout
- **Token expiry:** NEEDS VERIFICATION (lihat `config/sanctum.php`)
- **Refresh token:** TIDAK ADA — stateless, client minta token baru via login ulang

### Google OAuth (Socialite)
- **Package:** `laravel/socialite: ^5.26`
- **Provider:** Google
- **Flow:** Redirect → callback → `User::updateOrCreate(provider_id)`
- **Controller:** `GoogleCallbackController`
- **Catatan:** Hanya untuk web, tidak ada di API mobile

---

## Authorization

### Role-Based Access Control (Spatie Permission)
- **Package:** `spatie/laravel-permission: ^7.2`
- **Trait di User:** `HasRoles`
- **Guard yang digunakan:** `web` (default Spatie)

### Roles yang Teridentifikasi
| Role | Deskripsi |
|---|---|
| `superadmin` | Akses penuh, bisa melakukan bypass hampir semua check |
| `admin` | Akses admin dengan permission granular |
| `director` | Akses executive API + approval |
| `customer` | Pengguna mobile/web customer |
| `cs` | Customer service, akses cs-chat |

*Catatan: daftar role lengkap perlu diverifikasi dari database/seeder*

### Permissions yang Teridentifikasi
Dari `routes/web.php` (`can:` checks):
- `monitoring-kasir`
- `view-pos`
- `trade-in`
- `sell-phone`
- `sell-phone-history`
- `view-stock`
- `view-warehouse-stocks`
- `view-reporting`
- `view-riwayat-kasir`
- `view-message-logs`
- `view-dashboard`
- `manage-orders`
- `manage-users`
- `manage-settings`
- `manage-promos`
- `manage-trade-in`
- `manage-buyback`
- `manage-inbound`
- `manage-new-catalog`
- `manage-second-catalog`
- `manage-categories`
- `manage-brands`
- `manage-accurate-products`
- `manage-accurate-customers`
- `manage-qc-templates`
- `manage-qc-inspections`
- `warranty-activation`
- `access-cs-chat`
- `cek-unit-rentang-harga`
- `report-monitoring-kasir`

### Permission Checks di Middleware
- Route middleware `can:X` → Spatie `Gate::authorize('X')`
- `EnsureIsAdmin` → `getAllPermissions()->isEmpty() && !hasRole('superadmin')`

### Permission Checks di API
- `role:superadmin|admin|director` → dipakai di Executive API group
- `auth:sanctum` middleware untuk API endpoints yang memerlukan login
- Tidak ada `can:` middleware pada API routes yang teridentifikasi (cek via controller logic?)

---

## Token Issuance Detail

### Executive API Login
```
POST /api/v1/executive/login
Body: { email, password }
Response: {
  token: "sanctum_token_here",
  user: { id, name, email, roles, permissions }
}
```

### Mobile API Login
```
POST /api/v1/mobile/auth/login
Body: { email, password }
Response: {
  token: "sanctum_token_here",
  user: { id, name, email }
}
```

### Mobile API Register
```
POST /api/v1/mobile/auth/register
Body: { name, email, password, password_confirmation }
Response: {
  token: "sanctum_token_here",
  user: { id, name, email }
}
```

---

## Logout

### API Logout
```
POST /api/v1/executive/logout (auth:sanctum)
POST /api/v1/mobile/auth/logout (auth:sanctum)
→ $user->currentAccessToken()->delete()
```

### Web Logout
```
POST /logout (auth)
→ Auth::logout()
→ session()->invalidate()
→ session()->regenerateToken()
→ redirect('/')
```

---

## Password Reset

- Implementasi via Fortify (`Fortify::requestPasswordResetLinkView`, `Fortify::resetPasswordView`)
- Action: `ResetUserPassword` di `app/Actions/Fortify/`
- TIDAK TERSEDIA di API (hanya web)

---

## Account Status

- **Email verification:** Commented out di User model (`MustVerifyEmail` di-comment)
- **Account blocking:** NEEDS VERIFICATION — tidak ditemukan field `is_active` di tabel `users`
- **Two-factor auth:** Kolom ada di tabel (`two_factor_secret`, `two_factor_recovery_codes`) tapi fitur aktif atau tidak NEEDS VERIFICATION

---

## Rate Limiting

| Endpoint | Limit |
|---|---|
| Login (Executive API) | 10 req/menit |
| Login (Fortify web) | 5 req/menit per email+IP |
| Mobile API (semua) | 120 req/menit |
| Public Trade-In API | 60 req/menit |

---

## Compatibility Mapping: Laravel → Go

| Laravel Mechanism | Go Equivalent |
|---|---|
| Sanctum opaque token | JWT atau database-stored opaque token |
| `personal_access_tokens` table | Tetap pakai tabel yang sama (kompatibilitas data) |
| `HasRoles` (Spatie) | Custom RBAC dari tabel Spatie yang ada |
| `can:permission` middleware | Custom middleware cek tabel `model_has_permissions` |
| `role:X\|Y` middleware | Custom middleware cek tabel `model_has_roles` |
| `Auth::user()` | JWT claims atau DB lookup dari token |
| `session('active_business_unit_id')` | Header `X-Business-Unit-Id` di API |
| Password bcrypt | golang.org/x/crypto/bcrypt |

---

## Risiko Migration Auth

1. **Token format:** Sanctum menyimpan token sebagai hash SHA256 di DB. Jika Go perlu membaca token lama, harus memahami format ini atau minta user login ulang.
2. **Session-based auth (web):** Jika Livewire tetap berjalan sementara Go juga jalan, perlu coordination. Namun karena web tidak dimigrasi, ini risiko rendah.
3. **Spatie RBAC:** Tabel Spatie sudah ada di DB. Go cukup query tabel tersebut.
4. **Guard mismatch:** Spatie menggunakan guard `web` secara default. Perlu verifikasi apakah permission check untuk API (sanctum guard) juga berjalan via tabel yang sama.
