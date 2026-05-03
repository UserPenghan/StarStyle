<?php

declare(strict_types=1);

namespace App\Services;

final class SalonRepository
{
    private array $baseData;
    private ?\PDO $pdo = null;

    public function __construct(
        private readonly array $config,
        private readonly array $permissions,
    ) {
        date_default_timezone_set($config['timezone'] ?? 'Asia/Bangkok');
        $this->baseData = DemoData::make($config);
        $this->bootSessionState();
    }

    private function usingDb(): bool
    {
        return ($this->config['data_source'] ?? 'demo') === 'db';
    }

    private function pdo(): \PDO
    {
        if (!$this->usingDb()) {
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

    public function findUserByEmail(string $email, string $portal): ?array
    {
        if ($this->usingDb()) {
            $stmt = $this->pdo()->prepare(
                "SELECT u.id, u.name, u.email, u.password, u.portal, u.avatar, r.name AS role
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE LOWER(u.email) = LOWER(:email) AND u.portal = :portal AND u.is_active = 1
                 LIMIT 1"
            );
            $stmt->execute([
                'email' => $email,
                'portal' => $portal,
            ]);
            $row = $stmt->fetch();

            return is_array($row) ? $row : null;
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
            $stmt = $this->pdo()->prepare(
                "SELECT u.id, u.name, u.email, u.password, u.portal, u.avatar, r.name AS role
                 FROM users u
                 JOIN roles r ON r.id = u.role_id
                 WHERE u.id = :id AND u.is_active = 1
                 LIMIT 1"
            );
            $stmt->execute(['id' => $userId]);
            $row = $stmt->fetch();

            return is_array($row) ? $row : null;
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

        return [
            'heroMetrics' => [
                ['label' => 'Booking aktif hari ini', 'value' => count(array_filter($bookings, fn (array $booking): bool => str_starts_with($booking['start_at'], date('Y-m-d')) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true)))],
                ['label' => 'Transaksi minggu ini', 'value' => count($transactions)],
                ['label' => 'Layanan premium', 'value' => count(array_filter($services, fn (array $service): bool => $service['price'] >= 500000))],
            ],
            'featuredServices' => array_slice($services, 0, 4),
            'packages' => $this->getPackages(),
            'reviews' => $this->baseData['reviews'],
            'business' => $this->config['business'],
        ];
    }

    public function getServices(): array
    {
        if ($this->usingDb()) {
            $stmt = $this->pdo()->query(
                "SELECT s.id, s.name, s.duration_minutes, s.base_price AS price, s.status, s.description, g.name AS group_name
                 FROM services s
                 JOIN service_groups g ON g.id = s.group_id
                 WHERE s.deleted_at IS NULL
                 ORDER BY s.id"
            );

            return $stmt->fetchAll();
        }

        return $this->baseData['services'];
    }

    public function getServiceGroups(): array
    {
        if ($this->usingDb()) {
            $stmt = $this->pdo()->query("SELECT id, name, description FROM service_groups ORDER BY id");

            return $stmt->fetchAll();
        }

        return $this->baseData['service_groups'];
    }

    public function getPackages(): array
    {
        return $this->baseData['packages'];
    }

    public function getStaff(): array
    {
        if ($this->usingDb()) {
            $stmt = $this->pdo()->query(
                "SELECT s.id, s.name, s.email, s.phone, s.role_title, s.status, s.rating, l.name AS location_name
                 FROM staff s
                 LEFT JOIN locations l ON l.id = s.location_id
                 WHERE s.deleted_at IS NULL
                 ORDER BY s.id"
            );

            return $stmt->fetchAll();
        }

        return $this->baseData['staff'];
    }

    public function getCustomers(): array
    {
        if ($this->usingDb()) {
            $stmt = $this->pdo()->query(
                "SELECT id, member_id, name, gender, phone, email, loyalty_points, last_visit_at, status, notes
                 FROM customers
                 WHERE deleted_at IS NULL
                 ORDER BY id"
            );

            return $stmt->fetchAll();
        }

        return $this->baseData['customers'];
    }

    public function getProducts(): array
    {
        if ($this->usingDb()) {
            // Keep shape similar to demo (at minimum id/name fields).
            $stmt = $this->pdo()->query(
                "SELECT p.id, p.name, p.sku, p.status, p.description
                 FROM products p
                 WHERE p.deleted_at IS NULL
                 ORDER BY p.id"
            );

            return $stmt->fetchAll();
        }

        return $this->baseData['products'];
    }

    public function getVouchers(): array
    {
        return $this->baseData['vouchers'];
    }

    public function getClasses(): array
    {
        return $this->baseData['classes'];
    }

    public function getReviews(): array
    {
        return $this->baseData['reviews'];
    }

    public function getLogs(): array
    {
        $logs = array_merge($this->baseData['activity_logs'], $_SESSION['starstyle']['activity_logs']);
        usort($logs, fn (array $a, array $b): int => strcmp($b['time'], $a['time']));

        return $logs;
    }

    public function getSettings(): array
    {
        return $this->baseData['settings'];
    }

    public function getNotifications(): array
    {
        return $this->baseData['notifications'];
    }

    public function getAttendance(): array
    {
        return $this->baseData['attendance'];
    }

    public function getShifts(): array
    {
        return $this->baseData['shifts'];
    }

    public function getBookings(): array
    {
        $bookings = array_merge($this->baseData['bookings'], $_SESSION['starstyle']['bookings']);
        usort($bookings, fn (array $a, array $b): int => strcmp($a['start_at'], $b['start_at']));

        return $bookings;
    }

    public function getBlocks(): array
    {
        return array_merge($this->baseData['booking_blocks'], $_SESSION['starstyle']['booking_blocks']);
    }

    public function getTransactions(): array
    {
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
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $date = trim((string) ($payload['date'] ?? ''));
        $time = trim((string) ($payload['time'] ?? ''));
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        $customerPhone = trim((string) ($payload['customer_phone'] ?? ''));

        if ($serviceIds === [] || $staffId === 0 || $date === '' || $time === '' || $customerName === '') {
            return ['success' => false, 'message' => 'Mohon lengkapi layanan, staff, tanggal, dan data pelanggan.'];
        }

        $duration = array_reduce($serviceIds, fn (int $carry, int $serviceId): int => $carry + ((int) ($this->findService($serviceId)['duration'] ?? 0)), 0);
        $start = new \DateTimeImmutable("{$date} {$time}:00");
        $end = $start->modify("+{$duration} minutes");
        $dailyBookings = array_filter($this->getBookings(), fn (array $booking): bool => $booking['staff_id'] === $staffId && str_starts_with($booking['start_at'], $date) && in_array($booking['status'], ['new', 'pending', 'confirmed', 'arrived', 'started'], true));
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

            $serviceItems[] = [
                'service_id' => $serviceId,
                'staff_id' => $serviceStaffId,
                'start_at' => $serviceStart->format('Y-m-d H:i:s'),
                'end_at' => $serviceEnd->format('Y-m-d H:i:s'),
                'duration' => $serviceDuration,
            ];

            if ($serviceStartTime === '') {
                $serviceCursor = $serviceEnd;
            }
        }

        $customerId = $this->resolveCustomer($customerName, $customerPhone);
        $id = $this->nextId($_SESSION['starstyle']['bookings'], 9000);
        $booking = [
            'id' => $id,
            'reference' => 'BK-' . date('ymd') . '-' . $id,
            'customer_id' => $customerId,
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_items' => $serviceItems,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'status' => 'new',
            'channel' => $source === 'customer' ? 'Portal Customer' : 'Internal',
            'notes' => trim((string) ($payload['notes'] ?? '')),
        ];

        $_SESSION['starstyle']['bookings'][] = $booking;
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $source === 'customer' ? $customerName : 'Admin',
            'action' => 'Membuat booking ' . $booking['reference'],
        ];

        return ['success' => true, 'message' => 'Booking berhasil dibuat.', 'booking' => $booking];
    }

