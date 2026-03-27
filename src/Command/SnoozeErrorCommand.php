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

#[AsCommand(name: 'snooze-error', description: 'Snooze an error group')]
final class SnoozeErrorCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('error-id', null, InputOption::VALUE_REQUIRED, 'Representative error / occurrence ULID');
        $this->addOption('preset', null, InputOption::VALUE_REQUIRED, '1h|8h|24h|7d');
        $this->addOption('until', null, InputOption::VALUE_REQUIRED, 'Snooze until (ISO 8601 datetime)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $eid = $this->requireUlidOption($input, $output, 'error-id', '--error-id');
        if ($eid === null) {
            return Command::FAILURE;
        }
        $body = [];
        $preset = (string) ($input->getOption('preset') ?? '');
        $until = (string) ($input->getOption('until') ?? '');
        if ($preset !== '') {
            $body['preset'] = $preset;
        }
        if ($until !== '') {
            $body['until'] = $until;
        }

        $data = $this->client($input)->post('api/v1/errors/'.$eid.'/snooze', [], $body === [] ? null : $body);

        return $this->writeFormatted($input, $output, $data, function () use ($output, $data): void {
            $u = $data['data']['snoozed_until'] ?? '';
            $output->writeln('<info>Snoozed until '.(string) $u.'</info>');
        });
    }
}
