<?php
$confirmation = $bookingConfirmation ?? [];
$date = $confirmation['date'] ?? new DateTimeImmutable('today');
if (!$date instanceof DateTimeImmutable) {
    $date = new DateTimeImmutable('today');
}
$primaryItem = $confirmation['primary_item'] ?? [];
$itemName = (string) ($primaryItem['name'] ?? 'Layanan');
$itemImage = (string) ($primaryItem['image'] ?? '');
$selectedTime = (string) ($confirmation['selected_time'] ?? '03:00');
$totalDuration = (int) ($confirmation['total_duration'] ?? 0);
$totalPrice = (float) ($confirmation['total_price'] ?? 0);
$locationName = (string) ($confirmation['location_name'] ?? 'Star Salon');
$businessHours = (string) ($confirmation['business_hours'] ?? '09:00 - 20:00');
$businessAddress = (string) ($confirmation['business_address'] ?? '');
$selectedStaff = is_array($confirmation['selected_staff'] ?? null) ? $confirmation['selected_staff'] : [];
$customer = is_array($confirmation['customer'] ?? null) ? $confirmation['customer'] : [];
$isLoggedIn = !empty($confirmation['is_logged_in']);
$expandedItems = is_array($confirmation['expanded_items'] ?? null) ? $confirmation['expanded_items'] : [];
$monthLabels = [
    'Jan' => 'Jan',
    'Feb' => 'Feb',
    'Mar' => 'Mar',
    'Apr' => 'Apr',
    'May' => 'Mei',
    'Jun' => 'Jun',
    'Jul' => 'Jul',
    'Aug' => 'Agu',
    'Sep' => 'Sep',
    'Oct' => 'Okt',
    'Nov' => 'Nov',
    'Dec' => 'Des',
];
$monthName = $monthLabels[$date->format('M')] ?? $date->format('M');
$staffName = (string) ($selectedStaff['name'] ?? 'Staff');
$staffId = (int) ($selectedStaff['id'] ?? 0);
$cancelReasons = [
    'Rebooking',
    'Canceled because payment time is expired',
    'Appointment invoice not paid',
    'Appointment Made by Mistake',
    'Other',
];
?>

