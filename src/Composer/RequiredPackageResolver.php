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

        // required packages
        $packagesData = $lockData['packages'] ?? [];
        if ($packagesData === []) {
            return [];
        }

        foreach ($packagesData as $packagesDataItem) {
            dump($packagesDataItem);
        }

        die;

        // create value objects first

        // remove symfony/* packages, as they share the same code quality, no need to check 35 split packages
        // keep output informative and focused on non framework packages instead
        $packagesWithoutSymfony = array_filter($packages, fn (array $package) => ! str_starts_with($package['name'] ?? '', 'symfony'));

        // remove "psr" packages
        $packagesWithoutSymfony = array_filter(
            $packagesWithoutSymfony,
            fn (array $package) => ! str_starts_with($package['name'] ?? '', 'psr/')
        );

        if ($packages !== $packagesWithoutSymfony) {
            $symfonyStyle->write(sprintf(
                '<fg=green>Skipping %d Symfony packages to avoid repeated single-framework results.</>',
                count($packages) - count($packagesWithoutSymfony)
            ));

            $symfonyStyle->newLine();
        }

    }
}