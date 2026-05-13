-- Recovery script for mysql CLI / XAMPP shell.
-- This will recreate the starstyle database from the project schema and seeds.
-- Run from the project root with:
--   mysql -u root -p < database/recovery/2026_05_07_rebuild_starstyle.sql

DROP DATABASE IF EXISTS starstyle;
CREATE DATABASE starstyle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE starstyle;

SOURCE database/schema.sql;
SOURCE database/migrations/2026_05_03_inventory_workflow.sql;
SOURCE database/migrations/2026_05_03_staff_schedule.sql;
SOURCE database/seeders/demo_seed.sql;
SOURCE database/seeders/inventory_workflow_seed.sql;
SOURCE database/seeders/staff_schedule_seed.sql;
