{{--
    نشان «برنج خطیر» (خوشه برنج) — نسخه موقت تا دریافت لوگوی رسمی. TODO(OWNER): لوگوی رسمی
    رنگ از currentColor گرفته می‌شود؛ با کلاس text-* رنگ بدهید. هندسه = public/main/images/brand/mark.svg
--}}
@props(['title' => null])
<svg {{ $attributes->merge(['viewBox' => '10 5 56 56', 'xmlns' => 'http://www.w3.org/2000/svg', 'fill' => 'currentColor']) }}
     @if($title) role="img" aria-label="{{ $title }}" @else aria-hidden="true" focusable="false" @endif>
    <path d="M20 61C18 36 26 13 42 10C53 8 57 16 56 27" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
    @foreach([[27.6, 23.2, 22], [38, 17.9, -22], [41.4, 15.3, 22], [51, 15.8, -22], [50.1, 17.8, 22], [56.9, 21.3, -22], [53.7, 25.8, 22], [56.4, 33, -4]] as [$x, $y, $r])
        <path d="M0-6.4C2.9-3.6 2.9 3.6 0 6.4C-2.9 3.6-2.9-3.6 0-6.4Z" transform="translate({{ $x }} {{ $y }}) rotate({{ $r }})"/>
    @endforeach
</svg>
