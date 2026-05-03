<?php

declare(strict_types=1);

return [
    'catalog' => [
        'dashboard' => [
            'label' => 'Dashboard',
            'permissions' => [
                'dashboard.view' => 'Lihat KPI, grafik, dan insight bisnis',
            ],
        ],
        'calendar' => [
            'label' => 'Kalender',
            'permissions' => [
                'calendar.view' => 'Lihat agenda dan jadwal staf',
                'calendar.create' => 'Buat dan edit booking',
                'calendar.block' => 'Blokir waktu staff',
            ],
        ],
        'sales' => [
            'label' => 'Penjualan',
            'permissions' => [
                'sales.view' => 'Lihat transaksi, invoice, dan cash drawer',
                'sales.checkout' => 'Proses checkout POS',
                'sales.export' => 'Export dan print laporan penjualan',
            ],
        ],
        'customers' => [
            'label' => 'Pelanggan',
            'permissions' => [
                'customers.view' => 'Lihat data pelanggan',
                'customers.edit' => 'Tambah, edit, dan merge pelanggan',
            ],
        ],
        'staff' => [
            'label' => 'Staf',
            'permissions' => [
                'staff.view' => 'Lihat daftar staf, shift, dan attendance',
                'staff.manage' => 'Kelola shift, komisi, dan skill staf',
            ],
        ],
        'services' => [
            'label' => 'Layanan',
            'permissions' => [
                'services.view' => 'Lihat katalog layanan dan paket',
                'services.manage' => 'Kelola harga, varian, dan assignment staf',
            ],
        ],
        'inventory' => [
            'label' => 'Inventori',
            'permissions' => [
                'inventory.view' => 'Lihat stok, supplier, dan pergerakan barang',
                'inventory.manage' => 'Buat purchase, opname, dan adjustment stok',
            ],
        ],
        'vouchers' => [
            'label' => 'Voucher',
            'permissions' => [
                'vouchers.view' => 'Lihat voucher dan redemption log',
                'vouchers.manage' => 'Buat dan ubah voucher',
            ],
        ],
        'analytics' => [
            'label' => 'Analitik',
            'permissions' => [
                'analytics.view' => 'Lihat laporan keuangan dan performa',
            ],
        ],
        'reviews' => [
            'label' => 'Review & Logs',
            'permissions' => [
                'reviews.view' => 'Lihat review pelanggan dan audit log',
            ],
        ],
        'settings' => [
            'label' => 'Settings',
            'permissions' => [
                'settings.view' => 'Lihat pengaturan bisnis',
                'settings.permissions' => 'Atur role dan akses staff',
            ],
        ],
    ],
    'defaults' => [
        'staff' => [
            'dashboard.view',
            'calendar.view',
            'calendar.create',
            'sales.view',
            'customers.view',
            'staff.view',
            'services.view',
            'vouchers.view',
            'reviews.view',
        ],
        'customer' => [
            'customer.portal',
        ],
    ],
];
