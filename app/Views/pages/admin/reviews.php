<?php
$reviews = $reviews ?? [];
$notifications = $notifications ?? [];
$logs = $logs ?? [];
$today = new DateTimeImmutable('today');
$rangeStart = $today->modify('-6 days');
$rangeLabel = sprintf('7 hari sebelumnya, %s - %s', $rangeStart->format('j M Y'), $today->format('j M Y'));
$averageRating = count($reviews) > 0
    ? array_sum(array_map(fn (array $review): int => (int) ($review['rating'] ?? 0), $reviews)) / count($reviews)
    : 0;
$ratingSummary = [];
for ($rating = 5; $rating >= 1; $rating--) {
    $ratingSummary[$rating] = count(array_filter($reviews, static fn (array $review): bool => (int) ($review['rating'] ?? 0) === $rating));
}
$logOptions = [];
foreach ($notifications as $notification) {
    $customer = trim((string) ($notification['customer'] ?? 'Pelanggan'));
    if ($customer === '') {
        $customer = 'Pelanggan';
    }
    $logOptions[$customer] = [
        'customer' => $customer,
        'email' => (string) ($notification['email'] ?? '-'),
        'type' => (string) ($notification['type_label'] ?? ucfirst((string) ($notification['type'] ?? 'Notification'))),
    ];
}
?>

