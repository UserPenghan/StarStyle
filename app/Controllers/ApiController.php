<?php

declare(strict_types=1);

namespace App\Controllers;

final class ApiController extends BaseController
{
    public function calendarEvents(): void
    {
        $this->authorize('calendar.view');
        $this->json($this->repo()->calendar((string) ($_GET['date'] ?? date('Y-m-d'))));
    }

    public function availability(): void
    {
        $staffId = (int) ($_GET['staff_id'] ?? 0);
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        $serviceIds = array_map('intval', array_filter(explode(',', (string) ($_GET['service_ids'] ?? ''))));
        $this->json(['slots' => $this->repo()->availability($serviceIds, $staffId, $date)]);
    }

    public function bookingStatusUpdate(): void
    {
        verify_csrf();
        $this->authorize('sales.view');

        try {
            $booking = $this->repo()->updateBookingStatus(
                (string) ($_POST['reference'] ?? ''),
                (string) ($_POST['status'] ?? ''),
                (string) ($this->internalUser()['name'] ?? 'Admin'),
                (string) ($_POST['reason'] ?? '')
            );
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }

    public function bookingProductsUpdate(): void
    {
        verify_csrf();
        $this->authorize('sales.view');

        $products = json_decode((string) ($_POST['products_json'] ?? '[]'), true);
        $products = is_array($products) ? $products : [];

        try {
            $booking = $this->repo()->updateBookingProducts(
                (string) ($_POST['reference'] ?? ''),
                $products,
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }

    public function bookingPaymentReviewUpdate(): void
    {
        verify_csrf();
        $this->authorize('sales.view');

        try {
            $booking = $this->repo()->updateBookingPaymentReviewStatus(
                (string) ($_POST['reference'] ?? ''),
                (string) ($_POST['payment_review_status'] ?? ''),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }

    public function customerSearch(): void
    {
        $this->authorize('customers.view');
        $this->json(['customers' => $this->repo()->searchCustomers((string) ($_GET['q'] ?? ''))]);
    }

    public function customerDetail(): void
    {
        $this->authorize('customers.view');
        $customerId = (int) ($_GET['id'] ?? 0);
        $payload = $this->repo()->customerDetail($customerId);

        if ($payload === null) {
            $this->json(['message' => 'Customer tidak ditemukan.'], 404);
        }

        $this->json($payload);
    }

    public function customerSave(): void
    {
        verify_csrf();
        $this->authorize('customers.view');

        try {
            $customer = $this->repo()->saveCustomer(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'gender' => (string) ($_POST['gender'] ?? ''),
                    'phone' => (string) ($_POST['phone'] ?? ''),
                    'email' => (string) ($_POST['email'] ?? ''),
                    'member_id' => (string) ($_POST['member_id'] ?? ''),
                    'loyalty_points' => (int) ($_POST['loyalty_points'] ?? 0),
                    'last_visit_at' => (string) ($_POST['last_visit_at'] ?? ''),
                    'birthdate' => (string) ($_POST['birthdate'] ?? ''),
                    'tags' => $_POST['tags'] ?? [],
                    'status' => (string) ($_POST['status'] ?? 'Aktif'),
                    'notes' => (string) ($_POST['notes'] ?? ''),
                    'address' => (string) ($_POST['address'] ?? ''),
                    'family_card_number' => (string) ($_POST['family_card_number'] ?? ''),
                    'passport_number' => (string) ($_POST['passport_number'] ?? ''),
                    'notify_via' => (string) ($_POST['notify_via'] ?? 'off'),
                    'marketing_opt_in' => (int) ($_POST['marketing_opt_in'] ?? 0) === 1,
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'customer' => $customer,
        ]);
    }

    public function customerDelete(): void
    {
        verify_csrf();
        $this->authorize('customers.view');

        try {
            $this->repo()->deleteCustomer(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json(['success' => true]);
    }

    public function customerImport(): void
    {
        verify_csrf();
        $this->authorize('customers.view');

        $file = $_FILES['file'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->json([
                'success' => false,
                'message' => 'File CSV tidak ditemukan atau gagal diunggah.',
            ], 422);
        }

        $handle = fopen((string) $file['tmp_name'], 'rb');
        if ($handle === false) {
            $this->json([
                'success' => false,
                'message' => 'File CSV tidak bisa dibaca.',
            ], 422);
        }

        $rows = [];
        $header = null;
        while (($data = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(
                    static fn (string $value): string => strtolower(trim(str_replace([' ', '.'], ['_', ''], $value))),
                    $data
                );
                continue;
            }

            $row = [];
            foreach ($header as $index => $column) {
                $row[$column] = trim((string) ($data[$index] ?? ''));
            }

            $rows[] = [
                'name' => (string) ($row['nama'] ?? $row['name'] ?? ''),
                'phone' => (string) ($row['notelpon'] ?? $row['phone'] ?? ''),
                'email' => (string) ($row['email'] ?? ''),
                'member_id' => (string) ($row['member_id'] ?? $row['memberid'] ?? ''),
                'loyalty_points' => (int) ($row['loyalty_point'] ?? $row['loyalty_points'] ?? 0),
                'last_visit_at' => (string) ($row['kunjungan_terakhir'] ?? $row['last_visit_at'] ?? $row['lastvisit'] ?? ''),
                'birthdate' => (string) ($row['tanggal_lahir'] ?? $row['birthdate'] ?? ''),
                'tags' => (string) ($row['tags'] ?? ''),
                'gender' => (string) ($row['jenis_kelamin'] ?? $row['gender'] ?? ''),
                'status' => (string) ($row['status'] ?? 'Aktif'),
                'notes' => (string) ($row['catatan'] ?? $row['notes'] ?? ''),
                'address' => (string) ($row['alamat'] ?? $row['address'] ?? ''),
                'family_card_number' => (string) ($row['nomor_kartu_keluarga'] ?? $row['family_card_number'] ?? ''),
                'passport_number' => (string) ($row['paspor'] ?? $row['passport_number'] ?? ''),
                'notify_via' => (string) ($row['notify_via'] ?? 'off'),
                'marketing_opt_in' => in_array(strtolower((string) ($row['marketing_opt_in'] ?? '0')), ['1', 'true', 'yes', 'ya'], true),
            ];
        }
        fclose($handle);

        try {
            $result = $this->repo()->importCustomers($rows, (string) ($this->internalUser()['name'] ?? 'Admin'));
        } catch (\Throwable $throwable) {
            $this->json([
                'success' => false,
                'message' => $throwable->getMessage(),
            ], 422);
        }

        $this->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    public function staffSave(): void
    {
        verify_csrf();
        $this->authorize('staff.view');

        try {
            $staff = $this->repo()->saveStaff(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'email' => (string) ($_POST['email'] ?? ''),
                    'phone' => (string) ($_POST['phone'] ?? ''),
                    'role_title' => (string) ($_POST['role_title'] ?? ''),
                    'gender' => (string) ($_POST['gender'] ?? ''),
                    'booking_enabled' => (int) ($_POST['booking_enabled'] ?? 0) === 1,
                    'agenda_color' => (string) ($_POST['agenda_color'] ?? '#8cc9ff'),
                    'started_working_on' => (string) ($_POST['started_working_on'] ?? ''),
                    'ended_working_on' => (string) ($_POST['ended_working_on'] ?? ''),
                    'public_title' => (string) ($_POST['public_title'] ?? ''),
                    'notes' => (string) ($_POST['notes'] ?? ''),
                    'instagram_handle' => (string) ($_POST['instagram_handle'] ?? ''),
                    'photo_data_url' => (string) ($_POST['photo_data_url'] ?? ''),
                    'location_id' => (int) ($_POST['location_id'] ?? 1),
                    'service_ids' => $_POST['service_ids'] ?? [],
                    'status' => (string) ($_POST['status'] ?? 'Aktif'),
                    'commission_type' => (string) ($_POST['commission_type'] ?? 'Persentase'),
                    'commission_value' => (float) ($_POST['commission_value'] ?? 0),
                    'commission_rules' => (string) ($_POST['commission_rules'] ?? '[]'),
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'staff' => $staff]);
    }

    public function staffDelete(): void
    {
        verify_csrf();
        $this->authorize('staff.view');

        try {
            $this->repo()->deleteStaff(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function staffShiftSave(): void
    {
        verify_csrf();
        $this->authorize('staff.view');
        $shifts = json_decode((string) ($_POST['shifts_json'] ?? '[]'), true);

        try {
            $this->repo()->saveStaffShifts(
                (int) ($_POST['staff_id'] ?? 0),
                (string) ($_POST['date'] ?? ''),
                (string) ($_POST['repeat'] ?? 'none'),
                (string) ($_POST['repeat_end'] ?? 'ongoing'),
                (string) ($_POST['repeat_end_date'] ?? '') ?: null,
                is_array($shifts) ? $shifts : [],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function staffShiftDelete(): void
    {
        verify_csrf();
        $this->authorize('staff.view');

        try {
            $this->repo()->deleteStaffShifts(
                (int) ($_POST['staff_id'] ?? 0),
                (string) ($_POST['date'] ?? ''),
                (string) ($_POST['repeat'] ?? 'none'),
                (string) ($_POST['repeat_end'] ?? 'ongoing'),
                (string) ($_POST['repeat_end_date'] ?? '') ?: null,
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function staffAttendanceSave(): void
    {
        verify_csrf();
        $this->authorize('staff.view');

        try {
            $this->repo()->saveStaffAttendance([
                'staff_id' => (int) ($_POST['staff_id'] ?? 0),
                'attendance_date' => (string) ($_POST['attendance_date'] ?? ''),
                'shift_start' => (string) ($_POST['shift_start'] ?? '08:00'),
                'shift_end' => (string) ($_POST['shift_end'] ?? '17:00'),
                'clock_in' => (string) ($_POST['clock_in'] ?? '08:00'),
                'clock_out' => (string) ($_POST['clock_out'] ?? '17:00'),
                'source' => (string) ($_POST['source'] ?? '-'),
                'status' => (string) ($_POST['status'] ?? 'Ontime'),
                'selfie_in_score' => (float) ($_POST['selfie_in_score'] ?? 0),
                'selfie_out_score' => (float) ($_POST['selfie_out_score'] ?? 0),
            ], (string) ($this->internalUser()['name'] ?? 'Admin'));
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function staffAttendanceProfileSave(): void
    {
        verify_csrf();
        $this->authorize('staff.view');

        try {
            $this->repo()->updateStaffAttendanceProfile(
                (int) ($_POST['staff_id'] ?? 0),
                (int) ($_POST['active'] ?? 0) === 1,
                (string) ($_POST['pose'] ?? 'Right Tilt'),
                (string) ($_POST['uploaded_pose'] ?? ''),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function serviceGroupSave(): void
    {
        verify_csrf();
        $this->authorize('services.view');

        try {
            $group = $this->repo()->saveServiceGroup(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'description' => (string) ($_POST['description'] ?? ''),
                    'color' => (string) ($_POST['color'] ?? '#76b6e8'),
                    'image_data_url' => (string) ($_POST['image_data_url'] ?? ''),
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'group' => $group]);
    }

    public function serviceGroupDelete(): void
    {
        verify_csrf();
        $this->authorize('services.view');

        try {
            $this->repo()->deleteServiceGroup(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function serviceSave(): void
    {
        verify_csrf();
        $this->authorize('services.view');
        $variants = json_decode((string) ($_POST['variants_json'] ?? '[]'), true);
        $staffIds = json_decode((string) ($_POST['staff_ids_json'] ?? '[]'), true);

        try {
            $service = $this->repo()->saveService(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'group_id' => (int) ($_POST['group_id'] ?? 0),
                    'name' => (string) ($_POST['name'] ?? ''),
                    'description' => (string) ($_POST['description'] ?? ''),
                    'audience' => json_decode((string) ($_POST['audience_json'] ?? '[]'), true),
                    'status' => (string) ($_POST['status'] ?? 'Aktif'),
                    'image_data_url' => (string) ($_POST['image_data_url'] ?? ''),
                    'online_bookable' => (int) ($_POST['online_bookable'] ?? 1) === 1,
                    'commission_enabled' => (int) ($_POST['commission_enabled'] ?? 0) === 1,
                    'at_customer_location' => (int) ($_POST['at_customer_location'] ?? 0) === 1,
                    'extra_time_type' => (string) ($_POST['extra_time_type'] ?? 'none'),
                    'extra_time_minutes' => (int) ($_POST['extra_time_minutes'] ?? 0),
                    'variants' => is_array($variants) ? $variants : [],
                    'staff_ids' => is_array($staffIds) ? $staffIds : [],
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'service' => $service]);
    }

    public function serviceDelete(): void
    {
        verify_csrf();
        $this->authorize('services.view');

        try {
            $this->repo()->deleteService(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function servicePackageSave(): void
    {
        verify_csrf();
        $this->authorize('services.view');
        $items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);

        try {
            $package = $this->repo()->saveServicePackage(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'group_id' => (int) ($_POST['group_id'] ?? 0),
                    'name' => (string) ($_POST['name'] ?? ''),
                    'description' => (string) ($_POST['description'] ?? ''),
                    'package_price' => (float) ($_POST['package_price'] ?? 0),
                    'pricing_mode' => (string) ($_POST['pricing_mode'] ?? 'service'),
                    'discount_value' => (float) ($_POST['discount_value'] ?? 0),
                    'audience' => (string) ($_POST['audience'] ?? 'all'),
                    'image_data_url' => (string) ($_POST['image_data_url'] ?? ''),
                    'items' => is_array($items) ? $items : [],
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'package' => $package]);
    }

    public function servicePackageDelete(): void
    {
        verify_csrf();
        $this->authorize('services.view');

        try {
            $this->repo()->deleteServicePackage(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function voucherSave(): void
    {
        verify_csrf();
        $this->authorize('vouchers.view');
        $serviceItems = json_decode((string) ($_POST['service_items_json'] ?? '[]'), true);

        try {
            $voucher = $this->repo()->saveVoucher(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'type' => (string) ($_POST['type'] ?? 'gift'),
                    'name' => (string) ($_POST['name'] ?? ''),
                    'value' => (float) ($_POST['value'] ?? 0),
                    'price_value' => (float) ($_POST['price_value'] ?? 0),
                    'expiry_label' => (string) ($_POST['expiry_label'] ?? 'After 1 Month'),
                    'expiry_value' => (string) ($_POST['expiry_value'] ?? ''),
                    'location' => (string) ($_POST['location'] ?? 'Semua Lokasi'),
                    'message' => (string) ($_POST['message'] ?? 'Thank you!'),
                    'active' => (int) ($_POST['active'] ?? 1) === 1,
                    'combine_quantity' => (int) ($_POST['combine_quantity'] ?? 0) === 1,
                    'max_quantity' => (int) ($_POST['max_quantity'] ?? 1),
                    'service_items' => is_array($serviceItems) ? $serviceItems : [],
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'voucher' => $voucher]);
    }

    public function voucherDelete(): void
    {
        verify_csrf();
        $this->authorize('vouchers.view');

        try {
            $this->repo()->deleteVoucher(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function voucherDiscountSave(): void
    {
        verify_csrf();
        $this->authorize('vouchers.view');
        $scopes = json_decode((string) ($_POST['scopes_json'] ?? '[]'), true);

        try {
            $discount = $this->repo()->saveVoucherDiscount(
                ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
                [
                    'name' => (string) ($_POST['name'] ?? ''),
                    'mode' => (string) ($_POST['mode'] ?? 'amount'),
                    'amount_value' => (float) ($_POST['amount_value'] ?? 0),
                    'max_discount_value' => (float) ($_POST['max_discount_value'] ?? 0),
                    'scopes' => is_array($scopes) ? $scopes : [],
                ],
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true, 'discount' => $discount]);
    }

    public function voucherDiscountDelete(): void
    {
        verify_csrf();
        $this->authorize('vouchers.view');

        try {
            $this->repo()->deleteVoucherDiscount(
                (int) ($_POST['id'] ?? 0),
                (string) ($this->internalUser()['name'] ?? 'Admin')
            );
        } catch (\Throwable $throwable) {
            $this->json(['success' => false, 'message' => $throwable->getMessage()], 422);
        }

        $this->json(['success' => true]);
    }

    public function staffServices(): void
    {
        $this->json(['services' => $this->repo()->servicesByStaff((int) ($_GET['staff_id'] ?? 0))]);
    }

    public function validateVoucher(): void
    {
        $this->json($this->repo()->validateVoucher((string) ($_GET['code'] ?? '')));
    }

    public function dashboardKpis(): void
    {
        $this->authorize('dashboard.view');
        $this->json($this->repo()->dashboard((string) ($_GET['range'] ?? '7d')));
    }

    public function posCalculate(): void
    {
        $items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);
        $this->json($this->repo()->calculateCart(is_array($items) ? $items : [], (string) ($_POST['voucher_code'] ?? '')));
    }

    public function staffPermissions(): void
    {
        $this->authorize('settings.view');
        $staffId = (int) ($_GET['staff_id'] ?? 0);
        $payload = $this->repo()->settingsPayload();

        foreach ($payload['staff'] as $staffMember) {
            if ($staffMember['id'] === $staffId) {
                $this->json(['permissions' => $staffMember['permissions']]);
            }
        }

        $this->json(['permissions' => []], 404);
    }

    public function inventoryMasterSave(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $row = $this->repo()->saveInventoryMasterItem(
            (string) ($_POST['type'] ?? ''),
            ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
            (string) ($_POST['name'] ?? '')
        );

        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryMasterDelete(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $this->repo()->deleteInventoryMasterItem((string) ($_POST['type'] ?? ''), (int) ($_POST['id'] ?? 0));
        $this->json(['success' => true]);
    }

    public function inventorySupplierSave(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $row = $this->repo()->saveInventorySupplier(
            ($id = (int) ($_POST['id'] ?? 0)) > 0 ? $id : null,
            [
                'name' => (string) ($_POST['name'] ?? ''),
                'description' => (string) ($_POST['description'] ?? ''),
                'contact' => (string) ($_POST['contact'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'phone' => (string) ($_POST['phone'] ?? ''),
                'website' => (string) ($_POST['website'] ?? ''),
                'address' => (string) ($_POST['address'] ?? ''),
                'city' => (string) ($_POST['city'] ?? ''),
                'country' => (string) ($_POST['country'] ?? ''),
                'postal' => (string) ($_POST['postal'] ?? ''),
            ]
        );

        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventorySupplierDelete(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $this->repo()->deleteInventorySupplier((int) ($_POST['id'] ?? 0));
        $this->json(['success' => true]);
    }

    public function inventoryProductSave(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $product = $this->repo()->saveInventoryProduct((int) ($_POST['id'] ?? 0), [
            'name' => (string) ($_POST['name'] ?? ''),
            'category' => (string) ($_POST['category'] ?? ''),
            'brand' => (string) ($_POST['brand'] ?? ''),
            'price' => (float) ($_POST['price'] ?? 0),
            'status' => (string) ($_POST['status'] ?? 'Aktif'),
        ]);

        $this->json(['success' => true, 'product' => $product]);
    }

    public function inventoryProductHistory(): void
    {
        $this->authorize('inventory.view');
        $this->json([
            'success' => true,
            'rows' => $this->repo()->getInventoryProductHistory((int) ($_GET['id'] ?? 0)),
        ]);
    }

    public function inventoryProductAdjustStock(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $actor = $this->internalUser();
        $payload = $this->repo()->adjustInventoryProductStock(
            (int) ($_POST['product_id'] ?? 0),
            (string) ($_POST['mode'] ?? 'increase'),
            (int) ($_POST['quantity'] ?? 1),
            (float) ($_POST['supply_price'] ?? 0),
            (string) ($_POST['reason'] ?? ''),
            (string) ($_POST['note'] ?? ''),
            (string) ($actor['name'] ?? 'Staff')
        );

        $this->json(['success' => true] + $payload);
    }

    public function inventoryPurchaseCreate(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);
        $row = $this->repo()->createInventoryPurchaseOrder([
            'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
            'location_id' => (int) ($_POST['location_id'] ?? 0),
            'type' => (string) ($_POST['type'] ?? 'Order'),
            'note' => (string) ($_POST['note'] ?? ''),
            'items' => is_array($items) ? $items : [],
        ]);

        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryPurchaseReceive(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);
        $row = $this->repo()->receiveInventoryPurchaseOrder((int) ($_POST['id'] ?? 0), is_array($items) ? $items : []);
        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryPurchaseCancel(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $row = $this->repo()->cancelInventoryPurchaseOrder((int) ($_POST['id'] ?? 0));
        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryOpnameSave(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $actor = $this->internalUser();
        $items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);
        $row = $this->repo()->saveInventoryOpname([
            'id' => (int) ($_POST['id'] ?? 0),
            'name' => (string) ($_POST['name'] ?? ''),
            'note' => (string) ($_POST['note'] ?? ''),
            'status' => (string) ($_POST['status'] ?? 'Meninjau'),
            'location_id' => (int) ($_POST['location_id'] ?? 0),
            'started_at' => (string) ($_POST['started_at'] ?? date('Y-m-d H:i:s')),
            'ended_at' => (string) ($_POST['ended_at'] ?? ''),
            'started_by' => (string) ($actor['name'] ?? 'Staff'),
            'cancelled_by' => (string) ($_POST['cancelled_by'] ?? ''),
            'cancelled_note' => (string) ($_POST['cancelled_note'] ?? ''),
            'items' => is_array($items) ? $items : [],
        ]);

        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryOpnameRecount(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $row = $this->repo()->recountInventoryOpname((int) ($_POST['id'] ?? 0));
        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryOpnameCancel(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $actor = $this->internalUser();
        $row = $this->repo()->cancelInventoryOpname(
            (int) ($_POST['id'] ?? 0),
            (string) ($_POST['note'] ?? ''),
            (string) ($actor['name'] ?? 'Staff')
        );
        $this->json(['success' => true, 'row' => $row]);
    }

    public function inventoryOpnameComplete(): void
    {
        verify_csrf();
        $this->authorize('inventory.view');
        $row = $this->repo()->completeInventoryOpname((int) ($_POST['id'] ?? 0));
        $this->json(['success' => true, 'row' => $row]);
    }
}
