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
