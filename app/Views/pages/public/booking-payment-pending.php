<?php $confirmationEmail = trim((string) ($bookingCompletionEmail ?? '')); ?>
<section class="booking-pending-screen">
    <div class="booking-pending-screen__shell">
        <div class="booking-pending-card">
            <span class="booking-pending-card__icon"><i class="bi bi-hourglass-split"></i></span>
            <h1>Menunggu Respon Admin</h1>
            <p>Bukti pembayaran Anda sudah berhasil dikirim. Tim admin akan memeriksa pembayaran dan mengonfirmasi booking Anda secepatnya.</p>
            <?php if ($confirmationEmail !== ''): ?>
                <strong class="booking-pending-card__email"><?= e($confirmationEmail) ?></strong>
            <?php endif; ?>
            <a class="booking-pending-card__action" href="<?= e(url('/')) ?>">Kembali ke Landing Page</a>
        </div>
    </div>
</section>
