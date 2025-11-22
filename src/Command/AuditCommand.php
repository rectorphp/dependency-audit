<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Command;

use Nette\Utils\FileSystem;
use Rector\DependencyAudit\Contract\AuditorInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class AuditCommand extends Command
{
    /**
     * @param AuditorInterface[] $auditors
     */
    public function __construct(
        private array $auditors
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('audit');

        $this->setDescription('Audit your dependencies for code quality levels they hold');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        $clonedRepositoryDirectory = getcwd() . '/cloned-repos';
        FileSystem::createDir($clonedRepositoryDirectory);

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

        $clonedRepositoryDirectory = getcwd() . '/cloned-repos';
        FileSystem::createDir($clonedRepositoryDirectory);

        $symfonyStyle->text(sprintf('Found %d dependency packages', count($packagesWithoutSymfony)));
        $symfonyStyle->newLine();

        $this->cloneInstalledPackages($packagesWithoutSymfony, $clonedRepositoryDirectory, $symfonyStyle);

        // @todo next
        // run auditors

        $auditResults = [];

        // @todo introduce RequiredPackage value object
        foreach ($packagesWithoutSymfony as $package) {
            $packageDirName = str_replace('/', '-', $package['name']);
            $clonedPackageDirectory = $clonedRepositoryDirectory . '/' . $packageDirName;

            if (file_exists($clonedPackageDirectory)) {
                foreach ($this->auditors as $auditor) {
                    $auditResult = $auditor->audit($clonedPackageDirectory);

                    $auditResults[$package['name']] = array_merge($auditResults[$package['name']] ?? [], $auditResult);
                }
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<mixed> $packages
     */
    private function cloneInstalledPackages(array $packages, string $clonedRepositoryDirectory, SymfonyStyle $symfonyStyle): void
    {
        foreach ($packages as $package) {
            $name = $package['name'] ?? null;
            $source = $package['source']['url'] ?? null;
            if ($name === null || $name === '' || $source === null || $source === '') {
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
