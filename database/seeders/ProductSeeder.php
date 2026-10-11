<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Specification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * سیدر کاتالوگ «برنج خطیر» (فقط برای محیط توسعه).
 *
 * عمداً هیچ محصول، برند، قیمت یا موجودی ساختگی ایجاد نمی‌کند (اطلاعات تجاری باید از مالک بیاید).
 * فقط ساختار ویژگی‌های مناسب برنج را تعریف می‌کند تا ثبت محصول واقعی در پنل آسان شود.
 *
 * TODO(OWNER): محصولات واقعی (نام، رقم، وزن بسته، قیمت، موجودی) را از پنل مدیریت ثبت کنید.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Options (تنوع‌های قابل‌انتخاب در سبد خرید — هر وزن یک variant با قیمت جدا)
            |--------------------------------------------------------------------------
            */
            Option::create([
                'title' => 'وزن بسته',
                'slug' => $this->slug(),
                'sort' => 1,
                'status' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Specifications (مشخصات نمایشی و قابل‌فیلتر؛ مقدار هر محصول در پنل وارد می‌شود)
            |--------------------------------------------------------------------------
            */
            foreach ([
                'رقم برنج',
                'نوع بسته‌بندی',
                'فصل برداشت',
                'منطقه کشت',
            ] as $index => $title) {
                Specification::create([
                    'title' => $title,
                    'slug' => $this->slug(),
                    'type' => 1,
                    'is_filterable' => true,
                    'is_visible' => true,
                    'sort' => $index + 1,
                    'status' => true,
                ]);
            }
        });
    }

    private function slug(): string
    {
        return Str::lower(Str::random(16));
    }
}
