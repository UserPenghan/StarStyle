<?php
$selection = $bookingSelection ?? [];
$date = $selection['date'] ?? new DateTimeImmutable('today');
if (!$date instanceof DateTimeImmutable) {
    $date = new DateTimeImmutable('today');
}
$primaryItem = $selection['primary_item'] ?? [];
$itemName = (string) ($primaryItem['name'] ?? 'Layanan');
$itemImage = (string) ($primaryItem['image'] ?? '');
$totalDuration = (int) ($selection['total_duration'] ?? 0);
$dayMap = [
    'Sun' => 'Min',
    'Mon' => 'Sen',
    'Tue' => 'Sel',
    'Wed' => 'Rab',
    'Thu' => 'Kam',
    'Fri' => 'Jum',
    'Sat' => 'Sab',
];
$dayName = $dayMap[$date->format('D')] ?? $date->format('D');
$selectedTime = (string) ($selection['selected_time'] ?? '');
$slots = is_array($selection['slots'] ?? null) ? $selection['slots'] : [];
$businessHours = trim((string) ($selection['business_hours'] ?? ''));
$currentTime = trim((string) ($selection['current_time'] ?? ''));
$timezone = trim((string) ($selection['timezone'] ?? ''));
$isClosed = !empty($selection['is_closed']);
$morningSlots = [];
$eveningSlots = [];

foreach ($slots as $slot) {
    if (!is_array($slot) || empty($slot['time'])) {
        continue;
    }

    $hour = (int) substr((string) $slot['time'], 0, 2);
    if ($hour < 12) {
        $morningSlots[] = $slot;
        continue;
    }

    $eveningSlots[] = $slot;
}

$hasAvailableSlots = array_reduce($slots, static fn (bool $carry, array $slot): bool => $carry || !empty($slot['available']), false);
?>

<section class="booking-time-section">
    <div class="booking-time-shell">
        <header class="booking-picker-header">
            <a class="booking-picker-back" href="<?= e(url('/booking/services?date=' . $date->format('Y-m-d'))) ?>" aria-label="Kembali ke pilih item">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Pilih Waktu</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <div class="booking-time-day-banner">
            <span class="booking-time-day-banner__check"><i class="bi bi-check-lg"></i></span>
            <strong><?= e($dayName) ?></strong>
            <small><?= e($date->format('d')) ?></small>
        </div>

        <article class="booking-time-service-card">
            <span class="booking-time-service-card__thumb">
                <?php if ($itemImage !== ''): ?>
                    <img src="<?= e($itemImage) ?>" alt="<?= e($itemName) ?>">
                <?php else: ?>
                    <i class="bi bi-scissors"></i>
                <?php endif; ?>
            </span>
            <span class="booking-time-service-card__copy">
                <strong><?= e($itemName) ?></strong>
                <small><?= e((string) $totalDuration) ?>min</small>
            </span>
        </article>

        <p class="booking-time-caption">Pilih jam mulai yang masih tersedia.</p>
        <?php if ($businessHours !== '' || $currentTime !== ''): ?>
            <p class="booking-time-helper">
                <?php if ($businessHours !== ''): ?>
                    Jam salon: <?= e($businessHours) ?>
                <?php endif; ?>
                <?php if ($currentTime !== ''): ?>
                    <?= $businessHours !== '' ? ' • ' : '' ?>Waktu lokal: <?= e($currentTime) ?><?= $timezone !== '' ? ' (' . e($timezone) . ')' : '' ?>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if ($isClosed): ?>
            <p class="booking-time-empty">Salon tutup di hari ini. Silakan pilih tanggal lain.</p>
        <?php elseif (!$hasAvailableSlots): ?>
            <p class="booking-time-empty">Belum ada slot kosong pada tanggal ini. Coba pilih hari lain.</p>
        <?php endif; ?>

        <?php if ($morningSlots !== []): ?>
            <section class="booking-time-slot-group">
                <h2>Pagi</h2>
                <div class="booking-time-slot-grid js-booking-time-slot-grid">
                    <?php foreach ($morningSlots as $slot): ?>
                        <?php
                        $slotTime = (string) ($slot['time'] ?? '');
                        $isAvailable = !empty($slot['available']);
                        ?>
                        <button class="booking-time-slot<?= $slotTime === $selectedTime ? ' is-selected' : '' ?><?= !$isAvailable ? ' is-muted' : '' ?>" type="button" data-time-slot="<?= e($slotTime) ?>">
                            <?= e($slotTime) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($eveningSlots !== []): ?>
            <section class="booking-time-slot-group">
                <h2>Siang & Sore</h2>
                <div class="booking-time-slot-grid js-booking-time-slot-grid">
                    <?php foreach ($eveningSlots as $slot): ?>
                        <?php
                        $slotTime = (string) ($slot['time'] ?? '');
                        $isAvailable = !empty($slot['available']);
                        ?>
                        <button class="booking-time-slot<?= $slotTime === $selectedTime ? ' is-selected' : '' ?><?= !$isAvailable ? ' is-muted' : '' ?>" type="button" data-time-slot="<?= e($slotTime) ?>">
                            <?= e($slotTime) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <form class="booking-time-submit-form js-booking-time-submit-form" method="post" action="<?= e(url('/booking/summary')) ?>">
            <?= csrf_field() ?>
            <input class="js-booking-time-selected-input" type="hidden" name="selected_time" value="<?= e($selectedTime) ?>">
            <button class="booking-time-continue" type="submit"<?= $selectedTime === '' ? ' disabled' : '' ?>>Lanjutkan</button>
        </form>
    </div>
</section>
