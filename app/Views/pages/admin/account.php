<?php
$accountUser = $accountUser ?? $currentUser ?? [];
$accountStaff = $accountStaff ?? null;
$displayName = $accountStaff['name'] ?? $accountUser['name'] ?? 'Rayhan Doni Pramana';
$displayRole = $accountStaff['role'] ?? ucfirst((string) ($accountUser['role'] ?? 'Owner'));
$displayEmail = $accountStaff['email'] ?? $accountUser['email'] ?? 'rayhandonipramana01@gmail.com';
$displayPhone = $accountStaff['phone'] ?? '+6281221000156';
$joinedYear = '2026';
?>

<section class="account-shell">
    <div class="account-profile-card">
        <div class="account-profile-card__left">
            <div class="account-avatar">
                <i class="bi bi-person"></i>
            </div>
            <div class="account-badges">
                <span><i class="bi bi-person-plus"></i> <?= e($joinedYear) ?></span>
                <span><i class="bi bi-person"></i> <?= e($displayRole) ?></span>
            </div>
            <button class="account-action" type="button"><i class="bi bi-image"></i> Pilih Foto</button>
            <button class="account-action" type="button"><i class="bi bi-key"></i> Ganti Password</button>
        </div>

        <div class="account-profile-card__right">
            <h2>Data Pribadi</h2>
            <p>Informasi yang hanya Anda yang mengetahui. Hanya untuk sign in</p>

            <label>Nama</label>
            <div class="account-field"><?= e($displayName) ?></div>

            <label>Email</label>
            <div class="account-field account-field--with-link">
                <span><?= e($displayEmail) ?></span>
                <a href="#">Verifikasi</a>
            </div>

            <label>Nomor Ponsel</label>
            <div class="account-phone-row">
                <div class="account-phone-code">+62</div>
                <div class="account-field account-field--phone"><?= e(ltrim(str_replace([' ', '-', '+62'], '', $displayPhone), '0')) ?></div>
                <button type="button">Ganti</button>
            </div>
        </div>
    </div>

    <div class="account-card account-verify-card">
        <div class="account-verify-card__icon">
            <i class="bi bi-person-vcard"></i>
            <span><i class="bi bi-check-lg"></i></span>
        </div>
        <div>
            <h2>Yuk, Verifikasi Akun Anda!</h2>
            <p>Verifikasi identitas Anda hanya dalam beberapa langkah untuk menikmati semua fitur platform kami. Prosesnya cepat, aman, dan hanya memerlukan waktu satu menit!</p>
        </div>
        <button type="button">Lengkapi Profil</button>
    </div>

    <div class="account-card">
        <h2>Opsi sign-in</h2>
        <p>Gunakan Facebook untuk sign in</p>
        <button class="account-social" type="button"><i class="bi bi-facebook"></i> Gunakan Facebook</button>
    </div>

    <div class="account-card">
        <h2>Keamanan</h2>
        <p>Lindungi akun anda dari akses tanpa izin</p>

        <div class="account-security-row">
            <i class="bi bi-envelope"></i>
            <div>
                <strong>Aktifkan Kode OTP Email</strong>
                <span>Anda wajib memasukan Kode OTP yang dikirim ke email Anda saat login</span>
            </div>
            <label class="account-switch">
                <input type="checkbox">
                <span></span>
            </label>
        </div>

        <div class="account-security-row">
            <i class="bi bi-asterisk"></i>
            <div>
                <strong>Aktifkan Google Authenticator</strong>
                <span>Anda wajib memasukan kode Authentikasi dari aplikasi Google Authenticator saat login</span>
            </div>
            <label class="account-switch">
                <input type="checkbox">
                <span></span>
            </label>
        </div>
    </div>
</section>
