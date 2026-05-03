<?php
$servicesByGroup = [];
foreach ($groups as $group) {
    $servicesByGroup[$group['id']] = array_values(array_filter($services, fn (array $service): bool => (int) $service['group_id'] === (int) $group['id']));
}
?>

<section class="services-shell js-services-shell">
    <div class="services-tabs">
        <button class="services-tab is-active" type="button" data-services-tab="groups">Grup Layanan</button>
        <button class="services-tab" type="button" data-services-tab="services">Layanan</button>
        <button class="services-tab" type="button" data-services-tab="packages">Paket Layanan</button>
    </div>

    <div class="services-panels">
        <section class="services-panel is-active" data-services-panel="groups">
            <div class="services-toolbar">
                <div class="services-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter" type="button"><span>Urutkan</span><i class="bi bi-chevron-down"></i></button>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <div class="sales-search-field services-search"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                    <div class="services-menu-wrap">
                        <button class="services-dots services-dots--horizontal" type="button" data-services-menu-toggle>
                            <i class="bi bi-three-dots"></i>
                        </button>
                        <div class="services-dropdown" data-services-menu>
                            <button type="button">Export daftar</button>
                            <button type="button">Refresh</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="services-group-grid">
                <?php foreach ($groups as $group): ?>
                    <article class="services-group-card">
                        <div class="services-group-card__header">
                            <div>
                                <h3><?= e($group['name']) ?></h3>
                                <span><?= e((string) count($servicesByGroup[$group['id']] ?? [])) ?> layanan</span>
                            </div>
                            <div class="services-menu-wrap">
                                <button class="services-dots services-dots--vertical" type="button" data-services-menu-toggle>
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="services-dropdown" data-services-menu>
                                    <button type="button">Edit grup</button>
                                    <button type="button">Atur urutan</button>
                                    <button type="button">Arsipkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="services-group-card__body">
                            <?php foreach (array_slice($servicesByGroup[$group['id']] ?? [], 0, 3) as $service): ?>
                                <div class="services-group-inline-item">
                                    <strong><?= e($service['name']) ?></strong>
                                    <span><?= money($service['price']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="services-panel" data-services-panel="services">
            <div class="services-toolbar">
                <div class="services-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter services-filter-disabled" type="button">Semua Grup</button>
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <button class="dashboard-filter customers-filter-name" type="button"><span>Nama</span><i class="bi bi-chevron-down"></i></button>
                    <div class="sales-search-field services-search"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>

            <div class="services-card-grid">
                <?php foreach ($services as $service): ?>
                    <?php
                    $groupName = '';
                    foreach ($groups as $group) {
                        if ((int) $group['id'] === (int) $service['group_id']) {
                            $groupName = $group['name'];
                            break;
                        }
                    }
                    ?>
                    <article class="service-card">
                        <div class="service-card__accent"></div>
                        <div class="service-card__content">
                            <div class="service-card__header">
                                <div>
                                    <span class="service-card__group"><?= e($groupName) ?></span>
                                    <h3><?= e($service['name']) ?></h3>
                                </div>
                                <div class="services-menu-wrap">
                                    <button class="services-dots services-dots--vertical" type="button" data-services-menu-toggle>
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="services-dropdown" data-services-menu>
                                        <button type="button">Edit layanan</button>
                                        <button type="button">Duplikat</button>
                                        <button type="button">Nonaktifkan</button>
                                    </div>
                                </div>
                            </div>
                            <p><?= e($service['description']) ?></p>
                            <div class="service-card__meta">
                                <span><i class="bi bi-clock"></i> <?= e((string) $service['duration']) ?> menit</span>
                                <span><i class="bi bi-people"></i> <?= e((string) count($service['staff_ids'])) ?> staf</span>
                            </div>
                            <div class="service-card__footer">
                                <div class="service-card__variants"><?= e(implode(' • ', $service['variants'])) ?></div>
                                <strong><?= money($service['price']) ?></strong>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="services-panel" data-services-panel="packages">
            <div class="services-toolbar">
                <div class="services-toolbar__group">
                    <button class="dashboard-filter dashboard-filter--shop" type="button">
                        <i class="bi bi-shop"></i>
                        <span>Star Salon</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button class="dashboard-filter services-filter-disabled" type="button">Semua Grup</button>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    <div class="sales-search-field services-search"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
                </div>
            </div>

            <div class="services-package-grid">
                <?php foreach ($packages as $package): ?>
                    <article class="services-package-card">
                        <div class="services-package-card__head">
                            <div>
                                <h3><?= e($package['name']) ?></h3>
                                <span><?= e((string) count($package['items'])) ?> item paket</span>
                            </div>
                            <div class="services-menu-wrap">
                                <button class="services-dots services-dots--horizontal" type="button" data-services-menu-toggle>
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <div class="services-dropdown" data-services-menu>
                                    <button type="button">Edit paket</button>
                                    <button type="button">Duplikat</button>
                                    <button type="button">Nonaktifkan</button>
                                </div>
                            </div>
                        </div>
                        <div class="services-package-card__items">
                            <?php foreach ($package['items'] as $item): ?>
                                <span><?= e($item) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="services-package-card__price"><?= money($package['price']) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="services-fab-wrapper">
        <button class="customers-fab js-services-fab" type="button" aria-label="Tambah grup layanan" data-bs-toggle="modal" data-bs-target="#serviceGroupModal">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<div class="modal fade" id="serviceGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content customer-tag-modal services-dialog">
            <div class="customer-tag-modal__header">
                <h2>Tambah Grup Layanan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-tag-modal__body">
                <label>Nama grup</label>
                <input class="form-control customer-tag-input" type="text" placeholder="Misalnya Hair Signature">
            </div>
            <div class="customer-tag-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="serviceGroupPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content services-dialog services-picker-dialog">
            <div class="customer-tag-modal__header">
                <h2 class="js-service-picker-title">Pilih Grup Layanan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="services-picker-body">
                <?php foreach ($groups as $group): ?>
                    <button class="services-picker-option" type="button" data-bs-dismiss="modal">
                        <strong><?= e($group['name']) ?></strong>
                        <span><?= e((string) count($servicesByGroup[$group['id']] ?? [])) ?> layanan aktif</span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="customer-tag-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="customer-footer-btn staff-save-btn">Lanjut</button>
            </div>
        </div>
    </div>
</div>
