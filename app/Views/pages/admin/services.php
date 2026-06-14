<?php
$staffMembers = $staff ?? [];
$productList = $products ?? [];
$servicesByGroup = [];
$servicesByName = [];
foreach ($groups as $group) {
    $servicesByGroup[$group['id']] = array_values(array_filter($services, fn (array $service): bool => (int) $service['group_id'] === (int) $group['id']));
}
foreach ($services as $service) {
    $servicesByName[strtolower((string) ($service['name'] ?? ''))] = $service;
}

$groupPalette = [
    1 => '#6a7dff',
    2 => '#4ecdc4',
    3 => '#8b6df6',
];

$serviceTreatmentTypes = [
    'Blow & Styling',
    'Coloring',
    'Hair Cut',
    'Hair Extension',
    'Hair Spa & Treatment',
    'Perm & Rebonding',
    'Cuci & Blow',
    'Perawatan Pria',
    'Other',
];

$serviceDurations = [];
for ($minutes = 5; $minutes <= 55; $minutes += 5) {
    $serviceDurations[] = sprintf('%d min', $minutes);
}

$serviceDurations[] = '1 h';
for ($minutes = 65; $minutes <= 115; $minutes += 5) {
    $hours = intdiv($minutes, 60);
    $remainder = $minutes % 60;
    $serviceDurations[] = sprintf('%d h %d min', $hours, $remainder);
}

$serviceDurations[] = '2 h';
foreach ([15, 30, 45] as $remainder) {
    $serviceDurations[] = sprintf('2 h %d min', $remainder);
}

$serviceDurations[] = '3 h';
foreach ([15, 30] as $remainder) {
    $serviceDurations[] = sprintf('3 h %d min', $remainder);
}

for ($hours = 4; $hours <= 23; $hours++) {
    $serviceDurations[] = sprintf('%d h', $hours);
    $serviceDurations[] = sprintf('%d h 30 min', $hours);
}

$groupCards = array_map(function (array $group) use ($servicesByGroup, $groupPalette): array {
    $groupServices = $servicesByGroup[$group['id']] ?? [];
    return [
        'id' => $group['id'],
        'name' => $group['name'],
        'color' => $group['color'] ?? ($groupPalette[$group['id']] ?? '#6a7dff'),
        'description' => $group['description'] ?? '',
        'service_count' => count($groupServices),
        'services' => array_slice($groupServices, 0, 3),
    ];
}, $groups);

