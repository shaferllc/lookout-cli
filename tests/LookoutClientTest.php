<?php

declare(strict_types=1);

namespace Lookout\Cli\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Lookout\Cli\LookoutClient;
use PHPUnit\Framework\TestCase;

final class LookoutClientTest extends TestCase
{
    public function test_get_decodes_json(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"data":{"ok":true}}'),
        ]);
        $client = new LookoutClient('https://api.test', 'tok', new Client([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://api.test',
            'headers' => [
                'Authorization' => 'Bearer tok',
                'Accept' => 'application/json',
            ],
        ]));

        $out = $client->get('api/v1/me');
        $this->assertSame(['data' => ['ok' => true]], $out);
    }

    public function test_client_exception_includes_message(): void
    {
        $mock = new MockHandler([
            new Response(401, [], '{"message":"Unauthenticated."}'),
        ]);
        $client = new LookoutClient('https://api.test', 'bad', new Client([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'https://api.test',
            'headers' => [
                'Authorization' => 'Bearer bad',
                'Accept' => 'application/json',
            ],
        ]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unauthenticated');
        $client->get('api/v1/me');
    }
}
