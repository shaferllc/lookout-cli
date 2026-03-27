<?php

declare(strict_types=1);

namespace Lookout\Cli\Command;

use Lookout\Cli\AuthorizedCommand;
use Lookout\Cli\ConfigStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'get-error-occurrence', description: 'Show one occurrence (full detail)')]
final class GetErrorOccurrenceCommand extends AuthorizedCommand
{
    public function __construct(ConfigStore $config)
    {
        parent::__construct($config);
    }

    protected function configure(): void
    {
        $this->configureLookoutOptions();
        $this->addOption('occurrence-id', null, InputOption::VALUE_REQUIRED, 'Error event / occurrence ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getOption('occurrence-id');
        $data = $this->client($input)->get('api/v1/error-occurrences/'.$id);

        return $this->writeFormatted($input, $output, $data);
    }
}
