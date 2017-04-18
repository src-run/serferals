<?php

/*
 * This file is part of the `src-run/serferals` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Serferals\Component\Model;

class EngineEnvironment
{
    /**
     * @var string
     */
    private $pathName;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var \Twig_Environment
     */
    private $engine;

    /**
     * @var mixed[]
     */
    private $parameters = [];

    /**
     * @param \Twig_Environment $engine
     * @param string            $pathName
     * @param string            $fileName
     */
    public function __construct(\Twig_Environment $engine, string $pathName, string $fileName)
    {
        $this->engine = $engine;
        $this->pathName = $pathName;
        $this->fileName = $fileName;
    }

    /**
     * @return \Twig_Environment
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return array|\mixed[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameter
     *
     * @return $this
     */
    public function setParameters(array $parameter)
    {
        $this->parameters = $parameter;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathName()
    {
        return $this->pathName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }
}
