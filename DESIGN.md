---
name: اسپرت لند | Sport Land
description: فروشگاه اینترنتی کفش، پوشاک و تجهیزات ورزشی؛ فارسی، راست‌به‌چپ، اول موبایل
colors:
  primary-50: "oklch(97.5% 0.02 195)"
  primary-300: "oklch(82% 0.1 193)"
  primary-400: "oklch(74% 0.12 192)"
  primary-500: "oklch(54% 0.1 196)"
  primary-600: "oklch(50% 0.09 197)"
  primary-700: "oklch(43% 0.078 200)"
  primary-900: "oklch(29% 0.05 206)"
  secondary-400: "oklch(74% 0.15 45)"
  secondary-500: "oklch(57% 0.19 38)"
  secondary-600: "oklch(54% 0.18 36)"
  gray-50: "oklch(98.5% 0.003 220)"
  gray-100: "oklch(96.5% 0.005 220)"
  gray-200: "oklch(92.5% 0.007 220)"
  gray-400: "oklch(57.5% 0.013 220)"
  gray-500: "oklch(53% 0.014 220)"
  gray-900: "oklch(20.5% 0.011 220)"
  gray-950: "oklch(14.5% 0.01 220)"
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
    lineHeight: 2
  label:
    fontFamily: "payda, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.5
  price:
    fontFamily: "payda, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 900
    fontVariantNumeric: "tabular-nums"
rounded:
  control: "12px"
  card: "20px"
  card-large: "24px–32px"
  full: "9999px"
spacing:
  gutter-mobile: "16px"
  sm: "8px"
  md: "16px"
  lg: "32px"
  section: "48px"
components:
  button-primary:
    backgroundColor: "{colors.primary-600}"
    textColor: "{colors.white}"
    rounded: "{rounded.control}"
    minHeight: "44px"
  button-primary-hover:
    backgroundColor: "{colors.primary-700}"
  option-chip:
    border: "2px {colors.gray-200}"
    active: "2px {colors.primary-600} on {colors.primary-50}"
    minSize: "48x44px"
  badge-sale:
    backgroundColor: "{colors.secondary-600}"
    textColor: "{colors.white}"
  badge-sold-out:
    backgroundColor: "{colors.gray-800}"
    textColor: "{colors.white}"
---

# Design System: اسپرت لند | Sport Land

## Overview

**Creative North Star: «خط شروع» (The Start Line)**

فروشگاه تجهیزات ورزشی است: سریع، دقیق و قابل‌اعتماد. رنگ اصلی فیروزه‌ای است؛ فیروزه‌ای تیره برای عمل اصلی و سطوح تیره، و فیروزه‌ای روشن برای حالت تیره و تأکیدهای نرم. نارنجی مرجانی فقط برای لحظه‌های فوریت (تخفیف، حراج) است. سطوح تخت و مات‌اند و سایه فقط به تعامل پاسخ می‌دهد. انرژی از نشان ایتالیک، خطوط سرعت و تایپ سنگین می‌آید، نه از افکت‌های تزئینی.

**چرا فیروزه‌ای؟** درخواست مالک؛ در عین حال در بازار تجهیزات ورزشی (که قرمز، مشکی و نارنجی رایج‌اند) متمایز است، حس تازگی، آب و استقامت می‌دهد و با نارنجی مکمل، کنتراست انرژی‌بخش و قابل‌خواندن می‌سازد.

**Material: تخت (تصمیم مالک، مهر ۱۴۰۵).** سیستم شیشه‌ای برند قبلی حذف شد. کارت‌ها سفید (حالت تیره: gray-900) با border نازک و `--shadow-card` هستند. پیاده‌سازی متمرکز در انتهای `resources/css/app.css` (بخش «سطوح و دکمه‌های اسپرت لند»، خارج از `@layer`)؛ کلاس‌های نیمه‌شفاف/blur قدیمی کامپوننت‌ها خودکار مات می‌شوند. استثنا: `.no-flat`.

