<section class="container py-5">
    <div class="section-head">
        <div>
            <span class="eyebrow">Catalog Layanan</span>
            <h1 class="section-title">Daftar layanan StarStyle</h1>
        </div>
        <a class="btn btn-dark rounded-pill px-4" href="<?= e(url('/booking')) ?>">Buat booking</a>
    </div>
    <?php foreach ($groups as $bundle): ?>
        <div class="mt-5">
            <h2 class="mb-3"><?= e($bundle['group']['name']) ?></h2>
            <div class="row g-4">
                <?php foreach ($bundle['services'] as $service): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="showcase-card h-100">
                            <div class="badge text-bg-light mb-3"><?= e($service['status']) ?></div>
                            <h3><?= e($service['name']) ?></h3>
                            <p><?= e($service['description']) ?></p>
                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span><?= e((string) $service['duration']) ?> menit</span>
                                <span><?= e(implode(', ', $service['variants'])) ?></span>
                            </div>
                            <div class="fw-bold fs-4"><?= money($service['price']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
