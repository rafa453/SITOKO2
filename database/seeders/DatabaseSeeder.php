<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. PAYMENT METHODS ───────────────────────────────────────────
        $paymentMethods = [
            ['name' => 'Tunai',        'type' => 'cash',    'provider' => null,    'mdr_fee' => 0,   'notes' => 'Pembayaran tunai langsung', 'is_active' => true],
            ['name' => 'QRIS GoPay',   'type' => 'digital', 'provider' => 'GoPay', 'mdr_fee' => 0.7, 'notes' => 'Scan QR semua e-wallet',    'is_active' => true],
            ['name' => 'Transfer BCA', 'type' => 'digital', 'provider' => 'BCA',   'mdr_fee' => 0,   'notes' => 'Transfer antar bank BCA',   'is_active' => true],
            ['name' => 'Debit BRI',    'type' => 'edc',     'provider' => 'BRI',   'mdr_fee' => 1.0, 'notes' => 'Kartu debit BRI',           'is_active' => true],
            ['name' => 'OVO',          'type' => 'digital', 'provider' => 'OVO',   'mdr_fee' => 1.5, 'notes' => 'Dompet digital OVO',        'is_active' => false],
        ];

        DB::table('payment_methods')->insert(array_map(fn($p) => array_merge($p, [
            'created_at' => now(), 'updated_at' => now()
        ]), $paymentMethods));

        // ─── 2. USERS ─────────────────────────────────────────────────────
        $users = [
            ['name' => 'Admin Utama',    'email' => 'admin@marketos.id',    'role' => 'admin',      'phone' => '081200000001', 'shift' => 'pagi',  'status' => 'active'],
            ['name' => 'Budi Santoso',   'email' => 'budi@marketos.id',     'role' => 'cashier',    'phone' => '081200000002', 'shift' => 'pagi',  'status' => 'active'],
            ['name' => 'Siti Rahayu',    'email' => 'siti@marketos.id',     'role' => 'cashier',    'phone' => '081200000003', 'shift' => 'siang', 'status' => 'active'],
            ['name' => 'Dedi Kurniawan', 'email' => 'dedi@marketos.id',     'role' => 'cashier',    'phone' => '081200000004', 'shift' => 'malam', 'status' => 'active'],
            ['name' => 'Rina Wulandari', 'email' => 'rina@marketos.id',     'role' => 'supervisor', 'phone' => '081200000005', 'shift' => 'pagi',  'status' => 'active'],
            ['name' => 'Joko Widodo',    'email' => 'joko@marketos.id',     'role' => 'cashier',    'phone' => '081200000006', 'shift' => 'siang', 'status' => 'inactive'],
        ];

        foreach ($users as $u) {
            DB::table('users')->insert(array_merge($u, [
                'password'   => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // ─── 3. PRODUCTS ──────────────────────────────────────────────────
        $products = [
            // Beras & Tepung
            ['sku'=>'BR-001','name'=>'Beras Premium 5kg',     'category'=>'Beras & Tepung',  'unit'=>'Karung','qty'=>120,'threshold'=>20,'buy_price'=>68000, 'sell_price'=>75000],
            ['sku'=>'BR-002','name'=>'Beras Medium 5kg',      'category'=>'Beras & Tepung',  'unit'=>'Karung','qty'=>85, 'threshold'=>15,'buy_price'=>55000, 'sell_price'=>62000],
            ['sku'=>'BR-003','name'=>'Tepung Terigu 1kg',     'category'=>'Beras & Tepung',  'unit'=>'Pcs',   'qty'=>9,  'threshold'=>15,'buy_price'=>11000, 'sell_price'=>13500],
            ['sku'=>'BR-004','name'=>'Tepung Beras 500g',     'category'=>'Beras & Tepung',  'unit'=>'Pcs',   'qty'=>0,  'threshold'=>10,'buy_price'=>8000,  'sell_price'=>10000],

            // Minyak & Lemak
            ['sku'=>'MG-001','name'=>'Minyak Goreng 2L',      'category'=>'Minyak & Lemak',  'unit'=>'Botol', 'qty'=>95, 'threshold'=>20,'buy_price'=>28000, 'sell_price'=>32000],
            ['sku'=>'MG-002','name'=>'Minyak Goreng 1L',      'category'=>'Minyak & Lemak',  'unit'=>'Botol', 'qty'=>60, 'threshold'=>15,'buy_price'=>15000, 'sell_price'=>18000],
            ['sku'=>'MG-003','name'=>'Margarin Blue Band 200g','category'=>'Minyak & Lemak', 'unit'=>'Pcs',   'qty'=>7,  'threshold'=>10,'buy_price'=>9500,  'sell_price'=>12000],

            // Gula & Garam
            ['sku'=>'GG-001','name'=>'Gula Pasir 1kg',        'category'=>'Gula & Garam',    'unit'=>'Pcs',   'qty'=>150,'threshold'=>30,'buy_price'=>13500, 'sell_price'=>16000],
            ['sku'=>'GG-002','name'=>'Gula Merah 500g',       'category'=>'Gula & Garam',    'unit'=>'Pcs',   'qty'=>45, 'threshold'=>10,'buy_price'=>9000,  'sell_price'=>11500],
            ['sku'=>'GG-003','name'=>'Garam Halus 500g',      'category'=>'Gula & Garam',    'unit'=>'Pcs',   'qty'=>80, 'threshold'=>20,'buy_price'=>3500,  'sell_price'=>5000],

            // Mie & Pasta
            ['sku'=>'MI-001','name'=>'Indomie Goreng',        'category'=>'Mie & Pasta',     'unit'=>'Pcs',   'qty'=>300,'threshold'=>50,'buy_price'=>2800,  'sell_price'=>3500],
            ['sku'=>'MI-002','name'=>'Indomie Kuah',          'category'=>'Mie & Pasta',     'unit'=>'Pcs',   'qty'=>250,'threshold'=>50,'buy_price'=>2800,  'sell_price'=>3500],
            ['sku'=>'MI-003','name'=>'Mie Telur 200g',        'category'=>'Mie & Pasta',     'unit'=>'Pcs',   'qty'=>5,  'threshold'=>15,'buy_price'=>6500,  'sell_price'=>8500],

            // Bumbu & Rempah
            ['sku'=>'BM-001','name'=>'Kecap Manis ABC 600ml', 'category'=>'Bumbu & Rempah',  'unit'=>'Botol', 'qty'=>40, 'threshold'=>10,'buy_price'=>16000, 'sell_price'=>20000],
            ['sku'=>'BM-002','name'=>'Saos Sambal 340ml',     'category'=>'Bumbu & Rempah',  'unit'=>'Botol', 'qty'=>35, 'threshold'=>10,'buy_price'=>12000, 'sell_price'=>15000],
            ['sku'=>'BM-003','name'=>'Royco Sapi 230g',       'category'=>'Bumbu & Rempah',  'unit'=>'Pcs',   'qty'=>0,  'threshold'=>10,'buy_price'=>14000, 'sell_price'=>17500],

            // Minuman
            ['sku'=>'MN-001','name'=>'Teh Botol Sosro 350ml', 'category'=>'Minuman',         'unit'=>'Botol', 'qty'=>120,'threshold'=>24,'buy_price'=>4500,  'sell_price'=>6000],
            ['sku'=>'MN-002','name'=>'Air Mineral Aqua 600ml','category'=>'Minuman',         'unit'=>'Botol', 'qty'=>200,'threshold'=>48,'buy_price'=>2500,  'sell_price'=>4000],
            ['sku'=>'MN-003','name'=>'Susu Ultra 1L',         'category'=>'Minuman',         'unit'=>'Kotak', 'qty'=>8,  'threshold'=>12,'buy_price'=>17500, 'sell_price'=>22000],

            // Kebutuhan Rumah
            ['sku'=>'KR-001','name'=>'Sabun Cuci Piring 800ml','category'=>'Kebutuhan Rumah','unit'=>'Botol', 'qty'=>50, 'threshold'=>10,'buy_price'=>12000, 'sell_price'=>15000],
            ['sku'=>'KR-002','name'=>'Deterjen Rinso 800g',   'category'=>'Kebutuhan Rumah', 'unit'=>'Pcs',   'qty'=>30, 'threshold'=>10,'buy_price'=>22000, 'sell_price'=>27000],
        ];

        foreach ($products as $p) {
            DB::table('products')->insert(array_merge($p, [
                'created_at' => now(), 'updated_at' => now()
            ]));
        }

        // ─── 4. SHIFTS (30 hari terakhir) ────────────────────────────────
        // cashier_ids: Budi=2, Siti=3, Dedi=4
        $cashierShifts = [
            2 => 'pagi',
            3 => 'siang',
            4 => 'malam',
        ];

        $shiftHours = [
            'pagi'  => ['start' => 7,  'end' => 15],
            'siang' => ['start' => 15, 'end' => 23],
            'malam' => ['start' => 23, 'end' => 31], // +1 hari
        ];

        $shiftIds = [];

        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->startOfDay();

            foreach ($cashierShifts as $userId => $shiftType) {
                $h     = $shiftHours[$shiftType];
                $start = $date->copy()->addHours($h['start']);
                $end   = $date->copy()->addHours($h['end']);

                $shiftId = DB::table('shifts')->insertGetId([
                    'user_id'    => $userId,
                    'type'       => $shiftType,
                    'started_at' => $start,
                    'ended_at'   => $end,
                    'revenue'    => 0, // akan di-update nanti
                    'trx_count'  => 0,
                    'created_at' => $start,
                    'updated_at' => $end,
                ]);

                $shiftIds[] = [
                    'id'      => $shiftId,
                    'user_id' => $userId,
                    'type'    => $shiftType,
                    'start'   => $start,
                    'end'     => $end,
                ];
            }
        }

        // ─── 5. TRANSACTIONS + TRANSACTION ITEMS ─────────────────────────
        $allProducts    = DB::table('products')->get();
        $allPayMethods  = DB::table('payment_methods')->where('is_active', true)->get();
        $cashierIds     = [2, 3, 4];
        $trxCodes       = [];

        // Distribusi transaksi per shift per hari
        $trxPerShift = ['pagi' => 12, 'siang' => 8, 'malam' => 5];

        $shiftRevenueTotals = []; // [shift_id => ['revenue'=>0,'trx_count'=>0]]

        foreach ($shiftIds as $shift) {
            $trxCount = $trxPerShift[$shift['type']];

            for ($t = 0; $t < $trxCount; $t++) {
                // Waktu transaksi: acak dalam window shift
                $shiftDuration = $shift['start']->diffInMinutes($shift['end']);
                $offsetMinutes = rand(5, max(6, $shiftDuration - 10));
                $trxTime       = $shift['start']->copy()->addMinutes($offsetMinutes);

                // Buat kode transaksi unik
                $code = 'TRX-' . $trxTime->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

                // Pilih 1–4 produk acak
                $pickedProducts = $allProducts->random(rand(1, 4));
                $payMethod      = $allPayMethods->random();

                $total = 0;
                $items = [];

                foreach ($pickedProducts as $prod) {
                    $qty      = rand(1, 5);
                    $price    = $prod->sell_price;
                    $subtotal = $qty * $price;
                    $total   += $subtotal;

                    $items[] = [
                        'product_id' => $prod->id,
                        'qty'        => $qty,
                        'price'      => $price,
                        'subtotal'   => $subtotal,
                    ];
                }

                $amountPaid = $total + (($payMethod->type === 'cash') ? rand(0, 3) * 1000 : 0);
                $change     = $amountPaid - $total;

                $trxId = DB::table('transactions')->insertGetId([
                    'code'           => $code,
                    'cashier_id'     => $shift['user_id'],
                    'payment_method' => $payMethod->name,
                    'total'          => $total,
                    'amount_paid'    => $amountPaid,
                    'change'         => $change,
                    'status'         => 'completed',
                    'created_at'     => $trxTime,
                    'updated_at'     => $trxTime,
                ]);

                foreach ($items as $item) {
                    DB::table('transaction_items')->insert(array_merge($item, [
                        'transaction_id' => $trxId,
                        'created_at'     => $trxTime,
                        'updated_at'     => $trxTime,
                    ]));
                }

                // Akumulasi revenue per shift
                if (!isset($shiftRevenueTotals[$shift['id']])) {
                    $shiftRevenueTotals[$shift['id']] = ['revenue' => 0, 'trx_count' => 0];
                }
                $shiftRevenueTotals[$shift['id']]['revenue']   += $total;
                $shiftRevenueTotals[$shift['id']]['trx_count'] += 1;
            }
        }

        // ─── 6. UPDATE revenue & trx_count di tabel shifts ───────────────
        foreach ($shiftRevenueTotals as $shiftId => $totals) {
            DB::table('shifts')->where('id', $shiftId)->update([
                'revenue'   => $totals['revenue'],
                'trx_count' => $totals['trx_count'],
            ]);
        }
    }
}