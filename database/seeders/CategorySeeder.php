<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Category::truncate();
        // دسته‌های پایه «برنج خطیر» (فقط برای محیط توسعه).
        // TODO(OWNER): دسته‌بندی‌ها را با ارقام و بسته‌بندی‌های واقعی محصولات (مثلاً نام رقم برنج) جایگزین کنید.
        $categories = [
            'برنج' => [
                'برنج ایرانی' => [],
            ],
        ];

        $sort = 1;

        foreach ($categories as $parentTitle => $children) {

            // سطح اول
            $parent = Category::create([
                'parent_id' => null,
                'title' => $parentTitle,
                'slug' => $this->randomSlug(),

                'description' => "دسته‌بندی {$parentTitle}",
                'short_description' => "انواع {$parentTitle}",
                'status' => true,
                'sort' => $sort++,
            ]);

            $childSort = 1;

            foreach ($children as $childTitle => $grandChildren) {

                // سطح دوم
                $child = Category::create([
                    'parent_id' => $parent->id,
                    'title' => $childTitle,
                    'slug' => $this->randomSlug(),

                    'description' => "انواع {$childTitle}",
                    'short_description' => $childTitle,
                    'status' => true,
                    'sort' => $childSort++,
                ]);

                $grandChildSort = 1;

                foreach ($grandChildren as $grandChildTitle) {

                    // سطح سوم
                    Category::create([
                        'parent_id' => $child->id,
                        'title' => $grandChildTitle,
                        'slug' => $this->randomSlug(),

                        'description' => "انواع {$grandChildTitle}",
                        'short_description' => $grandChildTitle,
                        'status' => true,
                        'sort' => $grandChildSort++,
                    ]);
                }
            }
            }

    }
    private function randomSlug(): string
    {
        return Str::lower(Str::random(12));
    }
}
