<?php
$topServices = array_slice(is_array($featuredServices ?? null) ? $featuredServices : [], 0, 4);
$topPackages = array_slice(is_array($packages ?? null) ? $packages : [], 0, 3);
$topReviews = array_slice(is_array($reviews ?? null) ? $reviews : [], 0, 3);
$heroVisual = '';

foreach ($topServices as $service) {
    $candidate = trim((string) ($service['image_data_url'] ?? ''));
    if ($candidate !== '') {
        $heroVisual = $candidate;
        break;
    }
}

$metricBooking = $heroMetrics[0]['value'] ?? 0;
$metricTransactions = $heroMetrics[1]['value'] ?? 0;
$metricPremium = $heroMetrics[2]['value'] ?? 0;
$businessName = (string) ($business['name'] ?? 'StarStyle Salon');
$businessCity = (string) ($business['city'] ?? 'Jakarta');
$businessHours = (string) ($business['hours'] ?? '09:00 - 20:00');
$businessAddress = (string) ($business['address'] ?? '');
$businessHotline = (string) ($business['hotline'] ?? '');
?>

<section class="salon-hero">
    <div class="container salon-hero__shell">
        <div class="salon-hero__frame">
            <div class="salon-hero__visual">
                <?php if ($heroVisual !== ''): ?>
                    <img class="salon-hero__image" src="<?= e($heroVisual) ?>" alt="<?= e($businessName) ?>">
                <?php endif; ?>
                <div class="salon-hero__backdrop"></div>
                <div class="salon-hero__spot salon-hero__spot--one"></div>
                <div class="salon-hero__spot salon-hero__spot--two"></div>

                <div class="salon-hero__content">
                    <span class="salon-hero__kicker">Blue Signature Salon Experience</span>
                    <h1 class="salon-hero__title">Radiate with salon care that feels polished, calm, and personal.</h1>
                    <p class="salon-hero__copy">
                        <?= e($businessName) ?> menghadirkan hair styling, coloring, treatment, dan booking experience yang rapi
                        untuk tamu yang ingin tampil segar tanpa proses yang ribet.
                    </p>
                    <div class="salon-hero__actions">
                        <a class="btn btn-dark btn-lg rounded-pill px-4" href="<?= e(url('/booking')) ?>">Reservasi Sekarang</a>
                        <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="<?= e(url('/services-catalog')) ?>">Lihat Treatment</a>
                    </div>
                </div>

                <div class="salon-hero__badge">
                    <strong><?= e((string) $metricBooking) ?> booking aktif</strong>
                    <span>Hari ini di <?= e($businessCity) ?></span>
                </div>
            </div>

            <div class="consultation-banner">
                <div class="consultation-banner__copy">
                    <span class="eyebrow text-white mb-2">Salon Consultation</span>
                    <h2>Butuh saran treatment yang pas untuk rambut dan scalp Anda?</h2>
                    <p>
                        Mulai dari smoothing, color refresh, sampai hair spa recovery. Tim kami bantu arahkan paket yang paling cocok
                        sebelum Anda booking.
                    </p>
                </div>
                <div class="consultation-banner__form">
                    <div class="consultation-banner__field">Nama depan</div>
                    <div class="consultation-banner__field">Nama belakang</div>
                    <div class="consultation-banner__field consultation-banner__field--wide">Nomor WhatsApp / Email</div>
                    <a class="consultation-banner__button" href="<?= e(url('/booking')) ?>">Request Consultation</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container salon-story">
    <div class="row g-5 align-items-center">
        <div class="col-lg-5">
            <span class="eyebrow">Welcome To <?= e($businessName) ?></span>
            <h2 class="section-title salon-story__title">Salon biru yang fokus pada hasil rapi, suasana nyaman, dan jadwal yang mudah diatur.</h2>
            <p class="salon-story__copy">
                Kami merancang pengalaman salon yang terasa premium namun tetap hangat. Mulai dari konsultasi awal, pemilihan stylist,
                hingga treatment selesai, semuanya dibuat lebih jelas dan nyaman untuk pelanggan.
            </p>
            <p class="salon-story__copy">
                Cocok untuk pelanggan yang ingin haircut presisi, coloring yang halus, atau perawatan rambut intensif dengan ritme pelayanan yang tenang.
            </p>

            <div class="salon-hours">
                <div class="salon-hours__title">Jam Operasional</div>
                <div class="salon-hours__grid">
                    <div>
                        <strong>Senin - Kamis</strong>
                        <span><?= e($businessHours) ?></span>
                    </div>
                    <div>
                        <strong>Jumat - Sabtu</strong>
                        <span><?= e($businessHours) ?></span>
                    </div>
                    <div>
                        <strong>Minggu</strong>
                        <span>By appointment</span>
                    </div>
                </div>
                <div class="salon-hours__meta">
                    <span><i class="bi bi-geo-alt"></i> <?= e($businessAddress !== '' ? $businessAddress : $businessCity) ?></span>
                    <span><i class="bi bi-telephone"></i> <?= e($businessHotline !== '' ? $businessHotline : 'Hubungi admin') ?></span>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="salon-gallery">
                <div class="salon-gallery__card salon-gallery__card--wide">
                    <span>Signature Service</span>
                    <strong><?= e((string) ($topServices[0]['name'] ?? 'Hair Color Refresh')) ?></strong>
                    <small><?= money((float) ($topServices[0]['price'] ?? 450000)) ?> • <?= e((string) ($topServices[0]['duration'] ?? 90)) ?> menit</small>
                </div>
                <div class="salon-gallery__card salon-gallery__card--tall">
                    <span>Stylist Match</span>
                    <strong><?= e((string) $metricPremium) ?> layanan premium</strong>
                    <small>Pilihan treatment warna, repair, dan styling modern.</small>
                </div>
                <div class="salon-gallery__card">
                    <span>Weekly Flow</span>
                    <strong><?= e((string) $metricTransactions) ?> transaksi minggu ini</strong>
                    <small>Operasional lebih tertata lewat booking dan checkout yang sinkron.</small>
                </div>
                <div class="salon-gallery__card">
                    <span>Client Promise</span>
                    <strong>Finish yang clean</strong>
                    <small>Ritual salon yang fokus pada kenyamanan, detail, dan hasil tahan rapi.</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="salon-showcase">
    <div class="container py-5">
        <div class="salon-showcase__head">
            <span class="eyebrow">Professional Salon Treatments</span>
            <h2 class="section-title">Pilihan paket favorit untuk rambut yang lebih sehat, lembut, dan stylish.</h2>
            <p>
                Paket-paket ini disusun dari layanan yang paling sering dipilih pelanggan untuk makeover singkat maupun treatment rutin.
            </p>
        </div>

        <div class="row g-4 mt-1">
            <?php foreach ($topPackages as $index => $package): ?>
                <div class="col-lg-4 col-md-6">
                    <article class="salon-package-card">
                        <div class="salon-package-card__media salon-package-card__media--<?= e((string) (($index % 3) + 1)) ?>">
                            <span><?= e(sprintf('%02d', $index + 1)) ?></span>
                        </div>
                        <div class="salon-package-card__body">
                            <h3><?= e((string) ($package['name'] ?? 'Signature Package')) ?></h3>
                            <p><?= e((string) (($package['description'] ?? '') ?: implode(' + ', (array) ($package['items'] ?? [])))) ?></p>
                            <div class="salon-package-card__meta">
                                <strong><?= money((float) ($package['price'] ?? 0)) ?></strong>
                                <a href="<?= e(url('/booking')) ?>">Book now</a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="container salon-reviews">
    <div class="salon-showcase__head salon-showcase__head--left">
        <span class="eyebrow">Customer Love</span>
        <h2 class="section-title">Review pelanggan yang datang untuk cut, color, dan hair treatment.</h2>
        <p>Testimoni ini membantu calon tamu memahami nuansa pelayanan kami sebelum datang ke salon.</p>
    </div>

    <div class="row g-4 mt-1">
        <?php foreach ($topReviews as $review): ?>
            <div class="col-lg-4 col-md-6">
                <article class="salon-review-card">
                    <div class="salon-review-card__stars"><?= str_repeat('&#9733;', max(1, (int) ($review['rating'] ?? 0))) ?></div>
                    <p><?= e((string) ($review['feedback'] ?? 'Pelayanan memuaskan dan hasil treatment terasa lebih ringan.')) ?></p>
                    <strong><?= e((string) ($review['customer'] ?? 'Pelanggan StarStyle')) ?></strong>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
