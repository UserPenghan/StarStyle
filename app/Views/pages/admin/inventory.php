<?php
$statusTone = static function (string $status): string {
    $normalized = strtolower($status);
    if (str_contains($normalized, 'review') || str_contains($normalized, 'meninjau')) {
        return 'accent';
    }
    if (str_contains($normalized, 'draft') || str_contains($normalized, 'review') || str_contains($normalized, 'rendah') || str_contains($normalized, 'pending') || str_contains($normalized, 'perhitungan')) {
        return 'attention';
    }
    if (str_contains($normalized, 'komplit') || str_contains($normalized, 'complete') || str_contains($normalized, 'aman')) {
        return 'safe';
    }
    if (str_contains($normalized, 'dibatal') || str_contains($normalized, 'cancel')) {
        return 'muted';
    }
    if (str_contains($normalized, 'dikirim') || str_contains($normalized, 'proses') || str_contains($normalized, 'baru')) {
        return 'accent';
    }
    if (str_contains($normalized, 'nonaktif') || str_contains($normalized, 'arsip')) {
        return 'muted';
    }
    return 'safe';
};

$purchaseStatusClass = static function (string $status): string {
    $normalized = strtolower($status);
    if ($normalized === 'ordered') {
        return 'ordered';
    }
    if ($normalized === 'received') {
        return 'received';
    }
    if ($normalized === 'cancelled') {
        return 'cancelled';
    }
    return 'ordered';
};

$productRows = [];
$categoryRows = [];
$brandRows = [];
$supplierRows = [];

$supplierMeta = $supplierMeta ?? [
    'PT Glow Source' => ['contact' => 'Ayu Permata', 'lead_time' => '2 hari', 'status' => 'Aktif', 'address' => 'Silom Trade Center, Bangkok'],
    'PT Groom Lab' => ['contact' => 'Rendra Yusuf', 'lead_time' => '3 hari', 'status' => 'Prioritas', 'address' => 'Sukhumvit Distribution Hub, Bangkok'],
    'PT Color Boutique' => ['contact' => 'Mira Valencia', 'lead_time' => '4 hari', 'status' => 'Aktif', 'address' => 'Bang Rak Creative Warehouse, Bangkok'],
];

$categoryMeta = [
    'Hair Care' => ['code' => 'HC-01', 'shelf' => 'Rak A1', 'status' => 'Aman'],
    'Styling' => ['code' => 'ST-02', 'shelf' => 'Rak B2', 'status' => 'Rendah'],
    'Nail' => ['code' => 'NL-03', 'shelf' => 'Rak C1', 'status' => 'Aman'],
];

$brandMeta = [
    'StarStyle Pro' => ['code' => 'BR-001', 'status' => 'Aktif'],
    'Form Men' => ['code' => 'BR-002', 'status' => 'Aktif'],
    'Luna Nails' => ['code' => 'BR-003', 'status' => 'Aktif'],
];

$productTypeMeta = [
    'Silk Repair Serum' => [
        'type' => 'both',
        'label' => 'Retail & Konsumsi',
        'status' => 'Aktif',
        'retail_unit' => 'botol',
        'consumption_unit' => 'ml',
        'used_in' => ['Keratin Repair', 'Glossy Balayage', 'Scalp Detox', 'Color Lock Treatment', 'Hydra Repair Mask'],
    ],
    'Ocean Mist Spray' => [
        'type' => 'retail',
        'label' => 'Retail',
        'status' => 'Nonaktif',
        'retail_unit' => 'botol',
        'consumption_unit' => null,
        'used_in' => [],
    ],
    'Volume Clay' => [
        'type' => 'both',
        'label' => 'Retail & Konsumsi',
        'status' => 'Aktif',
        'retail_unit' => 'jar',
        'consumption_unit' => 'gram',
        'used_in' => ['Signature Haircut', 'Men Styling Finish', 'Texture Boost'],
    ],
    'Nail Gloss Kit' => [
        'type' => 'consumption',
        'label' => 'Konsumsi',
        'status' => 'Aktif',
        'retail_unit' => null,
        'consumption_unit' => 'sachet',
        'used_in' => ['Signature Gel Nails', 'Nail Repair Overlay', 'Cuticle Spa', 'Gloss Finish'],
    ],
];

foreach ($products as $index => $product) {
    $code = sprintf('INV-%03d', $index + 1);
    $typeMeta = $productTypeMeta[$product['name']] ?? [
        'type' => 'retail',
        'label' => 'Retail',
        'status' => 'Aktif',
        'retail_unit' => 'pcs',
        'consumption_unit' => null,
        'used_in' => [],
    ];
    $usedIn = array_values($typeMeta['used_in']);
    $productRows[] = [
        'name' => $product['name'],
        'code' => $code,
        'brand' => (string) $product['brand'],
        'category' => (string) $product['category'],
        'supplier' => (string) $product['supplier'],
        'type' => (string) $typeMeta['type'],
        'type_label' => (string) $typeMeta['label'],
        'unit_retail' => $typeMeta['retail_unit'],
        'unit_consumption' => $typeMeta['consumption_unit'],
        'unit_all' => $typeMeta['type'] === 'both'
            ? trim(sprintf('%s / %s', (string) $typeMeta['retail_unit'], (string) $typeMeta['consumption_unit']), ' /')
            : (string) ($typeMeta['retail_unit'] ?: $typeMeta['consumption_unit']),
        'used_in' => $usedIn,
        'used_in_count' => count($usedIn),
        'price' => money($product['price']),
        'qty' => (int) $product['stock'],
        'status' => (string) $typeMeta['status'],
    ];

    $categoryName = (string) $product['category'];
    if (!isset($categoryRows[$categoryName])) {
        $categoryRows[$categoryName] = [
            'name' => $categoryName,
            'code' => $categoryMeta[$categoryName]['code'] ?? 'CAT-000',
            'shelf' => $categoryMeta[$categoryName]['shelf'] ?? 'Rak Utama',
            'product_count' => 0,
            'status' => $categoryMeta[$categoryName]['status'] ?? 'Aman',
        ];
    }
    $categoryRows[$categoryName]['product_count']++;

    $brandName = (string) $product['brand'];
    if (!isset($brandRows[$brandName])) {
        $brandRows[$brandName] = [
            'name' => $brandName,
            'code' => $brandMeta[$brandName]['code'] ?? 'BR-000',
            'avg_price' => 0,
            'price_total' => 0,
            'product_count' => 0,
            'status' => $brandMeta[$brandName]['status'] ?? 'Aktif',
        ];
    }
    $brandRows[$brandName]['price_total'] += (int) $product['price'];
    $brandRows[$brandName]['product_count']++;

    $supplierName = (string) $product['supplier'];
    if (!isset($supplierRows[$supplierName])) {
        $supplierRows[$supplierName] = [
            'name' => $supplierName,
            'contact' => $supplierMeta[$supplierName]['contact'] ?? 'Tim Supplier',
            'lead_time' => $supplierMeta[$supplierName]['lead_time'] ?? '3 hari',
            'status' => $supplierMeta[$supplierName]['status'] ?? 'Aktif',
            'address' => $supplierMeta[$supplierName]['address'] ?? 'Bangkok',
            'product_count' => 0,
        ];
    }
    $supplierRows[$supplierName]['product_count']++;
}

foreach ($brandRows as &$brandRow) {
    $brandRow['avg_price'] = money((int) round($brandRow['price_total'] / max(1, $brandRow['product_count'])));
}
unset($brandRow);

if (!empty($inventoryBrands)) {
    $brandRows = [];
    foreach ($inventoryBrands as $brand) {
        $brandRows[(string) $brand['name']] = [
            'id' => (int) $brand['id'],
            'name' => (string) $brand['name'],
        ];
    }
}

if (!empty($inventoryCategories)) {
    $categoryRows = [];
    foreach ($inventoryCategories as $category) {
        $categoryRows[(string) $category['name']] = [
            'id' => (int) $category['id'],
            'name' => (string) $category['name'],
        ];
    }
}

if (!empty($inventorySuppliers)) {
    $supplierRows = [];
    foreach ($inventorySuppliers as $supplier) {
        $supplierRows[(string) $supplier['name']] = [
            'id' => (int) $supplier['id'],
            'name' => (string) $supplier['name'],
            'description' => (string) ($supplier['description'] ?? ''),
            'contact' => (string) ($supplier['contact'] ?? ''),
            'email' => (string) ($supplier['email'] ?? ''),
            'phone' => (string) ($supplier['phone'] ?? ''),
            'website' => (string) ($supplier['website'] ?? ''),
            'address' => (string) ($supplier['address'] ?? ''),
            'city' => (string) ($supplier['city'] ?? ''),
            'country' => (string) ($supplier['country'] ?? ''),
            'postal' => (string) ($supplier['postal'] ?? ''),
        ];
    }
}

$productBrands = array_values(array_keys($brandRows));
sort($productBrands, SORT_NATURAL | SORT_FLAG_CASE);
$productCategories = array_values(array_keys($categoryRows));
sort($productCategories, SORT_NATURAL | SORT_FLAG_CASE);
$productSuppliers = array_values(array_keys($supplierRows));
sort($productSuppliers, SORT_NATURAL | SORT_FLAG_CASE);
$inventoryLocations = $inventoryLocations ?? [[
    'id' => 1,
    'name' => 'Star Salon',
    'address' => 'Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur',
]];

