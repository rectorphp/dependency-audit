<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Composer;

use Rector\DependencyAudit\Utils\JsonLoader;
use Rector\DependencyAudit\ValueObject\RequiredPackage;
use Webmozart\Assert\Assert;

final class RequiredPackageResolver
{
    /**
     * @return RequiredPackage[]
     */
    public function resolve(string $projectDirectory): array
    {
        // 1. load installed packages
        $installedJsonFilePath = $projectDirectory . '/vendor/composer/installed.json';
        $installedJson = JsonLoader::loadFileToJson($installedJsonFilePath);

        $packagesData = $installedJson['packages'] ?? [];
        if ($packagesData === []) {
            return [];
        }

        $devPackageNames = $installedJson['dev-package-names'] ?? [];

        $requiredPackageNames = $this->createValueObjects($packagesData, $devPackageNames);

        $usefulPackages = array_filter(
            $requiredPackageNames,
            function (RequiredPackage $package): bool {
                // remove symfony/* packages, as they share the same code quality, no need to check 35 split packages
                // keep output informative and focused on non framework packages instead
                if (str_starts_with($package->getName(), 'symfony/')) {
                    return false;
                }

                return ! str_starts_with($package->getName(), 'psr/');
            }
        );

        return $usefulPackages;
    }

    /**
     * @param mixed[] $packagesData
     * @param mixed[] $devPackageNames
     *
     * @return RequiredPackage[]
     */
    private function createValueObjects(array $packagesData, array $devPackageNames): array
    {
        Assert::allString($devPackageNames);

        $requiredPackages = [];
        foreach ($packagesData as $packagesDataItem) {
            if (! isset($packagesDataItem['name'])) {
                continue;
            }

            // skip dev packages, as they are not required in production
            if (in_array($packagesDataItem['name'], $devPackageNames, true)) {
                continue;
            }

            if (! isset($packagesDataItem['source']['url'])) {
                continue;
            }

            $requiredPackages[] = new RequiredPackage(
                $packagesDataItem['name'],
                $packagesDataItem['source']['url']
            );
        }

        return $requiredPackages;
    }
}
