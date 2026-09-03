<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Elektronik', 'description' => 'Perangkat elektronik dan aksesoris'],
            ['name' => 'Pakaian', 'description' => 'Pakaian pria, wanita, dan anak-anak'],
            ['name' => 'Makanan & Minuman', 'description' => 'Produk makanan dan minuman'],
            ['name' => 'Peralatan Rumah Tangga', 'description' => 'Peralatan dan perlengkapan rumah tangga'],
            ['name' => 'Alat Tulis Kantor', 'description' => 'Perlengkapan kantor dan alat tulis'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