$purchaseRows = !empty($purchaseRows) ? $purchaseRows : [
    [
        'document' => 'P000002',
        'created_at' => '28 Apr 2026',
        'type' => 'Order',
        'supplier' => 'Wardah',
        'location' => 'Star Salon',
        'total' => money(125000),
        'status' => 'Ordered',
        'note' => 'test',
        'ordered_at' => '28 Apr 2026',
        'items' => [
            ['name' => 'Hair Serum Wardah - 500ml', 'qty' => 5, 'price' => 25000, 'total' => 125000],
        ],
        'receiving_logs' => [],
    ],
    [
        'document' => 'P000001',
        'created_at' => '28 Apr 2026',
        'type' => 'Order',
        'supplier' => 'Wardah',
        'location' => 'Star Salon',
        'total' => money(250000),
        'status' => 'Received',
        'note' => '',
        'ordered_at' => '28 Apr 2026',
        'items' => [
            ['name' => 'Hair Serum Wardah - 500ml', 'qty' => 10, 'price' => 25000, 'total' => 250000],
        ],
        'receiving_logs' => [
            ['product' => 'Hair Serum Wardah - 500ml', 'qty' => 10, 'date' => '28 Apr 2026 18:30:10', 'price' => 25000, 'total' => 250000],
        ],
    ],
];

$purchaseStatuses = [];
$purchaseSuppliers = [];

foreach ($purchaseRows as $purchaseRow) {
    $purchaseStatuses[] = (string) $purchaseRow['status'];
    $purchaseSuppliers[] = (string) $purchaseRow['supplier'];
}

$purchaseStatuses = array_values(array_unique($purchaseStatuses));
sort($purchaseStatuses, SORT_NATURAL | SORT_FLAG_CASE);
$purchaseSuppliers = array_values(array_unique($purchaseSuppliers));
sort($purchaseSuppliers, SORT_NATURAL | SORT_FLAG_CASE);

$opnameRows = !empty($opnameRows) ? $opnameRows : [
    ['name' => 'Hair Care Count Cycle', 'location' => 'Star Salon', 'started_at' => '2026-04-20', 'ended_at' => '2026-04-20', 'status' => 'Completed'],
    ['name' => 'Styling Rack Recount', 'location' => 'Star Salon', 'started_at' => '2026-04-22', 'ended_at' => '2026-04-23', 'status' => 'Meninjau'],
    ['name' => 'Nail Corner Audit', 'location' => 'Star Salon', 'started_at' => '2026-04-24', 'ended_at' => '2026-04-25', 'status' => 'Perhitungan'],
    ['name' => 'Retail Counter Spot Check', 'location' => 'Star Salon', 'started_at' => '2026-04-26', 'ended_at' => '2026-04-26', 'status' => 'Cancelled'],
];

$opnameDetailProducts = !empty($opnameDetailProducts) ? $opnameDetailProducts : [
    ['name' => 'Hair Serum Wardah - Per Pump', 'code' => '-', 'sku' => '-', 'expected' => 7],
    ['name' => 'Hair Serum Wardah - 500ml', 'code' => 'W11', 'sku' => '10', 'expected' => 10],
];

$inventoryToday = new DateTimeImmutable('today');
$opnameRangeStart = $inventoryToday->modify('-6 days');
$opnameRangeEnd = $inventoryToday;
$opnameStatuses = ['All Status', 'Perhitungan', 'Meninjau', 'Completed', 'Cancelled'];

$inventoryTabs = [
    ['key' => 'products', 'label' => 'Produk'],
    ['key' => 'purchases', 'label' => 'Pesanan'],
    ['key' => 'opname', 'label' => 'Stok Opname'],
    ['key' => 'master', 'label' => 'Master Data'],
];
?>

