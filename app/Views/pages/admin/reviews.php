<?php
$averageRating = count($reviews) > 0
    ? array_sum(array_map(fn (array $review): int => (int) $review['rating'], $reviews)) / count($reviews)
    : 0;
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
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter reviews-filter-rating" type="button">
                        <span>All Ratings</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter dashboard-filter--wide" type="button">
                        <i class="bi bi-calendar3"></i>
                        <span>7 hari sebelumnya, 3 Apr 2026 - 9 Apr 2026</span>
                    </button>
                </div>
                <div class="reviews-toolbar__group reviews-toolbar__group--end">
                    <div class="sales-search-field reviews-search"><span>Search</span><i class="bi bi-search"></i></div>
                </div>
            </div>

            <div class="reviews-summary-card">
                <div class="reviews-summary-cell reviews-summary-cell--brand">
                    <div class="reviews-summary-icon"><i class="bi bi-shop"></i></div>
                    <strong>Star Salon</strong>
                </div>
                <div class="reviews-summary-cell">
                    <strong><?= e(number_format($averageRating, 0)) ?></strong>
                    <div class="reviews-stars">
                        <?php for ($index = 1; $index <= 5; $index++): ?>
                            <i class="bi bi-star-fill <?= $index <= round($averageRating) ? 'is-filled' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-emoji-smile"></i>
                    <strong><?= e((string) count($reviews)) ?> Review(s)</strong>
                </div>
                <div class="reviews-summary-cell">
                    <i class="bi bi-calendar3"></i>
                    <strong><?= e((string) max(1, count($reviews))) ?></strong>
                </div>
            </div>

            <div class="reviews-empty-card">
                <div class="reviews-empty-card__icon"><i class="bi bi-chat-left"></i></div>
                <h2>No Review On Current Date!</h2>
                <p>After an appointment held, customer can give reviews. Arrange date and filter to discover more.</p>
            </div>
        </section>

        <section class="reviews-panel" data-reviews-panel="logs">
            <div class="reviews-toolbar">
                <div class="reviews-toolbar__group">
                    <button class="dashboard-filter reviews-filter-rating" type="button">
                        <span>Pelanggan</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="sales-search-field reviews-search"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>

            <div class="customers-table-card">
                <table class="customers-table reviews-log-table">
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
                    <tbody>
                        <?php foreach ($notifications as $index => $notification): ?>
                            <tr>
                                <td><?= e($reviews[$index % max(1, count($reviews))]['customer'] ?? 'Customer') ?></td>
                                <td><?= e('customer' . ($index + 1) . '@starstyle.test') ?></td>
                                <td><?= e(ucfirst($notification['type'])) ?></td>
                                <td><?= e($notification['title']) ?></td>
                                <td><?= e($logs[$index]['time'] ?? '-') ?></td>
                                <td><?= e($index < count($reviews) ? 'Review Follow Up' : 'No Data') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($notifications === []): ?>
                            <tr>
                                <td colspan="6" class="sales-no-data">No Data</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
