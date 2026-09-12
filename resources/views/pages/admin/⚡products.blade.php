<?php

use App\Models\Product;
use App\Support\ProductDescription;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Produk')] class extends Component {
    use WithFileUploads;

    public ?int $editingProductId = null;
    public string $search = '';
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $price = '';
    public bool $isPublished = false;
    public mixed $image = null;
    public bool $showForm = false;

    /**
     * Get the validation rules for a product form.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->editingProductId)],
            'description' => ['required', 'string', 'max:5000'],
            'price' => ['required', 'decimal:0,2', 'min:0'],
            'isPublished' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * Get all products for the admin catalogue.
     */
    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search !== '', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->get();
    }

    /**
     * Open an empty product form.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->dispatch('product-description-loaded', description: '');
    }

    /**
     * Open a product form pre-filled with its current values.
     */
    public function edit(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);

        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->isPublished = $product->is_published;
        $this->image = null;
        $this->showForm = true;
        $this->dispatch('product-description-loaded', description: $product->description);
    }

    /**
     * Generate a slug while creating a product.
     */
    public function updatedName(string $name): void
    {
        if ($this->editingProductId === null) {
            $this->slug = Str::slug($name);
        }
    }

    /**
     * Store a new product or update an existing one.
     */
    public function save(): void
    {
        $this->description = ProductDescription::sanitize($this->description);
        $validated = $this->validate();
        $uploadedImage = $validated['image'] ?? null;
        unset($validated['image']);
        $validated['is_published'] = $validated['isPublished'];
        unset($validated['isPublished']);

        $product = $this->editingProductId === null
            ? Product::create($validated)
            : Product::query()->findOrFail($this->editingProductId);

        if ($uploadedImage !== null) {
            if ($product->image_path !== null) {
                Storage::disk('public')->delete($product->image_path);
            }

            $validated['image_path'] = $uploadedImage->store('products', 'public');
        }

        $product->update($validated);

        $this->resetForm();
        Flux::toast(variant: 'success', text: 'Produk disimpan.');
    }

    /**
     * Reset the product form to its initial state.
     */
    public function resetForm(): void
    {
        $this->reset('editingProductId', 'name', 'slug', 'description', 'price', 'isPublished', 'image', 'showForm');
        $this->resetValidation();
    }
};
?>

<div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl">Produk</flux:heading>
                <flux:text class="mt-2">Urus katalog produk digital yang dipaparkan kepada pelanggan.</flux:text>
            </div>
            <flux:button wire:click="create" variant="primary" icon="plus">Tambah produk</flux:button>
        </div>

        <flux:card>
            <div class="mb-5">
                <flux:input wire:model.live.debounce.300ms="search" :label="__('Cari produk')" placeholder="Nama atau slug produk" />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>Produk</flux:table.column>
                    <flux:table.column>Harga</flux:table.column>
                    <flux:table.column>Status</flux:table.column>
                    <flux:table.column align="end">Tindakan</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($this->products as $product)
                        <flux:table.row :key="$product->id">
                            <flux:table.cell variant="strong">
                                <div class="flex items-center gap-3">
                                    @if ($product->image_path)
                                        <img src="{{ Storage::disk('public')->url($product->image_path) }}" alt="" class="size-10 rounded object-cover">
                                    @endif
                                    <div>
                                        <div>{{ $product->name }}</div>
                                        <div class="text-xs font-normal text-zinc-500 dark:text-zinc-400">/{{ $product->slug }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>RM {{ number_format((float) $product->price, 2) }}</flux:table.cell>
                            <flux:table.cell>{{ $product->is_published ? 'Diterbitkan' : 'Draf' }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button wire:click="edit({{ $product->id }})" variant="ghost" size="sm">Edit</flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">Belum ada produk.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>
    <flux:modal wire:model="showForm" class="w-[min(92vw,80rem)]! max-w-none!">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingProductId ? 'Edit produk' : 'Tambah produk' }}</flux:heading>
                <flux:text class="mt-1">Maklumat ini akan digunakan pada halaman produk awam.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Nama produk')" required />
                <flux:input wire:model="slug" :label="__('Slug URL')" required />
            </div>

            <flux:field>
                <flux:label>Penerangan</flux:label>
                <div wire:ignore x-data="productDescriptionEditor(@entangle('description'))" x-init="init()" x-on:product-description-loaded.window="setContent($event.detail.description)" class="overflow-hidden rounded-lg border border-zinc-300 bg-white shadow-sm focus-within:border-zinc-900 focus-within:ring-2 focus-within:ring-zinc-900/20 dark:border-zinc-700 dark:bg-zinc-950 dark:focus-within:border-white dark:focus-within:ring-white/20">
                    <div x-show="isReady" class="flex flex-wrap gap-1 border-b border-zinc-200 bg-zinc-50 p-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <button type="button" x-on:click="run('toggleBold')" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('bold') }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Tebal" title="Tebal">B</button>
                        <button type="button" x-on:click="run('toggleItalic')" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('italic') }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Condong" title="Condong"><span class="italic">I</span></button>
                        <button type="button" x-on:click="run('toggleHeading', { level: 2 })" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('heading', { level: 2 }) }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Tajuk" title="Tajuk">H2</button>
                        <button type="button" x-on:click="run('toggleBulletList')" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('bulletList') }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Senarai berbulet" title="Senarai berbulet">•</button>
                        <button type="button" x-on:click="run('toggleOrderedList')" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('orderedList') }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Senarai bernombor" title="Senarai bernombor">1.</button>
                        <button type="button" x-on:click="run('toggleBlockquote')" x-bind:class="{ 'bg-zinc-200 text-zinc-950 dark:bg-zinc-700 dark:text-white': isActive('blockquote') }" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Petikan" title="Petikan">❝</button>
                        <span class="mx-1 h-6 border-l border-zinc-300 dark:border-zinc-600"></span>
                        <button type="button" x-on:click="run('undo')" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Buat asal" title="Buat asal">↶</button>
                        <button type="button" x-on:click="run('redo')" class="rounded px-2 py-1 text-sm font-semibold text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white" aria-label="Buat semula" title="Buat semula">↷</button>
                    </div>
                    <div x-ref="editor"></div>
                </div>
                <flux:error name="description" />
            </flux:field>
            <flux:input wire:model="price" :label="__('Harga (RM)')" type="number" min="0" step="0.01" required />

            <flux:field>
                <flux:label>Imej utama</flux:label>
                <input wire:model="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-700 dark:text-zinc-300">
                <flux:error name="image" />
            </flux:field>

            <flux:switch wire:model="isPublished" :label="__('Terbitkan produk ini')" />

            <div class="flex justify-end gap-3">
                <flux:button type="button" wire:click="resetForm" variant="ghost">Batal</flux:button>
                <flux:button type="submit" variant="primary">Simpan</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
