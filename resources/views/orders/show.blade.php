<x-layouts.app :title="'Order ' . $order->amazon_order_id">

{{-- Back link --}}
<div class="mb-5">
    <a href="{{ route('orders.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Orders
    </a>
</div>

{{-- Header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold font-mono text-gray-900 tracking-tight">
                {{ $order->amazon_order_id }}
            </h1>

            {{-- Status badge --}}
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
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $badgeClass }}">
                <span class="w-1.5 h-1.5 rounded-full
                    {{ $color === 'green' ? 'bg-green-500' : ($color === 'yellow' ? 'bg-yellow-400' : ($color === 'red' ? 'bg-red-500' : 'bg-gray-400')) }}">
                </span>
                {{ $order->order_status }}
            </span>

            @if($order->is_prime)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-blue-600 text-white">
                    ★ Prime
                </span>
            @endif
            @if($order->is_business_order)
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-800 text-white">
                    B2B
                </span>
            @endif
        </div>
        <p class="mt-1.5 text-sm text-gray-500">
            Purchased {{ $order->purchase_date?->format('D, d M Y \a\t H:i') }} UTC
            &nbsp;·&nbsp;
            {{ $order->sales_channel ?? 'Amazon' }}
            &nbsp;·&nbsp;
            {{ $order->fulfillment_channel === 'AFN' ? 'Fulfilled by Amazon (FBA)' : 'Fulfilled by Merchant (FBM)' }}
        </p>
    </div>

    {{-- Order total --}}
    @if($order->order_total)
    <div class="text-right">
        <div class="text-2xl font-bold text-gray-900">
            {{ $order->currency_code }} {{ number_format($order->order_total, 2) }}
        </div>
        <div class="text-xs text-gray-400 mt-0.5">Order total</div>
    </div>
    @endif
</div>

