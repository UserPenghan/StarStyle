-- Drop inventory-related tables only (phpMyAdmin-friendly).
-- Goal: reset inventory module data without touching non-inventory modules.
-- WARNING: This deletes inventory data. Backup first.

USE starstyle;

SET FOREIGN_KEY_CHECKS = 0;

-- Stock opname workflow (custom tables / aliases may point here)
DROP TABLE IF EXISTS inventory_opname_session_items;
DROP TABLE IF EXISTS inventory_opname_sessions;
DROP TABLE IF EXISTS stock_opname_session_items;
DROP TABLE IF EXISTS stock_opname_sessions;
DROP TABLE IF EXISTS stock_opnames;

-- Purchasing workflow
DROP TABLE IF EXISTS purchase_order_receiving_logs;
DROP TABLE IF EXISTS purchase_order_items;
DROP TABLE IF EXISTS purchase_orders;

-- Inventory movements / adjustments
DROP TABLE IF EXISTS stock_movements;

-- Master data used by inventory screens
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS locations;

SET FOREIGN_KEY_CHECKS = 1;