<section class="booking-confirmation-screen">
    <div class="booking-confirmation-screen__shell">
        <header class="booking-picker-header booking-confirmation-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/summary')) ?>" aria-label="Kembali ke ringkasan">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Konfirmasi Pemesanan</h1>
            <span class="booking-confirmation-screen__time"><?= e(date('H:i')) ?></span>
        </header>

        <form method="post" action="<?= e(url('/booking/payment')) ?>" class="booking-confirmation-form">
            <?= csrf_field() ?>

            <article class="booking-confirmation-card">
                <div class="booking-confirmation-card__top">
                    <span class="booking-confirmation-card__thumb">
                        <?php if ($itemImage !== ''): ?>
                            <img src="<?= e($itemImage) ?>" alt="<?= e($itemName) ?>">
                        <?php else: ?>
                            <i class="bi bi-scissors"></i>
                        <?php endif; ?>
                    </span>
                    <div class="booking-confirmation-card__business">
                        <strong><?= e($locationName) ?></strong>
                        <small><?= e($businessHours) ?></small>
                        <span><?= e($businessAddress) ?></span>
                    </div>
                    <div class="booking-confirmation-card__date-pill">
                        <small><?= e($monthName) ?></small>
                        <strong><?= e($date->format('d')) ?></strong>
                    </div>
                </div>

                <div class="booking-confirmation-card__summary">
                    <div class="booking-confirmation-card__time-row">
                        <i class="bi bi-clock"></i>
                        <div>
                            <strong>Mulai dari <?= e($selectedTime) ?></strong>
                            <small><?= e($itemName) ?> dengan <?= e($staffName) ?></small>
                        </div>
                    </div>
                    <div class="booking-confirmation-card__total-row">
                        <span class="booking-confirmation-card__count"><?= e((string) count($expandedItems)) ?></span>
                        <strong>Total <?= e(money($totalPrice)) ?></strong>
                    </div>
                </div>
            </article>

            <?php if ($isLoggedIn): ?>
                <section class="booking-confirmation-panel">
                    <p class="booking-confirmation-panel__caption">Akun Anda sudah terdaftar. Data berikut akan dipakai untuk konfirmasi pesanan.</p>
                    <div class="booking-confirmation-profile">
                        <div>
                            <span>Nama lengkap</span>
                            <strong><?= e((string) ($customer['name'] ?? '-')) ?></strong>
                        </div>
                        <div>
                            <span>Email</span>
                            <strong><?= e((string) ($customer['email'] ?? '-')) ?></strong>
                        </div>
                        <div>
                            <span>Nomor telepon</span>
                            <strong><?= e((string) ($customer['phone'] ?? '-')) ?></strong>
                        </div>
                    </div>
                    <div class="booking-confirmation-field">
                        <label for="bookingNotes">Catatan pesanan</label>
                        <textarea id="bookingNotes" name="notes" rows="3" placeholder="Tambahkan catatan untuk tim salon"></textarea>
                    </div>
                </section>
            <?php else: ?>
                <section class="booking-confirmation-panel">
                    <p class="booking-confirmation-panel__caption">Tambahkan detail informasi anda untuk mengkonfirmasi pesanan.</p>
                    <div class="booking-confirmation-field">
                        <label for="bookingCustomerName">Nama lengkap</label>
                        <input id="bookingCustomerName" type="text" name="customer_name" value="<?= e((string) old('customer_name', '')) ?>" required>
                    </div>
                    <div class="booking-confirmation-field">
                        <label for="bookingCustomerEmail">Email</label>
                        <input id="bookingCustomerEmail" type="email" name="customer_email" value="<?= e((string) old('customer_email', '')) ?>" placeholder="nama@email.com">
                    </div>
                    <div class="booking-confirmation-field">
                        <label for="bookingCustomerPhone">Nomor telepon</label>
                        <input id="bookingCustomerPhone" type="tel" name="customer_phone" value="<?= e((string) old('customer_phone', '')) ?>" placeholder="+62 812 3456 7890" required>
                    </div>
                    <div class="booking-confirmation-field">
                        <label for="bookingGuestNotes">Catatan pesanan</label>
                        <textarea id="bookingGuestNotes" name="notes" rows="3" placeholder="Tambahkan catatan untuk tim salon"><?= e((string) old('notes', '')) ?></textarea>
                    </div>
                </section>
            <?php endif; ?>

            <p class="booking-confirmation-note">Pastikan data yang anda isikan sudah benar. Kami akan mengirimkan e-ticket ke alamat email yang anda daftarkan.</p>

            <button class="booking-confirmation-cancel js-booking-cancel-open" type="button">Batalkan pesanan</button>
            <button class="booking-confirmation-submit" type="submit">Konfirmasi Pesanan</button>
        </form>
    </div>

    <div class="booking-cancel-modal" hidden>
        <div class="booking-cancel-modal__backdrop js-booking-cancel-close"></div>
        <div class="booking-cancel-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="bookingCancelTitle">
            <h2 id="bookingCancelTitle" class="booking-cancel-modal__title">Apakah kamu yakin ingin membatalkan pemesanan ini?</h2>
            <div class="booking-cancel-modal__reasons">
                <?php foreach ($cancelReasons as $index => $reason): ?>
                    <button class="booking-cancel-modal__reason<?= $index === 0 ? ' is-selected' : '' ?>" type="button" data-cancel-reason="<?= e($reason) ?>">
                        <?= e($reason) ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="booking-cancel-modal__actions">
                <button class="booking-cancel-modal__back js-booking-cancel-close" type="button">Kembali</button>
                <a class="booking-cancel-modal__confirm" href="<?= e(url('/')) ?>">Batalkan pesanan</a>
            </div>
        </div>
    </div>
</section>
