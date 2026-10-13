{{--
    لودر صفحه «اسپرت لند | Sport Land»
    - کاشی لوگوی رسمی (زمینه مشکی، نوار فیروزه‌ای مورب)؛ S و L مونوگرام در امتداد شیب لوگو وارد می‌شوند (چرخه ۱.۶ ثانیه)
    - استایل و اسکریپت درون‌خطی: قبل از بارگذاری CSS/JS اصلی نمایش داده می‌شود
    - با کامل شدن بارگذاری محو می‌شود (حداقل ۴۵۰ms، حداکثر ۸ ثانیه)؛ در ناوبری داخلی دوباره نمایش داده می‌شود
    - حالت تیره و prefers-reduced-motion پشتیبانی می‌شود
    رنگ‌ها: لوگو = رنگ‌های لوگوی رسمی؛ زمینه و نوار = توکن‌های DESIGN.md (مقدار ثابت، چون قبل از CSS اصلی اجرا می‌شود)
--}}
@props(['name' => null, 'latin' => null])
@php
    $name ??= config('brand.name');
    $latin ??= config('brand.name_en');
@endphp

<style>
#sl-loader{
  --sl-bg:#f0f4f6; --sl-ink:#12181b; --sl-sub:#4d575b; --sl-bar:#007375; --sl-soft:rgba(0,115,117,.14);
  position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;
  background:var(--sl-bg);transition:opacity .4s cubic-bezier(.2,.8,.2,1),visibility .4s;direction:rtl}
