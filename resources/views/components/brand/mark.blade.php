{{--
    نشان «اسپرت لند»: حرف S ایتالیک (مسیر پیست دو) با خطوط سرعت.
    رنگ S از currentColor و رنگ خطوط سرعت از متغیر CSS ‎--mark-accent (پیش‌فرض: همان currentColor).
    speed=false برای اندازه‌های خیلی کوچک (خطوط سرعت حذف می‌شوند). هندسه = public/main/images/brand/mark.svg
--}}
@props(['title' => null, 'speed' => true])
<svg {{ $attributes->merge(['viewBox' => '0 0 48 48', 'xmlns' => 'http://www.w3.org/2000/svg', 'fill' => 'none']) }}
     @if($title) role="img" aria-label="{{ $title }}" @else aria-hidden="true" focusable="false" @endif>
    <g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" transform="skewX(-12) translate(6 0)">
        <path d="M36 11H21.5a7.5 7.5 0 0 0 0 15h6a7.5 7.5 0 0 1 0 15H12" stroke-width="6"/>
        @if($speed)
            <path d="M5 18.5h7M3 26h8M5 33.5h4" stroke="var(--mark-accent, currentColor)" stroke-width="3"/>
        @endif
    </g>
</svg>
