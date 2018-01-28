<?php

namespace Tests\Lib;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 *
 */
class HttpClient
{
    private $client = null;
    private $clientOptions = [
        'http_errors' => false,
        'base_uri' => '',
        'cookies' => false,
    ];
    private $response;
    /**
     */
    public function __construct(Array $params=[]) {
      $this->clientOptions = array_merge($this->clientOptions, $params);
    }

    public function getClient() {
        if ($this->client === null) {
            $this->client = new GuzzleHttpClient($this->clientOptions);
        }
        return $this->client;
    }

    /**
     * Make request to Proxy for Authentication
     */
    public function postRequest($path, $data, $accessToken='') {
        $client = $this->getClient();
        // fix for bug where it base_uri doesn't play nicely with localhost
        $uri = $client->getConfig('base_uri') . $path;

        $clientOptions = [
            'body' => json_encode($data),
            'headers' => $this->buildHeaders($accessToken),
            // 'cookies' => $client->getConfig('cookies'),
        ];

        return $client->request('POST', $uri, $clientOptions);
    }

    /**
     */
    public function getRequest($path, $data, $accessToken='') {
        $client = $this->getClient();
        // fix for bug where it base_uri doesn't play nicely with localhost
        $uri = $client->getConfig('base_uri') . $path;

        $clientOptions = [
            'body' => json_encode($data),
            'headers' => $this->buildHeaders($accessToken),
            // 'cookies' => $client->getConfig('cookies'),
        ];

        return $client->request('GET', $uri, $clientOptions);
    }



    public function jsonFromResponse($response) {
        return json_decode($response->getBody(), true);
    }


    /**
     * return Http headers to pass into phpunit request
     */
    public function buildHeaders($accessToken='') {
        $headers = [];
        if ($accessToken) {
            $headers['Authorization'] = 'Bearer '. $accessToken;
        }

        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        return $headers;
    }
}
