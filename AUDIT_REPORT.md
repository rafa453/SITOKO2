# Code Audit Report — SITOKO2

**Tanggal:** 2026-10-16  
**Project:** SITOKO2 Auditor (Laravel 13 + Vite)  
**Branch:** rafa453/auditor  
**Auditor:** Hermes Agent (Agnes)

---

## Ringkasan Eksekutif

Ditemukan **10 issue** selama audit: 3 kritis, 4 sedang, 3 ringan.  
6 di antaranya sudah diperbaiki (6 file berubah). 4 sisanya memerlukan pertimbangan atau sudah tidak relevan.

---

## 🔴 KRITIS

### C-01: APP_DEBUG=true default di .env.example
- **File:** `.env.example` baris 3
- **Masalah:** Default `APP_DEBUG=true` akan expose stack trace, file path, dan env var ke user jika deploy lupa overwrite.
- **Fix:** ✅ Sudah diubah ke `false`.

### C-02: Hardcoded admin password di .env.example
- **File:** `.env.example` baris ~26
- **Masalah:** `SEEDER_ADMIN_PASSWORD=admin123` — password plaintext di contoh env berisiko bocor ke repo.
- **Fix:** ✅ Sudah diganti placeholder eksplisit `CHANGEME_GANTI_SEBELUM_PRODUKSI`.

### C-03: Race condition pada markCancelled
- **File:** `app/Http/Controllers/PurchaseOrderController.php`, metode `markCancelled()`
- **Masalah:** Tidak ada `lockForUpdate()`. Dua request concurrent bisa double-cancel PO.
- **Fix:** ✅ Sudah ditambahkan `DB::transaction()` + `lockForUpdate()` sebelum check status, sesuai pola yang sudah ada di `settlePayment()`.

---

## 🟠 SEDANG

### M-04: $item->fresh() boros query di markReceived
- **File:** `app/Http/Controllers/PurchaseOrderController.php` dalam loop `markReceived()`
- **Masalah:** `$item->fresh()` memuat ulang row dari DB sekali per iterasi.
- **Fix:** ✅ Dihilangkan dengan assignment lokal `$item->qty_received += $actualQty;` setelah increment.

### M-05: N+1 query di generateSupplierSku
- **File:** `app/Models/Product.php` (metode `generateSupplierSku`) + `app/Http/Controllers/ProductController.php`
- **Masalah:** Setiap supplier dalam loop memanggil `ProductSupplier::whereDate()->count()` — N query untuk N supplier.
- **Fix:** ✅ Dipecahkan dengan:
  - Pre-fetch semua supplier dengan `whereIn()` (1 query vs N query)
  - Pre-calculate `baseCount` sekali di luar loop
  - Tambah param optional `?int $sequence` pada `generateSupplierSku()` agar sequence bisa dilewatkan caller

### M-06: PurchaseOrderStoreRequest salah isi
- **File:** `app/Http/Requests/PurchaseOrderStoreRequest.php`
- **Status:** ❌ **Tidak berlaku** — file sudah berisi rules yang benar untuk PO store (`supplier_id`, `items.*.product_id`, dll). Dugaan audit awal meleset.

### M-07: StoreProfile::get() tanpa guard singleton
- **File:** `app/Http/Controllers/PurchaseOrderController.php` baris 176
- **Masalah:** `StoreProfile::get()` mengambil semua record. Harus dipastikan hanya 1 baris.
- **Status:** ⏸️ **Info** — perlu investigasi migration `StoreProfile`. Diluar scope fix immediate.

---

## 🟡 RINGAN

### L-08: AutoClockInCashier tidak cek email_verified_at
- **File:** `app/Listeners/AutoClockInCashier.php`
- **Masalah:** Cashier baru yang belum verifikasi email tetap dapat shift aktif otomatis saat login.
- **Fix:** ✅ Ditambahkan kondisi `!$user->hasVerifiedEmail()` pada early return.

### L-09: Session cookie secure default null
- **File:** `config/session.php`
- **Masalah:** `'secure' => env('SESSION_SECURE_COOKIE')` — null berarti false di HTTP biasa.
- **Status:** ℹ️ **Expected** — dev menggunakan HTTP, production harus set `SESSION_SECURE_COOKIE=true` di `.env`. Bukan bug code.

### L-10: Search concat pattern di beberapa controller
- **File:** `ProductController`, `SupplierController`, `StaffController`
- **Masalah:** `$q->where('name', 'like', '%'.$request->search.'%')` — concatenation string literal, bukan raw SQL.
- **Status:** ℹ️ **Low risk** — Eloquent `where()` menggunakan parameter binding, aman dari SQL injection. Pattern ini standar Laravel. Tidak perlu fix.

---

## Perubahan yang Diterapkan

| File | Perubahan |
|------|-----------|
| `.env.example` | `APP_DEBUG=false`, password placeholder |
| `PurchaseOrderController.php` | Race condition fix di `markCancelled()`, N+1 fix di `markReceived()` |
| `ProductController.php` | Pre-fetch suppliers, pre-calculate sequence count |
| `Product.php` | Param `?int $sequence` pada `generateSupplierSku()` |
| `AutoClockInCashier.php` | Email verification check |

**Total:** 5 file diubah, +39 / -19 lines (tidak termasuk package-lock.json).

---

## Rekomendasi Selanjutnya

1. Jalankan `composer install` di workspace, lalu `php artisan test` untuk validasi full.
2. Periksa migration `StoreProfile` (M-07) apakah enforce singleton.
3. Pertimbangkan menambah `SESSION_SECURE_COOKIE=true` ke `.env` production.
4. Commit perubahan ke branch `rafa453/auditor`.

---

*Report dibuat oleh Hermes Agent.*
