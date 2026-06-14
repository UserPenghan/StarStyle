<?php

declare(strict_types=1);

namespace App\Controllers;

final class PublicController extends BaseController
{
    public function home(): void
    {
        $this->view('pages/public/home', array_merge($this->repo()->getLandingData(), [
            'title' => 'StarStyle Salon',
            'page' => '/',
            'publicNav' => config('public_nav'),
            'success' => flash('success'),
            'error' => flash('error'),
        ]), 'public');
    }

    public function services(): void
    {
        $groups = [];

        foreach ($this->repo()->getServiceGroups() as $group) {
            $groups[] = [
                'group' => $group,
                'services' => array_values(array_filter($this->repo()->getServices(), fn (array $service): bool => $service['group_id'] === $group['id'])),
            ];
        }

        $this->view('pages/public/services', [
            'title' => 'Daftar Layanan',
            'page' => '/services-catalog',
            'publicNav' => config('public_nav'),
            'groups' => $groups,
        ], 'public');
    }

    public function booking(): void
    {
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];

        $this->view('pages/public/booking', [
            'title' => 'Reservasi Salon',
            'page' => '/booking',
            'publicNav' => config('public_nav'),
            'bookingBusiness' => [
                'name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'location_name' => (string) ($primaryLocation['name'] ?? $settings['business_name'] ?? $business['name'] ?? 'Star Salon'),
                'hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'address' => (string) ($primaryLocation['address'] ?? $settings['address'] ?? $business['address'] ?? ''),
                'hotline' => (string) ($business['hotline'] ?? ''),
                'email' => (string) ($business['email'] ?? ''),
                'cover_image_url' => $this->resolveBookingCoverImage(),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function bookingServices(): void
    {
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];
        $services = array_values(array_filter(
            $this->repo()->getServices(),
            static fn (array $service): bool => (($service['status'] ?? 'Aktif') === 'Aktif') && (($service['online_bookable'] ?? true) === true)
        ));
        $groups = $this->repo()->getServiceGroups();
        $servicesByGroup = [];

        foreach ($services as $service) {
            $servicesByGroup[(int) ($service['group_id'] ?? 0)][] = $service;
        }

        $bundles = [];
        foreach ($groups as $group) {
            $groupId = (int) ($group['id'] ?? 0);
            if ($groupId <= 0 || empty($servicesByGroup[$groupId])) {
                continue;
            }

            $bundles[] = [
                'group' => $group,
                'services' => $servicesByGroup[$groupId],
            ];
        }

        $selectedGroupId = (int) ($_GET['group'] ?? ($bundles[0]['group']['id'] ?? 0));
        $selectedBundle = $bundles[0] ?? ['group' => ['id' => 0, 'name' => 'Layanan'], 'services' => []];

        foreach ($bundles as $bundle) {
            if ((int) ($bundle['group']['id'] ?? 0) === $selectedGroupId) {
                $selectedBundle = $bundle;
                break;
            }
        }

        $this->view('pages/public/booking-services', [
            'title' => 'Pilih Tanggal & Item',
            'page' => '/booking/services',
            'publicNav' => config('public_nav'),
            'bookingBusiness' => [
                'name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'location_name' => (string) ($primaryLocation['name'] ?? $settings['business_name'] ?? $business['name'] ?? 'Star Salon'),
                'hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'address' => (string) ($primaryLocation['address'] ?? $settings['address'] ?? $business['address'] ?? ''),
                'hotline' => (string) ($business['hotline'] ?? ''),
                'email' => (string) ($business['email'] ?? ''),
                'cover_image_url' => $this->resolveBookingCoverImage(),
            ],
            'serviceBundles' => $bundles,
            'selectedServiceBundle' => $selectedBundle,
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingTimeSelection(): void
    {
        verify_csrf();

        $date = (string) ($_POST['date'] ?? '');
        $groupId = (int) ($_POST['group_id'] ?? 0);
        $itemsRaw = (string) ($_POST['items'] ?? '[]');
        $items = json_decode($itemsRaw, true);

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->redirect('/booking/services');
        }

        if (!is_array($items) || $items === []) {
            $target = '/booking/services?date=' . rawurlencode($date);
            if ($groupId > 0) {
                $target .= '&group=' . $groupId;
            }

            $this->redirect($target);
        }

        $normalizedItems = array_values(array_filter(array_map(static function ($item): ?array {
            if (!is_array($item)) {
                return null;
            }

            return [
                'service_id' => (int) ($item['service_id'] ?? 0),
                'name' => (string) ($item['name'] ?? 'Layanan'),
                'price' => (float) ($item['price'] ?? 0),
                'duration' => (int) ($item['duration'] ?? 0),
                'qty' => max(1, (int) ($item['qty'] ?? 1)),
                'image' => (string) ($item['image'] ?? ''),
            ];
        }, $items)));

        $_SESSION['booking_time_selection'] = [
            'date' => $date,
            'group_id' => $groupId,
            'items' => $normalizedItems,
        ];

        $this->redirect('/booking/time');
    }

    public function bookingTime(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];

        $this->view('pages/public/booking-time', [
            'title' => 'Pilih Waktu',
            'page' => '/booking/time',
            'publicNav' => config('public_nav'),
            'bookingSelection' => [
                'date' => $date,
                'items' => $items,
                'primary_item' => $primaryItem,
                'selected_time' => (string) ($selection['selected_time'] ?? '03:00'),
                'total_qty' => array_reduce($items, static fn (int $sum, array $item): int => $sum + (int) ($item['qty'] ?? 1), 0),
                'total_duration' => array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingSummarySelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $selectedTime = trim((string) ($_POST['selected_time'] ?? ''));
        if (!preg_match('/^\d{2}:\d{2}$/', $selectedTime)) {
            $selectedTime = '03:00';
        }

        $selection['selected_time'] = $selectedTime;
        $_SESSION['booking_time_selection'] = $selection;

        $this->redirect('/booking/summary');
    }

    public function bookingSummary(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];
        $selectedTime = (string) ($selection['selected_time'] ?? '03:00');
        $totalDuration = array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0);
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $endTime = $selectedTime;
        $serviceIds = array_values(array_filter(array_map(static fn (array $item): int => (int) ($item['service_id'] ?? 0), $items)));
        $staff = array_values(array_filter($this->repo()->getStaff(), static function (array $staffMember) use ($serviceIds): bool {
            if (!($staffMember['booking_enabled'] ?? true)) {
                return false;
            }

            $staffServiceIds = array_map('intval', (array) ($staffMember['service_ids'] ?? []));
            if ($serviceIds === [] || $staffServiceIds === []) {
                return true;
            }

            return array_intersect($serviceIds, $staffServiceIds) !== [];
        }));

        if (preg_match('/^(\d{2}):(\d{2})$/', $selectedTime, $matches) === 1) {
            $minutes = (((int) $matches[1]) * 60) + (int) $matches[2] + max(0, $totalDuration);
            $hours = (int) floor(($minutes / 60) % 24);
            $mins = $minutes % 60;
            $endTime = sprintf('%02d:%02d', $hours, $mins);
        }

        $this->view('pages/public/booking-summary', [
            'title' => 'Ringkasan',
            'page' => '/booking/summary',
            'publicNav' => config('public_nav'),
            'bookingSummary' => [
                'date' => $date,
                'items' => $items,
                'primary_item' => $primaryItem,
                'selected_time' => $selectedTime,
                'end_time' => $endTime,
                'total_duration' => $totalDuration,
                'total_price' => $totalPrice,
                'staff_name' => (string) (($staff[0]['name'] ?? 'Staff')),
                'available_staff' => array_map(static function (array $staffMember): array {
                    return [
                        'id' => (int) ($staffMember['id'] ?? 0),
                        'name' => (string) ($staffMember['name'] ?? 'Staff'),
                        'role' => (string) ($staffMember['public_title'] ?? $staffMember['role'] ?? ''),
                        'photo_data_url' => (string) ($staffMember['photo_data_url'] ?? ''),
                    ];
                }, $staff),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingConfirmationSelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $selectedStaffId = max(0, (int) ($_POST['selected_staff_id'] ?? 0));
        $selectedTime = trim((string) ($_POST['selected_time'] ?? ''));
        if (preg_match('/^\d{2}:\d{2}$/', $selectedTime) !== 1) {
            $selectedTime = (string) ($selection['selected_time'] ?? '03:00');
        }
        $selection['selected_staff_id'] = $selectedStaffId;
        $selection['selected_time'] = $selectedTime;
        $_SESSION['booking_time_selection'] = $selection;

        $this->redirect('/booking/confirmation');
    }

    public function bookingConfirmation(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $date = new \DateTimeImmutable((string) $selection['date']);
        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $primaryItem = $items[0] ?? [
            'service_id' => 0,
            'name' => 'Layanan',
            'duration' => 0,
            'price' => 0,
            'qty' => 1,
            'image' => '',
        ];
        $selectedTime = (string) ($selection['selected_time'] ?? '03:00');
        $selectedStaffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $totalDuration = array_reduce($items, static fn (int $sum, array $item): int => $sum + ((int) ($item['duration'] ?? 0) * (int) ($item['qty'] ?? 1)), 0);
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $selectedStaff = $selectedStaffId > 0 ? $this->repo()->findStaff($selectedStaffId) : null;
        $customerUser = $this->auth()->user('customer');
        $customer = ($customerUser !== null && !empty($customerUser['customer_id']))
            ? $this->repo()->findCustomer((int) $customerUser['customer_id'])
            : null;
        $settings = $this->repo()->getSettings();
        $business = config('business');
        $locations = $this->repo()->getLocations();
        $primaryLocation = $locations[0] ?? [];
        $expandedItems = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $expandedItems[] = [
                    'service_id' => (int) ($item['service_id'] ?? 0),
                    'duration' => (int) ($item['duration'] ?? 0),
                    'price' => (float) ($item['price'] ?? 0),
                ];
            }
        }

        $this->view('pages/public/booking-confirmation', [
            'title' => 'Konfirmasi Pemesanan',
            'page' => '/booking/confirmation',
            'publicNav' => config('public_nav'),
            'bookingConfirmation' => [
                'date' => $date,
                'selected_time' => $selectedTime,
                'total_duration' => $totalDuration,
                'total_price' => $totalPrice,
                'items' => $items,
                'expanded_items' => $expandedItems,
                'primary_item' => $primaryItem,
                'selected_staff' => $selectedStaff,
                'customer' => $customer,
                'is_logged_in' => $customerUser !== null,
                'location_name' => (string) ($primaryLocation['name'] ?? $settings['business_name'] ?? $business['name'] ?? 'Star Salon'),
                'business_name' => (string) ($settings['business_name'] ?? $business['name'] ?? 'StarStyle'),
                'business_hours' => (string) ($settings['hours'] ?? $business['hours'] ?? '09:00 - 20:00'),
                'business_address' => (string) ($primaryLocation['address'] ?? $settings['address'] ?? $business['address'] ?? ''),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingPaymentSelection(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }

        $customerUser = $this->auth()->user('customer');
        $payload = [
            'customer_name' => trim((string) ($_POST['customer_name'] ?? '')),
            'customer_email' => trim((string) ($_POST['customer_email'] ?? '')),
            'customer_phone' => trim((string) ($_POST['customer_phone'] ?? '')),
            'notes' => trim((string) ($_POST['notes'] ?? '')),
        ];

        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? '');
                $payload['customer_email'] = (string) ($customer['email'] ?? '');
                $payload['customer_phone'] = (string) ($customer['phone'] ?? '');
            }
        }

        if ($payload['customer_name'] === '' || $payload['customer_phone'] === '') {
            remember_old_input($_POST);
            flash('error', 'Mohon lengkapi nama lengkap dan nomor telepon.');
            $this->redirect('/booking/confirmation');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $staffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $serviceIds = [];
        $serviceDurations = [];
        $servicePrices = [];
        $serviceStaffIds = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $serviceIds[] = (int) ($item['service_id'] ?? 0);
                $serviceDurations[] = (int) ($item['duration'] ?? 0);
                $servicePrices[] = (float) ($item['price'] ?? 0);
                $serviceStaffIds[] = $staffId;
            }
        }

        $bookingPayload = [
            'booking_reference' => (string) ($_SESSION['booking_checkout_payload']['booking_reference'] ?? ''),
            'date' => (string) ($selection['date'] ?? ''),
            'time' => (string) ($selection['selected_time'] ?? ''),
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_durations' => $serviceDurations,
            'service_prices' => $servicePrices,
            'service_staff_ids' => $serviceStaffIds,
            'customer_name' => $payload['customer_name'],
            'customer_email' => $payload['customer_email'],
            'customer_phone' => $payload['customer_phone'],
            'notes' => $payload['notes'],
            'payment_review_status' => 'waiting_admin',
        ];

        $result = $this->repo()->createBooking($bookingPayload, 'customer');
        if (!$result['success']) {
            remember_old_input($_POST);
            flash('error', $result['message']);
            $this->redirect('/booking/confirmation');
        }

        if (!empty($result['booking']['reference'])) {
            $payload['booking_reference'] = (string) $result['booking']['reference'];
        }

        $_SESSION['booking_checkout_payload'] = $payload;
        clear_old_input();

        $this->redirect('/booking/payment');
    }

    public function bookingPayment(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);

        $this->view('pages/public/booking-payment', [
            'title' => 'Pembayaran',
            'page' => '/booking/payment',
            'publicNav' => config('public_nav'),
            'bookingPayment' => [
                'total_price' => $totalPrice,
                'customer_name' => (string) ($checkout['customer_name'] ?? ''),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function storeBookingPaymentQris(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }

        $paymentMethod = strtoupper(trim((string) ($_POST['payment_method'] ?? '')));
        if ($paymentMethod !== 'QRIS') {
            flash('error', 'Metode pembayaran untuk booking hanya QRIS.');
            $this->redirect('/booking/payment');
        }

        $_SESSION['booking_checkout_payload']['payment_method'] = 'QRIS';
        $this->redirect('/booking/payment/qris');
    }

    public function bookingPaymentQris(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }
        if (($checkout['payment_method'] ?? '') !== 'QRIS') {
            $this->redirect('/booking/payment');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);
        $expiresAt = (new \DateTimeImmutable('now'))->modify('+15 minutes');
        $detailItems = array_values(array_filter(array_map(static function (array $item): ?array {
            $name = trim((string) ($item['name'] ?? ''));
            if ($name === '') {
                return null;
            }

            return [
                'label' => max(1, (int) ($item['qty'] ?? 1)) . ' ' . $name,
                'total' => (float) ($item['price'] ?? 0) * max(1, (int) ($item['qty'] ?? 1)),
            ];
        }, $items)));

        $this->view('pages/public/booking-payment-qris', [
            'title' => 'Pembayaran QRIS',
            'page' => '/booking/payment/qris',
            'publicNav' => config('public_nav'),
            'bookingPaymentQris' => [
                'total_price' => $totalPrice,
                'expires_at' => $expiresAt,
                'customer_name' => (string) ($checkout['customer_name'] ?? ''),
                'qris_image_url' => $this->resolveBookingQrisImage(),
                'detail_items' => $detailItems,
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function bookingPaymentProof(): void
    {
        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }
        if (($checkout['payment_method'] ?? '') !== 'QRIS') {
            $this->redirect('/booking/payment');
        }

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $totalPrice = array_reduce($items, static fn (float $sum, array $item): float => $sum + ((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)), 0.0);

        $this->view('pages/public/booking-payment-proof', [
            'title' => 'Upload Bukti Pembayaran',
            'page' => '/booking/payment/proof',
            'publicNav' => config('public_nav'),
            'bookingPaymentProof' => [
                'total_price' => $totalPrice,
                'customer_name' => (string) ($checkout['customer_name'] ?? ''),
            ],
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function bookingPaymentPending(): void
    {
        $this->view('pages/public/booking-payment-pending', [
            'title' => 'Menunggu Konfirmasi Admin',
            'page' => '/booking/payment/pending',
            'publicNav' => config('public_nav'),
            'bookingCompletionEmail' => flash('booking_completion_email'),
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function completeBookingPayment(): void
    {
        verify_csrf();

        $selection = $_SESSION['booking_time_selection'] ?? null;
        $checkout = $_SESSION['booking_checkout_payload'] ?? null;
        if (!is_array($selection) || empty($selection['items']) || empty($selection['date'])) {
            $this->redirect('/booking/services');
        }
        if (!is_array($checkout) || empty($checkout['customer_name']) || empty($checkout['customer_phone'])) {
            $this->redirect('/booking/confirmation');
        }

        $paymentMethod = strtoupper(trim((string) ($_POST['payment_method'] ?? '')));
        if ($paymentMethod !== 'QRIS') {
            flash('error', 'Metode pembayaran untuk booking hanya QRIS.');
            $this->redirect('/booking/payment');
        }

        if (!isset($_FILES['payment_proof']) || !is_array($_FILES['payment_proof'])) {
            flash('error', 'Mohon upload bukti pembayaran terlebih dahulu.');
            $this->redirect('/booking/payment/proof');
        }

        $proofFile = $_FILES['payment_proof'];
        if ((int) ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            flash('error', 'Upload bukti pembayaran gagal. Coba pilih file lagi.');
            $this->redirect('/booking/payment/proof');
        }

        $extension = strtolower(pathinfo((string) ($proofFile['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            flash('error', 'Format bukti pembayaran harus JPG, PNG, atau WEBP.');
            $this->redirect('/booking/payment/proof');
        }

        $proofDirectory = dirname(__DIR__, 2) . '/storage/cache/payment-proofs';
        if (!is_dir($proofDirectory) && !mkdir($proofDirectory, 0777, true) && !is_dir($proofDirectory)) {
            flash('error', 'Folder bukti pembayaran tidak bisa dibuat.');
            $this->redirect('/booking/payment/proof');
        }

        $proofFilename = 'payment-proof-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $proofPath = $proofDirectory . '/' . $proofFilename;
        if (!move_uploaded_file((string) ($proofFile['tmp_name'] ?? ''), $proofPath)) {
            flash('error', 'Bukti pembayaran gagal disimpan.');
            $this->redirect('/booking/payment/proof');
        }

        $_SESSION['booking_checkout_payload']['payment_proof_path'] = $proofPath;

        $items = array_values(array_filter((array) ($selection['items'] ?? []), static fn ($item): bool => is_array($item)));
        $staffId = max(0, (int) ($selection['selected_staff_id'] ?? 0));
        $serviceIds = [];
        $serviceDurations = [];
        $servicePrices = [];
        $serviceStaffIds = [];

        foreach ($items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            for ($index = 0; $index < $qty; $index += 1) {
                $serviceIds[] = (int) ($item['service_id'] ?? 0);
                $serviceDurations[] = (int) ($item['duration'] ?? 0);
                $servicePrices[] = (float) ($item['price'] ?? 0);
                $serviceStaffIds[] = $staffId;
            }
        }

        $payload = [
            'date' => (string) ($selection['date'] ?? ''),
            'time' => (string) ($selection['selected_time'] ?? ''),
            'staff_id' => $staffId,
            'service_ids' => $serviceIds,
            'service_durations' => $serviceDurations,
            'service_prices' => $servicePrices,
            'service_staff_ids' => $serviceStaffIds,
            'customer_name' => (string) ($checkout['customer_name'] ?? ''),
            'customer_email' => (string) ($checkout['customer_email'] ?? ''),
            'customer_phone' => (string) ($checkout['customer_phone'] ?? ''),
            'notes' => (string) ($checkout['notes'] ?? ''),
            'payment_method' => 'QRIS',
            'payment_proof_path' => (string) ($_SESSION['booking_checkout_payload']['payment_proof_path'] ?? ''),
            'payment_review_status' => 'waiting_admin',
            'booking_reference' => (string) ($checkout['booking_reference'] ?? ''),
        ];

        $customerUser = $this->auth()->user('customer');
        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? $payload['customer_name']);
                $payload['customer_phone'] = (string) ($customer['phone'] ?? $payload['customer_phone']);
                $payload['customer_email'] = (string) ($customer['email'] ?? $payload['customer_email']);
            }
        }

        $result = $this->repo()->createBooking($payload, 'customer');
        if ($result['success']) {
            clear_old_input();
            flash('booking_completion_email', (string) ($payload['customer_email'] ?? ''));
            unset($_SESSION['booking_time_selection'], $_SESSION['booking_checkout_payload']);
        }
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect($result['success'] ? '/booking/payment/pending' : '/booking/payment/proof');
    }

    public function bookingNext(): void
    {
        verify_csrf();

        $next = (string) ($_POST['next_target'] ?? 'services');
        $wantsDefitLogin = !empty($_POST['is_defit']);

        if ($next === 'contact') {
            $this->redirect('/booking?tab=contact');
        }

        if ($wantsDefitLogin) {
            $this->redirect('/customer/login?redirect=' . rawurlencode('/booking/services'));
        }

        $this->redirect('/booking/services');
    }

    public function createBooking(): void
    {
        verify_csrf();
        $payload = $_POST;
        $customerUser = $this->auth()->user('customer');

        if ($customerUser !== null && !empty($customerUser['customer_id'])) {
            $customer = $this->repo()->findCustomer((int) $customerUser['customer_id']);
            if ($customer !== null) {
                $payload['customer_name'] = (string) ($customer['name'] ?? '');
                $payload['customer_phone'] = (string) ($customer['phone'] ?? '');
            }
        }

        $result = $this->repo()->createBooking($payload, 'customer');
        if ($result['success']) {
            clear_old_input();
            unset($_SESSION['booking_time_selection']);
        } else {
            remember_old_input($_POST);
        }
        flash($result['success'] ? 'success' : 'error', $result['message']);
        $this->redirect($result['success'] ? '/booking' : '/booking/confirmation');
    }

    public function customerLogin(): void
    {
        $this->view('pages/public/customer-login', [
            'title' => 'Login Pelanggan',
            'page' => '/customer/login',
            'publicNav' => config('public_nav'),
            'redirectAfterLogin' => $this->sanitizeCustomerRedirect((string) ($_GET['redirect'] ?? '')),
            'success' => flash('success'),
            'error' => flash('error'),
        ], 'public');
    }

    public function authenticateCustomer(): void
    {
        verify_csrf();
        $ok = $this->auth()->attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''), 'customer');
        $redirectTarget = $this->sanitizeCustomerRedirect((string) ($_POST['redirect'] ?? ''));

        if (!$ok) {
            flash('error', 'Akun pelanggan tidak ditemukan.');
            $target = '/customer/login';
            if ($redirectTarget !== '') {
                $target .= '?redirect=' . rawurlencode($redirectTarget);
            }

            $this->redirect($target);
        }

        flash('success', 'Login pelanggan berhasil.');
        $this->redirect($redirectTarget !== '' ? $redirectTarget : '/customer/account');
    }

    public function customerAccount(): void
    {
        $user = $this->customerUser();
        $account = $this->repo()->customerAccount((int) $user['customer_id']);

        $this->view('pages/public/customer-account', [
            'title' => 'Akun Pelanggan',
            'page' => '/customer/account',
            'publicNav' => config('public_nav'),
            'success' => flash('success'),
            'error' => flash('error'),
        ] + $account, 'public');
    }

    public function customerLogout(): void
    {
        $this->auth()->logout('customer');
        flash('success', 'Sampai jumpa lagi.');
        $this->redirect('/');
    }

    private function sanitizeCustomerRedirect(string $path): string
    {
        if ($path === '' || !str_starts_with($path, '/')) {
            return '';
        }

        return preg_match('/[\r\n]/', $path) === 1 ? '' : $path;
    }

    private function resolveBookingCoverImage(): string
    {
        $path = dirname(__DIR__, 2) . '/img/Salon.jpeg';
        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function resolveBookingQrisImage(): string
    {
        $path = dirname(__DIR__, 2) . '/img/Qris.jpeg';
        if (!is_file($path)) {
            return '';
        }

        $mime = mime_content_type($path) ?: 'image/jpeg';
        $content = file_get_contents($path);
        if ($content === false) {
            return '';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }
}
