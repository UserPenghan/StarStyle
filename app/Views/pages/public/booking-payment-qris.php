<?php
$payment = $bookingPaymentQris ?? [];
$totalPrice = (float) ($payment['total_price'] ?? 0);
$expiresAt = $payment['expires_at'] ?? new DateTimeImmutable('now');
if (!$expiresAt instanceof DateTimeImmutable) {
    $expiresAt = new DateTimeImmutable('now');
}
$customerName = (string) ($payment['customer_name'] ?? 'Star Style');
$qrisImageUrl = (string) ($payment['qris_image_url'] ?? '');
$detailItems = is_array($payment['detail_items'] ?? null) ? $payment['detail_items'] : [];
?>

<section class="booking-qris-screen">
    <div class="booking-qris-screen__shell">
        <header class="booking-picker-header booking-qris-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/payment')) ?>" aria-label="Kembali ke pembayaran">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Pembayaran</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <section class="booking-qris-card">
            <div class="booking-qris-card__brand">
                <strong>Star Style</strong>
            </div>

            <div class="booking-qris-card__summary">
                <div>
                    <strong><?= e(money($totalPrice)) ?></strong>
                </div>
                <button class="booking-qris-card__details-toggle js-booking-qris-details-toggle" type="button" aria-expanded="false">Details <i class="bi bi-chevron-down"></i></button>
            </div>

            <div class="booking-qris-card__timer">
                Pay within <strong><?= e($expiresAt->format('H:i:s')) ?></strong>
            </div>

            <div class="booking-qris-card__panel">
                <div class="booking-qris-card__panel-head">
                    <strong>GoPay QRIS</strong>
                    <span>QRIS</span>
                </div>

                <div class="booking-qris-card__image-wrap">
                    <?php if ($qrisImageUrl !== ''): ?>
                        <img src="<?= e($qrisImageUrl) ?>" alt="QRIS <?= e($customerName) ?>">
                    <?php else: ?>
                        <div class="booking-qris-card__image-fallback">QRIS tidak ditemukan</div>
                    <?php endif; ?>
                </div>

                <div class="booking-qris-card__actions">
                    <a class="booking-qris-card__download" href="<?= e($qrisImageUrl) ?>" download="qris-booking-starstyle.jpg">Download QRIS</a>
                    <a class="booking-qris-card__status" href="<?= e(url('/booking/payment/proof')) ?>">Lanjutkan</a>
                </div>
            </div>
        </section>

        <div class="booking-qris-details" hidden>
            <div class="booking-qris-details__backdrop js-booking-qris-details-close"></div>
            <div class="booking-qris-details__sheet">
                <div class="booking-qris-details__head">
                    <strong>Order details</strong>
                    <button class="booking-qris-details__close js-booking-qris-details-close" type="button" aria-label="Tutup detail order">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="booking-qris-details__body">
                    <?php foreach ($detailItems as $item): ?>
                        <div class="booking-qris-details__row">
                            <span><?= e((string) ($item['label'] ?? '-')) ?></span>
                            <strong><?= e(money((float) ($item['total'] ?? 0))) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="booking-qris-details__total">
                        <span>Total</span>
                        <strong><?= e(money($totalPrice)) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
