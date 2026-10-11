<?php

namespace Database\Seeders;

use App\Models\Option;
use App\Models\Specification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * سیدر ساختار کاتالوگ «اسپرت لند» (فقط برای محیط توسعه یا دیتابیس جدید).
 *
 * عمداً هیچ محصول، برند، قیمت، موجودی یا جدول تبدیل سایز ساختگی ایجاد نمی‌کند (اطلاعات تجاری باید از مالک بیاید).
 * فقط ویژگی‌های قیمت‌ساز (سایز، رنگ) و مشخصات فنی رایج کالای ورزشی را تعریف می‌کند تا ثبت محصول واقعی در پنل آسان شود.
 * عنوان ویژگی‌های سایز باید «سایز» داشته باشد تا صفحه محصول انتخاب سایز را اجباری کند و راهنمای سایز را نشان دهد.
 *
 * TODO(OWNER): محصولات واقعی (نام، برند، قیمت، موجودی هر سایز/رنگ) را از پنل مدیریت ثبت کنید.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | Options (ویژگی‌های قیمت‌ساز؛ هر ترکیب سایز/رنگ یک variant با موجودی جدا)
            |--------------------------------------------------------------------------
            */
            $options = [
                // برچسب سایز کفش (EU). اندازه‌ی واقعی هر سایز به برند بستگی دارد و در جدول سایز مالک می‌آید.
                ['title' => 'سایز کفش', 'display_type' => 'buttons', 'values' => array_map('strval', range(36, 46))],
                ['title' => 'سایز پوشاک', 'display_type' => 'buttons', 'values' => ['XS', 'S', 'M', 'L', 'XL', 'XXL']],
                ['title' => 'رنگ', 'display_type' => 'color', 'values' => [
                    'مشکی' => '#111111',
                    'سفید' => '#ffffff',
                    'طوسی' => '#8a8f94',
                    'سرمه‌ای' => '#1f2a44',
                    'آبی' => '#1d6fd1',
                    'قرمز' => '#d12c2c',
                    'سبز' => '#2f8f4e',
                ]],
            ];

            foreach ($options as $index => $data) {
                $option = Option::create([
                    'title' => $data['title'],
                    'slug' => $this->slug(),
                    'display_type' => $data['display_type'],
                    'sort' => $index + 1,
                    'status' => true,
                ]);

                $sort = 1;

                foreach ($data['values'] as $key => $value) {
                    $isColor = is_string($key);

                    $option->values()->create([
                        'title' => $isColor ? $key : $value,
                        'slug' => $this->slug(),
                        'color_code' => $isColor ? $value : null,
                        'sort' => $sort++,
                        'status' => true,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Specifications (مشخصات نمایشی و قابل‌فیلتر؛ مقدار هر محصول در پنل وارد می‌شود)
            |--------------------------------------------------------------------------
            */
            foreach ([
                'نوع ورزش',
                'جنسیت',
                'جنس رویه',
                'جنس زیره',
                'وزن',
                'کشور سازنده',
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
