<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\ValueObject;

final class RequiredPackage
{
    public function __construct(
        private string $name,
        private string $sourceUrl,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    public function getDirectoryName(): string
    {
        return str_replace('/', '-', $this->name);
    }
}
