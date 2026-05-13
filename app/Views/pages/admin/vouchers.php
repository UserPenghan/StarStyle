<?php
$voucherRows = [
    [
        'type_code' => 'G',
        'type_label' => 'Gift Type',
        'name' => 'voucher gift',
        'value' => 'Rp 30.000,00',
        'editor_value' => '30000',
        'duration' => '1 Bulan',
        'expiry_label' => 'After 1 Month',
        'location' => 'Semua Lokasi',
        'status' => 'Active',
        'type_key' => 'gift',
        'message' => 'Thank you!',
        'active' => true,
        'search' => 'gift type voucher gift rp 30.000,00 1 bulan semua lokasi active',
    ],
    [
        'type_code' => 'S',
        'type_label' => 'Service Type',
        'name' => 'Potong Rambut Promo',
        'value' => 'Potong rambut pria -',
        'editor_value' => '0',
        'duration' => '1 Bulan',
        'expiry_label' => 'After 1 Month',
        'location' => 'Semua Lokasi',
        'status' => 'Active',
        'type_key' => 'service',
        'service_name' => 'Signature Haircut',
        'message' => 'Thank you!',
        'active' => true,
        'search' => 'service type potong rambut promo potong rambut pria 1 bulan semua lokasi active',
    ],
    [
        'type_code' => 'S',
        'type_label' => 'Service Type',
        'name' => 'Diskon Ulang Tahun',
        'value' => 'Potong rambut pria -',
        'editor_value' => '0',
        'duration' => '1 Bulan',
        'expiry_label' => 'After 1 Month',
        'location' => 'Semua Lokasi',
        'status' => 'Active',
        'type_key' => 'service',
        'service_name' => 'Signature Haircut',
        'message' => 'Thank you!',
        'active' => true,
        'search' => 'service type diskon ulang tahun potong rambut pria 1 bulan semua lokasi active',
    ],
    [
        'type_code' => 'G',
        'type_label' => 'Gift Type',
        'name' => 'Diskon Juni',
        'value' => 'Rp 20.000,00',
        'editor_value' => '20000',
        'duration' => '1 Bulan',
        'expiry_label' => 'After 1 Month',
        'location' => 'Semua Lokasi',
        'status' => 'Active',
        'type_key' => 'gift',
        'message' => 'Thank you!',
        'active' => true,
        'search' => 'gift type diskon juni rp 20.000,00 1 bulan semua lokasi active',
    ],
    [
        'type_code' => 'S',
        'type_label' => 'Service Type',
        'name' => 'Diskon Mei',
        'value' => 'Hair spa -, Cat rambut ful...',
        'editor_value' => '0',
        'duration' => '1 Minggu',
        'expiry_label' => 'After 1 Week',
        'location' => 'Semua Lokasi',
        'status' => 'Active',
        'type_key' => 'service',
        'service_name' => 'Hair spa -, Cat rambut ful...',
        'message' => 'Thank you!',
        'active' => true,
        'search' => 'service type diskon mei hair spa cat rambut 1 minggu semua lokasi active',
    ],
];
$discountRows = [
    [
        'id' => 1,
        'name' => 'Diskon 20%',
        'mode' => 'percent',
        'amount_label' => '20.00 %',
        'amount_value' => '20',
        'max_discount' => 'Rp 0,00',
        'applies_to' => ['Penjualan Service', 'Penjualan Kelas', 'Penjualan Produk', 'Penjualan voucher', 'Total Penjualan'],
    ],
    [
        'id' => 2,
        'name' => 'Diskon Mei Happy',
        'mode' => 'amount',
        'amount_label' => 'Rp 20.000,00',
        'amount_value' => '20000',
        'max_discount' => '',
        'applies_to' => ['Penjualan Service', 'Penjualan Kelas', 'Penjualan Produk', 'Penjualan voucher', 'Total Penjualan'],
    ],
];
$discountScopes = ['Penjualan Service', 'Penjualan Kelas', 'Penjualan Produk', 'Penjualan voucher', 'Total Penjualan'];
$voucherServiceOptions = [
    ['name' => 'Cat rambut full ()', 'price' => 'Rp 300.000,00', 'duration' => '3h'],
    ['name' => 'Hair spa ()', 'price' => 'Rp 120.000,00', 'duration' => '2h'],
    ['name' => 'Creambath ()', 'price' => 'Rp 100.000,00', 'duration' => '2h'],
    ['name' => 'Potong rambut Wanita ()', 'price' => 'Rp 50.000,00', 'duration' => '1h'],
    ['name' => 'Potong rambut pria ()', 'price' => 'Rp 50.000,00', 'duration' => '1h'],
];
?>

