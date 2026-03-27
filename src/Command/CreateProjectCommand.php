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

#[AsCommand(name: 'create-project', description: 'Create a project in an organization')]
final class CreateProjectCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('organization-id', null, InputOption::VALUE_REQUIRED, 'Organization ID');
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Project name');
        $this->addOption('team-ids', null, InputOption::VALUE_REQUIRED, 'Comma-separated team IDs (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $orgId = (int) $input->getOption('organization-id');
        if ($orgId < 1) {
            $output->writeln('<error>--organization-id must be a positive integer.</error>');

            return Command::FAILURE;
        }
        $name = trim((string) $input->getOption('name'));
        if ($name === '') {
            $output->writeln('<error>--name is required.</error>');

            return Command::FAILURE;
        }
        $body = [
            'name' => $name,
            'organization_id' => $orgId,
        ];
        $teams = (string) ($input->getOption('team-ids') ?? '');
        if ($teams !== '') {
            $body['team_ids'] = array_map('intval', array_filter(array_map('trim', explode(',', $teams))));
        }

        $data = $this->client($input)->post('api/v1/projects', [], $body);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $d = $data['data'] ?? $data;
            if (! is_array($d)) {
                return;
            }
            $output->writeln('<info>Created project '.$d['id'].': '.$d['name'].'</info>');
            if (isset($d['api_key'])) {
                $output->writeln('<comment>API key (copy now; not shown again): '.$d['api_key'].'</comment>');
            }
        });
    }
}
