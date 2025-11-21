<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Command;

use Nette\Utils\JsonException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;

final class AuditCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('audit');

        $this->setDescription('Audit your dependencies for code quality levels they hold');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $installedJsonFilePath = getcwd() . '/vendor/composer/installed.json';
        Assert::fileExists($installedJsonFilePath);

        $clonedRepositoryDirectory = getcwd() . '/cloned-repos';

        $symfonyStyle->section('Reading composer.lock');

        try {
            /** @var array<string, mixed> $lockData */
            $lockData = Json::decode(
                file_get_contents($installedJsonFilePath) ?: '',
                true
            );
        } catch (JsonException $exception) {
            $symfonyStyle->error('Failed to parse composer.lock: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $packages = array_merge($lockData['packages'] ?? [], $lockData['packages-dev'] ?? []);
        FileSystem::createDir($clonedRepositoryDirectory);

        // remove symfony/* packages, as they share the same code quality, no need to check 35 split packages
        // keep output informative and focused on non framework packages instead
        $packagesWithoutSymfony = array_filter($packages, fn (array $package) => ! str_starts_with($package['name'] ?? '', 'symfony'));

        if ($packages !== $packagesWithoutSymfony) {
            $symfonyStyle->write(sprintf(
                '<fg=green>Skipping %d Symfony packages to avoid repeated single-framework results.</>',
                count($packages) - count($packagesWithoutSymfony)
            ));
            $symfonyStyle->newLine();
        }

        $symfonyStyle->text(sprintf('Found %d installed packages', count($packages)));
        $symfonyStyle->newLine();

        $this->cloneInstalledPackages($packages, $clonedRepositoryDirectory, $symfonyStyle);

        return Command::SUCCESS;
    }

    private function cloneInstalledPackages(array $packages, string $clonedRepositoryDirectory, SymfonyStyle $symfonyStyle): void
    {
        foreach ($packages as $package) {
            $name = $package['name'] ?? null;
            $source = $package['source']['url'] ?? null;
            if (! $name || ! $source) {
                continue;
            }

            // directory name in "vendor-package" format (e.g. symfony-console)
            $dirName = str_replace('/', '-', $name);
            $repoDir = $clonedRepositoryDirectory . DIRECTORY_SEPARATOR . $dirName;

            if (is_dir($repoDir . DIRECTORY_SEPARATOR . '.git')) {
                // already cloned
                continue;
            }

            $symfonyStyle->writeln(sprintf(
                '🔄 Cloning <info>%s</info> from %s',
                $name,
                $source
            ));

            $gitCloneProcess = new Process(['git', 'clone', '--depth', '1', $source, $repoDir]);
            $gitCloneProcess->setTimeout(300);

            $gitCloneProcess->mustRun(function (string $type, string $buffer) use ($symfonyStyle): void {
                // stream git output via SymfonyStyle
                $symfonyStyle->write($buffer);
            });

            $symfonyStyle->writeln(sprintf(
                '✅ Cloned <info>%s</info> into %s',
                $name,
                $repoDir
            ));
            $symfonyStyle->newLine();
        }

        $symfonyStyle->success('Cloning is done');
    }
}
