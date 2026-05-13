-- phpMyAdmin-friendly recovery script for starstyle
-- WARNING: this script drops and recreates the starstyle database.
-- Backup the old database before importing this file.

DROP DATABASE IF EXISTS starstyle;
CREATE DATABASE starstyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE starstyle;


-- BEGIN schema.sql
CREATE DATABASE IF NOT EXISTS starstyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE starstyle;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(120) NOT NULL UNIQUE,
    module_name VARCHAR(80) NOT NULL,
    description VARCHAR(255) NULL
);

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    portal ENUM('internal', 'customer') NOT NULL DEFAULT 'internal',
    avatar VARCHAR(12) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    member_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    gender VARCHAR(20) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(160) NULL,
    loyalty_points INT NOT NULL DEFAULT 0,
    last_visit_at DATETIME NULL,
    tags JSON NULL,
    notes TEXT NULL,
    address TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Aktif',
    merged_to BIGINT UNSIGNED NULL,
    deleted_at DATETIME NULL
);

CREATE TABLE staff (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NULL,
    phone VARCHAR(40) NULL,
    role_title VARCHAR(80) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Aktif',
    commission_type VARCHAR(30) NOT NULL DEFAULT 'Persentase',
    commission_value DECIMAL(12,2) NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL
);

CREATE TABLE staff_permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    permission_key VARCHAR(120) NOT NULL,
    granted TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NULL
);

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    duration_minutes INT NOT NULL,
    base_price DECIMAL(12,2) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Aktif',
    description TEXT NULL,
    deleted_at DATETIME NULL
);

CREATE TABLE service_variants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id BIGINT UNSIGNED NOT NULL,
    variant_name VARCHAR(120) NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(12,2) NOT NULL
);

CREATE TABLE service_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    package_price DECIMAL(12,2) NOT NULL,
    description TEXT NULL
);

CREATE TABLE service_package_items (
    package_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (package_id, service_id)
);

CREATE TABLE staff_skills (
    staff_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (staff_id, service_id)
);

CREATE TABLE bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED NOT NULL,
    reference VARCHAR(60) NOT NULL UNIQUE,
    channel VARCHAR(60) NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    status VARCHAR(30) NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE booking_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(12,2) NOT NULL
);

CREATE TABLE booking_blocks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NULL,
    staff_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    start_at DATETIME NOT NULL,
    end_at DATETIME NOT NULL,
    description TEXT NULL
);

CREATE TABLE booking_status_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED NOT NULL,
    reference VARCHAR(60) NOT NULL UNIQUE,
    payment_method VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    rounding_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE transaction_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    item_type ENUM('service', 'product', 'class', 'voucher') NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(12,2) NOT NULL
);

CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(80) NOT NULL UNIQUE,
    status VARCHAR(30) NOT NULL,
    issued_at DATETIME NOT NULL
);

CREATE TABLE refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason VARCHAR(255) NULL,
    refunded_at DATETIME NOT NULL
);

CREATE TABLE cash_drawers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    open_date DATE NOT NULL,
    expected_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status VARCHAR(40) NOT NULL
);

CREATE TABLE cash_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_drawer_id BIGINT UNSIGNED NULL,
    movement_type ENUM('cash_in', 'cash_out') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL
);

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL
);

CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(160) NULL
);

CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    brand_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    sku VARCHAR(80) NULL,
    stock INT NOT NULL DEFAULT 0,
    sell_price DECIMAL(12,2) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'Aman'
);

CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id BIGINT UNSIGNED NOT NULL,
    reference VARCHAR(60) NOT NULL UNIQUE,
    order_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL
);

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type VARCHAR(40) NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE stock_opnames (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    expected_stock INT NOT NULL,
    actual_stock INT NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE vouchers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_type ENUM('service', 'class', 'gift') NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(80) NOT NULL UNIQUE,
    value DECIMAL(12,2) NOT NULL,
    usage_limit INT NOT NULL DEFAULT 1,
    used_count INT NOT NULL DEFAULT 0,
    expired_at DATE NOT NULL,
    status VARCHAR(30) NOT NULL
);

