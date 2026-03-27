<?php

declare(strict_types=1);

namespace Lookout\Cli\Tests;

use Lookout\Cli\ConfigStore;
use PHPUnit\Framework\TestCase;

final class ConfigStoreTest extends TestCase
{
    public function test_write_and_read_roundtrip(): void
    {
        $dir = sys_get_temp_dir().'/lookout-cli-test-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        try {
            $store = new ConfigStore($dir);
            $store->write([
                'base_url' => 'https://example.test',
                'api_token' => 'secret-token',
            ]);
            $read = $store->read();
            $this->assertNotNull($read);
            $this->assertSame('https://example.test', $read['base_url']);
            $this->assertSame('secret-token', $read['api_token']);
        } finally {
            $path = $dir.'/.lookout/config.json';
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($dir.'/.lookout')) {
                rmdir($dir.'/.lookout');
            }
            rmdir($dir);
        }
    }

    public function test_clear_removes_file(): void
    {
        $dir = sys_get_temp_dir().'/lookout-cli-test-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        $store = new ConfigStore($dir);
        $store->write(['base_url' => 'https://a.test', 'api_token' => 't']);
        $this->assertFileExists($store->path());
        $store->clear();
        $this->assertFileDoesNotExist($store->path());
        if (is_dir($dir.'/.lookout')) {
            rmdir($dir.'/.lookout');
        }
        rmdir($dir);
    }
}
