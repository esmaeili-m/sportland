<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
new class extends Component
{
    public $product;
    public $selectedVariant;
    public int $rating = 0;
    public string $activeTab = 'overview';
    public string $commentBody = '';

    public array $pros = [];
    public array $cons = [];

    public string $prosInput = '';
    public string $consInput = '';
    public array $selectedOptions = [];
    // آیا کاربر سایز را خودش انتخاب کرده است (برای جلوگیری از افزودن سایز پیش‌فرض به سبد)
    public bool $sizeChosen = true;
    public bool $sizeError = false;
    public bool $isWishlisted = false;
    public bool $isCompared = false;
    public function mount($product)
    {
        $this->product = Product::active()->where('slug',$product)
            ->with(['variants.optionValues', 'variants.values', 'variants.inventoryItems.inventory', 'options', 'primaryCategory', 'comments', 'specifications'])
            ->first();
        if (!$this->product){
            abort(404);
        }
        // همان نمونه‌ی داخل variants (با روابط بارگذاری‌شده‌ی مقدارها و موجودی)
        $cheapestId = $this->product->cheapestVariant?->id;
        $this->selectedVariant = $this->product->variants->firstWhere('id', $cheapestId)
            ?? $this->product->variants->first();

        // سایز باید آگاهانه انتخاب شود؛ اگر محصول ویژگی سایز ندارد یا فقط یک سایز دارد، نیازی نیست
        $this->sizeChosen = $this->sizeValueCount() <= 1;

        // سئوی صفحه (عنوان، توضیح، تصویر OG) برای layouts.main
        $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($this->product->short_description ?: $this->product->description))));
        view()->share('seo', [
            'title' => $this->product->title,
            'description' => \Illuminate\Support\Str::limit($description, 160, '…'),
            'og_image' => $this->product->featuredImageUrl,
            'og_type' => 'product',
            'canonical' => route('products.show', $this->product->slug),
        ]);

        $this->isWishlisted = Auth::check()
            && Auth::user()->hasInWishlist($this->product->id);

        $this->isCompared = app(\App\Services\Catalog\CompareList::class)->has($this->product->id);
    }

    // افزودن/حذف از لیست مقایسه (Session؛ برای مهمان هم کار می‌کند)
    public function toggleCompare(): void
    {
        $compare = app(\App\Services\Catalog\CompareList::class);

        if ($compare->has($this->product->id)) {
            $compare->remove($this->product->id);
            $this->isCompared = false;
            $this->dispatch('compare-updated');
            $this->dispatch('alert', type: 'success', message: 'محصول از لیست مقایسه حذف شد.');

            return;
        }

        $result = $compare->add($this->product->id);

        if ($result === 'full') {
            $this->dispatch('alert', type: 'warning', message: 'حداکثر ' . \App\Services\Catalog\CompareList::MAX . ' محصول قابل مقایسه است؛ ابتدا یکی را از نوار مقایسه حذف کنید.');

            return;
        }

        $this->isCompared = true;
        $this->dispatch('compare-updated');
        $this->dispatch('alert', type: 'success', message: 'محصول به لیست مقایسه اضافه شد.');
    }

    /**
     * تاریخچه قیمت تنوع انتخاب‌شده (نمودار قیمت) — یک نقطه در روز، تا ۳۶۵ روز
     * قیمت امروز همیشه از موتور قیمت اضافه می‌شود تا نمودار با قیمت فعلی صفحه یکی باشد.
     */
    public function priceHistory(): array
    {
        $variant = $this->selectedVariant;

        if (!$variant) {
            return [];
        }

        try {
            $rows = \App\Models\PriceHistory::where('product_variant_id', $variant->id)
                ->where('recorded_on', '>=', today()->subDays(365))
                ->orderBy('recorded_on')
                ->get(['recorded_on', 'price', 'final_price'])
                ->keyBy(fn ($row) => $row->recorded_on->toDateString());
        } catch (\Throwable) {
            $rows = collect(); // جدول هنوز ساخته نشده
        }

        $today = rescue(fn () => $variant->priceData(), [], false) ?: [];
        $rows[today()->toDateString()] = (object) [
            'recorded_on' => today(),
            'price' => (int) ($today['price'] ?? $variant->price),
            'final_price' => (int) ($today['after_discount'] ?? $variant->price),
        ];

        return $rows->sortKeys()->values()->map(fn ($row) => [
            'date' => \Carbon\Carbon::parse($row->recorded_on)->toDateString(),
            'label' => verta($row->recorded_on)->format('Y/m/d'),
            'price' => (int) $row->price,
            'final' => (int) $row->final_price,
        ])->filter(fn ($p) => $p['price'] > 0)->values()->all();
    }

    // حذف از نوار مقایسه => وضعیت آیکون همین صفحه هم به‌روز شود
    #[\Livewire\Attributes\On('compare-updated')]
    public function syncCompareState(): void
    {
        $this->isCompared = app(\App\Services\Catalog\CompareList::class)->has($this->product->id);
    }
    public function toggleWishlist()
    {
        // مهمان: بعد از ورود به همین صفحه برمی‌گردد
        if (! Auth::check()) {
            session()->put('url.intended', route('products.show', $this->product->slug));

            return $this->redirectRoute('login');
        }

        $wishlist = Auth::user()->wishlists()
            ->where('product_id', $this->product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            $this->isWishlisted = false;

            $this->dispatch('alert', type: 'success', message: 'محصول از علاقه‌مندی‌ها حذف شد.');
            return;
        }

        // unique(user_id, product_id) جلوی رکورد تکراری را می‌گیرد
        Auth::user()->wishlists()->createOrFirst([
            'product_id' => $this->product->id,
        ]);
        $this->isWishlisted = true;

        $this->dispatch('alert', type: 'success', message: 'محصول به علاقه‌مندی‌ها اضافه شد.');
    }
    public function findVariant()
    {
        $variants = $this->product->variants;

        $this->selectedVariant = $variants->first(function ($variant) {

            foreach ($this->selectedOptions as $optionId => $valueId) {

                $variantOption = $variant->options
                    ->firstWhere('id', $optionId);

                if (!$variantOption) {
                    return false;
                }

                if ($variantOption->pivot->value_id != $valueId) {
                    return false;
                }
            }

            return true;
        });
    }
    public function addProsFromInput(): void
    {

        $value = trim($this->prosInput);

        if ($value === '') {
            return;
        }

        if (count($this->pros) >= 10) {
            $this->addError('pros', 'حداکثر می‌توانید ۱۰ ویژگی مثبت اضافه کنید.');
            return;
        }

        if (mb_strlen($value) > 100) {
            $this->addError('pros', 'ویژگی مثبت نمی‌تواند بیشتر از ۱۰۰ کاراکتر باشد.');
            return;
        }

        if (in_array($value, $this->pros, true)) {
            $this->addError('pros', 'این ویژگی مثبت قبلاً اضافه شده است.');
            return;
        }

        $this->pros[] = $value;

        $this->prosInput = '';
        $this->resetErrorBag('pros');
    }
    public function getAverageRatingProperty()
    {
        return round($this->product->ratings->avg('rating') ?? 0, 1);
    }
    public function addConsFromInput(): void
    {
        $value = trim($this->consInput);

        if ($value === '') {
            return;
        }

        if (count($this->cons) >= 10) {
            $this->addError('cons', 'حداکثر می‌توانید ۱۰ نقطه ضعف اضافه کنید.');
            return;
        }

        if (mb_strlen($value) > 100) {
            $this->addError('cons', 'نقطه ضعف نمی‌تواند بیشتر از ۱۰۰ کاراکتر باشد.');
            return;
        }

        if (in_array($value, $this->cons, true)) {
            $this->addError('cons', 'این مورد قبلاً اضافه شده است.');
            return;
        }

        $this->cons[] = $value;

        $this->consInput = '';

        $this->resetErrorBag('cons');
    }

    public function removePros(int $index)
    {
        unset($this->pros[$index]);

        $this->pros = array_values($this->pros);
    }

    public function removeCons(int $index)
    {
        unset($this->cons[$index]);

        $this->cons = array_values($this->cons);
    }

    /** شناسه ویژگی «سایز» محصول (بر اساس عنوان یا slug ویژگی) */
    public function sizeOptionId(): ?int
    {
        return $this->product->options
            ->first(fn ($option) => preg_match('/سایز|size/iu', $option->title . ' ' . $option->slug))
            ?->id;
    }

    /** تعداد مقدارهای متفاوت سایز در واریانت‌های محصول */
    protected function sizeValueCount(): int
    {
        $sizeOptionId = $this->sizeOptionId();

        if (! $sizeOptionId) {
            return 0;
        }

        return $this->product->variants
            ->pluck('values')
            ->flatten()
            ->where('option_id', $sizeOptionId)
            ->unique('id')
            ->count();
    }

    public function addToCart(): void
    {
        if (! $this->selectedVariant) {
            $this->dispatch('alert', type: 'error', message: 'این ترکیب از محصول موجود نیست.');
            return;
        }

        if (! $this->sizeChosen) {
            $this->sizeError = true;
            $this->dispatch('alert', type: 'warning', message: 'لطفاً ابتدا سایز را انتخاب کنید.');
            return;
        }

        $variantId=$this->selectedVariant->id;
        if (! Auth::check()) {
            $this->dispatch('alert', type: 'error', message: 'برای خرید ابتدا وارد شوید.');
            return;
        }

        $variant = ProductVariant::query()->find($variantId);

        if (! $variant) {
            $this->dispatch('alert', type: 'error', message: 'این کالا در دسترس نیست.');
            return;
        }

        $pricing = app(\App\Services\Pricing\ProductPriceService::class)
            ->calculate($variant);

        // همان واریانت در سبد => فقط تعداد زیاد می‌شود؛ موجودیِ از قبل در سبد هم حساب می‌شود
        $result = app(\App\Services\Cart\CartService::class)->add(
            Auth::id(),
            $variant->product_id,
            $variant->id,
            (int) ($pricing['after_discount'] ?? $variant->price ?? 0)
        );

        if (! $result['ok']) {
            $this->dispatch('alert', type: 'error', message: $result['message']);
            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('alert', type: 'success', message: $result['message']);
    }
    public function selectOption($valueId)
    {
        $newValue = \App\Models\OptionValue::findOrFail($valueId);

        // فقط واریانت‌هایی از همین محصول که این مقدار را دارند
        $candidates = $this->product->variants
            ->filter(fn ($variant) => $variant->values->contains('id', $newValue->id));

        if ($candidates->isEmpty()) {
            return;
        }

        // مقادیر Variant فعلی؛ فقط مقدار ویژگی‌ای که کلیک شده عوض می‌شود
        $newValueIds = ($this->selectedVariant?->values ?? collect())
            ->map(fn ($value) => $value->option_id == $newValue->option_id ? $newValue->id : $value->id)
            ->push($newValue->id)
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // ۱) ترکیب دقیق  ۲) اگر نبود: واریانت موجود با بیشترین اشتراک با انتخاب فعلی  ۳) هر واریانت دارای این مقدار
        $overlap = fn ($variant) => count(array_intersect($variant->values->pluck('id')->all(), $newValueIds));

        $this->selectedVariant = $candidates->first(
                fn ($variant) => $variant->values->pluck('id')->sort()->values()->toArray() == $newValueIds
            )
            ?? $candidates->filter(fn ($variant) => $variant->isInStock())->sortByDesc($overlap)->first()
            ?? $candidates->sortByDesc($overlap)->first();

        if ($newValue->option_id === $this->sizeOptionId()) {
            $this->sizeChosen = true;
            $this->sizeError = false;
        }
    }
    private function stockForVariant(int $variantId): int
    {
        return (int) DB::table('inventory_items')
            ->where('product_variant_id', $variantId)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->selectRaw('SUM(quantity - reserved_quantity) as stock')
            ->value('stock');
    }

    public function submitComment(): void
    {
        $this->validate(
            [
                'rating' => [
                    'required',
                    'integer',
                    'between:1,5',
                ],

                'commentBody' => [
                    'required',
                    'string',
                    'min:10',
                    'max:2000',
                ],

                'pros' => [
                    'array',
                    'max:10',
                ],

                'pros.*' => [
                    'string',
                    'max:100',
                ],

                'cons' => [
                    'array',
                    'max:10',
                ],

                'cons.*' => [
                    'string',
                    'max:100',
                ],
            ],
            [
                'rating.required' => 'لطفاً امتیاز محصول را انتخاب کنید.',
                'rating.integer' => 'امتیاز انتخاب‌شده معتبر نیست.',
                'rating.between' => 'امتیاز محصول باید بین ۱ تا ۵ باشد.',

                'commentBody.required' => 'لطفاً متن دیدگاه خود را وارد کنید.',
                'commentBody.string' => 'متن دیدگاه واردشده معتبر نیست.',
                'commentBody.min' => 'متن دیدگاه باید حداقل ۱۰ کاراکتر باشد.',
                'commentBody.max' => 'متن دیدگاه نمی‌تواند بیشتر از ۲۰۰۰ کاراکتر باشد.',

                'pros.array' => 'ویژگی‌های مثبت واردشده معتبر نیستند.',
                'pros.max' => 'حداکثر می‌توانید ۱۰ ویژگی مثبت وارد کنید.',
                'pros.*.string' => 'ویژگی مثبت باید به صورت متن باشد.',
                'pros.*.max' => 'هر ویژگی مثبت نمی‌تواند بیشتر از ۱۰۰ کاراکتر باشد.',

                'cons.array' => 'نقاط ضعف واردشده معتبر نیستند.',
                'cons.max' => 'حداکثر می‌توانید ۱۰ نقطه ضعف وارد کنید.',
                'cons.*.string' => 'نقطه ضعف باید به صورت متن باشد.',
                'cons.*.max' => 'هر نقطه ضعف نمی‌تواند بیشتر از ۱۰۰ کاراکتر باشد.',
            ],
            [
                'rating' => 'امتیاز محصول',
                'commentBody' => 'متن دیدگاه',
                'pros' => 'ویژگی‌های مثبت',
                'pros.*' => 'ویژگی مثبت',
                'cons' => 'نقاط ضعف',
                'cons.*' => 'نقطه ضعف',
            ]
        );

        DB::transaction(function () {

            /*
            |--------------------------------------------------------------------------
            | ثبت دیدگاه
            |--------------------------------------------------------------------------
            */

            $comment = $this->product->comments()->create([
                'user_id' => auth()->id(),
                'body' => trim($this->commentBody),
                'name' => auth()->user()->full_name ?? auth()->user()->name ?? null,
                'is_approved' => false,
            ]);


            /*
            |--------------------------------------------------------------------------
            | ثبت امتیاز
            |--------------------------------------------------------------------------
            */

            $this->product->ratings()->updateOrCreate(
                [
                    'user_id' => auth()->id(),
                ],
                [
                    'rating' => $this->rating,
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | ثبت نقاط مثبت
            |--------------------------------------------------------------------------
            */

            foreach ($this->pros ?? [] as $pro) {

                $pro = trim($pro);

                if ($pro === '') {
                    continue;
                }

                $comment->points()->create([
                    'type' => 'positive',
                    'body' => $pro,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | ثبت نقاط منفی
            |--------------------------------------------------------------------------
            */

            foreach ($this->cons ?? [] as $con) {

                $con = trim($con);

                if ($con === '') {
                    continue;
                }

                $comment->points()->create([
                    'type' => 'negative',
                    'body' => $con,
                ]);
            }
        });

        /*
        |--------------------------------------------------------------------------
        | پاک کردن فرم
        |--------------------------------------------------------------------------
        */

        $this->reset([
            'rating',
            'commentBody',
            'pros',
            'cons',
        ]);

        /*
        |--------------------------------------------------------------------------
        | باقی ماندن روی تب نظرات
        |--------------------------------------------------------------------------
        */

        $this->activeTab = 'reviews';

        session()->flash(
            'comment-success',
            'دیدگاه شما با موفقیت ثبت شد و پس از تأیید نمایش داده خواهد شد.'
        );
    }
    public function getPositivePointsProperty()
    {
        return $this->product->comments
            ->flatMap(fn ($comment) => $comment->commentPoints)
            ->where('type', 'positive')
            ->pluck('body')
            ->unique()
            ->values();
    }

    public function getNegativePointsProperty()
    {
        return $this->product->comments
            ->flatMap(fn ($comment) => $comment->commentPoints)
            ->where('type', 'negative')
            ->pluck('body')
            ->unique()
            ->values();
    }
}
?>

<div>
    <main class="space-y-12">

        <!-- CONTENT -->
        <!-- CONTENT -->
        @php
            // واریانت انتخاب‌شده (ممکن است ترکیب انتخابی وجود نداشته باشد)
            $inStock = (bool) $selectedVariant?->isInStock();

            // واریانت‌های موجود و مقدارهایی (رنگ/سایز/...) که حداقل یک واریانت موجود دارند
            $inStockVariants = $product->variants->filter(fn ($v) => $v->isInStock());
            $availableValueIds = $inStockVariants->pluck('values')->flatten()->pluck('id')->unique()->all();
            $selectedValues = $selectedVariant?->values ?? collect();
            $sizeOptionId = $this->sizeOptionId();

            $pricing = $selectedVariant?->priceData() ?? ['has_discount' => false, 'before_discount' => 0, 'after_discount' => 0, 'discount_percent' => 0];

            // امتیاز واقعی از دیدگاه‌های تأییدشده (بدون عدد پیش‌فرض)
            $ratedComments = $product->comments->whereNotNull('rating')->where('rating', '>', 0);
            $ratingCount = $ratedComments->count();
            $ratingAverage = $ratingCount ? round($ratedComments->avg('rating'), 1) : null;

            $sizeGuidePage = \App\Models\Page::query()->where('slug', 'size-guide')->where('status', true)->exists();
        @endphp

        {{-- داده ساختاریافته محصول و مسیر صفحه (فقط داده واقعی). قیمت‌ها تومان هستند و برای schema به ریال (IRR) تبدیل می‌شوند --}}
        @push('structured-data')
            <script type="application/ld+json">{!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->title,
                'image' => $product->featuredImageUrl,
                'sku' => $selectedVariant?->sku ?: null,
                'description' => \Illuminate\Support\Str::limit(trim(strip_tags((string) ($product->short_description ?: $product->description))), 300, '…') ?: null,
                'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->title] : null,
                'offers' => $selectedVariant ? [
                    '@type' => 'Offer',
                    'url' => route('products.show', $product->slug),
                    'priceCurrency' => 'IRR',
                    'price' => (int) $pricing['after_discount'] * 10,
                    'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                ] : null,
                'aggregateRating' => $ratingCount ? ['@type' => 'AggregateRating', 'ratingValue' => $ratingAverage, 'reviewCount' => $ratingCount, 'bestRating' => 5, 'worstRating' => 1] : null,
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
            <script type="application/ld+json">{!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_values(array_filter([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => url('/')],
                    $product->primaryCategory ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->primaryCategory->title, 'item' => route('categories.show', $product->primaryCategory->slug)] : null,
                    ['@type' => 'ListItem', 'position' => $product->primaryCategory ? 3 : 2, 'name' => $product->title],
                ])),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endpush

        <section class="product-hero relative pt-8 overflow-hidden">
            <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-brown-600/5 blur-[100px] rounded-full -z-10 animate-pulse"></div>

            <div class="relative z-10 px-4">

                {{-- Breadcrumb --}}
                <nav aria-label="مسیر صفحه" class="mb-8">
                    <ol class="flex flex-wrap items-center gap-2 text-xs font-bold text-gray-500 dark:text-gray-400">
                        <li><a href="{{ route('home') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">خانه</a></li>
                        @if($product->primaryCategory)
                            <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
                            <li><a href="{{ route('categories.show', $product->primaryCategory->slug) }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">{{ $product->primaryCategory->title }}</a></li>
                        @endif
                        <li aria-hidden="true" class="text-gray-300 dark:text-gray-600">/</li>
                        <li aria-current="page" class="text-gray-900 dark:text-white font-black line-clamp-1">{{ $product->title }}</li>
                    </ol>
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">

                    {{-- ===================== Gallery column ===================== --}}
                    <div class="lg:col-span-5 space-y-6 flex flex-col items-center justify-center {{ $inStockVariants->isNotEmpty() ? '' : 'grayscale opacity-70' }}">

                        <div class="relative group bg-white/40 dark:bg-black/20 backdrop-blur-md rounded-[3rem] border border-white/60 dark:border-white/5 shadow-lg p-6 overflow-hidden w-full">

                            {{-- Out of stock stamp --}}
                            @if($inStockVariants->isEmpty())
                                <div class="absolute inset-0 z-[110] flex items-center justify-center pointer-events-none">
                                    <div class="px-8 py-3 bg-zinc-900/90 dark:bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-lg transform -rotate-12">
                                        <span class="text-white text-2xl font-black">ناموجود</span>
                                    </div>
                                </div>
                            @endif

                            {{-- Icon box --}}
                            <div class="absolute top-6 left-1/2 -translate-x-1/2 z-[100] isolate">
                                <div class="sl-gallery-tools flex items-center gap-1.5 p-1.5 bg-white/40 dark:bg-black/40 backdrop-blur-md rounded-2xl border border-white/60 dark:border-white/10 shadow-lg opacity-0 group-hover:opacity-100 [@media(hover:none)]:opacity-100 transition-all duration-500 transform translate-y-[-15px] group-hover:translate-y-0 [@media(hover:none)]:translate-y-0 flex-row-reverse">

                                    <div class="relative group/tooltip">
                                        <button type="button" wire:click="toggleWishlist" wire:loading.attr="disabled" wire:target="toggleWishlist" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 {{ $isWishlisted ? 'bg-red-500 text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-red-500 hover:text-white' }}">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                        </button>
                                        <span class="absolute top-full mt-3 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 dark:bg-zinc-800 text-white text-[10px] font-black rounded-lg whitespace-nowrap opacity-0 -translate-y-1 group-hover/tooltip:opacity-100 group-hover/tooltip:translate-y-0 transition-all duration-300 pointer-events-none shadow-lg z-[110] border border-white/10">
                                    {{ $isWishlisted ? 'حذف از علاقه‌مندی' : 'افزودن به علاقه‌مندی' }}
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-b-gray-900 dark:border-b-zinc-800"></span>
                                </span>
                                    </div>

                                    <div class="w-[1px] h-5 bg-gray-400/20 dark:bg-white/10"></div>

                                    <div class="relative group/tooltip">
                                        <button type="button" wire:click="toggleCompare" wire:loading.attr="disabled" wire:target="toggleCompare"
                                                aria-pressed="{{ $isCompared ? 'true' : 'false' }}" aria-label="{{ $isCompared ? 'حذف از مقایسه' : 'مقایسه محصول' }}"
                                                class="w-10 h-10 rounded-xl flex items-center justify-center transition-all duration-300 {{ $isCompared ? 'sl-compare-active bg-teal-500 text-white' : 'text-gray-700 dark:text-gray-200 hover:bg-teal-500 hover:text-white' }}">
                                            @if($isCompared)
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"></path><path d="M8 21H3v-5"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path><circle cx="12" cy="12" r="1.6" fill="currentColor" stroke="none"></circle></svg>
                                            @else
                                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"></path><path d="M8 21H3v-5"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>
                                            @endif
                                        </button>
                                        <span class="absolute top-full mt-3 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 dark:bg-zinc-800 text-white text-[10px] font-black rounded-lg whitespace-nowrap opacity-0 -translate-y-1 group-hover/tooltip:opacity-100 group-hover/tooltip:translate-y-0 transition-all duration-300 pointer-events-none shadow-lg z-[110] border border-white/10">
                                    {{ $isCompared ? 'حذف از مقایسه' : 'مقایسه محصول' }}
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-b-gray-900 dark:border-b-zinc-800"></span>
                                </span>
                                    </div>

                                    <div class="w-[1px] h-5 bg-gray-400/20 dark:bg-white/10"></div>

                                    <div class="relative group/tooltip">
                                        <button type="button" x-data @click="$dispatch('open-price-chart')" aria-label="نمودار قیمت" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-700 dark:text-gray-200 hover:bg-brown-600 hover:text-white transition-all duration-300">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>
                                        </button>
                                        <span class="absolute top-full mt-3 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 dark:bg-zinc-800 text-white text-[10px] font-black rounded-lg whitespace-nowrap opacity-0 -translate-y-1 group-hover/tooltip:opacity-100 group-hover/tooltip:translate-y-0 transition-all duration-300 pointer-events-none shadow-lg z-[110] border border-white/10">
                                    نمودار قیمت
                                </span>
                                    </div>

                                    <div class="relative group/tooltip">
                                        <button onclick="toggleModal('shareModal')" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-700 dark:text-gray-200 hover:bg-brown-600 hover:text-white transition-all duration-300">
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path><polyline points="16 6 12 2 8 6"></polyline><line x1="12" y1="2" x2="12" y2="15"></line></svg>
                                        </button>
                                        <span class="absolute top-full mt-3 left-1/2 -translate-x-1/2 px-3 py-1.5 bg-gray-900 dark:bg-zinc-800 text-white text-[10px] font-black rounded-lg whitespace-nowrap opacity-0 -translate-y-1 group-hover/tooltip:opacity-100 group-hover/tooltip:translate-y-0 transition-all duration-300 pointer-events-none shadow-lg z-[110] border border-white/10">
                                    اشتراک‌گذاری
                                    <span class="absolute bottom-full left-1/2 -translate-x-1/2 border-[5px] border-transparent border-b-gray-900 dark:border-b-zinc-800"></span>
                                </span>
                                    </div>

                                </div>
                            </div>

                            {{-- Main gallery --}}
                            {{-- wire:ignore: گالری به واریانت وابسته نیست؛ بدون آن رندر مجدد Livewire وضعیت Swiper را پاک می‌کند --}}
                            <div wire:ignore class="swiper productMainSwiper h-[200px] md:h-[450px]">
                                <div class="swiper-wrapper">
                                    @foreach($product->media()->get() ?? [] as $img)
                                        <div class="swiper-slide">
                                            <div class="swiper-zoom-container flex h-full w-full items-center justify-center p-3 md:p-4">
                                                <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-2xl">
                                                    <img
                                                        src="{{ asset('storage/' . $img->file_path) }}"
                                                        class="max-h-full max-w-full rounded-2xl object-contain transition-transform duration-700"
                                                        alt="{{ $product->title }}"
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="swiper-button-next !w-10 !h-10 md:!w-12 md:!h-12 !bg-white/90 dark:!bg-black/60 rounded-2xl after:!text-sm shadow-lg !text-brown-600 border border-white/50 dark:border-white/5"></div>
                                <div class="swiper-button-prev !w-10 !h-10 md:!w-12 md:!h-12 !bg-white/90 dark:!bg-black/60 rounded-2xl after:!text-sm shadow-lg !text-brown-600 border border-white/50 dark:border-white/5"></div>
                            </div>
                        </div>

                        {{-- Thumbnails --}}
                        <div class="w-full max-w-[400px]">
                            <div wire:ignore class="swiper productThumbsSwiper !pb-5">
                                <div class="swiper-wrapper">
                                    @foreach($product->media()->whereNull('external_url')->get() ?? [] as $img)
                                        <div class="swiper-slide shadow-lg cursor-pointer rounded-[1.5rem] border-2 border-transparent bg-white/40 dark:bg-white/5 p-2 transition-all opacity-40 overflow-hidden">
                                            <img src="{{asset('storage/'.$img->file_path)}}" class="w-full aspect-square object-contain" alt="thumb">
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ===================== Info column ===================== --}}
                    <div class="lg:col-span-7 space-y-6">

                        {{-- Title block --}}
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3">
                                @if($product->primaryCategory)
                                    <a href="{{ route('categories.show', $product->primaryCategory->slug) }}" class="text-[11px] font-black px-3 py-1 rounded-lg text-primary-700 bg-primary-50 dark:text-primary-300 dark:bg-primary-500/10 hover:bg-primary-100 transition-colors">
                                        {{ $product->primaryCategory->title }}
                                    </a>
                                @endif
                                @if($product->brand)
                                    <span class="text-[11px] font-black text-gray-700 dark:text-gray-300">{{ $product->brand->title }}</span>
                                @endif
                                @if($product->barcode)
                                    <span class="text-[11px] font-bold text-gray-500">شناسه کالا: <span dir="ltr">{{ $product->barcode }}</span></span>
                                @endif
                            </div>

                            <h1 class="text-2xl font-black leading-relaxed text-gray-900 dark:text-white">
                                {{ $product->title }}
                            </h1>

                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
                                {{-- امتیاز فقط وقتی دیدگاه امتیازدار تأییدشده وجود دارد --}}
                                @if($ratingCount)
                                    <a href="#product-tabs" wire:click="$set('activeTab', 'reviews')" class="flex items-center gap-1.5 text-xs font-black text-gray-800 dark:text-gray-200 hover:text-primary-600">
                                        <svg class="w-4 h-4 text-secondary-500" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                                        <span class="tabular-nums">{{ $ratingAverage }}</span>
                                        <span class="text-gray-500 font-bold">({{ $ratingCount }} امتیاز)</span>
                                    </a>
                                @else
                                    <span class="text-[11px] font-bold text-gray-500">هنوز امتیازی ثبت نشده</span>
                                @endif
                                <a href="#product-tabs" wire:click="$set('activeTab', 'faq')" class="text-primary-700 dark:text-primary-300 text-[11px] font-bold hover:underline">پرسش و پاسخ ({{ $product->questions()->active()->count() }})</a>
                                <button
                                    type="button"
                                    wire:click="toggleWishlist"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleWishlist"
                                    aria-pressed="{{ $isWishlisted ? 'true' : 'false' }}"
                                    class="flex items-center gap-1.5 min-h-11 text-[11px] font-bold transition-colors disabled:opacity-50 {{ $isWishlisted ? 'text-red-600' : 'text-gray-500 hover:text-red-600' }}">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="{{ $isWishlisted ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                                    {{ $isWishlisted ? 'در علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی' }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                            {{-- ---------- Options / specs ---------- --}}
                            <div class="xl:col-span-7 space-y-6">

                                {{-- Variant options: سایزها/رنگ‌های بدون موجودی غیرفعال‌اند --}}
                                <div class="space-y-6">
                                    @foreach($product->options ?? [] as $option)
                                        @php
                                            $isSize = $option->id === $sizeOptionId;
                                            $optionValues = $product->variants->pluck('values')->flatten()->where('option_id', $option->id)->unique('id')->sortBy('sort');
                                            $isColor = $option->display_type === 'color' || ($option->display_type === null && $optionValues->whereNotNull('color_code')->isNotEmpty());
                                            $currentValue = $selectedValues->firstWhere('option_id', $option->id);
                                            // مقدارهای انتخاب‌شده‌ی سایر ویژگی‌ها (برای تشخیص ترکیب موجود)
                                            $otherSelected = $selectedValues->where('option_id', '!=', $option->id)->pluck('id');
                                        @endphp
                                        <fieldset class="space-y-3" @if($isSize) id="size-picker" @endif>
                                            <div class="flex items-center justify-between gap-3">
                                                <legend class="text-[13px] font-black text-gray-900 dark:text-white">
                                                    {{ $option->title }}:
                                                    <span class="font-bold {{ $isSize && ! $sizeChosen ? 'text-secondary-700 dark:text-secondary-300' : 'text-gray-600 dark:text-gray-400' }}">
                                                        {{ $isSize && ! $sizeChosen ? 'انتخاب کنید' : $currentValue?->title }}
                                                    </span>
                                                </legend>
                                                @if($isSize)
                                                    <button type="button" x-data @click="$dispatch('open-size-guide')"
                                                            class="inline-flex items-center gap-1.5 min-h-11 text-[12px] font-bold text-primary-700 dark:text-primary-300 hover:underline underline-offset-4">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18v10H3zM7 7v4m4-4v2m4-2v4m4-4v2"/></svg>
                                                        راهنمای انتخاب سایز
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                @foreach($optionValues as $item)
                                                    @php
                                                        $isActive = $currentValue?->id === $item->id && (! $isSize || $sizeChosen);
                                                        // با انتخاب‌های فعلی موجود است؟ / در ترکیب دیگری موجود است؟ / اصلاً موجود نیست
                                                        $comboInStock = $inStockVariants->contains(fn ($v) => $v->values->contains('id', $item->id) && $otherSelected->diff($v->values->pluck('id'))->isEmpty());
                                                        $valueOut = ! in_array($item->id, $availableValueIds);
                                                        $label = $item->title . ($valueOut ? ' (ناموجود)' : (! $comboInStock ? ' (موجود در ترکیب دیگر)' : ''));
                                                        $hasUnavailable = ($hasUnavailable ?? false) || $valueOut || ! $comboInStock;
                                                    @endphp
                                                    @if($isColor)
                                                        <button type="button"
                                                                wire:click="selectOption({{ $item->id }})"
                                                                wire:loading.attr="disabled" wire:target="selectOption"
                                                                @disabled($valueOut)
                                                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                                                aria-label="{{ $label }}"
                                                                title="{{ $label }}"
                                                                class="relative w-11 h-11 rounded-full p-1 border-2 transition-colors {{ $isActive ? 'border-primary-600 dark:border-primary-400' : 'border-gray-200 dark:border-white/10 hover:border-gray-400' }} {{ $valueOut ? 'opacity-40' : '' }}">
                                                            <span class="block w-full h-full rounded-full border border-black/10" style="background-color: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $item->color_code) ? $item->color_code : '#d1d5db' }}"></span>
                                                            <span wire:loading.flex wire:target="selectOption({{ $item->id }})" class="absolute inset-0 items-center justify-center rounded-full bg-white/70 dark:bg-black/50 text-primary-700 dark:text-primary-300"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                                            @if($valueOut)
                                                                <span class="absolute inset-0 m-auto w-[2px] h-[120%] bg-gray-500 rotate-45 rounded-full" aria-hidden="true"></span>
                                                            @endif
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                                wire:click="selectOption({{ $item->id }})"
                                                                wire:loading.attr="disabled" wire:target="selectOption"
                                                                @disabled($valueOut)
                                                                aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                                                                aria-label="{{ $label }}"
                                                                class="option-btn relative min-w-12 min-h-11 px-4 rounded-xl border-2 text-sm font-bold tabular-nums transition-colors
                                                                    {{ $isActive ? 'border-primary-600 bg-primary-50 text-primary-800 dark:border-primary-400 dark:bg-primary-500/10 dark:text-primary-200' : 'border-gray-200 bg-white text-gray-800 hover:border-gray-400 dark:border-white/10 dark:bg-white/5 dark:text-gray-200' }}
                                                                    {{ $valueOut ? 'opacity-40 line-through cursor-not-allowed hover:border-gray-200' : '' }}
                                                                    {{ ! $valueOut && ! $comboInStock ? 'border-dashed' : '' }}">
                                                            <span wire:loading.class="invisible" wire:target="selectOption({{ $item->id }})">{{ $item->title }}</span>
                                                            <span wire:loading.flex wire:target="selectOption({{ $item->id }})" class="absolute inset-0 items-center justify-center"><svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg></span>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            </div>

                                            @if($isSize && $sizeError && ! $sizeChosen)
                                                <p role="alert" class="text-xs font-bold text-red-600 dark:text-red-400">برای افزودن به سبد، ابتدا سایز را انتخاب کنید.</p>
                                            @endif
                                        </fieldset>
                                    @endforeach

                                    @if($hasUnavailable ?? false)
                                        <p class="text-[11px] font-bold text-gray-500 leading-6">
                                            گزینه‌های خط‌خورده موجود نیستند؛ گزینه‌های خط‌چین فقط در ترکیب دیگری موجودند.
                                        </p>
                                    @endif
                                </div>

                                {{-- Specifications --}}
                                @if($product->specifications->isNotEmpty())
                                <div class="space-y-4">
                                    <p class="text-[13px] font-black text-gray-900 dark:text-white">ویژگی‌های اصلی کالا:</p>

                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($product->specifications as $specification)
                                            <div class="bg-gray-50 dark:bg-white/5 border border-gray-100 dark:border-white/5 p-3 rounded-2xl flex flex-col gap-2 transition-all hover:border-brown-500/30 group">
                                        <span class="text-[11px] text-gray-600 dark:text-gray-400 font-medium">
                                            {{ $specification->title }}
                                        </span>
                                                <span class="text-xs font-black text-gray-900 dark:text-white">
                                            {{ $specification->value }}
                                        </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                            </div>

                            {{-- ---------- Meta / buy box ---------- --}}
                            <div class="xl:col-span-5 space-y-4">
                                <div class="bg-gray-50 dark:bg-white/[0.03] border border-gray-100 dark:border-white/5 rounded-[2rem] p-6 space-y-6">

                                    {{-- فروشنده و قابلیت‌های واقعی فروشگاه (بدون امتیاز یا وعده‌ی زمان ارسال ساختگی) --}}
                                    <div class="space-y-4">
                                        <a href="{{ route('page.show', 'about-us') }}" class="flex items-center gap-4 group">
                                            <x-brand.mark tile class="w-12 h-12 shrink-0"/>
                                            <div>
                                                <p class="text-[14px] font-black text-gray-900 dark:text-white group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors">فروشنده: {{ config('brand.name') }}</p>
                                                <span class="text-[11px] font-bold text-gray-500">درباره فروشگاه</span>
                                            </div>
                                        </a>

                                        <ul class="space-y-3 pt-4 border-t border-gray-200 dark:border-white/5 text-[12px] font-bold text-gray-700 dark:text-gray-300">
                                            <li class="flex items-center gap-3">
                                                <svg class="w-5 h-5 shrink-0 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.129-1.125V11.25c0-4.446-3.61-8.156-8.086-8.156H10.875c-.621 0-1.125.504-1.125 1.125v12.375c0 .621.504 1.125 1.125 1.125h1.125m10.125 0V14.25m-9 0h9"/></svg>
                                                انتخاب بازه‌ی زمانی ارسال هنگام تسویه‌حساب
                                            </li>
                                            <li class="flex items-center gap-3">
                                                <svg class="w-5 h-5 shrink-0 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/></svg>
                                                فاکتور و پیگیری سفارش در حساب کاربری
                                            </li>
                                            <li class="flex items-center gap-3">
                                                <svg class="w-5 h-5 shrink-0 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/></svg>
                                                <span>درخواست مرجوعی از حساب کاربری · <a href="{{ route('page.show', \App\Support\Sections\ReturnPolicy::PAGE_SLUG) }}" class="text-primary-700 dark:text-primary-300 underline underline-offset-4">شرایط مرجوعی</a></span>
                                            </li>
                                        </ul>
                                    </div>

                                    @if($inStock)

                                        {{-- Price --}}
                                        <div class="space-y-2 pt-4 border-t border-gray-200 dark:border-white/5 transition-opacity" wire:loading.class="opacity-40" wire:target="selectOption">
                                            @if($pricing['has_discount'])
                                                <div class="flex items-center justify-between">
                                                    <span class="text-xs font-bold text-gray-500 line-through tabular-nums">
                                                        {{ number_format($pricing['before_discount']) }}
                                                    </span>
                                                    <span class="bg-secondary-600 text-white text-[11px] font-black px-2 py-0.5 rounded-lg tabular-nums">
                                                        {{ $pricing['discount_percent'] }}٪ تخفیف
                                                    </span>
                                                </div>
                                            @endif

                                            <div class="flex items-baseline justify-end gap-1.5">
                                                <span id="product-final-price" class="text-3xl font-black text-gray-900 dark:text-white tabular-nums">
                                                    {{ number_format($pricing['after_discount']) }}
                                                </span>
                                                <span class="text-xs font-bold text-gray-500">تومان</span>
                                            </div>
                                        </div>

                                        {{-- Add to cart (نوار چسبان موبایل وقتی این دکمه از دید خارج شود نمایش داده می‌شود) --}}
                                        <button type="button" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart, selectOption" id="add-to-cart-btn"
                                                x-data x-intersect:leave="$dispatch('buy-box-hidden', true)" x-intersect:enter="$dispatch('buy-box-hidden', false)"
                                                class="w-full h-14 bg-brown-600 hover:bg-brown-700 text-white rounded-2xl font-black text-sm flex items-center justify-center gap-3 disabled:opacity-70">
                                            <span wire:loading.remove wire:target="addToCart">{{ $sizeChosen ? 'افزودن به سبد خرید' : 'انتخاب سایز و افزودن به سبد' }}</span>
                                            <span wire:loading wire:target="addToCart">در حال افزودن…</span>
                                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                                        </button>

                                    @else

                                        {{-- Out of stock --}}
                                        <div class="pt-4 border-t border-gray-200 dark:border-white/5 space-y-5">
                                            <div class="space-y-3 flex flex-col items-center text-center">
                                                <div class="w-14 h-14 rounded-2xl bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"></path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h3 class="text-lg font-black text-gray-900 dark:text-white">
                                                        {{ $inStockVariants->isNotEmpty() ? 'این ترکیب فعلاً موجود نیست' : 'این کالا فعلاً موجود نیست' }}
                                                    </h3>
                                                    @if($inStockVariants->isNotEmpty())
                                                        <p class="text-xs font-bold text-gray-500 mt-2 leading-relaxed">سایز یا رنگ دیگری را از گزینه‌های بالا انتخاب کنید.</p>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($product->primaryCategory)
                                                <a href="{{ route('categories.show', $product->primaryCategory->slug) }}" class="flex items-center justify-center gap-2 min-h-11 text-xs font-black text-primary-700 dark:text-primary-300 hover:underline underline-offset-4">
                                                    مشاهده کالاهای مشابه
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"></path>
                                                    </svg>
                                                </a>
                                            @endif

                                        </div>

                                    @endif
                                </div>

                                {{-- Footer note --}}
                                <p class="flex items-center justify-center gap-2 text-[11px] font-bold {{ $inStock ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-500' }}">
                                    <span class="w-2 h-2 rounded-full {{ $inStock ? 'bg-emerald-500' : 'bg-gray-400' }}" aria-hidden="true"></span>
                                    {{ $inStock ? 'موجود در انبار' : 'ناموجود' }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- نوار چسبان خرید در موبایل: بالای نوار ناوبری پایین، فقط وقتی دکمه‌ی اصلی دیده نمی‌شود --}}
        @if($inStock)
            <div x-data="{ show: false }" x-on:buy-box-hidden.window="show = $event.detail"
                 x-show="show" x-transition.opacity x-cloak
                 class="fixed inset-x-3 bottom-28 z-40 md:hidden">
                <div class="flex items-center gap-3 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 p-2 ps-4 shadow-[var(--shadow-lift)]">
                    <div class="min-w-0 flex-1 leading-tight">
                        @if($pricing['has_discount'])
                            <span class="block text-[11px] font-bold text-gray-500 line-through tabular-nums">{{ number_format($pricing['before_discount']) }}</span>
                        @endif
                        <span class="text-base font-black text-gray-900 dark:text-white tabular-nums">{{ number_format($pricing['after_discount']) }}</span>
                        <span class="text-[11px] font-bold text-gray-500">تومان</span>
                    </div>
                    <button type="button" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart, selectOption"
                            class="h-12 px-5 shrink-0 bg-brown-600 text-white rounded-xl font-black text-sm disabled:opacity-70">
                        {{ $sizeChosen ? 'افزودن به سبد' : 'انتخاب سایز' }}
                    </button>
                </div>
            </div>
        @endif

        {{-- راهنمای انتخاب سایز: فقط روش اندازه‌گیری (دانش عمومی) و جدول ثبت‌شده توسط مدیر؛ هیچ عدد تبدیل سایزی ساخته نشده است --}}
        @if($sizeOptionId)
            <div x-data="{ open: false }" x-on:open-size-guide.window="open = true" x-on:keydown.escape.window="open = false"
                 x-show="open" x-cloak class="fixed inset-0 z-[1200] flex items-end md:items-center justify-center p-0 md:p-6"
                 role="dialog" aria-modal="true" aria-labelledby="size-guide-title">
                <div class="absolute inset-0 bg-black/50" x-on:click="open = false" x-show="open" x-transition.opacity></div>
                <div x-show="open" x-transition x-trap.noscroll="open"
                     class="relative w-full md:max-w-lg max-h-[85vh] overflow-y-auto rounded-t-3xl md:rounded-3xl bg-white dark:bg-gray-900 p-6 space-y-5">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="size-guide-title" class="text-lg font-black text-gray-900 dark:text-white">راهنمای انتخاب سایز</h2>
                        <button type="button" x-on:click="open = false" aria-label="بستن راهنمای سایز"
                                class="w-11 h-11 flex items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-white/5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="space-y-3">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white">اندازه‌گیری طول پا</h3>
                        <ol class="list-decimal ps-5 space-y-2 text-sm leading-7 text-gray-700 dark:text-gray-300">
                            <li>یک برگه کاغذ را روی زمین و کنار دیوار بگذارید.</li>
                            <li>با جوراب ورزشی، پاشنه را به دیوار بچسبانید و صاف بایستید.</li>
                            <li>بلندترین نقطه‌ی انگشتان را روی کاغذ علامت بزنید و فاصله تا دیوار را به سانتی‌متر اندازه بگیرید.</li>
                            <li>هر دو پا را اندازه بگیرید و عدد بزرگ‌تر را مبنا قرار دهید.</li>
                        </ol>
                    </div>

                    <div class="rounded-2xl border border-gray-200 dark:border-white/10 p-4 space-y-3">
                        <h3 class="text-sm font-black text-gray-900 dark:text-white">جدول سایز</h3>
                        @if($sizeGuidePage)
                            <p class="text-sm leading-7 text-gray-700 dark:text-gray-300">جدول تبدیل سایز (EU / US / UK / سانتی‌متر) در صفحه‌ی راهنمای سایز آمده است.</p>
                            <a href="{{ route('page.show', 'size-guide') }}" class="inline-flex items-center justify-center min-h-11 px-5 rounded-xl bg-primary-600 text-white text-sm font-black">مشاهده جدول سایز</a>
                        @else
                            {{-- TODO(OWNER): صفحه‌ای با slug «size-guide» در پنل > صفحات بسازید و جدول سایز تأییدشده را در آن قرار دهید --}}
                            <p class="text-sm leading-7 text-gray-700 dark:text-gray-300">
                                جدول تبدیل سایز این فروشگاه هنوز منتشر نشده است. اندازه‌ی سایزها بین برندها متفاوت است؛ اگر درباره‌ی سایز این محصول سؤال دارید، در بخش پرسش و پاسخ بپرسید.
                            </p>
                            <a href="#product-tabs" x-on:click="open = false" wire:click="$set('activeTab', 'faq')" class="inline-flex items-center min-h-11 text-sm font-black text-primary-700 dark:text-primary-300 underline underline-offset-4">پرسیدن سؤال درباره‌ی سایز</a>
                        @endif
                    </div>
                </div>
            </div>
        @endif
        <!-- END CONTENT -->

        <!-- START SECTION SELLER -->

        <!-- START SECTION DETAIL -->
        <section id="product-tabs" class="scroll-mt-28">
            <div class="">
                <div class="bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md border border-white/40 dark:border-white/10 rounded-[3rem] shadow-lg shadow-gray-200/30 dark:shadow-none">

                    <div class="flex items-center gap-2 p-4 bg-gray-50/50 dark:bg-white/[0.02] border-b rounded-[3rem] border-gray-100 dark:border-white/5 overflow-x-auto no-scrollbar">

                        {{-- بررسی اجمالی --}}
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'overview')"
                            id="btn-overview"
                            class="
            flex items-center gap-2 px-6 py-3 rounded-2xl
            text-sm font-black transition-all whitespace-nowrap
            {{ $activeTab === 'overview'
                ? 'active bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'
            }}
        "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="w-5 h-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                                />
                            </svg>

                            بررسی اجمالی
                        </button>


                        {{-- بررسی تخصصی --}}
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'expert')"
                            id="btn-expert"
                            class="
            flex items-center gap-2 px-6 py-3 rounded-2xl
            text-sm font-black transition-all whitespace-nowrap
            {{ $activeTab === 'expert'
                ? 'active bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'
            }}
        "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="w-5 h-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"
                                />
                            </svg>

                            بررسی تخصصی
                        </button>


                        {{-- مشخصات فنی --}}
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'specs')"
                            id="btn-specs"
                            class="
            flex items-center gap-2 px-6 py-3 rounded-2xl
            text-sm font-black transition-all whitespace-nowrap
            {{ $activeTab === 'specs'
                ? 'active bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'
            }}
        "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="w-5 h-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 12h11.25"
                                />
                            </svg>

                            مشخصات فنی
                        </button>


                        {{-- نظرات کاربران --}}
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'reviews')"
                            id="btn-reviews"
                            class="
            flex items-center gap-2 px-6 py-3 rounded-2xl
            text-sm font-black transition-all whitespace-nowrap
            {{ $activeTab === 'reviews'
                ? 'active bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'
            }}
        "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="w-5 h-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"
                                />
                            </svg>

                            نظرات کاربران
                        </button>


                        {{-- پرسش و پاسخ --}}
                        <button
                            type="button"
                            wire:click="$set('activeTab', 'faq')"
                            id="btn-faq"
                            class="
            flex items-center gap-2 px-6 py-3 rounded-2xl
            text-sm font-black transition-all whitespace-nowrap
            {{ $activeTab === 'faq'
                ? 'active bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white'
                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'
            }}
        "
                        >
                            <svg xmlns="http://www.w3.org/2000/svg"
                                 fill="none"
                                 viewBox="0 0 24 24"
                                 stroke-width="2"
                                 stroke="currentColor"
                                 class="w-5 h-5">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"
                                />
                            </svg>

                            پرسش و پاسخ
                        </button>

                    </div>
                    <div class="p-8 lg:p-12">

                        <!--Overview-->
                        <div
                            id="tab-overview"
                            class="tab-content {{ $activeTab === 'overview' ? 'block' : 'hidden' }} animate-fadeIn"
                        >
                            <div class="space-y-16">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                                    <div class="lg:col-span-7 space-y-6 order-2 lg:order-1">
                                        <div class="flex items-center gap-3">
                                            <span class="w-2 h-8 bg-brown-600 rounded-full shadow-[0_0_15px_rgba(0,115,117,0.6)]"></span>
                                            <h3 class="text-2xl font-black text-zinc-900 dark:text-white">{{$product->title}}</h3>
                                        </div>
                                        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300 leading-9 text-justify">
                                            {{$product->short_description}}
                                        </p>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 pt-6 border-t border-white/20 dark:border-white/5">
                                            @foreach($product->specifications ?? [] as $specification)
                                                <div class="flex items-center gap-3 group">
                                                    <div class="w-8 h-8 rounded-lg bg-brown-600/10 flex items-center justify-center text-brown-600 group-hover:bg-brown-600 group-hover:text-white transition-all">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    </div>
                                                    <span class="text-xs font-black text-zinc-900 dark:text-white">{{$specification->title}}: <span class="text-xs font-black text-zinc-700 dark:text-zinc-300">{{$specification->value}}</span></span>
                                                </div>
                                            @endforeach


                                        </div>
                                    </div>

                                    <div class="hidden lg:block lg:col-span-5 order-1 lg:order-2 relative group">
                                        <div class="absolute -inset-4 bg-gradient-to-tr from-brown-600/20 to-brown-600/20 rounded-[3.5rem] blur-md opacity-50 group-hover:opacity-80 transition duration-1000"></div>

                                        @if($product->featuredVideoUrl)
                                            <video
                                                src="{{ $product->featuredVideoUrl }}"
                                                class="relative rounded-[3rem] w-full h-[450px] object-cover border border-white/30 shadow-2xl"
                                                controls
                                                preload="metadata"
                                            ></video>
                                        @else
                                            <img
                                                src="{{ $product->featuredImageUrl ?: asset(config('brand.placeholder')) }}"
                                                alt="{{ $product->title }}"
                                                class="relative rounded-[3rem] w-full h-[450px] object-cover border border-white/30 shadow-2xl"
                                            >
                                        @endif
                                    </div>
                                </div>



                            </div>
                        </div>

                        <!--Expert review-->
                        <div
                            id="tab-expert"
                            class="tab-content {{ $activeTab === 'expert' ? 'block' : 'hidden' }} animate-fadeIn"
                        >
                            <div class="space-y-20">

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                                    <div class="lg:col-span-7 space-y-6">
                                        <div class="flex items-center gap-3">
                                            <span class="w-12 h-1.5 bg-brown-600 rounded-full shadow-[0_0_15px_rgba(0,115,117,0.4)]"></span>
                                            <h3 class="text-2xl font-black text-zinc-900 dark:text-white">{{$product->title}}</h3>
                                        </div>
                                        <div class="
    text-sm font-medium text-zinc-600 dark:text-white
    leading-9 text-justify
    [&_*]:dark:text-white
