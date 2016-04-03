<?php

/*
 * This file is part of the `rmf/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace RMF\Serferals\Component\Operation;

use RMF\Serferals\Component\Console\InputOutputAwareTrait;
use RMF\Serferals\Component\Fixture\FixtureData;
use RMF\Serferals\Component\Fixture\FixtureEpisodeData;
use RMF\Serferals\Component\Queue\QueueEpisodeItem;
use SR\Utility\StringUtil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class RenamerOperation
 */
class RenamerOperation
{
    use InputOutputAwareTrait;

    /**
     * @var string
     */
    protected $tplPath;

    /**
     * @var string
     */
    protected $tplFile;

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function setFileNameTemplate($tplPath, $tplFile)
    {
        $this->tplPath = $tplPath;
        $this->tplFile = $tplFile;
    }
    
    public function run($outputPath, array $collection)
    {
        foreach ($collection as $item) {
            $this->move($outputPath, $item);
        }
    }

    public function move($outputPath, FixtureEpisodeData $fixture)
    {
        $engine = $this->getTwig();
        $tplPathName = uniqid( 'string_template_', true );
        $tplFileName = uniqid( 'string_template_', true );
        $engine->setLoader(new \Twig_Loader_Array([$tplPathName => $this->tplPath, $tplFileName => $this->tplFile]));
        $engineParameters = [
            'name' => $fixture->getName(),
            'season' => str_pad($fixture->getSeasonNumber(), 2, 0, STR_PAD_LEFT),
            'start' => str_pad($fixture->getEpisodeNumberStart(), 2, 0, STR_PAD_LEFT),
            'ext' => pathinfo($fixture->getFile()->getRelativePathname(), PATHINFO_EXTENSION)
        ];

        if ($fixture->hasTitle()) {
            $engineParameters['title'] = $fixture->getTitle();
        }

        if ($fixture->hasYear()) {
            $engineParameters['year'] = $fixture->getYear();
        }

        $path = $engine->render($tplPathName, $engineParameters);
        $file = $engine->render($tplFileName, $engineParameters);
        $out = preg_replace('{[/+]}', '/', $outputPath.'/'.$path.'/'.$file);

        $outPath = pathinfo($out, PATHINFO_DIRNAME);

        $this->io()->text([
            '[ '.$fixture->getId().' ] '.$fixture->getName().' S'.$fixture->getSeasonNumber().' E'.$fixture->getEpisodeNumberStart().'',
            '',
            'Source : '.$fixture->getFile()->getRealPath(),
            'Dest   : '.$out
        ]);

        @mkdir($outPath, 0777, true);
        rename($fixture->getFile()->getRealPath(), $out);

        $this->io()->text(['', '<bg=green;fg=black> Successfully moved item id '.$fixture->getId().' </>', '']);
    }

    /**
     * @return \Twig_Environment
     */
    public function getTwig()
    {
        $twig = new \Twig_Environment();
        $twig->setCache(false);

        return $twig;
    }
}