<section class="inventory-shell js-inventory-shell">
    <div class="inventory-tabs" role="tablist" aria-label="Tab inventory">
        <?php foreach ($inventoryTabs as $index => $tab): ?>
            <button
                class="inventory-tab<?= $index === 0 ? ' is-active' : '' ?>"
                type="button"
                data-inventory-tab="<?= e($tab['key']) ?>"
                aria-selected="<?= $index === 0 ? 'true' : 'false' ?>"
            ><?= e($tab['label']) ?></button>
        <?php endforeach; ?>
    </div>

    <div class="inventory-panel is-active" data-inventory-panel="products">
        <div class="inventory-toolbar inventory-toolbar--products">
            <div class="dropdown">
                <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item is-active" type="button">Star Salon</button>
                </div>
            </div>

            <button class="dashboard-filter js-inventory-product-import" type="button" data-bs-toggle="modal" data-bs-target="#inventoryImportModal">
                <span>Import</span>
            </button>

            <div class="dropdown">
                <button class="dashboard-filter ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>Export</span>
                    <i class="bi bi-caret-down-fill"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item js-inventory-export" type="button" data-export="pdf">PDF</button>
                    <button class="dropdown-item js-inventory-export" type="button" data-export="xls">XLS</button>
                    <button class="dropdown-item js-inventory-export" type="button" data-export="xlsx">XLSX</button>
                    <button class="dropdown-item js-inventory-export" type="button" data-export="csv">CSV</button>
                </div>
            </div>

            <button class="dashboard-filter js-inventory-filter-open" type="button">
                <span>Filter</span>
            </button>

            <label class="sales-search-field inventory-search-field">
                <input class="js-inventory-search" type="search" autocomplete="off" aria-label="Cari produk" placeholder="Ketik kata kunci">
                <i class="bi bi-search"></i>
            </label>
        </div>

        <div class="inventory-table-card">
            <div class="inventory-table-wrap">
                <table class="customers-table inventory-table inventory-table--products">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kode Barang</th>
                            <th>Harga</th>
                            <th>QTY Nyata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productRows as $row): ?>
                            <tr
                                data-inventory-row
                                data-product-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                data-name="<?= e($row['name']) ?>"
                                data-code="<?= e($row['code']) ?>"
                                data-brand="<?= e($row['brand']) ?>"
                                data-category="<?= e($row['category']) ?>"
                                data-supplier="<?= e($row['supplier']) ?>"
                                data-type="<?= e($row['type']) ?>"
                                data-type-label="<?= e($row['type_label']) ?>"
                                data-unit-retail="<?= e((string) ($row['unit_retail'] ?? '')) ?>"
                                data-unit-consumption="<?= e((string) ($row['unit_consumption'] ?? '')) ?>"
                                data-unit-all="<?= e((string) ($row['unit_all'] ?? '')) ?>"
                                data-used-in="<?= e(implode('|', $row['used_in'])) ?>"
                                data-used-in-count="<?= e((string) $row['used_in_count']) ?>"
                                data-price="<?= e($row['price']) ?>"
                                data-qty="<?= e((string) $row['qty']) ?>"
                                data-status="<?= e($row['status']) ?>"
                                data-stock-state="<?= $row['qty'] > 0 ? 'available' : 'empty' ?>"
                            >
                                <td class="inventory-name-cell">
                                    <span class="inventory-row-icon"><i class="bi bi-bag"></i></span>
                                    <button class="inventory-name-button js-inventory-product-open" type="button"><?= e($row['name']) ?></button>
                                </td>
                                <td><?= e($row['code']) ?></td>
                                <td><?= e($row['price']) ?></td>
                                <td><?= e((string) $row['qty']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="customers-table-footer inventory-table-footer">
                <div class="js-inventory-total">Total <?= e((string) count($productRows)) ?></div>
                <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                <span class="customers-pagination-current">1</span>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                <div>Go to</div>
                <button class="dashboard-filter customers-mini-input" type="button">1</button>
                <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
            </div>
        </div>
    </div>

    <div class="inventory-panel" data-inventory-panel="purchases" hidden>
        <div class="inventory-toolbar inventory-toolbar--purchases">
            <div class="dropdown">
                <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item is-active" type="button">Star Salon</button>
                </div>
            </div>

            <div class="dropdown">
                <button class="dashboard-filter dashboard-filter--wide ss-dropdown-toggle js-inventory-purchase-location-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-location-value="">
                    <span>Pilih lokasi tujuan</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item js-inventory-purchase-location-option is-active" type="button" data-location-value="">Semua Lokasi</button>
                    <?php foreach ($inventoryLocations as $location): ?>
                        <button class="dropdown-item js-inventory-purchase-location-option" type="button" data-location-value="<?= e($location['name']) ?>"><?= e($location['name']) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dropdown">
                <button class="dashboard-filter ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span>Unduh</span>
                    <i class="bi bi-caret-down-fill"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item js-inventory-purchase-export" type="button" data-export="pdf">PDF</button>
                    <button class="dropdown-item js-inventory-purchase-export" type="button" data-export="xls">XLS</button>
                    <button class="dropdown-item js-inventory-purchase-export" type="button" data-export="xlsx">XLSX</button>
                    <button class="dropdown-item js-inventory-purchase-export" type="button" data-export="csv">CSV</button>
                </div>
            </div>

            <button class="dashboard-filter js-inventory-purchase-filter-open" type="button">
                <span>Filter</span>
            </button>

            <label class="sales-search-field inventory-search-field">
                <input class="js-inventory-purchase-search" type="search" autocomplete="off" aria-label="Cari pesanan" placeholder="Cari berdasarkan order...">
                <i class="bi bi-search"></i>
            </label>
        </div>

        <div class="inventory-table-card">
            <div class="inventory-table-wrap">
                <table class="customers-table inventory-table inventory-table--purchases">
                    <thead>
                        <tr>
                            <th>Order No.</th>
                            <th>Dibuat Pada</th>
                            <th>Tipe</th>
                            <th>Pemasok</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th>Total Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchaseRows as $row): ?>
                            <tr
                                data-inventory-purchase-row
                                data-purchase-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                data-order="<?= e($row['document']) ?>"
                                data-status="<?= e($row['status']) ?>"
                                data-supplier="<?= e($row['supplier']) ?>"
                                data-location="<?= e($row['location']) ?>"
                                data-created-at="<?= e($row['created_at']) ?>"
                                data-type="<?= e($row['type']) ?>"
                                data-total="<?= e($row['total']) ?>"
                                data-note="<?= e($row['note']) ?>"
                                data-items="<?= e(json_encode($row['items'], JSON_UNESCAPED_UNICODE)) ?>"
                                data-receiving-logs="<?= e(json_encode($row['receiving_logs'], JSON_UNESCAPED_UNICODE)) ?>"
                            >
                                <td class="inventory-name-cell">
                                    <span class="inventory-row-icon"><i class="bi bi-box-seam"></i></span>
                                    <strong><?= e($row['document']) ?></strong>
                                </td>
                                <td><?= e($row['created_at']) ?></td>
                                <td><?= e($row['type']) ?></td>
                                <td><?= e($row['supplier']) ?></td>
                                <td><?= e($row['location']) ?></td>
                                <td><span class="inventory-status inventory-status--purchase-<?= e($purchaseStatusClass($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                                <td><?= e($row['total']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="customers-table-footer inventory-table-footer">
                <div class="js-inventory-purchase-total">Total <?= e((string) count($purchaseRows)) ?></div>
                <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                <span class="customers-pagination-current">1</span>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                <div>Go to</div>
                <button class="dashboard-filter customers-mini-input" type="button">1</button>
                <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
            </div>
        </div>
    </div>

    <div class="inventory-panel" data-inventory-panel="opname" hidden>
        <div class="inventory-toolbar inventory-toolbar--opname">
            <div class="dropdown">
                <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu">
                    <button class="dropdown-item is-active" type="button">Star Salon</button>
                </div>
            </div>

            <div class="dropdown inventory-status-dropdown">
                <button class="dashboard-filter ss-dropdown-toggle js-inventory-opname-status-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-status-value="">
                    <span>Status</span>
                    <i class="bi bi-caret-down-fill"></i>
                </button>
                <div class="dropdown-menu ss-dropdown-menu inventory-status-menu">
                    <?php foreach ($opnameStatuses as $index => $status): ?>
                        <button
                            class="dropdown-item js-inventory-opname-status-option<?= $index === 0 ? ' is-active' : '' ?>"
                            type="button"
                            data-status-value="<?= $status === 'All Status' ? '' : e($status) ?>"
                        ><?= e($status) ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="inventory-toolbar__spacer"></div>

            <button
                class="dashboard-filter dashboard-filter--wide inventory-date-trigger js-inventory-opname-range"
                type="button"
                data-bs-toggle="modal"
                data-bs-target="#inventoryOpnameDateFilterModal"
            >
                <span class="inventory-date-trigger__content">
                    <strong class="js-inventory-opname-range-label">7 hari sebelumnya</strong>
                    <small class="js-inventory-opname-range-values"><?= e($opnameRangeStart->format('d M Y')) ?> - <?= e($opnameRangeEnd->format('d M Y')) ?></small>
                </span>
            </button>

            <label class="sales-search-field inventory-search-field">
                <input class="js-inventory-opname-search" type="search" autocomplete="off" aria-label="Cari stok opname" placeholder="Cari...">
                <i class="bi bi-search"></i>
            </label>
        </div>

        <div class="inventory-table-card">
            <div class="inventory-table-wrap">
                <table class="customers-table inventory-table inventory-table--opname">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Lokasi</th>
                            <th>Dimulai Pada</th>
                            <th>Selesai Pada</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="js-inventory-opname-body">
                        <?php foreach ($opnameRows as $row): ?>
                            <tr
                                data-inventory-opname-row
                                data-opname-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                data-name="<?= e($row['name']) ?>"
                                data-location="<?= e($row['location']) ?>"
                                data-status="<?= e($row['status']) ?>"
                                data-start="<?= e($row['started_at']) ?>"
                                data-end="<?= e($row['ended_at']) ?>"
                                data-note="<?= e((string) ($row['note'] ?? '')) ?>"
                                data-cancel-note="<?= e((string) ($row['cancelled_note'] ?? '')) ?>"
                                data-cancelled-by="<?= e((string) ($row['cancelled_by'] ?? '')) ?>"
                                data-started-by="<?= e((string) ($row['started_by'] ?? 'Rayhan Doni Pramana')) ?>"
                                data-review-items="<?= e(json_encode($row['items'] ?? [], JSON_UNESCAPED_UNICODE)) ?>"
                            >
                                <td class="inventory-name-cell">
                                    <span class="inventory-row-icon"><i class="bi bi-clipboard2-check"></i></span>
                                    <strong><?= e($row['name']) ?></strong>
                                </td>
                                <td><?= e($row['location']) ?></td>
                                <td><?= e($row['started_at']) ?></td>
                                <td><?= e($row['ended_at']) ?></td>
                                <td><span class="inventory-status inventory-status--<?= e($statusTone($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="customers-table-footer inventory-table-footer">
                <div class="js-inventory-opname-total">Total <?= e((string) count($opnameRows)) ?></div>
                <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                <span class="customers-pagination-current">1</span>
                <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                <div>Go to</div>
                <button class="dashboard-filter customers-mini-input" type="button">1</button>
                <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
            </div>
        </div>
    </div>

    <div class="inventory-panel" data-inventory-panel="master" hidden>
        <div class="inventory-master-tabs" role="tablist" aria-label="Master data inventory">
            <button class="inventory-master-tab is-active" type="button" data-inventory-master-tab="brands" aria-selected="true">Merk</button>
            <button class="inventory-master-tab" type="button" data-inventory-master-tab="categories" aria-selected="false">Kategori</button>
            <button class="inventory-master-tab" type="button" data-inventory-master-tab="suppliers" aria-selected="false">Supplier</button>
        </div>

        <div class="inventory-master-panel is-active" data-inventory-master-panel="brands">
            <div class="inventory-toolbar inventory-toolbar--simple">
                <label class="sales-search-field inventory-search-field">
                    <input class="js-inventory-brand-search" type="search" autocomplete="off" aria-label="Cari merk" placeholder="Ketik kata kunci">
                    <i class="bi bi-search"></i>
                </label>
            </div>

            <div class="inventory-table-card">
                <div class="inventory-table-wrap">
                    <table class="customers-table inventory-table inventory-table--simple">
                        <thead>
                            <tr>
                                <th>Nama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($brandRows as $row): ?>
                                <tr
                                    data-inventory-brand-row
                                    data-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                    data-name="<?= e($row['name']) ?>"
                                >
                                    <td class="inventory-simple-cell"><?= e($row['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="customers-table-footer inventory-table-footer">
                    <div class="js-inventory-brand-total">Total <?= e((string) count($brandRows)) ?></div>
                    <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                    <span class="customers-pagination-current">1</span>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                    <div>Go to</div>
                    <button class="dashboard-filter customers-mini-input" type="button">1</button>
                    <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </div>

        <div class="inventory-master-panel" data-inventory-master-panel="categories" hidden>
            <div class="inventory-toolbar inventory-toolbar--simple">
                <label class="sales-search-field inventory-search-field">
                    <input class="js-inventory-category-search" type="search" autocomplete="off" aria-label="Cari kategori" placeholder="Ketik kata kunci">
                    <i class="bi bi-search"></i>
                </label>
            </div>

            <div class="inventory-table-card">
                <div class="inventory-table-wrap">
                    <table class="customers-table inventory-table inventory-table--simple">
                        <thead>
                            <tr>
                                <th>Nama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryRows as $row): ?>
                                <tr
                                    data-inventory-category-row
                                    data-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                    data-name="<?= e($row['name']) ?>"
                                >
                                    <td class="inventory-simple-cell"><?= e($row['name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="customers-table-footer inventory-table-footer">
                    <div class="js-inventory-category-total">Total <?= e((string) count($categoryRows)) ?></div>
                    <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                    <span class="customers-pagination-current">1</span>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                    <div>Go to</div>
                    <button class="dashboard-filter customers-mini-input" type="button">1</button>
                    <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </div>

        <div class="inventory-master-panel" data-inventory-master-panel="suppliers" hidden>
            <div class="inventory-toolbar inventory-toolbar--simple">
                <label class="sales-search-field inventory-search-field">
                    <input class="js-inventory-supplier-search" type="search" autocomplete="off" aria-label="Cari supplier" placeholder="Ketik kata kunci">
                    <i class="bi bi-search"></i>
                </label>
            </div>

            <div class="inventory-table-card">
                <div class="inventory-table-wrap">
                    <table class="customers-table inventory-table inventory-table--suppliers">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Kontak</th>
                                <th>Alamat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($supplierRows as $row): ?>
                                <tr
                                    data-inventory-supplier-row
                                    data-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                    data-name="<?= e($row['name']) ?>"
                                    data-description="<?= e((string) ($row['description'] ?? '')) ?>"
                                    data-contact="<?= e($row['contact']) ?>"
                                    data-email="<?= e((string) ($row['email'] ?? '')) ?>"
                                    data-phone="<?= e((string) ($row['phone'] ?? '')) ?>"
                                    data-website="<?= e((string) ($row['website'] ?? '')) ?>"
                                    data-address="<?= e($row['address']) ?>"
                                    data-city="<?= e((string) ($row['city'] ?? 'Bangkok')) ?>"
                                    data-country="<?= e((string) ($row['country'] ?? 'Thailand')) ?>"
                                    data-postal="<?= e((string) ($row['postal'] ?? '')) ?>"
                                >
                                    <td class="inventory-simple-cell"><?= e($row['name']) ?></td>
                                    <td><?= e($row['contact']) ?></td>
                                    <td><?= e($row['address']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="customers-table-footer inventory-table-footer">
                    <div class="js-inventory-supplier-total">Total <?= e((string) count($supplierRows)) ?></div>
                    <button class="dashboard-filter customers-mini-filter" type="button"><span>20/page</span><i class="bi bi-chevron-down"></i></button>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                    <span class="customers-pagination-current">1</span>
                    <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                    <div>Go to</div>
                    <button class="dashboard-filter customers-mini-input" type="button">1</button>
                    <button class="dashboard-filter customers-mini-input inventory-footer-caret" type="button"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </div>
    </div>

    <button class="inventory-fab js-inventory-fab" type="button" aria-label="Tambah data inventory" aria-expanded="false">
        <i class="bi bi-plus-lg"></i>
    </button>

    <div class="inventory-fab-menu js-inventory-fab-menu" hidden>
        <button class="inventory-fab-menu__item js-inventory-purchase-action" type="button" data-purchase-action="pesanan">
            <i class="bi bi-plus-lg"></i>
            <span>Pesanan</span>
        </button>
        <button class="inventory-fab-menu__item js-inventory-purchase-action" type="button" data-purchase-action="transfer">
            <i class="bi bi-arrow-left-right"></i>
            <span>Transfer</span>
        </button>
        <button class="inventory-fab-menu__item inventory-fab-menu__item--close js-inventory-fab-close" type="button">
            <i class="bi bi-x-lg"></i>
            <span>Tutup</span>
        </button>
    </div>
</section>

<div class="modal fade" id="inventoryProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen inventory-product-dialog">
        <div class="modal-content inventory-product-modal">
            <div class="inventory-product-modal__header">
                <h2 class="js-inventory-product-title">Product Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="inventory-product-modal__tabs js-inventory-product-tabs" role="tablist" aria-label="Tab edit produk" hidden>
                <button class="inventory-product-modal__tab" type="button" data-inventory-product-tab="history" aria-selected="false">Riwayat Stock</button>
                <button class="inventory-product-modal__tab is-active" type="button" data-inventory-product-tab="details" aria-selected="true">Rincian</button>
                <button class="inventory-product-modal__tab" type="button" data-inventory-product-tab="locations" aria-selected="false">Lokasi</button>
            </div>

            <div class="inventory-product-modal__body">
                <div class="inventory-product-modal__panel" data-inventory-product-panel="history" hidden>
                    <section class="inventory-product-history-card">
                        <div class="inventory-product-history-card__main">
                            <div class="inventory-product-history-card__icon">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <div class="inventory-product-history-card__copy">
                                <h3 class="js-inventory-product-history-name">Hair Serum Wardah - Per Pump</h3>
                                <dl class="inventory-product-history-card__stats">
                                    <div>
                                        <dt>Total stock cost</dt>
                                        <dd class="js-inventory-product-history-total-cost">Rp 0</dd>
                                    </div>
                                    <div>
                                        <dt>Average stock cost</dt>
                                        <dd class="js-inventory-product-history-average-cost">Rp 0</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div class="inventory-product-history-card__side">
                            <div class="inventory-product-history-card__location">
                                <div class="inventory-product-history-card__location-dropdown">
                                <button class="inventory-product-history-card__location-btn js-inventory-history-location-toggle" type="button" aria-expanded="false">
                                    <span class="js-inventory-product-history-location">Star Salon (10)</span>
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                                    <div class="inventory-product-history-card__location-menu" hidden>
                                        <button class="js-inventory-history-location-option is-active" type="button" data-history-location="all">Semua Lokasi</button>
                                        <button class="js-inventory-history-location-option" type="button" data-history-location="Star Salon">Star Salon</button>
                                    </div>
                                </div>
                                <div class="inventory-product-history-card__qty">
                                    <i class="bi bi-box-seam"></i>
                                    <strong class="js-inventory-product-history-qty">10</strong>
                                </div>
                            </div>
                            <div class="inventory-product-history-card__actions">
                                <button class="js-inventory-history-stock-increase" type="button">Stok +</button>
                                <button class="js-inventory-history-stock-decrease" type="button">Stok -</button>
                            </div>
                        </div>
                    </section>

                    <section class="inventory-product-history-section">
                        <div class="inventory-product-history-section__head">
                            <h3>Riwayat Stock</h3>
                            <div class="inventory-product-history-section__tools">
                                <button class="inventory-product-history-pill js-inventory-history-range" type="button">
                                    <strong class="js-inventory-history-range-label">30 hari sebelumnya</strong>
                                    <small class="js-inventory-history-range-value">29 Mar 2026 - 28 Apr 2026</small>
                                </button>
                                <button class="inventory-product-history-export js-inventory-history-export" type="button">
                                    <span>Export</span>
                                    <i class="bi bi-caret-down-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div class="inventory-product-history-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Waktu Dan Tanggal</th>
                                        <th>Staff</th>
                                        <th>Lokasi</th>
                                        <th>Aksi</th>
                                        <th>QTY Yang Diatur</th>
                                        <th>Biaya</th>
                                        <th>QTY Nyata</th>
                                    </tr>
                                </thead>
                                <tbody class="js-inventory-history-body">
                                    <tr>
                                        <td>28 April 2026<br>15:39:51</td>
                                        <td>Rayhan Doni<br>Pramana</td>
                                        <td>Star Salon</td>
                                        <td>New stock</td>
                                        <td class="js-inventory-product-history-row-qty">10.00</td>
                                        <td class="js-inventory-product-history-row-cost">0,00</td>
                                        <td class="js-inventory-product-history-row-real">10.00</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="inventory-product-history-table__footer">
                                <div class="js-inventory-history-total">Total 1</div>
                                <button class="dashboard-filter customers-mini-filter js-inventory-history-page-size" type="button"><span class="js-inventory-history-page-size-label">10/page</span><i class="bi bi-chevron-down"></i></button>
                                <button class="customers-pagination-btn js-inventory-history-prev" type="button"><i class="bi bi-chevron-left"></i></button>
                                <span class="customers-pagination-current js-inventory-history-current">1</span>
                                <button class="customers-pagination-btn js-inventory-history-next" type="button"><i class="bi bi-chevron-right"></i></button>
                                <div>Go to</div>
                                <button class="dashboard-filter customers-mini-input js-inventory-history-goto" type="button">1</button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="inventory-product-modal__panel is-active" data-inventory-product-panel="details">
                    <section class="inventory-product-card">
                        <h3 class="js-inventory-product-section-title">Detail Produk</h3>
                        <div class="inventory-product-detail-grid">
                            <div class="inventory-product-detail-form">
                                <label>
                                    <span>Nama</span>
                                    <input class="form-control customer-input-flat js-inventory-product-name" type="text" placeholder="Masukkan nama produk">
                                </label>

                                <label>
                                    <span>Kategori</span>
                                    <select class="form-select customer-input-flat js-inventory-product-category">
                                        <option value="">Masukkan kata kunci</option>
                                        <?php foreach ($productCategories as $category): ?>
                                            <option value="<?= e($category) ?>"><?= e($category) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label>
                                    <span>Merk</span>
                                    <select class="form-select customer-input-flat js-inventory-product-brand">
                                        <option value="">Pilih</option>
                                        <?php foreach ($productBrands as $brand): ?>
                                            <option value="<?= e($brand) ?>"><?= e($brand) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label>
                                    <span>Deskripsi</span>
                                    <textarea class="form-control customer-input-flat inventory-product-textarea js-inventory-product-description" rows="4" placeholder="Tulis deskripsi produk"></textarea>
                                </label>
                            </div>

                            <div class="inventory-product-photo">
                                <label class="inventory-product-photo__label">Photo</label>
                                <label class="inventory-product-photo__dropzone js-inventory-product-photo-dropzone">
                                    <input type="file" accept="image/*" hidden>
                                    <span class="js-inventory-product-photo-copy">Drop file here or <strong>click to upload</strong></span>
                                </label>
                                <small class="js-inventory-product-photo-help">Use HD Photos (1920 x 1080 px) for best user experience</small>
                            </div>
                        </div>
                    </section>

                    <section class="inventory-product-card">
                        <div class="inventory-product-card__head">
                            <h3>Harga &amp; Stok</h3>
                            <label class="inventory-product-sales-toggle">
                                <span>Muncul di penjualan</span>
                                <span class="account-switch">
                                    <input class="js-inventory-sales-toggle" type="checkbox">
                                    <span></span>
                                </span>
                            </label>
                        </div>

                        <div class="inventory-product-note js-inventory-sales-note">
                            <i class="bi bi-info-circle"></i>
                            <span>Aktifkan untuk menjual produk ini di checkout</span>
                        </div>

                        <div class="inventory-variant-list js-inventory-variant-list"></div>

                        <button class="inventory-variant-add js-inventory-variant-add" type="button">
                            <i class="bi bi-plus-lg"></i>
                            <span>Tambah Varian</span>
                        </button>
                    </section>
                </div>

                <div class="inventory-product-modal__panel" data-inventory-product-panel="locations" hidden>
                    <section class="inventory-product-location-card">
                        <label class="sales-search-field inventory-search-field inventory-product-location-search">
                            <input class="js-inventory-location-search" type="search" autocomplete="off" placeholder="Cari lokasi">
                            <i class="bi bi-search"></i>
                        </label>

                        <div class="inventory-location-list">
                            <label class="inventory-location-item" data-location-label="Semua Lokasi">
                                <span>Semua Lokasi</span>
                                <span class="inventory-location-check">
                                    <input type="checkbox" checked>
                                    <i class="bi bi-check-lg"></i>
                                </span>
                            </label>
                            <?php foreach ($inventoryLocations as $location): ?>
                                <label class="inventory-location-item" data-location-label="<?= e($location['name']) ?>">
                                    <span><?= e($location['name']) ?></span>
                                    <span class="inventory-location-check">
                                        <input type="checkbox" checked>
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

            </div>

            <div class="inventory-stock-adjustment" hidden>
                <div class="inventory-stock-adjustment__backdrop js-inventory-stock-adjustment-close"></div>
                <section class="inventory-stock-adjustment__dialog" aria-modal="true" role="dialog" aria-label="Penyesuaian stok">
                    <header class="inventory-stock-adjustment__header">
                        <h3 class="js-inventory-stock-adjustment-title">Tambah Stok</h3>
                        <button class="inventory-stock-adjustment__close js-inventory-stock-adjustment-close" type="button" aria-label="Tutup">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </header>

                    <div class="inventory-stock-adjustment__summary js-inventory-stock-adjustment-summary">
                        <span class="inventory-stock-adjustment__summary-icon"><i class="bi bi-shop"></i></span>
                        <div class="inventory-stock-adjustment__summary-copy">
                            <small>Stock terakhir pada:</small>
                            <strong class="js-inventory-stock-adjustment-location">Star Salon</strong>
                        </div>
                        <strong class="inventory-stock-adjustment__summary-qty js-inventory-stock-adjustment-current">7</strong>
                    </div>

                    <div class="inventory-stock-adjustment__body">
                        <label class="inventory-stock-adjustment__field">
                            <span class="js-inventory-stock-adjustment-qty-label">QTY Tambah Stok</span>
                            <div class="inventory-stock-adjustment__qtybox">
                                <input class="js-inventory-stock-adjustment-qty" type="text" inputmode="numeric" value="1">
                                <div class="inventory-stock-adjustment__qtyactions">
                                    <button class="js-inventory-stock-adjustment-decrease" type="button" aria-label="Kurangi qty">-</button>
                                    <button class="js-inventory-stock-adjustment-increase" type="button" aria-label="Tambah qty">+</button>
                                </div>
                            </div>
                        </label>

                        <label class="inventory-stock-adjustment__field js-inventory-stock-adjustment-price-field">
                            <span>Supply Price</span>
                            <input class="form-control customer-input-flat js-inventory-stock-adjustment-price" type="text" inputmode="numeric" value="Rp 0,00">
                        </label>

                        <div class="inventory-stock-adjustment__field">
                            <span>Pilih Alasan</span>
                            <div class="inventory-stock-adjustment__reasons js-inventory-stock-adjustment-reasons"></div>
                        </div>

                        <label class="inventory-stock-adjustment__field js-inventory-stock-adjustment-note-field" hidden>
                            <span>Alasan</span>
                            <textarea class="form-control customer-input-flat js-inventory-stock-adjustment-note" rows="2" placeholder="Tulis alasan"></textarea>
                        </label>
                    </div>

                    <footer class="inventory-stock-adjustment__footer">
                        <button class="customer-footer-btn customer-footer-btn--secondary js-inventory-stock-adjustment-close" type="button">Batal</button>
                        <button class="customer-footer-btn customer-footer-btn--primary js-inventory-stock-adjustment-save" type="button">Simpan</button>
                    </footer>
                </section>
            </div>

            <div class="inventory-product-modal__footer">
                <button type="button" class="customer-footer-btn js-inventory-product-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary customer-footer-btn--disabled js-inventory-product-save" disabled>Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryPurchaseOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen inventory-order-dialog">
        <div class="modal-content inventory-order-modal">
            <div class="inventory-order-modal__header">
                <h2>Buat Order</h2>
                <div class="inventory-order-modal__header-actions">
                    <button type="button" class="inventory-order-modal__esc" data-bs-dismiss="modal">Esc</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <div class="inventory-order-stepper">
                <div class="inventory-order-stepper__track"></div>
                <div class="inventory-order-step is-active" data-order-progress="supplier">
                    <span></span>
                    <strong>Supplier</strong>
                </div>
                <div class="inventory-order-step" data-order-progress="location">
                    <span></span>
                    <strong>Lokasi Tujuan</strong>
                </div>
                <div class="inventory-order-step" data-order-progress="order">
                    <span></span>
                    <strong>Buat Order</strong>
                </div>
            </div>

            <div class="inventory-order-modal__body">
                <section class="inventory-order-panel is-active" data-order-panel="supplier">
                    <div class="inventory-order-pick-grid">
                        <?php foreach ($supplierRows as $supplier): ?>
                            <button
                                class="inventory-order-pick-card js-order-supplier-option"
                                type="button"
                                data-supplier-id="<?= e((string) ($supplier['id'] ?? 0)) ?>"
                                data-supplier-name="<?= e($supplier['name']) ?>"
                                data-supplier-contact="<?= e($supplier['contact']) ?>"
                                data-supplier-address="<?= e($supplier['address']) ?>"
                            >
                                <strong><?= e($supplier['name']) ?></strong>
                                <span><?= e($supplier['contact']) ?></span>
                                <small><?= e($supplier['address']) ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="inventory-order-panel" data-order-panel="location" hidden>
                    <div class="inventory-order-pick-grid inventory-order-pick-grid--single">
                        <?php foreach ($inventoryLocations as $location): ?>
                            <button
                                class="inventory-order-pick-card js-order-location-option"
                                type="button"
                                data-location-id="<?= e((string) ($location['id'] ?? 0)) ?>"
                                data-location-name="<?= e($location['name']) ?>"
                                data-location-address="<?= e((string) ($location['address'] ?? '')) ?>"
                            >
                                <strong><?= e($location['name']) ?></strong>
                                <span>Cabang aktif</span>
                                <small><?= e((string) ($location['address'] ?? '')) ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="inventory-order-panel" data-order-panel="order" hidden>
                    <div class="inventory-order-summary">
                        <div class="inventory-order-summary__route">
                            <div class="inventory-order-summary__supplier">
                                <strong class="js-order-selected-supplier">Supplier</strong>
                                <span class="js-order-selected-supplier-meta">Pilih supplier</span>
                            </div>
                            <div class="inventory-order-summary__arrow"><i class="bi bi-arrow-right"></i></div>
                            <div class="inventory-order-summary__location">
                                <strong class="js-order-selected-location">Lokasi</strong>
                                <span class="js-order-selected-location-meta">Pilih lokasi tujuan</span>
                            </div>
                        </div>

                        <div class="inventory-order-summary__footer">
                            <div>
                                <span>Jumlah Pesanan:</span>
                                <strong class="js-order-total">Rp 0</strong>
                            </div>
                            <div class="inventory-order-summary__actions">
                                <button class="inventory-order-note-toggle js-order-note-toggle" type="button">
                                    <i class="bi bi-chat-left"></i>
                                    <span>Catatan</span>
                                </button>
                                <button class="inventory-order-submit js-order-submit" type="button" disabled>Buat Order</button>
                            </div>
                        </div>
                    </div>

                    <textarea class="inventory-order-note js-order-note" rows="3" placeholder="Catatan" hidden></textarea>

                    <div class="inventory-order-searchbar">
                        <div class="inventory-order-searchfield">
                            <input class="js-order-product-search" type="text" autocomplete="off" placeholder="Cari berdasarkan nama dan tekan enter">
                            <div class="inventory-order-suggestions js-order-product-suggestions" hidden></div>
                        </div>
                    </div>

                    <div class="inventory-order-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah Order</th>
                                    <th>Harga Supply</th>
                                    <th>Total Biaya</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody class="js-order-items"></tbody>
                        </table>
                    </div>

                    <button class="inventory-order-back js-order-back" type="button">
                        <i class="bi bi-chevron-left"></i>
                        <span>Kembali</span>
                    </button>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryPurchaseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen inventory-order-dialog">
        <div class="modal-content inventory-order-modal inventory-order-detail-modal">
            <div class="inventory-order-modal__header">
                <h2 class="js-order-detail-title">Order P000002</h2>
                <div class="inventory-order-modal__header-actions">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <div class="inventory-order-modal__body inventory-order-detail__body">
                <div class="inventory-order-detail__head">
                    <div class="inventory-order-detail__status-wrap">
                        <span class="inventory-order-detail__status-icon"><i class="bi bi-truck"></i></span>
                        <div class="inventory-order-detail__status-copy">
                            <strong class="js-order-detail-status">Ordered</strong>
                            <span class="js-order-detail-date">Dipesan di 28 Apr 2026</span>
                        </div>
                    </div>

                    <div class="inventory-order-detail__tools">
                        <button class="inventory-order-detail__tool js-order-detail-email" type="button">
                            <i class="bi bi-envelope"></i>
                            <span>Email order</span>
                        </button>
                        <button class="inventory-order-detail__tool js-order-detail-pdf" type="button">
                            <i class="bi bi-file-earmark-text"></i>
                            <span>Unduh PDF</span>
                        </button>
                    </div>
                </div>

                <div class="inventory-order-summary inventory-order-summary--detail">
                    <div class="inventory-order-summary__route">
                        <div class="inventory-order-summary__supplier">
                            <strong class="js-order-detail-supplier">Wardah</strong>
                            <span class="js-order-detail-supplier-meta">wardah@gmail.com</span>
                        </div>
                        <div class="inventory-order-summary__arrow"><i class="bi bi-arrow-right"></i></div>
                        <div class="inventory-order-summary__location">
                            <strong class="js-order-detail-location">Star Salon</strong>
                            <span class="js-order-detail-location-meta">Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur</span>
                        </div>
                    </div>

                    <div class="inventory-order-summary__footer inventory-order-summary__footer--detail">
                        <div>
                            <span>Jumlah Pesanan:</span>
                            <strong class="js-order-detail-total">Rp 0</strong>
                        </div>
                        <div class="inventory-order-summary__actions js-order-detail-view-actions">
                            <button class="inventory-order-detail__action inventory-order-detail__action--danger js-order-detail-cancel" type="button">Batal Order</button>
                            <button class="inventory-order-detail__action inventory-order-detail__action--success js-order-detail-receive" type="button">Terima Stok</button>
                        </div>
                        <div class="inventory-order-summary__actions js-order-detail-receive-actions" hidden>
                            <button class="inventory-order-detail__action inventory-order-detail__action--ghost js-order-detail-receive-back" type="button">Kembali</button>
                            <button class="inventory-order-detail__action inventory-order-detail__action--success js-order-detail-receive-confirm" type="button">Konfirmasi</button>
                        </div>
                        <div class="inventory-order-summary__actions js-order-detail-closed-actions" hidden>
                            <button class="inventory-order-detail__action inventory-order-detail__action--ghost js-order-detail-close" type="button">Tutup</button>
                        </div>
                    </div>
                </div>

                <div class="inventory-order-detail__section js-order-detail-note-section">
                    <label class="inventory-order-detail__label" for="inventoryOrderDetailNote">Catatan</label>
                    <textarea class="inventory-order-detail__note js-order-detail-note" id="inventoryOrderDetailNote" rows="2" readonly></textarea>
                </div>

                <div class="inventory-order-table inventory-order-table--detail">
                    <table>
                        <thead class="js-order-detail-items-head"></thead>
                        <tbody class="js-order-detail-items"></tbody>
                    </table>
                </div>

                <div class="inventory-order-detail__logs">
                    <button class="inventory-order-detail__logs-toggle js-order-detail-logs-toggle" type="button" aria-expanded="true">
                        <span>Log Penerimaan</span>
                        <i class="bi bi-chevron-up"></i>
                    </button>

                    <div class="inventory-order-table inventory-order-table--detail js-order-detail-logs-panel">
                        <table>
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>QTY Diterima</th>
                                    <th>Waktu &amp; Tanggal</th>
                                    <th>Harga Supply</th>
                                    <th>Total Biaya</th>
                                </tr>
                            </thead>
                            <tbody class="js-order-detail-logs"></tbody>
                        </table>
                    </div>
                </div>

                <div class="inventory-order-detail__confirm js-order-detail-confirm" hidden>
                    <div class="inventory-order-detail__confirm-backdrop js-order-detail-confirm-close"></div>
                    <div class="inventory-order-detail__confirm-card">
                        <p>Apakah Anda yakin akan membatalkan order ini? Action ini permanent, tidak bisa dibatalkan.</p>
                        <button class="inventory-order-detail__confirm-button js-order-detail-confirm-submit" type="button">Batalkan Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen inventory-opname-detail-dialog">
        <div class="modal-content inventory-opname-detail-modal">
            <div class="inventory-opname-detail__header">
                <h2>Stok Barang Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="inventory-opname-detail__body">
                <section class="inventory-opname-detail__summary">
                    <div class="inventory-opname-detail__summary-top">
                        <div>
                            <strong class="js-inventory-opname-summary-name">Stock Opname #5</strong>
                            <span class="js-inventory-opname-summary-note">Tidak ada catatan</span>
                        </div>
                        <div class="inventory-opname-detail__summary-actions">
                            <button class="js-inventory-opname-import-open" type="button">Import</button>
                            <button class="js-inventory-opname-edit-open" type="button">Edit</button>
                        </div>
                    </div>
                    <div class="inventory-opname-detail__summary-bottom">
                        <div>
                            <span>Dimulai pada</span>
                            <strong class="js-inventory-opname-summary-start">01 Mei 2026, 13:16</strong>
                        </div>
                        <div>
                            <span>Lokasi</span>
                            <strong class="js-inventory-opname-summary-location">Star Salon</strong>
                        </div>
                        <div>
                            <span>Dimulai Oleh</span>
                            <strong class="js-inventory-opname-summary-staff">Rayhan Doni Pramana</strong>
                        </div>
                        <button class="inventory-opname-detail__review js-inventory-opname-review" type="button">Tinjau</button>
                    </div>
                </section>

                <div class="inventory-opname-detail__toolbar">
                    <label class="sales-search-field inventory-search-field inventory-opname-detail__search">
                        <input class="js-inventory-opname-detail-search" type="search" autocomplete="off" placeholder="Cari...">
                        <i class="bi bi-search"></i>
                    </label>
                </div>

                <section class="inventory-opname-detail__table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Kode Barang</th>
                                <th>SKU</th>
                                <th>Diharapkan</th>
                                <th>Terhitung</th>
                                <th>Perbedaan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="js-inventory-opname-detail-body">
                            <?php foreach ($opnameDetailProducts as $row): ?>
                                <tr
                                    class="js-inventory-opname-detail-row"
                                    data-product-id="<?= e((string) ($row['id'] ?? 0)) ?>"
                                    data-opname-name="<?= e($row['name']) ?>"
                                    data-opname-code="<?= e($row['code']) ?>"
                                    data-opname-sku="<?= e($row['sku']) ?>"
                                    data-opname-expected="<?= e((string) $row['expected']) ?>"
                                >
                                    <td>
                                        <div class="inventory-opname-detail__product">
                                            <span class="inventory-opname-detail__product-icon"><i class="bi bi-bottle-perfume"></i></span>
                                            <strong><?= e($row['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= e($row['code']) ?></td>
                                    <td><?= e($row['sku']) ?></td>
                                    <td class="js-inventory-opname-expected"><?= e((string) $row['expected']) ?></td>
                                    <td>
                                        <div class="inventory-opname-detail__counter">
                                            <input class="js-inventory-opname-counted" type="text" inputmode="numeric" value="0">
                                            <button class="js-inventory-opname-minus" type="button">-</button>
                                            <button class="js-inventory-opname-plus" type="button">+</button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="inventory-opname-detail__difference js-inventory-opname-difference">
                                            <span class="inventory-opname-detail__difference-icon"><i class="bi bi-dash"></i></span>
                                            <strong class="js-inventory-opname-diff-value">-<?= e((string) $row['expected']) ?></strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="inventory-opname-detail__menu-wrap">
                                            <button class="inventory-opname-detail__menu js-inventory-opname-row-menu" type="button" aria-label="Aksi baris">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <div class="inventory-opname-detail__menu-popover" hidden>
                                                <button class="js-inventory-opname-reset" type="button">Setel Ulang Stok</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="inventory-product-history-table__footer">
                        <div class="js-inventory-opname-detail-total">Total <?= e((string) count($opnameDetailProducts)) ?></div>
                        <button class="dashboard-filter customers-mini-filter" type="button"><span>10/page</span><i class="bi bi-chevron-down"></i></button>
                        <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                        <span class="customers-pagination-current">1</span>
                        <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                        <div>Go to</div>
                        <button class="dashboard-filter customers-mini-input" type="button">1</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog inventory-opname-mini-dialog">
        <div class="modal-content inventory-opname-mini-modal">
            <div class="inventory-opname-mini-modal__header">
                <h2>Informasi Persediaan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="inventory-opname-mini-modal__body">
                <label class="inventory-opname-mini-modal__field">
                    <span>Nama</span>
                    <input class="form-control customer-input-flat js-inventory-opname-edit-name" type="text" value="Stock Opname #5">
                </label>
                <label class="inventory-opname-mini-modal__field">
                    <span>Catatan</span>
                    <textarea class="form-control customer-input-flat js-inventory-opname-edit-note" rows="3" maxlength="200"></textarea>
                    <small class="js-inventory-opname-edit-counter">0/200</small>
                </label>
            </div>
            <div class="inventory-opname-mini-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-inventory-opname-edit-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog inventory-opname-import-dialog">
        <div class="modal-content inventory-opname-import-modal">
            <div class="inventory-opname-import-modal__header">
                <h2>Import Stok Barang</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="inventory-opname-import-modal__body">
                <div class="inventory-opname-import-modal__hero">
                    <div class="inventory-opname-import-modal__files">
                        <span>XLS</span>
                        <span>CSV</span>
                    </div>
                </div>
                <p>Klik pilih file untuk melakukan import</p>
                <div class="inventory-opname-import-modal__actions">
                    <button class="dashboard-filter js-inventory-opname-template" type="button">Download Template</button>
                    <label class="dashboard-filter inventory-opname-import-modal__upload">
                        <input class="js-inventory-opname-import-file" type="file" accept=".csv,.xls,.xlsx" hidden>
                        <span class="js-inventory-opname-import-file-label">Pilih File</span>
                    </label>
                </div>
                <small class="inventory-opname-import-modal__meta js-inventory-opname-import-meta">Belum ada file dipilih</small>
            </div>
            <div class="inventory-opname-import-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-inventory-opname-import-run">Import</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen inventory-opname-review-dialog">
        <div class="modal-content inventory-opname-review-modal">
            <div class="inventory-opname-review__header">
                <h2 class="js-inventory-opname-review-title">Tinjau Stok Barang</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="inventory-opname-review__body">
                <section class="inventory-opname-review__summary js-inventory-opname-review-summary">
                    <div class="inventory-opname-review__summary-top">
                        <div>
                            <strong class="js-inventory-opname-review-name">Stock Opname #5</strong>
                            <span class="js-inventory-opname-review-note">Tidak ada catatan</span>
                        </div>
                        <span class="inventory-opname-review__status js-inventory-opname-review-status">Meninjau</span>
                    </div>
                    <div class="inventory-opname-review__summary-bottom">
                        <div>
                            <span>Dimulai pada</span>
                            <strong class="js-inventory-opname-review-start">01 Mei 2026, 13:16</strong>
                        </div>
                        <div class="js-inventory-opname-review-ended-wrap" hidden>
                            <span>Selesai pada</span>
                            <strong class="js-inventory-opname-review-end">01 Mei 2026, 15:21</strong>
                        </div>
                        <div>
                            <span>Lokasi</span>
                            <strong class="js-inventory-opname-review-location">Star Salon</strong>
                        </div>
                        <div>
                            <span>Dimulai Oleh</span>
                            <strong class="js-inventory-opname-review-staff">Rayhan Doni Pramana</strong>
                        </div>
                        <div class="js-inventory-opname-review-reviewed-wrap" hidden>
                            <span>Diperiksa Oleh</span>
                            <strong class="js-inventory-opname-review-reviewed-by">Rayhan Doni Pramana</strong>
                        </div>
                        <div class="inventory-opname-review__cancelled-meta js-inventory-opname-review-cancelled" hidden>
                            <span>Dibatalkan Oleh</span>
                            <strong class="js-inventory-opname-review-cancelled-by">Rayhan Doni Pramana</strong>
                        </div>
                        <div class="inventory-opname-review__actions">
                            <div class="inventory-opname-review__more-wrap">
                                <button class="inventory-opname-review__more js-inventory-opname-review-more" type="button">Lainnya <i class="bi bi-caret-down-fill"></i></button>
                                <div class="inventory-opname-review__more-menu" hidden>
                                    <button class="js-inventory-opname-review-recount" type="button">Hitung Ulang</button>
                                    <button class="js-inventory-opname-review-cancel-open" type="button">Batalkan Stock Opname</button>
                                </div>
                            </div>
                            <button class="inventory-opname-review__complete js-inventory-opname-review-complete" type="button">Komplit</button>
                        </div>
                    </div>
                </section>

                <div class="inventory-opname-review__toolbar">
                    <div class="inventory-opname-review__toolbar-left">
                        <button class="inventory-opname-review__export js-inventory-opname-review-export" type="button" hidden>Export <i class="bi bi-caret-down-fill"></i></button>
                        <div class="inventory-opname-review__chips">
                            <button class="inventory-opname-review__chip is-active js-inventory-opname-review-filter" type="button" data-filter="counted">Terhitung (0)</button>
                            <button class="inventory-opname-review__chip js-inventory-opname-review-filter" type="button" data-filter="exception">Pengecualian (0)</button>
                            <button class="inventory-opname-review__chip js-inventory-opname-review-filter" type="button" data-filter="mismatch">Tidak Cocok (0)</button>
                        </div>
                    </div>
                    <label class="sales-search-field inventory-search-field inventory-opname-review__search">
                        <input class="js-inventory-opname-review-search" type="search" autocomplete="off" placeholder="Cari...">
                        <i class="bi bi-search"></i>
                    </label>
                </div>

                <section class="inventory-opname-review__table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th>Diharapkan</th>
                                <th>Terhitung</th>
                                <th>Perbedaan</th>
                                <th>Biaya</th>
                            </tr>
                        </thead>
                        <tbody class="js-inventory-opname-review-body"></tbody>
                    </table>

                    <div class="inventory-product-history-table__footer">
                        <div class="js-inventory-opname-review-total">Total 0</div>
                        <button class="dashboard-filter customers-mini-filter" type="button"><span>10/page</span><i class="bi bi-chevron-down"></i></button>
                        <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-left"></i></button>
                        <span class="customers-pagination-current">1</span>
                        <button class="customers-pagination-btn" type="button"><i class="bi bi-chevron-right"></i></button>
                        <div>Go to</div>
                        <button class="dashboard-filter customers-mini-input" type="button">1</button>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog inventory-opname-cancel-dialog">
        <div class="modal-content inventory-opname-cancel-modal">
            <div class="inventory-opname-cancel-modal__header">
                <h2>Batalkan Stock Opname</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="inventory-opname-cancel-modal__body">
                <p>Perubahan tersebut tidak akan disimpan jika Anda membatalkan Stock Opname ini.</p>
                <label class="inventory-opname-cancel-modal__field">
                    <span>Catatan ( Opsional )</span>
                    <textarea class="form-control customer-input-flat js-inventory-opname-cancel-note" rows="3" maxlength="200" placeholder="Masukkan catatan"></textarea>
                    <small class="js-inventory-opname-cancel-counter">0/200</small>
                </label>
            </div>
            <div class="inventory-opname-cancel-modal__footer">
                <button type="button" class="inventory-opname-cancel-modal__submit js-inventory-opname-cancel-submit">Batal</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryOpnameCompleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog inventory-opname-complete-dialog">
        <div class="modal-content inventory-opname-complete-modal">
            <div class="inventory-opname-complete-modal__body">
                <p>Apakah Anda yakin ingin menyelesaikan stock opname ini?</p>
            </div>
            <div class="inventory-opname-complete-modal__footer">
                <button type="button" class="inventory-opname-complete-modal__cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="inventory-opname-complete-modal__submit js-inventory-opname-complete-submit">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryMasterItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered inventory-master-dialog">
        <div class="modal-content inventory-master-modal">
            <div class="inventory-master-modal__header">
                <h2 class="js-master-item-title">Tambah Merk</h2>
                <div class="inventory-master-modal__header-actions">
                    <button type="button" class="inventory-master-modal__esc" data-bs-dismiss="modal">Esc</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <div class="inventory-master-modal__body">
                <label class="inventory-master-modal__field">
                    <span>Nama</span>
                    <input class="form-control customer-input-flat js-master-item-name" type="text" autocomplete="off">
                </label>

                <button class="inventory-master-modal__danger js-master-item-delete" type="button" hidden>Hapus</button>
            </div>

            <div class="inventory-master-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-master-item-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventorySupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered inventory-supplier-dialog">
        <div class="modal-content inventory-master-modal inventory-master-modal--supplier">
            <div class="inventory-master-modal__header">
                <h2 class="js-master-supplier-title">Tambah Supplier</h2>
                <div class="inventory-master-modal__header-actions">
                    <button type="button" class="inventory-master-modal__esc" data-bs-dismiss="modal">Esc</button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
            </div>

            <div class="inventory-master-modal__body inventory-master-modal__body--supplier">
                <div class="inventory-master-supplier-grid">
                    <label class="inventory-master-modal__field">
                        <span>Nama Pemasok</span>
                        <input class="form-control customer-input-flat js-master-supplier-name" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Deskripsi</span>
                        <textarea class="form-control customer-input-flat js-master-supplier-description" rows="2"></textarea>
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Nama Kontak</span>
                        <input class="form-control customer-input-flat js-master-supplier-contact" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Email</span>
                        <input class="form-control customer-input-flat js-master-supplier-email" type="email" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Nomor Ponsel</span>
                        <input class="form-control customer-input-flat js-master-supplier-phone" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Website</span>
                        <input class="form-control customer-input-flat js-master-supplier-website" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field inventory-master-modal__field--full">
                        <span>Alamat</span>
                        <textarea class="form-control customer-input-flat js-master-supplier-address" rows="2"></textarea>
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Kota</span>
                        <input class="form-control customer-input-flat js-master-supplier-city" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Negara</span>
                        <input class="form-control customer-input-flat js-master-supplier-country" type="text" autocomplete="off">
                    </label>
                    <label class="inventory-master-modal__field">
                        <span>Kode Pos</span>
                        <input class="form-control customer-input-flat js-master-supplier-postal" type="text" autocomplete="off">
                    </label>
                </div>

                <button class="inventory-master-modal__danger js-master-supplier-delete" type="button" hidden>Hapus</button>
            </div>

            <div class="inventory-master-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-master-supplier-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<template id="inventoryVariantTemplate">
    <article class="inventory-variant-card js-inventory-variant-card" data-variant-id="__INDEX__">
        <div class="inventory-variant-card__top">
            <div class="inventory-variant-card__confirm js-inventory-variant-confirm" hidden>
                <p>Apakah anda yakin akan menghapus ini?</p>
                <div class="inventory-variant-card__confirm-actions">
                    <button type="button" class="js-inventory-variant-cancel">Batal</button>
                    <button type="button" class="js-inventory-variant-confirm-delete">Konfirmasi</button>
                </div>
            </div>

            <div class="inventory-variant-card__actions">
                <button class="inventory-variant-card__icon inventory-variant-card__icon--danger js-inventory-variant-delete" type="button" aria-label="Hapus varian">
                    <i class="bi bi-trash"></i>
                </button>
                <button class="inventory-variant-card__done" type="button">Selesai</button>
            </div>
        </div>

        <div class="inventory-variant-card__body">
            <div class="inventory-variant-grid">
                <label class="inventory-variant-grid__name">
                    <span>* Nama Variant</span>
                    <input class="form-control customer-input-flat js-inventory-variant-name" type="text" placeholder="Masukkan nama variant">
                    <small class="js-inventory-variant-error">name is required</small>
                </label>

                <label class="inventory-variant-grid__price">
                    <span>Harga retail</span>
                    <input class="form-control customer-input-flat" type="text" value="Rp 0,00">
                </label>

                <label class="inventory-variant-grid__price">
                    <span>Harga Spesial</span>
                    <input class="form-control customer-input-flat" type="text" value="Rp 0,00">
                </label>

                <label class="inventory-variant-grid__code">
                    <span>Kode Barang</span>
                    <input class="form-control customer-input-flat" type="text">
                </label>

                <label class="inventory-variant-grid__sku">
                    <span>SKU</span>
                    <input class="form-control customer-input-flat" type="text">
                </label>

                <div class="inventory-variant-grid__stack">
                    <label>
                        <span>Pengaturan Stock</span>
                        <div class="inventory-variant-chip">
                            <strong>Nonaktif</strong>
                            <button type="button">Ganti</button>
                        </div>
                    </label>

                    <label>
                        <span>Harga tiap lokasi</span>
                        <div class="inventory-variant-chip">
                            <strong>Semua lokasi punya harga sama</strong>
                            <button type="button">Ganti</button>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </article>
</template>

<div class="modal fade" id="inventoryQuickActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content inventory-quick-modal">
            <div class="inventory-quick-modal__head">
                <h2>Tambah Produk</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="inventory-quick-modal__body">
                <p>Tombol + sudah aktif. Nanti kita sambungkan ke form tambah produk satu per satu.</p>
            </div>
            <div class="inventory-quick-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="inventoryImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content customers-import-modal inventory-import-modal">
            <div class="customers-import-modal__header">
                <h2>Import Produk</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customers-import-modal__body">
                <div class="customers-import-hero">
                    <div class="customers-import-hero__icons">
                        <div class="customers-import-file">XLS</div>
                        <div class="customers-import-file">CSV</div>
                    </div>
                    <p>Klik pilih file untuk melakukan import. <a href="#" class="customers-import-link js-inventory-import-help">Klik link ini</a> untuk mengetahui cara pengisian excel file</p>
                    <div class="customers-import-actions">
                        <button class="dashboard-filter customers-import-action js-inventory-template" type="button">Download Template</button>
                        <label class="dashboard-filter customers-import-action customers-import-upload">
                            <input class="js-inventory-import-file" type="file" accept=".csv,.xls,.xlsx">
                            <span>Pilih File</span>
                        </label>
                    </div>
                    <div class="customers-import-meta js-inventory-import-meta">Belum ada file dipilih</div>
                </div>
            </div>
            <div class="customers-import-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customers-import-btn js-inventory-import-run" disabled>Import (0)</button>
            </div>
        </div>
    </div>
</div>

<div class="inventory-filter-drawer" id="inventoryProductFilterDrawer" hidden aria-hidden="true">
    <div class="inventory-filter-drawer__backdrop js-inventory-filter-close"></div>
    <aside class="inventory-filter-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="inventoryFilterDrawerTitle">
        <div class="inventory-filter-drawer__head">
            <h2 id="inventoryFilterDrawerTitle">Filter</h2>
            <button type="button" class="staff-work-close js-inventory-filter-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="inventory-filter-drawer__body">
            <label>Merk</label>
            <select class="form-select customer-input-flat js-inventory-filter-brand">
                <option value="">Pilih</option>
                <?php foreach ($productBrands as $brand): ?>
                    <option value="<?= e($brand) ?>"><?= e($brand) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Kategori</label>
            <select class="form-select customer-input-flat js-inventory-filter-category">
                <option value="">Pilih</option>
                <?php foreach ($productCategories as $category): ?>
                    <option value="<?= e($category) ?>"><?= e($category) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Pemasok</label>
            <select class="form-select customer-input-flat js-inventory-filter-supplier">
                <option value="">Pilih</option>
                <?php foreach ($productSuppliers as $supplier): ?>
                    <option value="<?= e($supplier) ?>"><?= e($supplier) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Stok yang ada</label>
            <select class="form-select customer-input-flat js-inventory-filter-stock">
                <option value="">Pilih</option>
                <option value="available">Ada stok</option>
                <option value="empty">Stok kosong</option>
            </select>
        </div>
        <div class="inventory-filter-drawer__footer">
            <button type="button" class="customer-footer-btn js-inventory-filter-reset">Hapus filter</button>
            <button type="button" class="customer-footer-btn customer-footer-btn--primary js-inventory-filter-close">Tutup</button>
        </div>
    </aside>
</div>

<div class="inventory-filter-drawer" id="inventoryPurchaseFilterDrawer" hidden aria-hidden="true">
    <div class="inventory-filter-drawer__backdrop js-inventory-purchase-filter-close"></div>
    <aside class="inventory-filter-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="inventoryPurchaseFilterDrawerTitle">
        <div class="inventory-filter-drawer__head">
            <h2 id="inventoryPurchaseFilterDrawerTitle">Filter</h2>
            <button type="button" class="staff-work-close js-inventory-purchase-filter-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="inventory-filter-drawer__body">
            <label>Status</label>
            <select class="form-select customer-input-flat js-inventory-purchase-filter-status">
                <option value="">All Status</option>
                <?php foreach ($purchaseStatuses as $status): ?>
                    <option value="<?= e($status) ?>"><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Supplier</label>
            <select class="form-select customer-input-flat js-inventory-purchase-filter-supplier">
                <option value="">Select</option>
                <?php foreach ($purchaseSuppliers as $supplier): ?>
                    <option value="<?= e($supplier) ?>"><?= e($supplier) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="inventory-filter-drawer__footer">
            <button type="button" class="customer-footer-btn js-inventory-purchase-filter-reset">Hapus Filter</button>
            <button type="button" class="customer-footer-btn customer-footer-btn--primary js-inventory-purchase-filter-close">Tutup</button>
        </div>
    </aside>
</div>

<div class="modal fade" id="inventoryOpnameDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal staff-attendance-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-inventory-opname-date-preset is-active" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-inventory-opname-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-inventory-opname-start" type="text" value="<?= e($opnameRangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-inventory-opname-end" type="text" value="<?= e($opnameRangeEnd->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-inventory-opname-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-inventory-opname-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-inventory-opname-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>
