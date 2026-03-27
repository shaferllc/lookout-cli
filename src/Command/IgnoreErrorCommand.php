<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ignore-error', description: 'Ignore an error group')]
final class IgnoreErrorCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('error-id', null, InputOption::VALUE_REQUIRED, 'Representative error / event ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eid = (int) $input->getOption('error-id');
        $data = $this->client($input)->post('api/v1/errors/'.$eid.'/ignore', []);

        return $this->writeFormatted($input, $output, $data, function () use ($output): void {
            $output->writeln('<info>Ignored.</info>');
        });
    }
}
