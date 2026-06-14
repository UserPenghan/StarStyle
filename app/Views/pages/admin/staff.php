<?php
$today = new DateTimeImmutable('today');
$weekStart = $today->modify('sunday this week');
$commissionRangeStart = $today->modify('-6 days');
$weekDays = [];
for ($offset = 0; $offset < 7; $offset++) {
    $weekDays[] = $weekStart->modify("+{$offset} days");
}

$monthCursor = $today->modify('first day of this month');
$monthDays = [];
for ($offset = 0; $offset < (int) $monthCursor->format('t'); $offset++) {
    $monthDays[] = $monthCursor->modify("+{$offset} days");
}
$monthFilledDays = array_values(array_filter([2, 9, 16, 23, 30], fn (int $day): bool => $day <= count($monthDays)));
$monthCellWidth = 36;
$featuredStaff = array_slice($staff, 0, 1);
$serviceGroups = $serviceGroups ?? [];
$services = $services ?? [];
$products = $products ?? [];
$attendanceByStaff = [];
foreach ($attendance as $entry) {
    $attendanceByStaff[$entry['staff_id']] = $entry;
}
$shiftMap = [];
foreach ($shifts as $shift) {
    $shiftDate = (string) ($shift['date'] ?? $shift['shift_date'] ?? '');
    $shiftStart = (string) ($shift['start'] ?? $shift['start_time'] ?? '');
    $shiftEnd = (string) ($shift['end'] ?? $shift['end_time'] ?? '');

    if ($shiftDate === '' || $shiftStart === '' || $shiftEnd === '') {
        continue;
    }

    $shiftMap[(int) ($shift['staff_id'] ?? 0)][$shiftDate][] = [
        'start' => substr($shiftStart, 0, 5),
        'end' => substr($shiftEnd, 0, 5),
        'repeat_mode' => (string) ($shift['repeat_mode'] ?? 'none'),
    ];
}

$staffById = [];
foreach ($staff as $member) {
    $staffById[(int) $member['id']] = $member;
}

$activeStaffCount = count(array_filter($staff, fn (array $member): bool => ($member['status'] ?? '') === 'Aktif'));
$staffRoles = array_values(array_unique(array_filter(array_map(fn (array $member): string => (string) ($member['role'] ?? ''), $staff))));
sort($staffRoles);
$commissionRows = [];
foreach ($staff as $member) {
    $memberTransactions = array_values(array_filter($transactions ?? [], fn (array $transaction): bool => (int) $transaction['staff_id'] === (int) $member['id']));
    $grossSales = array_reduce($memberTransactions, function (float $carry, array $transaction): float {
        $lineTotal = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['qty'] * $item['price']), 0.0);
        return $carry + $lineTotal - (float) $transaction['discount'];
    }, 0.0);

    $commissionValue = ($member['commission_type'] ?? '') === 'Persentase'
        ? $grossSales * (((float) $member['commission_value']) / 100)
        : count($memberTransactions) * (float) ($member['commission_value'] ?? 0);

    $commissionRows[] = [
        'name' => $member['name'],
        'role' => $member['role'],
        'transactions' => count($memberTransactions),
        'gross' => $grossSales,
        'commission' => $commissionValue,
        'type' => $member['commission_type'],
    ];
}

// Dummy data khusus untuk tabel Komisi.
$commissionDummyEntries = [
    [
        'invoice_no' => '14',
        'invoice_date' => '2026-04-21',
        'invoice_date_label' => '21 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Cat rambut full',
        'quantity' => 1,
        'sale_value' => 300000,
        'commission_amount' => 20000,
        'commission_percent' => 0,
        'commission_mode' => 'amount',
        'status' => 'paid',
    ],
    [
        'invoice_no' => '14',
        'invoice_date' => '2026-04-21',
        'invoice_date_label' => '21 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Potong rambut pria',
        'quantity' => 1,
        'sale_value' => 50000,
        'commission_amount' => 10000,
        'commission_percent' => 20,
        'commission_mode' => 'percent',
        'status' => 'paid',
    ],
    [
        'invoice_no' => '13',
        'invoice_date' => '2026-04-21',
        'invoice_date_label' => '21 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Potong rambut pria',
        'quantity' => 1,
        'sale_value' => 50000,
        'commission_amount' => 5000,
        'commission_percent' => 10,
        'commission_mode' => 'percent',
        'status' => 'paid',
    ],
    [
        'invoice_no' => '13',
        'invoice_date' => '2026-04-21',
        'invoice_date_label' => '21 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Cat rambut full',
        'quantity' => 1,
        'sale_value' => 300000,
        'commission_amount' => 0,
        'commission_percent' => 0,
        'commission_mode' => 'amount',
        'status' => 'draft',
    ],
    [
        'invoice_no' => '11',
        'invoice_date' => '2026-04-20',
        'invoice_date_label' => '20 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Potong rambut wanita',
        'quantity' => 1,
        'sale_value' => 50000,
        'commission_amount' => 0,
        'commission_percent' => 0,
        'commission_mode' => 'amount',
        'status' => 'draft',
    ],
    [
        'invoice_no' => '11',
        'invoice_date' => '2026-04-20',
        'invoice_date_label' => '20 Apr 2026',
        'staff_name' => 'Rayhan Doni Pramana',
        'location' => 'Star Salon',
        'item_name' => 'Creambath',
        'quantity' => 1,
        'sale_value' => 100000,
        'commission_amount' => 0,
        'commission_percent' => 0,
        'commission_mode' => 'amount',
        'status' => 'draft',
    ],
];

$commissionStaffChoices = [];
foreach ($staff as $member) {
    $name = trim((string) ($member['name'] ?? ''));
    if ($name !== '') {
        $commissionStaffChoices[] = $name;
    }
}
foreach ($commissionDummyEntries as $entry) {
    $name = trim((string) ($entry['staff_name'] ?? ''));
    if ($name !== '') {
        $commissionStaffChoices[] = $name;
    }
}
$commissionStaffChoices = array_values(array_unique($commissionStaffChoices));
sort($commissionStaffChoices, SORT_NATURAL | SORT_FLAG_CASE);
?>

