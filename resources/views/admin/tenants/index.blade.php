<x-layouts.admin title="Tenants">

<div class="flex items-center justify-end mb-5">
    <a href="{{ route('admin.tenants.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New Tenant
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
                <th class="text-left px-4 py-3 font-medium text-gray-500">Tenant</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Plan</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Users</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Orders</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Amazon</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($tenants as $tenant)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">{{ $tenant->name }}</div>
                    <div class="text-xs text-gray-400">{{ $tenant->email }}</div>
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full capitalize">{{ $tenant->plan }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $tenant->users_count }}
                    <a href="{{ route('admin.users.index', ['tenant_id' => $tenant->id]) }}" class="text-xs text-indigo-500 hover:underline ml-1">view</a>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $tenant->orders_count }}
                    <a href="{{ route('admin.orders.index', ['tenant_id' => $tenant->id]) }}" class="text-xs text-indigo-500 hover:underline ml-1">view</a>
                </td>
                <td class="px-4 py-3">
                    @if($tenant->amazonAccounts->isNotEmpty())
                        <span class="inline-flex items-center gap-1.5 text-xs text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Connected
                        </span>
                    @else
                        <span class="text-xs text-gray-400">Not connected</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tenant->is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-4 py-16 text-center text-gray-400">No tenants yet.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($tenants->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $tenants->links() }}
    </div>
    @endif
</div>

</x-layouts.admin>