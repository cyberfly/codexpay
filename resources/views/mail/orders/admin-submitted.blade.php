<x-mail::message>
# Tempahan baharu diterima

Satu tempahan baharu telah diterima dan sedang menunggu pengesahan bayaran Stripe.

| Butiran | Maklumat |
| :--- | :--- |
| Rujukan tempahan | {{ $order->reference }} |
| Produk | {{ $order->product_name }} |
| Kuantiti | {{ $order->quantity }} |
| Jumlah | RM {{ number_format((float) $order->total_price, 2) }} |
| Pelanggan | {{ $order->customer_name }} |
| E-mel | {{ $order->customer_email }} |
| Telefon | {{ $order->customer_phone }} |
| Nota | {{ $order->customer_note ?: 'Tiada' }} |

<x-mail::button :url="route('admin.orders.show', $order)">
Urus tempahan
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
