<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\ValueObject;

final class RequiredPackage
{
    /**
     * @var array<mixed, mixed>
     */
    private array $auditResults = [];

    public function __construct(
        private readonly string $name,
        private readonly string $sourceUrl,
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

    /**
     * @param mixed[] $auditResults
     */
    public function addAuditResults(array $auditResults): void
    {
        $this->auditResults = array_merge($this->auditResults, $auditResults);
    }

    /**
     * @return mixed[]
     */
    public function getAuditResults(): array
    {
        return $this->auditResults;
    }
}
