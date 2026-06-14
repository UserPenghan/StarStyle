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
    cancel_reason VARCHAR(255) NULL,
    products_json LONGTEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE booking_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    start_at DATETIME NULL,
    end_at DATETIME NULL,
    resource_id VARCHAR(60) NULL,
    resource_name VARCHAR(150) NULL
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
