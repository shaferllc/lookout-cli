<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'delete-project', description: 'Delete a project (not available via API — use the web UI)')]
final class DeleteProjectCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<comment>Lookout does not expose project deletion on the REST API yet.</comment>');
        $output->writeln('Open the project in the app and delete it from settings.');

        return Command::FAILURE;
    }
}
