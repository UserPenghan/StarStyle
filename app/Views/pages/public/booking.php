<section class="container py-5">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="page-card">
                <div class="section-head mb-4">
                    <div>
                        <span class="eyebrow">Online Reservation</span>
                        <h1 class="section-title mb-0">Reservasi Salon</h1>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/booking')) ?>" class="row g-3 js-booking-form">
                    <?= csrf_field() ?>
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input class="form-control" type="text" name="customer_name" placeholder="Nama lengkap">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telepon</label>
                        <input class="form-control" type="text" name="customer_phone" placeholder="08xx">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Pilih Layanan</label>
                        <select class="form-select" name="service_ids[]" multiple size="5">
                            <?php foreach ($services as $service): ?>
                                <option value="<?= e((string) $service['id']) ?>"><?= e($service['name']) ?> - <?= money($service['price']) ?> / <?= e((string) $service['duration']) ?> menit</option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Gunakan Ctrl/Cmd untuk memilih beberapa layanan sekaligus.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Staff</label>
                        <select class="form-select js-staff-services" name="staff_id">
                            <option value="">Pilih staff</option>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?= e((string) $member['id']) ?>"><?= e($member['name']) ?> - <?= e($member['role']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input class="form-control js-datepicker" type="text" name="date" value="<?= e(date('Y-m-d')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jam</label>
                        <select class="form-select js-availability-target" name="time">
                            <option value="">Pilih slot</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Contoh: ingin hair stylist tertentu, ada preferensi treatment, dll"></textarea>
                    </div>
                    <div class="col-12 d-flex gap-3">
                        <button class="btn btn-dark rounded-pill px-4" type="submit">Konfirmasi Booking</button>
                        <button class="btn btn-light rounded-pill px-4 js-load-slots" type="button">Cek Ketersediaan</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="soft-card h-100">
                <div class="fw-semibold mb-3">Alur reservasi StarStyle</div>
                <div class="vstack gap-3">
                    <div class="timeline-item"><strong>1.</strong> Pilih layanan dan staff sesuai kebutuhan.</div>
                    <div class="timeline-item"><strong>2.</strong> Sistem mengecek anti double booking dan blocked time.</div>
                    <div class="timeline-item"><strong>3.</strong> Booking masuk sebagai <em>pending</em> dan akan tampil di dashboard internal.</div>
                    <div class="timeline-item"><strong>4.</strong> Setelah selesai, booking dapat diubah menjadi transaksi POS dan menambah loyalty point.</div>
                </div>
            </div>
        </div>
    </div>
</section>
