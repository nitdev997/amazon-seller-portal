<x-layouts.admin title="Manage Users">

<div class="flex items-center justify-between mb-5">
    <form method="GET" class="flex gap-3 flex-1 max-w-2xl">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search name or email..."
               class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">

        <select name="tenant_id" onchange="this.form.submit()"
                class="px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Tenants</option>
            @foreach($tenants as $tenant)
                <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                    {{ $tenant->name }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition">
            Search
        </button>
    </form>

    <a href="{{ route('admin.users.create') }}"
       class="ml-4 flex items-center gap-2 px-4 py-2 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New User
    </a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
                <th class="text-left px-4 py-3 font-medium text-gray-500">Name</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Tenant</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Role</th>
                <th class="text-left px-4 py-3 font-medium text-gray-500">Status</th>
                <th class="text-right px-4 py-3 font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-900">
                        {{ $user->name }}
                        @if($user->isSuperAdmin())
                            <span class="ml-1.5 text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-semibold">ADMIN</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-400">{{ $user->email }}</div>
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $user->tenant?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full capitalize">{{ $user->role }}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $user->is_active ? 'text-green-700' : 'text-gray-400' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-500 hover:underline text-xs font-medium">Edit</a>

                        <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-gray-500 hover:underline text-xs font-medium">
                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>

                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                              onsubmit="return confirm('Delete this user? This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-500 hover:underline text-xs font-medium">Delete</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-4 py-16 text-center text-gray-400">No users found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($users->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $users->links() }}
    </div>
    @endif
</div>

</x-layouts.admin>