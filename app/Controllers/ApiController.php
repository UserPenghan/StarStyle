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

    public function customerSearch(): void
    {
        $this->authorize('customers.view');
        $this->json(['customers' => $this->repo()->searchCustomers((string) ($_GET['q'] ?? ''))]);
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
}
