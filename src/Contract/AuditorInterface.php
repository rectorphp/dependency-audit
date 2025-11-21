<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Contract;

interface AuditorInterface
{
    public function audit(string $repositoryDirectory): array;
}