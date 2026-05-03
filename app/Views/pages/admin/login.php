<section class="auth-card">
    <div class="auth-card__visual">
        <span class="eyebrow">Zenwell-inspired UI</span>
        <h1>Portal Internal StarStyle</h1>
        <p>Monitoring agenda, POS, CRM, inventory, voucher, analitik, dan hak akses staff dalam satu dashboard modern.</p>
        <div class="demo-list">
            <div><strong>Admin</strong><span>admin@starstyle.test / password123</span></div>
            <div><strong>Staff</strong><span>stylist@starstyle.test / password123</span></div>
        </div>
    </div>
    <div class="auth-card__form">
        <div class="soft-card mb-4">
            <div class="fw-semibold">Login Internal</div>
            <div class="text-muted small">Akses penuh untuk Admin, akses terbatas untuk Staff.</div>
        </div>
        <?php if (!empty($success)): ?><div class="alert alert-success rounded-4 border-0"><?= e($success) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger rounded-4 border-0"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('/login')) ?>" class="vstack gap-3">
            <?= csrf_field() ?>
            <div>
                <label class="form-label">Email</label>
                <input class="form-control form-control-lg" type="email" name="email" value="<?= e(old('email')) ?>" placeholder="admin@starstyle.test">
            </div>
            <div>
                <label class="form-label">Password</label>
                <input class="form-control form-control-lg" type="password" name="password" placeholder="password123">
            </div>
            <button class="btn btn-dark btn-lg rounded-pill" type="submit">Masuk ke Dashboard</button>
            <a class="btn btn-light btn-lg rounded-pill" href="<?= e(url('/')) ?>">Kembali ke portal publik</a>
        </form>
    </div>
</section>
