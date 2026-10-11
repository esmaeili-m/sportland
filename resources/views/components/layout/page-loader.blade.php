{{--
    لودر صفحه «برنج خطیر / KHATIR RICE»
    - دانه‌های برنج در کاسه می‌ریزند، تپه برنج بالا می‌آید و بخار بلند می‌شود (چرخه ۲.۶ ثانیه)
    - استایل و اسکریپت درون‌خطی: قبل از بارگذاری CSS/JS اصلی نمایش داده می‌شود
    - با کامل شدن بارگذاری محو می‌شود (حداقل ۴۵۰ms، حداکثر ۸ ثانیه)؛ در ناوبری داخلی دوباره نمایش داده می‌شود
    - حالت تیره و prefers-reduced-motion پشتیبانی می‌شود
    رنگ‌ها = توکن‌های DESIGN.md (مقدار ثابت، چون قبل از CSS اصلی اجرا می‌شود)
--}}
@props(['name' => null, 'latin' => null])
@php
    $name ??= config('brand.name');
    $latin ??= config('brand.name_en');
@endphp

<style>
#bk-loader{
  --bk-bg:#f6f3ed; --bk-ink:#1a1815; --bk-sub:#43413b; --bk-disc:#2b633d; --bk-rice:#fcfaf6; --bk-soft:rgba(43,99,61,.14);
  position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;
  background:radial-gradient(60% 45% at 50% 42%,rgba(109,176,127,.16),transparent 70%),var(--bk-bg);
  transition:opacity .5s cubic-bezier(.22,1,.36,1),visibility .5s;direction:rtl}
html.dark #bk-loader{--bk-bg:#0b0a08;--bk-ink:#f6f3ed;--bk-sub:#a5a199;--bk-disc:#2b633d;--bk-soft:rgba(109,176,127,.18);
  background:radial-gradient(60% 45% at 50% 42%,rgba(43,99,61,.28),transparent 70%),var(--bk-bg)}
