<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Category::truncate();
        // ساختار پیشنهادی دسته‌های «اسپرت لند» (فقط برای محیط توسعه یا دیتابیس جدید و خالی).
        // ⚠ این سیدر جدول categories را TRUNCATE می‌کند؛ روی دیتابیس دارای داده‌ی واقعی اجرا نشود.
        // TODO(OWNER): پیش از انتشار، دسته‌ها را با کالاهای واقعی فروشگاه تطبیق دهید.
        $categories = [
            'کفش ورزشی' => [
                'کفش دویدن' => [],
                'کفش فوتبال' => ['کفش استوک', 'کفش فوتسال (سالنی)'],
                'کفش بسکتبال' => [],
                'کفش تمرین و بدنسازی' => [],
                'کفش پیاده‌روی' => [],
                'کتانی روزمره' => [],
            ],
            'پوشاک ورزشی' => [
                'تیشرت ورزشی' => [],
                'شلوار ورزشی' => [],
                'گرمکن' => [],
                'سویشرت و هودی' => [],
                'شورت ورزشی' => [],
                'پوشاک فنی' => [],
            ],
            'اکسسوری و تجهیزات' => [
                'کیف و ساک ورزشی' => [],
                'جوراب ورزشی' => [],
                'کلاه' => [],
                'بطری آب' => [],
                'تجهیزات تمرینی' => [],
            ],
        ];

        $sort = 1;

        foreach ($categories as $parentTitle => $children) {

            // سطح اول
            $parent = Category::create([
                'parent_id' => null,
                'title' => $parentTitle,
                'slug' => $this->slug($parentTitle),

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
                    'slug' => $this->slug($childTitle),

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
                        'slug' => $this->slug($grandChildTitle),

                        'description' => "انواع {$grandChildTitle}",
                        'short_description' => $grandChildTitle,
                        'status' => true,
                        'sort' => $grandChildSort++,
                    ]);
                }
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /** اسلاگ فارسی خوانا (الگوی پروژه: فاصله ← خط تیره؛ پرانتز حذف می‌شود) */
    private function slug(string $title): string
    {
        return trim(preg_replace(['/[()]/u', '/\s+/u'], ['', '-'], $title), '-');
    }
}
