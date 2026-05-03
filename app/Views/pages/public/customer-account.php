<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="page-card">
                <span class="eyebrow">Customer Profile</span>
                <h1 class="section-title"><?= e($customer['name'] ?? '-') ?></h1>
                <div class="vstack gap-2 text-muted">
                    <div><?= e($customer['member_id'] ?? '-') ?></div>
                    <div><?= e($customer['phone'] ?? '-') ?></div>
                    <div><?= e($customer['email'] ?? '-') ?></div>
                    <div>Loyalty Point: <strong><?= e((string) ($customer['loyalty_points'] ?? 0)) ?></strong></div>
                </div>
                <form method="post" action="<?= e(url('/customer/logout')) ?>" class="mt-4">
                    <?= csrf_field() ?>
                    <button class="btn btn-dark rounded-pill w-100" type="submit">Logout</button>
                </form>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="page-card mb-4">
                <div class="section-head mb-3">
                    <div><h2 class="section-title mb-0">Histori Booking</h2></div>
                    <a class="btn btn-light rounded-pill" href="<?= e(url('/booking')) ?>">Booking baru</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Ref</th><th>Jadwal</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?= e($booking['reference']) ?></td>
                                <td><?= e($booking['start_at']) ?></td>
                                <td><span class="badge text-bg-light"><?= e($booking['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="page-card">
                <h2 class="section-title">Voucher Tersedia</h2>
                <div class="row g-3">
                    <?php foreach ($vouchers as $voucher): ?>
                        <div class="col-md-6">
                            <div class="soft-card h-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= e($voucher['code']) ?></strong>
                                    <span class="badge text-bg-light"><?= e($voucher['status']) ?></span>
                                </div>
                                <div class="text-muted small mt-2">Berlaku hingga <?= e($voucher['expired_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
