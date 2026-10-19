# 10 — Go Architecture Proposal

**Dibuat:** 2026-10-19  
**Status:** PROPOSED & ALIGNED (Phase 1 Audit Completion)  
**Tujuan:** Memberikan blueprint teknis arsitektur backend Go yang kokoh, modular, scalable, dan selaras dengan 6 keputusan strategis yang telah disetujui.

---

## 1. Executive Summary & Keputusan Kunci

Berdasarkan hasil audit menyeluruh pada codebase Laravel (`tokopon-zed-offline`) dan arahan arsitektural:

1. **Strategi Autentikasi (`harus login`):**
   - Tidak melakukan *reverse-engineering* atau pembacaan token lama Laravel Sanctum.
   - Go backend menerbitkan token baru menggunakan standar **JWT (JSON Web Token)** (`access_token` + `refresh_token`).
   - Klien mobile dan frontend cukup login ulang saat beralih ke endpoint Go.
2. **WebSocket Native Go (`go websocket ready`):**
   - Menghilangkan dependensi runtime pada Laravel Reverb / Pusher cloud.
   - Go mengimplementasikan **WebSocket Hub & Room Manager** native (berbasis `gorilla/websocket`).
   - Siap menampung kanal: `conversation.{id}`, `user.{id}`, `cs-chats`, dan broadcast event order/stok secara realtime.
3. **Pola Migrasi Strangler Fig (`stargler fig`):**
   - Migrasi dilakukan bertahap menggunakan API Gateway / Reverse Proxy (Nginx / Traefik).
   - Endpoint Go menggantikan route Laravel satu per satu tanpa mengganggu operasional sistem POS/Web Livewire yang masih berjalan di Laravel.
4. **Eliminasi Erzap ERP (`skip aja itu erzap`):**
   - Semua modul, webhook, dan controller sinkronisasi Erzap dihapus dari cakupan migrasi Go.
5. **Queue Driver Pluggable (`database` sekarang, `redis` ready via ENV):**
   - Dibuat layer abstraksi antarmuka `QueueBroker` / `JobQueue`.
   - Driver aktif saat ini: **Database Driver** (menggunakan tabel database dengan query `FOR UPDATE SKIP LOCKED`).
   - Driver masa depan: **Redis / Asynq** sudah terpasang dan siap aktif hanya dengan mengganti `QUEUE_DRIVER=redis` di file `.env`.
6. **API Response Wrapper Scalable & Terstandarisasi:**
   - Menggantikan format response Laravel yang bervariasi dengan amplop JSON terpadu, konsisten, mendukung pagination meta, machine-readable error codes, dan backward-compatible dengan klien mobile.

---

## 2. Pilihan Teknologi (Technology Stack)

| Komponen | Pilihan Teknologi | Justifikasi |
|---|---|---|
| **Bahasa Pemrograman** | Go 1.22+ / 1.23 | Kinerja concurrency tinggi, memory footprint rendah, type-safety kuat |
| **HTTP Web Framework** | `labstack/echo/v4` | Performa router tinggi, middleware ekstensif, context-handling bersih, minim boilerplate |
| **Database Driver & Querying** | `jmoiron/sqlx` + MySQL Driver (`go-sql-driver/mysql`) | Explicit SQL kontrol, performa query maksimal, dukungan transaksi eksplisit (`lockForUpdate`), zero-magic |
| **Database Migration** | `golang-migrate/migrate/v4` | Standar industri untuk migrasi skema SQL terstruktur |
| **Token & Auth** | `golang-jwt/jwt/v5` + `golang.org/x/crypto/bcrypt` | Kompatibilitas verifikasi hash password Laravel `$2y$` (bcrypt) dan penerbitan token JWT modern |
| **Realtime WebSocket** | `gorilla/websocket` | Teruji produksi, stabil, mendukung channel/room pub-sub in-memory & horizontal scaling |
| **Background Queue** | Abstraksi internal + Driver Database (saat ini) & Driver Redis (`hibiken/asynq` ready) | Memenuhi syarat: aktif database, siap beralih ke Redis cukup ganti `QUEUE_DRIVER=redis` |
| **Config Management** | `spf13/viper` atau `caarlos0/env/v11` | Parsing file `.env` dan environment variable sistem yang type-safe |
| **Validation** | `go-playground/validator/v10` | Validasi struct request berbasis tag yang presisi dan cepat |
| **Logging** | `rs/zerolog` atau `uber-go/zap` | Structured logging JSON dengan performa zero-allocation |
| **Storage / Media** | AWS SDK for Go v2 (`aws-sdk-go-v2/service/s3`) | S3 & Cloudflare R2 kompatibel (menggantikan Spatie MediaLibrary) |
| **Payment Gateway** | Xendit Go SDK / Custom HTTP Client | Integrasi Invoice, VA, dan e-wallet Xendit |

