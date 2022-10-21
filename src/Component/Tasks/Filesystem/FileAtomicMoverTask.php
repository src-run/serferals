<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Tasks\Filesystem;

use SR\Console\Output\Style\StyleAwareTrait;
use SR\Console\Output\Style\StyleInterface;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Filesystem\PathDefinition;
use SR\Serferals\Component\Model\FileMoveInstruction;
use SR\Spl\File\SplFileInfo as FileInfo;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class FileAtomicMoverTask implements FileAtomicMoverTaskInterface
{
    use StyleAwareTrait;

    /**
     * @var string
     */
    private $mode = false;

    /**
     * @var bool
     */
    private $blindOverwrite;

    /**
     * @var bool
     */
    private $smartOverwrite;

    /**
     * @var FileMoveInstruction[]
     */
    private $instructions;

    /**
     * @param string $mode
     *
     * @return $this
     */
    public function setMode(string $mode)
    {
        if (!in_array($mode, [static::MODE_MV, static::MODE_CP])) {
            throw new InvalidArgumentException('Invalid mode value provided: %s', $mode);
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * @param bool $blindOverwrite
     *
     * @return $this
     */
    public function setBlindOverwrite(bool $blindOverwrite)
    {
        $this->blindOverwrite = $blindOverwrite;

        return $this;
    }

    /**
     * @param bool $smartOverwrite
     *
     * @return $this
     */
    public function setSmartOverwrite(bool $smartOverwrite)
    {
        $this->smartOverwrite = $smartOverwrite;

        return $this;
    }

    /**
     * @param FileMoveInstruction ...$instructions
     *
     * @return $this
     */
    public function setFileMoveInstructions(FileMoveInstruction ...$instructions): FileAtomicMoverTask
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->io->subSection('Writing Output Files');

        if (0 === count($this->instructions)) {
            throw new RuntimeException('No file move instructions.');
        }

        foreach ($this->instructions as $i => $inst) {
            $this->io
                ->environment(StyleInterface::VERBOSITY_VERBOSE)
                ->enumeratedSection('PLACING FILE', $i, count($this->instructions), $inst->getOutput());
            $this->move($inst);
        }

        $this->io->newline();
    }

    /**
     * @param FileMoveInstruction $inst
     */
    private function move(FileMoveInstruction $inst)
    {
        $outputGroup = $inst->getOutputPathDefinitions();
        $origin = $inst->getOrigin();
        $staged = $inst->getStaged();
        $output = $inst->getOutput();

        $originSize = $this->sanitizeFileSize($origin);
        $outputSize = $this->sanitizeFileSize($output);

        $tableRows[] = ['Origin File', sprintf('[...]%s', $origin->getFilename()), $originSize];
        $tableRows[] = ['Staged File', sprintf('[...]%s', $staged->getPathname()), 'n/a'];
        $tableRows[] = ['Output File', sprintf('[...]%s', $output->getPathname()), $outputSize ?: 'n/a'];

        $this->io->table(['', 'File', 'Size'], ...$tableRows);

        if (file_exists($output->getPathname()) &&
            false === $this->blindOverwrite &&
            false === $this->handleExistingFile($output, $origin)
        ) {
            return;
        }

        foreach ([PathDefinition::OBJECT_STAGED, PathDefinition::OBJECT_OUTPUT] as $pathDefinitionKey) {
            if (!$outputGroup->ensureDirectoryPathExists($pathDefinitionKey)) {
                return;
            }
        }

        /*
        $this->io->environment(StyleInterface::VERBOSITY_DEBUG)
             ->comment(sprintf('Writing "%s" (mode: %s)', $output->getPathname(), $this->mode === self::MODE_MV ? 'move' : 'copy'))
             ->newline();

        if (false === @copy($origin->getPathname(), $output->getPathname())) {
            $this->io->error(sprintf('Could not write file "%s"', $output->getPathname()));
        } elseif ($this->mode === self::MODE_MV) {
            unlink($origin->getPathname());
        }

        return;
        */

        $copy = (new ProcessBuilder(['rsync', '-a', '--info=progress2', $origin->getPathname(), $staged->getPathname()]))
            ->setTimeout($origin->getSize())
            ->getProcess();

        $this->io->environment(OutputInterface::VERBOSITY_DEBUG)
             ->comment(sprintf('Running "%s"', $copy->getCommandLine()))
             ->comment(sprintf('Writing "%s" (mode: %s)', $output->getPathname(), $this->mode === self::MODE_MV ? 'move' : 'copy'))
             ->newline();

        $pb = new ProgressBar($this->io->getOutput());
        $pb->setBarCharacter('<fg=cyan>=</>');
        $pb->setEmptyBarCharacter('<fg=blue;options=bold>-</>');
        $pb->setProgressCharacter('<fg=cyan;options=bold>></>');

        if ($this->io->isVerbose()) {
            $pb->setFormat(
                implode(PHP_EOL, [
                    '      Progress : [%bar%] (%percent:3s%%)',
                    ' Time Estimate : %elapsed:6s% / %estimated:-6s%',
                    ' %context:13s% : %message%',
                ]).PHP_EOL
            );
        }

        $pb->setMessage('Write Speed', 'context');
        $pb->setMessage('Calculating...');

        $pb->start(100);
        sleep(1);

        $copy->run(function (string $type, string $buff) use ($pb) {
            if (Process::ERR === $type) {
                $pb->setMessage('Error Text', 'context');
                $pb->setMessage($buff);
                $pb->display();
                sleep(1);
            }

            if (1 !== preg_match('/(?<percent>[0-9]+)%\s+(?<speed_numb>[0-9.]+)(?<speed_unit>[A-z\/]+)/', $buff, $matches)) {
                return;
            }

            $pb->setMessage('Write Speed', 'context');
            $pb->setMessage(sprintf('%s %s', $matches['speed_numb'], $matches['speed_unit']));
            $pb->setProgress($matches['percent']);
        });

        if (!$copy->isSuccessful()) {
            $pb->setMessage('Failure Text', 'context');
            $pb->setMessage(
                sprintf(
                    'Encountered unexpected exit status code: %s (%s)',
                    $copy->getExitCode(),
                    $copy->getExitCodeText()
                )
            );
            $pb->finish();
            $this->io->newline();

            return;
        }

        $pb->setMessage('Result Text', 'context');
        $pb->setMessage('Moving staged to output location and synchronizing cached writes to persistent disk...');
        $pb->display();
        sleep(1);

        $sync = (new ProcessBuilder(['sync', $staged->getPathname()]))
            ->setTimeout($origin->getSize())
            ->getProcess();
        $sync->run();

        if (!$sync->isSuccessful()) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage('Failed to sync disk! Leaving original file when unable to verify staged file...');
            $pb->finish();
            $this->io->newline();

            return;
        }

        if ($origin->getSize() !== $staged->getSize()) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage(
                sprintf(
                    'Original file size (%s) does not match staged file size (%s)...',
                    $origin->getSizeReadable(),
                    $staged->getSizeReadable()
                )
            );
            $pb->finish();
            $this->io->newline();

            return;
        }

        if (!$outputGroup->ensureFileOwnership(PathDefinition::OBJECT_STAGED)) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage('Failed assign configured permissions to staged file...');
            $pb->finish();
            $this->io->newline();

            return;
        }

        $move = (new ProcessBuilder(['mv', '-f', '-v', $staged->getPathname(), $output->getPathname()]))
            ->setTimeout($origin->getSize())
            ->getProcess();
        $move->run();

        if (!$move->isSuccessful()) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage('Failed to move staged file to output location...');
            $pb->finish();
            $this->io->newline();

            return;
        }

        $sync = (new ProcessBuilder(['sync', $output->getPathname()]))
            ->setTimeout($origin->getSize())
            ->getProcess();
        $sync->run();

        if (!$sync->isSuccessful()) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage('Failed to sync disk! Leaving original file when unable to verify output file...');
            $pb->finish();
            $this->io->newline();

            return;
        }

        if ($origin->getSize() !== $output->getSize()) {
            $pb->setMessage('Error Text', 'context');
            $pb->setMessage(
                sprintf(
                    'Original file size (%s) does not match output file size (%s)...',
                    $origin->getSizeReadable(),
                    $output->getSizeReadable()
                )
            );
            $pb->finish();
            $this->io->newline();

            return;
        }

        if ($this->mode == self::MODE_MV) {
            $pb->setMessage(sprintf('Removing origin file "%s" ...', $origin->getPathname()));
            $pb->display();
            sleep(1);

            $dels = (new ProcessBuilder(['rm', $origin->getPathname()]))
                ->setTimeout((int) ($origin->getSize() / 10))
                ->getProcess();

            $dels->run();

            if (!$dels->isSuccessful()) {
                $pb->setMessage('Error Text', 'context');
                $pb->setMessage(sprintf('Unable to remove origin file "%s" ...', $origin->getPathname()));
                $pb->finish();
                $this->io->newline();

                return;
            }
        }

        $pb->setMessage(sprintf('Successfully wrote %s to persistent disk ...', $output->getSizeReadable()));
        $pb->finish();

        $this->io->newline();
    }

    /**
     * @param FileInfo $file
     *
     * @return null|string
     */
    private function sanitizeFileSize(FileInfo $file)
    {
        try {
            return $file->getSizeReadable();
        } catch (\RuntimeException $e) {
            return null;
        }
    }

    /**
     * @param FileInfo $output
     * @param FileInfo $input
     *
     * @return bool|null
     */
    private function handleExistingFile(FileInfo $output, FileInfo $input)
    {
        try {
            if ($this->smartOverwrite === true && $input->getSize() > $output->getSize()) {
                $this->io->info('Automatically overwriting smaller output filepath with larger input.');
                return true;
            }

            if ($this->smartOverwrite === true && $input->getSize() <= $output->getSize()) {
                unlink($input->getPathname());
                $this->io->info('Automatically removing input file path of less than or equal size to existing output filepath.');
                return false;
            }
        } catch (\RuntimeException $e) {
            $this->io->error('Could not use smart output mode! An error occurred while processing file sizes.');
        }

        while (true) {
            $this->io->comment('File already exists in output path');

            $this->io->writeln([
                ' [ <em>o</em> ] Overwrite <info>(default)</info>',
                ' [ <em>s</em> ] Skip',
                ' [ <em>R</em> ] Delete Input',
            ]);

            $action = $this->io->ask('Enter action command shortcut name', 'o');

            switch ($action) {
                case 'o':
                    $this->io->info('Overwriting smaller output filepath with larger input.');
                    return true;

                case 's':
                    return false;

                case 'R':
                    $this->io->info(sprintf('Removing input file: %s', $input->getPathname()));
                    unlink($input->getPathname());
                    return false;

                default:
                    $this->io->error(sprintf('Invalid command shortcut "%s"', $action));
                    sleep(3);
            }
        }
    }
}
