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

        $versionParser = new VersionParser();
        $constraint = $versionParser->parseConstraints($requiredPhpVersion);

        dump($constraint);
        die;

        return [
            'min-php-version' => $constraint->getLowerBound()->getVersion()
        ];
    }
}