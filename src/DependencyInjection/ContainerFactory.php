<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\DependencyInjection;

use Illuminate\Container\Container;
use Rector\DependencyAudit\Command\AuditCommand;
use Symfony\Component\Console\Application;

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

        $container->when(AuditCommand::class)
            ->needs('$auditors')
            ->give([
                \Rector\DependencyAudit\Auditor\HasPHPStanAuditor::class,
                \Rector\DependencyAudit\Auditor\RequiredPHPVersionAuditor::class,
            ]);

        return $container;
    }
}
