<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'PT Sumber Makmur',
                'address' => 'Jl. Industri No. 45, Jakarta Utara',
                'phone' => '021-5551234',
                'email' => 'info@sumbermakmur.co.id',
            ],
            [
                'name' => 'CV Jaya Abadi',
                'address' => 'Jl. Raya Cibitung No. 12, Bekasi',
                'phone' => '021-8881234',
                'email' => 'order@jayaabadi.com',
            ],
            [
                'name' => 'UD Sentosa',
                'address' => 'Jl. Pahlawan No. 78, Surabaya',
                'phone' => '031-7771234',
                'email' => 'sentosa@gmail.com',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
