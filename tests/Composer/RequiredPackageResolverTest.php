<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Tests\Composer;

use PHPUnit\Framework\TestCase;
use Rector\DependencyAudit\Composer\RequiredPackageResolver;
use Rector\DependencyAudit\ValueObject\RequiredPackage;

final class RequiredPackageResolverTest extends TestCase
{
    public function test(): void
    {
        $requiredPackageResolver = new RequiredPackageResolver();

        $requiredPackages = $requiredPackageResolver->resolve(__DIR__ . '/../..');

        $this->assertGreaterThan(20, count($requiredPackages));
        $this->assertContainsOnlyInstancesOf(RequiredPackage::class, $requiredPackages);
    }
}
