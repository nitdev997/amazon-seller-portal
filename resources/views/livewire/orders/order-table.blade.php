<div>
    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Orders</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Shipped</div>
            <div class="mt-1 text-2xl font-bold text-green-600">{{ number_format($stats['shipped']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</div>
            <div class="mt-1 text-2xl font-bold text-yellow-500">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">${{ number_format($stats['revenue'], 2) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-4">
        <div class="p-4 flex flex-wrap gap-3 items-end">

            {{-- Search --}}
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Order ID, buyer, SKU, ASIN..."
                        class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    >
                </div>
            </div>

            {{-- Status --}}
            <div class="w-40">
                <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="Unshipped">Unshipped</option>
                    <option value="PartiallyShipped">Partially Shipped</option>
                    <option value="Shipped">Shipped</option>
                    <option value="Canceled">Canceled</option>
                    <option value="Unfulfillable">Unfulfillable</option>
                </select>
            </div>

            {{-- Channel --}}
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">Channel</label>
                <select wire:model.live="channelFilter"
                        class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">All Channels</option>
                    <option value="MFN">MFN (FBM)</option>
                    <option value="AFN">AFN (FBA)</option>
                </select>
            </div>

            {{-- Date range --}}
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" wire:model.live="dateFrom"
                       class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            <div class="w-36">
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" wire:model.live="dateTo"
                       class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            {{-- Reset --}}
            @if($search || $statusFilter || $channelFilter || $dateFrom || $dateTo)
                <button wire:click="resetFilters"
                        class="py-2 px-3 text-sm text-gray-500 hover:text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    Clear
                </button>
            @endif

            {{-- Sync button --}}
            <div class="ml-auto">
                <a href="{{ route('amazon.sync') }}"
                   class="flex items-center gap-2 py-2 px-4 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Sync Now
                </a>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-3 font-medium text-gray-500">
                            <button wire:click="sortBy('amazon_order_id')" class="flex items-center gap-1 hover:text-gray-700">
                                Order ID
                                @if($sortField === 'amazon_order_id')
                                    <svg class="w-3 h-3 {{ $sortDir === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">
                            <button wire:click="sortBy('purchase_date')" class="flex items-center gap-1 hover:text-gray-700">
                                Date
                                @if($sortField === 'purchase_date')
                                    <svg class="w-3 h-3 {{ $sortDir === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Buyer</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Channel</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-500">Items</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-500">
                            <button wire:click="sortBy('order_total')" class="flex items-center gap-1 ml-auto hover:text-gray-700">
                                Total
                                @if($sortField === 'order_total')
                                    <svg class="w-3 h-3 {{ $sortDir === 'asc' ? '' : 'rotate-180' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                @endif
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($orders as $order)
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs font-medium text-gray-900">{{ $order->amazon_order_id }}</div>
                                @if($order->is_prime)
                                    <span class="inline-block mt-0.5 text-xs text-blue-600 font-medium">Prime</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                {{ $order->purchase_date?->format('M d, Y') }}<br>
                                <span class="text-gray-400">{{ $order->purchase_date?->format('H:i') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-900 font-medium">{{ $order->buyer_name ?: '—' }}</div>
                                <div class="text-gray-400 text-xs">{{ $order->buyer_email ?: '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $color = $order->statusColor();
                                    $classes = [
                                        'green'  => 'bg-green-50 text-green-700 ring-green-100',
                                        'yellow' => 'bg-yellow-50 text-yellow-700 ring-yellow-100',
                                        'blue'   => 'bg-blue-50 text-blue-700 ring-blue-100',
                                        'red'    => 'bg-red-50 text-red-700 ring-red-100',
                                        'gray'   => 'bg-gray-50 text-gray-600 ring-gray-100',
                                    ][$color] ?? 'bg-gray-50 text-gray-600 ring-gray-100';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $classes }}">
                                    {{ $order->order_status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                    {{ $order->fulfillment_channel === 'AFN' ? 'FBA' : 'FBM' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">
                                <span title="{{ $order->items->pluck('title')->implode(', ') }}">
                                    {{ $order->items->count() }} item(s)
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">
                                @if($order->order_total)
                                    {{ $order->currency_code }} {{ number_format($order->order_total, 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center text-gray-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                No orders found.
                                @if($search || $statusFilter)
                                    <br><button wire:click="resetFilters" class="text-orange-500 hover:underline text-sm mt-1">Clear filters</button>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Wire loading indicator --}}
    <div wire:loading.delay class="fixed bottom-4 right-4 bg-gray-900 text-white text-xs px-3 py-2 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Filtering...
    </div>
</div>
