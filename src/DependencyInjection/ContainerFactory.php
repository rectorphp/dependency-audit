<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\DependencyInjection;

use Illuminate\Container\Container;
use Rector\DependencyAudit\Command\AuditCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

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

        $container->when(AuditCommand::class)
            ->needs('$auditors')
            ->give([
                \Rector\DependencyAudit\Auditor\HasPHPStanAuditor::class,
                \Rector\DependencyAudit\Auditor\RequiredPHPVersionAuditor::class,
            ]);

        return $container;
    }
}
