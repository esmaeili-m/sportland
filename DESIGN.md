---
name: برنج خطیر
description: فروشگاه اینترنتی برنج؛ فارسی، راست‌به‌چپ، اول موبایل
colors:
  paddy-50: "oklch(97.5% 0.014 152)"
  paddy-100: "oklch(94.5% 0.028 152)"
  paddy-400: "oklch(70% 0.1 152)"
  paddy-500: "oklch(51% 0.095 152)"
  paddy-600: "oklch(45% 0.085 152)"
  paddy-700: "oklch(38% 0.072 152)"
  paddy-900: "oklch(24% 0.044 152)"
  saffron-400: "oklch(72% 0.14 52)"
  saffron-500: "oklch(55% 0.15 52)"
  saffron-600: "oklch(49% 0.135 52)"
  grain-50: "oklch(98.6% 0.006 85)"
  grain-100: "oklch(96.4% 0.008 85)"
  grain-200: "oklch(92.2% 0.01 85)"
  grain-400: "oklch(64% 0.012 85)"
  grain-500: "oklch(52% 0.012 85)"
  grain-800: "oklch(27.5% 0.008 85)"
  grain-950: "oklch(14.5% 0.005 85)"
  white: "#ffffff"
typography:
  display:
    fontFamily: "payda, Vazirmatn, Tahoma, sans-serif"
    fontSize: "clamp(1.875rem, 5vw, 2.25rem)"
    fontWeight: 900
    lineHeight: 1.2
  title:
    fontFamily: "payda, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 900
    lineHeight: 1.4
  body:
    fontFamily: "payda, sans-serif"
    fontSize: "1rem"
    fontWeight: 500
    lineHeight: 2.25
  label:
    fontFamily: "payda, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.5
rounded:
  md: "12px"
  lg: "16px"
  xl: "24px"
  card: "40px"
  full: "9999px"
spacing:
  gutter-mobile: "16px"
  sm: "8px"
  md: "16px"
  lg: "32px"
  section: "80px"
components:
  button-primary:
    backgroundColor: "{colors.paddy-500}"
    textColor: "{colors.white}"
    rounded: "{rounded.md}"
    padding: "12px 24px"
  button-primary-hover:
    backgroundColor: "{colors.paddy-700}"
  chip-feature:
    backgroundColor: "{colors.paddy-50}"
    textColor: "{colors.paddy-700}"
    rounded: "{rounded.md}"
    padding: "8px 16px"
  card-product:
    backgroundColor: "{colors.white}"
    rounded: "{rounded.card}"
    padding: "8px"
  badge-discount:
    backgroundColor: "{colors.saffron-500}"
    textColor: "{colors.white}"
    rounded: "{rounded.md}"
---

# Design System: برنج خطیر

## Overview

**Creative North Star: "سفره‌ی شالیزار" (The Paddy Table)**

فروشگاه برنج یک فروشگاه مواد غذایی خانگی است، نه بوتیک مد. رنگ اصلی از سبز شالیزار می‌آید، زمینه‌ها رنگ گرم دانه برنج و کاغذ دارند و زعفرانی فقط برای لحظه‌های تأکید (تخفیف، پیشنهاد ویژه) استفاده می‌شود. حس مطلوب: قابل‌اعتماد، تمیز، خانگی و آرام. اعتماد با شفافیت ساخته می‌شود (قیمت، وزن، زمان ارسال)، نه با شعار.

این سند از روی پیاده‌سازی واقعی پس از تغییر برند (مهر ۱۴۰۵ / اکتبر ۲۰۲۶) نوشته شده است. ساختار بصری قبلی (کارت‌های گرد بزرگ، پنل‌های نیمه‌شفاف) در صدها کامپوننت حفظ شده و فقط رنگ‌آمیزی و جزئیات برند عوض شده است. ساده‌سازی شیشه‌ای‌ها و وزن‌های سنگین تایپ، کار مرحله بعد است.

**Material: شیشه (تصمیم مالک، مهر ۱۴۰۵).** همه کارت‌ها و دکمه‌های فروشگاه شیشه‌ای مات هستند (پس‌زمینه نیمه‌شفاف، blur و لبه روشن) روی بستر رنگی ثابت سبز/زعفرانی که پشت صفحه است. پیاده‌سازی متمرکز در انتهای `resources/css/app.css` (بخش «سیستم شیشه‌ای»، خارج از `@layer`).

