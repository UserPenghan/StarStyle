<section class="state-card">
    <div class="state-card__icon"><i class="bi bi-stars"></i></div>
    <h1><?= e($title ?? 'Halaman tidak ditemukan') ?></h1>
    <p><?= e($message ?? 'Halaman yang Anda cari belum tersedia.') ?></p>
    <a class="btn btn-dark rounded-pill px-4" href="<?= e(url('/')) ?>">Kembali ke beranda</a>
</section>
