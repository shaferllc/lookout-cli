<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(name: 'login', description: 'Save API base URL and personal access token to ~/.lookout/config.json')]
final class LoginCommand extends Command
{
    public function __construct(
        private readonly ConfigStore $config,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('base-url', null, InputOption::VALUE_REQUIRED, 'Lookout instance URL (e.g. https://errors.example.com)')
            ->addOption('token', null, InputOption::VALUE_REQUIRED, 'Personal access token (non-interactive)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $base = (string) ($input->getOption('base-url') ?? '');
        if ($base === '') {
            $base = (string) (getenv('LOOKOUT_BASE_URL') ?: '');
        }
        if ($base === '') {
            $helper = $this->getHelper('question');
            $q = new Question('Lookout base URL (e.g. https://errors.example.com): ');
            $base = trim((string) $helper->ask($input, $output, $q));
        }
        if ($base === '') {
            $output->writeln('<error>Base URL is required.</error>');

            return Command::FAILURE;
        }

        $token = (string) ($input->getOption('token') ?? '');
        if ($token === '') {
            $helper = $this->getHelper('question');
            $question = new Question('API token: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);
            $token = trim((string) $helper->ask($input, $output, $question));
        }
        if ($token === '') {
            $output->writeln('<error>Token is required.</error>');

            return Command::FAILURE;
        }

        $this->config->write([
            'base_url' => rtrim($base, '/'),
            'api_token' => $token,
        ]);
        $output->writeln('<info>Saved credentials to '.$this->config->path().'</info>');

        return Command::SUCCESS;
    }
}