CREATE TABLE voucher_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voucher_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    booking_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    redeemed_at DATETIME NOT NULL
);

CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL
);

CREATE TABLE class_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT UNSIGNED NOT NULL,
    start_at DATETIME NOT NULL,
    total_slot INT NOT NULL
);

CREATE TABLE class_bookings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_session_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL
);

CREATE TABLE loyalty_ledgers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    transaction_id BIGINT UNSIGNED NULL,
    points INT NOT NULL,
    type ENUM('earn', 'redeem', 'adjustment') NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT NOT NULL,
    feedback TEXT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    type VARCHAR(40) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
);

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    actor_name VARCHAR(120) NOT NULL,
    action_text VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE business_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(150) NOT NULL,
    business_hours VARCHAR(120) NULL,
    address TEXT NULL,
    booking_advance_days INT NOT NULL DEFAULT 30,
    loyalty_ratio INT NOT NULL DEFAULT 10000,
    currency VARCHAR(10) NOT NULL DEFAULT 'IDR',
    notification_channel VARCHAR(120) NULL
);

-- END schema.sql


-- BEGIN 2026_05_03_inventory_workflow.sql
USE starstyle;

ALTER TABLE suppliers
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER name,
    ADD COLUMN IF NOT EXISTS contact_name VARCHAR(120) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS website VARCHAR(160) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER website,
    ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL AFTER address,
    ADD COLUMN IF NOT EXISTS country VARCHAR(120) NULL AFTER city,
    ADD COLUMN IF NOT EXISTS postal_code VARCHAR(30) NULL AFTER country;

ALTER TABLE purchase_orders
    ADD COLUMN IF NOT EXISTS location_id BIGINT UNSIGNED NULL AFTER supplier_id,
    ADD COLUMN IF NOT EXISTS order_type VARCHAR(30) NOT NULL DEFAULT 'Order' AFTER reference,
    ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER status,
    ADD COLUMN IF NOT EXISTS total_amount DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER note,
    ADD COLUMN IF NOT EXISTS ordered_at DATETIME NULL AFTER total_amount,
    ADD COLUMN IF NOT EXISTS received_at DATETIME NULL AFTER ordered_at;

CREATE TABLE IF NOT EXISTS purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS purchase_order_receiving_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    received_qty INT NOT NULL DEFAULT 0,
    supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    received_at DATETIME NOT NULL,
    note VARCHAR(255) NULL
);

CREATE TABLE IF NOT EXISTS stock_opname_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    note TEXT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'Meninjau',
    started_at DATETIME NOT NULL,
    ended_at DATETIME NULL,
    started_by VARCHAR(120) NULL,
    cancelled_by VARCHAR(120) NULL,
    cancelled_note TEXT NULL
);

CREATE TABLE IF NOT EXISTS stock_opname_session_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    expected_stock INT NOT NULL DEFAULT 0,
    counted_stock INT NOT NULL DEFAULT 0,
    item_status VARCHAR(30) NOT NULL DEFAULT 'counted',
    note VARCHAR(255) NULL
);

-- END 2026_05_03_inventory_workflow.sql


-- BEGIN 2026_05_03_staff_schedule.sql
USE starstyle;

CREATE TABLE IF NOT EXISTS staff_shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    repeat_mode VARCHAR(20) NOT NULL DEFAULT 'none',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    shift_start TIME NOT NULL,
    shift_end TIME NOT NULL,
    clock_in TIME NOT NULL,
    clock_out TIME NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT '-',
    status VARCHAR(30) NOT NULL DEFAULT 'Ontime',
    selfie_in_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    selfie_out_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- END 2026_05_03_staff_schedule.sql


-- BEGIN demo_seed.sql
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

-- END demo_seed.sql


