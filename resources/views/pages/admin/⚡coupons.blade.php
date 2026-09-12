<?php

use App\DiscountType;
use App\Models\Coupon;
use App\Models\Product;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kupon')] class extends Component {
    public ?int $editingCouponId = null;
    public string $search = '';
    public string $code = '';
    public string $discountType = 'percentage';
    public string $discountValue = '';
    public bool $isActive = true;
    public bool $appliesToAllProducts = true;
    /** @var list<int> */
    public array $productIds = [];
    public string $expiresAt = '';
    public bool $showForm = false;

    /**
     * Get the validation rules for a coupon form.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($this->editingCouponId),
            ],
            'discountType' => ['required', Rule::enum(DiscountType::class)],
            'discountValue' => [
                'required',
                'decimal:0,2',
                'gt:0',
                Rule::when(
                    $this->discountType === DiscountType::Percentage->value,
                    ['max:100'],
                    ['max:99999999.99'],
                ),
            ],
            'isActive' => ['boolean'],
            'appliesToAllProducts' => ['boolean'],
            'productIds' => Rule::when(
                ! $this->appliesToAllProducts,
                ['required', 'array', 'min:1'],
                ['array'],
            ),
            'productIds.*' => ['integer', Rule::exists('products', 'id')],
            'expiresAt' => ['nullable', 'date_format:Y-m-d', Rule::date()->todayOrAfter()],
        ];
    }

    /**
     * Get coupons matching the current search.
     */
    #[Computed]
    public function coupons()
    {
        return Coupon::query()
            ->with('products')
            ->withCount('redemptions')
            ->when($this->search !== '', function (Builder $query): void {
                $query->where('code', 'like', '%'.Str::upper($this->search).'%');
            })
            ->latest()
            ->get();
    }

    /**
     * Get products available for a product-scoped coupon.
     */
    #[Computed]
    public function products()
    {
        return Product::query()->orderBy('name')->get();
    }

    /**
     * Open an empty coupon form.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    /**
     * Open a coupon form pre-filled with its current values.
     */
    public function edit(int $couponId): void
    {
        $coupon = Coupon::query()->with('products')->findOrFail($couponId);

        $this->editingCouponId = $coupon->id;
        $this->code = $coupon->code;
        $this->discountType = $coupon->discount_type->value;
        $this->discountValue = $coupon->discount_value;
        $this->isActive = $coupon->is_active;
        $this->appliesToAllProducts = $coupon->applies_to_all_products;
        $this->productIds = $coupon->products->pluck('id')->all();
        $this->expiresAt = $coupon->expires_at?->toDateString() ?? '';
        $this->showForm = true;
    }

    /**
     * Normalize a coupon code as the admin types.
     */
    public function updatedCode(string $code): void
    {
        $this->code = Str::upper($code);
    }

    /**
     * Store a new coupon or update an existing one.
     */
    public function save(): void
    {
        $this->code = Str::upper(trim($this->code));
        $validated = $this->validate();

        $attributes = [
            'code' => $validated['code'],
            'discount_type' => $validated['discountType'],
            'discount_value' => $validated['discountValue'],
            'is_active' => $validated['isActive'],
            'applies_to_all_products' => $validated['appliesToAllProducts'],
            'expires_at' => $validated['expiresAt'] === ''
                ? null
                : Carbon::createFromFormat('Y-m-d', $validated['expiresAt'], config('app.timezone'))->endOfDay(),
        ];

        $coupon = $this->editingCouponId === null
            ? Coupon::create($attributes)
            : Coupon::query()->findOrFail($this->editingCouponId);

        $coupon->update($attributes);
        $coupon->products()->sync($this->appliesToAllProducts ? [] : $validated['productIds']);

        $this->resetForm();
        Flux::toast(variant: 'success', text: 'Kupon disimpan.');
    }

    /**
     * Toggle whether a coupon can be redeemed.
     */
    public function toggleActive(int $couponId): void
    {
        $coupon = Coupon::query()->findOrFail($couponId);
        $coupon->update(['is_active' => ! $coupon->is_active]);

        Flux::toast(variant: 'success', text: $coupon->is_active ? 'Kupon diaktifkan.' : 'Kupon dinyahaktifkan.');
    }

    /**
     * Delete an unused coupon, or deactivate one with redemption history.
     */
    public function deleteCoupon(int $couponId): void
    {
        $coupon = Coupon::query()->findOrFail($couponId);

        if ($coupon->redemptions()->exists()) {
            $coupon->update(['is_active' => false]);
            Flux::toast(variant: 'success', text: 'Kupon yang telah ditebus dinyahaktifkan.');

            return;
        }

        $coupon->delete();
        Flux::toast(variant: 'success', text: 'Kupon dipadam.');
    }

    /**
     * Reset the coupon form to its initial state.
     */
    public function resetForm(): void
    {
        $this->reset(
            'editingCouponId',
            'code',
            'discountType',
            'discountValue',
            'isActive',
            'appliesToAllProducts',
            'productIds',
            'expiresAt',
            'showForm',
        );
        $this->resetValidation();
    }
};
?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Kupon</flux:heading>
            <flux:text class="mt-2">Urus diskaun untuk produk digital anda.</flux:text>
        </div>
        <flux:button wire:click="create" variant="primary" icon="plus">Tambah kupon</flux:button>
    </div>

    <flux:card>
        <div class="mb-5">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Cari kupon')" placeholder="Kod kupon" />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>Kod</flux:table.column>
                <flux:table.column>Diskaun</flux:table.column>
                <flux:table.column>Skop</flux:table.column>
                <flux:table.column>Luput</flux:table.column>
                <flux:table.column>Penebusan</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column align="end">Tindakan</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($this->coupons as $coupon)
                    <flux:table.row :key="$coupon->id">
                        <flux:table.cell variant="strong">{{ $coupon->code }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $coupon->discount_type === DiscountType::Percentage ? $coupon->discount_value.'%' : 'RM '.$coupon->discount_value }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if ($coupon->applies_to_all_products)
                                Semua produk
                            @else
                                {{ $coupon->products->pluck('name')->join(', ') }}
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $coupon->expires_at?->format('d/m/Y H:i') ?? 'Tiada' }}</flux:table.cell>
                        <flux:table.cell>{{ $coupon->redemptions_count }}</flux:table.cell>
                        <flux:table.cell>{{ $coupon->is_active ? 'Aktif' : 'Tidak aktif' }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="toggleActive({{ $coupon->id }})" variant="ghost" size="sm">
                                    {{ $coupon->is_active ? 'Nyahaktif' : 'Aktifkan' }}
                                </flux:button>
                                <flux:button wire:click="edit({{ $coupon->id }})" variant="ghost" size="sm">Edit</flux:button>
                                <flux:button wire:click="deleteCoupon({{ $coupon->id }})" variant="ghost" size="sm">Padam</flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">Belum ada kupon.</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    <flux:modal wire:model="showForm" class="w-[min(92vw,48rem)]!">
        <form wire:submit="save" class="grid gap-6">
            <div>
                <flux:heading size="lg">{{ $editingCouponId ? 'Edit kupon' : 'Tambah kupon' }}</flux:heading>
                <flux:text class="mt-1">Kupon dikunci untuk e-mel pelanggan sebaik tempahan dihantar.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="code" :label="__('Kod kupon')" placeholder="SALE10" required />
                <flux:select wire:model="discountType" :label="__('Jenis diskaun')">
                    @foreach (DiscountType::cases() as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="discountValue" :label="__('Nilai diskaun')" type="number" min="0.01" max="{{ $discountType === DiscountType::Percentage->value ? '100' : '99999999.99' }}" step="0.01" required />
                <flux:input wire:model="expiresAt" :label="__('Tarikh luput (pilihan)')" type="date" min="{{ today()->toDateString() }}" />
            </div>

            <flux:switch wire:model.live="isActive" :label="__('Aktif untuk penebusan')" />
            <flux:switch wire:model.live="appliesToAllProducts" :label="__('Boleh digunakan untuk semua produk')" />

            @if (! $appliesToAllProducts)
                <flux:field>
                    <flux:label>Produk yang layak</flux:label>
                    <div class="mt-2 grid max-h-52 gap-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-2">
                        @foreach ($this->products as $product)
                            <label wire:key="coupon-product-{{ $product->id }}" class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="productIds" value="{{ $product->id }}" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white dark:focus:ring-white">
                                <span>{{ $product->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="productIds" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button type="button" wire:click="resetForm" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