$serviceGroupColorOptions = ['#76b6e8', '#8be2e6', '#82e3c6', '#97e8aa', '#b9f85d', '#dc88c1', '#c98beb', '#b19aea', '#a7aef0', '#9dbaf0', '#efff45', '#fff33d', '#ffd857', '#f3b66f', '#8b6df6'];
$resolveServiceAudience = static function (array $service): array {
    $audience = array_values(array_filter($service['variants'] ?? [], static fn ($value): bool => in_array($value, ['Women', 'Men'], true)));
    return $audience !== [] ? $audience : ['Women', 'Men'];
};
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
                    <div class="services-menu-wrap services-toolbar__shop-menu">
                        <button class="dashboard-filter dashboard-filter--shop" type="button" data-services-menu-toggle>
                            <i class="bi bi-shop"></i>
                            <span data-services-shop-label="groups">Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-shop-option="groups" data-shop-name="Star Salon">Star Salon</button>
                        </div>
                    </div>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <label class="sales-search-field services-search" aria-label="Cari grup layanan">
                        <input type="text" placeholder="Ketik kata kunci" data-services-search="groups">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="services-group-grid">
                <?php foreach ($groupCards as $group): ?>
                    <article
                        class="services-group-card"
                        data-service-group-card
                        data-group-id="<?= e((string) $group['id']) ?>"
                        data-group-name="<?= e($group['name']) ?>"
                        data-group-description="<?= e($group['description']) ?>"
                        data-group-color="<?= e($group['color']) ?>"
                        data-group-image="<?= e((string) ($group['image_data_url'] ?? '')) ?>"
                        data-search-text="<?= e(strtolower($group['name'] . ' ' . $group['description'] . ' ' . implode(' ', array_column($group['services'], 'name')))) ?>"
                        style="--service-group-accent: <?= e($group['color']) ?>;"
                    >
                        <div class="services-group-card__header">
                            <div class="services-group-card__summary">
                                <button class="services-group-card__thumb" type="button" data-service-group-image-trigger aria-label="Upload gambar <?= e($group['name']) ?>">
                                    <i class="bi bi-image"></i>
                                </button>
                                <div>
                                    <h3><?= e($group['name']) ?></h3>
                                    <span><?= e((string) $group['service_count']) ?> layanan</span>
                                </div>
                            </div>
                            <div class="services-menu-wrap">
                                <button class="services-dots services-dots--vertical" type="button" data-services-menu-toggle>
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="services-dropdown" data-services-menu>
                                    <button type="button" data-service-group-action="add-service" data-group-id="<?= e((string) $group['id']) ?>">Tambah layanan</button>
                                    <button type="button" data-service-group-action="edit" data-group-id="<?= e((string) $group['id']) ?>">Edit group</button>
                                </div>
                            </div>
                        </div>
                        <div class="services-group-card__body" data-service-group-body>
                            <?php foreach ($group['services'] as $service): ?>
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
                    <div class="services-menu-wrap services-toolbar__shop-menu">
                        <button class="dashboard-filter dashboard-filter--shop" type="button" data-services-menu-toggle>
                            <i class="bi bi-shop"></i>
                            <span data-services-shop-label="services">Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-shop-option="services" data-shop-name="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <div class="services-menu-wrap services-toolbar-dropdown">
                        <button class="dashboard-filter services-toolbar-dropdown__toggle" type="button" data-services-menu-toggle>
                            <span data-services-group-label="services">Semua Grup</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-group-filter="services" data-group-filter-id="all">Semua Grup</button>
                            <?php foreach ($groups as $group): ?>
                                <button type="button" data-services-group-filter="services" data-group-filter-id="<?= e((string) $group['id']) ?>"><?= e($group['name']) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="services-menu-wrap services-toolbar-dropdown services-toolbar-dropdown--compact">
                        <button class="dashboard-filter services-toolbar-dropdown__toggle" type="button" data-services-menu-toggle>
                            <span>Export</span>
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-export="services" data-export-type="csv">Export CSV</button>
                        </div>
                    </div>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <label class="sales-search-field services-search" aria-label="Cari layanan">
                        <input type="text" placeholder="Ketik kata kunci" data-services-search="services">
                        <i class="bi bi-search"></i>
                    </label>
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
                    <?php $audiences = $resolveServiceAudience($service); ?>
                    <article
                        class="service-card"
                        data-service-card
                        data-service-id="<?= e((string) $service['id']) ?>"
                        data-group-id="<?= e((string) $service['group_id']) ?>"
                        data-group-name="<?= e($groupName) ?>"
                        data-service-name="<?= e($service['name']) ?>"
                        data-service-description="<?= e($service['description'] ?? '') ?>"
                        data-service-duration="<?= e((string) $service['duration']) ?>"
                        data-service-price="<?= e((string) $service['price']) ?>"
                        data-service-audience="<?= e(implode(',', $service['audience'] ?? $audiences)) ?>"
                        data-service-staff-ids="<?= e(implode(',', $service['staff_ids'] ?? [])) ?>"
                        data-service-variants="<?= e(json_encode($service['variant_details'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                        data-service-image="<?= e((string) ($service['image_data_url'] ?? '')) ?>"
                        data-service-status="<?= e((string) ($service['status'] ?? 'Aktif')) ?>"
                        data-service-online-bookable="<?= !empty($service['online_bookable']) ? '1' : '0' ?>"
                        data-service-commission-enabled="<?= !empty($service['commission_enabled']) ? '1' : '0' ?>"
                        data-service-at-customer-location="<?= !empty($service['at_customer_location']) ? '1' : '0' ?>"
                        data-service-extra-time-type="<?= e((string) ($service['extra_time_type'] ?? 'none')) ?>"
                        data-service-extra-time-minutes="<?= e((string) ($service['extra_time_minutes'] ?? 0)) ?>"
                        data-search-text="<?= e(strtolower($groupName . ' ' . $service['name'] . ' ' . ($service['description'] ?? '') . ' ' . implode(' ', $audiences))) ?>"
                    >
                        <div class="service-card__accent"></div>
                        <div class="service-card__content">
                            <div class="service-card__header">
                                <button class="services-card-thumb" type="button" data-card-image-trigger aria-label="Upload gambar <?= e($service['name']) ?>">
                                    <i class="bi bi-image"></i>
                                </button>
                                <div>
                                    <span class="service-card__group"><?= e($groupName) ?></span>
                                    <h3><?= e($service['name']) ?></h3>
                                </div>
                                <div class="services-menu-wrap">
                                    <button class="services-dots services-dots--vertical" type="button" data-services-menu-toggle>
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <div class="services-dropdown" data-services-menu>
                                        <button type="button" data-service-card-action="edit">Edit layanan</button>
                                    </div>
                                </div>
                            </div>
                            <p><?= e($service['description']) ?></p>
                            <div class="service-card__meta">
                                <span><i class="bi bi-clock"></i> <?= e((string) $service['duration']) ?> menit</span>
                            </div>
                            <div class="service-card__footer">
                                <div class="service-card__variants"><?= e(implode(' • ', $audiences)) ?></div>
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
                    <div class="services-menu-wrap services-toolbar__shop-menu">
                        <button class="dashboard-filter dashboard-filter--shop" type="button" data-services-menu-toggle>
                            <i class="bi bi-shop"></i>
                            <span data-services-shop-label="packages">Star Salon</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-shop-option="packages" data-shop-name="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <div class="services-menu-wrap services-toolbar-dropdown">
                        <button class="dashboard-filter services-toolbar-dropdown__toggle" type="button" data-services-menu-toggle>
                            <span data-services-group-label="packages">Semua Grup</span>
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-group-filter="packages" data-group-filter-id="all">Semua Grup</button>
                            <?php foreach ($groups as $group): ?>
                                <button type="button" data-services-group-filter="packages" data-group-filter-id="<?= e((string) $group['id']) ?>"><?= e($group['name']) ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="services-toolbar__group services-toolbar__group--end">
                    <div class="services-menu-wrap services-toolbar-dropdown services-toolbar-dropdown--compact">
                        <button class="dashboard-filter services-toolbar-dropdown__toggle" type="button" data-services-menu-toggle>
                            <span>Export</span>
                            <i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="services-dropdown services-dropdown--toolbar" data-services-menu>
                            <button type="button" data-services-export="packages" data-export-type="csv">Export CSV</button>
                        </div>
                    </div>
                    <label class="sales-search-field services-search" aria-label="Cari paket layanan">
                        <input type="text" placeholder="Ketik kata kunci" data-services-search="packages">
                        <i class="bi bi-search"></i>
                    </label>
                </div>
            </div>

            <div class="services-package-grid">
                <?php foreach ($packages as $package): ?>
                    <?php
                    $packageGroupIds = [];
                    $packageGroupNames = [];
                    foreach ($package['items'] as $item) {
                        $serviceItem = $servicesByName[strtolower((string) $item)] ?? null;
                        if (!$serviceItem) {
                            continue;
                        }
                        $packageGroupIds[] = (string) ($serviceItem['group_id'] ?? '');
                        foreach ($groups as $group) {
                            if ((int) $group['id'] === (int) ($serviceItem['group_id'] ?? 0)) {
                                $packageGroupNames[] = $group['name'];
                                break;
                            }
                        }
                    }
                    $packageGroupIds = array_values(array_unique(array_filter($packageGroupIds)));
                    $packageGroupNames = array_values(array_unique(array_filter($packageGroupNames)));
                    ?>
                    <article
                        class="services-package-card"
                        data-package-id="<?= e((string) $package['id']) ?>"
                        data-package-card
                        data-group-ids="<?= e(implode(',', $packageGroupIds)) ?>"
                        data-package-name="<?= e($package['name']) ?>"
                        data-package-description="<?= e($package['description'] ?? '') ?>"
                        data-package-items="<?= e(implode('||', $package['items'])) ?>"
                        data-package-items-detail="<?= e(rawurlencode(json_encode($package['items_detail'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) ?>"
                        data-package-price="<?= e((string) $package['price']) ?>"
                        data-package-pricing-mode="<?= e((string) ($package['pricing_mode'] ?? 'service')) ?>"
                        data-package-discount-value="<?= e((string) ($package['discount_value'] ?? 0)) ?>"
                        data-package-audience="<?= e((string) ($package['audience'] ?? 'all')) ?>"
                        data-search-text="<?= e(strtolower($package['name'] . ' ' . implode(' ', $package['items']) . ' ' . implode(' ', $packageGroupNames))) ?>"
                    >
                        <div class="services-package-card__head">
                            <button class="services-card-thumb" type="button" data-card-image-trigger aria-label="Upload gambar <?= e($package['name']) ?>">
                                <i class="bi bi-image"></i>
                            </button>
                            <div>
                                <h3><?= e($package['name']) ?></h3>
                                <span><?= e((string) count($package['items'])) ?> item paket</span>
                            </div>
                            <div class="services-menu-wrap">
                                <button class="services-dots services-dots--horizontal" type="button" data-services-menu-toggle>
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <div class="services-dropdown" data-services-menu>
                                    <button type="button" data-package-card-action="edit">Edit paket layanan</button>
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
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customer-tag-modal services-dialog">
            <div class="customer-tag-modal__header services-group-modal__header">
                <h2 data-service-group-modal-title>Tambah Grup Layanan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-tag-modal__body services-group-modal__body">
                <form class="services-group-form" data-service-group-form>
                    <input type="hidden" data-service-group-id>
                    <div class="services-group-form__grid">
                        <div class="services-group-field">
                            <label for="serviceGroupName">Nama</label>
                            <input id="serviceGroupName" class="form-control customer-tag-input" name="name" type="text" placeholder="">
                        </div>
                        <div class="services-group-field">
                            <label for="serviceGroupDescription">Deskripsi</label>
                            <textarea id="serviceGroupDescription" class="form-control services-group-textarea" name="description" rows="3"></textarea>
                        </div>
                        <div class="services-group-field">
                            <label>Warna Agenda</label>
                            <div class="services-group-color-grid" data-service-color-grid>
                                <?php foreach ($serviceGroupColorOptions as $index => $color): ?>
                                    <button
                                        class="services-group-color-swatch<?= $index === 0 ? ' is-active' : '' ?>"
                                        type="button"
                                        data-service-color
                                        data-color-value="<?= e($color) ?>"
                                        aria-label="Pilih warna <?= e($color) ?>"
                                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                        style="--service-color: <?= e($color) ?>;"
                                    ></button>
                                <?php endforeach; ?>
                                <button class="services-group-color-swatch services-group-color-swatch--custom" type="button" data-service-custom-color-trigger aria-label="Pilih warna lain">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <input type="color" class="services-group-color-input" data-service-custom-color value="#76b6e8">
                            <p class="services-group-help">Digunakan sebagai warna agenda pada kalender. Anda bisa menerapkan pengaturan lainnya di menu Settings / Calendar.</p>
                        </div>
                    </div>
                </form>
            </div>
            <div class="customer-tag-modal__footer services-group-modal__footer">
                <button type="button" class="customer-footer-btn customer-footer-btn--danger" data-service-group-delete hidden>Hapus</button>
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn" data-service-group-save>Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="serviceCreateModal" tabindex="-1" aria-hidden="true" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-xl services-create-dialog">
        <div class="modal-content services-create-modal">
            <div class="services-create-modal__header">
                <h2 data-service-create-title>Layanan Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="services-create-modal__tabs">
                <button class="services-create-tab is-active" type="button" data-service-create-tab="details">Details</button>
                <button class="services-create-tab" type="button" data-service-create-tab="location">Lokasi</button>
                <button class="services-create-tab" type="button" data-service-create-tab="staff">Staf</button>
                <button class="services-create-tab" type="button" data-service-create-tab="resources">Sumberdaya</button>
                <button class="services-create-tab" type="button" data-service-create-tab="settings">Settings</button>
            </div>

            <div class="services-create-modal__body">
                <section class="services-create-panel is-active" data-service-create-panel="details">
                    <div class="services-create-grid">
                        <div class="services-create-column services-create-column--left">
                            <div class="services-create-field">
                                <label for="serviceCreateName">Nama</label>
                                <input id="serviceCreateName" class="form-control" type="text">
                            </div>
                            <div class="services-create-field">
                                <label for="serviceCreateDescription">Deskripsi</label>
                                <textarea id="serviceCreateDescription" class="form-control services-create-textarea" rows="4" placeholder="Displayed on online booking"></textarea>
                            </div>
                            <div class="services-create-field">
                                <label for="serviceCreateType">Tipe Treatment</label>
                                <select id="serviceCreateType" class="form-select" data-service-create-group-select>
                                    <option>Select treatment type</option>
                                    <?php foreach ($serviceTreatmentTypes as $treatmentType): ?>
                                        <option value="<?= e($treatmentType) ?>"><?= e($treatmentType) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="services-create-field">
                                <label>Tersedia Untuk</label>
                                <div class="services-create-segmented">
                                    <button class="is-active" type="button">Semua</button>
                                    <button type="button">Pria</button>
                                    <button type="button">Wanita</button>
                                </div>
                            </div>
                            <div class="services-create-field">
                                <label>Foto (Opsional)</label>
                                <div class="services-create-media-actions">
                                    <button class="services-create-media-btn" type="button"><i class="bi bi-image"></i><span>Upload foto</span></button>
                                    <button class="services-create-media-btn" type="button"><i class="bi bi-camera-video"></i><span>Embed video</span></button>
                                </div>
                                <div class="services-create-note">
                                    <i class="bi bi-info-circle-fill"></i>
                                    <span>Untuk tampilan optimal, sematkan video portrait (vertikal) dari Instagram.</span>
                                </div>
                            </div>
                        </div>

                        <div class="services-create-column services-create-column--right" data-service-variant-list>
                            <div class="services-create-variant-section" data-service-variant-section>
                                <div class="services-create-variant-section__head" data-service-variant-section-head hidden>
                                    <div class="services-create-variant-actions__divider"></div>
                                    <div class="services-create-variant-actions__buttons">
                                        <button class="services-create-variant-actions__close" type="button" data-service-variant-remove aria-label="Hapus varian">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="services-create-row">
                                    <div class="services-create-field">
                                        <label>Durasi</label>
                                        <select class="form-select" data-service-variant-duration>
                                            <option>Select</option>
                                            <?php foreach ($serviceDurations as $duration): ?>
                                                <option><?= e($duration) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="services-create-field">
                                        <label>Nama variant</label>
                                        <input class="form-control" type="text" placeholder="e.g. Long Hair" data-service-variant-name>
                                    </div>
                                </div>
                                <div class="services-create-row">
                                    <div class="services-create-field">
                                        <label>Harga retail</label>
                                        <input class="form-control" type="text" value="Rp 0" data-service-variant-retail>
                                    </div>
                                    <div class="services-create-field">
                                        <label>Harga Spesial (Opsional)</label>
                                        <input class="form-control" type="text" value="Rp 0" data-service-variant-special>
                                    </div>
                                </div>
                                <div class="services-create-field">
                                    <label>Harga tiap lokasi</label>
                                    <button class="services-create-inline-box" type="button" data-service-location-pricing-trigger>
                                        <span data-service-location-pricing-summary>Semua lokasi memiliki harga yang sama</span>
                                        <strong>Ganti</strong>
                                    </button>
                                </div>
                                <div class="services-create-field">
                                    <label>Harga Modal (Opsional)</label>
                                    <button class="services-create-inline-box" type="button" data-service-cost-trigger>
                                        <span data-service-cost-summary>Rp 0 &bull; 0 Produk</span>
                                        <strong>Ganti</strong>
                                    </button>
                                </div>
                                <div class="services-create-field">
                                    <label>Tentukan Waktu Ketersediaan</label>
                                    <button class="services-create-inline-box" type="button" data-service-availability-trigger>
                                        <span data-service-availability-summary>Nonaktif</span>
                                        <strong>Ganti</strong>
                                    </button>
                                    <button class="services-create-variant-add" type="button" data-service-create-add-variant>
                                        <i class="bi bi-plus-lg"></i>
                                        <span>Tambah varian lain</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </section>

                <section class="services-create-panel" data-service-create-panel="location">
                    <div class="services-create-simple">
                        <p class="services-create-simple__title">Pilih lokasi dimana service ini bisa dibooking</p>
                        <div class="services-create-search">
                            <input class="form-control" type="text" placeholder="Cari lokasi">
                            <i class="bi bi-search"></i>
                        </div>
                        <div class="services-create-checklist">
                            <label class="services-create-check">
                                <span>Semua Lokasi</span>
                                <input type="checkbox" checked data-service-location-all>
                                <i class="bi bi-check-lg"></i>
                            </label>
                            <label class="services-create-check">
                                <span>Star Salon</span>
                                <input type="checkbox" checked value="1" data-service-location-check>
                                <i class="bi bi-check-lg"></i>
                            </label>
                        </div>
                    </div>
                </section>

                <section class="services-create-panel" data-service-create-panel="staff">
                    <div class="services-create-simple">
                        <p class="services-create-simple__title">Pilih Staff Yang Bisa Melayani Service Ini.</p>
                        <div class="services-create-search">
                            <input class="form-control" type="text" placeholder="Masukkan kata kunci">
                            <i class="bi bi-search"></i>
                        </div>
                        <div class="services-create-checklist">
                            <label class="services-create-check services-create-check--blue">
                                <span>Ceklis Semua</span>
                                <input type="checkbox" checked data-service-staff-all>
                                <i class="bi bi-check-lg"></i>
                            </label>
                            <?php foreach ($staffMembers as $member): ?>
                                <label class="services-create-check services-create-check--blue">
                                    <span><?= e($member['name']) ?></span>
                                    <input type="checkbox" checked value="<?= e((string) $member['id']) ?>" data-service-staff-check>
                                    <i class="bi bi-check-lg"></i>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <section class="services-create-panel" data-service-create-panel="resources">
                    <div class="services-create-resource-card">
                        <h3>Star Salon</h3>
                        <p>Belum ada sumberdaya!</p>
                    </div>
                </section>

                <section class="services-create-panel" data-service-create-panel="settings">
                    <div class="services-create-settings">
                        <div class="services-create-settings__panel">
                            <div class="services-create-field">
                                <label for="serviceExtraTimeType">Tipe Tambahan Waktu</label>
                                <select id="serviceExtraTimeType" class="form-select" data-service-settings-extra-time-type>
                                    <option value="none" selected>No extra time</option>
                                    <option value="processing_after">Processing time after</option>
                                    <option value="blocked_after">Blocked time after</option>
                                </select>
                            </div>

                            <div class="services-create-settings__duration" data-service-settings-duration hidden>
                                <input
                                    id="serviceExtraTimeDuration"
                                    class="form-control"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    placeholder=""
                                    data-service-settings-duration-input
                                >
                                <span>Menit</span>
                            </div>

                            <button class="services-create-inline-box services-create-settings__costume" type="button">
                                <span>Pengaturan Kostum</span>
                                <strong>Ganti</strong>
                            </button>

                            <div class="services-create-settings__divider"></div>

                            <label class="services-create-settings__check">
                                <input type="checkbox" checked data-service-online-bookable>
                                <span></span>
                                <strong>Bisa Dibooking Online</strong>
                            </label>
                            <label class="services-create-settings__check">
                                <input type="checkbox" data-service-commission-enabled>
                                <span></span>
                                <strong>Terapkan Komisi</strong>
                            </label>
                            <label class="services-create-settings__check">
                                <input type="checkbox" data-service-at-customer-location>
                                <span></span>
                                <strong>Izinkan Layanan Disediakan Di Lokasi Pelanggan</strong>
                            </label>
                        </div>
                    </div>
                </section>
            </div>

            <div class="services-create-drawer-layer" data-service-drawer-layer>
                <div class="services-create-drawer-backdrop" data-service-drawer-close></div>

                <aside class="services-create-drawer" data-service-location-pricing-drawer aria-hidden="true">
                    <div class="services-create-drawer__header">
                        <h3>Penetapan harga lokasi</h3>
                        <button type="button" class="btn-close" data-service-drawer-close></button>
                    </div>
                    <div class="services-create-drawer__body">
                        <label class="services-create-drawer__switch">
                            <span>Semua lokasi memiliki harga yang sama</span>
                            <input type="checkbox" checked data-service-location-shared-toggle>
                            <i class="bi bi-check-lg"></i>
                        </label>

                        <div class="services-create-drawer__search">
                            <input class="form-control" type="text" placeholder="Search" data-service-location-search>
                            <i class="bi bi-search"></i>
                        </div>

                        <div class="services-create-location-card" data-service-location-card>
                            <div class="services-create-location-card__head">
                                <strong>Star Salon</strong>
                                <button type="button" aria-label="Opsi lokasi">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                            </div>
                            <div class="services-create-location-card__prices">
                                <div class="services-create-location-card__field">
                                    <label for="serviceLocationRetailPrice">Harga retail</label>
                                    <input id="serviceLocationRetailPrice" class="form-control" type="text" value="Rp 0" data-service-location-retail-input disabled>
                                </div>
                                <div class="services-create-location-card__field">
                                    <label for="serviceLocationSpecialPrice">Harga Spesial</label>
                                    <input id="serviceLocationSpecialPrice" class="form-control" type="text" value="Rp 0" data-service-location-special-input disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="services-create-drawer__footer">
                        <button type="button" class="customer-footer-btn" data-service-drawer-close>Batal</button>
                        <button type="button" class="customer-footer-btn customer-footer-btn--ghost" data-service-location-pricing-apply>Selesai</button>
                    </div>
                </aside>

                <aside class="services-create-drawer" data-service-cost-drawer aria-hidden="true">
                    <div class="services-create-drawer__header">
                        <h3>Biaya Layanan</h3>
                        <button type="button" class="btn-close" data-service-drawer-close></button>
                    </div>
                    <div class="services-create-drawer__body">
                        <div class="services-create-drawer__field">
                            <label for="serviceCreateCostPrice">Harga Modal</label>
                            <input id="serviceCreateCostPrice" class="form-control" type="text" value="Rp 0" data-service-cost-price-input>
                        </div>

                        <div class="services-create-drawer__section">
                            <h4>Produk yang digunakan</h4>
                            <div class="services-create-drawer__search services-create-drawer__search--product">
                                <input class="form-control" type="text" placeholder="Search Product..." data-service-product-search>
                                <i class="bi bi-search"></i>
                                <div class="services-create-product-dropdown" data-service-product-dropdown hidden>
                                    <?php foreach ($productList as $product): ?>
                                        <button
                                            class="services-create-product-dropdown__item"
                                            type="button"
                                            data-service-product-option
                                            data-product-id="<?= e((string) $product['id']) ?>"
                                            data-product-name="<?= e($product['name']) ?>"
                                            data-product-price="<?= e((string) $product['price']) ?>"
                                        >
                                            <strong><?= e($product['name']) ?></strong>
                                            <span><?= e('Rp ' . number_format((int) $product['price'], 0, ',', '.')) ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="services-create-product-selected" data-service-selected-products></div>
                        </div>
                    </div>
                    <div class="services-create-drawer__footer">
                        <button type="button" class="customer-footer-btn" data-service-drawer-close>Batal</button>
                        <button type="button" class="customer-footer-btn customer-footer-btn--ghost" data-service-cost-apply>Selesai</button>
                    </div>
                </aside>

                <aside class="services-create-drawer" data-service-availability-drawer aria-hidden="true">
                    <div class="services-create-drawer__header">
                        <h3>Tentukan Waktu Ketersediaan</h3>
                        <button type="button" class="btn-close" data-service-drawer-close></button>
                    </div>
                    <div class="services-create-drawer__body">
                        <label class="services-create-drawer__toggle">
                            <span>Tersedia di waktu tertentu saja</span>
                            <input type="checkbox" data-service-availability-enabled>
                            <span class="services-create-drawer__toggle-ui"></span>
                        </label>

                        <div class="services-create-availability" data-service-availability-panel hidden>
                            <div class="services-create-availability__mode">
                                <button type="button" class="is-active" data-service-availability-mode="specific">Tanggal Spesifik</button>
                                <button type="button" data-service-availability-mode="weekly">Ulang tiap pekan</button>
                            </div>

                            <div class="services-create-availability__content is-active" data-service-availability-content="specific">
                                <div class="services-create-calendar">
                                    <div class="services-create-calendar__header">
                                        <button type="button" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></button>
                                        <strong>May 2026</strong>
                                        <button type="button" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                    <div class="services-create-calendar__weekdays">
                                        <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                                    </div>
                                    <div class="services-create-calendar__days">
                                        <?php foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31] as $day): ?>
                                            <button type="button" class="services-create-calendar__day<?= in_array($day, [13, 14, 20, 21, 25, 29], true) ? ' is-selected' : '' ?>" data-service-date-option data-date-value="2026-05-<?= e(str_pad((string) $day, 2, '0', STR_PAD_LEFT)) ?>">
                                                <?= e((string) $day) ?>
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="services-create-availability__selected" data-service-selected-dates></div>
                            </div>

                            <div class="services-create-availability__content" data-service-availability-content="weekly">
                                <div class="services-create-weekdays">
                                    <?php foreach (['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $index => $dayName): ?>
                                        <label class="services-create-weekday">
                                            <input type="checkbox" data-service-weekday-option value="<?= e((string) $index) ?>">
                                            <span></span>
                                            <strong><?= e($dayName) ?></strong>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="services-create-drawer__footer">
                        <button type="button" class="customer-footer-btn" data-service-drawer-close>Batal</button>
                        <button type="button" class="customer-footer-btn customer-footer-btn--ghost" data-service-availability-apply>Selesai</button>
                    </div>
                </aside>
            </div>

            <div class="services-create-modal__footer">
                <button type="button" class="customer-footer-btn customer-footer-btn--danger" data-service-create-delete hidden>Hapus</button>
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn" data-service-create-save>Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="servicePackageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl services-package-dialog">
        <div class="modal-content customer-tag-modal services-dialog services-package-modal">
            <div class="customer-tag-modal__header services-group-modal__header">
                <h2 data-service-package-title>Tambah Paket Layanan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="customer-tag-modal__body services-group-modal__body">
                <form class="services-group-form" data-service-package-form>
                    <input type="hidden" data-service-package-id>
                    <input type="hidden" data-service-package-pricing-mode-input value="service">
                    <input type="hidden" data-service-package-discount-value value="0">
                    <input type="hidden" data-service-package-audience value="all">
                    <div class="services-package-builder">
                        <div class="services-package-builder__form">
                            <div class="services-group-field" hidden>
                                <label for="servicePackageGroup">Grup Layanan</label>
                                <select id="servicePackageGroup" class="form-select" data-service-package-group>
                                    <option value="">Pilih grup layanan</option>
                                    <?php foreach ($groups as $group): ?>
                                        <option value="<?= e((string) $group['id']) ?>"><?= e($group['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="services-group-field">
                                <label for="servicePackageName">Nama Paket</label>
                                <input id="servicePackageName" class="form-control customer-tag-input" type="text" data-service-package-name>
                            </div>
                            <div class="services-group-field">
                                <label for="servicePackageDescription">Deskripsi</label>
                                <textarea id="servicePackageDescription" class="form-control services-group-textarea" rows="3" data-service-package-description></textarea>
                            </div>
                            <div class="services-group-field">
                                <label>Harga berdasarkan</label>
                                <div class="services-create-segmented services-package-segmented" data-service-package-pricing-group>
                                    <button class="is-active" type="button" data-service-package-pricing-mode="service">Layanan</button>
                                    <button type="button" data-service-package-pricing-mode="custom">Custom</button>
                                    <button type="button" data-service-package-pricing-mode="discount">Diskon</button>
                                </div>
                            </div>
                            <div class="services-group-field">
                                <label for="servicePackagePrice">Nilai Harga</label>
                                <div class="services-package-price-input">
                                    <span data-service-package-price-prefix>Rp</span>
                                    <input id="servicePackagePrice" class="form-control" type="text" value="0" data-service-package-price>
                                    <span data-service-package-price-suffix hidden>%</span>
                                </div>
                                <p class="services-group-help services-group-help--compact" data-service-package-price-help>Harga dihitung otomatis dari item layanan yang dipilih.</p>
                            </div>
                            <div class="services-package-divider"></div>
                            <div class="services-group-field">
                                <label>Tersedia Untuk</label>
                                <div class="services-create-segmented services-package-segmented" data-service-package-audience-group>
                                    <button class="is-active" type="button" data-service-package-audience-option="all">Semua</button>
                                    <button type="button" data-service-package-audience-option="men">Pria</button>
                                    <button type="button" data-service-package-audience-option="women">Wanita</button>
                                </div>
                            </div>
                            <div class="services-group-field" hidden>
                                <label for="servicePackageItems">Isi Layanan</label>
                                <textarea id="servicePackageItems" class="form-control services-group-textarea" rows="4" placeholder="Satu layanan per baris" data-service-package-items></textarea>
                            </div>
                        </div>
                        <div class="services-package-builder__summary">
                            <button class="services-package-builder__add" type="button" data-service-package-open-picker>
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambahkan Layanan</span>
                            </button>
                            <div class="services-package-builder__selected" data-service-package-selected-list></div>
                            <div class="services-package-builder__empty" data-service-package-empty>
                                <div class="services-package-builder__empty-icon">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                                <h3>Paket kosong</h3>
                                <p>Anda belum menambahkan layanan apa pun ke paket ini.</p>
                            </div>
                            <div class="services-package-builder__totals" hidden data-service-package-totals-card>
                                <div class="services-package-builder__totals-icon">
                                    <span></span>
                                    <span></span>
                                </div>
                                <div class="services-package-builder__totals-group">
                                    <small>Durasi</small>
                                    <strong data-service-package-total-duration>00:00</strong>
                                </div>
                                <div class="services-package-builder__totals-group services-package-builder__totals-group--price">
                                    <small>Harga</small>
                                    <div class="services-package-builder__totals-price" data-service-package-total-price>
                                        <strong>Rp 0</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="services-package-builder__summary-bar" data-service-package-selection-summary>0 item &bull; Rp 0</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="customer-tag-modal__footer services-group-modal__footer">
                <button type="button" class="customer-footer-btn customer-footer-btn--danger" data-service-package-delete hidden>Hapus</button>
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn" data-service-package-save>Simpan</button>
            </div>
            <div class="services-package-item-editor" hidden data-service-package-item-editor>
                <div class="services-package-item-editor__backdrop" data-service-package-item-editor-close></div>
                <div class="services-package-item-editor__dialog">
                    <div class="services-package-item-editor__content">
                        <h3>Edit Paket Layanan</h3>
                        <div class="services-package-item-editor__preview">
                            <div class="services-package-item-editor__avatar"></div>
                            <div class="services-package-item-editor__preview-body">
                                <strong data-service-package-item-editor-title>Item</strong>
                                <span data-service-package-item-editor-meta>0</span>
                            </div>
                            <div class="services-package-item-editor__preview-price" data-service-package-item-editor-price>Rp 0</div>
                        </div>
                        <p class="services-package-item-editor__help">
                            Tetapkan waktu ekstra untuk mengikuti layanan ini, misalnya waktu pemrosesan untuk perawatan spa, atau waktu yang diblokir untuk pembersihan setelahnya.
                        </p>
                        <div class="services-package-item-editor__select">
                            <button class="services-package-item-editor__select-trigger" type="button" data-service-package-item-editor-type-toggle aria-expanded="false">
                                <span data-service-package-item-editor-type-label>Tidak ada tambahan waktu</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="services-package-item-editor__select-menu" hidden data-service-package-item-editor-type-menu>
                                <button type="button" class="is-active" data-service-package-item-editor-type="none">Tidak ada tambahan waktu</button>
                                <button type="button" data-service-package-item-editor-type="processing_after">Pemrosesan waktu setelahnya</button>
                                <button type="button" data-service-package-item-editor-type="blocked_after">Blokir waktu setelahnya</button>
                            </div>
                        </div>
                        <div class="services-package-item-editor__qty" hidden data-service-package-item-editor-qty>
                            <button type="button" data-service-package-item-editor-qty-change="-1">-</button>
                            <strong data-service-package-item-editor-qty-value>1</strong>
                            <button type="button" data-service-package-item-editor-qty-change="1">+</button>
                        </div>
                        <div class="services-package-item-editor__time" hidden data-service-package-item-editor-time>
                            <button type="button" data-service-package-item-editor-time-change="-15">-</button>
                            <strong data-service-package-item-editor-time-value>15m</strong>
                            <button type="button" data-service-package-item-editor-time-change="15">+</button>
                        </div>
                    </div>
                    <div class="services-package-item-editor__footer">
                        <button class="customer-footer-btn" type="button" data-service-package-item-editor-close>Batal</button>
                        <button class="customer-footer-btn staff-save-btn" type="button" data-service-package-item-editor-apply>Selesai</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="servicePackageItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl services-package-picker-dialog">
        <div class="modal-content services-dialog services-package-picker">
            <div class="services-package-picker__header">
                <div class="services-package-picker__search">
                    <button class="services-package-picker__back" type="button" data-bs-dismiss="modal" aria-label="Kembali">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <input type="search" placeholder="Cari untuk menambahkan item" autocomplete="off" data-service-package-item-search>
                    <i class="bi bi-search"></i>
                </div>
            </div>
            <div class="services-package-picker__body">
                <div class="services-package-picker__tabs" data-service-package-item-tabs>
                    <button class="is-active" type="button" data-service-package-catalog="services">Services</button>
                    <button type="button" data-service-package-catalog="products">Products</button>
                </div>
                <div class="services-package-picker__filters" data-service-package-group-filters>
                    <button class="is-active" type="button" data-service-package-group-filter="all">Semua Grup</button>
                    <?php foreach ($groups as $group): ?>
                        <button type="button" data-service-package-group-filter="<?= e((string) $group['id']) ?>"><?= e($group['name']) ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="services-package-picker__grid" data-service-package-item-grid>
                    <?php foreach ($services as $service): ?>
                        <?php
                        $serviceGroupName = '';
                        foreach ($groups as $group) {
                            if ((int) $group['id'] === (int) $service['group_id']) {
                                $serviceGroupName = $group['name'];
                                break;
                            }
                        }
                        $serviceInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) $service['name']), 0, 2) ?: 'SV');
                        ?>
                        <button
                            class="services-package-picker-card"
                            type="button"
                            data-package-item-option
                            data-item-key="service:<?= e((string) $service['id']) ?>"
                            data-item-type="service"
                            data-item-id="<?= e((string) $service['id']) ?>"
                            data-item-name="<?= e($service['name']) ?>"
                            data-item-price="<?= e((string) $service['price']) ?>"
                            data-item-duration="<?= e((string) $service['duration']) ?>"
                            data-item-group-id="<?= e((string) $service['group_id']) ?>"
                            data-item-group-name="<?= e($serviceGroupName) ?>"
                            data-item-catalog="services"
                            data-item-search="<?= e(strtolower($serviceGroupName . ' ' . $service['name'] . ' ' . ($service['description'] ?? ''))) ?>"
                        >
                            <span class="services-package-picker-card__thumb"><?= e($serviceInitials) ?></span>
                            <span class="services-package-picker-card__body">
                                <strong><?= e($service['name']) ?></strong>
                                <span class="services-package-picker-card__meta">
                                    <span><?= e((string) $service['duration']) ?> menit</span>
                                    <span>&bull;</span>
                                    <span><?= money($service['price']) ?></span>
                                </span>
                            </span>
                            <i class="bi bi-check-lg"></i>
                        </button>
                    <?php endforeach; ?>
                    <?php foreach ($productList as $product): ?>
                        <?php
                        $productName = (string) ($product['name'] ?? 'Produk');
                        $productPrice = (int) ($product['price'] ?? $product['selling_price'] ?? $product['retail_price'] ?? 0);
                        $productMeta = trim((string) ($product['brand'] ?? $product['category'] ?? ''));
                        $productInitials = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $productName), 0, 2) ?: 'PR');
                        ?>
                        <button
                            class="services-package-picker-card"
                            type="button"
                            hidden
                            data-package-item-option
                            data-item-key="product:<?= e((string) ($product['id'] ?? $productName)) ?>"
                            data-item-type="product"
                            data-item-id="<?= e((string) ($product['id'] ?? $productName)) ?>"
                            data-item-name="<?= e($productName) ?>"
                            data-item-price="<?= e((string) $productPrice) ?>"
                            data-item-duration=""
                            data-item-brand="<?= e((string) ($product['brand'] ?? '')) ?>"
                            data-item-stock="<?= e((string) ($product['stock'] ?? 0)) ?>"
                            data-item-group-id=""
                            data-item-group-name=""
                            data-item-catalog="products"
                            data-item-search="<?= e(strtolower($productName . ' ' . $productMeta)) ?>"
                        >
                            <span class="services-package-picker-card__thumb services-package-picker-card__thumb--product"><?= e($productInitials) ?></span>
                            <span class="services-package-picker-card__body">
                                <strong><?= e($productName) ?></strong>
                                <span class="services-package-picker-card__meta">
                                    <span><?= e($productMeta !== '' ? $productMeta : 'Produk') ?></span>
                                    <span>&bull;</span>
                                    <span><?= money($productPrice) ?></span>
                                </span>
                            </span>
                            <i class="bi bi-check-lg"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="services-package-picker__empty" hidden data-service-package-item-empty>Tidak ada item yang cocok dengan pencarian.</div>
                <div class="services-package-picker__selected" data-service-package-picker-selected hidden>
                    <div class="services-package-picker__selected-list" data-service-package-picker-selected-list></div>
                    <div class="services-package-picker__selected-summary">
                        <div class="services-package-picker__selected-total" data-service-package-item-summary>0 items &bull; Rp 0</div>
                        <button class="services-package-picker__selected-toggle" type="button" data-service-package-picker-collapse aria-label="Tutup ringkasan">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="services-package-picker__footer">
                <div class="services-package-picker__footer-summary" data-service-package-item-footer-summary>0 item &bull; Rp 0</div>
                <button class="customer-footer-btn staff-save-btn" type="button" data-service-package-item-apply>Tambahkan 0 item</button>
            </div>
            <div class="services-package-picker-choice" hidden data-service-package-choice>
                <div class="services-package-picker-choice__backdrop" data-service-package-choice-close></div>
                <div class="services-package-picker-choice__dialog">
                    <div class="services-package-picker-choice__content">
                        <h3 data-service-package-choice-title>Item</h3>
                        <p data-service-package-choice-tagline hidden></p>
                        <p data-service-package-choice-subtitle>1 pilihan</p>
                        <button class="services-package-picker-choice__option" type="button" data-service-package-choice-option>
                            <span class="services-package-picker-choice__option-text">
                                <strong data-service-package-choice-option-title>Varian</strong>
                                <small data-service-package-choice-option-meta>Rp 0</small>
                            </span>
                            <span class="services-package-picker-choice__radio"></span>
                        </button>
                        <div class="services-package-picker-choice__qty" hidden data-service-package-choice-qty>
                            <button type="button" data-service-package-choice-decrease>-</button>
                            <strong data-service-package-choice-qty-value>1</strong>
                            <button type="button" data-service-package-choice-increase>+</button>
                        </div>
                    </div>
                    <div class="services-package-picker-choice__footer">
                        <button class="customer-footer-btn" type="button" data-service-package-choice-close>Batal</button>
                        <button class="customer-footer-btn staff-save-btn" type="button" data-service-package-choice-apply>Tambahkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="file" accept="image/*" hidden data-services-card-image-input>

