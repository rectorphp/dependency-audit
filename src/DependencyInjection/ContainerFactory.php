<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\DependencyInjection;

use Illuminate\Container\Container;
use Rector\DependencyAudit\Auditor\HasPHPStanAuditor;
use Rector\DependencyAudit\Auditor\RequiredPHPVersionAuditor;
use Rector\DependencyAudit\Command\AuditCommand;
use Rector\DependencyAudit\Contract\AuditorInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Webmozart\Assert\Assert;

final class ContainerFactory
{
    public function create(): Container
    {
        $container = new Container();

        $container->singleton(Application::class, function (Container $container) {
            $application = new Application();

            $auditCommand = $container->make(AuditCommand::class);
            $application->add($auditCommand);

            return $application;
        });

        $container->singleton(
            SymfonyStyle::class,
            static function (): SymfonyStyle {
                // use null output ofr tests to avoid printing
                $consoleOutput = defined('PHPUNIT_COMPOSER_INSTALL') ? new NullOutput() : new ConsoleOutput();
                return new SymfonyStyle(new ArrayInput([]), $consoleOutput);
            }
        );

        $this->registerAuditor($container, HasPHPStanAuditor::class);
        $this->registerAuditor($container, RequiredPHPVersionAuditor::class);

        $container->when(AuditCommand::class)
            ->needs('$auditors')
            ->giveTagged(AuditorInterface::class);

        return $container;
    }

    private function registerAuditor(Container $container, string $class): void
    {
        Assert::isAOf($class, AuditorInterface::class);

        $container->singleton($class);
        $container->tag($class, AuditorInterface::class);
    }
}
