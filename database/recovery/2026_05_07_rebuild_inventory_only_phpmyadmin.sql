-- phpMyAdmin-friendly partial recovery for inventory tables only.
-- This script does NOT drop the whole database.
-- It rebuilds the inventory tables that the inventory module depends on.
-- Backup the current database before importing.

USE starstyle;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS inventory_opname_session_items;
DROP TABLE IF EXISTS inventory_opname_sessions;
DROP TABLE IF EXISTS purchase_order_receiving_logs;
DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS locations;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
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
    description TEXT NULL,
    contact_name VARCHAR(120) NULL,
    phone VARCHAR(40) NULL,
    email VARCHAR(160) NULL,
    website VARCHAR(160) NULL,
    address TEXT NULL,
    city VARCHAR(120) NULL,
    country VARCHAR(120) NULL,
    postal_code VARCHAR(30) NULL
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
    location_id BIGINT UNSIGNED NULL,
    reference VARCHAR(60) NOT NULL UNIQUE,
    order_type VARCHAR(30) NOT NULL DEFAULT 'Order',
    order_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL,
    note TEXT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    ordered_at DATETIME NULL,
    received_at DATETIME NULL
);

CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchase_order_receiving_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    received_qty INT NOT NULL DEFAULT 0,
    supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    received_at DATETIME NOT NULL,
    note VARCHAR(255) NULL
);

CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type VARCHAR(40) NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

CREATE TABLE inventory_opname_sessions (
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

CREATE TABLE inventory_opname_session_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    expected_stock INT NOT NULL DEFAULT 0,
    counted_stock INT NOT NULL DEFAULT 0,
    item_status VARCHAR(30) NOT NULL DEFAULT 'counted',
    note VARCHAR(255) NULL
);

INSERT INTO locations (id, name, address, is_active) VALUES
    (1, 'Star Salon', 'Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur', 1);

INSERT INTO suppliers (id, name, description, contact_name, phone, email, website, address, city, country, postal_code) VALUES
    (1, 'PT Glow Source', 'Supplier utama produk retail dan konsumsi.', 'Ayu Permata', '021-555-111', 'supply@glowsource.test', 'https://supplier.local/glow-source', 'Silom Trade Center', 'Bangkok', 'Thailand', '10500');

INSERT INTO brands (id, name) VALUES
    (1, 'StarStyle Pro');

INSERT INTO categories (id, name) VALUES
    (1, 'Hair Care'),
    (2, 'Styling');

INSERT INTO products (id, brand_id, category_id, supplier_id, name, sku, stock, sell_price, status) VALUES
    (1, 1, 1, 1, 'Silk Repair Serum', 'SSR-001', 14, 190000, 'Aman'),
    (2, 1, 2, 1, 'Ocean Mist Spray', 'OMS-002', 6, 165000, 'Rendah');

INSERT INTO purchase_orders (id, supplier_id, location_id, reference, order_type, order_date, status, note, total_amount, ordered_at, received_at) VALUES
    (1, 1, 1, 'P000001', 'Order', '2026-04-28', 'Received', '', 250000, '2026-04-28 10:00:00', '2026-04-28 18:30:10'),
    (2, 1, 1, 'P000002', 'Order', '2026-04-28', 'Ordered', 'test', 125000, '2026-04-28 12:00:00', NULL);

INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, supply_price) VALUES
    (1, 2, 10, 25000),
    (2, 2, 5, 25000);

INSERT INTO purchase_order_receiving_logs (purchase_order_id, product_name, received_qty, supply_price, received_at, note) VALUES
    (1, 'Ocean Mist Spray', 10, 25000, '2026-04-28 18:30:10', 'Penerimaan penuh');

INSERT INTO stock_movements (id, product_id, movement_type, quantity, note, created_at) VALUES
    (1, 1, 'manual_adjust', 4, 'Stok awal sinkronisasi', '2026-04-27 09:00:00'),
    (2, 2, 'purchase_receive', 10, 'Penerimaan order 1', '2026-04-28 18:30:10'),
    (3, 2, 'sale', -4, 'Penjualan retail', '2026-05-01 14:20:00');

INSERT INTO inventory_opname_sessions (id, location_id, name, note, status, started_at, ended_at, started_by, cancelled_by, cancelled_note) VALUES
    (1, 1, 'Stock Opname #5', 'Tidak ada catatan', 'Meninjau', '2026-05-01 13:11:00', NULL, 'Rayhan Doni Pramana', NULL, NULL),
    (2, 1, 'Stock Opname #4', 'ga jadi', 'Cancelled', '2026-05-01 13:10:00', '2026-05-01 15:21:00', 'Rayhan Doni Pramana', 'Rayhan Doni Pramana', 'ga jadi');

INSERT INTO inventory_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note) VALUES
    (1, 1, 7, 7, 'counted', NULL),
    (1, 2, 10, 8, 'mismatch', NULL),
    (2, 1, 8, 7, 'mismatch', 'ga jadi'),
    (2, 2, 10, 8, 'mismatch', 'ga jadi');
