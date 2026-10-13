<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Article;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            RoleTableSeeder::class,
            UserTableSeeder::class,
            CategorySeeder::class,
            TagTableSeeder::class,
            // رجیستری سکشن‌ها قبل از صفحات پیش‌فرض (غیرمخرب)
            SectionTableSeeder::class,
            PageTableSeeder::class,
            ShippingSlotSeeder::class,
            RewardLevelSeeder::class,
        ]);

    }
}
