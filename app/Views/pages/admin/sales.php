<?php
$formatSalesAmount = static function (float $value): string {
    return number_format($value, 2, ',', '.');
};
$formatSalesCount = static function (int $value): string {
    return (string) $value;
};
$dailyTypeStats = [
    'service' => ['sales' => 0, 'refund' => 0, 'gross' => 0.0],
    'class' => ['sales' => 0, 'refund' => 0, 'gross' => 0.0],
    'package' => ['sales' => 0, 'refund' => 0, 'gross' => 0.0],
    'product' => ['sales' => 0, 'refund' => 0, 'gross' => 0.0],
    'voucher' => ['sales' => 0, 'refund' => 0, 'gross' => 0.0],
];
$paymentStats = [];
$grossSalesCount = 0;
$grossSalesAmount = 0.0;
$refundSalesCount = 0;
$refundSalesAmount = 0.0;
$discountedSalesCount = 0;
$discountSalesAmount = 0.0;
$roundingTotal = 0.0;
$tipsTotal = 0.0;

foreach ($transactions as $transaction) {
    $status = strtolower((string) ($transaction['status'] ?? 'paid'));
    $isRefund = $status === 'refund';
    $lineCount = 0;
    $lineGross = 0.0;

    foreach (($transaction['items'] ?? []) as $item) {
        $type = strtolower((string) ($item['type'] ?? 'service'));
        $normalizedType = match ($type) {
            'service' => 'service',
            'class' => 'class',
            'package', 'plan' => 'package',
            'product' => 'product',
            'voucher' => 'voucher',
            default => 'service',
        };

        $quantity = (int) ($item['qty'] ?? 0);
        $amount = $quantity * (float) ($item['price'] ?? 0);
        $lineCount += $quantity;
        $lineGross += $amount;

        if ($isRefund) {
            $dailyTypeStats[$normalizedType]['refund'] += $quantity;
            $dailyTypeStats[$normalizedType]['gross'] -= $amount;
        } else {
            $dailyTypeStats[$normalizedType]['sales'] += $quantity;
            $dailyTypeStats[$normalizedType]['gross'] += $amount;
        }
    }

    $discountValue = (float) ($transaction['discount'] ?? 0);
    $roundingValue = (float) ($transaction['rounding'] ?? 0);
    $tipsValue = (float) ($transaction['tips'] ?? 0);
    $netAmount = $isRefund
        ? $lineGross
        : max(0, $lineGross - $discountValue + $roundingValue + $tipsValue);

    if ($isRefund) {
        $refundSalesCount += $lineCount;
        $refundSalesAmount += $lineGross;
    } else {
        $grossSalesCount += $lineCount;
        $grossSalesAmount += $lineGross;
        $roundingTotal += $roundingValue;
        $tipsTotal += $tipsValue;

        if ($discountValue > 0) {
            $discountedSalesCount++;
            $discountSalesAmount += $discountValue;
        }

        $paymentMethod = strtoupper(trim((string) ($transaction['payment_method'] ?? 'CASH')));
        if ($paymentMethod === '') {
            $paymentMethod = 'CASH';
        }
        if (!isset($paymentStats[$paymentMethod])) {
            $paymentStats[$paymentMethod] = ['collected' => 0.0, 'refund' => 0.0];
        }
        $paymentStats[$paymentMethod]['collected'] += $netAmount;
    }

    if ($isRefund && $netAmount > 0) {
        $paymentMethod = strtoupper(trim((string) ($transaction['payment_method'] ?? 'CASH')));
        if ($paymentMethod === '') {
            $paymentMethod = 'CASH';
        }
        if (!isset($paymentStats[$paymentMethod])) {
            $paymentStats[$paymentMethod] = ['collected' => 0.0, 'refund' => 0.0];
        }
        $paymentStats[$paymentMethod]['refund'] += $netAmount;
    }
}

