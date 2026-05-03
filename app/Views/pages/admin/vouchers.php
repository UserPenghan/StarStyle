<?php
$voucherTypeFilters = [
    ['key' => 'all', 'label' => 'Semua'],
    ['key' => 'gift', 'label' => 'G'],
    ['key' => 'service', 'label' => 'S'],
    ['key' => 'class', 'label' => 'C'],
];
?>

<section class="vouchers-shell js-vouchers-shell">
    <div class="vouchers-toolbar">
        <div class="vouchers-toolbar__group">
            <button class="dashboard-filter dashboard-filter--shop" type="button">
                <i class="bi bi-shop"></i>
                <span>Star Salon</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="vouchers-filter-chips">
                <?php foreach ($voucherTypeFilters as $index => $filter): ?>
                    <button class="vouchers-filter-chip <?= $index === 0 ? 'is-active' : '' ?>" type="button"><?= e($filter['label']) ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="vouchers-toolbar__group vouchers-toolbar__group--end">
            <div class="sales-search-field vouchers-search"><span>Ketik kata kunci</span><i class="bi bi-search"></i></div>
        </div>
    </div>

    <div class="vouchers-empty-card">
        <div class="vouchers-empty-card__icon">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <h2>Tidak Ada Voucher</h2>
        <p>Tambahkan voucher untuk pelanggan yang akan dibeli atau untuk memberikan promosi</p>
        <button class="vouchers-empty-action" type="button" data-bs-toggle="modal" data-bs-target="#voucherActionModal">Tambahkan voucher</button>
    </div>

    <div class="services-fab-wrapper">
        <button class="customers-fab js-vouchers-fab" type="button" aria-label="Tambah voucher" data-bs-toggle="modal" data-bs-target="#voucherActionModal">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<div class="modal fade" id="voucherActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content vouchers-action-modal">
            <button class="vouchers-action-row" type="button" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#voucherServiceModal">
                <i class="bi bi-scissors"></i>
                <span>Voucher Layanan</span>
            </button>
            <button class="vouchers-action-row" type="button" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#voucherClassModal">
                <i class="bi bi-person-arms-up"></i>
                <span>Voucher Kelas</span>
            </button>
            <button class="vouchers-action-row" type="button" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#voucherGiftModal">
                <i class="bi bi-gift"></i>
                <span>Voucher Hadiah</span>
            </button>
            <button class="vouchers-action-close" type="button" data-bs-dismiss="modal">
                <i class="bi bi-x-lg"></i>
                <span>Tutup</span>
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal vouchers-editor">
            <div class="customer-modal__header">
                <div></div>
                <h2>Voucher Layanan Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="vouchers-editor-tabs">
                <button class="vouchers-editor-tab is-active" type="button">Rincian</button>
                <button class="vouchers-editor-tab" type="button">Komisi</button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Display Name</label>
                                <input class="form-control customer-input-flat" type="text">

                                <label>Layanan</label>
                                <button class="customer-picker" type="button"><span><?= e($services[0]['name'] ?? 'No item') ?></span><strong>Ganti</strong></button>

                                <label class="customer-toggle-row">
                                    <span>Kombinasikan kuantitas</span>
                                    <span class="sales-switch__track"></span>
                                </label>

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input" type="text" value="Rp 0,00">
                                <small>Jika 0, Anda harus secara manual menentukan harganya saat checkout</small>
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Tanggal Kadaluarsa</label>
                                <div class="customer-segmented customer-segmented--two">
                                    <button class="is-active" type="button">Setelah</button>
                                    <button type="button">Tanggal Spesifik</button>
                                </div>
                                <button class="customer-picker" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>

                                <label>Dapat digunakan di</label>
                                <button class="customer-picker" type="button"><span>Semua Lokasi</span><strong>Pilih</strong></button>

                                <label>Pesan</label>
                                <textarea class="form-control customer-input-flat" rows="4" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <span class="vouchers-preview-switch"></span>
                            </div>
                            <div class="vouchers-preview-card vouchers-preview-card--service">
                                <div class="vouchers-preview-left">
                                    <div class="vouchers-preview-brand"><i class="bi bi-record-circle"></i><span>Star Salon</span></div>
                                    <div class="vouchers-preview-qr"></div>
                                    <p>Digenenerate setelah penjualan</p>
                                    <span>Valid untuk 1 Bulan</span>
                                </div>
                                <div class="vouchers-preview-right">
                                    <strong><?= e($services[0]['name'] ?? 'Voucher Layanan') ?></strong>
                                    <span><i class="bi bi-geo-alt"></i> Dapat digunakan di Semua Lokasi</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--disabled">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal vouchers-editor">
            <div class="customer-modal__header">
                <div></div>
                <h2>Voucher Kelas Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="vouchers-editor-tabs">
                <button class="vouchers-editor-tab is-active" type="button">Rincian</button>
                <button class="vouchers-editor-tab" type="button">Pengaturan</button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Nama voucher</label>
                                <input class="form-control customer-input-flat" type="text" placeholder="Please input">

                                <label>Class</label>
                                <button class="customer-picker" type="button"><span><?= e($classes[0]['name'] ?? 'Select class') ?></span><i class="bi bi-chevron-down"></i></button>

                                <label>Jumlah sesi</label>
                                <div class="vouchers-stepper">
                                    <button type="button">-</button>
                                    <span>1</span>
                                    <button type="button">+</button>
                                </div>

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input" type="text" value="Rp 0,00">
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Expiry date</label>
                                <div class="customer-segmented customer-segmented--two">
                                    <button class="is-active" type="button">Setelah</button>
                                    <button type="button">Tanggal Spesifik</button>
                                </div>
                                <button class="customer-picker" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>

                                <label>Dapat digunakan di</label>
                                <button class="customer-picker" type="button"><span>Pilih sesi</span></button>

                                <label>Message</label>
                                <textarea class="form-control customer-input-flat" rows="4" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <span class="vouchers-preview-switch"></span>
                            </div>
                            <div class="vouchers-preview-card vouchers-preview-card--class">
                                <div class="vouchers-preview-left">
                                    <div class="vouchers-preview-brand"><i class="bi bi-record-circle"></i><span>Star Salon</span></div>
                                    <div class="vouchers-preview-qr"></div>
                                    <p>Digenenerate setelah penjualan</p>
                                </div>
                                <div class="vouchers-preview-right">
                                    <strong>For 1 Session</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn customer-footer-btn--disabled">Simpan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="voucherGiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content customer-modal vouchers-editor">
            <div class="customer-modal__header">
                <div></div>
                <h2>Voucher Gift Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="vouchers-editor-tabs">
                <button class="vouchers-editor-tab is-active" type="button">Rincian</button>
                <button class="vouchers-editor-tab" type="button">Lokasi</button>
                <button class="vouchers-editor-tab" type="button">Pengaturan</button>
            </div>
            <div class="customer-modal__body">
                <div class="vouchers-editor-layout">
                    <div class="vouchers-editor-form">
                        <div class="vouchers-editor-grid">
                            <div class="vouchers-editor-col">
                                <label>Nama voucher</label>
                                <input class="form-control customer-input-flat" type="text" placeholder="Please input">

                                <label>Nilai</label>
                                <input class="form-control customer-input-flat vouchers-money-input" type="text" value="Rp 0,00">

                                <label>Harga</label>
                                <input class="form-control customer-input-flat vouchers-money-input" type="text" value="Rp 0,00">

                                <label>Tanggal Kadaluarsa</label>
                                <div class="customer-segmented customer-segmented--two">
                                    <button class="is-active" type="button">Setelah</button>
                                    <button type="button">Tanggal Spesifik</button>
                                </div>
                                <button class="customer-picker" type="button"><span>After 1 Month</span><i class="bi bi-chevron-down"></i></button>
                            </div>

                            <div class="vouchers-editor-col">
                                <label>Dapat digunakan di</label>
                                <button class="customer-picker" type="button"><span>Semua Lokasi</span><strong>Ganti</strong></button>

                                <label>Pesan</label>
                                <textarea class="form-control customer-input-flat" rows="5" placeholder="Thank you!"></textarea>
                                <small>Dapat disesuaikan saat checkout</small>
                            </div>
                        </div>

                        <div class="vouchers-preview-shell">
                            <div class="vouchers-preview-head">
                                <strong>Voucher</strong>
                                <span class="vouchers-preview-switch"></span>
                            </div>
                            <div class="vouchers-preview-card vouchers-preview-card--gift">
                                <div class="vouchers-preview-left">
                                    <div class="vouchers-preview-brand"><i class="bi bi-record-circle"></i><span>Star Salon</span></div>
                                    <div class="vouchers-preview-qr"></div>
                                    <p>Digenenerate setelah penjualan</p>
                                </div>
                                <div class="vouchers-preview-right">
                                    <strong>Rp 0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="customer-modal__footer">
                <button type="button" class="customer-footer-btn" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="customer-footer-btn staff-save-btn">Simpan</button>
            </div>
        </div>
    </div>
</div>
