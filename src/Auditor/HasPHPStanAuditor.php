<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Auditor;

use Nette\Utils\FileSystem;
use Rector\DependencyAudit\Contract\AuditorInterface;
use Rector\DependencyAudit\Utils\JsonLoader;

final class HasPHPStanAuditor implements AuditorInterface
{
    /**
     * @var string[]
     */
    private const PHPSTAN_SHORT_NAMES = [
        'phpstan.neon', 'phpstan.neon.dist',
    ];

    public function audit(string $repositoryDirectory): array
    {
        $composerJsonFilePath = $repositoryDirectory . '/composer.json';
        if (! file_exists($composerJsonFilePath)) {
            return [];
        }

        $composerJson = JsonLoader::loadFileToJson($composerJsonFilePath);

        $hasPHPStan = isset($composerJson['require-dev']['phpstan/phpstan']);
        $level = '-';

        // fallback check
        foreach (self::PHPSTAN_SHORT_NAMES as $phpstanShortName) {
            if (! file_exists($repositoryDirectory . '/' . $phpstanShortName)) {
                continue;
            }

            $hasPHPStan = true;

            // parse level: x out of this file
            $phpstanNeonContent = FileSystem::read($repositoryDirectory . '/' . $phpstanShortName);
            if (preg_match('/level:\s*(\d+)/', $phpstanNeonContent, $matches) === 1) {
                $level = $matches[1];
                break;
            }
        }

        $baselineLineCount = '-';

        // resolve baseline size if found
        if (file_exists($repositoryDirectory . '/phpstan-baseline.neon')) {
            $baselineContent = FileSystem::read($repositoryDirectory . '/phpstan-baseline.neon');
            // line count
            $lines = explode("\n", $baselineContent);
            $baselineLineCount = count($lines);
        }

        $result = [
            'has-phpstan' => $hasPHPStan ? 'yes' : 'no',
        ];

        if ($hasPHPStan) {
            $result['level'] = $level;
            $result['baseline-line-count'] = $baselineLineCount;
        }

        return $result;
    }
}
