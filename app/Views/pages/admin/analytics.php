<?php
$transactions = $transactions ?? [];
$bookings = $bookings ?? [];
$staffMembers = $staff ?? [];
$customersList = $customers ?? [];
$voucherList = $vouchers ?? [];
$classList = $classes ?? [];
$today = new DateTimeImmutable('today');
$rangeStart = $today->modify('-6 days');
$rangeLabel = sprintf('7 hari sebelumnya, %s - %s', $rangeStart->format('j M Y'), $today->format('j M Y'));
$staffChoices = array_values(array_filter(array_map(
    static fn (array $staffMember): string => trim((string) ($staffMember['name'] ?? '')),
    $staffMembers
)));

$completedBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'completed'));
$confirmedBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'confirmed'));
$pendingBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'pending'));
$cancelledBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'cancelled'));
$noShowBookings = count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'no_show'));
$onlineBookings = count(array_filter($bookings, fn (array $booking): bool => in_array($booking['channel'], ['Online', 'Portal Customer', 'Instagram', 'WhatsApp'], true)));
$paidTransactions = array_values(array_filter($transactions, fn (array $transaction): bool => $transaction['status'] === 'paid'));
$refundTransactions = array_values(array_filter($transactions, fn (array $transaction): bool => $transaction['status'] === 'refund'));
$salesByType = ['service' => 0.0, 'product' => 0.0, 'voucher' => 0.0, 'package' => 0.0];
$grossSales = 0.0;
$totalDiscounts = 0.0;
$salesByDay = [];
$paymentSummaryMap = [];
$salesTypeLabels = [
    'service' => 'Services',
    'product' => 'Products',
    'voucher' => 'Voucher',
    'package' => 'Packages',
];

foreach ($paidTransactions as $transaction) {
    $lineTotal = 0.0;
    foreach (($transaction['items'] ?? []) as $item) {
        $itemTotal = ((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0));
        $lineTotal += $itemTotal;
        $itemType = (string) ($item['type'] ?? 'service');
        if (!array_key_exists($itemType, $salesByType)) {
            $salesByType[$itemType] = 0.0;
        }
        $salesByType[$itemType] += $itemTotal;
    }

    $discountValue = (float) ($transaction['discount'] ?? 0);
    $grossSales += $lineTotal;
    $totalDiscounts += $discountValue;

    $dateKey = date('Y-m-d', strtotime((string) ($transaction['date'] ?? 'now')));
    if (!isset($salesByDay[$dateKey])) {
        $salesByDay[$dateKey] = [
            'date' => $dateKey,
            'transactions' => 0,
            'gross' => 0.0,
            'discount' => 0.0,
            'net' => 0.0,
        ];
    }
    $salesByDay[$dateKey]['transactions']++;
    $salesByDay[$dateKey]['gross'] += $lineTotal;
    $salesByDay[$dateKey]['discount'] += $discountValue;
    $salesByDay[$dateKey]['net'] += max(0, $lineTotal - $discountValue);

    $paymentMethod = trim((string) ($transaction['payment_method'] ?? 'Unknown'));
    if (!isset($paymentSummaryMap[$paymentMethod])) {
        $paymentSummaryMap[$paymentMethod] = [
            'label' => $paymentMethod,
            'transactions' => 0,
            'gross' => 0.0,
            'discount' => 0.0,
            'net' => 0.0,
        ];
    }
    $paymentSummaryMap[$paymentMethod]['transactions']++;
    $paymentSummaryMap[$paymentMethod]['gross'] += $lineTotal;
    $paymentSummaryMap[$paymentMethod]['discount'] += $discountValue;
    $paymentSummaryMap[$paymentMethod]['net'] += max(0, $lineTotal - $discountValue);
}

$refundTotal = array_reduce($refundTransactions, function (float $carry, array $transaction): float {
    $lineTotal = array_reduce(($transaction['items'] ?? []), fn (float $sum, array $item): float => $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))), 0.0);
    return $carry + max(0, $lineTotal - (float) ($transaction['discount'] ?? 0));
}, 0.0);

$salesTotal = max(0, $grossSales - $totalDiscounts - $refundTotal);
$averageSale = count($paidTransactions) > 0 ? $salesTotal / count($paidTransactions) : 0.0;
$occupiedHours = round(($confirmedBookings + $completedBookings) * 1.5, 1);
$blockedHours = round(count(array_filter($bookings, fn (array $booking): bool => $booking['status'] === 'cancelled')) * 0.5 + 10, 1);
$workingHours = max(24.0, count($staffMembers) * 6.0);
$unbookedHours = max(0, $workingHours - $occupiedHours);
$returningCustomers = count(array_filter($customersList, fn (array $customer): bool => ($customer['loyalty_points'] ?? 0) >= 100));
$newCustomers = max(0, count($customersList) - $returningCustomers);
ksort($salesByDay);
$salesByDay = array_values($salesByDay);
uasort($paymentSummaryMap, fn (array $left, array $right): int => $right['net'] <=> $left['net']);
$paymentSummary = array_values($paymentSummaryMap);
$salesTypeRows = [];
$itemSalesMap = [];
$staffSalesMap = [];
$channelSalesMap = [];
foreach ($salesTypeLabels as $typeKey => $label) {
    $typeTotal = (float) ($salesByType[$typeKey] ?? 0);
    if ($typeTotal <= 0) {
        continue;
    }
    $salesTypeRows[] = [
        'label' => $label,
        'transactions' => count(array_filter($paidTransactions, static function (array $transaction) use ($typeKey): bool {
            foreach (($transaction['items'] ?? []) as $item) {
                if (($item['type'] ?? 'service') === $typeKey) {
                    return true;
                }
            }
            return false;
        })),
        'gross' => $typeTotal,
        'share' => $grossSales > 0 ? round(($typeTotal / $grossSales) * 100) : 0,
    ];
}

foreach ($paidTransactions as $transaction) {
    $staffId = (int) ($transaction['staff_id'] ?? 0);
    $staffName = 'Tanpa Staff';
    foreach ($staffMembers as $staffMember) {
        if ((int) ($staffMember['id'] ?? 0) === $staffId) {
            $staffName = trim((string) ($staffMember['name'] ?? 'Tanpa Staff'));
            break;
        }
    }

    $transactionDate = date('Y-m-d', strtotime((string) ($transaction['date'] ?? 'now')));
    $transactionNet = 0.0;
    foreach (($transaction['items'] ?? []) as $item) {
        $itemType = (string) ($item['type'] ?? 'service');
        $itemName = trim((string) ($item['name'] ?? 'Tanpa nama'));
        $itemKey = $itemType . ':' . $itemName;
        $itemQty = (int) ($item['qty'] ?? 0);
        $itemPrice = (float) ($item['price'] ?? 0);
        $itemTotal = $itemQty * $itemPrice;
        if (!isset($itemSalesMap[$itemKey])) {
            $itemSalesMap[$itemKey] = [
                'type' => $salesTypeLabels[$itemType] ?? ucfirst($itemType),
                'name' => $itemName,
                'qty' => 0,
                'transactions' => 0,
                'gross' => 0.0,
            ];
        }
        $itemSalesMap[$itemKey]['qty'] += $itemQty;
        $itemSalesMap[$itemKey]['transactions']++;
        $itemSalesMap[$itemKey]['gross'] += $itemTotal;
        $transactionNet += $itemTotal;
    }

    $transactionNet = max(0, $transactionNet - (float) ($transaction['discount'] ?? 0));
    if (!isset($staffSalesMap[$staffName])) {
        $staffSalesMap[$staffName] = [
            'name' => $staffName,
            'transactions' => 0,
            'gross' => 0.0,
            'net' => 0.0,
        ];
    }
    $staffSalesMap[$staffName]['transactions']++;
    $staffSalesMap[$staffName]['gross'] += max(0, $transactionNet + (float) ($transaction['discount'] ?? 0));
    $staffSalesMap[$staffName]['net'] += $transactionNet;

    $channelLabel = 'Walk-in';
    foreach ($bookings as $booking) {
        if ((int) ($booking['customer_id'] ?? 0) === (int) ($transaction['customer_id'] ?? 0)
            && date('Y-m-d', strtotime((string) ($booking['date'] ?? 'now'))) === $transactionDate) {
            $channelLabel = trim((string) ($booking['channel'] ?? 'Walk-in'));
            break;
        }
    }
    if (!isset($channelSalesMap[$channelLabel])) {
        $channelSalesMap[$channelLabel] = [
            'label' => $channelLabel,
            'transactions' => 0,
            'net' => 0.0,
        ];
    }
    $channelSalesMap[$channelLabel]['transactions']++;
    $channelSalesMap[$channelLabel]['net'] += $transactionNet;
}
uasort($itemSalesMap, fn (array $left, array $right): int => $right['gross'] <=> $left['gross']);
$topItemSales = array_slice(array_values($itemSalesMap), 0, 8);
uasort($staffSalesMap, fn (array $left, array $right): int => $right['net'] <=> $left['net']);
$staffSalesRows = array_values($staffSalesMap);
uasort($channelSalesMap, fn (array $left, array $right): int => $right['net'] <=> $left['net']);
$channelSalesRows = array_values($channelSalesMap);

$discountedTransactionsCount = count(array_filter($paidTransactions, fn (array $transaction): bool => ((float) ($transaction['discount'] ?? 0)) > 0));
$bestSalesDay = ['date' => null, 'transactions' => 0, 'gross' => 0.0, 'discount' => 0.0, 'net' => 0.0];
foreach ($salesByDay as $salesDayRow) {
    if (($salesDayRow['net'] ?? 0) > ($bestSalesDay['net'] ?? 0)) {
        $bestSalesDay = $salesDayRow;
    }
}
$bestPaymentMethod = $paymentSummary[0] ?? null;

