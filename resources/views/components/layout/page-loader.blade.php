{{--
    لودر صفحه «اسپرت لند | Sport Land»
    - نشان S روی دیسک فیروزه‌ای کشیده می‌شود و خطوط سرعت وارد می‌شوند (چرخه ۱.۶ ثانیه)
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
#sl-loader{
  --sl-bg:#f0f4f6; --sl-ink:#12181b; --sl-sub:#4d575b; --sl-disc:#007375; --sl-mark:#fff; --sl-accent:#f78955; --sl-soft:rgba(0,115,117,.14);
  position:fixed;inset:0;z-index:2147483000;display:flex;align-items:center;justify-content:center;
  background:var(--sl-bg);transition:opacity .4s cubic-bezier(.2,.8,.2,1),visibility .4s;direction:rtl}
html.dark #sl-loader{--sl-bg:#060b0d;--sl-ink:#f0f4f6;--sl-sub:#a3adb0;--sl-soft:rgba(40,194,190,.18)}
#sl-loader.sl-hidden{opacity:0;visibility:hidden;pointer-events:none}
#sl-loader .sl-box{display:flex;flex-direction:column;align-items:center;gap:16px;transform:translateY(-4%)}
#sl-loader .sl-badge{width:112px;height:112px;border-radius:30px;background:var(--sl-disc);display:grid;place-items:center;box-shadow:0 18px 40px -18px rgba(0,50,55,.55)}
#sl-loader svg{width:78px;height:78px;display:block;overflow:visible}
#sl-loader .sl-s{stroke:var(--sl-mark);stroke-dasharray:64;stroke-dashoffset:64;animation:sl-draw 1.6s cubic-bezier(.65,0,.35,1) infinite}
#sl-loader .sl-line{stroke:var(--sl-accent);opacity:0;animation:sl-dash 1.6s cubic-bezier(.2,.8,.2,1) infinite}
#sl-loader .sl-name{font-family:payda,Vazirmatn,Tahoma,sans-serif;font-size:28px;font-weight:900;line-height:1.15;word-spacing:.12em;color:var(--sl-ink)}
#sl-loader .sl-latin{margin-top:-8px;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:.42em;padding-left:.42em;color:var(--sl-sub);direction:ltr}
#sl-loader .sl-bar{width:120px;height:3px;border-radius:3px;background:var(--sl-soft);overflow:hidden}
#sl-loader .sl-bar span{display:block;width:40%;height:100%;border-radius:3px;background:var(--sl-disc);animation:sl-bar 1.1s cubic-bezier(.65,0,.35,1) infinite}
html.dark #sl-loader .sl-bar span{background:#28c2be}
@keyframes sl-draw{0%{stroke-dashoffset:64}45%,80%{stroke-dashoffset:0}100%{stroke-dashoffset:-64}}
@keyframes sl-dash{0%,30%{opacity:0;transform:translateX(8px)}50%,80%{opacity:1;transform:translateX(0)}100%{opacity:0;transform:translateX(-4px)}}
@keyframes sl-bar{0%{transform:translateX(260%)}100%{transform:translateX(-160%)}}
@media (prefers-reduced-motion:reduce){#sl-loader *,#sl-loader{animation:none!important;transition:opacity .2s,visibility .2s!important}#sl-loader .sl-s{stroke-dashoffset:0}#sl-loader .sl-line{opacity:1}}
</style>

<div id="sl-loader" role="status" aria-live="polite" aria-label="در حال بارگذاری {{ $name }}">
  <div class="sl-box">
    <div class="sl-badge" aria-hidden="true">
      <svg viewBox="0 0 48 48" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <g transform="skewX(-12) translate(6 0)">
          <path class="sl-s" pathLength="64" d="M36 11H21.5a7.5 7.5 0 0 0 0 15h6a7.5 7.5 0 0 1 0 15H12" stroke-width="6"/>
          <path class="sl-line" d="M5 18.5h7" stroke-width="3"/>
          <path class="sl-line" style="animation-delay:.08s" d="M3 26h8" stroke-width="3"/>
          <path class="sl-line" style="animation-delay:.16s" d="M5 33.5h4" stroke-width="3"/>
        </g>
      </svg>
    </div>
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
