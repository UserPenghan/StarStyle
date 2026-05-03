USE starstyle;

INSERT INTO roles (id, name, description) VALUES
(1, 'admin', 'Akses penuh ke seluruh sistem'),
(2, 'staff', 'Akses terbatas yang diatur admin'),
(3, 'customer', 'Portal pelanggan untuk booking dan histori');

INSERT INTO permissions (permission_key, module_name, description) VALUES
('dashboard.view', 'dashboard', 'Lihat dashboard'),
('calendar.view', 'calendar', 'Lihat kalender'),
('calendar.create', 'calendar', 'Buat booking'),
('sales.view', 'sales', 'Lihat penjualan'),
('sales.checkout', 'sales', 'Proses checkout POS'),
('customers.view', 'customers', 'Lihat pelanggan'),
('staff.view', 'staff', 'Lihat staf'),
('services.view', 'services', 'Lihat layanan'),
('inventory.view', 'inventory', 'Lihat inventory'),
('vouchers.view', 'vouchers', 'Lihat voucher'),
('analytics.view', 'analytics', 'Lihat analitik'),
('reviews.view', 'reviews', 'Lihat review dan logs'),
('settings.view', 'settings', 'Lihat settings'),
('settings.permissions', 'settings', 'Atur hak akses staff');

INSERT INTO users (id, role_id, name, email, password, portal, avatar) VALUES
(1, 1, 'Rayhan Donovan', 'admin@starstyle.test', '$2y$10$i55pDXTIdf.h4WETCVZON.M/F0g3OLMOs1CcAyifSLh8OUDcls5Wm', 'internal', 'RD'),
(2, 2, 'Maya Putri', 'stylist@starstyle.test', '$2y$10$i55pDXTIdf.h4WETCVZON.M/F0g3OLMOs1CcAyifSLh8OUDcls5Wm', 'internal', 'MP'),
(3, 3, 'Citra Aulia', 'customer@starstyle.test', '$2y$10$i55pDXTIdf.h4WETCVZON.M/F0g3OLMOs1CcAyifSLh8OUDcls5Wm', 'customer', 'CA');

INSERT INTO business_settings (id, business_name, business_hours, address, booking_advance_days, loyalty_ratio, currency, notification_channel) VALUES
(1, 'StarStyle Salon', '09:00 - 20:00', 'Silom Creative Avenue, Bangkok', 30, 10000, 'IDR', 'Email + WhatsApp placeholder');

INSERT INTO customers (id, user_id, member_id, name, gender, phone, email, loyalty_points, last_visit_at, tags, status) VALUES
(1, 3, 'MEM-0001', 'Citra Aulia', 'Perempuan', '0813-9000-1111', 'customer@starstyle.test', 340, NOW(), JSON_ARRAY('VIP', 'Hair Color'), 'Aktif'),
(2, NULL, 'MEM-0002', 'Alif Rahman', 'Laki-laki', '0813-9000-1112', 'alif@starstyle.test', 120, NOW(), JSON_ARRAY('Haircut'), 'Aktif');

INSERT INTO staff (id, user_id, name, email, phone, role_title, status, commission_type, commission_value, rating) VALUES
(1, 1, 'Rayhan Donovan', 'admin@starstyle.test', '0812-1111-1001', 'Owner', 'Aktif', 'Persentase', 18, 4.9),
(2, 2, 'Maya Putri', 'stylist@starstyle.test', '0812-1111-1002', 'Senior Stylist', 'Aktif', 'Persentase', 12, 4.8),
(3, NULL, 'Kevin Sebastian', 'kevin@starstyle.test', '0812-1111-1003', 'Color Expert', 'Aktif', 'Fixed', 75000, 4.7);

INSERT INTO staff_permissions (staff_id, permission_key, granted) VALUES
(2, 'dashboard.view', 1),
(2, 'calendar.view', 1),
(2, 'calendar.create', 1),
(2, 'sales.view', 1),
(2, 'customers.view', 1),
(2, 'staff.view', 1),
(2, 'services.view', 1),
(2, 'vouchers.view', 1),
(2, 'reviews.view', 1);

INSERT INTO service_groups (id, name) VALUES
(1, 'Hair Signature'),
(2, 'Color Studio'),
(3, 'Spa & Nail');