$financeReportItems = [
    ['key' => 'finance-summary', 'label' => 'Ringkasan keuangan'],
    ['key' => 'finance-payments', 'label' => 'Ringkasan pembayaran'],
    ['key' => 'finance-sales-type', 'label' => 'Penjualan per tipe'],
];

$salesReportItems = [
    ['key' => 'sales-daily', 'label' => 'Penjualan harian'],
    ['key' => 'sales-staff', 'label' => 'Penjualan per staf'],
    ['key' => 'sales-items', 'label' => 'Penjualan per item'],
];

$agendaReportItems = [
    ['key' => 'agenda-upcoming', 'label' => 'Agenda mendatang'],
    ['key' => 'agenda-cancellations', 'label' => 'Pembatalan'],
    ['key' => 'agenda-channels', 'label' => 'Channel booking'],
];

$staffReportItems = [
    ['key' => 'staff-performance', 'label' => 'Kinerja staf'],
    ['key' => 'staff-utilization', 'label' => 'Utilisasi staf'],
    ['key' => 'staff-commission', 'label' => 'Komisi staf'],
];

$customerReportItems = [
    ['key' => 'customers-summary', 'label' => 'Ringkasan pelanggan'],
    ['key' => 'customers-top', 'label' => 'Pelanggan teratas'],
    ['key' => 'customers-retention', 'label' => 'Retensi pelanggan'],
];

$loyaltyReportItems = [
    ['key' => 'loyalty-summary', 'label' => 'Ringkasan poin'],
    ['key' => 'loyalty-top', 'label' => 'Pelanggan poin tertinggi'],
    ['key' => 'loyalty-activity', 'label' => 'Aktivitas poin'],
];

$serviceNameMap = [];
$serviceDurationMap = [];
foreach (($services ?? []) as $service) {
    $serviceId = (int) ($service['id'] ?? 0);
    $serviceNameMap[$serviceId] = trim((string) ($service['name'] ?? 'Tanpa layanan'));
    $serviceDurationMap[$serviceId] = (int) ($service['duration'] ?? 0);
}

$upcomingBookings = array_values(array_filter($bookings, static fn (array $booking): bool => strtotime((string) ($booking['start_at'] ?? 'now')) >= strtotime('today')));
usort($upcomingBookings, static fn (array $left, array $right): int => strtotime((string) ($left['start_at'] ?? 'now')) <=> strtotime((string) ($right['start_at'] ?? 'now')));

$cancelledBookingsList = array_values(array_filter($bookings, static fn (array $booking): bool => in_array((string) ($booking['status'] ?? ''), ['cancelled', 'no_show'], true)));
$bookingChannelRowsMap = [];
foreach ($bookings as $booking) {
    $channel = trim((string) ($booking['channel'] ?? 'Walk-in'));
    if (!isset($bookingChannelRowsMap[$channel])) {
        $bookingChannelRowsMap[$channel] = [
            'label' => $channel,
            'count' => 0,
            'confirmed' => 0,
            'cancelled' => 0,
        ];
    }
    $bookingChannelRowsMap[$channel]['count']++;
    if (in_array((string) ($booking['status'] ?? ''), ['confirmed', 'completed'], true)) {
        $bookingChannelRowsMap[$channel]['confirmed']++;
    }
    if (in_array((string) ($booking['status'] ?? ''), ['cancelled', 'no_show'], true)) {
        $bookingChannelRowsMap[$channel]['cancelled']++;
    }
}
uasort($bookingChannelRowsMap, fn (array $left, array $right): int => $right['count'] <=> $left['count']);
$bookingChannelRows = array_values($bookingChannelRowsMap);

$staffAnalyticsMap = [];
foreach ($staffMembers as $staffMember) {
    $staffName = trim((string) ($staffMember['name'] ?? 'Tanpa Staff'));
    $staffAnalyticsMap[(int) ($staffMember['id'] ?? 0)] = [
        'name' => $staffName,
        'role' => trim((string) ($staffMember['role'] ?? '-')),
        'bookings' => 0,
        'booked_hours' => 0.0,
        'revenue' => 0.0,
        'commission' => 0.0,
        'rating' => (float) ($staffMember['rating'] ?? 0),
    ];
}
foreach ($bookings as $booking) {
    $staffId = (int) ($booking['staff_id'] ?? 0);
    if (!isset($staffAnalyticsMap[$staffId])) {
        continue;
    }
    $staffAnalyticsMap[$staffId]['bookings']++;
    $startAt = strtotime((string) ($booking['start_at'] ?? 'now'));
    $endAt = strtotime((string) ($booking['end_at'] ?? 'now'));
    $durationHours = max(0, ($endAt - $startAt) / 3600);
    $staffAnalyticsMap[$staffId]['booked_hours'] += $durationHours;
}
foreach ($transactions as $transaction) {
    if (($transaction['status'] ?? '') !== 'paid') {
        continue;
    }
    $staffId = (int) ($transaction['staff_id'] ?? 0);
    if (!isset($staffAnalyticsMap[$staffId])) {
        continue;
    }
    $lineTotal = array_reduce(($transaction['items'] ?? []), fn (float $sum, array $item): float => $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))), 0.0);
    $netTotal = max(0, $lineTotal - (float) ($transaction['discount'] ?? 0));
    $staffAnalyticsMap[$staffId]['revenue'] += $netTotal;
    $staffConfig = current(array_filter($staffMembers, static fn (array $staffMember): bool => (int) ($staffMember['id'] ?? 0) === $staffId));
    if (is_array($staffConfig)) {
        if (($staffConfig['commission_type'] ?? '') === 'Persentase') {
            $staffAnalyticsMap[$staffId]['commission'] += $netTotal * (((float) ($staffConfig['commission_value'] ?? 0)) / 100);
        } else {
            $staffAnalyticsMap[$staffId]['commission'] += (float) ($staffConfig['commission_value'] ?? 0);
        }
    }
}
$staffAnalyticsRows = array_values($staffAnalyticsMap);
usort($staffAnalyticsRows, fn (array $left, array $right): int => $right['revenue'] <=> $left['revenue']);

$customerAnalyticsMap = [];
foreach ($customersList as $customer) {
    $customerId = (int) ($customer['id'] ?? 0);
    $customerAnalyticsMap[$customerId] = [
        'name' => trim((string) ($customer['name'] ?? 'Tanpa Nama')),
        'member_id' => trim((string) ($customer['member_id'] ?? '-')),
        'loyalty_points' => (int) ($customer['loyalty_points'] ?? 0),
        'last_visit' => (string) ($customer['last_visit'] ?? ''),
        'visits' => 0,
        'spend' => 0.0,
    ];
}
foreach ($bookings as $booking) {
    $customerId = (int) ($booking['customer_id'] ?? 0);
    if (isset($customerAnalyticsMap[$customerId])) {
        $customerAnalyticsMap[$customerId]['visits']++;
    }
}
foreach ($transactions as $transaction) {
    if (($transaction['status'] ?? '') !== 'paid') {
        continue;
    }
    $customerId = (int) ($transaction['customer_id'] ?? 0);
    if (!isset($customerAnalyticsMap[$customerId])) {
        continue;
    }
    $lineTotal = array_reduce(($transaction['items'] ?? []), fn (float $sum, array $item): float => $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))), 0.0);
    $customerAnalyticsMap[$customerId]['spend'] += max(0, $lineTotal - (float) ($transaction['discount'] ?? 0));
}
$customerAnalyticsRows = array_values($customerAnalyticsMap);
usort($customerAnalyticsRows, fn (array $left, array $right): int => $right['spend'] <=> $left['spend']);
$topCustomerRows = array_slice($customerAnalyticsRows, 0, 6);
$returningCustomerRows = array_values(array_filter($customerAnalyticsRows, static fn (array $row): bool => ($row['visits'] ?? 0) > 1));
$newCustomerRows = array_values(array_filter($customerAnalyticsRows, static fn (array $row): bool => ($row['visits'] ?? 0) <= 1));
usort($customerAnalyticsRows, fn (array $left, array $right): int => $right['loyalty_points'] <=> $left['loyalty_points']);
$topLoyaltyRows = array_slice($customerAnalyticsRows, 0, 6);

$loyaltyRatio = 10000;
$loyaltyActivityRows = [];
foreach ($paidTransactions as $transaction) {
    $customerId = (int) ($transaction['customer_id'] ?? 0);
    $customerRow = $customerAnalyticsMap[$customerId] ?? null;
    if (!is_array($customerRow)) {
        continue;
    }
    $lineTotal = array_reduce(($transaction['items'] ?? []), fn (float $sum, array $item): float => $sum + (((float) ($item['qty'] ?? 0)) * ((float) ($item['price'] ?? 0))), 0.0);
    $netTotal = max(0, $lineTotal - (float) ($transaction['discount'] ?? 0));
    $earnedPoints = (int) floor($netTotal / $loyaltyRatio);
    $loyaltyActivityRows[] = [
        'date' => (string) ($transaction['date'] ?? ''),
        'customer' => $customerRow['name'],
        'reference' => (string) ($transaction['reference'] ?? '-'),
        'earned' => $earnedPoints,
        'net' => $netTotal,
    ];
}
usort($loyaltyActivityRows, static fn (array $left, array $right): int => strtotime((string) ($right['date'] ?? 'now')) <=> strtotime((string) ($left['date'] ?? 'now')));

$inventoryReportItems = [
    ['key' => 'inventory-summary', 'label' => 'Ringkasan inventori'],
    ['key' => 'inventory-low-stock', 'label' => 'Stok rendah'],
    ['key' => 'inventory-product-sales', 'label' => 'Penjualan produk'],
];

$voucherReportItems = [
    ['key' => 'voucher-summary', 'label' => 'Ringkasan voucher'],
    ['key' => 'voucher-active', 'label' => 'Voucher aktif'],
    ['key' => 'voucher-redemption', 'label' => 'Aktivitas penukaran'],
];

