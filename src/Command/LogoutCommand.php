<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'logout', description: 'Remove saved ~/.lookout/config.json credentials')]
final class LogoutCommand extends Command
{
    public function __construct(
        private readonly ConfigStore $config,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->config->clear();
        $output->writeln('<info>Removed local Lookout CLI credentials.</info>');

        return Command::SUCCESS;
    }
}
