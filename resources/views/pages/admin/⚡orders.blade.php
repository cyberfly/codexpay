<?php

use App\Models\Order;
use App\OrderStatus;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tempahan')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = 'all';

    /**
     * Get the orders matching the current admin filters.
     */
    #[Computed]
    public function orders()
    {
        return Order::query()
            ->when($this->status !== 'all', function (Builder $query): void {
                $query->where('status', $this->status);
            })
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('reference', 'like', '%'.$this->search.'%')
                        ->orWhere('product_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_email', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    /**
     * Reset pagination when the search changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination when the status filter changes.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Update an order status from the table.
     */
    public function updateStatus(Order $order, string $status): void
    {
        validator(
            ['status' => $status],
            ['status' => ['required', Rule::enum(OrderStatus::class)]],
        )->validate();

        $order->update(['status' => OrderStatus::from($status)]);

        Flux::toast(variant: 'success', text: 'Status tempahan dikemas kini.');
    }
};
?>

<div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">Tempahan</flux:heading>
            <flux:text class="mt-2">Semak pesanan dan serahkan produk digital secara manual.</flux:text>
        </div>

        <flux:card>
            <div class="mb-5 grid gap-4 md:grid-cols-[1fr_auto]">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Cari')" placeholder="Rujukan, produk atau pelanggan" />
                <flux:select wire:model.live="status" :label="__('Status')" class="min-w-48">
                    <option value="all">Semua status</option>
                    @foreach (OrderStatus::cases() as $orderStatus)
                        <option value="{{ $orderStatus->value }}">{{ $orderStatus->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            <flux:table :paginate="$this->orders">
                <flux:table.columns>
                    <flux:table.column>Tempahan</flux:table.column>
                    <flux:table.column>Produk</flux:table.column>
                    <flux:table.column>Pelanggan</flux:table.column>
                    <flux:table.column>Jumlah</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Tindakan</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->orders as $order)
                        <flux:table.row :key="$order->id">
                            <flux:table.cell variant="strong">
                                <a href="{{ route('admin.orders.show', $order) }}" wire:navigate class="hover:underline">{{ $order->reference }}</a>
                                <div class="mt-1 text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->product_name }} × {{ $order->quantity }}</flux:table.cell>
                            <flux:table.cell>
                                <div>{{ $order->customer_name }}</div>
                                <div class="mt-1 text-xs">{{ $order->customer_email }}</div>
                                <div class="mt-1 text-xs">{{ $order->customer_phone }}</div>
                                @if ($order->customer_note)
                                    <div class="mt-2 max-w-xs whitespace-normal text-xs">{{ $order->customer_note }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <div>RM {{ number_format((float) $order->total_price, 2) }}</div>
                                @if ($order->coupon_code)
                                    <div class="mt-1 text-xs">{{ $order->coupon_code }} · − RM {{ number_format((float) $order->discount_amount, 2) }}</div>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <select
                                    wire:change="updateStatus('{{ $order->reference }}', $event.target.value)"
                                    class="rounded-md border-zinc-300 bg-white text-sm text-zinc-900 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                >
                                    @foreach (OrderStatus::cases() as $orderStatus)
                                        <option value="{{ $orderStatus->value }}" @selected($order->status === $orderStatus)>{{ $orderStatus->label() }}</option>
                                    @endforeach
                                </select>
                            </flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button :href="route('admin.orders.show', $order)" wire:navigate variant="ghost" size="sm">Lihat detail</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">Tiada tempahan sepadan.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
</div>
