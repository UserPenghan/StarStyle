<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config('name')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body class="app-shell">
<div class="dashboard-shell">
    <aside class="sidebar-panel">
        <div class="sidebar-panel__header">
            <div class="brand-profile dropdown">
                <button class="brand-card" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="avatar-badge"><i class="bi bi-person"></i></div>
                    <div class="brand-card__text">
                        <div class="brand-card__name"><?= e($currentUser['name'] ?? 'Guest') ?></div>
                        <div class="brand-card__sub">Star Salon</div>
                    </div>
                    <i class="bi bi-caret-down-fill brand-card__chevron"></i>
                </button>
                <div class="dropdown-menu brand-profile-menu">
                    <a class="brand-profile-menu__item" href="<?= e(url('/dashboard')) ?>">Beranda</a>
                    <a class="brand-profile-menu__item" href="<?= e(url('/account')) ?>">Akun Saya</a>
                    <div class="brand-profile-menu__section">
                        <span>Pindah Lokasi</span>
                        <strong>Star Salon</strong>
                    </div>
                    <div class="brand-profile-menu__section">
                        <span>Ubah bahasa ke:</span>
                        <strong>ENGLISH</strong>
                    </div>
                    <form method="post" action="<?= e(url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="brand-profile-menu__logout" type="submit">Log Out</button>
                    </form>
                </div>
            </div>
        </div>

        <nav class="side-navigation">
            <?php foreach (($sidebarModules ?? []) as $module): ?>
                <a class="side-navigation__link <?= active_path($module['path'], $page ?? '') ? 'is-active' : '' ?>" href="<?= e(url($module['path'])) ?>">
                    <i class="bi bi-<?= e($module['icon']) ?>"></i>
                    <span><?= e($module['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

    </aside>

    <main class="main-panel">
        <header class="topbar">
            <div class="topbar__left">
                <div class="topbar__title"><?= e($pageTitle ?? $title ?? 'Dashboard') ?></div>
            </div>
            <div class="topbar__actions">
                <div class="search-pill">
                    <i class="bi bi-search"></i>
                    <span>Cari layanan...</span>
                </div>
                <button class="topbar-icon" type="button" aria-label="Quick action"><i class="bi bi-lightning-charge"></i></button>
                <button class="topbar-icon" type="button" aria-label="Tambah"><i class="bi bi-plus-lg"></i></button>
                <button class="topbar-icon" type="button" aria-label="Notifikasi"><i class="bi bi-bell"></i></button>
                <button class="topbar-icon" type="button" aria-label="Profile"><i class="bi bi-person-circle"></i></button>
            </div>
        </header>

        <section class="content-panel">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success border-0 rounded-4"><?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger border-0 rounded-4"><?= e($error) ?></div>
            <?php endif; ?>
            <?= $content ?>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
