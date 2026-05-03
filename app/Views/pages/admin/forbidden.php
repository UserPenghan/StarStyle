<section class="state-card">
    <div class="state-card__icon"><i class="bi bi-shield-lock"></i></div>
    <h1>Akses Ditolak</h1>
    <p><?= e($message ?? 'Permission Anda belum diaktifkan untuk modul ini.') ?></p>
    <a class="btn btn-dark rounded-pill px-4" href="<?= e(url('/dashboard')) ?>">Kembali ke dashboard</a>
</section>
