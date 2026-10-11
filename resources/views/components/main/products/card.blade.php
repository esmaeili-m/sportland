{{--
    کارت مشترک محصول (طرح Products / View 1)
    استفاده در: سکشن محصولات، سکشن محصولات با فیلتر برند، صفحه برند
    دکمه علاقه‌مندی به toggleWishlist() کامپوننت Livewire والد (HandlesWishlist) وصل است.
    روابط موردنیاز محصول: brand, media(featuredImage), cheapestVariant, specifications
--}}
@props([
    'product',
    'pictureMode' => 'background',
    'wishlisted' => false,
])

@php
    $prices = $product->cheapestVariant?->priceData() ?? [
        'price' => 0,
        'after_discount' => 0,
        'has_discount' => false,
        'discount_percent' => 0,
    ];
    $inStock = (bool) $product->cheapestVariant?->isInStock();
    $productUrl = route('products.show', $product->slug);
@endphp
<div
    {{ $attributes->class('group relative bg-white dark:bg-white/[0.03] rounded-[1.5rem] border border-gray-200 dark:border-white/10 p-2 transition-shadow duration-200 hover:shadow-[var(--shadow-lift)]') }}
>
    <div class="flex h-[220px]">

        {{-- Product Image --}}
        <div class="w-2/5 relative rounded-[1.25rem] overflow-hidden m-1 bg-gray-100 dark:bg-white/5 {{ $inStock ? '' : 'grayscale' }}">

            @if(! $product->featuredImageUrl)

                {{-- بدون تصویر: نشان برند به‌جای تصویر شکسته --}}
                <div class="absolute inset-0 flex items-center justify-center">
                    <x-brand.mark :speed="false" class="w-12 h-12 text-gray-300 dark:text-gray-600"/>
                </div>

            @elseif($pictureMode === 'background')

                <img
                    src="{{ $product->featuredImageUrl }}"
                    alt="{{ $product->title }}"
                    loading="lazy" decoding="async"
                    class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-out group-hover:scale-105"
                >

            @else

                {{-- Transparent / Product Mode --}}
                <div class="w-full h-full relative bg-gradient-to-br from-gray-100 to-transparent dark:from-white/5 dark:to-transparent flex items-center justify-center">

                    <img
                        src="{{ $product->featuredImageUrl }}"
                        class="w-32 h-32 object-contain drop-shadow-md transition-transform duration-500 group-hover:scale-105"
                        alt="{{ $product->title }}"
                        loading="lazy" decoding="async"
                    >

                </div>

            @endif

            <a href="{{ $productUrl }}" tabindex="-1" aria-hidden="true" class="absolute inset-0 z-10"></a>

            {{-- نشان‌های وضعیت (فقط بر اساس داده واقعی) --}}
            @if(! $inStock)
                <span class="absolute z-20 top-3 right-3 bg-gray-800 text-white text-[11px] font-black px-2.5 py-1 rounded-lg">ناموجود</span>
            @elseif($prices['has_discount'])
                <span class="absolute z-20 top-3 right-3 bg-secondary-600 text-white text-[11px] font-black px-2.5 py-1 rounded-lg tabular-nums">
                    {{ $prices['discount_percent'] }}٪ تخفیف
                </span>
            @endif

            {{-- Wishlist --}}
            <x-main.wishlist-button
                :product-id="$product->id"
                :active="$wishlisted"
                icon-class="w-4 h-4"
                class="absolute top-3 left-3 z-20 w-8 h-8 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-md rounded-xl flex items-center justify-center shadow-sm transition-all"
            />

        </div>
        {{-- Product Info --}}
        <div class="w-3/5 p-5 flex flex-col justify-between">

            <div>

                <div class="flex justify-between items-start">

                    @if($product->brand)
                        <span class="text-xs font-bold text-primary-700 dark:text-primary-300 mb-1 block">{{ $product->brand->title }}</span>
                    @endif

                </div>

                <h3 class="font-black text-gray-900 dark:text-white text-base leading-tight mb-2 line-clamp-2">
                    <a href="{{ $productUrl }}" class="hover:text-primary-700 dark:hover:text-primary-300 transition-colors">{{ $product->title }}</a>
                </h3>

                <div class="flex flex-wrap gap-1.5 mt-2">
                    @foreach($product->specifications->take(2) as $specification)

                        @php
                            $value = match ($specification->type) {
                                1 => $specification->pivot->text_value,
                                2 => $specification->pivot->number_value,
                                3 => $specification->pivot->decimal_value,
                                4 => $specification->pivot->boolean_value !== null
                                    ? ($specification->pivot->boolean_value ? 'بله' : 'خیر')
                                    : null,
                                5 => $specification->pivot->date_value,
                                default => null,
                            };
                        @endphp

                        @if($value !== null && $value !== '')
                            <div
                                class="inline-flex items-center gap-1
                               px-2 py-1
                               rounded-lg
                               bg-gray-100/80 dark:bg-white/[0.05]
                               border border-gray-200/70 dark:border-white/[0.08]
                               shadow-sm
                               text-[11px] font-bold
                               text-gray-500 dark:text-gray-400
                               transition-all duration-300
                               hover:-translate-y-0.5
                               hover:bg-brown-50 dark:hover:bg-brown-500/10
                               hover:border-brown-200 dark:hover:border-brown-500/30
                               hover:text-brown-600 dark:hover:text-brown-400
                               hover:shadow-md hover:shadow-brown-500/10"
                            >
<span class="opacity-70">
{{ $specification->title }}:
</span>

                                <span class="font-black text-gray-700 dark:text-gray-200">
{{ $value }}
</span>
                            </div>
                        @endif

                    @endforeach
                </div>

            </div>

            <div class="mt-auto">

                <div class="mb-3 text-left">

                    @if($prices['has_discount'])
                        <p class="text-[11px] text-gray-500 line-through mb-0.5 tabular-nums">
                            {{ number_format($prices['price'] ?? 0) }}
                        </p>
                    @endif

                    <div class="flex items-baseline justify-end gap-1">

<span class="text-xl font-black text-gray-900 dark:text-white tabular-nums">
{{ number_format($prices['after_discount'] ?? 0) }}
</span>

                        <span class="text-[11px] font-bold text-gray-500">
تومان
</span>

                    </div>

                </div>


               <a href="{{ $productUrl }}"
                class="w-full min-h-11 py-3 bg-brown-500 text-white rounded-xl text-xs font-black flex items-center justify-center gap-2 group/btn"
                >
                <span>{{ $inStock ? 'انتخاب سایز و خرید' : 'مشاهده محصول' }}</span>

                <svg
                    class="w-4 h-4 transition-transform group-hover/btn:-translate-x-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>

                </a>

            </div>

        </div>

    </div>
</div>
