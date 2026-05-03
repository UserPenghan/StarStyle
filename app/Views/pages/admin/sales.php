<?php
$servicesSalesRows = [
    ['label' => 'Services', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Classes', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Plan', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Products', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Vouchers', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Sales by Vouchers Redeem', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Gross Total Sales', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Net Total Sales', 'sales' => 0, 'refund' => 0, 'gross' => 0],
    ['label' => 'Total Discount In Sales', 'sales' => 0, 'refund' => 0, 'gross' => 0],
];

$serviceRows = [];
foreach ($transactions as $transaction) {
    foreach ($transaction['items'] as $item) {
        if ($item['type'] !== 'service') {
            continue;
        }

        $serviceRows[] = [
            'ref' => $transaction['reference'],
            'customer' => 'Customer #' . $transaction['customer_id'],
            'staff' => 'Staff #' . $transaction['staff_id'],
            'item' => $item['name'],
            'date' => substr($transaction['date'], 0, 10),
            'time' => substr($transaction['date'], 11, 5),
            'duration' => '1h',
            'status' => strtoupper($transaction['status']),
        ];
    }
}

$invoiceRows = array_map(static function (array $transaction): array {
    $gross = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['price'] * $item['qty']), 0.0);

    return [
        'invoice' => 'INV-' . substr($transaction['reference'], 4),
        'customer' => 'Customer #' . $transaction['customer_id'],
        'date' => substr($transaction['date'], 0, 10),
        'time' => substr($transaction['date'], 11, 5),
        'location' => 'Star Salon',
        'tips' => 0,
        'gross' => $gross,
        'status' => strtoupper($transaction['status']),
        'payment_method' => strtoupper($transaction['payment_method'] ?? 'Cash'),
        'items' => array_map(static fn (array $item): array => [
            'name' => $item['name'],
            'qty' => (int) $item['qty'],
            'price' => (float) $item['price'],
            'staff' => 'Rayhan Doni Pramana',
            'time' => substr($transaction['date'], 11, 5),
        ], $transaction['items']),
    ];
}, $transactions);

$voucherRows = array_map(static function (array $voucher): array {
    return [
        'name' => $voucher['name'],
        'expired' => $voucher['expired_at'],
        'invoice' => '-',
        'customer' => '-',
        'code' => $voucher['code'],
        'total' => $voucher['value'],
        'used' => $voucher['used'],
        'status' => $voucher['status'],
    ];
}, $vouchers);

$cashDrawerRows = array_map(static function (array $drawer): array {
    return [
        'date' => '05 Mar 2026, 11:42 PM',
        'staff' => 'Rayhan Doni Pramana',
        'expected' => $drawer['expected'],
        'actual' => $drawer['actual'],
        'status' => $drawer['status'] === 'Sesuai' ? 'Buka' : 'Review',
    ];
}, $cash_drawers);

$cashFlowTotal = array_reduce($cash_movements, fn (float $sum, array $movement): float => $sum + (($movement['type'] === 'cash_in' ? 1 : -1) * $movement['amount']), 0.0);
?>

