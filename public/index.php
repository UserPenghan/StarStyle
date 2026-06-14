<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;
use App\Core\Router;

$router = new Router();

$router->get('/', [PublicController::class, 'home']);
$router->get('/services-catalog', [PublicController::class, 'services']);
$router->get('/booking', [PublicController::class, 'booking']);
$router->get('/booking/services', [PublicController::class, 'bookingServices']);
$router->get('/booking/time', [PublicController::class, 'bookingTime']);
$router->post('/booking/time', [PublicController::class, 'storeBookingTimeSelection']);
$router->get('/booking/summary', [PublicController::class, 'bookingSummary']);
$router->post('/booking/summary', [PublicController::class, 'storeBookingSummarySelection']);
$router->get('/booking/confirmation', [PublicController::class, 'bookingConfirmation']);
$router->post('/booking/confirmation-selection', [PublicController::class, 'storeBookingConfirmationSelection']);
$router->get('/booking/payment', [PublicController::class, 'bookingPayment']);
$router->post('/booking/payment', [PublicController::class, 'storeBookingPaymentSelection']);
$router->get('/booking/payment/qris', [PublicController::class, 'bookingPaymentQris']);
$router->post('/booking/payment/qris', [PublicController::class, 'storeBookingPaymentQris']);
$router->get('/booking/payment/proof', [PublicController::class, 'bookingPaymentProof']);
$router->get('/booking/payment/pending', [PublicController::class, 'bookingPaymentPending']);
$router->post('/booking/payment/complete', [PublicController::class, 'completeBookingPayment']);
$router->post('/booking/next', [PublicController::class, 'bookingNext']);
$router->post('/booking', [PublicController::class, 'createBooking']);
$router->get('/customer/login', [PublicController::class, 'customerLogin']);
$router->post('/customer/login', [PublicController::class, 'authenticateCustomer']);
$router->get('/customer/account', [PublicController::class, 'customerAccount']);
$router->post('/customer/logout', [PublicController::class, 'customerLogout']);

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'authenticate']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [AdminController::class, 'dashboard']);
$router->get('/account', [AdminController::class, 'account']);
$router->get('/calendar', [AdminController::class, 'calendar']);
$router->post('/calendar/bookings', [AdminController::class, 'createInternalBooking']);
$router->post('/calendar/blocks', [AdminController::class, 'createBlock']);
$router->post('/calendar/blocks/update', [AdminController::class, 'updateBlock']);
$router->post('/calendar/blocks/delete', [AdminController::class, 'deleteBlock']);
$router->get('/sales', [AdminController::class, 'sales']);
$router->post('/sales/checkout', [AdminController::class, 'checkout']);
$router->get('/customers', [AdminController::class, 'customers']);
$router->get('/staff', [AdminController::class, 'staff']);
$router->get('/services', [AdminController::class, 'services']);
$router->get('/inventory', [AdminController::class, 'inventory']);
$router->get('/vouchers', [AdminController::class, 'vouchers']);
$router->get('/analytics', [AdminController::class, 'analytics']);
$router->get('/reviews', [AdminController::class, 'reviews']);
$router->get('/settings', [AdminController::class, 'settings']);
$router->post('/settings/business-profile', [AdminController::class, 'updateBusinessProfile']);
$router->post('/settings/staff-permissions', [AdminController::class, 'updateStaffPermissions']);

$router->get('/api/calendar/events', [ApiController::class, 'calendarEvents']);
$router->get('/api/bookings/availability', [ApiController::class, 'availability']);
$router->post('/api/bookings/status', [ApiController::class, 'bookingStatusUpdate']);
$router->post('/api/bookings/products', [ApiController::class, 'bookingProductsUpdate']);
$router->post('/api/bookings/payment-review', [ApiController::class, 'bookingPaymentReviewUpdate']);
$router->get('/api/customers/search', [ApiController::class, 'customerSearch']);
$router->get('/api/customers/detail', [ApiController::class, 'customerDetail']);
$router->post('/api/customers/save', [ApiController::class, 'customerSave']);
$router->post('/api/customers/delete', [ApiController::class, 'customerDelete']);
$router->post('/api/customers/import', [ApiController::class, 'customerImport']);
$router->post('/api/staff/save', [ApiController::class, 'staffSave']);
$router->post('/api/staff/delete', [ApiController::class, 'staffDelete']);
$router->post('/api/staff/shifts/save', [ApiController::class, 'staffShiftSave']);
$router->post('/api/staff/shifts/delete', [ApiController::class, 'staffShiftDelete']);
$router->post('/api/staff/attendance/save', [ApiController::class, 'staffAttendanceSave']);
$router->post('/api/staff/attendance/profile', [ApiController::class, 'staffAttendanceProfileSave']);
$router->post('/api/services/groups/save', [ApiController::class, 'serviceGroupSave']);
$router->post('/api/services/groups/delete', [ApiController::class, 'serviceGroupDelete']);
$router->post('/api/services/save', [ApiController::class, 'serviceSave']);
$router->post('/api/services/delete', [ApiController::class, 'serviceDelete']);
$router->post('/api/services/packages/save', [ApiController::class, 'servicePackageSave']);
$router->post('/api/services/packages/delete', [ApiController::class, 'servicePackageDelete']);
$router->post('/api/vouchers/save', [ApiController::class, 'voucherSave']);
$router->post('/api/vouchers/delete', [ApiController::class, 'voucherDelete']);
$router->post('/api/vouchers/discounts/save', [ApiController::class, 'voucherDiscountSave']);
$router->post('/api/vouchers/discounts/delete', [ApiController::class, 'voucherDiscountDelete']);
$router->get('/api/services/by-staff', [ApiController::class, 'staffServices']);
$router->get('/api/vouchers/validate', [ApiController::class, 'validateVoucher']);
$router->get('/api/dashboard/kpis', [ApiController::class, 'dashboardKpis']);
$router->post('/api/pos/calculate', [ApiController::class, 'posCalculate']);
$router->get('/api/settings/staff-permissions', [ApiController::class, 'staffPermissions']);
$router->post('/api/inventory/master/save', [ApiController::class, 'inventoryMasterSave']);
$router->post('/api/inventory/master/delete', [ApiController::class, 'inventoryMasterDelete']);
$router->post('/api/inventory/suppliers/save', [ApiController::class, 'inventorySupplierSave']);
$router->post('/api/inventory/suppliers/delete', [ApiController::class, 'inventorySupplierDelete']);
$router->post('/api/inventory/products/save', [ApiController::class, 'inventoryProductSave']);
$router->get('/api/inventory/products/history', [ApiController::class, 'inventoryProductHistory']);
$router->post('/api/inventory/products/adjust-stock', [ApiController::class, 'inventoryProductAdjustStock']);
$router->post('/api/inventory/purchases/create', [ApiController::class, 'inventoryPurchaseCreate']);
$router->post('/api/inventory/purchases/receive', [ApiController::class, 'inventoryPurchaseReceive']);
$router->post('/api/inventory/purchases/cancel', [ApiController::class, 'inventoryPurchaseCancel']);
$router->post('/api/inventory/opnames/save', [ApiController::class, 'inventoryOpnameSave']);
$router->post('/api/inventory/opnames/recount', [ApiController::class, 'inventoryOpnameRecount']);
$router->post('/api/inventory/opnames/cancel', [ApiController::class, 'inventoryOpnameCancel']);
$router->post('/api/inventory/opnames/complete', [ApiController::class, 'inventoryOpnameComplete']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
