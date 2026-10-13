{{--
    لوگوی افقی «اسپرت لند خوشدل»: مونوگرام SL + نام فارسی در دو خط («اسپرت لند» / «خوشدل») و نام لاتین (متن زنده با فونت سایت).
    رنگ نام لاتین همان گرادیان سرخابی ← نارنجی لوگوی رسمی است. نام‌ها از config/brand.php خوانده می‌شوند.
--}}
@props(['lines' => null, 'nameEn' => null, 'size' => 'md', 'latin' => true])
@php
    [$line1, $line2] = array_pad($lines ?? config('brand.name_lines'), 2, null);
    $nameEn ??= config('brand.name_en');
    $markSize = ['sm' => 'w-9 h-8', 'md' => 'w-10 h-9 md:w-12 md:h-11', 'lg' => 'w-16 h-14'][$size] ?? 'w-11 h-10';
    $textSize = ['sm' => 'text-base', 'md' => 'text-lg md:text-2xl', 'lg' => 'text-3xl'][$size] ?? 'text-xl';
    $subSize = ['sm' => 'text-xs', 'md' => 'text-[13px] md:text-base', 'lg' => 'text-lg'][$size] ?? 'text-sm';
    $latinSize = ['sm' => 'text-[9px]', 'md' => 'text-[9px] md:text-[11px]', 'lg' => 'text-xs'][$size] ?? 'text-[10px]';
@endphp
<span {{ $attributes->class('inline-flex items-center gap-2 min-w-0') }}>
    <x-brand.mark class="{{ $markSize }} shrink-0 text-gray-900 dark:text-gray-100"/>
    <span class="flex flex-col min-w-0 leading-none">
        <span class="truncate {{ $textSize }} font-black text-gray-900 dark:text-white [word-spacing:0.12em]">{{ $line1 }}</span>
        @if($line2)
            <span class="truncate mt-0.5 {{ $subSize }} font-bold text-gray-600 dark:text-gray-300">{{ $line2 }}</span>
        @endif
        @if($latin && $nameEn)
            <span class="mt-1 {{ $latinSize }} whitespace-nowrap font-black tracking-[0.16em] bg-gradient-to-l from-[#b30f4e] to-[#ed4e26] bg-clip-text text-transparent" dir="ltr" lang="en">{{ $nameEn }}</span>
        @endif
    </span>
</span>