---

## 3. Desain Arsitektur (Modular Monolith - Clean Architecture)

Sistem dirancang sebagai **Modular Monolith** dengan pendekatan Clean Architecture (Domain, UseCase, Repository, Delivery) untuk memudahkan pemisahan domain bisnis tanpa kompleksitas prematur microservices.

```
tokopon-go/
├── cmd/
│   ├── server/                 # Entrypoint API HTTP & WebSocket
│   │   └── main.go
│   ├── worker/                 # Entrypoint Queue Worker Background Runner
│   │   └── main.go
│   └── seeder/                 # Utility script / CLI command
├── config/                     # Konfigurasi ENV & koneksi global
│   └── config.go
├── internal/
│   ├── domain/                 # Domain Entities & Business Interfaces (Zero Dependencies)
│   │   ├── auth.go
│   │   ├── user.go
│   │   ├── product.go
│   │   ├── order.go
│   │   ├── inventory.go
│   │   ├── coupon.go
│   │   ├── loyalty.go
│   │   ├── chat.go
│   │   └── approval.go
│   ├── usecase/                # Business Logic Interactors (Pure Go)
│   │   ├── auth_usecase.go
│   │   ├── product_usecase.go
│   │   ├── order_checkout_usecase.go
│   │   ├── order_payment_usecase.go
│   │   ├── chat_usecase.go
│   │   └── accurate_sync_usecase.go
│   ├── repository/             # Database Access Layer (SQL Queries & Transactions)
│   │   ├── user_repository.go
│   │   ├── product_repository.go
│   │   ├── order_repository.go
│   │   ├── stock_repository.go
│   │   └── accurate_repository.go
│   ├── delivery/
│   │   ├── http/               # HTTP Handlers (Echo Framework)
│   │   │   ├── router.go
│   │   │   ├── middleware/     # Auth JWT, BU Resolver, CORS, Recover
│   │   │   ├── auth_handler.go
│   │   │   ├── mobile_order_handler.go
│   │   │   ├── product_handler.go
│   │   │   └── webhook_handler.go
│   │   ├── ws/                 # WebSocket Delivery & Room Handlers
│   │   │   ├── hub.go
│   │   │   ├── client.go
│   │   │   └── chat_ws_handler.go
│   │   └── worker/             # Job Processors (Database/Redis Consumers)
│   │       ├── accurate_sync_processor.go
│   │       ├── notification_processor.go
│   │       └── order_expiry_processor.go
│   └── infrastructure/         # External Clients (Accurate, Xendit, S3, Expo)
│       ├── accurate/
│       │   └── client.go
│       ├── xendit/
│       │   └── client.go
│       ├── s3/
│       │   └── client.go
│       └── expo/
│           └── client.go
└── pkg/                        # Shared Reusable Utilities
    ├── auth/                   # JWT helper & Bcrypt verifier
    ├── response/               # Standardized Scalable API Response Envelope
    ├── queue/                  # Pluggable Queue Broker Abstraction
    │   ├── broker.go           # Interface definisi
    │   ├── database_broker.go  # Driver database (aktif)
    │   └── redis_broker.go     # Driver redis (ready)
    └── validator/              # Form validation translator
```

---

## 4. API Response Wrapper Scalable & Standarisasi

Untuk menyelesaikan inkonsistensi respon controller Laravel, Go backend menggunakan format respon amplop seragam (*Unified Response Envelope*).

### 4.1. Definisi Kontrak JSON Standar

#### Respons Sukses (Single / Object):
```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "Operasi berhasil diselesaikan.",
  "data": {
    "id": 105,
    "order_number": "MOB-20261019-8921-0001",
    "status": "WAITING_PAYMENT",
    "grand_total": 12500000.00
  },
  "meta": null,
  "errors": null
}
```

