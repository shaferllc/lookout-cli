<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'get-project', description: 'Show one project by ID')]
final class GetProjectCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('project-id', null, InputOption::VALUE_REQUIRED, 'Project ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getOption('project-id');
        $data = $this->client($input)->get('api/v1/projects/'.$id);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $d = $data['data'] ?? $data;
            if (! is_array($d)) {
                return;
            }
            foreach ($d as $k => $v) {
                if (is_array($v)) {
                    $output->writeln($k.': '.json_encode($v));

                    continue;
                }
                $output->writeln($k.': '.$v);
            }
        });
    }
}