**Key Characteristics:**
- فیروزه‌ای تنها رنگ عمل اصلی (دکمه، لینک، حالت فعال، انتخاب سایز/رنگ).
- خنثی سرد با ته‌رنگ فیروزه‌ای بسیار کم (hue 220).
- نارنجی مرجانی کم‌مصرف و فقط برای تخفیف و فوریت.
- نشان: S ایتالیک (مسیر پیست) + سه خط سرعت نارنجی.
- فارسی، RTL، اعداد فارسی (فونت FaNum)، قیمت‌ها tabular.

## Colors

همه مقادیر در `resources/css/app.css` → `@theme` تعریف شده‌اند و منبع واحدند. نام فنی `brown-*` هم‌مقدار `primary-*` است (صدها کلاس قدیمی به آن ارجاع می‌دهند).

| نقش | توکن | تقریب hex | کنتراست |
|---|---|---|---|
| دکمه/لینک اصلی | primary-600 | `#007375` | متن سفید ۵.۷:۱ |
| اصلی روشن‌تر | primary-500 | `#008082` | متن سفید ۴.۸:۱ |
| hover / متن لینک روی سفید | primary-700 | `#005c61` | متن سفید ۷.۸:۱ |
| فیروزه‌ای تیره (سطوح تیره، OG) | primary-900 | `#003237` | متن سفید ۱۳.۹:۱ |
| فیروزه‌ای روشن (متن در حالت تیره) | primary-400 / 300 | `#28c2be` / `#6dd9d6` | روی gray-950: ۹.۰ / ۱۱.۸ |
| تأکید / تخفیف | secondary-600 | `#c03809` | متن سفید ۵.۵:۱ |
| تأکید در حالت تیره | secondary-400 | `#f78955` | — (فقط خطوط سرعت و جزئیات) |
| پس‌زمینه صفحه | gray-100 | `#f0f4f6` | — |
| متن ثانویه | gray-500 | `#636e71` | روی سفید ۵.۳، روی gray-100 ۴.۸ |
| متن کمکی/آیکون | gray-400 | `#717b7e` | روی سفید ۴.۴ (فقط آیکون، placeholder، متن ≥ 14px bold) |
| پس‌زمینه حالت تیره | gray-950 | `#060b0d` | — |

رنگ‌های معنایی: `--color-success` (سبز)، `--color-warning`، `--color-danger`، `--color-info` (= primary-600)، `--color-sale` (= secondary-600)، `--color-sold-out` (= gray-500). کامپوننت‌های قدیمی هنوز emerald/amber/red مستقیم دارند (معنایی، نه برند).

### Named Rules
**The Single Source Rule.** رنگ برند فقط از توکن خوانده می‌شود. مقدار hex/rgba جدید در کامپوننت‌ها ممنوع است؛ تنها استثنا `rgba(0,115,117,α)` در چند کلاس دلخواه Tailwind که معادل primary-600 است.

**The Coral Restraint Rule.** نارنجی فقط برای تخفیف، حراج و خطوط سرعت نشان است؛ هرگز رنگ دکمه‌ی عمل اصلی نیست.

## Typography

**فونت:** Peyda FaNum (`payda`)، self-host در `public/main/fonts/payda` (وزن‌های Medium، Bold، Black؛ `font-display` پیش‌فرض). لاتین نشان: Arial/Helvetica بولد با فاصله‌ی حروف باز.
⚠ مجوز وب Peyda تجاری است و مالک تصمیم به حفظ آن گرفته است؛ مجوز باید نزد مالک موجود باشد.

- **Display** (900، ۳۰ تا ۳۶px، 1.2): عنوان صفحه و بخش.
- **Title** (900، 20px): عنوان کارت و بلوک.
- **Body** (500، 16px، ~2): متن توضیحی، حداکثر 65ch.
- **Label** (700، 12px): برچسب، نام برند روی کارت، مشخصات.
- **Price** (900، tabular-nums): قیمت؛ «تومان» در 12px کنار آن.

**The 11px Floor Rule.** متن کوچک‌تر از 11px ممنوع است (برخی کامپوننت‌های قدیمی هنوز 10px دارند؛ در کارهای بعدی اصلاح شوند).

## Layout & Motion

