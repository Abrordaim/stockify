<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\StockTransaction;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil kategori
        $elektronik = Category::where('name', 'Elektronik')->first();
        $pakaian = Category::where('name', 'Pakaian')->first();
        $makanan = Category::where('name', 'Makanan & Minuman')->first();
        $rumahTangga = Category::where('name', 'Peralatan Rumah Tangga')->first();
        $atk = Category::where('name', 'Alat Tulis Kantor')->first();

        // Ambil supplier
        $supplierMakmur = Supplier::where('name', 'PT Sumber Makmur')->first() ?? Supplier::first();
        $supplierJaya = Supplier::where('name', 'CV Jaya Abadi')->first() ?? Supplier::first();
        $supplierSentosa = Supplier::where('name', 'UD Sentosa')->first() ?? Supplier::first();

        // Ambil user manajer untuk pencatat transaksi stok awal
        $manager = User::where('role', 'manager')->first() ?? User::first();

        $productsData = [
            // 1. Laptop (Elektronik)
            [
                'category_id' => $elektronik->id,
                'supplier_id' => $supplierMakmur->id,
                'name' => 'Laptop ASUS Vivobook 14',
                'sku' => 'ELK-ASUS-V14',
                'description' => 'Laptop produktivitas tipis dan ringan dengan layar 14 inci FHD.',
                'purchase_price' => 7500000,
                'selling_price' => 8999000,
                'image' => null,
                'minimum_stock' => 5,
                'initial_stock' => 15,
                'attributes' => [
                    ['name' => 'Processor', 'value' => 'Intel Core i5-1235U'],
                    ['name' => 'RAM', 'value' => '8GB DDR4'],
                    ['name' => 'Storage', 'value' => '512GB NVMe SSD'],
                    ['name' => 'Warna', 'value' => 'Quiet Blue'],
                ],
            ],
            // 2. Mouse Wireless (Elektronik)
            [
                'category_id' => $elektronik->id,
                'supplier_id' => $supplierMakmur->id,
                'name' => 'Logitech B170 Wireless Mouse',
                'sku' => 'ELK-LOGI-B170',
                'description' => 'Mouse nirkabel 2.4 GHz dengan receiver USB plug and play.',
                'purchase_price' => 110000,
                'selling_price' => 159000,
                'image' => null,
                'minimum_stock' => 10,
                'initial_stock' => 30,
                'attributes' => [
                    ['name' => 'Konektivitas', 'value' => 'Wireless 2.4GHz'],
                    ['name' => 'DPI', 'value' => '1000 DPI'],
                    ['name' => 'Warna', 'value' => 'Hitam'],
                ],
            ],
            // 3. Kaos Polos (Pakaian)
            [
                'category_id' => $pakaian->id,
                'supplier_id' => $supplierJaya->id,
                'name' => 'Kaos Polos Cotton Combed 30s',
                'sku' => 'CLO-TSHIRT-C30',
                'description' => 'Kaos polos bahan katun combed 30s adem dan menyerap keringat.',
                'purchase_price' => 35000,
                'selling_price' => 55000,
                'image' => null,
                'minimum_stock' => 20,
                'initial_stock' => 60,
                'attributes' => [
                    ['name' => 'Bahan', 'value' => '100% Cotton Combed 30s'],
                    ['name' => 'Ukuran', 'value' => 'L'],
                    ['name' => 'Warna', 'value' => 'Hitam'],
                ],
            ],
            // 4. Kemeja Flanel (Pakaian) - Contoh stok menipis (Low Stock untuk testing)
            [
                'category_id' => $pakaian->id,
                'supplier_id' => $supplierJaya->id,
                'name' => 'Kemeja Flanel Pria Lengan Panjang',
                'sku' => 'CLO-FLANNEL-RED',
                'description' => 'Kemeja flanel kasual pria motif kotak-kotak.',
                'purchase_price' => 95000,
                'selling_price' => 145000,
                'image' => null,
                'minimum_stock' => 10,
                'initial_stock' => 4, // Di bawah minimum_stock (10) -> is_low_stock = true
                'attributes' => [
                    ['name' => 'Bahan', 'value' => 'Flanel Wol'],
                    ['name' => 'Ukuran', 'value' => 'XL'],
                    ['name' => 'Motif', 'value' => 'Kotak Merah Hitam'],
                ],
            ],
            // 5. Kopi Bubuk Arabika (Makanan & Minuman)
            [
                'category_id' => $makanan->id,
                'supplier_id' => $supplierSentosa->id,
                'name' => 'Kopi Arabika Gayo Specialty 250g',
                'sku' => 'FNB-KOPI-GAYO250',
                'description' => 'Kopi bubuk single origin Arabika Gayo Aceh kemasan standing pouch.',
                'purchase_price' => 45000,
                'selling_price' => 70000,
                'image' => null,
                'minimum_stock' => 15,
                'initial_stock' => 40,
                'attributes' => [
                    ['name' => 'Berat Bersih', 'value' => '250 gram'],
                    ['name' => 'Roast Level', 'value' => 'Medium Roast'],
                    ['name' => 'Bentuk', 'value' => 'Bubuk Halus'],
                ],
            ],
            // 6. Kertas HVS (ATK) - Contoh stok menipis
            [
                'category_id' => $atk->id,
                'supplier_id' => $supplierSentosa->id,
                'name' => 'Kertas HVS PaperOne A4 75gsm (1 Rim)',
                'sku' => 'ATK-HVS-A4-75',
                'description' => 'Kertas cetak HVS ukuran A4 ketebalan 75 gram, isi 500 lembar.',
                'purchase_price' => 42000,
                'selling_price' => 52000,
                'image' => null,
                'minimum_stock' => 20,
                'initial_stock' => 8, // Di bawah minimum_stock (20) -> is_low_stock = true
                'attributes' => [
                    ['name' => 'Ukuran', 'value' => 'A4 (210 x 297 mm)'],
                    ['name' => 'Ketebalan', 'value' => '75 gsm'],
                    ['name' => 'Isi', 'value' => '500 lembar/rim'],
                ],
            ],
            // 7. Blender (Peralatan Rumah Tangga)
            [
                'category_id' => $rumahTangga->id,
                'supplier_id' => $supplierMakmur->id,
                'name' => 'Philips Blender Plastik 2 Liter',
                'sku' => 'HOU-BLENDER-PH2',
                'description' => 'Blender serbaguna kapasitas 2 liter dengan 4 mata pisau stainless steel.',
                'purchase_price' => 380000,
                'selling_price' => 485000,
                'image' => null,
                'minimum_stock' => 5,
                'initial_stock' => 12,
                'attributes' => [
                    ['name' => 'Kapasitas', 'value' => '2.0 Liter'],
                    ['name' => 'Daya', 'value' => '290 Watt'],
                    ['name' => 'Garansi', 'value' => '2 Tahun Resmi'],
                ],
            ],
            // 8. Ballpoint Box (ATK)
            [
                'category_id' => $atk->id,
                'supplier_id' => $supplierSentosa->id,
                'name' => 'Pulpen Gel Standard 0.5mm (Pack 12 pcs)',
                'sku' => 'ATK-PEN-STD05',
                'description' => 'Pulpen tinta gel hitam ketebalan 0.5mm cepat kering.',
                'purchase_price' => 24000,
                'selling_price' => 36000,
                'image' => null,
                'minimum_stock' => 15,
                'initial_stock' => 50,
                'attributes' => [
                    ['name' => 'Ketebalan Tip', 'value' => '0.5 mm'],
                    ['name' => 'Warna Tinta', 'value' => 'Hitam'],
                    ['name' => 'Kemasan', 'value' => 'Box 12 pcs'],
                ],
            ],
        ];

        foreach ($productsData as $data) {
            $attributes = $data['attributes'] ?? [];
            $initialStock = $data['initial_stock'] ?? 0;

            unset($data['attributes'], $data['initial_stock']);

            // Buat produk baru atau perbarui jika SKU sudah ada
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                $data
            );

            // Buat atribut produk
            foreach ($attributes as $attr) {
                $product->productAttributes()->updateOrCreate(
                    [
                        'name' => $attr['name'],
                    ],
                    [
                        'value' => $attr['value'],
                    ]
                );
            }

            // Catat transaksi barang masuk awal (Initial Stock) jika kuantitas > 0
            if ($initialStock > 0 && $manager) {
                // Cek apakah sudah pernah dibuat transaksi stok awal untuk produk ini
                $existingTransaction = StockTransaction::where('product_id', $product->id)
                    ->where('type', 'in')
                    ->where('notes', 'Stok awal (Seeder)')
                    ->first();

                if (!$existingTransaction) {
                    StockTransaction::create([
                        'product_id' => $product->id,
                        'user_id' => $manager->id,
                        'type' => 'in',
                        'quantity' => $initialStock,
                        'date' => now()->toDateString(),
                        'status' => 'completed',
                        'notes' => 'Stok awal (Seeder)',
                    ]);
                }
            }
        }
    }
}
