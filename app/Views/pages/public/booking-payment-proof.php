<?php
$proof = $bookingPaymentProof ?? [];
$totalPrice = (float) ($proof['total_price'] ?? 0);
$customerName = (string) ($proof['customer_name'] ?? 'Pelanggan');
?>

<section class="booking-proof-screen">
    <div class="booking-proof-screen__shell">
        <header class="booking-picker-header booking-proof-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/payment/qris')) ?>" aria-label="Kembali ke QRIS">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Upload Bukti</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <form method="post" action="<?= e(url('/booking/payment/complete')) ?>" enctype="multipart/form-data" class="booking-proof-form js-booking-proof-form">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_method" value="QRIS">

            <section class="booking-proof-card">
                <div class="booking-proof-card__top">
                    <small>Total Pembayaran</small>
                    <strong><?= e(money($totalPrice)) ?></strong>
                    <span><?= e($customerName) ?></span>
                </div>

                <label class="booking-proof-upload" for="paymentProofInput">
                    <span class="booking-proof-upload__icon"><i class="bi bi-cloud-arrow-up"></i></span>
                    <strong>Upload bukti pembayaran</strong>
                    <small>Pilih file JPG, PNG, atau WEBP</small>
                    <span class="booking-proof-upload__button">Pilih file</span>
                    <input id="paymentProofInput" class="js-booking-proof-input" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" hidden required>
                </label>

                <div class="booking-proof-file js-booking-proof-file" hidden>
                    <i class="bi bi-file-earmark-image"></i>
                    <span class="js-booking-proof-file-name"></span>
                </div>
            </section>

            <button class="booking-proof-submit js-booking-proof-submit" type="submit" disabled>Submit</button>
        </form>
    </div>
</section>