$netSalesCount = $grossSalesCount - $discountedSalesCount;
$netSalesAmount = $grossSalesAmount - $refundSalesAmount - $discountSalesAmount;
$salesWithRounding = $netSalesAmount + $roundingTotal;
$outstandingTotal = 0.0;
$servicesSalesRows = [
    ['label' => 'Services', 'sales' => $dailyTypeStats['service']['sales'], 'refund' => $dailyTypeStats['service']['refund'], 'gross' => $dailyTypeStats['service']['gross']],
    ['label' => 'Classes', 'sales' => $dailyTypeStats['class']['sales'], 'refund' => $dailyTypeStats['class']['refund'], 'gross' => $dailyTypeStats['class']['gross']],
    ['label' => 'Plan', 'sales' => $dailyTypeStats['package']['sales'], 'refund' => $dailyTypeStats['package']['refund'], 'gross' => $dailyTypeStats['package']['gross']],
    ['label' => 'Products', 'sales' => $dailyTypeStats['product']['sales'], 'refund' => $dailyTypeStats['product']['refund'], 'gross' => $dailyTypeStats['product']['gross']],
    ['label' => 'Vouchers', 'sales' => $dailyTypeStats['voucher']['sales'], 'refund' => $dailyTypeStats['voucher']['refund'], 'gross' => $dailyTypeStats['voucher']['gross']],
    ['label' => 'Sales by Vouchers Redeem', 'sales' => $discountedSalesCount > 0 ? -$discountedSalesCount : 0, 'refund' => 0, 'gross' => -$discountSalesAmount],
    ['label' => 'Gross Total Sales', 'sales' => $grossSalesCount, 'refund' => $refundSalesCount, 'gross' => $grossSalesAmount - $refundSalesAmount],
    ['label' => 'Net Total Sales', 'sales' => $netSalesCount, 'refund' => 0, 'gross' => $netSalesAmount],
    ['label' => 'Total Discount In Sales', 'sales' => 0, 'refund' => 0, 'gross' => $discountSalesAmount],
    ['label' => 'Total Rounding', 'sales' => 0, 'refund' => 0, 'gross' => $roundingTotal, 'emphasis' => true],
    ['label' => 'Total Sales With Rounding', 'sales' => 0, 'refund' => 0, 'gross' => $salesWithRounding, 'emphasis' => true],
    ['label' => 'Total Sales Outstanding', 'sales' => 0, 'refund' => 0, 'gross' => $outstandingTotal, 'muted' => true],
];
$paymentRows = [];
ksort($paymentStats);
foreach ($paymentStats as $method => $paymentRow) {
    $paymentRows[] = [
        'label' => $method,
        'collected' => $paymentRow['collected'],
        'refund' => $paymentRow['refund'],
        'gross' => $paymentRow['collected'] - $paymentRow['refund'],
    ];
}
if ($discountSalesAmount > 0) {
    $paymentRows[] = [
        'label' => 'VOUCHER (Redemption)',
        'collected' => -$discountSalesAmount,
        'refund' => 0.0,
        'gross' => -$discountSalesAmount,
        'emphasis' => true,
    ];
}
$paymentCollectedTotal = array_reduce($paymentRows, static fn (float $sum, array $row): float => $sum + (float) ($row['gross'] ?? 0), 0.0);
$paymentRows[] = [
    'label' => 'Payment collected',
    'collected' => $paymentCollectedTotal,
    'refund' => 0.0,
    'gross' => $paymentCollectedTotal,
    'emphasis' => true,
];
$paymentRows[] = [
    'label' => 'Of which tips',
    'collected' => $tipsTotal,
    'refund' => 0.0,
    'gross' => $tipsTotal,
    'emphasis' => true,
];
$paymentRows[] = [
    'label' => 'Outstanding',
    'collected' => $outstandingTotal,
    'refund' => 0.0,
    'gross' => $outstandingTotal,
    'negative' => true,
];
$hasTransactionChartData = $grossSalesAmount > 0 || $dailyTypeStats['product']['gross'] > 0 || $dailyTypeStats['service']['gross'] > 0;
$hasPaymentChartData = array_reduce($paymentStats, static fn (float $sum, array $row): float => $sum + max(0, (float) ($row['collected'] ?? 0)), 0.0) > 0;
$dailySalesChart = [
    'labels' => ['Services', 'Products', 'Gross Total Sales'],
    'datasets' => [[
        'data' => [
            max(0, $dailyTypeStats['service']['gross']),
            max(0, $dailyTypeStats['product']['gross']),
            max(0, $grossSalesAmount - $refundSalesAmount),
        ],
        'backgroundColor' => ['#86a8eb', '#466dc8', '#314d86'],
        'borderWidth' => 0,
        'hoverOffset' => 6,
    ]],
];
$paymentChartLabels = [];
$paymentChartValues = [];
$paymentChartColors = ['#7b2230', '#d9425d', '#ef6b79', '#f4a3ae'];
foreach ($paymentStats as $method => $paymentRow) {
    $collected = max(0, (float) ($paymentRow['collected'] ?? 0));
    if ($collected <= 0) {
        continue;
    }
    $paymentChartLabels[] = $method;
    $paymentChartValues[] = $collected;
}
$paymentChart = [
    'labels' => $paymentChartLabels,
    'datasets' => [[
        'data' => $paymentChartValues,
        'backgroundColor' => array_slice($paymentChartColors, 0, count($paymentChartValues)),
        'borderWidth' => 0,
        'hoverOffset' => 6,
    ]],
];
$cashFlowTotal = array_reduce($cash_movements, fn (float $sum, array $movement): float => $sum + (($movement['type'] === 'cash_in' ? 1 : -1) * $movement['amount']), 0.0);
$dailySalesDateLabel = '09 April 2026';
$summaryPrintedAt = date('d M Y, H:i');
$registerPaymentLines = [];
foreach ($paymentRows as $row) {
    $label = (string) ($row['label'] ?? '');
    if (in_array($label, ['Payment collected', 'Of which tips', 'Outstanding'], true)) {
        continue;
    }
    $registerPaymentLines[] = [
        'label' => $label,
        'value' => $formatSalesAmount((float) ($row['gross'] ?? 0)),
    ];
}
$registerSummaryPayload = [
    'title' => 'Register Summary',
    'sections' => [
        [
            ['label' => 'Kas masuk/keluar', 'value' => $formatSalesAmount($cashFlowTotal)],
            ['label' => 'Total', 'value' => $formatSalesAmount($paymentCollectedTotal + $cashFlowTotal)],
        ],
        [
            ['label' => 'Sub total', 'value' => $formatSalesAmount($grossSalesAmount)],
            ['label' => 'Item Diskon', 'value' => $formatSalesAmount($discountSalesAmount)],
            ['label' => 'Pajak', 'value' => $formatSalesAmount(0)],
            ['label' => 'Tips', 'value' => $formatSalesAmount($tipsTotal)],
            ['label' => 'Pembulatan', 'value' => $formatSalesAmount($roundingTotal)],
            ['label' => 'Sisa', 'value' => $formatSalesAmount($outstandingTotal)],
            ['label' => 'Total diskon penjualan', 'value' => $formatSalesAmount($discountSalesAmount)],
        ],
        $registerPaymentLines,
        [
            ['label' => 'Kas Penjualan', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Kas Masuk', 'value' => $formatSalesAmount(max(0, $cashFlowTotal))],
            ['label' => 'Kas Keluar', 'value' => $formatSalesAmount(min(0, $cashFlowTotal))],
        ],
        [
            ['label' => 'Total kas masuk', 'value' => $formatSalesAmount(max(0, $cashFlowTotal))],
            ['label' => 'Total kas keluar', 'value' => $formatSalesAmount(abs(min(0, $cashFlowTotal)))],
            ['label' => 'Total kas penjualan', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Total jumlah diharapkan', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Total kas diharapkan', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Total aktual', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Total perbedaan', 'value' => $formatSalesAmount(0)],
            ['label' => 'Total kas aktual', 'value' => $formatSalesAmount($paymentCollectedTotal)],
            ['label' => 'Perbedaan total kas', 'value' => $formatSalesAmount(0)],
            ['label' => 'Perbaikan total kas aktual', 'value' => $formatSalesAmount(0)],
            ['label' => 'Perbaikan perbedaan total kas', 'value' => $formatSalesAmount(0)],
            ['label' => 'Perbaikan total aktual', 'value' => $formatSalesAmount(0)],
        ],
    ],
];
$transactionSummaryPayload = [
    'title' => 'Transaction Summary',
    'summary_lines' => [
        ['label' => 'Total penjualan kotor', 'count' => $formatSalesCount($grossSalesCount), 'value' => $formatSalesAmount($grossSalesAmount)],
        ['label' => 'Total kekurangan penjualan', 'count' => '', 'value' => $formatSalesAmount($outstandingTotal)],
        ['label' => 'Pengembalian penjualan', 'count' => $formatSalesCount($refundSalesCount), 'value' => $formatSalesAmount($refundSalesAmount)],
        ['label' => 'Penjualan dengan penukaran voucher', 'count' => '', 'value' => $formatSalesAmount($discountSalesAmount)],
        ['label' => 'Diskon di penjualan', 'count' => '', 'value' => $formatSalesAmount($discountSalesAmount)],
        ['label' => 'Total penjualan bersih', 'count' => $formatSalesCount($netSalesCount), 'value' => $formatSalesAmount($netSalesAmount)],
    ],
    'item_lines' => [
        ['label' => 'Layanan', 'count' => $formatSalesCount($dailyTypeStats['service']['sales']), 'value' => $formatSalesAmount($dailyTypeStats['service']['gross'])],
        ['label' => 'Pengembalian layanan', 'count' => $formatSalesCount($dailyTypeStats['service']['refund']), 'value' => $formatSalesAmount(0 - max(0, $refundSalesAmount > 0 ? abs(min(0.0, $dailyTypeStats['service']['gross'])) : 0))],
        ['label' => 'Kelas', 'count' => $formatSalesCount($dailyTypeStats['class']['sales']), 'value' => $formatSalesAmount($dailyTypeStats['class']['gross'])],
        ['label' => 'Pengembalian Kelas', 'count' => $formatSalesCount($dailyTypeStats['class']['refund']), 'value' => $formatSalesAmount(0)],
        ['label' => 'Produk', 'count' => $formatSalesCount($dailyTypeStats['product']['sales']), 'value' => $formatSalesAmount($dailyTypeStats['product']['gross'])],
        ['label' => 'Pengembalian Produk', 'count' => $formatSalesCount($dailyTypeStats['product']['refund']), 'value' => $formatSalesAmount(0)],
        ['label' => 'Voucher', 'count' => $formatSalesCount($dailyTypeStats['voucher']['sales']), 'value' => $formatSalesAmount($dailyTypeStats['voucher']['gross'])],
        ['label' => 'Pengembalian Voucher', 'count' => $formatSalesCount($dailyTypeStats['voucher']['refund']), 'value' => $formatSalesAmount(0)],
        ['label' => 'Plan Kelas', 'count' => $formatSalesCount($dailyTypeStats['package']['sales']), 'value' => $formatSalesAmount($dailyTypeStats['package']['gross'])],
        ['label' => 'Pengembalian Plan Kelas', 'count' => $formatSalesCount($dailyTypeStats['package']['refund']), 'value' => $formatSalesAmount(0)],
    ],
];
$buildSalesReceiptText = static function (array $summary, bool $includeItems = false) use ($summaryPrintedAt): string {
    $pad = static function (string $label, string $middle = '', string $value = ''): string {
        $leftColumn = trim($label . ($middle !== '' ? ' ' . $middle : ''));
        $spaces = max(2, 30 - strlen($leftColumn) - strlen($value));

        return rtrim($leftColumn . str_repeat(' ', $spaces) . $value);
    };

    $lines = [
        '       Star Salon - Star Salon',
        '          ' . ($summary['title'] ?? 'Summary'),
        '     Printed on ' . $summaryPrintedAt,
    ];

    foreach (($summary['summary_lines'] ?? []) as $item) {
        $lines[] = '';
        $lines[] = '--------------------------------';
        $lines[] = $pad((string) ($item['label'] ?? ''), (string) ($item['count'] ?? ''), (string) ($item['value'] ?? ''));
    }

    foreach (($summary['sections'] ?? []) as $section) {
        $lines[] = '';
        $lines[] = '--------------------------------';
        foreach ($section as $item) {
            $lines[] = $pad((string) ($item['label'] ?? ''), (string) ($item['count'] ?? ''), (string) ($item['value'] ?? ''));
        }
    }

    if ($includeItems) {
        foreach (($summary['item_lines'] ?? []) as $index => $item) {
            if ($index === 0) {
                $lines[] = '';
                $lines[] = '--------------------------------';
            }
            $lines[] = $pad((string) ($item['label'] ?? ''), (string) ($item['count'] ?? ''), (string) ($item['value'] ?? ''));
        }
    }

    $lines[] = '';
    $lines[] = '         Powered by Zenwel';

    return implode("\n", $lines);
};
$defaultRegisterReceiptText = $buildSalesReceiptText($registerSummaryPayload, false);

$staffDirectory = [];
foreach (($staff ?? []) as $staffMember) {
    $staffId = (int) ($staffMember['id'] ?? 0);
    if ($staffId <= 0) {
        continue;
    }
    $staffDirectory[$staffId] = [
        'name' => (string) ($staffMember['name'] ?? ('Staff #' . $staffId)),
        'location' => (string) ($staffMember['location_name'] ?? 'Star Salon'),
    ];
}

$serviceDirectory = [];
foreach (($services ?? []) as $service) {
    $serviceId = (int) ($service['id'] ?? 0);
    if ($serviceId <= 0) {
        continue;
    }
    $serviceDirectory[$serviceId] = [
        'name' => (string) ($service['name'] ?? ('Service #' . $serviceId)),
        'duration' => (int) ($service['duration'] ?? 60),
        'price' => (float) ($service['price'] ?? 0),
    ];
}

$customerDirectory = [];
foreach (($customers ?? []) as $customer) {
    $customerId = (int) ($customer['id'] ?? 0);
    if ($customerId <= 0) {
        continue;
    }
    $customerDirectory[$customerId] = (string) ($customer['name'] ?? ('Customer #' . $customerId));
}

$todayDate = new DateTimeImmutable('today');
$serviceRangeEnd = $todayDate;
$serviceRangeStart = $todayDate->modify('-30 days');
$serviceRangeLabel = sprintf(
    'Bulan kemarin, %s - %s',
    $serviceRangeStart->format('j M Y'),
    $serviceRangeEnd->format('j M Y')
);
$serviceSearchOptions = [
    'ref' => 'Ref No.',
    'customer' => 'Pelanggan',
    'item' => 'Nama Item',
    'invoice' => 'Nomor Faktur',
];
$invoiceRangeEnd = $todayDate;
$invoiceRangeStart = $todayDate->modify('-6 days');
$invoiceRangeLabel = sprintf(
    '7 hari sebelumnya, %s - %s',
    $invoiceRangeStart->format('j M Y'),
    $invoiceRangeEnd->format('j M Y')
);
$invoiceSearchOptions = [
    'invoice' => 'Nomor Faktur',
    'customer' => 'Nama Pelanggan',
];
$voucherRangeEnd = $todayDate;
$voucherRangeStart = $todayDate->modify('-30 days');
$voucherRangeLabel = sprintf(
    'Diterbitkan, %s - %s',
    $voucherRangeStart->format('j M Y'),
    $voucherRangeEnd->format('j M Y')
);
$cashDrawerRangeEnd = $todayDate;
$cashDrawerRangeStart = $todayDate->setDate((int) $todayDate->format('Y'), 1, 1);
$cashDrawerRangeLabel = sprintf(
    'Tahun ini, %s - %s',
    $cashDrawerRangeStart->format('j M Y'),
    $cashDrawerRangeEnd->format('j M Y')
);
$voucherSearchOptions = [
    'name' => 'Nama Voucher',
    'customer' => 'Nama Pelanggan',
    'invoice' => 'Faktur',
    'code' => 'Kode',
];

$invoiceByBookingId = [];
foreach ($transactions as $transaction) {
    $bookingId = $transaction['booking_id'] ?? null;
    if ($bookingId === null) {
        continue;
    }
    $invoiceByBookingId[(int) $bookingId] = 'INV-' . substr((string) ($transaction['reference'] ?? ''), 4);
}

$normalizeServiceStatus = static function (string $status): string {
    $statusKey = strtoupper(trim(str_replace(['-', '_'], ' ', $status)));

    return match ($statusKey) {
        'NEW' => 'NEW',
        'CONFIRMED' => 'CONFIRMED',
        'ARRIVED' => 'ARRIVED',
        'STARTED' => 'STARTED',
        'COMPLETED' => 'COMPLETED',
        'NO SHOW', 'NOSHOW' => 'NO SHOW',
        'CANCELLED', 'CANCELED' => 'CANCELLED',
        default => 'NEW',
    };
};

$formatDurationLabel = static function (int $minutes): string {
    $minutes = max(0, $minutes);
    $hours = intdiv($minutes, 60);
    $restMinutes = $minutes % 60;

    if ($hours > 0 && $restMinutes > 0) {
        return sprintf('%dh %02dmin', $hours, $restMinutes);
    }

    if ($hours > 0) {
        return sprintf('%dh', $hours);
    }

    return sprintf('%dmin', $restMinutes);
};

$serviceRows = [];
foreach (($bookings ?? []) as $booking) {
    $staffId = (int) ($booking['staff_id'] ?? 0);
    $staffProfile = $staffDirectory[$staffId] ?? null;
    $customerId = (int) ($booking['customer_id'] ?? 0);
    $bookingStatus = $normalizeServiceStatus((string) ($booking['status'] ?? 'new'));
    $bookingItems = $booking['service_items'] ?? [];
    $bookingStart = (string) ($booking['start_at'] ?? '');
    $bookingDate = substr($bookingStart, 0, 10);
    $bookingUpdatedRaw = (string) ($booking['updated_at'] ?? $bookingStart);
    $bookingUpdated = $bookingUpdatedRaw !== '' ? date('d-M-Y H:i:s', strtotime($bookingUpdatedRaw)) : '-';
    $bookingProductsJson = json_encode(($booking['products'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    foreach ($bookingItems as $serviceItem) {
        $serviceId = (int) ($serviceItem['service_id'] ?? 0);
        $serviceProfile = $serviceDirectory[$serviceId] ?? null;
        $itemStartAt = (string) ($serviceItem['start_at'] ?? $bookingStart);
        $itemEndAt = (string) ($serviceItem['end_at'] ?? $bookingStart);
        $durationMinutes = (int) ($serviceItem['duration'] ?? ($serviceProfile['duration'] ?? 60));
        $itemName = (string) ($serviceProfile['name'] ?? ('Service #' . $serviceId));
        $itemPrice = (float) ($serviceItem['price'] ?? ($serviceProfile['price'] ?? 0));

        $serviceRows[] = [
            'ref' => (string) ($booking['reference'] ?? '-'),
            'customer_id' => $customerId,
            'customer' => $customerId > 0
                ? (string) ($customerDirectory[$customerId] ?? ('Customer #' . $customerId))
                : 'Walk-In',
            'staff' => (string) ($staffProfile['name'] ?? ('Staff #' . $staffId)),
            'item' => $itemName,
            'resource' => (string) (($serviceItem['resource_name'] ?? '') ?: '-'),
            'date' => substr($itemStartAt, 0, 10),
            'time' => substr($itemStartAt, 11, 5) . ' - ' . substr($itemEndAt, 11, 5),
            'start_time' => substr($itemStartAt, 11, 5),
            'duration' => $formatDurationLabel($durationMinutes),
            'duration_minutes' => $durationMinutes,
            'location' => (string) ($staffProfile['location'] ?? 'Star Salon'),
            'price' => $itemPrice,
            'status' => $bookingStatus,
            'invoice' => (string) ($invoiceByBookingId[(int) ($booking['id'] ?? 0)] ?? (string) ($booking['reference'] ?? '-')),
            'invoice_count' => isset($invoiceByBookingId[(int) ($booking['id'] ?? 0)]) ? 1 : 0,
            'staff_id' => $staffId,
            'updated_at' => $bookingUpdated,
            'notes' => (string) ($booking['notes'] ?? ''),
            'booking_id' => (int) ($booking['id'] ?? 0),
            'products_json' => is_string($bookingProductsJson) ? $bookingProductsJson : '[]',
            'cancel_reason' => (string) ($booking['cancel_reason'] ?? ''),
        ];
    }
}

$serviceStatusClass = static function (string $status): string {
    return match (strtoupper(trim($status))) {
        'ARRIVED' => 'is-arrived',
        'COMPLETED' => 'is-completed',
        'STARTED' => 'is-started',
        'NO SHOW', 'NOSHOW' => 'is-no-show',
        'NEW' => 'is-new',
        'CONFIRMED' => 'is-confirmed',
        'CANCELLED', 'CANCELED' => 'is-cancelled',
        default => 'is-default',
    };
};
$salesProductCatalogRows = array_values(array_map(static function (array $product): array {
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
}, $products ?? []));
$voucherStatusClass = static function (string $status): string {
    return match (strtoupper(trim($status))) {
        'VALID' => 'is-confirmed',
        'EXPIRED' => 'is-started',
        default => 'is-default',
    };
};
$normalizeVoucherStatus = static function (?string $status, ?string $expiredAt) use ($todayDate): string {
    $rawStatus = strtoupper(trim((string) $status));
    $expiredDate = $expiredAt ? date('Y-m-d', strtotime((string) $expiredAt)) : '';
    if ($rawStatus === 'EXPIRED' || $rawStatus === 'KADALUARSA') {
        return 'Expired';
    }
    if ($expiredDate !== '' && $expiredDate < $todayDate->format('Y-m-d')) {
        return 'Expired';
    }
    return 'Valid';
};
$normalizeInvoiceStatus = static function (string $status): string {
    return match (strtoupper(trim($status))) {
        'PAID' => 'PAID',
        'VOID', 'VOIDED', 'CANCELLED', 'CANCELED' => 'VOIDED',
        default => 'UNPAID',
    };
};
$invoiceStatusClass = static function (string $status): string {
    return match (strtoupper(trim($status))) {
        'PAID' => 'is-completed',
        'VOIDED' => 'is-voided',
        default => 'is-started',
    };
};

$invoiceRows = array_map(static function (array $transaction) use ($customerDirectory, $normalizeInvoiceStatus): array {
    $gross = array_reduce($transaction['items'], fn (float $sum, array $item): float => $sum + ($item['price'] * $item['qty']), 0.0);
    $status = $normalizeInvoiceStatus((string) ($transaction['status'] ?? 'PAID'));
    $customerId = (int) ($transaction['customer_id'] ?? 0);

    return [
        'invoice' => 'INV-' . substr($transaction['reference'], 4),
        'customer_id' => $customerId,
        'customer' => $customerId > 0
            ? (string) ($customerDirectory[$customerId] ?? ('Customer #' . $customerId))
            : 'Walk-In',
        'date' => substr($transaction['date'], 0, 10),
        'time' => substr($transaction['date'], 11, 5),
        'location' => 'Star Salon',
        'tips' => 0,
        'gross' => $gross,
        'status' => $status,
        'payment_method' => strtoupper($transaction['payment_method'] ?? 'Cash'),
        'items' => array_map(static fn (array $item): array => [
            'name' => $item['name'],
            'qty' => (int) $item['qty'],
            'price' => (float) $item['price'],
            'staff' => 'Rayhan Doni Pramana',
            'time' => substr($transaction['date'], 11, 5),
        ], $transaction['items']),
    ];
}, $transactions);

$salesInvoicesReturnTo = '/sales?tab=invoices';
$salesServicesReturnTo = '/sales?tab=services';
$salesVouchersReturnTo = '/sales?tab=vouchers';

$voucherRows = array_map(static function (array $voucher, int $index) use ($invoiceRows, $customerDirectory, $normalizeVoucherStatus): array {
    $rawTotal = $voucher['price_value'] ?? $voucher['editor_value'] ?? $voucher['value'] ?? 0;
    $customerNames = array_values($customerDirectory);
    $linkedInvoice = $invoiceRows[$index % max(1, count($invoiceRows))] ?? null;
    $issuedDate = !empty($linkedInvoice['date'])
        ? (string) $linkedInvoice['date']
        : (!empty($voucher['expired_at']) ? (string) date('Y-m-d', strtotime((string) $voucher['expired_at'] . ' -30 days')) : '');
    $customerName = (string) ($linkedInvoice['customer'] ?? ($customerNames[$index % max(1, count($customerNames))] ?? 'Walk-In'));
    $usageLimit = max(1, (int) ($voucher['usage_limit'] ?? 1));
    $usedCount = (int) ($voucher['used'] ?? 0);
    $remainingCount = max(0, $usageLimit - $usedCount);
    $isGiftVoucher = strtolower((string) ($voucher['type'] ?? $voucher['type_key'] ?? '')) === 'gift';
    $numericTotal = is_numeric($rawTotal) ? (float) $rawTotal : 0.0;
    $status = $normalizeVoucherStatus((string) ($voucher['status'] ?? ''), (string) ($voucher['expired_at'] ?? ''));

    return [
        'name' => $voucher['name'],
        'expired' => $voucher['expired_at'],
        'issued' => $issuedDate,
        'invoice' => (string) ($linkedInvoice['invoice'] ?? ('INV-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT))),
        'customer_id' => (int) ($linkedInvoice['customer_id'] ?? 0),
        'customer' => $customerName,
        'code' => $voucher['code'],
        'total' => $numericTotal,
        'used' => $usedCount,
        'remaining' => $remainingCount,
        'display_total' => $isGiftVoucher ? money($numericTotal) : ($usageLimit . 'x'),
        'display_used' => $isGiftVoucher ? money($usedCount > 0 ? ($numericTotal / $usageLimit) * $usedCount : 0.0) : ($usedCount . 'x'),
        'display_remaining' => $isGiftVoucher ? money($remainingCount > 0 ? ($numericTotal / $usageLimit) * $remainingCount : 0.0) : ($remainingCount . 'x'),
        'status' => $status,
        'invoice_date' => (string) ($linkedInvoice['date'] ?? $issuedDate),
        'invoice_time' => (string) ($linkedInvoice['time'] ?? ''),
        'location' => (string) ($linkedInvoice['location'] ?? 'Star Salon'),
        'gross' => (float) ($linkedInvoice['gross'] ?? 0),
        'payment_method' => (string) ($linkedInvoice['payment_method'] ?? 'CASH'),
        'invoice_status' => (string) ($linkedInvoice['status'] ?? 'PAID'),
        'items' => $linkedInvoice['items'] ?? [],
    ];
}, $vouchers, array_keys($vouchers));

$cashDrawerRows = array_map(static function (array $drawer, int $index) use ($invoiceRows): array {
    $status = $drawer['status'] === 'Sesuai' ? 'Buka' : 'Tutup';
    $transactionSamples = array_slice($invoiceRows, $index * 3, 5);
    if ($transactionSamples === []) {
        $transactionSamples = array_slice($invoiceRows, 0, 5);
    }
    $detailRows = [];
    if ($status === 'Buka') {
        $detailRows[] = [
            'datetime' => '24 May 2026, 06:29 PM',
            'type' => 'Open Register',
            'payment' => '-',
            'author' => 'Rayhan Doni Pramana',
            'amount' => 500000,
            'note' => '-',
        ];
    }
    foreach ($transactionSamples as $sample) {
        $detailRows[] = [
            'datetime' => date('d M Y, h:i A', strtotime(($sample['date'] ?? date('Y-m-d')) . ' ' . ($sample['time'] ?? '10:00'))),
            'type' => '#' . preg_replace('/^INV-/', '', (string) ($sample['invoice'] ?? '0')),
            'payment' => (string) ($sample['payment_method'] ?? 'CASH'),
            'author' => 'Rayhan Doni Pramana',
            'amount' => (float) ($sample['gross'] ?? 0),
            'note' => (string) ($sample['customer'] ?? '-'),
        ];
    }
    $summaryRows = [
        ['label' => 'Total kas masuk', 'value' => (float) $drawer['expected']],
        ['label' => 'Total kas terhitung', 'value' => (float) $drawer['actual']],
        ['label' => 'Selisih register', 'value' => (float) $drawer['expected'] - (float) $drawer['actual']],
    ];

    return [
        'id' => 'drawer-' . ($index + 1),
        'date' => '05 Mar 2026, 11:42 PM',
        'staff' => 'Rayhan Doni Pramana',
        'expected' => $drawer['expected'],
        'actual' => $drawer['actual'],
        'status' => $status,
        'opened_at' => $status === 'Buka' ? '24 May 2026, 06:29 PM' : '05 Mar 2026, 11:42 PM',
        'closed_at' => $status === 'Buka' ? '' : '24 May 2026, 06:28 PM',
        'closed_by' => $status === 'Buka' ? '' : 'Rayhan Doni Pramana',
        'note' => '-',
        'detail_rows' => $detailRows,
        'summary_rows' => $summaryRows,
    ];
}, $cash_drawers, array_keys($cash_drawers));

?>

<section class="sales-shell js-sales-shell"
         data-calendar-url="<?= e(url('/calendar?modal=agenda')) ?>"
         data-products-catalog="<?= e(json_encode($salesProductCatalogRows, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>">
    <div class="sales-tabs">
        <button class="sales-tab is-active" type="button" data-sales-tab="daily">Penjualan Harian</button>
        <button class="sales-tab" type="button" data-sales-tab="services">Layanan</button>
        <button class="sales-tab" type="button" data-sales-tab="invoices">Faktur</button>
        <button class="sales-tab" type="button" data-sales-tab="vouchers">Voucher</button>
    </div>

    <div class="sales-tab-panels">
        <section class="sales-panel is-active" data-sales-panel="daily">
            <div class="sales-toolbar">
                <div class="sales-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i><span>Star Salon</span><i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item is-active" type="button">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Export</span><i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item" type="button" data-sales-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-sales-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-sales-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-sales-export="csv">CSV</button>
                        </div>
                    </div>
                    <button class="dashboard-filter sales-btn-print js-sales-print-open" type="button" data-bs-toggle="modal" data-bs-target="#salesSummaryModal"><i class="bi bi-printer"></i><span>Cetak Ringkasan</span></button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter dashboard-filter--wide sales-date-button js-sales-date-button" type="button">
                        <i class="bi bi-calendar3"></i><span><?= e($dailySalesDateLabel) ?></span>
                    </button>
                    <input class="sales-date-picker js-sales-date-picker" type="date" value="2026-04-09" tabindex="-1" aria-hidden="true">
                </div>
            </div>

            <div class="sales-grid-two sales-grid-two--daily">
                <div>
                    <h2 class="sales-section-title">Transaksi</h2>
                    <div class="sales-table-card">
                        <table class="sales-table sales-table--daily">
                            <thead>
                                <tr>
                                    <th>Tipe Item</th>
                                    <th>Total Penjualan</th>
                                    <th>Jumlah Pengembalian</th>
                                    <th>Pendapatan Kotor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($servicesSalesRows as $row): ?>
                                    <tr class="<?= !empty($row['emphasis']) ? 'sales-table__row--emphasis' : (!empty($row['muted']) ? 'sales-table__row--muted' : '') ?>">
                                        <td><?= e($row['label']) ?></td>
                                        <td><?= e($formatSalesCount((int) $row['sales'])) ?></td>
                                        <td><?= e($formatSalesCount((int) $row['refund'])) ?></td>
                                        <td><?= e($formatSalesAmount((float) $row['gross'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h2 class="sales-section-title">Pendapatan Kotor</h2>
                    <?php if ($hasTransactionChartData): ?>
                        <div class="sales-chart-card">
                            <div class="sales-chart-card__canvas">
                                <canvas
                                    class="js-chart sales-donut-chart"
                                    data-chart-type="doughnut"
                                    data-chart='<?= e(json_encode($dailySalesChart, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'
                                ></canvas>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="sales-empty-chart">
                            <div class="sales-empty-chart__icon">
                                <span></span><span></span><span></span>
                            </div>
                            <div class="sales-empty-chart__text">No Result</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sales-grid-two sales-grid-two--daily sales-grid-two--secondary">
                <div>
                    <h2 class="sales-section-title">Pergerakan Kas</h2>
                    <div class="sales-table-card">
                        <table class="sales-table sales-table--daily">
                            <thead>
                                <tr>
                                    <th>Tipe Pembayaran</th>
                                    <th>Terkumpul</th>
                                    <th>Pengembalian</th>
                                    <th>Pendapatan Kotor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentRows as $row): ?>
                                    <tr class="<?= !empty($row['negative']) ? 'sales-table__row--negative' : (!empty($row['emphasis']) ? 'sales-table__row--emphasis' : '') ?>">
                                        <td><?= e($row['label']) ?></td>
                                        <td><?= e($formatSalesAmount((float) $row['collected'])) ?></td>
                                        <td><?= e($formatSalesAmount((float) $row['refund'])) ?></td>
                                        <td><?= e($formatSalesAmount((float) $row['gross'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>
                    <h2 class="sales-section-title">Pembayaran</h2>
                    <?php if ($hasPaymentChartData): ?>
                        <div class="sales-chart-card sales-chart-card--payment">
                            <div class="sales-chart-card__canvas">
                                <canvas
                                    class="js-chart sales-donut-chart sales-donut-chart--payment"
                                    data-chart-type="doughnut"
                                    data-chart='<?= e(json_encode($paymentChart, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'
                                ></canvas>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="sales-empty-chart">
                            <div class="sales-empty-chart__icon">
                                <span></span><span></span><span></span>
                            </div>
                            <div class="sales-empty-chart__text">No Result</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="services">
            <div class="sales-toolbar sales-toolbar--services">
                <div class="sales-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i><span data-sales-services-shop-label>Star Salon</span><i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item is-active" type="button" data-sales-services-shop-option="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <div class="dropdown">
                        <button class="dashboard-filter sales-toolbar-dropdown sales-toolbar-dropdown--staff" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span data-sales-services-staff-label>Semua Staff</span><i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item is-active" type="button" data-sales-services-staff-option="all">Semua Staff</button>
                            <?php foreach (($staff ?? []) as $staffMember): ?>
                                <button class="dropdown-item" type="button" data-sales-services-staff-option="<?= e((string) ($staffMember['id'] ?? 0)) ?>">
                                    <?= e((string) ($staffMember['name'] ?? 'Staff')) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="dashboard-filter dashboard-filter--wide js-sales-services-date-open" type="button" data-bs-toggle="modal" data-bs-target="#salesServicesDateFilterModal">
                        <i class="bi bi-calendar3"></i><span data-sales-services-range-label><?= e($serviceRangeLabel) ?></span>
                    </button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <div class="dropdown">
                        <button class="dashboard-filter sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Export</span><i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item" type="button" data-sales-services-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-sales-services-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-sales-services-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-sales-services-export="csv">CSV</button>
                        </div>
                    </div>
                    <div class="sales-search-combo">
                        <div class="dropdown">
                            <button class="dashboard-filter sales-search-select sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span data-sales-services-search-label>Ref No.</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu sales-toolbar-menu">
                                <?php foreach ($serviceSearchOptions as $searchKey => $searchLabel): ?>
                                    <button class="dropdown-item <?= $searchKey === 'ref' ? 'is-active' : '' ?>" type="button" data-sales-services-search-option="<?= e($searchKey) ?>">
                                        <?= e($searchLabel) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <label class="sales-search-field sales-search-field--input">
                            <input class="js-sales-services-search" type="search" placeholder="Ketik kata kunci" autocomplete="off">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide sales-table-card--services">
                <div class="sales-table-scroll sales-table-scroll--services">
                    <table class="sales-table sales-table--wide sales-table--services">
                        <thead>
                            <tr>
                                <th class="sales-table__sticky-left">Ref No.</th>
                                <th>Pelanggan</th>
                                <th>Staff</th>
                                <th>Nama Item</th>
                                <th>Sumberdaya</th>
                                <th>Tanggal</th>
                                <th>Waktu</th>
                                <th>Durasi</th>
                                <th>Lokasi</th>
                                <th>Harga</th>
                                <th class="sales-table__sticky-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($serviceRows === []): ?>
                                <tr><td colspan="11" class="sales-no-data">No Data</td></tr>
                            <?php else: ?>
                                <?php foreach ($serviceRows as $row): ?>
                                    <tr class="js-sales-services-row"
                                        data-staff-id="<?= e((string) $row['staff_id']) ?>"
                                        data-date="<?= e($row['date']) ?>"
                                        data-search-ref="<?= e($row['ref']) ?>"
                                        data-search-customer="<?= e($row['customer']) ?>"
                                        data-search-item="<?= e($row['item']) ?>"
                                        data-search-invoice="<?= e($row['invoice']) ?>"
                                        data-ref="<?= e($row['ref']) ?>"
                                        data-booking-id="<?= e((string) ($row['booking_id'] ?? 0)) ?>"
                                        data-customer-id="<?= e((string) ($row['customer_id'] ?? 0)) ?>"
                                        data-customer="<?= e($row['customer']) ?>"
                                        data-staff="<?= e($row['staff']) ?>"
                                        data-item="<?= e($row['item']) ?>"
                                        data-resource="<?= e($row['resource']) ?>"
                                        data-location="<?= e($row['location']) ?>"
                                        data-status="<?= e($row['status']) ?>"
                                        data-start-time="<?= e($row['start_time']) ?>"
                                        data-duration-minutes="<?= e((string) $row['duration_minutes']) ?>"
                                        data-price="<?= e((string) $row['price']) ?>"
                                        data-invoice-count="<?= e((string) ($row['invoice_count'] ?? 0)) ?>"
                                        data-updated-at="<?= e($row['updated_at']) ?>"
                                        data-notes="<?= e($row['notes']) ?>"
                                        data-products="<?= e($row['products_json']) ?>"
                                        data-cancel-reason="<?= e($row['cancel_reason']) ?>">
                                        <td class="sales-table__sticky-left">
                                            <button class="sales-ref-code sales-ref-code--button js-sales-service-open" type="button">
                                                <span><?= e($row['ref']) ?></span>
                                                <small><i class="bi bi-file-earmark-text"></i><?= e((string) ($row['invoice_count'] ?? 0)) ?></small>
                                            </button>
                                        </td>
                                        <td>
                                            <?php if (($row['customer_id'] ?? 0) > 0 && strcasecmp($row['customer'], 'Walk-In') !== 0): ?>
                                                <a
                                                    class="sales-customer-link js-sales-customer-link"
                                                    href="<?= e(url('/customers') . '?customer_id=' . rawurlencode((string) $row['customer_id']) . '&return_to=' . rawurlencode($salesServicesReturnTo)) ?>"
                                                ><?= e($row['customer']) ?></a>
                                            <?php else: ?>
                                                <span class="sales-customer-name sales-customer-name--walkin"><?= e($row['customer']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($row['staff']) ?></td>
                                        <td><?= e($row['item']) ?></td>
                                        <td><?= e($row['resource']) ?></td>
                                        <td><?= e($row['date']) ?></td>
                                        <td><?= e($row['time']) ?></td>
                                        <td><?= e($row['duration']) ?></td>
                                        <td><?= e($row['location']) ?></td>
                                        <td><?= money($row['price']) ?></td>
                                        <td class="sales-table__sticky-right">
                                            <span class="sales-status-pill <?= e($serviceStatusClass($row['status'])) ?>"><?= e($row['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sales-pagination sales-pagination--services sales-pagination--fixed" data-sales-pagination="services">
                    <div class="sales-pagination__meta">Total <span class="js-sales-services-total"><?= e((string) count($serviceRows)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-sales-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-sales-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-sales-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-sales-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-sales-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="invoices">
            <div class="sales-toolbar sales-toolbar--services sales-toolbar--invoices">
                <div class="sales-toolbar__group">
                    <div class="dropdown">
                        <button class="dashboard-filter dashboard-filter--shop sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shop"></i><span data-sales-invoices-shop-label>Star Salon</span><i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item is-active" type="button" data-sales-invoices-shop-option="Star Salon">Star Salon</button>
                        </div>
                    </div>
                    <label class="sales-backdate js-sales-invoices-backdate">
                        <span>Backdate</span>
                        <input class="js-sales-invoices-backdate-input" type="checkbox">
                    </label>
                    <button class="dashboard-filter dashboard-filter--wide js-sales-invoices-date-open" type="button" data-bs-toggle="modal" data-bs-target="#salesInvoicesDateFilterModal">
                        <i class="bi bi-calendar3"></i><span data-sales-invoices-range-label><?= e($invoiceRangeLabel) ?></span>
                    </button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <div class="dropdown">
                        <button class="dashboard-filter sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Export</span><i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item" type="button" data-sales-invoices-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-sales-invoices-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-sales-invoices-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-sales-invoices-export="csv">CSV</button>
                        </div>
                    </div>
                    <div class="sales-search-combo">
                        <div class="dropdown">
                            <button class="dashboard-filter sales-search-select sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span data-sales-invoices-search-label>Nomor Faktur</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu sales-toolbar-menu">
                                <?php foreach ($invoiceSearchOptions as $searchKey => $searchLabel): ?>
                                    <button class="dropdown-item <?= $searchKey === 'invoice' ? 'is-active' : '' ?>" type="button" data-sales-invoices-search-option="<?= e($searchKey) ?>">
                                        <?= e($searchLabel) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <label class="sales-search-field sales-search-field--input">
                            <input class="js-sales-invoices-search" type="search" placeholder="Ketik kata kunci" autocomplete="off">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide sales-table-card--services sales-table-card--invoices">
                <div class="sales-table-scroll sales-table-scroll--services sales-table-scroll--invoices">
                    <table class="sales-table sales-table--wide sales-table--services sales-table--invoices">
                        <thead>
                            <tr>
                                <th>Faktur</th>
                                <th>Pelanggan</th>
                                <th>Tanggal Faktur</th>
                                <th>Lokasi</th>
                                <th>Tips</th>
                                <th>Pendapatan Kotor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="js-sales-invoice-rows">
                            <?php if ($invoiceRows === []): ?>
                                <tr><td colspan="7" class="sales-no-data">No Data</td></tr>
                            <?php else: ?>
                                <?php foreach ($invoiceRows as $row): ?>
                                    <tr class="js-sales-invoice-row"
                                        tabindex="0"
                                        role="button"
                                        aria-label="Lihat faktur <?= e($row['invoice']) ?>"
                                        data-search-invoice="<?= e($row['invoice']) ?>"
                                        data-search-customer="<?= e($row['customer']) ?>"
                                        data-invoice="<?= e($row['invoice']) ?>"
                                        data-customer-id="<?= e((string) $row['customer_id']) ?>"
                                        data-customer="<?= e($row['customer']) ?>"
                                        data-date="<?= e($row['date']) ?>"
                                        data-time="<?= e($row['time']) ?>"
                                        data-location="<?= e($row['location']) ?>"
                                        data-gross="<?= e((string) $row['gross']) ?>"
                                        data-status="<?= e($row['status']) ?>"
                                        data-payment-method="<?= e($row['payment_method']) ?>"
                                        data-items="<?= e(json_encode($row['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                                        <td><?= e($row['invoice']) ?></td>
                                        <td>
                                            <?php if (($row['customer_id'] ?? 0) > 0 && strcasecmp($row['customer'], 'Walk-In') !== 0): ?>
                                                <a
                                                    class="sales-customer-link js-sales-customer-link"
                                                    href="<?= e(url('/customers') . '?customer_id=' . rawurlencode((string) $row['customer_id']) . '&return_to=' . rawurlencode($salesInvoicesReturnTo)) ?>"
                                                ><?= e($row['customer']) ?></a>
                                            <?php else: ?>
                                                <span class="sales-customer-name sales-customer-name--walkin"><?= e($row['customer']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($row['date']) ?></td>
                                        <td><?= e($row['location']) ?></td>
                                        <td><?= money($row['tips']) ?></td>
                                        <td><?= money($row['gross']) ?></td>
                                        <td><button class="sales-status-pill js-sales-invoice-open <?= e($invoiceStatusClass($row['status'])) ?>" type="button"><?= e($row['status']) ?></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sales-pagination sales-pagination--services sales-pagination--fixed" data-sales-pagination="invoices">
                    <div class="sales-pagination__meta">Total <span class="js-sales-invoices-total"><?= e((string) count($invoiceRows)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-sales-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-sales-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-sales-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-sales-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-sales-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </section>

        <section class="sales-panel" data-sales-panel="vouchers">
            <div class="sales-toolbar sales-toolbar--services sales-toolbar--vouchers">
                <div class="sales-toolbar__group">
                    <button class="sales-refresh-btn js-sales-vouchers-refresh" type="button">Refresh</button>
                </div>
                <div class="sales-toolbar__group sales-toolbar__group--end">
                    <button class="dashboard-filter dashboard-filter--wide js-sales-vouchers-date-open" type="button" data-bs-toggle="modal" data-bs-target="#salesVouchersDateFilterModal">
                        <i class="bi bi-calendar3"></i><span data-sales-vouchers-range-label><?= e($voucherRangeLabel) ?></span>
                    </button>
                    <div class="dropdown">
                        <button class="dashboard-filter sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span>Export</span><i class="bi bi-caret-down-fill"></i>
                        </button>
                        <div class="dropdown-menu sales-toolbar-menu">
                            <button class="dropdown-item" type="button" data-sales-vouchers-export="pdf">PDF</button>
                            <button class="dropdown-item" type="button" data-sales-vouchers-export="xls">XLS</button>
                            <button class="dropdown-item" type="button" data-sales-vouchers-export="xlsx">XLSX</button>
                            <button class="dropdown-item" type="button" data-sales-vouchers-export="csv">CSV</button>
                        </div>
                    </div>
                    <div class="sales-search-combo">
                        <div class="dropdown">
                            <button class="dashboard-filter sales-search-select sales-toolbar-dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span data-sales-vouchers-search-label>Nama Pelanggan</span><i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu sales-toolbar-menu">
                                <?php foreach ($voucherSearchOptions as $searchKey => $searchLabel): ?>
                                    <button class="dropdown-item <?= $searchKey === 'customer' ? 'is-active' : '' ?>" type="button" data-sales-vouchers-search-option="<?= e($searchKey) ?>">
                                        <?= e($searchLabel) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <label class="sales-search-field sales-search-field--input">
                            <input class="js-sales-vouchers-search" type="search" placeholder="Ketik kata kunci" autocomplete="off">
                            <i class="bi bi-search"></i>
                        </label>
                    </div>
                </div>
            </div>
            <div class="sales-table-card sales-table-card--wide sales-table-card--services">
                <div class="sales-table-scroll sales-table-scroll--services">
                    <table class="sales-table sales-table--wide sales-table--services sales-table--vouchers">
                        <thead>
                            <tr>
                                <th>Nama Voucher</th>
                                <th>Kadaluarsa</th>
                                <th>Faktur</th>
                                <th>Pelanggan</th>
                                <th>Kode</th>
                                <th>Total</th>
                                <th>Digunakan</th>
                                <th>Tersisa</th>
                                <th class="sales-table__sticky-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($voucherRows === []): ?>
                                <tr><td colspan="9" class="sales-no-data">No Data</td></tr>
                            <?php else: ?>
                                <?php foreach ($voucherRows as $row): ?>
                                    <tr class="js-sales-vouchers-row"
                                        data-date="<?= e($row['issued']) ?>"
                                        data-invoice="<?= e($row['invoice']) ?>"
                                        data-customer-id="<?= e((string) ($row['customer_id'] ?? 0)) ?>"
                                        data-customer="<?= e($row['customer']) ?>"
                                        data-time="<?= e($row['invoice_time']) ?>"
                                        data-location="<?= e($row['location']) ?>"
                                        data-gross="<?= e((string) $row['gross']) ?>"
                                        data-status="<?= e($row['invoice_status']) ?>"
                                        data-payment-method="<?= e($row['payment_method']) ?>"
                                        data-items="<?= e(json_encode($row['items'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                                        data-search-customer="<?= e($row['customer']) ?>"
                                        data-search-invoice="<?= e($row['invoice']) ?>"
                                        data-search-code="<?= e($row['code']) ?>"
                                        data-search-name="<?= e($row['name']) ?>">
                                        <td><?= e($row['name']) ?></td>
                                        <td><?= e($row['expired']) ?></td>
                                        <td><a class="sales-ref-code js-sales-voucher-invoice-open" href="#"><?= e($row['invoice']) ?></a></td>
                                        <td>
                                            <?php if (($row['customer_id'] ?? 0) > 0 && strcasecmp($row['customer'], 'Walk-In') !== 0): ?>
                                                <a
                                                    class="sales-customer-link js-sales-customer-link"
                                                    href="<?= e(url('/customers') . '?customer_id=' . rawurlencode((string) $row['customer_id']) . '&return_to=' . rawurlencode($salesVouchersReturnTo)) ?>"
                                                ><?= e($row['customer']) ?></a>
                                            <?php else: ?>
                                                <span class="sales-customer-name sales-customer-name--walkin"><?= e($row['customer']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="sales-code-copy js-sales-voucher-code-copy" type="button" data-copy-text="<?= e($row['code']) ?>" aria-label="Copy kode voucher <?= e($row['code']) ?>">
                                                <span><?= e($row['code']) ?></span>
                                                <i class="bi bi-copy"></i>
                                            </button>
                                        </td>
                                        <td><?= e($row['display_total']) ?></td>
                                        <td><?= e($row['display_used']) ?></td>
                                        <td><?= e($row['display_remaining']) ?></td>
                                        <td class="sales-table__sticky-right"><span class="sales-status-pill <?= e($voucherStatusClass($row['status'])) ?>"><?= e($row['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sales-pagination sales-pagination--services sales-pagination--fixed" data-sales-pagination="vouchers">
                    <div class="sales-pagination__meta">Total <span class="js-sales-vouchers-total"><?= e((string) count($voucherRows)) ?></span></div>
                    <div class="sales-pagination__page-size">
                        <button type="button" class="sales-pagination__select" data-sales-page-size-toggle aria-expanded="false">20/page <i class="bi bi-chevron-down"></i></button>
                        <div class="sales-pagination__page-size-menu" data-sales-page-size-menu hidden></div>
                    </div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-prev aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                    <div class="sales-pagination__pages" data-sales-page-list></div>
                    <button type="button" class="sales-pagination__nav" data-sales-page-next aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                    <div class="sales-pagination__goto">Go to</div>
                    <input class="sales-pagination__input" data-sales-page-input type="text" inputmode="numeric" value="1" aria-label="Pergi ke halaman">
                    <button type="button" class="sales-pagination__top" data-sales-page-top aria-label="Kembali ke atas"><i class="bi bi-chevron-up"></i></button>
                </div>
            </div>
        </section>

    </div>

    <div class="sales-fab-wrapper">
        <button class="sales-fab js-sales-fab" type="button" data-sales-fab-icon="plus">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
</section>

<div class="modal fade" id="salesServiceViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sales-service-view-dialog">
        <div class="modal-content sales-service-view">
            <div class="sales-service-view__panel">
                <div class="sales-service-view__header">
                    <h2>Lihat Agenda</h2>
                    <button type="button" class="sales-service-view__close" data-bs-dismiss="modal" aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="sales-service-view__customer js-sales-service-customer">Pelanggan Walk-In</div>

                <div class="sales-service-view__status-wrap">
                    <button class="sales-service-view__status js-sales-service-status-toggle is-new" type="button" aria-expanded="false">
                        <span class="js-sales-service-status-label">NEW</span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="sales-service-view__status-menu js-sales-service-status-menu" hidden>
                        <button class="sales-service-view__status-option is-new" type="button" data-sales-service-status="new">
                            <i class="bi bi-circle"></i>
                            <span>NEW</span>
                        </button>
                        <button class="sales-service-view__status-option is-confirmed" type="button" data-sales-service-status="confirmed">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span>CONFIRMED</span>
                        </button>
                        <button class="sales-service-view__status-option is-arrived" type="button" data-sales-service-status="arrived">
                            <i class="bi bi-emoji-smile"></i>
                            <span>ARRIVED</span>
                        </button>
                        <button class="sales-service-view__status-option is-started" type="button" data-sales-service-status="started">
                            <i class="bi bi-play"></i>
                            <span>STARTED</span>
                        </button>
                        <button class="sales-service-view__status-option is-completed" type="button" data-sales-service-status="completed">
                            <i class="bi bi-check-lg"></i>
                            <span>COMPLETED</span>
                        </button>
                    </div>
                </div>

                <div class="sales-service-view__meta">
                    <i class="bi bi-geo-alt-fill"></i>
                    <span class="js-sales-service-branch">Star Salon</span>
                    <span>&bull;</span>
                    <span class="js-sales-service-date">-</span>
                </div>

                <div class="sales-service-view__summary js-sales-service-products-summary">
                    <span class="sales-service-view__summary-icon"><i class="bi bi-bottle"></i></span>
                    <strong class="js-sales-service-products-count">0 Produk</strong>
                    <button type="button" class="sales-service-view__summary-edit js-sales-service-products-edit" aria-label="Ubah produk">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>

                <div class="sales-service-view__services js-sales-service-services"></div>

                <div class="sales-service-view__updated">
                    <i class="bi bi-clock-history"></i>
                    <span class="js-sales-service-updated">Terakhir diperbarui pada: -</span>
                </div>

                <div class="sales-service-view__notes js-sales-service-notes" hidden></div>

                <div class="sales-service-view__footer">
                    <div class="sales-service-view__total js-sales-service-total">Total: Rp 0,00</div>
                    <div class="sales-service-view__more-wrap">
                        <button type="button" class="sales-service-view__more js-sales-service-more-toggle" aria-expanded="false">
                            Lainnya
                            <i class="bi bi-caret-down-fill ms-1"></i>
                        </button>
                        <div class="sales-service-view__more-menu js-sales-service-more-menu" hidden>
                            <button type="button" class="sales-service-view__more-option js-sales-service-edit-trigger">
                                <i class="bi bi-pencil"></i>
                                <span>Ubah Agenda</span>
                            </button>
                            <button type="button" class="sales-service-view__more-option js-sales-service-add-product-trigger">
                                <i class="bi bi-plus-lg"></i>
                                <span>Tambahkan Produk</span>
                            </button>
                            <button type="button" class="sales-service-view__more-option is-danger js-sales-service-cancel-trigger">
                                <i class="bi bi-x-lg"></i>
                                <span>Batal</span>
                            </button>
                            <button type="button" class="sales-service-view__more-option is-danger js-sales-service-no-show-trigger">
                                <i class="bi bi-x-circle"></i>
                                <span>Tidak hadir</span>
                            </button>
                            <button type="button" class="sales-service-view__more-option js-sales-service-reset-no-show-trigger" hidden>
                                <i class="bi bi-arrow-repeat"></i>
                                <span>Ubah status "Tidak Hadir"</span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="sales-service-view__checkout js-sales-service-checkout">Checkout</button>
                </div>
            </div>
            <div class="sales-service-product-panel__backdrop js-sales-service-product-backdrop" hidden></div>
            <aside class="sales-service-product-panel js-sales-service-product-panel" aria-hidden="true" hidden>
                <div class="sales-service-product-panel__header">
                    <h3>Tambahkan Produk</h3>
                    <button type="button" class="sales-service-product-panel__close js-sales-service-product-close" aria-label="Tutup">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="sales-service-product-panel__search">
                    <input type="search" class="js-sales-service-product-search" placeholder="Search Product..." autocomplete="off">
                </div>
                <div class="sales-service-product-panel__results js-sales-service-product-results" hidden></div>
                <div class="sales-service-product-panel__empty js-sales-service-product-empty">
                    <i class="bi bi-box-seam"></i>
                    <span>Belum ada produk untuk agenda ini</span>
                </div>
                <div class="sales-service-product-panel__selected js-sales-service-product-selected" hidden></div>
                <div class="sales-service-product-panel__footer">
                    <button type="button" class="sales-service-product-panel__cancel js-sales-service-product-cancel">Batal</button>
                    <button type="button" class="sales-service-product-panel__done js-sales-service-product-done">Selesai</button>
                </div>
            </aside>
        </div>
    </div>
</div>

<div class="sales-service-toast js-sales-service-toast" hidden>
    <div class="sales-service-toast__icon">
        <i class="bi bi-check-lg"></i>
    </div>
    <span>Saved</span>
    <button type="button" class="sales-service-toast__close js-sales-service-toast-close" aria-label="Tutup">
        <i class="bi bi-x-lg"></i>
    </button>
</div>

<div class="modal fade" id="salesServiceNoShowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sales-service-no-show-dialog">
        <div class="modal-content sales-service-no-show">
            <div class="sales-service-no-show__body">
                <div class="sales-service-no-show__icon">
                    <i class="bi bi-info-lg"></i>
                </div>
                <p>Perubahan ini tidak permanent, Anda masih bisa mengubah statusnya lagi nanti. Lanjutkan?</p>
            </div>
            <div class="sales-service-no-show__actions">
                <button type="button" class="sales-service-no-show__cancel" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="sales-service-no-show__confirm js-sales-service-no-show-confirm">OK</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesServiceCancelReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered sales-service-cancel-dialog">
        <div class="modal-content sales-service-cancel">
            <div class="sales-service-cancel__header">
                <h2>Alasan Pembatalan</h2>
                <button type="button" class="sales-service-cancel__close" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <form class="sales-service-cancel__body js-sales-service-cancel-form">
                <p>Pilih alasan kenapa agenda ini dibatalkan. Setelah submit, Anda tidak bisa mengubahnya lagi.</p>
                <div class="sales-service-cancel__options">
                    <label class="sales-service-cancel__option">
                        <input type="radio" name="sales_service_cancel_reason" value="Appointment Made by Mistake">
                        <span>Appointment Made by Mistake</span>
                    </label>
                    <label class="sales-service-cancel__option">
                        <input type="radio" name="sales_service_cancel_reason" value="Appointment invoice not paid">
                        <span>Appointment invoice not paid</span>
                    </label>
                    <label class="sales-service-cancel__option">
                        <input type="radio" name="sales_service_cancel_reason" value="Other">
                        <span>Other</span>
                    </label>
                    <label class="sales-service-cancel__option">
                        <input type="radio" name="sales_service_cancel_reason" value="Canceled because payment time is expired">
                        <span>Canceled because payment time is expired</span>
                    </label>
                    <label class="sales-service-cancel__option">
                        <input type="radio" name="sales_service_cancel_reason" value="Rebooking">
                        <span>Rebooking</span>
                    </label>
                </div>
                <div class="sales-service-cancel__actions">
                    <button type="button" class="sales-service-cancel__dismiss" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="sales-service-cancel__submit js-sales-service-cancel-submit" disabled>Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="sales-invoice-view js-sales-invoice-view" hidden aria-label="Lihat faktur">
    <div class="sales-invoice-view__header">
        <div></div>
        <h2>Lihat Faktur</h2>
        <button class="sales-invoice-view__close js-sales-invoice-close" type="button" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="sales-invoice-view__body">
        <div class="sales-invoice-view__left">
            <div class="sales-invoice-paper">
                <div class="sales-invoice-paper__brand">
                    <div class="sales-invoice-paper__logo"><i class="bi bi-shop"></i></div>
                    <strong>Star Salon</strong>
                    <span>Star Salon - Jl. Raya Inpres No.04, RT.4/RW.10, P. Tengah,<br>Kec. Kramat jati, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13540</span>
                </div>
                <div class="sales-invoice-paper__meta">
                    <strong class="js-sales-invoice-number">Faktur</strong>
                    <span class="js-sales-invoice-date"></span>
                </div>
                <div class="sales-invoice-paper__items js-sales-invoice-items"></div>
                <div class="sales-invoice-paper__totals">
                    <div><span>Sub Total</span><strong class="js-sales-invoice-subtotal">Rp 0,00</strong></div>
                    <div><span>Total</span><strong class="js-sales-invoice-total">Rp 0,00</strong></div>
                    <div><span>Grand total</span><strong class="js-sales-invoice-grand-total">Rp 0,00</strong></div>
                    <div class="js-sales-invoice-payment-line" hidden><span>CASH</span><strong class="js-sales-invoice-paid-total">Rp 0,00</strong></div>
                    <div><span>Sisa pembayaran</span><strong class="js-sales-invoice-remaining">Rp 0,00</strong></div>
                </div>
                <div class="sales-invoice-paper__footer">Penjualan oleh Rayhan Doni Pramana</div>
            </div>
            <div class="sales-invoice-floating-actions">
                <button type="button" class="js-sales-invoice-download" aria-label="Download faktur"><i class="bi bi-download"></i></button>
                <button type="button" class="js-sales-invoice-print" aria-label="Print faktur"><i class="bi bi-printer"></i></button>
            </div>
        </div>
        <aside class="sales-invoice-view__right">
            <h3 class="js-sales-invoice-customer">Walk-In</h3>
            <div class="sales-invoice-status js-sales-invoice-status">PAID</div>
            <div class="sales-invoice-meta js-sales-invoice-meta"></div>
            <div class="sales-invoice-share">
                <button class="js-sales-invoice-copy" type="button"><i class="bi bi-link-45deg"></i><span>Copy link</span></button>
                <button class="js-sales-invoice-email" type="button"><i class="bi bi-envelope"></i><span>Email</span></button>
                <button class="js-sales-invoice-whatsapp" type="button"><i class="bi bi-whatsapp"></i><span>whatsapp</span></button>
            </div>
            <div class="sales-invoice-actions">
                <button type="button">Lainnya <i class="bi bi-caret-down-fill"></i></button>
                <button class="js-sales-invoice-close" type="button">Tutup</button>
            </div>
        </aside>
    </div>
</section>

<div class="modal fade" id="salesServicesDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-services-date-preset is-active" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-services-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-services-start" type="text" value="<?= e($serviceRangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-services-end" type="text" value="<?= e($serviceRangeEnd->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-sales-services-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-sales-services-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-sales-services-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesInvoicesDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-invoices-date-preset is-active" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-invoices-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-invoices-start" type="text" value="<?= e($invoiceRangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-invoices-end" type="text" value="<?= e($invoiceRangeEnd->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-sales-invoices-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-sales-invoices-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-sales-invoices-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesVouchersDateFilterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content customers-date-modal">
            <div class="customers-date-modal__header">
                <h2>Date Filter</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="customers-date-modal__body">
                <div class="customers-date-grid">
                    <div class="customers-date-presets">
                        <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="today">Hari ini</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="this_month">Bulan ini</button>
                            <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="yesterday">Kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="7d">7 hari sebelumnya</button>
                        <button class="customers-date-preset js-sales-vouchers-date-preset is-active" type="button" data-preset="30d">30 hari sebelumnya</button>
                        <div class="customers-date-presets__row">
                            <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="last_month">Bulan kemarin</button>
                            <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="last_year">Tahun kemarin</button>
                        </div>
                        <button class="customers-date-preset js-sales-vouchers-date-preset" type="button" data-preset="this_year">Tahun ini</button>
                    </div>

                    <div class="customers-date-picker">
                        <div class="customers-date-fields">
                            <div>
                                <label>Mulai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-vouchers-start" type="text" value="<?= e($voucherRangeStart->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                            <div>
                                <label>Sampai Tanggal</label>
                                <input class="form-control customers-date-input js-sales-vouchers-end" type="text" value="<?= e($voucherRangeEnd->format('Y-m-d')) ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            </div>
                        </div>

                        <div class="customers-date-inline">
                            <input class="js-sales-vouchers-date-range customers-date-range-input" type="text" aria-hidden="true" tabindex="-1">
                        </div>
                    </div>
                </div>
            </div>
            <div class="customers-date-modal__footer">
                <button type="button" class="customer-footer-btn js-sales-vouchers-date-reset">Reset</button>
                <button type="button" class="customer-footer-btn customers-date-apply js-sales-vouchers-date-apply" data-bs-dismiss="modal">Terapkan</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content sales-summary-modal"
             data-register-summary='<?= e(json_encode($registerSummaryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
             data-transaction-summary='<?= e(json_encode($transactionSummaryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
             data-printed-at="<?= e($summaryPrintedAt) ?>">
            <div class="sales-summary-modal__header">
                <div></div>
                <h2>Cetak Ringkasan</h2>
                <button type="button" class="sales-summary-modal__close" data-bs-dismiss="modal" aria-label="Tutup">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="sales-summary-modal__body">
                <div class="sales-summary-preview">
                    <div class="sales-summary-receipt js-sales-summary-receipt"><?= nl2br(e($defaultRegisterReceiptText)) ?></div>
                </div>
                <aside class="sales-summary-sidebar">
                    <div class="sales-summary-segmented" role="tablist" aria-label="Mode cetak">
                        <button class="is-active js-sales-summary-mode" type="button" data-summary-mode="register">Register</button>
                        <button class="js-sales-summary-mode" type="button" data-summary-mode="transaction">Transaction</button>
                    </div>

                    <label class="sales-summary-checkbox js-sales-summary-items-toggle" hidden>
                        <input class="js-sales-summary-items-input" type="checkbox">
                        <span class="sales-summary-checkbox__box"><i class="bi bi-check-lg"></i></span>
                        <span>Masukkan item penjualan</span>
                    </label>

                    <div class="sales-summary-calendar">
                        <div class="sales-summary-calendar__head">
                            <strong>May 2026</strong>
                            <div class="sales-summary-calendar__nav">
                                <button type="button" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
                                <button type="button" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></button>
                            </div>
                        </div>
                        <div class="sales-summary-calendar__weekdays">
                            <span>S</span><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span>
                        </div>
                        <div class="sales-summary-calendar__days">
                            <span class="is-empty"></span><span class="is-empty"></span><span class="is-empty"></span><span class="is-empty"></span><span class="is-empty"></span><span>1</span><span>2</span>
                            <span>3</span><span>4</span><span>5</span><span>6</span><span>7</span><span>8</span><span>9</span>
                            <span>10</span><span>11</span><span>12</span><span>13</span><span>14</span><span>15</span><span>16</span>
                            <span>17</span><span>18</span><span>19</span><span>20</span><span>21</span><span>22</span><span>23</span>
                            <span class="is-active">24</span><span class="is-muted">25</span><span class="is-muted">26</span><span class="is-muted">27</span><span class="is-muted">28</span><span class="is-muted">29</span><span class="is-muted">30</span>
                            <span class="is-muted">31</span>
                        </div>
                        <div class="sales-summary-calendar__footer">
                            <span>24 Mei 2026</span>
                            <button type="button">Hari ini</button>
                        </div>
                    </div>

                    <button class="sales-summary-range is-active" type="button">
                        <span class="sales-summary-range__dot"></span>
                        <span><strong>Daily Sales</strong><small>24 Mei 2026</small></span>
                    </button>

                    <button class="sales-summary-print-btn js-sales-summary-print" type="button">Cetak</button>
                </aside>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesAgendaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content sales-agenda-modal">
            <div class="sales-agenda-modal__header">
                <div></div>
                <h2 class="js-sales-agenda-title">Agenda Baru</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="sales-agenda-modal__body">
                <div class="sales-agenda-left">
                    <div class="sales-agenda-searchbar">
                        <i class="bi bi-arrow-left"></i>
                        <span class="js-sales-agenda-search-label">Cari service...</span>
                        <i class="bi bi-search"></i>
                    </div>
                    <div class="sales-agenda-chips">
                        <span class="sales-chip">Paket Layanan</span>
                        <span class="sales-chip">Hair Cut</span>
                        <span class="sales-chip">Hair Treatment</span>
                        <span class="sales-chip">Hair Coloring</span>
                    </div>
                    <div class="sales-service-grid js-sales-agenda-services">
                        <?php foreach (array_slice($services, 0, 5) as $service): ?>
                            <div class="sales-service-card">
                                <div class="sales-service-card__thumb"><?= e(substr($service['name'], 0, 2)) ?></div>
                                <div class="sales-service-card__body">
                                    <strong><?= e($service['name']) ?></strong>
                                    <span><?= e((string) round($service['duration'] / 60, 1)) ?>h • <?= money($service['price']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="sales-agenda-footer">
                        <div class="sales-agenda-footer__summary">0 Layanan • Rp 0</div>
                        <button class="sales-agenda-footer__action" type="button">Tambahkan 0 Layanan</button>
                    </div>
                </div>
                <div class="sales-agenda-right">
                    <div class="sales-agenda-customer">
                        <div class="sales-agenda-customer__avatar"><i class="bi bi-emoji-smile"></i></div>
                        <div>
                            <strong>Daniel</strong>
                            <span>tag oyen</span>
                        </div>
                        <button type="button" class="sales-agenda-more"><i class="bi bi-three-dots"></i></button>
                    </div>
                    <div class="sales-agenda-actions">
                        <button type="button" disabled>Checkout</button>
                        <button type="button" disabled>Simpan Agenda</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="salesClassModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content calendar-modal">
            <div class="modal-header border-0">
                <h3 class="panel-subtitle">Kelas Baru</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form class="row g-3">
                    <div class="col-12"><input class="form-control" type="text" placeholder="Nama kelas"></div>
                    <div class="col-md-6"><input class="form-control js-datepicker" type="text" placeholder="Tanggal"></div>
                    <div class="col-md-6"><input class="form-control" type="text" placeholder="Jam"></div>
                    <div class="col-md-6"><input class="form-control" type="text" placeholder="Durasi"></div>
                    <div class="col-md-6"><input class="form-control" type="number" placeholder="Slot"></div>
                    <div class="col-12"><button class="btn btn-dark rounded-pill px-4" type="button">Simpan Kelas</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
