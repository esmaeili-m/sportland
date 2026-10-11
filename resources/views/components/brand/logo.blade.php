{{--
    لوگوی افقی «برنج خطیر / KHATIR RICE»: نشان + نام فارسی و لاتین (متن زنده با فونت سایت).
    نام‌ها از config/brand.php خوانده می‌شوند.
--}}
@props(['name' => null, 'nameEn' => null, 'size' => 'md', 'latin' => true])
@php
    $name ??= config('brand.name');
    $nameEn ??= config('brand.name_en');
    $markSize = ['sm' => 'w-8 h-8', 'md' => 'w-9 h-9 md:w-11 md:h-11', 'lg' => 'w-14 h-14'][$size] ?? 'w-10 h-10';
    $textSize = ['sm' => 'text-base', 'md' => 'text-lg md:text-2xl', 'lg' => 'text-3xl'][$size] ?? 'text-xl';
    $latinSize = ['sm' => 'text-[9px]', 'md' => 'text-[9px] md:text-[11px]', 'lg' => 'text-xs'][$size] ?? 'text-[10px]';
@endphp
<span {{ $attributes->class('inline-flex items-center gap-2 min-w-0') }}>
    <x-brand.mark class="{{ $markSize }} shrink-0 text-primary-600 dark:text-primary-400"/>
    <span class="flex flex-col min-w-0 leading-none">
        <span class="truncate {{ $textSize }} font-black text-gray-900 dark:text-white">{{ $name }}</span>
        @if($latin && $nameEn)
            <span class="mt-1 {{ $latinSize }} font-bold tracking-[0.28em] text-primary-700 dark:text-primary-300" dir="ltr" lang="en">{{ $nameEn }}</span>
        @endif
    </span>
</span>
