<?php
$transactions = $transactions ?? [];
$bookings = $bookings ?? [];
$staffMembers = $staff ?? [];
$customersList = $customers ?? [];
$voucherList = $vouchers ?? [];
$classList = $classes ?? [];

$completedBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'completed'));
$confirmedBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'confirmed'));
$pendingBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'pending'));
$cancelledBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'cancelled'));
$noShowBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'no_show'));
$onlineBookings = count(array_filter($bookings, fn (array $booking): bool => in_array($booking['channel'], ['Online', 'Portal Customer', 'Instagram', 'WhatsApp'], true)));
$paidTransactions = array_values(array_filter($transactions, fn (array $transaction): bool => $transaction['status'] === 'paid'));
$salesTotal = array_reduce($paidTransactions, function (float $carry, array $transaction): float {
    $lineTotal = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['qty'] * $item['price']), 0.0);
    return $carry + $lineTotal - (float) $transaction['discount'];
}, 0.0);
$averageSale = count($paidTransactions) > 0 ? $salesTotal / count($paidTransactions) : 0.0;
$occupiedHours = round(($confirmedBookings + $completedBookings) * 1.5, 1);
$blockedHours = round(count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'cancelled')) * 0.5 + 10, 1);
$workingHours = max(24.0, count($staffMembers) * 6.0);
$unbookedHours = max(0, $workingHours - $occupiedHours);
$returningCustomers = count(array_filter($customersList, fn (array $customer): bool => ($customer['loyalty_points'] ?? 0) >= 100));
$newCustomers = max(0, count($customersList) - $returningCustomers);

