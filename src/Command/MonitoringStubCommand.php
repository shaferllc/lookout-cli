<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Placeholders for performance-monitoring commands until Lookout exposes an equivalent API.
 */
final class MonitoringStubCommand extends Command
{
    public const NAMES = [
        'get-monitoring-summary',
        'list-monitoring-aggregations',
        'get-monitoring-time-series',
        'get-monitoring-aggregation',
        'list-aggregation-traces',
        'get-trace',
    ];

    public static function named(string $name): self
    {
        $cmd = new self;
        $cmd->setName($name);
        $cmd->setDescription('Performance monitoring is not available in Lookout yet (no API).');

        return $cmd;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>Lookout does not yet offer a performance monitoring API for these commands.</comment>');
        $output->writeln('Use distributed traces on individual error events in the web UI, or watch the roadmap.');

        return Command::FAILURE;
    }
}
