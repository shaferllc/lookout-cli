<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'list-error-occurrences', description: 'List occurrences for an error (representative error event ID)')]
final class ListErrorOccurrencesCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addPaginationOptions();
        $this->addOption('error-id', null, InputOption::VALUE_REQUIRED, 'Representative error / event ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eid = (int) $input->getOption('error-id');
        $query = $this->mergePagination($input, []);
        $data = $this->client($input)->get('api/v1/errors/'.$eid.'/occurrences', $query);

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
                $tableRows[] = [
                    'id' => $r['id'] ?? '',
                    'level' => $r['level'] ?? '',
                    'created_at' => $r['created_at'] ?? '',
                    'message' => strlen((string) ($r['message'] ?? '')) > 50
                        ? substr((string) ($r['message'] ?? ''), 0, 47).'...'
                        : (string) ($r['message'] ?? ''),
                ];
            }
            $this->renderTable($output, ['id', 'level', 'created_at', 'message'], $tableRows);
            if (isset($data['meta']) && is_array($data['meta'])) {
                $m = $data['meta'];
                $output->writeln(sprintf(
                    '<comment>Page %s/%s — %s total</comment>',
                    $m['current_page'] ?? '?',
                    $m['last_page'] ?? '?',
                    $m['total'] ?? '?'
                ));
            }
        });
    }
}
