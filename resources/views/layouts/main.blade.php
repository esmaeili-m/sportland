<!doctype html>
<html lang="fa" dir="rtl">

<head>
    @php

        $settings = \App\Models\Setting::cachedAll();

        // لوگو/فاوآیکون آپلودی فقط وقتی config/brand.php اجازه دهد (دیتابیس فعلاً با پروژه‌های دیگر مشترک است)
        $favicon = config('brand.use_uploaded_favicon') ? ($settings['favicon'] ?? null) : null;
        $logo = config('brand.use_uploaded_logo') ? ($settings['logo'] ?? null) : null;
        // نام برند: config/brand.php
        $siteName = config('brand.name');
        $siteNameEn = config('brand.name_en');
        $siteTitle = config('brand.title');
        $siteAbout = config('brand.about');

        // سئوی صفحه: کامپوننت صفحه در mount با view()->share('seo', $model->getSeoPayload()) می‌فرستد
        $seo = $seo ?? [];
        $pageTitle = filled($seo['title'] ?? null) && ! request()->routeIs('home')
            ? $seo['title'] . ' | ' . $siteTitle
            : $siteTitle;
        $pageDescription = ($seo['description'] ?? null) ?: ($settings['meta_description'] ?? config('brand.description'));
        $pageImage = ($seo['og_image'] ?? null) ?: ($logo ? asset('storage/' . $logo) : asset('main/images/brand/og-default.png'));
        $pageCanonical = ($seo['canonical'] ?? null) ?: url()->current();
        $robots = (! empty($seo['no_index']) ? 'noindex' : 'index') . ', ' . (! empty($seo['no_follow']) ? 'nofollow' : 'follow');
    @endphp

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>{{ $pageTitle }}</title>

    <meta name="description"
          content="{{ $pageDescription }}">

    <meta name="keywords"
          content="{{ ($seo['keywords'] ?? null) ?: ($settings['meta_keywords'] ?? $siteName . ', کفش ورزشی, لباس ورزشی, تجهیزات ورزشی') }}">

    <meta name="robots" content="{{ $robots }}">

    <meta name="author"
          content="{{ $siteName }}">

    <meta name="copyright"
          content="All rights belong to {{ $siteName }}.">

    @if($favicon)
        {{-- فاوآیکون آپلودشده در پنل > تنظیمات --}}
        <link rel="icon" href="{{ asset('storage/' . $favicon) }}">
        <link rel="apple-touch-icon" href="{{ asset('storage/' . $favicon) }}">
    @else
        {{-- نشان «اسپرت لند» (public/main/images/brand) --}}
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('main/images/favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('main/images/favicon_io/favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('main/images/favicon_io/apple-touch-icon.png') }}">
    @endif
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#007375" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#060b0d" media="(prefers-color-scheme: dark)">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="{{ $seo['og_type'] ?? 'website' }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ $siteTitle }}">
    <meta property="og:title" content="{{ ($seo['og_title'] ?? null) ?: $pageTitle }}">
    <meta property="og:description" content="{{ ($seo['og_description'] ?? null) ?: $pageDescription }}">
    <meta property="og:url" content="{{ $pageCanonical }}">
    <meta property="og:image" content="{{ $pageImage }}">
    <meta name="twitter:card" content="{{ $seo['twitter_card'] ?? 'summary_large_image' }}">
    <meta name="twitter:title" content="{{ ($seo['twitter_title'] ?? null) ?: $pageTitle }}">
    <meta name="twitter:description" content="{{ ($seo['twitter_description'] ?? null) ?: $pageDescription }}">
    <meta name="twitter:image" content="{{ ($seo['twitter_image'] ?? null) ?: $pageImage }}">
    <link rel="canonical" href="{{ $pageCanonical }}">

    {{-- داده ساختاریافته سازمان و وب‌سایت: فقط فیلدهای واقعی. تلفن، نشانی و شبکه‌های اجتماعی تا تکمیل اطلاعات مالک اضافه نمی‌شوند — TODO(OWNER) --}}
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/') . '#organization',
                'name' => $siteName,
                'alternateName' => 'Sport Land',
                'url' => url('/'),
                'logo' => $logo ? asset('storage/' . $logo) : asset('main/images/favicon_io/icon-512.png'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/') . '#website',
                'name' => $siteTitle,
                'url' => url('/'),
                'inLanguage' => 'fa-IR',
                'publisher' => ['@id' => url('/') . '#organization'],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('search') . '?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @stack('structured-data')
    <link rel="stylesheet" href="{{asset('main/js/plugin/story-player/styles.css')}}?v=4.0.0">
    <link rel="stylesheet" href="{{asset('main/js/plugin/swiper/swiper-bundle.min.css')}}">
    <link rel="stylesheet" href="{{asset('main/css/app.css')}}">
    <style>
        @media (max-width: 767px) {
            .mainHeroSwiper .swiper-pagination {
                display: none !important;
            }
        }
    </style>
    @stack('styles')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-100 dark:bg-gray-950 min-h-screen transition-colors duration-700 selection:bg-brown-500/30 selection:text-brown-600 overflow-x-hidden">

{{-- لودر صفحه (قبل از نمایش محتوا) --}}
<x-layout.page-loader :name="$siteName" :latin="$siteNameEn" />

<!-- HEADER -->
<header class="sticky top-0 z-50 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-950">
{{--    <div class="bg-primary-900 text-white py-1.5 text-center text-[11px] font-medium tracking-wide hidden md:block">--}}
{{--        <p>🎉 جشنواره زمستانی: تا ۵۰٪ تخفیف روی تمام محصولات  | کد تخفیف: <span class="text-secondary-400">WINTER2025</span></p>--}}
{{--    </div>--}}

    <div class="lg:container">
        <div class="flex items-center justify-between h-16 md:h-20 gap-2 md:gap-8 px-3 md:px-2">

            {{-- راست: منو + لوگو --}}
            <div class="flex items-center gap-1 md:gap-4 min-w-0">
                <button id="resMenu" type="button" aria-label="منو"
                        class="lg:hidden shrink-0 w-10 h-10 flex items-center justify-center rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-6 h-6 text-gray-700 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <a href="/" class="flex items-center min-w-0" aria-label="{{ $siteName }}">
                    @if($logo)
                        <img src="{{ asset('storage/' . $logo) }}"
                             class="h-9 md:h-14 w-auto max-w-[120px] md:max-w-[160px] object-contain"
                             alt="{{ $siteName }}">
                    @else
                        <x-brand.logo :name="$siteName"/>
                    @endif
                </a>
            </div>

            <!-- Search (دسکتاپ) -->
            <livewire:layout.search />

            {{-- چپ: اکشن‌ها (در موبایل ورود و سبد خرید در نوار پایین هستند) --}}
            <div class="flex items-center gap-1.5 md:gap-3 shrink-0">

                <button id="mobile-search-toggle" type="button" aria-label="جستجو"
                        class="md:hidden w-10 h-10 flex items-center justify-center rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>

                <button id="dark-mode-toggle" type="button" aria-label="حالت تاریک"
                        class="w-10 h-10 md:w-auto md:h-auto md:p-2.5 flex items-center justify-center rounded-xl md:border border-gray-200/50 dark:border-white/10 md:bg-white/40 md:dark:bg-white/5 hover:bg-gray-100 md:hover:border-primary-500/50 md:hover:bg-white/80 dark:hover:bg-white/10 text-gray-600 dark:text-gray-400 hover:text-primary-500 transition-all duration-300 md:shadow-sm">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                </button>

                <div class="relative group hidden md:block">
                    <a href="{{ auth()->check() ? route('user.dashboard') : route('login')}}"
                       class="flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200/50 dark:border-white/10 bg-white/40 dark:bg-white/5 backdrop-blur-md hover:border-primary-500/50 hover:bg-primary-50/50 dark:hover:bg-primary-500/10 transition-all duration-300 group shadow-sm">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-primary-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span class="text-xs font-black text-gray-700 dark:text-gray-200 hidden lg:block uppercase tracking-tighter">{{ auth()->check() ? (trim(auth()->user()->full_name) ?: auth()->user()->mobile) : 'ورود یا ثبت ‌نام' }}</span>
                    </a>
                </div>
                @auth
                    <div class="hidden md:block">
                        <livewire:main.cart />
                    </div>
                @endauth
            </div>

        </div>
    </div>

    <livewire:layout.navbar />

</header>
<!-- END HEADER -->

<main class="space-y-12 mx-2 md:mx-0">
    {{$slot}}
</main>

<!-- FOOTER -->
<footer class="mx-2  relative amazing-glass-footer pt-24 pb-12 overflow-hidden transition-colors duration-500">

    <div class="lg:container relative z-10">



        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-24">

            <div class="md:col-span-2 lg:col-span-2 space-y-8">
                <div class="flex items-center gap-4">
                    <x-brand.mark tile class="w-14 h-14 shrink-0"/>
                    <span class="flex flex-col leading-none">
                        <span class="text-2xl font-black text-gray-900 dark:text-white [word-spacing:0.12em]">{{ $siteName }}</span>
                        <span class="mt-1.5 text-xs font-black tracking-[0.28em] bg-gradient-to-l from-[#b30f4e] to-[#ed4e26] bg-clip-text text-transparent" dir="ltr" lang="en">{{ $siteNameEn }}</span>
                    </span>
                </div>
                {{-- متن پیش‌فرض بدون ادعای کیفیت/منشأ؛ متن واقعی «درباره» از پنل > تنظیمات --}}
                <p class="text-sm text-gray-600 dark:text-gray-400 leading-8 font-medium max-w-md">
                    {{ $siteAbout ?? ('فروشگاه اینترنتی ' . $siteName . '؛ کفش ورزشی، لباس ورزشی و تجهیزات تمرینی، با انتخاب سایز و رنگ و پیگیری سفارش از حساب کاربری.') }}
                </p>
                {{-- شبکه‌های اجتماعی از دیتابیس (پنل > شبکه‌های اجتماعی) --}}
                <x-main.site-socials />
            </div>

            <div class="space-y-8">
                <h3 class="footer-title">دسترسی سریع</h3>
                <ul class="space-y-5">
                    @foreach(\App\Models\Category::active()->whereNotNull('parent_id')->orderBy('sort')->take(4)->pluck('title','slug') as $k => $c)
                        <li><a href="{{route('categories.show',$k)}}" class="footer-link">{{$c}}</a></li>

                    @endforeach
                </ul>
            </div>

            <div class="space-y-8">
                <h3 class="footer-title">راهنمای خرید</h3>
                <ul class="space-y-5">
                    {{-- پیگیری سفارش و پشتیبانی: داشبورد کاربر (middleware auth؛ مهمان به ورود هدایت و سپس برگردانده می‌شود) --}}
                    <li><a href="{{ route('user.dashboard', ['tab' => 'orders']) }}" class="footer-link">پیگیری سفارش</a></li>
                    <li><a href="{{ route('page.show', \App\Support\Sections\ReturnPolicy::PAGE_SLUG) }}" class="footer-link">شرایط مرجوعی</a></li>
                    <li><a href="{{ route('page.show', 'faq') }}" class="footer-link">سوالات متداول</a></li>
                    <li><a href="{{ route('user.dashboard', ['tab' => 'tickets']) }}" class="footer-link">تماس با پشتیبانی</a></li>
                </ul>
            </div>

            {{-- نمادهای اعتماد (اینماد/ساماندهی) عمداً نمایش داده نمی‌شوند تا مجوز واقعی ثبت شود.
                 TODO(OWNER): کد نماد اعتماد الکترونیکی و ساماندهی را دریافت و اینجا قرار دهید. --}}
        </div>

        <div class="pt-10 border-t border-black/5 dark:border-white/5 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-4">
                <p class="text-[11px] text-gray-500 font-bold">© {{ $siteTitle }} · طراحی و توسعه توسط <span class="text-gray-900 dark:text-white font-black underline decoration-primary-500/40 decoration-4">STARTWEBONE</span>.</p>
            </div>
            {{-- فقط صفحه‌هایی که وجود دارند؛ قوانین و حریم خصوصی پس از نوشته‌شدن متن واقعی اضافه شوند — TODO(OWNER) --}}
            <nav aria-label="پیوندهای فروشگاه" class="flex flex-wrap justify-center gap-x-8 gap-y-3">
                <a href="{{ route('page.show', 'about-us') }}" class="legal-link">درباره ما</a>
                <a href="{{ route('page.show', 'contact-us') }}" class="legal-link">تماس با ما</a>
                <a href="{{ route('brands.list') }}" class="legal-link">برندها</a>
            </nav>
        </div>
    </div>
</footer>
<!-- END FOOTER -->

<!-- NAV MOBILE -->
<div class="fixed bottom-4 left-1/2 -translate-x-1/2 w-[92%] max-w-[400px] z-50 md:hidden">

    <div class="relative bg-white dark:bg-gray-950 border border-gray-200 dark:border-gray-800 rounded-3xl px-2 py-3">

        <ul class="flex items-center justify-around">

            <!-- Home -->
            <li>
                <a href="/" @if(request()->routeIs('home')) aria-current="page" @endif class="flex flex-col items-center gap-1 px-3 py-1 min-w-11 {{ request()->routeIs('home') ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>

                    <span class="text-[11px] font-bold">
                        خانه
                    </span>
                </a>
            </li>

            <!-- Categories -->
            <li>
                <a href="{{ route('page.show', 'categories') }}" class="flex flex-col items-center gap-1 px-3 py-1 min-w-11 text-gray-500 dark:text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>

                    <span class="text-[11px] font-bold">
                        دسته‌ها
                    </span>
                </a>
            </li>

            <!-- Cart -->
            <!-- Cart -->
            <li class="-mt-8">
                <button
                    type="button"
                    x-data
                    @click="$dispatch('open-cart')"
                    aria-label="سبد خرید"
                    class="relative flex items-center justify-center w-14 h-14 bg-brown-600 rounded-2xl text-white"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                        />
                    </svg>
                    @auth
                        <livewire:main.cart-count />
                    @endauth
                </button>
            </li>

            <!-- Wishlist -->
            <li>
                <a href="{{ route('user.dashboard', ['tab' => 'wishlist']) }}" class="flex flex-col items-center gap-1 px-3 py-1 min-w-11 text-gray-500 dark:text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>

                    <span class="text-[11px] font-bold">
                        علاقه‌مندی
                    </span>
                </a>
            </li>

            <!-- Profile -->
            <li>
                <a href="{{ auth()->check() ? route('user.dashboard') : route('login')}}" class="flex flex-col items-center gap-1 px-3 py-1 min-w-11 text-gray-500 dark:text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>

                    <span class="text-[11px] font-bold">
                        پروفایل
                    </span>
                </a>
            </li>

        </ul>

    </div>
</div>
<!-- END NAV MOBILE -->

<!-- SEARCH MODAL -->
<div id="search-modal" class="fixed inset-0 z-[2000] invisible opacity-0 transition-all duration-300 md:hidden flex flex-col">
    <div class="absolute inset-0 bg-gray-900/60 dark:bg-black/90 backdrop-blur-2xl"></div>

    <div class="relative flex-1 flex flex-col bg-white/10 dark:bg-black/20 overflow-hidden">

        <div class="flex items-center justify-between p-5 border-b border-white/10">
            <span class="text-xl font-black text-gray-800 dark:text-white">جستجو در <span class="text-primary-500">{{ $siteName }}</span></span>
            <button id="close-search-modal" type="button" aria-label="بستن جستجو" class="p-2 bg-white/10 rounded-full text-gray-500 dark:text-gray-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <livewire:layout.search variant="mobile" />
    </div>
</div>
<!-- END SEARCH MODAL -->

<!--QUICK VIEW-->
<livewire:main.quick-view />
<!--END QUICK VIEW-->

<!--LOGIN MODAL-->
<!--END LOGIN MODAL-->

<!--CART DRAWER-->
<livewire:layout.cart />
<!--END CART DRAWER-->

<!--Mobile Menu-->
<div id="mobile-menu" class="fixed inset-0 z-[1100] pointer-events-none">
    <div class="absolute inset-0 bg-black/40 dark:bg-black/70 opacity-0 transition-opacity duration-500 backdrop-blur-sm menu-overlay cursor-pointer"></div>

    <div class="absolute right-0 top-0 h-full w-[320px] bg-white/70 dark:bg-gray-950/80 backdrop-blur-md shadow-[-15px_0_50px_rgba(0,0,0,0.2)] transform translate-x-full transition-transform duration-500 pointer-events-auto flex flex-col border-l border-white/40 dark:border-white/10">

        <div class="p-5 border-b border-white/40 dark:border-gray-800 flex justify-between items-center bg-white/30 dark:bg-gray-900/30">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-brown-600 rounded-lg flex items-center justify-center shadow-lg shadow-brown-500/40">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M4 6h16M4 12h16m-7 6h7" stroke-width="2.5" stroke-linecap="round"/>
                    </svg>
                </div>
                <span class="font-black text-xl dark:text-white">منوی اصلی</span>
            </div>
            <button type="button" aria-label="بستن منو" class="close-menu p-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 rounded-xl transition-all border border-transparent hover:border-red-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M6 18L18 6M6 6l12 12" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar pt-2">

            <div class="px-4 mb-6">
                <a href="{{ auth()->check() ? route('user.dashboard') : route('login')}}"  class="p-4 rounded-[2rem] bg-gradient-to-br from-brown-600 to-brown-700 text-white shadow-lg shadow-brown-500/20 flex items-center justify-between cursor-pointer group transition-all active:scale-95">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <div>
                            @auth
                                <span class="block font-black text-sm">{{ trim(auth()->user()->full_name) ?: auth()->user()->mobile }}</span>
                                <span class="block text-[11px] opacity-80 mt-0.5">مشاهده پنل کاربری</span>
                            @else
                                <span class="block font-black text-sm">ورود یا ثبت‌نام</span>
                                <span class="block text-[11px] opacity-80 mt-0.5">با شماره موبایل وارد شوید</span>
                            @endauth
                        </div>
                    </div>
                    <svg class="w-5 h-5 opacity-50 group-hover:translate-x-[-5px] transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15 19l-7-7 7-7" stroke-width="3"/>
                    </svg>
                </a>
            </div>
            <livewire:layout.mobile-navbar />

        </div>
    </div>
</div>
<!--END Mobile Menu-->

<script src="{{asset('main/js/plugin/swiper/swiper-bundle.min.js')}}"></script>
<script src="{{asset('main/js/dependencies/swiper-script.js')}}"></script>
<script src="{{asset('main/js/plugin/sweetalert/sweetalert.min.js')}}"></script>

<script src="{{asset('main/js/dependencies/app.js')}}"></script>
<script src="{{asset('dashboard')}}/libs/sweetalert2/sweetalert2@11"></script>

{{-- نوار مقایسه محصولات (در خود صفحه مقایسه نمایش داده نمی‌شود) --}}
@unless(request()->routeIs('compare.index'))
    <livewire:main.compare-bar />
@endunless

<!-- INITIAL STORY SECTION -->
@livewireScripts
@stack('scripts')
<script>
    document.addEventListener('livewire:init', () => {

        Livewire.on('alert', (event) => {

            Swal.fire({
                position: 'top-end',
                icon: event.type ?? 'success',
                title: event.title ?? '',
                text: event.message ?? '',

                showConfirmButton: false,
                customClass: {
                    popup: 'my-swal-popup'
                },
                timer: 2000,
                toast: true
            });

        });

    });
</script>

</body>

</html>
