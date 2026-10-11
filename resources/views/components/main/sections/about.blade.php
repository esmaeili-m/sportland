<?php

use Livewire\Component;

new class extends Component
{
    public $data,$photo;
    public function mount($data,$media)
    {
        $this->data=$data;
        $this->photo=$media->first();
    }
};
?>

@php
    $siteName = config('brand.name');
    // آمار فقط وقتی نمایش داده می‌شود که مدیر مقدار واقعی آن را در پنل وارد کرده باشد (عدد ساختگی نمایش داده نمی‌شود)
    $stats = array_filter([
        ['value' => $data['client'] ?? null, 'label' => 'هزار مشتری فعال'],
        ['value' => $data['service'] ?? null, 'label' => 'رضایت از خدمات'],
        ['value' => $data['shop'] ?? null, 'label' => 'نمایندگی'],
        ['value' => $data['support'] ?? null, 'label' => 'پشتیبانی آنلاین'],
    ], fn ($s) => filled($s['value']));
@endphp

<div>
    <section class="container w-full my-20" dir="rtl">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center mb-20 px-4 md:px-6">
            <div class="space-y-6">
                <h1 class="text-3xl md:text-4xl font-black text-gray-900 dark:text-white text-balance">{{ $data['title'] ?? ('درباره ' . $siteName) }}</h1>
                <div class="text-base leading-9 text-gray-600 dark:text-gray-300 font-medium max-w-[65ch]">
                    {!! \App\Support\SafeHtml::clean($data['description'] ?? '') !!}
                </div>
                {{-- فقط قابلیت‌هایی که سایت واقعاً دارد --}}
                <ul class="flex flex-wrap gap-2.5">
                    @foreach(['سفارش آنلاین', 'انتخاب زمان ارسال', 'پرداخت با کیف پول یا کارت‌به‌کارت', 'پیگیری سفارش', 'پشتیبانی با تیکت'] as $feature)
                        <li class="px-4 py-2 bg-primary-50 dark:bg-primary-500/10 border border-primary-100 dark:border-primary-500/20 rounded-xl text-primary-700 dark:text-primary-300 text-sm font-bold">
                            {{ $feature }}
                        </li>
                    @endforeach
                </ul>
            </div>

            @if($this->photo)
                <div class="relative">
                    <img src="{{ asset('storage/'.$this->photo->file_path) }}"
                         class="rounded-[2.5rem] w-full h-[320px] md:h-[450px] object-cover"
                         alt="{{ $siteName }}" loading="lazy">
                </div>
            @endif
        </div>

        @if(count($stats))
            <div class="grid grid-cols-2 {{ [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'][count($stats)] }} gap-4 md:gap-6 mb-20 px-4">
                @foreach($stats as $stat)
                    <div class="bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-white/10 rounded-3xl p-8 text-center">
                        <p class="text-3xl font-black text-primary-600 dark:text-primary-400 mb-2 tabular-nums">{{ $stat['value'] }}</p>
                        <p class="text-sm font-bold text-gray-600 dark:text-gray-400">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="bg-primary-700 dark:bg-primary-900 rounded-[2.5rem] p-8 md:p-12 lg:p-16">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-12">
                <div class="space-y-4">
                    <div class="w-14 h-14 bg-white/15 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-white">ثبت سفارش آنلاین</h3>
                    <p class="text-sm leading-7 text-primary-50/90">محصول را به سبد اضافه کنید، نشانی و بازه زمانی ارسال را انتخاب کنید و سفارش را در چند قدم ثبت کنید.</p>
                </div>

                <div class="space-y-4">
                    <div class="w-14 h-14 bg-white/15 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-2 2-2-2-2 2-2-2-3 2V5a2 2 0 012-2z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-white">پرداخت و فاکتور شفاف</h3>
                    <p class="text-sm leading-7 text-primary-50/90">پرداخت از کیف پول یا کارت‌به‌کارت با ثبت رسید. فاکتور هر سفارش در حساب کاربری شما در دسترس است.</p>
                </div>

                <div class="space-y-4">
                    <div class="w-14 h-14 bg-white/15 rounded-2xl flex items-center justify-center text-white">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <h3 class="text-xl font-black text-white">پیگیری و پشتیبانی</h3>
                    <p class="text-sm leading-7 text-primary-50/90">وضعیت سفارش را از حساب کاربری دنبال کنید و برای هر پرسش، از بخش تیکت‌ها با پشتیبانی در ارتباط باشید.</p>
                </div>
            </div>
        </div>

    </section>
</div>
