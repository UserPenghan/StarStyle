<?php
$business = $bookingBusiness ?? [];
$venueName = (string) ($business['location_name'] ?? $business['name'] ?? 'Star Salon');
$venueHours = (string) ($business['hours'] ?? '09:00 - 20:00');
$venueAddress = (string) ($business['address'] ?? '');
$venuePhone = (string) ($business['hotline'] ?? '');
$venueEmail = (string) ($business['email'] ?? '');
$coverImage = (string) ($business['cover_image_url'] ?? '');
$venuePhoneLink = preg_replace('/[^0-9+]/', '', $venuePhone) ?? '';
$venueAddressShort = $venueAddress !== '' ? preg_replace('/\s+/', ' ', $venueAddress) : '';
$venueAddressShort = $venueAddressShort !== null ? trim($venueAddressShort) : '';
$activeTab = (string) ($_GET['tab'] ?? 'services');
$statusLabel = 'Jam operasional';
$statusClass = 'is-neutral';

if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $venueHours, $matches) === 1) {
    $now = new DateTimeImmutable('now');
    $openAt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $matches[1]) ?: null;
    $closeAt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $now->format('Y-m-d') . ' ' . $matches[2]) ?: null;

    if ($openAt instanceof DateTimeImmutable && $closeAt instanceof DateTimeImmutable) {
        $statusLabel = ($now >= $openAt && $now <= $closeAt) ? 'Buka' : 'Tutup';
        $statusClass = $statusLabel === 'Buka' ? 'is-open' : 'is-closed';
    }
}
?>

<section class="booking-experience-section">
    <div class="booking-experience-shell">
        <form method="post" action="<?= e(url('/booking/next')) ?>" class="booking-experience">
            <?= csrf_field() ?>

            <div class="booking-experience__top">
                <a class="booking-back-link" href="<?= e(url('/')) ?>" aria-label="Back ke beranda">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>

            <section class="booking-venue-card">
                <div class="booking-venue-card__visual">
                    <?php if ($coverImage !== ''): ?>
                        <img src="<?= e($coverImage) ?>" alt="<?= e($venueName) ?>">
                    <?php else: ?>
                        <div class="booking-venue-card__art" aria-hidden="true">
                            <span class="booking-venue-card__art-blur"></span>
                            <span class="booking-venue-card__art-wall"></span>
                            <span class="booking-venue-card__art-table"></span>
                            <span class="booking-venue-card__art-chair-back"></span>
                            <span class="booking-venue-card__art-chair-seat"></span>
                            <span class="booking-venue-card__art-chair-leg"></span>
                            <span class="booking-venue-card__art-plant"></span>
                        </div>
                    <?php endif; ?>
                    <span class="booking-venue-card__floating" aria-hidden="true">
                        <i class="bi bi-person"></i>
                    </span>
                </div>
                <div class="booking-venue-card__body">
                    <strong class="booking-venue-card__title"><?= e($venueName) ?></strong>
                    <div class="booking-venue-card__meta">
                        <span class="booking-venue-card__status <?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
                        <span><?= e($venueHours) ?></span>
                    </div>
                    <?php if ($venueAddress !== ''): ?>
                        <p class="booking-venue-card__address"><?= e($venueAddress) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <nav class="booking-quick-nav" aria-label="Navigasi booking">
                <button class="booking-quick-nav__item<?= $activeTab === 'services' ? ' is-active' : '' ?>" type="submit" name="next_target" value="services">
                    <span class="booking-quick-nav__icon"><i class="bi bi-magic"></i></span>
                    <span class="booking-quick-nav__label">Layanan</span>
                </button>
                <button class="booking-quick-nav__item<?= $activeTab === 'contact' ? ' is-active' : '' ?>" type="submit" name="next_target" value="contact" formnovalidate>
                    <span class="booking-quick-nav__icon"><i class="bi bi-telephone"></i></span>
                    <span class="booking-quick-nav__label">Kontak</span>
                </button>
            </nav>

            <p class="booking-section-caption">Di mana layanan akan berlangsung?</p>

            <div class="booking-intro-stack">
                <button class="booking-option-card booking-option-card--location" type="submit" name="next_target" value="services">
                    <span class="booking-option-card__icon">
                        <i class="bi bi-shop"></i>
                    </span>
                    <span class="booking-option-card__copy">
                        <strong>Lokasi Penyedia layanan</strong>
                        <small><?= e($venueAddressShort !== '' ? $venueAddressShort : 'Layanan dilakukan di lokasi salon.') ?></small>
                    </span>
                    <span class="booking-option-card__arrow" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </button>

                <label class="booking-switch-card" for="bookingDefitToggle">
                    <span class="booking-switch-card__copy">
                        <strong>Hi DEFIT</strong>
                        <small>Aktifkan untuk memesan sebagai DEFIT</small>
                    </span>
                    <span class="booking-switch">
                        <input id="bookingDefitToggle" class="booking-switch__input" type="checkbox" name="is_defit" value="1">
                        <span class="booking-switch__track"></span>
                    </span>
                </label>
            </div>

            <?php if ($activeTab === 'contact'): ?>
                <section class="booking-contact-panel">
                    <div class="booking-contact-panel__row">
                        <strong>Telepon</strong>
                        <?php if ($venuePhoneLink !== ''): ?>
                            <a href="<?= e('tel:' . $venuePhoneLink) ?>"><?= e($venuePhone) ?></a>
                        <?php else: ?>
                            <span><?= e($venuePhone !== '' ? $venuePhone : '-') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="booking-contact-panel__row">
                        <strong>Email</strong>
                        <?php if ($venueEmail !== ''): ?>
                            <a href="<?= e('mailto:' . $venueEmail) ?>"><?= e($venueEmail) ?></a>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </div>
                    <div class="booking-contact-panel__row">
                        <strong>Alamat</strong>
                        <span><?= e($venueAddress !== '' ? $venueAddress : '-') ?></span>
                    </div>
                </section>
            <?php endif; ?>

        </form>
    </div>
</section>
