<?php

namespace App\Services\Amazon;

use App\Models\AmazonAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Handles the Amazon SP-API Login with Amazon (LWA) OAuth 2.0 flow.
 *
 * Flow:
 *  1. redirectToAmazon()  → seller lands on Seller Central to grant consent
 *  2. handleCallback()    → Amazon redirects back with `spapi_oauth_code`
 *  3. exchangeCodeForTokens() → swap code for access + refresh tokens
 *  4. getSellerInfo()     → fetch seller ID & marketplace from SP-API
 */
class SpApiOAuthService
{
    // Amazon LWA token endpoint
    private const LWA_TOKEN_URL = 'https://api.amazon.com/auth/o2/token';

    // SP-API endpoint to get seller participation (to confirm seller ID)
    private const SP_API_BASE = 'https://sellingpartnerapi-na.amazon.com';

    // ─── OAuth Step 1: Build authorization URL ────────────────────

    /**
     * Generate the Amazon Seller Central authorization URL.
     *
     * The seller is redirected here to grant your app access.
     * After approval, Amazon redirects to your redirect_uri with:
     *   - spapi_oauth_code (authorization code)
     *   - state (your CSRF token)
     *   - selling_partner_id (seller ID)
     */
    public function buildAuthorizationUrl(string $state): string
    {
        $params = http_build_query([
            'application_id' => config('amazon-sp-api.application_id'),
            'state'          => $state,
            'redirect_uri'   => config('amazon-sp-api.redirect_uri'),
            'version'        => 'beta', // use 'production' for live app
        ]);

        return 'https://sellercentral.amazon.com/apps/authorize/consent?' . $params;
    }

    // ─── OAuth Step 2: Exchange code for tokens ───────────────────

    /**
     * Exchange the authorization code for LWA access + refresh tokens.
     *
     * @throws \Exception on failure
     */
    public function exchangeCodeForTokens(string $code, AmazonAccount $account): void
    {
        $response = Http::asForm()->post(self::LWA_TOKEN_URL, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->getClientId($account),
            'client_secret' => $this->getClientSecret($account),
            'redirect_uri'  => config('amazon-sp-api.redirect_uri'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to exchange auth code: ' . $response->body());
        }

        $data = $response->json();

        $account->update([
            'access_token'             => $data['access_token'],
            'refresh_token'            => $data['refresh_token'],
            'access_token_expires_at'  => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);
    }

    // ─── Token refresh ────────────────────────────────────────────

    /**
     * Use the refresh token to get a new access token.
     * Call this when isAccessTokenExpired() is true.
     *
     * @throws \Exception on failure
     */
    public function refreshAccessToken(AmazonAccount $account): string
    {
        $response = Http::asForm()->post(self::LWA_TOKEN_URL, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $account->refresh_token,
            'client_id'     => $this->getClientId($account),
            'client_secret' => $this->getClientSecret($account),
        ]);

        if ($response->failed()) {
            $account->markAsError('Token refresh failed: ' . $response->body());
            throw new \Exception('Failed to refresh token: ' . $response->body());
        }

        $data = $response->json();

        $account->update([
            'access_token'            => $data['access_token'],
            'access_token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
        ]);

        return $data['access_token'];
    }

    // ─── Get valid access token (auto-refresh) ────────────────────

    /**
     * Returns a valid access token, refreshing if expired.
     */
    public function getValidAccessToken(AmazonAccount $account): string
    {
        if ($account->isAccessTokenExpired()) {
            return $this->refreshAccessToken($account);
        }

        return $account->access_token;
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function getClientId(AmazonAccount $account): string
    {
        // Per-account credentials override global config
        return $account->lwa_client_id ?? config('amazon-sp-api.lwa_client_id');
    }

    private function getClientSecret(AmazonAccount $account): string
    {
        return $account->lwa_client_secret ?? config('amazon-sp-api.lwa_client_secret');
    }
}
