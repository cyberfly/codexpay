@props([
    'product',
])

<article class="group flex h-full overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-indigo-100/70">
    <div class="flex w-full flex-col">
        <a href="{{ route('products.show', $product) }}" class="relative block aspect-[16/10] overflow-hidden bg-gradient-to-br from-indigo-100 via-sky-50 to-violet-100">
            @if ($product->image_path)
                <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="size-full object-cover transition duration-500 group-hover:scale-105" loading="lazy">
            @else
                <div class="absolute inset-0 grid place-items-center">
                    <div class="grid size-16 place-items-center rounded-2xl bg-white/80 text-indigo-600 shadow-lg shadow-indigo-200/60">
                        <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true"><path d="m4 19 5.5-5.5a2 2 0 0 1 2.8 0L16 17"/><path d="m14 15 1.5-1.5a2 2 0 0 1 2.8 0L20 15.2"/><path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><circle cx="9" cy="9" r="1.5"/></svg>
                    </div>
                </div>
            @endif
            <span class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur">Produk digital</span>
        </a>

        <div class="flex flex-1 flex-col gap-5 p-5 sm:p-6">
            <div>
                <h3 class="text-xl font-bold tracking-tight text-slate-950">
                    <a href="{{ route('products.show', $product) }}" class="transition hover:text-indigo-700">{{ $product->name }}</a>
                </h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ Str::limit(\App\Support\ProductDescription::plainText($product->description), 132) }}</p>
            </div>
            <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <p class="text-lg font-bold tracking-tight text-slate-950">RM {{ number_format((float) $product->price, 2) }}</p>
                <a href="{{ route('products.show', $product) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-50 px-3.5 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-600 hover:text-white">Lihat produk <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</article>
