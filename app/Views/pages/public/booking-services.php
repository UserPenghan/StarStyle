<?php
$bundle = $selectedServiceBundle ?? ['group' => ['name' => 'Layanan'], 'services' => []];
$group = $bundle['group'] ?? ['name' => 'Layanan'];
$services = $bundle['services'] ?? [];
$groupName = (string) ($group['name'] ?? 'Layanan');
$groupImage = (string) ($group['image_data_url'] ?? '');
$coverImage = (string) (($bookingBusiness ?? [])['cover_image_url'] ?? '');
$today = new DateTimeImmutable('today');
$selectedDate = new DateTimeImmutable($_GET['date'] ?? $today->modify('+1 day')->format('Y-m-d'));
$selectedGroupId = (int) ($group['id'] ?? 0);
$selectedGroupCount = count($services);
$defaultRailEnd = $today->modify('+7 day');
$useSelectedRailStart = $selectedDate > $defaultRailEnd;
$railStartDate = $useSelectedRailStart ? $selectedDate : $today;
$dayLabels = [
    'Sun' => 'Min',
    'Mon' => 'Sen',
    'Tue' => 'Sel',
    'Wed' => 'Rab',
    'Thu' => 'Kam',
    'Fri' => 'Jum',
    'Sat' => 'Sab',
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
?>

<section class="booking-picker-section">
    <div class="booking-picker-shell js-booking-picker">
        <header class="booking-picker-header">
            <a class="booking-picker-back" href="<?= e(url('/booking')) ?>" aria-label="Kembali ke reservasi">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h1>Pilih Tanggal &amp; Item</h1>
            <span class="booking-picker-header__spacer" aria-hidden="true"></span>
        </header>

        <div class="booking-picker-dates" aria-label="Pilih tanggal">
            <?php for ($offset = 0; $offset < 8; $offset += 1): ?>
                <?php
                $date = $railStartDate->modify('+' . $offset . ' day');
                $isActive = $date->format('Y-m-d') === $selectedDate->format('Y-m-d');
                $dayKey = (string) $date->format('D');
                $monthKey = (string) $date->format('M');
                $weekday = (!$useSelectedRailStart && $offset === 0) ? 'Hari ini' : ($dayLabels[$dayKey] ?? $dayKey);
                $monthShort = $monthLabels[$monthKey] ?? $monthKey;
                ?>
                <a class="booking-picker-date<?= $isActive ? ' is-active' : '' ?>" href="<?= e(url('/booking/services?date=' . $date->format('Y-m-d') . '&group=' . (int) ($group['id'] ?? 0))) ?>">
                    <?php if ($isActive): ?>
                        <small><?= e($monthShort) ?></small>
                    <?php endif; ?>
                    <span><?= e($weekday) ?></span>
                    <strong><?= e($date->format('d')) ?></strong>
                </a>
            <?php endfor; ?>
            <button class="booking-picker-date-more js-booking-picker-date-toggle" type="button" aria-label="Tanggal lainnya" aria-expanded="false">
                <i class="bi bi-chevron-down"></i>
            </button>
            <input
                class="js-booking-picker-calendar"
                type="text"
                value="<?= e($selectedDate->format('Y-m-d')) ?>"
                data-group-id="<?= e((string) $selectedGroupId) ?>"
                hidden
            >
        </div>

        <section class="booking-picker-group-card js-booking-picker-group">
            <button class="booking-picker-group-card__head js-booking-picker-group-toggle" type="button" aria-expanded="true">
                <span class="booking-picker-group-card__thumb">
                    <?php if ($groupImage !== ''): ?>
                        <img src="<?= e($groupImage) ?>" alt="<?= e($groupName) ?>">
                    <?php elseif ($coverImage !== ''): ?>
                        <img src="<?= e($coverImage) ?>" alt="<?= e($groupName) ?>">
                    <?php else: ?>
                        <i class="bi bi-scissors"></i>
                    <?php endif; ?>
                </span>
                <strong><?= e($groupName) ?></strong>
                <span class="booking-picker-group-card__chevron"><i class="bi bi-chevron-down"></i></span>
            </button>
        </section>

        <div class="booking-picker-services js-booking-picker-services">
            <?php foreach ($services as $service): ?>
                <?php
                $serviceImage = (string) ($service['image_data_url'] ?? '');
                $variantDetails = $service['variant_details'] ?? [];
                $payload = [
                    'id' => (int) ($service['id'] ?? 0),
                    'name' => (string) ($service['name'] ?? 'Layanan'),
                    'description' => (string) ($service['description'] ?? ''),
                    'price' => (float) ($service['price'] ?? 0),
                    'duration' => (int) ($service['duration'] ?? 0),
                    'image' => $serviceImage !== '' ? $serviceImage : $coverImage,
                    'variants' => [],
                ];

                if (is_array($variantDetails) && $variantDetails !== []) {
                    foreach ($variantDetails as $variantIndex => $variant) {
                        $variantName = trim((string) ($variant['variant_name'] ?? ''));
                        $payload['variants'][] = [
                            'id' => (string) ($service['id'] ?? 0) . '-' . $variantIndex,
                            'name' => $variantName !== '' ? $variantName : (string) ($service['name'] ?? 'Layanan'),
                            'description' => (string) ($service['description'] ?? ''),
                            'price' => (float) ($variant['price'] ?? $service['price'] ?? 0),
                            'duration' => (int) ($variant['duration_minutes'] ?? $service['duration'] ?? 0),
                        ];
                    }
                }

                if ($payload['variants'] === []) {
                    $payload['variants'][] = [
                        'id' => (string) ($service['id'] ?? 0) . '-0',
                        'name' => (string) ($service['name'] ?? 'Layanan'),
                        'description' => (string) ($service['description'] ?? ''),
                        'price' => (float) ($service['price'] ?? 0),
                        'duration' => (int) ($service['duration'] ?? 0),
                    ];
                }
                ?>
                <article class="booking-picker-service js-booking-picker-service" data-service-id="<?= e((string) ($service['id'] ?? 0)) ?>" data-service-base-name="<?= e((string) ($service['name'] ?? 'Layanan')) ?>" data-service-payload="<?= e((string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                    <span class="booking-picker-service__thumb">
                        <?php if ($serviceImage !== ''): ?>
                            <img src="<?= e($serviceImage) ?>" alt="<?= e((string) ($service['name'] ?? 'Layanan')) ?>">
                        <?php elseif ($coverImage !== ''): ?>
                            <img src="<?= e($coverImage) ?>" alt="<?= e((string) ($service['name'] ?? 'Layanan')) ?>">
                        <?php else: ?>
                            <i class="bi bi-scissors"></i>
                        <?php endif; ?>
                    </span>
                    <span class="booking-picker-service__copy">
                        <strong><?= e((string) ($service['name'] ?? 'Layanan')) ?></strong>
                        <small><?= e(money((float) ($service['price'] ?? 0))) ?> &#8226; <?= e((string) ($service['duration'] ?? 0)) ?>m</small>
                    </span>
                    <button class="booking-picker-service__action js-booking-picker-service-open" type="button" aria-label="Tambah <?= e((string) ($service['name'] ?? 'Layanan')) ?>">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                    <div class="booking-picker-service__stepper" hidden>
                        <button class="booking-picker-service__stepper-btn js-booking-picker-stepper-minus" type="button" aria-label="Kurangi jumlah">
                            <i class="bi bi-dash-lg"></i>
                        </button>
                        <strong class="js-booking-picker-stepper-count">1</strong>
                        <button class="booking-picker-service__stepper-btn js-booking-picker-stepper-plus" type="button" aria-label="Tambah jumlah">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button class="booking-picker-bookmark js-booking-picker-category-toggle" type="button" aria-label="Buka kategori" aria-expanded="false">
            <i class="bi bi-bookmarks"></i>
            <span>Kategori</span>
        </button>

        <aside class="booking-picker-category-panel" hidden>
            <div class="booking-picker-category-panel__head">
                <span class="booking-picker-category-panel__icon"><i class="bi bi-bookmarks"></i></span>
                <strong>Kategori</strong>
                <button class="booking-picker-category-panel__close js-booking-picker-category-close" type="button" aria-label="Tutup kategori">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="booking-picker-category-panel__body">
                <?php foreach (($serviceBundles ?? []) as $item): ?>
                    <?php
                    $itemGroup = $item['group'] ?? [];
                    $itemGroupId = (int) ($itemGroup['id'] ?? 0);
                    $itemCount = count($item['services'] ?? []);
                    $isCurrentGroup = $itemGroupId === $selectedGroupId;
                    ?>
                    <a class="booking-picker-category-item<?= $isCurrentGroup ? ' is-active' : '' ?>" href="<?= e(url('/booking/services?date=' . $selectedDate->format('Y-m-d') . '&group=' . $itemGroupId)) ?>">
                        <span class="booking-picker-category-item__icon">
                            <?php if ($isCurrentGroup): ?>
                                <i class="bi bi-heart-pulse"></i>
                            <?php else: ?>
                                <i class="bi bi-bookmark"></i>
                            <?php endif; ?>
                        </span>
                        <span class="booking-picker-category-item__label"><?= e((string) ($itemGroup['name'] ?? 'Layanan')) ?></span>
                        <span class="booking-picker-category-item__count"><?= e((string) $itemCount) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section class="booking-picker-selection-sheet" hidden>
            <div class="booking-picker-selection-sheet__dialog">
                <div class="booking-picker-selection-sheet__header">
                    <strong>Pilih layanan</strong>
                    <button class="booking-picker-selection-sheet__close js-booking-picker-sheet-close" type="button" aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="booking-picker-selection-sheet__visual">
                    <img class="js-booking-picker-sheet-image" src="" alt="">
                </div>
                <div class="booking-picker-selection-sheet__summary">
                    <strong class="js-booking-picker-sheet-title"></strong>
                    <small class="js-booking-picker-sheet-description"></small>
                </div>
                <div class="booking-picker-selection-sheet__count js-booking-picker-sheet-count"></div>
                <div class="booking-picker-selection-sheet__options js-booking-picker-sheet-options"></div>
                <div class="booking-picker-selection-sheet__qty">
                    <button class="booking-picker-selection-sheet__qty-btn js-booking-picker-sheet-minus" type="button" aria-label="Kurangi jumlah">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <strong class="js-booking-picker-sheet-qty">1</strong>
                    <button class="booking-picker-selection-sheet__qty-btn js-booking-picker-sheet-plus" type="button" aria-label="Tambah jumlah">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
                <div class="booking-picker-selection-sheet__actions">
                    <button class="booking-picker-selection-sheet__cancel js-booking-picker-sheet-cancel" type="button">Batal</button>
                    <button class="booking-picker-selection-sheet__submit js-booking-picker-sheet-submit" type="button">Tambah</button>
                </div>
            </div>
        </section>

        <section class="booking-picker-bottom-bar" hidden>
            <button class="booking-picker-bottom-bar__summary" type="button">
                <strong class="js-booking-picker-bottom-count">0 item</strong>
                <small class="js-booking-picker-bottom-total">Rp0</small>
                <span class="booking-picker-bottom-bar__chevron"><i class="bi bi-chevron-down"></i></span>
            </button>
            <button class="booking-picker-bottom-bar__continue js-booking-picker-continue" type="button">Lanjutkan</button>
        </section>

        <section class="booking-picker-cost-panel" hidden>
            <div class="booking-picker-cost-panel__head">
                <strong>Tanggal Appointment</strong>
                <span class="js-booking-picker-cost-date"></span>
            </div>
            <div class="booking-picker-cost-panel__items js-booking-picker-cost-items"></div>
        </section>

        <form class="js-booking-picker-submit-form" method="post" action="<?= e(url('/booking/time')) ?>" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="date" value="<?= e($selectedDate->format('Y-m-d')) ?>">
            <input type="hidden" name="group_id" value="<?= e((string) $selectedGroupId) ?>">
            <input class="js-booking-picker-submit-items" type="hidden" name="items" value="[]">
        </form>
    </div>
</section>
