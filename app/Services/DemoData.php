<?php

declare(strict_types=1);

namespace App\Services;

final class DemoData
{
    public static function make(array $config): array
    {
        $today = new \DateTimeImmutable('today');
        $date = static fn (int $offset, string $time): string => $today->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d') . ' ' . $time;
        $password = password_hash('password123', PASSWORD_DEFAULT);

        return [
            'users' => [
                ['id' => 1, 'name' => 'Rayhan Donovan', 'email' => 'admin@starstyle.test', 'password' => $password, 'role' => 'admin', 'portal' => 'internal', 'avatar' => 'RD', 'staff_id' => 1],
                ['id' => 2, 'name' => 'Maya Putri', 'email' => 'stylist@starstyle.test', 'password' => $password, 'role' => 'staff', 'portal' => 'internal', 'avatar' => 'MP', 'staff_id' => 2],
                ['id' => 3, 'name' => 'Citra Aulia', 'email' => 'customer@starstyle.test', 'password' => $password, 'role' => 'customer', 'portal' => 'customer', 'avatar' => 'CA', 'customer_id' => 1],
            ],
            'staff' => [
                ['id' => 1, 'name' => 'Rayhan Donovan', 'role' => 'Owner', 'email' => 'admin@starstyle.test', 'phone' => '0812-1111-1001', 'status' => 'Aktif', 'specialties' => ['Management', 'Coloring'], 'commission_type' => 'Persentase', 'commission_value' => 18, 'rating' => 4.9],
                ['id' => 2, 'name' => 'Maya Putri', 'role' => 'Senior Stylist', 'email' => 'stylist@starstyle.test', 'phone' => '0812-1111-1002', 'status' => 'Aktif', 'specialties' => ['Haircut', 'Treatment'], 'commission_type' => 'Persentase', 'commission_value' => 12, 'rating' => 4.8],
                ['id' => 3, 'name' => 'Kevin Sebastian', 'role' => 'Color Expert', 'email' => 'kevin@starstyle.test', 'phone' => '0812-1111-1003', 'status' => 'Aktif', 'specialties' => ['Hair Color', 'Balayage'], 'commission_type' => 'Fixed', 'commission_value' => 75000, 'rating' => 4.7],
                ['id' => 4, 'name' => 'Nadia Maharani', 'role' => 'Therapist', 'email' => 'nadia@starstyle.test', 'phone' => '0812-1111-1004', 'status' => 'Aktif', 'specialties' => ['Spa', 'Nail Art'], 'commission_type' => 'Persentase', 'commission_value' => 10, 'rating' => 4.9],
            ],
            'customers' => [
                ['id' => 1, 'name' => 'Citra Aulia', 'gender' => 'Perempuan', 'phone' => '0813-9000-1111', 'email' => 'customer@starstyle.test', 'member_id' => 'MEM-0001', 'loyalty_points' => 340, 'last_visit' => $date(-1, '15:30:00'), 'birthdate' => '1997-04-13', 'tags' => ['VIP', 'Hair Color'], 'status' => 'Aktif', 'notes' => 'Suka treatment premium.', 'address' => 'Sathorn, Bangkok'],
                ['id' => 2, 'name' => 'Alif Rahman', 'gender' => 'Laki-laki', 'phone' => '0813-9000-1112', 'email' => 'alif@starstyle.test', 'member_id' => 'MEM-0002', 'loyalty_points' => 120, 'last_visit' => $date(-3, '11:00:00'), 'birthdate' => '1995-02-22', 'tags' => ['Haircut'], 'status' => 'Aktif', 'notes' => 'Prefer booking pagi.', 'address' => 'Silom, Bangkok'],
                ['id' => 3, 'name' => 'Naura Sofia', 'gender' => 'Perempuan', 'phone' => '0813-9000-1113', 'email' => 'naura@starstyle.test', 'member_id' => 'MEM-0003', 'loyalty_points' => 420, 'last_visit' => $date(-6, '13:00:00'), 'birthdate' => '1998-11-05', 'tags' => ['Bridal', 'Loyal'], 'status' => 'Aktif', 'notes' => 'Paket bridal bulanan.', 'address' => 'Bang Rak, Bangkok'],
                ['id' => 4, 'name' => 'Daniel Wijaya', 'gender' => 'Laki-laki', 'phone' => '0813-9000-1114', 'email' => 'daniel@starstyle.test', 'member_id' => 'MEM-0004', 'loyalty_points' => 60, 'last_visit' => $date(-10, '18:30:00'), 'birthdate' => '1993-12-02', 'tags' => ['Product Buyer'], 'status' => 'Aktif', 'notes' => 'Sering beli pomade.', 'address' => 'Pathum Wan, Bangkok'],
            ],
            'service_groups' => [
                ['id' => 1, 'name' => 'Hair Signature'],
                ['id' => 2, 'name' => 'Color Studio'],
                ['id' => 3, 'name' => 'Spa & Nail'],
            ],
            'services' => [
                ['id' => 1, 'group_id' => 1, 'name' => 'Signature Haircut', 'duration' => 60, 'price' => 280000, 'variants' => ['Women', 'Men'], 'staff_ids' => [2, 3], 'status' => 'Aktif', 'description' => 'Cutting presisi dengan styling finish.'],
                ['id' => 2, 'group_id' => 2, 'name' => 'Glossy Balayage', 'duration' => 150, 'price' => 1250000, 'variants' => ['Short', 'Long'], 'staff_ids' => [1, 3], 'status' => 'Aktif', 'description' => 'Color gradient lembut dan dimensional.'],
                ['id' => 3, 'group_id' => 1, 'name' => 'Keratin Repair', 'duration' => 90, 'price' => 650000, 'variants' => ['Standard', 'Intensive'], 'staff_ids' => [2], 'status' => 'Aktif', 'description' => 'Recovery treatment untuk rambut rusak.'],
                ['id' => 4, 'group_id' => 3, 'name' => 'Relaxing Head Spa', 'duration' => 75, 'price' => 450000, 'variants' => ['Aroma Mint', 'Ocean Calm'], 'staff_ids' => [4], 'status' => 'Aktif', 'description' => 'Spa kulit kepala dengan massage relaksasi.'],
                ['id' => 5, 'group_id' => 3, 'name' => 'Signature Gel Nails', 'duration' => 90, 'price' => 520000, 'variants' => ['Plain', 'Art'], 'staff_ids' => [4], 'status' => 'Aktif', 'description' => 'Gel nails premium dengan finishing glossy.'],
            ],
            'packages' => [
                ['id' => 1, 'name' => 'Beauty Reset', 'items' => ['Signature Haircut', 'Relaxing Head Spa'], 'price' => 680000],
                ['id' => 2, 'name' => 'Color Glow', 'items' => ['Glossy Balayage', 'Keratin Repair'], 'price' => 1750000],
            ],
            'products' => [
                ['id' => 1, 'name' => 'Silk Repair Serum', 'brand' => 'StarStyle Pro', 'category' => 'Hair Care', 'supplier' => 'PT Glow Source', 'stock' => 14, 'price' => 190000, 'status' => 'Aman'],
                ['id' => 2, 'name' => 'Ocean Mist Spray', 'brand' => 'StarStyle Pro', 'category' => 'Styling', 'supplier' => 'PT Glow Source', 'stock' => 6, 'price' => 165000, 'status' => 'Rendah'],
                ['id' => 3, 'name' => 'Volume Clay', 'brand' => 'Form Men', 'category' => 'Styling', 'supplier' => 'PT Groom Lab', 'stock' => 20, 'price' => 145000, 'status' => 'Aman'],
                ['id' => 4, 'name' => 'Nail Gloss Kit', 'brand' => 'Luna Nails', 'category' => 'Nail', 'supplier' => 'PT Color Boutique', 'stock' => 9, 'price' => 225000, 'status' => 'Aman'],
            ],
            'vouchers' => [
                ['id' => 1, 'name' => 'WELCOME10', 'code' => 'WELCOME10', 'type' => 'gift', 'value' => 100000, 'expired_at' => $today->modify('+25 days')->format('Y-m-d'), 'status' => 'Aktif', 'usage_limit' => 1, 'used' => 0],
                ['id' => 2, 'name' => 'HEADSPA25', 'code' => 'HEADSPA25', 'type' => 'service', 'value' => 25, 'expired_at' => $today->modify('+12 days')->format('Y-m-d'), 'status' => 'Aktif', 'usage_limit' => 50, 'used' => 8],
                ['id' => 3, 'name' => 'CLASSPASS', 'code' => 'CLASSPASS', 'type' => 'class', 'value' => 1, 'expired_at' => $today->modify('-2 days')->format('Y-m-d'), 'status' => 'Expired', 'usage_limit' => 100, 'used' => 65],
            ],
            'classes' => [
                ['id' => 1, 'name' => 'Bridal Hair Intensive', 'schedule' => $date(2, '10:00:00'), 'slot' => 8, 'staff_id' => 1, 'booked' => 6],
                ['id' => 2, 'name' => 'Nail Art Masterclass', 'schedule' => $date(5, '14:00:00'), 'slot' => 10, 'staff_id' => 4, 'booked' => 7],
            ],
            'bookings' => [
                ['id' => 1001, 'reference' => 'BK-240401', 'customer_id' => 1, 'staff_id' => 2, 'service_ids' => [1, 3], 'start_at' => $date(0, '10:00:00'), 'end_at' => $date(0, '12:30:00'), 'status' => 'confirmed', 'channel' => 'Online', 'notes' => 'Request stylist Maya'],
                ['id' => 1002, 'reference' => 'BK-240402', 'customer_id' => 2, 'staff_id' => 3, 'service_ids' => [2], 'start_at' => $date(0, '13:00:00'), 'end_at' => $date(0, '15:30:00'), 'status' => 'confirmed', 'channel' => 'Walk-in', 'notes' => 'Color consultation lengkap'],
                ['id' => 1003, 'reference' => 'BK-240403', 'customer_id' => 3, 'staff_id' => 4, 'service_ids' => [5], 'start_at' => $date(1, '11:30:00'), 'end_at' => $date(1, '13:00:00'), 'status' => 'pending', 'channel' => 'Instagram', 'notes' => 'Ingin nail art floral'],
                ['id' => 1004, 'reference' => 'BK-240404', 'customer_id' => 4, 'staff_id' => 2, 'service_ids' => [1], 'start_at' => $date(-1, '16:00:00'), 'end_at' => $date(-1, '17:00:00'), 'status' => 'completed', 'channel' => 'Online', 'notes' => 'Upsell produk volume clay'],
                ['id' => 1005, 'reference' => 'BK-240405', 'customer_id' => 2, 'staff_id' => 4, 'service_ids' => [4], 'start_at' => $date(2, '15:00:00'), 'end_at' => $date(2, '16:15:00'), 'status' => 'cancelled', 'channel' => 'WhatsApp', 'notes' => 'Customer reschedule minggu depan'],
            ],
            'booking_blocks' => [
                ['id' => 1, 'staff_id' => 3, 'title' => 'Color Prep & Inventory', 'start_at' => $date(0, '16:00:00'), 'end_at' => $date(0, '17:30:00')],
                ['id' => 2, 'staff_id' => 2, 'title' => 'Lunch Break', 'start_at' => $date(0, '12:30:00'), 'end_at' => $date(0, '13:30:00')],
            ],
            'transactions' => [
                ['id' => 2001, 'reference' => 'TRX-240401', 'customer_id' => 1, 'staff_id' => 2, 'date' => $date(-6, '14:10:00'), 'items' => [['type' => 'service', 'name' => 'Keratin Repair', 'qty' => 1, 'price' => 650000], ['type' => 'product', 'name' => 'Silk Repair Serum', 'qty' => 1, 'price' => 190000]], 'discount' => 50000, 'rounding' => 0, 'status' => 'paid', 'payment_method' => 'Cash'],
                ['id' => 2002, 'reference' => 'TRX-240402', 'customer_id' => 2, 'staff_id' => 3, 'date' => $date(-4, '12:40:00'), 'items' => [['type' => 'service', 'name' => 'Glossy Balayage', 'qty' => 1, 'price' => 1250000]], 'discount' => 0, 'rounding' => 0, 'status' => 'paid', 'payment_method' => 'Transfer'],
                ['id' => 2003, 'reference' => 'TRX-240403', 'customer_id' => 4, 'staff_id' => 2, 'date' => $date(-1, '17:20:00'), 'items' => [['type' => 'service', 'name' => 'Signature Haircut', 'qty' => 1, 'price' => 280000], ['type' => 'product', 'name' => 'Volume Clay', 'qty' => 1, 'price' => 145000]], 'discount' => 20000, 'rounding' => 0, 'status' => 'paid', 'payment_method' => 'E-Wallet'],
                ['id' => 2004, 'reference' => 'TRX-240404', 'customer_id' => 3, 'staff_id' => 4, 'date' => $date(-2, '15:15:00'), 'items' => [['type' => 'service', 'name' => 'Signature Gel Nails', 'qty' => 1, 'price' => 520000]], 'discount' => 0, 'rounding' => 0, 'status' => 'refund', 'payment_method' => 'Cash'],
            ],
            'cash_drawers' => [
                ['staff_id' => 1, 'expected' => 4500000, 'actual' => 4450000, 'status' => 'Perlu review'],
                ['staff_id' => 2, 'expected' => 2800000, 'actual' => 2800000, 'status' => 'Sesuai'],
            ],
            'cash_movements' => [
                ['date' => $date(0, '09:05:00'), 'type' => 'cash_in', 'amount' => 1500000, 'note' => 'Saldo awal kas'],
                ['date' => $date(0, '12:10:00'), 'type' => 'cash_out', 'amount' => 120000, 'note' => 'Pembelian snack staff'],
            ],
            'attendance' => [
                ['staff_id' => 2, 'date' => $today->format('Y-m-d'), 'clock_in' => '08:55', 'clock_out' => '-', 'status' => 'On Shift'],
                ['staff_id' => 3, 'date' => $today->format('Y-m-d'), 'clock_in' => '09:02', 'clock_out' => '-', 'status' => 'On Shift'],
                ['staff_id' => 4, 'date' => $today->format('Y-m-d'), 'clock_in' => '08:50', 'clock_out' => '-', 'status' => 'On Shift'],
            ],
            'shifts' => [
                ['staff_id' => 2, 'day' => 'Senin - Jumat', 'hours' => '09:00 - 18:00'],
                ['staff_id' => 3, 'day' => 'Selasa - Sabtu', 'hours' => '10:00 - 19:00'],
                ['staff_id' => 4, 'day' => 'Senin - Sabtu', 'hours' => '09:00 - 17:00'],
            ],
            'reviews' => [
                ['customer' => 'Citra Aulia', 'rating' => 5, 'feedback' => 'Coloring rapi, staff komunikatif, hasilnya mewah sekali.', 'date' => $date(-2, '18:00:00')],
                ['customer' => 'Alif Rahman', 'rating' => 4, 'feedback' => 'Haircut presisi dan cepat, waiting time singkat.', 'date' => $date(-4, '13:00:00')],
            ],
            'activity_logs' => [
                ['time' => $date(0, '09:12:00'), 'actor' => 'Rayhan Donovan', 'action' => 'Menyetujui booking BK-240401'],
                ['time' => $date(0, '10:45:00'), 'actor' => 'Maya Putri', 'action' => 'Memulai layanan Signature Haircut'],
                ['time' => $date(-1, '17:32:00'), 'actor' => 'Rayhan Donovan', 'action' => 'Checkout transaksi TRX-240403'],
                ['time' => $date(-1, '18:10:00'), 'actor' => 'System', 'action' => 'Low stock alert untuk Ocean Mist Spray'],
            ],
            'notifications' => [
                ['title' => '2 booking menunggu konfirmasi', 'type' => 'info'],
                ['title' => '1 voucher akan expired minggu ini', 'type' => 'warning'],
                ['title' => '2 produk low stock', 'type' => 'danger'],
            ],
            'settings' => [
                'business_name' => $config['business']['name'],
                'hours' => $config['business']['hours'],
                'address' => $config['business']['address'],
                'booking_advance_days' => 30,
                'loyalty_ratio' => 10000,
                'currency' => 'IDR',
                'notification_channel' => 'Email + WhatsApp placeholder',
            ],
        ];
    }
}