INSERT INTO services (id, group_id, name, duration_minutes, base_price, status, description) VALUES
(1, 1, 'Signature Haircut', 60, 280000, 'Aktif', 'Cutting presisi dengan styling finish'),
(2, 2, 'Glossy Balayage', 150, 1250000, 'Aktif', 'Color gradient lembut dan dimensional'),
(3, 1, 'Keratin Repair', 90, 650000, 'Aktif', 'Recovery treatment untuk rambut rusak');

INSERT INTO service_packages (id, name, package_price, description) VALUES
(1, 'Beauty Reset', 680000, 'Signature Haircut + Relaxing Head Spa');

INSERT INTO service_package_items (package_id, service_id) VALUES
(1, 1),
(1, 3);

INSERT INTO staff_skills (staff_id, service_id) VALUES
(2, 1),
(2, 3),
(3, 2);

INSERT INTO suppliers (id, name, phone, email) VALUES
(1, 'PT Glow Source', '021-555-111', 'supply@glowsource.test');

INSERT INTO brands (id, name) VALUES
(1, 'StarStyle Pro');

INSERT INTO categories (id, name) VALUES
(1, 'Hair Care'),
(2, 'Styling');

INSERT INTO products (id, brand_id, category_id, supplier_id, name, sku, stock, sell_price, status) VALUES
(1, 1, 1, 1, 'Silk Repair Serum', 'SSR-001', 14, 190000, 'Aman'),
(2, 1, 2, 1, 'Ocean Mist Spray', 'OMS-002', 6, 165000, 'Rendah');

INSERT INTO vouchers (id, voucher_type, name, code, value, usage_limit, used_count, expired_at, status) VALUES
(1, 'gift', 'WELCOME10', 'WELCOME10', 100000, 1, 0, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'Aktif'),
(2, 'service', 'HEADSPA25', 'HEADSPA25', 25, 50, 8, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'Aktif');

INSERT INTO bookings (id, customer_id, staff_id, reference, channel, start_at, end_at, status, notes) VALUES
(1001, 1, 2, 'BK-240401', 'Online', CONCAT(CURDATE(), ' 10:00:00'), CONCAT(CURDATE(), ' 12:30:00'), 'confirmed', 'Request stylist Maya'),
(1002, 2, 3, 'BK-240402', 'Walk-in', CONCAT(CURDATE(), ' 13:00:00'), CONCAT(CURDATE(), ' 15:30:00'), 'confirmed', 'Color consultation lengkap');

INSERT INTO booking_items (booking_id, service_id, duration_minutes, price) VALUES
(1001, 1, 60, 280000),
(1001, 3, 90, 650000),
(1002, 2, 150, 1250000);

INSERT INTO booking_blocks (staff_id, title, start_at, end_at, description) VALUES
(3, 'Color Prep & Inventory', CONCAT(CURDATE(), ' 16:00:00'), CONCAT(CURDATE(), ' 17:30:00'), 'Internal blocked time');

INSERT INTO transactions (id, booking_id, customer_id, staff_id, reference, payment_method, status, discount_amount, rounding_amount, paid_at) VALUES
(2001, 1001, 1, 2, 'TRX-240401', 'Cash', 'paid', 50000, 0, NOW());

INSERT INTO transaction_items (transaction_id, item_type, item_name, quantity, price) VALUES
(2001, 'service', 'Keratin Repair', 1, 650000),
(2001, 'product', 'Silk Repair Serum', 1, 190000);

INSERT INTO invoices (transaction_id, invoice_number, status, issued_at) VALUES
(2001, 'INV-240401', 'paid', NOW());

INSERT INTO loyalty_ledgers (customer_id, transaction_id, points, type, note, created_at) VALUES
(1, 2001, 84, 'earn', 'Checkout transaksi TRX-240401', NOW());

INSERT INTO reviews (booking_id, customer_id, rating, feedback, created_at) VALUES
(1001, 1, 5, 'Coloring rapi, staff komunikatif, hasilnya mewah sekali.', NOW());

INSERT INTO notifications (title, type, created_at) VALUES
('2 booking menunggu konfirmasi', 'info', NOW()),
('1 voucher akan expired minggu ini', 'warning', NOW());

INSERT INTO activity_logs (actor_name, action_text, created_at) VALUES
('Rayhan Donovan', 'Menyetujui booking BK-240401', NOW()),
('System', 'Low stock alert untuk Ocean Mist Spray', NOW());