$overviewCards = [
    [
        'title' => 'Total Appointment',
        'value' => (string) count($bookings),
        'change' => sprintf('%d%%', count($bookings) > 0 ? round((($completedBookings + $confirmedBookings) / count($bookings)) * 100) : 0),
        'details' => [
            sprintf('NOT COMPLETED %d (%d%%)', $pendingBookings, count($bookings) > 0 ? round(($pendingBookings / count($bookings)) * 100) : 0),
            sprintf('COMPLETED %d (%d%%)', $completedBookings, count($bookings) > 0 ? round(($completedBookings / count($bookings)) * 100) : 0),
            sprintf('CANCELED %d (%d%%)', $cancelledBookings, count($bookings) > 0 ? round(($cancelledBookings / count($bookings)) * 100) : 0),
            sprintf('NO SHOW %d (%d%%)', $noShowBookings, count($bookings) > 0 ? round(($noShowBookings / count($bookings)) * 100) : 0),
        ],
    ],
    [
        'title' => 'Online Appointments',
        'value' => sprintf('%d%%', count($bookings) > 0 ? round(($onlineBookings / count($bookings)) * 100) : 0),
        'change' => sprintf('%d%%', count($bookings) > 0 ? round(($confirmedBookings / count($bookings)) * 100) : 0),
        'details' => [
            sprintf('NOT COMPLETED %d (%d%%)', $pendingBookings, count($bookings) > 0 ? round(($pendingBookings / count($bookings)) * 100) : 0),
            sprintf('COMPLETED %d (%d%%)', $completedBookings, count($bookings) > 0 ? round(($completedBookings / count($bookings)) * 100) : 0),
            sprintf('CANCELED %d (%d%%)', $cancelledBookings, count($bookings) > 0 ? round(($cancelledBookings / count($bookings)) * 100) : 0),
            sprintf('NO SHOW %d (%d%%)', $noShowBookings, count($bookings) > 0 ? round(($noShowBookings / count($bookings)) * 100) : 0),
        ],
    ],
    [
        'title' => 'Occupancy',
        'value' => sprintf('%d%%', $workingHours > 0 ? round(($occupiedHours / $workingHours) * 100) : 0),
        'change' => sprintf('%d%%', $workingHours > 0 ? round(($blockedHours / $workingHours) * 100) : 0),
        'details' => [
            sprintf('Working Hours %.0f Hours (100%%)', $workingHours),
            sprintf('Booked Hours %.1f Hours (%d%%)', $occupiedHours, $workingHours > 0 ? round(($occupiedHours / $workingHours) * 100) : 0),
            sprintf('Blocked Hours %.1f Hours (%d%%)', $blockedHours, $workingHours > 0 ? round(($blockedHours / $workingHours) * 100) : 0),
            sprintf('Unbooked Hours %.1f Hours (%d%%)', $unbookedHours, $workingHours > 0 ? round(($unbookedHours / $workingHours) * 100) : 0),
        ],
    ],
    [
        'title' => 'Total Sales',
        'value' => number_format($salesTotal, 2, ',', '.'),
        'change' => sprintf('%d%%', $salesTotal > 0 ? 100 : 0),
        'details' => [
            sprintf('Services %s (%d%%)', number_format($salesByType['service'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['service'] ?? 0) / $salesTotal) * 100) : 0),
            sprintf('Products %s (%d%%)', number_format($salesByType['product'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['product'] ?? 0) / $salesTotal) * 100) : 0),
            sprintf('Class %s (%d%%)', number_format(array_sum(array_map(fn (array $class): int => $class['booked'], $classList)) * 0, 2, ',', '.'), 0),
            sprintf('Voucher %s (%d%%)', number_format($salesByType['voucher'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['voucher'] ?? 0) / $salesTotal) * 100) : 0),
        ],
    ],
    [
        'title' => 'Average Sale',
        'value' => number_format($averageSale, 2, ',', '.'),
        'change' => sprintf('%d%%', count($paidTransactions) > 0 ? 100 : 0),
        'details' => [
            sprintf('Sales %d (%d)', count($paidTransactions), count($paidTransactions)),
            sprintf('Avg. Service Sale %s', number_format(($salesByType['service'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
            sprintf('Avg. Product Sale %s', number_format(($salesByType['product'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
            sprintf('Avg. Class Sale %s', number_format(0, 2, ',', '.')),
            sprintf('Avg. Voucher Sale %s', number_format(($salesByType['voucher'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
        ],
    ],
    [
        'title' => 'Client Retention (Sales)',
        'value' => sprintf('%d%%', count($customersList) > 0 ? round(($returningCustomers / count($customersList)) * 100) : 0),
        'change' => sprintf('%d%%', count($customersList) > 0 ? round(($newCustomers / count($customersList)) * 100) : 0),
        'details' => [
            sprintf('Returning %d (%d%%)', $returningCustomers, count($customersList) > 0 ? round(($returningCustomers / count($customersList)) * 100) : 0),
            sprintf('New %d (%d%%)', $newCustomers, count($customersList) > 0 ? round(($newCustomers / count($customersList)) * 100) : 0),
            sprintf('Walk-In %d (%d%%)', count($bookings) - $onlineBookings, count($bookings) > 0 ? round(((count($bookings) - $onlineBookings) / count($bookings)) * 100) : 0),
        ],
    ],
];

$reportCards = [
    [
        'key' => 'finance',
        'icon' => 'pie-chart',
        'title' => 'Keuangan',
        'description' => 'Pantau keseluruhan keuangan Anda termasuk penjualan, pengembalian uang, pajak, pembayaran, dan lainnya.',
        'items' => ['Ringkasan keuangan', 'Ringkasan pembayaran', 'Log pembayaran', 'Ringkasan pajak', 'Tips dikumpulkan', 'Ringkasan diskon'],
    ],
    [
        'key' => 'sales',
        'icon' => 'file-earmark-text',
        'title' => 'Penjualan',
        'description' => 'Analisis kinerja bisnis Anda dengan membandingkan penjualan di seluruh produk, staf, saluran, dan lainnya.',
        'items' => ['Penjualan harian', 'Penjualan per staf', 'Penjualan per layanan'],
    ],
    [
        'key' => 'inventory',
        'icon' => 'box-seam',
        'title' => 'Inventori',
        'description' => 'Pantau tingkat stok produk dan penyesuaian yang dilakukan, analisis kinerja penjualan produk, biaya konsumsi, dan lainnya.',
        'items' => ['Stok rendah', 'Mutasi stok', 'Penyesuaian stok'],
    ],
    [
        'key' => 'voucher',
        'icon' => 'tag',
        'title' => 'Voucher',
        'description' => 'Lacak total liabilitas terutang Anda serta penjualan voucher dan aktivitas penukaran.',
        'items' => ['Ringkasan voucher', 'Voucher aktif', 'Aktivitas penukaran'],
    ],
    [
        'key' => 'agenda',
        'icon' => 'calendar3',
        'title' => 'Agenda',
        'description' => 'Lihat proyeksi pendapatan dari agenda yang akan datang, lacak tingkat pembatalan dan alasannya.',
        'items' => ['Agenda mendatang', 'Pembatalan', 'No show'],
    ],
    [
        'key' => 'staff',
        'icon' => 'people',
        'title' => 'Staf',
        'description' => 'Track jam kerja staff, komisi, dan tip.',
        'items' => ['Jam kerja', 'Komisi', 'Tip staf'],
    ],
    [
        'key' => 'customers',
        'icon' => 'emoji-smile',
        'title' => 'Pelanggan',
        'description' => 'Dapatkan wawasan tentang bagaimana klien berinteraksi dengan bisnis Anda dan siapa pembelanja utama Anda.',
        'items' => ['Pelanggan baru', 'Retensi pelanggan', 'Loyalitas'],
    ],
    [
        'key' => 'class-plan',
        'icon' => 'toggles2',
        'title' => 'Plan Kelas',
        'description' => 'Lacak total liabilitas terutang Anda serta penjualan perencanaan kelas dan aktivitas penukaran.',
        'items' => ['Penjualan plan kelas', 'Aktivitas penukaran'],
    ],
    [
        'key' => 'loyalty',
        'icon' => 'emoji-smile',
        'title' => 'Point Loyalitas',
        'description' => 'Lacak total poin loyalitas Anda dari setiap pelanggan serta aktivitas penukaran mereka.',
        'items' => ['Saldo poin', 'Penukaran poin', 'Pelanggan teratas'],
    ],
];
?>

<section class="analytics-shell js-analytics-shell">
    <div class="analytics-tabs">
        <button class="analytics-tab is-active" type="button" data-analytics-tab="overview">Beranda</button>
        <button class="analytics-tab" type="button" data-analytics-tab="reports">Laporan</button>
    </div>

    <div class="analytics-panels">
        <section class="analytics-panel is-active" data-analytics-panel="overview">
            <div class="analytics-toolbar">
                <div class="analytics-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter dashboard-filter--shop analytics-staff-filter" type="button">
                        <span>Semua Staff</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
                <div class="analytics-toolbar__group analytics-toolbar__group--end">
                    <button class="dashboard-filter dashboard-filter--wide" type="button">
                        <i class="bi bi-calendar3"></i>
                        <span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span>
                    </button>
                </div>
            </div>

            <div class="analytics-overview-grid">
                <?php foreach ($overviewCards as $card): ?>
                    <article class="analytics-overview-card">
                        <h3><?= e($card['title']) ?></h3>
                        <div class="analytics-overview-card__value">
                            <strong><?= e($card['value']) ?></strong>
                            <span>— <?= e($card['change']) ?></span>
                        </div>
                        <div class="analytics-overview-card__details">
                            <?php foreach ($card['details'] as $detail): ?>
                                <div><?= e($detail) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="analytics-panel" data-analytics-panel="reports">
            <div class="analytics-report-grid">
                <?php foreach ($reportCards as $index => $report): ?>
                    <button class="analytics-report-card <?= $index === 0 ? 'is-selected' : '' ?>" type="button" data-report-card data-report-key="<?= e($report['key']) ?>">
                        <div class="analytics-report-card__icon"><i class="bi bi-<?= e($report['icon']) ?>"></i></div>
                        <div class="analytics-report-card__content">
                            <h3><?= e($report['title']) ?></h3>
                            <p><?= e($report['description']) ?></p>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="analytics-report-popover" data-report-popover>
                <div class="analytics-report-popover__head">
                    <input type="text" placeholder="Masukkan kata kunci">
                </div>
                <div class="analytics-report-popover__list">
                    <?php foreach ($reportCards as $report): ?>
                        <div class="analytics-report-group <?= $report['key'] === 'finance' ? 'is-active' : '' ?>" data-report-group="<?= e($report['key']) ?>">
                            <?php foreach ($report['items'] as $itemIndex => $item): ?>
                                <button class="analytics-report-item <?= $report['key'] === 'finance' && $itemIndex === 0 ? 'is-active' : '' ?>" type="button" data-report-item data-report-target="<?= e($report['key']) ?>" data-report-title="<?= e($item) ?>">
                                    <span><?= e($item) ?></span>
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="analytics-report-popover__close" type="button" data-report-close>
                    <i class="bi bi-x-lg"></i>
                    <span>Tutup</span>
                </button>
            </div>

            <section class="analytics-report-detail" data-analytics-detail>
                <div class="analytics-report-detail__header">
                    <button class="analytics-back-link" type="button" data-report-back>
                        <i class="bi bi-arrow-left"></i>
                        <span>Ringkasan keuangan</span>
                    </button>
                    <div class="analytics-detail-toolbar">
                        <button class="dashboard-filter dashboard-filter--shop" type="button">
                            <i class="bi bi-shop"></i>
                            <span>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button class="dashboard-filter dashboard-filter--wide" type="button">
                            <i class="bi bi-calendar3"></i>
                            <span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span>
                        </button>
                        <label class="analytics-switch">
                            <span class="analytics-switch__track"></span>
                            <span>Berdasarkan tanggal pembayaran</span>
                        </label>
                        <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    </div>
                </div>

                <div class="analytics-report-tables">
                    <div class="analytics-table-block">
                        <h3>Sales</h3>
                        <table class="sales-table analytics-table">
                            <thead>
                                <tr>
                                    <th>Penjualan Kotor</th>
                                    <th>Diskon</th>
                                    <th>Diskon Total Penjualan</th>
                                    <th>Pengembalian</th>
                                    <th>Penjualan Bersih</th>
                                    <th>Total Pajak</th>
                                    <th>Total Pembulatan</th>
                                    <th>Penggunaan Voucher</th>
                                    <th>Total Penjualan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?= e(number_format($salesTotal, 0, ',', '.')) ?></td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td><?= e(number_format($salesTotal, 0, ',', '.')) ?></td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>0,00</td>
                                    <td><?= e(number_format($salesTotal, 2, ',', '.')) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="analytics-table-block">
                        <h3>Payments</h3>
                        <table class="sales-table analytics-table analytics-table--empty">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Total</th>
                                    <th>Kembalian</th>
                                    <th>Jumlah Bersih</th>
                                    <th>Outstanding</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="sales-no-data">No Data</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="analytics-table-block">
                        <h3>Payment Invoice Other Period</h3>
                        <table class="sales-table analytics-table analytics-table--empty">
                            <thead>
                                <tr>
                                    <th>Tanggal Faktur</th>
                                    <th>Nomor Faktur</th>
                                    <th>Tanggal Pembayaran</th>
                                    <th>Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="4" class="sales-no-data">No Data</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="analytics-table-block">
                        <h3>Tips</h3>
                        <table class="sales-table analytics-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td>0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="analytics-table-block">
                        <h3>Vouchers</h3>
                        <table class="sales-table analytics-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td><?= e((string) count($voucherList)) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <button class="analytics-detail-fab" type="button" aria-label="Panel laporan">
                    <i class="bi bi-grid"></i>
                </button>
            </section>
        </section>
    </div>
</section>
