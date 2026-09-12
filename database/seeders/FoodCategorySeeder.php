<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Seeder;

class FoodCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Oatmeal', 'type' => 'safe'],
            ['name' => 'Banana', 'type' => 'safe'],
            ['name' => 'Melon', 'type' => 'safe'],
            ['name' => 'Lean chicken', 'type' => 'safe'],
            ['name' => 'Steamed vegetables', 'type' => 'safe'],
            ['name' => 'Citrus', 'type' => 'avoid'],
            ['name' => 'Tomato', 'type' => 'avoid'],
            ['name' => 'Fried foods', 'type' => 'avoid'],
            ['name' => 'Chocolate', 'type' => 'avoid'],
            ['name' => 'Coffee', 'type' => 'avoid'],
            ['name' => 'Spicy foods', 'type' => 'avoid'],
        ];

        foreach ($categories as $category) {
            FoodCategory::updateOrCreate(['name' => $category['name']], $category);
        }
    }
}
