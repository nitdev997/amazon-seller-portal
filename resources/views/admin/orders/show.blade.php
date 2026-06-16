<x-layouts.admin :title="'Order ' . $order->amazon_order_id">

<div class="mb-5">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to All Orders
    </a>
</div>

{{-- Tenant banner --}}
<div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 mb-5 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0h-2M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 6v-3a1 1 0 011-1h0a1 1 0 011 1v3"/>
        </svg>
        <span class="text-sm font-medium text-indigo-900">{{ $order->tenant?->name ?? 'Unknown tenant' }}</span>
    </div>
    <a href="{{ route('admin.users.index', ['tenant_id' => $order->tenant_id]) }}" class="text-xs text-indigo-600 hover:underline">
        View tenant's users →
    </a>
</div>

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold font-mono text-gray-900 tracking-tight">{{ $order->amazon_order_id }}</h1>

            @php
                $color = $order->statusColor();
                $badgeClass = [
                    'green'  => 'bg-green-50 text-green-700 ring-green-200',
                    'yellow' => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
                    'blue'   => 'bg-blue-50 text-blue-700 ring-blue-200',
                    'red'    => 'bg-red-50 text-red-700 ring-red-200',
                    'gray'   => 'bg-gray-100 text-gray-600 ring-gray-200',
                ][$color] ?? 'bg-gray-100 text-gray-600 ring-gray-200';
            @endphp
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                {{ $order->order_status }}
            </span>
        </div>
        <p class="mt-1.5 text-sm text-gray-500">
            Purchased {{ $order->purchase_date?->format('D, d M Y \a\t H:i') }} UTC
            &nbsp;·&nbsp;
            {{ $order->fulfillment_channel === 'AFN' ? 'FBA' : 'FBM' }}
        </p>
    </div>

    @if($order->order_total)
    <div class="text-right">
        <div class="text-2xl font-bold text-gray-900">{{ $order->currency_code }} {{ number_format($order->order_total, 2) }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Order total</div>
    </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Items --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Order Items</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">{{ $order->items->count() }} item(s)</span>
            </div>

            <div class="divide-y divide-gray-50">
                @foreach($order->items as $item)
                <div class="px-5 py-4">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center shrink-0 text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 leading-snug">{{ $item->title ?? 'Unknown Product' }}</p>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1.5">
                                @if($item->asin)<span class="text-xs text-gray-500">ASIN: <span class="font-mono font-medium text-gray-700">{{ $item->asin }}</span></span>@endif
                                @if($item->seller_sku)<span class="text-xs text-gray-500">SKU: <span class="font-mono font-medium text-gray-700">{{ $item->seller_sku }}</span></span>@endif
                            </div>
                            <div class="flex items-center gap-2 text-sm mt-2">
                                <span class="text-gray-500">Qty:</span>
                                <span class="font-semibold text-gray-900">{{ $item->quantity_ordered }} ordered / {{ $item->quantity_shipped }} shipped</span>
                            </div>

                            {{-- Customization (API-parsed) --}}
                            @if($item->hasApiCustomization())
                            <div class="mt-3 rounded-lg border border-orange-200 bg-orange-50 p-2.5 space-y-1.5">
                                @foreach($item->customization_data as $field)
                                    @if(!empty($field['value']))
                                        @if(($field['type'] ?? 'text') === 'image')
                                            <img src="{{ $field['url'] ?? asset('storage/' . $field['value']) }}" class="max-h-32 rounded border border-orange-200">
                                        @else
                                            <div class="flex gap-2 text-xs">
                                                <span class="text-orange-600 font-medium shrink-0">{{ $field['label'] }}:</span>
                                                <span class="text-orange-900 font-semibold">{{ $field['value'] }}</span>
                                            </div>
                                        @endif
                                    @endif
                                @endforeach
                            </div>
                            @endif

                            {{-- Manual customization notes --}}
                            @if($item->hasManualCustomization())
                            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-2.5">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Customization Notes</span>
                                <pre class="text-xs text-gray-700 whitespace-pre-wrap font-sans">{{ $item->manual_customization_notes }}</pre>
                            </div>
                            @endif
                        </div>
                        <div class="text-right shrink-0">
                            @if($item->item_price !== null)
                                <div class="text-base font-bold text-gray-900">{{ $item->currency_code }} {{ number_format($item->item_price, 2) }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Meta --}}
    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Buyer</h2></div>
            <div class="px-5 py-4">
                @if($order->buyer_name)
                    <div class="text-sm font-medium text-gray-900">{{ $order->buyer_name }}</div>
                    <div class="text-xs text-gray-500">{{ $order->buyer_email }}</div>
                @else
                    <p class="text-sm text-gray-400 italic">No buyer info available.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Shipping Address</h2></div>
            <div class="px-5 py-4">
                @if($order->shipping_address)
                    @php $addr = $order->shipping_address; @endphp
                    <address class="not-italic text-sm text-gray-700 space-y-0.5">
                        @if(!empty($addr['PostalCode']) || !empty($addr['City']))
                            <div>{{ $addr['PostalCode'] ?? '' }} {{ $addr['City'] ?? '' }}</div>
                        @endif
                        @if(!empty($addr['StateOrRegion']))<div>{{ $addr['StateOrRegion'] }}</div>@endif
                        @if(!empty($addr['CountryCode']))<div class="text-xs font-semibold bg-gray-100 inline-block px-2 py-0.5 rounded mt-1">{{ $addr['CountryCode'] }}</div>@endif
                    </address>
                @else
                    <p class="text-sm text-gray-400 italic">No address available.</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-900">Order Details</h2></div>
            <div class="px-5 py-4 space-y-2">
                <div class="flex justify-between text-xs"><span class="text-gray-500">Marketplace</span><span class="font-mono text-gray-700">{{ $order->marketplace_id }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-gray-500">Fulfillment</span><span class="text-gray-700">{{ $order->fulfillment_channel === 'AFN' ? 'FBA' : 'FBM' }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-gray-500">Amazon Account</span><span class="font-mono text-gray-700">{{ $order->amazonAccount?->seller_id }}</span></div>
            </div>
        </div>
    </div>
</div>

</x-layouts.admin>