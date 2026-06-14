<?php

declare(strict_types=1);

return [
    'name' => 'StarStyle',
    'tagline' => 'Salon Management System',
    'description' => 'Platform reservasi dan operasional salon modern dengan dashboard, POS, CRM, inventory, analytics, serta kontrol akses staf.',
    'timezone' => getenv('APP_TIMEZONE') ?: 'Asia/Bangkok',
    // Switch data source:
    // - demo: in-memory seeded data + session writes (default)
    // - db:   MySQL/MariaDB via PDO (XAMPP)
    'data_source' => getenv('APP_DATA_SOURCE') ?: 'db',
    'db' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        // NOTE: schema.sql uses "starstyle" as database name.
        // If you created a different db name in phpMyAdmin (eg: db_starstyle),
        // update this value to match.
        'database' => getenv('DB_DATABASE') ?: 'starstyle',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    ],
    'theme' => [
        'primary' => '#63b4ff',
        'secondary' => '#dff1ff',
        'accent' => '#4f84ff',
        'dark' => '#17324d',
        'soft' => '#f5faff',
    ],
    'business' => [
        'name' => getenv('BUSINESS_NAME') ?: 'StarStyle Salon',
        'city' => getenv('BUSINESS_CITY') ?: 'Bangkok',
        'hotline' => getenv('BUSINESS_HOTLINE') ?: '+66 2 555 0101',
        'email' => getenv('BUSINESS_EMAIL') ?: 'hello@starstyle.test',
        'hours' => getenv('BUSINESS_HOURS') ?: '09:00 - 20:00',
        'address' => getenv('BUSINESS_ADDRESS') ?: 'Silom Creative Avenue, Bangkok',
    ],
    'internal_nav' => [
        ['label' => 'Beranda', 'icon' => 'house-door', 'path' => '/dashboard', 'permission' => 'dashboard.view'],
        ['label' => 'Kalender', 'icon' => 'calendar3', 'path' => '/calendar', 'permission' => 'calendar.view'],
        ['label' => 'Penjualan', 'icon' => 'receipt', 'path' => '/sales', 'permission' => 'sales.view'],
        ['label' => 'Pelanggan', 'icon' => 'emoji-smile', 'path' => '/customers', 'permission' => 'customers.view'],
        ['label' => 'Staf', 'icon' => 'people', 'path' => '/staff', 'permission' => 'staff.view'],
        ['label' => 'Layanan', 'icon' => 'scissors', 'path' => '/services', 'permission' => 'services.view'],
        ['label' => 'Inventory', 'icon' => 'box-seam', 'path' => '/inventory', 'permission' => 'inventory.view'],
        ['label' => 'Voucher', 'icon' => 'ticket-perforated', 'path' => '/vouchers', 'permission' => 'vouchers.view'],
        ['label' => 'Analitik', 'icon' => 'graph-up-arrow', 'path' => '/analytics', 'permission' => 'analytics.view'],
        ['label' => 'Review & Logs', 'icon' => 'chat-left-heart', 'path' => '/reviews', 'permission' => 'reviews.view'],
        ['label' => 'Pengaturan', 'icon' => 'gear', 'path' => '/settings', 'permission' => 'settings.view'],
    ],
    'public_nav' => [
        ['label' => 'Beranda', 'path' => '/'],
        ['label' => 'Layanan', 'path' => '/services-catalog'],
        ['label' => 'Booking', 'path' => '/booking'],
    ],
    'date_presets' => [
        '7d' => '7 hari terakhir',
        '30d' => '30 hari terakhir',
        '90d' => '90 hari terakhir',
    ],
];
