<?php

namespace App\Lib;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 *
 */
class AuthProxyClient
{
    private $client = null;
    private $clientOptions = [
        'http_errors' => false,
        'base_url' => '',
    ];
    /**
     */
    public function __construct(Array $params=[]) {
      $this->clientOptions = array_merge($this->clientOptions, $params);
    }

    private function getClient() {
        if ($this->client === null) {
            $this->client = new GuzzleHttpClient($this->clientOptions);
        }
        return $this->client;
    }

    private function buildHeaders() {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        return $headers;
    }

    private function jsonFromResponse($response) {
        return json_decode($response->getBody(), true);
    }

    /**
     * Make request to Proxy for Authentication
     */
    public function postRequest($url, $data, $authToken='') {
        $url = $this->clientOptions['base_url'] . $url;
        $clientOptions = [
            'http_errors' => false,
            'body' => json_encode($data),
            'headers' => $this->buildHeaders(),
        ];

        $response = $this->getClient()->request('POST', $url, $clientOptions);

        // pass through data and status code from endpoint
        return [
            // add headers
            'status' => $response->getStatusCode(),
            'data' => $this->jsonFromResponse($response),
        ];
    }
}