<section class="vouchers-shell js-vouchers-shell">
    <div class="vouchers-tabs" role="tablist" aria-label="Voucher tabs">
        <button class="vouchers-tab is-active js-vouchers-tab" type="button" data-vouchers-tab="voucher" aria-selected="true">Voucher</button>
        <button class="vouchers-tab js-vouchers-tab" type="button" data-vouchers-tab="discount" aria-selected="false">Diskon</button>
    </div>

    <div class="vouchers-panels">
        <section class="vouchers-panel is-active js-vouchers-panel" data-vouchers-panel="voucher">
            <div class="vouchers-toolbar vouchers-toolbar--compact">
                <div class="vouchers-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="vouchers-filter-chips">
                        <button class="vouchers-filter-chip is-active js-voucher-type-filter" type="button" data-voucher-type="all">Semua</button>
                        <button class="vouchers-filter-chip js-voucher-type-filter" type="button" data-voucher-type="gift">G</button>
                        <button class="vouchers-filter-chip js-voucher-type-filter" type="button" data-voucher-type="service">S</button>
                    </div>
                    <label class="sales-search-field vouchers-search">
                        <input class="js-vouchers-search" type="search" placeholder="Ketik kata kunci" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="inventory-table-card vouchers-table-card">
                <table class="inventory-table voucher-table">
                    <thead>
                        <tr>
                            <th>Tipe Voucher</th>
                            <th>Nama</th>
                            <th>Nilai</th>
                            <th>Durasi Waktu</th>
                            <th>Lokasi Penggunaan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="js-voucher-table-body">
                        <?php foreach ($voucherRows as $voucher): ?>
                            <tr
                                class="js-voucher-row"
                                data-voucher-type="<?= e($voucher['type_key']) ?>"
                                data-voucher-type-code="<?= e($voucher['type_code']) ?>"
                                data-voucher-type-label="<?= e($voucher['type_label']) ?>"
                                data-voucher-name="<?= e($voucher['name']) ?>"
                                data-voucher-value="<?= e($voucher['value']) ?>"
                                data-voucher-editor-value="<?= e($voucher['editor_value']) ?>"
                                data-search="<?= e($voucher['search']) ?>"
                                data-voucher-duration="<?= e($voucher['duration']) ?>"
                                data-voucher-expiry-label="<?= e($voucher['expiry_label']) ?>"
                                data-voucher-location="<?= e($voucher['location']) ?>"
                                data-voucher-status="<?= e($voucher['status']) ?>"
                                data-voucher-service-name="<?= e($voucher['service_name'] ?? '') ?>"
                                data-voucher-message="<?= e($voucher['message'] ?? 'Thank you!') ?>"
                                data-voucher-active="<?= $voucher['active'] ? '1' : '0' ?>"
                            >
                                <td>
                                    <div class="voucher-table__type">
                                        <span class="voucher-table__badge voucher-table__badge--<?= e($voucher['type_key']) ?>"><?= e($voucher['type_code']) ?></span>
                                        <strong><?= e($voucher['type_label']) ?></strong>
                                    </div>
                                </td>
                                <td><strong><?= e($voucher['name']) ?></strong></td>
                                <td><?= e($voucher['value']) ?></td>
                                <td><?= e($voucher['duration']) ?></td>
                                <td><?= e($voucher['location']) ?></td>
                                <td><span class="voucher-table__status"><?= e($voucher['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="inventory-table__footer">
                    <span>Total <?= count($voucherRows) ?></span>
                    <div class="inventory-table__footer-controls">
                        <button class="inventory-table__page-size" type="button">20/page <i class="bi bi-chevron-down"></i></button>
                        <button class="inventory-table__pager" type="button"><i class="bi bi-chevron-left"></i></button>
                        <button class="inventory-table__page is-active" type="button">1</button>
                        <button class="inventory-table__pager" type="button"><i class="bi bi-chevron-right"></i></button>
                        <span>Go to</span>
                        <button class="inventory-table__goto" type="button">1</button>
                        <button class="inventory-table__collapse" type="button"><i class="bi bi-chevron-up"></i></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="vouchers-panel js-vouchers-panel" data-vouchers-panel="discount" hidden>
            <div class="vouchers-toolbar vouchers-toolbar--compact">
                <div class="vouchers-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <label class="sales-search-field vouchers-search">
                        <input class="js-vouchers-discount-search" type="search" placeholder="Ketik kata kunci" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="voucher-discount-board">
                <div class="voucher-discount-list js-voucher-discount-list">
                    <?php foreach ($discountRows as $index => $discount): ?>
                        <button
                            class="voucher-discount-item js-voucher-discount-item<?= $index === 1 ? ' is-soft' : '' ?>"
                            type="button"
                            data-discount-id="<?= e((string) $discount['id']) ?>"
                            data-discount-name="<?= e($discount['name']) ?>"
                            data-discount-mode="<?= e($discount['mode']) ?>"
                            data-discount-amount="<?= e($discount['amount_value']) ?>"
                            data-discount-max="<?= e($discount['max_discount']) ?>"
                            data-discount-scopes="<?= e(json_encode($discount['applies_to'], JSON_UNESCAPED_UNICODE)) ?>"
                            data-search="<?= e(strtolower($discount['name'] . ' ' . $discount['amount_label'])) ?>"
                        >
                            <strong><?= e($discount['name']) ?></strong>
                            <span><?= e($discount['amount_label']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="services-fab-wrapper">
        <button class="customers-fab js-vouchers-fab" type="button" aria-label="Tambah voucher atau diskon">
            <i class="bi bi-plus-lg"></i>
        </button>
        <div class="voucher-fab-menu js-voucher-fab-menu" hidden>
            <button class="voucher-fab-menu__item js-voucher-create-trigger" type="button" data-voucher-create="service" data-bs-toggle="modal" data-bs-target="#voucherServiceModal">
                <i class="bi bi-scissors"></i>
                <span>Voucher Layanan</span>
            </button>
            <button class="voucher-fab-menu__item js-voucher-create-trigger" type="button" data-voucher-create="gift" data-bs-toggle="modal" data-bs-target="#voucherGiftModal">
                <i class="bi bi-gift"></i>
                <span>Voucher Hadiah</span>
            </button>
            <button class="voucher-fab-menu__close js-voucher-fab-close" type="button">
                <i class="bi bi-x-lg"></i>
                <span>Tutup</span>
            </button>
        </div>
    </div>
</section>

<div class="modal fade" id="voucherDiscountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered voucher-discount-dialog">
        <div class="modal-content voucher-discount-modal">
            <div class="voucher-discount-modal__head">
                <h2 class="js-voucher-discount-title">Buat Diskon</h2>
                <div class="voucher-discount-modal__actions">
                    <button type="button" class="voucher-discount-modal__esc" data-bs-dismiss="modal">Esc</button>
                    <button type="button" class="voucher-discount-modal__close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="voucher-discount-modal__body">
                <label class="voucher-discount-field">
                    <span>Nama Diskon</span>
                    <input class="form-control customer-input-flat js-voucher-discount-name" type="text" placeholder="e.g. Birthday Disc." autocomplete="off">
                </label>

                <div class="voucher-discount-field">
                    <span>Jumlah Diskon</span>
                    <div class="voucher-discount-amount">
                        <input class="form-control customer-input-flat js-voucher-discount-amount" type="text" value="Rp 0,00" inputmode="decimal" autocomplete="off" placeholder="0">
                        <div class="voucher-discount-amount__switch">
                            <button class="js-voucher-discount-mode is-active" type="button" data-mode="amount">Rp</button>
                            <button class="js-voucher-discount-mode" type="button" data-mode="percent">%</button>
                        </div>
                    </div>
                </div>

                <label class="voucher-discount-field js-voucher-discount-max-wrap" hidden>
                    <span>Jumlah Maksimum Discount</span>
                    <input class="form-control customer-input-flat js-voucher-discount-max" type="text" value="Rp 0,00" inputmode="decimal" autocomplete="off" placeholder="0">
                </label>

                <div class="voucher-discount-field">
                    <span>Aktifkan diskon ini untuk</span>
                    <div class="voucher-discount-scopes">
                        <?php foreach ($discountScopes as $scope): ?>
                            <label class="voucher-discount-scope">
                                <input class="js-voucher-discount-scope" type="checkbox" value="<?= e($scope) ?>" checked>
                                <span><i class="bi bi-check-lg"></i></span>
                                <strong><?= e($scope) ?></strong>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="voucher-discount-delete js-voucher-discount-delete" type="button" hidden>Hapus</button>
            </div>
            <div class="voucher-discount-modal__footer">
                <button type="button" class="customer-footer-btn js-voucher-discount-cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-voucher-discount-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen voucher-service-dialog">
        <div class="modal-content customer-modal vouchers-editor vouchers-editor--service">
            <div class="customer-modal__header">
                <h2>Voucher Layanan Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Display Name</label>
                                <input class="form-control customer-input-flat js-voucher-service-name" type="text">

                                <label>Layanan</label>
                                <button class="customer-picker js-voucher-service-item" type="button"><span>No item</span><strong>Ganti</strong></button>

                                <label class="customer-toggle-row">
                                    <span>Kombinasikan kuantitas</span>
                                    <button class="sales-switch__track js-voucher-service-quantity" type="button" aria-pressed="false"></button>
                                </label>

                                <div class="vouchers-quantity-wrap js-voucher-service-max-wrap" hidden>
                                    <label>Kuantitas maksimal yang dikombinasikan</label>
                                    <div class="vouchers-quantity-box">
                                        <span class="js-voucher-service-max-value">1</span>
                                        <div class="vouchers-quantity-box__actions">
                                            <button class="js-voucher-service-max-minus" type="button">-</button>
                                            <button class="js-voucher-service-max-plus" type="button">+</button>
                                        </div>
                                    </div>
                                </div>

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input js-voucher-service-price" type="text" value="Rp 0,00">
                                <small>Jika 0, Anda harus secara manual menentukan harganya saat checkout</small>
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Tanggal Kadaluarsa</label>
                                <div class="customer-segmented customer-segmented--two js-voucher-service-expiry-mode">
                                    <button class="is-active" type="button" data-expiry-mode="relative">Setelah</button>
                                    <button type="button" data-expiry-mode="specific">Tanggal Spesifik</button>
                                </div>
                                <div class="voucher-expiry-picker js-voucher-service-expiry-picker">
                                    <button class="customer-picker js-voucher-service-expiry" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>
                                    <button class="voucher-expiry-picker__clear js-voucher-service-expiry-clear" type="button" aria-label="Hapus tanggal" hidden><i class="bi bi-x-circle"></i></button>
                                    <div class="voucher-expiry-dropdown js-voucher-service-expiry-dropdown" hidden></div>
                                    <input class="js-voucher-service-expiry-date" type="text" hidden>
                                </div>

                                <label>Dapat digunakan di</label>
                                <button class="customer-picker js-voucher-service-location" type="button"><span>Semua Lokasi</span><strong>Pilih</strong></button>

                                <label>Pesan</label>
                                <textarea class="form-control customer-input-flat js-voucher-service-message" rows="4" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <button class="vouchers-preview-switch is-active js-voucher-preview-toggle" type="button" aria-pressed="true"></button>
                            </div>
                            <div class="vouchers-preview-body js-voucher-preview-body"></div>
                            <div class="vouchers-preview-empty js-voucher-preview-empty" hidden>
                                Ganti ke aktif untuk menggunakan voucher saat checkout
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--disabled js-voucher-service-save">Simpan</button>
            </div>

            <aside class="voucher-service-panel js-voucher-service-panel" hidden>
                <div class="voucher-service-panel__head">
                    <strong>Layanan</strong>
                    <button class="voucher-service-panel__close js-voucher-service-panel-close" type="button"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="voucher-service-panel__body">
                <label class="voucher-service-panel__search">
                    <input class="js-voucher-service-search" type="search" placeholder="Search services..." autocomplete="off">
                </label>
                <div class="voucher-service-panel__list js-voucher-service-options" hidden>
                        <?php foreach ($voucherServiceOptions as $option): ?>
                            <div
                                class="voucher-service-option js-voucher-service-option"
                                data-service-name="<?= e($option['name']) ?>"
                                data-service-price="<?= e($option['price']) ?>"
                                data-service-duration="<?= e($option['duration']) ?>"
                                data-search="<?= e(strtolower($option['name'] . ' ' . $option['price'] . ' ' . $option['duration'])) ?>"
                            >
                                <div class="voucher-service-option__meta">
                                    <strong><?= e($option['name']) ?></strong>
                                    <span><?= e($option['price']) ?> <i class="bi bi-dot"></i> <?= e($option['duration']) ?></span>
                                </div>
                                <div class="voucher-service-option__action js-voucher-service-option-action"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="voucher-service-panel__selected js-voucher-service-selected" hidden></div>
                <div class="voucher-service-panel__notice js-voucher-service-panel-notice" hidden>
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Jumlah voucher layanan ini <strong>digabungkan</strong>. Gunakan Maks. Jumlah Gabungan untuk membatasi penggunaan voucher ini</span>
                </div>
                </div>
                <div class="voucher-service-panel__footer">
                    <button class="voucher-service-panel__button" type="button" data-service-panel-cancel>Batal</button>
                    <button class="voucher-service-panel__button voucher-service-panel__button--primary js-voucher-service-panel-apply" type="button">Tambahkan (0)</button>
                </div>
            </aside>
            <aside class="voucher-location-panel js-voucher-location-panel" hidden>
                <div class="voucher-location-panel__head">
                    <strong>Dapat digunakan di</strong>
                    <button class="voucher-location-panel__close js-voucher-location-panel-close" type="button"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="voucher-location-panel__body">
                    <label class="voucher-location-panel__search">
                        <input class="js-voucher-location-search" type="search" placeholder="Cari lokasi" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                    <div class="voucher-location-panel__list js-voucher-location-options">
                        <?php foreach (['Semua Lokasi', 'Star Salon', 'Cabang Utama'] as $locationOption): ?>
                            <button class="voucher-location-option js-voucher-location-option" type="button" data-location-name="<?= e($locationOption) ?>">
                                <span><?= e($locationOption) ?></span>
                                <i class="bi bi-check-lg"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="voucher-location-panel__footer">
                    <button class="voucher-location-panel__button voucher-location-panel__button--primary js-voucher-location-panel-apply" type="button">Selesai</button>
                </div>
            </aside>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal vouchers-editor">
            <div class="customer-modal__header">
                <div></div>
                <h2>Voucher Kelas Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="vouchers-editor-tabs">
                <button class="vouchers-editor-tab is-active" type="button">Rincian</button>
                <button class="vouchers-editor-tab" type="button">Pengaturan</button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Nama voucher</label>
                                <input class="form-control customer-input-flat" type="text" placeholder="Please input">

                                <label>Class</label>
                                <button class="customer-picker" type="button"><span><?= e($classes[0]['name'] ?? 'Select class') ?></span><i class="bi bi-chevron-down"></i></button>

                                <label>Jumlah sesi</label>
                                <div class="vouchers-stepper">
                                    <button type="button">-</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input" type="text" value="Rp 0,00">
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Expiry date</label>
                                <div class="customer-segmented customer-segmented--two">
                                    <button class="is-active" type="button">Setelah</button>
                                    <button type="button">Tanggal Spesifik</button>
                                </div>
                                <button class="customer-picker" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>

                                <label>Dapat digunakan di</label>
                                <button class="customer-picker" type="button"><span>Pilih sesi</span></button>

                                <label>Message</label>
                                <textarea class="form-control customer-input-flat" rows="4" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <span class="vouchers-preview-switch"></span>
                            </div>
                            <div class="vouchers-preview-card vouchers-preview-card--class">
                                <div class="vouchers-preview-left">
                                    <div class="vouchers-preview-brand"><i class="bi bi-record-circle"></i><span>Star Salon</span></div>
                                    <div class="vouchers-preview-qr"></div>
                                    <p>Digenenerate setelah penjualan</p>
                                </div>
                                <div class="vouchers-preview-right">
                                    <strong>For 1 Session</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--disabled">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherGiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen voucher-gift-dialog">
        <div class="modal-content customer-modal vouchers-editor vouchers-editor--gift">
            <div class="customer-modal__header">
                <h2>Voucher Hadiah Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Nama voucher</label>
                                <input class="form-control customer-input-flat js-voucher-gift-name" type="text" placeholder="Please input">

                                <label>Nilai</label>
                                <input class="form-control customer-input-flat vouchers-money-input js-voucher-gift-value" type="text" value="Rp 0,00">

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input js-voucher-gift-price" type="text" value="Rp 0,00">

                                <label>Tanggal Kadaluarsa</label>
                                <div class="customer-segmented customer-segmented--two js-voucher-gift-expiry-mode">
                                    <button class="is-active" type="button" data-expiry-mode="relative">Setelah</button>
                                    <button type="button" data-expiry-mode="specific">Tanggal Spesifik</button>
                                </div>
                                <div class="voucher-expiry-picker js-voucher-gift-expiry-picker">
                                    <button class="customer-picker js-voucher-gift-expiry" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>
                                    <button class="voucher-expiry-picker__clear js-voucher-gift-expiry-clear" type="button" aria-label="Hapus tanggal" hidden><i class="bi bi-x-circle"></i></button>
                                    <div class="voucher-expiry-dropdown js-voucher-gift-expiry-dropdown" hidden></div>
                                    <input class="js-voucher-gift-expiry-date" type="text" hidden>
                                </div>
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Dapat digunakan di</label>
                                <button class="customer-picker js-voucher-gift-location" type="button"><span>Semua Lokasi</span><strong>Ganti</strong></button>

                                <label>Pesan</label>
                                <textarea class="form-control customer-input-flat js-voucher-gift-message" rows="5" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <button class="vouchers-preview-switch is-active js-voucher-preview-toggle" type="button" aria-pressed="true"></button>
                            </div>
                            <div class="vouchers-preview-body js-voucher-preview-body"></div>
                            <div class="vouchers-preview-empty js-voucher-preview-empty" hidden>
                                Ganti ke aktif untuk menggunakan voucher saat checkout
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn js-voucher-gift-save">Simpan</button>
            </div>
            <aside class="voucher-location-panel js-voucher-gift-location-panel" hidden>
                <div class="voucher-location-panel__head">
                    <strong>Dapat digunakan di</strong>
                    <button class="voucher-location-panel__close js-voucher-gift-location-panel-close" type="button"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="voucher-location-panel__body">
                    <label class="voucher-location-panel__search">
                        <input class="js-voucher-gift-location-search" type="search" placeholder="Cari lokasi" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                    <div class="voucher-location-panel__list js-voucher-gift-location-options">
                        <?php foreach (['Semua Lokasi', 'Star Salon', 'Cabang Utama'] as $locationOption): ?>
                            <button class="voucher-location-option js-voucher-gift-location-option" type="button" data-location-name="<?= e($locationOption) ?>">
                                <span><?= e($locationOption) ?></span>
                                <i class="bi bi-check-lg"></i>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="voucher-location-panel__footer">
                    <button class="voucher-location-panel__button voucher-location-panel__button--primary js-voucher-gift-location-panel-apply" type="button">Selesai</button>
                </div>
            </aside>
        </div>
    </div>
</div>
