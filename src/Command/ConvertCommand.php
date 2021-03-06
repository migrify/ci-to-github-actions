<?php

declare(strict_types=1);

namespace Migrify\CIToGithubActions\Command;

use Migrify\CIToGithubActions\CIToGithubActionsConverter\TravisToGithubActionsConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\SmartFileSystem\FileSystemGuard;
use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\SmartFileSystem\SmartFileSystem;

final class ConvertCommand extends Command
{
    /**
     * @var string
     */
    private const ARGUMENT_SOURCE = 'source';

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var TravisToGithubActionsConverter
     */
    private $travisToGithubActionsConverter;

    /**
     * @var SmartFileSystem
     */
    private $smartFileSystem;

    /**
     * @var FileSystemGuard
     */
    private $fileSystemGuard;

    public function __construct(
        TravisToGithubActionsConverter $travisToGithubActionsConverter,
        SymfonyStyle $symfonyStyle,
        SmartFileSystem $smartFileSystem,
        FileSystemGuard $fileSystemGuard
    ) {
        parent::__construct();

        $this->symfonyStyle = $symfonyStyle;
        $this->travisToGithubActionsConverter = $travisToGithubActionsConverter;
        $this->smartFileSystem = $smartFileSystem;
        $this->fileSystemGuard = $fileSystemGuard;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->addArgument(self::ARGUMENT_SOURCE, InputArgument::REQUIRED, 'Directory or file to convert');
        $this->setDescription('Converts Neon syntax to Yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = (string) $input->getArgument(self::ARGUMENT_SOURCE);
        $this->fileSystemGuard->ensureFileExists($source, __METHOD__);

        $inputFileInfo = new SmartFileInfo($source);
        $convertedContent = $this->travisToGithubActionsConverter->convert($inputFileInfo);

        $pathname = $inputFileInfo->getPathname();

        $codeChecksWorkflowFilePath = $pathname . '/.github/workflows/code_checks.yaml';
        $this->smartFileSystem->dumpFile($convertedContent, $codeChecksWorkflowFilePath);

        $message = sprintf('File %s was created', $codeChecksWorkflowFilePath);
        $this->symfonyStyle->success($message);

        $this->symfonyStyle->success('Done');

        return ShellCode::SUCCESS;
    }
}
