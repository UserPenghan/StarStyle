<?php
$payment = $bookingPayment ?? [];
$totalPrice = (float) ($payment['total_price'] ?? 0);
?>

<section class="booking-payment-screen">
    <div class="booking-payment-screen__shell">
        <header class="booking-picker-header booking-payment-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/confirmation')) ?>" aria-label="Kembali ke konfirmasi pesanan">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Pembayaran</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <form method="post" action="<?= e(url('/booking/payment/qris')) ?>" class="booking-payment-form js-booking-payment-form">
            <?= csrf_field() ?>
            <input class="js-booking-payment-method-input" type="hidden" name="payment_method" value="QRIS">

            <section class="booking-payment-total-card">
                <div>
                    <small>Bayar</small>
                    <strong><?= e(money($totalPrice)) ?></strong>
                </div>
                <i class="bi bi-chevron-right"></i>
            </section>

            <button class="booking-payment-method is-qris is-selected js-booking-payment-method" type="button" data-payment-method="QRIS" aria-pressed="true">
                <span class="booking-payment-method__radio"></span>
                <span class="booking-payment-method__logo">QR</span>
                <span class="booking-payment-method__copy">
                    <strong>QRIS</strong>
                    <small>Scan QR untuk menyelesaikan booking</small>
                </span>
            </button>

            <button class="booking-payment-submit js-booking-payment-submit" type="submit">Continue</button>
        </form>
    </div>
</section>
