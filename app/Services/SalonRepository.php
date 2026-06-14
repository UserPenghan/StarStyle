<?php

declare(strict_types=1);

namespace App\Services;

final class SalonRepository
{
    private const LEGACY_TABLE_ALIASES = [
        'stock_opname_sessions' => 'inventory_opname_sessions',
        'stock_opname_session_items' => 'inventory_opname_session_items',
    ];

    private array $baseData;
    private ?\PDO $pdo = null;
    private ?bool $dbReady = null;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];
    private bool $inventorySchemaEnsured = false;
    private bool $inventorySeedEnsured = false;
    private bool $staffScheduleSchemaEnsured = false;
    private bool $staffScheduleSeedEnsured = false;
    private bool $customerSchemaEnsured = false;
    private bool $staffProfileSchemaEnsured = false;
    private bool $serviceCatalogSchemaEnsured = false;
    private bool $voucherCatalogSchemaEnsured = false;
    private bool $bookingSchemaEnsured = false;

    public function __construct(
        private readonly array $config,
        private readonly array $permissions,
    ) {
        date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');
        $this->baseData = DemoData::make($config);
        $this->bootSessionState();
    }

    private function wantsDb(): bool
    {
        return ($this->config['data_source'] ?? 'demo') === 'db';
    }

    private function usingDb(): bool
    {
        return $this->wantsDb() && $this->databaseReady();
    }

    private function databaseReady(): bool
    {
        if (!$this->wantsDb()) {
            return false;
        }

        if ($this->dbReady !== null) {
            return $this->dbReady;
        }

        try {
            $pdo = $this->pdo();
            $pdo->query('SELECT 1');

            foreach (['users', 'roles', 'permissions'] as $table) {
                $pdo->query(sprintf('SELECT 1 FROM `%s` LIMIT 1', $table))->fetch();
            }

            return $this->dbReady = true;
        } catch (\Throwable) {
            return $this->dbReady = false;
        }
    }

    private function canUsePdo(): bool
    {
        if (!$this->wantsDb()) {
            return false;
        }

        try {
            $this->pdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function inventoryDbEnabled(): bool
    {
        if (!$this->canUsePdo()) {
            return false;
        }

        foreach ([
            'products',
            'suppliers',
            'stock_opname_sessions',
            'stock_opnames',
            'purchase_orders',
            'stock_movements',
        ] as $table) {
            if ($this->tableExists($table)) {
                return true;
            }
        }

        return false;
    }

    private function pdo(): \PDO
    {
        if (!$this->wantsDb()) {
            throw new \RuntimeException('Data source bukan DB.');
        }

        if ($this->pdo instanceof \PDO) {
            return $this->pdo;
        }

        $db = $this->config['db'] ?? [];
        $host = (string) ($db['host'] ?? '127.0.0.1');
        $port = (int) ($db['port'] ?? 3306);
        $database = (string) ($db['database'] ?? '');
        $charset = (string) ($db['charset'] ?? 'utf8mb4');
        $username = (string) ($db['username'] ?? 'root');
        $password = (string) ($db['password'] ?? '');

        if ($database === '') {
            throw new \RuntimeException('Konfigurasi database kosong: config.db.database');
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        $this->pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }

    private function dbAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo()->prepare($this->resolveSqlTableAliases($sql));
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function dbOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo()->prepare($this->resolveSqlTableAliases($sql));
        $stmt->execute($params);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    private function dbExecute(string $sql, array $params = []): void
    {
        $stmt = $this->pdo()->prepare($this->resolveSqlTableAliases($sql));
        $stmt->execute($params);
    }

    private function resolveTableName(string $table): string
    {
        if (!$this->canUsePdo()) {
            return self::LEGACY_TABLE_ALIASES[$table] ?? $table;
        }

        if ($this->tableListedInSchemaRaw($table)) {
            return $table;
        }

        $legacyTable = self::LEGACY_TABLE_ALIASES[$table] ?? null;
        if ($legacyTable !== null && $this->tableListedInSchemaRaw($legacyTable)) {
            return $legacyTable;
        }

        return $table;
    }

    private function resolveSqlTableAliases(string $sql): string
    {
        foreach (self::LEGACY_TABLE_ALIASES as $logicalTable => $_legacyTable) {
            $resolvedTable = $this->resolveTableName($logicalTable);
            if ($resolvedTable === $logicalTable) {
                continue;
            }

            $sql = preg_replace('/`' . preg_quote($logicalTable, '/') . '`/', '`' . $resolvedTable . '`', $sql) ?? $sql;
            $sql = preg_replace('/\b' . preg_quote($logicalTable, '/') . '\b/', $resolvedTable, $sql) ?? $sql;
        }

        return $sql;
    }

    private function tableListedInSchemaRaw(string $table): bool
    {
        $stmt = $this->pdo()->prepare(
            "SELECT 1
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table_name
             LIMIT 1"
        );
        $stmt->execute(['table_name' => $table]);

        return (bool) $stmt->fetchColumn();
    }

    private function tableListedInSchema(string $table): bool
    {
        return $this->tableListedInSchemaRaw($this->resolveTableName($table));
    }

    private function tableCanBeQueried(string $table): bool
    {
        $table = $this->resolveTableName($table);
        try {
            $this->pdo()->query(sprintf('SELECT 1 FROM `%s` LIMIT 1', $table))->fetch();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        if (!$this->canUsePdo()) {
            return false;
        }

        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        if (!$this->tableListedInSchema($table)) {
            return $this->tableExistsCache[$table] = false;
        }

        return $this->tableExistsCache[$table] = $this->tableCanBeQueried($table);
    }

    private function isRepairableWorkflowTableException(\Throwable $throwable, array $tables): bool
    {
        if (!$throwable instanceof \PDOException) {
            return false;
        }

        $message = strtolower($throwable->getMessage());
        $isMissingInEngine = str_contains($message, "doesn't exist in engine");
        $isMissingTable = str_contains($message, 'base table or view not found')
            || str_contains($message, 'sqlstate[42s02]')
            || str_contains($message, "doesn't exist");

        if (!$isMissingInEngine && !$isMissingTable) {
            return false;
        }

        foreach ($tables as $table) {
            $resolvedTable = strtolower($this->resolveTableName($table));
            if (str_contains($message, strtolower($table)) || str_contains($message, $resolvedTable)) {
                return true;
            }
        }

        return false;
    }

    private function isMissingOrBrokenTableException(\Throwable $throwable): bool
    {
        if (!$throwable instanceof \PDOException) {
            return false;
        }

        $message = strtolower($throwable->getMessage());

        return str_contains($message, "doesn't exist in engine")
            || str_contains($message, 'base table or view not found')
            || str_contains($message, 'sqlstate[42s02]')
            || str_contains($message, 'sqlstate[hy000]: general error: 1813');
    }

    private function resetInventoryWorkflowSchemaState(): void
    {
        $this->tableExistsCache = [];
        $this->columnExistsCache = [];
        $this->inventorySchemaEnsured = false;
    }

    private function rebuildInventoryWorkflowTables(array $tables): void
    {
        $this->resetInventoryWorkflowSchemaState();

        foreach ($tables as $table) {
            if (!$this->tableListedInSchema($table)) {
                continue;
            }

            $this->pdo()->exec(sprintf('DROP TABLE IF EXISTS `%s`', $this->resolveTableName($table)));
        }

        $this->resetInventoryWorkflowSchemaState();
        $this->ensureInventoryWorkflowSchema();
    }

    private function withInventoryWorkflowRepair(callable $callback)
    {
        $workflowTables = [
            'stock_opname_session_items',
            'stock_opname_sessions',
            'purchase_order_receiving_logs',
            'purchase_order_items',
        ];

        try {
            return $callback();
        } catch (\Throwable $throwable) {
            if (!$this->isRepairableWorkflowTableException($throwable, $workflowTables)) {
                throw $throwable;
            }

            $this->rebuildInventoryWorkflowTables($workflowTables);

            return $callback();
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }

        $cacheKey = $table . '.' . $column;
        if (array_key_exists($cacheKey, $this->columnExistsCache)) {
            return $this->columnExistsCache[$cacheKey];
        }

        $resolvedTable = $this->resolveTableName($table);
        $row = $this->dbOne(
            "SELECT 1
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND column_name = :column_name
             LIMIT 1",
            [
                'table_name' => $resolvedTable,
                'column_name' => $column,
            ]
        );

        return $this->columnExistsCache[$cacheKey] = $row !== null;
    }

    private function ensureBookingSchema(): void
    {
        if (!$this->usingDb() || $this->bookingSchemaEnsured) {
            return;
        }

        if ($this->tableExists('bookings')) {
            $bookingColumns = [
                'updated_at' => 'ADD COLUMN updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at',
                'cancel_reason' => 'ADD COLUMN cancel_reason VARCHAR(255) NULL DEFAULT NULL AFTER notes',
                'products_json' => 'ADD COLUMN products_json LONGTEXT NULL AFTER cancel_reason',
                'payment_method' => "ADD COLUMN payment_method VARCHAR(40) NULL DEFAULT NULL AFTER products_json",
                'payment_proof_path' => 'ADD COLUMN payment_proof_path LONGTEXT NULL AFTER payment_method',
                'payment_review_status' => "ADD COLUMN payment_review_status VARCHAR(40) NOT NULL DEFAULT 'waiting_admin' AFTER payment_proof_path",
            ];
            foreach ($bookingColumns as $column => $ddl) {
                if (!$this->columnExists('bookings', $column)) {
                    $this->dbExecute("ALTER TABLE bookings {$ddl}");
                }
            }
        }

        if ($this->tableExists('booking_items')) {
            $itemColumns = [
                'staff_id' => 'ADD COLUMN staff_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER service_id',
                'start_at' => 'ADD COLUMN start_at DATETIME NULL DEFAULT NULL AFTER price',
                'end_at' => 'ADD COLUMN end_at DATETIME NULL DEFAULT NULL AFTER start_at',
                'resource_id' => 'ADD COLUMN resource_id VARCHAR(60) NULL DEFAULT NULL AFTER end_at',
                'resource_name' => 'ADD COLUMN resource_name VARCHAR(150) NULL DEFAULT NULL AFTER resource_id',
            ];
            foreach ($itemColumns as $column => $ddl) {
                if (!$this->columnExists('booking_items', $column)) {
                    $this->dbExecute("ALTER TABLE booking_items {$ddl}");
                }
            }
        }

        $this->tableExistsCache = [];
        $this->columnExistsCache = [];
        $this->bookingSchemaEnsured = true;
    }

    private function ensureBusinessSettingsSchema(): void
    {
        if (!$this->usingDb() || !$this->tableExists('business_settings')) {
            return;
        }

        $columns = [
            'timezone' => "ADD COLUMN timezone VARCHAR(80) NOT NULL DEFAULT 'Asia/Bangkok' AFTER notification_channel",
            'hours_schedule_json' => 'ADD COLUMN hours_schedule_json LONGTEXT NULL AFTER timezone',
        ];

        foreach ($columns as $column => $ddl) {
            if (!$this->columnExists('business_settings', $column)) {
                $this->dbExecute("ALTER TABLE business_settings {$ddl}");
            }
        }

        $this->tableExistsCache = [];
        $this->columnExistsCache = [];
    }

    private function normalizeStoredBookingProducts(mixed $rawValue): array
    {
        $items = is_string($rawValue) ? json_decode($rawValue, true) : $rawValue;
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = trim((string) ($item['id'] ?? ''));
            $name = trim((string) ($item['name'] ?? ''));
            if ($id === '' || $name === '') {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'name' => $name,
                'variant' => trim((string) ($item['variant'] ?? '')),
                'price' => (float) ($item['price'] ?? 0),
                'stock' => (int) ($item['stock'] ?? 0),
                'qty' => max(1, (int) ($item['qty'] ?? 1)),
            ];
        }

        return $normalized;
    }

    private function bookingReferenceLookup(string $reference): ?array
    {
        if ($reference === '') {
            return null;
        }

        if ($this->usingDb()) {
            $this->ensureBookingSchema();

            return $this->dbOne(
                "SELECT id, location_id, customer_id, staff_id, reference, channel, start_at, end_at, status, notes, cancel_reason, products_json, payment_method, payment_proof_path, payment_review_status, created_at, updated_at
                 FROM bookings
                 WHERE reference = :reference
                 LIMIT 1",
                ['reference' => $reference]
            );
        }

        foreach ($_SESSION['starstyle']['bookings'] as $booking) {
            if ((string) ($booking['reference'] ?? '') === $reference) {
                return $booking;
            }
        }

        return null;
    }

    private function bookingByReference(string $reference): ?array
    {
        if ($reference === '') {
            return null;
        }

        foreach ($this->getBookings() as $booking) {
            if ((string) ($booking['reference'] ?? '') === $reference) {
                return $booking;
            }
        }

        return null;
    }

    private function normalizeBookingPaymentReviewStatus(string $status): string
    {
        $normalized = strtolower(trim(str_replace(' ', '_', $status)));
        $allowedStatuses = ['waiting_admin', 'complete'];

        return in_array($normalized, $allowedStatuses, true) ? $normalized : 'waiting_admin';
    }

    private function paymentProofDataUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            return '';
        }

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === '') {
            return '';
        }

        $mimeType = mime_content_type($path) ?: 'image/jpeg';

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    public function findUserByEmail(string $email, string $portal): ?array
    {
        if ($this->usingDb()) {
            return $this->dbOne(
                "SELECT u.id, u.name, u.email, u.password, u.portal, u.avatar, r.name AS role,
                        s.id AS staff_id, c.id AS customer_id
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 LEFT JOIN staff s ON s.user_id = u.id AND s.deleted_at IS NULL
                 LEFT JOIN customers c ON c.user_id = u.id AND c.deleted_at IS NULL
                 WHERE LOWER(u.email) = LOWER(:email) AND u.portal = :portal AND u.is_active = 1
                 LIMIT 1",
                [
                    'email' => $email,
                    'portal' => $portal,
                ]
            );
        }

        foreach ($this->baseData['users'] as $user) {
            if (strcasecmp($user['email'], $email) === 0 && $user['portal'] === $portal) {
                return $user;
            }
        }

        return null;
    }

    public function findUserById(int $userId): ?array
    {
        if ($this->usingDb()) {
            return $this->dbOne(
                "SELECT u.id, u.name, u.email, u.password, u.portal, u.avatar, r.name AS role,
                        s.id AS staff_id, c.id AS customer_id
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 LEFT JOIN staff s ON s.user_id = u.id AND s.deleted_at IS NULL
                 LEFT JOIN customers c ON c.user_id = u.id AND c.deleted_at IS NULL
                 WHERE u.id = :id AND u.is_active = 1
                 LIMIT 1",
                ['id' => $userId]
            );
        }

        foreach ($this->baseData['users'] as $user) {
            if ($user['id'] === $userId) {
                return $user;
            }
        }

        return null;
    }

    public function getPermissionsForUser(array $user): array
    {
        if ($this->usingDb()) {
            // Admin gets all permissions.
            if (($user['role'] ?? '') === 'admin') {
                $stmt = $this->pdo()->query('SELECT permission_key FROM permissions ORDER BY permission_key');

                return array_map(static fn (array $row): string => (string) $row['permission_key'], $stmt->fetchAll());
            }

            // Staff can be overridden by staff_permissions table; otherwise role_permissions.
            if (($user['role'] ?? '') === 'staff') {
                $stmt = $this->pdo()->prepare(
                    "SELECT sp.permission_key
                     FROM staff s
                     JOIN staff_permissions sp ON sp.staff_id = s.id
                     WHERE s.user_id = :user_id AND sp.granted = 1"
                );
                $stmt->execute(['user_id' => (int) ($user['id'] ?? 0)]);
                $overrides = $stmt->fetchAll();
                if ($overrides !== []) {
                    return array_values(array_unique(array_map(static fn (array $row): string => (string) $row['permission_key'], $overrides)));
                }
            }

            // Role-based permissions.
            $stmt = $this->pdo()->prepare(
                "SELECT p.permission_key
                 FROM users u
                 JOIN role_permissions rp ON rp.role_id = u.role_id
                 JOIN permissions p ON p.id = rp.permission_id
                 WHERE u.id = :user_id"
            );
            $stmt->execute(['user_id' => (int) ($user['id'] ?? 0)]);

            return array_map(static fn (array $row): string => (string) $row['permission_key'], $stmt->fetchAll());
        }

        if ($user['role'] === 'admin') {
            return $this->allPermissionKeys();
        }

        if ($user['role'] !== 'staff') {
            return $this->permissions['defaults'][$user['role']] ?? [];
        }

        $staffId = $user['staff_id'] ?? null;
        $defaults = $this->permissions['defaults']['staff'] ?? [];
        $overrides = $_SESSION['starstyle']['staff_permissions'][$staffId] ?? null;

        return array_values(array_unique(is_array($overrides) ? $overrides : $defaults));
    }

    public function getLandingData(): array
    {
        $services = $this->getServices();
        $transactions = $this->getTransactions();
        $bookings = $this->getBookings();
        $settings = $this->getSettings();

        return [
            'heroMetrics' => [
                ['label' => 'Booking aktif hari ini', 'value' => count(array_filter($bookings, fn (array $booking): bool => str_starts_with($booking['start_at'], date('Y-m-d')) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true)))],
                ['label' => 'Transaksi minggu ini', 'value' => count($transactions)],
                ['label' => 'Layanan premium', 'value' => count(array_filter($services, fn (array $service): bool => $service['price'] >= 500000))],
            ],
            'featuredServices' => array_slice($services, 0, 4),
            'packages' => $this->getPackages(),
            'reviews' => $this->getReviews(),
            'business' => [
                'name' => $settings['business_name'] ?? $this->config['business']['name'],
                'city' => $this->config['business']['city'],
                'hotline' => $this->config['business']['hotline'],
                'email' => $this->config['business']['email'],
                'hours' => $settings['hours'] ?? $this->config['business']['hours'],
                'address' => $settings['address'] ?? $this->config['business']['address'],
            ],
        ];
    }

    public function getServices(): array
    {
        if ($this->usingDb()) {
            $this->ensureServiceCatalogSchema();
            $services = $this->dbAll(
                "SELECT s.id, s.group_id, s.name, s.duration_minutes, s.base_price, s.status, s.description,
                        s.audience_json, s.image_data_url, s.online_bookable, s.commission_enabled,
                        s.at_customer_location, s.extra_time_type, s.extra_time_minutes
                 FROM services s
                 WHERE s.deleted_at IS NULL
                 ORDER BY s.id"
            );
            $variantRows = $this->dbAll(
                "SELECT service_id, variant_name, duration_minutes, price, special_price, location_pricing_json,
                        cost_price, cost_products_json, availability_json
                 FROM service_variants
                 ORDER BY id"
            );
            $skillRows = $this->dbAll("SELECT staff_id, service_id FROM staff_skills ORDER BY staff_id, service_id");

            $variantsByService = [];
            foreach ($variantRows as $row) {
                $serviceId = (int) $row['service_id'];
                $variantsByService[$serviceId][] = [
                    'variant_name' => (string) ($row['variant_name'] ?? ''),
                    'duration_minutes' => (int) ($row['duration_minutes'] ?? 0),
                    'price' => (float) ($row['price'] ?? 0),
                    'special_price' => (float) ($row['special_price'] ?? 0),
                    'location_pricing' => json_decode((string) ($row['location_pricing_json'] ?? 'null'), true),
                    'cost_price' => (float) ($row['cost_price'] ?? 0),
                    'cost_products' => json_decode((string) ($row['cost_products_json'] ?? '[]'), true),
                    'availability' => json_decode((string) ($row['availability_json'] ?? 'null'), true),
                ];
            }

            $staffByService = [];
            foreach ($skillRows as $row) {
                $staffByService[(int) $row['service_id']][] = (int) $row['staff_id'];
            }

            return array_map(static function (array $service) use ($variantsByService, $staffByService): array {
                $serviceId = (int) $service['id'];
                $audience = json_decode((string) ($service['audience_json'] ?? '[]'), true);
                $variantDetails = array_map(static function (array $variant): array {
                    return [
                        'variant_name' => (string) ($variant['variant_name'] ?? ''),
                        'duration_minutes' => (int) ($variant['duration_minutes'] ?? 0),
                        'price' => (float) ($variant['price'] ?? 0),
                        'special_price' => (float) ($variant['special_price'] ?? 0),
                        'location_pricing' => is_array($variant['location_pricing'] ?? null) ? $variant['location_pricing'] : null,
                        'cost_price' => (float) ($variant['cost_price'] ?? 0),
                        'cost_products' => is_array($variant['cost_products'] ?? null) ? $variant['cost_products'] : [],
                        'availability' => is_array($variant['availability'] ?? null) ? $variant['availability'] : null,
                    ];
                }, $variantsByService[$serviceId] ?? []);

                return [
                    'id' => $serviceId,
                    'group_id' => (int) $service['group_id'],
                    'name' => (string) $service['name'],
                    'duration' => (int) $service['duration_minutes'],
                    'price' => (float) $service['base_price'],
                    'variants' => array_values(array_filter(array_map(static fn (array $variant): string => (string) ($variant['variant_name'] ?? ''), $variantDetails))),
                    'variant_details' => $variantDetails,
                    'staff_ids' => $staffByService[$serviceId] ?? [],
                    'status' => (string) $service['status'],
                    'description' => (string) ($service['description'] ?? ''),
                    'audience' => is_array($audience) ? array_values($audience) : ['Women', 'Men'],
                    'image_data_url' => (string) ($service['image_data_url'] ?? ''),
                    'online_bookable' => (bool) ($service['online_bookable'] ?? true),
                    'commission_enabled' => (bool) ($service['commission_enabled'] ?? false),
                    'at_customer_location' => (bool) ($service['at_customer_location'] ?? false),
                    'extra_time_type' => (string) ($service['extra_time_type'] ?? 'none'),
                    'extra_time_minutes' => (int) ($service['extra_time_minutes'] ?? 0),
                ];
            }, $services);
        }

        return $this->baseData['services'];
    }

    public function getServiceGroups(): array
    {
        if ($this->usingDb()) {
            $this->ensureServiceCatalogSchema();
            $rows = $this->dbAll("SELECT id, name, description, color, image_data_url FROM service_groups ORDER BY id");

            return array_map(static function (array $group): array {
                return [
                    'id' => (int) $group['id'],
                    'name' => (string) $group['name'],
                    'description' => (string) ($group['description'] ?? ''),
                    'color' => (string) ($group['color'] ?? '#76b6e8'),
                    'image_data_url' => (string) ($group['image_data_url'] ?? ''),
                ];
            }, $rows);
        }

        return $this->baseData['service_groups'];
    }

    public function getPackages(): array
    {
        if ($this->usingDb()) {
            $this->ensureServiceCatalogSchema();
            $packages = $this->dbAll(
                "SELECT id, group_id, name, package_price, description, pricing_mode, discount_value, audience, image_data_url, items_json
                 FROM service_packages
                 ORDER BY id"
            );
            $itemRows = $this->dbAll(
                "SELECT spi.package_id, s.name
                 FROM service_package_items spi
                 JOIN services s ON s.id = spi.service_id
                 ORDER BY spi.package_id, s.name"
            );
            $itemsByPackage = [];
            foreach ($itemRows as $row) {
                $itemsByPackage[(int) $row['package_id']][] = (string) $row['name'];
            }

            return array_map(static function (array $package) use ($itemsByPackage): array {
                $decodedItems = json_decode((string) ($package['items_json'] ?? '[]'), true);
                $itemsDetail = is_array($decodedItems) ? $decodedItems : [];
                $items = $itemsDetail !== []
                    ? array_map(static function (array $item): string {
                        $name = (string) ($item['name'] ?? '');
                        $qty = max(1, (int) ($item['qty'] ?? 1));

                        return ($item['type'] ?? 'service') === 'product' && $qty > 1
                            ? $name . ' x' . $qty
                            : $name;
                    }, $itemsDetail)
                    : ($itemsByPackage[(int) $package['id']] ?? []);

                return [
                    'id' => (int) $package['id'],
                    'group_id' => $package['group_id'] !== null ? (int) $package['group_id'] : null,
                    'name' => (string) $package['name'],
                    'items' => $items,
                    'items_detail' => $itemsDetail,
                    'price' => (float) $package['package_price'],
                    'description' => (string) ($package['description'] ?? ''),
                    'pricing_mode' => (string) ($package['pricing_mode'] ?? 'service'),
                    'discount_value' => (float) ($package['discount_value'] ?? 0),
                    'audience' => (string) ($package['audience'] ?? 'all'),
                    'image_data_url' => (string) ($package['image_data_url'] ?? ''),
                ];
            }, $packages);
        }

        return $this->baseData['packages'];
    }

    public function getStaff(): array
    {
        if ($this->usingDb()) {
            $this->ensureStaffProfileSchema();
            $staffRows = $this->dbAll(
                "SELECT s.id, s.user_id, s.location_id, s.name, s.email, s.phone, s.role_title, s.status,
                        s.commission_type, s.commission_value, s.rating, l.name AS location_name,
                        s.gender, s.booking_enabled, s.agenda_color, s.started_working_on, s.ended_working_on,
                        s.public_title, s.notes, s.instagram_handle, s.photo_data_url, s.commission_rules,
                        s.attendance_pose, s.attendance_uploaded_pose
                 FROM staff s
                 LEFT JOIN locations l ON l.id = s.location_id
                 WHERE s.deleted_at IS NULL
                 ORDER BY s.id"
            );
            $skillRows = $this->dbAll(
                "SELECT ss.staff_id, ss.service_id, sv.name AS service_name
                 FROM staff_skills ss
                 JOIN services sv ON sv.id = ss.service_id
                 ORDER BY ss.staff_id, sv.name"
            );
            $specialtiesByStaff = [];
            $serviceIdsByStaff = [];
            foreach ($skillRows as $row) {
                $specialtiesByStaff[(int) $row['staff_id']][] = (string) $row['service_name'];
                $serviceIdsByStaff[(int) $row['staff_id']][] = (int) $row['service_id'];
            }

            return array_map(function (array $staff) use ($specialtiesByStaff, $serviceIdsByStaff): array {
                $staffId = (int) $staff['id'];
                $commissionRules = json_decode((string) ($staff['commission_rules'] ?? '[]'), true);

                return [
                    'id' => $staffId,
                    'user_id' => $staff['user_id'] !== null ? (int) $staff['user_id'] : null,
                    'location_id' => $staff['location_id'] !== null ? (int) $staff['location_id'] : null,
                    'name' => (string) $staff['name'],
                    'role' => (string) $staff['role_title'],
                    'email' => (string) ($staff['email'] ?? ''),
                    'phone' => (string) ($staff['phone'] ?? ''),
                    'status' => (string) $staff['status'],
                    'specialties' => $specialtiesByStaff[$staffId] ?? [],
                    'service_ids' => $serviceIdsByStaff[$staffId] ?? [],
                    'commission_type' => (string) $staff['commission_type'],
                    'commission_value' => (float) $staff['commission_value'],
                    'rating' => (float) $staff['rating'],
                    'location_name' => (string) ($staff['location_name'] ?: 'Star Salon'),
                    'gender' => (string) ($staff['gender'] ?? ''),
                    'booking_enabled' => (bool) ($staff['booking_enabled'] ?? true),
                    'agenda_color' => (string) ($staff['agenda_color'] ?? '#8cc9ff'),
                    'started_working_on' => (string) ($staff['started_working_on'] ?? ''),
                    'ended_working_on' => (string) ($staff['ended_working_on'] ?? ''),
                    'public_title' => (string) ($staff['public_title'] ?? ''),
                    'notes' => (string) ($staff['notes'] ?? ''),
                    'instagram_handle' => (string) ($staff['instagram_handle'] ?? ''),
                    'photo_data_url' => (string) ($staff['photo_data_url'] ?? ''),
                    'commission_rules' => is_array($commissionRules) ? $commissionRules : [],
                    'attendance_pose' => (string) ($staff['attendance_pose'] ?? 'Right Tilt'),
                    'attendance_uploaded_pose' => (string) ($staff['attendance_uploaded_pose'] ?? ''),
                    'permissions' => $this->staffPermissionsForList($staffId, (string) $staff['role_title']),
                ];
            }, $staffRows);
        }

        return $this->baseData['staff'];
    }

    public function getCustomers(): array
    {
        if ($this->usingDb()) {
            $this->ensureCustomerSchema();
            $hasBirthdate = $this->columnExists('customers', 'birthdate');
            $hasFamilyCard = $this->columnExists('customers', 'family_card_number');
            $hasPassport = $this->columnExists('customers', 'passport_number');
            $hasNotifyVia = $this->columnExists('customers', 'notify_via');
            $hasMarketingOptIn = $this->columnExists('customers', 'marketing_opt_in');
            $rows = $this->dbAll(
                'SELECT id, user_id, member_id, name, gender, phone, email, loyalty_points, last_visit_at, tags, status, notes, address'
                . ($hasBirthdate ? ', birthdate' : '')
                . ($hasFamilyCard ? ', family_card_number' : '')
                . ($hasPassport ? ', passport_number' : '')
                . ($hasNotifyVia ? ', notify_via' : '')
                . ($hasMarketingOptIn ? ', marketing_opt_in' : '')
                . ' FROM customers
                   WHERE deleted_at IS NULL
                   ORDER BY id'
            );

            return array_map(static function (array $customer) use ($hasBirthdate, $hasFamilyCard, $hasPassport, $hasNotifyVia, $hasMarketingOptIn): array {
                $tags = json_decode((string) ($customer['tags'] ?? '[]'), true);

                return [
                    'id' => (int) $customer['id'],
                    'user_id' => $customer['user_id'] !== null ? (int) $customer['user_id'] : null,
                    'name' => (string) $customer['name'],
                    'gender' => (string) ($customer['gender'] ?? ''),
                    'phone' => (string) ($customer['phone'] ?? ''),
                    'email' => (string) ($customer['email'] ?? ''),
                    'member_id' => (string) $customer['member_id'],
                    'loyalty_points' => (int) $customer['loyalty_points'],
                    'last_visit' => (string) ($customer['last_visit_at'] ?? ''),
                    'birthdate' => $hasBirthdate ? (string) ($customer['birthdate'] ?? '') : '',
                    'tags' => is_array($tags) ? array_values(array_map('strval', $tags)) : [],
                    'status' => (string) ($customer['status'] ?? 'Aktif'),
                    'notes' => (string) ($customer['notes'] ?? ''),
                    'address' => (string) ($customer['address'] ?? ''),
                    'family_card_number' => $hasFamilyCard ? (string) ($customer['family_card_number'] ?? '') : '',
                    'passport_number' => $hasPassport ? (string) ($customer['passport_number'] ?? '') : '',
                    'notify_via' => $hasNotifyVia ? (string) ($customer['notify_via'] ?? 'off') : 'off',
                    'marketing_opt_in' => $hasMarketingOptIn ? (bool) ($customer['marketing_opt_in'] ?? false) : false,
                ];
            }, $rows);
        }

        return $this->baseData['customers'];
    }

    public function getProducts(): array
    {
        if ($this->inventoryDbEnabled() && $this->tableExists('products')) {
            $hasSku = $this->columnExists('products', 'sku');
            $hasCode = $this->columnExists('products', 'code');
            $hasBrandName = $this->columnExists('products', 'brand');
            $hasCategoryName = $this->columnExists('products', 'category');
            $hasBrandId = $this->columnExists('products', 'brand_id') && $this->tableExists('brands');
            $hasCategoryId = $this->columnExists('products', 'category_id') && $this->tableExists('categories');
            $hasSupplierId = $this->columnExists('products', 'supplier_id') && $this->tableExists('suppliers');
            $hasSellPrice = $this->columnExists('products', 'sell_price');
            $hasSellingPrice = $this->columnExists('products', 'selling_price');
            $rows = $this->dbAll(
                'SELECT p.id, p.name, p.stock, p.status'
                . ($hasSku ? ', p.sku' : '')
                . ($hasCode ? ', p.code' : '')
                . ($hasBrandName ? ', p.brand AS brand_name' : '')
                . ($hasCategoryName ? ', p.category AS category_name' : '')
                . ($hasSellPrice ? ', p.sell_price' : '')
                . ($hasSellingPrice ? ', p.selling_price' : '')
                . ($hasBrandId ? ', b.name AS brand_name_lookup' : '')
                . ($hasCategoryId ? ', c.name AS category_name_lookup' : '')
                . ($hasSupplierId ? ', s.name AS supplier_name' : '')
                . ' FROM products p'
                . ($hasBrandId ? ' LEFT JOIN brands b ON b.id = p.brand_id' : '')
                . ($hasCategoryId ? ' LEFT JOIN categories c ON c.id = p.category_id' : '')
                . ($hasSupplierId ? ' LEFT JOIN suppliers s ON s.id = p.supplier_id' : '')
                . ' ORDER BY p.id'
            );

            return array_map(static function (array $product): array {
                $price = isset($product['sell_price'])
                    ? (float) $product['sell_price']
                    : (isset($product['selling_price']) ? (float) $product['selling_price'] : 0.0);
                $brand = (string) ($product['brand_name_lookup'] ?? $product['brand_name'] ?? '');
                $category = (string) ($product['category_name_lookup'] ?? $product['category_name'] ?? '');
                $sku = (string) ($product['sku'] ?? $product['code'] ?? '');

                return [
                    'id' => (int) $product['id'],
                    'name' => (string) $product['name'],
                    'brand' => $brand,
                    'category' => $category,
                    'supplier' => (string) ($product['supplier_name'] ?? ''),
                    'stock' => (int) $product['stock'],
                    'price' => $price,
                    'status' => (string) $product['status'],
                    'sku' => $sku,
                ];
            }, $rows);
        }

        return $this->baseData['products'];
    }

    public function getLocations(): array
    {
        if ($this->inventoryDbEnabled() && $this->tableExists('locations')) {
            $hasAddress = $this->columnExists('locations', 'address');
            $hasIsActive = $this->columnExists('locations', 'is_active');
            $rows = $this->dbAll(
                'SELECT id, name'
                . ($hasAddress ? ', address' : '')
                . ($hasIsActive ? ', is_active' : '')
                . ' FROM locations'
                . ($hasIsActive ? ' WHERE is_active = 1' : '')
                . ' ORDER BY id'
            );

            if ($rows !== []) {
                return array_map(static function (array $location) use ($hasAddress, $hasIsActive): array {
                    return [
                        'id' => (int) $location['id'],
                        'name' => (string) $location['name'],
                        'address' => $hasAddress ? (string) ($location['address'] ?? '') : '',
                        'is_active' => $hasIsActive ? (bool) ($location['is_active'] ?? true) : true,
                    ];
                }, $rows);
            }
        }

        return [[
            'id' => 1,
            'name' => 'Star Salon',
            'address' => (string) ($this->config['business']['address'] ?? ''),
            'is_active' => true,
        ]];
    }

    public function inventoryPayload(): array
    {
        if ($this->inventoryDbEnabled()) {
            $this->ensureInventoryWorkflowSchema();
            $this->ensureInventoryWorkflowSeedData();
        }

        $products = $this->getProducts();

        return [
            'products' => $products,
            'inventoryLocations' => $this->getLocations(),
            'inventoryBrands' => $this->getInventoryBrands(),
            'inventoryCategories' => $this->getInventoryCategories(),
            'inventorySuppliers' => $this->getInventorySuppliers(),
            'supplierMeta' => $this->inventorySupplierMeta(),
            'purchaseRows' => $this->inventoryPurchaseRows(),
            'opnameRows' => $this->inventoryOpnameRows(),
            'opnameDetailProducts' => $this->inventoryOpnameDetailProducts($products),
        ];
    }

    public function getVouchers(): array
    {
        if ($this->usingDb()) {
            $this->ensureVoucherCatalogSchema();
            $rows = $this->dbAll(
                "SELECT id, voucher_type, name, code, value, price_value, usage_limit, used_count, expired_at, status,
                        location_name, message_text, service_items_json, combine_quantity, max_quantity, expiry_mode, expiry_value
                 FROM vouchers
                 WHERE deleted_at IS NULL
                 ORDER BY id DESC"
            );

            return array_map(fn (array $voucher): array => $this->mapVoucherRecord($voucher), $rows);
        }

        return array_map(fn (array $voucher): array => $this->mapDemoVoucherRecord($voucher), $this->baseData['vouchers']);
    }

    public function getVoucherDiscounts(): array
    {
        if ($this->usingDb()) {
            $this->ensureVoucherCatalogSchema();
            $rows = $this->dbAll(
                "SELECT id, name, mode, amount_value, max_discount_value, scopes_json, status
                 FROM voucher_discounts
                 WHERE deleted_at IS NULL
                 ORDER BY id DESC"
            );

            return array_map(fn (array $discount): array => $this->mapVoucherDiscountRecord($discount), $rows);
        }

        return [
            [
                'id' => 1,
                'name' => 'Diskon 20%',
                'mode' => 'percent',
                'amount_label' => '20.00 %',
                'amount_value' => '20',
                'max_discount' => 'Rp 0,00',
                'max_discount_value' => '0',
                'applies_to' => ['Penjualan Service', 'Penjualan Kelas', 'Penjualan Produk', 'Penjualan voucher', 'Total Penjualan'],
                'search' => 'diskon 20 20',
            ],
        ];
    }

    public function getClasses(): array
    {
        if ($this->usingDb()) {
            $classRows = $this->dbAll("SELECT id, staff_id, name, description FROM classes ORDER BY id");
            $sessionRows = $this->dbAll(
                "SELECT cs.class_id, cs.start_at, cs.total_slot, COUNT(cb.id) AS booked_count
                 FROM class_sessions cs
                 LEFT JOIN class_bookings cb ON cb.class_session_id = cs.id AND cb.status <> 'cancelled'
                 GROUP BY cs.id, cs.class_id, cs.start_at, cs.total_slot
                 ORDER BY cs.start_at"
            );
            $sessionByClass = [];
            foreach ($sessionRows as $row) {
                $classId = (int) $row['class_id'];
                if (!isset($sessionByClass[$classId])) {
                    $sessionByClass[$classId] = $row;
                }
            }

            return array_map(static function (array $class) use ($sessionByClass): array {
                $session = $sessionByClass[(int) $class['id']] ?? null;

                return [
                    'id' => (int) $class['id'],
                    'name' => (string) $class['name'],
                    'schedule' => (string) ($session['start_at'] ?? date('Y-m-d H:i:s')),
                    'slot' => (int) ($session['total_slot'] ?? 0),
                    'staff_id' => $class['staff_id'] !== null ? (int) $class['staff_id'] : null,
                    'booked' => (int) ($session['booked_count'] ?? 0),
                    'description' => (string) ($class['description'] ?? ''),
                ];
            }, $classRows);
        }

        return $this->baseData['classes'];
    }

    public function getReviews(): array
    {
        if ($this->usingDb()) {
            if (!$this->tableExists('reviews') || !$this->tableExists('customers')) {
                return [];
            }

            $joinUsers = $this->tableExists('users');
            $rows = $this->dbAll(
                "SELECT r.id, r.booking_id, c.name AS customer_name, c.email AS customer_email, r.rating, r.feedback, r.created_at"
                . ($joinUsers ? ", u.email AS user_email" : ", NULL AS user_email")
                . " FROM reviews r
                 JOIN customers c ON c.id = r.customer_id
                " . ($joinUsers ? "LEFT JOIN users u ON u.id = c.user_id" : "") . "
                 ORDER BY r.created_at DESC"
            );

            return array_map(static function (array $review): array {
                return [
                    'id' => (int) $review['id'],
                    'customer' => (string) $review['customer_name'],
                    'email' => (string) (($review['user_email'] ?? '') ?: ($review['customer_email'] ?? '')),
                    'rating' => (int) $review['rating'],
                    'feedback' => (string) ($review['feedback'] ?? ''),
                    'date' => (string) $review['created_at'],
                    'agenda' => 'Review Booking #' . (int) ($review['booking_id'] ?? 0),
                ];
            }, $rows);
        }

        return $this->baseData['reviews'];
    }

    public function getLogs(): array
    {
        if ($this->usingDb()) {
            if (!$this->tableExists('activity_logs')) {
                return [];
            }

            $rows = $this->dbAll(
                "SELECT id, actor_name, action_text, created_at
                 FROM activity_logs
                 ORDER BY created_at DESC"
            );

            return array_map(static function (array $log): array {
                return [
                    'id' => (int) $log['id'],
                    'time' => (string) $log['created_at'],
                    'actor' => (string) $log['actor_name'],
                    'action' => (string) $log['action_text'],
                ];
            }, $rows);
        }

        $logs = array_merge($this->baseData['activity_logs'], $_SESSION['starstyle']['activity_logs']);
        usort($logs, fn (array $a, array $b): int => strcmp($b['time'], $a['time']));

        return $logs;
    }

    public function getSettings(): array
    {
        if ($this->usingDb()) {
            $this->ensureBusinessSettingsSchema();
            $row = $this->dbOne(
                "SELECT business_name, business_hours, address, booking_advance_days, loyalty_ratio, currency, notification_channel, timezone, hours_schedule_json
                 FROM business_settings
                 ORDER BY id ASC
                 LIMIT 1"
            );

            $schedule = $this->normalizeBusinessHoursSchedule(json_decode((string) ($row['hours_schedule_json'] ?? '[]'), true));

            return [
                'business_name' => (string) ($row['business_name'] ?? $this->config['business']['name']),
                'hours' => (string) ($row['business_hours'] ?? $this->summarizeBusinessHoursSchedule($schedule)),
                'address' => (string) ($row['address'] ?? $this->config['business']['address']),
                'booking_advance_days' => (int) ($row['booking_advance_days'] ?? 30),
                'loyalty_ratio' => (int) ($row['loyalty_ratio'] ?? 10000),
                'currency' => (string) ($row['currency'] ?? 'IDR'),
                'notification_channel' => (string) ($row['notification_channel'] ?? ''),
                'timezone' => (string) ($row['timezone'] ?? ($this->config['timezone'] ?? 'Asia/Bangkok')),
                'hours_schedule' => $schedule,
            ];
        }

        $settings = $_SESSION['starstyle']['settings'] ?? $this->baseData['settings'];
        $settings['timezone'] = (string) ($settings['timezone'] ?? ($this->config['timezone'] ?? 'Asia/Bangkok'));
        $settings['hours_schedule'] = $this->normalizeBusinessHoursSchedule($settings['hours_schedule'] ?? null);
        $settings['hours'] = (string) ($settings['hours'] ?? $this->summarizeBusinessHoursSchedule($settings['hours_schedule']));

        return $settings;
    }

    public function getNotifications(): array
    {
        if ($this->usingDb()) {
            if (!$this->tableExists('notifications')) {
                return [];
            }

            $joinUsers = $this->tableExists('users');
            $rows = $this->dbAll(
                "SELECT n.id, n.title, n.type, n.is_read, n.created_at"
                . ($joinUsers ? ", u.name AS recipient_name, u.email AS recipient_email" : ", NULL AS recipient_name, NULL AS recipient_email")
                . " FROM notifications n
                " . ($joinUsers ? "LEFT JOIN users u ON u.id = n.user_id" : "") . "
                 ORDER BY n.created_at DESC"
            );

            return array_map(static function (array $notification): array {
                $type = (string) ($notification['type'] ?? 'notification');

                return [
                    'id' => (int) $notification['id'],
                    'title' => (string) ($notification['title'] ?? ''),
                    'type' => $type,
                    'type_label' => ucfirst($type),
                    'customer' => (string) (($notification['recipient_name'] ?? '') ?: 'Pelanggan'),
                    'email' => (string) (($notification['recipient_email'] ?? '') ?: '-'),
                    'agenda' => 'Notification',
                    'is_read' => (bool) ($notification['is_read'] ?? false),
                    'created_at' => (string) ($notification['created_at'] ?? ''),
                ];
            }, $rows);
        }

        return $this->baseData['notifications'];
    }

    public function getAttendance(): array
    {
        if ($this->usingDb()) {
            $this->ensureStaffScheduleSchema();
            $this->ensureStaffScheduleSeedData();

            $rows = $this->dbAll(
                "SELECT staff_id, attendance_date, shift_start, shift_end, clock_in, clock_out, source, status, selfie_in_score, selfie_out_score
                 FROM staff_attendance
                 ORDER BY attendance_date DESC, id DESC"
            );
            $latestByStaff = [];
            foreach ($rows as $row) {
                $staffId = (int) $row['staff_id'];
                if (!isset($latestByStaff[$staffId])) {
                    $latestByStaff[$staffId] = [
                        'staff_id' => $staffId,
                        'date' => (string) $row['attendance_date'],
                        'shift_start' => substr((string) $row['shift_start'], 0, 5),
                        'shift_end' => substr((string) $row['shift_end'], 0, 5),
                        'clock_in' => substr((string) $row['clock_in'], 0, 5),
                        'clock_out' => substr((string) $row['clock_out'], 0, 5),
                        'source' => (string) ($row['source'] ?? '-'),
                        'status' => (string) ($row['status'] ?? 'Ontime'),
                        'selfie_in_score' => (float) ($row['selfie_in_score'] ?? 0),
                        'selfie_out_score' => (float) ($row['selfie_out_score'] ?? 0),
                    ];
                }
            }

            return array_values($latestByStaff);
        }

        return $this->baseData['attendance'];
    }

    public function getShifts(): array
    {
        if ($this->usingDb()) {
            $this->ensureStaffScheduleSchema();
            $this->ensureStaffScheduleSeedData();

            return array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'staff_id' => (int) $row['staff_id'],
                    'date' => (string) $row['shift_date'],
                    'start' => substr((string) $row['start_time'], 0, 5),
                    'end' => substr((string) $row['end_time'], 0, 5),
                    'repeat_mode' => (string) ($row['repeat_mode'] ?? 'none'),
                ];
            }, $this->dbAll(
                "SELECT id, staff_id, shift_date, start_time, end_time, repeat_mode
                 FROM staff_shifts
                 ORDER BY shift_date ASC, staff_id ASC, id ASC"
            ));
        }

        return $this->baseData['shifts'];
    }

    public function getBookings(): array
    {
        if ($this->usingDb()) {
            $this->ensureBookingSchema();
            $bookingRows = $this->dbAll(
                "SELECT id, location_id, customer_id, staff_id, reference, channel, start_at, end_at, status, notes, cancel_reason, products_json, payment_method, payment_proof_path, payment_review_status, created_at, updated_at
                 FROM bookings
                 ORDER BY start_at ASC"
            );
            $itemRows = $this->dbAll(
                "SELECT booking_id, service_id, staff_id, duration_minutes, price, start_at, end_at, resource_id, resource_name
                 FROM booking_items
                 ORDER BY booking_id, id"
            );
            $itemsByBooking = [];
            foreach ($itemRows as $row) {
                $bookingId = (int) $row['booking_id'];
                $itemsByBooking[$bookingId][] = [
                    'service_id' => (int) $row['service_id'],
                    'staff_id' => isset($row['staff_id']) ? (int) $row['staff_id'] : 0,
                    'duration' => (int) $row['duration_minutes'],
                    'price' => (float) $row['price'],
                    'start_at' => (string) ($row['start_at'] ?? ''),
                    'end_at' => (string) ($row['end_at'] ?? ''),
                    'resource_id' => (string) ($row['resource_id'] ?? ''),
                    'resource_name' => (string) ($row['resource_name'] ?? ''),
                ];
            }

            return array_map(function (array $booking) use ($itemsByBooking): array {
                $bookingId = (int) $booking['id'];
                $cursor = new \DateTimeImmutable((string) $booking['start_at']);
                $serviceItems = [];
                $serviceIds = [];
                foreach ($itemsByBooking[$bookingId] ?? [] as $item) {
                    $serviceStart = !empty($item['start_at'])
                        ? new \DateTimeImmutable((string) $item['start_at'])
                        : $cursor;
                    $serviceEnd = !empty($item['end_at'])
                        ? new \DateTimeImmutable((string) $item['end_at'])
                        : $serviceStart->modify('+' . $item['duration'] . ' minutes');
                    $serviceItems[] = [
                        'service_id' => $item['service_id'],
                        'staff_id' => (int) ($item['staff_id'] ?: $booking['staff_id']),
                        'start_at' => $serviceStart->format('Y-m-d H:i:s'),
                        'end_at' => $serviceEnd->format('Y-m-d H:i:s'),
                        'duration' => $item['duration'],
                        'price' => $item['price'],
                        'resource_id' => (string) ($item['resource_id'] ?? ''),
                        'resource_name' => (string) ($item['resource_name'] ?? ''),
                    ];
                    $serviceIds[] = $item['service_id'];
                    $cursor = $serviceEnd;
                }

                return [
                    'id' => $bookingId,
                    'reference' => (string) $booking['reference'],
                    'customer_id' => (int) $booking['customer_id'],
                    'staff_id' => (int) $booking['staff_id'],
                    'service_ids' => $serviceIds,
                    'service_items' => $serviceItems,
                    'start_at' => (string) $booking['start_at'],
                    'end_at' => (string) $booking['end_at'],
                    'status' => (string) $booking['status'],
                    'channel' => (string) ($booking['channel'] ?? ''),
                    'notes' => (string) ($booking['notes'] ?? ''),
                    'cancel_reason' => (string) ($booking['cancel_reason'] ?? ''),
                    'products' => $this->normalizeStoredBookingProducts((string) ($booking['products_json'] ?? '[]')),
                    'payment_method' => (string) ($booking['payment_method'] ?? ''),
                    'payment_proof_path' => (string) ($booking['payment_proof_path'] ?? ''),
                    'payment_proof_url' => $this->paymentProofDataUrl((string) ($booking['payment_proof_path'] ?? '')),
                    'payment_review_status' => $this->normalizeBookingPaymentReviewStatus((string) ($booking['payment_review_status'] ?? 'waiting_admin')),
                    'updated_at' => (string) ($booking['updated_at'] ?? $booking['created_at'] ?? $booking['start_at']),
                ];
            }, $bookingRows);
        }

        $bookings = array_merge($this->baseData['bookings'], $_SESSION['starstyle']['bookings']);
        usort($bookings, fn (array $a, array $b): int => strcmp($a['start_at'], $b['start_at']));

        return array_map(function (array $booking): array {
            $booking['payment_method'] = (string) ($booking['payment_method'] ?? '');
            $booking['payment_proof_path'] = (string) ($booking['payment_proof_path'] ?? '');
            $booking['payment_proof_url'] = $this->paymentProofDataUrl((string) ($booking['payment_proof_path'] ?? ''));
            $booking['payment_review_status'] = $this->normalizeBookingPaymentReviewStatus((string) ($booking['payment_review_status'] ?? 'waiting_admin'));

            return $booking;
        }, $bookings);
    }

    public function getBlocks(): array
    {
        if ($this->usingDb()) {
            return array_map(static function (array $block): array {
                return [
                    'id' => (int) $block['id'],
                    'staff_id' => (int) $block['staff_id'],
                    'title' => (string) $block['title'],
                    'start_at' => (string) $block['start_at'],
                    'end_at' => (string) $block['end_at'],
                    'description' => (string) ($block['description'] ?? ''),
                ];
            }, $this->dbAll(
                "SELECT id, staff_id, title, start_at, end_at, description
                 FROM booking_blocks
                 ORDER BY start_at ASC"
            ));
        }

        return array_merge($this->baseData['booking_blocks'], $_SESSION['starstyle']['booking_blocks']);
    }

    public function getTransactions(): array
    {
        if ($this->usingDb()) {
            $transactionRows = $this->dbAll(
                "SELECT id, booking_id, customer_id, staff_id, reference, payment_method, status,
                        discount_amount, rounding_amount, paid_at
                 FROM transactions
                 ORDER BY paid_at DESC"
            );
            $itemRows = $this->dbAll(
                "SELECT transaction_id, item_type, item_name, quantity, price
                 FROM transaction_items
                 ORDER BY transaction_id, id"
            );
            $itemsByTransaction = [];
            foreach ($itemRows as $row) {
                $itemsByTransaction[(int) $row['transaction_id']][] = [
                    'type' => (string) $row['item_type'],
                    'name' => (string) $row['item_name'],
                    'qty' => (int) $row['quantity'],
                    'price' => (float) $row['price'],
                ];
            }

            return array_map(static function (array $transaction) use ($itemsByTransaction): array {
                return [
                    'id' => (int) $transaction['id'],
                    'reference' => (string) $transaction['reference'],
                    'booking_id' => $transaction['booking_id'] !== null ? (int) $transaction['booking_id'] : null,
                    'customer_id' => (int) $transaction['customer_id'],
                    'staff_id' => (int) $transaction['staff_id'],
                    'date' => (string) $transaction['paid_at'],
                    'items' => $itemsByTransaction[(int) $transaction['id']] ?? [],
                    'discount' => (float) $transaction['discount_amount'],
                    'rounding' => (float) $transaction['rounding_amount'],
                    'status' => (string) $transaction['status'],
                    'payment_method' => (string) $transaction['payment_method'],
                ];
            }, $transactionRows);
        }

        $transactions = array_merge($this->baseData['transactions'], $_SESSION['starstyle']['transactions']);
        usort($transactions, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        return $transactions;
    }

    public function dashboard(string $range = '7d'): array
    {
        $days = match ($range) {
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };

        $start = new \DateTimeImmutable("-{$days} days");
        $transactions = array_filter($this->getTransactions(), fn (array $transaction): bool => new \DateTimeImmutable($transaction['date']) >= $start);
        $bookings = array_filter($this->getBookings(), fn (array $booking): bool => new \DateTimeImmutable($booking['start_at']) >= $start);

        $salesTotal = array_reduce($transactions, function (float $carry, array $transaction): float {
            $items = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['qty'] * $item['price']), 0.0);

            return $carry + $items - $transaction['discount'] + $transaction['rounding'];
        }, 0.0);

        $agendaValue = array_reduce($bookings, function (float $carry, array $booking): float {
            $serviceTotal = array_reduce($booking['service_ids'], function (float $sum, int $serviceId): float {
                $service = $this->findService($serviceId);

                return $sum + ($service['price'] ?? 0);
            }, 0.0);

            return $carry + $serviceTotal;
        }, 0.0);

        $dailySales = [];
        $dailyAgenda = [];

        for ($index = $days - 1; $index >= 0; $index--) {
            $date = (new \DateTimeImmutable("-{$index} days"))->format('Y-m-d');
            $dailySales[$date] = 0;
            $dailyAgenda[$date] = 0;
        }

        foreach ($transactions as $transaction) {
            $date = substr($transaction['date'], 0, 10);
            $dailySales[$date] = ($dailySales[$date] ?? 0) + array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['qty'] * $item['price']), 0.0);
        }

        foreach ($bookings as $booking) {
            $date = substr($booking['start_at'], 0, 10);
            $dailyAgenda[$date] = ($dailyAgenda[$date] ?? 0) + 1;
        }

        $topClasses = array_map(fn (array $class): array => ['label' => $class['name'], 'value' => $class['booked']], $this->getClasses());

        return [
            'cards' => [
                'sales_total' => $salesTotal,
                'agenda_value' => $agendaValue,
                'confirmed' => count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'confirmed')),
                'cancelled' => count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'cancelled')),
            ],
            'chart' => [
                'labels' => array_keys($dailySales),
                'sales' => array_values($dailySales),
                'agenda' => array_values($dailyAgenda),
            ],
            'upcoming' => array_slice(array_values(array_filter($bookings, fn (array $booking): bool => in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true))), 0, 5),
            'recent' => array_slice(array_reverse($this->getBookings()), 0, 5),
            'top' => [
                'services' => array_slice($this->rankItems('service'), 0, 5),
                'products' => array_slice($this->rankItems('product'), 0, 5),
                'classes' => array_slice($topClasses, 0, 5),
                'staff' => array_slice($this->rankStaff(), 0, 5),
            ],
        ];
    }

    public function calendar(?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');

        return [
            'date' => $date,
            'staff' => $this->getStaff(),
            'events' => array_values(array_filter($this->getBookings(), fn (array $booking): bool => str_starts_with($booking['start_at'], $date))),
            'blocks' => array_values(array_filter($this->getBlocks(), fn (array $block): bool => str_starts_with($block['start_at'], $date))),
            'now' => date('Y-m-d') === $date ? date('H:i') : null,
        ];
    }

    public function calendarPagePayload(string $date): array
    {
        $calendar = $this->calendar($date);
        $services = $this->getServices();
        $packages = $this->getPackages();
        $products = $this->getProducts();
        $vouchers = $this->getVouchers();
        $customers = $this->getCustomers();
        $locations = $this->getLocations();
        $discounts = $this->getVoucherDiscounts();

        return [
            'calendar' => $calendar,
            'services' => $services,
            'customers' => $customers,
            'locations' => $locations,
            'calendarResources' => $this->calendarResourceRows($locations),
            'calendarDiscounts' => $this->calendarDiscountRows($discounts),
            'calendarOwnedVouchers' => $this->calendarOwnedVoucherRows($customers, $vouchers),
            'calendarSalesCatalogs' => [
                'services' => $this->calendarSalesServiceRows($services),
                'packages' => $this->calendarSalesPackageRows($packages, $services),
                'products' => $this->calendarSalesProductRows($products),
                'vouchers' => $this->calendarSalesVoucherRows($vouchers),
                'plans' => [],
                'payable' => $this->calendarPayableRows(),
            ],
        ];
    }

    public function availability(array $serviceIds, int $staffId, string $date): array
    {
        $totalDuration = array_reduce($serviceIds, function (int $carry, int $serviceId): int {
            $service = $this->findService($serviceId);

            return $carry + (int) ($service['duration'] ?? 0);
        }, 0);

        $bookings = array_filter($this->getBookings(), fn (array $booking): bool => $booking['staff_id'] === $staffId && str_starts_with($booking['start_at'], $date) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true));
        $blocks = array_filter($this->getBlocks(), fn (array $block): bool => $block['staff_id'] === $staffId && str_starts_with($block['start_at'], $date));

        $slots = [];

        for ($hour = 9; $hour < 20; $hour++) {
            foreach ([0, 15, 30, 45] as $minute) {
                $start = new \DateTimeImmutable(sprintf('%s %02d:%02d:00', $date, $hour, $minute));
                $end = $start->modify("+{$totalDuration} minutes");

                if ((int) $end->format('H') >= 21 && (int) $end->format('i') > 0) {
                    continue;
                }

                $slots[] = [
                    'time' => $start->format('H:i'),
                    'available' => !$this->hasOverlap($start, $end, $bookings, $blocks),
                ];
            }
        }

        return $slots;
    }

    public function createBooking(array $payload, string $source = 'customer'): array
    {
        $serviceIds = array_map('intval', $payload['service_ids'] ?? []);
        $serviceStartTimes = array_values($payload['service_start_times'] ?? []);
        $serviceDurations = array_values($payload['service_durations'] ?? []);
        $serviceStaffIds = array_values($payload['service_staff_ids'] ?? []);
        $serviceResources = array_values($payload['service_resources'] ?? []);
        $servicePrices = array_values($payload['service_prices'] ?? []);
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $date = trim((string) ($payload['date'] ?? ''));
        $time = trim((string) ($payload['time'] ?? ''));
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $customerPhone = trim((string) ($payload['customer_phone'] ?? ''));
        $customerEmail = trim((string) ($payload['customer_email'] ?? ''));
        $bookingReference = trim((string) ($payload['booking_reference'] ?? ''));
        $notes = trim((string) ($payload['notes'] ?? ''));
        $paymentMethod = trim((string) ($payload['payment_method'] ?? ''));
        $paymentProofPath = trim((string) ($payload['payment_proof_path'] ?? ''));
        $paymentReviewStatus = $this->normalizeBookingPaymentReviewStatus((string) ($payload['payment_review_status'] ?? 'waiting_admin'));

        if ($serviceIds === [] || $staffId === 0 || $date === '' || $time === '' || $customerName === '') {
            return ['success' => false, 'message' => 'Mohon lengkapi layanan, staff, tanggal, dan data pelanggan.'];
        }

        $duration = array_reduce($serviceIds, fn (int $carry, int $serviceId): int => $carry + ((int) ($this->findService($serviceId)['duration'] ?? 0)), 0);
        $start = new \DateTimeImmutable("{$date} {$time}:00");
        $end = $start->modify("+{$duration} minutes");
        $dailyBookings = array_filter($this->getBookings(), fn (array $booking): bool => $booking['staff_id'] === $staffId
            && str_starts_with($booking['start_at'], $date)
            && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true)
            && ((string) ($booking['reference'] ?? '') !== $bookingReference));
        $blocks = array_filter($this->getBlocks(), fn (array $block): bool => $block['staff_id'] === $staffId && str_starts_with($block['start_at'], $date));

        if ($this->hasOverlap($start, $end, $dailyBookings, $blocks)) {
            return ['success' => false, 'message' => 'Slot bentrok dengan booking lain atau blocked time.'];
        }

        $serviceItems = [];
        $serviceCursor = $start;
        foreach ($serviceIds as $index => $serviceId) {
            $service = $this->findService($serviceId);
            $serviceDuration = max(5, (int) ($serviceDurations[$index] ?? $service['duration'] ?? 60));
            $serviceStartTime = trim((string) ($serviceStartTimes[$index] ?? ''));
            $serviceStaffId = (int) ($serviceStaffIds[$index] ?? $staffId);
            $serviceStaffId = $serviceStaffId > 0 ? $serviceStaffId : $staffId;
            $serviceStart = $serviceStartTime !== ''
                ? new \DateTimeImmutable("{$date} {$serviceStartTime}:00")
                : $serviceCursor;
            $serviceEnd = $serviceStart->modify("+{$serviceDuration} minutes");
            $servicePrice = isset($servicePrices[$index])
                ? (float) $servicePrices[$index]
                : (float) ($service['price'] ?? 0);
            $resourceName = trim((string) ($serviceResources[$index] ?? ''));

            $serviceItems[] = [
                'service_id' => $serviceId,
                'staff_id' => $serviceStaffId,
                'start_at' => $serviceStart->format('Y-m-d H:i:s'),
                'end_at' => $serviceEnd->format('Y-m-d H:i:s'),
                'duration' => $serviceDuration,
                'price' => $servicePrice,
                'resource_id' => $resourceName,
                'resource_name' => $resourceName,
            ];

            if ($serviceStartTime === '') {
                $serviceCursor = $serviceEnd;
            }
        }

        $customerId = $this->resolveCustomer($customerName, $customerPhone);
        $this->syncCustomerContact($customerId, $customerName, $customerPhone, $customerEmail);

        if ($this->usingDb()) {
            $this->ensureBookingSchema();
            $existingBooking = $bookingReference !== '' ? $this->bookingReferenceLookup($bookingReference) : null;
            $reference = $existingBooking['reference'] ?? ('BK-' . date('ymdHis'));
            $channel = (string) ($existingBooking['channel'] ?? ($source === 'customer' ? 'Portal Customer' : 'Internal'));
            $status = (string) ($existingBooking['status'] ?? 'new');
            $productsJson = $existingBooking['products_json'] ?? '[]';
            $cancelReason = $existingBooking['cancel_reason'] ?? null;
            $storedPaymentMethod = (string) ($existingBooking['payment_method'] ?? $paymentMethod);
            $storedPaymentProofPath = (string) ($existingBooking['payment_proof_path'] ?? $paymentProofPath);
            $storedPaymentReviewStatus = $this->normalizeBookingPaymentReviewStatus((string) ($existingBooking['payment_review_status'] ?? $paymentReviewStatus));

            $this->pdo()->beginTransaction();
            try {
                if ($existingBooking !== null) {
                    $bookingId = (int) $existingBooking['id'];
                    $this->dbExecute(
                        "UPDATE bookings
                         SET customer_id = :customer_id,
                             staff_id = :staff_id,
                             channel = :channel,
                             start_at = :start_at,
                             end_at = :end_at,
                             notes = :notes,
                             cancel_reason = :cancel_reason,
                             products_json = :products_json,
                             payment_method = :payment_method,
                             payment_proof_path = :payment_proof_path,
                             payment_review_status = :payment_review_status,
                             updated_at = NOW()
                         WHERE id = :id",
                        [
                            'id' => $bookingId,
                            'customer_id' => $customerId,
                            'staff_id' => $staffId,
                            'channel' => $channel,
                            'start_at' => $start->format('Y-m-d H:i:s'),
                            'end_at' => $end->format('Y-m-d H:i:s'),
                            'notes' => $notes !== '' ? $notes : null,
                            'cancel_reason' => $cancelReason !== '' ? $cancelReason : null,
                            'products_json' => $productsJson,
                            'payment_method' => $storedPaymentMethod !== '' ? $storedPaymentMethod : null,
                            'payment_proof_path' => $storedPaymentProofPath !== '' ? $storedPaymentProofPath : null,
                            'payment_review_status' => $storedPaymentReviewStatus,
                        ]
                    );
                    $this->dbExecute("DELETE FROM booking_items WHERE booking_id = :booking_id", ['booking_id' => $bookingId]);
                } else {
                    $this->dbExecute(
                        "INSERT INTO bookings (location_id, customer_id, staff_id, reference, channel, start_at, end_at, status, notes, cancel_reason, products_json, payment_method, payment_proof_path, payment_review_status, created_at, updated_at)
                         VALUES (NULL, :customer_id, :staff_id, :reference, :channel, :start_at, :end_at, :status, :notes, :cancel_reason, :products_json, :payment_method, :payment_proof_path, :payment_review_status, NOW(), NOW())",
                        [
                            'customer_id' => $customerId,
                            'staff_id' => $staffId,
                            'reference' => $reference,
                            'channel' => $channel,
                            'start_at' => $start->format('Y-m-d H:i:s'),
                            'end_at' => $end->format('Y-m-d H:i:s'),
                            'status' => $status,
                            'notes' => $notes !== '' ? $notes : null,
                            'cancel_reason' => $cancelReason !== '' ? $cancelReason : null,
                            'products_json' => $productsJson,
                            'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
                            'payment_proof_path' => $paymentProofPath !== '' ? $paymentProofPath : null,
                            'payment_review_status' => $paymentReviewStatus,
                        ]
                    );
                    $bookingId = (int) $this->pdo()->lastInsertId();
                }

                foreach ($serviceItems as $item) {
                    $this->dbExecute(
                        "INSERT INTO booking_items (booking_id, service_id, staff_id, duration_minutes, price, start_at, end_at, resource_id, resource_name)
                         VALUES (:booking_id, :service_id, :staff_id, :duration, :price, :start_at, :end_at, :resource_id, :resource_name)",
                        [
                            'booking_id' => $bookingId,
                            'service_id' => (int) $item['service_id'],
                            'staff_id' => (int) $item['staff_id'],
                            'duration' => (int) $item['duration'],
                            'price' => (float) ($item['price'] ?? $this->findService((int) $item['service_id'])['price'] ?? 0),
                            'start_at' => (string) $item['start_at'],
                            'end_at' => (string) $item['end_at'],
                            'resource_id' => (string) ($item['resource_id'] ?? ''),
                            'resource_name' => (string) ($item['resource_name'] ?? ''),
                        ]
                    );
                }

                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $source === 'customer' ? $customerName : 'Admin',
                        'action_text' => ($existingBooking !== null ? 'Memperbarui booking ' : 'Membuat booking ') . $reference,
                    ]
                );

                $this->pdo()->commit();

                $booking = $this->bookingByReference($reference);

                return [
                    'success' => true,
                    'message' => $existingBooking !== null ? 'Booking berhasil diperbarui.' : 'Booking berhasil dibuat.',
                    'booking' => $booking ?? [
                        'id' => $bookingId,
                        'reference' => $reference,
                        'customer_id' => $customerId,
                        'staff_id' => $staffId,
                        'service_ids' => $serviceIds,
                        'service_items' => $serviceItems,
                        'start_at' => $start->format('Y-m-d H:i:s'),
                        'end_at' => $end->format('Y-m-d H:i:s'),
                        'status' => $status,
                        'channel' => $channel,
                        'notes' => $notes,
                        'cancel_reason' => (string) $cancelReason,
                        'products' => $this->normalizeStoredBookingProducts((string) $productsJson),
                        'payment_method' => $storedPaymentMethod,
                        'payment_proof_path' => $storedPaymentProofPath,
                        'payment_proof_url' => $this->paymentProofDataUrl($storedPaymentProofPath),
                        'payment_review_status' => $storedPaymentReviewStatus,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                ];
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();

                return ['success' => false, 'message' => 'Gagal menyimpan booking ke database: ' . $throwable->getMessage()];
            }
        }

        $existingIndex = null;
        foreach ($_SESSION['starstyle']['bookings'] as $index => $bookingRow) {
            if ((string) ($bookingRow['reference'] ?? '') === $bookingReference) {
                $existingIndex = $index;
                break;
            }
        }
        $existingBooking = $existingIndex !== null ? ($_SESSION['starstyle']['bookings'][$existingIndex] ?? null) : null;
        $existingPaymentMethod = is_array($existingBooking) ? (string) ($existingBooking['payment_method'] ?? $paymentMethod) : $paymentMethod;
        $existingPaymentProofPath = is_array($existingBooking) ? (string) ($existingBooking['payment_proof_path'] ?? $paymentProofPath) : $paymentProofPath;
        $existingPaymentReviewStatus = is_array($existingBooking)
            ? $this->normalizeBookingPaymentReviewStatus((string) ($existingBooking['payment_review_status'] ?? $paymentReviewStatus))
            : $paymentReviewStatus;
        $id = (int) ($existingBooking['id'] ?? $this->nextId($_SESSION['starstyle']['bookings'], 9000));
        $booking = [
            'id' => $id,
            'reference' => (string) ($existingBooking['reference'] ?? ('BK-' . date('ymd') . '-' . $id)),
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_items' => $serviceItems,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'status' => (string) ($existingBooking['status'] ?? 'new'),
            'channel' => (string) ($existingBooking['channel'] ?? ($source === 'customer' ? 'Portal Customer' : 'Internal')),
            'notes' => $notes,
            'cancel_reason' => (string) ($existingBooking['cancel_reason'] ?? ''),
            'products' => $this->normalizeStoredBookingProducts($existingBooking['products'] ?? []),
            'payment_method' => $existingPaymentMethod,
            'payment_proof_path' => $existingPaymentProofPath,
            'payment_proof_url' => $this->paymentProofDataUrl($existingPaymentProofPath),
            'payment_review_status' => $existingPaymentReviewStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingIndex !== null) {
            $_SESSION['starstyle']['bookings'][$existingIndex] = $booking;
        } else {
            $_SESSION['starstyle']['bookings'][] = $booking;
        }
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $source === 'customer' ? $customerName : 'Admin',
            'action' => ($existingBooking !== null ? 'Memperbarui booking ' : 'Membuat booking ') . $booking['reference'],
        ];

        return ['success' => true, 'message' => $existingBooking !== null ? 'Booking berhasil diperbarui.' : 'Booking berhasil dibuat.', 'booking' => $booking];
    }

    public function updateBookingStatus(string $reference, string $status, string $actorName = 'Admin', string $reason = ''): array
    {
        $reference = trim($reference);
        $status = trim($status);
        if ($reference === '' || $status === '') {
            throw new \InvalidArgumentException('Referensi booking atau status tidak valid.');
        }

        $normalizedStatus = strtolower(str_replace(' ', '_', $status));
        $allowedStatuses = ['new', 'pending', 'confirmed', 'arrived', 'started', 'completed', 'cancelled', 'no_show'];
        if (!in_array($normalizedStatus, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Status booking tidak dikenali.');
        }

        if ($this->usingDb()) {
            $this->ensureBookingSchema();
            $booking = $this->bookingReferenceLookup($reference);
            if ($booking === null) {
                throw new \RuntimeException('Booking tidak ditemukan.');
            }

            $oldStatus = (string) ($booking['status'] ?? 'new');
            $cancelReason = $normalizedStatus === 'cancelled' ? trim($reason) : null;

            $this->pdo()->beginTransaction();
            try {
                $this->dbExecute(
                    "UPDATE bookings
                     SET status = :status,
                         cancel_reason = :cancel_reason,
                         updated_at = NOW()
                     WHERE id = :id",
                    [
                        'id' => (int) $booking['id'],
                        'status' => $normalizedStatus,
                        'cancel_reason' => $cancelReason !== '' ? $cancelReason : null,
                    ]
                );

                $this->dbExecute(
                    "INSERT INTO booking_status_logs (booking_id, old_status, new_status, note, created_at)
                     VALUES (:booking_id, :old_status, :new_status, :note, NOW())",
                    [
                        'booking_id' => (int) $booking['id'],
                        'old_status' => $oldStatus,
                        'new_status' => $normalizedStatus,
                        'note' => $cancelReason !== '' ? $cancelReason : null,
                    ]
                );

                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => sprintf('Mengubah status booking %s menjadi %s', $reference, strtoupper(str_replace('_', ' ', $normalizedStatus))),
                    ]
                );

                $this->pdo()->commit();
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();
                throw $throwable;
            }

            return $this->bookingByReference($reference) ?? [];
        }

        foreach ($_SESSION['starstyle']['bookings'] as $index => $booking) {
            if ((string) ($booking['reference'] ?? '') !== $reference) {
                continue;
            }

            $_SESSION['starstyle']['bookings'][$index]['status'] = $normalizedStatus;
            $_SESSION['starstyle']['bookings'][$index]['cancel_reason'] = $normalizedStatus === 'cancelled' ? trim($reason) : '';
            $_SESSION['starstyle']['bookings'][$index]['updated_at'] = date('Y-m-d H:i:s');

            return $_SESSION['starstyle']['bookings'][$index];
        }

        throw new \RuntimeException('Booking tidak ditemukan.');
    }

    public function updateBookingPaymentReviewStatus(string $reference, string $status, string $actorName = 'Admin'): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new \InvalidArgumentException('Referensi booking tidak valid.');
        }

        $normalizedStatus = $this->normalizeBookingPaymentReviewStatus($status);

        if ($this->usingDb()) {
            $this->ensureBookingSchema();
            $booking = $this->bookingReferenceLookup($reference);
            if ($booking === null) {
                throw new \RuntimeException('Booking tidak ditemukan.');
            }

            $this->dbExecute(
                "UPDATE bookings
                 SET payment_review_status = :payment_review_status,
                     updated_at = NOW()
                 WHERE id = :id",
                [
                    'id' => (int) $booking['id'],
                    'payment_review_status' => $normalizedStatus,
                ]
            );

            $this->dbExecute(
                "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                 VALUES (NULL, :actor_name, :action_text, NOW())",
                [
                    'actor_name' => $actorName,
                    'action_text' => sprintf('Mengubah verifikasi pembayaran booking %s menjadi %s', $reference, strtoupper(str_replace('_', ' ', $normalizedStatus))),
                ]
            );

            return $this->bookingByReference($reference) ?? [];
        }

        foreach ($_SESSION['starstyle']['bookings'] as $index => $booking) {
            if ((string) ($booking['reference'] ?? '') !== $reference) {
                continue;
            }

            $_SESSION['starstyle']['bookings'][$index]['payment_review_status'] = $normalizedStatus;
            $_SESSION['starstyle']['bookings'][$index]['updated_at'] = date('Y-m-d H:i:s');

            return $_SESSION['starstyle']['bookings'][$index];
        }

        throw new \RuntimeException('Booking tidak ditemukan.');
    }

    public function updateBookingProducts(string $reference, array $products, string $actorName = 'Admin'): array
    {
        $reference = trim($reference);
        if ($reference === '') {
            throw new \InvalidArgumentException('Referensi booking tidak valid.');
        }

        $normalizedProducts = $this->normalizeStoredBookingProducts($products);

        if ($this->usingDb()) {
            $this->ensureBookingSchema();
            $booking = $this->bookingReferenceLookup($reference);
            if ($booking === null) {
                throw new \RuntimeException('Booking tidak ditemukan.');
            }

            $this->dbExecute(
                "UPDATE bookings
                 SET products_json = :products_json,
                     updated_at = NOW()
                 WHERE id = :id",
                [
                    'id' => (int) $booking['id'],
                    'products_json' => json_encode($normalizedProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]
            );

            $this->dbExecute(
                "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                 VALUES (NULL, :actor_name, :action_text, NOW())",
                [
                    'actor_name' => $actorName,
                    'action_text' => sprintf('Memperbarui produk booking %s', $reference),
                ]
            );

            return $this->bookingByReference($reference) ?? [];
        }

        foreach ($_SESSION['starstyle']['bookings'] as $index => $booking) {
            if ((string) ($booking['reference'] ?? '') !== $reference) {
                continue;
            }

            $_SESSION['starstyle']['bookings'][$index]['products'] = $normalizedProducts;
            $_SESSION['starstyle']['bookings'][$index]['updated_at'] = date('Y-m-d H:i:s');

            return $_SESSION['starstyle']['bookings'][$index];
        }

        throw new \RuntimeException('Booking tidak ditemukan.');
    }

    public function createBlock(array $payload, array $actor): array
    {
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $date = trim((string) ($payload['date'] ?? ''));
        $startTime = trim((string) ($payload['start_time'] ?? ''));
        $endTime = trim((string) ($payload['end_time'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($staffId === 0 || $date === '' || $startTime === '' || $endTime === '' || $title === '' || $description === '') {
            return ['success' => false, 'message' => 'Mohon lengkapi staff, tanggal, jam mulai, jam selesai, dan deskripsi blokir waktu.'];
        }

        $start = new \DateTimeImmutable("{$date} {$startTime}:00");
        $end = new \DateTimeImmutable("{$date} {$endTime}:00");

        if ($end <= $start) {
            return ['success' => false, 'message' => 'Jam selesai harus lebih besar daripada jam mulai.'];
        }

        $dailyBookings = array_filter($this->getBookings(), fn (array $booking): bool => $booking['staff_id'] === $staffId && str_starts_with($booking['start_at'], $date) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true));
        $dailyBlocks = array_filter($this->getBlocks(), fn (array $block): bool => $block['staff_id'] === $staffId && str_starts_with($block['start_at'], $date));

        if ($this->hasOverlap($start, $end, $dailyBookings, $dailyBlocks)) {
            return ['success' => false, 'message' => 'Block time bentrok dengan booking atau block time lain.'];
        }

        if ($this->usingDb()) {
            try {
                $this->dbExecute(
                    "INSERT INTO booking_blocks (location_id, staff_id, title, start_at, end_at, description)
                     VALUES (NULL, :staff_id, :title, :start_at, :end_at, :description)",
                    [
                        'staff_id' => $staffId,
                        'title' => $title,
                        'start_at' => $start->format('Y-m-d H:i:s'),
                        'end_at' => $end->format('Y-m-d H:i:s'),
                        'description' => $description !== '' ? $description : null,
                    ]
                );
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (:user_id, :actor_name, :action_text, NOW())",
                    [
                        'user_id' => $actor['id'] ?? null,
                        'actor_name' => $actor['name'],
                        'action_text' => 'Membuat block time untuk staff ID #' . $staffId,
                    ]
                );
            } catch (\Throwable $throwable) {
                return ['success' => false, 'message' => 'Gagal menyimpan block time ke database: ' . $throwable->getMessage()];
            }

            return [
                'success' => true,
                'message' => 'Block time berhasil dibuat.',
                'block' => [
                    'staff_id' => $staffId,
                    'title' => $title,
                    'start_at' => $start->format('Y-m-d H:i:s'),
                    'end_at' => $end->format('Y-m-d H:i:s'),
                    'description' => $description,
                ],
            ];
        }

        $id = $this->nextId($_SESSION['starstyle']['booking_blocks'], 7000);
        $block = [
            'id' => $id,
            'staff_id' => $staffId,
            'title' => $title,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'description' => $description,
        ];

        $_SESSION['starstyle']['booking_blocks'][] = $block;
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actor['name'],
            'action' => 'Membuat block time untuk staff ID #' . $staffId,
        ];

        return ['success' => true, 'message' => 'Block time berhasil dibuat.', 'block' => $block];
    }

    public function updateBlock(array $payload, array $actor): array
    {
        $blockId = (int) ($payload['block_id'] ?? 0);
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $date = trim((string) ($payload['date'] ?? ''));
        $startTime = trim((string) ($payload['start_time'] ?? ''));
        $endTime = trim((string) ($payload['end_time'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($blockId === 0 || $staffId === 0 || $date === '' || $startTime === '' || $endTime === '' || $title === '' || $description === '') {
            return ['success' => false, 'message' => 'Mohon lengkapi staff, tanggal, jam mulai, jam selesai, dan deskripsi blokir waktu.'];
        }

        $start = new \DateTimeImmutable("{$date} {$startTime}:00");
        $end = new \DateTimeImmutable("{$date} {$endTime}:00");

        if ($end <= $start) {
            return ['success' => false, 'message' => 'Jam selesai harus lebih besar daripada jam mulai.'];
        }

        $dailyBookings = array_filter($this->getBookings(), fn (array $booking): bool => $booking['staff_id'] === $staffId && str_starts_with($booking['start_at'], $date) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true));
        $dailyBlocks = array_filter(
            $this->getBlocks(),
            fn (array $block): bool => (int) $block['id'] !== $blockId && $block['staff_id'] === $staffId && str_starts_with($block['start_at'], $date)
        );

        if ($this->hasOverlap($start, $end, $dailyBookings, $dailyBlocks)) {
            return ['success' => false, 'message' => 'Block time bentrok dengan booking atau block time lain.'];
        }

        if ($this->usingDb()) {
            try {
                $this->dbExecute(
                    "UPDATE booking_blocks
                     SET staff_id = :staff_id,
                         title = :title,
                         start_at = :start_at,
                         end_at = :end_at,
                         description = :description
                     WHERE id = :id",
                    [
                        'id' => $blockId,
                        'staff_id' => $staffId,
                        'title' => $title,
                        'start_at' => $start->format('Y-m-d H:i:s'),
                        'end_at' => $end->format('Y-m-d H:i:s'),
                        'description' => $description,
                    ]
                );
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (:user_id, :actor_name, :action_text, NOW())",
                    [
                        'user_id' => $actor['id'] ?? null,
                        'actor_name' => $actor['name'],
                        'action_text' => 'Mengubah block time #' . $blockId,
                    ]
                );
            } catch (\Throwable $throwable) {
                return ['success' => false, 'message' => 'Gagal mengubah block time: ' . $throwable->getMessage()];
            }

            return ['success' => true, 'message' => 'Block time berhasil diperbarui.'];
        }

        foreach ($_SESSION['starstyle']['booking_blocks'] as &$block) {
            if ((int) ($block['id'] ?? 0) !== $blockId) {
                continue;
            }

            $block['staff_id'] = $staffId;
            $block['title'] = $title;
            $block['start_at'] = $start->format('Y-m-d H:i:s');
            $block['end_at'] = $end->format('Y-m-d H:i:s');
            $block['description'] = $description;
            break;
        }
        unset($block);

        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actor['name'],
            'action' => 'Mengubah block time #' . $blockId,
        ];

        return ['success' => true, 'message' => 'Block time berhasil diperbarui.'];
    }

    public function deleteBlock(array $payload, array $actor): array
    {
        $blockId = (int) ($payload['block_id'] ?? 0);

        if ($blockId === 0) {
            return ['success' => false, 'message' => 'Block time tidak ditemukan.'];
        }

        if ($this->usingDb()) {
            try {
                $this->dbExecute("DELETE FROM booking_blocks WHERE id = :id", ['id' => $blockId]);
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (:user_id, :actor_name, :action_text, NOW())",
                    [
                        'user_id' => $actor['id'] ?? null,
                        'actor_name' => $actor['name'],
                        'action_text' => 'Menghapus block time #' . $blockId,
                    ]
                );
            } catch (\Throwable $throwable) {
                return ['success' => false, 'message' => 'Gagal menghapus block time: ' . $throwable->getMessage()];
            }

            return ['success' => true, 'message' => 'Block time berhasil dihapus.'];
        }

        $_SESSION['starstyle']['booking_blocks'] = array_values(array_filter(
            $_SESSION['starstyle']['booking_blocks'],
            fn (array $block): bool => (int) ($block['id'] ?? 0) !== $blockId
        ));
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actor['name'],
            'action' => 'Menghapus block time #' . $blockId,
        ];

        return ['success' => true, 'message' => 'Block time berhasil dihapus.'];
    }

    public function sales(): array
    {
        $transactions = $this->getTransactions();
        $gross = 0;
        $discount = 0;
        $refund = 0;

        foreach ($transactions as $transaction) {
            $lineTotal = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['qty'] * $item['price']), 0.0);
            $gross += $lineTotal;
            $discount += $transaction['discount'];
            if ($transaction['status'] === 'refund') {
                $refund += $lineTotal;
            }
        }

        return [
            'summary' => [
                'gross' => $gross,
                'net' => $gross - $discount - $refund,
                'discount' => $discount,
                'refund' => $refund,
            ],
            'bookings' => $this->getBookings(),
            'transactions' => $transactions,
            'services' => $this->getServices(),
            'products' => $this->getProducts(),
            'vouchers' => $this->getVouchers(),
            'cash_drawers' => $this->usingDb()
                ? array_map(static fn (array $drawer): array => [
                    'staff_id' => (int) $drawer['staff_id'],
                    'expected' => (float) $drawer['expected_amount'],
                    'actual' => (float) $drawer['actual_amount'],
                    'status' => (string) $drawer['status'],
                ], $this->dbAll("SELECT staff_id, expected_amount, actual_amount, status FROM cash_drawers ORDER BY open_date DESC"))
                : $this->baseData['cash_drawers'],
            'cash_movements' => $this->usingDb()
                ? array_map(static fn (array $movement): array => [
                    'date' => (string) $movement['created_at'],
                    'type' => (string) $movement['movement_type'],
                    'amount' => (float) $movement['amount'],
                    'note' => (string) ($movement['note'] ?? ''),
                ], $this->dbAll("SELECT created_at, movement_type, amount, note FROM cash_movements ORDER BY created_at DESC"))
                : $this->baseData['cash_movements'],
            'classes' => $this->getClasses(),
            'staff' => $this->getStaff(),
            'customers' => $this->getCustomers(),
        ];
    }

    public function checkout(array $payload, array $actor): array
    {
        $items = json_decode((string) ($payload['items_json'] ?? '[]'), true);
        $customerId = (int) ($payload['customer_id'] ?? 0);
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $voucherCode = trim((string) ($payload['voucher_code'] ?? ''));

        if (!is_array($items) || $items === [] || $customerId === 0 || $staffId === 0) {
            return ['success' => false, 'message' => 'Cart POS belum lengkap.'];
        }

        $calculation = $this->calculateCart($items, $voucherCode);

        if ($this->usingDb()) {
            $reference = 'TRX-' . date('ymdHis');
            $paymentMethod = (string) ($payload['payment_method'] ?? 'Cash');

            $this->pdo()->beginTransaction();
            try {
                $this->dbExecute(
                    "INSERT INTO transactions (booking_id, customer_id, staff_id, reference, payment_method, status, discount_amount, rounding_amount, paid_at)
                     VALUES (NULL, :customer_id, :staff_id, :reference, :payment_method, 'paid', :discount, 0, NOW())",
                    [
                        'customer_id' => $customerId,
                        'staff_id' => $staffId,
                        'reference' => $reference,
                        'payment_method' => $paymentMethod,
                        'discount' => (float) $calculation['discount'],
                    ]
                );
                $transactionId = (int) $this->pdo()->lastInsertId();

                foreach ($items as $item) {
                    $this->dbExecute(
                        "INSERT INTO transaction_items (transaction_id, item_type, item_name, quantity, price)
                         VALUES (:transaction_id, :item_type, :item_name, :quantity, :price)",
                        [
                            'transaction_id' => $transactionId,
                            'item_type' => (string) ($item['type'] ?? 'product'),
                            'item_name' => (string) ($item['name'] ?? 'Item'),
                            'quantity' => (int) ($item['qty'] ?? 1),
                            'price' => (float) ($item['price'] ?? 0),
                        ]
                    );
                }

                $invoiceNumber = 'INV-' . date('ymdHis');
                $this->dbExecute(
                    "INSERT INTO invoices (transaction_id, invoice_number, status, issued_at)
                     VALUES (:transaction_id, :invoice_number, 'paid', NOW())",
                    [
                        'transaction_id' => $transactionId,
                        'invoice_number' => $invoiceNumber,
                    ]
                );

                if ($voucherCode !== '') {
                    $voucher = $this->validateVoucher($voucherCode);
                    if ($voucher['valid'] && isset($voucher['voucher']['id'])) {
                        $this->dbExecute(
                            "UPDATE vouchers SET used_count = used_count + 1 WHERE id = :id",
                            ['id' => (int) $voucher['voucher']['id']]
                        );
                    }
                }

                $settings = $this->getSettings();
                $points = (int) floor(((float) $calculation['total']) / max(1, (int) ($settings['loyalty_ratio'] ?? 10000)));
                if ($points > 0) {
                    $this->dbExecute(
                        "INSERT INTO loyalty_ledgers (customer_id, transaction_id, points, type, note, created_at)
                         VALUES (:customer_id, :transaction_id, :points, 'earn', :note, NOW())",
                        [
                            'customer_id' => $customerId,
                            'transaction_id' => $transactionId,
                            'points' => $points,
                            'note' => 'Checkout transaksi ' . $reference,
                        ]
                    );
                    $this->dbExecute(
                        "UPDATE customers SET loyalty_points = loyalty_points + :points, last_visit_at = NOW() WHERE id = :id",
                        [
                            'points' => $points,
                            'id' => $customerId,
                        ]
                    );
                }

                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (:user_id, :actor_name, :action_text, NOW())",
                    [
                        'user_id' => $actor['id'] ?? null,
                        'actor_name' => $actor['name'],
                        'action_text' => 'Checkout transaksi ' . $reference,
                    ]
                );

                $this->pdo()->commit();

                return [
                    'success' => true,
                    'message' => 'Checkout berhasil diproses.',
                    'transaction' => [
                        'id' => $transactionId,
                        'reference' => $reference,
                        'customer_id' => $customerId,
                        'staff_id' => $staffId,
                        'date' => date('Y-m-d H:i:s'),
                        'items' => $items,
                        'discount' => (float) $calculation['discount'],
                        'rounding' => 0,
                        'status' => 'paid',
                        'payment_method' => $paymentMethod,
                    ],
                ];
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();

                return ['success' => false, 'message' => 'Checkout database gagal: ' . $throwable->getMessage()];
            }
        }

        $id = $this->nextId($_SESSION['starstyle']['transactions'], 8000);
        $transaction = [
            'id' => $id,
            'reference' => 'TRX-' . date('ymd') . '-' . $id,
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'date' => date('Y-m-d H:i:s'),
            'items' => $items,
            'discount' => $calculation['discount'],
            'rounding' => 0,
            'status' => 'paid',
            'payment_method' => $payload['payment_method'] ?? 'Cash',
        ];

        $_SESSION['starstyle']['transactions'][] = $transaction;
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actor['name'],
            'action' => 'Checkout transaksi ' . $transaction['reference'],
        ];

        return ['success' => true, 'message' => 'Checkout berhasil diproses.', 'transaction' => $transaction];
    }

    public function calculateCart(array $items, ?string $voucherCode = null): array
    {
        $subtotal = array_reduce($items, fn (float $sum, array $item): float => $sum + ((float) $item['price'] * (int) $item['qty']), 0.0);
        $discount = 0.0;

        if ($voucherCode !== null && $voucherCode !== '') {
            $voucher = $this->validateVoucher($voucherCode);

            if ($voucher['valid']) {
                $voucherData = $voucher['voucher'];
                $discount = $voucherData['type'] === 'gift' ? $voucherData['value'] : round($subtotal * ($voucherData['value'] / 100));
            }
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
        ];
    }

    public function validateVoucher(string $code): array
    {
        foreach ($this->getVouchers() as $voucher) {
            if (strcasecmp($voucher['code'], $code) !== 0) {
                continue;
            }

            $expired = $voucher['expired_at'] < date('Y-m-d');
            $limitReached = $voucher['used'] >= $voucher['usage_limit'];
            $status = strtolower(trim((string) ($voucher['status'] ?? '')));

            if ($expired || $limitReached || !in_array($status, ['aktif', 'active'], true)) {
                return ['valid' => false, 'message' => 'Voucher tidak aktif atau sudah expired.', 'voucher' => $voucher];
            }

            return ['valid' => true, 'message' => 'Voucher valid.', 'voucher' => $voucher];
        }

        return ['valid' => false, 'message' => 'Kode voucher tidak ditemukan.', 'voucher' => null];
    }

    public function analytics(): array
    {
        $transactions = $this->getTransactions();
        $services = $this->getServices();
        $products = $this->getProducts();
        $bookings = array_map(static function (array $booking): array {
            return $booking + [
                'date' => (string) ($booking['date'] ?? substr((string) ($booking['start_at'] ?? ''), 0, 10)),
            ];
        }, $this->getBookings());
        $customers = $this->getCustomers();
        $staff = $this->getStaff();
        $vouchers = array_map(static function (array $voucher): array {
            $type = (string) ($voucher['type'] ?? 'gift');
            $status = strtolower(trim((string) ($voucher['status'] ?? '')));
            $rawValue = $type === 'gift'
                ? (float) ($voucher['editor_value'] ?? $voucher['value'] ?? 0)
                : (float) ($voucher['price_value'] ?? $voucher['editor_value'] ?? 0);

            return $voucher + [
                'type' => $type,
                'value' => $rawValue,
                'status' => in_array($status, ['active', 'aktif'], true)
                    ? 'aktif'
                    : ($status !== '' ? $status : 'nonaktif'),
            ];
        }, $this->getVouchers());
        $classes = $this->getClasses();

        $paidTransactions = array_values(array_filter(
            $transactions,
            static fn (array $transaction): bool => strtolower((string) ($transaction['status'] ?? '')) === 'paid'
        ));
        $currentPeriodStart = new \DateTimeImmutable('-29 days');
        $previousPeriodStart = new \DateTimeImmutable('-59 days');
        $previousPeriodEnd = new \DateTimeImmutable('-30 days');

        $currentTransactions = array_values(array_filter(
            $paidTransactions,
            static fn (array $transaction): bool => new \DateTimeImmutable((string) ($transaction['date'] ?? 'now')) >= $currentPeriodStart
        ));
        $previousTransactions = array_values(array_filter(
            $paidTransactions,
            static fn (array $transaction): bool => ($date = new \DateTimeImmutable((string) ($transaction['date'] ?? 'now'))) >= $previousPeriodStart && $date <= $previousPeriodEnd
        ));

        $currentSales = array_sum(array_map(fn (array $transaction): float => $this->analyticsTransactionNetTotal($transaction), $currentTransactions));
        $previousSales = array_sum(array_map(fn (array $transaction): float => $this->analyticsTransactionNetTotal($transaction), $previousTransactions));

        $currentBookings = array_values(array_filter(
            $bookings,
            static fn (array $booking): bool => new \DateTimeImmutable((string) ($booking['start_at'] ?? 'now')) >= $currentPeriodStart
        ));
        $completedBookings = count(array_filter(
            $currentBookings,
            static fn (array $booking): bool => in_array(strtolower((string) ($booking['status'] ?? '')), ['completed', 'done', 'selesai'], true)
        ));
        $cancelledBookings = count(array_filter(
            $currentBookings,
            static fn (array $booking): bool => in_array(strtolower((string) ($booking['status'] ?? '')), ['cancelled', 'canceled', 'batal', 'no_show', 'no-show'], true)
        ));
        $conversionBase = max(1, count($currentBookings));
        $conversionRate = round((($conversionBase - $cancelledBookings) / $conversionBase) * 100);

        $activeCustomers = array_filter($customers, static fn (array $customer): bool => strtolower((string) ($customer['status'] ?? 'aktif')) === 'aktif');
        $returningCustomers = count(array_filter(
            $activeCustomers,
            static fn (array $customer): bool => (int) ($customer['loyalty_points'] ?? 0) >= 100 || in_array('VIP', (array) ($customer['tags'] ?? []), true)
        ));
        $retentionRate = count($activeCustomers) > 0 ? round(($returningCustomers / count($activeCustomers)) * 100) : 0;
        $lowStockCount = count(array_filter($products, static fn (array $product): bool => (int) ($product['stock'] ?? 0) <= 8));

        return [
            'kpis' => [
                ['label' => 'Appointment Conversion', 'value' => $conversionRate . '%'],
                ['label' => 'Sales Growth', 'value' => $this->formatSignedPercent($this->percentChange($currentSales, $previousSales))],
                ['label' => 'Retention Rate', 'value' => $retentionRate . '%'],
                ['label' => 'Low Stock Item', 'value' => (string) $lowStockCount],
            ],
            'salesByType' => [
                'service' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'service'), $transactions)),
                'product' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'product'), $transactions)),
                'voucher' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'voucher'), $transactions)),
                'package' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'package'), $transactions)),
            ],
            'retention' => [
                'new' => max(0, count($activeCustomers) - $returningCustomers),
                'returning' => $returningCustomers,
                'vip' => count(array_filter($customers, fn (array $customer): bool => in_array('VIP', $customer['tags'], true))),
            ],
            'inventory' => $products,
            'bookings' => $bookings,
            'transactions' => $transactions,
            'customers' => $customers,
            'staff' => $staff,
            'vouchers' => $vouchers,
            'classes' => $classes,
            'services' => $services,
            'products' => $products,
            'analytics_generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    public function staffDirectory(): array
    {
        return [
            'staff' => $this->getStaff(),
            'shifts' => $this->getShifts(),
            'attendance' => $this->getAttendance(),
            'serviceGroups' => $this->getServiceGroups(),
            'services' => $this->getServices(),
            'products' => $this->getProducts(),
        ];
    }

    public function saveStaff(?int $staffId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan staff hanya tersedia pada data source DB.');
        }

        $this->ensureStaffProfileSchema();
        $record = $this->normalizeStaffPayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama staff wajib diisi.');
        }

        $existing = $staffId !== null ? $this->findStaff($staffId) : null;
        $this->pdo()->beginTransaction();
        try {
            $userId = $existing['user_id'] ?? null;
            if ($userId === null && $record['email'] !== null) {
                $matchedUser = $this->dbOne(
                    "SELECT id
                     FROM users
                     WHERE LOWER(email) = LOWER(:email) AND portal = 'internal'
                     LIMIT 1",
                    ['email' => $record['email']]
                );
                if ($matchedUser !== null) {
                    $userId = (int) $matchedUser['id'];
                }
            }

            if ($userId === null) {
                $this->dbExecute(
                    "INSERT INTO users (role_id, name, email, password, portal, avatar, is_active)
                     VALUES (:role_id, :name, :email, :password, 'internal', :avatar, :is_active)",
                    [
                        'role_id' => $this->staffRoleId(),
                        'name' => $record['name'],
                        'email' => $record['email'] ?? $this->generateFallbackStaffEmail($record['name']),
                        'password' => password_hash('password123', PASSWORD_DEFAULT),
                        'avatar' => strtoupper(substr(preg_replace('/\s+/', '', $record['name']), 0, 2)),
                        'is_active' => $record['status'] === 'Aktif' ? 1 : 0,
                    ]
                );
                $userId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE users
                     SET name = :name,
                         email = :email,
                         is_active = :is_active
                     WHERE id = :id",
                    [
                        'id' => $userId,
                        'name' => $record['name'],
                        'email' => $record['email'] ?? $this->generateFallbackStaffEmail($record['name']),
                        'is_active' => $record['status'] === 'Aktif' ? 1 : 0,
                    ]
                );
            }

            if ($staffId === null) {
                $this->dbExecute(
                    "INSERT INTO staff (
                        user_id, location_id, name, email, phone, role_title, status, commission_type, commission_value, rating,
                        gender, booking_enabled, agenda_color, started_working_on, ended_working_on, public_title, notes,
                        instagram_handle, photo_data_url, commission_rules, attendance_pose, attendance_uploaded_pose
                    ) VALUES (
                        :user_id, :location_id, :name, :email, :phone, :role_title, :status, :commission_type, :commission_value, :rating,
                        :gender, :booking_enabled, :agenda_color, :started_working_on, :ended_working_on, :public_title, :notes,
                        :instagram_handle, :photo_data_url, :commission_rules, :attendance_pose, :attendance_uploaded_pose
                    )",
                    $record + ['user_id' => $userId]
                );
                $staffId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE staff
                     SET user_id = :user_id,
                         location_id = :location_id,
                         name = :name,
                         email = :email,
                         phone = :phone,
                         role_title = :role_title,
                         status = :status,
                         commission_type = :commission_type,
                         commission_value = :commission_value,
                         rating = :rating,
                         gender = :gender,
                         booking_enabled = :booking_enabled,
                         agenda_color = :agenda_color,
                         started_working_on = :started_working_on,
                         ended_working_on = :ended_working_on,
                         public_title = :public_title,
                         notes = :notes,
                         instagram_handle = :instagram_handle,
                         photo_data_url = :photo_data_url,
                         commission_rules = :commission_rules,
                         attendance_pose = :attendance_pose,
                         attendance_uploaded_pose = :attendance_uploaded_pose
                     WHERE id = :staff_id AND deleted_at IS NULL",
                    $record + ['user_id' => $userId, 'staff_id' => $staffId]
                );
            }

            $this->dbExecute("DELETE FROM staff_skills WHERE staff_id = :staff_id", ['staff_id' => $staffId]);
            foreach ($record['service_ids_raw'] as $serviceId) {
                $this->dbExecute(
                    "INSERT INTO staff_skills (staff_id, service_id) VALUES (:staff_id, :service_id)",
                    [
                        'staff_id' => $staffId,
                        'service_id' => $serviceId,
                    ]
                );
            }

            if ($this->tableExists('staff_permissions')) {
                $this->dbExecute("DELETE FROM staff_permissions WHERE staff_id = :staff_id", ['staff_id' => $staffId]);
                foreach ($this->staffPermissionDefaultsByRoleTitle($record['role_title'], (bool) $record['booking_enabled']) as $permissionKey) {
                    $this->dbExecute(
                        "INSERT INTO staff_permissions (staff_id, permission_key, granted, created_at)
                         VALUES (:staff_id, :permission_key, 1, NOW())",
                        [
                            'staff_id' => $staffId,
                            'permission_key' => $permissionKey,
                        ]
                    );
                }
            }

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => ($existing === null ? 'Menambah' : 'Memperbarui') . ' staff #' . $staffId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $throwable;
        }

        return $this->findStaff($staffId) ?? throw new \RuntimeException('Staff gagal dimuat ulang.');
    }

    public function deleteStaff(int $staffId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan staff hanya tersedia pada data source DB.');
        }

        $staff = $this->dbOne(
            "SELECT id, user_id
             FROM staff
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1",
            ['id' => $staffId]
        );
        if ($staff === null) {
            throw new \InvalidArgumentException('Staff tidak ditemukan.');
        }

        $this->pdo()->beginTransaction();
        try {
            $this->dbExecute("UPDATE staff SET deleted_at = NOW(), status = 'Nonaktif' WHERE id = :id", ['id' => $staffId]);
            if ($staff['user_id'] !== null) {
                $this->dbExecute("UPDATE users SET is_active = 0 WHERE id = :id", ['id' => (int) $staff['user_id']]);
            }
            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Menghapus staff #' . $staffId,
                    ]
                );
            }
            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $throwable;
        }
    }

    public function saveStaffShifts(int $staffId, string $startDate, string $repeatMode, string $repeatEnd, ?string $repeatEndDate, array $shifts, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Jadwal staff hanya tersedia pada data source DB.');
        }

        $this->ensureStaffScheduleSchema();
        $this->ensureStaffProfileSchema();
        $staff = $this->findStaff($staffId);
        if ($staff === null) {
            throw new \InvalidArgumentException('Staff tidak ditemukan.');
        }

        $dates = $this->expandShiftDates($startDate, $repeatMode, $repeatEnd, $repeatEndDate);
        if ($dates === []) {
            throw new \InvalidArgumentException('Tanggal shift tidak valid.');
        }

        $cleanShifts = [];
        foreach ($shifts as $shift) {
            $start = substr(trim((string) ($shift['start'] ?? '')), 0, 5);
            $end = substr(trim((string) ($shift['end'] ?? '')), 0, 5);
            if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
                continue;
            }
            $cleanShifts[] = ['start' => $start, 'end' => $end];
        }
        if ($cleanShifts === []) {
            throw new \InvalidArgumentException('Shift minimal harus memiliki satu jam kerja yang valid.');
        }

        $this->pdo()->beginTransaction();
        try {
            foreach ($dates as $date) {
                $this->dbExecute(
                    "DELETE FROM staff_shifts
                     WHERE staff_id = :staff_id AND shift_date = :shift_date",
                    [
                        'staff_id' => $staffId,
                        'shift_date' => $date,
                    ]
                );
                foreach ($cleanShifts as $shift) {
                    $this->dbExecute(
                        "INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, repeat_mode)
                         VALUES (:staff_id, :shift_date, :start_time, :end_time, :repeat_mode)",
                        [
                            'staff_id' => $staffId,
                            'shift_date' => $date,
                            'start_time' => $shift['start'] . ':00',
                            'end_time' => $shift['end'] . ':00',
                            'repeat_mode' => $repeatMode,
                        ]
                    );
                }
            }

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Mengatur shift staff #' . $staffId . ' mulai ' . $startDate,
                    ]
                );
            }
            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $throwable;
        }
    }

    public function deleteStaffShifts(int $staffId, string $startDate, string $repeatMode, string $repeatEnd, ?string $repeatEndDate, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Jadwal staff hanya tersedia pada data source DB.');
        }

        $this->ensureStaffScheduleSchema();
        $dates = $this->expandShiftDates($startDate, $repeatMode, $repeatEnd, $repeatEndDate);
        if ($dates === []) {
            $dates = [$startDate];
        }

        $this->pdo()->beginTransaction();
        try {
            foreach ($dates as $date) {
                $this->dbExecute(
                    "DELETE FROM staff_shifts
                     WHERE staff_id = :staff_id AND shift_date = :shift_date",
                    [
                        'staff_id' => $staffId,
                        'shift_date' => $date,
                    ]
                );
            }
            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Menghapus shift staff #' . $staffId . ' mulai ' . $startDate,
                    ]
                );
            }
            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }
            throw $throwable;
        }
    }

    public function saveStaffAttendance(array $payload, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Attendance staff hanya tersedia pada data source DB.');
        }

        $this->ensureStaffScheduleSchema();
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $attendanceDate = trim((string) ($payload['attendance_date'] ?? ''));
        if ($staffId === 0 || $attendanceDate === '') {
            throw new \InvalidArgumentException('Staff dan tanggal attendance wajib diisi.');
        }

        $record = [
            'staff_id' => $staffId,
            'attendance_date' => $attendanceDate,
            'shift_start' => substr((string) ($payload['shift_start'] ?? '08:00'), 0, 5) . ':00',
            'shift_end' => substr((string) ($payload['shift_end'] ?? '17:00'), 0, 5) . ':00',
            'clock_in' => substr((string) ($payload['clock_in'] ?? '08:00'), 0, 5) . ':00',
            'clock_out' => substr((string) ($payload['clock_out'] ?? '17:00'), 0, 5) . ':00',
            'source' => (string) ($payload['source'] ?? '-'),
            'status' => (string) ($payload['status'] ?? 'Ontime'),
            'selfie_in_score' => (float) ($payload['selfie_in_score'] ?? 0),
            'selfie_out_score' => (float) ($payload['selfie_out_score'] ?? 0),
        ];

        $existing = $this->dbOne(
            "SELECT id
             FROM staff_attendance
             WHERE staff_id = :staff_id AND attendance_date = :attendance_date
             LIMIT 1",
            [
                'staff_id' => $staffId,
                'attendance_date' => $attendanceDate,
            ]
        );

        if ($existing === null) {
            $this->dbExecute(
                "INSERT INTO staff_attendance
                 (staff_id, attendance_date, shift_start, shift_end, clock_in, clock_out, source, status, selfie_in_score, selfie_out_score)
                 VALUES
                 (:staff_id, :attendance_date, :shift_start, :shift_end, :clock_in, :clock_out, :source, :status, :selfie_in_score, :selfie_out_score)",
                $record
            );
        } else {
            $this->dbExecute(
                "UPDATE staff_attendance
                 SET shift_start = :shift_start,
                     shift_end = :shift_end,
                     clock_in = :clock_in,
                     clock_out = :clock_out,
                     source = :source,
                     status = :status,
                     selfie_in_score = :selfie_in_score,
                     selfie_out_score = :selfie_out_score
                 WHERE id = :id",
                $record + ['id' => (int) $existing['id']]
            );
        }

        if ($this->tableExists('activity_logs')) {
            $this->dbExecute(
                "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                 VALUES (NULL, :actor_name, :action_text, NOW())",
                [
                    'actor_name' => $actorName,
                    'action_text' => 'Mengatur attendance staff #' . $staffId . ' tanggal ' . $attendanceDate,
                ]
            );
        }
    }

    public function updateStaffAttendanceProfile(int $staffId, bool $active, string $pose, string $uploadedPose, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Profil attendance staff hanya tersedia pada data source DB.');
        }

        $this->ensureStaffProfileSchema();
        $this->dbExecute(
            "UPDATE staff
             SET status = :status,
                 attendance_pose = :attendance_pose,
                 attendance_uploaded_pose = :attendance_uploaded_pose
             WHERE id = :id AND deleted_at IS NULL",
            [
                'id' => $staffId,
                'status' => $active ? 'Aktif' : 'Nonaktif',
                'attendance_pose' => $pose !== '' ? $pose : 'Right Tilt',
                'attendance_uploaded_pose' => $uploadedPose !== '' ? $uploadedPose : null,
            ]
        );

        if ($this->tableExists('activity_logs')) {
            $this->dbExecute(
                "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                 VALUES (NULL, :actor_name, :action_text, NOW())",
                [
                    'actor_name' => $actorName,
                    'action_text' => 'Mengubah profil attendance staff #' . $staffId,
                ]
            );
        }
    }

    public function customerAccount(int $customerId): array
    {
        return [
            'customer' => $this->findCustomer($customerId),
            'bookings' => array_values(array_filter($this->getBookings(), fn (array $booking): bool => $booking['customer_id'] === $customerId)),
            'transactions' => array_values(array_filter($this->getTransactions(), fn (array $transaction): bool => $transaction['customer_id'] === $customerId)),
            'vouchers' => $this->getVouchers(),
        ];
    }

    public function customerDetail(int $customerId): ?array
    {
        $customer = $this->findCustomer($customerId);
        if ($customer === null) {
            return null;
        }

        if (!$this->usingDb()) {
            return [
                'customer' => $customer,
                'detail' => $this->buildCustomerDetailPayload($customer, [], [], []),
            ];
        }

        $this->ensureCustomerSchema();
            $bookings = $this->dbAll(
                "SELECT b.id, b.reference, b.start_at, b.status, b.notes,
                    COALESCE(s.name, 'Staff') AS staff_name,
                    COALESCE(l.name, :fallback_location) AS location_name,
                    COALESCE(SUM(bi.price), 0) AS total_amount,
                    GROUP_CONCAT(DISTINCT sv.name ORDER BY sv.name SEPARATOR ', ') AS service_names
             FROM bookings b
             LEFT JOIN staff s ON s.id = b.staff_id
             LEFT JOIN locations l ON l.id = b.location_id
             LEFT JOIN booking_items bi ON bi.booking_id = b.id
             LEFT JOIN services sv ON sv.id = bi.service_id
             WHERE b.customer_id = :customer_id
             GROUP BY b.id, b.reference, b.start_at, b.status, b.notes, s.name, l.name
             ORDER BY b.start_at DESC",
            [
                'customer_id' => $customerId,
                'fallback_location' => (string) ($this->config['business']['name'] ?? 'Star Salon'),
            ]
        );
        $transactions = $this->dbAll(
            "SELECT t.id, t.reference, t.status, t.discount_amount, t.rounding_amount, t.paid_at,
                    COALESCE(i.invoice_number, t.reference) AS invoice_number,
                    COALESCE(loc.name, :fallback_location) AS location_name,
                    COALESCE(SUM(ti.quantity * ti.price), 0) AS gross_total,
                    COALESCE(SUM(CASE WHEN ti.item_type = 'service' THEN ti.quantity * ti.price ELSE 0 END), 0) AS service_total,
                    COALESCE(SUM(CASE WHEN ti.item_type = 'product' THEN ti.quantity * ti.price ELSE 0 END), 0) AS product_total,
                    COALESCE(SUM(CASE WHEN ti.item_type = 'service' THEN ti.quantity ELSE 0 END), 0) AS service_qty,
                    COALESCE(SUM(CASE WHEN ti.item_type = 'product' THEN ti.quantity ELSE 0 END), 0) AS product_qty,
                    GROUP_CONCAT(DISTINCT CASE WHEN ti.item_type = 'service' THEN ti.item_name END ORDER BY ti.item_name SEPARATOR ', ') AS service_names,
                    GROUP_CONCAT(DISTINCT CASE WHEN ti.item_type = 'product' THEN CONCAT(ti.item_name, ' x', ti.quantity) END ORDER BY ti.item_name SEPARATOR ', ') AS product_names
             FROM transactions t
             LEFT JOIN invoices i ON i.transaction_id = t.id
             LEFT JOIN bookings b ON b.id = t.booking_id
             LEFT JOIN locations loc ON loc.id = b.location_id
             LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
             WHERE t.customer_id = :customer_id
             GROUP BY t.id, t.reference, t.status, t.discount_amount, t.rounding_amount, t.paid_at, i.invoice_number, loc.name
             ORDER BY t.paid_at DESC",
            [
                'customer_id' => $customerId,
                'fallback_location' => (string) ($this->config['business']['name'] ?? 'Star Salon'),
            ]
        );
        $voucherUsage = (int) ($this->dbOne(
            "SELECT COUNT(*) AS total
             FROM voucher_redemptions
             WHERE customer_id = :customer_id",
            ['customer_id' => $customerId]
        )['total'] ?? 0);

        return [
            'customer' => $customer,
            'detail' => $this->buildCustomerDetailPayload($customer, $bookings, $transactions, ['voucher_usage' => $voucherUsage]),
        ];
    }

    public function saveCustomer(?int $customerId, array $payload, string $actorName): array
    {
        if ($this->usingDb()) {
            $this->ensureCustomerSchema();
            $record = $this->normalizeCustomerPayload($payload);
            $existing = $customerId !== null ? $this->findCustomer($customerId) : null;

            if ($record['name'] === '') {
                throw new \InvalidArgumentException('Nama pelanggan wajib diisi.');
            }

            if ($record['member_id'] === '') {
                $record['member_id'] = $this->generateCustomerMemberId();
            }

            $memberOwner = $this->dbOne(
                "SELECT id
                 FROM customers
                 WHERE deleted_at IS NULL
                   AND member_id = :member_id
                   AND (:customer_id_match IS NULL OR id <> :customer_id_exclude)
                 LIMIT 1",
                [
                    'member_id' => $record['member_id'],
                    'customer_id_match' => $customerId,
                    'customer_id_exclude' => $customerId,
                ]
            );
            if ($memberOwner !== null) {
                throw new \InvalidArgumentException('Member ID sudah dipakai pelanggan lain.');
            }

            $this->pdo()->beginTransaction();
            try {
                if ($customerId === null) {
                    $this->dbExecute(
                        "INSERT INTO customers (
                            user_id, member_id, name, gender, phone, email, loyalty_points, last_visit_at, tags, notes, address, status,
                            birthdate, family_card_number, passport_number, notify_via, marketing_opt_in
                        ) VALUES (
                            NULL, :member_id, :name, :gender, :phone, :email, :loyalty_points, :last_visit_at, :tags, :notes, :address, :status,
                            :birthdate, :family_card_number, :passport_number, :notify_via, :marketing_opt_in
                        )",
                        $record
                    );
                    $customerId = (int) $this->pdo()->lastInsertId();
                } else {
                    $this->dbExecute(
                        "UPDATE customers
                         SET member_id = :member_id,
                             name = :name,
                             gender = :gender,
                             phone = :phone,
                             email = :email,
                             loyalty_points = :loyalty_points,
                             last_visit_at = :last_visit_at,
                             tags = :tags,
                             notes = :notes,
                             address = :address,
                             status = :status,
                             birthdate = :birthdate,
                             family_card_number = :family_card_number,
                             passport_number = :passport_number,
                             notify_via = :notify_via,
                             marketing_opt_in = :marketing_opt_in
                         WHERE id = :customer_id AND deleted_at IS NULL",
                        $record + ['customer_id' => $customerId]
                    );
                }

                $customerRow = $this->dbOne(
                    "SELECT user_id
                     FROM customers
                     WHERE id = :id AND deleted_at IS NULL
                     LIMIT 1",
                    ['id' => $customerId]
                );

                if ($customerRow !== null && $customerRow['user_id'] !== null) {
                    $this->dbExecute(
                        "UPDATE users
                         SET name = :name,
                             email = :email
                         WHERE id = :id",
                        [
                            'id' => (int) $customerRow['user_id'],
                            'name' => $record['name'],
                            'email' => $record['email'] !== null ? $record['email'] : ('customer-' . $customerId . '@starstyle.test'),
                        ]
                    );
                }

                if ($this->tableExists('activity_logs')) {
                    $this->dbExecute(
                        "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                         VALUES (NULL, :actor_name, :action_text, NOW())",
                        [
                            'actor_name' => $actorName,
                            'action_text' => ($existing === null ? 'Menambah' : 'Memperbarui') . ' customer #' . $customerId,
                        ]
                    );
                }

                $this->pdo()->commit();
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();
                throw $throwable;
            }

            return $this->findCustomer($customerId) ?? throw new \RuntimeException('Customer gagal dimuat ulang.');
        }

        throw new \RuntimeException('Penyimpanan customer hanya tersedia pada data source DB.');
    }

    public function deleteCustomer(int $customerId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan customer hanya tersedia pada data source DB.');
        }

        $customer = $this->dbOne(
            "SELECT id, user_id
             FROM customers
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1",
            ['id' => $customerId]
        );

        if ($customer === null) {
            throw new \InvalidArgumentException('Customer tidak ditemukan.');
        }

        $this->pdo()->beginTransaction();
        try {
            $this->dbExecute(
                "UPDATE customers
                 SET deleted_at = NOW(), status = 'Non-Aktif'
                 WHERE id = :id",
                ['id' => $customerId]
            );

            if ($customer['user_id'] !== null) {
                $this->dbExecute(
                    "UPDATE users
                     SET is_active = 0
                     WHERE id = :id",
                    ['id' => (int) $customer['user_id']]
                );
            }

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Menghapus customer #' . $customerId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            $this->pdo()->rollBack();
            throw $throwable;
        }
    }

    public function importCustomers(array $rows, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Import customer hanya tersedia pada data source DB.');
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $memberId = trim((string) ($row['member_id'] ?? $row['memberId'] ?? ''));
                $email = trim((string) ($row['email'] ?? ''));
                $phone = trim((string) ($row['phone'] ?? ''));
                $existing = null;

                if ($memberId !== '') {
                    $existing = $this->dbOne(
                        "SELECT id
                         FROM customers
                         WHERE deleted_at IS NULL AND member_id = :member_id
                         LIMIT 1",
                        ['member_id' => $memberId]
                    );
                }
                if ($existing === null && $email !== '') {
                    $existing = $this->dbOne(
                        "SELECT id
                         FROM customers
                         WHERE deleted_at IS NULL AND email = :email
                         LIMIT 1",
                        ['email' => $email]
                    );
                }
                if ($existing === null && $phone !== '') {
                    $existing = $this->dbOne(
                        "SELECT id
                         FROM customers
                         WHERE deleted_at IS NULL AND phone = :phone
                         LIMIT 1",
                        ['phone' => $phone]
                    );
                }

                $saved = $this->saveCustomer($existing !== null ? (int) $existing['id'] : null, $row, $actorName);
                if ($existing === null) {
                    $created++;
                } elseif ($saved !== []) {
                    $updated++;
                }
            } catch (\Throwable $throwable) {
                $errors[] = 'Baris ' . ($index + 1) . ': ' . $throwable->getMessage();
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    public function settingsPayload(): array
    {
        if ($this->usingDb()) {
            $staff = array_map(function (array $staffMember): array {
                $staffMember['permissions'] = array_map(
                    static fn (array $row): string => (string) $row['permission_key'],
                    $this->dbAll(
                        "SELECT permission_key
                         FROM staff_permissions
                         WHERE staff_id = :staff_id AND granted = 1
                         ORDER BY permission_key",
                        ['staff_id' => (int) $staffMember['id']]
                    )
                );

                if ($staffMember['permissions'] === []) {
                    $staffMember['permissions'] = $staffMember['role'] === 'Owner'
                        ? $this->allPermissionKeys()
                        : ($this->permissions['defaults']['staff'] ?? []);
                }

                return $staffMember;
            }, $this->getStaff());

            return [
                'settings' => $this->getSettings(),
                'catalog' => $this->permissions['catalog'],
                'staff' => $staff,
            ];
        }

        $staff = array_map(function (array $staffMember): array {
            $staffMember['permissions'] = $_SESSION['starstyle']['staff_permissions'][$staffMember['id']] ?? $this->permissions['defaults']['staff'];

            return $staffMember;
        }, $this->getStaff());

        return [
            'settings' => $this->getSettings(),
            'catalog' => $this->permissions['catalog'],
            'staff' => $staff,
        ];
    }

    public function updateBusinessProfile(array $payload, string $actorName): array
    {
        $businessName = trim((string) ($payload['business_name'] ?? ''));
        $address = trim((string) ($payload['address'] ?? ''));
        $notificationChannel = trim((string) ($payload['notification_channel'] ?? ''));
        $timezone = trim((string) ($payload['timezone'] ?? '')) ?: ($this->config['timezone'] ?? 'Asia/Bangkok');
        $schedulePayload = json_decode((string) ($payload['hours_schedule_json'] ?? '[]'), true);

        if ($businessName === '') {
            return ['success' => false, 'message' => 'Nama bisnis wajib diisi.'];
        }

        $schedule = $this->normalizeBusinessHoursSchedule($schedulePayload);
        $activeDays = array_values(array_filter($schedule, static fn (array $day): bool => !empty($day['enabled'])));
        if ($activeDays === []) {
            return ['success' => false, 'message' => 'Pilih minimal satu hari operasional.'];
        }

        foreach ($activeDays as $day) {
            $openTime = (string) ($day['open'] ?? '');
            $closeTime = (string) ($day['close'] ?? '');
            if (preg_match('/^\d{2}:\d{2}$/', $openTime) !== 1 || preg_match('/^\d{2}:\d{2}$/', $closeTime) !== 1) {
                return ['success' => false, 'message' => 'Format jam operasional harus HH:MM.'];
            }
            if (strcmp($openTime, $closeTime) >= 0) {
                return ['success' => false, 'message' => 'Jam buka harus lebih kecil dari jam tutup.'];
            }
        }

        $hours = $this->summarizeBusinessHoursSchedule($schedule);

        if ($this->usingDb()) {
            $this->ensureBusinessSettingsSchema();
            $existing = $this->dbOne(
                "SELECT id, booking_advance_days, loyalty_ratio, currency
                 FROM business_settings
                 ORDER BY id ASC
                 LIMIT 1"
            );

            $bookingAdvanceDays = (int) ($existing['booking_advance_days'] ?? 30);
            $loyaltyRatio = (int) ($existing['loyalty_ratio'] ?? 10000);
            $currency = (string) ($existing['currency'] ?? 'IDR');

            $this->pdo()->beginTransaction();
            try {
                if ($existing !== null) {
                    $this->dbExecute(
                        "UPDATE business_settings
                         SET business_name = :business_name,
                             business_hours = :business_hours,
                             address = :address,
                             notification_channel = :notification_channel,
                             timezone = :timezone,
                             hours_schedule_json = :hours_schedule_json
                         WHERE id = :id",
                        [
                            'id' => (int) $existing['id'],
                            'business_name' => $businessName,
                            'business_hours' => $hours,
                            'address' => $address !== '' ? $address : null,
                            'notification_channel' => $notificationChannel !== '' ? $notificationChannel : null,
                            'timezone' => $timezone,
                            'hours_schedule_json' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
                        ]
                    );
                } else {
                    $this->dbExecute(
                        "INSERT INTO business_settings (business_name, business_hours, address, booking_advance_days, loyalty_ratio, currency, notification_channel, timezone, hours_schedule_json)
                         VALUES (:business_name, :business_hours, :address, :booking_advance_days, :loyalty_ratio, :currency, :notification_channel, :timezone, :hours_schedule_json)",
                        [
                            'business_name' => $businessName,
                            'business_hours' => $hours,
                            'address' => $address !== '' ? $address : null,
                            'booking_advance_days' => $bookingAdvanceDays,
                            'loyalty_ratio' => $loyaltyRatio,
                            'currency' => $currency,
                            'notification_channel' => $notificationChannel !== '' ? $notificationChannel : null,
                            'timezone' => $timezone,
                            'hours_schedule_json' => json_encode($schedule, JSON_UNESCAPED_UNICODE),
                        ]
                    );
                }

                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Memperbarui profil salon',
                    ]
                );

                $this->pdo()->commit();
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();

                return ['success' => false, 'message' => 'Gagal menyimpan profil salon: ' . $throwable->getMessage()];
            }

            return ['success' => true, 'message' => 'Profil salon berhasil diperbarui.'];
        }

        $_SESSION['starstyle']['settings'] = array_merge($this->getSettings(), [
            'business_name' => $businessName,
            'hours' => $hours,
            'address' => $address,
            'notification_channel' => $notificationChannel,
            'timezone' => $timezone,
            'hours_schedule' => $schedule,
        ]);
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actorName,
            'action' => 'Memperbarui profil salon',
        ];

        return ['success' => true, 'message' => 'Profil salon berhasil diperbarui.'];
    }

    public function updateStaffPermissions(int $staffId, array $grantedPermissions, string $actorName): void
    {
        if ($this->usingDb()) {
            $this->pdo()->beginTransaction();
            try {
                $this->dbExecute("DELETE FROM staff_permissions WHERE staff_id = :staff_id", ['staff_id' => $staffId]);
                foreach (array_values(array_unique($grantedPermissions)) as $permissionKey) {
                    $this->dbExecute(
                        "INSERT INTO staff_permissions (staff_id, permission_key, granted, created_at)
                         VALUES (:staff_id, :permission_key, 1, NOW())",
                        [
                            'staff_id' => $staffId,
                            'permission_key' => (string) $permissionKey,
                        ]
                    );
                }
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Mengubah permission staff ID #' . $staffId,
                    ]
                );
                $this->pdo()->commit();
            } catch (\Throwable $throwable) {
                $this->pdo()->rollBack();
                throw $throwable;
            }

            return;
        }

        $_SESSION['starstyle']['staff_permissions'][$staffId] = array_values(array_unique($grantedPermissions));
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actorName,
            'action' => 'Mengubah permission staff ID #' . $staffId,
        ];
    }

    public function searchCustomers(string $query): array
    {
        if ($this->usingDb()) {
            $query = trim($query);
            if ($query === '') {
                return $this->getCustomers();
            }

            $rows = $this->dbAll(
                "SELECT id FROM customers
                 WHERE deleted_at IS NULL
                   AND (name LIKE :query OR phone LIKE :query OR email LIKE :query OR member_id LIKE :query)
                 ORDER BY name ASC",
                ['query' => '%' . $query . '%']
            );
            $ids = array_map(static fn (array $row): int => (int) $row['id'], $rows);

            return array_values(array_filter($this->getCustomers(), static fn (array $customer): bool => in_array((int) $customer['id'], $ids, true)));
        }

        return array_values(array_filter($this->getCustomers(), fn (array $customer): bool => stripos($customer['name'], $query) !== false || stripos($customer['phone'], $query) !== false));
    }

    public function servicesByStaff(int $staffId): array
    {
        if ($this->usingDb()) {
            $serviceIds = array_map(
                static fn (array $row): int => (int) $row['service_id'],
                $this->dbAll("SELECT service_id FROM staff_skills WHERE staff_id = :staff_id", ['staff_id' => $staffId])
            );

            return array_values(array_filter($this->getServices(), static fn (array $service): bool => in_array((int) $service['id'], $serviceIds, true)));
        }

        return array_values(array_filter($this->getServices(), fn (array $service): bool => in_array($staffId, $service['staff_ids'], true)));
    }

    public function saveServiceGroup(?int $groupId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan grup layanan membutuhkan koneksi database.');
        }

        $this->ensureServiceCatalogSchema();
        $record = $this->normalizeServiceGroupPayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama grup layanan wajib diisi.');
        }

        if ($groupId === null) {
            $this->dbExecute(
                "INSERT INTO service_groups (name, description, color, image_data_url)
                 VALUES (:name, :description, :color, :image_data_url)",
                $record
            );
            $groupId = (int) $this->pdo()->lastInsertId();
        } else {
            $this->dbExecute(
                "UPDATE service_groups
                 SET name = :name,
                     description = :description,
                     color = :color,
                     image_data_url = :image_data_url
                 WHERE id = :id",
                $record + ['id' => $groupId]
            );
        }

        $group = $this->dbOne(
            "SELECT id, name, description, color, image_data_url
             FROM service_groups
             WHERE id = :id
             LIMIT 1",
            ['id' => $groupId]
        );

        return [
            'id' => (int) ($group['id'] ?? $groupId),
            'name' => (string) ($group['name'] ?? $record['name']),
            'description' => (string) ($group['description'] ?? $record['description'] ?? ''),
            'color' => (string) ($group['color'] ?? $record['color']),
            'image_data_url' => (string) ($group['image_data_url'] ?? $record['image_data_url'] ?? ''),
        ];
    }

    public function deleteServiceGroup(int $groupId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan grup layanan membutuhkan koneksi database.');
        }

        $this->ensureServiceCatalogSchema();
        if ($groupId <= 0) {
            throw new \InvalidArgumentException('Grup layanan tidak valid.');
        }

        $activeServices = (int) (($this->dbOne(
            "SELECT COUNT(*) AS aggregate
             FROM services
             WHERE group_id = :group_id AND deleted_at IS NULL",
            ['group_id' => $groupId]
        )['aggregate'] ?? 0));
        if ($activeServices > 0) {
            throw new \RuntimeException('Grup layanan masih dipakai oleh layanan aktif.');
        }

        $activePackages = (int) (($this->dbOne(
            "SELECT COUNT(*) AS aggregate
             FROM service_packages
             WHERE group_id = :group_id",
            ['group_id' => $groupId]
        )['aggregate'] ?? 0));
        if ($activePackages > 0) {
            throw new \RuntimeException('Grup layanan masih dipakai oleh paket layanan.');
        }

        $this->dbExecute("DELETE FROM service_groups WHERE id = :id", ['id' => $groupId]);
    }

    public function saveService(?int $serviceId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan layanan membutuhkan koneksi database.');
        }

        $this->ensureServiceCatalogSchema();
        $record = $this->normalizeServicePayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama layanan wajib diisi.');
        }

        $this->pdo()->beginTransaction();

        try {
            if ($serviceId === null) {
                $this->dbExecute(
                    "INSERT INTO services (
                        group_id, name, duration_minutes, base_price, status, description, audience_json, image_data_url,
                        online_bookable, commission_enabled, at_customer_location, extra_time_type, extra_time_minutes
                    ) VALUES (
                        :group_id, :name, :duration_minutes, :base_price, :status, :description, :audience_json, :image_data_url,
                        :online_bookable, :commission_enabled, :at_customer_location, :extra_time_type, :extra_time_minutes
                    )",
                    $record
                );
                $serviceId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE services
                     SET group_id = :group_id,
                         name = :name,
                         duration_minutes = :duration_minutes,
                         base_price = :base_price,
                         status = :status,
                         description = :description,
                         audience_json = :audience_json,
                         image_data_url = :image_data_url,
                         online_bookable = :online_bookable,
                         commission_enabled = :commission_enabled,
                         at_customer_location = :at_customer_location,
                         extra_time_type = :extra_time_type,
                         extra_time_minutes = :extra_time_minutes
                     WHERE id = :id",
                    $record + ['id' => $serviceId]
                );
            }

            $this->dbExecute("DELETE FROM service_variants WHERE service_id = :service_id", ['service_id' => $serviceId]);
            foreach ($record['variants_raw'] as $variant) {
                $this->dbExecute(
                    "INSERT INTO service_variants (
                        service_id, variant_name, duration_minutes, price, special_price, location_pricing_json,
                        cost_price, cost_products_json, availability_json
                    ) VALUES (
                        :service_id, :variant_name, :duration_minutes, :price, :special_price, :location_pricing_json,
                        :cost_price, :cost_products_json, :availability_json
                    )",
                    [
                        'service_id' => $serviceId,
                        'variant_name' => $variant['variant_name'],
                        'duration_minutes' => $variant['duration_minutes'],
                        'price' => $variant['price'],
                        'special_price' => $variant['special_price'],
                        'location_pricing_json' => $variant['location_pricing_json'],
                        'cost_price' => $variant['cost_price'],
                        'cost_products_json' => $variant['cost_products_json'],
                        'availability_json' => $variant['availability_json'],
                    ]
                );
            }

            $this->dbExecute("DELETE FROM staff_skills WHERE service_id = :service_id", ['service_id' => $serviceId]);
            foreach ($record['staff_ids_raw'] as $staffId) {
                $this->dbExecute(
                    "INSERT INTO staff_skills (staff_id, service_id)
                     VALUES (:staff_id, :service_id)",
                    [
                        'staff_id' => $staffId,
                        'service_id' => $serviceId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }

        foreach ($this->getServices() as $service) {
            if ((int) $service['id'] === (int) $serviceId) {
                return $service;
            }
        }

        throw new \RuntimeException('Layanan gagal dimuat ulang setelah disimpan.');
    }

    public function deleteService(int $serviceId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan layanan membutuhkan koneksi database.');
        }

        if ($serviceId <= 0) {
            throw new \InvalidArgumentException('Layanan tidak valid.');
        }

        $this->pdo()->beginTransaction();

        try {
            $this->dbExecute(
                "UPDATE services
                 SET deleted_at = NOW()
                 WHERE id = :id",
                ['id' => $serviceId]
            );
            $this->dbExecute("DELETE FROM staff_skills WHERE service_id = :service_id", ['service_id' => $serviceId]);
            $this->dbExecute("DELETE FROM service_package_items WHERE service_id = :service_id", ['service_id' => $serviceId]);
            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }
    }

    public function saveServicePackage(?int $packageId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan paket layanan membutuhkan koneksi database.');
        }

        $this->ensureServiceCatalogSchema();
        $record = $this->normalizeServicePackagePayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama paket layanan wajib diisi.');
        }

        $this->pdo()->beginTransaction();

        try {
            if ($packageId === null) {
                $this->dbExecute(
                    "INSERT INTO service_packages (
                        group_id, name, package_price, description, pricing_mode, discount_value, audience, image_data_url, items_json
                    ) VALUES (
                        :group_id, :name, :package_price, :description, :pricing_mode, :discount_value, :audience, :image_data_url, :items_json
                    )",
                    $record
                );
                $packageId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE service_packages
                     SET group_id = :group_id,
                         name = :name,
                         package_price = :package_price,
                         description = :description,
                         pricing_mode = :pricing_mode,
                         discount_value = :discount_value,
                         audience = :audience,
                         image_data_url = :image_data_url,
                         items_json = :items_json
                     WHERE id = :id",
                    $record + ['id' => $packageId]
                );
            }

            $this->dbExecute("DELETE FROM service_package_items WHERE package_id = :package_id", ['package_id' => $packageId]);
            foreach ($record['service_item_ids'] as $serviceItemId) {
                $this->dbExecute(
                    "INSERT INTO service_package_items (package_id, service_id)
                     VALUES (:package_id, :service_id)",
                    [
                        'package_id' => $packageId,
                        'service_id' => $serviceItemId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }

        foreach ($this->getPackages() as $package) {
            if ((int) $package['id'] === (int) $packageId) {
                return $package;
            }
        }

        throw new \RuntimeException('Paket layanan gagal dimuat ulang setelah disimpan.');
    }

    public function deleteServicePackage(int $packageId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan paket layanan membutuhkan koneksi database.');
        }

        if ($packageId <= 0) {
            throw new \InvalidArgumentException('Paket layanan tidak valid.');
        }

        $this->pdo()->beginTransaction();

        try {
            $this->dbExecute("DELETE FROM service_package_items WHERE package_id = :package_id", ['package_id' => $packageId]);
            $this->dbExecute("DELETE FROM service_packages WHERE id = :id", ['id' => $packageId]);
            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }
    }

    public function saveVoucher(?int $voucherId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan voucher membutuhkan koneksi database.');
        }

        $this->ensureVoucherCatalogSchema();
        $record = $this->normalizeVoucherPayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama voucher wajib diisi.');
        }

        if ($record['voucher_type'] === 'service' && $record['service_items_json'] === '[]') {
            throw new \InvalidArgumentException('Pilih minimal satu layanan untuk voucher layanan.');
        }

        $current = $voucherId !== null
            ? $this->dbOne("SELECT id, code FROM vouchers WHERE id = :id AND deleted_at IS NULL LIMIT 1", ['id' => $voucherId])
            : null;
        $record['code'] = (string) ($current['code'] ?? $this->generateVoucherCode($record['voucher_type']));

        $this->pdo()->beginTransaction();
        try {
            if ($voucherId === null) {
                $this->dbExecute(
                    "INSERT INTO vouchers (
                        voucher_type, name, code, value, price_value, usage_limit, used_count, expired_at, status,
                        location_name, message_text, service_items_json, combine_quantity, max_quantity, expiry_mode, expiry_value,
                        created_at, updated_at, deleted_at
                    ) VALUES (
                        :voucher_type, :name, :code, :value, :price_value, :usage_limit, 0, :expired_at, :status,
                        :location_name, :message_text, :service_items_json, :combine_quantity, :max_quantity, :expiry_mode, :expiry_value,
                        NOW(), NOW(), NULL
                    )",
                    $record
                );
                $voucherId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE vouchers
                     SET voucher_type = :voucher_type,
                         name = :name,
                         value = :value,
                         price_value = :price_value,
                         usage_limit = :usage_limit,
                         expired_at = :expired_at,
                         status = :status,
                         location_name = :location_name,
                         message_text = :message_text,
                         service_items_json = :service_items_json,
                         combine_quantity = :combine_quantity,
                         max_quantity = :max_quantity,
                         expiry_mode = :expiry_mode,
                         expiry_value = :expiry_value,
                         updated_at = NOW()
                     WHERE id = :id AND deleted_at IS NULL",
                    $record + ['id' => $voucherId]
                );
            }

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => ($current === null ? 'Menambah' : 'Memperbarui') . ' voucher #' . $voucherId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }

        return $this->findVoucherRow($voucherId) ?? throw new \RuntimeException('Voucher gagal dimuat ulang setelah disimpan.');
    }

    public function deleteVoucher(int $voucherId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan voucher membutuhkan koneksi database.');
        }

        if ($voucherId <= 0) {
            throw new \InvalidArgumentException('Voucher tidak valid.');
        }

        $voucher = $this->dbOne("SELECT id FROM vouchers WHERE id = :id AND deleted_at IS NULL LIMIT 1", ['id' => $voucherId]);
        if ($voucher === null) {
            throw new \InvalidArgumentException('Voucher tidak ditemukan.');
        }

        $this->pdo()->beginTransaction();
        try {
            $this->dbExecute(
                "UPDATE vouchers
                 SET deleted_at = NOW(), status = 'Nonaktif', updated_at = NOW()
                 WHERE id = :id",
                ['id' => $voucherId]
            );

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Menghapus voucher #' . $voucherId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }
    }

    public function saveVoucherDiscount(?int $discountId, array $payload, string $actorName): array
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penyimpanan diskon membutuhkan koneksi database.');
        }

        $this->ensureVoucherCatalogSchema();
        $record = $this->normalizeVoucherDiscountPayload($payload);
        if ($record['name'] === '') {
            throw new \InvalidArgumentException('Nama diskon wajib diisi.');
        }

        $current = $discountId !== null
            ? $this->dbOne("SELECT id FROM voucher_discounts WHERE id = :id AND deleted_at IS NULL LIMIT 1", ['id' => $discountId])
            : null;

        $this->pdo()->beginTransaction();
        try {
            if ($discountId === null) {
                $this->dbExecute(
                    "INSERT INTO voucher_discounts (
                        name, mode, amount_value, max_discount_value, scopes_json, status, created_at, updated_at, deleted_at
                    ) VALUES (
                        :name, :mode, :amount_value, :max_discount_value, :scopes_json, :status, NOW(), NOW(), NULL
                    )",
                    $record
                );
                $discountId = (int) $this->pdo()->lastInsertId();
            } else {
                $this->dbExecute(
                    "UPDATE voucher_discounts
                     SET name = :name,
                         mode = :mode,
                         amount_value = :amount_value,
                         max_discount_value = :max_discount_value,
                         scopes_json = :scopes_json,
                         status = :status,
                         updated_at = NOW()
                     WHERE id = :id AND deleted_at IS NULL",
                    $record + ['id' => $discountId]
                );
            }

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => ($current === null ? 'Menambah' : 'Memperbarui') . ' diskon voucher #' . $discountId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }

        return $this->findVoucherDiscountRow($discountId) ?? throw new \RuntimeException('Diskon gagal dimuat ulang setelah disimpan.');
    }

    public function deleteVoucherDiscount(int $discountId, string $actorName): void
    {
        if (!$this->usingDb()) {
            throw new \RuntimeException('Penghapusan diskon membutuhkan koneksi database.');
        }

        if ($discountId <= 0) {
            throw new \InvalidArgumentException('Diskon tidak valid.');
        }

        $discount = $this->dbOne("SELECT id FROM voucher_discounts WHERE id = :id AND deleted_at IS NULL LIMIT 1", ['id' => $discountId]);
        if ($discount === null) {
            throw new \InvalidArgumentException('Diskon tidak ditemukan.');
        }

        $this->pdo()->beginTransaction();
        try {
            $this->dbExecute(
                "UPDATE voucher_discounts
                 SET deleted_at = NOW(), status = 'Nonaktif', updated_at = NOW()
                 WHERE id = :id",
                ['id' => $discountId]
            );

            if ($this->tableExists('activity_logs')) {
                $this->dbExecute(
                    "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                     VALUES (NULL, :actor_name, :action_text, NOW())",
                    [
                        'actor_name' => $actorName,
                        'action_text' => 'Menghapus diskon voucher #' . $discountId,
                    ]
                );
            }

            $this->pdo()->commit();
        } catch (\Throwable $throwable) {
            if ($this->pdo()->inTransaction()) {
                $this->pdo()->rollBack();
            }

            throw $throwable;
        }
    }

    public function findCustomer(int $customerId): ?array
    {
        foreach ($this->getCustomers() as $customer) {
            if ($customer['id'] === $customerId) {
                return $customer;
            }
        }

        return null;
    }

    public function findStaff(int $staffId): ?array
    {
        foreach ($this->getStaff() as $staff) {
            if ($staff['id'] === $staffId) {
                return $staff;
            }
        }

        return null;
    }

    public function findService(int $serviceId): ?array
    {
        foreach ($this->getServices() as $service) {
            if ($service['id'] === $serviceId) {
                return $service;
            }
        }

        return null;
    }

    private function inventorySupplierMeta(): array
    {
        if (!$this->inventoryDbEnabled() || !$this->tableExists('suppliers')) {
            return [];
        }

        $hasDescription = $this->columnExists('suppliers', 'description');
        $hasContactName = $this->columnExists('suppliers', 'contact_name');
        $hasWebsite = $this->columnExists('suppliers', 'website');
        $hasAddress = $this->columnExists('suppliers', 'address');
        $hasCity = $this->columnExists('suppliers', 'city');
        $hasCountry = $this->columnExists('suppliers', 'country');
        $hasPostalCode = $this->columnExists('suppliers', 'postal_code');

        $rows = $this->dbAll(
            'SELECT id, name, phone, email'
            . ($hasDescription ? ', description' : '')
            . ($hasContactName ? ', contact_name' : '')
            . ($hasWebsite ? ', website' : '')
            . ($hasAddress ? ', address' : '')
            . ($hasCity ? ', city' : '')
            . ($hasCountry ? ', country' : '')
            . ($hasPostalCode ? ', postal_code' : '')
            . ' FROM suppliers ORDER BY id'
        );

        $meta = [];
        foreach ($rows as $row) {
            $addressParts = array_values(array_filter([
                $hasAddress ? (string) ($row['address'] ?? '') : '',
                $hasCity ? (string) ($row['city'] ?? '') : '',
                $hasCountry ? (string) ($row['country'] ?? '') : '',
                $hasPostalCode ? (string) ($row['postal_code'] ?? '') : '',
            ], static fn (string $value): bool => $value !== ''));

            $meta[(string) $row['name']] = [
                'description' => $hasDescription ? (string) ($row['description'] ?? '') : '',
                'contact' => $hasContactName
                    ? (string) ($row['contact_name'] ?? '')
                    : (string) ($row['email'] ?: ($row['phone'] ?: 'Tim Supplier')),
                'email' => (string) ($row['email'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'website' => $hasWebsite ? (string) ($row['website'] ?? '') : '',
                'address' => $addressParts !== [] ? implode(', ', $addressParts) : 'Bangkok',
                'city' => $hasCity ? (string) ($row['city'] ?? '') : 'Bangkok',
                'country' => $hasCountry ? (string) ($row['country'] ?? '') : 'Thailand',
                'postal' => $hasPostalCode ? (string) ($row['postal_code'] ?? '') : '',
                'lead_time' => '3 hari',
                'status' => 'Aktif',
            ];
        }

        return $meta;
    }

    public function getInventoryBrands(): array
    {
        if (!$this->inventoryDbEnabled() || !$this->tableExists('brands')) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }, $this->dbAll('SELECT id, name FROM brands ORDER BY name ASC, id ASC'));
    }

    public function getInventoryCategories(): array
    {
        if (!$this->inventoryDbEnabled() || !$this->tableExists('categories')) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }, $this->dbAll('SELECT id, name FROM categories ORDER BY name ASC, id ASC'));
    }

    public function getInventorySuppliers(): array
    {
        if (!$this->inventoryDbEnabled() || !$this->tableExists('suppliers')) {
            return [];
        }

        $this->ensureInventoryWorkflowSchema();

        $hasDescription = $this->columnExists('suppliers', 'description');
        $hasContactName = $this->columnExists('suppliers', 'contact_name');
        $hasWebsite = $this->columnExists('suppliers', 'website');
        $hasAddress = $this->columnExists('suppliers', 'address');
        $hasCity = $this->columnExists('suppliers', 'city');
        $hasCountry = $this->columnExists('suppliers', 'country');
        $hasPostalCode = $this->columnExists('suppliers', 'postal_code');

        $rows = $this->dbAll(
            'SELECT id, name, phone, email'
            . ($hasDescription ? ', description' : '')
            . ($hasContactName ? ', contact_name' : '')
            . ($hasWebsite ? ', website' : '')
            . ($hasAddress ? ', address' : '')
            . ($hasCity ? ', city' : '')
            . ($hasCountry ? ', country' : '')
            . ($hasPostalCode ? ', postal_code' : '')
            . ' FROM suppliers ORDER BY name ASC, id ASC'
        );

        return array_map(static function (array $row) use ($hasDescription, $hasContactName, $hasWebsite, $hasAddress, $hasCity, $hasCountry, $hasPostalCode): array {
            return [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'description' => $hasDescription ? (string) ($row['description'] ?? '') : '',
                'contact' => $hasContactName ? (string) ($row['contact_name'] ?? '') : '',
                'email' => (string) ($row['email'] ?? ''),
                'phone' => (string) ($row['phone'] ?? ''),
                'website' => $hasWebsite ? (string) ($row['website'] ?? '') : '',
                'address' => $hasAddress ? (string) ($row['address'] ?? '') : '',
                'city' => $hasCity ? (string) ($row['city'] ?? '') : '',
                'country' => $hasCountry ? (string) ($row['country'] ?? '') : '',
                'postal' => $hasPostalCode ? (string) ($row['postal_code'] ?? '') : '',
            ];
        }, $rows);
    }

    private function inventoryPurchaseRows(): array
    {
        if (!$this->inventoryDbEnabled() || !$this->tableExists('purchase_orders') || !$this->tableExists('suppliers')) {
            return [];
        }

        $hasLocationId = $this->columnExists('purchase_orders', 'location_id');
        $hasType = $this->columnExists('purchase_orders', 'order_type');
        $hasNote = $this->columnExists('purchase_orders', 'note');
        $hasTotal = $this->columnExists('purchase_orders', 'total_amount');
        $hasOrderedAt = $this->columnExists('purchase_orders', 'ordered_at');
        $hasReceivedAt = $this->columnExists('purchase_orders', 'received_at');
        $hasItems = $this->tableExists('purchase_order_items');
        $hasReceivingLogs = $this->tableExists('purchase_order_receiving_logs');

        $rows = $this->dbAll(
            'SELECT po.id, po.reference, po.order_date, po.status, s.name AS supplier_name'
            . ($this->columnExists('suppliers', 'email') ? ', s.email AS supplier_email' : '')
            . ($hasLocationId ? ', l.name AS location_name' : '')
            . ($hasType ? ', po.order_type' : '')
            . ($hasNote ? ', po.note' : '')
            . ($hasTotal ? ', po.total_amount' : '')
            . ($hasOrderedAt ? ', po.ordered_at' : '')
            . ($hasReceivedAt ? ', po.received_at' : '')
            . ' FROM purchase_orders po
                JOIN suppliers s ON s.id = po.supplier_id'
            . ($hasLocationId ? ' LEFT JOIN locations l ON l.id = po.location_id' : '')
            . ' ORDER BY po.order_date DESC, po.id DESC'
        );

        $itemsByOrder = [];
        if ($hasItems && $this->tableExists('products')) {
            $itemRows = $this->dbAll(
                "SELECT poi.purchase_order_id, poi.product_id, poi.quantity, poi.supply_price, p.name AS product_name
                 FROM purchase_order_items poi
                 JOIN products p ON p.id = poi.product_id
                 ORDER BY poi.purchase_order_id, poi.id"
            );

            foreach ($itemRows as $row) {
                $itemsByOrder[(int) $row['purchase_order_id']][] = [
                    'name' => (string) $row['product_name'],
                    'qty' => (int) $row['quantity'],
                    'price' => (float) $row['supply_price'],
                    'total' => (int) ((int) $row['quantity'] * (float) $row['supply_price']),
                ];
            }
        }

        $logsByOrder = [];
        if ($hasReceivingLogs) {
            $logRows = $this->dbAll(
                "SELECT prl.purchase_order_id, prl.product_name, prl.received_qty, prl.received_at, prl.supply_price
                 FROM purchase_order_receiving_logs prl
                 ORDER BY prl.purchase_order_id, prl.received_at DESC, prl.id DESC"
            );

            foreach ($logRows as $row) {
                $logsByOrder[(int) $row['purchase_order_id']][] = [
                    'product' => (string) $row['product_name'],
                    'qty' => (int) $row['received_qty'],
                    'date' => (string) $row['received_at'],
                    'price' => (float) $row['supply_price'],
                    'total' => (int) ((int) $row['received_qty'] * (float) $row['supply_price']),
                ];
            }
        }

        $defaultLocation = $this->getLocations()[0]['name'] ?? 'Star Salon';

        return array_map(function (array $row) use ($itemsByOrder, $logsByOrder, $defaultLocation, $hasType, $hasNote, $hasTotal, $hasOrderedAt, $hasReceivedAt): array {
            $orderId = (int) $row['id'];
            $items = $itemsByOrder[$orderId] ?? [];
            $total = $hasTotal && isset($row['total_amount'])
                ? (float) $row['total_amount']
                : array_reduce($items, static fn (float $carry, array $item): float => $carry + ((float) $item['price'] * (int) $item['qty']), 0.0);

            $createdAt = (string) ($hasOrderedAt && !empty($row['ordered_at']) ? $row['ordered_at'] : ((string) $row['order_date'] . ' 09:00:00'));
            $receivedAt = $hasReceivedAt && !empty($row['received_at']) ? (string) $row['received_at'] : null;
            $status = $this->normalizeInventoryPurchaseStatus((string) $row['status']);

            return [
                'id' => $orderId,
                'document' => (string) $row['reference'],
                'created_at' => $this->humanDate($createdAt),
                'type' => $hasType && !empty($row['order_type']) ? ucfirst((string) $row['order_type']) : 'Order',
                'supplier' => (string) $row['supplier_name'],
                'location' => (string) ($row['location_name'] ?? $defaultLocation),
                'total' => money($total),
                'status' => $status,
                'note' => $hasNote ? (string) ($row['note'] ?? '') : '',
                'ordered_at' => $this->humanDate($createdAt),
                'received_at' => $receivedAt !== null ? $this->humanDateTime($receivedAt) : '',
                'supplier_meta' => (string) ($row['supplier_email'] ?? ''),
                'items' => $items,
                'receiving_logs' => $logsByOrder[$orderId] ?? [],
            ];
        }, $rows);
    }

    private function inventoryOpnameRows(): array
    {
        if (!$this->inventoryDbEnabled()) {
            return [];
        }

        $this->ensureInventoryWorkflowSchema();

        $defaultLocation = $this->getLocations()[0]['name'] ?? 'Star Salon';

        if ($this->tableExists('stock_opname_sessions')) {
            $hasEndedAt = $this->columnExists('stock_opname_sessions', 'ended_at');
            $hasStatus = $this->columnExists('stock_opname_sessions', 'status');
            $hasNote = $this->columnExists('stock_opname_sessions', 'note');
            $hasLocationId = $this->columnExists('stock_opname_sessions', 'location_id');
            $hasStartedBy = $this->columnExists('stock_opname_sessions', 'started_by');
            $hasCancelledBy = $this->columnExists('stock_opname_sessions', 'cancelled_by');
            $hasCancelledNote = $this->columnExists('stock_opname_sessions', 'cancelled_note');

            $rows = $this->dbAll(
                'SELECT sos.id, sos.name, sos.started_at'
                . ($hasEndedAt ? ', sos.ended_at' : '')
                . ($hasStatus ? ', sos.status' : '')
                . ($hasNote ? ', sos.note' : '')
                . ($hasStartedBy ? ', sos.started_by' : '')
                . ($hasCancelledBy ? ', sos.cancelled_by' : '')
                . ($hasCancelledNote ? ', sos.cancelled_note' : '')
                . ($hasLocationId ? ', l.name AS location_name' : '')
                . ' FROM stock_opname_sessions sos'
                . ($hasLocationId ? ' LEFT JOIN locations l ON l.id = sos.location_id' : '')
                . ' ORDER BY sos.started_at DESC, sos.id DESC'
            );

            $itemsBySession = [];
            if ($this->tableExists('stock_opname_session_items') && $this->tableExists('products')) {
                $productSkuColumn = $this->columnExists('products', 'sku')
                    ? 'p.sku'
                    : ($this->columnExists('products', 'code') ? 'p.code' : "''");
                $itemRows = $this->dbAll(
                    "SELECT sosi.session_id, sosi.expected_stock, sosi.counted_stock, p.name AS product_name, {$productSkuColumn} AS product_sku
                     FROM stock_opname_session_items sosi
                     JOIN products p ON p.id = sosi.product_id
                     ORDER BY sosi.session_id, sosi.id"
                );

                foreach ($itemRows as $itemRow) {
                    $expected = (int) $itemRow['expected_stock'];
                    $counted = (int) $itemRow['counted_stock'];
                    $itemsBySession[(int) $itemRow['session_id']][] = [
                        'name' => (string) $itemRow['product_name'],
                        'sku' => (string) (($itemRow['product_sku'] ?? '') ?: '-'),
                        'expected' => $expected,
                        'counted' => $counted,
                        'diff' => $counted - $expected,
                        'cost' => ($counted - $expected) * 25000,
                    ];
                }
            }

            return array_map(function (array $row) use ($defaultLocation, $hasEndedAt, $hasStatus, $hasNote, $hasStartedBy, $hasCancelledBy, $hasCancelledNote, $itemsBySession): array {
                $startedAt = (string) $row['started_at'];
                $status = $hasStatus && !empty($row['status']) ? $this->normalizeInventoryOpnameStatus((string) $row['status']) : 'Meninjau';
                $sessionId = (int) $row['id'];

                return [
                    'id' => $sessionId,
                    'name' => (string) $row['name'],
                    'location' => (string) ($row['location_name'] ?? $defaultLocation),
                    'started_at' => $this->humanDateTime($startedAt),
                    'ended_at' => $hasEndedAt && !empty($row['ended_at']) ? $this->humanDateTime((string) $row['ended_at']) : '-',
                    'status' => $status,
                    'note' => $hasNote ? (string) ($row['note'] ?? '') : '',
                    'started_by' => $hasStartedBy ? (string) ($row['started_by'] ?? '') : '',
                    'cancelled_by' => $hasCancelledBy ? (string) ($row['cancelled_by'] ?? '') : '',
                    'cancelled_note' => $hasCancelledNote ? (string) ($row['cancelled_note'] ?? '') : '',
                    'items' => $itemsBySession[$sessionId] ?? [],
                ];
            }, $rows);
        }

        if (!$this->tableExists('stock_opnames')) {
            return [];
        }

        $rows = $this->dbAll(
            "SELECT so.id, so.expected_stock, so.actual_stock, so.note, so.created_at
             FROM stock_opnames so
             ORDER BY so.created_at DESC, so.id DESC"
        );

        return array_map(function (array $row) use ($defaultLocation): array {
            $difference = (int) $row['actual_stock'] - (int) $row['expected_stock'];

            return [
                'id' => (int) $row['id'],
                'name' => 'Stock Opname #' . (int) $row['id'],
                'location' => $defaultLocation,
                'started_at' => $this->humanDateTime((string) $row['created_at']),
                'ended_at' => $difference === 0 ? $this->humanDateTime((string) $row['created_at']) : '-',
                'status' => $difference === 0 ? 'Completed' : 'Perhitungan',
                'note' => (string) ($row['note'] ?? ''),
            ];
        }, $rows);
    }

    private function inventoryOpnameDetailProducts(array $products): array
    {
        if (!$this->canUsePdo() || !$this->tableExists('products')) {
            return [];
        }

        return array_map(static function (array $product): array {
            return [
                'id' => (int) ($product['id'] ?? 0),
                'name' => (string) $product['name'],
                'code' => (string) ($product['sku'] !== '' ? $product['sku'] : '-'),
                'sku' => (string) ($product['sku'] !== '' ? $product['sku'] : '-'),
                'expected' => (int) $product['stock'],
            ];
        }, $products);
    }

    public function saveInventoryMasterItem(string $type, ?int $id, string $name): array
    {
        $this->ensureInventoryWorkflowSchema();

        $table = match ($type) {
            'brands' => 'brands',
            'categories' => 'categories',
            default => throw new \InvalidArgumentException('Tipe master item tidak valid.'),
        };

        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Nama wajib diisi.');
        }

        if ($id !== null && $id > 0) {
            $this->dbExecute(
                "UPDATE {$table} SET name = :name WHERE id = :id",
                ['name' => $name, 'id' => $id]
            );

            return ['id' => $id, 'name' => $name];
        }

        $existing = $this->dbOne("SELECT id, name FROM {$table} WHERE LOWER(name) = LOWER(:name) LIMIT 1", ['name' => $name]);
        if ($existing !== null) {
            return ['id' => (int) $existing['id'], 'name' => (string) $existing['name']];
        }

        $this->dbExecute("INSERT INTO {$table} (name) VALUES (:name)", ['name' => $name]);

        return [
            'id' => (int) $this->pdo()->lastInsertId(),
            'name' => $name,
        ];
    }

    public function deleteInventoryMasterItem(string $type, int $id): void
    {
        $table = match ($type) {
            'brands' => 'brands',
            'categories' => 'categories',
            default => throw new \InvalidArgumentException('Tipe master item tidak valid.'),
        };

        $this->dbExecute("DELETE FROM {$table} WHERE id = :id", ['id' => $id]);
    }

    public function saveInventorySupplier(?int $id, array $payload): array
    {
        $this->ensureInventoryWorkflowSchema();

        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nama supplier wajib diisi.');
        }

        $params = [
            'name' => $name,
            'description' => trim((string) ($payload['description'] ?? '')),
            'contact_name' => trim((string) ($payload['contact'] ?? '')),
            'email' => trim((string) ($payload['email'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'website' => trim((string) ($payload['website'] ?? '')),
            'address' => trim((string) ($payload['address'] ?? '')),
            'city' => trim((string) ($payload['city'] ?? '')),
            'country' => trim((string) ($payload['country'] ?? '')),
            'postal_code' => trim((string) ($payload['postal'] ?? '')),
        ];

        if ($id !== null && $id > 0) {
            $this->dbExecute(
                "UPDATE suppliers
                 SET name = :name,
                     description = :description,
                     contact_name = :contact_name,
                     email = :email,
                     phone = :phone,
                     website = :website,
                     address = :address,
                     city = :city,
                     country = :country,
                     postal_code = :postal_code
                 WHERE id = :id",
                $params + ['id' => $id]
            );

            return $this->inventorySupplierById($id) ?? ['id' => $id] + $payload;
        }

        $this->dbExecute(
            "INSERT INTO suppliers (name, description, contact_name, email, phone, website, address, city, country, postal_code)
             VALUES (:name, :description, :contact_name, :email, :phone, :website, :address, :city, :country, :postal_code)",
            $params
        );

        $supplierId = (int) $this->pdo()->lastInsertId();

        return $this->inventorySupplierById($supplierId) ?? ['id' => $supplierId] + $payload;
    }

    public function deleteInventorySupplier(int $id): void
    {
        $this->dbExecute('DELETE FROM suppliers WHERE id = :id', ['id' => $id]);
    }

    public function saveInventoryProduct(int $id, array $payload): array
    {
        $name = trim((string) ($payload['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nama produk wajib diisi.');
        }

        $categoryName = trim((string) ($payload['category'] ?? ''));
        $brandName = trim((string) ($payload['brand'] ?? ''));
        $hasBrandId = $this->columnExists('products', 'brand_id') && $this->tableExists('brands');
        $hasCategoryId = $this->columnExists('products', 'category_id') && $this->tableExists('categories');
        $hasBrandText = $this->columnExists('products', 'brand');
        $hasCategoryText = $this->columnExists('products', 'category');
        $hasSku = $this->columnExists('products', 'sku');
        $hasCode = $this->columnExists('products', 'code');
        $hasSellPrice = $this->columnExists('products', 'sell_price');
        $hasSellingPrice = $this->columnExists('products', 'selling_price');
        $categoryId = $hasCategoryId ? $this->inventoryEnsureNamedRecord('categories', $categoryName) : null;
        $brandId = $hasBrandId ? $this->inventoryEnsureNamedRecord('brands', $brandName) : null;
        $price = (float) ($payload['price'] ?? 0);
        $status = trim((string) ($payload['status'] ?? 'Aktif'));

        if ($id <= 0) {
            $nextCode = 'PRD' . str_pad((string) ((int) ($this->dbOne('SELECT MAX(id) AS max_id FROM products')['max_id'] ?? 0) + 1), 4, '0', STR_PAD_LEFT);
            $columns = ['name', 'stock', 'status'];
            $placeholders = [':name', '0', ':status'];
            $params = [
                'name' => $name,
                'status' => $status,
            ];

            if ($hasBrandId) {
                $columns[] = 'brand_id';
                $placeholders[] = ':brand_id';
                $params['brand_id'] = $brandId;
            } elseif ($hasBrandText) {
                $columns[] = 'brand';
                $placeholders[] = ':brand';
                $params['brand'] = $brandName !== '' ? $brandName : null;
            }

            if ($hasCategoryId) {
                $columns[] = 'category_id';
                $placeholders[] = ':category_id';
                $params['category_id'] = $categoryId;
            } elseif ($hasCategoryText) {
                $columns[] = 'category';
                $placeholders[] = ':category';
                $params['category'] = $categoryName !== '' ? $categoryName : null;
            }

            if ($hasSku) {
                $columns[] = 'sku';
                $placeholders[] = ':sku';
                $params['sku'] = $nextCode;
            } elseif ($hasCode) {
                $columns[] = 'code';
                $placeholders[] = ':code';
                $params['code'] = $nextCode;
            }

            if ($hasSellPrice) {
                $columns[] = 'sell_price';
                $placeholders[] = ':sell_price';
                $params['sell_price'] = $price;
            } elseif ($hasSellingPrice) {
                $columns[] = 'selling_price';
                $placeholders[] = ':selling_price';
                $params['selling_price'] = $price;
            }

            $this->dbExecute(
                sprintf(
                    'INSERT INTO products (%s) VALUES (%s)',
                    implode(', ', $columns),
                    implode(', ', $placeholders)
                ),
                $params
            );

            return $this->inventoryProductById((int) $this->pdo()->lastInsertId()) ?? [];
        }

        $assignments = ['name = :name', 'status = :status'];
        $params = [
            'id' => $id,
            'name' => $name,
            'status' => $status,
        ];

        if ($hasCategoryId) {
            $assignments[] = 'category_id = :category_id';
            $params['category_id'] = $categoryId;
        } elseif ($hasCategoryText) {
            $assignments[] = 'category = :category';
            $params['category'] = $categoryName !== '' ? $categoryName : null;
        }

        if ($hasBrandId) {
            $assignments[] = 'brand_id = :brand_id';
            $params['brand_id'] = $brandId;
        } elseif ($hasBrandText) {
            $assignments[] = 'brand = :brand';
            $params['brand'] = $brandName !== '' ? $brandName : null;
        }

        if ($hasSellPrice) {
            $assignments[] = 'sell_price = :sell_price';
            $params['sell_price'] = $price;
        } elseif ($hasSellingPrice) {
            $assignments[] = 'selling_price = :selling_price';
            $params['selling_price'] = $price;
        }

        $this->dbExecute(
            sprintf(
                'UPDATE products SET %s WHERE id = :id',
                implode(', ', $assignments)
            ),
            $params
        );

        return $this->inventoryProductById($id) ?? [];
    }

    public function adjustInventoryProductStock(int $productId, string $mode, int $quantity, float $supplyPrice, string $reason, string $note, string $actorName): array
    {
        $direction = $mode === 'decrease' ? 'decrease' : 'increase';
        $quantity = max(1, $quantity);
        $this->pdo()->beginTransaction();

        try {
            $product = $this->dbOne('SELECT id, stock FROM products WHERE id = :id LIMIT 1 FOR UPDATE', ['id' => $productId]);
            if ($product === null) {
                throw new \RuntimeException('Produk tidak ditemukan.');
            }

            $currentStock = (int) $product['stock'];
            $delta = $direction === 'decrease' ? -min($quantity, $currentStock) : $quantity;
            $nextStock = max(0, $currentStock + $delta);

            $this->dbExecute(
                'UPDATE products SET stock = :stock, status = :status WHERE id = :id',
                [
                    'id' => $productId,
                    'stock' => $nextStock,
                    'status' => $nextStock > 0 ? 'Aman' : 'Kosong',
                ]
            );

            $movementType = $direction === 'decrease' ? 'stock_out' : 'stock_in';
            $movementNote = trim($reason . ($note !== '' ? ' - ' . $note : ''));
            $this->dbExecute(
                'INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_at)
                 VALUES (:product_id, :movement_type, :quantity, :note, NOW())',
                [
                    'product_id' => $productId,
                    'movement_type' => $movementType,
                    'quantity' => $delta,
                    'note' => $movementNote,
                ]
            );

            $this->dbExecute(
                "INSERT INTO activity_logs (user_id, actor_name, action_text, created_at)
                 VALUES (NULL, :actor_name, :action_text, NOW())",
                [
                    'actor_name' => $actorName,
                    'action_text' => sprintf('Penyesuaian stok produk #%d (%s %d)', $productId, $direction, $quantity),
                ]
            );

            $this->pdo()->commit();

            return [
                'product' => $this->inventoryProductById($productId) ?? [],
                'movement' => [
                    'mode' => $direction,
                    'qty' => abs($delta),
                    'actual_delta' => $delta,
                    'current_qty' => $nextStock,
                    'cost' => $supplyPrice,
                    'reason' => $reason,
                    'note' => $note,
                    'created_at' => date('d F Y, H:i:s'),
                    'staff' => $actorName,
                    'location' => $this->getLocations()[0]['name'] ?? 'Star Salon',
                ],
            ];
        } catch (\Throwable $throwable) {
            $this->pdo()->rollBack();
            throw $throwable;
        }
    }

    public function createInventoryPurchaseOrder(array $payload): array
    {
        $this->ensureInventoryWorkflowSchema();

        $supplierId = (int) ($payload['supplier_id'] ?? 0);
        $locationId = (int) ($payload['location_id'] ?? 0);
        $type = trim((string) ($payload['type'] ?? 'Order'));
        $note = trim((string) ($payload['note'] ?? ''));
        $items = array_values(array_filter($payload['items'] ?? [], static fn ($item): bool => is_array($item)));
        if ($supplierId <= 0 || $locationId <= 0 || $items === []) {
            throw new \InvalidArgumentException('Data pesanan belum lengkap.');
        }

        $reference = $this->nextPurchaseReference();
        $total = array_reduce($items, static fn (float $carry, array $item): float => $carry + (((int) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))), 0.0);

        $this->pdo()->beginTransaction();
        try {
            $this->dbExecute(
                "INSERT INTO purchase_orders
                 (supplier_id, location_id, reference, order_type, order_date, status, note, total_amount, ordered_at)
                 VALUES (:supplier_id, :location_id, :reference, :order_type, CURDATE(), 'ordered', :note, :total_amount, NOW())",
                [
                    'supplier_id' => $supplierId,
                    'location_id' => $locationId,
                    'reference' => $reference,
                    'order_type' => $type,
                    'note' => $note,
                    'total_amount' => $total,
                ]
            );

            $orderId = (int) $this->pdo()->lastInsertId();
            foreach ($items as $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                if ($productId <= 0) {
                    $productId = $this->inventoryProductIdByName((string) ($item['name'] ?? ''));
                }
                if ($productId <= 0) {
                    continue;
                }

                $this->dbExecute(
                    'INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, supply_price)
                     VALUES (:purchase_order_id, :product_id, :quantity, :supply_price)',
                    [
                        'purchase_order_id' => $orderId,
                        'product_id' => $productId,
                        'quantity' => max(1, (int) ($item['qty'] ?? 1)),
                        'supply_price' => (float) ($item['price'] ?? 0),
                    ]
                );
            }

            $this->pdo()->commit();

            return $this->inventoryPurchaseRowById($orderId) ?? [];
        } catch (\Throwable $throwable) {
            $this->pdo()->rollBack();
            throw $throwable;
        }
    }

    public function receiveInventoryPurchaseOrder(int $orderId, array $items): array
    {
        $this->ensureInventoryWorkflowSchema();
        $this->pdo()->beginTransaction();

        try {
            $orderItems = $this->dbAll(
                "SELECT poi.id, poi.product_id, poi.quantity, poi.supply_price, p.name AS product_name
                 FROM purchase_order_items poi
                 JOIN products p ON p.id = poi.product_id
                 WHERE poi.purchase_order_id = :purchase_order_id
                 ORDER BY poi.id",
                ['purchase_order_id' => $orderId]
            );

            $this->dbExecute('DELETE FROM purchase_order_receiving_logs WHERE purchase_order_id = :purchase_order_id', ['purchase_order_id' => $orderId]);

            foreach ($orderItems as $index => $orderItem) {
                $receivedQty = max(0, (int) (($items[$index]['received_qty'] ?? $orderItem['quantity'])));
                $this->dbExecute(
                    'INSERT INTO purchase_order_receiving_logs (purchase_order_id, product_name, received_qty, supply_price, received_at, note)
                     VALUES (:purchase_order_id, :product_name, :received_qty, :supply_price, NOW(), NULL)',
                    [
                        'purchase_order_id' => $orderId,
                        'product_name' => (string) $orderItem['product_name'],
                        'received_qty' => $receivedQty,
                        'supply_price' => (float) $orderItem['supply_price'],
                    ]
                );

                $this->dbExecute(
                    'UPDATE products SET stock = stock + :received_qty WHERE id = :id',
                    [
                        'received_qty' => $receivedQty,
                        'id' => (int) $orderItem['product_id'],
                    ]
                );

                $this->dbExecute(
                    'INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_at)
                     VALUES (:product_id, :movement_type, :quantity, :note, NOW())',
                    [
                        'product_id' => (int) $orderItem['product_id'],
                        'movement_type' => 'purchase_receive',
                        'quantity' => $receivedQty,
                        'note' => 'Penerimaan order ' . $orderId,
                    ]
                );
            }

            $this->dbExecute(
                "UPDATE purchase_orders
                 SET status = 'received', received_at = NOW()
                 WHERE id = :id",
                ['id' => $orderId]
            );

            $this->pdo()->commit();

            return $this->inventoryPurchaseRowById($orderId) ?? [];
        } catch (\Throwable $throwable) {
            $this->pdo()->rollBack();
            throw $throwable;
        }
    }

    public function cancelInventoryPurchaseOrder(int $orderId): array
    {
        $this->ensureInventoryWorkflowSchema();
        $this->dbExecute(
            "UPDATE purchase_orders
             SET status = 'cancelled'
             WHERE id = :id",
            ['id' => $orderId]
        );

        return $this->inventoryPurchaseRowById($orderId) ?? [];
    }

    public function saveInventoryOpname(array $payload): array
    {
        if (!$this->inventoryDbEnabled()) {
            return [];
        }

        return $this->withInventoryWorkflowRepair(function () use ($payload): array {
            $this->ensureInventoryWorkflowSchema();

            $sessionId = (int) ($payload['id'] ?? 0);
            $name = trim((string) ($payload['name'] ?? 'Stock Opname'));
            $note = trim((string) ($payload['note'] ?? ''));
            $status = trim((string) ($payload['status'] ?? 'Meninjau'));
            $locationId = (int) ($payload['location_id'] ?? 0);
            $startedBy = trim((string) ($payload['started_by'] ?? 'Rayhan Doni Pramana'));
            $items = array_values(array_filter($payload['items'] ?? [], static fn ($item): bool => is_array($item)));

            if ($locationId <= 0) {
                $locationId = (int) ($this->getLocations()[0]['id'] ?? 1);
            }

            $startedAt = (string) ($payload['started_at'] ?? date('Y-m-d H:i:s'));
            $endedAt = (string) ($payload['ended_at'] ?? '');
            $cancelledNote = trim((string) ($payload['cancelled_note'] ?? ''));
            $cancelledBy = trim((string) ($payload['cancelled_by'] ?? ''));

            $this->pdo()->beginTransaction();
            try {
                if ($sessionId > 0) {
                    $this->dbExecute(
                        "UPDATE stock_opname_sessions
                         SET name = :name,
                             note = :note,
                             status = :status,
                             location_id = :location_id,
                             ended_at = :ended_at,
                             cancelled_note = :cancelled_note,
                             cancelled_by = :cancelled_by
                         WHERE id = :id",
                        [
                            'id' => $sessionId,
                            'name' => $name,
                            'note' => $note,
                            'status' => $status,
                            'location_id' => $locationId,
                            'ended_at' => $endedAt !== '' ? $endedAt : null,
                            'cancelled_note' => $cancelledNote !== '' ? $cancelledNote : null,
                            'cancelled_by' => $cancelledBy !== '' ? $cancelledBy : null,
                        ]
                    );
                    $this->dbExecute('DELETE FROM stock_opname_session_items WHERE session_id = :session_id', ['session_id' => $sessionId]);
                } else {
                    $this->dbExecute(
                        "INSERT INTO stock_opname_sessions (location_id, name, note, status, started_at, ended_at, started_by, cancelled_by, cancelled_note)
                         VALUES (:location_id, :name, :note, :status, :started_at, :ended_at, :started_by, :cancelled_by, :cancelled_note)",
                        [
                            'location_id' => $locationId,
                            'name' => $name,
                            'note' => $note,
                            'status' => $status,
                            'started_at' => $startedAt,
                            'ended_at' => $endedAt !== '' ? $endedAt : null,
                            'started_by' => $startedBy,
                            'cancelled_by' => $cancelledBy !== '' ? $cancelledBy : null,
                            'cancelled_note' => $cancelledNote !== '' ? $cancelledNote : null,
                        ]
                    );
                    $sessionId = (int) $this->pdo()->lastInsertId();
                }

                foreach ($items as $item) {
                    $productId = (int) ($item['product_id'] ?? 0);
                    if ($productId <= 0) {
                        $productId = $this->inventoryProductIdByName((string) ($item['name'] ?? ''));
                    }
                    if ($productId <= 0) {
                        continue;
                    }

                    $expected = (int) ($item['expected'] ?? 0);
                    $counted = (int) ($item['counted'] ?? 0);
                    $diff = $counted - $expected;
                    $itemStatus = $diff === 0 ? 'counted' : 'exception';

                    $this->dbExecute(
                        'INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
                         VALUES (:session_id, :product_id, :expected_stock, :counted_stock, :item_status, :note)',
                        [
                            'session_id' => $sessionId,
                            'product_id' => $productId,
                            'expected_stock' => $expected,
                            'counted_stock' => $counted,
                            'item_status' => $itemStatus,
                            'note' => trim((string) ($item['note'] ?? '')),
                        ]
                    );
                }

                $this->pdo()->commit();

                return $this->inventoryOpnameRowById($sessionId) ?? [];
            } catch (\Throwable $throwable) {
                if ($this->pdo()->inTransaction()) {
                    $this->pdo()->rollBack();
                }

                throw $throwable;
            }
        });
    }

    public function recountInventoryOpname(int $sessionId): array
    {
        if (!$this->inventoryDbEnabled()) {
            return [];
        }

        return $this->withInventoryWorkflowRepair(function () use ($sessionId): array {
            $this->ensureInventoryWorkflowSchema();
            $this->dbExecute(
                "UPDATE stock_opname_sessions
                 SET status = 'Perhitungan', ended_at = NULL, cancelled_by = NULL, cancelled_note = NULL
                 WHERE id = :id",
                ['id' => $sessionId]
            );

            return $this->inventoryOpnameRowById($sessionId) ?? [];
        });
    }

    public function cancelInventoryOpname(int $sessionId, string $note, string $cancelledBy): array
    {
        if (!$this->inventoryDbEnabled()) {
            return [];
        }

        return $this->withInventoryWorkflowRepair(function () use ($sessionId, $note, $cancelledBy): array {
            $this->ensureInventoryWorkflowSchema();
            $this->dbExecute(
                "UPDATE stock_opname_sessions
             SET status = 'Cancelled',
                     ended_at = NOW(),
                     cancelled_by = :cancelled_by,
                     cancelled_note = :cancelled_note
                 WHERE id = :id",
                [
                    'id' => $sessionId,
                    'cancelled_by' => $cancelledBy,
                    'cancelled_note' => trim($note),
                ]
            );

            return $this->inventoryOpnameRowById($sessionId) ?? [];
        });
    }

    public function completeInventoryOpname(int $sessionId): array
    {
        if (!$this->inventoryDbEnabled()) {
            return [];
        }

        return $this->withInventoryWorkflowRepair(function () use ($sessionId): array {
            $this->ensureInventoryWorkflowSchema();
            $this->pdo()->beginTransaction();

            try {
                $items = $this->dbAll(
                    'SELECT product_id, expected_stock, counted_stock
                     FROM stock_opname_session_items
                     WHERE session_id = :session_id',
                    ['session_id' => $sessionId]
                );

                foreach ($items as $item) {
                    $countedStock = (int) $item['counted_stock'];
                    $expectedStock = (int) $item['expected_stock'];
                    $difference = $countedStock - $expectedStock;

                    if ($this->tableExists('products') && $this->columnExists('products', 'stock')) {
                        $assignments = ['stock = :stock'];
                        $params = [
                            'stock' => $countedStock,
                            'id' => (int) $item['product_id'],
                        ];

                        // Some schemas use status as 'Aman/Kosong', others use 'active'/'inactive'.
                        if ($this->columnExists('products', 'status')) {
                            $assignments[] = 'status = :status';
                            $params['status'] = $countedStock > 0 ? 'Aman' : 'Kosong';
                        }

                        $this->dbExecute(
                            sprintf('UPDATE products SET %s WHERE id = :id', implode(', ', $assignments)),
                            $params
                        );
                    }

                    if ($difference !== 0 && $this->tableExists('stock_movements')) {
                        $this->dbExecute(
                            'INSERT INTO stock_movements (product_id, movement_type, quantity, note, created_at)
                             VALUES (:product_id, :movement_type, :quantity, :note, NOW())',
                            [
                                'product_id' => (int) $item['product_id'],
                                'movement_type' => 'opname_adjustment',
                                'quantity' => $difference,
                                'note' => 'Stok opname #' . $sessionId,
                            ]
                        );
                    }
                }

                $this->dbExecute(
                    "UPDATE stock_opname_sessions
                 SET status = 'Completed', ended_at = NOW()
                     WHERE id = :id",
                    ['id' => $sessionId]
                );

                $this->pdo()->commit();

                return $this->inventoryOpnameRowById($sessionId) ?? [];
            } catch (\Throwable $throwable) {
                if ($this->pdo()->inTransaction()) {
                    $this->pdo()->rollBack();
                }

                throw $throwable;
            }
        });
    }

    public function inventoryProductById(int $productId): ?array
    {
        foreach ($this->getProducts() as $product) {
            if ((int) ($product['id'] ?? 0) === $productId) {
                return $product;
            }
        }

        return null;
    }

    public function getInventoryProductHistory(int $productId): array
    {
        $product = $this->inventoryProductById($productId);
        if ($product === null) {
            return [];
        }

        if (!$this->inventoryDbEnabled() || !$this->tableExists('stock_movements')) {
            return [];
        }

        $rows = $this->dbAll(
            'SELECT movement_type, quantity, note, created_at
             FROM stock_movements
             WHERE product_id = :product_id
             ORDER BY created_at DESC, id DESC',
            ['product_id' => $productId]
        );

        if ($rows === []) {
            return [[
                'date' => '28 April 2026',
                'time' => '15:39:51',
                'staffPrimary' => 'System',
                'staffSecondary' => '-',
                'location' => $this->getLocations()[0]['name'] ?? 'Star Salon',
                'action' => 'New stock',
                'delta' => (int) ($product['stock'] ?? 0),
                'cost' => (float) ($product['price'] ?? 0),
                'realQty' => (int) ($product['stock'] ?? 0),
            ]];
        }

        $runningQty = (int) ($product['stock'] ?? 0);
        $locationName = $this->getLocations()[0]['name'] ?? 'Star Salon';

        return array_map(function (array $row) use (&$runningQty, $locationName, $product): array {
            $delta = (int) $row['quantity'];
            $realQty = $runningQty;
            $runningQty -= $delta;

            $date = new \DateTimeImmutable((string) $row['created_at']);
            $note = trim((string) ($row['note'] ?? ''));

            return [
                'date' => $date->format('d F Y'),
                'time' => $date->format('H:i:s'),
                'staffPrimary' => 'System',
                'staffSecondary' => '-',
                'location' => $locationName,
                'action' => $this->inventoryMovementLabel((string) $row['movement_type'], $note),
                'delta' => $delta,
                'cost' => (float) ($product['price'] ?? 0),
                'realQty' => $realQty,
            ];
        }, $rows);
    }

    private function inventoryPurchaseRowById(int $orderId): ?array
    {
        foreach ($this->inventoryPurchaseRows() as $row) {
            if ((int) ($row['id'] ?? 0) === $orderId) {
                return $row;
            }
        }

        return null;
    }

    private function inventoryOpnameRowById(int $sessionId): ?array
    {
        foreach ($this->inventoryOpnameRows() as $row) {
            if ((int) ($row['id'] ?? 0) === $sessionId) {
                return $row;
            }
        }

        return null;
    }

    private function inventorySupplierById(int $supplierId): ?array
    {
        foreach ($this->getInventorySuppliers() as $supplier) {
            if ((int) ($supplier['id'] ?? 0) === $supplierId) {
                return $supplier;
            }
        }

        return null;
    }

    private function inventoryEnsureNamedRecord(string $table, string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $existing = $this->dbOne("SELECT id FROM {$table} WHERE LOWER(name) = LOWER(:name) LIMIT 1", ['name' => $name]);
        if ($existing !== null) {
            return (int) $existing['id'];
        }

        $this->dbExecute("INSERT INTO {$table} (name) VALUES (:name)", ['name' => $name]);

        return (int) $this->pdo()->lastInsertId();
    }

    private function inventoryLocationIdByName(string $name): int
    {
        $name = trim($name);
        foreach ($this->getLocations() as $location) {
            if (strcasecmp((string) $location['name'], $name) === 0) {
                return (int) $location['id'];
            }
        }

        return (int) ($this->getLocations()[0]['id'] ?? 1);
    }

    private function inventoryProductIdByName(string $name): int
    {
        $row = $this->dbOne('SELECT id FROM products WHERE LOWER(name) = LOWER(:name) LIMIT 1', ['name' => trim($name)]);

        return $row !== null ? (int) $row['id'] : 0;
    }

    private function nextPurchaseReference(): string
    {
        $row = $this->dbOne("SELECT reference FROM purchase_orders ORDER BY id DESC LIMIT 1");
        $current = 0;
        if ($row !== null && preg_match('/^P(\d+)$/i', (string) $row['reference'], $matches) === 1) {
            $current = (int) $matches[1];
        }

        return 'P' . str_pad((string) ($current + 1), 6, '0', STR_PAD_LEFT);
    }

    private function inventoryMovementLabel(string $movementType, string $note): string
    {
        if ($note !== '') {
            $parts = preg_split('/\s+-\s+/', $note, 2);
            if (is_array($parts) && isset($parts[0]) && trim((string) $parts[0]) !== '') {
                return trim((string) $parts[0]);
            }
        }

        return match (strtolower(trim($movementType))) {
            'stock_in' => 'New stock',
            'stock_out' => 'Other',
            'purchase_receive' => 'Penerimaan pesanan',
            'opname_adjustment' => 'Stok opname',
            default => 'Penyesuaian stok',
        };
    }

    private function ensureInventoryWorkflowSchema(): void
    {
        if (!$this->canUsePdo() || $this->inventorySchemaEnsured) {
            return;
        }

        foreach ([
            'stock_opname_session_items',
            'stock_opname_sessions',
            'purchase_order_receiving_logs',
            'purchase_order_items',
        ] as $workflowTable) {
            if ($this->tableListedInSchema($workflowTable) && !$this->tableCanBeQueried($workflowTable)) {
                $this->pdo()->exec(sprintf('DROP TABLE IF EXISTS `%s`', $workflowTable));
            }
        }

        $statements = [
            'CREATE TABLE IF NOT EXISTS locations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(100) NULL,
                type VARCHAR(50) DEFAULT \'storage\',
                status VARCHAR(50) DEFAULT \'active\',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL
            )',
            'CREATE TABLE IF NOT EXISTS brands (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL
            )',
            'ALTER TABLE suppliers
                ADD COLUMN IF NOT EXISTS description TEXT NULL,
                ADD COLUMN IF NOT EXISTS contact_name VARCHAR(120) NULL,
                ADD COLUMN IF NOT EXISTS website VARCHAR(160) NULL,
                ADD COLUMN IF NOT EXISTS address TEXT NULL,
                ADD COLUMN IF NOT EXISTS city VARCHAR(120) NULL,
                ADD COLUMN IF NOT EXISTS country VARCHAR(120) NULL,
                ADD COLUMN IF NOT EXISTS postal_code VARCHAR(30) NULL',
            'CREATE TABLE IF NOT EXISTS purchase_orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                supplier_id BIGINT UNSIGNED NOT NULL,
                location_id BIGINT UNSIGNED NULL,
                reference VARCHAR(60) NOT NULL UNIQUE,
                order_type VARCHAR(30) NOT NULL DEFAULT \'Order\',
                order_date DATE NOT NULL,
                status VARCHAR(30) NOT NULL,
                note TEXT NULL,
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                ordered_at DATETIME NULL,
                received_at DATETIME NULL
            )',
            "ALTER TABLE purchase_orders
                ADD COLUMN IF NOT EXISTS location_id BIGINT UNSIGNED NULL,
                ADD COLUMN IF NOT EXISTS order_type VARCHAR(30) NOT NULL DEFAULT 'Order',
                ADD COLUMN IF NOT EXISTS note TEXT NULL,
                ADD COLUMN IF NOT EXISTS total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                ADD COLUMN IF NOT EXISTS ordered_at DATETIME NULL,
                ADD COLUMN IF NOT EXISTS received_at DATETIME NULL",
            'CREATE TABLE IF NOT EXISTS purchase_order_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                purchase_order_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS purchase_order_receiving_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                purchase_order_id BIGINT UNSIGNED NOT NULL,
                product_name VARCHAR(160) NOT NULL,
                received_qty INT NOT NULL DEFAULT 0,
                supply_price DECIMAL(12,2) NOT NULL DEFAULT 0,
                received_at DATETIME NOT NULL,
                note VARCHAR(255) NULL
            )',
            'CREATE TABLE IF NOT EXISTS stock_movements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id BIGINT UNSIGNED NOT NULL,
                movement_type VARCHAR(40) NOT NULL,
                quantity INT NOT NULL,
                note VARCHAR(255) NULL,
                created_at DATETIME NOT NULL
            )',
            'CREATE TABLE IF NOT EXISTS stock_opname_sessions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                location_id BIGINT UNSIGNED NULL,
                name VARCHAR(160) NOT NULL,
                note TEXT NULL,
                status VARCHAR(30) NOT NULL DEFAULT \'Meninjau\',
                started_at DATETIME NOT NULL,
                ended_at DATETIME NULL,
                started_by VARCHAR(120) NULL,
                cancelled_by VARCHAR(120) NULL,
                cancelled_note TEXT NULL
            )',
            'CREATE TABLE IF NOT EXISTS stock_opname_session_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                expected_stock INT NOT NULL DEFAULT 0,
                counted_stock INT NOT NULL DEFAULT 0,
                item_status VARCHAR(30) NOT NULL DEFAULT \'counted\',
                note VARCHAR(255) NULL
            )',
            'CREATE TABLE IF NOT EXISTS stock_opnames (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id BIGINT UNSIGNED NOT NULL,
                expected_stock INT NOT NULL,
                actual_stock INT NOT NULL,
                note VARCHAR(255) NULL,
                created_at DATETIME NOT NULL
            )',
        ];

        foreach ($statements as $statement) {
            try {
                $this->pdo()->exec($this->resolveSqlTableAliases($statement));
            } catch (\Throwable $throwable) {
                $trimmed = ltrim($statement);
                if ($this->isMissingOrBrokenTableException($throwable)) {
                    if (str_starts_with($trimmed, 'ALTER TABLE')) {
                        continue;
                    }

                    if (str_contains($trimmed, 'purchase_order_items') || str_contains($trimmed, 'purchase_order_receiving_logs')) {
                        continue;
                    }
                }

                throw $throwable;
            }
        }

        $this->tableExistsCache = [];
        $this->columnExistsCache = [];
        $this->inventorySchemaEnsured = true;
    }

    private function ensureStaffScheduleSchema(): void
    {
        if (!$this->usingDb() || $this->staffScheduleSchemaEnsured) {
            return;
        }

        $statements = [
            'CREATE TABLE IF NOT EXISTS staff_shifts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                staff_id BIGINT UNSIGNED NOT NULL,
                shift_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                repeat_mode VARCHAR(20) NOT NULL DEFAULT \'none\',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS staff_attendance (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                staff_id BIGINT UNSIGNED NOT NULL,
                attendance_date DATE NOT NULL,
                shift_start TIME NOT NULL,
                shift_end TIME NOT NULL,
                clock_in TIME NOT NULL,
                clock_out TIME NOT NULL,
                source VARCHAR(40) NOT NULL DEFAULT \'-\',
                status VARCHAR(30) NOT NULL DEFAULT \'Ontime\',
                selfie_in_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                selfie_out_score DECIMAL(5,2) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
        ];

        foreach ($statements as $statement) {
            $this->pdo()->exec($statement);
        }

        $this->tableExistsCache = [];
        $this->columnExistsCache = [];
        $this->staffScheduleSchemaEnsured = true;
    }

    private function ensureStaffScheduleSeedData(): void
    {
        if (!$this->usingDb() || $this->staffScheduleSeedEnsured) {
            return;
        }

        $staffRows = $this->dbAll('SELECT id FROM staff WHERE deleted_at IS NULL ORDER BY id ASC');
        if ($staffRows === []) {
            $this->staffScheduleSeedEnsured = true;

            return;
        }

        $shiftCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM staff_shifts')['total'] ?? 0));
        if ($shiftCount === 0) {
            foreach ($staffRows as $index => $staffRow) {
                $shiftDate = (new \DateTimeImmutable('today'))->modify('-' . $index . ' days')->format('Y-m-d');
                $this->dbExecute(
                    "INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, repeat_mode)
                     VALUES (:staff_id, :shift_date, :start_time, :end_time, :repeat_mode)",
                    [
                        'staff_id' => (int) $staffRow['id'],
                        'shift_date' => $shiftDate,
                        'start_time' => '08:00:00',
                        'end_time' => '17:00:00',
                        'repeat_mode' => 'weekly',
                    ]
                );
            }
        }

        $attendanceCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM staff_attendance')['total'] ?? 0));
        if ($attendanceCount === 0) {
            foreach ($staffRows as $index => $staffRow) {
                $attendanceDate = (new \DateTimeImmutable('today'))->modify('-' . $index . ' days')->format('Y-m-d');
                $status = $index === 0 ? 'Ontime' : ($index % 2 === 0 ? 'Late' : 'Overtime');
                $clockIn = $status === 'Late' ? '08:17:00' : '08:00:00';
                $clockOut = $status === 'Overtime' ? '17:48:00' : '17:00:00';

                $this->dbExecute(
                    "INSERT INTO staff_attendance
                     (staff_id, attendance_date, shift_start, shift_end, clock_in, clock_out, source, status, selfie_in_score, selfie_out_score)
                     VALUES
                     (:staff_id, :attendance_date, :shift_start, :shift_end, :clock_in, :clock_out, :source, :status, :selfie_in_score, :selfie_out_score)",
                    [
                        'staff_id' => (int) $staffRow['id'],
                        'attendance_date' => $attendanceDate,
                        'shift_start' => '08:00:00',
                        'shift_end' => '17:00:00',
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'source' => 'Manual',
                        'status' => $status,
                        'selfie_in_score' => 0,
                        'selfie_out_score' => 0,
                    ]
                );
            }
        }

        $this->staffScheduleSeedEnsured = true;
    }

    private function ensureInventoryWorkflowSeedData(): void
    {
        if (!$this->inventoryDbEnabled() || $this->inventorySeedEnsured) {
            return;
        }

        if ($this->tableExists('locations')) {
            $locationCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM locations')['total'] ?? 0));
            if ($locationCount === 0) {
                $hasAddress = $this->columnExists('locations', 'address');
                $hasIsActive = $this->columnExists('locations', 'is_active');
                $columns = ['id', 'name'];
                $placeholders = ['1', ':name'];
                $params = ['name' => 'Star Salon'];

                if ($hasAddress) {
                    $columns[] = 'address';
                    $placeholders[] = ':address';
                    $params['address'] = 'Jl. Raya Inpres No.04, RT.4/RW.10, Kp. Tengah, Kec. Kramat jati, Kota Jakarta Timur';
                }

                if ($hasIsActive) {
                    $columns[] = 'is_active';
                    $placeholders[] = '1';
                }

                $this->dbExecute(
                    sprintf(
                        'INSERT INTO locations (%s) VALUES (%s)',
                        implode(', ', $columns),
                        implode(', ', $placeholders)
                    ),
                    $params
                );
            }
        }

        if ($this->tableExists('suppliers')) {
            $firstSupplier = $this->dbOne('SELECT id FROM suppliers ORDER BY id ASC LIMIT 1');
            if ($firstSupplier !== null && $this->columnExists('suppliers', 'contact_name')) {
                $this->dbExecute(
                    "UPDATE suppliers
                     SET description = COALESCE(NULLIF(description, ''), :description),
                         contact_name = COALESCE(NULLIF(contact_name, ''), :contact_name),
                         website = COALESCE(NULLIF(website, ''), :website),
                         address = COALESCE(NULLIF(address, ''), :address),
                         city = COALESCE(NULLIF(city, ''), :city),
                         country = COALESCE(NULLIF(country, ''), :country),
                         postal_code = COALESCE(NULLIF(postal_code, ''), :postal_code)
                     WHERE id = :id",
                    [
                        'id' => (int) $firstSupplier['id'],
                        'description' => 'Supplier utama produk retail dan konsumsi.',
                        'contact_name' => 'Ayu Permata',
                        'website' => 'https://supplier.local/glow-source',
                        'address' => 'Silom Trade Center',
                        'city' => 'Bangkok',
                        'country' => 'Thailand',
                        'postal_code' => '10500',
                    ]
                );
            }
        }

        if ($this->tableExists('suppliers')) {
            $supplierCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM suppliers')['total'] ?? 0));
            if ($supplierCount === 0) {
                $hasPhone = $this->columnExists('suppliers', 'phone');
                $hasEmail = $this->columnExists('suppliers', 'email');
                $hasAddress = $this->columnExists('suppliers', 'address');
                $columns = ['name'];
                $placeholders = [':name'];
                $params = ['name' => 'Wardah'];

                if ($hasPhone) {
                    $columns[] = 'phone';
                    $placeholders[] = ':phone';
                    $params['phone'] = '+62 812 0000 1111';
                }

                if ($hasEmail) {
                    $columns[] = 'email';
                    $placeholders[] = ':email';
                    $params['email'] = 'wardah@gmail.com';
                }

                if ($hasAddress) {
                    $columns[] = 'address';
                    $placeholders[] = ':address';
                    $params['address'] = 'Jakarta';
                }

                $this->dbExecute(
                    sprintf(
                        'INSERT INTO suppliers (%s) VALUES (%s)',
                        implode(', ', $columns),
                        implode(', ', $placeholders)
                    ),
                    $params
                );
            }
        }

        $supplierId = (int) (($this->dbOne('SELECT id FROM suppliers ORDER BY id ASC LIMIT 1')['id'] ?? 0));

        if ($this->tableExists('products')) {
            $productCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM products')['total'] ?? 0));
            if ($productCount === 0 && $supplierId > 0) {
                $hasSupplierId = $this->columnExists('products', 'supplier_id');
                $hasSku = $this->columnExists('products', 'sku');
                $hasCode = $this->columnExists('products', 'code');
                $hasSellPrice = $this->columnExists('products', 'sell_price');
                $hasSellingPrice = $this->columnExists('products', 'selling_price');

                $insertSeedProduct = function (string $name, string $code, int $stock, float $price) use ($supplierId, $hasSupplierId, $hasSku, $hasCode, $hasSellPrice, $hasSellingPrice): void {
                    $columns = ['name', 'stock', 'status'];
                    $placeholders = [':name', ':stock', ':status'];
                    $params = [
                        'name' => $name,
                        'stock' => $stock,
                        'status' => 'Aman',
                    ];

                    if ($hasSupplierId) {
                        $columns[] = 'supplier_id';
                        $placeholders[] = ':supplier_id';
                        $params['supplier_id'] = $supplierId;
                    }

                    if ($hasSku) {
                        $columns[] = 'sku';
                        $placeholders[] = ':sku';
                        $params['sku'] = $code;
                    } elseif ($hasCode) {
                        $columns[] = 'code';
                        $placeholders[] = ':code';
                        $params['code'] = $code;
                    }

                    if ($hasSellPrice) {
                        $columns[] = 'sell_price';
                        $placeholders[] = ':sell_price';
                        $params['sell_price'] = $price;
                    } elseif ($hasSellingPrice) {
                        $columns[] = 'selling_price';
                        $placeholders[] = ':selling_price';
                        $params['selling_price'] = $price;
                    }

                    $this->dbExecute(
                        sprintf(
                            'INSERT INTO products (%s) VALUES (%s)',
                            implode(', ', $columns),
                            implode(', ', $placeholders)
                        ),
                        $params
                    );
                };

                $insertSeedProduct('Hair Serum Wardah - Per Pump', 'INV-0001', 7, 0);
                $insertSeedProduct('Hair Serum Wardah - 500ml', 'INV-0002', 10, 25000);
            }
        }

        $priceSelect = $this->columnExists('products', 'sell_price')
            ? 'sell_price'
            : ($this->columnExists('products', 'selling_price') ? 'selling_price AS sell_price' : '0 AS sell_price');
        $productRows = $this->dbAll("SELECT id, name, stock, {$priceSelect} FROM products ORDER BY id ASC LIMIT 2");
        $locationId = 1;
        if ($this->tableExists('locations')) {
            $locationId = (int) (($this->dbOne('SELECT id FROM locations ORDER BY id ASC LIMIT 1')['id'] ?? 1));
        }

        if ($supplierId > 0 && count($productRows) >= 2 && $this->tableExists('purchase_orders')) {
            $purchaseCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM purchase_orders')['total'] ?? 0));
            if ($purchaseCount === 0) {
                $this->dbExecute(
                    "INSERT INTO purchase_orders
                     (id, supplier_id, location_id, reference, order_type, order_date, status, note, total_amount, ordered_at, received_at)
                     VALUES
                     (1, :supplier_id, :location_id, :reference, :order_type, :order_date, :status, :note, :total_amount, :ordered_at, :received_at)",
                    [
                        'supplier_id' => $supplierId,
                        'location_id' => $locationId,
                        'reference' => 'P000001',
                        'order_type' => 'Order',
                        'order_date' => '2026-04-28',
                        'status' => 'received',
                        'note' => '',
                        'total_amount' => 250000,
                        'ordered_at' => '2026-04-28 10:00:00',
                        'received_at' => '2026-04-28 18:30:10',
                    ]
                );
                $this->dbExecute(
                    "INSERT INTO purchase_orders
                     (id, supplier_id, location_id, reference, order_type, order_date, status, note, total_amount, ordered_at, received_at)
                     VALUES
                     (2, :supplier_id, :location_id, :reference, :order_type, :order_date, :status, :note, :total_amount, :ordered_at, :received_at)",
                    [
                        'supplier_id' => $supplierId,
                        'location_id' => $locationId,
                        'reference' => 'P000002',
                        'order_type' => 'Order',
                        'order_date' => '2026-04-28',
                        'status' => 'ordered',
                        'note' => 'test',
                        'total_amount' => 125000,
                        'ordered_at' => '2026-04-28 12:00:00',
                        'received_at' => null,
                    ]
                );

                $this->dbExecute(
                    'INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, supply_price)
                     VALUES (:purchase_order_id, :product_id, :quantity, :supply_price)',
                    [
                        'purchase_order_id' => 1,
                        'product_id' => (int) $productRows[1]['id'],
                        'quantity' => 10,
                        'supply_price' => 25000,
                    ]
                );
                $this->dbExecute(
                    'INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, supply_price)
                     VALUES (:purchase_order_id, :product_id, :quantity, :supply_price)',
                    [
                        'purchase_order_id' => 2,
                        'product_id' => (int) $productRows[1]['id'],
                        'quantity' => 5,
                        'supply_price' => 25000,
                    ]
                );

                $this->dbExecute(
                    "INSERT INTO purchase_order_receiving_logs (purchase_order_id, product_name, received_qty, supply_price, received_at, note)
                     VALUES (1, :product_name, 10, 25000, '2026-04-28 18:30:10', 'Penerimaan penuh')",
                    [
                        'product_name' => (string) $productRows[1]['name'],
                    ]
                );
            }
        }

        if (count($productRows) >= 2 && $this->tableExists('stock_opname_sessions')) {
            $opnameCount = (int) (($this->dbOne('SELECT COUNT(*) AS total FROM stock_opname_sessions')['total'] ?? 0));
            if ($opnameCount === 0) {
                $this->dbExecute(
                    "INSERT INTO stock_opname_sessions
                     (id, location_id, name, note, status, started_at, ended_at, started_by, cancelled_by, cancelled_note)
                     VALUES
                     (1, :location_id, :name, :note, :status, :started_at, :ended_at, :started_by, :cancelled_by, :cancelled_note)",
                    [
                        'location_id' => $locationId,
                        'name' => 'Stock Opname #5',
                        'note' => 'Tidak ada catatan',
                        'status' => 'Meninjau',
                        'started_at' => '2026-05-01 13:11:00',
                        'ended_at' => null,
                        'started_by' => 'Rayhan Doni Pramana',
                        'cancelled_by' => null,
                        'cancelled_note' => null,
                    ]
                );
                $this->dbExecute(
                    "INSERT INTO stock_opname_sessions
                     (id, location_id, name, note, status, started_at, ended_at, started_by, cancelled_by, cancelled_note)
                     VALUES
                     (2, :location_id, :name, :note, :status, :started_at, :ended_at, :started_by, :cancelled_by, :cancelled_note)",
                    [
                        'location_id' => $locationId,
                        'name' => 'Stock Opname #4',
                        'note' => 'ga jadi',
                'status' => 'Cancelled',
                        'started_at' => '2026-05-01 13:10:00',
                        'ended_at' => '2026-05-01 15:21:00',
                        'started_by' => 'Rayhan Doni Pramana',
                        'cancelled_by' => 'Rayhan Doni Pramana',
                        'cancelled_note' => 'ga jadi',
                    ]
                );

                $this->dbExecute(
                    'INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
                     VALUES (:session_id, :product_id, :expected_stock, :counted_stock, :item_status, :note)',
                    [
                        'session_id' => 1,
                        'product_id' => (int) $productRows[0]['id'],
                        'expected_stock' => 7,
                        'counted_stock' => 7,
                        'item_status' => 'counted',
                        'note' => null,
                    ]
                );
                $this->dbExecute(
                    'INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
                     VALUES (:session_id, :product_id, :expected_stock, :counted_stock, :item_status, :note)',
                    [
                        'session_id' => 1,
                        'product_id' => (int) $productRows[1]['id'],
                        'expected_stock' => 10,
                        'counted_stock' => 8,
                        'item_status' => 'mismatch',
                        'note' => null,
                    ]
                );
                $this->dbExecute(
                    'INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
                     VALUES (:session_id, :product_id, :expected_stock, :counted_stock, :item_status, :note)',
                    [
                        'session_id' => 2,
                        'product_id' => (int) $productRows[0]['id'],
                        'expected_stock' => 8,
                        'counted_stock' => 7,
                        'item_status' => 'mismatch',
                        'note' => 'ga jadi',
                    ]
                );
                $this->dbExecute(
                    'INSERT INTO stock_opname_session_items (session_id, product_id, expected_stock, counted_stock, item_status, note)
                     VALUES (:session_id, :product_id, :expected_stock, :counted_stock, :item_status, :note)',
                    [
                        'session_id' => 2,
                        'product_id' => (int) $productRows[1]['id'],
                        'expected_stock' => 10,
                        'counted_stock' => 8,
                        'item_status' => 'mismatch',
                        'note' => 'ga jadi',
                    ]
                );
            }
        }

        $this->inventorySeedEnsured = true;
    }

    private function normalizeInventoryPurchaseStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'received', 'diterima' => 'Received',
            'cancelled', 'canceled', 'dibatalkan' => 'Cancelled',
            default => 'Ordered',
        };
    }

    private function normalizeInventoryOpnameStatus(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'completed', 'complete', 'komplit' => 'Completed',
            'cancelled', 'canceled', 'dibatalkan' => 'Cancelled',
            'counting', 'perhitungan' => 'Perhitungan',
            default => 'Meninjau',
        };
    }

    private function humanDate(string $dateTime): string
    {
        return (new \DateTimeImmutable($dateTime))->format('d M Y');
    }

    private function humanDateTime(string $dateTime): string
    {
        return (new \DateTimeImmutable($dateTime))->format('d M Y, H:i');
    }

    private function analyticsTransactionNetTotal(array $transaction): float
    {
        $gross = array_reduce(
            $transaction['items'] ?? [],
            static fn (float $sum, array $item): float => $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))),
            0.0
        );

        return max(0, $gross - (float) ($transaction['discount'] ?? 0) + (float) ($transaction['rounding'] ?? 0));
    }

    private function percentChange(float $current, float $previous): float
    {
        if (abs($previous) < 0.00001) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    private function formatSignedPercent(float $value): string
    {
        $rounded = round($value);
        if ($rounded > 0) {
            return '+' . $rounded . '%';
        }

        return $rounded . '%';
    }

    private function calendarResourceRows(array $locations): array
    {
        return array_values(array_map(static function (array $location): array {
            return [
                'id' => 'location-' . (int) ($location['id'] ?? 0),
                'name' => (string) ($location['name'] ?? 'Star Salon'),
            ];
        }, $locations));
    }

    private function calendarDiscountRows(array $discounts): array
    {
        return array_values(array_map(static function (array $discount): array {
            return [
                'id' => (int) ($discount['id'] ?? 0),
                'name' => (string) ($discount['name'] ?? 'Diskon'),
                'mode' => (string) ($discount['mode'] ?? 'amount'),
                'amount' => (float) ($discount['amount_value'] ?? 0),
                'amount_label' => (string) ($discount['amount_label'] ?? ''),
                'max_discount' => (float) ($discount['max_discount_value'] ?? 0),
            ];
        }, $discounts));
    }

    private function calendarOwnedVoucherRows(array $customers, array $vouchers): array
    {
        $customerNamesById = [];
        foreach ($customers as $customer) {
            $customerNamesById[(int) ($customer['id'] ?? 0)] = (string) ($customer['name'] ?? 'Pelanggan');
        }

        $vouchersById = [];
        $vouchersByName = [];
        foreach ($vouchers as $voucher) {
            $voucherId = (int) ($voucher['id'] ?? 0);
            if ($voucherId > 0) {
                $vouchersById[$voucherId] = $voucher;
            }
            $vouchersByName[$this->calendarMatchKey((string) ($voucher['name'] ?? ''))] = $voucher;
        }

        $sales = [];
        foreach ($this->getTransactions() as $transaction) {
            $customerId = (int) ($transaction['customer_id'] ?? 0);
            if ($customerId <= 0) {
                continue;
            }

            foreach ((array) ($transaction['items'] ?? []) as $item) {
                if (($item['type'] ?? '') !== 'voucher') {
                    continue;
                }

                $voucher = $vouchersByName[$this->calendarMatchKey((string) ($item['name'] ?? ''))] ?? null;
                if (!is_array($voucher)) {
                    continue;
                }

                $voucherId = (int) ($voucher['id'] ?? 0);
                if ($voucherId <= 0) {
                    continue;
                }

                $key = $customerId . ':' . $voucherId;
                if (!isset($sales[$key])) {
                    $sales[$key] = [
                        'customer_id' => $customerId,
                        'voucher' => $voucher,
                        'qty' => 0,
                    ];
                }
                $sales[$key]['qty'] += max(1, (int) ($item['qty'] ?? 1));
            }
        }

        $redemptionCount = [];
        if ($this->usingDb() && $this->tableExists('voucher_redemptions')) {
            foreach ($this->dbAll(
                "SELECT customer_id, voucher_id, COUNT(*) AS total
                 FROM voucher_redemptions
                 GROUP BY customer_id, voucher_id"
            ) as $row) {
                $redemptionCount[(int) $row['customer_id'] . ':' . (int) $row['voucher_id']] = (int) ($row['total'] ?? 0);
            }
        }

        $rows = [];
        foreach ($sales as $sale) {
            $voucher = $sale['voucher'];
            $voucherId = (int) ($voucher['id'] ?? 0);
            $customerId = (int) $sale['customer_id'];
            $key = $customerId . ':' . $voucherId;
            $total = max(0, (int) ($sale['qty'] ?? 0));
            $used = max(0, (int) ($redemptionCount[$key] ?? 0));
            $remaining = max(0, $total - $used);
            $type = (string) ($voucher['type'] ?? 'gift');
            $serviceNames = json_decode((string) ($voucher['services_json'] ?? '[]'), true);
            $serviceNames = is_array($serviceNames)
                ? array_values(array_filter(array_map(static fn (array $item): string => trim((string) ($item['name'] ?? '')), $serviceNames)))
                : [];
            $rows[] = [
                'id' => 'owned-' . $customerId . '-' . $voucherId,
                'owner' => (string) ($customerNamesById[$customerId] ?? 'Pelanggan'),
                'type' => $type,
                'type_label' => $type === 'gift' ? 'Gift Voucher' : 'Service Voucher',
                'name' => (string) ($voucher['name'] ?? 'Voucher'),
                'service_label' => (string) (($voucher['service_name'] ?? '') ?: implode(', ', $serviceNames)),
                'service_names' => $serviceNames,
                'remaining' => $remaining,
                'total' => $total,
                'remaining_value' => $type === 'gift' ? $remaining * (float) ($voucher['editor_value'] ?? 0) : 0,
                'expiry_date' => (string) ($voucher['expired_at'] ?? ''),
                'location' => (string) ($voucher['location'] ?? 'Star Salon'),
                'code' => (string) ($voucher['code'] ?? ''),
                'status' => $remaining > 0 ? 'active' : 'used',
            ];
        }

        usort($rows, static fn (array $left, array $right): int => strcmp((string) ($left['owner'] ?? ''), (string) ($right['owner'] ?? '')));

        return $rows;
    }

    private function calendarSalesServiceRows(array $services): array
    {
        return array_values(array_map(function (array $service): array {
            return [
                'id' => (string) ($service['id'] ?? ''),
                'kind' => 'service',
                'name' => (string) ($service['name'] ?? 'Layanan'),
                'price' => (float) ($service['price'] ?? 0),
                'duration' => (int) ($service['duration'] ?? 0),
                'category' => $this->calendarServiceCategory($service),
                'category_label' => $this->calendarServiceCategoryLabel($this->calendarServiceCategory($service)),
                'initials' => $this->calendarInitials((string) ($service['name'] ?? 'SV')),
                'gender' => $this->calendarGenderFromAudience((array) ($service['audience'] ?? [])),
            ];
        }, $services));
    }

    private function calendarSalesPackageRows(array $packages, array $services): array
    {
        $servicesByName = [];
        foreach ($services as $service) {
            $servicesByName[$this->calendarMatchKey((string) ($service['name'] ?? ''))] = $service;
        }

        return array_values(array_map(function (array $package) use ($servicesByName): array {
            $duration = 0;
            foreach ((array) ($package['items_detail'] ?? []) as $item) {
                if (($item['type'] ?? 'service') !== 'service') {
                    continue;
                }
                $service = $servicesByName[$this->calendarMatchKey((string) ($item['name'] ?? ''))] ?? null;
                if (!is_array($service)) {
                    continue;
                }
                $duration += max(1, (int) ($item['qty'] ?? 1)) * (int) ($service['duration'] ?? 0);
            }

            return [
                'id' => (string) ($package['id'] ?? ''),
                'kind' => 'package',
                'name' => (string) ($package['name'] ?? 'Package'),
                'description' => (string) ($package['description'] ?? implode(', ', (array) ($package['items'] ?? []))),
                'price' => (float) ($package['price'] ?? 0),
                'duration' => $duration,
                'category' => 'hair-cut',
                'category_label' => 'Hair Cut',
            ];
        }, $packages));
    }

    private function calendarSalesProductRows(array $products): array
    {
        return array_values(array_map(static function (array $product): array {
            return [
                'id' => (string) ($product['id'] ?? ''),
                'kind' => 'product',
                'name' => (string) ($product['name'] ?? 'Produk'),
                'variant' => (string) (($product['sku'] ?? '') ?: ($product['category'] ?? 'Default')),
                'brand' => (string) ($product['brand'] ?? ''),
                'stock' => (int) ($product['stock'] ?? 0),
                'price' => (float) ($product['price'] ?? 0),
                'category' => 'all',
                'category_label' => 'Semua',
            ];
        }, $products));
    }

    private function calendarSalesVoucherRows(array $vouchers): array
    {
        return array_values(array_map(function (array $voucher): array {
            $type = (string) ($voucher['type'] ?? 'gift');
            return [
                'id' => (string) ($voucher['id'] ?? ''),
                'kind' => 'voucher',
                'voucher_kind' => $type,
                'name' => (string) ($voucher['name'] ?? 'Voucher'),
                'subtitle' => (string) (($voucher['expiry_label'] ?? '') ?: ($voucher['duration'] ?? '')),
                'price' => (float) ($voucher['price_value'] ?? $voucher['editor_value'] ?? 0),
                'badge' => $type === 'gift' ? 'G' : ($type === 'class' ? 'C' : 'S'),
                'badge_color' => $type === 'gift' ? 'green' : 'yellow',
                'category' => $type,
            ];
        }, $vouchers));
    }

    private function calendarPayableRows(): array
    {
        $rows = [];

        if ($this->usingDb() && $this->tableExists('invoices') && $this->tableExists('transactions')) {
            $joinCustomers = $this->tableExists('customers');
            foreach ($this->dbAll(
                "SELECT i.id, i.status, i.issued_at, t.reference, t.customer_id,
                        COALESCE(SUM(ti.quantity * ti.price), 0) - t.discount_amount + t.rounding_amount AS amount"
                . ($joinCustomers ? ", c.name AS customer_name" : ", NULL AS customer_name") . "
                 FROM invoices i
                 JOIN transactions t ON t.id = i.transaction_id
                 LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
                 " . ($joinCustomers ? "LEFT JOIN customers c ON c.id = t.customer_id" : "") . "
                 WHERE LOWER(i.status) <> 'paid'
                 GROUP BY i.id, i.status, i.issued_at, t.reference, t.customer_id" . ($joinCustomers ? ", c.name" : "") . "
                 ORDER BY i.issued_at DESC"
            ) as $row) {
                $rows[] = [
                    'id' => 'payable-' . (int) $row['id'],
                    'kind' => 'payable',
                    'customer' => (string) (($row['customer_name'] ?? '') ?: 'Walk-In'),
                    'date' => $this->formatCalendarCatalogDate((string) ($row['issued_at'] ?? '')),
                    'amount' => (float) ($row['amount'] ?? 0),
                    'qty' => 1,
                    'badge' => strtoupper((string) ($row['status'] ?? 'NEW')),
                ];
            }
        }

        return $rows;
    }

    private function calendarServiceCategory(array $service): string
    {
        $name = strtolower((string) ($service['name'] ?? ''));
        $groupId = (int) ($service['group_id'] ?? 0);

        if ($groupId === 2 || str_contains($name, 'color') || str_contains($name, 'balayage') || str_contains($name, 'cat rambut')) {
            return 'hair-coloring';
        }

        if ($groupId === 3 || str_contains($name, 'spa') || str_contains($name, 'repair') || str_contains($name, 'treatment') || str_contains($name, 'creambath')) {
            return 'hair-treatment';
        }

        return 'hair-cut';
    }

    private function calendarServiceCategoryLabel(string $category): string
    {
        return match ($category) {
            'hair-coloring' => 'Hair Coloring',
            'hair-treatment' => 'Hair Treatment',
            default => 'Hair Cut',
        };
    }

    private function calendarGenderFromAudience(array $audience): ?string
    {
        $normalized = array_map(static fn (mixed $value): string => strtolower(trim((string) $value)), $audience);
        $normalized = array_values(array_filter($normalized));
        if ($normalized === ['men'] || $normalized === ['male']) {
            return 'male';
        }
        if ($normalized === ['women'] || $normalized === ['female']) {
            return 'female';
        }

        return null;
    }

    private function calendarInitials(string $value): string
    {
        $words = preg_split('/\s+/', trim($value)) ?: [];
        $letters = '';
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            $letters .= strtoupper(substr($word, 0, 1));
            if (strlen($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : 'SV';
    }

    private function calendarMatchKey(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['()', '-'], '', $normalized);
        return preg_replace('/\s+/', ' ', $normalized) ?: $normalized;
    }

    private function formatCalendarCatalogDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($value))->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function rankItems(string $type): array
    {
        $bucket = [];

        foreach ($this->getTransactions() as $transaction) {
            foreach ($transaction['items'] as $item) {
                if ($item['type'] !== $type) {
                    continue;
                }

                $bucket[$item['name']] = ($bucket[$item['name']] ?? 0) + $item['qty'];
            }
        }

        arsort($bucket);

        return array_map(fn (string $label, int $value): array => ['label' => $label, 'value' => $value], array_keys($bucket), array_values($bucket));
    }

    private function rankStaff(): array
    {
        $scores = [];

        foreach ($this->getBookings() as $booking) {
            if (!in_array($booking['status'], ['completed', 'confirmed'], true)) {
                continue;
            }

            $staff = $this->findStaff($booking['staff_id']);

            if ($staff !== null) {
                $scores[$staff['name']] = ($scores[$staff['name']] ?? 0) + 1;
            }
        }

        arsort($scores);

        return array_map(fn (string $label, int $value): array => ['label' => $label, 'value' => $value], array_keys($scores), array_values($scores));
    }

    private function sumItemsByType(array $items, string $type): float
    {
        return array_reduce($items, function (float $sum, array $item) use ($type): float {
            return $item['type'] === $type ? $sum + ($item['qty'] * $item['price']) : $sum;
        }, 0.0);
    }

    private function allPermissionKeys(): array
    {
        $permissions = [];

        foreach ($this->permissions['catalog'] as $module) {
            $permissions = array_merge($permissions, array_keys($module['permissions']));
        }

        return $permissions;
    }

    private function bootSessionState(): void
    {
        $_SESSION['starstyle'] ??= [
            'bookings' => [],
            'booking_blocks' => [],
            'transactions' => [],
            'staff_permissions' => [],
            'activity_logs' => [],
        ];
    }

    private function nextId(array $items, int $fallback): int
    {
        if ($items === []) {
            return $fallback;
        }

        $ids = array_column($items, 'id');

        return max($ids) + 1;
    }

    private function hasOverlap(\DateTimeImmutable $start, \DateTimeImmutable $end, array $bookings, array $blocks): bool
    {
        foreach ($bookings as $booking) {
            $existingStart = new \DateTimeImmutable($booking['start_at']);
            $existingEnd = new \DateTimeImmutable($booking['end_at']);

            if ($start < $existingEnd && $end > $existingStart) {
                return true;
            }
        }

        foreach ($blocks as $block) {
            $existingStart = new \DateTimeImmutable($block['start_at']);
            $existingEnd = new \DateTimeImmutable($block['end_at']);

            if ($start < $existingEnd && $end > $existingStart) {
                return true;
            }
        }

        return false;
    }

    private function ensureCustomerSchema(): void
    {
        if (!$this->usingDb() || $this->customerSchemaEnsured || !$this->tableExists('customers')) {
            return;
        }

        $columns = [
            'birthdate' => 'ADD COLUMN birthdate DATE NULL AFTER status',
            'family_card_number' => 'ADD COLUMN family_card_number VARCHAR(40) NULL AFTER birthdate',
            'passport_number' => 'ADD COLUMN passport_number VARCHAR(40) NULL AFTER family_card_number',
            'notify_via' => "ADD COLUMN notify_via VARCHAR(20) NOT NULL DEFAULT 'off' AFTER passport_number",
            'marketing_opt_in' => 'ADD COLUMN marketing_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER notify_via',
        ];

        foreach ($columns as $column => $definition) {
            if ($this->columnExists('customers', $column)) {
                continue;
            }

            $this->pdo()->exec('ALTER TABLE customers ' . $definition);
            $this->tableExistsCache = [];
            $this->columnExistsCache = [];
        }

        $this->customerSchemaEnsured = true;
    }

    private function ensureStaffProfileSchema(): void
    {
        if (!$this->usingDb() || $this->staffProfileSchemaEnsured || !$this->tableExists('staff')) {
            return;
        }

        $columns = [
            'gender' => 'ADD COLUMN gender VARCHAR(20) NULL AFTER rating',
            'booking_enabled' => 'ADD COLUMN booking_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER gender',
            'agenda_color' => "ADD COLUMN agenda_color VARCHAR(20) NOT NULL DEFAULT '#8cc9ff' AFTER booking_enabled",
            'started_working_on' => 'ADD COLUMN started_working_on DATE NULL AFTER agenda_color',
            'ended_working_on' => 'ADD COLUMN ended_working_on DATE NULL AFTER started_working_on',
            'public_title' => 'ADD COLUMN public_title VARCHAR(120) NULL AFTER ended_working_on',
            'notes' => 'ADD COLUMN notes TEXT NULL AFTER public_title',
            'instagram_handle' => 'ADD COLUMN instagram_handle VARCHAR(160) NULL AFTER notes',
            'photo_data_url' => 'ADD COLUMN photo_data_url LONGTEXT NULL AFTER instagram_handle',
            'commission_rules' => 'ADD COLUMN commission_rules JSON NULL AFTER photo_data_url',
            'attendance_pose' => "ADD COLUMN attendance_pose VARCHAR(80) NOT NULL DEFAULT 'Right Tilt' AFTER commission_rules",
            'attendance_uploaded_pose' => 'ADD COLUMN attendance_uploaded_pose VARCHAR(80) NULL AFTER attendance_pose',
        ];

        foreach ($columns as $column => $definition) {
            if ($this->columnExists('staff', $column)) {
                continue;
            }

            $this->pdo()->exec('ALTER TABLE staff ' . $definition);
            $this->tableExistsCache = [];
            $this->columnExistsCache = [];
        }

        $this->staffProfileSchemaEnsured = true;
    }

    private function ensureServiceCatalogSchema(): void
    {
        if (!$this->usingDb() || $this->serviceCatalogSchemaEnsured) {
            return;
        }

        if ($this->tableExists('service_groups')) {
            $groupColumns = [
                'color' => "ADD COLUMN color VARCHAR(20) NOT NULL DEFAULT '#76b6e8' AFTER description",
                'image_data_url' => 'ADD COLUMN image_data_url LONGTEXT NULL AFTER color',
            ];

            foreach ($groupColumns as $column => $definition) {
                if ($this->columnExists('service_groups', $column)) {
                    continue;
                }

                $this->pdo()->exec('ALTER TABLE service_groups ' . $definition);
                $this->tableExistsCache = [];
                $this->columnExistsCache = [];
            }
        }

        if ($this->tableExists('services')) {
            $serviceColumns = [
                'audience_json' => 'ADD COLUMN audience_json JSON NULL AFTER description',
                'image_data_url' => 'ADD COLUMN image_data_url LONGTEXT NULL AFTER audience_json',
                'online_bookable' => 'ADD COLUMN online_bookable TINYINT(1) NOT NULL DEFAULT 1 AFTER image_data_url',
                'commission_enabled' => 'ADD COLUMN commission_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER online_bookable',
                'at_customer_location' => 'ADD COLUMN at_customer_location TINYINT(1) NOT NULL DEFAULT 0 AFTER commission_enabled',
                'extra_time_type' => "ADD COLUMN extra_time_type VARCHAR(40) NOT NULL DEFAULT 'none' AFTER at_customer_location",
                'extra_time_minutes' => 'ADD COLUMN extra_time_minutes INT NOT NULL DEFAULT 0 AFTER extra_time_type',
            ];

            foreach ($serviceColumns as $column => $definition) {
                if ($this->columnExists('services', $column)) {
                    continue;
                }

                $this->pdo()->exec('ALTER TABLE services ' . $definition);
                $this->tableExistsCache = [];
                $this->columnExistsCache = [];
            }
        }

        if ($this->tableExists('service_variants')) {
            $variantColumns = [
                'special_price' => 'ADD COLUMN special_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER price',
                'location_pricing_json' => 'ADD COLUMN location_pricing_json JSON NULL AFTER special_price',
                'cost_price' => 'ADD COLUMN cost_price DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER location_pricing_json',
                'cost_products_json' => 'ADD COLUMN cost_products_json JSON NULL AFTER cost_price',
                'availability_json' => 'ADD COLUMN availability_json JSON NULL AFTER cost_products_json',
            ];

            foreach ($variantColumns as $column => $definition) {
                if ($this->columnExists('service_variants', $column)) {
                    continue;
                }

                $this->pdo()->exec('ALTER TABLE service_variants ' . $definition);
                $this->tableExistsCache = [];
                $this->columnExistsCache = [];
            }
        }

        if ($this->tableExists('service_packages')) {
            $packageColumns = [
                'group_id' => 'ADD COLUMN group_id BIGINT UNSIGNED NULL AFTER id',
                'pricing_mode' => "ADD COLUMN pricing_mode VARCHAR(20) NOT NULL DEFAULT 'service' AFTER description",
                'discount_value' => 'ADD COLUMN discount_value DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER pricing_mode',
                'audience' => "ADD COLUMN audience VARCHAR(20) NOT NULL DEFAULT 'all' AFTER discount_value",
                'image_data_url' => 'ADD COLUMN image_data_url LONGTEXT NULL AFTER audience',
                'items_json' => 'ADD COLUMN items_json JSON NULL AFTER image_data_url',
            ];

            foreach ($packageColumns as $column => $definition) {
                if ($this->columnExists('service_packages', $column)) {
                    continue;
                }

                $this->pdo()->exec('ALTER TABLE service_packages ' . $definition);
                $this->tableExistsCache = [];
                $this->columnExistsCache = [];
            }
        }

        $this->serviceCatalogSchemaEnsured = true;
    }

    private function resolveCustomer(string $name, string $phone): int
    {
        if ($this->usingDb()) {
            $this->ensureCustomerSchema();
            $existing = $this->dbOne(
                "SELECT id
                 FROM customers
                 WHERE deleted_at IS NULL
                   AND (
                        LOWER(name) = LOWER(:name_match)
                        OR (:phone_match <> '' AND phone = :phone_value)
                   )
                 ORDER BY id ASC
                 LIMIT 1",
                [
                    'name_match' => $name,
                    'phone_match' => $phone,
                    'phone_value' => $phone,
                ]
            );

            if ($existing !== null) {
                return (int) $existing['id'];
            }

            $memberId = $this->generateCustomerMemberId();
            $this->dbExecute(
                "INSERT INTO customers (member_id, name, phone, status)
                 VALUES (:member_id, :name, :phone, 'Aktif')",
                [
                    'member_id' => $memberId,
                    'name' => $name !== '' ? $name : 'Walk In Customer',
                    'phone' => $phone !== '' ? $phone : null,
                ]
            );

            return (int) $this->pdo()->lastInsertId();
        }

        foreach ($this->getCustomers() as $customer) {
            if (strcasecmp($customer['name'], $name) === 0 || $customer['phone'] === $phone) {
                return $customer['id'];
            }
        }

        return 1;
    }

    private function syncCustomerContact(int $customerId, string $name, string $phone, string $email): void
    {
        if ($customerId <= 0) {
            return;
        }

        if ($this->usingDb()) {
            $this->ensureCustomerSchema();
            $this->dbExecute(
                "UPDATE customers
                 SET name = CASE WHEN :name <> '' THEN :name ELSE name END,
                     phone = CASE WHEN :phone <> '' THEN :phone ELSE phone END,
                     email = CASE WHEN :email <> '' THEN :email ELSE email END
                 WHERE id = :id",
                [
                    'id' => $customerId,
                    'name' => $name,
                    'phone' => $phone,
                    'email' => $email,
                ]
            );
            return;
        }

        foreach ($_SESSION['starstyle']['customers'] as $index => $customer) {
            if ((int) ($customer['id'] ?? 0) !== $customerId) {
                continue;
            }

            if ($name !== '') {
                $_SESSION['starstyle']['customers'][$index]['name'] = $name;
            }
            if ($phone !== '') {
                $_SESSION['starstyle']['customers'][$index]['phone'] = $phone;
            }
            if ($email !== '') {
                $_SESSION['starstyle']['customers'][$index]['email'] = $email;
            }
            break;
        }
    }

    private function normalizeServiceGroupPayload(array $payload): array
    {
        return [
            'name' => trim((string) ($payload['name'] ?? '')),
            'description' => $this->nullIfEmpty((string) ($payload['description'] ?? '')),
            'color' => trim((string) ($payload['color'] ?? '#76b6e8')) ?: '#76b6e8',
            'image_data_url' => $this->nullIfEmpty((string) ($payload['image_data_url'] ?? '')),
        ];
    }

    private function normalizeServicePayload(array $payload): array
    {
        $variants = is_array($payload['variants'] ?? null) ? $payload['variants'] : [];
        $staffIds = array_values(array_unique(array_map('intval', is_array($payload['staff_ids'] ?? null) ? $payload['staff_ids'] : [])));
        $audience = array_values(array_unique(array_filter(
            is_array($payload['audience'] ?? null) ? array_map('strval', $payload['audience']) : [],
            static fn (string $value): bool => in_array($value, ['Women', 'Men'], true)
        )));
        if ($audience === []) {
            $audience = ['Women', 'Men'];
        }

        $normalizedVariants = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $durationMinutes = max(0, (int) ($variant['duration_minutes'] ?? 0));
            $price = (float) ($variant['price'] ?? 0);
            $specialPrice = (float) ($variant['special_price'] ?? 0);
            $variantName = trim((string) ($variant['variant_name'] ?? ''));
            $locationPricing = is_array($variant['location_pricing'] ?? null) ? $variant['location_pricing'] : null;
            $costProducts = is_array($variant['cost_products'] ?? null) ? $variant['cost_products'] : [];
            $availability = is_array($variant['availability'] ?? null) ? $variant['availability'] : null;

            if ($variantName === '' && $durationMinutes === 0 && $price <= 0 && $specialPrice <= 0) {
                continue;
            }

            $normalizedVariants[] = [
                'variant_name' => $variantName,
                'duration_minutes' => $durationMinutes,
                'price' => $price,
                'special_price' => $specialPrice,
                'location_pricing_json' => json_encode($locationPricing, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'cost_price' => (float) ($variant['cost_price'] ?? 0),
                'cost_products_json' => json_encode($costProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'availability_json' => json_encode($availability, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }

        if ($normalizedVariants === []) {
            $normalizedVariants[] = [
                'variant_name' => '',
                'duration_minutes' => max(0, (int) ($payload['duration_minutes'] ?? 0)),
                'price' => (float) ($payload['base_price'] ?? 0),
                'special_price' => 0.0,
                'location_pricing_json' => json_encode(null),
                'cost_price' => 0.0,
                'cost_products_json' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'availability_json' => json_encode(null),
            ];
        }

        $primaryVariant = $normalizedVariants[0];
        $extraTimeType = (string) ($payload['extra_time_type'] ?? 'none');
        if (!in_array($extraTimeType, ['none', 'processing_after', 'blocked_after'], true)) {
            $extraTimeType = 'none';
        }

        return [
            'group_id' => max(1, (int) ($payload['group_id'] ?? 1)),
            'name' => trim((string) ($payload['name'] ?? '')),
            'duration_minutes' => (int) ($primaryVariant['duration_minutes'] ?? 0),
            'base_price' => (float) ($primaryVariant['price'] ?? 0),
            'status' => trim((string) ($payload['status'] ?? 'Aktif')) ?: 'Aktif',
            'description' => $this->nullIfEmpty((string) ($payload['description'] ?? '')),
            'audience_json' => json_encode($audience, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'image_data_url' => $this->nullIfEmpty((string) ($payload['image_data_url'] ?? '')),
            'online_bookable' => !empty($payload['online_bookable']) ? 1 : 0,
            'commission_enabled' => !empty($payload['commission_enabled']) ? 1 : 0,
            'at_customer_location' => !empty($payload['at_customer_location']) ? 1 : 0,
            'extra_time_type' => $extraTimeType,
            'extra_time_minutes' => max(0, (int) ($payload['extra_time_minutes'] ?? 0)),
            'variants_raw' => $normalizedVariants,
            'staff_ids_raw' => array_values(array_filter($staffIds, static fn (int $staffId): bool => $staffId > 0)),
        ];
    }

    private function normalizeServicePackagePayload(array $payload): array
    {
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $normalizedItems = [];
        $serviceItemIds = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $type = (string) ($item['type'] ?? 'service');
            $itemId = (string) ($item['id'] ?? '');
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $extraTimeType = (string) ($item['extraTimeType'] ?? 'none');
            if (!in_array($extraTimeType, ['none', 'processing_after', 'blocked_after'], true)) {
                $extraTimeType = 'none';
            }

            $normalizedItems[] = [
                'key' => (string) ($item['key'] ?? ($type . '-' . $itemId . '-' . count($normalizedItems))),
                'type' => $type,
                'id' => $itemId,
                'name' => trim((string) ($item['name'] ?? '')),
                'price' => (float) ($item['price'] ?? 0),
                'duration' => (string) ($item['duration'] ?? ''),
                'brand' => (string) ($item['brand'] ?? ''),
                'stock' => (int) ($item['stock'] ?? 0),
                'groupId' => (string) ($item['groupId'] ?? ''),
                'groupName' => (string) ($item['groupName'] ?? ''),
                'qty' => $qty,
                'extraTimeType' => $extraTimeType,
                'extraTimeMinutes' => max(0, (int) ($item['extraTimeMinutes'] ?? 0)),
            ];

            if ($type === 'service' && ctype_digit($itemId)) {
                $serviceItemIds[] = (int) $itemId;
            }
        }

        return [
            'group_id' => ($groupId = (int) ($payload['group_id'] ?? 0)) > 0 ? $groupId : null,
            'name' => trim((string) ($payload['name'] ?? '')),
            'package_price' => (float) ($payload['package_price'] ?? 0),
            'description' => $this->nullIfEmpty((string) ($payload['description'] ?? '')),
            'pricing_mode' => trim((string) ($payload['pricing_mode'] ?? 'service')) ?: 'service',
            'discount_value' => (float) ($payload['discount_value'] ?? 0),
            'audience' => trim((string) ($payload['audience'] ?? 'all')) ?: 'all',
            'image_data_url' => $this->nullIfEmpty((string) ($payload['image_data_url'] ?? '')),
            'items_json' => json_encode($normalizedItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'service_item_ids' => array_values(array_unique(array_filter($serviceItemIds, static fn (int $serviceId): bool => $serviceId > 0))),
        ];
    }

    private function normalizeStaffPayload(array $payload): array
    {
        $serviceIds = array_values(array_unique(array_map('intval', is_array($payload['service_ids'] ?? null) ? $payload['service_ids'] : [])));
        $commissionRules = $payload['commission_rules'] ?? '{}';
        if (is_string($commissionRules)) {
            $decoded = json_decode($commissionRules, true);
            $commissionRules = is_array($decoded) ? $decoded : [];
        }

        return [
            'location_id' => max(1, (int) ($payload['location_id'] ?? 1)),
            'name' => trim((string) ($payload['name'] ?? '')),
            'email' => $this->nullIfEmpty((string) ($payload['email'] ?? '')),
            'phone' => $this->nullIfEmpty((string) ($payload['phone'] ?? '')),
            'role_title' => trim((string) ($payload['role_title'] ?? 'Basic')),
            'status' => trim((string) ($payload['status'] ?? 'Aktif')) ?: 'Aktif',
            'commission_type' => trim((string) ($payload['commission_type'] ?? 'Persentase')) ?: 'Persentase',
            'commission_value' => (float) ($payload['commission_value'] ?? 0),
            'rating' => (float) ($payload['rating'] ?? 0),
            'gender' => $this->nullIfEmpty((string) ($payload['gender'] ?? '')),
            'booking_enabled' => !empty($payload['booking_enabled']) ? 1 : 0,
            'agenda_color' => trim((string) ($payload['agenda_color'] ?? '#8cc9ff')) ?: '#8cc9ff',
            'started_working_on' => $this->nullIfEmpty((string) ($payload['started_working_on'] ?? '')),
            'ended_working_on' => $this->nullIfEmpty((string) ($payload['ended_working_on'] ?? '')),
            'public_title' => $this->nullIfEmpty((string) ($payload['public_title'] ?? '')),
            'notes' => $this->nullIfEmpty((string) ($payload['notes'] ?? '')),
            'instagram_handle' => $this->nullIfEmpty((string) ($payload['instagram_handle'] ?? '')),
            'photo_data_url' => $this->nullIfEmpty((string) ($payload['photo_data_url'] ?? '')),
            'commission_rules' => json_encode($commissionRules, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'attendance_pose' => trim((string) ($payload['attendance_pose'] ?? 'Right Tilt')) ?: 'Right Tilt',
            'attendance_uploaded_pose' => $this->nullIfEmpty((string) ($payload['attendance_uploaded_pose'] ?? '')),
            'service_ids_raw' => array_values(array_filter($serviceIds, static fn (int $serviceId): bool => $serviceId > 0)),
        ];
    }

    private function staffRoleId(): int
    {
        $row = $this->dbOne("SELECT id FROM roles WHERE name = 'staff' LIMIT 1");

        return $row !== null ? (int) $row['id'] : 2;
    }

    private function generateFallbackStaffEmail(string $name): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '.', $name), '.'));
        if ($slug === '') {
            $slug = 'staff';
        }

        return $slug . '.' . time() . '@starstyle.test';
    }

    private function staffPermissionDefaultsByRoleTitle(string $roleTitle, bool $bookingEnabled): array
    {
        $permissions = $this->permissions['defaults']['staff'] ?? [];
        if (!$bookingEnabled) {
            $permissions = array_values(array_filter($permissions, static fn (string $permission): bool => $permission !== 'calendar.view' && $permission !== 'calendar.create'));
        }

        return array_values(array_unique($permissions));
    }

    private function staffPermissionsForList(int $staffId, string $roleTitle): array
    {
        if (!$this->usingDb() || !$this->tableExists('staff_permissions')) {
            return $this->staffPermissionDefaultsByRoleTitle($roleTitle, true);
        }

        $rows = $this->dbAll(
            "SELECT permission_key
             FROM staff_permissions
             WHERE staff_id = :staff_id AND granted = 1
             ORDER BY permission_key",
            ['staff_id' => $staffId]
        );
        if ($rows === []) {
            return $this->staffPermissionDefaultsByRoleTitle($roleTitle, true);
        }

        return array_map(static fn (array $row): string => (string) $row['permission_key'], $rows);
    }

    private function expandShiftDates(string $startDate, string $repeatMode, string $repeatEnd, ?string $repeatEndDate): array
    {
        $startDate = trim($startDate);
        if ($startDate === '') {
            return [];
        }

        if ($repeatMode !== 'weekly') {
            return [$startDate];
        }

        $dates = [];
        $cursor = new \DateTimeImmutable($startDate);
        $limit = $repeatEnd === 'specific' && $repeatEndDate !== null && $repeatEndDate !== ''
            ? new \DateTimeImmutable($repeatEndDate)
            : $cursor->modify('+90 days');

        while ($cursor <= $limit) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify('+7 days');
        }

        return $dates;
    }

    private function normalizeCustomerPayload(array $payload): array
    {
        $lastVisit = trim((string) ($payload['last_visit_at'] ?? $payload['lastVisit'] ?? ''));
        if ($lastVisit === '') {
            $lastVisit = trim((string) ($payload['last_visit'] ?? ''));
        }

        $birthdate = trim((string) ($payload['birthdate'] ?? ''));
        $birthdate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate) === 1 ? $birthdate : null;

        $status = trim((string) ($payload['status'] ?? 'Aktif'));
        if ($status === '') {
            $status = 'Aktif';
        }

        $notifyVia = strtolower(trim((string) ($payload['notify_via'] ?? $payload['notifyVia'] ?? 'off')));
        if (!in_array($notifyVia, ['off', 'email', 'sms', 'whatsapp'], true)) {
            $notifyVia = 'off';
        }

        return [
            'member_id' => trim((string) ($payload['member_id'] ?? $payload['memberId'] ?? '')),
            'name' => trim((string) ($payload['name'] ?? '')),
            'gender' => trim((string) ($payload['gender'] ?? '')),
            'phone' => $this->nullIfEmpty((string) ($payload['phone'] ?? '')),
            'email' => $this->nullIfEmpty((string) ($payload['email'] ?? '')),
            'loyalty_points' => max(0, (int) ($payload['loyalty_points'] ?? $payload['loyalty'] ?? 0)),
            'last_visit_at' => $this->normalizeDateTime($lastVisit),
            'tags' => json_encode($this->normalizeCustomerTags($payload['tags'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'notes' => $this->nullIfEmpty((string) ($payload['notes'] ?? '')),
            'address' => $this->nullIfEmpty((string) ($payload['address'] ?? '')),
            'status' => $status,
            'birthdate' => $birthdate,
            'family_card_number' => $this->nullIfEmpty((string) ($payload['family_card_number'] ?? $payload['familyCardNumber'] ?? $payload['family_card'] ?? '')),
            'passport_number' => $this->nullIfEmpty((string) ($payload['passport_number'] ?? $payload['passportNumber'] ?? '')),
            'notify_via' => $notifyVia,
            'marketing_opt_in' => !empty($payload['marketing_opt_in']) || !empty($payload['marketingOptIn']) ? 1 : 0,
        ];
    }

    private function normalizeCustomerTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[|,]/', $tags) ?: [];
        }

        if (!is_array($tags)) {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            $value = trim((string) $tag);
            if ($value === '') {
                continue;
            }
            $normalized[$value] = true;
        }

        return array_keys($normalized);
    }

    private function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function nullIfEmpty(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function generateCustomerMemberId(): string
    {
        do {
            $memberId = 'MEM-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->dbOne(
                "SELECT id
                 FROM customers
                 WHERE member_id = :member_id
                 LIMIT 1",
                ['member_id' => $memberId]
            );
        } while ($exists !== null);

        return $memberId;
    }

    private function buildCustomerDetailPayload(array $customer, array $bookings, array $transactions, array $meta): array
    {
        $now = new \DateTimeImmutable();
        $upcoming = [];
        $past = [];
        $bookingCount = count($bookings);
        $completedCount = 0;
        $cancelCount = 0;
        $noShowCount = 0;

        foreach ($bookings as $booking) {
            $status = (string) ($booking['status'] ?? '');
            $statusKey = $this->customerStatusKey($status);
            if ($statusKey === 'completed') {
                $completedCount++;
            }
            if ($statusKey === 'cancelled') {
                $cancelCount++;
            }
            if ($statusKey === 'noshow') {
                $noShowCount++;
            }

            $item = [
                'date' => $this->formatCustomerDate((string) ($booking['start_at'] ?? '')),
                'type' => 'Agenda',
                'name' => (string) ($booking['service_names'] ?: 'Booking'),
                'staff' => (string) ($booking['staff_name'] ?? 'Staff'),
                'location' => (string) ($booking['location_name'] ?? 'Star Salon'),
                'total' => $this->formatCustomerAmount((float) ($booking['total_amount'] ?? 0)),
                'note' => (string) ($booking['notes'] ?? '-'),
                'status' => strtoupper($status !== '' ? $status : 'NEW'),
                'statusKey' => $statusKey,
            ];

            $bookingDate = isset($booking['start_at']) ? new \DateTimeImmutable((string) $booking['start_at']) : null;
            if ($bookingDate !== null && $bookingDate >= $now) {
                $upcoming[] = $item;
            } else {
                $past[] = $item;
            }
        }

        $serviceRows = [];
        $productRows = [];
        $invoiceRows = [];
        $totalSales = 0.0;

        foreach ($transactions as $transaction) {
            $grossTotal = (float) ($transaction['gross_total'] ?? 0);
            $totalSales += max(0, $grossTotal - (float) ($transaction['discount_amount'] ?? 0) + (float) ($transaction['rounding_amount'] ?? 0));
            $paidAt = (string) ($transaction['paid_at'] ?? '');
            $locationName = (string) ($transaction['location_name'] ?? 'Star Salon');

            if ((float) ($transaction['service_total'] ?? 0) > 0) {
                $serviceRows[] = [
                    'name' => (string) ($transaction['service_names'] ?: 'Layanan'),
                    'paymentDate' => $this->formatCustomerDateTime($paidAt),
                    'location' => $locationName,
                    'quantity' => (string) (int) ($transaction['service_qty'] ?? 0),
                    'total' => $this->formatCustomerAmount((float) ($transaction['service_total'] ?? 0)),
                ];
            }

            if ((float) ($transaction['product_total'] ?? 0) > 0) {
                $productRows[] = [
                    'product' => (string) ($transaction['product_names'] ?: 'Produk'),
                    'amount' => (string) (int) ($transaction['product_qty'] ?? 0),
                    'paymentDate' => $this->formatCustomerDateTime($paidAt),
                    'location' => $locationName,
                    'total' => $this->formatCustomerAmount((float) ($transaction['product_total'] ?? 0)),
                ];
            }

            $invoiceStatus = (string) ($transaction['status'] ?? 'paid');
            $invoiceRows[] = [
                'invoiceDate' => $this->formatCustomerDateTime($paidAt),
                'invoice' => (string) ($transaction['invoice_number'] ?? $transaction['reference'] ?? '-'),
                'status' => strtoupper($invoiceStatus),
                'statusKey' => $this->customerStatusKey($invoiceStatus),
                'location' => $locationName,
                'total' => $this->formatCustomerAmount($grossTotal),
            ];
        }

        return [
            'stats' => [
                'totalSales' => $this->formatCustomerAmount($totalSales),
                'voucherUse' => (string) (int) ($meta['voucher_usage'] ?? 0),
                'due' => $this->formatCustomerAmount(0),
                'totalBooking' => (string) $bookingCount,
                'completed' => (string) $completedCount,
                'cancel' => (string) $cancelCount,
                'noShow' => (string) $noShowCount,
            ],
            'sections' => [
                'agenda' => [
                    'upcoming' => $upcoming,
                    'past' => $past,
                ],
                'layanan' => [
                    'upcoming' => [],
                    'past' => $serviceRows,
                ],
                'produk' => [
                    'upcoming' => [],
                    'past' => $productRows,
                ],
                'faktur' => [
                    'upcoming' => [],
                    'past' => $invoiceRows,
                ],
            ],
        ];
    }

    private function formatCustomerAmount(float $value): string
    {
        return number_format($value, 2, ',', '.');
    }

    private function formatCustomerDate(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        try {
            return (new \DateTimeImmutable($value))->format('d M Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function formatCustomerDateTime(string $value): string
    {
        if ($value === '') {
            return '-';
        }

        try {
            return (new \DateTimeImmutable($value))->format('d M Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function customerStatusKey(string $status): string
    {
        $normalized = strtolower(trim($status));

        return match ($normalized) {
            'cancelled', 'canceled', 'batal' => 'cancelled',
            'completed', 'done', 'selesai', 'paid' => 'completed',
            'no-show', 'noshow', 'tidak hadir' => 'noshow',
            default => 'new',
        };
    }

    private function ensureVoucherCatalogSchema(): void
    {
        if (!$this->usingDb() || $this->voucherCatalogSchemaEnsured) {
            return;
        }

        if (!$this->tableExists('vouchers')) {
            $this->dbExecute(
                "CREATE TABLE vouchers (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    voucher_type ENUM('service', 'class', 'gift') NOT NULL,
                    name VARCHAR(120) NOT NULL,
                    code VARCHAR(80) NOT NULL UNIQUE,
                    value DECIMAL(12,2) NOT NULL DEFAULT 0,
                    price_value DECIMAL(12,2) NOT NULL DEFAULT 0,
                    usage_limit INT NOT NULL DEFAULT 1,
                    used_count INT NOT NULL DEFAULT 0,
                    expired_at DATE NOT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'Aktif',
                    location_name VARCHAR(150) NOT NULL DEFAULT 'Semua Lokasi',
                    message_text TEXT NULL,
                    service_items_json LONGTEXT NULL,
                    combine_quantity TINYINT(1) NOT NULL DEFAULT 0,
                    max_quantity INT NOT NULL DEFAULT 1,
                    expiry_mode VARCHAR(20) NOT NULL DEFAULT 'relative',
                    expiry_value VARCHAR(60) NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME NULL DEFAULT NULL
                )"
            );
        }

        $voucherColumns = [
            'price_value' => 'ADD COLUMN price_value DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER value',
            'location_name' => "ADD COLUMN location_name VARCHAR(150) NOT NULL DEFAULT 'Semua Lokasi' AFTER status",
            'message_text' => 'ADD COLUMN message_text TEXT NULL AFTER location_name',
            'service_items_json' => 'ADD COLUMN service_items_json LONGTEXT NULL AFTER message_text',
            'combine_quantity' => 'ADD COLUMN combine_quantity TINYINT(1) NOT NULL DEFAULT 0 AFTER service_items_json',
            'max_quantity' => 'ADD COLUMN max_quantity INT NOT NULL DEFAULT 1 AFTER combine_quantity',
            'expiry_mode' => "ADD COLUMN expiry_mode VARCHAR(20) NOT NULL DEFAULT 'relative' AFTER max_quantity",
            'expiry_value' => 'ADD COLUMN expiry_value VARCHAR(60) NULL AFTER expiry_mode',
            'created_at' => 'ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER expiry_value',
            'updated_at' => 'ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER created_at',
            'deleted_at' => 'ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL AFTER updated_at',
        ];
        foreach ($voucherColumns as $column => $ddl) {
            if (!$this->columnExists('vouchers', $column)) {
                $this->dbExecute("ALTER TABLE vouchers {$ddl}");
            }
        }

        if (!$this->tableExists('voucher_discounts')) {
            $this->dbExecute(
                "CREATE TABLE voucher_discounts (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL,
                    mode VARCHAR(20) NOT NULL DEFAULT 'amount',
                    amount_value DECIMAL(12,2) NOT NULL DEFAULT 0,
                    max_discount_value DECIMAL(12,2) NULL DEFAULT NULL,
                    scopes_json LONGTEXT NULL,
                    status VARCHAR(30) NOT NULL DEFAULT 'Aktif',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    deleted_at DATETIME NULL DEFAULT NULL
                )"
            );
        }

        $this->voucherCatalogSchemaEnsured = true;
    }

    private function mapVoucherRecord(array $voucher): array
    {
        $type = (string) ($voucher['voucher_type'] ?? 'gift');
        $serviceItems = json_decode((string) ($voucher['service_items_json'] ?? '[]'), true);
        $serviceItems = is_array($serviceItems) ? $serviceItems : [];
        $isGift = $type === 'gift';
        $combineQuantity = (bool) ($voucher['combine_quantity'] ?? false);
        $maxQuantity = max(1, (int) ($voucher['max_quantity'] ?? 1));
        $status = (string) ($voucher['status'] ?? 'Aktif');
        $expiryValue = (string) (($voucher['expiry_value'] ?? '') ?: ($voucher['expired_at'] ?? ''));
        $duration = $this->formatVoucherDurationLabel($expiryValue);
        $value = $isGift
            ? $this->formatCurrencyAmount((float) ($voucher['value'] ?? 0))
            : $this->buildVoucherServiceDisplay($serviceItems, $combineQuantity, $maxQuantity);

        return [
            'id' => (int) ($voucher['id'] ?? 0),
            'code' => (string) ($voucher['code'] ?? ''),
            'type' => $type,
            'type_key' => $type,
            'type_code' => $isGift ? 'G' : 'S',
            'type_label' => $isGift ? 'Gift Type' : 'Service Type',
            'name' => (string) ($voucher['name'] ?? ''),
            'value' => $value,
            'editor_value' => (string) ($isGift ? (float) ($voucher['value'] ?? 0) : (float) ($voucher['price_value'] ?? 0)),
            'price_value' => (string) ((float) ($voucher['price_value'] ?? 0)),
            'duration' => $duration,
            'expiry_label' => $expiryValue,
            'expiry_value' => $expiryValue,
            'location' => (string) (($voucher['location_name'] ?? '') ?: 'Semua Lokasi'),
            'status' => $this->formatVoucherStatusLabel($status),
            'service_name' => $this->buildVoucherServiceDisplay($serviceItems, $combineQuantity, $maxQuantity),
            'message' => (string) (($voucher['message_text'] ?? '') ?: 'Thank you!'),
            'active' => in_array(strtolower(trim($status)), ['aktif', 'active'], true),
            'search' => strtolower(trim(implode(' ', [
                $isGift ? 'gift type' : 'service type',
                (string) ($voucher['name'] ?? ''),
                $value,
                $duration,
                (string) (($voucher['location_name'] ?? '') ?: 'Semua Lokasi'),
                $this->formatVoucherStatusLabel($status),
            ]))),
            'usage_limit' => (int) ($voucher['usage_limit'] ?? 1),
            'used' => (int) ($voucher['used_count'] ?? 0),
            'expired_at' => (string) ($voucher['expired_at'] ?? ''),
            'services_json' => json_encode($serviceItems, JSON_UNESCAPED_UNICODE),
            'combine_quantity' => $combineQuantity,
            'max_quantity' => $maxQuantity,
        ];
    }

    private function mapDemoVoucherRecord(array $voucher): array
    {
        return [
            'id' => (int) ($voucher['id'] ?? 0),
            'code' => (string) ($voucher['code'] ?? ''),
            'type' => (string) ($voucher['type'] ?? 'gift'),
            'type_key' => (string) ($voucher['type'] ?? 'gift'),
            'type_code' => strtoupper(substr((string) ($voucher['type'] ?? 'g'), 0, 1)),
            'type_label' => (($voucher['type'] ?? 'gift') === 'gift') ? 'Gift Type' : 'Service Type',
            'name' => (string) ($voucher['name'] ?? ''),
            'value' => (($voucher['type'] ?? 'gift') === 'gift')
                ? $this->formatCurrencyAmount((float) ($voucher['value'] ?? 0))
                : (string) (($voucher['service_name'] ?? $voucher['name'] ?? 'No item')),
            'editor_value' => (string) ((float) ($voucher['value'] ?? 0)),
            'price_value' => (string) ((float) ($voucher['value'] ?? 0)),
            'duration' => $this->formatVoucherDurationLabel((string) ($voucher['expired_at'] ?? '')),
            'expiry_label' => (string) ($voucher['expired_at'] ?? ''),
            'expiry_value' => (string) ($voucher['expired_at'] ?? ''),
            'location' => 'Semua Lokasi',
            'status' => $this->formatVoucherStatusLabel((string) ($voucher['status'] ?? 'Aktif')),
            'service_name' => (string) (($voucher['service_name'] ?? '') ?: ($voucher['name'] ?? '')),
            'message' => 'Thank you!',
            'active' => in_array(strtolower(trim((string) ($voucher['status'] ?? 'Aktif'))), ['aktif', 'active'], true),
            'search' => strtolower((string) (($voucher['name'] ?? '') . ' ' . ($voucher['code'] ?? ''))),
            'usage_limit' => (int) ($voucher['usage_limit'] ?? 1),
            'used' => (int) ($voucher['used'] ?? 0),
            'expired_at' => (string) ($voucher['expired_at'] ?? ''),
            'services_json' => '[]',
            'combine_quantity' => false,
            'max_quantity' => 1,
        ];
    }

    private function mapVoucherDiscountRecord(array $discount): array
    {
        $mode = (string) ($discount['mode'] ?? 'amount');
        $amount = (float) ($discount['amount_value'] ?? 0);
        $max = (float) ($discount['max_discount_value'] ?? 0);
        $scopes = json_decode((string) ($discount['scopes_json'] ?? '[]'), true);
        $scopes = is_array($scopes) ? array_values(array_filter(array_map('strval', $scopes))) : [];

        return [
            'id' => (int) ($discount['id'] ?? 0),
            'name' => (string) ($discount['name'] ?? ''),
            'mode' => $mode === 'percent' ? 'percent' : 'amount',
            'amount_label' => $mode === 'percent' ? number_format($amount, 2, '.', '') . ' %' : $this->formatCurrencyAmount($amount),
            'amount_value' => $this->trimDecimalString($amount),
            'max_discount' => $this->formatCurrencyAmount($max),
            'max_discount_value' => $this->trimDecimalString($max),
            'applies_to' => $scopes,
            'search' => strtolower((string) (($discount['name'] ?? '') . ' ' . $amount . ' ' . implode(' ', $scopes))),
        ];
    }

    private function normalizeVoucherPayload(array $payload): array
    {
        $type = strtolower(trim((string) ($payload['type'] ?? $payload['voucher_type'] ?? 'gift')));
        if (!in_array($type, ['gift', 'service'], true)) {
            $type = 'gift';
        }

        $serviceItems = $payload['service_items'] ?? $payload['services'] ?? [];
        if (is_string($serviceItems)) {
            $serviceItems = json_decode($serviceItems, true);
        }
        $serviceItems = is_array($serviceItems) ? array_values(array_filter(array_map(static function (mixed $item): ?array {
            if (!is_array($item)) {
                return null;
            }

            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                return null;
            }

            return [
                'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'name' => $name,
                'price' => (string) ($item['price'] ?? 'Rp 0,00'),
                'duration' => (string) ($item['duration'] ?? ''),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            ];
        }, $serviceItems))) : [];

        $value = (float) ($payload['value'] ?? $payload['amount_value'] ?? 0);
        $priceValue = (float) ($payload['price_value'] ?? $payload['price'] ?? $value);
        $expiryValue = trim((string) ($payload['expiry_value'] ?? $payload['expiry_label'] ?? 'After 1 Month'));
        $status = (int) ($payload['active'] ?? 1) === 1 ? 'Aktif' : 'Nonaktif';

        return [
            'voucher_type' => $type,
            'name' => trim((string) ($payload['name'] ?? '')),
            'code' => trim((string) ($payload['code'] ?? '')),
            'value' => $type === 'gift' ? max(0, $value) : 0,
            'price_value' => max(0, $priceValue),
            'usage_limit' => max(1, (int) ($payload['usage_limit'] ?? 1)),
            'expired_at' => $this->resolveVoucherExpiredAt($expiryValue),
            'status' => $status,
            'location_name' => trim((string) ($payload['location_name'] ?? $payload['location'] ?? '')) ?: 'Semua Lokasi',
            'message_text' => trim((string) ($payload['message_text'] ?? $payload['message'] ?? '')) ?: 'Thank you!',
            'service_items_json' => json_encode($serviceItems, JSON_UNESCAPED_UNICODE),
            'combine_quantity' => !empty($payload['combine_quantity']) ? 1 : 0,
            'max_quantity' => max(1, (int) ($payload['max_quantity'] ?? 1)),
            'expiry_mode' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryValue) ? 'specific' : 'relative',
            'expiry_value' => $expiryValue,
        ];
    }

    private function normalizeVoucherDiscountPayload(array $payload): array
    {
        $mode = strtolower(trim((string) ($payload['mode'] ?? 'amount')));
        if (!in_array($mode, ['amount', 'percent'], true)) {
            $mode = 'amount';
        }

        $scopes = $payload['scopes'] ?? [];
        if (is_string($scopes)) {
            $decodedScopes = json_decode($scopes, true);
            $scopes = is_array($decodedScopes) ? $decodedScopes : preg_split('/[|,]/', $scopes);
        }
        $scopes = is_array($scopes) ? array_values(array_filter(array_map(static fn (mixed $scope): string => trim((string) $scope), $scopes))) : [];

        return [
            'name' => trim((string) ($payload['name'] ?? '')),
            'mode' => $mode,
            'amount_value' => max(0, (float) ($payload['amount_value'] ?? $payload['amount'] ?? 0)),
            'max_discount_value' => $mode === 'percent' ? max(0, (float) ($payload['max_discount_value'] ?? $payload['max_discount'] ?? 0)) : 0,
            'scopes_json' => json_encode($scopes, JSON_UNESCAPED_UNICODE),
            'status' => 'Aktif',
        ];
    }

    private function generateVoucherCode(string $type): string
    {
        $prefix = $type === 'gift' ? 'GIF' : 'SRV';

        do {
            $code = $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = $this->dbOne("SELECT id FROM vouchers WHERE code = :code LIMIT 1", ['code' => $code]);
        } while ($exists !== null);

        return $code;
    }

    private function resolveVoucherExpiredAt(string $expiryValue): string
    {
        $value = trim($expiryValue);
        if ($value === '') {
            return date('Y-m-d', strtotime('+1 month'));
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        $normalized = strtolower($value);
        return match (true) {
            str_contains($normalized, 'no expiry') => '2099-12-31',
            str_contains($normalized, '2 week') => date('Y-m-d', strtotime('+2 weeks')),
            str_contains($normalized, '1 week') => date('Y-m-d', strtotime('+1 week')),
            str_contains($normalized, '2 month') => date('Y-m-d', strtotime('+2 months')),
            str_contains($normalized, '3 month') => date('Y-m-d', strtotime('+3 months')),
            str_contains($normalized, '6 month') => date('Y-m-d', strtotime('+6 months')),
            str_contains($normalized, '1 year') => date('Y-m-d', strtotime('+1 year')),
            default => date('Y-m-d', strtotime('+1 month')),
        };
    }

    private function formatVoucherStatusLabel(string $status): string
    {
        return in_array(strtolower(trim($status)), ['aktif', 'active'], true) ? 'Active' : 'Disable';
    }

    private function formatVoucherDurationLabel(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '1 Bulan';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $trimmed)) {
            try {
                return (new \DateTimeImmutable($trimmed))->format('d M Y');
            } catch (\Throwable) {
                return $trimmed;
            }
        }

        $normalized = strtolower($trimmed);
        return match (true) {
            str_contains($normalized, 'no expiry') => 'Tanpa Kadaluarsa',
            str_contains($normalized, '2 week') => '2 Minggu',
            str_contains($normalized, '1 week') => '1 Minggu',
            str_contains($normalized, '3 month') => '3 Bulan',
            str_contains($normalized, '2 month') => '2 Bulan',
            str_contains($normalized, '6 month') => '6 Bulan',
            str_contains($normalized, '1 year') => '1 Tahun',
            default => '1 Bulan',
        };
    }

    private function buildVoucherServiceDisplay(array $serviceItems, bool $combineQuantity, int $maxQuantity): string
    {
        if ($serviceItems === []) {
            return 'No item';
        }

        $names = array_map(static function (array $item) use ($combineQuantity): string {
            $name = trim((string) ($item['name'] ?? ''));
            $quantity = max(1, (int) ($item['quantity'] ?? 1));

            if ($combineQuantity || $name === '') {
                return $name;
            }

            return $quantity > 1 ? $quantity . 'x ' . $name : $name;
        }, $serviceItems);
        $names = array_values(array_filter($names));

        if ($names === []) {
            return 'No item';
        }

        return $combineQuantity ? ($maxQuantity . 'x ' . implode(', ', $names)) : implode(', ', $names);
    }

    private function formatCurrencyAmount(float $value): string
    {
        return 'Rp ' . number_format($value, 2, ',', '.');
    }

    private function trimDecimalString(float $value): string
    {
        $formatted = number_format($value, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    private function findVoucherRow(int $voucherId): ?array
    {
        foreach ($this->getVouchers() as $voucher) {
            if ((int) ($voucher['id'] ?? 0) === $voucherId) {
                return $voucher;
            }
        }

        return null;
    }

    private function findVoucherDiscountRow(int $discountId): ?array
    {
        foreach ($this->getVoucherDiscounts() as $discount) {
            if ((int) ($discount['id'] ?? 0) === $discountId) {
                return $discount;
            }
        }

        return null;
    }

    private function normalizeBusinessHoursSchedule(mixed $schedule): array
    {
        $defaults = [
            ['key' => 'minggu', 'label' => 'Minggu', 'enabled' => true, 'open' => '03:00', 'close' => '23:00'],
            ['key' => 'senin', 'label' => 'Senin', 'enabled' => true, 'open' => '00:00', 'close' => '23:00'],
            ['key' => 'selasa', 'label' => 'Selasa', 'enabled' => false, 'open' => '08:00', 'close' => '22:00'],
            ['key' => 'rabu', 'label' => 'Rabu', 'enabled' => false, 'open' => '08:00', 'close' => '22:00'],
            ['key' => 'kamis', 'label' => 'Kamis', 'enabled' => false, 'open' => '08:00', 'close' => '22:00'],
            ['key' => 'jumat', 'label' => 'Jumat', 'enabled' => false, 'open' => '08:00', 'close' => '22:00'],
            ['key' => 'sabtu', 'label' => 'Sabtu', 'enabled' => true, 'open' => '08:00', 'close' => '22:00'],
        ];

        $indexed = [];
        if (is_array($schedule)) {
            foreach ($schedule as $day) {
                if (!is_array($day)) {
                    continue;
                }

                $key = trim((string) ($day['key'] ?? ''));
                if ($key === '') {
                    continue;
                }

                $indexed[$key] = [
                    'key' => $key,
                    'label' => (string) ($day['label'] ?? ucfirst($key)),
                    'enabled' => !empty($day['enabled']),
                    'open' => preg_match('/^\d{2}:\d{2}$/', (string) ($day['open'] ?? '')) === 1 ? (string) $day['open'] : '08:00',
                    'close' => preg_match('/^\d{2}:\d{2}$/', (string) ($day['close'] ?? '')) === 1 ? (string) $day['close'] : '22:00',
                ];
            }
        }

        $normalized = [];
        foreach ($defaults as $default) {
            $key = $default['key'];
            $normalized[] = $indexed[$key] ?? $default;
        }

        return $normalized;
    }

    private function summarizeBusinessHoursSchedule(array $schedule): string
    {
        $activeDays = array_values(array_filter($schedule, static fn (array $day): bool => !empty($day['enabled'])));
        if ($activeDays === []) {
            return 'Tutup';
        }

        $first = $activeDays[0];
        $sameHours = array_reduce(
            $activeDays,
            static fn (bool $carry, array $day): bool => $carry && $day['open'] === $first['open'] && $day['close'] === $first['close'],
            true
        );

        if ($sameHours) {
            return $first['open'] . ' - ' . $first['close'];
        }

        $opens = array_map(static fn (array $day): string => (string) $day['open'], $activeDays);
        $closes = array_map(static fn (array $day): string => (string) $day['close'], $activeDays);

        return min($opens) . ' - ' . max($closes);
    }
}
