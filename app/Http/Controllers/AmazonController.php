<?php

namespace App\Http\Controllers;

use App\Models\AmazonAccount;
use App\Services\Amazon\SpApiOAuthService;
use App\Services\Amazon\SpApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AmazonController extends Controller
{
    public function __construct(
        private readonly SpApiOAuthService $oauthService,
        private readonly SpApiService      $apiService,
    ) {}

    // ─── Connect page ─────────────────────────────────────────────

    /**
     * Show the Amazon connection management page.
     */
    public function index(): View
    {
        $account = auth()->user()->tenant->activeAmazonAccount();

        return view('amazon.connect', compact('account'));
    }

    // ─── Step 1: Redirect to Amazon ───────────────────────────────

    /**
     * Start the SP-API OAuth flow by redirecting the seller to Amazon.
     */
    public function redirect(Request $request): RedirectResponse
    {
        // Generate CSRF state token and store in session
        $state = Str::random(40);
        session(['amazon_oauth_state' => $state]);

        $url = $this->oauthService->buildAuthorizationUrl($state);

        return redirect($url);
    }

    // ─── Step 2: Amazon callback ──────────────────────────────────

    /**
     * Amazon redirects here after the seller grants consent.
     *
     * Query params:
     *   - spapi_oauth_code   : authorization code (exchange for tokens)
     *   - state              : must match session value
     *   - selling_partner_id : seller's Amazon ID
     */
    public function callback(Request $request): RedirectResponse
    {
        // Validate CSRF state
        if ($request->state !== session('amazon_oauth_state')) {
            return redirect()->route('amazon.connect')
                ->with('error', 'Invalid OAuth state. Please try again.');
        }

        session()->forget('amazon_oauth_state');

        if ($request->filled('error')) {
            return redirect()->route('amazon.connect')
                ->with('error', 'Amazon authorization was denied: ' . $request->error_description);
        }

        try {
            $tenantId = auth()->user()->tenant_id;

            // Create or find the Amazon account record for this tenant
            $account = AmazonAccount::firstOrCreate(
                ['tenant_id' => $tenantId],
                ['status'    => 'disconnected']
            );

            // Exchange auth code for access + refresh tokens
            $this->oauthService->exchangeCodeForTokens(
                code: $request->spapi_oauth_code,
                account: $account,
            );

            // Store the seller ID from the callback
            $account->update([
                'seller_id' => $request->selling_partner_id,
                'status'    => 'connected',
            ]);

            // Trigger initial order sync (queued job in production)
            $synced = $this->apiService->syncOrders($account);

            return redirect()->route('dashboard')
                ->with('success', "Amazon account connected! Synced {$synced} orders.");

        } catch (\Exception $e) {
            Log::error('Amazon OAuth callback failed', [
                'tenant_id' => auth()->user()->tenant_id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->route('amazon.connect')
                ->with('error', 'Failed to connect Amazon account: ' . $e->getMessage());
        }
    }

    // ─── Disconnect ───────────────────────────────────────────────

    /**
     * Disconnect the Amazon account (revoke tokens + soft-delete).
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $account = auth()->user()->tenant->activeAmazonAccount();

        if ($account) {
            $account->update([
                'access_token'  => null,
                'refresh_token' => null,
                'status'        => 'disconnected',
            ]);
        }

        return redirect()->route('amazon.connect')
            ->with('success', 'Amazon account disconnected.');
    }

    // ─── Manual sync ──────────────────────────────────────────────

    /**
     * Manually trigger an order sync.
     */
    public function sync(Request $request): RedirectResponse
    {
        $account = auth()->user()->tenant->activeAmazonAccount();

        if (! $account) {
            return redirect()->route('amazon.connect')
                ->with('error', 'No connected Amazon account found.');
        }

        // ?limit=50 for quick test syncs, no param = full sync
        $limit = $request->filled('limit') ? (int) $request->limit : null;

        try {
            $synced = $this->apiService->syncOrders($account, limit: $limit);

            $msg = $limit
                ? "Test sync complete: fetched {$synced} orders (limit {$limit})."
                : "Full sync complete: {$synced} orders synced.";

            return redirect()->route('orders.index')->with('success', $msg);

        } catch (\Exception $e) {
            return redirect()->route('orders.index')
                ->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }
}