html.dark #sl-loader{--sl-bg:#060b0d;--sl-ink:#f0f4f6;--sl-sub:#a3adb0;--sl-bar:#28c2be;--sl-soft:rgba(40,194,190,.18)}
#sl-loader.sl-hidden{opacity:0;visibility:hidden;pointer-events:none}
#sl-loader .sl-box{display:flex;flex-direction:column;align-items:center;gap:16px;transform:translateY(-4%)}
#sl-loader svg{width:112px;height:112px;display:block;filter:drop-shadow(0 18px 24px rgba(0,0,0,.28))}
#sl-loader .sl-s{animation:sl-s 1.6s cubic-bezier(.2,.8,.2,1) infinite}
#sl-loader .sl-l{animation:sl-l 1.6s cubic-bezier(.2,.8,.2,1) infinite}
#sl-loader .sl-name{font-family:payda,Vazirmatn,Tahoma,sans-serif;font-size:28px;font-weight:900;line-height:1.15;word-spacing:.12em;color:var(--sl-ink)}
#sl-loader .sl-latin{margin-top:-8px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:900;letter-spacing:.42em;padding-left:.42em;direction:ltr;background:linear-gradient(90deg,#ed4e26,#b30f4e);-webkit-background-clip:text;background-clip:text;color:transparent}
#sl-loader .sl-bar{width:120px;height:3px;border-radius:3px;background:var(--sl-soft);overflow:hidden}
#sl-loader .sl-bar span{display:block;width:40%;height:100%;border-radius:3px;background:var(--sl-bar);animation:sl-bar 1.1s cubic-bezier(.65,0,.35,1) infinite}
@keyframes sl-s{0%{opacity:0;transform:translate(90px,-90px)}30%,85%{opacity:1;transform:none}100%{opacity:0;transform:none}}
@keyframes sl-l{0%,12%{opacity:0;transform:translate(-90px,90px)}42%,85%{opacity:1;transform:none}100%{opacity:0;transform:none}}
@keyframes sl-bar{0%{transform:translateX(260%)}100%{transform:translateX(-160%)}}
@media (prefers-reduced-motion:reduce){#sl-loader *,#sl-loader{animation:none!important;transition:opacity .2s,visibility .2s!important}}
</style>

<div id="sl-loader" role="status" aria-live="polite" aria-label="در حال بارگذاری {{ $name }}">
  <div class="sl-box">
    <svg viewBox="0 0 1000 1000" aria-hidden="true">
      <defs>
        <linearGradient id="sl-loader-l" x1="0.85" y1="0" x2="0.2" y2="1"><stop offset="0" stop-color="#b30f4e"/><stop offset=".55" stop-color="#d0283a"/><stop offset="1" stop-color="#ed4e26"/></linearGradient>
        <linearGradient id="sl-loader-s" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff"/><stop offset=".5" stop-color="#e4dfe1"/><stop offset="1" stop-color="#c7c0c3"/></linearGradient>
        <clipPath id="sl-loader-c"><rect width="1000" height="1000" rx="220"/></clipPath>
      </defs>
      <g clip-path="url(#sl-loader-c)">
        <rect width="1000" height="1000" fill="#0e0e0e"/>
        <path d="M0 1000V720L720 0H1000V280L280 1000Z" fill="#35c3e9"/>
        <g transform="translate(78 148)">
          <g class="sl-s" fill="url(#sl-loader-s)">
            <path d="M300 25H740L621 150H382Q345 150 345 178Q345 206 382 206H572L470 315H232Q160 315 160 238V150Q160 25 300 25Z"/>
            <path d="M165 345H445L322 475H40Z"/>
          </g>
          <path class="sl-l" d="M600 222H805L497 545H690L560 680H130Z" fill="url(#sl-loader-l)"/>
        </g>
      </g>
    </svg>
    <div class="sl-name">{{ $name }}</div>
    @if($latin)
    <div class="sl-latin">{{ $latin }}</div>
    @endif
    <div class="sl-bar"><span></span></div>
  </div>
</div>

<script>
    (function () {
        var root = document.documentElement;

        // تم قبل از نمایش لودر (همان منطق app.js) تا در حالت تاریک چشمک سفید نزند
        try {
            var saved = localStorage.getItem('theme');
            if (saved === 'dark' || (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                root.classList.add('dark');
            }
        } catch (e) {}

        var loader = document.getElementById('sl-loader');
        if (!loader) return;

        var shownAt = Date.now();
        var MIN = 450, MAX = 8000, hideTimer = null;

        function hide() {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                loader.classList.add('sl-hidden');
                document.body && document.body.classList.remove('overflow-hidden');
            }, Math.max(0, MIN - (Date.now() - shownAt)));
        }

        function show() {
            clearTimeout(hideTimer);
            shownAt = Date.now();
            loader.classList.remove('sl-hidden');
            // اگر ناوبری لغو شد (مثلاً دانلود فایل)، لودر نماند
            hideTimer = setTimeout(hide, MAX);
        }

        if (document.readyState === 'complete') {
            hide();
        } else {
            window.addEventListener('load', hide, { once: true });
            setTimeout(hide, MAX);
        }

        // بازگشت با دکمه Back از کش مرورگر
        window.addEventListener('pageshow', function (e) { if (e.persisted) hide(); });

        // لینک‌های داخلی سایت (بارگذاری کامل صفحه)
        document.addEventListener('click', function (e) {
            if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            var a = e.target.closest && e.target.closest('a[href]');
            if (!a || a.target && a.target !== '_self' || a.hasAttribute('download') || a.hasAttribute('wire:navigate')) return;
            if (a.hasAttribute('wire:click') || a.hasAttribute('x-on:click') || a.hasAttribute('@click') || a.dataset.noLoader !== undefined) return;

            var href = a.getAttribute('href') || '';
            if (!href || href.charAt(0) === '#' || /^(javascript|mailto|tel|sms|whatsapp|tg):/i.test(href)) return;

            var url;
            try { url = new URL(a.href, location.href); } catch (err) { return; }
            if (url.origin !== location.origin) return;
            if (url.pathname === location.pathname && url.search === location.search && url.hash) return;

            show();
        });

        // ناوبری Livewire (wire:navigate)
        document.addEventListener('livewire:navigate', show);
        document.addEventListener('livewire:navigated', hide);
    })();
</script>