- موبایل‌محور؛ نوار ناوبری شناور پایین در موبایل، هدر چسبان. گاتر حداقل 16px.
- اهداف لمسی ≥ 44px (`min-h-11`) در انتخابگر سایز/رنگ، نوار پایین و دکمه‌های خرید.
- حرکت: transition ۱۸۰ms با `--ease-snap`؛ فقط transform/رنگ/سایه. `prefers-reduced-motion` همه انیمیشن‌ها و transitionها را عملاً خاموش می‌کند (`app.css`).

## Components

### Buttons
- **Primary:** primary-600 توپر، متن سفید، گوشه 12px، hover = primary-700 + `--shadow-lift`، active = scale 0.98. بدون نوار نور یا هاله‌ی نئونی.
- **Secondary:** سطح سفید با border.
- **Focus:** حلقه‌ی 2px فیروزه‌ای با offset 2px (سراسری `:focus-visible`).

### Variant pickers (صفحه محصول)
- سایز/متن: چیپ با border 2px؛ فعال = border فیروزه‌ای روی primary-50. ناموجود = خط‌خورده و `disabled`. «فقط در ترکیب دیگر موجود» = border خط‌چین.
- رنگ: دایره‌ی 44px با `color_code` (اگر `display_type = color` یا مقدارها کد رنگ داشته باشند)؛ ناموجود = خط مورب.
- ویژگی‌ای که عنوان یا slug آن «سایز/size» دارد، انتخاب آگاهانه می‌خواهد: تا انتخاب نشود، افزودن به سبد با پیام خطای درون‌خطی متوقف می‌شود و لینک «راهنمای انتخاب سایز» نمایش داده می‌شود.

### Size guide
مودال (bottom sheet در موبایل): روش اندازه‌گیری طول پا + جدول سایز از صفحه‌ی CMS با slug `size-guide` (اگر منتشر شده باشد). هیچ عدد تبدیل سایزی در کد نیست.

### Product card
تصویر lazy با نسبت ثابت؛ نبود تصویر = نشان برند. نشان‌ها فقط از داده‌ی واقعی: «ناموجود» (gray-800) یا «٪ تخفیف» (secondary-600). عنوان لینک است؛ دکمه «انتخاب سایز و خرید».

### Sticky buy bar (موبایل)
فقط وقتی دکمه‌ی اصلی خرید از دید خارج است (Alpine `x-intersect`)، بالای نوار ناوبری پایین؛ قیمت + دکمه.

### Page Loader (Signature)
کاشی فیروزه‌ای با S که کشیده می‌شود و خطوط سرعت نارنجی؛ زیر آن «اسپرت لند» و «SPORT LAND». حالت تیره و reduced-motion. فایل: `resources/views/components/layout/page-loader.blade.php`.

### Brand assets
- `<x-brand.mark>` (نشان؛ `--mark-accent` رنگ خطوط سرعت، `:speed="false"` برای اندازه‌های خیلی کوچک) و `<x-brand.logo>` (افقی، متن زنده).
- فایل‌های ثابت: `public/main/images/brand/{mark,mark-mono,mark-white,logo-horizontal,logo-horizontal-mono,logo-horizontal-white,logo-square}.svg`، `og-default.png` (1200×630)، `public/favicon.svg` (بدون خطوط سرعت)، `public/main/images/favicon_io/*.png` (16، 32، 180، 192، 512، maskable-512).

## Do's and Don'ts

### Do:
- **Do** رنگ را فقط از توکن‌های `@theme` بخوانید.
- **Do** آمار، امتیاز، زمان ارسال و ادعاها را فقط با داده‌ی واقعی نشان دهید (الگوی امتیاز صفحه محصول: بدون دیدگاه ← «هنوز امتیازی ثبت نشده»).
- **Do** برای نبود لوگوی آپلودی از `<x-brand.logo>` و `<x-brand.mark>` استفاده کنید.

### Don't:
- **Don't** نماد اعتماد، جدول سایز، زمان تحویل یا محدوده‌ی ارسال ساختگی بنویسید.
- **Don't** متن گرادیانی، blur تزئینی، هاله‌ی نئونی یا اسلایدر خودکار آزاردهنده اضافه کنید.
- **Don't** متن را کوچک‌تر از 11px یا با کنتراست کمتر از 4.5:1 بنویسید.
- **Don't** از نشان یا عناصر شاخص برندهای ورزشی واقعی تقلید کنید.
