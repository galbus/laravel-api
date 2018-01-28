<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends ApiTestCase
{
    /**
     */
    public function testinvalidLoginRequest()
    {
        $data = [
          ['', ''],
          ['blah', ''],
          ['', 'somepassword'],
        ];

        foreach ($data as $dataSet) {
            $loginResponse = $this->login($dataSet[0], $dataSet[1]);
            $loginResult = $this->jsonFromResponse($loginResponse);
            $errorMessage = sprintf("inputData=[%s]\n", implode('::', $dataSet));
            $this->assertEquals(400, $loginResponse->getStatusCode(), $errorMessage);
            $this->assertEquals('invalid_request', $loginResult['error'], $errorMessage);
        }
    }

    /**
     */
    public function testinvalidLogin()
    {
        $loginResponse = $this->login('admin@example.com', 'password2');
        $loginResult = $this->jsonFromResponse($loginResponse);

        $this->assertEquals(401, $loginResponse->getStatusCode());
        $this->assertEquals('invalid_credentials', $loginResult['error']);
    }

    /**
     */
    public function testLoginRefreshLogout()
    {
        // Login - must be seeded
        $loginResponse = $this->login('admin@example.com', 'password');
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);

        // // refresh token with token we just got
        $refreshResponse = $this->refreshToken($loginCredentials['access_token']);
        $this->assertEquals(200, $refreshResponse->getStatusCode());

        $credentials = $this->jsonFromResponse($refreshResponse);
        $this->assertArrayHasKey('expires_in', $credentials);
        $this->assertArrayHasKey('access_token', $credentials);
        $this->assertArrayNotHasKey('refresh_token', $credentials);
        $this->assertEquals('Bearer', $credentials['token_type']);

        // logout with login credentials should result in error
        $invalidLogoutResponse = $this->logout($loginCredentials['access_token']);
        $this->assertEquals(401, $invalidLogoutResponse->getStatusCode());

        // logout with correct credentials
        $logoutResponse = $this->logout($credentials['access_token']);
        $this->assertEquals(200, $logoutResponse->getStatusCode());

        $logoutResult = $this->jsonFromResponse($logoutResponse);
        $this->assertEquals('OK', $logoutResult['status']);

        // logout with previously used credentials should result in 401
        $logoutResponse = $this->logout($credentials['access_token']);
        $this->assertEquals(401, $logoutResponse->getStatusCode());

        $invalidLogoutResponse = $this->logout($loginCredentials['access_token']);
        $this->assertEquals(401, $invalidLogoutResponse->getStatusCode());
    }
}
