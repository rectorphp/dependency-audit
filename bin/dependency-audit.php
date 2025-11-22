<?php

declare(strict_types=1);

use Rector\DependencyAudit\Command\AuditCommand;
use Rector\DependencyAudit\DependencyInjection\ContainerFactory;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$containerFactory = new ContainerFactory();
$container = $containerFactory->create();

/** @var Application $applcation */
$application = $container->get(Application::class);
$exitCode = $application->run();

exit($exitCode);
