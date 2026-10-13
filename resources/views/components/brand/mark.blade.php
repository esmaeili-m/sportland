{{--
    نشان «اسپرت لند»: مونوگرام SL برگرفته از لوگوی رسمی (public/main/images/brand/logo-original.jpg)
    - S: رنگ از currentColor (با text-* رنگ بدهید؛ روی زمینه تیره نقره‌ای/سفید)
    - L: گرادیان سرخابی ← نارنجی لوگو
    - tile=true: کاشی کامل لوگو (زمینه مشکی با نوار فیروزه‌ای مورب و S نقره‌ای) برای آیکون‌ها و فوتر
    هندسه = public/main/images/brand/mark.svg
--}}
@props(['title' => null, 'tile' => false])
@php($uid = 'slm' . substr(md5(uniqid('', true)), 0, 8))
<svg {{ $attributes->merge(['viewBox' => $tile ? '0 0 1000 1000' : '30 20 785 665', 'xmlns' => 'http://www.w3.org/2000/svg']) }}
     @if($title) role="img" aria-label="{{ $title }}" @else aria-hidden="true" focusable="false" @endif>
    <defs>
        <linearGradient id="{{ $uid }}-l" x1="0.85" y1="0" x2="0.2" y2="1">
            <stop offset="0" stop-color="#b30f4e"/>
            <stop offset=".55" stop-color="#d0283a"/>
            <stop offset="1" stop-color="#ed4e26"/>
        </linearGradient>
        @if($tile)
            <linearGradient id="{{ $uid }}-s" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#ffffff"/>
                <stop offset=".5" stop-color="#e4dfe1"/>
                <stop offset="1" stop-color="#c7c0c3"/>
            </linearGradient>
            <clipPath id="{{ $uid }}-c"><rect width="1000" height="1000" rx="220"/></clipPath>
        @endif
    </defs>
    @if($tile)
        <g clip-path="url(#{{ $uid }}-c)">
            <rect width="1000" height="1000" fill="#0e0e0e"/>
            <path d="M0 1000V720L720 0H1000V280L280 1000Z" fill="#35c3e9"/>
        </g>
    @endif
    <g @if($tile) transform="translate(78 148)" @endif>
        <g fill="{{ $tile ? 'url(#' . $uid . '-s)' : 'currentColor' }}">
            <path d="M300 25H740L621 150H382Q345 150 345 178Q345 206 382 206H572L470 315H232Q160 315 160 238V150Q160 25 300 25Z"/>
            <path d="M165 345H445L322 475H40Z"/>
        </g>
        <path d="M600 222H805L497 545H690L560 680H130Z" fill="url(#{{ $uid }}-l)"/>
    </g>
</svg>
