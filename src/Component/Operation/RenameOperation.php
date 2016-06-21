<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Operation;

use SR\Console\Style\StyleAwareTrait;
use SR\Console\Style\StyleInterface;
use SR\Primitive\FileInfo;
use SR\Serferals\Component\Fixture\FixtureData;
use SR\Serferals\Component\Fixture\FixtureEpisodeData;
use SR\Serferals\Component\Fixture\FixtureMovieData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RenameOperation.
 */
class RenameOperation
{
    use StyleAwareTrait;

    /**
     * @var string
     */
    protected $outputPath;

    /**
     * @var bool
     */
    protected $outputOverwrite;

    /**
     * @var bool
     */
    protected $smartOutputOverwrite;

    /**
     * @var string
     */
    protected $tplPathEpisode;

    /**
     * @var string
     */
    protected $tplFileEpisode;

    /**
     * @var string
     */
    protected $tplPathMovie;

    /**
     * @var string
     */
    protected $tplFileMovie;

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
     * @param string $tplPath
     * @param string $tplFile
     */
    public function setFileTemplateEpisode($tplPath, $tplFile)
    {
        $this->tplPathEpisode = $tplPath;
        $this->tplFileEpisode = $tplFile;
    }

    /**
     * @param string $tplPath
     * @param string $tplFile
     */
    public function setFileTemplateMovie($tplPath, $tplFile)
    {
        $this->tplPathMovie = $tplPath;
        $this->tplFileMovie = $tplFile;
    }

    /**
     * @param string                                  $outputPath
     * @param FixtureMovieData[]|FixtureEpisodeData[] $collection
     * @param bool                                    $outputOverwrite
     * @param bool                                    $smartOutputOverwrite
     */
    public function run($outputPath, array $collection, $outputOverwrite = false, $smartOutputOverwrite = false)
    {
        $this->outputPath = $outputPath;
        $this->outputOverwrite = $outputOverwrite;
        $this->smartOutputOverwrite = $smartOutputOverwrite;

        if (count($collection) === 0) {
            $this->io()->warning('No output files selected during run');

            return;
        }

        $this->io()->subSection('Writing Output Files');
        $count = count($collection);
        $i = 1;

        foreach ($collection as $item) {
            $this->ioVerbose(function () use (&$i, $count) {
                $this->io()->section(sprintf('%03d of %03d', $i++, $count));
            });

            $this->move($item);
        }

        $this->io()->newLine();
    }

    /**
     * @param FixtureData $f
     */
    private function move(FixtureData $f)
    {
        $tplPathName = uniqid('string_template_'.mt_rand(10, 99).'_', true);
        $tplFileName = uniqid('string_template_'.mt_rand(10, 99).'_', true);

        if ($f instanceof FixtureMovieData) {
            list($e, $opts) = $this->moveMovie($f, $this->getTwig(), $tplPathName, $tplFileName);
        } elseif ($f instanceof FixtureEpisodeData) {
            list($e, $opts) = $this->moveEpisode($f, $this->getTwig(), $tplPathName, $tplFileName);
        } else {
            $this->io()->error('Invalid fixture type!');

            return;
        }

        $path = $e->render($tplPathName, $opts);
        $file = $e->render($tplFileName, $opts);

        $outputFilePath = preg_replace('{[/+]}', '/', $this->outputPath.'/'.$path.'/'.$file);
        $outputPath = pathinfo($outputFilePath, PATHINFO_DIRNAME);
        $inputFilePath = $f->getFile()->getRealPath();

        $offset = 2;
        while (true) {
            if (strncmp($outputFilePath, $inputFilePath, $offset++) !== 0) {
                $offset = $offset - 2;
                break;
            }
        }

        if ($offset !== 0) {
            $tableRows[] = ['Base Path', substr($outputFilePath, 0, $offset)];
        }

        $outputFileInfo = new FileInfo($outputFilePath, null, null, false);
        $inputFileInfo = new FileInfo($inputFilePath);

        $tableRows[] = ['Input File', substr($inputFilePath, $offset)];
        $tableRows[] = ['Input Size', $inputFileInfo->getSizeHuman()];
        $tableRows[] = ['Output File', substr($outputFilePath, $offset)];


        try {
            $tablesRows[] = ['Output Size', $outputFileInfo->getSizeHuman()];
        } catch (\RuntimeException $e) {
            $tablesRows[] = ['Output Size', 'N/A'];
        }

        $this->ioVerbose(function (StyleInterface $io) use ($tableRows) {
            $io->table($tableRows, []);
        });

        if (file_exists($outputFilePath) &&
            false === $this->outputOverwrite &&
            false === $this->handleExistingFile($outputFileInfo, $inputFileInfo)
        ) {
            return;
        }

        if (!is_dir($outputPath) && false === @mkdir($outputPath, 0777, true)) {
            $this->io()->error(sprintf('Could not create directory "%s"', $outputPath));

            return;
        }

        if (!$this->io()->isVeryVerbose()) {
            $this->io()->comment(sprintf('Writing "%s"', $outputFilePath), false);
        }

        if (false === @copy($inputFilePath, $outputFilePath)) {
            $this->io()->error(sprintf('Could not write file "%s"', $outputFilePath));
        } else {
            unlink($inputFilePath);
        }
    }

