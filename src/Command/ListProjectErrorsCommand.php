<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'list-project-errors', description: 'List grouped errors (fingerprints) for a project')]
final class ListProjectErrorsCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addPaginationOptions();
        $this->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'Project ID');
        $this->addOption('status', null, InputOption::VALUE_REQUIRED, 'open|resolved|ignored|all');
        $this->addOption('time', null, InputOption::VALUE_REQUIRED, '24h|7d|30d');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, 'Log level filter');
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, 'Environment filter');
        $this->addOption('release', null, InputOption::VALUE_REQUIRED, 'Release filter');
        $this->addOption('search', null, InputOption::VALUE_REQUIRED, 'Search query (structured directives supported by API)');
        $this->addOption('fingerprint', null, InputOption::VALUE_REQUIRED, 'Single fingerprint');
        $this->addOption('filter-status', null, InputOption::VALUE_REQUIRED, 'Alias of --status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = (int) $input->getOption('project-id');
        $query = [];
        $status = (string) ($input->getOption('filter-status') ?: $input->getOption('status') ?: '');
        if ($status !== '') {
            $query['status'] = $status;
        }
        foreach (['time', 'level', 'environment', 'release', 'search', 'fingerprint'] as $k) {
            $v = (string) ($input->getOption($k) ?? '');
            if ($v !== '') {
                $query[$k] = $v;
            }
        }
        $query = $this->mergePagination($input, $query);

        $data = $this->client($input)->get('api/v1/projects/'.$pid.'/errors', $query);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $rows = $data['data'] ?? [];
            if (! is_array($rows)) {
                return;
            }
            $tableRows = [];
            foreach ($rows as $r) {
                if (! is_array($r)) {
                    continue;
                }
                $msg = (string) ($r['message'] ?? '');
                if (strlen($msg) > 60) {
                    $msg = substr($msg, 0, 57).'...';
                }
                $fp = (string) ($r['fingerprint'] ?? '');
                if (strlen($fp) > 24) {
                    $fp = substr($fp, 0, 21).'...';
                }
                $tableRows[] = [
                    'id' => $r['id'] ?? '',
                    'count' => $r['occurrence_count'] ?? '',
                    'status' => $r['status'] ?? '',
                    'level' => $r['level'] ?? '',
                    'last_seen' => $r['last_seen_at'] ?? '',
                    'fingerprint' => $fp,
                    'message' => $msg,
                ];
            }
            $this->renderTable($output, ['id', 'count', 'status', 'level', 'last_seen', 'fingerprint', 'message'], $tableRows);
            if (isset($data['meta']) && is_array($data['meta'])) {
                $m = $data['meta'];
                $output->writeln(sprintf(
                    '<comment>Page %s/%s — %s groups total</comment>',
                    $m['current_page'] ?? '?',
                    $m['last_page'] ?? '?',
                    $m['total'] ?? '?'
                ));
            }
        });
    }
}
