<?php

declare(strict_types=1);

namespace Rector\DependencyAudit\Command;

use Nette\Utils\FileSystem;
use Rector\DependencyAudit\Composer\RequiredPackageResolver;
use Rector\DependencyAudit\Contract\AuditorInterface;
use Rector\DependencyAudit\ValueObject\RequiredPackage;
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
        private readonly RequiredPackageResolver $requiredPackageResolver,
        private readonly SymfonyStyle $symfonyStyle,
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
        $clonedRepositoryDirectory = getcwd() . '/cloned-repos';
        FileSystem::createDir($clonedRepositoryDirectory);

        $requiredPackages =  $this->requiredPackageResolver->resolve(getcwd());

        $clonedRepositoryDirectory = getcwd() . '/cloned-repos';
        FileSystem::createDir($clonedRepositoryDirectory);

        $this->symfonyStyle->text(sprintf('Found %d dependency packages', count($requiredPackages)));
        $this->symfonyStyle->newLine();

        $auditResults = [];

        foreach ($requiredPackages as $requiredPackage) {
            $this->cloneRequiredPackageIfMissing($requiredPackage, $clonedRepositoryDirectory);

            $clonedPackageDirectory = $clonedRepositoryDirectory . '/' . $requiredPackage->getDirectoryName();

            foreach ($this->auditors as $auditor) {
                $auditResult = $auditor->audit($clonedPackageDirectory);
                $auditResults[$requiredPackage['name']] = array_merge($auditResults[$requiredPackage['name']] ?? [], $auditResult);
            }
        }

        $this->symfonyStyle->title('Audit Results');
        foreach ($auditResults as $packageName => $packageAuditResults) {
            $this->symfonyStyle->section($packageName);
            foreach ($packageAuditResults as $packageAuditResult) {
                $this->symfonyStyle->writeln($packageAuditResult);
            }

            $this->symfonyStyle->newLine();
        }

        return Command::SUCCESS;
    }

    private function cloneRequiredPackageIfMissing(RequiredPackage $requiredPackage, string $clonedRepositoryDirectory): void
    {
        $repositoryTemporaryDirectory = $clonedRepositoryDirectory . DIRECTORY_SEPARATOR . $requiredPackage->getDirectoryName();
        if (is_dir($repositoryTemporaryDirectory . DIRECTORY_SEPARATOR . '.git')) {
            // already cloned
            return;
        }

        $this->symfonyStyle->writeln(sprintf(
            '🔄 Cloning <info>%s</info> from %s',
            $requiredPackage->getName(),
            $requiredPackage->getSourceUrl()
        ));

        $gitCloneProcess = new Process(['git', 'clone', '--depth', '1', $requiredPackage->getSourceUrl(), $repositoryTemporaryDirectory], timeout: 30);

        $gitCloneProcess->mustRun(function (string $type, string $buffer): void {
            // stream git output via SymfonyStyle
            $this->symfonyStyle->write($buffer);
        });
    }
}
