<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserAccountTest extends ApiTestCase
{
    protected function userAccount($accessToken) {
        $data = [];
        return $this->client->getRequest('/api/user/account', $data, $accessToken);
    }

    protected function updateUserAccount($data, $accessToken) {
        return $this->client->postRequest('/api/user/account', $data, $accessToken);
    }

    /**
     */
    public function testUnauthorisedUserAccount()
    {
        $userAccountResponse = $this->userAccount('');
        $this->assertEquals(401, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
    }

    /**
     */
    public function testUserAccount()
    {
        // Login - must be seeded
        $email = 'admin@example.com';
        $loginResponse = $this->login($email, 'password');
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);

        $userAccountResponse = $this->userAccount($loginCredentials['access_token']);
        $this->assertEquals(200, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('name', $userAccount);
        $this->assertEquals($email, $userAccount['email']);
    }

    /**
     */
    public function testUserAccountUpdate()
    {
        // Login - must be seeded
        $email = 'test@example.com';
        $loginResponse = $this->login($email, 'password');
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);

        $updateData = [
            'email'  => $email,
            'name' => 'Updated - ' . date('Ymd H:i:s')
        ];
        $userAccountResponse = $this->updateUserAccount($updateData, $loginCredentials['access_token']);
        $this->assertEquals(200, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('name', $userAccount);
        $this->assertEquals($userAccount['name'], $updateData['name']);
        $this->assertEquals($userAccount['email'], $updateData['email']);

        // go get the details to verify again
        $userAccountResponse = $this->userAccount($loginCredentials['access_token']);
        $this->assertEquals(200, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('name', $userAccount);
        $this->assertEquals($userAccount['name'], $updateData['name']);
        $this->assertEquals($userAccount['email'], $updateData['email']);
    }

    /**
     */
    public function testUserAccountUpdateErrors()
    {
        // Login - must be seeded
        $email = 'test@example.com';
        $loginResponse = $this->login($email, 'password');
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);

        $updateData = [];
        $userAccountResponse = $this->updateUserAccount($updateData, $loginCredentials['access_token']);
        $this->assertEquals(422, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('errors', $userAccount);
        $this->assertArrayHasKey('name', $userAccount['errors']);
        $this->assertArrayHasKey('email', $userAccount['errors']);

        $updateData = [
            'email'  => $email,
        ];
        $userAccountResponse = $this->updateUserAccount($updateData, $loginCredentials['access_token']);
        $this->assertEquals(422, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('errors', $userAccount);
        $this->assertArrayHasKey('name', $userAccount['errors']);

        $updateData = [
            'name'  => $email,
        ];
        $userAccountResponse = $this->updateUserAccount($updateData, $loginCredentials['access_token']);
        $this->assertEquals(422, $userAccountResponse->getStatusCode());

        $userAccount = $this->jsonFromResponse($userAccountResponse);
        $this->assertArrayHasKey('errors', $userAccount);
        $this->assertArrayHasKey('email', $userAccount['errors']);
    }
}
