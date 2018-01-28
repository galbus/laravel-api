<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForgotPasswordTest extends ApiTestCase
{
    public function setUp() {
        parent::setUp();
        $this->forgotPasswordEmail = env('TEST_EMAIL', 'test@example.com');
    }

    protected function forgotPassword($data) {
        return $this->client->postRequest('/api/user/password/forgot', $data);
    }

    protected function resetPassword($data) {
        return $this->client->postRequest('/api/user/password/reset', $data);
    }

    /**
     */
    public function testForgotPasswordBadEmail()
    {
        $dataSet = [
            [],
            ['email' => ''],
            ['email' => 'fas'],
        ];

        foreach ($dataSet as $i => $data) {
            $response = $this->forgotPassword($data);
            $this->assertEquals(422, $response->getStatusCode());

            $result = $this->jsonFromResponse($response);
        }
    }

    /**
     */
    public function testForgotPasswordInvalidEmail()
    {
        $data = [
            'email' => 'xxx.' . $this->forgotPasswordEmail, // valid email
        ];

        $response = $this->forgotPassword($data);
        $this->assertEquals(200, $response->getStatusCode());

        $result = $this->jsonFromResponse($response);
        $this->assertContains($data['email'], json_encode($result));
    }

    /**
     */
    public function testForgotResetPassword()
    {
        $data = [
            'email' => $this->forgotPasswordEmail,
        ];

        $response = $this->forgotPassword($data);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->jsonFromResponse($response);
        $this->assertContains($data['email'], json_encode($result));

        if (empty($result['token'])) {
            // no token returned
            return;
        }

        $badResetData = [
            'email' => $this->forgotPasswordEmail,
            'token' => $result['token'] . 'ss',
            'password' => 'password.changed',
            'password_confirmation' => 'password.changed',
        ];

        $response = $this->resetPassword($badResetData);
        $this->assertEquals(422, $response->getStatusCode());

        $badResetData = [
            'email' => 'someOtherEmail@example.com',
            'token' => $result['token'],
            'password' => 'password.changed',
            'password_confirmation' => 'password.changed',
        ];

        $response = $this->resetPassword($badResetData);
        $this->assertEquals(422, $response->getStatusCode());

        $resetData = [
            'email' => $this->forgotPasswordEmail,
            'token' => $result['token'],
            'password' => 'password.changed',
            'password_confirmation' => 'password.changed',
        ];

        $response = $this->resetPassword($resetData);
        $this->assertEquals(200, $response->getStatusCode());
        $result = $this->jsonFromResponse($response);


        $loginResponse = $this->login($resetData['email'], $resetData['password']);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);
    }
}