**Key Characteristics:**
- سبز شالیزار به‌عنوان تنها رنگ برند برای عمل اصلی (دکمه، لینک، حالت فعال).
- خنثی‌های گرم (ته‌رنگ زرد بسیار کم) به‌جای خاکستری سرد پیش‌فرض Tailwind.
- زعفرانی کم‌مصرف و فقط برای تأکید.
- نشان: خوشه برنجِ خمیده از سنگینی دانه‌ها (موقت تا دریافت لوگوی رسمی).
- فارسی، RTL، اعداد فارسی (فونت FaNum).

## Colors

پالت از دو رنگ طبیعی با ریشه در موضوع (شالیزار و زعفران) و یک خنثی گرم ساخته شده است. همه مقادیر در `resources/css/app.css` → `@theme` تعریف شده‌اند و منبع واحد هستند.

### Primary
- **سبز شالیزار** (paddy-500 / paddy-600): دکمه‌های اصلی، لینک‌ها، حالت فعال، نشان. متن سفید روی paddy-500 کنتراست ۵.۴۹ و روی paddy-600 کنتراست ۷.۱۴ دارد (AA). paddy-400 برای متن و نشان در حالت تیره است (۷.۷ روی پس‌زمینه تیره).

### Secondary
- **زعفرانی** (saffron-500): برچسب تخفیف و پیشنهاد ویژه. متن سفید روی آن ۵.۱۲. saffron-400 فقط در حالت تیره (۷.۶).

### Neutral
- **دانه برنج** (grain-100): پس‌زمینه صفحه.
- **سفید**: سطح کارت‌ها.
- **خاکستری گرم** (grain-500): متن ثانویه. ۵.۵ روی سفید و ۴.۹۶ روی grain-100.
- **grain-400**: فقط متن بزرگ، آیکون و placeholder (۳.۳۶ روی سفید؛ برای متن کوچک کافی نیست).
- **grain-950**: پس‌زمینه حالت تیره.

### Named Rules
**The Single Source Rule.** رنگ برند فقط از توکن خوانده می‌شود (`primary-*` یا نام فنی قدیمی `brown-*` که عمداً هم‌مقدار primary است). مقدار hex یا rgba جدید در کامپوننت‌ها ممنوع است. تنها استثنا `rgba(43,99,61,α)` در چند کلاس دلخواه Tailwind است که معادل paddy-600 است.

**The Saffron Restraint Rule.** زعفرانی فقط برای تخفیف و فوریت است و هرگز رنگ عمل اصلی نیست.

## Typography

**Body/Display Font:** Peyda FaNum (`payda`)، self-host در `public/main/fonts/payda`
**Character:** فونت فارسی هندسی و خوانا با اعداد فارسی.

⚠ مجوز Peyda تجاری است و در این بازطراحی تغییر نکرده است. مالک باید مجوز استفاده وب را تأیید کند؛ جایگزین آزاد: Vazirmatn (OFL).

### Hierarchy
- **Display** (900، حدود 30 تا 36px، 1.2): عنوان صفحه و بخش.
- **Title** (900، 20px): عنوان کارت و بلوک.
- **Body** (500، 16px، line-height بلند ~2.25): متن توضیحی، حداکثر 65ch.
- **Label** (700، 12px): برچسب، نام برند روی کارت، مشخصات. هیچ متنی کوچک‌تر از 11px نباشد.

### Named Rules
**The 11px Floor Rule.** متن کوچک‌تر از 11px ممنوع است (کارت محصول قبلاً 7px داشت و اصلاح شد).

## Layout

موبایل‌محور با نوار ناوبری ثابت پایین در موبایل و هدر چسبان. کانتینر `.container` با `px-3`. حداقل گاتر ۱۶px روی گوشی. بخش‌ها با فاصله‌ی `space-y-12` در `main`. بریک‌پوینت‌های پیش‌فرض Tailwind به‌علاوه‌ی `laptop` (75rem) و `desktop` (90rem). کلاس‌های پویای ستون/فاصله فقط از safelist `@source inline` می‌آیند.

## Elevation & Depth

