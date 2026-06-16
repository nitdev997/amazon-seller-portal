<x-layouts.admin title="New Tenant">

<div class="max-w-lg">
    <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-4 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Tenants
    </a>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-5">Create New Tenant</h2>

        <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                <input type="text" name="slug" required value="{{ old('slug') }}"
                       placeholder="e.g. acme-llc"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Lowercase letters, numbers, and dashes only.</p>
                @error('slug') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                <select name="plan" required
                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="free" {{ old('plan') === 'free' ? 'selected' : '' }}>Free</option>
                    <option value="pro" {{ old('plan') === 'pro' ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ old('plan') === 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked
                       class="w-4 h-4 rounded border-gray-300 text-indigo-500 focus:ring-indigo-500">
                <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>

            <button type="submit"
                    class="w-full py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-lg transition text-sm">
                Create Tenant
            </button>
        </form>
    </div>
</div>

</x-layouts.admin>