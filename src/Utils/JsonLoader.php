<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Utils;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;

final class JsonLoader
{
    /**
     * @return array<string, mixed>
     */
    public static function loadFileToJson(string $filePath): array
    {
        $fileContents = FileSystem::read($filePath);

        return Json::decode($fileContents, true);
    }
}