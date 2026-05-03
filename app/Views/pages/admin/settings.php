<div class="row g-4">
    <div class="col-xl-5">
        <div class="page-card">
            <div class="section-head mb-3">
                <div>
                    <span class="eyebrow">Business Settings</span>
                    <h2 class="section-title mb-0">Profil Salon</h2>
                </div>
            </div>
            <div class="soft-card mb-3">
                <div class="small text-muted">Nama bisnis</div>
                <strong><?= e($settings['business_name']) ?></strong>
            </div>
            <div class="soft-card mb-3">
                <div class="small text-muted">Jam operasional</div>
                <strong><?= e($settings['hours']) ?></strong>
            </div>
            <div class="soft-card mb-3">
                <div class="small text-muted">Alamat</div>
                <strong><?= e($settings['address']) ?></strong>
            </div>
            <div class="soft-card">
                <div class="small text-muted">Channel notifikasi</div>
                <strong><?= e($settings['notification_channel']) ?></strong>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="page-card">
            <div class="section-head mb-3">
                <div>
                    <span class="eyebrow">Role & Access</span>
                    <h2 class="section-title mb-0">Atur akses staff oleh Admin</h2>
                </div>
            </div>
            <form method="post" action="<?= e(url('/settings/staff-permissions')) ?>" class="vstack gap-3 js-permission-form">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label">Pilih Staff</label>
                    <select class="form-select js-permission-staff" name="staff_id">
                        <?php foreach ($staff as $member): ?>
                            <?php if ($member['role'] !== 'Owner'): ?>
                                <option value="<?= e((string) $member['id']) ?>"><?= e($member['name']) ?> - <?= e($member['role']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="permission-grid">
                    <?php foreach ($catalog as $module): ?>
                        <div class="soft-card">
                            <div class="fw-semibold mb-2"><?= e($module['label']) ?></div>
                            <div class="vstack gap-2">
                                <?php foreach ($module['permissions'] as $permissionKey => $permissionLabel): ?>
                                    <label class="permission-check">
                                        <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= e($permissionKey) ?>">
                                        <span>
                                            <strong><?= e($permissionKey) ?></strong>
                                            <small><?= e($permissionLabel) ?></small>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-dark rounded-pill" type="submit">Simpan Hak Akses Staff</button>
            </form>
        </div>
    </div>
</div>
