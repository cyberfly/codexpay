<x-layouts::store :title="__('Tempahan diterima')">
    <section class="mx-auto flex min-h-[64vh] max-w-3xl items-center py-12 sm:py-16">
        <div class="w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white text-center shadow-2xl shadow-indigo-100/80">
            <div class="bg-gradient-to-br from-indigo-50 via-white to-sky-50 px-6 py-10 sm:px-12 sm:py-12">
                <span class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-600 ring-8 ring-emerald-50">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.3" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                </span>
                <p class="mt-7 text-xs font-bold tracking-[0.18em] text-indigo-600 uppercase">Tempahan diterima</p>
                <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Terima kasih atas tempahan anda.</h1>
                <p class="mx-auto mt-4 max-w-xl leading-7 text-slate-600">Kami sedang menyemak tempahan ini dan akan menghubungi anda untuk proses penyerahan produk digital.</p>
            </div>

            <div class="px-6 pb-9 sm:px-12 sm:pb-12">
                <dl class="-mt-2 grid gap-px overflow-hidden rounded-2xl border border-slate-200 bg-slate-200 text-left sm:grid-cols-2">
                    <div class="bg-white p-5">
                        <dt class="text-sm font-medium text-slate-500">Nombor rujukan</dt>
                        <dd class="mt-1.5 break-all font-bold text-slate-950">{{ $order->reference }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-sm font-medium text-slate-500">Produk</dt>
                        <dd class="mt-1.5 font-bold text-slate-950">{{ $order->product_name }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-sm font-medium text-slate-500">Subtotal</dt>
                        <dd class="mt-1.5 font-bold text-slate-950">RM {{ number_format((float) $order->unit_price * $order->quantity, 2) }}</dd>
                    </div>
                    <div class="bg-white p-5">
                        <dt class="text-sm font-medium text-slate-500">Diskaun{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt>
                        <dd class="mt-1.5 font-bold text-slate-950">− RM {{ number_format((float) $order->discount_amount, 2) }}</dd>
                    </div>
                    <div class="bg-white p-5 sm:col-span-2">
                        <dt class="text-sm font-medium text-slate-500">Jumlah akhir</dt>
                        <dd class="mt-1.5 text-lg font-bold text-slate-950">RM {{ number_format((float) $order->total_price, 2) }}</dd>
                    </div>
                </dl>

                <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:-translate-y-0.5 hover:bg-indigo-700">Kembali ke kedai <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::store>
