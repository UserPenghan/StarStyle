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
