<?php
$summary = $bookingSummary ?? [];
$date = $summary['date'] ?? new DateTimeImmutable('today');
if (!$date instanceof DateTimeImmutable) {
    $date = new DateTimeImmutable('today');
}
$primaryItem = $summary['primary_item'] ?? [];
$itemName = (string) ($primaryItem['name'] ?? 'Layanan');
$itemImage = (string) ($primaryItem['image'] ?? '');
$totalDuration = (int) ($summary['total_duration'] ?? 0);
$totalPrice = (float) ($summary['total_price'] ?? 0);
$selectedTime = (string) ($summary['selected_time'] ?? '');
$endTime = (string) ($summary['end_time'] ?? $selectedTime);
$staffName = (string) ($summary['staff_name'] ?? 'StarStyle');
$availableStaff = is_array($summary['available_staff'] ?? null) ? $summary['available_staff'] : [];
$selectedStaffId = (int) ($summary['selected_staff_id'] ?? ($availableStaff[0]['id'] ?? 0));
$timeOptions = is_array($summary['time_options'] ?? null) ? $summary['time_options'] : [];
$dayLabels = [
    'Sun' => 'Minggu',
    'Mon' => 'Senin',
    'Tue' => 'Selasa',
    'Wed' => 'Rabu',
    'Thu' => 'Kamis',
    'Fri' => 'Jumat',
    'Sat' => 'Sabtu',
];
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
$dayName = $dayLabels[$date->format('D')] ?? $date->format('l');
$monthName = $monthLabels[$date->format('M')] ?? $date->format('M');
$formattedDate = sprintf('%s, %s %s %s', $dayName, $date->format('d'), $monthName, $date->format('Y'));
$currentTimeLabel = $selectedTime !== '' ? $selectedTime . ' - ' . $endTime : 'Pilih jam tersedia';
?>

<section class="booking-summary-screen">
    <div class="booking-summary-screen__shell">
        <header class="booking-picker-header booking-summary-screen__header">
            <a class="booking-picker-back" href="<?= e(url('/booking/time')) ?>" aria-label="Kembali ke pilih waktu">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Ringkasan</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <div class="booking-summary-screen__date"><?= e($formattedDate) ?></div>

        <article class="booking-summary-card">
            <div class="booking-summary-card__top">
                <div class="booking-summary-card__service">
                    <span class="booking-summary-card__thumb">
                        <?php if ($itemImage !== ''): ?>
                            <img src="<?= e($itemImage) ?>" alt="<?= e($itemName) ?>">
                        <?php else: ?>
                            <i class="bi bi-scissors"></i>
                        <?php endif; ?>
                    </span>
                    <span class="booking-summary-card__copy">
                        <strong><?= e($itemName) ?></strong>
                        <small><?= e((string) $totalDuration) ?>m</small>
                        <span><?= e(money($totalPrice)) ?></span>
                    </span>
                </div>

                <button class="booking-summary-card__staff js-booking-summary-staff-toggle" type="button" aria-expanded="false" aria-label="Pilih staf">
                    <span class="booking-summary-card__staff-badge">0</span>
                    <small class="js-booking-summary-staff-label"><?= e($staffName) ?></small>
                    <i class="bi bi-pencil"></i>
                </button>
            </div>

            <div class="booking-summary-card__staff-picker" hidden>
                <div class="booking-summary-card__staff-picker-label">Pilih staf</div>
                <div class="booking-summary-card__staff-options">
                    <?php foreach ($availableStaff as $index => $staff): ?>
                        <?php
                        $staffOptionName = (string) ($staff['name'] ?? 'Staff');
                        $staffOptionRole = trim((string) ($staff['role'] ?? ''));
                        $staffOptionPhoto = (string) ($staff['photo_data_url'] ?? '');
                        ?>
                        <button
                            class="booking-summary-card__staff-option<?= (int) ($staff['id'] ?? 0) === $selectedStaffId || ($selectedStaffId === 0 && $index === 0) ? ' is-selected' : '' ?>"
                            type="button"
                            data-staff-label="<?= e($staffOptionName) ?>"
                            data-staff-id="<?= e((string) ($staff['id'] ?? 0)) ?>"
                        >
                            <span class="booking-summary-card__staff-option-avatar">
                                <?php if ($staffOptionPhoto !== ''): ?>
                                    <img src="<?= e($staffOptionPhoto) ?>" alt="<?= e($staffOptionName) ?>">
                                <?php else: ?>
                                    <i class="bi bi-person"></i>
                                <?php endif; ?>
                            </span>
                            <span class="booking-summary-card__staff-option-copy">
                                <strong><?= e($staffOptionName) ?></strong>
                                <?php if ($staffOptionRole !== ''): ?>
                                    <small><?= e($staffOptionRole) ?></small>
                                <?php endif; ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="booking-summary-card__bottom">
                <div class="booking-summary-card__time">
                    <i class="bi bi-clock"></i>
                    <strong class="js-booking-summary-time-label"><?= e($currentTimeLabel) ?></strong>
                </div>
                <button class="booking-summary-card__expand js-booking-summary-time-toggle" type="button" aria-expanded="false" aria-label="Pilih jam tersedia">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>

                <div class="booking-summary-card__time-picker" hidden>
                    <?php foreach ($timeOptions as $index => $timeOption): ?>
                        <?php $timeLabel = $timeOption['start'] . ' - ' . $timeOption['end']; ?>
                    <button
                        class="booking-summary-card__time-option<?= $timeOption['start'] === $selectedTime || ($index === 0 && $selectedTime === '') ? ' is-selected' : '' ?>"
                        type="button"
                        data-time-start="<?= e($timeOption['start']) ?>"
                        data-time-end="<?= e($timeOption['end']) ?>"
                        data-time-label="<?= e($timeLabel) ?>"
                        data-available-staff-ids="<?= e(implode(',', array_map('intval', (array) ($timeOption['available_staff_ids'] ?? [])))) ?>"
                    >
                        <span class="booking-summary-card__time-option-icon"><i class="bi bi-clock"></i></span>
                        <strong><?= e($timeLabel) ?></strong>
                    </button>
                <?php endforeach; ?>
            </div>
        </article>

        <form method="post" action="<?= e(url('/booking/confirmation-selection')) ?>">
            <?= csrf_field() ?>
            <input class="js-booking-summary-selected-staff-input" type="hidden" name="selected_staff_id" value="<?= e((string) $selectedStaffId) ?>">
            <input class="js-booking-summary-selected-time-input" type="hidden" name="selected_time" value="<?= e($selectedTime) ?>">
            <button class="booking-summary-screen__submit" type="submit">Pesan sekarang</button>
        </form>
    </div>
</section>
