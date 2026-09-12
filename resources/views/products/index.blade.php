<x-layouts::store :title="__('Produk')">
    <section class="py-12 sm:py-16 lg:py-20">
        <div class="relative overflow-hidden rounded-[2rem] border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-sky-50 px-6 py-12 sm:px-10 sm:py-14">
            <div class="absolute -right-16 -top-20 size-64 rounded-full bg-indigo-100/70 blur-3xl"></div>
            <div class="relative max-w-2xl">
                <p class="text-xs font-bold tracking-[0.18em] text-indigo-600 uppercase">Kedai digital</p>
                <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-950 sm:text-5xl">Pilihan yang bantu anda buat lebih banyak.</h1>
                <p class="mt-5 max-w-xl text-lg leading-8 text-slate-600">Terokai koleksi sumber digital kami. Setiap produk disediakan untuk memberi anda langkah yang lebih jelas ke arah matlamat anda.</p>
            </div>
        </div>

        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($products as $product)
                <x-product-card :product="$product" />
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/70 p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">Produk akan tersedia tidak lama lagi.</div>
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="mt-10">{{ $products->links() }}</div>
        @endif
    </section>
</x-layouts::store>