    /**
     * @param string $output
     * @param string $input
     *
     * @return bool
     */
    private function handleExistingFile(FileInfo $output, FileInfo $input)
    {
        if ($this->smartOutputOverwrite === true && $input->getSize() > $output->getSize()) {
            $this->io()->warning('Automatically overwriting smaller output filepath with larger input.');

            return true;
        }

        if ($this->smartOutputOverwrite === true && $input->getSize() <= $output->getSize()) {
            unlink($input->getPathname());
            $this->io()->warning('Automatically removing input filepath of less than or equal size to existing output filepath.');

            return false;
        }

        while (true) {
            $this->io()->comment('File already exists in output path');

            $this->io()->writeln(' [ <em>o</em> ] Overwrite <info>(default)</info>', false);
            $this->io()->writeln(' [ <em>s</em> ] Skip', false);
            $this->io()->writeln(' [ <em>R</em> ] Delete Input', false);

            $action = $this->io()->ask('Enter action command shortcut name', 'o');

            switch ($action) {
                case 'o':
                    $this->io()->comment('Overwriting output path.');
                    $this->io()->newLine();

                    return true;
                case 's':
                    return false;
                case 'R':
                    unlink($input->getPathname());
                    $this->io()->warning('Removing input file.');

                    return false;
                default:
                    $this->io()->error(sprintf('Invalid command shortcut "%s"', $action));
                    sleep(3);
            }
        }
    }

    /**
     * @param FixtureEpisodeData $f
     * @param \Twig_Environment  $e
     * @param string             $tplPathName
     * @param string             $tplFileName
     *
     * @return \Twig_Environment[]|mixed[][]
     */
    private function moveEpisode(FixtureEpisodeData $f, \Twig_Environment $e, $tplPathName, $tplFileName)
    {
        $e->setLoader(new \Twig_Loader_Array([$tplPathName => $this->tplPathEpisode, $tplFileName => $this->tplFileEpisode]));

        $opts = [
            'name' => $f->getName(),
            'season' => str_pad($f->getSeasonNumber(), 2, 0, STR_PAD_LEFT),
            'start' => str_pad($f->getEpisodeNumberStart(), 2, 0, STR_PAD_LEFT),
            'ext' => strtolower(pathinfo($f->getFile()->getRelativePathname(), PATHINFO_EXTENSION)),
        ];

        if ($f->hasTitle()) {
            $opts['title'] = $f->getTitle();
        }

        if ($f->hasYear()) {
            $opts['year'] = $f->getYear();
        }

        return [$e, $opts];
    }

    /**
     * @param FixtureMovieData  $f
     * @param \Twig_Environment $e
     * @param string            $tplPathName
     * @param string            $tplFileName
     *
     * @return \Twig_Environment[]|mixed[][]
     */
    private function moveMovie(FixtureMovieData $f, \Twig_Environment $e, $tplPathName, $tplFileName)
    {
        $e->setLoader(new \Twig_Loader_Array([$tplPathName => $this->tplPathMovie, $tplFileName => $this->tplFileMovie]));

        $opts = [
            'name' => $f->getName(),
            'ext' => strtolower(pathinfo($f->getFile()->getRelativePathname(), PATHINFO_EXTENSION)),
        ];

        if ($f->hasId()) {
            $opts['id'] = $f->getId();
        }

        if ($f->hasYear()) {
            $opts['year'] = $f->getYear();
        }

        return [$e, $opts];
    }

    /**
     * @return \Twig_Environment
     */
    private function getTwig()
    {
        $twig = new \Twig_Environment();
        $twig->setCache(false);

        return $twig;
    }
}