#### Respons Sukses dengan Pagination (List):
```json
{
  "success": true,
  "code": "SUCCESS",
  "message": "Data produk berhasil diambil.",
  "data": [
    { "id": 1, "name": "iPhone 15 Pro", "price": 18500000.00 }
  ],
  "meta": {
    "page": 1,
    "per_page": 20,
    "total_records": 154,
    "total_pages": 8
  },
  "errors": null
}
```

#### Respons Gagal / Validasi Error:
```json
{
  "success": false,
  "code": "VALIDATION_ERROR",
  "message": "Data permohonan pesanan tidak valid.",
  "data": null,
  "meta": null,
  "errors": [
    {
      "field": "items.0.qty",
      "message": "Jumlah stok yang diminta melebihi persediaan."
    },
    {
      "field": "shipping_address.phone",
      "message": "Nomor handphone wajib diisi minimal 9 digit."
    }
  ]
}
```

#### Respons Gagal / Server Error:
```json
{
  "success": false,
  "code": "INSUFFICIENT_STOCK",
  "message": "Stok untuk produk 'iPhone 15 128GB' tidak mencukupi.",
  "data": null,
  "meta": null,
  "errors": null
}
```

### 4.2. Implementasi Struct di Package `pkg/response`

```go
package response

import (
	"net/http"
	"github.com/labstack/echo/v4"
)

type Meta struct {
	Page         int   `json:"page,omitempty"`
	PerPage      int   `json:"per_page,omitempty"`
	TotalRecords int64 `json:"total_records,omitempty"`
	TotalPages   int   `json:"total_pages,omitempty"`
}

type FieldError struct {
	Field   string `json:"field"`
	Message string `json:"message"`
}

type Envelope struct {
	Success bool         `json:"success"`
	Code    string       `json:"code"`
	Message string       `json:"message"`
	Data    any          `json:"data"`
	Meta    *Meta        `json:"meta,omitempty"`
	Errors  []FieldError `json:"errors,omitempty"`
}

func OK(c echo.Context, message string, data any) error {
	return c.JSON(http.StatusOK, Envelope{
		Success: true,
		Code:    "SUCCESS",
		Message: message,
		Data:    data,
	})
}

func Paginated(c echo.Context, message string, data any, meta Meta) error {
	return c.JSON(http.StatusOK, Envelope{
		Success: true,
		Code:    "SUCCESS",
		Message: message,
		Data:    data,
		Meta:    &meta,
	})
}

func Created(c echo.Context, message string, data any) error {
	return c.JSON(http.StatusCreated, Envelope{
		Success: true,
		Code:    "CREATED",
		Message: message,
		Data:    data,
	})
}

func Error(c echo.Context, httpStatus int, code, message string, fieldErrors ...FieldError) error {
	return c.JSON(httpStatus, Envelope{
		Success: false,
		Code:    code,
		Message: message,
		Errors:  fieldErrors,
	})
}
```

> **Keunggulan:** Kompatibel 100% dengan mobile frontend yang mengecek `response.data.success` atau `response.data.data`, sekaligus memberikan field `code` dan `errors` terstruktur untuk kemudahan debugging dan penanganan UI.

---

## 5. Strategi Autentikasi (`harus login`)

Karena pengguna wajib login ulang saat beralih ke backend Go, kita tidak terbebani oleh mekanisme stateful token Sanctum lama.

### 5.1. Alur Autentikasi
1. Klien mengirim `POST /api/mobile/auth/login` dengan `email` dan `password`.
2. Go membaca record `users` dari MySQL.
3. Go memverifikasi password hash menggunakan `bcrypt.CompareHashAndPassword([]byte(user.Password), []byte(inputPassword))`. (Kompatibel penuh dengan hash password `$2y$` Laravel).
4. Jika valid, Go menerbitkan:
   - **Access Token:** JWT berumur pendek (misal: 1 jam atau 24 jam) berisi claims: `user_id`, `email`, `roles`, `bu_id`.
   - **Refresh Token:** Token berumur panjang (misal: 30 hari) disimpan terenkripsi/hashed di database.
5. Klien menyertakan header `Authorization: Bearer <jwt>` pada setiap request terproteksi.
6. Middleware Go mengekstrak token, memvalidasi signature secara lokal (CPU-only, tanpa query DB tiap request), dan menyuntikkan user ke dalam context.

---

## 6. Desain WebSocket Go Native (`go websocket ready`)

