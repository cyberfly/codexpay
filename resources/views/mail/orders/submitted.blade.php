<x-mail::message>
# Tempahan diterima

Hai {{ $order->customer_name }},

Kami telah menerima tempahan anda. Sila lengkapkan bayaran melalui Stripe untuk mengesahkan tempahan.

| Butiran | Maklumat |
| :--- | :--- |
| Rujukan tempahan | {{ $order->reference }} |
| Produk | {{ $order->product_name }} |
| Kuantiti | {{ $order->quantity }} |
| Jumlah | RM {{ number_format((float) $order->total_price, 2) }} |

<x-mail::button :url="route('orders.confirmation', $order)">
Lihat tempahan
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