$productInventoryRows = array_map(static function (array $product): array {
    return [
        'name' => trim((string) ($product['name'] ?? 'Tanpa Produk')),
        'brand' => trim((string) ($product['brand'] ?? '-')),
        'category' => trim((string) ($product['category'] ?? '-')),
        'stock' => (int) ($product['stock'] ?? 0),
        'price' => (float) ($product['price'] ?? 0),
        'status' => trim((string) ($product['status'] ?? 'Aman')),
    ];
}, $products ?? []);

$lowStockRows = array_values(array_filter($productInventoryRows, static fn (array $row): bool => ($row['stock'] ?? 0) <= 10 || strtolower((string) ($row['status'] ?? '')) === 'rendah'));
usort($lowStockRows, static fn (array $left, array $right): int => $left['stock'] <=> $right['stock']);

$productSalesRows = array_values(array_filter($topItemSales, static fn (array $row): bool => strtolower((string) ($row['type'] ?? '')) === 'products'));
$inventoryValue = array_sum(array_map(static fn (array $row): float => ((float) ($row['stock'] ?? 0)) * ((float) ($row['price'] ?? 0)), $productInventoryRows));

$voucherRows = array_map(static function (array $voucher): array {
    $type = trim((string) ($voucher['type'] ?? 'gift'));
    $value = (float) ($voucher['value'] ?? 0);
    $usageLimit = max(0, (int) ($voucher['usage_limit'] ?? 0));
    $used = max(0, (int) ($voucher['used'] ?? 0));
    $remaining = max(0, $usageLimit - $used);
    $liabilityValue = $type === 'gift' ? ($remaining * $value) : $remaining;
    return [
        'name' => trim((string) ($voucher['name'] ?? 'Tanpa Voucher')),
        'code' => trim((string) ($voucher['code'] ?? '-')),
        'type' => $type,
        'value' => $value,
        'status' => trim((string) ($voucher['status'] ?? '-')),
        'usage_limit' => $usageLimit,
        'used' => $used,
        'remaining' => $remaining,
        'expired_at' => (string) ($voucher['expired_at'] ?? ''),
        'liability' => $liabilityValue,
    ];
}, $voucherList);
usort($voucherRows, static fn (array $left, array $right): int => $right['remaining'] <=> $left['remaining']);

$activeVoucherRows = array_values(array_filter($voucherRows, static fn (array $row): bool => strtolower((string) ($row['status'] ?? '')) === 'aktif'));
$expiredVoucherRows = array_values(array_filter($voucherRows, static fn (array $row): bool => strtolower((string) ($row['status'] ?? '')) === 'expired'));
$voucherLiability = array_sum(array_map(static fn (array $row): float => (float) ($row['liability'] ?? 0), $activeVoucherRows));
$voucherGiftSales = array_sum(array_map(static fn (array $row): float => ($row['type'] === 'gift' ? (float) ($row['value'] ?? 0) * (float) ($row['used'] ?? 0) : 0.0), $voucherRows));
$makeChartPayload = static fn (array $payload): string => json_encode($payload, JSON_THROW_ON_ERROR);

$salesDayLabels = array_map(static fn (array $row): string => date('d M', strtotime($row['date'])), $salesByDay);
$salesDayNetSeries = array_map(static fn (array $row): float => (float) $row['net'], $salesByDay);
$salesDayGrossSeries = array_map(static fn (array $row): float => (float) $row['gross'], $salesByDay);

$paymentChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['label'], $paymentSummary),
    'datasets' => [[
        'label' => 'Net Sales',
        'data' => array_map(static fn (array $row): float => (float) $row['net'], $paymentSummary),
        'backgroundColor' => ['#63b4ff', '#7bdcb5', '#ffb86c', '#8ea6ff', '#ffd66b', '#f497b5'],
        'borderWidth' => 0,
    ]],
]);

$salesTypeChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['label'], $salesTypeRows),
    'datasets' => [[
        'label' => 'Gross Sales',
        'data' => array_map(static fn (array $row): float => (float) $row['gross'], $salesTypeRows),
        'backgroundColor' => ['#63b4ff', '#7bdcb5', '#ffb86c', '#8ea6ff', '#f497b5', '#ffd66b'],
        'borderWidth' => 0,
    ]],
]);

$salesDailyChartPayload = $makeChartPayload([
    'labels' => $salesDayLabels,
    'datasets' => [
        [
            'label' => 'Net Sales',
            'data' => $salesDayNetSeries,
            'borderColor' => '#63b4ff',
            'backgroundColor' => 'transparent',
            'pointRadius' => 3,
            'pointHoverRadius' => 4,
            'pointBackgroundColor' => '#ffffff',
            'pointBorderColor' => '#63b4ff',
            'pointBorderWidth' => 2,
            'tension' => 0.25,
            'fill' => false,
        ],
        [
            'label' => 'Gross Sales',
            'data' => $salesDayGrossSeries,
            'borderColor' => '#7bdcb5',
            'backgroundColor' => 'transparent',
            'pointRadius' => 0,
            'pointHoverRadius' => 0,
            'tension' => 0.25,
            'fill' => false,
        ],
    ],
]);

$topStaffChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($staffAnalyticsRows, 0, 6)),
    'datasets' => [[
        'label' => 'Revenue',
        'data' => array_map(static fn (array $row): float => (float) $row['revenue'], array_slice($staffAnalyticsRows, 0, 6)),
        'backgroundColor' => '#63b4ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$topItemChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($topItemSales, 0, 6)),
    'datasets' => [[
        'label' => 'Gross Sales',
        'data' => array_map(static fn (array $row): float => (float) $row['gross'], array_slice($topItemSales, 0, 6)),
        'backgroundColor' => '#7bdcb5',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$agendaStatusChartPayload = $makeChartPayload([
    'labels' => ['Confirmed', 'Completed', 'Pending', 'Cancelled', 'No Show'],
    'datasets' => [[
        'label' => 'Booking',
        'data' => [$confirmedBookings, $completedBookings, $pendingBookings, $cancelledBookings, $noShowBookings],
        'backgroundColor' => ['#63b4ff', '#7bdcb5', '#ffd66b', '#ff9a8b', '#f497b5'],
        'borderWidth' => 0,
    ]],
]);

$agendaChannelChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['label'], $bookingChannelRows),
    'datasets' => [[
        'label' => 'Booking',
        'data' => array_map(static fn (array $row): int => (int) $row['count'], $bookingChannelRows),
        'backgroundColor' => '#63b4ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$staffUtilizationChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], $staffAnalyticsRows),
    'datasets' => [[
        'label' => 'Utilisasi',
        'data' => array_map(static fn (array $row): int => max(0, min(100, round(((float) ($row['booked_hours'] ?? 0) / 24) * 100))), $staffAnalyticsRows),
        'backgroundColor' => '#8ea6ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$staffCommissionChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($staffAnalyticsRows, 0, 6)),
    'datasets' => [[
        'label' => 'Komisi',
        'data' => array_map(static fn (array $row): float => (float) $row['commission'], array_slice($staffAnalyticsRows, 0, 6)),
        'backgroundColor' => '#ffb86c',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$customerRetentionChartPayload = $makeChartPayload([
    'labels' => ['Returning', 'New'],
    'datasets' => [[
        'label' => 'Pelanggan',
        'data' => [count($returningCustomerRows), count($newCustomerRows)],
        'backgroundColor' => ['#63b4ff', '#7bdcb5'],
        'borderWidth' => 0,
    ]],
]);

$topCustomerChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($topCustomerRows, 0, 6)),
    'datasets' => [[
        'label' => 'Total Belanja',
        'data' => array_map(static fn (array $row): float => (float) $row['spend'], array_slice($topCustomerRows, 0, 6)),
        'backgroundColor' => '#63b4ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$loyaltyTopChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($topLoyaltyRows, 0, 6)),
    'datasets' => [[
        'label' => 'Poin',
        'data' => array_map(static fn (array $row): int => (int) $row['loyalty_points'], array_slice($topLoyaltyRows, 0, 6)),
        'backgroundColor' => '#8ea6ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$loyaltyActivityChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => date('d M', strtotime($row['date'])), array_slice($loyaltyActivityRows, 0, 6)),
    'datasets' => [[
        'label' => 'Poin Earned',
        'data' => array_map(static fn (array $row): int => (int) $row['earned'], array_slice($loyaltyActivityRows, 0, 6)),
        'backgroundColor' => '#7bdcb5',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$inventoryStatusChartPayload = $makeChartPayload([
    'labels' => ['Aman', 'Rendah'],
    'datasets' => [[
        'label' => 'Produk',
        'data' => [
            count(array_filter($productInventoryRows, static fn (array $row): bool => strtolower((string) ($row['status'] ?? '')) !== 'rendah')),
            count($lowStockRows),
        ],
        'backgroundColor' => ['#7bdcb5', '#ff9a8b'],
        'borderWidth' => 0,
    ]],
]);

$inventoryLowStockChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($lowStockRows, 0, 6)),
    'datasets' => [[
        'label' => 'Stock',
        'data' => array_map(static fn (array $row): int => (int) $row['stock'], array_slice($lowStockRows, 0, 6)),
        'backgroundColor' => '#ff9a8b',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$productSalesChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($productSalesRows, 0, 6)),
    'datasets' => [[
        'label' => 'Gross Sales',
        'data' => array_map(static fn (array $row): float => (float) $row['gross'], array_slice($productSalesRows, 0, 6)),
        'backgroundColor' => '#63b4ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$voucherStatusChartPayload = $makeChartPayload([
    'labels' => ['Aktif', 'Expired'],
    'datasets' => [[
        'label' => 'Voucher',
        'data' => [count($activeVoucherRows), count($expiredVoucherRows)],
        'backgroundColor' => ['#63b4ff', '#c9d4e5'],
        'borderWidth' => 0,
    ]],
]);

$voucherActiveChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($activeVoucherRows, 0, 6)),
    'datasets' => [[
        'label' => 'Remaining',
        'data' => array_map(static fn (array $row): int => (int) $row['remaining'], array_slice($activeVoucherRows, 0, 6)),
        'backgroundColor' => '#8ea6ff',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$voucherUsageChartPayload = $makeChartPayload([
    'labels' => array_map(static fn (array $row): string => $row['name'], array_slice($voucherRows, 0, 6)),
    'datasets' => [[
        'label' => 'Used',
        'data' => array_map(static fn (array $row): int => (int) $row['used'], array_slice($voucherRows, 0, 6)),
        'backgroundColor' => '#ffb86c',
        'borderRadius' => 10,
        'borderSkipped' => false,
    ]],
]);

$overviewCards = [
    [
        'title' => 'Total Appointment',
        'value' => (string) count($bookings),
        'change' => sprintf('%d%%', count($bookings) > 0 ? round((($completedBookings + $confirmedBookings) / count($bookings)) * 100) : 0),
        'details' => [
            sprintf('NOT COMPLETED %d (%d%%)', $pendingBookings, count($bookings) > 0 ? round(($pendingBookings / count($bookings)) * 100) : 0),
            sprintf('COMPLETED %d (%d%%)', $completedBookings, count($bookings) > 0 ? round(($completedBookings / count($bookings)) * 100) : 0),
            sprintf('CANCELED %d (%d%%)', $cancelledBookings, count($bookings) > 0 ? round(($cancelledBookings / count($bookings)) * 100) : 0),
            sprintf('NO SHOW %d (%d%%)', $noShowBookings, count($bookings) > 0 ? round(($noShowBookings / count($bookings)) * 100) : 0),
        ],
    ],
    [
        'title' => 'Online Appointments',
        'value' => sprintf('%d%%', count($bookings) > 0 ? round(($onlineBookings / count($bookings)) * 100) : 0),
        'change' => sprintf('%d%%', count($bookings) > 0 ? round(($confirmedBookings / count($bookings)) * 100) : 0),
        'details' => [
            sprintf('NOT COMPLETED %d (%d%%)', $pendingBookings, count($bookings) > 0 ? round(($pendingBookings / count($bookings)) * 100) : 0),
            sprintf('COMPLETED %d (%d%%)', $completedBookings, count($bookings) > 0 ? round(($completedBookings / count($bookings)) * 100) : 0),
            sprintf('CANCELED %d (%d%%)', $cancelledBookings, count($bookings) > 0 ? round(($cancelledBookings / count($bookings)) * 100) : 0),
            sprintf('NO SHOW %d (%d%%)', $noShowBookings, count($bookings) > 0 ? round(($noShowBookings / count($bookings)) * 100) : 0),
        ],
    ],
    [
        'title' => 'Occupancy',
        'value' => sprintf('%d%%', $workingHours > 0 ? round(($occupiedHours / $workingHours) * 100) : 0),
        'change' => sprintf('%d%%', $workingHours > 0 ? round(($blockedHours / $workingHours) * 100) : 0),
        'details' => [
            sprintf('Working Hours %.0f Hours (100%%)', $workingHours),
            sprintf('Booked Hours %.1f Hours (%d%%)', $occupiedHours, $workingHours > 0 ? round(($occupiedHours / $workingHours) * 100) : 0),
            sprintf('Blocked Hours %.1f Hours (%d%%)', $blockedHours, $workingHours > 0 ? round(($blockedHours / $workingHours) * 100) : 0),
            sprintf('Unbooked Hours %.1f Hours (%d%%)', $unbookedHours, $workingHours > 0 ? round(($unbookedHours / $workingHours) * 100) : 0),
        ],
    ],
    [
        'title' => 'Total Sales',
        'value' => number_format($salesTotal, 2, ',', '.'),
        'change' => sprintf('%d%%', $salesTotal > 0 ? 100 : 0),
        'details' => [
            sprintf('Services %s (%d%%)', number_format($salesByType['service'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['service'] ?? 0) / $salesTotal) * 100) : 0),
            sprintf('Products %s (%d%%)', number_format($salesByType['product'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['product'] ?? 0) / $salesTotal) * 100) : 0),
            sprintf('Class %s (%d%%)', number_format(array_sum(array_map(fn (array $class): int => $class['booked'], $classList)) * 0, 2, ',', '.'), 0),
            sprintf('Voucher %s (%d%%)', number_format($salesByType['voucher'] ?? 0, 2, ',', '.'), $salesTotal > 0 ? round((($salesByType['voucher'] ?? 0) / $salesTotal) * 100) : 0),
        ],
    ],
    [
        'title' => 'Average Sale',
        'value' => number_format($averageSale, 2, ',', '.'),
        'change' => sprintf('%d%%', count($paidTransactions) > 0 ? 100 : 0),
        'details' => [
            sprintf('Sales %d (%d)', count($paidTransactions), count($paidTransactions)),
            sprintf('Avg. Service Sale %s', number_format(($salesByType['service'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
            sprintf('Avg. Product Sale %s', number_format(($salesByType['product'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
            sprintf('Avg. Class Sale %s', number_format(0, 2, ',', '.')),
            sprintf('Avg. Voucher Sale %s', number_format(($salesByType['voucher'] ?? 0) / max(1, count($paidTransactions)), 2, ',', '.')),
        ],
    ],
    [
        'title' => 'Client Retention (Sales)',
        'value' => sprintf('%d%%', count($customersList) > 0 ? round(($returningCustomers / count($customersList)) * 100) : 0),
        'change' => sprintf('%d%%', count($customersList) > 0 ? round(($newCustomers / count($customersList)) * 100) : 0),
        'details' => [
            sprintf('Returning %d (%d%%)', $returningCustomers, count($customersList) > 0 ? round(($returningCustomers / count($customersList)) * 100) : 0),
            sprintf('New %d (%d%%)', $newCustomers, count($customersList) > 0 ? round(($newCustomers / count($customersList)) * 100) : 0),
            sprintf('Walk-In %d (%d%%)', count($bookings) - $onlineBookings, count($bookings) > 0 ? round(((count($bookings) - $onlineBookings) / count($bookings)) * 100) : 0),
        ],
    ],
];

$reportCards = [
    [
        'key' => 'finance',
        'icon' => 'pie-chart',
        'title' => 'Keuangan',
        'description' => 'Pantau keseluruhan keuangan Anda termasuk penjualan, pengembalian uang, pajak, pembayaran, dan lainnya.',
        'items' => $financeReportItems,
    ],
    [
        'key' => 'sales',
        'icon' => 'file-earmark-text',
        'title' => 'Penjualan',
        'description' => 'Analisis kinerja bisnis Anda dengan membandingkan penjualan di seluruh produk, staf, saluran, dan lainnya.',
        'items' => $salesReportItems,
    ],
    [
        'key' => 'inventory',
        'icon' => 'box-seam',
        'title' => 'Inventori',
        'description' => 'Pantau tingkat stok produk dan penyesuaian yang dilakukan, analisis kinerja penjualan produk, biaya konsumsi, dan lainnya.',
        'items' => $inventoryReportItems,
    ],
    [
        'key' => 'voucher',
        'icon' => 'tag',
        'title' => 'Voucher',
        'description' => 'Lacak total liabilitas terutang Anda serta penjualan voucher dan aktivitas penukaran.',
        'items' => $voucherReportItems,
    ],
    [
        'key' => 'agenda',
        'icon' => 'calendar3',
        'title' => 'Agenda',
        'description' => 'Lihat proyeksi pendapatan dari agenda yang akan datang, lacak tingkat pembatalan dan alasannya.',
        'items' => $agendaReportItems,
    ],
    [
        'key' => 'staff',
        'icon' => 'people',
        'title' => 'Staf',
        'description' => 'Track jam kerja staff, komisi, dan tip.',
        'items' => $staffReportItems,
    ],
    [
        'key' => 'customers',
        'icon' => 'emoji-smile',
        'title' => 'Pelanggan',
        'description' => 'Dapatkan wawasan tentang bagaimana klien berinteraksi dengan bisnis Anda dan siapa pembelanja utama Anda.',
        'items' => $customerReportItems,
    ],
    [
        'key' => 'loyalty',
        'icon' => 'emoji-smile',
        'title' => 'Point Loyalitas',
        'description' => 'Lacak total poin loyalitas Anda dari setiap pelanggan serta aktivitas penukaran mereka.',
        'items' => $loyaltyReportItems,
    ],
];
?>

<section class="analytics-shell js-analytics-shell">
    <div class="analytics-tabs">
        <button class="analytics-tab is-active" type="button" data-analytics-tab="overview">Beranda</button>
        <button class="analytics-tab" type="button" data-analytics-tab="reports">Laporan</button>
    </div>

    <div class="analytics-panels">
        <section class="analytics-panel is-active" data-analytics-panel="overview">
            <div class="analytics-panel__sticky">
                <div class="analytics-toolbar">
                    <div class="analytics-toolbar__group">
                        <div class="dropdown">
                            <button class="dashboard-filter dashboard-filter--shop" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-shop"></i>
                                <span data-analytics-shop-label>Star Salon</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu analytics-filter-menu">
                                <button class="dropdown-item analytics-filter-option is-active" type="button" data-analytics-shop-option="Star Salon">Star Salon</button>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="dashboard-filter dashboard-filter--shop analytics-staff-filter" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span data-analytics-staff-label>Semua Staff</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu analytics-filter-menu">
                                <button class="dropdown-item analytics-filter-option is-active" type="button" data-analytics-staff-option="Semua Staff">Semua Staff</button>
                                <?php foreach ($staffChoices as $staffName): ?>
                                    <button class="dropdown-item analytics-filter-option" type="button" data-analytics-staff-option="<?= e($staffName) ?>"><?= e($staffName) ?></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="analytics-toolbar__group analytics-toolbar__group--end">
                        <button class="dashboard-filter dashboard-filter--wide" type="button" data-bs-toggle="modal" data-bs-target="#analyticsDateFilterModal">
                            <i class="bi bi-calendar3"></i>
                            <span data-analytics-range-label><?= e($rangeLabel) ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="analytics-panel__scroll analytics-panel__scroll--overview">
                <div class="analytics-overview-grid">
                    <?php foreach ($overviewCards as $card): ?>
                        <article class="analytics-overview-card" data-analytics-card tabindex="0" role="button" aria-expanded="false">
                            <h3><?= e($card['title']) ?></h3>
                            <div class="analytics-overview-card__value">
                                <strong><?= e($card['value']) ?></strong>
                                <span>&mdash; <?= e($card['change']) ?></span>
                            </div>
                            <div class="analytics-overview-card__details" data-analytics-card-details>
                                <?php foreach ($card['details'] as $detail): ?>
                                    <div><?= e($detail) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <section class="analytics-panel" data-analytics-panel="reports">
            <div class="analytics-panel__scroll analytics-panel__scroll--reports">
            <div class="analytics-report-grid">
                <?php foreach ($reportCards as $report): ?>
                    <button class="analytics-report-card" type="button" data-report-card data-report-key="<?= e($report['key']) ?>">
                        <div class="analytics-report-card__icon"><i class="bi bi-<?= e($report['icon']) ?>"></i></div>
                        <div class="analytics-report-card__content">
                            <h3><?= e($report['title']) ?></h3>
                            <p><?= e($report['description']) ?></p>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="analytics-report-popover" data-report-popover>
                <div class="analytics-report-popover__list">
                    <?php foreach ($reportCards as $report): ?>
                        <div class="analytics-report-group <?= $report['key'] === 'finance' ? 'is-active' : '' ?>" data-report-group="<?= e($report['key']) ?>">
                            <?php foreach ($report['items'] as $itemIndex => $item): ?>
                                <?php
                                $itemKey = is_array($item) ? (string) ($item['key'] ?? '') : '';
                                $itemLabel = is_array($item) ? (string) ($item['label'] ?? '') : (string) $item;
                                ?>
                                <button class="analytics-report-item <?= $report['key'] === 'finance' && $itemIndex === 0 ? 'is-active' : '' ?>" type="button" data-report-item data-report-target="<?= e($report['key']) ?>" data-report-title="<?= e($itemLabel) ?>" data-report-item-key="<?= e($itemKey !== '' ? $itemKey : strtolower(str_replace(' ', '-', $itemLabel))) ?>">
                                    <span><?= e($itemLabel) ?></span>
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <section class="analytics-report-detail" data-analytics-detail>
                <div class="analytics-report-detail__header">
                    <button class="analytics-back-link" type="button" data-report-back>
                        <i class="bi bi-arrow-left"></i>
                        <span>Ringkasan keuangan</span>
                    </button>
                    <div class="analytics-detail-toolbar">
                        <div class="dropdown">
                            <button class="dashboard-filter dashboard-filter--shop" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-shop"></i>
                                <span data-analytics-shop-label>Star Salon</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu analytics-filter-menu">
                                <button class="dropdown-item analytics-filter-option is-active" type="button" data-analytics-shop-option="Star Salon">Star Salon</button>
                            </div>
                        </div>
                        <button class="dashboard-filter dashboard-filter--wide" type="button" data-bs-toggle="modal" data-bs-target="#analyticsDateFilterModal">
                            <i class="bi bi-calendar3"></i>
                            <span data-analytics-range-label><?= e($rangeLabel) ?></span>
                        </button>
                        <label class="analytics-switch">
                            <span class="analytics-switch__track"></span>
                            <span>Berdasarkan tanggal pembayaran</span>
                        </label>
                        <button class="dashboard-filter" type="button"><span>Export</span><i class="bi bi-caret-down-fill"></i></button>
                    </div>
                </div>

                <div class="analytics-report-detail__body">
                <div class="analytics-report-panels">
                    <section class="analytics-report-panel is-active" data-analytics-detail-panel="finance-summary">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Penjualan kotor</span>
                                <strong>Rp <?= e(number_format($grossSales, 0, ',', '.')) ?></strong>
                                <small>Sebelum diskon dan refund</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Total diskon</span>
                                <strong>Rp <?= e(number_format($totalDiscounts, 0, ',', '.')) ?></strong>
                                <small><?= e((string) count(array_filter($paidTransactions, fn (array $transaction): bool => ((float) ($transaction['discount'] ?? 0)) > 0))) ?> transaksi pakai diskon</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Total refund</span>
                                <strong>Rp <?= e(number_format($refundTotal, 0, ',', '.')) ?></strong>
                                <small><?= e((string) count($refundTransactions)) ?> transaksi refund</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Penjualan bersih</span>
                                <strong>Rp <?= e(number_format($salesTotal, 0, ',', '.')) ?></strong>
                                <small>Setelah diskon dan refund</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Jumlah transaksi</span>
                                <strong><?= e((string) count($paidTransactions)) ?></strong>
                                <small>Transaksi berstatus paid</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Rata-rata transaksi</span>
                                <strong>Rp <?= e(number_format($averageSale, 0, ',', '.')) ?></strong>
                                <small>Net sales / transaksi paid</small>
                            </article>
                        </div>

                        <div class="analytics-report-highlights">
                            <article class="analytics-report-highlight">
                                <span>Hari penjualan terbaik</span>
                                <strong><?= e($bestSalesDay['date'] ? date('d M Y', strtotime((string) $bestSalesDay['date'])) : '-') ?></strong>
                                <small>Net sales Rp <?= e(number_format((float) ($bestSalesDay['net'] ?? 0), 0, ',', '.')) ?> dari <?= e((string) ($bestSalesDay['transactions'] ?? 0)) ?> transaksi</small>
                            </article>
                            <article class="analytics-report-highlight">
                                <span>Transaksi dengan diskon</span>
                                <strong><?= e((string) $discountedTransactionsCount) ?></strong>
                                <small><?= e(count($paidTransactions) > 0 ? (string) round(($discountedTransactionsCount / count($paidTransactions)) * 100) : '0') ?>% dari transaksi paid menggunakan diskon</small>
                            </article>
                            <article class="analytics-report-highlight">
                                <span>Metode pembayaran teratas</span>
                                <strong><?= e($bestPaymentMethod['label'] ?? '-') ?></strong>
                                <small>Net sales Rp <?= e(number_format((float) ($bestPaymentMethod['net'] ?? 0), 0, ',', '.')) ?></small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="line" data-chart='<?= e($salesDailyChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan keuangan</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Penjualan Kotor</th>
                                            <th>Diskon</th>
                                            <th>Refund</th>
                                            <th>Penjualan Bersih</th>
                                            <th>Rata-rata Sale</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Rp <?= e(number_format($grossSales, 0, ',', '.')) ?></td>
                                            <td>Rp <?= e(number_format($totalDiscounts, 0, ',', '.')) ?></td>
                                            <td>Rp <?= e(number_format($refundTotal, 0, ',', '.')) ?></td>
                                            <td>Rp <?= e(number_format($salesTotal, 0, ',', '.')) ?></td>
                                            <td>Rp <?= e(number_format($averageSale, 0, ',', '.')) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analytics-table-block">
                                <h3>Tren penjualan harian</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Transaksi</th>
                                            <th>Penjualan Kotor</th>
                                            <th>Diskon</th>
                                            <th>Penjualan Bersih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salesByDay as $row): ?>
                                            <tr>
                                                <td><?= e(date('d M Y', strtotime($row['date']))) ?></td>
                                                <td><?= e((string) $row['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($row['gross'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($row['discount'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($row['net'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="finance-payments">
                        <div class="analytics-report-kpis">
                            <?php foreach ($paymentSummary as $paymentRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($paymentRow['label']) ?></span>
                                    <strong>Rp <?= e(number_format($paymentRow['net'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $paymentRow['transactions']) ?> transaksi • <?= e($grossSales > 0 ? (string) round(($paymentRow['gross'] / $grossSales) * 100) : '0') ?>% gross sales</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-highlights">
                            <article class="analytics-report-highlight">
                                <span>Total metode aktif</span>
                                <strong><?= e((string) count($paymentSummary)) ?></strong>
                                <small>Metode pembayaran yang dipakai di periode ini</small>
                            </article>
                            <article class="analytics-report-highlight">
                                <span>Pembayaran cash</span>
                                <strong>Rp <?= e(number_format((float) (($paymentSummaryMap['Cash']['net'] ?? 0)), 0, ',', '.')) ?></strong>
                                <small><?= e((string) (($paymentSummaryMap['Cash']['transactions'] ?? 0))) ?> transaksi cash</small>
                            </article>
                            <article class="analytics-report-highlight">
                                <span>Pembayaran non-cash</span>
                                <strong>Rp <?= e(number_format(max(0, $salesTotal - (float) (($paymentSummaryMap['Cash']['net'] ?? 0))), 0, ',', '.')) ?></strong>
                                <small>Transfer, e-wallet, dan metode lainnya</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($paymentChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan pembayaran</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Metode</th>
                                            <th>Transaksi</th>
                                            <th>Penjualan Kotor</th>
                                            <th>Diskon</th>
                                            <th>Penjualan Bersih</th>
                                            <th>Rata-rata</th>
                                            <th>Kontribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($paymentSummary as $paymentRow): ?>
                                            <tr>
                                                <td><?= e($paymentRow['label']) ?></td>
                                                <td><?= e((string) $paymentRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($paymentRow['gross'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($paymentRow['discount'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($paymentRow['net'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($paymentRow['transactions'] > 0 ? ($paymentRow['net'] / $paymentRow['transactions']) : 0, 0, ',', '.')) ?></td>
                                                <td><?= e($salesTotal > 0 ? (string) round(($paymentRow['net'] / $salesTotal) * 100) : '0') ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="finance-sales-type">
                        <div class="analytics-report-kpis">
                            <?php foreach ($salesTypeRows as $typeRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($typeRow['label']) ?></span>
                                    <strong>Rp <?= e(number_format($typeRow['gross'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $typeRow['transactions']) ?> transaksi • <?= e((string) $typeRow['share']) ?>% dari gross sales</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-highlights">
                            <?php foreach (array_slice($topItemSales, 0, 3) as $itemRow): ?>
                                <article class="analytics-report-highlight">
                                    <span><?= e($itemRow['type']) ?> teratas</span>
                                    <strong><?= e($itemRow['name']) ?></strong>
                                    <small><?= e((string) $itemRow['qty']) ?> qty • Rp <?= e(number_format($itemRow['gross'], 0, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($salesTypeChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Penjualan per tipe</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Transaksi</th>
                                            <th>Gross Sales</th>
                                            <th>Kontribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salesTypeRows as $typeRow): ?>
                                            <tr>
                                                <td><?= e($typeRow['label']) ?></td>
                                                <td><?= e((string) $typeRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($typeRow['gross'], 0, ',', '.')) ?></td>
                                                <td><?= e((string) $typeRow['share']) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analytics-table-block">
                                <h3>Item terlaris berdasarkan revenue</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Tipe</th>
                                            <th>Qty</th>
                                            <th>Transaksi</th>
                                            <th>Gross Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topItemSales as $itemRow): ?>
                                            <tr>
                                                <td><?= e($itemRow['name']) ?></td>
                                                <td><?= e($itemRow['type']) ?></td>
                                                <td><?= e((string) $itemRow['qty']) ?></td>
                                                <td><?= e((string) $itemRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($itemRow['gross'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="sales-daily">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total penjualan bersih</span>
                                <strong>Rp <?= e(number_format($salesTotal, 0, ',', '.')) ?></strong>
                                <small>Dari <?= e((string) count($paidTransactions)) ?> transaksi paid</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Hari terbaik</span>
                                <strong><?= e($bestSalesDay['date'] ? date('d M Y', strtotime((string) $bestSalesDay['date'])) : '-') ?></strong>
                                <small>Rp <?= e(number_format((float) ($bestSalesDay['net'] ?? 0), 0, ',', '.')) ?></small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Rata-rata harian</span>
                                <strong>Rp <?= e(number_format(count($salesByDay) > 0 ? ($salesTotal / count($salesByDay)) : 0, 0, ',', '.')) ?></strong>
                                <small>Net sales per hari aktif</small>
                            </article>
                        </div>

                        <div class="analytics-report-highlights">
                            <?php foreach (array_slice($salesByDay, 0, 3) as $dailyRow): ?>
                                <article class="analytics-report-highlight">
                                    <span><?= e(date('d M Y', strtotime($dailyRow['date']))) ?></span>
                                    <strong>Rp <?= e(number_format($dailyRow['net'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $dailyRow['transactions']) ?> transaksi • diskon Rp <?= e(number_format($dailyRow['discount'], 0, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="line" data-chart='<?= e($salesDailyChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Penjualan harian</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Transaksi</th>
                                            <th>Penjualan Kotor</th>
                                            <th>Diskon</th>
                                            <th>Penjualan Bersih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($salesByDay as $row): ?>
                                            <tr>
                                                <td><?= e(date('d M Y', strtotime($row['date']))) ?></td>
                                                <td><?= e((string) $row['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($row['gross'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($row['discount'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($row['net'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analytics-table-block">
                                <h3>Penjualan per channel</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Channel</th>
                                            <th>Transaksi</th>
                                            <th>Net Sales</th>
                                            <th>Kontribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($channelSalesRows as $channelRow): ?>
                                            <tr>
                                                <td><?= e($channelRow['label']) ?></td>
                                                <td><?= e((string) $channelRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($channelRow['net'], 0, ',', '.')) ?></td>
                                                <td><?= e($salesTotal > 0 ? (string) round(($channelRow['net'] / $salesTotal) * 100) : '0') ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="sales-staff">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($staffSalesRows, 0, 3) as $staffRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($staffRow['name']) ?></span>
                                    <strong>Rp <?= e(number_format($staffRow['net'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $staffRow['transactions']) ?> transaksi</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-highlights">
                            <?php foreach (array_slice($staffSalesRows, 0, 3) as $staffRow): ?>
                                <article class="analytics-report-highlight">
                                    <span>Top staff</span>
                                    <strong><?= e($staffRow['name']) ?></strong>
                                    <small>Avg sale Rp <?= e(number_format($staffRow['transactions'] > 0 ? ($staffRow['net'] / $staffRow['transactions']) : 0, 0, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($topStaffChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Penjualan per staf</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Transaksi</th>
                                            <th>Gross Sales</th>
                                            <th>Net Sales</th>
                                            <th>Avg Sale</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffSalesRows as $staffRow): ?>
                                            <tr>
                                                <td><?= e($staffRow['name']) ?></td>
                                                <td><?= e((string) $staffRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($staffRow['gross'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($staffRow['net'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($staffRow['transactions'] > 0 ? ($staffRow['net'] / $staffRow['transactions']) : 0, 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="sales-items">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($topItemSales, 0, 3) as $itemRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($itemRow['type']) ?></span>
                                    <strong><?= e($itemRow['name']) ?></strong>
                                    <small>Rp <?= e(number_format($itemRow['gross'], 0, ',', '.')) ?> • <?= e((string) $itemRow['qty']) ?> qty</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-highlights">
                            <?php foreach ($salesTypeRows as $typeRow): ?>
                                <article class="analytics-report-highlight">
                                    <span><?= e($typeRow['label']) ?></span>
                                    <strong><?= e((string) $typeRow['share']) ?>%</strong>
                                    <small><?= e((string) $typeRow['transactions']) ?> transaksi • Rp <?= e(number_format($typeRow['gross'], 0, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($topItemChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Penjualan per item</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Tipe</th>
                                            <th>Qty</th>
                                            <th>Transaksi</th>
                                            <th>Gross Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topItemSales as $itemRow): ?>
                                            <tr>
                                                <td><?= e($itemRow['name']) ?></td>
                                                <td><?= e($itemRow['type']) ?></td>
                                                <td><?= e((string) $itemRow['qty']) ?></td>
                                                <td><?= e((string) $itemRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($itemRow['gross'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="agenda-upcoming">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total agenda mendatang</span>
                                <strong><?= e((string) count($upcomingBookings)) ?></strong>
                                <small>Booking hari ini dan seterusnya</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Booking online</span>
                                <strong><?= e((string) $onlineBookings) ?></strong>
                                <small>Dari semua booking di periode ini</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Status terkonfirmasi</span>
                                <strong><?= e((string) ($confirmedBookings + $completedBookings)) ?></strong>
                                <small>Booking siap atau sudah berjalan</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($agendaStatusChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Agenda mendatang</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Referensi</th>
                                            <th>Pelanggan</th>
                                            <th>Staff</th>
                                            <th>Mulai</th>
                                            <th>Selesai</th>
                                            <th>Channel</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($upcomingBookings as $booking): ?>
                                            <?php
                                            $customerName = '-';
                                            foreach ($customersList as $customer) {
                                                if ((int) ($customer['id'] ?? 0) === (int) ($booking['customer_id'] ?? 0)) {
                                                    $customerName = trim((string) ($customer['name'] ?? '-'));
                                                    break;
                                                }
                                            }
                                            $staffName = '-';
                                            foreach ($staffMembers as $staffMember) {
                                                if ((int) ($staffMember['id'] ?? 0) === (int) ($booking['staff_id'] ?? 0)) {
                                                    $staffName = trim((string) ($staffMember['name'] ?? '-'));
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td><?= e((string) ($booking['reference'] ?? '-')) ?></td>
                                                <td><?= e($customerName) ?></td>
                                                <td><?= e($staffName) ?></td>
                                                <td><?= e(date('d M Y H:i', strtotime((string) ($booking['start_at'] ?? 'now')))) ?></td>
                                                <td><?= e(date('H:i', strtotime((string) ($booking['end_at'] ?? 'now')))) ?></td>
                                                <td><?= e((string) ($booking['channel'] ?? '-')) ?></td>
                                                <td><?= e(ucfirst((string) ($booking['status'] ?? '-'))) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="agenda-cancellations">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total pembatalan</span>
                                <strong><?= e((string) $cancelledBookings) ?></strong>
                                <small><?= e(count($bookings) > 0 ? (string) round(($cancelledBookings / count($bookings)) * 100) : '0') ?>% dari booking</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Total no show</span>
                                <strong><?= e((string) $noShowBookings) ?></strong>
                                <small>Belum ada data no show tambahan</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Booking pending</span>
                                <strong><?= e((string) $pendingBookings) ?></strong>
                                <small>Perlu follow up lebih lanjut</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($agendaStatusChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Daftar pembatalan & no show</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Referensi</th>
                                            <th>Pelanggan</th>
                                            <th>Status</th>
                                            <th>Channel</th>
                                            <th>Jadwal</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cancelledBookingsList as $booking): ?>
                                            <?php
                                            $customerName = '-';
                                            foreach ($customersList as $customer) {
                                                if ((int) ($customer['id'] ?? 0) === (int) ($booking['customer_id'] ?? 0)) {
                                                    $customerName = trim((string) ($customer['name'] ?? '-'));
                                                    break;
                                                }
                                            }
                                            ?>
                                            <tr>
                                                <td><?= e((string) ($booking['reference'] ?? '-')) ?></td>
                                                <td><?= e($customerName) ?></td>
                                                <td><?= e(ucfirst((string) ($booking['status'] ?? '-'))) ?></td>
                                                <td><?= e((string) ($booking['channel'] ?? '-')) ?></td>
                                                <td><?= e(date('d M Y H:i', strtotime((string) ($booking['start_at'] ?? 'now')))) ?></td>
                                                <td><?= e((string) ($booking['notes'] ?? '-')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="agenda-channels">
                        <div class="analytics-report-kpis">
                            <?php foreach ($bookingChannelRows as $channelRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($channelRow['label']) ?></span>
                                    <strong><?= e((string) $channelRow['count']) ?></strong>
                                    <small><?= e((string) $channelRow['confirmed']) ?> confirmed/completed</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($agendaChannelChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Booking per channel</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Channel</th>
                                            <th>Total booking</th>
                                            <th>Confirmed/Completed</th>
                                            <th>Cancelled/No show</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookingChannelRows as $channelRow): ?>
                                            <tr>
                                                <td><?= e($channelRow['label']) ?></td>
                                                <td><?= e((string) $channelRow['count']) ?></td>
                                                <td><?= e((string) $channelRow['confirmed']) ?></td>
                                                <td><?= e((string) $channelRow['cancelled']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="staff-performance">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($staffAnalyticsRows, 0, 3) as $staffRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($staffRow['name']) ?></span>
                                    <strong>Rp <?= e(number_format($staffRow['revenue'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $staffRow['bookings']) ?> booking • rating <?= e(number_format($staffRow['rating'], 1, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($topStaffChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Kinerja staf</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Role</th>
                                            <th>Booking</th>
                                            <th>Booked Hours</th>
                                            <th>Revenue</th>
                                            <th>Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffAnalyticsRows as $staffRow): ?>
                                            <tr>
                                                <td><?= e($staffRow['name']) ?></td>
                                                <td><?= e($staffRow['role']) ?></td>
                                                <td><?= e((string) $staffRow['bookings']) ?></td>
                                                <td><?= e(number_format($staffRow['booked_hours'], 1, ',', '.')) ?> jam</td>
                                                <td>Rp <?= e(number_format($staffRow['revenue'], 0, ',', '.')) ?></td>
                                                <td><?= e(number_format($staffRow['rating'], 1, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="staff-utilization">
                        <div class="analytics-report-kpis">
                            <?php foreach ($staffAnalyticsRows as $staffRow): ?>
                                <?php $utilization = max(0, min(100, round(($staffRow['booked_hours'] / 24) * 100))); ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($staffRow['name']) ?></span>
                                    <strong><?= e((string) $utilization) ?>%</strong>
                                    <small><?= e(number_format($staffRow['booked_hours'], 1, ',', '.')) ?> jam terisi</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($staffUtilizationChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Utilisasi staf</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Booked Hours</th>
                                            <th>Booking</th>
                                            <th>Utilisasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffAnalyticsRows as $staffRow): ?>
                                            <?php $utilization = max(0, min(100, round(($staffRow['booked_hours'] / 24) * 100))); ?>
                                            <tr>
                                                <td><?= e($staffRow['name']) ?></td>
                                                <td><?= e(number_format($staffRow['booked_hours'], 1, ',', '.')) ?> jam</td>
                                                <td><?= e((string) $staffRow['bookings']) ?></td>
                                                <td><?= e((string) $utilization) ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="staff-commission">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($staffAnalyticsRows, 0, 3) as $staffRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($staffRow['name']) ?></span>
                                    <strong>Rp <?= e(number_format($staffRow['commission'], 0, ',', '.')) ?></strong>
                                    <small>Estimasi komisi dari transaksi paid</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($staffCommissionChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Komisi staf</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Staff</th>
                                            <th>Revenue</th>
                                            <th>Komisi</th>
                                            <th>Kontribusi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($staffAnalyticsRows as $staffRow): ?>
                                            <tr>
                                                <td><?= e($staffRow['name']) ?></td>
                                                <td>Rp <?= e(number_format($staffRow['revenue'], 0, ',', '.')) ?></td>
                                                <td>Rp <?= e(number_format($staffRow['commission'], 0, ',', '.')) ?></td>
                                                <td><?= e($salesTotal > 0 ? (string) round(($staffRow['revenue'] / $salesTotal) * 100) : '0') ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="customers-summary">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total pelanggan</span>
                                <strong><?= e((string) count($customersList)) ?></strong>
                                <small>Pelanggan aktif di data saat ini</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Pelanggan returning</span>
                                <strong><?= e((string) count($returningCustomerRows)) ?></strong>
                                <small>Sudah lebih dari 1 kunjungan</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Pelanggan baru</span>
                                <strong><?= e((string) count($newCustomerRows)) ?></strong>
                                <small>Masih 1 kunjungan atau kurang</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($customerRetentionChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan pelanggan</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Pelanggan</th>
                                            <th>Member ID</th>
                                            <th>Visit</th>
                                            <th>Total Belanja</th>
                                            <th>Poin</th>
                                            <th>Last Visit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customerAnalyticsRows as $customerRow): ?>
                                            <tr>
                                                <td><?= e($customerRow['name']) ?></td>
                                                <td><?= e($customerRow['member_id']) ?></td>
                                                <td><?= e((string) $customerRow['visits']) ?></td>
                                                <td>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></td>
                                                <td><?= e((string) $customerRow['loyalty_points']) ?></td>
                                                <td><?= e($customerRow['last_visit'] !== '' ? date('d M Y', strtotime($customerRow['last_visit'])) : '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="customers-top">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($topCustomerRows, 0, 3) as $customerRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($customerRow['name']) ?></span>
                                    <strong>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $customerRow['visits']) ?> visit • <?= e((string) $customerRow['loyalty_points']) ?> poin</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($topCustomerChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Pelanggan teratas</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Pelanggan</th>
                                            <th>Visit</th>
                                            <th>Total Belanja</th>
                                            <th>Poin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topCustomerRows as $customerRow): ?>
                                            <tr>
                                                <td><?= e($customerRow['name']) ?></td>
                                                <td><?= e((string) $customerRow['visits']) ?></td>
                                                <td>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></td>
                                                <td><?= e((string) $customerRow['loyalty_points']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="customers-retention">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Retention rate</span>
                                <strong><?= e(count($customersList) > 0 ? (string) round((count($returningCustomerRows) / count($customersList)) * 100) : '0') ?>%</strong>
                                <small>Pelanggan dengan visit lebih dari 1</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Returning customers</span>
                                <strong><?= e((string) count($returningCustomerRows)) ?></strong>
                                <small>Sudah kembali lagi ke salon</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>New customers</span>
                                <strong><?= e((string) count($newCustomerRows)) ?></strong>
                                <small>Masih tahap kunjungan awal</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($customerRetentionChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Retensi pelanggan</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Pelanggan</th>
                                            <th>Visit</th>
                                            <th>Total Belanja</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customerAnalyticsRows as $customerRow): ?>
                                            <tr>
                                                <td><?= e($customerRow['name']) ?></td>
                                                <td><?= e((string) $customerRow['visits']) ?></td>
                                                <td>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></td>
                                                <td><?= e($customerRow['visits'] > 1 ? 'Returning' : 'New') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="loyalty-summary">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total saldo poin</span>
                                <strong><?= e((string) array_sum(array_map(static fn (array $row): int => (int) ($row['loyalty_points'] ?? 0), $customerAnalyticsRows))) ?></strong>
                                <small>Akumulasi seluruh pelanggan</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Pelanggan dengan poin</span>
                                <strong><?= e((string) count(array_filter($customerAnalyticsRows, static fn (array $row): bool => ($row['loyalty_points'] ?? 0) > 0))) ?></strong>
                                <small>Memiliki saldo poin aktif</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Estimasi poin earned</span>
                                <strong><?= e((string) array_sum(array_map(static fn (array $row): int => (int) ($row['earned'] ?? 0), $loyaltyActivityRows))) ?></strong>
                                <small>Dari transaksi paid di periode ini</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($customerRetentionChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan poin loyalitas</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Pelanggan</th>
                                            <th>Saldo Poin</th>
                                            <th>Visit</th>
                                            <th>Total Belanja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topLoyaltyRows as $customerRow): ?>
                                            <tr>
                                                <td><?= e($customerRow['name']) ?></td>
                                                <td><?= e((string) $customerRow['loyalty_points']) ?></td>
                                                <td><?= e((string) $customerRow['visits']) ?></td>
                                                <td>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="loyalty-top">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($topLoyaltyRows, 0, 3) as $customerRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($customerRow['name']) ?></span>
                                    <strong><?= e((string) $customerRow['loyalty_points']) ?> poin</strong>
                                    <small>Belanja Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($loyaltyTopChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Pelanggan poin tertinggi</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Pelanggan</th>
                                            <th>Poin</th>
                                            <th>Visit</th>
                                            <th>Total Belanja</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topLoyaltyRows as $customerRow): ?>
                                            <tr>
                                                <td><?= e($customerRow['name']) ?></td>
                                                <td><?= e((string) $customerRow['loyalty_points']) ?></td>
                                                <td><?= e((string) $customerRow['visits']) ?></td>
                                                <td>Rp <?= e(number_format($customerRow['spend'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="loyalty-activity">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total aktivitas poin</span>
                                <strong><?= e((string) count($loyaltyActivityRows)) ?></strong>
                                <small>Aktivitas earn dari transaksi paid</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Transaksi poin terbesar</span>
                                <strong><?= e((string) max(array_map(static fn (array $row): int => (int) ($row['earned'] ?? 0), $loyaltyActivityRows ?: [['earned' => 0]]))) ?> poin</strong>
                                <small>Earned points tertinggi per transaksi</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Rasio poin</span>
                                <strong>1 / Rp <?= e(number_format($loyaltyRatio, 0, ',', '.')) ?></strong>
                                <small>Perhitungan estimasi poin earned</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($loyaltyActivityChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Aktivitas poin</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th>Referensi</th>
                                            <th>Net Sales</th>
                                            <th>Poin Earned</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loyaltyActivityRows as $activityRow): ?>
                                            <tr>
                                                <td><?= e(date('d M Y', strtotime($activityRow['date']))) ?></td>
                                                <td><?= e($activityRow['customer']) ?></td>
                                                <td><?= e($activityRow['reference']) ?></td>
                                                <td>Rp <?= e(number_format($activityRow['net'], 0, ',', '.')) ?></td>
                                                <td><?= e((string) $activityRow['earned']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="inventory-summary">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total produk</span>
                                <strong><?= e((string) count($productInventoryRows)) ?></strong>
                                <small>Produk aktif di inventori</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Nilai inventori</span>
                                <strong>Rp <?= e(number_format($inventoryValue, 0, ',', '.')) ?></strong>
                                <small>Estimasi stok x harga jual</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Produk stok rendah</span>
                                <strong><?= e((string) count($lowStockRows)) ?></strong>
                                <small>Butuh restock lebih dulu</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($inventoryStatusChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan inventori</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Brand</th>
                                            <th>Kategori</th>
                                            <th>Stock</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productInventoryRows as $productRow): ?>
                                            <tr>
                                                <td><?= e($productRow['name']) ?></td>
                                                <td><?= e($productRow['brand']) ?></td>
                                                <td><?= e($productRow['category']) ?></td>
                                                <td><?= e((string) $productRow['stock']) ?></td>
                                                <td>Rp <?= e(number_format($productRow['price'], 0, ',', '.')) ?></td>
                                                <td><?= e($productRow['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="inventory-low-stock">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($lowStockRows, 0, 3) as $productRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($productRow['name']) ?></span>
                                    <strong><?= e((string) $productRow['stock']) ?> pcs</strong>
                                    <small><?= e($productRow['status']) ?> • <?= e($productRow['brand']) ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($inventoryLowStockChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Produk stok rendah</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Brand</th>
                                            <th>Kategori</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockRows as $productRow): ?>
                                            <tr>
                                                <td><?= e($productRow['name']) ?></td>
                                                <td><?= e($productRow['brand']) ?></td>
                                                <td><?= e($productRow['category']) ?></td>
                                                <td><?= e((string) $productRow['stock']) ?></td>
                                                <td><?= e($productRow['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="inventory-product-sales">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($productSalesRows, 0, 3) as $productRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($productRow['name']) ?></span>
                                    <strong>Rp <?= e(number_format($productRow['gross'], 0, ',', '.')) ?></strong>
                                    <small><?= e((string) $productRow['qty']) ?> qty • <?= e((string) $productRow['transactions']) ?> transaksi</small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($productSalesChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Penjualan produk</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Qty</th>
                                            <th>Transaksi</th>
                                            <th>Gross Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productSalesRows as $productRow): ?>
                                            <tr>
                                                <td><?= e($productRow['name']) ?></td>
                                                <td><?= e((string) $productRow['qty']) ?></td>
                                                <td><?= e((string) $productRow['transactions']) ?></td>
                                                <td>Rp <?= e(number_format($productRow['gross'], 0, ',', '.')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="voucher-summary">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total voucher</span>
                                <strong><?= e((string) count($voucherRows)) ?></strong>
                                <small>Voucher di data saat ini</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Voucher aktif</span>
                                <strong><?= e((string) count($activeVoucherRows)) ?></strong>
                                <small>Masih bisa dipakai pelanggan</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Outstanding liability</span>
                                <strong><?= $voucherLiability > 1000 ? 'Rp ' . e(number_format($voucherLiability, 0, ',', '.')) : e((string) $voucherLiability) ?></strong>
                                <small>Estimasi sisa liability voucher aktif</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame analytics-report-chart__frame--compact">
                                <canvas class="js-chart" height="220" data-chart-type="doughnut" data-chart='<?= e($voucherStatusChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Ringkasan voucher</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Voucher</th>
                                            <th>Tipe</th>
                                            <th>Used</th>
                                            <th>Remaining</th>
                                            <th>Status</th>
                                            <th>Expired</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($voucherRows as $voucherRow): ?>
                                            <tr>
                                                <td><?= e($voucherRow['name']) ?></td>
                                                <td><?= e(ucfirst($voucherRow['type'])) ?></td>
                                                <td><?= e((string) $voucherRow['used']) ?></td>
                                                <td><?= e((string) $voucherRow['remaining']) ?></td>
                                                <td><?= e($voucherRow['status']) ?></td>
                                                <td><?= e($voucherRow['expired_at'] !== '' ? date('d M Y', strtotime($voucherRow['expired_at'])) : '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="voucher-active">
                        <div class="analytics-report-kpis">
                            <?php foreach (array_slice($activeVoucherRows, 0, 3) as $voucherRow): ?>
                                <article class="analytics-report-kpi">
                                    <span><?= e($voucherRow['name']) ?></span>
                                    <strong><?= e((string) $voucherRow['remaining']) ?></strong>
                                    <small>Sisa pemakaian • exp <?= e($voucherRow['expired_at'] !== '' ? date('d M Y', strtotime($voucherRow['expired_at'])) : '-') ?></small>
                                </article>
                            <?php endforeach; ?>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($voucherActiveChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Voucher aktif</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Voucher</th>
                                            <th>Code</th>
                                            <th>Tipe</th>
                                            <th>Sisa</th>
                                            <th>Liability</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activeVoucherRows as $voucherRow): ?>
                                            <tr>
                                                <td><?= e($voucherRow['name']) ?></td>
                                                <td><?= e($voucherRow['code']) ?></td>
                                                <td><?= e(ucfirst($voucherRow['type'])) ?></td>
                                                <td><?= e((string) $voucherRow['remaining']) ?></td>
                                                <td><?= $voucherRow['type'] === 'gift' ? 'Rp ' . e(number_format($voucherRow['liability'], 0, ',', '.')) : e((string) $voucherRow['liability']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="voucher-redemption">
                        <div class="analytics-report-kpis">
                            <article class="analytics-report-kpi">
                                <span>Total pemakaian</span>
                                <strong><?= e((string) array_sum(array_map(static fn (array $row): int => (int) ($row['used'] ?? 0), $voucherRows))) ?></strong>
                                <small>Jumlah voucher sudah terpakai</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Voucher expired</span>
                                <strong><?= e((string) count($expiredVoucherRows)) ?></strong>
                                <small>Tidak aktif lagi</small>
                            </article>
                            <article class="analytics-report-kpi">
                                <span>Gift voucher terjual</span>
                                <strong>Rp <?= e(number_format($voucherGiftSales, 0, ',', '.')) ?></strong>
                                <small>Estimasi dari voucher gift yang digunakan</small>
                            </article>
                        </div>

                        <div class="analytics-report-chart">
                            <div class="analytics-report-chart__frame">
                                <canvas class="js-chart" height="180" data-chart-type="bar" data-chart='<?= e($voucherUsageChartPayload) ?>'></canvas>
                            </div>
                        </div>

                        <div class="analytics-report-tables">
                            <div class="analytics-table-block">
                                <h3>Aktivitas penukaran voucher</h3>
                                <table class="sales-table analytics-table">
                                    <thead>
                                        <tr>
                                            <th>Voucher</th>
                                            <th>Used</th>
                                            <th>Sisa</th>
                                            <th>Status</th>
                                            <th>Expired</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($voucherRows as $voucherRow): ?>
                                            <tr>
                                                <td><?= e($voucherRow['name']) ?></td>
                                                <td><?= e((string) $voucherRow['used']) ?></td>
                                                <td><?= e((string) $voucherRow['remaining']) ?></td>
                                                <td><?= e($voucherRow['status']) ?></td>
                                                <td><?= e($voucherRow['expired_at'] !== '' ? date('d M Y', strtotime($voucherRow['expired_at'])) : '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <section class="analytics-report-panel" data-analytics-detail-panel="report-placeholder">
                        <div class="analytics-table-block">
                            <h3>Laporan sedang disiapkan</h3>
                            <p class="analytics-report-placeholder">
                                Contoh implementasi detail baru sudah kami mulai dari <strong>Keuangan</strong>, <strong>Penjualan</strong>, <strong>Agenda</strong>, <strong>Staf</strong>, <strong>Pelanggan</strong>, <strong>Point Loyalitas</strong>, <strong>Voucher</strong>, dan <strong>Inventori</strong>. Kategori lain masih memakai struktur lama dan bisa kita lanjutkan satu per satu setelah ini.
                            </p>
                        </div>
                    </section>
                </div>
                </div>

                <div class="analytics-detail-menu" data-analytics-detail-menu>
                    <button class="analytics-detail-menu__trigger" type="button" aria-label="Pindah sub-laporan" aria-expanded="false" data-analytics-detail-menu-toggle>
                        <i class="bi bi-grid"></i>
                    </button>
                    <div class="analytics-detail-menu__panel" hidden data-analytics-detail-menu-panel>
                        <?php foreach ($reportCards as $report): ?>
                            <div class="analytics-detail-menu__group <?= $report['key'] === 'finance' ? 'is-active' : '' ?>" data-analytics-detail-menu-group="<?= e($report['key']) ?>">
                                <?php foreach ($report['items'] as $item): ?>
                                    <?php
                                    $itemKey = is_array($item) ? (string) ($item['key'] ?? '') : '';
                                    $itemLabel = is_array($item) ? (string) ($item['label'] ?? '') : (string) $item;
                                    ?>
                                    <button class="analytics-detail-menu__item" type="button" data-analytics-detail-menu-item data-report-target="<?= e($report['key']) ?>" data-report-title="<?= e($itemLabel) ?>" data-report-item-key="<?= e($itemKey !== '' ? $itemKey : strtolower(str_replace(' ', '-', $itemLabel))) ?>">
                                        <span><?= e($itemLabel) ?></span>
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            </section>
        </section>
    </div>
</section>

<div class="modal fade" id="analyticsDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-analytics-date-preset is-active" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-analytics-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-analytics-start" type="text" value="<?= e($rangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-analytics-end" type="text" value="<?= e($today->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-analytics-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-analytics-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-analytics-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>
