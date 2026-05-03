<?php
$rangeText = '';
foreach ($presets as $value => $label) {
    if ($range === $value) {
        $rangeText = $label . ', ' . ($data['chart']['labels'][0] ?? '') . ' - ' . (end($data['chart']['labels']) ?: '');
        break;
    }
}

$agendaLabels = $data['chart']['labels'];
$agendaConfirmed = array_fill(0, count($agendaLabels), 0);
$agendaCancelled = array_fill(0, count($agendaLabels), 0);
$bookingsForAgenda = [];

foreach (array_merge($data['recent'], $data['upcoming']) as $booking) {
    $bookingsForAgenda[$booking['reference']] = $booking;
}

foreach ($agendaLabels as $index => $label) {
    foreach ($bookingsForAgenda as $booking) {
        if (substr($booking['start_at'], 0, 10) !== $label) {
            continue;
        }

        if ($booking['status'] === 'confirmed') {
            $agendaConfirmed[$index]++;
        }

        if ($booking['status'] === 'cancelled') {
            $agendaCancelled[$index]++;
        }
    }
}

$agendaTotal = count($data['upcoming']);
$agendaConfirmedTotal = array_sum($agendaConfirmed);
$agendaCancelledTotal = array_sum($agendaCancelled);
?>

<section class="dashboard-stack">
    <div class="dashboard-section-card">
        <div class="dashboard-section-card__header">
            <h2 class="dashboard-section-card__title">Penjualan Terakhir</h2>
            <div class="dashboard-match__filters">
                <button class="dashboard-filter" type="button">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <form method="get" action="<?= e(url('/dashboard')) ?>">
                    <label class="dashboard-filter dashboard-filter--wide">
                        <i class="bi bi-calendar3"></i>
                        <span><?= e($rangeText) ?></span>
                        <select class="dashboard-filter__select" name="range" onchange="this.form.submit()">
                            <?php foreach ($presets as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $range === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </label>
                </form>
            </div>
        </div>

        <div class="dashboard-match__metrics">
            <div class="dashboard-inline-metric">
                <span>Total Penjualan</span>
                <strong><?= money($data['cards']['sales_total']) ?></strong>
            </div>
            <div class="dashboard-inline-metric">
                <span>Nilai Agenda</span>
                <strong><?= money($data['cards']['agenda_value']) ?></strong>
            </div>
        </div>

        <div class="dashboard-chart-wrap">
            <div class="dashboard-chart-frame">
                <canvas class="js-chart js-dashboard-reference-chart" height="150" data-chart-type="line" data-chart='<?= e(json_encode(["labels" => $data["chart"]["labels"], "datasets" => [["label" => "Agenda", "data" => $data["chart"]["agenda"], "borderColor" => "#69aefe", "backgroundColor" => "transparent", "pointRadius" => 4, "pointHoverRadius" => 4, "pointBackgroundColor" => "#ffffff", "pointBorderColor" => "#69aefe", "pointBorderWidth" => 2, "tension" => 0, "fill" => false], ["label" => "Penjualan", "data" => $data["chart"]["sales"], "borderColor" => "#65d0ac", "backgroundColor" => "transparent", "pointRadius" => 4, "pointHoverRadius" => 4, "pointBackgroundColor" => "#ffffff", "pointBorderColor" => "#65d0ac", "pointBorderWidth" => 2, "tension" => 0, "fill" => false]]], JSON_THROW_ON_ERROR)) ?>'></canvas>
            </div>
            <div class="dashboard-scrollbar-fake">
                <span></span>
            </div>
        </div>
    </div>

    <div class="dashboard-section-card">
        <div class="dashboard-section-card__header">
            <h2 class="dashboard-section-card__title">Agenda Yang Akan Datang</h2>
            <div class="dashboard-match__filters">
                <button class="dashboard-filter" type="button">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <form method="get" action="<?= e(url('/dashboard')) ?>">
                    <label class="dashboard-filter dashboard-filter--wide">
                        <i class="bi bi-calendar3"></i>
                        <span><?= e($rangeText) ?></span>
                        <select class="dashboard-filter__select" name="range" onchange="this.form.submit()">
                            <?php foreach ($presets as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $range === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </label>
                </form>
            </div>
        </div>

        <div class="dashboard-agenda-meta">
            <div class="dashboard-agenda-meta__count"><?= e((string) $agendaTotal) ?> Agenda</div>
            <div class="dashboard-agenda-meta__sub"><?= e((string) $agendaConfirmedTotal) ?> Dikonfirmasi <?= e((string) $agendaCancelledTotal) ?> Dibatalkan</div>
        </div>

        <div class="dashboard-chart-wrap">
            <div class="dashboard-chart-frame">
                <canvas class="js-chart js-dashboard-reference-chart" height="150" data-chart-type="line" data-chart='<?= e(json_encode(["labels" => $agendaLabels, "datasets" => [["label" => "confirmed", "data" => $agendaConfirmed, "borderColor" => "#65d0ac", "backgroundColor" => "transparent", "pointRadius" => 0, "pointHoverRadius" => 0, "tension" => 0, "fill" => false], ["label" => "cancelled", "data" => $agendaCancelled, "borderColor" => "#dc4860", "backgroundColor" => "transparent", "pointRadius" => 0, "pointHoverRadius" => 0, "tension" => 0, "fill" => false]]], JSON_THROW_ON_ERROR)) ?>'></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-section-card">
        <div class="dashboard-section-card__header">
            <h2 class="dashboard-section-card__title">Aktifitas agenda</h2>
            <div class="dashboard-match__filters">
                <button class="dashboard-filter" type="button">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <form method="get" action="<?= e(url('/dashboard')) ?>">
                    <label class="dashboard-filter dashboard-filter--wide">
                        <i class="bi bi-calendar3"></i>
                        <span><?= e($rangeText) ?></span>
                        <select class="dashboard-filter__select" name="range" onchange="this.form.submit()">
                            <?php foreach ($presets as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $range === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </label>
                </form>
            </div>
        </div>

        <div class="dashboard-empty-panel">
            <div class="dashboard-empty-panel__icon"></div>
            <div class="dashboard-empty-panel__text">Tidak Ada Agenda</div>
        </div>
    </div>

    <div class="dashboard-section-card">
        <div class="dashboard-section-card__header">
            <h2 class="dashboard-section-card__title">5 Teratas</h2>
            <div class="dashboard-match__filters">
                <button class="dashboard-filter" type="button">
                    <i class="bi bi-shop"></i>
                    <span>Star Salon</span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <form method="get" action="<?= e(url('/dashboard')) ?>">
                    <label class="dashboard-filter dashboard-filter--wide">
                        <i class="bi bi-calendar3"></i>
                        <span><?= e($rangeText) ?></span>
                        <select class="dashboard-filter__select" name="range" onchange="this.form.submit()">
                            <?php foreach ($presets as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $range === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </label>
                </form>
            </div>
        </div>

        <div class="dashboard-top-grid">
            <div class="dashboard-top-card">
                <div class="dashboard-top-card__title">Layanan</div>
                <div class="dashboard-empty-panel dashboard-empty-panel--small">
                    <div class="dashboard-empty-panel__icon"></div>
                    <div class="dashboard-empty-panel__text">Tidak ada layanan</div>
                </div>
            </div>
            <div class="dashboard-top-card">
                <div class="dashboard-top-card__title">Produk</div>
                <div class="dashboard-empty-panel dashboard-empty-panel--small">
                    <div class="dashboard-empty-panel__icon"></div>
                    <div class="dashboard-empty-panel__text">Tidak ada produk</div>
                </div>
            </div>
            <div class="dashboard-top-card">
                <div class="dashboard-top-card__title">Kelas</div>
                <div class="dashboard-empty-panel dashboard-empty-panel--small">
                    <div class="dashboard-empty-panel__icon"></div>
                    <div class="dashboard-empty-panel__text">Tidak Ada Kelas</div>
                </div>
            </div>
            <div class="dashboard-top-card">
                <div class="dashboard-top-card__title">Staf</div>
                <div class="dashboard-empty-panel dashboard-empty-panel--small">
                    <div class="dashboard-empty-panel__icon"></div>
                    <div class="dashboard-empty-panel__text">Tidak ada staff</div>
                </div>
            </div>
        </div>
    </div>
</section>
