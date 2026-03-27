<?php

declare(strict_types=1);

namespace Lookout\Cli;

use Lookout\Cli\Command\CreateProjectCommand;
use Lookout\Cli\Command\DeleteProjectCommand;
use Lookout\Cli\Command\GetAuthenticatedUserCommand;
use Lookout\Cli\Command\GetErrorOccurrenceCommand;
use Lookout\Cli\Command\GetProjectCommand;
use Lookout\Cli\Command\GetProjectErrorCountCommand;
use Lookout\Cli\Command\GetProjectErrorOccurrenceCountCommand;
use Lookout\Cli\Command\IgnoreErrorCommand;
use Lookout\Cli\Command\InstallSkillCommand;
use Lookout\Cli\Command\ListErrorOccurrencesCommand;
use Lookout\Cli\Command\ListProjectErrorsCommand;
use Lookout\Cli\Command\ListProjectsCommand;
use Lookout\Cli\Command\LoginCommand;
use Lookout\Cli\Command\LogoutCommand;
use Lookout\Cli\Command\MonitoringStubCommand;
use Lookout\Cli\Command\OpenErrorCommand;
use Lookout\Cli\Command\ResolveErrorCommand;
use Lookout\Cli\Command\ShipLogsCommand;
use Lookout\Cli\Command\SnoozeErrorCommand;
use Lookout\Cli\Command\UnsnoozeErrorCommand;
use Symfony\Component\Console\Application;

final class LookoutApplication extends Application
{
    public function __construct(
        private readonly ConfigStore $configStore,
    ) {
        parent::__construct('Lookout', '1.0.0');

        $this->registerCommands();
    }

    public static function withDefaultConfig(): self
    {
        return new self(ConfigStore::default());
    }

    private function registerCommands(): void
    {
        $c = $this->configStore;

        $this->add(new LoginCommand($c));
        $this->add(new LogoutCommand($c));
        $this->add(new GetAuthenticatedUserCommand($c));
        $this->add(new ListProjectsCommand($c));
        $this->add(new CreateProjectCommand($c));
        $this->add(new GetProjectCommand($c));
        $this->add(new DeleteProjectCommand);
        $this->add(new ListProjectErrorsCommand($c));
        $this->add(new GetProjectErrorCountCommand($c));
        $this->add(new GetProjectErrorOccurrenceCountCommand($c));
        $this->add(new ListErrorOccurrencesCommand($c));
        $this->add(new GetErrorOccurrenceCommand($c));
        $this->add(new ResolveErrorCommand($c));
        $this->add(new OpenErrorCommand($c));
        $this->add(new IgnoreErrorCommand($c));
        $this->add(new SnoozeErrorCommand($c));
        $this->add(new UnsnoozeErrorCommand($c));
        $this->add(new InstallSkillCommand);
        $this->add(new ShipLogsCommand);
        foreach (MonitoringStubCommand::NAMES as $name) {
            $this->add(MonitoringStubCommand::named($name));
        }
    }
}
