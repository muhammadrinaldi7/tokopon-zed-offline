# 16 — Wave 4: Native Go WebSocket & Live Chat CS API

**Dibuat:** 2026-10-19  
**Status:** COMPLETED & VERIFIED ✅  
**Base URL HTTP:** `http://localhost:8080/api/v1/mobile/chat`  
**Base URL WebSocket:** `ws://localhost:8080/api/v1/ws` (atau `ws://localhost:8080/ws`)  
**Testing:** 26/26 Go Native Tests Passed (`go test -v ./...`)

---

## 1. Daftar Endpoint yang Selesai Dimigrasikan

| Endpoint | Method / Protocol | Fungsi | Parameter Kunci |
|---|---|---|---|
| `/ws` / `/api/v1/ws` | WebSocket (`GET`) | Upgrade koneksi HTTP ke WebSocket Realtime | `token`, `guest_token`, `room` |
| `/chat/init` | POST | Inisialisasi sesi obrolan (Customer / Guest) | `guest_token`, `guest_name`, `guest_phone`, `push_token`, `product_accurate_id` |
| `/chat/:conversation_id/messages` | GET | Riwayat pesan obrolan (Paginasi) | `:conversation_id`, `guest_token`, `page`, `per_page` |
| `/chat/:conversation_id/messages` | POST | Kirim pesan teks, tag produk, atau lampiran berkas | `:conversation_id`, `message`, `attachment`, `product_accurate_id` |
| `/chat/:conversation_id/read` | POST | Tandai pesan lawan bicara sudah dibaca | `:conversation_id`, `guest_token` |

---

## 2. Fitur & Keunggulan Khusus di Backend Go

1. **Eliminasi Total Runtime Laravel Reverb / Pusher:**
   - Obrolan realtime ditenagai oleh in-memory concurrency Hub (`gorilla/websocket`), menghemat konsumsi memori dan menghilangkan dependensi layanan pihak ketiga.
2. **Arsitektur Room-Based Broadcasting:**
   - Room `conversation.{id}` untuk komunikasi terisolasi antar klien percakapan.
   - Room `cs-broadcast` untuk siaran notifikasi pesan masuk ke seluruh tim CS dashboard.
3. **Penyimpanan Berkas Kompatibel Spatie MediaLibrary:**
   - Lampiran foto/dokumen pada pesan obrolan dicatat ke tabel `media`, menjamin kompatibilitas langsung dengan antarmuka web admin Laravel.
4. **Go Native Testing:**
   - Pengujian siklus hidup WebSocket Hub (register, join room, broadcast, leave room, unregister) serta integrasi handler REST lulus 100% via `go test -v ./...`.
