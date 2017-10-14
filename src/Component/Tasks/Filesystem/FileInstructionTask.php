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

use SR\Exception\Logic\InvalidArgumentException;
use SR\Exception\Runtime\RuntimeException;
use SR\Serferals\Component\Model\EngineEnvironment;
use SR\Serferals\Component\Model\FileMoveInstruction;
use SR\Serferals\Component\Model\SubtitleMetadataModel;
use SR\Spl\File\SplFileInfo as FileInfo;
use SR\Serferals\Component\Model\MediaMetadataModel;
use SR\Serferals\Component\Model\EpisodeMetadataModel;
use SR\Serferals\Component\Model\MovieMetadataModel;

class FileInstructionTask
{
    /**
     * @var string
     */
    protected $outputPath;

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
     * @param string $outputPath
     *
     * @return $this
     */
    public function setOutputPath(string $outputPath)
    {
        $this->outputPath = $outputPath;

        return $this;
    }

    /**
     * @param MovieMetadataModel[]|EpisodeMetadataModel[] $files
     *
     * @return FileMoveInstruction[]
     */
    public function execute(array $files)
    {
        if (0 === count($files)) {
            throw new RuntimeException('No input files provided.');
        }

        return array_map([$this, 'generateInstruction'], $files);
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     *
     * @return FileMoveInstruction
     */
    private function generateInstruction(MediaMetadataModel $media)
    {
        $media->setName($this->sanitizeName($media->getName()));

        if ($media instanceof MovieMetadataModel) {
            $environment = $this->setupTemplateEngineMovie($media);
        }

        if ($media instanceof EpisodeMetadataModel) {
            $environment = $this->setupTemplateEngineEpisode($media);
        }

        if (!isset($environment)) {
            throw new InvalidArgumentException('Could not create template environment for invalid fixture type.');
        }

        $path = $environment->getEngine()->render($environment->getPathName(), $environment->getParameters());
        $file = $environment->getEngine()->render($environment->getFileName(), $environment->getParameters());

        $output = new FileInfo(preg_replace('{[/]+}', '/', $this->outputPath.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file));
        $origin = new FileInfo($media->getFile()->getRealPath());

        return new FileMoveInstruction($origin, $output, $this->generateSubtitleInstruction($media));
    }

    /**
     * @param MediaMetadataModel $media
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     *
     * @return null|FileMoveInstruction
     */
    private function generateSubtitleInstruction(MediaMetadataModel $media): ?FileMoveInstruction
    {
        if (!$media->hasActiveSubtitle()) {
            return null;
        }

        $media->setName($this->sanitizeName($media->getName()));

        if ($media instanceof MovieMetadataModel) {
            $environment = $this->setupTemplateEngineMovie($media);
        }

        if ($media instanceof EpisodeMetadataModel) {
            $environment = $this->setupTemplateEngineEpisode($media);
        }

        if (!isset($environment)) {
            throw new InvalidArgumentException('Could not create template environment for invalid fixture type.');
        }

        $environment->setParameter('ext', $this->sanitizeExtension($media->getActiveSubtitle()->getFile()));

        $path = $environment->getEngine()->render($environment->getPathName(), $environment->getParameters());
        $file = $environment->getEngine()->render($environment->getFileName(), $environment->getParameters());

        $output = new FileInfo(preg_replace('{[/]+}', '/', $this->outputPath.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR.$file));
        $origin = new FileInfo($media->getActiveSubtitle()->getFile()->getRealPath());

        return new FileMoveInstruction($origin, $output);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function sanitizeName(string $name)
    {
        $name = str_replace(DIRECTORY_SEPARATOR, '-', $name);
        $name = html_entity_decode($name, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $name;
    }

    /**
     * @param int $int
     *
     * @return string
     */
    private function zeroPadInteger(int $int)
    {
        return str_pad($int, 2, 0, STR_PAD_LEFT);
    }

    /**
     * @param FileInfo $file
     *
     * @return string
     */
    private function sanitizeExtension(FileInfo $file)
    {
        return strtolower(pathinfo($file->getRealPath(), PATHINFO_EXTENSION));
    }

    /**
     * @return \Twig_Environment
     */
    private function getTemplateEngine()
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Array([]));
        $twig->setCache(false);

        return $twig;
    }

    /**
     * @param MediaMetadataModel $metadata
     * @param string             $pathTemplate
     * @param string             $fileTemplate
     *
     * @return EngineEnvironment
     */
    private function setupTemplateEngine(MediaMetadataModel $metadata, string $pathTemplate, string $fileTemplate)
    {
        $template = sprintf('_template_%s', hash('sha512', implode(':', [
            $metadata->getFile()->getRealPath(),
            $metadata->getFile()->getSize(),
            $metadata->getFile()->getTimeAccessed()->format('r'),
        ])));

        $pathName = sprintf('%s_path', $template);
        $fileName = sprintf('%s_file', $template);

        $engine = $this->getTemplateEngine();
        $engine->setLoader(new \Twig_Loader_Array([
            $pathName => $pathTemplate,
            $fileName => $fileTemplate,
        ]));

        return new EngineEnvironment($engine, $pathName, $fileName);
    }

    /**
     * @param EpisodeMetadataModel $metadata
     *
     * @return EngineEnvironment
     */
    private function setupTemplateEngineEpisode(EpisodeMetadataModel $metadata)
    {
        $environment = $this->setupTemplateEngine($metadata, $this->tplPathEpisode, $this->tplFileEpisode);

        $parameters = [
            'name'   => $metadata->getName(),
            'season' => $this->zeroPadInteger($metadata->getSeasonNumber()),
            'start'  => $this->zeroPadInteger($metadata->getEpisodeNumberStart()),
            'ext'    => $this->sanitizeExtension($metadata->getFile()),
        ];

        if ($metadata->hasTitle()) {
            $parameters['title'] = $metadata->getTitle();
        }

        if ($metadata->hasYear()) {
            $parameters['year'] = $metadata->getYear();
        }

        return $environment->setParameters($parameters);
    }

    /**
     * @param MovieMetadataModel  $metadata
     *
     * @return EngineEnvironment
     */
    private function setupTemplateEngineMovie(MovieMetadataModel $metadata)
    {
        $environment = $this->setupTemplateEngine($metadata, $this->tplPathMovie, $this->tplFileMovie);

        $parameters = [
            'name' => $metadata->getName(),
            'ext'  => $this->sanitizeExtension($metadata->getFile()),
        ];

        if ($metadata->hasId()) {
            $parameters['id'] = $metadata->getId();
        }

        if ($metadata->hasYear()) {
            $parameters['year'] = $metadata->getYear();
        }

        return $environment->setParameters($parameters);
    }
}