-- BEGIN inventory_workflow_seed.sql
USE starstyle;

INSERT INTO locations (id, name, address, is_active)
VALUES (1, 'Star Salon', 'Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    is_active = VALUES(is_active);

UPDATE suppliers
SET description = 'Supplier utama produk retail dan konsumsi.',
    contact_name = 'Ayu Permata',
    website = 'https://supplier.local/glow-source',
    address = 'Silom Trade Center',
    city = 'Bangkok',
    country = 'Thailand',
    postal_code = '10500'
WHERE id = 1;

INSERT INTO purchase_orders (id, supplier_id, location_id, reference, order_type, order_date, status, note, total_amount, ordered_at, received_at)
VALUES
    (1, 1, 1, 'P000001', 'Order', '2026-04-28', 'Received', '', 250000, '2026-04-28 10:00:00', '2026-04-28 18:30:10'),
    (2, 1, 1, 'P000002', 'Order', '2026-04-28', 'Ordered', 'test', 125000, '2026-04-28 12:00:00', NULL)
ON DUPLICATE KEY UPDATE
    supplier_id = VALUES(supplier_id),
    location_id = VALUES(location_id),
    order_type = VALUES(order_type),
    order_date = VALUES(order_date),
    status = VALUES(status),
    note = VALUES(note),
    total_amount = VALUES(total_amount),
    ordered_at = VALUES(ordered_at),
    received_at = VALUES(received_at);

INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, supply_price)
VALUES
    (1, 2, 10, 25000),
    (2, 2, 5, 25000);

INSERT INTO purchase_order_receiving_logs (purchase_order_id, product_name, received_qty, supply_price, received_at, note)
VALUES
    (1, 'Hair Serum Wardah - 500ml', 10, 25000, '2026-04-28 18:30:10', 'Penerimaan penuh');

INSERT INTO stock_opname_sessions (id, location_id, name, note, status, started_at, ended_at, started_by, cancelled_by, cancelled_note)
VALUES
    (1, 1, 'Stock Opname #5', 'Tidak ada catatan', 'Meninjau', '2026-05-01 13:11:00', NULL, 'Rayhan Doni Pramana', NULL, NULL),
    (2, 1, 'Stock Opname #4', 'ga jadi', 'Dibatalkan', '2026-05-01 13:10:00', '2026-05-01 15:21:00', 'Rayhan Doni Pramana', 'Rayhan Doni Pramana', 'ga jadi')
ON DUPLICATE KEY UPDATE
    location_id = VALUES(location_id),
    name = VALUES(name),
    note = VALUES(note),
    status = VALUES(status),
    started_at = VALUES(started_at),
    ended_at = VALUES(ended_at),
    started_by = VALUES(started_by),
    cancelled_by = VALUES(cancelled_by),
    cancelled_note = VALUES(cancelled_note);

INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
VALUES
    (1, 1, 7, 7, 'counted', NULL),
    (1, 2, 10, 8, 'mismatch', NULL),
    (2, 1, 8, 7, 'mismatch', 'ga jadi'),
    (2, 2, 10, 8, 'mismatch', 'ga jadi');

-- END inventory_workflow_seed.sql


-- BEGIN staff_schedule_seed.sql
USE starstyle;

INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, repeat_mode)
VALUES
    (1, '2026-05-03', '08:00:00', '17:00:00', 'weekly'),
    (2, '2026-05-02', '08:00:00', '17:00:00', 'weekly');

INSERT INTO staff_attendance (staff_id, attendance_date, shift_start, shift_end, clock_in, clock_out, source, status, selfie_in_score, selfie_out_score)
VALUES
    (1, '2026-05-03', '08:00:00', '17:00:00', '08:00:00', '17:00:00', 'Manual', 'Ontime', 0, 0),
    (2, '2026-05-02', '08:00:00', '17:00:00', '08:00:00', '17:48:00', 'Manual', 'Overtime', 0, 0);

-- END staff_schedule_seed.sql