Arsitektur WebSocket di Go dirancang untuk menggantikan Laravel Reverb / Pusher server tanpa memerlukan layanan pihak ketiga.

### 6.1. Diagram Komponen WebSocket Hub

```
               [ Mobile App / Web Client ]
                           │
             Handshake: GET /ws/connect?token=...
                           │
             ┌─────────────▼─────────────┐
             │    Echo HTTP Upgrader     │
             │   (JWT/Guest Handshake)   │
             └─────────────┬─────────────┘
                           │
            Upgrade ke WebSocket Connection
                           │
             ┌─────────────▼─────────────┐
             │       WebSocket Client    │
             │     (ReadPump/WritePump)  │
             └─────────────┬─────────────┘
                           │
             ┌─────────────▼─────────────┐
             │       Central Hub         │
             │  - Register / Unregister  │
             │  - Rooms (Map[string]Set) │
             │  - Broadcast In-Memory    │
             └──────▲─────────────▲──────┘
                    │             │
        Pesan Chat Baru      Notifikasi Order/Stok
        (Chat UseCase)       (Worker/Event Bus)
```

### 6.2. Kanal & Otorisasi Room
- `conversation.{id}`: Sesi chat pelanggan & CS. Pelanggan hanya bisa bergabung jika pemilik percakapan atau guest token cocok. User CS/Admin dengan role terkait dapat bergabung ke semua room percakapan.
- `user.{userId}`: Kanal privat notifikasi order, status pesanan, dan promo personal.
- `cs-chats`: Broadcast daftar chat masuk ke seluruh staf Customer Service.

### 6.3. Event Payloads
Format frame JSON seragam:
```json
{
  "event": "message.sent",
  "room": "conversation.102",
  "payload": {
    "id": 501,
    "sender_type": "customer",
    "message": "Halo, apakah stok iPhone 15 masih ada?",
    "created_at": "2026-10-19T10:15:30Z"
  }
}
```

---

## 7. Desain Background Queue (Database Aktif, Redis Ready)

Untuk memenuhi kebutuhan operasional saat ini (memanfaatkan database yang ada tanpa dependensi tambahan) sekaligus siap beralih ke Redis dengan instan:

### 7.1. Antarmuka Abstraksi Queue

```go
package queue

import "context"

type JobPayload struct {
	ID        string `json:"id"`
	Queue     string `json:"queue"`
	Type      string `json:"type"`       // Contoh: "accurate:sync_order", "push:send"
	Payload   []byte `json:"payload"`
	MaxRetries int   `json:"max_retries"`
}

type Broker interface {
	Enqueue(ctx context.Context, job JobPayload) error
	RegisterHandler(jobType string, handler func(ctx context.Context, payload []byte) error)
	Start(ctx context.Context) error
	Stop() error
}
```

### 7.2. Factory Switcher via `.env`

```go
func NewQueueBroker(cfg *config.Config, db *sqlx.DB) (queue.Broker, error) {
	switch cfg.QueueDriver {
	case "redis":
		// Asynq / Redis Streams implementation
		return queue.NewRedisBroker(cfg.RedisURL), nil
	case "database":
		fallthrough
	default:
		// Database-backed job queue with SELECT ... FOR UPDATE SKIP LOCKED
		return queue.NewDatabaseBroker(db, cfg.DatabaseQueueTable), nil
	}
}
```

- **Saat ini:** Menggunakan `DatabaseBroker` yang membaca dari tabel database (`jobs`) dengan interval poll efisien dan `FOR UPDATE SKIP LOCKED`.
- **Beralih ke Redis:** Cukup ubah `QUEUE_DRIVER=redis` dan `REDIS_URL=redis://localhost:6379` di `.env`, restart service, tanpa mengubah satupun usecase atau processor bisnis.

---

## 8. Pola Migrasi Strangler Fig

Migrasi sistem dilakukan dengan pola **Strangler Fig** menggunakan Reverse Proxy di lapisan terdepan.

