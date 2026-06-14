<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="page-card">
                <span class="eyebrow">Portal Pelanggan</span>
                <h1 class="section-title">Masuk ke akun customer</h1>
                <p class="text-muted">Lihat histori booking, transaksi, voucher, dan loyalty point Anda.</p>
                <div class="soft-card mb-4">
                    <strong>Demo Account</strong>
                    <div class="small text-muted">customer@starstyle.test / password123</div>
                </div>
                <form method="post" action="<?= e(url('/customer/login')) ?>" class="vstack gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect" value="<?= e((string) ($redirectAfterLogin ?? '')) ?>">
                    <div>
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" placeholder="customer@starstyle.test">
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input class="form-control" type="password" name="password" placeholder="password123">
                    </div>
                    <button class="btn btn-dark rounded-pill" type="submit">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</section>
