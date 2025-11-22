<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Auditor;

use Nette\Utils\FileSystem;
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
        $level = '-';

        // fallback check
        if (file_exists($repositoryDirectory . '/phpstan.neon')) {
            $hasPhpstan = true;

            // parse level: x out of this file
            $phpstanNeonContent = FileSystem::read($repositoryDirectory . '/phpstan.neon');
            if (preg_match('/level:\s*(\d+)/', $phpstanNeonContent, $matches) === 1) {
                $level = $matches[1];
            }

            dump($level);
            die;
        }


        return [
            'has-phpstan' => $hasPhpstan ? 'yes' : 'no',
            'level' => $hasPhpstan ? '-' : $hasPhpstan,
        ];
    }
}