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
use SR\Serferals\Component\Fixture\FixtureData;
use SR\Serferals\Component\Fixture\FixtureEpisodeData;
use SR\Serferals\Component\Fixture\FixtureMovieData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RenameOperation
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
     */
    public function run($outputPath, array $collection, $outputOverwrite = false)
    {
        $this->outputPath = $outputPath;
        $this->outputOverwrite = $outputOverwrite;

        if (count($collection) === 0) {
            $this->io()->warning('No output files selected during run');

            return;
        }

        $this->io()->subSection('Writing Output Files');

        foreach ($collection as $item) {
            $this->move($item);
        }

        $this->io()->newLine();
    }

    /**
     * @param FixtureData $f
     */
    private function move(FixtureData $f)
    {
        static $i = 1;

        $tplPathName = uniqid( 'string_template_'.mt_rand(10,99).'_', true );
        $tplFileName = uniqid( 'string_template_'.mt_rand(10,99).'_', true );

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
        while(true) {
            if (strncmp($outputFilePath, $inputFilePath, $offset++) !== 0) {
                $offset = $offset - 2;
                break;
            }
        }

        if ($offset !== 0) {
            $tableRows[] = ['Base Path', substr($outputFilePath, 0, $offset)];
        }

        $tableRows[] = ['Input', substr($inputFilePath, $offset)];
        $tableRows[] = ['Output', substr($outputFilePath, $offset)];

        $this->ioVeryVerbose(function (StyleInterface $io) use ($tableRows) {
            $io->table($tableRows);
        });

        if (file_exists($outputFilePath) &&
            false === $this->outputOverwrite &&
            false === $this->io()->confirm(sprintf('Overwrite file "%s"', $outputFilePath), false)
        ) {
            $this->io()->comment(sprintf('Skipping "%s"', $outputFilePath));

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
            'ext' => pathinfo($f->getFile()->getRelativePathname(), PATHINFO_EXTENSION)
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
            'ext' => pathinfo($f->getFile()->getRelativePathname(), PATHINFO_EXTENSION)
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
