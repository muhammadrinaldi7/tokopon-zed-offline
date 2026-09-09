<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryandBrand extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = Category::firstOrCreate([
            'name' => 'Smartphone',
            'slug' => 'smartphone',
        ]);

        $brands = [
            'Xiaomi',
            'Apple',
            'Samsung',
            'Oppo',
            'Vivo',
            'Realme',
            'Infinix',
            'Tecno',
        ];

        foreach ($brands as $brandName) {
            Brand::firstOrCreate([
                'name' => $brandName,
            ]);
        }
        $this->command->info('Category and brand seeded successfully');
    }
}