```
                              [ Client Traffic ]
                                      │
                         ┌────────────▼────────────┐
                         │   Reverse Proxy / API   │
                         │    Gateway (Nginx)      │
                         └──────┬────────────┬─────┘
                                │            │
           Route Migrasi Go     │            │  Route Legacy / Unmigrated
      /api/v1/auth/*           │            │  /api/v1/trade-in/*
      /api/v1/mobile/orders/*  │            │  /api/v1/admin/*
      /api/v1/catalog/*        │            │  Web UI Livewire
      /ws/*                     │            │  (All Web Routes)
                                │            │
                    ┌───────────▼──┐     ┌───▼──────────┐
                    │  Go Backend  │     │   Laravel    │
                    │   Service    │     │  App Monolith│
                    └───────────┬──┘     └───┬──────────┘
                                │            │
                                └─────┬──────┘
                                      │ Shared DB
                                ┌─────▼──────┐
                                │   MySQL    │
                                │  Database  │
                                └────────────┘
```

### 8.1. Konfigurasi Nginx Strangler Fig (Contoh)
```nginx
upstream go_backend {
    server 127.0.0.1:8080;
    keepalive 32;
}

upstream laravel_backend {
    server 127.0.0.1:8000;
}

server {
    listen 80;
    server_name api.tokopon.com;

    # 1. Route Go WebSocket
    location /ws/ {
        proxy_pass http://go_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
    }

    # 2. Endpoint yang sudah dimigrasikan ke Go
    location ~ ^/api/(v1/)?(auth|catalog|mobile/orders|mobile/chat)/ {
        proxy_pass http://go_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # 3. Fallback seluruh route lainnya ke Laravel
    location / {
        proxy_pass http://laravel_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

---

## 9. Replikasi Business Logic Kritis (Anti-Overselling & Hooks)

Di Laravel, event seperti `Order::booted()` dan `lockForUpdate()` di `MobileOrderService` berjalan secara otomatis/implisit. Di Go, seluruh alur bisnis dibuat **eksplisit dalam unit transaksi database**.

### 9.1. Checkout Order & Lock Stok di Go (`OrderCheckoutUseCase`)
```go
func (uc *OrderCheckoutUseCase) Execute(ctx context.Context, input CheckoutInput) (*domain.Order, error) {
    tx, err := uc.db.BeginTxx(ctx, &sql.TxOptions{Isolation: sql.LevelReadCommitted})
    if err != nil {
        return nil, err
    }
    defer tx.Rollback()

    // 1. Kunci dan validasi stok di warehouse_stocks
    for _, item := range input.Items {
        var currentStock int
        err := tx.GetContext(ctx, &currentStock, `
            SELECT stock FROM warehouse_stocks 
            WHERE warehouse_id = ? AND variant_id = ? 
            FOR UPDATE`, input.WarehouseID, item.ProductID)
        if err != nil || currentStock < item.Qty {
            return nil, domain.ErrInsufficientStock
        }

        // Pengurangan stok
        _, err = tx.ExecContext(ctx, `
            UPDATE warehouse_stocks 
            SET stock = stock - ? 
            WHERE warehouse_id = ? AND variant_id = ?`, item.Qty, input.WarehouseID, item.ProductID)
        if err != nil {
            return nil, err
        }

        // 2. Kunci Serial Number jika produk memiliki SN (Available -> Reserved)
        if item.HasSN {
            // SELECT id FROM product_serial_numbers WHERE ... AND status = 'Available' LIMIT ? FOR UPDATE
            // UPDATE product_serial_numbers SET status = 'Reserved' WHERE id IN (...)
        }
    }

    // 3. Validasi & potong kupon diskon
    // 4. Validasi & potong loyalty points
    // 5. Generate Order Record & Order Items
    // 6. Commit Transaksi
    if err := tx.Commit(); err != nil {
        return nil, err
    }

    // 7. Jadwalkan Job Expiry (60 menit) ke Background Queue
    uc.queue.Enqueue(ctx, queue.JobPayload{
        Type: "order:check_expiry",
        Payload: orderPayload,
    })

    return order, nil
}
```

---

## 10. Catatan Khusus Integrasi Accurate ERP

Berdasarkan audit fungsi pada `AccurateService.php`:
1. Go membuat package `internal/infrastructure/accurate` dengan HTTP Client yang membaca kredensial (`host`, `token`, `database_id`) per Business Unit secara dinamis dari tabel `business_units`.
2. Disediakan mekanisme retry otomatis dengan exponential backoff untuk mengantisipasi *rate limit* Accurate.
3. Seluruh sinkronisasi penjualan (`postSalesOrder`, `postSalesInvoice`, `postSalesReceipt`) dipicu melalui Queue Worker, bukan saat request synchronous checkout.
