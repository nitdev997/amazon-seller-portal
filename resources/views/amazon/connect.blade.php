<x-layouts.app title="Amazon Integration">

<div class="max-w-2xl">

    {{-- Connection card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100 flex items-center gap-4">
            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595l.315-.14c.138-.06.234-.1.293-.13.226-.088.39-.046.525.13.12.174.09.336-.1.48-.256.19-.6.41-1.006.654-1.244.743-2.64 1.316-4.185 1.726a18.48 18.48 0 01-4.963.646c-3.853 0-7.304-.96-10.354-2.88-.394-.245-.466-.477-.19-.695z"/>
                </svg>
            </div>
            <div>
                <h2 class="font-semibold text-gray-900">Amazon Seller Account</h2>
                <p class="text-sm text-gray-500">Connect via SP-API to sync your orders</p>
            </div>
        </div>

        {{-- Body --}}
        <div class="px-6 py-6">

            @if($account && $account->isConnected())
                {{-- Connected state --}}
                <div class="flex items-start gap-4 p-4 bg-green-50 border border-green-200 rounded-xl mb-6">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="font-medium text-green-800">Connected</div>
                        <div class="text-sm text-green-700 mt-0.5">
                            Seller ID: <span class="font-mono font-medium">{{ $account->seller_id }}</span>
                        </div>
                        @if($account->marketplace_name)
                        <div class="text-sm text-green-700">
                            Marketplace: {{ $account->marketplace_name }}
                        </div>
                        @endif
                        @if($account->last_synced_at)
                        <div class="text-xs text-green-600 mt-1">
                            Last synced {{ $account->last_synced_at->diffForHumans() }}
                        </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('amazon.sync') }}"
                       class="flex items-center gap-2 px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync Orders Now
                    </a>

                    <form method="POST" action="{{ route('amazon.disconnect') }}" onsubmit="return confirm('Disconnect Amazon account?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-red-600 border border-red-200 hover:bg-red-50 rounded-lg transition">
                            Disconnect
                        </button>
                    </form>
                </div>

            @else
                {{-- Disconnected state --}}
                <p class="text-gray-600 text-sm mb-6">
                    Connect your Amazon Seller account to automatically sync orders, inventory, and financial data.
                    You'll be redirected to Amazon Seller Central to authorize access.
                </p>

                <div class="space-y-3 mb-6">
                    @foreach(['Read-only access to your order data', 'Automatic order sync every hour', 'Secure OAuth — we never store your password'] as $item)
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ $item }}
                    </div>
                    @endforeach
                </div>

                <a href="{{ route('amazon.redirect') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-medium rounded-lg transition text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Connect Amazon Account
                </a>

                @if($account && $account->status === 'error')
                    <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                        <strong>Last error:</strong> {{ $account->error_message }}
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer: SP-API info --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
            <p class="text-xs text-gray-400">
                Uses Amazon Selling Partner API (SP-API).
                Your tokens are encrypted and stored securely.
                <a href="https://developer-docs.amazon.com/sp-api" target="_blank" class="text-orange-500 hover:underline">Learn more →</a>
            </p>
        </div>
    </div>
</div>

</x-layouts.app>
