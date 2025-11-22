<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Tests\Composer;

use PHPUnit\Framework\TestCase;

final class RequiredPackageResolverTest extends TestCase
{
    public function test(): void
    {
        $requiredPackageResolver = new \Rector\DependencyAudit\Composer\RequiredPackageResolver();

        $requiredPackages = $requiredPackageResolver->resolve(__DIR__ . '/../..');

        dump($requiredPackages);
    }
}
