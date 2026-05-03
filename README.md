# StarStyle

StarStyle adalah aplikasi web reservasi dan manajemen salon modern berbasis PHP, CSS, dan JavaScript tanpa framework berat. Proyek ini menampilkan portal publik pelanggan, dashboard internal admin/staff, POS, CRM, inventory, voucher, analytics, review, dan pengaturan akses staff.

## Stack

- PHP 8.2
- Bootstrap 5 + vanilla JavaScript
- Chart.js
- Flatpickr
- MySQL/MariaDB schema tersedia di `database/schema.sql`

## Fitur Utama

- Dashboard beranda dengan KPI, grafik penjualan, upcoming agenda, aktivitas booking, dan top performer
- Scheduling system dengan kalender per staff, anti double booking, blocked time, dan booking form internal
- Portal booking publik customer dengan slot availability
- POS ringan dengan multi-item cart, validasi voucher, dan checkout transaksi
- CRM pelanggan dengan loyalty, tags, histori kunjungan, dan segmentasi dasar
- Staff management dengan shift, attendance, komisi, dan permission matrix
- Layanan, paket layanan, inventory, voucher, analytics, review, dan activity logs
- Admin full access dan staff access yang bisa diatur oleh admin dari halaman Settings

## Menjalankan Aplikasi

1. Pastikan PHP 8.2+ tersedia.
2. Jalankan server lokal:

```bash
php -S localhost:8000 -t public
```

3. Buka [http://localhost:8000](http://localhost:8000).

## Demo Account

- Admin: `admin@starstyle.test` / `password123`
- Staff: `stylist@starstyle.test` / `password123`
- Customer: `customer@starstyle.test` / `password123`

## Struktur Folder

- `public/` entrypoint, CSS, dan JavaScript
- `app/Controllers` controller halaman dan API
- `app/Services` auth, permission, repository data demo
- `app/Views` layout dan halaman publik/internal
- `config/` konfigurasi aplikasi dan katalog permission
- `database/` skema MySQL dan seed demo
- `storage/cache/` session cache runtime lokal

## Database

- Skema utama: [database/schema.sql](/C:/Users/hi/Documents/New%20project/database/schema.sql)
- Seed demo: [database/seeders/demo_seed.sql](/C:/Users/hi/Documents/New%20project/database/seeders/demo_seed.sql)

Versi aplikasi saat ini memakai repository data demo berbasis PHP agar UI dan flow bisa langsung dicoba tanpa setup database lebih dulu. Saat akan dipindah ke MySQL/MariaDB penuh, struktur tabel dan seed awal sudah disiapkan.

## Catatan Implementasi

- Session disimpan ke `storage/cache` agar aman dipakai pada environment lokal ini.
- Permission staff dimuat dari default config lalu dapat dioverride admin dari halaman Settings.
- Booking baru dari portal publik masuk sebagai `pending`, sedangkan booking dari internal langsung `confirmed`.
- Seluruh styling memakai tema soft blue clean minimal dengan layout dashboard yang terinspirasi Zenwell.
