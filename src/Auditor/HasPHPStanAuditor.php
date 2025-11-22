<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Auditor;

use Rector\DependencyAudit\Contract\AuditorInterface;
use Rector\DependencyAudit\Utils\JsonLoader;

final class HasPHPStanAuditor implements AuditorInterface
{
    public function audit(string $repositoryDirectory): array
    {
        $composerJsonFilePath = $repositoryDirectory . '/composer.json';
        if (! file_exists($composerJsonFilePath)) {
            return [];
        }

        $composerJson = JsonLoader::loadFileToJson($composerJsonFilePath);

        $hasPhpstan = isset($composerJson['require-dev']['phpstan/phpstan']);

        return [
            'has-phpstan' => $hasPhpstan ? 'yes' : 'no',
        ];
    }
}