<section class="sales-shell js-sales-shell">
    <div class="sales-tabs">
        <button class="sales-tab is-active" type="button" data-sales-tab="daily">Penjualan Harian</button>
        <button class="sales-tab" type="button" data-sales-tab="services">Layanan</button>
        <button class="sales-tab" type="button" data-sales-tab="classes">Kelas</button>
        <button class="sales-tab" type="button" data-sales-tab="invoices">Faktur</button>
        <button class="sales-tab" type="button" data-sales-tab="vouchers">Voucher</button>
        <button class="sales-tab" type="button" data-sales-tab="cash-drawer">Laci Kas</button>
        <button class="sales-tab" type="button" data-sales-tab="cash-flow">Kas Masuk/Keluar</button>
    </div>

    <div class="sales-tab-panels">
        <section class="sales-panel is-active" data-sales-panel="daily">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button"><i class="bi bi-shop"></i><span>Star Salon</span><i class="bi bi-chevron-down"></i></button>
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dashboard-filter sales-btn-print" type="button"><i class="bi bi-printer"></i><span>Cetak Ringkasan</span></button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <label class="sales-switch">
                        <input type="checkbox">
                        <span class="sales-switch__track"></span>
                        <span>Berdasarkan tanggal invoice</span>
                    </label>
                    <button class="dashboard-filter dashboard-filter--wide" type="button"><i class="bi bi-calendar3"></i><span>09 April 2026</span></button>
                </div>
            </div>

            <div class="sales-grid-two">
                <div>
                    <h2 class="sales-section-title">Transaksi</h2>
                    <div class="sales-table-card">
                        <table class="sales-table">
                            <thead>
                                <tr>
                                    <th>Tipe Item</th>
                                    <th>Total Penjualan</th>
                                    <th>Jumlah Pengembalian</th>
                                    <th>Pendapatan Kotor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicesSalesRows as $row): ?>
                                    <tr>
                                        <td><?= e($row['label']) ?></td>
                                        <td><?= e((string) $row['sales']) ?></td>
                                        <td><?= e((string) $row['refund']) ?></td>
                                        <td><?= number_format((float) $row['gross'], 2, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h2 class="sales-section-title">Pendapatan Kotor</h2>
                    <div class="sales-empty-chart">
                        <div class="sales-empty-chart__icon">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="sales-empty-chart__text">No Result</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="services">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button"><i class="bi bi-shop"></i><span>Star Salon</span><i class="bi bi-chevron-down"></i></button>
                    <button class="dashboard-filter sales-filter-disabled" type="button"><span>Semua St...</span><i class="bi bi-chevron-down"></i></button>
                    <button class="dashboard-filter dashboard-filter--wide" type="button"><i class="bi bi-calendar3"></i><span>7 hari kedepan, 9 Apr 2026 - 15 Apr 2026</span></button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dashboard-filter sales-search-select" type="button"><span>Ref No.</span><i class="bi bi-chevron-down"></i></button>
                    <div class="sales-search-field"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Ref No.</th>
                            <th>Pelanggan</th>
                            <th>Staff</th>
                            <th>Nama Item</th>
                            <th>Sumberdaya</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($serviceRows === []): ?>
                            <tr><td colspan="9" class="sales-no-data">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach ($serviceRows as $row): ?>
                                <tr>
                                    <td><?= e($row['ref']) ?></td>
                                    <td><?= e($row['customer']) ?></td>
                                    <td><?= e($row['staff']) ?></td>
                                    <td><?= e($row['item']) ?></td>
                                    <td>-</td>
                                    <td><?= e($row['date']) ?></td>
                                    <td><?= e($row['time']) ?></td>
                                    <td><?= e($row['duration']) ?></td>
                                    <td><?= e($row['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="sales-scrollbar"></div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="classes">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button"><i class="bi bi-shop"></i><span>Star Salon</span><i class="bi bi-chevron-down"></i></button>
                    <button class="dashboard-filter sales-filter-disabled" type="button"><span>Semua St...</span><i class="bi bi-chevron-down"></i></button>
                    <button class="dashboard-filter dashboard-filter--wide" type="button"><i class="bi bi-calendar3"></i><span>7 hari kedepan, 9 Apr 2026 - 15 Apr 2026</span></button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dashboard-filter sales-search-select" type="button"><span>Nama kelas</span><i class="bi bi-chevron-down"></i></button>
                    <div class="sales-search-field"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Ref No.</th>
                            <th>Nama Sesi</th>
                            <th>Slot Digunakan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Lokasi</th>
                            <th>Staff</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($classes === []): ?>
                            <tr><td colspan="9" class="sales-no-data">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td>CLS-<?= e((string) $class['id']) ?></td>
                                    <td><?= e($class['name']) ?></td>
                                    <td><?= e((string) $class['booked']) ?>/<?= e((string) $class['slot']) ?></td>
                                    <td><?= e(substr($class['schedule'], 0, 10)) ?></td>
                                    <td><?= e(substr($class['schedule'], 11, 5)) ?></td>
                                    <td>2h</td>
                                    <td>Star Salon</td>
                                    <td>Staff #<?= e((string) $class['staff_id']) ?></td>
                                    <td>ACTIVE</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="sales-scrollbar"></div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="invoices">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button"><i class="bi bi-shop"></i><span>Star Salon</span><i class="bi bi-chevron-down"></i></button>
                    <label class="sales-backdate">
                        <span>Backdate</span>
                        <input type="checkbox">
                    </label>
                    <button class="dashboard-filter dashboard-filter--wide" type="button"><i class="bi bi-calendar3"></i><span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span></button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dashboard-filter sales-search-select" type="button"><span>Nomor Faktur</span><i class="bi bi-chevron-down"></i></button>
                    <div class="sales-search-field"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Faktur</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Faktur</th>
                            <th>Lokasi</th>
                            <th>Tips</th>
                            <th>Pendapatan Kotor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="js-sales-invoice-rows">
                        <?php if ($invoiceRows === []): ?>
                            <tr><td colspan="7" class="sales-no-data">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach ($invoiceRows as $row): ?>
                                <tr class="js-sales-invoice-row"
                                    data-invoice="<?= e($row['invoice']) ?>"
                                    data-customer="<?= e($row['customer']) ?>"
                                    data-date="<?= e($row['date']) ?>"
                                    data-time="<?= e($row['time']) ?>"
                                    data-location="<?= e($row['location']) ?>"
                                    data-gross="<?= e((string) $row['gross']) ?>"
                                    data-status="<?= e($row['status']) ?>"
                                    data-payment-method="<?= e($row['payment_method']) ?>"
                                    data-items="<?= e(json_encode($row['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                                    <td><?= e($row['invoice']) ?></td>
                                    <td><?= e($row['customer']) ?></td>
                                    <td><?= e($row['date']) ?></td>
                                    <td><?= e($row['location']) ?></td>
                                    <td><?= money($row['tips']) ?></td>
                                    <td><?= money($row['gross']) ?></td>
                                    <td><button class="sales-status-pill js-sales-invoice-open" type="button"><?= e($row['status']) ?></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="vouchers">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <button class="sales-refresh-btn" type="button">Refresh</button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter sales-filter-disabled" type="button"><i class="bi bi-calendar3"></i><span>Diterbitkan</span></button>
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <button class="dashboard-filter sales-search-select" type="button"><span>Nama Pelanggan</span><i class="bi bi-chevron-down"></i></button>
                    <div class="sales-search-field"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Kadaluarsa</th>
                            <th>Faktur</th>
                            <th>Pelanggan</th>
                            <th>Kode</th>
                            <th>Total</th>
                            <th>Digunakan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($voucherRows === []): ?>
                            <tr><td colspan="8" class="sales-no-data">No Data</td></tr>
                        <?php else: ?>
                            <?php foreach ($voucherRows as $row): ?>
                                <tr>
                                    <td><?= e($row['name']) ?></td>
                                    <td><?= e($row['expired']) ?></td>
                                    <td><?= e($row['invoice']) ?></td>
                                    <td><?= e($row['customer']) ?></td>
                                    <td><?= e($row['code']) ?></td>
                                    <td><?= money($row['total']) ?></td>
                                    <td><?= e((string) $row['used']) ?></td>
                                    <td><?= e($row['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="sales-scrollbar"></div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="cash-drawer">
            <div class="sales-cash-header">
                <div class="sales-cash-header__info">
                    <i class="bi bi-pin-map-fill"></i>
                    <span>Kasir di lokasi: Star Salon</span>
                </div>
                <button class="dashboard-filter dashboard-filter--wide" type="button"><i class="bi bi-calendar3"></i><span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span></button>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Staff</th>
                            <th>Total Diharapkan</th>
                            <th>Total Terhitung</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cashDrawerRows as $row): ?>
                            <tr>
                                <td><?= e($row['date']) ?></td>
                                <td><?= e($row['staff']) ?></td>
                                <td><?= number_format((float) $row['expected'], 2, ',', '.') ?></td>
                                <td><?= number_format((float) $row['actual'], 2, ',', '.') ?></td>
                                <td><span class="sales-status-pill"><?= e($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sales-pagination">
                    <button type="button"><i class="bi bi-chevron-left"></i></button>
                    <span>1</span>
                    <button type="button"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="cash-flow">
            <div class="sales-cashflow-header">
                <div class="sales-cashflow-card">
                    <div class="sales-cashflow-card__left">
                        <div class="sales-cashflow-card__icon"><i class="bi bi-shop"></i></div>
                        <div>
                            <h2>Kas Masuk/Keluar</h2>
                            <span>Star Salon</span>
                        </div>
                    </div>
                    <div class="sales-cashflow-card__right">
                        <div class="sales-cashflow-total">
                            <span>Total</span>
                            <strong><?= money($cashFlowTotal) ?></strong>
                        </div>
                        <div class="sales-cashflow-range"><i class="bi bi-calendar3"></i><span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span></div>
                    </div>
                </div>
                <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
            </div>
            <div class="sales-table-card sales-table-card--wide">
                <table class="sales-table sales-table--wide">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Judul</th>
                            <th>Tipe</th>
                            <th>Dibuat Oleh</th>
                            <th>Jumlah</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cash_movements as $movement): ?>
                            <tr>
                                <td><?= e(substr($movement['date'], 0, 10)) ?></td>
                                <td><?= e(ucwords(str_replace('_', ' ', $movement['type']))) ?></td>
                                <td><?= e($movement['type']) ?></td>
                                <td>Admin</td>
                                <td><?= money($movement['amount']) ?></td>
                                <td><?= e($movement['note']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="sales-fab-wrapper">
        <div class="sales-fab-menu" id="salesFabMenu">
            <button class="sales-fab-menu__item" type="button" data-bs-toggle="modal" data-bs-target="#salesAgendaModal">Agenda Baru</button>
            <button class="sales-fab-menu__item" type="button" data-bs-toggle="modal" data-bs-target="#salesClassModal">Kelas Baru</button>
            <button class="sales-fab-menu__item" type="button" data-bs-toggle="modal" data-bs-target="#salesCashModal">Kas Masuk/Keluar</button>
        </div>
        <button class="sales-fab js-sales-fab" type="button" data-sales-fab-icon="plus">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<section class="sales-invoice-view js-sales-invoice-view" hidden aria-label="Lihat faktur">
    <div class="sales-invoice-view__header">
        <div></div>
        <h2>Lihat Faktur</h2>
        <button class="sales-invoice-view__close js-sales-invoice-close" type="button" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sales-invoice-view__body">
        <div class="sales-invoice-view__left">
            <div class="sales-invoice-paper">
                <div class="sales-invoice-paper__brand">
                    <div class="sales-invoice-paper__logo"><i class="bi bi-shop"></i></div>
                    <strong>Star Salon</strong>
                    <span>Star Salon - Jl. Raya Inpres No.04, RT.4/RW.10, P. Tengah,<br>Kec. Kramat jati, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13540</span>
                </div>
                <div class="sales-invoice-paper__meta">
                    <strong class="js-sales-invoice-number">Faktur</strong>
                    <span class="js-sales-invoice-date"></span>
                </div>
                <div class="sales-invoice-paper__items js-sales-invoice-items"></div>
                <div class="sales-invoice-paper__totals">
                    <div><span>Sub Total</span><strong class="js-sales-invoice-subtotal">Rp 0,00</strong></div>
                    <div><span>Total</span><strong class="js-sales-invoice-total">Rp 0,00</strong></div>
                    <div><span>Grand total</span><strong class="js-sales-invoice-grand-total">Rp 0,00</strong></div>
                    <div class="js-sales-invoice-payment-line" hidden><span>CASH</span><strong class="js-sales-invoice-paid-total">Rp 0,00</strong></div>
                    <div><span>Sisa pembayaran</span><strong class="js-sales-invoice-remaining">Rp 0,00</strong></div>
                </div>
                <div class="sales-invoice-paper__footer">Penjualan oleh Rayhan Doni Pramana</div>
            </div>
            <div class="sales-invoice-floating-actions">
                <button type="button" class="js-sales-invoice-download" aria-label="Download faktur"><i class="bi bi-download"></i></button>
                <button type="button" class="js-sales-invoice-print" aria-label="Print faktur"><i class="bi bi-printer"></i></button>
            </div>
        </div>
        <aside class="sales-invoice-view__right">
            <h3 class="js-sales-invoice-customer">Walk-In</h3>
            <div class="sales-invoice-status js-sales-invoice-status">PAID</div>
            <div class="sales-invoice-meta js-sales-invoice-meta"></div>
            <div class="sales-invoice-share">
                <button class="js-sales-invoice-copy" type="button"><i class="bi bi-link-45deg"></i><span>Copy link</span></button>
                <button class="js-sales-invoice-email" type="button"><i class="bi bi-envelope"></i><span>Email</span></button>
                <button class="js-sales-invoice-whatsapp" type="button"><i class="bi bi-whatsapp"></i><span>whatsapp</span></button>
            </div>
            <div class="sales-invoice-actions">
                <button type="button">Lainnya <i class="bi bi-caret-down-fill"></i></button>
                <button class="js-sales-invoice-close" type="button">Tutup</button>
            </div>
        </aside>
    </div>
</section>

<div class="modal fade" id="salesAgendaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content sales-agenda-modal">
            <div class="sales-agenda-modal__header">
                <div></div>
                <h2>Agenda Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="sales-agenda-modal__body">
                <div class="sales-agenda-left">
                    <div class="sales-agenda-searchbar">
                        <i class="bi bi-arrow-left"></i>
                        <span>Cari service...</span>
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="sales-agenda-chips">
                        <span class="sales-chip">Paket Layanan</span>
                        <span class="sales-chip">Hair Cut</span>
                        <span class="sales-chip">Hair Treatment</span>
                        <span class="sales-chip">Hair Coloring</span>
                    </div>
                    <div class="sales-service-grid">
                        <?php foreach (array_slice($services, 0, 5) as $service): ?>
                            <div class="sales-service-card">
                                <div class="sales-service-card__thumb"><?= e(substr($service['name'], 0, 2)) ?></div>
                                <div class="sales-service-card__body">
                                    <strong><?= e($service['name']) ?></strong>
                                    <span><?= e((string) round($service['duration'] / 60, 1)) ?>h • <?= money($service['price']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="sales-agenda-footer">
                        <div class="sales-agenda-footer__summary">0 Layanan • Rp 0</div>
                        <button class="sales-agenda-footer__action" type="button">Tambahkan 0 Layanan</button>
                    </div>
                </div>
                <div class="sales-agenda-right">
                    <div class="sales-agenda-customer">
                        <div class="sales-agenda-customer__avatar"><i class="bi bi-emoji-smile"></i></div>
                        <div>
                            <strong>Daniel</strong>
                            <span>tag oyen</span>
                        </div>
                        <button type="button" class="sales-agenda-more"><i class="bi bi-three-dots"></i></button>
                    </div>
                    <div class="sales-agenda-actions">
                        <button type="button" disabled>Checkout</button>
                        <button type="button" disabled>Simpan Agenda</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content calendar-modal">
            <div class="modal-header border-0">
                <h3 class="panel-subtitle">Kelas Baru</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3">
                    <div class="col-12"><input class="form-control" type="text" placeholder="Nama kelas"></div>
                    <div class="col-md-6"><input class="form-control js-datepicker" type="text" placeholder="Tanggal"></div>
                    <div class="col-md-6"><input class="form-control" type="text" placeholder="Jam"></div>
                    <div class="col-md-6"><input class="form-control" type="text" placeholder="Durasi"></div>
                    <div class="col-md-6"><input class="form-control" type="number" placeholder="Slot"></div>
                    <div class="col-12"><button class="btn btn-dark rounded-pill px-4" type="button">Simpan Kelas</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesCashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content calendar-modal">
            <div class="modal-header border-0">
                <h3 class="panel-subtitle">Kas Masuk/Keluar</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3">
                    <div class="col-md-6">
                        <select class="form-select">
                            <option>Kas Masuk</option>
                            <option>Kas Keluar</option>
                        </select>
                    </div>
                    <div class="col-md-6"><input class="form-control" type="number" placeholder="Jumlah"></div>
                    <div class="col-12"><input class="form-control" type="text" placeholder="Judul"></div>
                    <div class="col-12"><textarea class="form-control" rows="3" placeholder="Catatan"></textarea></div>
                    <div class="col-12"><button class="btn btn-dark rounded-pill px-4" type="button">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
