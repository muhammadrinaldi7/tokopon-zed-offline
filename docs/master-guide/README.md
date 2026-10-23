# 🧭 Tokopon Master Guide

> **Pusat Dokumentasi, Pengambilan Keputusan, Pelacakan Progres, dan Panduan Pengembangan Ekosistem Tokopon**  
> Baseline Audit: **22 Oktober 2026** | Versi Master Guide: **1.0.0**

---

## 📌 1. Selamat Datang di Tokopon Master Guide

Dokumen ini merupakan **Single Source of Truth (SSOT)** untuk seluruh pengembang (human engineer) dan AI coding agent yang bekerja di dalam ekosistem Tokopon.

Ekosistem Tokopon terdiri dari 3 repository utama yang saling terhubung:
1. [tokopon-zed-offline](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline) — Monolit Laravel legacy (referensi bisnis, skema database master, dan operasional eksisting).
2. [tokopon-go](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-go) — Backend Go Modular Monolith modern (pengganti backend Laravel via Strangler Fig pattern).
3. [tokopon-web](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/tokopon-web) — Frontend SPA (Vite + React 19 + TypeScript + Tailwind v4 + Zustand) untuk Kasir POS dan Backoffice.

---

## 🗺️ 2. Peta Navigasi Master Guide

Setiap dokumen dirancang spesifik untuk tujuan operasional tertentu. Gunakan tabel berikut sebagai panduan navigasi:

| Dokumen | Deskripsi & Tujuan | Target Audiens |
|---|---|---|
| [`PROJECT_STATUS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/PROJECT_STATUS.md) | Snapshot status aktual, metrik verifikasi, gap terbesar, dan blocker | Lead Architect, PM, Developer |
| [`SYSTEM_ARCHITECTURE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/SYSTEM_ARCHITECTURE.md) | Arsitektur 3 repo, aliran data, multi-tenancy, topologi, dan boundary | Backend & DevOps Engineer |
| [`BUSINESS_LOGIC.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/BUSINESS_LOGIC.md) | Rekonstruksi alur bisnis Laravel, invarian stok, promo, HPP, multi-BU | Backend Engineer & QA |
| [`FEATURE_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/FEATURE_MATRIX.md) | Matriks seluruh fitur bisnis, acceptance criteria, dan status verifikasi | PM, QA & Developers |
| [`MIGRATION_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/MIGRATION_MATRIX.md) | Pemetaan fitur Laravel ke Go, status parity, dan gap teknis | Go Backend Engineer |
| [`API_CONTRACTS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/API_CONTRACTS.md) | Daftar endpoint terverifikasi, payload request/response, dan Postman mocks | Frontend & Backend Engineer |
| [`DATABASE_MAP.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DATABASE_MAP.md) | Peta skema database `zedpost-7`, tabel utama, multi-tenant key, indeks | Database & Backend Engineer |
| [`INTEGRATION_MATRIX.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/INTEGRATION_MATRIX.md) | Kesiapan integrasi frontend-backend, third-party (Accurate, WA, Xendit, Telegram) | Fullstack & QA |
| [`TESTING_AND_RELEASE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/TESTING_AND_RELEASE.md) | Hasil test aktual, runner command, strategi pengujian, dan kriteria cutover | QA & Release Engineer |
| [`BACKLOG.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/BACKLOG.md) | Daftar task siap eksekusi (P0 - P3) dengan dependencies dan DoD | All Engineers & AI Agents |
| [`DECISIONS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DECISIONS.md) | Architectural Decision Records (ADR) dan justifikasi bisnis | Architects & Tech Leads |
| [`RISKS_AND_BLOCKERS.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/RISKS_AND_BLOCKERS.md) | Matriks risiko teknis/operasional, severity, likelihood, dan mitigasi | Project Manager & Tech Lead |
| [`DEVELOPMENT_GUIDE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/DEVELOPMENT_GUIDE.md) | Standar coding, workflow harian, panduan menambah fitur/endpoint/test | All Developers |
| [`AI_CONTEXT.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/AI_CONTEXT.md) | Konteks cepat yang WAJIB dibaca oleh AI coding agent sebelum mulai bekerja | AI Coding Agents |
| [`CHANGELOG.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/CHANGELOG.md) | Riwayat pembaruan Master Guide dan transisi status | All Team Members |

---

## 🛠️ 3. Folder Template Standar

Untuk menjaga kualitas dan format standar dokumentasi, gunakan template di [`templates/`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/):
- [`FEATURE_STATUS_TEMPLATE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/FEATURE_STATUS_TEMPLATE.md)
- [`SESSION_HANDOFF_TEMPLATE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/SESSION_HANDOFF_TEMPLATE.md)
- [`BUG_REPORT_TEMPLATE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/BUG_REPORT_TEMPLATE.md)
- [`DECISION_RECORD_TEMPLATE.md`](file:///c:/Users/ADVAN/Herd/tokopon-zed-offline/docs/master-guide/templates/DECISION_RECORD_TEMPLATE.md)

---

## 📋 4. Aturan Pemeliharaan Master Guide

1. **Evidence-Based:** Tidak ada fitur yang boleh dinyatakan `COMPLETED` atau `PARITY_VERIFIED` tanpa bukti kode, test, atau verifikasi end-to-end.
2. **Atomic Updates:** Setiap perubahan kode yang mengubah kontrak API, tabel database, atau business rule wajib diikuti update dokumen terkait di `docs/master-guide/`.
3. **Audit Baseline:** Selalu catat branch dan commit hash saat melakukan audit atau pengujian.
