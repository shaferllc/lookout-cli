<?php

declare(strict_types=1);

namespace Lookout\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

abstract class AuthorizedCommand extends Command
{
    public function __construct(
        protected readonly ConfigStore $config,
    ) {
        parent::__construct();
    }

    protected function configureLookoutOptions(): void
    {
        $this->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'API base URL (overrides ~/.lookout/config.json and LOOKOUT_BASE_URL)');
        $this->addOption('token', null, InputOption::VALUE_REQUIRED, 'API token (overrides ~/.lookout/config.json and LOOKOUT_API_TOKEN)');
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON (for scripting)');
        $this->addOption('yaml', null, InputOption::VALUE_NONE, 'Output YAML');
    }

    protected function addPaginationOptions(): void
    {
        $this->addOption('page-number', null, InputOption::VALUE_REQUIRED, 'Page number (default 1)');
        $this->addOption('page-size', null, InputOption::VALUE_REQUIRED, 'Page size (max 100)');
    }

    protected function client(InputInterface $input): LookoutClient
    {
        $token = $this->stringOption($input, 'token') ?: (getenv('LOOKOUT_API_TOKEN') ?: '');
        $base = $this->stringOption($input, 'base-url') ?: (getenv('LOOKOUT_BASE_URL') ?: '');
        $conf = $this->config->read();
        if ($token === '') {
            $token = $conf['api_token'] ?? '';
        }
        if ($base === '') {
            $base = $conf['base_url'] ?? '';
        }
        if ($token === '' || $base === '') {
            throw new \RuntimeException('Not configured. Run `lookout login` or pass --base-url and --token.');
        }

        return new LookoutClient($base, $token);
    }

    protected function stringOption(InputInterface $input, string $name): string
    {
        $v = $input->getOption($name);

        return is_string($v) ? trim($v) : '';
    }

    /**
     * @param  array<string, mixed>|list<mixed>  $payload
     */
    protected function writeFormatted(InputInterface $input, OutputInterface $output, array $payload, ?callable $humanRenderer = null): int
    {
        if ($input->getOption('yaml')) {
            $output->write(Yaml::dump($payload, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));

            return Command::SUCCESS;
        }
        if ($input->getOption('json')) {
            $output->writeln(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return Command::SUCCESS;
        }
        if ($humanRenderer !== null) {
            $humanRenderer($output);

            return Command::SUCCESS;
        }
        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return Command::SUCCESS;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $headers
     */
    protected function renderTable(OutputInterface $output, array $headers, array $rows): void
    {
        $table = new Table($output);
        $table->setHeaders($headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $h) {
                $line[] = $row[$h] ?? '';
            }
            $table->addRow($line);
        }
        $table->render();
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    protected function mergePagination(InputInterface $input, array $query): array
    {
        $pn = $input->getOption('page-number');
        if ($pn !== null && $pn !== '') {
            $query['page']['number'] = (int) $pn;
        }
        $ps = $input->getOption('page-size');
        if ($ps !== null && $ps !== '') {
            $query['page']['size'] = min(100, max(1, (int) $ps));
        }

        return $query;
    }
}
