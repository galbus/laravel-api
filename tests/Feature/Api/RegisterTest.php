<?php

namespace Tests\Feature\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends ApiTestCase
{
    /**
     */
    public function testRegisterMissingFields()
    {
        $dataSets = [
            [
                'missing' => ['name', 'email', 'password'],
                'data' => []
            ],
            [
                'missing' => ['name'],
                'data' => [
                  // 'name' => 'test.register',
                  'email' => 'test.register@example.com',
                  'password' => 'test.register.password',
                  'password_confirmation' => 'test.register.password',
                ],
            ],
            [
                'missing' => ['email'],
                'data' => [
                    'name' => 'test.register',
                    // 'email' => 'test.register@example.com',
                    'password' => 'test.register.password',
                    'password_confirmation' => 'test.register.password',
                ],
            ],
            [
                'missing' => ['password'],
                'data' => [
                    'name' => 'test.register',
                    'email' => 'test.register@example.com',
                    // 'password' => 'test.register.password',
                    'password_confirmation' => 'test.register.password',
                ],
            ],
            [
                'missing' => ['password'],
                'data' => [
                    'name' => 'test.register',
                    'email' => 'test.register@example.com',
                    'password' => 'test.register.password',
                    // 'password_confirmation' => 'test.register.password',
                ]
            ],
        ];

        foreach ($dataSets as $i => $dataSet) {
            $registerResponse = $this->register($dataSet['data']);
            $this->assertEquals(422, $registerResponse->getStatusCode());

            $result = $this->jsonFromResponse($registerResponse);

            foreach ($dataSet['missing'] as $key) {
                $message = 'index=' . $i . ' :: key=' .  $key;
                $this->assertArrayHasKey($key, $result['errors'], $message);
            }
        }
    }

    /**
     */
    public function testRegister()
    {
        $name = 'test.register.'. time();
        $data = [
            'name' => $name,
            'email' => $name . '@example.com',
            'password' => 'test.register.password',
            'password_confirmation' => 'test.register.password',
        ];

        $registerResponse = $this->register($data);
        $this->assertEquals(200, $registerResponse->getStatusCode());

        $result = $this->jsonFromResponse($registerResponse);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['email'], $result['email']);
    }

    /**
     */
    public function testRegisterDuplicate()
    {
        $name = 'test.register.duplicate.'. time();
        $data = [
            'name' => $name,
            'email' => $name . '@example.com',
            'password' => 'test.register.password',
            'password_confirmation' => 'test.register.password',
        ];

        $registerResponse = $this->register($data);
        $this->assertEquals(200, $registerResponse->getStatusCode());

        $result = $this->jsonFromResponse($registerResponse);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['email'], $result['email']);

        $registerResponse = $this->register($data);
        $this->assertEquals(422, $registerResponse->getStatusCode());
        $result = $this->jsonFromResponse($registerResponse);
        $this->assertArrayHasKey('email', $result['errors']);
        $this->assertContains('The email has already been taken', json_encode($result));
    }

    /**
     */
    public function testRegisterLogin()
    {
        $name = 'test.register.login.'. time();
        $data = [
            'name' => $name,
            'email' => $name . '@example.com',
            'password' => 'test.register.password',
            'password_confirmation' => 'test.register.password',
        ];

        $registerResponse = $this->register($data);
        $this->assertEquals(200, $registerResponse->getStatusCode());

        $result = $this->jsonFromResponse($registerResponse);

        $this->assertArrayHasKey('id', $result);
        $this->assertEquals($data['name'], $result['name']);
        $this->assertEquals($data['email'], $result['email']);

        // Login - must be seeded
        $loginResponse = $this->login($data['email'], $data['password']);
        $this->assertEquals(200, $loginResponse->getStatusCode());

        $loginCredentials = $this->jsonFromResponse($loginResponse);
        $this->assertArrayHasKey('expires_in', $loginCredentials);
        $this->assertArrayHasKey('access_token', $loginCredentials);
        $this->assertArrayNotHasKey('refresh_token', $loginCredentials);
        $this->assertEquals('Bearer', $loginCredentials['token_type']);
    }
}