<section class="reviews-shell js-reviews-shell">
    <div class="reviews-tabs">
        <button class="reviews-tab is-active" type="button" data-reviews-tab="customer">Customer Review</button>
        <button class="reviews-tab" type="button" data-reviews-tab="logs">Message Logs</button>
    </div>

    <div class="reviews-panels">
        <section class="reviews-panel is-active" data-reviews-panel="customer">
            <div class="reviews-toolbar">
                <div class="reviews-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span data-reviews-shop-label>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu">
                            <button class="dropdown-item analytics-filter-option is-active" type="button" data-reviews-shop-option="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter reviews-filter-rating" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span data-reviews-rating-label>All Ratings</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu reviews-rating-menu">
                            <button class="reviews-rating-option is-active" type="button" data-review-rating-option="All Ratings">
                                <strong>All Ratings</strong>
                            </button>
                            <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                                <button class="reviews-rating-option" type="button" data-review-rating-option="<?= e((string) $rating) ?>">
                                    <strong><?= e((string) $rating) ?></strong>
                                    <span class="reviews-rating-option__stars" aria-hidden="true">
                                        <?php for ($starIndex = 1; $starIndex <= 5; $starIndex++): ?>
                                            <i class="bi bi-star-fill <?= $starIndex <= $rating ? 'is-filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </button>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <button class="dashboard-filter dashboard-filter--wide" type="button" data-bs-toggle="modal" data-bs-target="#reviewsDateFilterModal">
                        <i class="bi bi-calendar3"></i>
                        <span data-reviews-range-label><?= e($rangeLabel) ?></span>
                    </button>
                </div>
                <div class="reviews-toolbar__group reviews-toolbar__group--end">
                    <label class="sales-search-field reviews-search">
                        <input class="js-reviews-search" type="search" placeholder="Cari review atau pelanggan" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="reviews-summary-card">
                <div class="reviews-summary-cell reviews-summary-cell--brand">
                    <div class="reviews-summary-icon"><i class="bi bi-shop"></i></div>
                    <strong>Star Salon</strong>
                </div>
                <div class="reviews-summary-cell">
                    <strong><?= e(number_format($averageRating, 1)) ?></strong>
                    <div class="reviews-stars">
                        <?php for ($index = 1; $index <= 5; $index++): ?>
                            <i class="bi bi-star-fill <?= $index <= round($averageRating) ? 'is-filled' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-chat-left-text"></i>
                    <strong><?= e((string) count($reviews)) ?> Review(s)</strong>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-bar-chart"></i>
                    <strong><?= e((string) ($ratingSummary[5] ?? 0)) ?> Bintang 5</strong>
                </div>
            </div>

            <div class="customers-table-card">
                <div class="voucher-discount-board">
                    <div class="voucher-discount-list js-reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <?php
                            $search = strtolower(trim(implode(' ', [
                                (string) ($review['customer'] ?? ''),
                                (string) ($review['feedback'] ?? ''),
                                (string) ($review['email'] ?? ''),
                                (string) ($review['agenda'] ?? ''),
                            ])));
                            ?>
                            <article
                                class="voucher-discount-item js-review-card"
                                data-review-rating="<?= e((string) ($review['rating'] ?? 0)) ?>"
                                data-review-date="<?= e(substr((string) ($review['date'] ?? ''), 0, 10)) ?>"
                                data-search="<?= e($search) ?>"
                            >
                                <strong><?= e((string) ($review['customer'] ?? 'Pelanggan')) ?></strong>
                                <span><?= e((string) ($review['agenda'] ?? 'Review')) ?></span>
                                <span><?= e((string) ($review['feedback'] ?? 'Tanpa komentar')) ?></span>
                                <span><?= e((string) ($review['date'] ?? '-')) ?></span>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($reviews === []): ?>
                            <div class="reviews-empty-card js-reviews-empty">
                                <div class="reviews-empty-card__icon"><i class="bi bi-chat-left"></i></div>
                                <h2>Belum ada review</h2>
                                <p>Review pelanggan yang sudah masuk dari database akan tampil di sini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="reviews-panel" data-reviews-panel="logs">
            <div class="reviews-toolbar">
                <div class="reviews-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter reviews-filter-rating reviews-filter-rating--logs" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span data-reviews-log-label>Pelanggan</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu reviews-filter-menu reviews-log-filter-menu">
                            <button class="reviews-log-filter-option is-active" type="button" data-review-log-option="Pelanggan" data-review-log-email="-" data-review-log-type="-">
                                <strong>Pelanggan</strong>
                                <span>Email</span>
                                <span>Tipe Notifikasi</span>
                            </button>
                            <?php foreach ($logOptions as $option): ?>
                                <button class="reviews-log-filter-option" type="button" data-review-log-option="<?= e($option['customer']) ?>" data-review-log-email="<?= e($option['email']) ?>" data-review-log-type="<?= e($option['type']) ?>">
                                    <strong><?= e($option['customer']) ?></strong>
                                    <span><?= e($option['email']) ?></span>
                                    <span><?= e($option['type']) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <label class="sales-search-field reviews-search">
                        <input class="js-reviews-log-search" type="search" placeholder="Cari log pesan" autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="inventory-table-card reviews-log-card">
                <div class="inventory-table-wrap reviews-log-table-wrap">
                    <table class="customers-table inventory-table reviews-log-table reviews-log-table--notifications">
                        <thead>
                            <tr>
                                <th>Pelanggan</th>
                                <th>Email</th>
                                <th>Tipe Notifikasi</th>
                                <th>Pesan</th>
                                <th>Waktu Terkirim</th>
                                <th>Agenda</th>
                            </tr>
                        </thead>
                        <tbody class="js-review-log-body">
                            <?php foreach ($notifications as $notification): ?>
                                <?php
                                $typeLabel = (string) ($notification['type_label'] ?? ucfirst((string) ($notification['type'] ?? 'Notification')));
                                $search = strtolower(trim(implode(' ', [
                                    (string) ($notification['customer'] ?? ''),
                                    (string) ($notification['email'] ?? ''),
                                    $typeLabel,
                                    (string) ($notification['title'] ?? ''),
                                    (string) ($notification['agenda'] ?? ''),
                                ])));
                                ?>
                                <tr
                                    class="js-review-log-row"
                                    data-log-customer="<?= e((string) ($notification['customer'] ?? 'Pelanggan')) ?>"
                                    data-search="<?= e($search) ?>"
                                >
                                    <td><?= e((string) ($notification['customer'] ?? 'Pelanggan')) ?></td>
                                    <td><?= e((string) ($notification['email'] ?? '-')) ?></td>
                                    <td><?= e($typeLabel) ?></td>
                                    <td><?= e((string) ($notification['title'] ?? '-')) ?></td>
                                    <td><?= e((string) ($notification['created_at'] ?? '-')) ?></td>
                                    <td><?= e((string) ($notification['agenda'] ?? 'Notification')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($notifications === []): ?>
                                <tr class="js-review-log-empty">
                                    <td colspan="6" class="sales-no-data">No Data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="inventory-table-card reviews-log-card reviews-log-card--secondary" style="margin-top:16px;">
                <div class="inventory-table-wrap reviews-log-table-wrap">
                    <table class="customers-table inventory-table reviews-log-table reviews-log-table--activity">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Aktor</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= e((string) ($log['time'] ?? '-')) ?></td>
                                    <td><?= e((string) ($log['actor'] ?? '-')) ?></td>
                                    <td><?= e((string) ($log['action'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($logs === []): ?>
                                <tr>
                                    <td colspan="3" class="sales-no-data">No Activity Logs</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</section>

<div class="modal fade" id="reviewsDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-reviews-date-preset is-active" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-reviews-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-reviews-start" type="text" value="<?= e($rangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-reviews-end" type="text" value="<?= e($today->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-reviews-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-reviews-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-reviews-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>
