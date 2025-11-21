<?php

declare(strict_types=1);

use Rector\DependencyAudit\Command\CloneComposerReposCommand;
use Symfony\Component\Console\Application;

require __DIR__ . '/../vendor/autoload.php';

$application = new Application();
$application->add(new CloneComposerReposCommand());

$exitCode = $application->run();

exit($exitCode);
