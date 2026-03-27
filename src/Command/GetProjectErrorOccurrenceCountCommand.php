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

#[AsCommand(name: 'get-project-error-occurrence-count', description: 'Count raw error occurrences in a time range')]
final class GetProjectErrorOccurrenceCountCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'Project ULID');
        $this->addOption('from', null, InputOption::VALUE_REQUIRED, 'Start date (ISO)');
        $this->addOption('to', null, InputOption::VALUE_REQUIRED, 'End date (ISO)');
        $this->addOption('time', null, InputOption::VALUE_REQUIRED, '24h|7d|30d (if from/to omitted)');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, 'Log level');
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, 'Environment');
        $this->addOption('release', null, InputOption::VALUE_REQUIRED, 'Release');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = $this->requireUlidOption($input, $output, 'project-id', '--project-id');
        if ($pid === null) {
            return Command::FAILURE;
        }
        $query = [];
        foreach (['from', 'to', 'time', 'level', 'environment', 'release'] as $k) {
            $v = (string) ($input->getOption($k) ?? '');
            if ($v !== '') {
                $query[$k] = $v;
            }
        }
        $data = $this->client($input)->get('api/v1/projects/'.$pid.'/error-occurrence-count', $query);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $c = $data['data']['count'] ?? null;
            $output->writeln('<info>Occurrences: '.(string) $c.'</info>');
        });
    }
}
