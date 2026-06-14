<?php
$customerTags = [];
$tagCustomers = [];
foreach ($customers as $customer) {
    foreach ($customer['tags'] as $tag) {
        $customerTags[$tag] = ($customerTags[$tag] ?? 0) + 1;
        $tagCustomers[$tag] ??= [];
        $tagCustomers[$tag][] = $customer['name'];
    }
}
?>

<section class="customers-shell js-customers-shell">
    <div class="customers-tabs">
        <button class="customers-tab is-active" type="button" data-customer-tab="customers">Pelanggan</button>
        <button class="customers-tab" type="button" data-customer-tab="tags">Tag Pelanggan</button>
    </div>

    <div class="customers-panels">
        <section class="customers-panel is-active" data-customer-panel="customers">
            <div class="customers-toolbar">
                <div class="customers-toolbar__inner">
                    <div class="customers-toolbar__group">
                        <button class="dashboard-filter js-customer-birth-filter" type="button" data-bs-toggle="modal" data-bs-target="#customerBirthFilterModal"><i class="bi bi-gift"></i><span>Tanggal Lahir</span></button>
                        <button class="dashboard-filter js-customer-import" type="button" data-bs-toggle="modal" data-bs-target="#customerImportModal">Import</button>
                        <div class="dropdown">
                            <button class="dashboard-filter ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span>Export</span><i class="bi bi-caret-down-fill"></i>
                            </button>
                            <div class="dropdown-menu ss-dropdown-menu">
                                <button class="dropdown-item js-customer-export" type="button" data-export="pdf">PDF</button>
                                <button class="dropdown-item js-customer-export" type="button" data-export="xls">XLS</button>
                                <button class="dropdown-item js-customer-export" type="button" data-export="xlsx">XLSX</button>
                                <button class="dropdown-item js-customer-export" type="button" data-export="csv">CSV</button>
                            </div>
                        </div>
                    </div>
                    <div class="customers-toolbar__group customers-toolbar__group--end">
                        <div class="dropdown">
                            <button class="dashboard-filter customers-filter-disabled ss-dropdown-toggle js-customer-tag-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="js-customer-tag-label">All Tags</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu ss-dropdown-menu ss-dropdown-menu--wide js-customer-tags-menu">
                                <button class="dropdown-item is-active js-customer-tag" type="button" data-tag="">All Tags</button>
                                <?php foreach ($customerTags as $tagName => $tagCount): ?>
                                    <button class="dropdown-item js-customer-tag" type="button" data-tag="<?= e($tagName) ?>"><?= e($tagName) ?> (<?= e((string) $tagCount) ?>)</button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="dashboard-filter customers-filter-name ss-dropdown-toggle js-customer-sort-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="js-customer-sort-label">Nama</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu ss-dropdown-menu js-customer-sort-menu">
                                <button class="dropdown-item is-active js-customer-sort" type="button" data-sort="name">Nama</button>
                                <button class="dropdown-item js-customer-sort" type="button" data-sort="phone">No. Telpon</button>
                                <button class="dropdown-item js-customer-sort" type="button" data-sort="last_visit">Kunjungan Terakhir</button>
                                <button class="dropdown-item js-customer-sort" type="button" data-sort="member_id">Member ID</button>
                            </div>
                        </div>
                        <label class="sales-search-field customers-search-field">
                            <input class="customers-search-input js-customer-search" type="text" placeholder="Ketik kata kunci" autocomplete="off">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                </div>
            </div>

            <div class="customers-table-card">
                <table class="customers-table js-customers-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>No. Telpon</th>
                            <th>Email</th>
                            <th>Member ID</th>
                            <th>Loyalty Point</th>
                            <th>Kunjungan Terakhir</th>
                            <th>Tanggal Lahir</th>
                            <th>Tags</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <?php
                            $birthdate = (string) ($customer['birthdate'] ?? '0000-00-00');
                            $tagsText = implode(', ', $customer['tags']);
                            ?>
                            <tr>
                                <td>
                                    <button class="customers-person-button js-customer-open" type="button" aria-label="Ubah data pelanggan <?= e($customer['name']) ?>">
                                        <div class="customers-person-cell">
                                            <div class="customers-person-cell__avatar"><i class="bi bi-emoji-smile"></i></div>
                                            <strong><?= e($customer['name']) ?></strong>
                                        </div>
                                    </button>
                                </td>
                                <td
                                    class="js-customer-row"
                                    data-customer-id="<?= e((string) $customer['id']) ?>"
                                    data-customer-name="<?= e($customer['name']) ?>"
                                    data-customer-phone="<?= e($customer['phone']) ?>"
                                    data-customer-email="<?= e($customer['email']) ?>"
                                    data-customer-member-id="<?= e($customer['member_id']) ?>"
                                    data-customer-loyalty="<?= e((string) $customer['loyalty_points']) ?>"
                                    data-customer-last-visit="<?= e((string) $customer['last_visit']) ?>"
                                    data-customer-birthdate="<?= e($birthdate) ?>"
                                    data-customer-tags="<?= e(implode('|', $customer['tags'])) ?>"
                                    data-customer-gender="<?= e((string) ($customer['gender'] ?? '')) ?>"
                                    data-customer-status="<?= e((string) ($customer['status'] ?? 'Aktif')) ?>"
                                    data-customer-notes="<?= e((string) ($customer['notes'] ?? '')) ?>"
                                    data-customer-address="<?= e((string) ($customer['address'] ?? '')) ?>"
                                    data-customer-family-card-number="<?= e((string) ($customer['family_card_number'] ?? '')) ?>"
                                    data-customer-passport-number="<?= e((string) ($customer['passport_number'] ?? '')) ?>"
                                    data-customer-notify-via="<?= e((string) ($customer['notify_via'] ?? 'off')) ?>"
                                    data-customer-marketing-opt-in="<?= !empty($customer['marketing_opt_in']) ? '1' : '0' ?>"
                                ><?= e($customer['phone']) ?></td>
                                <td><?= e($customer['email']) ?></td>
                                <td><?= e($customer['member_id']) ?></td>
                                <td><?= e((string) $customer['loyalty_points']) ?></td>
                                <td><?= e(str_replace('T', ' ', substr($customer['last_visit'], 0, 19))) ?></td>
                                <td><?= e($birthdate) ?></td>
                                <td><?= e($tagsText) ?></td>
                                <td><span class="customers-status-pill"><?= e((string) ($customer['status'] ?? 'Aktif')) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="sales-pagination sales-pagination--services sales-pagination--fixed" data-sales-pagination="customers">
                    <div class="sales-pagination__meta">Total <span class="js-customers-total"><?= e((string) count($customers)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-customers-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-customers-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-customers-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-customers-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-customers-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-customers-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-customers-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </section>

        <section class="customers-panel" data-customer-panel="tags">
            <div class="customers-table-card customers-tags-card">
                <div class="customers-tags-list">
                    <?php foreach ($customerTags as $tagName => $tagCount): ?>
                        <?php $names = $tagCustomers[$tagName] ?? []; ?>
                        <div class="customers-tag-row">
                            <button
                                class="customers-tag-row__handle"
                                type="button"
                                aria-label="Ubah tag"
                                data-bs-toggle="modal"
                                data-bs-target="#customerEditTagModal"
                                data-tag-name="<?= e($tagName) ?>"
                                data-tag-customers="<?= e(implode('|', $names)) ?>"
                            >
                                <i class="bi bi-list"></i>
                            </button>
                            <div class="customers-tag-row__name"><?= e($tagName) ?></div>
                            <div class="customers-tag-row__count"><?= e((string) $tagCount) ?></div>
                            <div class="customers-tag-row__arrow"><i class="bi bi-chevron-right"></i></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </div>

    <div class="customers-fab-wrapper">
        <button class="customers-fab js-customers-fab" type="button" aria-label="Tambah pelanggan" data-bs-toggle="modal" data-bs-target="#customerModal">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<div class="modal fade" id="customerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal">
            <div class="customer-modal__header">
                <div></div>
                <h2>Tambah Pelanggan Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-modal__body">
                <div class="customer-form-grid">
                    <div class="customer-form-col">
                        <label>Photo</label>
                        <div class="customer-upload-box">Drop file here or <span>click to upload</span></div>

                        <label>Nama</label>
                        <input class="form-control customer-input-flat js-customer-create-name" type="text">

                        <label>Jenis Kelamin</label>
                        <div class="customer-segmented js-customer-create-gender">
                            <button class="is-active" type="button" data-customer-create-gender="non-active">Non-Aktifkan</button>
                            <button type="button" data-customer-create-gender="pria">Pria</button>
                            <button type="button" data-customer-create-gender="wanita">Wanita</button>
                        </div>

                        <label>No. Telpon</label>
                        <div class="customer-phone-row">
                            <span class="customer-phone-flag"></span>
                            <input class="form-control customer-input-flat js-customer-create-phone" type="text" value="+62">
                        </div>

                        <label>Email</label>
                        <input class="form-control customer-input-flat js-customer-create-email" type="text">

                        <label>Member ID</label>
                        <div class="customer-counter-field">
                            <input class="form-control customer-input-flat js-customer-create-member-id" type="text">
                            <span class="js-customer-create-member-counter">0 / 16</span>
                        </div>
                        <small>Max. 16 digits</small>

                        <label>Tanggal Lahir</label>
                        <div class="customer-birthday-row">
                            <input class="form-control customer-input-flat js-customer-create-birth-year" type="text" placeholder="YYYY">
                            <span>-</span>
                            <input class="form-control customer-input-flat js-customer-create-birth-month" type="text" placeholder="MMM">
                            <span>-</span>
                            <input class="form-control customer-input-flat js-customer-create-birth-day" type="text" placeholder="DD">
                        </div>
                    </div>

                    <div class="customer-form-col">
                        <label>Nomor Kartu Keluarga</label>
                        <div class="customer-counter-field">
                            <input class="form-control customer-input-flat js-customer-create-family-card" type="text">
                            <span class="js-customer-create-family-counter">0 / 16</span>
                        </div>

                        <label>Paspor</label>
                        <input class="form-control customer-input-flat js-customer-create-passport" type="text">

                        <label>Catatan</label>
                        <textarea class="form-control customer-input-flat js-customer-create-notes" rows="4"></textarea>

                        <label>Tags</label>
                        <div class="dropdown">
                            <button class="customer-picker ss-dropdown-toggle js-customer-create-tag-picker" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="js-customer-create-tag-picker-label">No item</span><strong>Pilih</strong>
                            </button>
                            <div class="dropdown-menu ss-dropdown-menu ss-dropdown-menu--wide">
                                <?php foreach (array_keys($customerTags) as $tagName): ?>
                                    <button class="dropdown-item js-customer-create-tag-option" type="button" data-customer-create-tag="<?= e($tagName) ?>"><?= e($tagName) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <label>Kirim notifikasi melalui:</label>
                        <div class="customer-segmented customer-segmented--two js-customer-create-notify">
                            <button class="is-active" type="button" data-customer-create-notify="email">Email</button>
                            <button type="button" data-customer-create-notify="off">Non-Aktifkan</button>
                        </div>

                        <label class="customer-toggle-row customer-toggle-row--interactive">
                            <span>Terima notifikasi marketing:</span>
                            <input class="customer-marketing-toggle js-customer-create-marketing-toggle" type="checkbox">
                            <span class="customer-toggle-track"></span>
                        </label>

                        <h3>Alamat pelanggan</h3>
                        <label>Alamat</label>
                        <textarea class="form-control customer-input-flat js-customer-create-address" rows="6"></textarea>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-customer-create-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerBirthFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-customer-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-customer-birth-start" type="text" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-customer-birth-end" type="text" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-customer-birth-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-customer-birth-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-customer-birth-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal customer-edit-modal">
            <div class="customer-modal__header customer-edit-modal__header">
                <div></div>
                <h2 class="js-customer-edit-title">Ubah Data Pelanggan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="customer-edit-modal__tabs">
                <button class="customer-edit-modal__tab is-active" type="button" data-customer-edit-tab="profile">Profile</button>
                <button class="customer-edit-modal__tab" type="button" data-customer-edit-tab="details">Rincian</button>
            </div>

            <div class="customer-edit-modal__body">
                <section class="customer-edit-panel is-active" data-customer-edit-panel="profile">
                    <div class="customer-edit-profile">
                        <div class="customer-edit-profile__summary">
                            <div class="customer-edit-profile__identity">
                                <div class="customer-edit-profile__avatar"><i class="bi bi-emoji-smile"></i></div>
                                <div>
                                    <h3 class="js-customer-profile-name">Pelanggan</h3>
                                    <div class="customer-edit-profile__meta">
                                        <span class="js-customer-profile-phone">-</span>
                                        <span class="js-customer-profile-member">-</span>
                                    </div>
                                </div>
                            </div>

                            <div class="customer-edit-profile__shortcuts">
                                <button class="customer-profile-shortcut is-active js-customer-profile-shortcut" type="button" data-customer-profile-target="agenda">
                                    <span class="customer-profile-shortcut__icon"><i class="bi bi-journal-text"></i></span>
                                    <span>Record</span>
                                </button>
                                <button class="customer-profile-shortcut js-customer-profile-shortcut" type="button" data-customer-profile-target="layanan">
                                    <span class="customer-profile-shortcut__icon"><i class="bi bi-moon-stars-fill"></i></span>
                                    <span>Plan</span>
                                </button>
                                <button class="customer-profile-shortcut js-customer-profile-shortcut" type="button" data-customer-profile-target="produk">
                                    <span class="customer-profile-shortcut__icon"><i class="bi bi-ticket-perforated-fill"></i></span>
                                    <span>Voucher</span>
                                </button>
                                <button class="customer-profile-shortcut js-customer-profile-shortcut" type="button" data-customer-profile-target="faktur">
                                    <span class="customer-profile-shortcut__icon"><i class="bi bi-star-fill"></i></span>
                                    <span>Loyalty Point</span>
                                </button>
                            </div>
                        </div>

                        <div class="customer-edit-profile__stats">
                            <div class="customer-profile-stat"><span>Total Penjualan</span><strong class="js-customer-stat-sales">0,00</strong></div>
                            <div class="customer-profile-stat"><span>Penggunaan voucher</span><strong class="js-customer-stat-vouchers">0</strong></div>
                            <div class="customer-profile-stat"><span>Belum bayar</span><strong class="js-customer-stat-due">0,00</strong></div>
                            <div class="customer-profile-stat"><span>Total Booking</span><strong class="js-customer-stat-booking">0</strong></div>
                            <div class="customer-profile-stat"><span>Komplit</span><strong class="js-customer-stat-complete">0</strong></div>
                            <div class="customer-profile-stat"><span>Pembatalan</span><strong class="js-customer-stat-cancel">0</strong></div>
                            <div class="customer-profile-stat"><span>Tidak hadir</span><strong class="js-customer-stat-noshow">0</strong></div>
                        </div>

                        <div class="customer-edit-profile__section-head">
                            <div class="customer-detail-switcher">
                                <button class="customer-detail-switcher__btn is-active" type="button" data-customer-detail-tab="agenda">Agenda</button>
                                <button class="customer-detail-switcher__btn" type="button" data-customer-detail-tab="layanan">Layanan</button>
                                <button class="customer-detail-switcher__btn" type="button" data-customer-detail-tab="produk">Produk</button>
                                <button class="customer-detail-switcher__btn" type="button" data-customer-detail-tab="faktur">Faktur</button>
                            </div>

                            <div class="customer-edit-profile__actions">
                                <button class="customer-profile-action customer-profile-action--primary js-customer-add-agenda" type="button">Agenda Baru</button>
                                <div class="dropdown">
                                    <button class="customer-profile-action ss-dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <span>Lainnya</span><i class="bi bi-caret-down-fill"></i>
                                    </button>
                                    <div class="dropdown-menu ss-dropdown-menu">
                                        <button class="dropdown-item js-customer-more-action" type="button" data-customer-more-action="block">Block</button>
                                        <button class="dropdown-item js-customer-more-action" type="button" data-customer-more-action="delete">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="customer-detail-card js-customer-upcoming-card">
                            <div class="customer-detail-card__title js-customer-upcoming-title">Akan Datang</div>
                            <div class="customer-detail-table-wrap">
                                <table class="customers-table customer-detail-table">
                                    <thead class="js-customer-upcoming-head">
                                        <tr></tr>
                                    </thead>
                                    <tbody class="js-customer-upcoming-body"></tbody>
                                </table>
                            </div>
                            <div class="customers-table-footer customer-detail-table__footer">
                                <div class="js-customer-upcoming-total">Total 0</div>
                                <button class="dashboard-filter customers-mini-filter" type="button"><span>10/page</span><i class="bi bi-chevron-down"></i></button>
                                <button class="customers-pagination-btn js-customer-page-prev" type="button" data-customer-page-scope="upcoming"><i class="bi bi-chevron-left"></i></button>
                                <span class="customers-pagination-current js-customer-page-current" data-customer-page-scope="upcoming">1</span>
                                <button class="customers-pagination-btn js-customer-page-next" type="button" data-customer-page-scope="upcoming"><i class="bi bi-chevron-right"></i></button>
                                <div>Go to</div>
                                <button class="dashboard-filter customers-mini-input js-customer-page-input" type="button" data-customer-page-scope="upcoming">1</button>
                            </div>
                        </div>

                        <div class="customer-detail-card js-customer-past-card">
                            <div class="customer-detail-card__title js-customer-past-title">Berlalu</div>
                            <div class="customer-detail-table-wrap">
                                <table class="customers-table customer-detail-table">
                                    <thead class="js-customer-past-head">
                                        <tr></tr>
                                    </thead>
                                    <tbody class="js-customer-past-body"></tbody>
                                </table>
                            </div>
                            <div class="customers-table-footer customer-detail-table__footer">
                                <div class="js-customer-past-total">Total 0</div>
                                <button class="dashboard-filter customers-mini-filter" type="button"><span>10/page</span><i class="bi bi-chevron-down"></i></button>
                                <button class="customers-pagination-btn js-customer-page-prev" type="button" data-customer-page-scope="past"><i class="bi bi-chevron-left"></i></button>
                                <span class="customers-pagination-current js-customer-page-current" data-customer-page-scope="past">1</span>
                                <button class="customers-pagination-btn js-customer-page-next" type="button" data-customer-page-scope="past"><i class="bi bi-chevron-right"></i></button>
                                <div>Go to</div>
                                <button class="dashboard-filter customers-mini-input js-customer-page-input" type="button" data-customer-page-scope="past">1</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="customer-edit-panel" data-customer-edit-panel="details">
                    <div class="customer-form-grid customer-edit-form">
                        <div class="customer-form-col">
                            <label>Photo</label>
                            <div class="customer-edit-photo-row">
                                <div class="customer-edit-photo"><i class="bi bi-emoji-smile"></i></div>
                                <button class="dashboard-filter js-customer-photo-change" type="button"><i class="bi bi-image"></i><span>Ganti</span></button>
                            </div>

                            <label>Nama</label>
                            <input class="form-control customer-input-flat js-customer-edit-name" type="text">

                            <label>Jenis Kelamin</label>
                            <div class="customer-segmented js-customer-gender">
                                <button type="button" data-customer-gender="non-active">Non-Aktifkan</button>
                                <button type="button" data-customer-gender="pria">Pria</button>
                                <button type="button" data-customer-gender="wanita">Wanita</button>
                            </div>

                            <label>No. Telpon</label>
                            <div class="customer-phone-row">
                                <span class="customer-phone-flag"></span>
                                <input class="form-control customer-input-flat js-customer-edit-phone" type="text">
                            </div>

                            <label>Email</label>
                            <input class="form-control customer-input-flat js-customer-edit-email" type="text">

                            <label>Member ID</label>
                            <div class="customer-counter-field">
                                <input class="form-control customer-input-flat js-customer-edit-member-id" type="text">
                                <span class="js-customer-member-counter">0 / 16</span>
                            </div>
                            <small>Max. 16 digits</small>

                            <label>Tanggal Lahir</label>
                            <div class="customer-birthday-row">
                                <input class="form-control customer-input-flat js-customer-edit-birth-year" type="text" placeholder="YYYY">
                                <span>-</span>
                                <input class="form-control customer-input-flat js-customer-edit-birth-month" type="text" placeholder="MMM">
                                <span>-</span>
                                <input class="form-control customer-input-flat js-customer-edit-birth-day" type="text" placeholder="DD">
                            </div>

                            <button class="customer-delete-btn js-customer-delete" type="button">Hapus Customer</button>
                        </div>

                        <div class="customer-form-col">
                            <label>Nomor Kartu Keluarga</label>
                            <div class="customer-counter-field">
                                <input class="form-control customer-input-flat js-customer-edit-family-card" type="text">
                                <span class="js-customer-family-counter">0 / 16</span>
                            </div>

                            <label>Paspor</label>
                            <input class="form-control customer-input-flat js-customer-edit-passport" type="text">

                            <label>Catatan</label>
                            <textarea class="form-control customer-input-flat js-customer-edit-notes" rows="4"></textarea>

                            <label>Tags</label>
                            <div class="dropdown">
                                <button class="customer-picker ss-dropdown-toggle js-customer-tag-picker" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="js-customer-tag-picker-label">No item</span><strong>Pilih</strong>
                                </button>
                                <div class="dropdown-menu ss-dropdown-menu ss-dropdown-menu--wide customer-tag-picker-menu">
                                    <?php foreach (array_keys($customerTags) as $tagName): ?>
                                        <button class="dropdown-item js-customer-edit-tag-option" type="button" data-customer-edit-tag="<?= e($tagName) ?>"><?= e($tagName) ?></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <label>Kirim notifikasi melalui:</label>
                            <div class="customer-segmented customer-segmented--two js-customer-notify">
                                <button type="button" data-customer-notify="email">Email</button>
                                <button type="button" data-customer-notify="off">Non-Aktifkan</button>
                            </div>

                            <label class="customer-toggle-row customer-toggle-row--interactive">
                                <span>Terima notifikasi marketing:</span>
                                <input class="customer-marketing-toggle js-customer-marketing-toggle" type="checkbox">
                                <span class="customer-toggle-track"></span>
                            </label>

                            <h3>Alamat pelanggan</h3>
                            <label>Alamat</label>
                            <textarea class="form-control customer-input-flat js-customer-edit-address" rows="6"></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <div class="customer-modal__footer customer-edit-modal__footer js-customer-edit-footer" hidden>
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-customer-edit-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerAgendaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content sales-agenda-modal customer-agenda-modal">
            <div class="sales-agenda-modal__header">
                <div></div>
                <h2>Agenda Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="sales-agenda-modal__body">
                <div class="sales-agenda-left">
                    <label class="sales-agenda-searchbar customer-agenda-searchbar">
                        <i class="bi bi-arrow-left"></i>
                        <input class="js-customer-agenda-search" type="search" placeholder="Cari service..." autocomplete="off">
                        <i class="bi bi-search"></i>
                    </label>
                    <div class="sales-agenda-chips">
                        <button class="sales-chip is-active js-customer-agenda-filter" type="button" data-agenda-filter="all">Paket Layanan</button>
                        <button class="sales-chip js-customer-agenda-filter" type="button" data-agenda-filter="hair-cut">Hair Cut</button>
                        <button class="sales-chip js-customer-agenda-filter" type="button" data-agenda-filter="hair-treatment">Hair Treatment</button>
                        <button class="sales-chip js-customer-agenda-filter" type="button" data-agenda-filter="hair-coloring">Hair Coloring</button>
                    </div>
                    <div class="sales-service-grid js-customer-agenda-services">
                        <button class="sales-service-card customer-agenda-service-card" type="button" data-agenda-service-id="svc-1" data-agenda-service-name="Signature Haircut" data-agenda-service-price="280000" data-agenda-service-duration="1h" data-agenda-service-category="hair-cut">
                            <div class="sales-service-card__thumb">SI</div>
                            <div class="sales-service-card__body">
                                <strong>Signature Haircut</strong>
                                <span>1h • Rp 280.000</span>
                            </div>
                        </button>
                        <button class="sales-service-card customer-agenda-service-card" type="button" data-agenda-service-id="svc-2" data-agenda-service-name="Glossy Balayage" data-agenda-service-price="1250000" data-agenda-service-duration="3h" data-agenda-service-category="hair-coloring">
                            <div class="sales-service-card__thumb">GL</div>
                            <div class="sales-service-card__body">
                                <strong>Glossy Balayage</strong>
                                <span>3h • Rp 1.250.000</span>
                            </div>
                        </button>
                        <button class="sales-service-card customer-agenda-service-card" type="button" data-agenda-service-id="svc-3" data-agenda-service-name="Keratin Repair" data-agenda-service-price="650000" data-agenda-service-duration="2h" data-agenda-service-category="hair-treatment">
                            <div class="sales-service-card__thumb">KE</div>
                            <div class="sales-service-card__body">
                                <strong>Keratin Repair</strong>
                                <span>2h • Rp 650.000</span>
                            </div>
                        </button>
                        <button class="sales-service-card customer-agenda-service-card" type="button" data-agenda-service-id="svc-4" data-agenda-service-name="Relaxing Head Spa" data-agenda-service-price="450000" data-agenda-service-duration="2h" data-agenda-service-category="hair-treatment">
                            <div class="sales-service-card__thumb">RE</div>
                            <div class="sales-service-card__body">
                                <strong>Relaxing Head Spa</strong>
                                <span>2h • Rp 450.000</span>
                            </div>
                        </button>
                        <button class="sales-service-card customer-agenda-service-card" type="button" data-agenda-service-id="svc-5" data-agenda-service-name="Signature Gel Nails" data-agenda-service-price="520000" data-agenda-service-duration="2h" data-agenda-service-category="hair-coloring">
                            <div class="sales-service-card__thumb">SG</div>
                            <div class="sales-service-card__body">
                                <strong>Signature Gel Nails</strong>
                                <span>2h • Rp 520.000</span>
                            </div>
                        </button>
                    </div>
                    <div class="sales-agenda-footer">
                        <div class="sales-agenda-footer__summary js-customer-agenda-summary">0 Layanan • Rp 0</div>
                        <button class="sales-agenda-footer__action js-customer-agenda-add" type="button" disabled>Tambahkan 0 Layanan</button>
                    </div>
                </div>
                <div class="sales-agenda-right">
                    <div class="sales-agenda-customer">
                        <div class="sales-agenda-customer__avatar"><i class="bi bi-emoji-smile"></i></div>
                        <div>
                            <strong class="js-customer-agenda-name">Pelanggan</strong>
                            <span class="js-customer-agenda-tag">Star Salon</span>
                        </div>
                        <div class="dropdown">
                            <button type="button" class="sales-agenda-more ss-dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-three-dots"></i></button>
                            <div class="dropdown-menu ss-dropdown-menu">
                                <button class="dropdown-item js-customer-agenda-more" type="button" data-customer-agenda-more="block">Blokir Pelanggan</button>
                            </div>
                        </div>
                    </div>
                    <div class="sales-agenda-actions">
                        <button type="button" class="js-customer-agenda-checkout" disabled>Checkout</button>
                        <button type="button" class="js-customer-agenda-submit" disabled>Simpan Agenda</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content customers-import-modal">
            <div class="customers-import-modal__header">
                <h2>Import Pelanggan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customers-import-modal__body">
                <div class="customers-import-hero">
                    <div class="customers-import-hero__icons">
                        <div class="customers-import-file">XLS</div>
                        <div class="customers-import-file">CSV</div>
                    </div>
                    <p>Klik pilih file untuk melakukan import. <a href="#" class="customers-import-link js-customer-import-help">Klik link ini</a> untuk mengetahui cara pengisian excel file</p>
                    <div class="customers-import-actions">
                        <button class="dashboard-filter customers-import-action js-customer-template" type="button">Download Template</button>
                        <label class="dashboard-filter customers-import-action customers-import-upload">
                            <input class="js-customer-import-file" type="file" accept=".csv,text/csv">
                            <span>Pilih File</span>
                        </label>
                    </div>
                    <div class="customers-import-meta js-customer-import-meta">Belum ada file dipilih</div>
                </div>
            </div>
            <div class="customers-import-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customers-import-btn js-customer-import-run" disabled>Import (0)</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerEditTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content customers-tag-edit-modal">
            <div class="customers-tag-edit-modal__header">
                <h2>Ubah Tag</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customers-tag-edit-modal__body">
                <label>Nama</label>
                <input class="form-control customer-tag-input js-edit-tag-name" type="text" value="">

                <div class="customers-tag-edit-modal__label">Pelanggan di dalam tag</div>
                <div class="customers-tag-edit-list js-edit-tag-list">
                    <div class="customers-tag-edit-empty">
                        <div class="customers-tag-edit-empty__icon"><i class="bi bi-person-circle"></i></div>
                        <div class="customers-tag-edit-empty__title">Belum Ada Pelanggan Di Dalam Tag Ini</div>
                        <div class="customers-tag-edit-empty__sub">Tambahkan tag di halaman detail pelanggan</div>
                    </div>
                </div>

                <button class="customers-tag-edit-delete js-edit-tag-delete" type="button">Hapus</button>
            </div>
            <div class="customers-tag-edit-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--primary js-edit-tag-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="customerTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content customer-tag-modal">
            <div class="customer-tag-modal__header">
                <h2>Tambah Tag</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-tag-modal__body">
                <label>Nama</label>
                <input class="form-control customer-tag-input" type="text">
                <button class="customer-tag-delete" type="button">Hapus</button>
            </div>
            <div class="customer-tag-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--disabled">Simpan</button>
            </div>
        </div>
    </div>
</div>
