<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'install-skill', description: 'Write the Lookout agent skill for Cursor and other agents')]
final class InstallSkillCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file path', '.cursor/skills/lookout-cli/SKILL.md');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getOption('output');
        $dir = dirname($path);
        if ($dir !== '' && $dir !== '.' && ! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            $output->writeln('<error>Could not create directory: '.$dir.'</error>');

            return Command::FAILURE;
        }

        $source = dirname(__DIR__, 2).'/resources/agent-skill.md';
        if (! is_file($source)) {
            $output->writeln('<error>Missing bundled skill template: '.$source.'</error>');

            return Command::FAILURE;
        }
        $markdown = file_get_contents($source);
        if ($markdown === false || $markdown === '') {
            $output->writeln('<error>Could not read skill template.</error>');

            return Command::FAILURE;
        }

        if (file_put_contents($path, $markdown) === false) {
            $output->writeln('<error>Could not write '.$path.'</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Wrote '.$path.'</info>');

        return Command::SUCCESS;
    }
}
