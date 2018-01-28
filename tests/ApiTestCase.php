<?php

namespace Tests;

use Tests\TestCase;
use Tests\Lib\HttpClient;

abstract class ApiTestCase extends TestCase
{
    use CreatesApplication;

    protected $client = null;

    public function setUp() {
        parent::setUp();
        $this->client = new HttpClient([
            'http_errors' => false,
            'cookies' => true,
            'base_uri' => env('TEST_API_URL', ''),
        ]);
    }

    public function tearDown() {
        parent::tearDown();
        $this->client = null;
    }

    protected function refreshToken($refreshToken) {
        $data = [
          'refreshToken' => $refreshToken,
        ];
        return $this->client->postRequest('/api/user/refreshToken', $data);
    }

    protected function logout($accessToken) {
        $data = [];
        return $this->client->postRequest('/api/user/logout', $data, $accessToken);
    }

    protected function login($username, $password) {
        $data = [
            'username' => $username,
            'password' => $password,
        ];
        return $this->client->postRequest('/api/user/login', $data);
    }

    protected function register($data) {
        return $this->client->postRequest('/api/user/register', $data);
    }

    protected function jsonFromResponse($response) {
        return json_decode($response->getBody(), true);
    }
}
