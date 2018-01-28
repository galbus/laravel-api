<?php

namespace App\Lib;

use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Token;
use Illuminate\Support\Facades\DB;
use App\Lib\AuthProxyClient;
// http://esbenp.github.io/2017/03/19/modern-rest-api-laravel-part-4/

/**
 *
 */
class AuthProxy
{
    private $clientId;
    private $clientSecret;
    private $proxyBaseUrl;
    private $client = null;

    /**
     */
    public function __construct(Array $params) {
        $this->clientId = $params['clientId'];
        $this->clientSecret = $params['clientSecret'];
        $this->proxyBaseUrl = $params['proxyBaseUrl'];
    }

    /**
     * Login to API
     */
    public function login($credentials) {
         $data = [
            'grant_type' => 'password',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'scope' => '',
        ];

        return $this->proxyRequest($data);
    }

    /**
     * Logout from API
     */
    public function logout($user) {
        $accessTokenId = $user->token()->id;
        return $this->deleteUserTokens($accessTokenId);
    }

    /**
     * Refresh API Token
     */
    public function refreshToken($data) {
        $data = [
            'grant_type' => 'refresh_token',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $data['refreshToken'],
            'scope' => '',
        ];

        return $this->proxyRequest($data);
    }


    private function getClient() {
        if ($this->client === null) {
            // $this->client = new GuzzleHttpClient();
            $options = [
                'base_url' => $this->proxyBaseUrl
            ];
            $this->client = new AuthProxyClient($options);
        }
        return $this->client;
    }

    /**
     * Make request to Proxy for Authentication
     */
    private function proxyRequest($data) {
        return $this->getClient()->postRequest('/oauth/token', $data);
    }

    /**
     * Sets refresh token to revoked in database
     */
    private function revokeRefreshToken($user) {
        // this is pretty horrible but can't figure out how else to revoke the refresh token
        $accessTokenId = $user->token()->id;
        $repo = app()->make(RefreshTokenRepository::class);
        return $repo->revokeRefreshToken($refreshToken[0]->id);
    }

    /**
     * Remove tokens from database
     */
    private function deleteUserTokens($accessToken) {
        $deletedAccessToken = DB::delete('DELETE FROM oauth_access_tokens WHERE id = ? ', [$accessToken]);
        $deletedRefreshToken = DB::delete('DELETE FROM oauth_refresh_tokens WHERE access_token_id = ? ', [$accessToken]);

        return ($deletedAccessToken && $deletedRefreshToken);
    }
}
