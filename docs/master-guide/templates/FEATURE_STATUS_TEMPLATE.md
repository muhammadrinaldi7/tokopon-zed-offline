# 📄 [FEAT-XXX] Feature Status Document Template

## 1. Identitas Fitur
- **Feature ID:** FEAT-XXX
- **Nama Fitur:** [Nama Fitur Bisnis]
- **Modul Terkait:** [identity / pos / inventory / catalog / loyalty / tradein / integration / analytics]
- **Target Repository:** [tokopon-go / tokopon-web / tokopon-zed-offline]
- **Status Aktual:** [READY_FOR_DEV / IN_PROGRESS / IMPLEMENTED_UNTESTED / VERIFIED]
- **Prioritas:** [P0 / P1 / P2 / P3]

---

## 2. Kebutuhan Bisnis & Aturan (Business Rules)
- **Tujuan Bisnis:** [Jelaskan apa yang dicapai fitur ini untuk operasional toko]
- **Aktor Terlibat:** [Kasir / Kepala Gudang / Salesman / Teknisi / Direksi / Pelanggan]
- **Invarian Kunci:** [Multi-tenancy BU, row locking, validasi izin, dll]

---

## 3. Implementasi Teknis
- **Backend Handler & Route:** `[Path ke handler.go]` — `METHOD /api/v1/...`
- **Backend UseCase & Repo:** `[Path ke usecase.go]` & `[Path ke repository.go]`
- **Frontend Page / Store:** `[Path ke Page.tsx]` & `[Path ke store.ts]`
- **Database Tables Impacted:** `[Tabel MySQL yang dibaca atau diubah]`

---

## 4. Bukti Pengujian (Test Evidence)
- **Unit Test File:** `[Path ke file_test.go]`
- **Perintah Pengujian:** `go test -v -run TestXxx ./...`
- **Hasil Pengujian:** [Lulus / Gagal beserta durasi]
- **Tangkapan Layar / Log Output:** [Bila ada]

---

## 5. Acceptance Criteria Checklist
- [ ] Kriteria 1: [Penjelasan]
- [ ] Kriteria 2: [Penjelasan]
- [ ] Kriteria 3: [Penjelasan]