{{-- Main grid --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- LEFT COLUMN: Items + Financials --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Order Items --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Order Items</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                    {{ $order->items->count() }} item{{ $order->items->count() !== 1 ? 's' : '' }}
                </span>
            </div>

            @if($order->items->isEmpty())
                <div class="px-5 py-10 text-center text-gray-400 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                    </svg>
                    No item data synced yet.
                </div>
            @else
                <div class="divide-y divide-gray-50">
                    @foreach($order->items as $item)
                    <div class="px-5 py-4">
                        <div class="flex gap-4">
                            {{-- Product icon placeholder --}}
                            <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center shrink-0 text-gray-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                            </div>

                            <div class="flex-1 min-w-0">
                                {{-- Title --}}
                                <p class="text-sm font-medium text-gray-900 leading-snug line-clamp-2">
                                    {{ $item->title ?? 'Unknown Product' }}
                                </p>

                                {{-- Identifiers --}}
                                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1.5">
                                    @if($item->asin)
                                        <span class="text-xs text-gray-500">
                                            ASIN: <span class="font-mono font-medium text-gray-700">{{ $item->asin }}</span>
                                        </span>
                                    @endif
                                    @if($item->seller_sku)
                                        <span class="text-xs text-gray-500">
                                            SKU: <span class="font-mono font-medium text-gray-700">{{ $item->seller_sku }}</span>
                                        </span>
                                    @endif
                                    @if($item->order_item_id)
                                        <span class="text-xs text-gray-500">
                                            Item ID: <span class="font-mono text-gray-600">{{ $item->order_item_id }}</span>
                                        </span>
                                    @endif
                                </div>

                                {{-- Qty + pricing row --}}
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-1 mt-2.5">
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-500">Qty ordered:</span>
                                        <span class="font-semibold text-gray-900">{{ $item->quantity_ordered }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="text-gray-500">Qty shipped:</span>
                                        <span class="font-semibold {{ $item->quantity_shipped >= $item->quantity_ordered ? 'text-green-600' : 'text-yellow-600' }}">
                                            {{ $item->quantity_shipped }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Customization data --}}
                                @if($item->hasCustomization())
                                <div class="mt-3 rounded-lg border border-orange-200 bg-orange-50 overflow-hidden">
                                    <div class="flex items-center gap-2 px-3 py-2 border-b border-orange-100">
                                        <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        <span class="text-xs font-semibold text-orange-700 uppercase tracking-wide">Customization</span>
                                    </div>
                                    <div class="px-3 py-2 grid grid-cols-1 gap-y-1.5">
                                        @foreach($item->customization_data as $field)
                                            @if(!empty($field['value']))
                                            <div class="flex gap-2 text-xs">
                                                <span class="text-orange-600 font-medium shrink-0 min-w-24">
                                                    {{ $field['label'] ?? 'Field' }}:
                                                </span>
                                                <span class="text-orange-900 font-semibold break-all">
                                                    {{ $field['value'] }}
                                                </span>
                                            </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Pricing block --}}
                            <div class="text-right shrink-0 space-y-1">
                                @if($item->item_price !== null)
                                    <div class="text-base font-bold text-gray-900">
                                        {{ $item->currency_code }} {{ number_format($item->item_price, 2) }}
                                    </div>
                                @endif
                                @if($item->item_tax)
                                    <div class="text-xs text-gray-400">
                                        + {{ $item->currency_code }} {{ number_format($item->item_tax, 2) }} tax
                                    </div>
                                @endif
                                @if($item->shipping_price)
                                    <div class="text-xs text-gray-400">
                                        + {{ $item->currency_code }} {{ number_format($item->shipping_price, 2) }} shipping
                                    </div>
                                @endif
                                @if($item->promotion_discount)
                                    <div class="text-xs text-green-600 font-medium">
                                        − {{ $item->currency_code }} {{ number_format($item->promotion_discount, 2) }} promo
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Items total --}}
                <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                    <span class="text-sm text-gray-500">
                        {{ $order->number_of_items_shipped }} shipped
                        / {{ $order->number_of_items_unshipped }} unshipped
                    </span>
                    @if($order->order_total)
                    <div class="text-sm font-semibold text-gray-900">
                        Total: {{ $order->currency_code }} {{ number_format($order->order_total, 2) }}
                    </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Financial breakdown --}}
        @if($order->items->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Financial Breakdown</h2>
            </div>
            <div class="px-5 py-4 space-y-2">
                @php
                    $subtotal  = $order->items->sum('item_price');
                    $totalTax  = $order->items->sum('item_tax');
                    $totalShip = $order->items->sum('shipping_price');
                    $totalDisc = $order->items->sum('promotion_discount');
                    $currency  = $order->currency_code;
                @endphp

                <div class="flex justify-between text-sm text-gray-600">
                    <span>Items subtotal</span>
                    <span>{{ $currency }} {{ number_format($subtotal, 2) }}</span>
                </div>
                @if($totalTax > 0)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Tax</span>
                    <span>{{ $currency }} {{ number_format($totalTax, 2) }}</span>
                </div>
                @endif
                @if($totalShip > 0)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>Shipping</span>
                    <span>{{ $currency }} {{ number_format($totalShip, 2) }}</span>
                </div>
                @endif
                @if($totalDisc > 0)
                <div class="flex justify-between text-sm text-green-600">
                    <span>Promotions / Discounts</span>
                    <span>− {{ $currency }} {{ number_format($totalDisc, 2) }}</span>
                </div>
                @endif

                <div class="flex justify-between text-sm font-bold text-gray-900 pt-2 border-t border-gray-100">
                    <span>Order Total</span>
                    <span>{{ $currency }} {{ number_format($order->order_total ?? ($subtotal + $totalTax + $totalShip - $totalDisc), 2) }}</span>
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- RIGHT COLUMN: Meta panels --}}
    <div class="space-y-5">

        {{-- Buyer Info --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Buyer</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                @if($order->buyer_name)
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-orange-100 rounded-full flex items-center justify-center text-sm font-bold text-orange-600 shrink-0">
                            {{ strtoupper(substr($order->buyer_name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $order->buyer_name }}</div>
                            @if($order->buyer_email)
                                <div class="text-xs text-gray-500">{{ $order->buyer_email }}</div>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400 italic">
                        Buyer opted out of contact info sharing.
                    </p>
                @endif
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Shipping Address</h2>
            </div>
            <div class="px-5 py-4">
                @if($order->shipping_address)
                    @php $addr = $order->shipping_address; @endphp
                    <address class="not-italic text-sm text-gray-700 space-y-0.5">
                        @if(!empty($addr['Name']))       <div class="font-medium">{{ $addr['Name'] }}</div> @endif
                        @if(!empty($addr['AddressLine1']) && $addr['AddressLine1'] !== 'null') <div>{{ $addr['AddressLine1'] }}</div> @endif
                        @if(!empty($addr['AddressLine2']) && $addr['AddressLine2'] !== 'null') <div>{{ $addr['AddressLine2'] }}</div> @endif
                        @if(!empty($addr['AddressLine3']) && $addr['AddressLine3'] !== 'null') <div>{{ $addr['AddressLine3'] }}</div> @endif
                        <div>
                            @if(!empty($addr['PostalCode'])) {{ $addr['PostalCode'] }} @endif
                            @if(!empty($addr['City'])) {{ $addr['City'] }} @endif
                        </div>
                        @if(!empty($addr['StateOrRegion']))  <div>{{ $addr['StateOrRegion'] }}</div> @endif
                        @if(!empty($addr['CountryCode']))
                            <div class="mt-1">
                                <span class="inline-block text-xs font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                    {{ $addr['CountryCode'] }}
                                </span>
                            </div>
                        @endif
                    </address>
                @else
                    <p class="text-sm text-gray-400 italic">No shipping address available.</p>
                @endif
            </div>
        </div>

        {{-- Dates --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Timeline</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                @foreach([
                    ['label' => 'Purchase Date',       'value' => $order->purchase_date],
                    ['label' => 'Last Updated',        'value' => $order->last_update_date],
                    ['label' => 'Earliest Ship Date',  'value' => $order->earliest_ship_date],
                    ['label' => 'Latest Ship Date',    'value' => $order->latest_ship_date],
                    ['label' => 'Earliest Delivery',   'value' => $order->earliest_delivery_date],
                    ['label' => 'Latest Delivery',     'value' => $order->latest_delivery_date],
                ] as $date)
                    @if($date['value'])
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-xs text-gray-500 shrink-0">{{ $date['label'] }}</span>
                        <span class="text-xs font-medium text-gray-700 text-right">
                            {{ $date['value']->format('d M Y') }}<br>
                            <span class="text-gray-400">{{ $date['value']->format('H:i') }} UTC</span>
                        </span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Order metadata --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Order Details</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                @foreach([
                    ['label' => 'Marketplace',       'value' => $order->marketplace_id],
                    ['label' => 'Sales Channel',     'value' => $order->sales_channel],
                    ['label' => 'Fulfillment',       'value' => $order->fulfillment_channel === 'AFN' ? 'FBA' : 'FBM'],
                    ['label' => 'Seller Order ID',   'value' => $order->seller_order_id],
                    ['label' => 'Amazon Account',    'value' => $order->amazonAccount?->seller_id],
                    ['label' => 'Prime',             'value' => $order->is_prime ? 'Yes' : 'No'],
                    ['label' => 'Business Order',    'value' => $order->is_business_order ? 'Yes' : 'No'],
                    ['label' => 'Replacement',       'value' => $order->is_replacement_order ? 'Yes' : 'No'],
                ] as $meta)
                    @if(!is_null($meta['value']) && $meta['value'] !== '')
                    <div class="flex justify-between items-start gap-4">
                        <span class="text-xs text-gray-500 shrink-0">{{ $meta['label'] }}</span>
                        <span class="text-xs font-medium text-gray-700 text-right font-mono">{{ $meta['value'] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

    </div>
</div>

</x-layouts.app>