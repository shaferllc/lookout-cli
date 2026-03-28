<?php

declare(strict_types=1);

namespace Lookout\Cli;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

final class LookoutClient
{
    private Client $http;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiToken,
        ?Client $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiToken,
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>|null  $json
     * @return array<string, mixed>
     */
    public function post(string $path, array $query = [], ?array $json = null): array
    {
        $opts = ['query' => $query];
        if ($json !== null) {
            $opts['json'] = $json;
        }

        return $this->request('POST', $path, $opts);
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $options): array
    {
        $path = '/'.ltrim($path, '/');
        try {
            $response = $this->http->request($method, $path, $options);
        } catch (ClientException $e) {
            $body = $e->hasResponse() ? (string) $e->getResponse()->getBody() : '';
            $decoded = json_decode($body, true);
            $msg = is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])
                ? $decoded['message']
                : ($body !== '' ? $body : $e->getMessage());
            if (is_array($decoded) && isset($decoded['billing_url']) && is_string($decoded['billing_url']) && $decoded['billing_url'] !== '') {
                $msg .= ' Billing: '.$decoded['billing_url'];
            }
            $status = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            throw new \RuntimeException('API error: '.$msg, $status, $e);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('HTTP error: '.$e->getMessage(), 0, $e);
        }

        $raw = (string) $response->getBody();
        if ($raw === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Invalid JSON response from API.');
        }

        return $data;
    }
}