<section class="staff-shell js-staff-shell" data-staff-shifts="<?= e(json_encode($shiftMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
    <div class="staff-tabs">
        <button class="staff-tab is-active" type="button" data-staff-tab="work">Jam Kerja</button>
        <button class="staff-tab" type="button" data-staff-tab="members">Anggota Staf</button>
        <button class="staff-tab" type="button" data-staff-tab="attendance">Kehadiran</button>
        <button class="staff-tab" type="button" data-staff-tab="commission">Komisi</button>
    </div>

    <div class="staff-panels">
        <section class="staff-panel is-active" data-staff-panel="work">
            <div class="staff-toolbar">
                <div class="staff-toolbar__group staff-mode-switch">
                    <button class="staff-mode-btn is-active" type="button" data-staff-mode="week">Week</button>
                    <button class="staff-mode-btn" type="button" data-staff-mode="month">Month</button>
                </div>
                <div class="staff-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button" data-commission-shop="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <button class="dashboard-filter staff-range-filter js-staff-week-range" type="button">
                        <i class="bi bi-chevron-left"></i>
                        <span><i class="bi bi-calendar-week"></i> <?= e($weekStart->format('d M')) ?> - <?= e($weekStart->modify('+6 days')->format('d M, Y')) ?></span>
                        <i class="bi bi-chevron-right"></i>
                        <input class="staff-range-input js-staff-week-picker" type="text" aria-label="Pilih minggu">
                    </button>
                    <button class="dashboard-filter staff-range-filter js-staff-month-range" type="button" hidden>
                        <i class="bi bi-chevron-left"></i>
                        <span><i class="bi bi-calendar3"></i> <?= e($today->format('M Y')) ?></span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div class="staff-schedule-view is-active" data-staff-mode-panel="week">
                <div class="staff-schedule-card">
                    <div class="staff-week-table">
                        <div class="staff-week-left">
                            <div class="staff-week-left__head">
                                <div class="staff-schedule-card__staff-label">Staf (<?= e((string) count($featuredStaff)) ?>)</div>
                            </div>
                            <div class="staff-week-left__body">
                                <?php foreach ($featuredStaff as $member): ?>
                                    <div class="staff-week-left__row">
                                        <div class="staff-schedule-person">
                                            <div class="staff-schedule-person__avatar"><i class="bi bi-person"></i></div>
                                            <div>
                                                <strong><?= e($member['name']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="staff-week-right staff-schedule-scroll" data-staff-schedule-scroll="week">
                            <div class="staff-week-right__head">
                                <div class="staff-schedule-days-grid staff-schedule-days-grid--week">
                                    <?php foreach ($weekDays as $day): ?>
                                        <div class="staff-schedule-day <?= $day->format('Y-m-d') === $today->format('Y-m-d') ? 'is-today' : '' ?>">
                                            <strong><?= e(['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int) $day->format('w')]) ?></strong>
                                            <span><?= e($day->format('j M y')) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="staff-week-right__body">
                                <?php foreach ($featuredStaff as $member): ?>
                                    <div class="staff-schedule-days-grid staff-schedule-days-grid--week staff-week-days-row"
                                         data-staff-id="<?= e((string) $member['id']) ?>"
                                         data-staff-name="<?= e($member['name']) ?>">
                                        <?php foreach ($weekDays as $day): ?>
                                            <div class="staff-schedule-cell"
                                                 data-staff-work-cell
                                                 data-staff-id="<?= e((string) $member['id']) ?>"
                                                 data-staff-name="<?= e($member['name']) ?>"
                                                 data-date="<?= e($day->format('Y-m-d')) ?>"
                                                 data-day-index="<?= e($day->format('w')) ?>"
                                                 data-day-label="<?= e($day->format('l, j M Y')) ?>">
                                                <?php if ($day->format('Y-m-d') === $today->format('Y-m-d')): ?>
                                                    <span class="staff-schedule-hours">00:00 - 23:55</span>
                                                <?php else: ?>
                                                    <button class="staff-schedule-add" type="button" aria-label="Tambah shift"><i class="bi bi-plus-lg"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="staff-schedule-card__footer">
                        <div class="staff-pagination">
                            <button type="button"><i class="bi bi-chevron-left"></i></button>
                            <span>1</span>
                            <button type="button"><i class="bi bi-chevron-right"></i></button>
                            <button type="button" class="staff-pagination__caret"><i class="bi bi-chevron-up"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="staff-schedule-view" data-staff-mode-panel="month">
                <div class="staff-schedule-card">
                    <div class="staff-month-table">
                        <div class="staff-month-left">
                            <div class="staff-month-left__head">
                                <div class="staff-schedule-card__staff-label">Staf (<?= e((string) count($featuredStaff)) ?>)</div>
                            </div>
                            <div class="staff-month-left__body">
                                <?php foreach ($featuredStaff as $member): ?>
                                    <div class="staff-month-left__row">
                                        <div class="staff-schedule-person">
                                            <div class="staff-schedule-person__avatar staff-schedule-person__avatar--initials"><?= e(substr($member['name'], 0, 1)) ?><?= e(substr(strrchr($member['name'], ' ') ?: $member['name'], 1, 1)) ?></div>
                                            <div>
                                                <strong><?= e($member['name']) ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="staff-month-right staff-schedule-scroll" data-staff-schedule-scroll="month">
                            <div class="staff-month-right__head">
                                <div class="staff-schedule-days-grid staff-schedule-days-grid--month" style="grid-template-columns: repeat(<?= count($monthDays) ?>, <?= $monthCellWidth ?>px);">
                                    <?php foreach ($monthDays as $day): ?>
                                        <div class="staff-schedule-month-day <?= $day->format('Y-m-d') === $today->format('Y-m-d') ? 'is-today' : '' ?>">
                                            <strong><?= e($day->format('j')) ?></strong>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="staff-month-right__body">
                                <?php foreach ($featuredStaff as $member): ?>
                                    <div class="staff-schedule-days-grid staff-schedule-days-grid--month staff-month-days-row"
                                         data-staff-id="<?= e((string) $member['id']) ?>"
                                         data-staff-name="<?= e($member['name']) ?>"
                                         style="grid-template-columns: repeat(<?= count($monthDays) ?>, <?= $monthCellWidth ?>px);">
                                        <?php foreach ($monthDays as $day): ?>
                                            <?php
                                            $dayNumber = (int) $day->format('j');
                                            $className = 'staff-month-cell';
                                            if (in_array($dayNumber, $monthFilledDays, true)) {
                                                $className .= ' is-filled';
                                            }
                                            ?>
                                            <div class="<?= e($className) ?>"
                                                 data-staff-work-cell
                                                 data-staff-id="<?= e((string) $member['id']) ?>"
                                                 data-staff-name="<?= e($member['name']) ?>"
                                                 data-date="<?= e($day->format('Y-m-d')) ?>"
                                                 data-day-index="<?= e($day->format('w')) ?>"
                                                 data-day-label="<?= e($day->format('l, j M Y')) ?>">
                                                <button class="staff-schedule-add" type="button" aria-label="Tambah shift"><i class="bi bi-plus-lg"></i></button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="staff-schedule-card__footer">
                        <div class="staff-pagination">
                            <button type="button"><i class="bi bi-chevron-left"></i></button>
                            <span>1</span>
                            <button type="button"><i class="bi bi-chevron-right"></i></button>
                            <button type="button" class="staff-pagination__caret"><i class="bi bi-chevron-up"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="staff-panel" data-staff-panel="members">
            <div class="staff-summary-card">
                <div class="staff-summary-card__left">
                    <div class="staff-summary-icon"><i class="bi bi-compass"></i></div>
                    <div>
                        <strong>Free</strong>
                        <span><?= e((string) count($staff)) ?> Staff</span>
                    </div>
                </div>
                <div class="staff-summary-card__right">
                    <strong><?= e((string) $activeStaffCount) ?> Aktif</strong>
                    <span><?= e((string) max(0, count($staff) - $activeStaffCount)) ?> Tersedia</span>
                </div>
            </div>

            <div class="staff-toolbar staff-toolbar--members">
                <div class="staff-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter js-staff-member-sort-toggle ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Pengurutan</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button" data-staff-member-sort="asc">A-Z</button>
                            <button class="dropdown-item" type="button" data-staff-member-sort="desc">Z-A</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter js-staff-member-export ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Unduh</span>
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu staff-export-menu">
                            <button class="dropdown-item" type="button" data-staff-member-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-staff-member-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-staff-member-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-staff-member-export="csv">CSV</button>
                        </div>
                    </div>
                </div>
                <div class="staff-toolbar__group staff-toolbar__group--end">
                    <div class="dropdown">
                        <button class="dashboard-filter staff-filter-disabled js-staff-member-role-filter ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-role-filter="all">
                            <span>Roles</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button" data-staff-member-role="all">Roles</button>
                            <?php foreach ($staffRoles as $role): ?>
                                <button class="dropdown-item" type="button" data-staff-member-role="<?= e($role) ?>"><?= e($role) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter customers-filter-name js-staff-member-name-sort ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-sort-field="name">
                            <span>Nama</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu staff-name-menu">
                            <button class="dropdown-item is-active" type="button" data-staff-member-field="name">Nama</button>
                            <button class="dropdown-item" type="button" data-staff-member-field="phone">Nomor Ponsel</button>
                            <button class="dropdown-item" type="button" data-staff-member-field="email">Email</button>
                            <button class="dropdown-item" type="button" data-staff-member-field="location">Lokasi</button>
                        </div>
                    </div>
                    <label class="sales-search-field">
                        <input class="js-staff-member-search" type="search" placeholder="Ketik kata kunci">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="staff-member-list">
                <?php foreach ($staff as $member): ?>
                    <div class="staff-member-row"
                         data-staff-member-row
                         data-staff-id="<?= e((string) $member['id']) ?>"
                         data-staff-user-id="<?= e((string) ($member['user_id'] ?? 0)) ?>"
                         data-name="<?= e($member['name']) ?>"
                         data-email="<?= e($member['email']) ?>"
                         data-phone="<?= e($member['phone']) ?>"
                         data-location="<?= e((string) ($member['location_name'] ?? 'Star Salon')) ?>"
                         data-location-id="<?= e((string) ($member['location_id'] ?? 1)) ?>"
                         data-role="<?= e($member['role']) ?>"
                         data-status="<?= e($member['status']) ?>"
                         data-gender="<?= e((string) ($member['gender'] ?? '')) ?>"
                         data-booking-enabled="<?= in_array('calendar.view', $member['permissions'] ?? ['calendar.view'], true) ? '1' : '0' ?>"
                         data-agenda-color="<?= e((string) ($member['agenda_color'] ?? '#8cc9ff')) ?>"
                         data-started-working-on="<?= e((string) ($member['started_working_on'] ?? '')) ?>"
                         data-ended-working-on="<?= e((string) ($member['ended_working_on'] ?? '')) ?>"
                         data-public-title="<?= e((string) ($member['public_title'] ?? '')) ?>"
                         data-notes="<?= e((string) ($member['notes'] ?? '')) ?>"
                         data-instagram-handle="<?= e((string) ($member['instagram_handle'] ?? '')) ?>"
                         data-photo-data-url="<?= e((string) ($member['photo_data_url'] ?? '')) ?>"
                         data-service-ids="<?= e(implode(',', $member['service_ids'] ?? [])) ?>"
                         data-commission-type="<?= e((string) ($member['commission_type'] ?? 'Persentase')) ?>"
                         data-commission-value="<?= e((string) ($member['commission_value'] ?? 0)) ?>"
                         data-commission-rules="<?= e(json_encode($member['commission_rules'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                         tabindex="0"
                         role="button">
                        <div class="staff-member-cell staff-member-cell--person">
                            <div class="staff-member-avatar"><i class="bi bi-person"></i></div>
                            <strong><?= e($member['name']) ?></strong>
                        </div>
                        <div class="staff-member-cell staff-member-cell--link"><?= e($member['email']) ?></div>
                        <div class="staff-member-cell staff-member-cell--link"><?= e('+62' . preg_replace('/\D+/', '', $member['phone'])) ?></div>
                        <div class="staff-member-cell"><?= e($member['role']) ?></div>
                        <div class="staff-member-cell"><?= in_array('calendar.view', $member['permissions'] ?? ['calendar.view'], true) ? 'Kalender Pemesanan diaktifkan' : 'Kalender belum diaktifkan' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="sales-pagination sales-pagination--services" data-staff-pagination="members">
                <div class="sales-pagination__meta">Total <span class="js-staff-members-total"><?= e((string) count($staff)) ?></span></div>
                <div class="sales-pagination__page-size">
                    <button type="button" class="sales-pagination__select" data-pagination-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                    <div class="sales-pagination__page-size-menu" data-pagination-page-size-menu hidden></div>
                </div>
                <button type="button" class="sales-pagination__nav" data-pagination-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                <div class="sales-pagination__pages" data-pagination-page-list></div>
                <button type="button" class="sales-pagination__nav" data-pagination-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                <div class="sales-pagination__goto">Go to</div>
                <input class="sales-pagination__input" data-pagination-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                <button type="button" class="sales-pagination__top" data-pagination-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
            </div>
        </section>

        <section class="staff-panel" data-staff-panel="attendance">
            <div class="staff-toolbar staff-toolbar--attendance" data-staff-attendance-toolbar>
                <div class="staff-toolbar__group staff-mode-switch">
                    <button class="staff-mode-btn is-active" type="button" data-staff-attendance-mode="staff">Staff</button>
                    <button class="staff-mode-btn" type="button" data-staff-attendance-mode="attendance">Attendance</button>
                </div>
                <div class="staff-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown staff-attendance-only">
                        <button class="dashboard-filter js-staff-attendance-export ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Export</span>
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu staff-attendance-export-menu">
                            <button class="dropdown-item" type="button" data-staff-attendance-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-staff-attendance-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-staff-attendance-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-staff-attendance-export="csv">CSV</button>
                        </div>
                    </div>
                    <button class="dashboard-filter dashboard-filter--wide staff-attendance-only js-staff-attendance-range" type="button" data-bs-toggle="modal" data-bs-target="#staffAttendanceDateFilterModal">
                        <i class="bi bi-calendar3"></i>
                        <span>Hari ini, <?= e($today->format('d M Y')) ?> - <?= e($today->format('d M Y')) ?></span>
                    </button>
                    <label class="sales-search-field staff-search-short">
                        <input class="js-staff-attendance-search" type="search" placeholder="Ketik kata kunci">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="customers-table-card" data-staff-attendance-view="staff">
                <table class="customers-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Face Photo</th>
                            <th>Last Modified</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $member): ?>
                            <?php
                            $attendanceStatusLabel = (($member['status'] ?? '') === 'Aktif') ? 'Active' : 'Deactive';
                            $attendanceStatusClass = (($member['status'] ?? '') === 'Aktif') ? '' : ' is-inactive';
                            ?>
                            <tr data-staff-attendance-row data-staff-id="<?= e((string) $member['id']) ?>" data-name="<?= e($member['name']) ?>" data-status="<?= e($member['status']) ?>" data-attendance-pose="<?= e((string) ($member['attendance_pose'] ?? 'Right Tilt')) ?>" data-attendance-uploaded-pose="<?= e((string) ($member['attendance_uploaded_pose'] ?? '')) ?>">
                                <td>
                                    <div class="customers-person-cell">
                                        <div class="customers-person-cell__avatar"><i class="bi bi-person"></i></div>
                                        <strong><?= e($member['name']) ?></strong>
                                    </div>
                                </td>
                                <td data-attendance-face-cell>-</td>
                                <td data-attendance-last-modified><?= e($today->modify('-30 days')->format('d M Y')) ?></td>
                                <td><span class="staff-active-pill<?= e($attendanceStatusClass) ?>" data-attendance-status-pill><?= e($attendanceStatusLabel) ?></span></td>
                                <td class="staff-attendance-edit">
                                    <button class="staff-attendance-edit-btn" type="button" data-attendance-edit="<?= e($member['name']) ?>" aria-label="Edit <?= e($member['name']) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sales-pagination sales-pagination--services" data-staff-pagination="attendance-staff">
                    <div class="sales-pagination__meta">Total <span class="js-staff-attendance-staff-total"><?= e((string) count($staff)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-pagination-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-pagination-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-pagination-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-pagination-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-pagination-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>

            <div class="staff-attendance-table-card" data-staff-attendance-view="attendance" hidden>
                <div class="staff-attendance-scroll">
                    <table class="staff-attendance-table">
                        <thead>
                            <tr>
                                <th class="staff-attendance-date-col">Date</th>
                                <th>Staff</th>
                                <th>Shift</th>
                                <th>Clock In</th>
                                <th>Clock Out</th>
                                <th>Duration</th>
                                <th>Early</th>
                                <th>Late</th>
                                <th>Overtime</th>
                                <th>Source</th>
                                <th>Clock In Selfie</th>
                                <th>Clock Out Selfie</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member): ?>
                                <?php
                                $entry = $attendanceByStaff[$member['id']] ?? null;
                                $clockIn = $entry['clock_in'] ?? '08:00';
                                $clockOut = $entry['clock_out'] ?? '17:00';
                                $duration = $clockOut !== '-' ? '9h' : '-';
                                ?>
                                <tr data-staff-attendance-record data-staff-id="<?= e((string) $member['id']) ?>" data-name="<?= e($member['name']) ?>" data-status="<?= e($member['status']) ?>">
                                    <td class="staff-attendance-date-col"><?= e($today->format('Y-m-d')) ?></td>
                                    <td><button class="staff-attendance-name" type="button"><?= e($member['name']) ?></button></td>
                                    <td>08:00 - 17:00</td>
                                    <td><?= e($clockIn) ?></td>
                                    <td><?= e($clockOut) ?></td>
                                    <td><?= e($duration) ?></td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>
                                        <span class="staff-attendance-selfie"><i class="bi bi-person"></i></span>
                                        <span>0.00%</span>
                                    </td>
                                    <td>
                                        <span class="staff-attendance-selfie"><i class="bi bi-person"></i></span>
                                        <span>0.00%</span>
                                    </td>
                                    <td>
                                        <button class="staff-attendance-edit-btn" type="button" data-attendance-edit="<?= e($member['name']) ?>" aria-label="Edit <?= e($member['name']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sales-pagination sales-pagination--services" data-staff-pagination="attendance-records">
                    <div class="sales-pagination__meta">Total <span class="js-staff-attendance-records-total"><?= e((string) count($staff)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-pagination-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-pagination-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-pagination-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-pagination-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-pagination-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>

            <button class="staff-attendance-fab js-staff-attendance-fab" type="button" aria-label="Tambah attendance" hidden>
                <i class="bi bi-plus-lg"></i>
            </button>
        </section>

        <div class="staff-attendance-detail-drawer" id="staffAttendanceDetailDrawer" hidden aria-hidden="true">
            <div class="staff-attendance-detail-drawer__backdrop js-attendance-detail-close"></div>
            <aside class="staff-attendance-detail-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="staffAttendanceDetailTitle">
                <div class="staff-attendance-detail-drawer__head">
                    <h2 id="staffAttendanceDetailTitle">Staff Detail</h2>
                    <button type="button" class="staff-work-close js-attendance-detail-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="staff-attendance-detail-drawer__body">
                    <div class="staff-attendance-detail-drawer__profile">
                        <div class="staff-attendance-detail-drawer__person">
                            <div class="customers-person-cell__avatar"><i class="bi bi-person"></i></div>
                            <strong class="js-attendance-detail-name">Staf</strong>
                        </div>
                        <label class="staff-attendance-detail-drawer__switch">
                            <input class="js-attendance-detail-toggle" type="checkbox" checked>
                            <span></span>
                        </label>
                    </div>
                    <div class="staff-attendance-detail-drawer__blank" aria-hidden="true"></div>
                </div>
                <div class="staff-attendance-detail-drawer__footer">
                    <button type="button" class="customer-footer-btn js-attendance-detail-done">Selesai</button>
                </div>
            </aside>
        </div>

        <section class="staff-panel" data-staff-panel="commission">
            <div class="staff-toolbar staff-toolbar--commission">
                <div class="staff-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop js-staff-commission-shop ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i>
                            <span>Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter js-staff-commission-staff-filter ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-staff-name="all">
                            <span>Semua Staf</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu ss-dropdown-menu">
                            <button class="dropdown-item is-active" type="button" data-commission-staff="all">Semua Staf</button>
                            <?php foreach ($commissionStaffChoices as $staffName): ?>
                                <button class="dropdown-item" type="button" data-commission-staff="<?= e($staffName) ?>"><?= e($staffName) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="staff-toolbar__group staff-toolbar__group--end">
                    <button class="dashboard-filter dashboard-filter--wide js-staff-commission-range" type="button" data-bs-toggle="modal" data-bs-target="#staffCommissionDateFilterModal"><i class="bi bi-calendar3"></i><span>7 hari sebelumnya, <?= e($commissionRangeStart->format('j M Y')) ?> - <?= e($today->format('j M Y')) ?></span></button>
                    <label class="sales-search-field staff-search-short staff-search-short--commission">
                        <input class="js-staff-commission-search" type="search" placeholder="Ketik kata kunci">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="staff-commission-table">
                <div class="staff-commission-table__scroll">
                    <table class="sales-table sales-table--wide staff-commission-grid">
                        <thead>
                            <tr>
                                <th class="staff-commission-col-invoice">No Faktur</th>
                                <th>Tanggal Faktur</th>
                                <th>Anggota Staf</th>
                                <th>Lokasi</th>
                                <th>Barang Terjual</th>
                                <th>Jumlah</th>
                                <th>Nilai Jual</th>
                                <th>Besaran Komisi</th>
                                <th>Persen Komisi</th>
                                <th class="staff-commission-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commissionDummyEntries as $index => $entry): ?>
                                <?php
                                $statusClass = ($entry['status'] ?? 'paid') === 'paid' ? 'is-paid' : 'is-draft';
                                ?>
                                <tr
                                    data-commission-row
                                    data-commission-id="commission-<?= e((string) $index) ?>"
                                    data-commission-staff-name="<?= e($entry['staff_name']) ?>"
                                    data-commission-date="<?= e($entry['invoice_date']) ?>"
                                    data-commission-date-label="<?= e($entry['invoice_date_label']) ?>"
                                    data-commission-invoice-no="<?= e($entry['invoice_no']) ?>"
                                    data-commission-location="<?= e($entry['location']) ?>"
                                    data-commission-item-name="<?= e($entry['item_name']) ?>"
                                    data-commission-sale-value="<?= e((string) $entry['sale_value']) ?>"
                                    data-commission-amount="<?= e((string) $entry['commission_amount']) ?>"
                                    data-commission-percent="<?= e((string) $entry['commission_percent']) ?>"
                                    data-commission-mode="<?= e($entry['commission_mode']) ?>"
                                    data-commission-status="<?= e($entry['status']) ?>"
                                >
                                    <td class="staff-commission-col-invoice">
                                        <span class="staff-commission-invoice <?= e($statusClass) ?>"><?= e($entry['invoice_no']) ?></span>
                                    </td>
                                    <td><?= e($entry['invoice_date_label']) ?></td>
                                    <td>
                                        <button class="staff-commission-person" type="button" data-commission-edit-staff="<?= e($entry['staff_name']) ?>">
                                            <span data-commission-person-label><?= e($entry['staff_name']) ?></span>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                    <td><?= e($entry['location']) ?></td>
                                    <td><?= e($entry['item_name']) ?></td>
                                    <td><?= e((string) $entry['quantity']) ?></td>
                                    <td><?= e(number_format((float) $entry['sale_value'], 2, ',', '.')) ?></td>
                                    <td data-cell-commission-amount><?= e(number_format((float) $entry['commission_amount'], 2, ',', '.')) ?></td>
                                    <td data-cell-commission-percent><?= e(number_format((float) $entry['commission_percent'], 0, ',', '.')) ?>%</td>
                                    <td class="staff-commission-col-actions">
                                        <button class="staff-commission-action <?= e($statusClass) ?>" type="button" data-commission-action="<?= e((string) $index) ?>" aria-label="Edit komisi <?= e($entry['staff_name']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr hidden>
                                <td colspan="10" class="sales-no-data" data-commission-empty>No Data</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="sales-pagination sales-pagination--services staff-commission-table__footer" data-staff-pagination="commission">
                    <div class="sales-pagination__meta">Total <span class="js-staff-commission-total"><?= e((string) count($commissionDummyEntries)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-pagination-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-pagination-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-pagination-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-pagination-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-pagination-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-pagination-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </section>
    </div>

    <div class="staff-fab-group" data-staff-fab-group>
        <button class="staff-secondary-fab" type="button" aria-label="Tools staf">
            <i class="bi bi-briefcase"></i>
        </button>
        <button class="customers-fab js-staff-fab" type="button" aria-label="Tambah staf" data-bs-toggle="modal" data-bs-target="#staffModal">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<div class="modal fade" id="staffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal staff-modal">
            <div class="customer-modal__header">
                <div></div>
                <h2 class="js-staff-modal-title">Staf Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="staff-modal__tabs">
                <button class="staff-modal__tab is-active" type="button" data-staff-new-tab="details">Rincian</button>
                <button class="staff-modal__tab" type="button" data-staff-new-tab="locations">Lokasi</button>
                <button class="staff-modal__tab" type="button" data-staff-new-tab="services">Layanan</button>
                <button class="staff-modal__tab" type="button" data-staff-new-tab="commission">Komisi</button>
            </div>
            <div class="customer-modal__body">
                <div class="staff-new-panel is-active" data-staff-new-panel="details">
                <div class="customer-form-grid">
                    <div class="customer-form-col">
                        <label>Nama</label>
                        <input class="form-control customer-input-flat js-staff-new-name" type="text" placeholder="Nama">

                        <label>Jenis Kelamin</label>
                        <div class="customer-segmented staff-new-segmented">
                            <button class="is-active" type="button">Disable</button>
                            <button type="button">Pria</button>
                            <button type="button">Wanita</button>
                        </div>

                        <label>Nomor Ponsel</label>
                        <div class="customer-phone-row">
                            <span class="customer-phone-flag"></span>
                            <input class="form-control customer-input-flat js-staff-new-phone" type="text" value="+62">
                        </div>

                        <label>Email</label>
                        <input class="form-control customer-input-flat js-staff-new-email" type="text" placeholder="Email">

                        <label>Izin Pengguna</label>
                        <div class="staff-role-grid staff-new-segmented">
                            <button type="button" data-staff-new-role="No Access">No Access</button>
                            <button type="button" class="is-active" data-staff-new-role="Basic">Basic</button>
                            <button type="button" data-staff-new-role="Junior">Junior</button>
                            <button type="button" data-staff-new-role="Senior">Senior</button>
                            <button type="button" data-staff-new-role="Supervisor">Supervisor</button>
                            <button type="button" data-staff-new-role="Manager">Manager</button>
                        </div>

                        <label class="customer-toggle-row">
                            <span>Aktifkan booking agenda</span>
                            <input class="js-staff-booking-toggle" type="checkbox" checked hidden>
                            <span class="sales-switch__track is-active"></span>
                        </label>

                        <label>Warna Agenda</label>
                        <div class="staff-color-grid">
                            <?php foreach (['#8cc9ff', '#9ce7ff', '#9ebdff', '#bccdff', '#d59cff', '#b59cff', '#f4a4d4', '#f5bfd6', '#b3b9f5', '#a7c4f8', '#f6d97e', '#ffd86b', '#f6c17f', '#ffb2a1'] as $index => $color): ?>
                                <button class="<?= $index === 0 ? 'is-active' : '' ?>" type="button" style="background: <?= e($color) ?>"></button>
                            <?php endforeach; ?>
                            <button type="button" class="staff-color-grid__add"><i class="bi bi-plus-lg"></i></button>
                        </div>

                        <button type="button" class="staff-edit-delete js-staff-edit-delete" hidden>Hapus staff</button>
                    </div>

                    <div class="customer-form-col">
                        <label>Photo</label>
                        <div class="staff-photo-row">
                            <div class="staff-photo-avatar js-staff-photo-preview"><i class="bi bi-person"></i></div>
                            <button class="dashboard-filter js-staff-photo-button" type="button"><i class="bi bi-image"></i><span>Choose Photo</span></button>
                            <input class="js-staff-photo-input" type="file" accept="image/*" hidden>
                        </div>

                        <label>Tanggal Mulai Bekerja</label>
                        <button class="customer-picker js-staff-date-button" type="button" data-date-target="start"><span><i class="bi bi-calendar3"></i> <?= e($today->format('Y-m-d')) ?></span></button>
                        <input class="js-staff-start-date staff-native-date" type="date" value="<?= e($today->format('Y-m-d')) ?>">

                        <label>Tanggal Akhir Bekerja</label>
                        <button class="customer-picker js-staff-date-button" type="button" data-date-target="end"><span><i class="bi bi-calendar3"></i> Pilih hari</span></button>
                        <input class="js-staff-end-date staff-native-date" type="date">

                        <label>Staff Title (optional)</label>
                        <input class="form-control customer-input-flat js-staff-new-title" type="text" placeholder="Terlihat hanya di pemesanan online">

                        <label>Catatan</label>
                        <textarea class="form-control customer-input-flat js-staff-new-notes" rows="4" placeholder="Terlihat hanya di pengaturan staff"></textarea>

                        <label>Media Sosial</label>
                        <div class="staff-social-row">
                            <span><i class="bi bi-instagram"></i></span>
                            <input class="form-control customer-input-flat js-staff-new-instagram" type="text">
                        </div>
                    </div>
                </div>
                </div>

                <div class="staff-new-panel" data-staff-new-panel="locations">
                    <div class="staff-new-narrow">
                        <p class="staff-location-intro">Tetapkan lokasi tempat anggota staf ini dapat dibooking.</p>
                        <label class="staff-location-search">
                            <input class="js-staff-location-search" type="search" placeholder="Cari lokasi">
                            <i class="bi bi-search"></i>
                        </label>
                        <label class="staff-location-row" data-location-row>
                            <span>Star Salon</span>
                            <input class="js-staff-location-check" type="checkbox" value="1" checked>
                            <i></i>
                        </label>
                    </div>
                </div>

                <div class="staff-new-panel" data-staff-new-panel="services">
                    <div class="staff-new-narrow">
                        <label class="staff-service-check staff-service-check--all">
                            <input class="js-staff-service-all" type="checkbox" checked>
                            <span>Semua layanan</span>
                        </label>
                        <?php foreach ($serviceGroups as $group): ?>
                            <div class="staff-service-group">
                                <h3><?= e($group['name']) ?></h3>
                                <?php foreach (array_filter($services, fn (array $service): bool => (int) $service['group_id'] === (int) $group['id']) as $service): ?>
                                    <label class="staff-service-check">
                                        <input class="js-staff-service-check" type="checkbox" value="<?= e((string) $service['id']) ?>" checked>
                                        <span><?= e($service['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="staff-new-panel" data-staff-new-panel="commission">
                    <div class="staff-new-narrow">
                        <?php
                        $commissionCategories = [
                            ['id' => 'service', 'label' => 'Komisi Layanan', 'value' => 'Commission per service'],
                            ['id' => 'product', 'label' => 'Komisi Produk', 'value' => 'Commission per product'],
                        ];
                        ?>
                        <?php foreach ($commissionCategories as $category): ?>
                            <div class="staff-commission-setting">
                                <label><?= e($category['label']) ?></label>
                                <div>
                                    <span data-commission-summary="<?= e($category['id']) ?>"><?= e($category['value']) ?></span>
                                    <button type="button" data-commission-edit="<?= e($category['id']) ?>">Ganti</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn js-staff-new-save">Simpan</button>
            </div>

            <div class="staff-commission-editor js-staff-commission-editor" hidden>
                <div class="staff-commission-editor__box">
                    <div class="staff-commission-editor__head">
                        <span class="staff-commission-editor__handle"><i></i><i></i><i></i></span>
                        <h3 class="js-staff-commission-editor-title">Komisi per Layanan</h3>
                        <button type="button" class="js-staff-commission-cancel" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="staff-commission-editor__body">
                        <label class="staff-commission-default">
                            <span class="js-staff-commission-default-label">Komisi layanan default</span>
                            <div class="staff-commission-input">
                                <input class="js-staff-commission-value" type="text" value="0" inputmode="decimal" disabled>
                                <button class="is-active" type="button" data-commission-type="percent">%</button>
                                <button type="button" data-commission-type="amount">Rp</button>
                            </div>
                        </label>

                        <label class="staff-location-row staff-commission-default-check">
                            <input class="js-staff-commission-use-default" type="checkbox">
                            <i></i>
                            <span class="js-staff-commission-use-default-label">Semua layanan menggunakan komisi default yang sama</span>
                        </label>

                        <div class="staff-commission-editor__filters">
                            <label class="staff-location-search">
                                <input class="js-staff-commission-search" type="search" placeholder="Cari berdasarkan nama dan tekan enter">
                                <i class="bi bi-search"></i>
                            </label>
                            <label class="staff-location-row staff-commission-assigned">
                                <input class="js-staff-commission-assigned" type="checkbox">
                                <i></i>
                                <span>Assigned services only</span>
                            </label>
                        </div>

                        <div class="staff-commission-editor__location"><i class="bi bi-info-circle-fill"></i> Lokasi: Star Salon</div>

                        <div class="staff-commission-table-editor">
                            <div class="staff-commission-table-editor__head">
                                <span>Nama</span>
                                <span>Harga</span>
                                <span>Komisi</span>
                            </div>
                            <?php foreach ($services as $service): ?>
                                <div class="staff-commission-table-editor__row" data-commission-service-row data-commission-kind="service" data-service-id="<?= e((string) $service['id']) ?>">
                                    <span><?= e($service['name']) ?></span>
                                    <span><?= e(number_format((float) $service['price'], 2, ',', '.')) ?></span>
                                    <div class="staff-commission-input staff-commission-input--row">
                                        <input type="text" value="0" inputmode="decimal">
                                        <button class="is-active" type="button" data-row-commission-type="percent">%</button>
                                        <button type="button" data-row-commission-type="amount">Rp</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($products as $product): ?>
                                <div class="staff-commission-table-editor__row" data-commission-service-row data-commission-kind="product" data-service-id="product-<?= e((string) $product['id']) ?>" hidden>
                                    <span><?= e($product['name']) ?></span>
                                    <span><?= e(number_format((float) $product['price'], 2, ',', '.')) ?></span>
                                    <div class="staff-commission-input staff-commission-input--row">
                                        <input type="text" value="0" inputmode="decimal">
                                        <button class="is-active" type="button" data-row-commission-type="percent">%</button>
                                        <button type="button" data-row-commission-type="amount">Rp</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="staff-commission-editor__footer">
                        <button type="button" class="customer-footer-btn js-staff-commission-cancel">Batal</button>
                        <button type="button" class="customer-footer-btn staff-save-btn js-staff-commission-done">Selesai</button>
                    </div>
                </div>

                <div class="staff-commission-confirm js-staff-commission-confirm" hidden>
                    <div class="staff-commission-confirm__box">
                        <button type="button" class="staff-commission-confirm__close js-staff-commission-confirm-cancel" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                        <h3>Apakah Anda yakin?</h3>
                        <p>Semua nilai komisi pada masing-masing <span class="js-staff-commission-confirm-item">Layanan</span> akan menerapkan nilai <span class="js-staff-commission-confirm-value">0%</span></p>
                        <div class="staff-commission-confirm__actions">
                            <button type="button" class="customer-footer-btn js-staff-commission-confirm-cancel">Batal</button>
                            <button type="button" class="customer-footer-btn staff-commission-confirm__continue js-staff-commission-confirm-continue">Lanjutkan</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staffCommissionDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal staff-commission-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-staff-commission-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-staff-commission-start" type="text" value="<?= e($commissionRangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-staff-commission-end" type="text" value="<?= e($today->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-staff-commission-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-staff-commission-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-staff-commission-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="staff-commission-staff-drawer" id="staffCommissionStaffDrawer" hidden aria-hidden="true">
    <div class="staff-commission-staff-drawer__backdrop js-commission-staff-drawer-close"></div>
    <aside class="staff-commission-staff-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="staffCommissionStaffDrawerTitle">
        <div class="staff-commission-staff-drawer__head">
            <h2 id="staffCommissionStaffDrawerTitle">Ganti Staf</h2>
            <button type="button" class="staff-work-close js-commission-staff-drawer-close" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="staff-commission-staff-drawer__body">
            <label class="sales-search-field staff-commission-staff-drawer__search">
                <input class="js-commission-staff-drawer-search" type="search" placeholder="Cari..." autocomplete="off">
                <i class="bi bi-search"></i>
            </label>
            <div class="staff-commission-staff-drawer__list js-commission-staff-drawer-list">
                <?php foreach ($commissionStaffChoices as $staffName): ?>
                    <button class="staff-commission-staff-drawer__option" type="button" data-commission-staff-option="<?= e($staffName) ?>">
                        <span><?= e($staffName) ?></span>
                        <i class="bi bi-check2"></i>
                    </button>
                <?php endforeach; ?>
                <div class="staff-commission-staff-drawer__empty" hidden>Tidak ada staf</div>
            </div>
        </div>
        <div class="staff-commission-staff-drawer__footer">
            <button type="button" class="customer-footer-btn js-commission-staff-drawer-close">Batal</button>
            <button type="button" class="customer-footer-btn staff-save-btn js-commission-staff-drawer-save">Simpan</button>
        </div>
    </aside>
</div>

<div class="modal fade" id="staffCommissionAdjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered staff-commission-adjust-dialog">
        <div class="modal-content staff-commission-adjust-modal">
            <div class="staff-commission-adjust__head">
                <h2>Ubah Komisi</h2>
                <button type="button" class="staff-work-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="staff-commission-adjust__hero">
                <div class="staff-commission-adjust__hero-main">
                    <div class="staff-commission-adjust__avatar"><i class="bi bi-person"></i></div>
                    <div class="staff-commission-adjust__hero-copy">
                        <strong class="js-commission-adjust-name">Staf</strong>
                        <div class="js-commission-adjust-item">Layanan</div>
                        <small class="js-commission-adjust-invoice">No Faktur</small>
                        <span class="js-commission-adjust-sale">Rp 0,00</span>
                    </div>
                </div>
                <div class="staff-commission-adjust-input js-commission-adjust-main" data-mode="amount" data-amount="0" data-percent="0">
                    <input class="js-commission-adjust-input-value" type="text" inputmode="decimal" value="Rp 0,00">
                    <button class="is-active" type="button" data-commission-adjust-type="amount">Rp</button>
                    <button type="button" data-commission-adjust-type="percent">%</button>
                </div>
            </div>
            <div class="staff-commission-adjust__body">
                <div class="staff-commission-adjust-group">
                    <button class="staff-commission-adjust-group__toggle js-commission-adjust-toggle" type="button" data-group="invoice">
                        <span class="staff-commission-adjust-group__label"><i class="bi bi-file-earmark-text"></i><span class="js-commission-adjust-invoice-label">14</span></span>
                        <span class="staff-commission-adjust-group__meta"><span class="js-commission-adjust-invoice-count">0 staff lain</span><i class="bi bi-chevron-down"></i></span>
                    </button>
                    <div class="staff-commission-adjust-group__list js-commission-adjust-group-list" data-group-list="invoice" hidden></div>
                </div>
                <div class="staff-commission-adjust-group">
                    <button class="staff-commission-adjust-group__toggle js-commission-adjust-toggle" type="button" data-group="range">
                        <span class="staff-commission-adjust-group__label"><i class="bi bi-calendar3"></i><span class="js-commission-adjust-range-label">17 Apr - 23 Apr 2026</span></span>
                        <span class="staff-commission-adjust-group__meta"><span class="js-commission-adjust-range-count">0 faktur lain</span><i class="bi bi-chevron-down"></i></span>
                    </button>
                    <div class="staff-commission-adjust-group__list js-commission-adjust-group-list" data-group-list="range" hidden></div>
                </div>
            </div>
            <div class="staff-commission-adjust__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn js-commission-adjust-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staffAttendanceDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal staff-attendance-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-staff-attendance-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-staff-attendance-start" type="text" value="<?= e($today->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-staff-attendance-end" type="text" value="<?= e($today->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-staff-attendance-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-staff-attendance-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-staff-attendance-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staffNewAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered staff-new-attendance-dialog">
        <div class="modal-content staff-new-attendance-modal">
            <div class="staff-new-attendance-modal__head">
                <h2>New Attendance</h2>
                <button type="button" class="staff-work-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="staff-new-attendance-modal__body">
                <label>Tanggal</label>
                <button class="customer-picker js-new-attendance-date-button" type="button">
                    <span><i class="bi bi-calendar3"></i> <?= e($today->format('Y-m-d')) ?></span>
                </button>
                <input class="js-new-attendance-date staff-native-date" type="date" value="<?= e($today->format('Y-m-d')) ?>">

                <label>Staff</label>
                <select class="form-select customer-input-flat js-new-attendance-staff">
                    <option value="" selected disabled>Pilih staff</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= e((string) $member['id']) ?>" data-staff-name="<?= e($member['name']) ?>"><?= e($member['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="staff-new-attendance-grid">
                    <label>
                        <span>Shift Mulai</span>
                        <button class="staff-time-field js-staff-time-field" type="button" data-time-target="shiftStart"><i class="bi bi-clock"></i><span>08:00</span></button>
                    </label>
                    <label>
                        <span>Shift Berakhir</span>
                        <button class="staff-time-field js-staff-time-field" type="button" data-time-target="shiftEnd"><i class="bi bi-clock"></i><span>17:00</span></button>
                    </label>
                    <label>
                        <span>Clock in</span>
                        <button class="staff-time-field js-staff-time-field" type="button" data-time-target="clockIn"><i class="bi bi-clock"></i><span>08:00</span></button>
                    </label>
                    <label>
                        <span>Clock out</span>
                        <button class="staff-time-field js-staff-time-field" type="button" data-time-target="clockOut"><i class="bi bi-clock"></i><span>17:00</span></button>
                    </label>
                </div>

                <label>Catatan</label>
                <textarea class="form-control customer-input-flat js-new-attendance-note" rows="4" placeholder="Please input"></textarea>

                <div class="attendance-time-picker js-staff-time-picker" hidden>
                    <div class="attendance-time-picker__col">
                        <span>HH</span>
                        <div class="attendance-time-picker__list js-staff-time-hour"></div>
                    </div>
                    <div class="attendance-time-picker__col">
                        <span>mm</span>
                        <div class="attendance-time-picker__list js-staff-time-minute"></div>
                    </div>
                </div>
            </div>
            <div class="staff-new-attendance-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn js-new-attendance-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="staffWorkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered staff-work-dialog">
        <div class="modal-content staff-work-modal">
            <div class="staff-work-modal__head">
                <h2>Jam Kerja - <span class="js-staff-work-name">Staf</span></h2>
                <button type="button" class="staff-work-close" data-bs-dismiss="modal" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="staff-work-modal__body">
                <div class="staff-work-label">Hari</div>
                <div class="staff-work-date js-staff-work-date">Monday, 20 Apr 2026</div>

                <div class="staff-work-shifts js-staff-work-shifts"></div>

                <button class="staff-work-add-shift js-staff-work-add-shift" type="button"><i class="bi bi-plus-lg"></i><span>Tambah Shift Lain</span></button>

                <div class="staff-work-repeat">
                    <div class="staff-work-section-label">Ulang</div>
                    <div class="staff-work-segmented">
                        <button class="is-active" type="button" data-staff-repeat="none">Tidak berulang</button>
                        <button type="button" data-staff-repeat="weekly">Mingguan</button>
                    </div>
                </div>

                <div class="staff-work-weekly-options js-staff-work-weekly-options" hidden>
                    <div class="staff-work-section-label">Akhir perulangan</div>
                    <div class="staff-work-segmented">
                        <button type="button" data-staff-repeat-end="specific">Tanggal Spesifik</button>
                        <button class="is-active" type="button" data-staff-repeat-end="ongoing">Berlangsung</button>
                    </div>

                    <label class="staff-work-date-field js-staff-work-repeat-date-field" hidden>
                        <i class="bi bi-calendar3"></i>
                        <input class="js-staff-work-repeat-date" type="text" readonly aria-label="Tanggal akhir perulangan">
                    </label>
                </div>

                <button class="staff-work-delete js-staff-work-delete" type="button" hidden>Hapus</button>
            </div>

            <div class="staff-work-modal__footer">
                <button type="button" class="staff-work-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="staff-work-footer-btn staff-work-save js-staff-work-save">Simpan</button>
            </div>
        </div>
    </div>
</div>
