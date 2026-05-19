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

        $brand = Brand::firstOrCreate([
            'name' => 'Xiaomi',
            'slug' => 'xiaomi',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Apple',
            'slug' => 'apple',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Samsung',
            'slug' => 'samsung',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Oppo',
            'slug' => 'oppo',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Vivo',
            'slug' => 'vivo',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Realme',
            'slug' => 'realme',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Infinix',
            'slug' => 'infinix',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Xiaomi',
            'slug' => 'xiaomi',
        ]);
        $brand = Brand::firstOrCreate([
            'name' => 'Tecno',
            'slug' => 'tecno',
        ]);
        $this->command->info('Category and brand seeded successfully');
    }
}
