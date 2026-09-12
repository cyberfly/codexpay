<x-layouts::store :title="__('Produk digital')">
    <section class="grid items-center gap-14 py-14 sm:py-20 lg:grid-cols-[1.05fr_0.95fr] lg:py-24">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full border border-indigo-100 bg-white/80 px-3.5 py-2 text-xs font-bold tracking-[0.14em] text-indigo-700 shadow-sm">
                <span class="size-2 rounded-full bg-emerald-500"></span>
                PRODUK DIGITAL TERPILIH
            </div>
            <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-[-0.055em] text-slate-950 sm:text-5xl lg:text-6xl">Semua yang anda perlukan untuk <span class="text-indigo-600">bergerak lebih jauh.</span></h1>
            <p class="mt-6 max-w-xl text-base leading-8 text-slate-600 sm:text-lg">Temui produk digital yang praktikal untuk membantu anda belajar, membina dan mengembangkan idea dengan lebih yakin.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#produk" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-indigo-200 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-indigo-300">Terokai produk <span aria-hidden="true">→</span></a>
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-200 hover:text-indigo-700">Lihat semua koleksi</a>
            </div>
            <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm font-medium text-slate-600">
                <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span> Akses mudah</span>
                <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span> Proses tempahan ringkas</span>
                <span class="flex items-center gap-2"><span class="grid size-5 place-items-center rounded-full bg-emerald-100 text-emerald-700">✓</span> Sokongan peribadi</span>
            </div>
        </div>

        <div class="relative mx-auto w-full max-w-xl">
            <div class="absolute -inset-5 -z-10 rounded-[2.5rem] bg-gradient-to-br from-indigo-200/60 via-sky-100/70 to-violet-100 blur-2xl"></div>
            <div class="overflow-hidden rounded-[2rem] border border-white bg-white p-3 shadow-2xl shadow-indigo-200/60 sm:p-4">
                <div class="rounded-[1.45rem] bg-gradient-to-br from-indigo-50 via-white to-violet-50 p-6 sm:p-8">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-950">{{ config('app.name') }}</span>
                        <span class="rounded-full bg-white px-3 py-1.5 text-xs font-medium text-indigo-700 shadow-sm">Pilihan utama</span>
                    </div>
                    <div class="mt-11 rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <span class="grid size-12 place-items-center rounded-2xl bg-indigo-100 text-indigo-600">
                                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"><path d="M12 3v18M3 12h18"/><path d="M19 5 5 19"/></svg>
                            </span>
                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Sedia ditempah</span>
                        </div>
                        <div class="mt-8 h-3 w-4/5 rounded-full bg-slate-900"></div>
                        <div class="mt-3 h-2 w-full rounded-full bg-slate-100"></div>
                        <div class="mt-2 h-2 w-3/5 rounded-full bg-slate-100"></div>
                        <div class="mt-8 flex items-center justify-between border-t border-slate-100 pt-5">
                            <span class="text-sm font-medium text-slate-500">Beli dengan yakin</span>
                            <span class="text-sm font-bold text-indigo-600">Mudah &amp; selamat</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 sm:grid-cols-3">
        <div class="bg-white px-6 py-5"><p class="text-sm font-bold text-slate-950">Untuk pembelajar yang serius</p><p class="mt-1 text-sm text-slate-500">Sumber yang jelas dan praktikal.</p></div>
        <div class="bg-white px-6 py-5"><p class="text-sm font-bold text-slate-950">Tempahan tanpa rumit</p><p class="mt-1 text-sm text-slate-500">Pilih, isi borang, kami uruskan.</p></div>
        <div class="bg-white px-6 py-5"><p class="text-sm font-bold text-slate-950">Sokongan yang manusiawi</p><p class="mt-1 text-sm text-slate-500">Kami ada untuk bantu anda.</p></div>
    </section>

    <section id="produk" class="scroll-mt-24 py-20 sm:py-24">
        <div class="mb-10 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <p class="text-xs font-bold tracking-[0.18em] text-indigo-600 uppercase">Koleksi pilihan</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Cari langkah seterusnya untuk anda.</h2>
                <p class="mt-3 leading-7 text-slate-600">Pilih produk, lihat butiran penuh dan hantar tempahan dalam beberapa minit.</p>
            </div>
            <a href="{{ route('products.index') }}" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-bold text-indigo-700 transition hover:text-indigo-900">Lihat semua produk <span aria-hidden="true">→</span></a>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">Produk baharu akan tersedia tidak lama lagi.</div>
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="mt-8">{{ $products->links() }}</div>
        @endif
    </section>
</x-layouts::store>
