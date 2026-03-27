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

#[AsCommand(name: 'get-project-error-count', description: 'Count distinct error groups (fingerprints) for a project')]
final class GetProjectErrorCountCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'Project ULID');
        $this->addOption('status', null, InputOption::VALUE_REQUIRED, 'open|resolved|ignored|all');
        $this->addOption('time', null, InputOption::VALUE_REQUIRED, '24h|7d|30d');
        $this->addOption('level', null, InputOption::VALUE_REQUIRED, 'Log level');
        $this->addOption('environment', null, InputOption::VALUE_REQUIRED, 'Environment');
        $this->addOption('release', null, InputOption::VALUE_REQUIRED, 'Release');
        $this->addOption('search', null, InputOption::VALUE_REQUIRED, 'Search query');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pid = $this->requireUlidOption($input, $output, 'project-id', '--project-id');
        if ($pid === null) {
            return Command::FAILURE;
        }
        $query = $this->filterQuery($input);
        $data = $this->client($input)->get('api/v1/projects/'.$pid.'/error-count', $query);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $c = $data['data']['count'] ?? null;
            $output->writeln('<info>Distinct error groups: '.(string) $c.'</info>');
        });
    }

    /**
     * @return array<string, string>
     */
    private function filterQuery(InputInterface $input): array
    {
        $query = [];
        foreach (['status', 'time', 'level', 'environment', 'release', 'search'] as $k) {
            $v = (string) ($input->getOption($k) ?? '');
            if ($v !== '') {
                $query[$k] = $v;
            }
        }

        return $query;
    }
}
