# SITOKO

Sistem Toko — aplikasi inventory / ERP / POS berbasis Laravel 13.

Stack: PHP 8.3, MySQL 8.4, Vite + Tailwind. Fitur: inventori (SKU otomatis, kadaluarsa, stok menipis), kasir/POS (shift clock-in, void), purchase order (DP/settle, PDF), retur supplier, laporan (PDF/CSV), supplier & brand, staff, notifikasi, pengaturan.

## Prasyarat

- PHP 8.3 (ext: openssl, mbstring, curl, fileinfo, pdo_mysql, sqlite3, zip, gd, intl)
- MySQL 8.4 (server di `127.0.0.1:3306`, user `root` tanpa password)
- Composer, Node.js + npm

## Instalasi

```bash
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

`.env` sudah dikonfigurasi untuk MySQL lokal (root tanpa password). Sesuaikan bila berbeda.

## Jalankan

```bash
php artisan migrate:fresh --seed
php artisan serve
```

Buka http://127.0.0.1:8000.

## Akun default

| Email | Password | Role |
|---|---|---|
| `admin@sitoko.test` | `admin123` | admin |

Password admin default bisa diubah lewat variabel env sebelum seed:

```
SEEDER_ADMIN_PASSWORD=rahasia
```

**Wajib ganti password ini sebelum deploy ke produksi.** Registrasi publik sudah dinonaktifkan — user baru hanya bisa dibuat admin lewat halaman register yang dikunci role `admin`.

## Catatan MySQL

Backend memakai MySQL (bukan SQLite). Seeder memakai `SET FOREIGN_KEY_CHECKS=0`, jadi harus MySQL.

Bila `migrate:fresh` gagal karena perintah `mysql` tidak ditemukan, tambahkan `mysql` CLI ke `PATH` (mis. Laragon):

```bash
export PATH="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin:$PATH"
```

`database/schema/mysql-schema.sql` adalah schema dump (dipakai Laravel untuk mempercepat migrate). Regenerasi dengan:

```bash
php artisan schema:dump
```

## Test

```bash
php artisan test
```

## License

MIT.
