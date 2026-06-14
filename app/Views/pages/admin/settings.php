<?php
$hoursSchedule = is_array($settings['hours_schedule'] ?? null) ? $settings['hours_schedule'] : [];
$timezoneOptions = [
    'Asia/Jakarta' => '(GMT +07:00) Asia/Jakarta',
    'Asia/Makassar' => '(GMT +08:00) Asia/Makassar',
    'Asia/Jayapura' => '(GMT +09:00) Asia/Jayapura',
    'Asia/Bangkok' => '(GMT +07:00) Asia/Bangkok',
];
?>
<div class="row g-4">
    <div class="col-xl-5">
        <div class="page-card">
            <div class="section-head mb-3">
                <div>
                    <span class="eyebrow">Business Settings</span>
                    <h2 class="section-title mb-0">Profil Salon</h2>
                </div>
            </div>
            <form method="post" action="<?= e(url('/settings/business-profile')) ?>" class="vstack gap-3 js-business-settings-form">
                <?= csrf_field() ?>

                <div class="soft-card">
                    <label class="form-label small text-muted mb-2">Nama bisnis</label>
                    <input class="form-control" type="text" name="business_name" value="<?= e((string) ($settings['business_name'] ?? '')) ?>" placeholder="Nama bisnis">
                </div>

                <div class="soft-card business-hours-card">
                    <div>
                        <label class="form-label small text-muted mb-2">Zona waktu</label>
                        <select class="form-select js-business-timezone" name="timezone">
                            <?php foreach ($timezoneOptions as $timezoneValue => $timezoneLabel): ?>
                                <option value="<?= e($timezoneValue) ?>" <?= (($settings['timezone'] ?? '') === $timezoneValue) ? 'selected' : '' ?>><?= e($timezoneLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button class="btn btn-light business-hours-card__default js-business-hours-default" type="button">Sesuaikan Jam bisnis dengan default</button>

                    <input class="js-business-hours-json" type="hidden" name="hours_schedule_json" value="<?= e((string) json_encode($hoursSchedule, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">

                    <div class="business-hours-list">
                        <?php foreach ($hoursSchedule as $day): ?>
                            <div class="business-hours-row<?= empty($day['enabled']) ? ' is-disabled' : '' ?>" data-business-hours-row data-day-key="<?= e((string) ($day['key'] ?? '')) ?>">
                                <label class="business-hours-row__toggle">
                                    <input class="js-business-day-enabled" type="checkbox" <?= !empty($day['enabled']) ? 'checked' : '' ?>>
                                    <span class="business-hours-row__check"><i class="bi bi-check-lg"></i></span>
                                    <span class="business-hours-row__label"><?= e((string) ($day['label'] ?? 'Hari')) ?></span>
                                </label>

                                <button class="business-hours-row__time js-business-time-trigger" type="button" data-time-target="open" <?= empty($day['enabled']) ? 'disabled' : '' ?>>
                                    <i class="bi bi-clock"></i>
                                    <span class="js-business-time-display"><?= e((string) ($day['open'] ?? '08:00')) ?></span>
                                    <input class="js-business-time-input" type="hidden" data-time-field="open" value="<?= e((string) ($day['open'] ?? '08:00')) ?>">
                                </button>

                                <button class="business-hours-row__time js-business-time-trigger" type="button" data-time-target="close" <?= empty($day['enabled']) ? 'disabled' : '' ?>>
                                    <i class="bi bi-clock"></i>
                                    <span class="js-business-time-display"><?= e((string) ($day['close'] ?? '22:00')) ?></span>
                                    <input class="js-business-time-input" type="hidden" data-time-field="close" value="<?= e((string) ($day['close'] ?? '22:00')) ?>">
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="soft-card">
                    <label class="form-label small text-muted mb-2">Alamat</label>
                    <textarea class="form-control" name="address" rows="3" placeholder="Alamat bisnis"><?= e((string) ($settings['address'] ?? '')) ?></textarea>
                </div>

                <div class="soft-card">
                    <label class="form-label small text-muted mb-2">Channel notifikasi</label>
                    <input class="form-control" type="text" name="notification_channel" value="<?= e((string) ($settings['notification_channel'] ?? '')) ?>" placeholder="Contoh: Email + WhatsApp">
                </div>

                <button class="btn btn-dark rounded-pill" type="submit">Simpan Profil Salon</button>
            </form>
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
