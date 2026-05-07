<x-layouts.app title="Dashboard">

{{-- Welcome + Amazon status banner --}}
@if(!auth()->user()->tenant->hasConnectedAmazonAccount())
<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-6 flex items-center gap-4">
    <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center shrink-0">
        <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
    </div>
    <div class="flex-1">
        <p class="text-sm font-medium text-orange-800">Amazon account not connected</p>
        <p class="text-sm text-orange-700">Connect your Seller account to start syncing orders.</p>
    </div>
    <a href="{{ route('amazon.connect') }}" class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
        Connect Now
    </a>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Orders</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total_orders']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Shipped Today</div>
        <div class="mt-2 text-3xl font-bold text-green-600">{{ number_format($stats['shipped_today']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</div>
        <div class="mt-2 text-3xl font-bold text-yellow-500">{{ number_format($stats['pending_orders']) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue (30d)</div>
        <div class="mt-2 text-3xl font-bold text-gray-900">${{ number_format($stats['revenue_30d'], 0) }}</div>
    </div>
</div>

{{-- Quick links --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <a href="{{ route('orders.index') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-sm transition group">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-gray-100 group-hover:bg-orange-50 rounded-lg flex items-center justify-center transition">
                <svg class="w-5 h-5 text-gray-500 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div>
                <div class="font-semibold text-gray-900">View Orders</div>
                <div class="text-sm text-gray-500">Browse, search and filter all orders</div>
            </div>
        </div>
    </a>
    <a href="{{ route('amazon.connect') }}" class="bg-white rounded-xl border border-gray-200 p-5 hover:border-orange-300 hover:shadow-sm transition group">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-gray-100 group-hover:bg-orange-50 rounded-lg flex items-center justify-center transition">
                <svg class="w-5 h-5 text-orange-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595l.315-.14c.138-.06.234-.1.293-.13.226-.088.39-.046.525.13.12.174.09.336-.1.48-.256.19-.6.41-1.006.654-1.244.743-2.64 1.316-4.185 1.726a18.48 18.48 0 01-4.963.646c-3.853 0-7.304-.96-10.354-2.88-.394-.245-.466-.477-.19-.695z"/>
                </svg>
            </div>
            <div>
                <div class="font-semibold text-gray-900">Amazon Integration</div>
                <div class="text-sm text-gray-500">
                    @if(auth()->user()->tenant->hasConnectedAmazonAccount())
                        Connected — manage or sync now
                    @else
                        Connect your seller account
                    @endif
                </div>
            </div>
        </div>
    </a>
</div>

</x-layouts.app>