">
                                            {!! \App\Support\SafeHtml::clean($product->description) !!}
                                        </div>

                                    </div>
                                    <div class="hidden lg:block lg:col-span-5 relative">
                                        <div class="absolute -inset-4 bg-brown-600/10 rounded-[4rem] -rotate-3 backdrop-blur-sm"></div>
                                        <img src="{{$product->featuredImageUrl ?: asset(config('brand.placeholder')) }}" class="relative rounded-[3rem] shadow-lg shadow-brown-500/10 object-cover h-[400px] w-full border border-white/20" alt="Titanium Frame Detail">
                                    </div>
                                </div>



                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">

                                    {{-- نقاط قوت و چالش‌ها --}}
                                    <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-6">

                                        {{-- نقاط قوت --}}
                                        <div
                                            class="p-10
                   bg-emerald-500/[0.03]
                   backdrop-blur-md
                   rounded-[3rem]
                   border border-emerald-500/20
                   shadow-sm"
                                        >

                                            <h4 class="text-sm font-black text-emerald-600 mb-6 flex items-center gap-2">

                                                <div class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                                                    <svg
                                                        class="w-5 h-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2.5"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </div>

                                                نقاط قوت

                                            </h4>


                                            @if($this->positivePoints->isNotEmpty())

                                                <ul class="space-y-4 text-xs font-bold text-zinc-500 dark:text-zinc-400">

                                                    @foreach($this->positivePoints as $point)

                                                        <li class="flex items-center gap-3">

                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>

                                                            {{ $point }}

                                                        </li>

                                                    @endforeach

                                                </ul>

                                            @else

                                                <p class="text-xs font-bold text-zinc-400">
                                                    هنوز نقطه قوتی برای این محصول ثبت نشده است.
                                                </p>

                                            @endif

                                        </div>


                                        {{-- چالش‌ها --}}
                                        <div
                                            class="p-10
                   bg-brown-500/[0.03]
                   backdrop-blur-md
                   rounded-[3rem]
                   border border-brown-500/20
                   shadow-sm"
                                        >

                                            <h4 class="text-sm font-black text-brown-600 mb-6 flex items-center gap-2">

                                                <div class="w-8 h-8 rounded-xl bg-brown-500/10 flex items-center justify-center">

                                                    <svg
                                                        class="w-5 h-5"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                                        />
                                                    </svg>

                                                </div>

                                                چالش‌های محصول

                                            </h4>


                                            @if($this->negativePoints->isNotEmpty())

                                                <ul class="space-y-4 text-xs font-bold text-zinc-500 dark:text-zinc-400">

                                                    @foreach($this->negativePoints as $point)

                                                        <li class="flex items-center gap-3">

                                                            <span class="w-1.5 h-1.5 rounded-full bg-brown-500/50 shrink-0"></span>

                                                            {{ $point }}

                                                        </li>

                                                    @endforeach

                                                </ul>

                                            @else

                                                <p class="text-xs font-bold text-zinc-400">
                                                    هنوز چالشی برای این محصول ثبت نشده است.
                                                </p>

                                            @endif

                                        </div>

                                    </div>


                                    {{-- امتیاز --}}
                                    <div
                                        class="lg:col-span-4
               bg-zinc-900/90
               dark:bg-black/80
               backdrop-blur-md
               rounded-[3.5rem]
               p-10
               text-center
               flex flex-col
               justify-center
               items-center
               shadow-lg
               border border-white/10
               relative
               overflow-hidden
               group"
                                    >

                                        <div
                                            class="absolute top-0 right-0
                   w-32 h-32
                   bg-brown-600/20
                   blur-[60px]
                   rounded-full"
                                        ></div>


                                        <span
                                            class="text-[10px]
                   font-black
                   text-brown-400
                   uppercase
                   tracking-[0.3em]
                   mb-4
                   relative"
                                        >
            امتیاز کاربران
        </span>


                                        <div
                                            class="text-8xl
                   font-black
                   text-white
                   mb-6
                   tracking-tighter
                   relative
                   group-hover:scale-110
                   transition-transform
                   duration-500"
                                        >
                                            {{ $this->averageRating }}
                                        </div>


                                        {{-- ستاره‌ها --}}
                                        <div
                                            class="flex gap-1
                   mb-8
                   bg-white/5
                   px-4 py-2
                   rounded-2xl
                   backdrop-blur-sm
                   border border-white/5"
                                        >

                                            @for($i = 1; $i <= 5; $i++)

                                                <svg
                                                    class="w-5 h-5 {{ $i <= round($this->averageRating / 2) ? 'text-brown-500' : 'text-zinc-700' }}"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>

                                            @endfor

                                        </div>


                                        <p class="text-xs font-medium text-zinc-400 leading-7 relative">
                                            امتیاز ثبت‌شده بر اساس نظر کاربران این محصول است.
                                        </p>


                                        <button
                                            type="button"
                                            class="mt-8 w-full py-5
                   bg-brown-600
                   text-white
                   text-sm
                   font-black
                   rounded-2xl
                   hover:bg-brown-500
                   shadow-[0_15px_30px_-10px_rgba(0,115,117,0.5)]
                   transition-all
                   active:scale-95
                   relative
                   overflow-hidden"
                                        >
                                            مشاهده قیمت و خرید
                                        </button>

                                    </div>

                                </div>
                            </div>
                        </div>

                        <!--Technical specifications-->
                        <div
                            id="tab-specs"
                            class="tab-content {{ $activeTab === 'specs' ? 'block' : 'hidden' }} animate-fadeIn"
                        >
                            <div class="space-y-16 p-2" dir="rtl">


                                @if($product->specifications->isNotEmpty())
                                    <div class="relative p-8 rounded-[2.5rem]
                bg-white/30 dark:bg-[#0c0c0c]/40
                backdrop-blur-md
                border border-white/50 dark:border-white/10">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-12">

                                            @foreach($product->specifications as $specification)
                                                <div
                                                    class="flex items-center justify-between gap-6 pb-4
                           border-b border-zinc-200/50 dark:border-white/5"
                                                >

                                                    {{-- عنوان مشخصات --}}
                                                    <span class="text-[11px] font-bold text-zinc-500
                                 dark:text-zinc-400 uppercase tracking-widest">
                        {{ $specification->title }}
                    </span>

                                                    {{-- مقدار مشخصات --}}
                                                    <span class="text-xs font-black text-zinc-900
                                 dark:text-white text-left">
                        {{ $specification->value }}
                    </span>

                                                </div>
                                            @endforeach

                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>

                        <!--User comments-->
                        {{-- =========================================================
    PRODUCT REVIEWS
========================================================= --}}
                        <div
                            id="tab-reviews"
                            class="tab-content {{ $activeTab === 'reviews' ? 'block' : 'hidden' }} animate-fadeIn pb-20"
                        >

                            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">

                                {{-- =========================
                                    SIDEBAR
                                ========================== --}}
                                <div class="lg:col-span-4 lg:sticky top-10 self-start space-y-6">

                                    @php
                                        $commentsCount = $product->comments->count();

                                        $averageRating = $commentsCount
                                            ? round($product->comments->avg('rating'), 1)
                                            : 0;
                                    @endphp

                                    {{-- Rating --}}
                                    <div class="relative overflow-hidden p-10 bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md border border-white dark:border-white/10 rounded-[3rem] shadow-lg shadow-zinc-200/50 dark:shadow-none">

                                        <div class="absolute top-0 left-0 bg-brown-600 px-5 py-2 rounded-br-[1.8rem] shadow-lg shadow-brown-600/20">
                    <span class="text-[11px] font-black text-white uppercase tracking-tighter">
                        تأیید شده
                    </span>
                                        </div>

                                        <div class="flex flex-col items-center pt-8">

                    <span class="text-[13px] font-black text-zinc-400 mb-3 uppercase tracking-widest">
                        امتیاز کلی محصول
                    </span>

                                            <h4 class="text-8xl font-black text-zinc-900 dark:text-white tracking-tighter mb-5">
                                                {{ $averageRating }}
                                            </h4>

                                            <div class="flex gap-2 mb-6">

                                                @for($i = 1; $i <= 5; $i++)

                                                    <div class="w-10 h-2 rounded-full
                                {{ $i <= round($averageRating)
                                    ? 'bg-brown-600'
                                    : 'bg-zinc-200 dark:bg-zinc-800' }}">
                                                    </div>

                                                @endfor

                                            </div>

                                            <p class="text-[13px] font-bold text-zinc-500 tracking-tight">
                                                بر اساس {{ $commentsCount }} تجربه ثبت شده
                                            </p>

                                        </div>

                                    </div>


                                    {{-- Info --}}
                                    <div class="space-y-6">

                                        <div class="group relative overflow-hidden p-8 bg-white/40 dark:bg-white/[0.03] backdrop-blur-md rounded-[2.5rem] border border-white/80 dark:border-white/10 shadow-[0_20px_40px_rgba(0,0,0,0.03)] transition-all duration-500 hover:shadow-brown-500/5 hover:border-brown-500/20">

                                            <div class="absolute -top-12 -left-12 w-32 h-32 bg-brown-500/5 rounded-full blur-md group-hover:bg-brown-500/10 transition-all"></div>

                                            <div class="relative z-10 flex items-start gap-5">

                                                <div class="flex-shrink-0 w-14 h-14 bg-white dark:bg-zinc-800 rounded-[1.3rem] flex items-center justify-center shadow-sm border border-zinc-100 dark:border-white/5 text-brown-600 group-hover:scale-110 group-hover:bg-brown-600 group-hover:text-white transition-all duration-500">

                                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                                        </path>
                                                    </svg>

                                                </div>

                                                <div class="space-y-2 pt-1">

                                                    <h5 class="text-[15px] font-black text-zinc-900 dark:text-white tracking-tight">
                                                        پایش و صیانت از محتوا
                                                    </h5>

                                                    <p class="text-[12px] font-bold text-zinc-500 dark:text-zinc-400 leading-7">
                                                        دیدگاه‌ها به صورت
                                                        <span class="text-brown-600 dark:text-brown-400">
                                    هوشمند
                                </span>
                                                        پایش شده و پس از تأیید تیم پشتیبانی، برای عموم کاربران نمایش داده خواهند شد.
                                                    </p>

                                                </div>

                                            </div>

                                        </div>


                                        <button
                                            type="button"
                                            wire:click="$refresh"
                                            wire:loading.attr="disabled"
                                            class="relative w-full group overflow-hidden py-5 bg-zinc-900 dark:bg-white rounded-[1.8rem] transition-all duration-500 active:scale-[0.97] shadow-lg shadow-zinc-900/10 dark:shadow-white/5">

                                            <div class="absolute inset-0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 bg-gradient-to-r from-transparent via-white/20 dark:via-zinc-900/10 to-transparent"></div>

                                            <div class="relative flex items-center justify-center gap-3">

                                                <svg class="w-5 h-5 text-white dark:text-zinc-900"
                                                     fill="none"
                                                     stroke="currentColor"
                                                     viewBox="0 0 24 24">

                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          stroke-width="2.5"
                                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                                    </path>

                                                </svg>

                                                <span class="text-[13px] font-black text-white dark:text-zinc-900 uppercase tracking-[0.2em]">
                            بروزرسانی زنده نظرات
                        </span>

                                            </div>

                                        </button>

                                    </div>

                                </div>


                                {{-- =========================
                                    MAIN
                                ========================== --}}
                                <div class="lg:col-span-8 space-y-12">


                                    {{-- =========================
                                        COMMENT FORM
                                    ========================== --}}
                                    <section class="relative overflow-hidden bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md border border-white dark:border-white/10 rounded-[3rem] p-8 lg:p-12 shadow-lg shadow-zinc-200/50 dark:shadow-none">

                                        <div class="flex items-center gap-4 mb-12">

                                            <span class="w-2.5 h-8 bg-brown-600 rounded-full shadow-[0_0_15px_rgba(0,115,117,0.4)]"></span>

                                            <h3 class="text-[16px] font-black text-zinc-900 dark:text-white uppercase tracking-tighter">
                                                ثبت تجربه و دیدگاه جدید
                                            </h3>

                                        </div>


                                        <form
                                            wire:submit="submitComment"
                                            class="space-y-10">


                                            {{-- =========================
                                                RATING
                                            ========================== --}}
                                            <div class="space-y-4">

                                                <label class="text-[13px] font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mr-2">
                                                    امتیاز به محصول
                                                </label>

                                                <div class="flex flex-row-reverse justify-end gap-2 pt-1 text-zinc-300 dark:text-zinc-700">

                                                    @for($i = 5; $i >= 1; $i--)

                                                        <input
                                                            type="radio"
                                                            wire:model="rating"
                                                            value="{{ $i }}"
                                                            id="s{{ $i }}"
                                                            class="hidden peer/r{{ $i }}">

                                                        <label
                                                            for="s{{ $i }}"
                                                            class="cursor-pointer peer-hover/r{{ $i }}:text-brown-600 peer-checked/r{{ $i }}:text-brown-600 transition-all scale-110 hover:scale-125">

                                                            <svg class="w-10 h-10 fill-current"
                                                                 viewBox="0 0 20 20">

                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292z"/>

                                                            </svg>

                                                        </label>

                                                    @endfor

                                                </div>

                                                @error('rating')
                                                <p class="text-xs font-bold text-red-500">
                                                    {{ $message }}
                                                </p>
                                                @enderror

                                            </div>


                                            {{-- =========================
                                                PROS / CONS
                                            ========================== --}}
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">


                                                {{-- PROS --}}
                                                <div class="space-y-4">

                                                    <label class="text-[13px] font-black text-emerald-600 dark:text-emerald-500 uppercase tracking-widest mr-2">
                                                        ویژگی‌های مثبت
                                                    </label>

                                                    <div class="relative group">

                                                        <input
                                                            type="text"
                                                            id="prosInput"
                                                            wire:model.lazy="prosInput"
                                                            wire:keydown.enter.prevent="addProsFromInput"
                                                            class="w-full bg-white/80 dark:bg-black/40 border border-emerald-500/20 rounded-2xl px-6 py-5 text-[14px] font-bold outline-none focus:border-emerald-500 transition-all shadow-sm"
                                                            placeholder="افزودن ویژگی مثبت + اینتر">

                                                        <button
                                                            type="button"
                                                            wire:click="addProsFromInput"
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 w-11 h-11 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30 hover:scale-110 active:scale-95 transition-all">

                                                            <svg class="w-6 h-6"
                                                                 fill="none"
                                                                 stroke="currentColor"
                                                                 viewBox="0 0 24 24">

                                                                <path stroke-linecap="round"
                                                                      stroke-linejoin="round"
                                                                      stroke-width="3"
                                                                      d="M12 6v12M6 12h12">
                                                                </path>

                                                            </svg>

                                                        </button>

                                                    </div>


                                                    <div class="flex flex-wrap gap-3">

                                                        @foreach($pros as $index => $pro)

                                                            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">

                                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>

                                                                <span class="text-[12px] font-black text-emerald-600">
                                            {{ $pro }}
                                        </span>

                                                                <button
                                                                    type="button"
                                                                    wire:click="removePros({{ $index }})"
                                                                    class="mr-1 text-emerald-500 hover:text-red-500">

                                                                    ×

                                                                </button>

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                    @error('pros')
                                                    <p class="text-xs font-bold text-red-500">
                                                        {{ $message }}
                                                    </p>
                                                    @enderror

                                                </div>


                                                {{-- CONS --}}
                                                <div class="space-y-4">

                                                    <label class="text-[13px] font-black text-rose-500 uppercase tracking-widest mr-2">
                                                        نقاط ضعف
                                                    </label>

                                                    <div class="relative group">

                                                        <input
                                                            type="text"
                                                            id="consInput"
                                                            wire:model.lazy="consInput"
                                                            wire:keydown.enter.prevent="addConsFromInput"
                                                            class="w-full bg-white/80 dark:bg-black/40 border border-rose-500/20 rounded-2xl px-6 py-5 text-[14px] font-bold outline-none focus:border-rose-500 transition-all shadow-sm"
                                                            placeholder="افزودن مورد منفی + اینتر">

                                                        <button
                                                            type="button"
                                                            wire:click="addConsFromInput"
                                                            class="absolute left-3 top-1/2 -translate-y-1/2 w-11 h-11 bg-rose-500 text-white rounded-xl flex items-center justify-center shadow-lg shadow-rose-500/30 hover:scale-110 active:scale-95 transition-all">

                                                            <svg class="w-6 h-6"
                                                                 fill="none"
                                                                 stroke="currentColor"
                                                                 viewBox="0 0 24 24">

                                                                <path stroke-linecap="round"
                                                                      stroke-linejoin="round"
                                                                      stroke-width="3"
                                                                      d="M12 6v12M6 12h12">
                                                                </path>

                                                            </svg>

                                                        </button>

                                                    </div>


                                                    <div class="flex flex-wrap gap-3">

                                                        @foreach($cons as $index => $con)

                                                            <div class="flex items-center gap-2 px-4 py-2 bg-rose-500/10 border border-rose-500/20 rounded-xl">

                                                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>

                                                                <span class="text-[12px] font-black text-rose-600">
                                            {{ $con }}
                                        </span>

                                                                <button
                                                                    type="button"
                                                                    wire:click="removeCons({{ $index }})"
                                                                    class="mr-1 text-rose-500 hover:text-red-700">

                                                                    ×

                                                                </button>

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                    @error('cons')
                                                    <p class="text-xs font-bold text-red-500">
                                                        {{ $message }}
                                                    </p>
                                                    @enderror

                                                </div>

                                            </div>


                                            {{-- =========================
                                                BODY
                                            ========================== --}}
                                            <div class="space-y-4">

                                                <label class="text-[13px] font-black text-zinc-500 dark:text-zinc-400 uppercase tracking-widest mr-2">
                                                    متن دیدگاه شما
                                                </label>

                                                <textarea
                                                    wire:model="commentBody"
                                                    rows="5"
                                                    class="w-full bg-white/80 dark:bg-black/40 border border-zinc-200 dark:border-white/5 rounded-[2.5rem] px-8 py-6 text-[14px] font-bold text-zinc-900 dark:text-white outline-none focus:ring-4 focus:ring-brown-600/10 focus:border-brown-600 transition-all shadow-sm resize-none"
                                                    placeholder="جزئیات تجربه استفاده از محصول را اینجا بنویسید..."></textarea>

                                                @error('commentBody')
                                                <p class="text-xs font-bold text-red-500 mr-2">
                                                    {{ $message }}
                                                </p>
                                                @enderror

                                            </div>


                                            {{-- =========================
                                                SUCCESS
                                            ========================== --}}
                                            @if(session()->has('comment_success'))

                                                <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 text-[13px] font-bold">
                                                    {{ session('comment_success') }}
                                                </div>

                                            @endif


                                            {{-- =========================
                                                SUBMIT
                                            ========================== --}}
                                            <button
                                                type="submit"
                                                wire:loading.attr="disabled"
                                                class="w-full py-6
           bg-brown-600
           text-white
           rounded-[2rem]
           font-black
           text-[14px]
           uppercase
           tracking-[0.3em]
           shadow-[0_20px_40px_rgba(0,115,117,0.25)]
           hover:bg-brown-700
           dark:hover:bg-brown-500
           transition-all
           duration-500
           active:scale-[0.97]
           border-none
           outline-none
           disabled:opacity-50">

    <span wire:loading.remove wire:target="submitComment">
        تایید و ثبت نهایی دیدگاه
    </span>

                                                <span wire:loading wire:target="submitComment">
        در حال ثبت دیدگاه...
    </span>

                                            </button>

                                        </form>

                                    </section>


                                    {{-- =================================================
                                        COMMENTS LIST
                                    ================================================== --}}
                                    <div class="space-y-8" dir="rtl">

                                        @forelse($product->comments as $comment)

                                            <div class="group relative p-8 bg-white/40 dark:bg-zinc-900/40 backdrop-blur-md border border-gray-200 dark:border-white/10 rounded-[3rem] transition-all duration-500 hover:shadow-lg hover:-translate-y-1">

                                                {{-- Header --}}
                                                <div class="flex flex-col md:flex-row justify-between items-start gap-6">

                                                    <div class="flex items-center gap-5">

                                                        <div class="relative">

                                                            <div class="w-16 h-16 rounded-[1.5rem] bg-brown-600/10 flex items-center justify-center text-brown-600 font-black text-xl border border-brown-600/20">

                                                                {{ mb_substr($comment->user?->name ?? 'کاربر', 0, 2) }}

                                                            </div>

                                                            @if($comment->is_verified)

                                                                <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-emerald-500 border-4 border-white dark:border-zinc-900 rounded-full flex items-center justify-center">

                                                                    <svg class="w-3 h-3 text-white"
                                                                         fill="none"
                                                                         stroke="currentColor"
                                                                         viewBox="0 0 24 24">

                                                                        <path stroke-linecap="round"
                                                                              stroke-linejoin="round"
                                                                              stroke-width="4"
                                                                              d="M5 13l4 4L19 7">
                                                                        </path>

                                                                    </svg>

                                                                </div>

                                                            @endif

                                                        </div>


                                                        <div>

                                                            <div class="flex items-center gap-3">

                                                                <p class="text-[14px] font-black text-zinc-900 dark:text-white">

                                                                    {{ $comment->user?->name ?? 'کاربر مهمان' }}

                                                                </p>

                                                                @if($comment->is_verified)

                                                                    <span class="px-3 py-1 bg-emerald-500 text-white text-[10px] font-black rounded-lg">
                                                خریدار
                                            </span>

                                                                @endif

                                                            </div>

                                                            <p class="text-[12px] font-bold text-zinc-400 mt-1">

                                                                {{ verta($comment->created_at)->format('d F Y') }}

                                                            </p>

                                                        </div>

                                                    </div>


                                                    {{-- Rating --}}
                                                    <div class="flex gap-1 text-brown-600">

                                                        @for($i = 1; $i <= 5; $i++)

                                                            <svg
                                                                class="w-5 h-5 {{ $i <= $comment->rating ? 'text-brown-600' : 'text-zinc-200 dark:text-zinc-800' }}"
                                                                fill="currentColor"
                                                                viewBox="0 0 20 20">

                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 0 0 .95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 0 0-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 0 0-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 0 0-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 0 0 .951-.69l1.07-3.292z"/>

                                                            </svg>

                                                        @endfor

                                                    </div>

                                                </div>


                                                {{-- Body --}}
                                                <p class="mt-8 text-[14px] font-medium text-zinc-600 dark:text-zinc-300 leading-8">
                                                    {{ $comment->body }}
                                                </p>


                                                {{-- Pros --}}
                                                @if(!empty($comment->pros))

                                                    <div class="mt-8 flex flex-wrap gap-4">

                                                        @foreach($comment->pros as $pro)

                                                            <div class="flex items-center gap-2 px-4 py-2 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">

                                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>

                                                                <span class="text-[12px] font-black text-emerald-600">
                                            {{ $pro }}
                                        </span>

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @endif


                                                {{-- Cons --}}
                                                @if(!empty($comment->cons))

                                                    <div class="mt-4 flex flex-wrap gap-4">

                                                        @foreach($comment->cons as $con)

                                                            <div class="flex items-center gap-2 px-4 py-2 bg-rose-500/10 border border-rose-500/20 rounded-xl">

                                                                <span class="w-1.5 h-1.5 bg-rose-500 rounded-full"></span>

                                                                <span class="text-[12px] font-black text-rose-600">
                                            {{ $con }}
                                        </span>

                                                            </div>

                                                        @endforeach

                                                    </div>

                                                @endif


                                                {{-- Footer --}}
                                                <div class="mt-8 pt-6 border-t border-zinc-100 dark:border-white/5 flex justify-between items-center">

                                                    <button
                                                        type="button"
                                                        class="flex items-center gap-2 text-zinc-400 hover:text-brown-600 transition-colors">

                                                        <svg class="size-5"
                                                             fill="currentColor"
                                                             viewBox="0 0 24 24">

                                                            <path d="M4 21h1V8H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2zM20 8h-7l1.122-3.368A2 2 0 0 0 12.225 2H12L7 7.438V21h11l3.912-8.596L22 12v-2a2 2 0 0 0-2-2z"/>

                                                        </svg>

                                                        <span class="text-[12px] font-bold">
                                    آیا این نظر مفید بود؟
                                </span>

                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="text-[12px] font-black text-zinc-900 dark:text-white hover:text-brown-600">

                                                        پاسخ

                                                    </button>

                                                </div>

                                            </div>

                                        @empty

                                            <div class="p-12 text-center bg-white/40 dark:bg-zinc-900/40 rounded-[3rem] border border-zinc-200 dark:border-white/10">

                                                <p class="text-[14px] font-bold text-zinc-400">
                                                    هنوز نظری برای این محصول ثبت نشده است
                                                </p>

                                            </div>

                                        @endforelse

                                    </div>

                                </div>

                            </div>

                        </div>

                        <!--Questions and Answers-->
                        <div
                            id="tab-faq"
                            class="tab-content {{ $activeTab === 'faq' ? 'block' : 'hidden' }} animate-fadeIn pb-24 w-full"
                        >
                            <livewire:main.products.questions :product-id="$product->id" :key="'product-questions-'.$product->id" />
                        </div>

                    </div>
                </div>
            </div>
        </section>
        <!-- END SECTION DETAIL -->

        <!-- SHOP FEATURE -->
        <section class="relative overflow-hidden transition-colors duration-500">
            <div class=" pt-5">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">

                    <div class="flex flex-col items-center text-center group">
                        <div class="relative w-20 h-20 mb-6 flex items-center justify-center">
                            <div class="absolute inset-0 bg-brown-500/10 dark:bg-brown-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative z-10 w-full h-full bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm transition-all duration-500 group-hover:-translate-y-2 group-hover:border-brown-500/50 group-hover:shadow-lg group-hover:shadow-brown-500/10 flex items-center justify-center">
                                <svg class="w-9 h-9 text-gray-700 dark:text-gray-300 group-hover:text-brown-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white mb-2 transition-colors group-hover:text-brown-600 dark:group-hover:text-brown-400">انتخاب زمان ارسال</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-6 max-w-[150px]">بازه‌ی ارسال را هنگام تسویه‌حساب انتخاب کنید</p>
                    </div>

                    <div class="flex flex-col items-center text-center group">
                        <div class="relative w-20 h-20 mb-6 flex items-center justify-center">
                            <div class="absolute inset-0 bg-secondary-500/10 dark:bg-secondary-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative z-10 w-full h-full bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm transition-all duration-500 group-hover:-translate-y-2 group-hover:border-secondary-500/50 group-hover:shadow-lg group-hover:shadow-secondary-500/10 flex items-center justify-center">
                                <svg class="w-9 h-9 text-gray-700 dark:text-gray-300 group-hover:text-secondary-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white mb-2 transition-colors group-hover:text-secondary-600 dark:group-hover:text-secondary-500">امکان ثبت مرجوعی</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-6 max-w-[150px]">طبق شرایط مرجوعی فروشگاه، از حساب کاربری</p>
                    </div>

                    <div class="flex flex-col items-center text-center group">
                        <div class="relative w-20 h-20 mb-6 flex items-center justify-center">
                            <div class="absolute inset-0 bg-emerald-500/10 dark:bg-emerald-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative z-10 w-full h-full bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm transition-all duration-500 group-hover:-translate-y-2 group-hover:border-emerald-500/50 group-hover:shadow-lg group-hover:shadow-emerald-500/10 flex items-center justify-center">
                                <svg class="w-9 h-9 text-gray-700 dark:text-gray-300 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white mb-2 transition-colors group-hover:text-emerald-600 dark:group-hover:text-emerald-500">پرداخت شفاف</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-6 max-w-[150px]">کیف پول یا کارت‌به‌کارت با ثبت رسید</p>
                    </div>

                    <div class="flex flex-col items-center text-center group">
                        <div class="relative w-20 h-20 mb-6 flex items-center justify-center">
                            <div class="absolute inset-0 bg-brown-500/10 dark:bg-brown-500/20 blur-md rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative z-10 w-full h-full bg-white dark:bg-gray-900 rounded-[2rem] border border-gray-100 dark:border-gray-800 shadow-sm transition-all duration-500 group-hover:-translate-y-2 group-hover:border-brown-500/50 group-hover:shadow-lg group-hover:shadow-brown-500/10 flex items-center justify-center">
                                <svg class="w-9 h-9 text-gray-700 dark:text-gray-300 group-hover:text-brown-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white mb-2 transition-colors group-hover:text-brown-600 dark:group-hover:text-brown-400">فاکتور هر سفارش</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 leading-6 max-w-[150px]">فاکتور و جزئیات سفارش در حساب کاربری</p>
                    </div>

                </div>
            </div>
        </section>
        <!-- END SHOP FEATURE -->

        {{-- ===================== نمودار قیمت ===================== --}}
        @php
            $pricePoints = $this->priceHistory();
            $chartVariantLabel = $selectedVariant?->values?->pluck('title')->implode(' / ');
        @endphp
        <div wire:ignore.self
             x-data="{
                open: false,
                range: 90,
                chart: null,
                version: 0,
                // data-points واکنش‌پذیر نیست؛ version در هر باز شدن (پس از تغییر تنوع) محاسبه را تازه می‌کند
                points() { this.version; try { return JSON.parse(this.$refs.data.dataset.points || '[]') } catch (e) { return [] } },
                visible() {
                    const all = this.points();
                    if (this.range >= 365) return all;
                    const from = new Date(); from.setDate(from.getDate() - this.range);
                    const iso = from.toISOString().slice(0, 10);
                    const list = all.filter(p => p.date >= iso);
                    const before = all.filter(p => p.date < iso).pop();
                    return before ? [before, ...list] : list;
                },
                stats() {
                    const list = this.visible();
                    if (!list.length) return null;
                    const finals = list.map(p => p.final);
                    const first = finals[0], last = finals[finals.length - 1];
                    return { current: last, min: Math.min(...finals), max: Math.max(...finals), change: first ? Math.round((last - first) / first * 1000) / 10 : 0 };
                },
                fmt(n) { return Number(n || 0).toLocaleString('fa-IR') },
                show() { this.version++; this.open = true; document.body.classList.add('overflow-hidden'); this.$nextTick(() => this.render()) },
                hide() { this.open = false; document.body.classList.remove('overflow-hidden') },
                render() {
                    if (!window.Chart || !this.$refs.canvas) return;
                    const list = this.visible();
                    const dark = document.documentElement.classList.contains('dark');
                    const ctx = this.$refs.canvas.getContext('2d');
                    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(139,106,79,.28)');
                    gradient.addColorStop(1, 'rgba(139,106,79,0)');
                    const hasBase = list.some(p => p.price !== p.final);
                    const font = { family: 'payda, sans-serif', size: 11, weight: '600' };
                    if (this.chart) this.chart.destroy();
                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: list.map(p => p.label),
                            datasets: [
                                { label: 'قیمت نهایی', data: list.map(p => p.final), stepped: true, borderColor: '#8b6a4f', backgroundColor: gradient, fill: true, borderWidth: 3, pointRadius: list.length < 2 ? 5 : 0, pointHoverRadius: 6, pointBackgroundColor: '#8b6a4f' },
                                ...(hasBase ? [{ label: 'قیمت بدون تخفیف', data: list.map(p => p.price), stepped: true, borderColor: dark ? 'rgba(255,255,255,.35)' : 'rgba(100,116,139,.55)', borderDash: [6, 6], borderWidth: 2, pointRadius: 0, fill: false }] : []),
                            ],
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            interaction: { intersect: false, mode: 'index' },
                            plugins: {
                                legend: { display: hasBase, labels: { font, color: dark ? '#d4d4d8' : '#475569', boxWidth: 14 } },
                                tooltip: { rtl: true, backgroundColor: '#18181b', padding: 12, cornerRadius: 12, titleFont: font, bodyFont: font,
                                    callbacks: { label: c => ' ' + c.dataset.label + ': ' + Number(c.raw).toLocaleString('fa-IR') + ' تومان' } },
                            },
                            scales: {
                                y: { position: 'right', grid: { color: dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)' }, ticks: { font, color: '#94a3b8', callback: v => Number(v).toLocaleString('fa-IR') } },
                                x: { grid: { display: false }, ticks: { font, color: '#94a3b8', maxTicksLimit: 6, maxRotation: 0 } },
                            },
                        },
                    });
                },
             }"
             @open-price-chart.window="show()"
             @keydown.escape.window="open && hide()"
             x-show="open" x-cloak
             class="fixed inset-0 z-[1000] flex items-end md:items-center justify-center p-0 md:p-6"
             dir="rtl" role="dialog" aria-modal="true" aria-label="نمودار قیمت">

            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="hide()"></div>

            <div class="relative w-full md:max-w-2xl bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md border border-white/60 dark:border-white/10 rounded-t-[2.5rem] md:rounded-[2.5rem] shadow-lg p-6 md:p-8">

                {{-- داده نمودار (با هر تغییر تنوع به‌روز می‌شود) --}}
                <span x-ref="data" class="hidden" data-points="{{ json_encode($pricePoints) }}"></span>

                <div class="flex items-start justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-9 bg-brown-600 rounded-full"></div>
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-white">نمودار قیمت</h3>
                            <p class="text-[11px] font-bold text-gray-400 mt-0.5 line-clamp-1">{{ $product->title }}@if($chartVariantLabel) — {{ $chartVariantLabel }}@endif</p>
                        </div>
                    </div>
                    <button type="button" @click="hide()" aria-label="بستن"
                            class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center bg-gray-100 dark:bg-white/5 text-gray-500 hover:bg-red-500 hover:text-white transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex gap-2 mb-5">
                    @foreach([30 => '۳۰ روز', 90 => '۳ ماه', 365 => '۱ سال'] as $days => $label)
                        <button type="button" @click="range = {{ $days }}; render()"
                                :class="range === {{ $days }} ? 'bg-brown-600 text-white shadow-lg shadow-brown-500/20' : 'bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400 hover:text-brown-600'"
                                class="px-4 py-2 rounded-xl text-[11px] font-black transition-all">{{ $label }}</button>
                    @endforeach
                </div>

                <template x-if="stats()">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                        <div class="p-3 rounded-2xl bg-brown-600/10">
                            <div class="text-[10px] font-bold text-gray-500 dark:text-gray-400">قیمت فعلی</div>
                            <div class="text-sm font-black text-brown-600 dark:text-brown-400 tabular-nums mt-1"><span x-text="fmt(stats().current)"></span> <span class="text-[9px]">تومان</span></div>
                        </div>
                        <div class="p-3 rounded-2xl bg-emerald-500/10">
                            <div class="text-[10px] font-bold text-gray-500 dark:text-gray-400">کمترین</div>
                            <div class="text-sm font-black text-emerald-600 dark:text-emerald-400 tabular-nums mt-1" x-text="fmt(stats().min)"></div>
                        </div>
                        <div class="p-3 rounded-2xl bg-red-500/10">
                            <div class="text-[10px] font-bold text-gray-500 dark:text-gray-400">بیشترین</div>
                            <div class="text-sm font-black text-red-500 tabular-nums mt-1" x-text="fmt(stats().max)"></div>
                        </div>
                        <div class="p-3 rounded-2xl bg-gray-100 dark:bg-white/5">
                            <div class="text-[10px] font-bold text-gray-500 dark:text-gray-400">تغییر در این بازه</div>
                            <div class="text-sm font-black tabular-nums mt-1" dir="ltr"
                                 :class="stats().change > 0 ? 'text-red-500' : (stats().change < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500')"
                                 x-text="(stats().change > 0 ? '+' : '') + stats().change.toLocaleString('fa-IR') + '٪'"></div>
                        </div>
                    </div>
                </template>

                <div class="relative h-64 md:h-72">
                    <canvas x-ref="canvas"></canvas>
                </div>

                <p x-show="points().length < 2" class="mt-4 text-[11px] font-bold text-gray-400 leading-6 text-center">
                    تاریخچه قیمت این کالا از امروز ثبت می‌شود؛ با تغییر قیمت یا شروع و پایان تخفیف‌ها، نمودار کامل‌تر می‌شود.
                </p>
                <p x-show="points().length >= 2" class="mt-4 text-[10px] font-bold text-gray-400 text-center">
                    قیمت‌ها به تومان و برای تنوع انتخاب‌شده است؛ قیمت نهایی شامل تخفیف‌ها و کمپین‌های همان روز است.
                </p>
            </div>
        </div>
    </main>
</div>

@push('styles')
    {{-- ابزارهای گالری (علاقه‌مندی، مقایسه، نمودار قیمت، اشتراک) در موبایل و صفحه‌های لمسی همیشه نمایش داده شوند؛
         مستقل از build فایل CSS (در نسخه build قدیمی این قاعده وجود نداشت) --}}
    <style>
        @media (max-width: 1023px), (hover: none) {
            .sl-gallery-tools { opacity: 1 !important; transform: none !important; translate: none !important; }
        }
        .sl-compare-active { background-color: #14b8a6; color: #fff; }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('main/js/plugin/chart-js/chart.js') }}"></script>
@endpush
