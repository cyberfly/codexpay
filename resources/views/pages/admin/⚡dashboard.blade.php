<?php

use App\Models\Order;
use App\OrderStatus;
use App\PaymentStatus;
use Carbon\CarbonInterface;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin dashboard')] class extends Component {
    /**
     * Get the total orders grouped by status.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function orderCounts(): array
    {
        return Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (int $total): int => $total)
            ->all();
    }

    /**
     * Get the most recently submitted orders.
     */
    #[Computed]
    public function recentOrders()
    {
        return Order::query()->latest()->limit(5)->get();
    }

    /**
     * Get daily sales for the last 30 days, excluding cancelled orders.
     *
     * @return array{labels: list<string>, totals: list<float>}
     */
    #[Computed]
    public function salesChart(): array
    {
        $startDate = today()->subDays(29);
        $salesByDate = Order::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('date(created_at) as date, sum(total_price) as total')
            ->groupBy('date')
            ->pluck('total', 'date');

        $days = collect(range(0, 29))->map(
            fn (int $offset): CarbonInterface => $startDate->addDays($offset),
        );

        return [
            'labels' => $days->map(fn (CarbonInterface $day): string => $day->format('d M'))->all(),
            'totals' => $days->map(fn (CarbonInterface $day): float => (float) ($salesByDate[$day->format('Y-m-d')] ?? 0))->all(),
        ];
    }
};
?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Kedai</flux:heading>
        <flux:text class="mt-2">Pantau tempahan produk digital anda.</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach (OrderStatus::cases() as $status)
            <flux:card wire:key="status-{{ $status->value }}" class="flex flex-col gap-2">
                <flux:text>{{ $status->label() }}</flux:text>
                <flux:heading size="xl">{{ $this->orderCounts[$status->value] ?? 0 }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    <flux:card>
        <div class="mb-6">
            <flux:heading size="lg">Jualan 30 hari terakhir</flux:heading>
            <flux:text class="mt-1">Jumlah bayaran yang telah disahkan, dalam RM.</flux:text>
        </div>

        <div
            x-data="{
                chart: null,
                init() {
                    const sales = JSON.parse(this.$refs.salesData.textContent)

                    this.chart = new window.Chart(this.$refs.salesChart, {
                        type: 'line',
                        data: {
                            labels: sales.labels,
                            datasets: [{
                                label: 'Jualan (RM)',
                                data: sales.totals,
                                borderColor: '#18181b',
                                backgroundColor: 'rgba(24, 24, 27, 0.12)',
                                fill: true,
                                tension: 0.35,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: (value) => 'RM ' + value,
                                    },
                                },
                            },
                        },
                    })
                },
                destroy() {
                    this.chart?.destroy()
                },
            }"
            class="h-80"
        >
            <script type="application/json" x-ref="salesData">{!! json_encode($this->salesChart) !!}</script>
            <canvas x-ref="salesChart" aria-label="Graf jualan 30 hari terakhir" role="img"></canvas>
        </div>
    </flux:card>

    <flux:card>
        <div class="mb-4 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Tempahan terkini</flux:heading>
                <flux:text class="mt-1">Lima tempahan yang paling baru diterima.</flux:text>
            </div>
            <flux:button :href="route('admin.orders')" wire:navigate variant="ghost">Lihat semua</flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Rujukan</flux:table.column>
                <flux:table.column>Produk</flux:table.column>
                <flux:table.column>Pelanggan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->recentOrders as $order)
                    <flux:table.row :key="$order->id">
                        <flux:table.cell variant="strong">{{ $order->reference }}</flux:table.cell>
                        <flux:table.cell>{{ $order->product_name }}</flux:table.cell>
                        <flux:table.cell>{{ $order->customer_name }}</flux:table.cell>
                        <flux:table.cell>{{ $order->status->label() }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">Belum ada tempahan.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
