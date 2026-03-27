<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'list-projects', description: 'List projects you can access')]
final class ListProjectsCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('organization-id', null, InputOption::VALUE_REQUIRED, 'Filter by organization ULID');
        $this->addPaginationOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = [];
        $org = $input->getOption('organization-id');
        if ($org !== null && $org !== '') {
            $org = trim((string) $org);
            if (! self::isValidUlid($org)) {
                $output->writeln('<error>--organization-id must be a valid ULID.</error>');

                return Command::FAILURE;
            }
            $query['organization_id'] = $org;
        }
        $query = $this->mergePagination($input, $query);

        $data = $this->client($input)->get('api/v1/projects', $query);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $rows = $data['data'] ?? [];
            if (! is_array($rows)) {
                return;
            }
            $tableRows = [];
            foreach ($rows as $p) {
                if (! is_array($p)) {
                    continue;
                }
                $org = $p['organization'] ?? [];
                $tableRows[] = [
                    'id' => $p['id'] ?? '',
                    'name' => $p['name'] ?? '',
                    'slug' => $p['slug'] ?? '',
                    'organization' => is_array($org) ? ($org['name'] ?? '') : '',
                ];
            }
            $this->renderTable($output, ['id', 'name', 'slug', 'organization'], $tableRows);
            if (isset($data['meta']) && is_array($data['meta'])) {
                $m = $data['meta'];
                $output->writeln(sprintf(
                    '<comment>Page %s of %s — %s total</comment>',
                    $m['current_page'] ?? '?',
                    $m['last_page'] ?? '?',
                    $m['total'] ?? '?'
                ));
            }
        });
    }
}
