<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Composer;

use Rector\DependencyAudit\Utils\JsonLoader;
use Rector\DependencyAudit\ValueObject\RequiredPackage;

final class RequiredPackageResolver
{
    /**
     * @return RequiredPackage[]
     */
    public function resolve(string $projectDirectory): array
    {
        $installedJsonFilePath = $projectDirectory . '/vendor/composer/installed.json';
        $lockData = JsonLoader::loadFileToJson($installedJsonFilePath);

        $packagesData = $lockData['packages'] ?? [];
        if ($packagesData === []) {
            return [];
        }

        $requiredPackages = $this->createValueObjects($packagesData);

        $usefulPackages = array_filter(
            $requiredPackages,
            function (RequiredPackage $package): bool {
                // remove symfony/* packages, as they share the same code quality, no need to check 35 split packages
                // keep output informative and focused on non framework packages instead
                if (str_starts_with($package->getName(), 'symfony/')) {
                    return false;
                }

                if (str_starts_with($package->getName(), 'laravel/')) {
                    return false;
                }

                return ! str_starts_with($package->getName(), 'psr/');
            }
        );

        return $usefulPackages;
    }

    /**
     * @param mixed[] $packagesData
     * @return RequiredPackage[]
     */
    private function createValueObjects(array $packagesData): array
    {
        $requiredPackages = [];
        foreach ($packagesData as $packagesDataItem) {
            if (!isset($packagesDataItem['name'])) {
                continue;
            }

            if (!isset($packagesDataItem['source']['url'])) {
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
