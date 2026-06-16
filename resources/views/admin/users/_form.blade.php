@php
    $isEdit = isset($user);
@endphp

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Tenant</label>
    <select name="tenant_id" required
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <option value="">Select tenant...</option>
        @foreach($tenants as $tenant)
            <option value="{{ $tenant->id }}" {{ old('tenant_id', $isEdit ? $user->tenant_id : null) == $tenant->id ? 'selected' : '' }}>
                {{ $tenant->name }}
            </option>
        @endforeach
    </select>
    @error('tenant_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
    <input type="text" name="name" required
           value="{{ old('name', $isEdit ? $user->name : '') }}"
           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
    <input type="email" name="email" required
           value="{{ old('email', $isEdit ? $user->email : '') }}"
           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">
        Password {{ $isEdit ? '(leave blank to keep current)' : '' }}
    </label>
    <input type="password" name="password" {{ $isEdit ? '' : 'required' }}
           class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Role (within tenant)</label>
    <select name="role" required
            class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        @foreach(['owner' => 'Owner', 'admin' => 'Admin', 'member' => 'Member'] as $value => $label)
            <option value="{{ $value }}" {{ old('role', $isEdit ? $user->role : 'member') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
</div>

<div class="flex items-center gap-2">
    <input type="checkbox" name="is_active" id="is_active" value="1"
           {{ old('is_active', $isEdit ? $user->is_active : true) ? 'checked' : '' }}
           class="w-4 h-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500">
    <label for="is_active" class="text-sm text-gray-700">Active</label>
</div>

<div class="flex items-start gap-2 p-3 bg-indigo-50 border border-indigo-100 rounded-lg">
    <input type="checkbox" name="is_super_admin" id="is_super_admin" value="1"
           {{ old('is_super_admin', $isEdit ? $user->is_super_admin : false) ? 'checked' : '' }}
           class="w-4 h-4 mt-0.5 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500">
    <label for="is_super_admin" class="text-sm text-gray-700">
        <span class="font-medium">Platform Super Admin</span>
        <br>
        <span class="text-xs text-gray-500">Can manage all tenants, users, and view every tenant's orders.</span>
    </label>
</div>