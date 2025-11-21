<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Command;

use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class CloneComposerReposCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('clone-composer-repos');

        $this->setDescription('Clone all Git repositories from composer.lock into a target directory');

        $this->addOption(
            'lockfile',
            null,
            InputOption::VALUE_REQUIRED,
            'Path to composer.lock',
            'composer.lock'
        );

        $this->addOption(
            'target-dir',
            null,
            InputOption::VALUE_REQUIRED,
            'Directory where repositories will be cloned',
            '_deps_repos'
        );

        $this->addOption(
            'github-only',
            null,
            InputOption::VALUE_NONE,
            'If set, only GitHub repositories will be cloned'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $lockfile = (string) $input->getOption('lockfile');
        $targetDir = (string) $input->getOption('target-dir');
        $githubOnly = (bool) $input->getOption('github-only');

        // normalize to absolute paths based on CWD
        if (! str_starts_with($lockfile, DIRECTORY_SEPARATOR)) {
            $lockfile = getcwd() . DIRECTORY_SEPARATOR . $lockfile;
        }

        if (! str_starts_with($targetDir, DIRECTORY_SEPARATOR)) {
            $targetDir = getcwd() . DIRECTORY_SEPARATOR . $targetDir;
        }

        if (! file_exists($lockfile)) {
            $io->error(sprintf('composer.lock not found at "%s"', $lockfile));
            return Command::FAILURE;
        }

        $io->section('Reading composer.lock');

        try {
            /** @var array<string, mixed> $lockData */
            $lockData = Json::decode(
                file_get_contents($lockfile) ?: '',
                Json::FORCE_ARRAY
            );
        } catch (JsonException $exception) {
            $io->error('Failed to parse composer.lock: ' . $exception->getMessage());
            return Command::FAILURE;
        }

        $packages = array_merge(
            $lockData['packages'] ?? [],
            $lockData['packages-dev'] ?? [],
        );

        if (! is_dir($targetDir) && ! mkdir($targetDir, 0777, true) && ! is_dir($targetDir)) {
            $io->error(sprintf('Could not create target directory "%s"', $targetDir));
            return Command::FAILURE;
        }

        $io->text(sprintf('Target directory: <info>%s</info>', $targetDir));
        $io->text(sprintf('Total packages in lock file: <info>%d</info>', count($packages)));
        $io->newLine();

        $clonedCount = 0;
        $skippedCount = 0;

        foreach ($packages as $package) {
            $name = $package['name'] ?? null;
            $source = $package['source']['url'] ?? null;

            if (! $name || ! $source) {
                $skippedCount++;
                continue;
            }

            if ($githubOnly && ! str_contains($source, 'github.com')) {
                $skippedCount++;
                continue;
            }

            // directory name: vendor__package (e.g. symfony__console)
            $dirName = str_replace('/', '__', $name);
            $repoDir = $targetDir . DIRECTORY_SEPARATOR . $dirName;

            if (is_dir($repoDir . DIRECTORY_SEPARATOR . '.git')) {
                $io->writeln(sprintf(
                    '⏭  Skipping <comment>%s</comment>, already cloned at %s',
                    $name,
                    $repoDir
                ));
                $skippedCount++;
                continue;
            }

            $io->writeln(sprintf(
                '🔄 Cloning <info>%s</info> from %s',
                $name,
                $source
            ));

            $process = new Process([
                'git',
                'clone',
                '--depth',
                '1',
                $source,
                $repoDir,
            ]);

            $process->setTimeout(300);

            $process->run(function (string $type, string $buffer) use ($io): void {
                // stream git output via SymfonyStyle
                $io->write($buffer);
            });

            if (! $process->isSuccessful()) {
                $io->error(sprintf('Failed to clone %s', $name));
                $io->writeln($process->getErrorOutput());
                continue;
            }

            $io->writeln(sprintf(
                '✅ Cloned <info>%s</info> into %s',
                $name,
                $repoDir
            ));
            $io->newLine();

            $clonedCount++;
        }

        $io->success(sprintf(
            'Done. Cloned %d packages, skipped %d.',
            $clonedCount,
            $skippedCount
        ));

        return Command::SUCCESS;
    }
}
