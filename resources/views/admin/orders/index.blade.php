<x-layouts.admin title="All Orders">

<form method="GET" class="bg-white rounded-xl border border-gray-200 p-4 mb-4 flex flex-wrap gap-3 items-end">

    <div class="flex-1 min-w-48">
        <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Order ID, buyer..."
               class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>

    <div class="w-56">
        <label class="block text-xs font-medium text-gray-500 mb-1">Tenant</label>
        <select name="tenant_id" class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Tenants</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="w-44">
        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
        <select name="status" class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Statuses</option>
            @foreach(['Pending','Unshipped','PartiallyShipped','Shipped','Canceled','Unfulfillable'] as $status)
                <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
        </select>
    </div>

    <button type="submit" class="py-2 px-4 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition">
        Filter
    </button>

    @if(request()->anyFilled(['search', 'tenant_id', 'status']))
        <a href="{{ route('admin.orders.index') }}" class="py-2 px-3 text-sm text-gray-500 hover:text-gray-700 transition">Clear</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
                <th class="text-left px-4 py-3 font-medium text-gray-500">Order ID</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Tenant</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Date</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Items</th>
                <th class="text-right px-4 py-3 font-medium text-gray-500">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <a href="{{ route('admin.orders.show', $order->amazon_order_id) }}"
                       class="font-mono text-xs font-medium text-gray-900 hover:text-indigo-500 hover:underline transition">
                        {{ $order->amazon_order_id }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $order->tenant?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->purchase_date?->format('M d, Y') }}</td>
                <td class="px-4 py-3">
                    @php
                        $color = $order->statusColor();
                        $classes = [
                            'green'  => 'bg-green-50 text-green-700',
                            'yellow' => 'bg-yellow-50 text-yellow-700',
                            'blue'   => 'bg-blue-50 text-blue-700',
                            'red'    => 'bg-red-50 text-red-700',
                            'gray'   => 'bg-gray-100 text-gray-600',
                        ][$color] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $classes }}">
                        {{ $order->order_status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $order->items->count() }} item(s)</td>
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
                <td colspan="6" class="px-4 py-16 text-center text-gray-400">No orders found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($orders->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $orders->links() }}
    </div>
    @endif
</div>

</x-layouts.admin>