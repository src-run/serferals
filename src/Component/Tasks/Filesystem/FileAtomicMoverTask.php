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

use SR\Console\Style\StyleAwareTrait;
use SR\Exception\Logic\InvalidArgumentException;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Model\FileMoveInstruction;
use SR\Spl\File\SplFileInfo as FileInfo;
use SR\Serferals\Component\Model\MediaMetadataModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FileAtomicMoverTask
{
    use StyleAwareTrait;

    /**
     * @var string
     */
    const MODE_MV = 'mv';

    /**
     * @var string
     */
    const MODE_CP = 'cp';

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
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

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
     * @param FileMoveInstruction[] ...$instructions
     *
     * @return $this
     */
    public function setFileMoveInstructions(FileMoveInstruction ...$instructions)
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return void
     */
    public function execute()
    {
        $this->io()->subSection('Writing Output Files');

        if (0 === count($this->instructions)) {
            throw new RuntimeException('No file move instructions.');
        }

        foreach ($this->instructions as $inst) {
            $this->ioVerbose(function () use (&$iteration) {
                static $iteration = 0;

                $this->io()->section(sprintf('%03d of %03d', $iteration++, count($this->instructions)));
            });

            $this->move($inst);
        }

        $this->io()->newLine();
    }

    /**
     * @param FileMoveInstruction $inst
     */
    private function move(FileMoveInstruction $inst)
    {
        $origin = $inst->getOrigin();
        $output = $inst->getOutput();

        $originSize = $this->sanitizeFileSize($origin);
        $outputSize = $this->sanitizeFileSize($output);

        $tableRows[] = ['Origin File', sprintf('[...]%s', $origin->getFilename()), $originSize];
        $tableRows[] = ['Output File', sprintf('[...]%s', $output->getPathname()), $outputSize,];

        $this->io()->table($tableRows, [null, 'Path', 'Size']);

        if (file_exists($output->getPathname()) &&
            false === $this->blindOverwrite &&
            false === $this->handleExistingFile($output, $origin)
        ) {
            return;
        }

        if (!is_dir($output->getPath()) && false === @mkdir($output->getPath(), 0777, true)) {
            $this->io()->error(sprintf('Could not create directory "%s"', $output->getPath()));
            return;
        }

        if (!$this->io()->isVeryVerbose()) {
            $this->io()->comment(sprintf('Writing "%s"', $output->getPathname()));
        }

        if (false === @copy($origin->getPathname(), $output->getPathname())) {
            $this->io()->error(sprintf('Could not write file "%s"', $output->getPathname()));
        } elseif ($this->mode === self::MODE_MV) {
            unlink($origin->getPathname());
        }
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
                $this->io()->info('Automatically overwriting smaller output filepath with larger input.');
                return true;
            }

            if ($this->smartOverwrite === true && $input->getSize() <= $output->getSize()) {
                unlink($input->getPathname());
                $this->io()->info('Automatically removing input file path of less than or equal size to existing output filepath.');
                return false;
            }
        } catch (\RuntimeException $e) {
            $this->io()->error('Could not use smart output mode! An error occurred while processing file sizes.');
        }

        while (true) {
            $this->io()->comment('File already exists in output path');

            $this->io()->writeln(' [ <em>o</em> ] Overwrite <info>(default)</info>', false);
            $this->io()->writeln(' [ <em>s</em> ] Skip', false);
            $this->io()->writeln(' [ <em>R</em> ] Delete Input', false);

            $action = $this->io()->ask('Enter action command shortcut name', 'o');

            switch ($action) {
                case 'o':
                    $this->io()->info('Overwriting smaller output filepath with larger input.');
                    return true;

                case 's':
                    return false;

                case 'R':
                    $this->io()->info(sprintf('Removing input file: %s', $input->getPathname()));
                    unlink($input->getPathname());
                    return false;

                default:
                    $this->io()->error(sprintf('Invalid command shortcut "%s"', $action));
                    sleep(3);
            }
        }

        return null;
    }
}
