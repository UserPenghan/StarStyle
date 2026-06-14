<?php
$customerName = (string) ($customer['name'] ?? 'Customer');
$initials = trim(implode('', array_map(static fn (string $part): string => strtoupper(substr($part, 0, 1)), array_slice(preg_split('/\s+/', $customerName) ?: [], 0, 2))));
$initials = $initials !== '' ? $initials : 'C';
$bookingCount = count($bookings);
$voucherCount = count($vouchers);
$activeVoucherCount = count(array_filter($vouchers, static fn (array $voucher): bool => in_array(strtolower((string) ($voucher['status'] ?? '')), ['aktif', 'active'], true)));
?>

<section class="customer-account-shell">
    <div class="container">
        <div class="customer-account-hero">
            <div>
                <span class="customer-account-hero__eyebrow">Customer Area</span>
                <h1>Halo, <?= e($customerName) ?></h1>
                <p>Semua jadwal booking dan voucher Anda dirapikan di satu tempat agar mudah dicek sebelum datang ke salon.</p>
            </div>
            <a class="customer-account-hero__action" href="<?= e(url('/booking')) ?>">
                <i class="bi bi-calendar2-plus"></i>
                <span>Booking baru</span>
            </a>
        </div>

        <div class="customer-account-grid">
            <aside class="customer-profile-panel">
                <div class="customer-profile-panel__avatar"><?= e($initials) ?></div>
                <span class="customer-profile-panel__label">Customer Profile</span>
                <h2><?= e($customerName) ?></h2>

                <div class="customer-profile-panel__details">
                    <div>
                        <i class="bi bi-person-vcard"></i>
                        <span><?= e($customer['member_id'] ?? '-') ?></span>
                    </div>
                    <div>
                        <i class="bi bi-telephone"></i>
                        <span><?= e($customer['phone'] ?? '-') ?></span>
                    </div>
                    <div>
                        <i class="bi bi-envelope"></i>
                        <span><?= e($customer['email'] ?? '-') ?></span>
                    </div>
                </div>

                <div class="customer-profile-panel__stats">
                    <div>
                        <span>Loyalty Point</span>
                        <strong><?= e((string) ($customer['loyalty_points'] ?? 0)) ?></strong>
                    </div>
                    <div>
                        <span>Booking</span>
                        <strong><?= e((string) $bookingCount) ?></strong>
                    </div>
                    <div>
                        <span>Voucher Aktif</span>
                        <strong><?= e((string) $activeVoucherCount) ?></strong>
                    </div>
                </div>

                <form method="post" action="<?= e(url('/customer/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="customer-profile-panel__logout" type="submit">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </aside>

            <div class="customer-account-main">
                <section class="customer-section-card">
                    <div class="customer-section-card__head">
                        <div>
                            <span>Histori</span>
                            <h2>Histori Booking</h2>
                        </div>
                        <div class="customer-section-card__count"><?= e((string) $bookingCount) ?> booking</div>
                    </div>

                    <div class="customer-booking-list">
                        <?php if (empty($bookings)): ?>
                            <div class="customer-empty-state">
                                <i class="bi bi-calendar-heart"></i>
                                <strong>Belum ada booking</strong>
                                <span>Mulai dengan memilih layanan favorit Anda.</span>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($bookings as $booking): ?>
                            <?php
                            $status = strtolower((string) ($booking['status'] ?? ''));
                            $statusClass = in_array($status, ['completed', 'paid', 'confirmed'], true) ? 'is-success' : (in_array($status, ['cancelled', 'failed'], true) ? 'is-danger' : 'is-info');
                            ?>
                            <article class="customer-booking-item">
                                <div class="customer-booking-item__icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="customer-booking-item__content">
                                    <strong><?= e($booking['reference'] ?? '-') ?></strong>
                                    <span><?= e($booking['start_at'] ?? '-') ?></span>
                                </div>
                                <span class="customer-status-pill <?= e($statusClass) ?>"><?= e($booking['status'] ?? '-') ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="customer-section-card">
                    <div class="customer-section-card__head">
                        <div>
                            <span>Benefit</span>
                            <h2>Voucher Tersedia</h2>
                        </div>
                        <div class="customer-section-card__count"><?= e((string) $voucherCount) ?> voucher</div>
                    </div>

                    <div class="customer-voucher-grid">
                        <?php if (empty($vouchers)): ?>
                            <div class="customer-empty-state">
                                <i class="bi bi-ticket-perforated"></i>
                                <strong>Voucher belum tersedia</strong>
                                <span>Voucher aktif akan muncul di sini.</span>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($vouchers as $voucher): ?>
                            <?php
                            $voucherStatus = strtolower((string) ($voucher['status'] ?? ''));
                            $voucherActive = in_array($voucherStatus, ['aktif', 'active'], true);
                            ?>
                            <article class="customer-voucher-ticket<?= $voucherActive ? ' is-active' : ' is-muted' ?>">
                                <div class="customer-voucher-ticket__mark">
                                    <i class="bi bi-ticket-perforated"></i>
                                </div>
                                <div>
                                    <strong><?= e($voucher['code'] ?? '-') ?></strong>
                                    <span>Berlaku hingga <?= e($voucher['expired_at'] ?? '-') ?></span>
                                </div>
                                <em><?= e($voucher['status'] ?? '-') ?></em>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </div>
</section>
