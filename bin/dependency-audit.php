<?php

declare(strict_types=1);

use Rector\DependencyAudit\Command\AuditCommand;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application();
$application->add(new AuditCommand());

$exitCode = $application->run();

exit($exitCode);
