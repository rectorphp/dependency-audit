<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Auditor;

use Composer\Semver\VersionParser;
use Rector\DependencyAudit\Contract\AuditorInterface;
use Rector\DependencyAudit\Utils\JsonLoader;

final class RequiredPHPVersionAuditor implements AuditorInterface
{
    public function audit(string $repositoryDirectory): array
    {
        $composerJsonFilePath = $repositoryDirectory . '/composer.json';
        if (! file_exists($composerJsonFilePath)) {
            return [];
        }

        $composerJson = JsonLoader::loadFileToJson($composerJsonFilePath);

        $requiredPhpVersion = $composerJson['require']['php'] ?? null;

        // extract lower bound from constraint
        if (is_string($requiredPhpVersion)) {
            $versionParser = new VersionParser();
            $constraint = $versionParser->parseConstraints($requiredPhpVersion);

            $minPhpVersion = $constraint->getLowerBound()->getVersion();

            // get first 2 version

            $versionParts = explode('.', $minPhpVersion);
            if (count($versionParts) >= 2) {
                $minPhpVersion = $versionParts[0] . '.' . $versionParts[1];
            }
        } else {
            $minPhpVersion = '';
        }

        return [
            'min-php-version' => $minPhpVersion,
        ];
    }
}