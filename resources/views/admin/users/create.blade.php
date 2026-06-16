<x-layouts.admin title="New User">

<div class="max-w-lg">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-4 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Users
    </a>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-900 mb-5">Create New User</h2>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            @include('admin.users._form')

            <button type="submit"
                    class="w-full py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-lg transition text-sm">
                Create User
            </button>
        </form>
    </div>
</div>

</x-layouts.admin>