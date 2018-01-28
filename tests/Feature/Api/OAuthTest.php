<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OAuthTest extends ApiTestCase
{
    /**
     */
    public function testOauthLogin()
    {
        $data = [
            'grant_type' => 'password',
            'client_id' => env('AUTH_CLIENT_ID', ''),
            'client_secret' => env('AUTH_CLIENT_SECRET', ''),
            'username' => 'admin@example.com',
            'password' => 'password',
            'scope' => '',
        ];

        $loginResponse = $this->client->postRequest('/oauth/token', $data);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);
    }
}
