<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Contract;

interface AuditorInterface
{
    /**
     * @return array<string, mixed>
     */
    public function audit(string $repositoryDirectory): array;
}