<div class="modal fade" id="serviceGroupPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content services-dialog services-picker-dialog">
            <div class="customer-tag-modal__header">
                <h2 class="js-service-picker-title">Pilih Grup Layanan</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="services-picker-body">
                <?php foreach ($groups as $group): ?>
                    <button class="services-picker-option" type="button" data-service-picker-option data-group-id="<?= e((string) $group['id']) ?>" data-group-name="<?= e($group['name']) ?>" aria-pressed="false">
                        <strong><?= e($group['name']) ?></strong>
                        <span><?= e((string) count($servicesByGroup[$group['id']] ?? [])) ?> layanan aktif</span>
                        <i class="bi bi-chevron-right"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<template id="serviceVariantTemplate">
    <div class="services-create-variant-section" data-service-variant-section>
        <div class="services-create-variant-section__head" data-service-variant-section-head>
            <div class="services-create-variant-actions__divider"></div>
            <div class="services-create-variant-actions__buttons">
                <button class="services-create-variant-actions__close" type="button" data-service-variant-remove aria-label="Hapus varian">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="services-create-row">
            <div class="services-create-field">
                <label>Durasi</label>
                <select class="form-select" data-service-variant-duration>
                    <option>Select</option>
                    <?php foreach ($serviceDurations as $duration): ?>
                        <option><?= e($duration) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="services-create-field">
                <label>Nama variant</label>
                <input class="form-control" type="text" placeholder="e.g. Long Hair" data-service-variant-name>
            </div>
        </div>
        <div class="services-create-row">
            <div class="services-create-field">
                <label>Harga retail</label>
                <input class="form-control" type="text" value="Rp 0" data-service-variant-retail>
            </div>
            <div class="services-create-field">
                <label>Harga Spesial (Opsional)</label>
                <input class="form-control" type="text" value="Rp 0" data-service-variant-special>
            </div>
        </div>
        <div class="services-create-field">
            <label>Harga tiap lokasi</label>
            <button class="services-create-inline-box" type="button" data-service-location-pricing-trigger>
                <span data-service-location-pricing-summary>Semua lokasi memiliki harga yang sama</span>
                <strong>Ganti</strong>
            </button>
        </div>
        <div class="services-create-field">
            <label>Harga Modal (Opsional)</label>
            <button class="services-create-inline-box" type="button" data-service-cost-trigger>
                <span data-service-cost-summary>Rp 0 &bull; 0 Produk</span>
                <strong>Ganti</strong>
            </button>
        </div>
        <div class="services-create-field">
            <label>Tentukan Waktu Ketersediaan</label>
            <button class="services-create-inline-box" type="button" data-service-availability-trigger>
                <span data-service-availability-summary>Nonaktif</span>
                <strong>Ganti</strong>
            </button>
            <button class="services-create-variant-add" type="button" data-service-create-add-variant>
                <i class="bi bi-plus-lg"></i>
                <span>Tambah varian lain</span>
            </button>
        </div>
    </div>
</template>

