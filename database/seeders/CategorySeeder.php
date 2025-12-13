<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Herbs',
            'slug'=>'herbs'
        ]);
        Category::create([
            'name' => 'Foods',
            'slug'=>'foods'
        ]);
        Category::create([
            'name' => 'Drinks',
            'slug'=>'drinks'
        ]);
    }
}