    public function createBlock(array $payload, array $actor): array
    {
        $staffId = (int) ($payload['staff_id'] ?? 0);
        $date = trim((string) ($payload['date'] ?? ''));
        $startTime = trim((string) ($payload['start_time'] ?? ''));
        $endTime = trim((string) ($payload['end_time'] ?? ''));
        $title = trim((string) ($payload['title'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($staffId === 0 || $date === '' || $startTime === '' || $endTime === '' || $title === '') {
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
            'transactions' => $transactions,
            'services' => $this->getServices(),
            'products' => $this->getProducts(),
            'vouchers' => $this->getVouchers(),
            'cash_drawers' => $this->baseData['cash_drawers'],
            'cash_movements' => $this->baseData['cash_movements'],
            'classes' => $this->getClasses(),
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

            if ($expired || $limitReached || $voucher['status'] !== 'Aktif') {
                return ['valid' => false, 'message' => 'Voucher tidak aktif atau sudah expired.', 'voucher' => $voucher];
            }

            return ['valid' => true, 'message' => 'Voucher valid.', 'voucher' => $voucher];
        }

        return ['valid' => false, 'message' => 'Kode voucher tidak ditemukan.', 'voucher' => null];
    }

    public function analytics(): array
    {
        $transactions = $this->getTransactions();
        $customers = $this->getCustomers();
        $inventory = $this->getProducts();
        $bookings = $this->getBookings();
        $staff = $this->getStaff();
        $vouchers = $this->getVouchers();
        $classes = $this->getClasses();

        return [
            'kpis' => [
                ['label' => 'Appointment Conversion', 'value' => '86%'],
                ['label' => 'Sales Growth', 'value' => '+18%'],
                ['label' => 'Retention Rate', 'value' => '72%'],
                ['label' => 'Low Stock Item', 'value' => (string) count(array_filter($inventory, fn (array $product): bool => $product['stock'] <= 8))],
            ],
            'salesByType' => [
                'service' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'service'), $transactions)),
                'product' => array_sum(array_map(fn (array $transaction): float => $this->sumItemsByType($transaction['items'], 'product'), $transactions)),
                'voucher' => 240000,
            ],
            'retention' => [
                'new' => 24,
                'returning' => 61,
                'vip' => count(array_filter($customers, fn (array $customer): bool => in_array('VIP', $customer['tags'], true))),
            ],
            'inventory' => $inventory,
            'bookings' => $bookings,
            'transactions' => $transactions,
            'customers' => $customers,
            'staff' => $staff,
            'vouchers' => $vouchers,
            'classes' => $classes,
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

    public function customerAccount(int $customerId): array
    {
        return [
            'customer' => $this->findCustomer($customerId),
            'bookings' => array_values(array_filter($this->getBookings(), fn (array $booking): bool => $booking['customer_id'] === $customerId)),
            'transactions' => array_values(array_filter($this->getTransactions(), fn (array $transaction): bool => $transaction['customer_id'] === $customerId)),
            'vouchers' => $this->getVouchers(),
        ];
    }

    public function settingsPayload(): array
    {
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

    public function updateStaffPermissions(int $staffId, array $grantedPermissions, string $actorName): void
    {
        $_SESSION['starstyle']['staff_permissions'][$staffId] = array_values(array_unique($grantedPermissions));
        $_SESSION['starstyle']['activity_logs'][] = [
            'time' => date('Y-m-d H:i:s'),
            'actor' => $actorName,
            'action' => 'Mengubah permission staff ID #' . $staffId,
        ];
    }

    public function searchCustomers(string $query): array
    {
        return array_values(array_filter($this->getCustomers(), fn (array $customer): bool => stripos($customer['name'], $query) !== false || stripos($customer['phone'], $query) !== false));
    }

    public function servicesByStaff(int $staffId): array
    {
        return array_values(array_filter($this->getServices(), fn (array $service): bool => in_array($staffId, $service['staff_ids'], true)));
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

    private function resolveCustomer(string $name, string $phone): int
    {
        foreach ($this->getCustomers() as $customer) {
            if (strcasecmp($customer['name'], $name) === 0 || $customer['phone'] === $phone) {
                return $customer['id'];
            }
        }

        return 1;
    }
}
