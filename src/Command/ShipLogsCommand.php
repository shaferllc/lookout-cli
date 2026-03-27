<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Forwards plain-text lines to POST /api/ingest/log using the project API key (not the Sanctum token).
 * Example: tail -F /var/log/nginx/access.log | lookout ship-logs --base-url=… --api-key=…
 */
final class ShipLogsCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('ship-logs');
        $this->setDescription('Send log lines to Lookout (POST /api/ingest/log) with the project API key.');
        $this->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Lookout base URL, e.g. https://lookout.example.com (env LOOKOUT_BASE_URL)');
        $this->addOption('api-key', null, InputOption::VALUE_REQUIRED, 'Project ingest API key (env LOOKOUT_PROJECT_API_KEY or LOOKOUT_INGEST_API_KEY)');
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Read lines from this file; omit or use - for stdin');
        $this->addOption('source', null, InputOption::VALUE_REQUIRED, 'Source label stored in Lookout (default nginx.access)', 'nginx.access');
        $this->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Lines per request (max 200)', '100');
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'URL path for ingest (default /api/ingest/log)', '/api/ingest/log');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print JSON payloads instead of POSTing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $base = $this->optionString($input, 'base-url') ?: (string) (getenv('LOOKOUT_BASE_URL') ?: '');
        $base = rtrim(trim($base), '/');
        $key = $this->optionString($input, 'api-key') ?: (string) (getenv('LOOKOUT_PROJECT_API_KEY') ?: getenv('LOOKOUT_INGEST_API_KEY') ?: '');
        $source = $this->optionString($input, 'source') ?: 'nginx.access';
        $path = $this->optionString($input, 'path') ?: '/api/ingest/log';
        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }
        $batchSize = (int) ($this->optionString($input, 'batch-size') ?: '100');
        $batchSize = max(1, min(200, $batchSize));
        $dryRun = (bool) $input->getOption('dry-run');

        if ($base === '') {
            $output->writeln('<error>Set --base-url or LOOKOUT_BASE_URL.</error>');

            return Command::FAILURE;
        }
        if ($key === '') {
            $output->writeln('<error>Set --api-key, LOOKOUT_PROJECT_API_KEY, or LOOKOUT_INGEST_API_KEY (project API key from Lookout project settings).</error>');

            return Command::FAILURE;
        }

        $fileOpt = $input->getOption('file');
        $filePath = is_string($fileOpt) ? trim($fileOpt) : '';

        if ($filePath === '' || $filePath === '-') {
            $handle = STDIN;
            stream_set_blocking($handle, true);
        } else {
            if (! is_readable($filePath)) {
                $output->writeln('<error>File not readable: '.$filePath.'</error>');

                return Command::FAILURE;
            }
            $handle = fopen($filePath, 'rb');
            if ($handle === false) {
                $output->writeln('<error>Could not open: '.$filePath.'</error>');

                return Command::FAILURE;
            }
        }

        $client = $dryRun ? null : new Client([
            'base_uri' => $base,
            'timeout' => 45,
            'http_errors' => false,
            'headers' => [
                'X-Api-Key' => $key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        $batch = [];
        $totalLines = 0;
        $totalPosts = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");
                if ($line === '') {
                    continue;
                }
                $batch[] = $line;
                $totalLines++;
                if (count($batch) >= $batchSize) {
                    $ok = $this->flushBatch($client, $path, $source, $batch, $dryRun, $output);
                    if (! $ok) {
                        if (is_resource($handle) && $handle !== STDIN) {
                            fclose($handle);
                        }

                        return Command::FAILURE;
                    }
                    $totalPosts++;
                    $batch = [];
                }
            }
        } finally {
            if (is_resource($handle) && $handle !== STDIN) {
                fclose($handle);
            }
        }

        if ($batch !== []) {
            if (! $this->flushBatch($client, $path, $source, $batch, $dryRun, $output)) {
                return Command::FAILURE;
            }
            $totalPosts++;
        }

        if ($dryRun) {
            $output->writeln("<info>Dry run: would have sent {$totalLines} line(s) in {$totalPosts} request(s).</info>");
        } else {
            $output->writeln("<info>Sent {$totalLines} line(s) in {$totalPosts} request(s).</info>");
        }

        return Command::SUCCESS;
    }

    /**
     * @param  list<string>  $lines
     */
    private function flushBatch(?Client $client, string $ingestPath, string $source, array $lines, bool $dryRun, OutputInterface $output): bool
    {
        $body = ['lines' => $lines, 'source' => $source];
        if ($dryRun) {
            $output->writeln(json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return true;
        }
        if ($client === null) {
            return false;
        }

        try {
            $response = $client->post($ingestPath, ['json' => $body]);
        } catch (GuzzleException $e) {
            $output->writeln('<error>HTTP error: '.$e->getMessage().'</error>');

            return false;
        }

        $code = $response->getStatusCode();
        $raw = (string) $response->getBody();
        if ($code !== 202) {
            $output->writeln("<error>Lookout returned HTTP {$code}: {$raw}</error>");

            return false;
        }

        return true;
    }

    private function optionString(InputInterface $input, string $name): string
    {
        $v = $input->getOption($name);

        return is_string($v) ? trim($v) : '';
    }
}