#bk-loader.bk-hidden{opacity:0;visibility:hidden;pointer-events:none}
#bk-loader .bk-box{display:flex;flex-direction:column;align-items:center;gap:18px;transform:translateY(-4%)}
#bk-loader .bk-badge{position:relative;width:128px;height:128px}
#bk-loader .bk-badge::before{content:"";position:absolute;inset:-10px;border-radius:50%;border:1.5px solid var(--bk-soft);animation:bk-ring 2.6s cubic-bezier(.22,1,.36,1) infinite}
#bk-loader svg{width:100%;height:100%;display:block}
#bk-loader .bk-grain{fill:var(--bk-rice);opacity:0;transform-box:fill-box;transform-origin:50% 50%;animation:bk-fall 2.6s cubic-bezier(.55,0,.75,.4) infinite}
#bk-loader .bk-mound{fill:var(--bk-rice);transform-box:fill-box;transform-origin:50% 100%;animation:bk-fill 2.6s cubic-bezier(.45,0,.35,1) infinite}
#bk-loader .bk-steam{fill:none;stroke:var(--bk-rice);stroke-width:2;stroke-linecap:round;opacity:0;stroke-dasharray:24;stroke-dashoffset:24;animation:bk-steam 2.6s ease-out infinite}
#bk-loader .bk-name{font-family:payda,Vazirmatn,Tahoma,sans-serif;font-size:30px;font-weight:900;line-height:1.15;color:var(--bk-ink)}
#bk-loader .bk-latin{margin-top:-10px;font-family:payda,Tahoma,sans-serif;font-size:11px;font-weight:700;letter-spacing:.42em;padding-left:.42em;color:var(--bk-sub);direction:ltr}
#bk-loader .bk-bar{width:120px;height:2px;border-radius:2px;background:var(--bk-soft);overflow:hidden}
#bk-loader .bk-bar span{display:block;width:40%;height:100%;border-radius:2px;background:var(--bk-disc);animation:bk-bar 1.3s cubic-bezier(.65,0,.35,1) infinite}
html.dark #bk-loader .bk-bar span{background:#6db07f}
@keyframes bk-fall{0%{opacity:0;transform:translateY(-34px) rotate(var(--r,0deg))}12%{opacity:1}55%{opacity:1;transform:translateY(0) rotate(var(--r2,0deg))}62%,100%{opacity:0;transform:translateY(2px) rotate(var(--r2,0deg))}}
@keyframes bk-fill{0%,8%{transform:scaleY(.05);opacity:0}14%{opacity:1}62%,88%{transform:scaleY(1);opacity:1}100%{transform:scaleY(1);opacity:0}}
@keyframes bk-steam{0%,62%{opacity:0;stroke-dashoffset:24}74%{opacity:.85}100%{opacity:0;stroke-dashoffset:-24}}
@keyframes bk-ring{0%{transform:scale(.92);opacity:0}30%{opacity:1}100%{transform:scale(1.12);opacity:0}}
@keyframes bk-bar{0%{transform:translateX(260%)}100%{transform:translateX(-160%)}}
@media (prefers-reduced-motion:reduce){#bk-loader *,#bk-loader *::before,#bk-loader{animation:none!important;transition:opacity .2s,visibility .2s!important}#bk-loader .bk-mound{transform:none}#bk-loader .bk-grain,#bk-loader .bk-steam{opacity:0}}
</style>

<div id="bk-loader" role="status" aria-live="polite" aria-label="در حال بارگذاری {{ $name }}">
  <div class="bk-box">
    <div class="bk-badge" aria-hidden="true">
      <svg viewBox="0 0 128 128">
        <circle cx="64" cy="64" r="64" fill="var(--bk-disc)"/>
        <path class="bk-steam" d="M54 34c-3-4 3-7 0-11"/>
        <path class="bk-steam" style="animation-delay:.12s" d="M66 32c-3-4 3-7 0-11"/>
        <path class="bk-steam" style="animation-delay:.24s" d="M78 34c-3-4 3-7 0-11"/>
        <g>
          <path class="bk-grain" style="--r:-30deg;--r2:-60deg;animation-delay:0s" d="M52 50c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
          <path class="bk-grain" style="--r:20deg;--r2:70deg;animation-delay:.18s" d="M66 52c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
          <path class="bk-grain" style="--r:-10deg;--r2:-80deg;animation-delay:.36s" d="M76 50c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
          <path class="bk-grain" style="--r:35deg;--r2:85deg;animation-delay:.54s" d="M58 54c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
          <path class="bk-grain" style="--r:-25deg;--r2:-50deg;animation-delay:.72s" d="M71 55c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
          <path class="bk-grain" style="--r:10deg;--r2:60deg;animation-delay:.9s" d="M62 51c2-3 2-8 0-11c-2 3-2 8 0 11z"/>
        </g>
        <path class="bk-mound" d="M33 70C40 58 52 53 64 53S88 58 95 70Z"/>
        <path d="M28 70h72c0 17-14 30-36 30S28 87 28 70Z" fill="var(--bk-rice)"/>
        <path d="M28 70h72" stroke="var(--bk-disc)" stroke-width="3" stroke-linecap="round" opacity=".35"/>
        <path d="M50 104h28" stroke="var(--bk-rice)" stroke-width="4" stroke-linecap="round"/>
      </svg>
    </div>
    <div class="bk-name">{{ $name }}</div>
    @if($latin)
    <div class="bk-latin">{{ $latin }}</div>
    @endif
    <div class="bk-bar"><span></span></div>
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

        var loader = document.getElementById('bk-loader');
        if (!loader) return;

        var shownAt = Date.now();
        var MIN = 450, MAX = 8000, hideTimer = null;

        function hide() {
            clearTimeout(hideTimer);
            hideTimer = setTimeout(function () {
                loader.classList.add('bk-hidden');
                document.body && document.body.classList.remove('overflow-hidden');
            }, Math.max(0, MIN - (Date.now() - shownAt)));
        }

        function show() {
            clearTimeout(hideTimer);
            shownAt = Date.now();
            loader.classList.remove('bk-hidden');
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