سیستم ترکیبی است: سطوح عمدتاً تخت با border نازک، و سایه‌ی نرم فقط در hover یا برای عناصر شناور (نوار پایین، drawer). سایه‌های رنگی هاله‌ای قدیمی با رنگ برند جدید جایگزین شده‌اند ولی هنوز در برخی کامپوننت‌ها هستند.

### Named Rules
**The Lift-On-Intent Rule.** سایه پاسخ به تعامل است، نه تزئین دائمی.

## Shapes

گوشه‌های گرد بزرگ (کارت 2rem تا 2.5rem، دکمه 12px، چیپ 12px). نشان برند دایره و قوس طبیعی دارد و با این زبان گرد هماهنگ است.

## Components

### Buttons
- **Glass + نئون نرم (تأیید مالک):** دکمه اصلی = سبز ۸۴٪ نیمه‌شفاف + blur 12px + لبه نئونی خیلی نرم + سایه دولایه ملایم. ثانویه = شیشه روشن با همان لبه و سایه خنثی. خودکار روی button/a با کلاس‌های رنگ برند یا سفید/خاکستری روشن؛ استثنا `.no-glass`.
- **Hover لوکس:** یک نوار نور مورب یک‌بار از روی دکمه عبور می‌کند (::after، ۰.۹ ثانیه)، دکمه ۲px بالا می‌آید و درخشش/سایه عمیق‌تر می‌شود؛ کلیک: فشرده شدن ملایم. با reduced-motion حرکت حذف می‌شود.
- **Primary:** سبز شالیزار با متن سفید، 12px radius. Hover: یک پله تیره‌تر.
- **Focus:** حلقه‌ی 2px سبز با offset 2px (سراسری، `:focus-visible`).

### Chips
- **Feature chip:** زمینه paddy-50، متن paddy-700، border paddy-100. فقط قابلیت‌های واقعی فروشگاه را نشان می‌دهد.

### Cards / Containers
- **کارت محصول:** سفید (نیمه‌شفاف قدیمی)، radius 2.5rem، border خاکستری گرم. برچسب تخفیف قرمز/زعفرانی. دکمه «خرید سریع» سبز.

### Navigation
- هدر سفید و چسبان. لوگو از تنظیمات، یا اگر آپلود نشده باشد `x-brand.logo` (نشان + نام). نوار پایین موبایل با دکمه‌ی سبد مرکزی سبز.

### Glass Cards (Signature)
- هر کارت با گوشه گرد بزرگ و سطح روشن در `main` خودکار شیشه‌ای می‌شود؛ کلاس صریح `.glass-card` و استثنا `.no-glass`.
- کارت داخل کارت بدون blur دوم (کارایی).
- پشتیبانی: `@supports` (مرورگر بدون backdrop-filter → سطح تقریباً مات) و `prefers-reduced-transparency` (سطح کاملاً مات، بدون بستر رنگی).

### Page Loader (Signature)
- دیسک سبز با کاسه برنج: دانه‌ها یکی‌یکی در کاسه می‌ریزند، تپه برنج بالا می‌آید و بخار بلند می‌شود؛ زیر آن «برنج خطیر» و «KHATIR RICE». حالت تیره و `prefers-reduced-motion`. فایل: `resources/views/components/layout/page-loader.blade.php`.

## Do's and Don'ts

### Do:
- **Do** رنگ را فقط از توکن‌های `@theme` بخوانید.
- **Do** برای نبود لوگوی آپلودشده از `<x-brand.logo>` و `<x-brand.mark>` استفاده کنید.
- **Do** آمار و ادعاها را فقط وقتی نشان دهید که داده‌ی واقعی وجود دارد (الگوی بخش «درباره ما»).

### Don't:
- **Don't** ادعای کیفیت، منشأ، سلامت، مجوز یا آمار مشتری را بدون داده‌ی واقعی مالک بنویسید.
- **Don't** نماد اعتماد (اینماد/ساماندهی) ساختگی یا placeholder نمایش دهید.
- **Don't** متن را کوچک‌تر از 11px یا با کنتراست کمتر از 4.5:1 بنویسید.
- **Don't** متن گرادیانی یا هاله‌ی رنگی بدون offset اضافه کنید.
- **Don't** برای یک کارت تکی blur جداگانه بنویسید؛ از سیستم شیشه‌ای مرکزی استفاده کنید.
