USE starstyle;

CREATE TABLE IF NOT EXISTS locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    address TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL
);

CREATE TABLE IF NOT EXISTS purchase_orders (
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

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    movement_type VARCHAR(40) NOT NULL,
    quantity INT NOT NULL,
    note VARCHAR(255) NULL,
    created_at DATETIME NOT NULL
);

INSERT INTO locations (id, name, address, is_active)
VALUES (1, 'Star Salon', 'Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur', 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    address = VALUES(address),
    is_active = VALUES(is_active);

INSERT INTO brands (id, name)
VALUES (1, 'Wardah')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);

INSERT INTO categories (id, name)
VALUES (1, 'Hair Care')
ON DUPLICATE KEY UPDATE
    name = VALUES(name);
