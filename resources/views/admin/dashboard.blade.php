<x-layouts.admin title="Admin Dashboard">

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tenants</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_tenants']) }}</div>
        <div class="text-xs text-gray-400 mt-1">{{ $stats['active_tenants'] }} active</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Users</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_users']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Orders</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Platform Revenue</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">${{ number_format($stats['total_revenue'], 0) }}</div>
        <div class="text-xs text-gray-400 mt-1">{{ $stats['connected_amazon'] }} Amazon accounts connected</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    {{-- Recent tenants --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Recent Tenants</h2>
            <a href="{{ route('admin.tenants.index') }}" class="text-xs text-indigo-500 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentTenants as $tenant)
            <div class="px-5 py-3 flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ $tenant->name }}</div>
                    <div class="text-xs text-gray-400">{{ $tenant->users_count }} users · {{ $tenant->orders_count }} orders</div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $tenant->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">No tenants yet.</div>
            @endforelse
        </div>
    </div>

    {{-- Recent orders --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Recent Orders (All Tenants)</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-indigo-500 hover:underline">View all</a>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentOrders as $order)
            <a href="{{ route('admin.orders.show', $order->amazon_order_id) }}" class="px-5 py-3 flex items-center justify-between hover:bg-gray-50 transition block">
                <div>
                    <div class="text-sm font-mono font-medium text-gray-900">{{ $order->amazon_order_id }}</div>
                    <div class="text-xs text-gray-400">{{ $order->tenant?->name ?? '—' }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-medium text-gray-900">
                        {{ $order->currency_code }} {{ number_format($order->order_total ?? 0, 2) }}
                    </div>
                    <div class="text-xs text-gray-400">{{ $order->order_status }}</div>
                </div>
            </a>
            @empty
            <div class="px-5 py-8 text-center text-sm text-gray-400">No orders yet.</div>
            @endforelse
        </div>
    </div>
</div>

</x-layouts.admin>