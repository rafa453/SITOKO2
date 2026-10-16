# Audit Fixes Applied — SITOKO2

**Tanggal:** 2026-10-16
**Sumber audit:** `AUDIT_REPORT.md` (Hermes Agent / Agnes)
**Workspace target:** `C:\Users\rafa\orca\workspaces\SITOKO2\testing`
**Dokumentasi oleh:** OpenCode

---

## Ringkasan

Seluruh poin perbaikan dari laporan audit diterapkan ke workspace `testing`.
Diverifikasi dengan `php artisan test` (**39 passed / 92 assertions**) dan
`./vendor/bin/pint --test` (**passed**).

| ID | Severity | Status | Ringkas |
|----|----------|--------|---------|
| C-01 | Kritis | Diterapkan | `APP_DEBUG=false` di `.env.example` |
| C-02 | Kritis | Diterapkan | Password seeder placeholder, bukan `admin123` |
| C-03 | Kritis | Diterapkan | `lockForUpdate()` pada `markCancelled()` |
| M-04 | Sedang | Diterapkan | Hapus `$item->fresh()` di `markReceived()` |
| M-05 | Sedang | Diterapkan | N+1 `generateSupplierSku()` dipecahkan |
| M-06 | Sedang | Tidak berlaku | `PurchaseOrderStoreRequest` sudah benar |
| M-07 | Sedang | Info | Singleton `StoreProfile` di luar scope |
| L-08 | Ringan | Diterapkan (revisi) | Guard verifikasi email cashier |
| L-09 | Ringan | Expected | `SESSION_SECURE_COOKIE` urusan env produksi |
| L-10 | Ringan | Aman | `like` pakai parameter binding Eloquent |

---

## Perubahan per file

| File | Perubahan |
|------|-----------|
| `.env.example` | `APP_DEBUG=true` → `false`; `SEEDER_ADMIN_PASSWORD=admin123` → `CHANGEME_GANTI_SEBELUM_PRODUKSI` |
| `app/Http/Controllers/PurchaseOrderController.php` | `markCancelled()` dibungkus `DB::transaction()` + `lockForUpdate()`; `markReceived()` tidak lagi `$item->fresh()` |
| `app/Http/Controllers/ProductController.php` | Pre-fetch supplier via `whereIn()` + `keyBy`, pre-calculate `baseCount` sekali (store & update) |
| `app/Models/Product.php` | `generateSupplierSku()` tambah param opsional `?int $sequence = null` |
| `app/Models/User.php` | `implements MustVerifyEmail` (import diaktifkan) |
| `app/Http/Controllers/Auth/RegisteredUserController.php` | `email_verified_at => now()` saat admin membuat user |
| `app/Listeners/AutoClockInCashier.php` | Guard `! $user->hasVerifiedEmail()` pada early return |

---

## Koreksi penting: L-08

Patch L-08 pada laporan awal **tidak dapat dijalankan** di repo ini:

- `User` belum `implements MustVerifyEmail` → method `hasVerifiedEmail()` tidak ada,
  sehingga `$user->hasVerifiedEmail()` akan **fatal error di setiap login**.
- `RegisteredUserController` tidak pernah mengisi `email_verified_at`, sehingga
  **semua** cashier buatan admin berstatus unverified. Guard versi Hermes akan
  **mematikan auto clock-in untuk seluruh cashier** (bukan menutup celah).

**Revisi yang diterapkan (fungsional & tidak memutus fitur):**

1. `User implements MustVerifyEmail` → `hasVerifiedEmail()` tersedia.
2. `RegisteredUserController::store()` menandai user buatan admin terverifikasi
   (`email_verified_at => now()`), karena registrasi admin-only dan tidak ada
   alur email verifikasi aktif.
3. `AutoClockInCashier` menolak clock-in bila `! $user->hasVerifiedEmail()`.

Efek: guard tetap bermakna saat email diubah lewat profil (ProfileController
men-null-kan `email_verified_at`), tanpa memutus auto clock-in normal.

---

## Verifikasi

```
composer install            → OK (ext-zip diaktifkan di php.ini)
npm install && npm run build → OK (Vite manifest dibuat)
php artisan key:generate    → OK
php artisan test            → 39 passed / 92 assertions
./vendor/bin/pint --test     → passed
```

---

## Catatan lingkungan

- `extension=zip` di `C:/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.ini`
  diaktifkan (sebelumnya dikomentari); backup: `php.ini.bak_sitoko`.
  Diperlukan oleh `phpoffice/phpspreadsheet` (maatwebsite/excel).
- `package-lock.json` sempat berubah nama menjadi `testing` karena `npm install`;
  sudah dikembalikan (revert) agar diff bersih.

---

## Status commit

Belum di-commit. File berubah (workspace `testing`):

```
M .env.example
M app/Http/Controllers/Auth/RegisteredUserController.php
M app/Http/Controllers/ProductController.php
M app/Http/Controllers/PurchaseOrderController.php
M app/Listeners/AutoClockInCashier.php
M app/Models/Product.php
M app/Models/User.php
```

Terkait: `AUDIT_REPORT.md`
