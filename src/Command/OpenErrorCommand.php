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

#[AsCommand(name: 'open-error', aliases: ['unresolve-error'], description: 'Reopen an error group')]
final class OpenErrorCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('error-id', null, InputOption::VALUE_REQUIRED, 'Representative error / occurrence ULID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eid = $this->requireUlidOption($input, $output, 'error-id', '--error-id');
        if ($eid === null) {
            return Command::FAILURE;
        }
        $data = $this->client($input)->post('api/v1/errors/'.$eid.'/open', []);

        return $this->writeFormatted($input, $output, $data, function () use ($output): void {
            $output->writeln('<info>Reopened.</info>');
        });
    }
}
