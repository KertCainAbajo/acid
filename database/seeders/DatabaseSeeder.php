<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminSeeder::class);

        // Only seed the starter food guidance into an empty table, so redeploys never undo an admin's edits.
        if (FoodCategory::query()->doesntExist()) {
            $this->call(FoodCategorySeeder::class);
        }
    }
}
