<?php

use App\Models\Order;
use App\OrderStatus;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail tempahan')] class extends Component {
    public Order $order;
    public string $status = '';

    /**
     * Load the requested order.
     */
    public function mount(Order $order): void
    {
        $this->order = $order;
        $this->status = $order->status->value;
    }

    /**
     * Save the selected order status.
     */
    public function updateStatus(): void
    {
        $this->order->refresh();

        if (! $this->order->hasConfirmedPayment()) {
            $this->addError('status', 'Bayaran perlu disahkan sebelum tempahan diproses.');

            return;
        }

        $validated = $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $this->order->update(['status' => OrderStatus::from($validated['status'])]);
        $this->order->refresh();

        Flux::toast(variant: 'success', text: 'Status tempahan dikemas kini.');
    }
};
?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.orders') }}" wire:navigate class="text-sm text-zinc-600 hover:text-zinc-950 dark:text-zinc-300 dark:hover:text-white">← Kembali ke tempahan</a>
            <flux:heading size="xl" class="mt-3">Detail tempahan</flux:heading>
            <flux:text class="mt-2">{{ $order->reference }}</flux:text>
        </div>
        <flux:text>{{ $order->created_at->format('d/m/Y H:i') }}</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="grid gap-6">
            <flux:card>
                <flux:heading size="lg">Produk</flux:heading>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Nama produk</dt>
                        <dd class="mt-1 font-medium">{{ $order->product_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Kuantiti</dt>
                        <dd class="mt-1 font-medium">{{ $order->quantity }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Harga seunit</dt>
                        <dd class="mt-1 font-medium">RM {{ number_format((float) $order->unit_price, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Subtotal</dt>
                        <dd class="mt-1 font-medium">RM {{ number_format((float) $order->unit_price * $order->quantity, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Diskaun{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</dt>
                        <dd class="mt-1 font-medium">− RM {{ number_format((float) $order->discount_amount, 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Jumlah akhir</dt>
                        <dd class="mt-1 text-lg font-semibold">RM {{ number_format((float) $order->total_price, 2) }}</dd>
                    </div>
                </dl>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Pembayaran</flux:heading>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Status bayaran</dt>
                        <dd class="mt-1"><flux:badge size="sm">{{ $order->payment_status->label() }}</flux:badge></dd>
                    </div>
                    @if ($order->paid_at)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Dibayar pada</dt>
                            <dd class="mt-1 font-medium">{{ $order->paid_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    @endif
                    @if ($order->stripe_checkout_session_id)
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Stripe Checkout Session</dt>
                            <dd class="mt-1 break-all font-mono text-sm">{{ $order->stripe_checkout_session_id }}</dd>
                        </div>
                    @endif
                    @if ($order->stripe_payment_intent_id)
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Stripe PaymentIntent</dt>
                            <dd class="mt-1 break-all font-mono text-sm">{{ $order->stripe_payment_intent_id }}</dd>
                        </div>
                    @endif
                </dl>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">Maklumat pelanggan</flux:heading>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Nama</dt>
                        <dd class="mt-1 font-medium">{{ $order->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Telefon</dt>
                        <dd class="mt-1 font-medium">{{ $order->customer_phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">E-mel</dt>
                        <dd class="mt-1 font-medium">{{ $order->customer_email }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Nota</dt>
                        <dd class="mt-1 whitespace-pre-line">{{ $order->customer_note ?: 'Tiada nota.' }}</dd>
                    </div>
                </dl>
            </flux:card>
        </div>

        <flux:card class="h-fit">
            <form wire:submit="updateStatus" class="grid gap-5">
                <div>
                    <flux:heading size="lg">Status tempahan</flux:heading>
                    <flux:text class="mt-1">Kemas kini selepas menyemak atau menyerahkan produk digital.</flux:text>
                </div>

                <flux:select wire:model="status" :label="__('Status')" :disabled="! $order->hasConfirmedPayment()">
                    @foreach (OrderStatus::cases() as $orderStatus)
                        <option value="{{ $orderStatus->value }}">{{ $orderStatus->label() }}</option>
                    @endforeach
                </flux:select>

                <flux:error name="status" />
                <flux:button type="submit" variant="primary" :disabled="! $order->hasConfirmedPayment()">Simpan status</flux:button>
            </form>
        </flux:card>
    </div>
</div>
