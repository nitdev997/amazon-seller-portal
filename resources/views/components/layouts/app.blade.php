<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — {{ $title ?? 'Dashboard' }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|space-mono:400,700" rel="stylesheet" />

    {{-- Styles (Vite) --}}
   <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

{{-- Sidebar layout --}}
<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="hidden md:flex md:flex-col w-64 bg-gray-900 text-white shrink-0">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-700">
            <div class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <div class="font-semibold text-sm leading-none">{{ config('app.name') }}</div>
                <div class="text-xs text-gray-400 mt-0.5">{{ auth()->user()->tenant->name }}</div>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('orders.index') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('orders.*') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Orders
            </a>

            <div class="pt-4 pb-2">
                <span class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Integrations</span>
            </div>

            <a href="{{ route('amazon.connect') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('amazon.*') ? 'bg-gray-700 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800' }}">
                <svg class="w-4 h-4 text-orange-400" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595l.315-.14c.138-.06.234-.1.293-.13.226-.088.39-.046.525.13.12.174.09.336-.1.48-.256.19-.6.41-1.006.654-1.244.743-2.64 1.316-4.185 1.726a18.48 18.48 0 01-4.963.646c-3.853 0-7.304-.96-10.354-2.88-.394-.245-.466-.477-.19-.695zm-.96-2.23c.138-.217.287-.244.44-.07 3.46 3.26 7.68 4.89 12.66 4.89 2.555 0 5.11-.528 7.666-1.583l.29-.125c.246-.096.43-.163.543-.198.214-.065.376-.01.487.16.105.163.08.31-.077.44-.295.242-.74.51-1.324.81-1.33.68-2.852 1.188-4.566 1.527a20.3 20.3 0 01-4.162.437c-4.204 0-7.97-1.285-11.3-3.85-.395-.305-.424-.567-.09-.786zM6.95 8.74c-.195-.26-.135-.49.18-.685C9.56 6.595 12.1 5.96 14.74 5.96c1.97 0 3.907.39 5.81 1.168.352.144.517.33.495.558-.02.23-.194.35-.52.36-.125 0-.307-.03-.545-.09-1.603-.413-3.218-.619-4.847-.619-2.35 0-4.667.52-6.946 1.56-.196.093-.38.14-.555.14-.13 0-.26-.043-.39-.128l-.29-.2zm13.524 4.92c-.014.285-.188.428-.527.428-.193 0-.43-.054-.71-.16-1.63-.617-3.296-.926-5-.926-2.225 0-4.303.497-6.236 1.49-.262.135-.487.2-.674.2-.303 0-.453-.135-.453-.407 0-.156.073-.308.22-.455.14-.145.3-.255.48-.33C10.26 12.93 12.5 12.44 14.9 12.44c1.86 0 3.67.335 5.43 1.004.334.124.496.32.48.585l-.16.6z"/>
                </svg>
                Amazon
                @if(auth()->user()->tenant->hasConnectedAmazonAccount())
                    <span class="ml-auto w-2 h-2 rounded-full bg-green-400"></span>
                @endif
            </a>
        </nav>

        {{-- User --}}
        <div class="border-t border-gray-700 px-3 py-3">
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="w-8 h-8 rounded-full bg-orange-500 flex items-center justify-center text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-white transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shrink-0">
            <h1 class="text-lg font-semibold text-gray-900">{{ $title ?? 'Dashboard' }}</h1>

            {{-- Flash messages --}}
            <div>
                @if(session('success'))
                    <div class="flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-4 py-2">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="flex items-center gap-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </main>
    </div>
</div>

@livewireScripts
</body>
</html>
