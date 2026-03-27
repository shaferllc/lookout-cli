<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'get-authenticated-user', aliases: ['me'], description: 'Show the current user, organizations, and teams')]
final class GetAuthenticatedUserCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = $this->client($input)->get('api/v1/me');

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $d = $data['data'] ?? $data;
            if (! is_array($d)) {
                return;
            }
            $output->writeln('<info>'.($d['email'] ?? '').'</info> ('.($d['name'] ?? '').')');
            if (isset($d['organizations']) && is_array($d['organizations'])) {
                $output->writeln('');
                $output->writeln('Organizations:');
                foreach ($d['organizations'] as $o) {
                    if (is_array($o)) {
                        $output->writeln(sprintf('  %d  %s  (%s)  role=%s', $o['id'] ?? 0, $o['name'] ?? '', $o['slug'] ?? '', $o['role'] ?? ''));
                    }
                }
            }
        });
    }
}
