<?php

declare(strict_types=1);

namespace Lookout\Cli\Tests;

use Lookout\Cli\Command\ShipLogsCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ShipLogsCommandTest extends TestCase
{
    public function test_dry_run_prints_json_batches(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lookout-ship-');
        $this->assertIsString($path);
        file_put_contents($path, "line one\nline two\n");

        try {
            $cmd = new ShipLogsCommand;
            $tester = new CommandTester($cmd);
            $tester->execute([
                '--base-url' => 'https://example.test',
                '--api-key' => 'k',
                '--batch-size' => '1',
                '--dry-run' => true,
                '--file' => $path,
            ]);
        } finally {
            @unlink($path);
        }

        $out = $tester->getDisplay();
        $this->assertStringContainsString('"line one"', $out);
        $this->assertStringContainsString('"line two"', $out);
        $this->assertSame(0, $tester->getStatusCode());
    }

    public function test_requires_base_url(): void
    {
        $prev = getenv('LOOKOUT_BASE_URL');
        putenv('LOOKOUT_BASE_URL');
        try {
            $cmd = new ShipLogsCommand;
            $tester = new CommandTester($cmd);
            $tester->execute([
                '--api-key' => 'k',
                '--dry-run' => true,
            ]);

            $this->assertNotSame(0, $tester->getStatusCode());
        } finally {
            if ($prev !== false && $prev !== '') {
                putenv('LOOKOUT_BASE_URL='.$prev);
            } else {
                putenv('LOOKOUT_BASE_URL');
            }
        }
    }
}
