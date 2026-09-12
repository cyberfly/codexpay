<x-layouts::store :title="$product->name">
    <div class="py-8 sm:py-12 lg:py-16">
        <a href="{{ route('products.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-700"><span aria-hidden="true">←</span> Kembali ke semua produk</a>

        <div class="mt-7 grid gap-10 lg:grid-cols-[minmax(0,1fr)_25rem] lg:items-start xl:grid-cols-[minmax(0,1fr)_28rem]">
            <section>
                <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-indigo-100 via-sky-50 to-violet-100 shadow-sm">
                    <div class="aspect-[16/10]">
                        @if ($product->image_path)
                            <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="{{ $product->name }}" class="size-full object-cover">
                        @else
                            <div class="grid size-full place-items-center text-indigo-600">
                                <span class="grid size-20 place-items-center rounded-3xl bg-white/80 shadow-xl shadow-indigo-200/70">
                                    <svg viewBox="0 0 24 24" class="size-9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" aria-hidden="true"><path d="m4 19 5.5-5.5a2 2 0 0 1 2.8 0L16 17"/><path d="m14 15 1.5-1.5a2 2 0 0 1 2.8 0L20 15.2"/><path d="M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><circle cx="9" cy="9" r="1.5"/></svg>
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-8 max-w-3xl">
                    <p class="text-xs font-bold tracking-[0.18em] text-indigo-600 uppercase">Produk digital</p>
                    <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl lg:text-5xl">{{ $product->name }}</h1>
                    <p class="mt-5 text-2xl font-bold tracking-tight text-indigo-700">RM {{ number_format((float) $product->price, 2) }}</p>
                    <div class="mt-8 border-t border-slate-200 pt-8 leading-7 text-slate-600 [&_blockquote]:border-l-4 [&_blockquote]:border-indigo-200 [&_blockquote]:pl-4 [&_blockquote]:italic [&_h2]:mt-8 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h2]:text-slate-950 [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-slate-900 [&_li]:ml-6 [&_ol]:list-decimal [&_p]:mt-4 [&_p:first-child]:mt-0 [&_pre]:overflow-x-auto [&_pre]:rounded-xl [&_pre]:bg-slate-100 [&_pre]:p-4 [&_ul]:list-disc">{!! \App\Support\ProductDescription::sanitize($product->description) !!}</div>
                </div>
            </section>

            <aside class="lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70">
                    <div class="border-b border-indigo-100 bg-indigo-50 px-6 py-5 sm:px-7">
                        <p class="text-xs font-bold tracking-[0.16em] text-indigo-600 uppercase">Tempahan terus</p>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-slate-950">Borang tempahan</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Lengkapkan borang di bawah. Kami akan semak tempahan dan hubungi anda untuk langkah seterusnya.</p>
                    </div>

                    <form id="order-form" method="POST" action="{{ route('products.orders.store', $product) }}" data-coupon-preview-url="{{ route('products.coupons.preview', $product) }}" data-unit-price="{{ $product->price }}" class="grid gap-5 p-6 sm:p-7">
                        @csrf

                        @if (session('payment_error'))
                            <p class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-sm text-amber-800">{{ session('payment_error') }}</p>
                        @endif

                        @error('payment')<p class="rounded-xl border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-700">{{ $message }}</p>@enderror

                        <div>
                            <label for="customer_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama</label>
                            <input id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('customer_name')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="customer_email" class="mb-2 block text-sm font-semibold text-slate-700">E-mel</label>
                            <input id="customer_email" name="customer_email" value="{{ old('customer_email') }}" type="email" required autocomplete="email" class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('customer_email')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-2 block text-sm font-semibold text-slate-700">Nombor telefon</label>
                            <input id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}" type="tel" required autocomplete="tel" class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('customer_phone')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="quantity" class="mb-2 block text-sm font-semibold text-slate-700">Kuantiti</label>
                            <input id="quantity" name="quantity" value="{{ old('quantity', 1) }}" type="number" min="1" max="100" required class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('quantity')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="coupon_code" class="mb-2 block text-sm font-semibold text-slate-700">Kod kupon <span class="font-normal text-slate-400">(pilihan)</span></label>
                            <div class="flex gap-2">
                                <input id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" autocomplete="off" class="block min-w-0 flex-1 rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 uppercase shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                                <button id="apply-coupon" type="button" class="shrink-0 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100 disabled:cursor-not-allowed disabled:opacity-60">Guna kupon</button>
                            </div>
                            @error('coupon_code')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                            <p id="coupon-feedback" class="mt-1.5 hidden text-sm" aria-live="polite"></p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <dl class="grid gap-2 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-600">Subtotal</dt>
                                    <dd id="order-subtotal" class="font-semibold text-slate-900">RM {{ number_format((float) $product->price * old('quantity', 1), 2) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-slate-600">Diskaun</dt>
                                    <dd id="order-discount" class="font-semibold text-slate-900">− RM 0.00</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                                    <dt class="font-bold text-slate-950">Jumlah akhir</dt>
                                    <dd id="order-total" class="text-lg font-bold text-indigo-700">RM {{ number_format((float) $product->price * old('quantity', 1), 2) }}</dd>
                                </div>
                            </dl>
                            <p class="mt-3 text-xs leading-5 text-slate-500">Harga akhir akan disemak semula semasa tempahan dihantar.</p>
                        </div>

                        <div>
                            <label for="customer_note" class="mb-2 block text-sm font-semibold text-slate-700">Nota <span class="font-normal text-slate-400">(pilihan)</span></label>
                            <textarea id="customer_note" name="customer_note" rows="4" class="block w-full rounded-xl border-slate-200 bg-white px-3.5 py-3 text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">{{ old('customer_note') }}</textarea>
                            @error('customer_note')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-indigo-300">Teruskan ke pembayaran selamat <span aria-hidden="true">→</span></button>
                        <p class="text-center text-xs leading-5 text-slate-500">Maklumat anda hanya digunakan untuk menguruskan tempahan dan pembayaran ini.</p>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</x-layouts::store>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('order-form');

        if (form === null) {
            return;
        }

        const quantityInput = document.getElementById('quantity');
        const couponInput = document.getElementById('coupon_code');
        const applyButton = document.getElementById('apply-coupon');
        const feedback = document.getElementById('coupon-feedback');
        const subtotal = document.getElementById('order-subtotal');
        const discount = document.getElementById('order-discount');
        const total = document.getElementById('order-total');
        const csrfToken = form.querySelector('input[name="_token"]')?.value;
        const unitPrice = Number(form.dataset.unitPrice);
        let appliedCoupon = null;

        const formatCurrency = (amount) => 'RM ' + Number(amount).toFixed(2);

        const currentQuantity = () => {
            const quantity = Number.parseInt(quantityInput.value, 10);

            return Number.isInteger(quantity) && quantity > 0 ? quantity : 1;
        };

        const renderTotals = () => {
            const baseSubtotal = unitPrice * currentQuantity();
            const quote = appliedCoupon ?? {
                subtotal: baseSubtotal,
                discount_amount: 0,
                total_price: baseSubtotal,
            };

            subtotal.textContent = formatCurrency(quote.subtotal);
            discount.textContent = '− ' + formatCurrency(quote.discount_amount);
            total.textContent = formatCurrency(quote.total_price);
        };

        const showFeedback = (message, isError) => {
            feedback.textContent = message;
            feedback.classList.remove('hidden', 'text-red-600', 'text-emerald-600');
            feedback.classList.add(isError ? 'text-red-600' : 'text-emerald-600');
        };

        const clearAppliedCoupon = (message = null) => {
            if (appliedCoupon === null) {
                return;
            }

            appliedCoupon = null;
            renderTotals();

            if (message !== null) {
                showFeedback(message, false);
            }
        };

        quantityInput.addEventListener('input', () => {
            clearAppliedCoupon('Kuantiti berubah. Gunakan kupon sekali lagi untuk mengemas kini jumlah.');
            renderTotals();
        });

        couponInput.addEventListener('input', () => {
            clearAppliedCoupon();
        });

        applyButton.addEventListener('click', async () => {
            const couponCode = couponInput.value.trim();

            if (couponCode === '') {
                showFeedback('Masukkan kod kupon terlebih dahulu.', true);

                return;
            }

            applyButton.disabled = true;
            applyButton.textContent = 'Menyemak…';

            try {
                const response = await fetch(form.dataset.couponPreviewUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        coupon_code: couponCode,
                        quantity: currentQuantity(),
                    }),
                });
                const payload = await response.json();

                if (!response.ok) {
                    throw new Error(payload.errors?.coupon_code?.[0] ?? 'Kupon tidak dapat digunakan.');
                }

                appliedCoupon = payload;
                couponInput.value = payload.coupon_code;
                renderTotals();
                showFeedback('Kupon ' + payload.coupon_code + ' digunakan.', false);
            } catch (error) {
                appliedCoupon = null;
                renderTotals();
                showFeedback(error.message, true);
            } finally {
                applyButton.disabled = false;
                applyButton.textContent = 'Guna kupon';
            }
        });
    });
</script>
