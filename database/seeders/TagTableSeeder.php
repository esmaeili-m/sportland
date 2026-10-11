<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TagTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Tag::truncate();
        $tags = [
            // برچسب‌های عمومی برای مقالات آموزشی (بدون ادعای اختصاصی درباره محصول)
            'کفش ورزشی',
            'کفش دویدن',
            'کفش فوتبال',
            'لباس ورزشی',
            'بدنسازی',
            'دویدن',
            'راهنمای انتخاب سایز',
            'نگهداری کفش ورزشی',
        ];
        foreach ($tags as $tag) {
            $slug = Tag::generateSlugFrom($tag);
            Tag::create([
                'title' => $tag,
                'slug' => $slug
            ]);
        }

    }
}
